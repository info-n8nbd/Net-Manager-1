<?php

namespace App\Services;

use App\Models\IpPool;
use App\Models\IpAllocation;
use Illuminate\Support\Facades\DB;

class IpamService
{
    public function createPool(array $data): IpPool
    {
        $this->validateNoOverlap($data['network_address'], $data['cidr_prefix'], $data['ip_version']);
        $totalIps = $this->calculateTotalIps($data['cidr_prefix'], $data['ip_version']);
        return IpPool::create(array_merge($data, ['total_ips' => $totalIps]));
    }

    public function validateNoOverlap(string $network, int $cidr, string $version, ?int $excludePoolId = null): bool
    {
        $pools = IpPool::where('ip_version', $version)
            ->when($excludePoolId, fn($q) => $q->where('id', '!=', $excludePoolId))
            ->get();

        foreach ($pools as $pool) {
            if ($this->networksOverlap($network, $cidr, $pool->network_address, $pool->cidr_prefix, $version)) {
                throw new \Exception("Network {$network}/{$cidr} overlaps with existing pool: {$pool->name} ({$pool->network_address}/{$pool->cidr_prefix})");
            }
        }
        return true;
    }

    protected function networksOverlap(string $net1, int $cidr1, string $net2, int $cidr2, string $version): bool
    {
        if ($version === 'ipv6') {
            return $this->ipv6NetworksOverlap($net1, $cidr1, $net2, $cidr2);
        }

        $net1Long = ip2long($net1);
        $net2Long = ip2long($net2);
        $mask1 = -1 << (32 - $cidr1);
        $mask2 = -1 << (32 - $cidr2);
        $start1 = $net1Long & $mask1;
        $end1 = $start1 | ~$mask1;
        $start2 = $net2Long & $mask2;
        $end2 = $start2 | ~$mask2;

        return $start1 <= $end2 && $start2 <= $end1;
    }

    protected function ipv6NetworksOverlap(string $net1, int $cidr1, string $net2, int $cidr2): bool
    {
        $bin1 = $this->ipv6ToBinary($net1);
        $bin2 = $this->ipv6ToBinary($net2);
        $minCidr = min($cidr1, $cidr2);
        return substr($bin1, 0, $minCidr) === substr($bin2, 0, $minCidr);
    }

    protected function ipv6ToBinary(string $ip): string
    {
        $expanded = inet_pton($ip);
        $binary = '';
        foreach (str_split($expanded) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        return $binary;
    }

    public function calculateTotalIps(int $cidr, string $version): int
    {
        if ($version === 'ipv6') {
            return min(pow(2, 128 - $cidr), 65536);
        }
        return max(0, pow(2, 32 - $cidr) - 2);
    }

    public function allocateIp(int $poolId, ?int $clientId = null, ?string $specificIp = null): IpAllocation
    {
        return DB::transaction(function () use ($poolId, $clientId, $specificIp) {
            $pool = IpPool::lockForUpdate()->findOrFail($poolId);

            if ($specificIp) {
                $existing = IpAllocation::where('ip_pool_id', $poolId)->where('ip_address', $specificIp)->first();
                if ($existing && $existing->status !== 'available') {
                    throw new \Exception("IP {$specificIp} is already in use");
                }
                if ($existing) {
                    $existing->update(['status' => 'assigned', 'client_id' => $clientId, 'assigned_by' => auth()->id(), 'assigned_at' => now()]);
                    $pool->increment('used_ips');
                    return $existing->fresh();
                }
            }

            $ipAddress = $specificIp ?? $this->getNextAvailableIp($pool);
            if (!$ipAddress) {
                throw new \Exception("No available IPs in pool {$pool->name}");
            }

            $allocation = IpAllocation::create([
                'ip_pool_id' => $poolId,
                'ip_address' => $ipAddress,
                'status' => $clientId ? 'assigned' : 'reserved',
                'client_id' => $clientId,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);

            $pool->increment('used_ips');
            return $allocation;
        });
    }

    public function releaseIp(int $allocationId): bool
    {
        return DB::transaction(function () use ($allocationId) {
            $allocation = IpAllocation::lockForUpdate()->findOrFail($allocationId);
            $allocation->update(['status' => 'available', 'client_id' => null, 'released_at' => now()]);
            IpPool::where('id', $allocation->ip_pool_id)->decrement('used_ips');
            return true;
        });
    }

    protected function getNextAvailableIp(IpPool $pool): ?string
    {
        if ($pool->ip_version === 'ipv6') {
            return $this->getNextAvailableIpv6($pool);
        }

        $networkLong = ip2long($pool->network_address);
        $totalIps = pow(2, 32 - $pool->cidr_prefix);
        $usedIps = IpAllocation::where('ip_pool_id', $pool->id)
            ->where('status', '!=', 'available')
            ->pluck('ip_address')
            ->map(fn($ip) => ip2long($ip))
            ->toArray();

        for ($i = 1; $i < $totalIps - 1; $i++) {
            $candidateLong = $networkLong + $i;
            if (!in_array($candidateLong, $usedIps)) {
                return long2ip($candidateLong);
            }
        }
        return null;
    }

    protected function getNextAvailableIpv6(IpPool $pool): ?string
    {
        $usedIps = IpAllocation::where('ip_pool_id', $pool->id)
            ->where('status', '!=', 'available')
            ->pluck('ip_address')
            ->toArray();

        $baseAddr = inet_pton($pool->network_address);
        $addrArray = array_values(unpack('C*', $baseAddr));

        for ($i = 1; $i <= min($pool->total_ips, 1000); $i++) {
            $testAddr = $addrArray;
            $carry = $i;
            for ($j = 15; $j >= 0 && $carry > 0; $j--) {
                $testAddr[$j] += $carry;
                $carry = intdiv($testAddr[$j], 256);
                $testAddr[$j] = $testAddr[$j] % 256;
            }
            $packed = pack('C*', ...$testAddr);
            $candidateIp = inet_ntop($packed);
            if (!in_array($candidateIp, $usedIps)) {
                return $candidateIp;
            }
        }
        return null;
    }

    public function getPoolUtilization(int $poolId): array
    {
        $pool = IpPool::withCount([
            'allocations as assigned_count' => fn($q) => $q->where('status', 'assigned'),
            'allocations as reserved_count' => fn($q) => $q->where('status', 'reserved'),
        ])->findOrFail($poolId);

        return [
            'total' => $pool->total_ips,
            'assigned' => $pool->assigned_count,
            'reserved' => $pool->reserved_count,
            'available' => $pool->total_ips - $pool->assigned_count - $pool->reserved_count,
            'utilization_percent' => $pool->total_ips > 0 ? round(($pool->assigned_count / $pool->total_ips) * 100, 2) : 0,
        ];
    }
}
