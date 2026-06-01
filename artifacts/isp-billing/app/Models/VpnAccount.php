<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class VpnAccount extends Model
{
    use TenantAware;

    protected $fillable = [
        'name', 'account_type', 'client_id', 'vpn_type', 'username', 'password',
        'local_address', 'remote_address', 'server_address', 'dns_server', 'profile',
        'service', 'is_active', 'mikrotik_device_id', 'mikrotik_id',
        'monthly_fee', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'monthly_fee' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function mikrotikDevice()
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    public function linkedRouters()
    {
        return $this->hasMany(MikrotikDevice::class, 'vpn_account_id');
    }

    public static function getVpnTypes(): array
    {
        return [
            'l2tp' => 'L2TP/IPSec',
            'pptp' => 'PPTP',
            'sstp' => 'SSTP',
            'ovpn' => 'OpenVPN',
        ];
    }

    public static function getAccountTypes(): array
    {
        return [
            'main_router' => 'Main Router',
            'branch_router' => 'Branch Router',
            'client' => 'Client VPN',
        ];
    }

    public function isManagementTunnel(): bool
    {
        return in_array($this->account_type, ['main_router', 'branch_router']);
    }

    public function getLinkedRouterCount(): int
    {
        return $this->linkedRouters()->count();
    }

    public function getUniqueTag(): string
    {
        return 'vpn-' . $this->id . '-' . substr(md5($this->username), 0, 8);
    }

    public function getProfileName(): string
    {
        return 'vpn-profile-' . $this->getUniqueTag();
    }

    public function getInterfaceName(): string
    {
        $short = 'sstp-' . $this->id;
        return substr($short, 0, 15);
    }

    public function generateMikrotikCommands(): array
    {
        $tag = $this->getUniqueTag();
        $profileName = $this->getProfileName();
        $interfaceName = $this->getInterfaceName();
        $serverAddress = $this->server_address ?: SystemSetting::get('vpn_server_domain', 'vpn.yourisp.com');
        $serverPort = SystemSetting::get('vpn_server_port', '443');
        $connectTo = $serverAddress . ':' . $serverPort;
        $vpnNetwork = SystemSetting::get('vpn_network_range', '100.127.240.0/20');

        $localGateway = $this->local_address ?: SystemSetting::get('vpn_local_gateway', '100.127.224.1');
        $tunnelIp = $this->remote_address ?: '100.127.230.1';

        $commands = [];

        $commands['profile'] = "/ppp profile\n"
            . "remove [find name=\"{$profileName}\"]\n"
            . "add local-address=\"{$tunnelIp}\" name=\"{$profileName}\" remote-address=\"{$localGateway}\"";

        $commands['interface'] = "/interface sstp-client\n"
            . "remove [find comment=\"{$tag}\"]\n"
            . "add comment=\"{$tag}\" connect-to=\"{$connectTo}\" disabled=no name=\"{$interfaceName}\" password=\"{$this->password}\" profile=\"{$profileName}\" user=\"{$this->username}\"";

        $commands['nat'] = "/ip firewall nat\n"
            . "remove [find comment=\"vpn-nat-{$tag}\"]\n"
            . "add action=masquerade chain=srcnat comment=\"vpn-nat-{$tag}\" place-before=0 dst-address=\"{$vpnNetwork}\"";

        $commands['route'] = "/ip route\n"
            . "remove [find comment=\"vpn-route-{$tag}\"]\n"
            . "add comment=\"vpn-route-{$tag}\" distance=1 dst-address=\"{$vpnNetwork}\" gateway=\"{$localGateway}\"";

        $commands['full'] = implode("\n", [$commands['profile'], $commands['interface'], $commands['nat'], $commands['route']]);

        return $commands;
    }

    public static function getNextAvailableIp(): string
    {
        $baseOctet3Start = intval(SystemSetting::get('vpn_ip_start_octet3', '230'));
        $baseOctet4Start = intval(SystemSetting::get('vpn_ip_start_octet4', '1'));

        $usedIps = static::whereNotNull('remote_address')
            ->pluck('remote_address')
            ->toArray();

        for ($o3 = $baseOctet3Start; $o3 <= 255; $o3++) {
            $start4 = ($o3 === $baseOctet3Start) ? $baseOctet4Start : 1;
            for ($o4 = $start4; $o4 <= 254; $o4++) {
                $ip = "100.127.{$o3}.{$o4}";
                if (!in_array($ip, $usedIps)) {
                    return $ip;
                }
            }
        }

        return '100.127.230.1';
    }

    public static function generateRandomCredentials(): array
    {
        $username = bin2hex(random_bytes(10));
        $password = bin2hex(random_bytes(8));
        return ['username' => $username, 'password' => $password];
    }
}
