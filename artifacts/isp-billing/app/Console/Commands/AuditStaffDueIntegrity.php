<?php

namespace App\Console\Commands;

use App\Models\StaffDue;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditStaffDueIntegrity extends Command
{
    protected $signature = 'audit:staff-due
                            {--fix : Automatically correct mismatched StaffDue records}
                            {--user= : Audit a specific user ID only}';

    protected $description = 'Verify StaffDue accuracy against actual payment records and detect drift from race conditions';

    public function handle(): int
    {
        $fix = $this->option('fix');
        $specificUser = $this->option('user');

        $this->info("╔══════════════════════════════════════════════════════════╗");
        $this->info("║  StaffDue Integrity Audit                              ║");
        $this->info("╚══════════════════════════════════════════════════════════╝");
        $this->newLine();

        $query = StaffDue::query()->with('user');
        if ($specificUser) {
            $query->where('user_id', $specificUser);
        }
        $staffDues = $query->get();

        if ($staffDues->isEmpty()) {
            $this->warn("No StaffDue records found.");
            return self::SUCCESS;
        }

        $mismatches = [];
        $totalChecked = 0;
        $totalCorrect = 0;

        $this->info("━━━ Per-Staff Audit ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $rows = [];
        foreach ($staffDues as $staffDue) {
            $totalChecked++;

            $actualCollected = Payment::where('collected_by', $staffDue->user_id)
                ->whereNotIn('status', ['rejected', 'refunded', 'void', 'reversed'])
                ->sum('amount');

            $actualSubmittedFallback = false;
            $hasSubmissionsTable = DB::getSchemaBuilder()->hasTable('staff_submissions');
            if ($hasSubmissionsTable) {
                $actualSubmitted = DB::table('staff_submissions')
                    ->where('staff_user_id', $staffDue->user_id)
                    ->where('status', 'approved')
                    ->sum('amount');
            } else {
                $actualSubmitted = $staffDue->total_submitted;
                $actualSubmittedFallback = true;
            }

            $expectedDue = round((float)$actualCollected - (float)$actualSubmitted, 2);
            $recordedCollected = round((float)$staffDue->total_collected, 2);
            $recordedSubmitted = round((float)$staffDue->total_submitted, 2);
            $recordedDue = round((float)$staffDue->current_due, 2);

            $collectedDrift = round($recordedCollected - $actualCollected, 2);
            $dueDrift = round($recordedDue - $expectedDue, 2);

            $hasMismatch = abs($collectedDrift) > 0.01 || abs($dueDrift) > 0.01;

            $userName = $staffDue->user?->name ?? "User #{$staffDue->user_id}";
            $status = $hasMismatch ? 'MISMATCH' : 'OK';

            $rows[] = [
                $staffDue->user_id,
                substr($userName, 0, 20),
                '৳' . number_format($recordedCollected, 2),
                '৳' . number_format($actualCollected, 2),
                $collectedDrift != 0 ? ($collectedDrift > 0 ? '+' : '') . number_format($collectedDrift, 2) : '-',
                '৳' . number_format($recordedDue, 2),
                '৳' . number_format($expectedDue, 2),
                $dueDrift != 0 ? ($dueDrift > 0 ? '+' : '') . number_format($dueDrift, 2) : '-',
                $status,
            ];

            if ($hasMismatch) {
                $mismatches[] = [
                    'staff_due' => $staffDue,
                    'user_name' => $userName,
                    'actual_collected' => $actualCollected,
                    'actual_submitted' => $actualSubmitted,
                    'expected_due' => $expectedDue,
                    'collected_drift' => $collectedDrift,
                    'due_drift' => $dueDrift,
                    'submitted_fallback' => $actualSubmittedFallback,
                ];
            } else {
                $totalCorrect++;
            }
        }

        $this->table(
            ['User ID', 'Name', 'Rec. Collected', 'Act. Collected', 'Coll. Drift', 'Rec. Due', 'Exp. Due', 'Due Drift', 'Status'],
            $rows
        );
        $this->newLine();

        $this->info("━━━ Summary ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  Total staff records checked: {$totalChecked}");
        $this->info("  Correct records: {$totalCorrect}");

        if (empty($mismatches)) {
            $this->info("  All StaffDue records match payment records perfectly.");
        } else {
            $this->warn("  Mismatched records: " . count($mismatches));
            $this->newLine();

            $this->info("━━━ Mismatch Details ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            foreach ($mismatches as $m) {
                $sd = $m['staff_due'];
                $this->warn("  Staff: {$m['user_name']} (ID: {$sd->user_id})");
                if ($m['collected_drift'] != 0) {
                    $direction = $m['collected_drift'] > 0 ? 'OVER-counted' : 'UNDER-counted';
                    $this->line("    Collection drift: ৳" . number_format(abs($m['collected_drift']), 2) . " ({$direction})");
                }
                if ($m['due_drift'] != 0) {
                    $direction = $m['due_drift'] > 0 ? 'OVER-stated' : 'UNDER-stated';
                    $this->line("    Due drift: ৳" . number_format(abs($m['due_drift']), 2) . " ({$direction})");
                }

                $recentPayments = Payment::where('collected_by', $sd->user_id)
                    ->where('status', '!=', 'rejected')
                    ->latest('payment_date')
                    ->limit(3)
                    ->get(['id', 'amount', 'payment_date', 'payment_no']);
                if ($recentPayments->isNotEmpty()) {
                    $this->line("    Recent payments:");
                    foreach ($recentPayments as $p) {
                        $this->line("      - {$p->payment_no}: ৳" . number_format($p->amount, 2) . " on {$p->payment_date}");
                    }
                }
                $this->newLine();
            }

            if ($fix) {
                $this->info("━━━ Applying Fixes ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $fixed = 0;
                foreach ($mismatches as $m) {
                    $sd = $m['staff_due'];
                    $oldCollected = $sd->total_collected;
                    $oldDue = $sd->current_due;

                    DB::transaction(function () use ($sd, $m) {
                        $sd = StaffDue::lockForUpdate()->find($sd->id);
                        $sd->total_collected = $m['actual_collected'];
                        if (!$m['submitted_fallback']) {
                            $sd->total_submitted = $m['actual_submitted'];
                        }
                        $sd->current_due = $m['expected_due'];
                        $sd->save();
                    });

                    $fixed++;
                    $this->line("  ✓ Fixed {$m['user_name']}: collected ৳{$oldCollected}→৳" . number_format($m['actual_collected'], 2) .
                        ", due ৳{$oldDue}→৳" . number_format($m['expected_due'], 2));

                    Log::channel('daily')->warning("StaffDue corrected by audit", [
                        'user_id' => $sd->user_id,
                        'old_collected' => $oldCollected,
                        'new_collected' => $m['actual_collected'],
                        'old_due' => $oldDue,
                        'new_due' => $m['expected_due'],
                        'collected_drift' => $m['collected_drift'],
                        'due_drift' => $m['due_drift'],
                    ]);
                }
                $this->info("  Fixed {$fixed}/" . count($mismatches) . " records.");
            } else {
                $this->comment("  Run with --fix to automatically correct mismatched records.");
            }
        }

        $this->newLine();
        $this->info("━━━ Duplicate Collection Detection ━━━━━━━━━━━━━━━━━━━━━");
        $duplicates = Payment::select('client_id', 'amount', 'payment_date', DB::raw('COUNT(*) as count'))
            ->where('status', '!=', 'rejected')
            ->whereNotNull('collected_by')
            ->where('payment_date', '>=', now()->subDays(7))
            ->groupBy('client_id', 'amount', 'payment_date')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->limit(10)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info("  No duplicate collections detected in last 7 days.");
        } else {
            $this->warn("  Potential duplicate collections found:");
            foreach ($duplicates as $dup) {
                $client = \App\Models\Client::find($dup->client_id);
                $this->line("    Client: " . ($client->client_id ?? $dup->client_id) .
                    " | Amount: ৳" . number_format($dup->amount, 2) .
                    " | Date: {$dup->payment_date} | Count: {$dup->count}");
            }
        }

        $severity = empty($mismatches) ? 'OK' : (count($mismatches) > 3 ? 'CRITICAL' : 'WARNING');
        $this->newLine();
        $color = match($severity) {
            'CRITICAL' => 'error',
            'WARNING' => 'warn',
            default => 'info',
        };
        $this->$color("Overall Status: {$severity}");

        Log::channel('daily')->info("StaffDue integrity audit completed", [
            'total_checked' => $totalChecked,
            'correct' => $totalCorrect,
            'mismatches' => count($mismatches),
            'status' => $severity,
        ]);

        return empty($mismatches) ? self::SUCCESS : self::FAILURE;
    }
}
