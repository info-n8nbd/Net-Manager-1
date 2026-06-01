<?php

namespace App\Jobs;

use App\Models\OltDevice;
use App\Services\OLT\BdcomTelnetPoller;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollOltRxPowerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 1;
    public $timeout = 600;

    public function handle(): void
    {
        $startTime    = microtime(true);
        $totalUpdated = 0;

        $oltDevices = OltDevice::where("is_active", true)
            ->where("snmp_enabled", false)
            ->where("connection_type", "telnet")
            ->where("vendor", "bdcom")
            ->get();

        foreach ($oltDevices as $olt) {
            try {
                $updated = $this->pollOpticalForOlt($olt);
                $totalUpdated += $updated;
                Log::info("RX Power poll [{$olt->name}]: updated {$updated} ONUs");
            } catch (\Exception $e) {
                Log::error("RX Power poll [{$olt->name}] failed: " . $e->getMessage());
            }
        }

        $elapsed = round(microtime(true) - $startTime, 1);
        Log::info("PollOltRxPowerJob completed: {$totalUpdated} ONUs updated in {$elapsed}s");

        DB::table("cron_job_logs")->insert([
            "job_name"          => "poll_olt_rx_power",
            "job_class"         => self::class,
            "status"            => "completed",
            "records_processed" => $totalUpdated,
            "execution_time_ms" => (int)($elapsed * 1000),
            "started_at"        => now(),
            "completed_at"      => now(),
            "created_at"        => now(),
            "updated_at"        => now(),
        ]);
    }

    protected function pollOpticalForOlt(OltDevice $olt): int
    {
        $poller  = new BdcomTelnetPoller($olt);
        $onuList = $poller->getRxPowers();

        $updated = 0;

        // --- Save per-ONU optical data to onus table (unchanged) ---
        foreach ($onuList as $data) {
            if ($data["rx_power"] === null) continue;

            $rows = DB::table("onus")
                ->where("olt_device_id", $olt->id)
                ->where("pon_port", $data["pon_port"])
                ->where("onu_number", $data["onu_number"])
                ->update([
                    "rx_power"    => $data["rx_power"],
                    "tx_power"    => $data["tx_power"]    ?? null,
                    "temperature" => $data["temperature"] ?? null,
                    "voltage"     => $data["voltage"]     ?? null,
                    "tx_bias"     => $data["tx_bias"]     ?? null,
                    "updated_at"  => now(),
                ]);

            if ($rows) $updated++;
        }

        // --- Save port-level metrics to olt_pon_port_metrics (new) ---
        // tx_power, temperature, voltage, tx_bias are the same for all ONUs on the same port
        // (they come from the OLT PON module, not the ONU)
        $portSeen = [];
        foreach ($onuList as $data) {
            $port = strtoupper($data["pon_port"]);
            if (isset($portSeen[$port])) continue;
            if ($data["tx_power"] === null && $data["temperature"] === null) continue;

            $portSeen[$port] = true;

            DB::table("olt_pon_port_metrics")->updateOrInsert(
                [
                    "olt_device_id" => $olt->id,
                    "pon_port"      => $port,
                ],
                [
                    "tx_power"    => $data["tx_power"]    ?? null,
                    "temperature" => $data["temperature"] ?? null,
                    "voltage"     => $data["voltage"]     ?? null,
                    "tx_bias"     => $data["tx_bias"]     ?? null,
                    "polled_at"   => now(),
                    "updated_at"  => now(),
                ]
            );
        }

        // Sync clients table
        DB::statement("
            UPDATE clients
            SET onu_status   = onus.status,
                onu_rx_power = COALESCE(onus.rx_power, clients.onu_rx_power)
            FROM onus
            WHERE (clients.onu_serial = onus.serial_number
               OR  clients.onu_mac   = onus.mac_address)
              AND onus.olt_device_id = ?
        ", [$olt->id]);

        // Log weak signal
        $weak = DB::table("onus")
            ->where("olt_device_id", $olt->id)
            ->where("rx_power", "<", -27)
            ->whereNotNull("rx_power")
            ->where("status", "online")
            ->count();
        if ($weak > 0) {
            Log::warning("RX Power [{$olt->name}]: {$weak} ONUs with weak signal (< -27 dBm)");
        }

        return $updated;
    }
}
