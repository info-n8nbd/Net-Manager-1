<?php

namespace App\Services;

use App\Models\Client;
use App\Models\OltDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\OLT\GponTelnetCollector;

class OnuClientMappingService
{
    protected int $mapped   = 0;
    protected int $skipped  = 0;
    protected int $failed   = 0;
    protected array $log    = [];

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------
    public function mapAll(): array
    {
        $this->mapped  = 0;
        $this->skipped = 0;
        $this->failed  = 0;
        $this->log     = [];

        $oltDevices = OltDevice::where('is_active', true)
            ->where(function ($q) {
                $q->where('snmp_enabled', true)
                  ->whereNotNull('snmp_community');
            })
            ->get();

        if ($oltDevices->isEmpty()) {
            $this->addLog('warn', 'No SNMP-enabled OLT devices found.');
            return $this->result();
        }

        // Build a combined MAC -> ONU info map from all OLTs
        $fdbMap = $this->buildFdbMap($oltDevices);

        if (empty($fdbMap)) {
            $this->addLog('warn', 'FDB table empty — no per-ONU MACs learned from any OLT.');
            return $this->result();
        }

        $this->addLog('info', 'FDB built — ' . count($fdbMap) . ' MAC entries across ' . $oltDevices->count() . ' OLT(s).');

        // Use mikrotik_sessions DB table as session source (faster + covers all devices)
        $this->processSessionsFromDb($fdbMap);

        return $this->result();
    }

    // -------------------------------------------------------------------------
    // Build MAC -> ONU map from all OLTs
    // -------------------------------------------------------------------------
    protected function buildFdbMap(iterable $oltDevices): array
    {
        $fdbMap = [];

        foreach ($oltDevices as $olt) {
            try {
                $entries = $this->queryOltFdb($olt);
                foreach ($entries as $mac => $info) {
                    // Don't overwrite an existing entry that has a DB-matched ONU
                    if (!isset($fdbMap[strtoupper($mac)])) {
                        $fdbMap[strtoupper($mac)] = $info;
                    }
                }
                $this->addLog('info', "[OLT:{$olt->name}] FDB read — " . count($entries) . ' entries.');
            } catch (\Throwable $e) {
                $this->addLog('error', "[OLT:{$olt->name}] FDB query failed: " . $e->getMessage());
            }
        }

        return $fdbMap;
    }

