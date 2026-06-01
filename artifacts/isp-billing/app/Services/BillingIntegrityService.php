<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Client;
use App\Models\Payment;
use App\Models\AdvancePayment;
use App\Models\CorrectionAuditLog;
use App\Models\IntegrityCheckLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BillingIntegrityService
{
    const CACHE_KEY_LAST_CHECK = 'billing_integrity_last_check';
    const CACHE_KEY_ISSUES = 'billing_integrity_issues';
    
    protected array $issues = [];
    protected array $autoCorrected = [];
    protected array $requiresReview = [];
    protected int $clientsChecked = 0;
    protected int $billsChecked = 0;
    
    public function runFullIntegrityCheck(bool $autoCorrectSafe = true): array
    {
        $startTime = microtime(true);
        $this->resetCounters();
        
        Log::info('Billing Integrity Check started', [
            'auto_correct' => $autoCorrectSafe,
            'timestamp' => now()->toISOString()
        ]);
        
        DB::beginTransaction();
        try {
            $this->checkClientBalances($autoCorrectSafe);
            $this->checkBillAmounts($autoCorrectSafe);
            $this->checkPaymentBillLinks($autoCorrectSafe);
            $this->checkDuplicateBills();
            $this->checkOrphanedPayments();
            $this->checkAdvanceBalances($autoCorrectSafe);
            $this->checkBillStatusConsistency($autoCorrectSafe);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Billing Integrity Check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        $duration = round(microtime(true) - $startTime, 2);
        
        $result = [
            'status' => count($this->requiresReview) > 0 ? 'issues_found' : 'healthy',
            'checked_at' => now()->toISOString(),
            'duration_seconds' => $duration,
            'clients_checked' => $this->clientsChecked,
            'bills_checked' => $this->billsChecked,
            'issues_found' => count($this->issues),
            'auto_corrected' => count($this->autoCorrected),
            'requires_review' => count($this->requiresReview),
            'issues' => $this->issues,
            'auto_corrected_details' => $this->autoCorrected,
            'requires_review_details' => $this->requiresReview,
        ];
        
        $this->logIntegrityCheck($result);
        $this->cacheResults($result);
        $this->notifySuperAdminIfNeeded($result);
        
        return $result;
    }
    
    protected function resetCounters(): void
    {
        $this->issues = [];
        $this->autoCorrected = [];
        $this->requiresReview = [];
        $this->clientsChecked = 0;
        $this->billsChecked = 0;
    }
    
    protected function checkClientBalances(bool $autoCorrect): void
    {
        Client::whereNotNull('id')->chunkById(500, function ($clients) use ($autoCorrect) {
            foreach ($clients as $client) {
                $this->clientsChecked++;
                
                $calculatedDue = (float) $client->bills()
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->where('is_cancelled', false)
                    ->sum('due_amount');
                
                $calculatedAdvance = (float) $client->advancePayments()
                    ->where('remaining_amount', '>', 0)
                    ->sum('remaining_amount');
                
                $storedDue = (float) ($client->due_amount ?? 0);
                $storedBalance = (float) ($client->balance ?? 0);
                
                $dueMismatch = abs($calculatedDue - $storedDue) > 0.01;
                $balanceMismatch = abs($calculatedAdvance - $storedBalance) > 0.01;
                
                if ($dueMismatch || $balanceMismatch) {
                    $issue = [
                        'type' => 'client_balance_mismatch',
                        'client_id' => $client->id,
                        'client_name' => $client->name,
                        'client_code' => $client->client_code,
                        'stored_due' => $storedDue,
                        'calculated_due' => $calculatedDue,
                        'due_difference' => round($calculatedDue - $storedDue, 2),
                        'stored_balance' => $storedBalance,
                        'calculated_balance' => $calculatedAdvance,
                        'balance_difference' => round($calculatedAdvance - $storedBalance, 2),
                    ];
                    
                    $this->issues[] = $issue;
                    
                    if ($autoCorrect) {
                        $client->due_amount = round($calculatedDue, 2);
                        $client->balance = round($calculatedAdvance, 2);
                        $client->save();
                        
                        $this->autoCorrected[] = array_merge($issue, [
                            'correction' => 'Balance recalculated from bills and advance payments'
                        ]);
                        
                        CorrectionAuditLog::log('Client', $client->id, 'integrity_auto_correct', [
                            'due_amount' => $storedDue,
                            'balance' => $storedBalance,
                        ], [
                            'due_amount' => $client->due_amount,
                            'balance' => $client->balance,
                        ], null, 'system');
                    }
                }
            }
        });
    }
    
    protected function checkBillAmounts(bool $autoCorrect): void
    {
        Bill::where('is_cancelled', false)->chunkById(1000, function ($bills) use ($autoCorrect) {
            foreach ($bills as $bill) {
                $this->billsChecked++;
                
                $totalPaid = (float) $bill->payments()->sum('amount');
                $expectedDue = max(0, $bill->total_amount - $totalPaid);
                
                $paidMismatch = abs($bill->paid_amount - $totalPaid) > 0.01;
                $dueMismatch = abs($bill->due_amount - $expectedDue) > 0.01;
                
                if ($paidMismatch || $dueMismatch) {
                    $issue = [
                        'type' => 'bill_amount_mismatch',
                        'bill_id' => $bill->id,
                        'bill_no' => $bill->bill_no,
                        'client_id' => $bill->client_id,
                        'stored_paid' => $bill->paid_amount,
                        'calculated_paid' => $totalPaid,
                        'stored_due' => $bill->due_amount,
                        'calculated_due' => $expectedDue,
                    ];
                    
                    $this->issues[] = $issue;
                    
                    if ($autoCorrect) {
                        $beforeState = $bill->toArray();
                        
                        $bill->paid_amount = round($totalPaid, 2);
                        $bill->due_amount = round($expectedDue, 2);
                        
                        if ($bill->due_amount <= 0) {
                            $bill->status = 'paid';
                        } elseif ($bill->paid_amount > 0) {
                            $bill->status = 'partial';
                        } else {
                            $bill->status = 'unpaid';
                        }
                        
                        $bill->save();
                        
                        $this->autoCorrected[] = array_merge($issue, [
                            'correction' => 'Bill amounts recalculated from linked payments'
                        ]);
                        
                        CorrectionAuditLog::log('Bill', $bill->id, 'integrity_auto_correct', $beforeState, $bill->toArray(), null, 'system');
                    }
                }
            }
        });
    }
    
    protected function checkPaymentBillLinks(bool $autoCorrect): void
    {
        Payment::whereNull('bill_id')
            ->where('status', 'approved')
            ->chunkById(500, function ($payments) use ($autoCorrect) {
                foreach ($payments as $payment) {
                    $client = $payment->client;
                    if (!$client) continue;
                    
                    $oldestUnpaidBill = $client->bills()
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->where('is_cancelled', false)
                        ->orderBy('billing_year')
                        ->orderBy('billing_month')
                        ->first();
                    
                    if ($oldestUnpaidBill) {
                        $issue = [
                            'type' => 'unlinked_payment_with_outstanding_bills',
                            'payment_id' => $payment->id,
                            'payment_no' => $payment->payment_no,
                            'client_id' => $client->id,
                            'client_name' => $client->name,
                            'amount' => $payment->amount,
                            'payment_date' => $payment->payment_date,
                            'oldest_unpaid_bill_id' => $oldestUnpaidBill->id,
                        ];
                        
                        if ($autoCorrect) {
                            $beforeState = ['bill_id' => null];
                            $payment->bill_id = $oldestUnpaidBill->id;
                            $payment->save();
                            
                            $this->autoCorrected[] = array_merge($issue, [
                                'correction' => "Linked to bill #{$oldestUnpaidBill->id} (FIFO)"
                            ]);
                            
                            CorrectionAuditLog::log('Payment', $payment->id, 'integrity_link_to_bill', 
                                $beforeState, 
                                ['bill_id' => $oldestUnpaidBill->id], 
                                null, 'system'
                            );
                        } else {
                            $this->requiresReview[] = $issue;
                        }
                        
                        $this->issues[] = $issue;
                    }
                }
            });
    }
    
    protected function checkDuplicateBills(): void
    {
        $duplicates = DB::table('bills')
            ->select('client_id', 'billing_month', 'billing_year', DB::raw('COUNT(*) as duplicate_count'))
            ->where('is_cancelled', false)
            ->groupBy('client_id', 'billing_month', 'billing_year')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        
        foreach ($duplicates as $dup) {
            $client = Client::find($dup->client_id);
            $issue = [
                'type' => 'duplicate_bills',
                'client_id' => $dup->client_id,
                'client_name' => $client?->name ?? 'Unknown',
                'billing_period' => "{$dup->billing_year}-" . str_pad($dup->billing_month, 2, '0', STR_PAD_LEFT),
                'count' => $dup->duplicate_count,
                'severity' => 'critical',
            ];
            
            $this->requiresReview[] = $issue;
            $this->issues[] = $issue;
        }
    }
    
    protected function checkOrphanedPayments(): void
    {
        Payment::whereNotNull('bill_id')
            ->whereDoesntHave('bill')
            ->chunkById(500, function ($payments) {
                foreach ($payments as $payment) {
                    $issue = [
                        'type' => 'orphaned_payment',
                        'payment_id' => $payment->id,
                        'payment_no' => $payment->payment_no,
                        'bill_id' => $payment->bill_id,
                        'amount' => $payment->amount,
                        'severity' => 'high',
                    ];
                    
                    $this->requiresReview[] = $issue;
                    $this->issues[] = $issue;
                }
            });
    }
    
    protected function checkAdvanceBalances(bool $autoCorrect): void
    {
        AdvancePayment::where('remaining_amount', '>', 0)
            ->chunkById(500, function ($advances) use ($autoCorrect) {
                foreach ($advances as $advance) {
                    if ($advance->remaining_amount > $advance->amount) {
                        $issue = [
                            'type' => 'invalid_advance_remaining',
                            'advance_id' => $advance->id,
                            'client_id' => $advance->client_id,
                            'original_amount' => $advance->amount,
                            'remaining_amount' => $advance->remaining_amount,
                            'severity' => 'medium',
                        ];
                        
                        $this->issues[] = $issue;
                        
                        if ($autoCorrect) {
                            $beforeState = [
                                'remaining_amount' => $advance->remaining_amount,
                                'used_amount' => $advance->used_amount,
                            ];
                            
                            $advance->remaining_amount = max(0, $advance->amount - ($advance->used_amount ?? 0));
                            $advance->save();
                            
                            $this->autoCorrected[] = array_merge($issue, [
                                'correction' => 'Remaining amount corrected based on used amount'
                            ]);
                            
                            CorrectionAuditLog::log('AdvancePayment', $advance->id, 'integrity_auto_correct', 
                                $beforeState, 
                                ['remaining_amount' => $advance->remaining_amount], 
                                null, 'system'
                            );
                        }
                    }
                }
            });
    }
    
    protected function checkBillStatusConsistency(bool $autoCorrect): void
    {
        Bill::where('is_cancelled', false)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'paid')->where('due_amount', '>', 0);
                })->orWhere(function ($q) {
                    $q->where('status', 'unpaid')->where('paid_amount', '>', 0);
                })->orWhere(function ($q) {
                    $q->where('status', 'partial')
                      ->where(function ($sq) {
                          $sq->where('paid_amount', '<=', 0)
                             ->orWhere('due_amount', '<=', 0);
                      });
                });
            })
            ->chunkById(500, function ($bills) use ($autoCorrect) {
                foreach ($bills as $bill) {
                    $correctStatus = 'unpaid';
                    if ($bill->due_amount <= 0) {
                        $correctStatus = 'paid';
                    } elseif ($bill->paid_amount > 0) {
                        $correctStatus = 'partial';
                    }
                    
                    $issue = [
                        'type' => 'bill_status_inconsistent',
                        'bill_id' => $bill->id,
                        'bill_no' => $bill->bill_no,
                        'current_status' => $bill->status,
                        'correct_status' => $correctStatus,
                        'paid_amount' => $bill->paid_amount,
                        'due_amount' => $bill->due_amount,
                    ];
                    
                    $this->issues[] = $issue;
                    
                    if ($autoCorrect) {
                        $beforeStatus = $bill->status;
                        $bill->status = $correctStatus;
                        $bill->save();
                        
                        $this->autoCorrected[] = array_merge($issue, [
                            'correction' => "Status changed from {$beforeStatus} to {$correctStatus}"
                        ]);
                        
                        CorrectionAuditLog::log('Bill', $bill->id, 'integrity_status_correct', 
                            ['status' => $beforeStatus], 
                            ['status' => $correctStatus], 
                            null, 'system'
                        );
                    }
                }
            });
    }
    
    protected function logIntegrityCheck(array $result): void
    {
        IntegrityCheckLog::create([
            'check_type' => 'full_billing_integrity',
            'status' => $result['status'],
            'clients_checked' => $result['clients_checked'],
            'bills_checked' => $result['bills_checked'],
            'issues_found' => $result['issues_found'],
            'auto_corrected' => $result['auto_corrected'],
            'requires_review' => $result['requires_review'],
            'details' => json_encode([
                'issues' => $result['issues'],
                'auto_corrected_details' => $result['auto_corrected_details'],
                'requires_review_details' => $result['requires_review_details'],
            ]),
            'duration_seconds' => $result['duration_seconds'],
            'run_by' => auth()->id(),
        ]);
    }
    
    protected function cacheResults(array $result): void
    {
        Cache::put(self::CACHE_KEY_LAST_CHECK, now()->toISOString(), now()->addDay());
        Cache::put(self::CACHE_KEY_ISSUES, [
            'count' => $result['requires_review'],
            'details' => $result['requires_review_details'],
        ], now()->addDay());
    }
    
    protected function notifySuperAdminIfNeeded(array $result): void
    {
        if ($result['requires_review'] > 0 || $result['issues_found'] > 0) {
            Log::warning('Billing Integrity Check found issues requiring Super Admin review', [
                'issues_count' => $result['requires_review'],
                'total_issues' => $result['issues_found'],
                'issues' => $result['requires_review_details']
            ]);
            
            $this->createSuperAdminAlert($result);
        }
    }
    
    protected function createSuperAdminAlert(array $result): void
    {
        try {
            $superAdmins = \App\Models\User::role('Super Admin')->get();
            
            foreach ($superAdmins as $admin) {
                DB::table('notification_queue')->insert([
                    'recipient_type' => 'user',
                    'recipient_id' => $admin->id,
                    'type' => 'integrity_alert',
                    'title' => 'Billing Integrity Issues Detected',
                    'message' => "Integrity check found {$result['issues_found']} issue(s). " .
                                 "{$result['auto_corrected']} auto-corrected, " .
                                 "{$result['requires_review']} require manual review.",
                    'priority' => $result['requires_review'] > 0 ? 'high' : 'medium',
                    'data' => json_encode([
                        'route' => 'admin.super-admin.integrity',
                        'issues_found' => $result['issues_found'],
                        'requires_review' => $result['requires_review'],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            CorrectionAuditLog::log('System', 0, 'integrity_alert_sent', null, [
                'recipients' => $superAdmins->pluck('id')->toArray(),
                'issues_found' => $result['issues_found'],
                'requires_review' => $result['requires_review'],
            ], null, 'system');
            
        } catch (\Exception $e) {
            Log::error('Failed to send Super Admin integrity alert: ' . $e->getMessage());
        }
    }
    
    public function getLastCheckStatus(): ?array
    {
        $lastCheck = IntegrityCheckLog::orderBy('created_at', 'desc')->first();
        
        if (!$lastCheck) {
            return null;
        }
        
        return [
            'checked_at' => $lastCheck->created_at->toISOString(),
            'status' => $lastCheck->status,
            'issues_found' => $lastCheck->issues_found,
            'auto_corrected' => $lastCheck->auto_corrected,
            'requires_review' => $lastCheck->requires_review,
            'details' => json_decode($lastCheck->details, true),
        ];
    }
    
    public function getPendingIssues(): array
    {
        $cached = Cache::get(self::CACHE_KEY_ISSUES);
        return $cached ?? ['count' => 0, 'details' => []];
    }
    
    public function runQuickHealthCheck(): array
    {
        $issues = [];
        
        $clientMismatchCount = DB::select("
            SELECT COUNT(*) as count FROM clients c
            WHERE ABS(c.due_amount - COALESCE((
                SELECT SUM(b.due_amount) FROM bills b 
                WHERE b.client_id = c.id 
                AND b.status IN ('unpaid', 'partial') 
                AND b.is_cancelled = false
            ), 0)) > 0.01
        ")[0]->count ?? 0;
        
        $duplicateBillCount = DB::table('bills')
            ->select(DB::raw('COUNT(*) as count'))
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('bills')
                    ->where('is_cancelled', false)
                    ->groupBy('client_id', 'billing_month', 'billing_year')
                    ->havingRaw('COUNT(*) > 1');
            })
            ->first()->count ?? 0;
        
        return [
            'status' => ($clientMismatchCount == 0 && $duplicateBillCount == 0) ? 'healthy' : 'issues_detected',
            'client_balance_mismatches' => $clientMismatchCount,
            'duplicate_bills' => $duplicateBillCount,
            'checked_at' => now()->toISOString(),
        ];
    }
}
