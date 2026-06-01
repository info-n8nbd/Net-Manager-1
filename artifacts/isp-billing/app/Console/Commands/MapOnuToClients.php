<?php

namespace App\Console\Commands;

use App\Services\OnuClientMappingService;
use Illuminate\Console\Command;

class MapOnuToClients extends Command
{
    protected $signature   = "network:map-onus";
    protected $description = "Auto-detect ONU per PPPoE client by correlating MikroTik caller-id with OLT FDB (SNMP).";

    public function handle(): int
    {
        $this->info("Starting ONU -> Client auto-mapping...");
        $startTime = microtime(true);

        /** @var OnuClientMappingService $svc */
        $svc    = app(OnuClientMappingService::class);
        $result = $svc->mapAll();

        $elapsed = round(microtime(true) - $startTime, 2);

        foreach ($result["log"] as $entry) {
            $msg = "[{$entry["at"]}] {$entry["msg"]}";
            match ($entry["level"]) {
                "success" => $this->line("<fg=green>{$msg}</>"),
                "warn"    => $this->warn($msg),
                "error"   => $this->error($msg),
                default   => $this->line($msg),
            };
        }

        $this->newLine();
        $this->table(
            ["Metric", "Count"],
            [
                ["Clients mapped to ONU", $result["mapped"]],
                ["Sessions skipped (no FDB match)", $result["skipped"]],
                ["Errors", $result["failed"]],
                ["Time elapsed (s)", $elapsed],
            ]
        );

        if ($result["mapped"] > 0) {
            $this->info("Done. {$result["mapped"]} client(s) mapped successfully.");
        } else {
            $this->warn("No clients mapped. Check OLT SNMP connectivity and that ONUs exist in the onus table.");
        }

        return Command::SUCCESS;
    }
}
