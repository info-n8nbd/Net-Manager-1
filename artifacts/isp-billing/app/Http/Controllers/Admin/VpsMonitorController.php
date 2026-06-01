<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class VpsMonitorController extends Controller
{
    public function index()
    {
        $data = $this->getVpsStats();
        return view('admin.system.vps-monitor', compact('data'));
    }

    public function stats()
    {
        return response()->json($this->getVpsStats());
    }

    private function getVpsStats(): array
    {
        return [
            'cpu'      => $this->getCpuUsage(),
            'ram'      => $this->getRamUsage(),
            'swap'     => $this->getSwapUsage(),
            'disk'     => $this->getDiskUsage(),
            'load'     => $this->getLoadAverage(),
            'uptime'   => $this->getUptime(),
            'network'  => $this->getNetworkStats(),
            'services' => $this->getServiceStatus(),
            'top'      => $this->getTopProcesses(),
            'db_size'  => $this->getDatabaseSize(),
            'app_size' => $this->getAppSize(),
            'os'       => $this->getOsInfo(),
        ];
    }

    private function getCpuUsage(): array
    {
        $cpu = 0;
        try {
            $s1 = file_get_contents('/proc/stat');
            usleep(300000);
            $s2 = file_get_contents('/proc/stat');
            $c1 = array_map('intval', array_slice(preg_split('/\s+/', trim(explode("\n", $s1)[0])), 1));
            $c2 = array_map('intval', array_slice(preg_split('/\s+/', trim(explode("\n", $s2)[0])), 1));
            $id1 = $c1[3] + ($c1[4] ?? 0);
            $id2 = $c2[3] + ($c2[4] ?? 0);
            $td  = array_sum($c2) - array_sum($c1);
            $idd = $id2 - $id1;
            $cpu = $td > 0 ? round(100 * ($td - $idd) / $td, 1) : 0;
        } catch (\Throwable $e) {}
        $cores = (int) trim((string)(@shell_exec('nproc') ?: '1'));
        return ['usage' => $cpu, 'cores' => $cores];
    }

    private function getRamUsage(): array
    {
        try {
            $info = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $info, $mT);
            preg_match('/MemAvailable:\s+(\d+)/', $info, $mA);
            $total = (int)($mT[1] ?? 0);
            $avail = (int)($mA[1] ?? 0);
            $used  = $total - $avail;
            return [
                'total'   => round($total / 1024, 1),
                'used'    => round($used  / 1024, 1),
                'free'    => round($avail / 1024, 1),
                'percent' => $total > 0 ? round(100 * $used / $total, 1) : 0,
            ];
        } catch (\Throwable $e) {}
        return ['total' => 0, 'used' => 0, 'free' => 0, 'percent' => 0];
    }

    private function getSwapUsage(): array
    {
        try {
            $info = file_get_contents('/proc/meminfo');
            preg_match('/SwapTotal:\s+(\d+)/', $info, $mT);
            preg_match('/SwapFree:\s+(\d+)/',  $info, $mF);
            $total = (int)($mT[1] ?? 0);
            $free  = (int)($mF[1] ?? 0);
            $used  = $total - $free;
            return [
                'total'   => round($total / 1024, 1),
                'used'    => round($used  / 1024, 1),
                'percent' => $total > 0 ? round(100 * $used / $total, 1) : 0,
            ];
        } catch (\Throwable $e) {}
        return ['total' => 0, 'used' => 0, 'percent' => 0];
    }

    private function getDiskUsage(): array
    {
        $disks = [];
        try {
            $raw = (string)shell_exec('df -h --output=source,size,used,avail,pcent,target 2>/dev/null | grep -E "^/dev/"');
            foreach (explode("\n", trim($raw)) as $line) {
                $c = preg_split('/\s+/', trim($line));
                if (count($c) >= 6) {
                    $disks[] = [
                        'device'  => $c[0],
                        'size'    => $c[1],
                        'used'    => $c[2],
                        'free'    => $c[3],
                        'percent' => (int) str_replace('%', '', $c[4]),
                        'mount'   => $c[5],
                    ];
                }
            }
        } catch (\Throwable $e) {}
        return $disks;
    }

    private function getLoadAverage(): array
    {
        try {
            $raw   = file_get_contents('/proc/loadavg');
            $parts = explode(' ', $raw);
            return [(float)$parts[0], (float)$parts[1], (float)$parts[2]];
        } catch (\Throwable $e) {}
        return [0, 0, 0];
    }

    private function getUptime(): string
    {
        try {
            $secs = (int) explode(' ', (string)file_get_contents('/proc/uptime'))[0];
            $d = floor($secs / 86400);
            $h = floor(($secs % 86400) / 3600);
            $m = floor(($secs % 3600)  / 60);
            return "{$d}d {$h}h {$m}m";
        } catch (\Throwable $e) {}
        return 'Unknown';
    }

    private function getNetworkStats(): array
    {
        $net = [];
        try {
            $raw = (string)file_get_contents('/proc/net/dev');
            foreach (explode("\n", trim($raw)) as $line) {
                if (strpos($line, ':') === false) continue;
                [$iface, $stats] = explode(':', $line, 2);
                $iface = trim($iface);
                if ($iface === 'lo') continue;
                $cols  = preg_split('/\s+/', trim($stats));
                $net[] = [
                    'interface' => $iface,
                    'rx_mb'     => round((float)$cols[0] / 1048576, 2),
                    'tx_mb'     => round((float)$cols[8] / 1048576, 2),
                ];
            }
        } catch (\Throwable $e) {}
        return $net;
    }

    private function getServiceStatus(): array
    {
        $services = [
            'Nginx'      => 'nginx',
            'PHP-FPM'    => 'php8.4-fpm',
            'PostgreSQL' => 'postgresql',
            'Redis'      => 'redis-server',
            'Supervisor' => 'supervisor',
        ];
        $result = [];
        foreach ($services as $label => $svc) {
            $out = trim((string)(@shell_exec("systemctl is-active {$svc} 2>/dev/null") ?: 'unknown'));
            $result[] = ['name' => $label, 'status' => $out];
        }
        return $result;
    }

    private function getTopProcesses(): array
    {
        $procs = [];
        try {
            $raw   = (string)shell_exec('ps aux --sort=-%cpu 2>/dev/null | head -11');
            $lines = explode("\n", trim($raw));
            array_shift($lines);
            foreach ($lines as $line) {
                $c = preg_split('/\s+/', trim($line), 11);
                if (count($c) >= 11) {
                    $procs[] = [
                        'user'    => $c[0],
                        'cpu'     => $c[2],
                        'mem'     => $c[3],
                        'command' => mb_substr($c[10], 0, 55),
                    ];
                }
            }
        } catch (\Throwable $e) {}
        return $procs;
    }

    private function getDatabaseSize(): string
    {
        try {
            $r = \DB::select('SELECT pg_size_pretty(pg_database_size(current_database())) as size');
            return $r[0]->size ?? 'Unknown';
        } catch (\Throwable $e) {}
        return 'Unknown';
    }

    private function getAppSize(): array
    {
        $base = '/var/www/smart-isp/isp-app';
        $dirs = [
            'App Code'       => 'app',
            'Vendor (libs)'  => 'vendor',
            'Storage / Logs' => 'storage',
            'Public Assets'  => 'public',
            'Resources'      => 'resources',
            'Config'         => 'config',
        ];
        $breakdown = [];
        foreach ($dirs as $label => $dir) {
            $fullPath = $base . '/' . $dir;
            if (is_dir($fullPath)) {
                $bytes = $this->dirSize($fullPath);
                $breakdown[] = [
                    'label' => $label,
                    'dir'   => $dir,
                    'bytes' => $bytes,
                    'human' => $this->formatBytes($bytes),
                ];
            }
        }
        $totalBytes = $this->dirSize($base);
        return [
            'total'       => $this->formatBytes($totalBytes),
            'total_bytes' => $totalBytes,
            'breakdown'   => $breakdown,
        ];
    }

    private function dirSize(string $path): int
    {
        $output = shell_exec('du -sb ' . escapeshellarg($path) . ' 2>/dev/null');
        if ($output) {
            $parts = explode("\t", trim($output));
            return (int)($parts[0] ?? 0);
        }
        return 0;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
    private function getOsInfo(): string
    {
        try {
            $raw = @file_get_contents("/etc/os-release") ?: "";
            if ($raw && preg_match('/PRETTY_NAME="([^"]+)"/', $raw, $m)) {
                return $m[1];
            }
            $u = trim((string)(@shell_exec("uname -sr 2>/dev/null") ?: ""));
            return $u ?: "Linux";
        } catch (\Throwable $e) {}
        return "Unknown";
    }
}