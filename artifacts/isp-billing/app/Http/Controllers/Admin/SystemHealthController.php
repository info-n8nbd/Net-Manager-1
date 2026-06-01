<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobFailureLog;
use App\Models\SystemHealthLog;
use App\Services\SystemHealthService;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    protected SystemHealthService $healthService;

    public function __construct(SystemHealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    public function index()
    {
        $latestStatus = SystemHealthLog::getLatestStatus();
        $failedJobs = JobFailureLog::where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.system.health', compact('latestStatus', 'failedJobs'));
    }

    public function runCheck()
    {
        $results = $this->healthService->runAllChecks();

        return response()->json([
            'success' => true,
            'results' => $results,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function checkComponent(Request $request)
    {
        $component = $request->get('component');
        
        $methodMap = [
            'database' => 'checkDatabase',
            'storage' => 'checkStorage',
            'queue' => 'checkQueue',
            'sms_api' => 'checkSmsApi',
            'mikrotik' => 'checkMikrotik',
            'telegram' => 'checkTelegram',
            'cron' => 'checkCron',
        ];

        if (!isset($methodMap[$component])) {
            return response()->json(['error' => 'Invalid component'], 400);
        }

        $method = $methodMap[$component];
        $result = $this->healthService->$method();

        return response()->json([
            'success' => true,
            'component' => $component,
            'result' => $result,
        ]);
    }

    public function failedJobs()
    {
        $jobs = JobFailureLog::orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.system.failed-jobs', compact('jobs'));
    }

    public function retryJob(Request $request, $id)
    {
        $job = JobFailureLog::findOrFail($id);
        
        if ($job->status !== JobFailureLog::STATUS_FAILED) {
            return response()->json([
                'success' => false,
                'message' => 'Only failed jobs can be retried',
            ], 400);
        }

        try {
            $jobClass = $job->job_class;
            $payload = $job->payload;

            if (!class_exists($jobClass)) {
                \Log::warning('Job retry failed: class not found', [
                    'job_id' => $id,
                    'job_class' => $jobClass,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Job class no longer exists: ' . $jobClass,
                ], 400);
            }

            $reflection = new \ReflectionClass($jobClass);
            $constructor = $reflection->getConstructor();
            $jobInstance = null;

            if (!$constructor || $constructor->getNumberOfRequiredParameters() === 0) {
                $jobInstance = new $jobClass();
                if (is_array($payload) && method_exists($jobInstance, 'setPayload')) {
                    $jobInstance->setPayload($payload);
                }
            } elseif (isset($payload['command']) && is_string($payload['command'])) {
                $jobInstance = unserialize($payload['command']);
            } elseif (is_array($payload)) {
                $params = $constructor->getParameters();
                $args = [];
                foreach ($params as $param) {
                    $name = $param->getName();
                    if (array_key_exists($name, $payload)) {
                        $args[] = $payload[$name];
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        throw new \Exception("Missing required parameter: {$name}");
                    }
                }
                $jobInstance = $reflection->newInstanceArgs($args);
            }

            if (!$jobInstance) {
                throw new \Exception('Unable to reconstruct job from stored payload');
            }

            dispatch($jobInstance);

            $job->update([
                'status' => JobFailureLog::STATUS_RETRYING,
                'retried_at' => now(),
                'attempts' => $job->attempts + 1,
            ]);

            \Log::info('Job retry dispatched', [
                'job_id' => $id,
                'job_class' => $jobClass,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Job dispatched for retry',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to retry job', [
                'job_id' => $id,
                'job_class' => $job->job_class,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry job: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function resolveJob(Request $request, $id)
    {
        $job = JobFailureLog::findOrFail($id);
        
        $job->markResolved(auth()->id(), $request->get('notes'));

        return response()->json([
            'success' => true,
            'message' => 'Job marked as resolved',
        ]);
    }

    public function diagnostics()
    {
        $diagnostics = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'disk_free_space' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'uptime' => $this->getUptime(),
            'database_size' => $this->getDatabaseSize(),
            'active_sessions' => \DB::table('sessions')->count(),
            'pending_jobs' => \DB::table('jobs')->count(),
            'failed_jobs' => \DB::table('failed_jobs')->count(),
        ];

        return response()->json([
            'success' => true,
            'diagnostics' => $diagnostics,
        ]);
    }

    protected function getUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime) {
                $seconds = (int) explode(' ', $uptime)[0];
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                return "{$days} days, {$hours} hours";
            }
        }
        return 'Unknown';
    }

    protected function getDatabaseSize(): string
    {
        try {
            $result = \DB::select("SELECT pg_database_size(current_database()) as size");
            $sizeBytes = $result[0]->size ?? 0;
            return round($sizeBytes / 1024 / 1024, 2) . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
}
