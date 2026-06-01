<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AppInfoController extends Controller
{
    public function index()
    {
        $info = [
            'app'      => $this->getAppInfo(),
            'stack'    => $this->getTechStack(),
            'frontend' => $this->getFrontendStack(),
            'packages' => $this->getKeyPackages(),
            'stats'    => $this->getCodeStats(),
            'runtime'  => $this->getRuntimeInfo(),
            'server'   => $this->getServerInfo(),
        ];
        return view('admin.system.app-info', compact('info'));
    }

    private function getAppInfo(): array
    {
        return [
            'name'        => config('app.name', 'SOFTYNEX'),
            'full_name'   => 'SOFTYNEX — Smart ISP Management System',
            'purpose'     => 'Complete ISP billing, MikroTik/OLT management, client & invoice management, SMS/Email/Telegram notifications, and network monitoring platform.',
            'environment' => config('app.env', 'production'),
            'url'         => config('app.url', ''),
            'locale'      => config('app.locale', 'en'),
            'timezone'    => config('app.timezone', 'UTC'),
            'debug'       => config('app.debug', false) ? 'Enabled' : 'Disabled',
        ];
    }

    private function getTechStack(): array
    {
        return [
            [
                'icon'    => 'fab fa-php',
                'color'   => '#8993be',
                'label'   => 'PHP',
                'version' => PHP_VERSION,
                'desc'    => 'Server-side scripting language',
            ],
            [
                'icon'    => 'fas fa-layer-group',
                'color'   => '#ff2d20',
                'label'   => 'Laravel',
                'version' => app()->version(),
                'desc'    => 'PHP web application framework',
            ],
            [
                'icon'    => 'fas fa-database',
                'color'   => '#336791',
                'label'   => 'PostgreSQL',
                'version' => $this->getPostgresVersion(),
                'desc'    => 'Relational database',
            ],
            [
                'icon'    => 'fas fa-memory',
                'color'   => '#dc382d',
                'label'   => 'Redis',
                'version' => $this->getRedisVersion(),
                'desc'    => 'Cache & session store',
            ],
            [
                'icon'    => 'fas fa-server',
                'color'   => '#269539',
                'label'   => 'Nginx',
                'version' => $this->getNginxVersion(),
                'desc'    => 'Web server & reverse proxy',
            ],
            [
                'icon'    => 'fab fa-ubuntu',
                'color'   => '#e95420',
                'label'   => 'Ubuntu',
                'version' => '24.04.4 LTS',
                'desc'    => 'Operating system',
            ],
        ];
    }

    private function getFrontendStack(): array
    {
        return [
            ['label' => 'AdminLTE',      'version' => '3.2',    'color' => '#3c8dbc', 'desc' => 'Admin dashboard template'],
            ['label' => 'Bootstrap',     'version' => '4.x',    'color' => '#7952b3', 'desc' => 'CSS framework'],
            ['label' => 'jQuery',        'version' => '3.x',    'color' => '#0769ad', 'desc' => 'JavaScript library'],
            ['label' => 'DataTables',    'version' => '1.13.6', 'color' => '#00b0f0', 'desc' => 'Interactive tables'],
            ['label' => 'Chart.js',      'version' => '4.x',    'color' => '#ff6384', 'desc' => 'Data visualization'],
            ['label' => 'Font Awesome',  'version' => '5.x',    'color' => '#339af0', 'desc' => 'Icon library'],
            ['label' => 'Select2',       'version' => '4.x',    'color' => '#adb5bd', 'desc' => 'Enhanced select boxes'],
            ['label' => 'SweetAlert2',   'version' => '11.x',   'color' => '#f27474', 'desc' => 'Beautiful alerts'],
        ];
    }

    private function getKeyPackages(): array
    {
        return [
            ['name' => 'laravel/framework',           'version' => '^12.0',  'category' => 'Core',          'desc' => 'Laravel core framework'],
            ['name' => 'spatie/laravel-permission',   'version' => '^6.24',  'category' => 'Auth',          'desc' => 'Role & permission management'],
            ['name' => 'barryvdh/laravel-dompdf',     'version' => '^3.1',   'category' => 'PDF',           'desc' => 'PDF generation for invoices'],
            ['name' => 'laravel/sanctum',             'version' => '^4.3',   'category' => 'API Auth',      'desc' => 'API token authentication'],
            ['name' => 'predis/predis',               'version' => '^2.x',   'category' => 'Cache',         'desc' => 'Redis client for PHP'],
            ['name' => 'telegram-bot/sdk',            'version' => '^3.x',   'category' => 'Notification',  'desc' => 'Telegram bot notifications'],
            ['name' => 'php-snmp',                    'version' => 'ext',    'category' => 'OLT',           'desc' => 'SNMP for OLT monitoring'],
            ['name' => 'routeros-api',                'version' => 'custom', 'category' => 'MikroTik',      'desc' => 'MikroTik RouterOS API'],
        ];
    }

    private function getCodeStats(): array
    {
        $base = base_path();
        return [
            ['label' => 'Controllers', 'icon' => 'fas fa-cogs',       'color' => '#007bff', 'count' => $this->countFiles($base . '/app/Http/Controllers', '*.php')],
            ['label' => 'Models',      'icon' => 'fas fa-cubes',       'color' => '#28a745', 'count' => $this->countFiles($base . '/app/Models', '*.php')],
            ['label' => 'Services',    'icon' => 'fas fa-tools',       'color' => '#fd7e14', 'count' => $this->countFiles($base . '/app/Services', '*.php')],
            ['label' => 'Jobs',        'icon' => 'fas fa-tasks',       'color' => '#6f42c1', 'count' => $this->countFiles($base . '/app/Jobs', '*.php')],
            ['label' => 'Migrations',  'icon' => 'fas fa-database',    'color' => '#17a2b8', 'count' => $this->countFiles($base . '/database/migrations', '*.php')],
            ['label' => 'Blade Views', 'icon' => 'fas fa-file-code',   'color' => '#e83e8c', 'count' => $this->countFiles($base . '/resources/views', '*.blade.php', true)],
        ];
    }

    private function getRuntimeInfo(): array
    {
        $memLimit = ini_get('memory_limit');
        $memUsed  = round(memory_get_usage(true) / 1048576, 2) . ' MB';

        try {
            $sessions = \DB::table('sessions')->count();
        } catch (\Throwable $e) {
            $sessions = 'N/A';
        }
        try {
            $pendingJobs = \DB::table('jobs')->count();
        } catch (\Throwable $e) {
            $pendingJobs = 'N/A';
        }

        return [
            ['label' => 'PHP Version',       'value' => PHP_VERSION,                         'icon' => 'fab fa-php',           'color' => '#8993be'],
            ['label' => 'Laravel Version',   'value' => app()->version(),                    'icon' => 'fas fa-layer-group',   'color' => '#ff2d20'],
            ['label' => 'Environment',       'value' => strtoupper(config('app.env', 'production')), 'icon' => 'fas fa-globe', 'color' => '#28a745'],
            ['label' => 'Debug Mode',        'value' => config('app.debug') ? 'ON' : 'OFF',  'icon' => 'fas fa-bug',           'color' => config('app.debug') ? '#dc3545' : '#6c757d'],
            ['label' => 'Memory Usage',      'value' => $memUsed,                            'icon' => 'fas fa-memory',        'color' => '#17a2b8'],
            ['label' => 'Memory Limit',      'value' => $memLimit === '-1' ? 'Unlimited' : $memLimit, 'icon' => 'fas fa-microchip', 'color' => '#fd7e14'],
            ['label' => 'Active Sessions',   'value' => $sessions,                           'icon' => 'fas fa-users',         'color' => '#007bff'],
            ['label' => 'Pending Queue Jobs','value' => $pendingJobs,                        'icon' => 'fas fa-clock',         'color' => '#6f42c1'],
            ['label' => 'Timezone',          'value' => config('app.timezone', 'UTC'),        'icon' => 'fas fa-clock',         'color' => '#20c997'],
            ['label' => 'PHP Extensions',    'value' => count(get_loaded_extensions()) . ' loaded', 'icon' => 'fas fa-puzzle-piece', 'color' => '#343a40'],
        ];
    }

    private function getServerInfo(): array
    {
        $uptime = 'Unknown';
        try {
            $secs  = (int) explode(' ', (string)file_get_contents('/proc/uptime'))[0];
            $d     = floor($secs / 86400);
            $h     = floor(($secs % 86400) / 3600);
            $m     = floor(($secs % 3600) / 60);
            $uptime = "{$d}d {$h}h {$m}m";
        } catch (\Throwable $e) {}

        return [
            ['label' => 'Hostname',        'value' => gethostname() ?: 'Unknown',             'icon' => 'fas fa-server'],
            ['label' => 'OS',              'value' => 'Ubuntu 24.04.4 LTS',                   'icon' => 'fab fa-ubuntu'],
            ['label' => 'Web Server',      'value' => 'Nginx 1.24.0',                         'icon' => 'fas fa-globe'],
            ['label' => 'Server Uptime',   'value' => $uptime,                                'icon' => 'fas fa-clock'],
            ['label' => 'PHP-FPM',         'value' => 'PHP 8.4 FPM',                          'icon' => 'fas fa-cog'],
            ['label' => 'Database',        'value' => 'PostgreSQL 16.13',                     'icon' => 'fas fa-database'],
            ['label' => 'Cache',           'value' => 'Redis 7.0.15',                         'icon' => 'fas fa-bolt'],
            ['label' => 'SSL',             'value' => 'Let\'s Encrypt (HTTPS)',                'icon' => 'fas fa-lock'],
        ];
    }

    private function countFiles(string $dir, string $pattern, bool $recursive = false): int
    {
        if (!is_dir($dir)) return 0;
        $flag = $recursive ? '-r' : '';
        $out  = shell_exec("find " . escapeshellarg($dir) . " -name " . escapeshellarg($pattern) . " 2>/dev/null | wc -l");
        return (int)trim((string)$out);
    }

    private function getPostgresVersion(): string
    {
        try {
            $r = \DB::select('SELECT version()');
            preg_match('/PostgreSQL\s+([\d.]+)/', $r[0]->version, $m);
            return $m[1] ?? '16.13';
        } catch (\Throwable $e) {}
        return '16.13';
    }

    private function getRedisVersion(): string
    {
        try {
            $info = \Cache::getRedis()->info('server');
            return $info['redis_version'] ?? '7.0.15';
        } catch (\Throwable $e) {}
        return '7.0.15';
    }

    private function getNginxVersion(): string
    {
        $out = trim((string)shell_exec('nginx -v 2>&1'));
        preg_match('/nginx\/([\d.]+)/', $out, $m);
        return $m[1] ?? '1.24.0';
    }
}
