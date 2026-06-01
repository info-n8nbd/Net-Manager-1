<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    protected string $backupPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    protected function getDbCredentials(): array
    {
        $dbUrl = env('DATABASE_URL');
        if ($dbUrl) {
            $parts = parse_url($dbUrl);
            return [
                'host' => $parts['host'] ?? 'localhost',
                'port' => $parts['port'] ?? 5432,
                'dbname' => ltrim($parts['path'] ?? '', '/'),
                'user' => $parts['user'] ?? '',
                'pass' => $parts['pass'] ?? '',
            ];
        }

        return [
            'host' => env('DB_HOST', env('PGHOST', '127.0.0.1')),
            'port' => env('DB_PORT', env('PGPORT', 5432)),
            'dbname' => env('DB_DATABASE', env('PGDATABASE', 'postgres')),
            'user' => env('DB_USERNAME', env('PGUSER', 'postgres')),
            'pass' => env('DB_PASSWORD', env('PGPASSWORD', '')),
        ];
    }

    protected function findBinary(string $name): string
    {
        $output = [];
        exec("which $name 2>/dev/null", $output, $code);
        if ($code === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        $commonPaths = glob("/nix/store/*/bin/$name");
        if (!empty($commonPaths)) {
            return $commonPaths[0];
        }

        return $name;
    }

    public function createDatabaseBackup(?string $name = null): Backup
    {
        $name = $name ?? 'db-backup-' . date('Y-m-d-His');
        $filename = $name . '.sql';
        $filepath = $this->backupPath . '/' . $filename;

        $backup = Backup::create([
            'name' => $name,
            'type' => 'database',
            'file_path' => $filepath,
            'status' => 'running',
            'created_by' => auth()->id(),
        ]);

        try {
            $creds = $this->getDbCredentials();
            $pgDump = $this->findBinary('pg_dump');
            $errFile = tempnam(sys_get_temp_dir(), 'pgdump_err_');

            $cmd = sprintf(
                'PGPASSWORD=%s %s -h %s -p %d -U %s -d %s --no-owner --no-acl -f %s 2>%s',
                escapeshellarg($creds['pass']),
                escapeshellarg($pgDump),
                escapeshellarg($creds['host']),
                (int)$creds['port'],
                escapeshellarg($creds['user']),
                escapeshellarg($creds['dbname']),
                escapeshellarg($filepath),
                escapeshellarg($errFile)
            );

            exec($cmd, $output, $returnCode);

            $errOutput = file_exists($errFile) ? trim(file_get_contents($errFile)) : '';
            @unlink($errFile);

            if ($returnCode !== 0) {
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
                throw new \Exception('pg_dump failed (code ' . $returnCode . '): ' . ($errOutput ?: implode("\n", $output)));
            }

            if (!file_exists($filepath) || filesize($filepath) === 0) {
                throw new \Exception('pg_dump produced empty output. ' . $errOutput);
            }

            $fileSize = filesize($filepath);

            $backup->update([
                'status' => 'completed',
                'file_size' => $fileSize,
                'completed_at' => now(),
                'metadata' => [
                    'tables' => $this->getTableCount(),
                    'database' => $creds['dbname'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $backup;
    }

    public function createConfigBackup(?string $name = null): Backup
    {
        $name = $name ?? 'config-backup-' . date('Y-m-d-His');
        $filename = $name . '.tar.gz';
        $filepath = $this->backupPath . '/' . $filename;

        $backup = Backup::create([
            'name' => $name,
            'type' => 'config',
            'file_path' => $filepath,
            'status' => 'running',
            'created_by' => auth()->id(),
        ]);

        try {
            $cmd = sprintf(
                'tar -czf %s -C %s .env config/ 2>&1',
                escapeshellarg($filepath),
                escapeshellarg(base_path())
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Config backup failed: ' . implode("\n", $output));
            }

            $backup->update([
                'status' => 'completed',
                'file_size' => file_exists($filepath) ? filesize($filepath) : 0,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Config backup failed: ' . $e->getMessage());
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $backup;
    }

    public function restoreDatabase(Backup $backup): bool
    {
        if ($backup->type !== 'database' || $backup->status !== 'completed') {
            return false;
        }

        if (!file_exists($backup->file_path)) {
            return false;
        }

        try {
            $creds = $this->getDbCredentials();
            $psql = $this->findBinary('psql');
            $errFile = tempnam(sys_get_temp_dir(), 'psql_err_');

            $cmd = sprintf(
                'PGPASSWORD=%s %s -h %s -p %d -U %s -d %s -f %s 2>%s',
                escapeshellarg($creds['pass']),
                escapeshellarg($psql),
                escapeshellarg($creds['host']),
                (int)$creds['port'],
                escapeshellarg($creds['user']),
                escapeshellarg($creds['dbname']),
                escapeshellarg($backup->file_path),
                escapeshellarg($errFile)
            );

            exec($cmd, $output, $returnCode);

            $errOutput = file_exists($errFile) ? trim(file_get_contents($errFile)) : '';
            @unlink($errFile);

            if ($returnCode !== 0) {
                Log::error('Restore failed (code ' . $returnCode . '): ' . $errOutput);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Restore failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteBackup(Backup $backup): void
    {
        if ($backup->file_path && file_exists($backup->file_path)) {
            unlink($backup->file_path);
        }
        $backup->delete();
    }

    public function getBackups(): \Illuminate\Database\Eloquent\Collection
    {
        return Backup::orderBy('created_at', 'desc')->get();
    }

    public function cleanOldBackups(int $keepDays = 30): int
    {
        $cutoff = now()->subDays($keepDays);
        $oldBackups = Backup::where('created_at', '<', $cutoff)->get();
        $count = 0;

        foreach ($oldBackups as $backup) {
            $this->deleteBackup($backup);
            $count++;
        }

        return $count;
    }

    protected function getTableCount(): int
    {
        try {
            $result = \DB::select("SELECT count(*) as count FROM pg_tables WHERE schemaname = 'public'");
            return $result[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function downloadBackup(Backup $backup): ?string
    {
        if ($backup->file_path && file_exists($backup->file_path)) {
            return $backup->file_path;
        }
        return null;
    }
}