    // -------------------------------------------------------------------------
    // Query per-ONU client MACs from a single OLT
    // Uses dot1q VLAN FDB (bridge port = ifIndex for BDCOM EPON)
    // Falls back to standard dot1d FDB for other vendors.
    // -------------------------------------------------------------------------
    protected function queryOltFdb(OltDevice $olt): array
    {
        // Step 1: Build ifIndex -> ONU info map from ifName walk
        $ifNameRaw = $this->bulkWalkCc($olt, '.1.3.6.1.2.1.31.1.1.1.1');
        if (empty($ifNameRaw)) {
            $this->addLog('warn', "[OLT:{$olt->name}] ifName walk empty — SNMP issue?");
            return [];
        }

        // ifIndex -> ['pon_port', 'onu_number'] for EPON/GPON ONU sub-interfaces
        $ifIndexToLoc = [];
        foreach ($ifNameRaw as $oid => $name) {
            $name = trim($name, " \t\n\r\"'");
            if (preg_match('/^((?:EPON|GPON)\d+\/\d+):(\d+)$/i', $name, $m)) {
                if (preg_match('/\.(\d+)$/', $oid, $im)) {
                    $ifIndexToLoc[(int)$im[1]] = [
                        'pon_port'   => strtoupper($m[1]),
                        'onu_number' => (int)$m[2],
                    ];
                }
            }
        }

        if (empty($ifIndexToLoc)) {
            $this->addLog('warn', "[OLT:{$olt->name}] No ONU sub-interfaces in ifName.");
            return [];
        }

        $this->addLog('info', "[OLT:{$olt->name}] " . count($ifIndexToLoc) . ' ONU sub-interfaces detected.');

        // Preload onus from DB keyed by (pon_port, onu_number)
        $onuDb = DB::table('onus')
            ->where('olt_device_id', $olt->id)
            ->get(['id', 'pon_port', 'onu_number', 'serial_number', 'mac_address', 'client_id'])
            ->keyBy(fn($o) => $o->pon_port . ':' . $o->onu_number);

        // Build ifIndex -> enriched ONU record
        $ifIndexToOnu = [];
        foreach ($ifIndexToLoc as $ifIndex => $loc) {
            $key = $loc['pon_port'] . ':' . $loc['onu_number'];
            if (isset($onuDb[$key])) {
                $dbOnu = $onuDb[$key];
                $ifIndexToOnu[$ifIndex] = [
                    'pon_port'   => $loc['pon_port'],
                    'onu_number' => $loc['onu_number'],
                    'serial'     => $dbOnu->serial_number,
                    'onu_mac'    => $dbOnu->mac_address,
                    'onu_id'     => $dbOnu->id,
                    'olt'        => $olt,
                ];
            }
        }

        // Step 2: Try dot1q VLAN FDB
        // OID: .1.3.6.1.2.1.17.7.1.2.2.1.2.{VLAN}.{MAC_BYTES} = bridge_port
        // On BDCOM EPON, bridge_port == ifIndex for ONU sub-interfaces
        $dot1qRaw = $this->bulkWalkCc($olt, '.1.3.6.1.2.1.17.7.1.2.2.1.2');
        $result = [];

        if (!empty($dot1qRaw)) {
            foreach ($dot1qRaw as $oid => $bridgePort) {
                $bridgePort = (int)$bridgePort;
                if (!isset($ifIndexToOnu[$bridgePort])) continue;

                // Parse VLAN + MAC: OID ends with .VLAN.M1.M2.M3.M4.M5.M6
                if (!preg_match('/\.(\d+)\.(\d+)\.(\d+)\.(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $oid, $m)) continue;

                $mac = strtoupper(sprintf('%02X:%02X:%02X:%02X:%02X:%02X',
                    $m[2], $m[3], $m[4], $m[5], $m[6], $m[7]));

                if ($mac === 'FF:FF:FF:FF:FF:FF') continue;
                if (hexdec(substr($mac, 0, 2)) & 0x01) continue; // Multicast

                $result[$mac] = $ifIndexToOnu[$bridgePort];
            }

            if (!empty($result)) {
                $this->addLog('info', "[OLT:{$olt->name}] dot1q FDB: " . count($result) . ' per-ONU MACs found.');
                return $result;
            }
        }

        // Step 3: Fallback to standard dot1d FDB
        $this->addLog('info', "[OLT:{$olt->name}] dot1q empty — trying standard dot1d FDB.");
        $dot1dRaw = $this->bulkWalkCc($olt, '.1.3.6.1.2.1.17.4.3.1.2');

        foreach ($dot1dRaw as $oid => $bridgePort) {
            $bridgePort = (int)$bridgePort;
            if (!isset($ifIndexToOnu[$bridgePort])) continue;
            if (!preg_match('/\.(\d+)\.(\d+)\.(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $oid, $m)) continue;

            $mac = strtoupper(sprintf('%02X:%02X:%02X:%02X:%02X:%02X',
                $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]));

            if ($mac === 'FF:FF:FF:FF:FF:FF') continue;
            if (hexdec(substr($mac, 0, 2)) & 0x01) continue;

            $result[$mac] = $ifIndexToOnu[$bridgePort];
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Process sessions from mikrotik_sessions DB table
    // Uses caller_id to look up FDB, then client_id or username to find client
    // -------------------------------------------------------------------------
    protected function processSessionsFromDb(array $fdbMap): void
    {
        // Load all online sessions that have a caller_id
        $sessions = DB::table('mikrotik_sessions')
            ->where('status', 'online')
            ->whereNotNull('caller_id')
            ->where('caller_id', '!=', '')
            ->get(['id', 'username', 'caller_id', 'address', 'client_id', 'mikrotik_device_id']);

        $this->addLog('info', 'DB sessions with caller_id: ' . $sessions->count());

        foreach ($sessions as $session) {
            $this->processDbSession($session, $fdbMap);
        }
    }

    protected function processDbSession(object $session, array $fdbMap): void
    {
        // Normalize caller MAC
        $callerMac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $session->caller_id ?? ''));
        if (strlen($callerMac) !== 12) {
            $this->skipped++;
            return;
        }
        $callerMac = implode(':', str_split($callerMac, 2));

        // Look up ONU info from FDB
        $onuInfo = $fdbMap[$callerMac] ?? null;
        if (!$onuInfo) {
            $this->skipped++;
            return;
        }

        // Find client: prefer client_id from session, then username match
        $client = null;
        if (!empty($session->client_id) && $session->client_id > 0) {
            $client = Client::find($session->client_id);
        }
        if (!$client && !empty($session->username)) {
            $client = Client::where('username', $session->username)->first();
        }

        if (!$client) {
            // Log only if this is a known/matched FDB entry (worth flagging)
            $this->addLog('warn', "PPPoE user [{$session->username}] not found in clients (caller:{$callerMac}).");
            $this->skipped++;
            return;
        }

        /** @var OltDevice $olt */
        $olt = $onuInfo['olt'];

        $updateData = [
            'olt_device_id' => $olt->id,
            'olt_port'      => $onuInfo['pon_port'],
        ];
        if (!empty($onuInfo['serial'])) {
            $updateData['onu_serial'] = $onuInfo['serial'];
        }
        if (!empty($onuInfo['onu_mac'])) {
            $updateData['onu_mac'] = $onuInfo['onu_mac'];
        }

        $client->update($updateData);

        // Update onus.client_id back-reference
        if (!empty($onuInfo['onu_id'])) {
            DB::table('onus')
                ->where('id', $onuInfo['onu_id'])
                ->update(['client_id' => $client->id]);
        }

        $this->mapped++;
        $logMsg = "MAPPED [{$session->username}] WiFi-MAC:{$callerMac} -> ONU:{$onuInfo['pon_port']}:{$onuInfo['onu_number']}"
            . (!empty($onuInfo['serial']) ? " SN:{$onuInfo['serial']}" : '')
            . " (OLT:{$olt->name})";
        $this->addLog('success', $logMsg);
        Log::info("[ONU-MAP] {$logMsg}");
    }

    // -------------------------------------------------------------------------
    // CLI snmpbulkwalk -Cc — handles BDCOM non-monotonic OIDs
    // -------------------------------------------------------------------------
    protected function bulkWalkCc(OltDevice $olt, string $baseOid): array
    {
        $snmpPort  = (int)($olt->snmp_port ?? $olt->port ?? 161);
        $community = escapeshellarg($olt->snmp_community ?? 'public');
        $host      = escapeshellarg($olt->ip_address . ':' . $snmpPort);
        $oid       = escapeshellarg($baseOid);

        $cmd    = "snmpbulkwalk -v2c -Cc -OQn -c {$community} -t 3 -r 1 {$host} {$oid} 2>&1";
        $output = @shell_exec($cmd);

        if (empty($output)) return [];

        $result = [];
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ($line === '' || str_contains($line, 'Error') || str_contains($line, 'Timeout')) continue;
            if (!str_contains($line, ' = ')) continue;

            [$oidPart, $val] = explode(' = ', $line, 2);
            $val = trim($val);

            // Strip SNMP type prefix: "INTEGER: 5" -> "5", "STRING: foo" -> "foo"
            if (preg_match('/^[A-Z0-9_-]+:\s*(.+)$/', $val, $vm)) {
                $val = trim($vm[1], '"');
            }

            $result[trim($oidPart)] = $val;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    protected function addLog(string $level, string $msg): void
    {
        $this->log[] = ['level' => $level, 'msg' => $msg, 'at' => now()->toTimeString()];
    }

    protected function result(): array
    {
        return [
            'mapped'  => $this->mapped,
            'skipped' => $this->skipped,
            'failed'  => $this->failed,
            'log'     => $this->log,
        ];
    }

    // -------------------------------------------------------------------------
    // Map ONUs to clients for a single OLT only (used by Scan & Match button)
    // -------------------------------------------------------------------------
    public function mapForOlt(OltDevice $olt): array
    {
        $this->mapped  = 0;
        $this->skipped = 0;
        $this->failed  = 0;
        $this->log     = [];

        // Route to telnet-based FDB for GPON OLTs without SNMP
        if ((!$olt->snmp_enabled || empty($olt->snmp_community)) && $olt->pon_type === 'gpon') {
            try {
                $entries = $this->buildGponFdbMapViaTelnet($olt);
                $this->addLog("info", "[OLT:{$olt->name}] GPON telnet FDB — " . count($entries) . " entries.");
            } catch (\Throwable $e) {
                $this->addLog("error", "[OLT:{$olt->name}] GPON telnet FDB failed: " . $e->getMessage());
                return $this->result();
            }
            if (empty($entries)) {
                $this->addLog("warn", "[OLT:{$olt->name}] GPON FDB empty — check telnet credentials.");
                return $this->result();
            }
            $this->processSessionsFromDb($entries);
            return $this->result();
        }

        if (!$olt->snmp_enabled || empty($olt->snmp_community)) {
            $this->addLog("warn", "OLT {$olt->name} has SNMP disabled or no community string set.");
            return $this->result();
        }

        try {
            $entries = $this->queryOltFdb($olt);
        } catch (\Throwable $e) {
            $this->addLog("error", "[OLT:{$olt->name}] FDB query failed: " . $e->getMessage());
            return $this->result();
        }

        if (empty($entries)) {
            $this->addLog("warn", "[OLT:{$olt->name}] FDB empty — no per-ONU MACs found. Check SNMP connectivity.");
            return $this->result();
        }

        $fdbMap = [];
        foreach ($entries as $mac => $info) {
            $fdbMap[strtoupper($mac)] = $info;
        }

        $this->addLog("info", "[OLT:{$olt->name}] FDB built — " . count($fdbMap) . " MAC entries.");
        $this->processSessionsFromDb($fdbMap);

        return $this->result();
    }


    // -------------------------------------------------------------------------
    // Get unmatched sessions: MAC found in FDB but no client record
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // Build FDB map for GPON OLT via Telnet/SSH
    // -------------------------------------------------------------------------
    protected function buildGponFdbMapViaTelnet(OltDevice $olt): array
    {
        $collector = new GponTelnetCollector($olt);
        return $collector->getFdbMap();
    }

        public function getMismatches(array $oltIds = []): array
    {
        $cacheKey = "onu_mismatches_" . (empty($oltIds) ? "all" : implode("_", $oltIds));
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 180, function () use ($oltIds) {
            return $this->fetchMismatches($oltIds);
        });
    }

    public static function clearMismatchCache(array $oltIds = []): void
    {
        $cacheKey = "onu_mismatches_" . (empty($oltIds) ? "all" : implode("_", $oltIds));
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        \Illuminate\Support\Facades\Cache::forget("onu_mismatches_all");
    }

    protected function fetchMismatches(array $oltIds = []): array
    {
        // Non-GPON SNMP-enabled OLTs (EPON, etc.)
        $snmpQuery = OltDevice::where("is_active", true)
            ->where("snmp_enabled", true)
            ->whereNotNull("snmp_community")
            ->where(function ($q) {
                $q->where("pon_type", "!=", "gpon")->orWhereNull("pon_type");
            });

        // ALL GPON OLTs — handled with 3-level fallback: SNMP → telnet FDB → ONU-based
        $gponQuery = OltDevice::where("is_active", true)
            ->where("pon_type", "gpon");

        if (!empty($oltIds)) {
            $snmpQuery->whereIn("id", $oltIds);
            $gponQuery->whereIn("id", $oltIds);
        }

        $snmpOlts = $snmpQuery->get();
        $gponOlts = $gponQuery->get();

        if ($snmpOlts->isEmpty() && $gponOlts->isEmpty()) return [];

        // Build FDB from non-GPON SNMP OLTs
        $fdbMap = $snmpOlts->isEmpty() ? [] : $this->buildFdbMap($snmpOlts);

        // Handle GPON OLTs with 3-level fallback: SNMP → telnet FDB → ONU-based
        $gponOltsWithEmptyFdb = [];
        foreach ($gponOlts as $olt) {
            $gponFdb = [];

            // Level 1: Try SNMP if enabled
            if ($olt->snmp_enabled && !empty($olt->snmp_community)) {
                try {
                    $gponFdb = $this->buildFdbMap(collect([$olt]));
                    if (!empty($gponFdb)) {
                        $this->addLog("info", "[OLT:{$olt->name}] GPON SNMP FDB — " . count($gponFdb) . " entries.");
                    } else {
                        $this->addLog("warn", "[OLT:{$olt->name}] GPON SNMP returned 0 entries — trying telnet FDB.");
                    }
                } catch (\Throwable $e) {
                    $this->addLog("warn", "[OLT:{$olt->name}] GPON SNMP failed: " . $e->getMessage() . " — trying telnet FDB.");
                }
            }

            // Level 2: Try telnet FDB if SNMP unavailable or empty
            if (empty($gponFdb)) {
                try {
                    $gponFdb = $this->buildGponFdbMapViaTelnet($olt);
                    if (!empty($gponFdb)) {
                        $this->addLog("info", "[OLT:{$olt->name}] GPON telnet FDB — " . count($gponFdb) . " entries.");
                    } else {
                        $this->addLog("warn", "[OLT:{$olt->name}] GPON telnet FDB empty — using ONU-based fallback.");
                    }
                } catch (\Throwable $e) {
                    $this->addLog("warn", "[OLT:{$olt->name}] GPON telnet FDB failed: " . $e->getMessage() . " — using ONU-based fallback.");
                }
            }

            // Level 3: ONU-based fallback if both SNMP and telnet returned nothing
            if (!empty($gponFdb)) {
                foreach ($gponFdb as $mac => $info) {
                    if (!isset($fdbMap[$mac])) $fdbMap[$mac] = $info;
                }
            } else {
                $gponOltsWithEmptyFdb[] = $olt;
            }
        }

        if (empty($fdbMap) && empty($gponOltsWithEmptyFdb)) return [];

        $sessions = DB::table("mikrotik_sessions")
            ->where("status", "online")
            ->whereNotNull("caller_id")
            ->where("caller_id", "!=", "")
            ->get(["username", "caller_id", "address", "client_id"]);

        // Bulk-load clients to avoid N+1 queries
        $clientIds = $sessions->filter(fn($s) => !empty($s->client_id) && $s->client_id > 0)
            ->pluck("client_id")->unique()->values()->toArray();
        $usernames = $sessions->filter(fn($s) => !empty($s->username))
            ->pluck("username")->unique()->values()->toArray();

        $clientsById       = Client::whereIn("id", $clientIds)->get(["id"])->keyBy("id");
        $clientsByUsername = Client::whereIn("username", $usernames)->get(["id", "username"])->keyBy("username");

        $mismatches = [];
        foreach ($sessions as $session) {
            $raw = preg_replace("/[^0-9A-Fa-f]/", "", $session->caller_id ?? "");
            if (strlen($raw) !== 12) continue;
            $callerMac = strtoupper(implode(":", str_split($raw, 2)));

            $onuInfo = $fdbMap[$callerMac] ?? null;
            if (!$onuInfo) continue;

            // Check if already matched using preloaded maps (no extra DB queries)
            $matched = false;
            if (!empty($session->client_id) && $session->client_id > 0) {
                $matched = isset($clientsById[$session->client_id]);
            }
            if (!$matched && !empty($session->username)) {
                $matched = isset($clientsByUsername[$session->username]);
            }
            if ($matched) continue;

            $olt = $onuInfo["olt"];
            $mismatches[] = [
                "username"   => $session->username,
                "caller_mac" => $callerMac,
                "ip"         => $session->address ?? null,
                "olt_name"   => $olt->name,
                "olt_id"     => $olt->id,
                "pon_port"   => $onuInfo["pon_port"],
                "onu_number" => $onuInfo["onu_number"],
                "onu_serial" => $onuInfo["serial"] ?? null,
                "onu_mac"    => $onuInfo["onu_mac"] ?? null,
                "onu_id"     => $onuInfo["onu_id"] ?? null,
            ];
        }

        // Fallback for GPON OLTs where FDB was unavailable:
        // Show active ONUs without a billing client as mismatches
        foreach ($gponOltsWithEmptyFdb as $olt) {
            $onuMismatches = $this->findActiveOnuMismatches($olt);
            foreach ($onuMismatches as $m) {
                $mismatches[] = $m;
            }
            $this->addLog("info", "[OLT:{$olt->name}] ONU fallback: " . count($onuMismatches) . " unassigned active ONUs.");
        }

        usort($mismatches, fn($a, $b) => [$a["olt_name"], $a["pon_port"], $a["onu_number"]] <=> [$b["olt_name"], $b["pon_port"], $b["onu_number"]]);

        return $mismatches;
    }

    // -------------------------------------------------------------------------
    // ONU-based mismatch fallback: active ONUs without a billing client
    // Used for GPON OLTs where FDB-based MAC detection is unavailable
    // -------------------------------------------------------------------------
    protected function findActiveOnuMismatches(OltDevice $olt): array
    {
        $activeOnus = DB::table("onus")
            ->where("olt_device_id", $olt->id)
            ->where("status", "online")
            ->whereNull("client_id")
            ->select("id", "pon_port", "onu_number", "serial_number", "mac_address")
            ->get();

        $mismatches = [];
        foreach ($activeOnus as $onu) {
            $mismatches[] = [
                "username"   => null,
                "caller_mac" => $onu->mac_address ?? null,
                "ip"         => null,
                "olt_name"   => $olt->name,
                "olt_id"     => $olt->id,
                "pon_port"   => $onu->pon_port,
                "onu_number" => $onu->onu_number,
                "onu_serial" => $onu->serial_number,
                "onu_mac"    => $onu->mac_address,
                "onu_id"     => $onu->id,
                "type"       => "onu_unassigned",
            ];
        }
        return $mismatches;
    }

}