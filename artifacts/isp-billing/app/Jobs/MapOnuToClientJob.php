<?php

namespace App\Jobs;

use App\Services\OnuClientMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MapOnuToClientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 180;

    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            /** @var OnuClientMappingService $svc */
            $svc    = app(OnuClientMappingService::class);
            $result = $svc->mapAll();

            $elapsed = round(microtime(true) - $startTime, 2);

            Log::info("[ONU-MAP] Job complete", [
                "mapped"  => $result["mapped"],
                "skipped" => $result["skipped"],
                "failed"  => $result["failed"],
                "time_s"  => $elapsed,
            ]);

            $this->logCron("success", $startTime, $result["mapped"]);
        } catch (\Throwable $e) {
            Log::error("[ONU-MAP] Job failed: " . $e->getMessage());
            $this->logCron("failed", $startTime, 0, $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[ONU-MAP] Permanently failed: " . $e->getMessage());
    }

    private function logCron(string $status, float $startTime, int $records, string $error = ""): void
    {
        try {
            DB::table("cron_job_logs")->insert([
                "job_name"          => "map-onu-to-client",
                "status"            => $status,
                "records_processed" => $records,
                "duration_seconds"  => round(microtime(true) - $startTime, 2),
                "error_message"     => $error ? substr($error, 0, 500) : null,
                "created_at"        => now(),
                "updated_at"        => now(),
            ]);
        } catch (\Throwable) {}
    }
}
