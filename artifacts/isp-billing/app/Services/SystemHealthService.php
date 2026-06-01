<?php

namespace App\Services;

use App\Models\SystemHealthLog;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SystemHealthService
{
    public function runAllChecks(): array
    {
        $results = [];
        
        $results['database'] = $this->checkDatabase();
        $results['storage'] = $this->checkStorage();
        $results['queue'] = $this->checkQueue();
        $results['sms_api'] = $this->checkSmsApi();
        $results['mikrotik'] = $this->checkMikrotik();
        $results['telegram'] = $this->checkTelegram();
        $results['cron'] = $this->checkCron();
        
        $criticalComponents = ['database', 'storage', 'queue'];
        $hasCriticalFailure = false;
        $hasWarning = false;
        
        foreach ($results as $component => $check) {
            if ($check['status'] === SystemHealthLog::STATUS_ERROR) {
                if (in_array($component, $criticalComponents)) {
                    $hasCriticalFailure = true;
                } else {
                    $hasWarning = true;
                }
            } elseif ($check['status'] === SystemHealthLog::STATUS_WARNING) {
                $hasWarning = true;
            }
        }
        
        $results['overall_status'] = $hasCriticalFailure 
            ? 'critical' 
            : ($hasWarning ? 'degraded' : 'healthy');
        
        $results['checked_at'] = now()->toIso8601String();
        
        return $results;
    }

    public function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            $responseTime = (int) ((microtime(true) - $start) * 1000);
            
            $result = [
                'status' => SystemHealthLog::STATUS_OK,
                'message' => 'Database connection successful',
                'response_time_ms' => $responseTime,
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_DATABASE,
                $result['status'],
                $result['message'],
                $responseTime
            );
            
            return $result;
        } catch (\Exception $e) {
            $result = [
                'status' => SystemHealthLog::STATUS_ERROR,
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'response_time_ms' => null,
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_DATABASE,
                $result['status'],
                $result['message']
            );
            
            return $result;
        }
    }

    public function checkStorage(): array
    {
        $start = microtime(true);
        try {
            $testFile = 'health_check_' . time() . '.txt';
            Storage::disk('local')->put($testFile, 'test');
            Storage::disk('local')->delete($testFile);
            $responseTime = (int) ((microtime(true) - $start) * 1000);
            
            $result = [
                'status' => SystemHealthLog::STATUS_OK,
                'message' => 'Storage read/write successful',
                'response_time_ms' => $responseTime,
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_STORAGE,
                $result['status'],
                $result['message'],
                $responseTime
            );
            
            return $result;
        } catch (\Exception $e) {
            $result = [
                'status' => SystemHealthLog::STATUS_ERROR,
                'message' => 'Storage check failed: ' . $e->getMessage(),
                'response_time_ms' => null,
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_STORAGE,
                $result['status'],
                $result['message']
            );
            
            return $result;
        }
    }

    public function checkQueue(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();
            
            $status = $failedJobs > 10 ? SystemHealthLog::STATUS_WARNING : SystemHealthLog::STATUS_OK;
            $message = "Queue active. Pending: {$pendingJobs}, Failed: {$failedJobs}";
            
            if ($failedJobs > 50) {
                $status = SystemHealthLog::STATUS_ERROR;
                $message = "Too many failed jobs: {$failedJobs}";
            }
            
            $result = [
                'status' => $status,
                'message' => $message,
                'details' => ['pending' => $pendingJobs, 'failed' => $failedJobs],
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_QUEUE,
                $result['status'],
                $result['message'],
                null,
                $result['details']
            );
            
            return $result;
        } catch (\Exception $e) {
            $result = [
                'status' => SystemHealthLog::STATUS_ERROR,
                'message' => 'Queue check failed: ' . $e->getMessage(),
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_QUEUE,
                $result['status'],
                $result['message']
            );
            
            return $result;
        }
    }

    public function checkSmsApi(): array
    {
        $start = microtime(true);
        try {
            $apiKey = env('BULKSMS_API_KEY');
            if (!$apiKey) {
                $result = [
                    'status' => SystemHealthLog::STATUS_WARNING,
                    'message' => 'SMS API key not configured',
                ];
                
                SystemHealthLog::logCheck(
                    SystemHealthLog::COMPONENT_SMS_API,
                    $result['status'],
                    $result['message']
                );
                
                return $result;
            }
            
            $response = Http::timeout(10)->get('https://bulksmsdhaka.com/api/getbalance', [
                'apikey' => $apiKey,
            ]);
            
            $responseTime = (int) ((microtime(true) - $start) * 1000);
            
            if ($response->successful()) {
                $data = $response->json();
                $balance = $data['balance'] ?? 'unknown';
                
                $result = [
                    'status' => SystemHealthLog::STATUS_OK,
                    'message' => "SMS API connected. Balance: {$balance} BDT",
                    'response_time_ms' => $responseTime,
                    'details' => ['balance' => $balance],
                ];
            } else {
                $result = [
                    'status' => SystemHealthLog::STATUS_ERROR,
                    'message' => 'SMS API returned error',
                    'response_time_ms' => $responseTime,
                ];
            }
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_SMS_API,
                $result['status'],
                $result['message'],
                $responseTime,
                $result['details'] ?? null
            );
            
            return $result;
        } catch (\Exception $e) {
            $result = [
                'status' => SystemHealthLog::STATUS_ERROR,
                'message' => 'SMS API check failed: ' . $e->getMessage(),
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_SMS_API,
                $result['status'],
                $result['message']
            );
            
            return $result;
        }
    }

    public function checkMikrotik(): array
    {
        try {
            $devices = MikrotikDevice::where('is_active', true)->get();
            
            if ($devices->isEmpty()) {
                $result = [
                    'status' => SystemHealthLog::STATUS_WARNING,
                    'message' => 'No MikroTik devices configured',
                ];
                
                SystemHealthLog::logCheck(
                    SystemHealthLog::COMPONENT_MIKROTIK,
                    $result['status'],
                    $result['message']
                );
                
                return $result;
            }
            
            $online = $devices->where('status', 'online')->count();
            $total = $devices->count();
            
            $status = $online === $total ? SystemHealthLog::STATUS_OK : 
                     ($online > 0 ? SystemHealthLog::STATUS_WARNING : SystemHealthLog::STATUS_ERROR);
            
            $result = [
                'status' => $status,
                'message' => "MikroTik devices: {$online}/{$total} online",
                'details' => ['online' => $online, 'total' => $total],
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_MIKROTIK,
                $result['status'],
                $result['message'],
                null,
                $result['details']
            );
            
            return $result;
        } catch (\Exception $e) {
            $result = [
                'status' => SystemHealthLog::STATUS_ERROR,
                'message' => 'MikroTik check failed: ' . $e->getMessage(),
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_MIKROTIK,
                $result['status'],
                $result['message']
            );
            
            return $result;
        }
    }

    public function checkTelegram(): array
    {
        $start = microtime(true);
        try {
            $botToken = env('TELEGRAM_CLIENT_BOT_TOKEN');
            if (!$botToken) {
                $result = [
                    'status' => SystemHealthLog::STATUS_WARNING,
                    'message' => 'Telegram bot not configured',
                ];
                
                SystemHealthLog::logCheck(
                    SystemHealthLog::COMPONENT_TELEGRAM,
                    $result['status'],
                    $result['message']
                );
                
                return $result;
            }
            
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$botToken}/getMe");
            $responseTime = (int) ((microtime(true) - $start) * 1000);
            
            if ($response->successful() && $response->json('ok')) {
                $botName = $response->json('result.username');
                $result = [
                    'status' => SystemHealthLog::STATUS_OK,
                    'message' => "Telegram bot connected: @{$botName}",
                    'response_time_ms' => $responseTime,
                ];
            } else {
                $result = [
                    'status' => SystemHealthLog::STATUS_ERROR,
                    'message' => 'Telegram bot verification failed',
                    'response_time_ms' => $responseTime,
                ];
            }
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_TELEGRAM,
                $result['status'],
                $result['message'],
                $responseTime
            );
            
            return $result;
        } catch (\Exception $e) {
            $result = [
                'status' => SystemHealthLog::STATUS_ERROR,
                'message' => 'Telegram check failed: ' . $e->getMessage(),
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_TELEGRAM,
                $result['status'],
                $result['message']
            );
            
            return $result;
        }
    }

    public function checkCron(): array
    {
        try {
            $lastCronRun = DB::table('system_settings')
                ->where('key', 'last_cron_run')
                ->value('value');
            
            if (!$lastCronRun) {
                $result = [
                    'status' => SystemHealthLog::STATUS_WARNING,
                    'message' => 'Cron has never run',
                ];
            } else {
                $lastRun = \Carbon\Carbon::parse($lastCronRun);
                $minutesAgo = $lastRun->diffInMinutes(now());
                
                if ($minutesAgo > 10) {
                    $result = [
                        'status' => SystemHealthLog::STATUS_ERROR,
                        'message' => "Cron not running. Last run: {$minutesAgo} minutes ago",
                    ];
                } elseif ($minutesAgo > 5) {
                    $result = [
                        'status' => SystemHealthLog::STATUS_WARNING,
                        'message' => "Cron delayed. Last run: {$minutesAgo} minutes ago",
                    ];
                } else {
                    $result = [
                        'status' => SystemHealthLog::STATUS_OK,
                        'message' => "Cron running. Last run: {$minutesAgo} minutes ago",
                    ];
                }
            }
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_CRON,
                $result['status'],
                $result['message']
            );
            
            return $result;
        } catch (\Exception $e) {
            $result = [
                'status' => SystemHealthLog::STATUS_ERROR,
                'message' => 'Cron check failed: ' . $e->getMessage(),
            ];
            
            SystemHealthLog::logCheck(
                SystemHealthLog::COMPONENT_CRON,
                $result['status'],
                $result['message']
            );
            
            return $result;
        }
    }
}
