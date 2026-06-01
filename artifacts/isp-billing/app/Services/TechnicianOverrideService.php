<?php

namespace App\Services;

use App\Models\TechnicianActionLog;
use App\Models\AdminOverride;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\StaffDue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechnicianOverrideService
{
    protected ?MikroTikService $mikrotikService = null;

    public function __construct()
    {
        if (class_exists(MikroTikService::class)) {
            $this->mikrotikService = new MikroTikService();
        }
    }

    public function overridePayment(
        TechnicianActionLog $actionLog,
        int $adminId,
        string $overrideType,
        array $correctionData,
        string $reason
    ): array {
        return DB::transaction(function () use ($actionLog, $adminId, $overrideType, $correctionData, $reason) {
            $beforeValues = $actionLog->action_data;
            $balanceAdjustment = 0;
            $afterValues = [];
            $networkSyncRequired = false;

            $payment = Payment::find($actionLog->reference_id);
            if (!$payment) {
                throw new \Exception('Payment not found');
            }

            $client = $payment->client;

            if ($payment->status === 'cancelled' || $payment->status === 'reversed') {
                throw new \Exception('Payment already cancelled or reversed');
            }

            $billingService = app(\App\Services\BillingService::class);

            switch ($overrideType) {
                case AdminOverride::TYPE_EDIT:
                    $oldAmount = $payment->amount;
                    $newAmount = $correctionData['amount'] ?? $oldAmount;
                    $difference = $newAmount - $oldAmount;

                    $payment->update([
                        'amount' => $newAmount,
                        'payment_method' => $correctionData['payment_method'] ?? $payment->payment_method,
                        'notes' => $payment->notes . "\n[CORRECTED by Admin: {$reason}]",
                    ]);

                    if ($payment->bill) {
                        $bill = $payment->bill;
                        $bill->paid_amount += $difference;
                        $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
                        if ($bill->due_amount <= 0) {
                            $bill->status = 'paid';
                        } elseif ($bill->paid_amount > 0) {
                            $bill->status = 'partial';
                        } else {
                            $bill->status = 'unpaid';
                        }
                        $bill->save();
                    }

                    if ($client) {
                        $billingService->recalculateClientBalance($client);
                        $balanceAdjustment = -$difference;
                    }

                    $afterValues = ['amount' => $newAmount, 'payment_method' => $payment->payment_method];
                    break;

                case AdminOverride::TYPE_CANCEL:
                    $originalAmount = $payment->amount;
                    $payment->update(['status' => 'cancelled']);
                    $balanceAdjustment = $originalAmount;

                    if ($payment->bill) {
                        $bill = $payment->bill;
                        $bill->paid_amount -= $originalAmount;
                        $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
                        if ($bill->due_amount > 0 && $bill->paid_amount > 0) {
                            $bill->status = 'partial';
                        } elseif ($bill->due_amount > 0) {
                            $bill->status = 'unpaid';
                        }
                        $bill->save();
                    }

                    if ($client) {
                        $billingService->recalculateClientBalance($client);
                        $networkSyncRequired = $client->due_amount > 0;
                    }

                    $afterValues = ['status' => 'cancelled'];
                    break;

                case AdminOverride::TYPE_REVERSE:
                    $payment->update(['status' => 'reversed']);

                    if ($payment->bill) {
                        $bill = $payment->bill;
                        $bill->paid_amount -= $payment->amount;
                        $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
                        if ($bill->due_amount > 0 && $bill->paid_amount > 0) {
                            $bill->status = 'partial';
                        } elseif ($bill->due_amount > 0) {
                            $bill->status = 'unpaid';
                        }
                        $bill->save();
                    }

                    $balanceAdjustment = $payment->amount;
                    if ($client) {
                        $billingService->recalculateClientBalance($client);
                        $networkSyncRequired = $client->due_amount > 0;
                    }

                    $afterValues = ['reversed' => true, 'reversal_amount' => -$payment->amount];
                    break;
            }

            $this->updateStaffDue($actionLog->technician_id, $balanceAdjustment);

            $actionLog->markOverridden($adminId, TechnicianActionLog::STATUS_CORRECTED);

            $override = AdminOverride::createOverride(
                $adminId,
                $actionLog,
                $overrideType,
                $afterValues,
                $reason,
                $balanceAdjustment,
                $networkSyncRequired
            );

            if ($networkSyncRequired && $client && $this->mikrotikService) {
                $this->syncClientNetwork($client, $override);
            }

            return [
                'success' => true,
                'override_id' => $override->id,
                'balance_adjustment' => $balanceAdjustment,
            ];
        });
    }

    public function overrideCashSettlement(
        TechnicianActionLog $actionLog,
        int $adminId,
        string $overrideType,
        array $correctionData,
        string $reason
    ): array {
        return DB::transaction(function () use ($actionLog, $adminId, $overrideType, $correctionData, $reason) {
            $beforeValues = $actionLog->action_data;
            $technicianId = $actionLog->technician_id;
            $afterValues = [];

            $oldAmount = $beforeValues['amount'] ?? 0;
            $newAmount = $correctionData['corrected_amount'] ?? $oldAmount;
            $difference = $newAmount - $oldAmount;

            if ($overrideType === AdminOverride::TYPE_CORRECT) {
                $this->updateStaffDue($technicianId, -$difference);
                $afterValues = ['corrected_amount' => $newAmount, 'difference' => $difference];
            } elseif ($overrideType === AdminOverride::TYPE_CANCEL) {
                $this->updateStaffDue($technicianId, $oldAmount);
                $afterValues = ['cancelled' => true, 'restored_due' => $oldAmount];
            }

            $actionLog->markOverridden($adminId, TechnicianActionLog::STATUS_CORRECTED);

            $override = AdminOverride::createOverride(
                $adminId,
                $actionLog,
                $overrideType,
                $afterValues,
                $reason,
                $difference
            );

            return [
                'success' => true,
                'override_id' => $override->id,
                'staff_due_adjustment' => $difference,
            ];
        });
    }

    public function overrideBill(
        TechnicianActionLog $actionLog,
        int $adminId,
        string $overrideType,
        array $correctionData,
        string $reason
    ): array {
        return DB::transaction(function () use ($actionLog, $adminId, $overrideType, $correctionData, $reason) {
            $bill = Bill::find($actionLog->reference_id);
            if (!$bill) {
                throw new \Exception('Bill not found');
            }

            $client = $bill->client;
            $beforeValues = $actionLog->action_data;
            $balanceAdjustment = 0;
            $afterValues = [];

            $originalStatus = $bill->status;
            
            switch ($overrideType) {
                case AdminOverride::TYPE_EDIT:
                    $oldAmount = $bill->amount;
                    $newAmount = $correctionData['amount'] ?? $oldAmount;
                    $balanceAdjustment = $newAmount - $oldAmount;

                    $bill->update([
                        'amount' => $newAmount,
                        'due_date' => $correctionData['due_date'] ?? $bill->due_date,
                        'notes' => $bill->notes . "\n[CORRECTED by Admin: {$reason}]",
                    ]);

                    if ($client && $originalStatus === 'unpaid' && $balanceAdjustment != 0) {
                        $client->increment('due_amount', $balanceAdjustment);
                    }

                    $afterValues = ['amount' => $newAmount, 'due_date' => $bill->due_date];
                    break;

                case AdminOverride::TYPE_CANCEL:
                    if ($originalStatus === 'paid') {
                        throw new \Exception('Cannot cancel a paid bill');
                    }
                    
                    $originalAmount = $bill->total_amount;
                    $bill->update(['status' => 'cancelled', 'is_cancelled' => true, 'due_amount' => 0]);

                    $billingService = app(\App\Services\BillingService::class);
                    $billingService->recalculateClientBalance($client);

                    $afterValues = ['status' => 'cancelled', 'is_cancelled' => true];
                    break;
            }

            $actionLog->markOverridden($adminId, TechnicianActionLog::STATUS_CORRECTED);

            $override = AdminOverride::createOverride(
                $adminId,
                $actionLog,
                $overrideType,
                $afterValues,
                $reason,
                $balanceAdjustment
            );

            return [
                'success' => true,
                'override_id' => $override->id,
                'balance_adjustment' => $balanceAdjustment,
            ];
        });
    }

    public function overrideClientStatus(
        TechnicianActionLog $actionLog,
        int $adminId,
        string $overrideType,
        array $correctionData,
        string $reason
    ): array {
        return DB::transaction(function () use ($actionLog, $adminId, $overrideType, $correctionData, $reason) {
            $client = Client::find($actionLog->client_id);
            if (!$client) {
                throw new \Exception('Client not found');
            }

            $beforeStatus = $actionLog->action_data['status'] ?? $client->status;
            $afterValues = [];
            $networkSyncRequired = true;

            if ($overrideType === AdminOverride::TYPE_REVERSE) {
                $originalStatus = $actionLog->before_values['status'] ?? 'active';
                $client->update(['status' => $originalStatus]);
                $afterValues = ['status' => $originalStatus, 'reversed_to' => $originalStatus];
            } elseif ($overrideType === AdminOverride::TYPE_CORRECT) {
                $newStatus = $correctionData['status'] ?? $beforeStatus;
                $client->update(['status' => $newStatus]);
                $afterValues = ['status' => $newStatus];
            }

            $actionLog->markOverridden($adminId, TechnicianActionLog::STATUS_REVERSED);

            $override = AdminOverride::createOverride(
                $adminId,
                $actionLog,
                $overrideType,
                $afterValues,
                $reason,
                0,
                $networkSyncRequired
            );

            if ($this->mikrotikService) {
                $this->syncClientNetwork($client, $override);
            }

            return [
                'success' => true,
                'override_id' => $override->id,
                'new_status' => $client->status,
            ];
        });
    }

    public function overrideClientData(
        TechnicianActionLog $actionLog,
        int $adminId,
        string $overrideType,
        array $correctionData,
        string $reason
    ): array {
        return DB::transaction(function () use ($actionLog, $adminId, $overrideType, $correctionData, $reason) {
            $afterValues = [];

            if ($overrideType === AdminOverride::TYPE_EDIT) {
                $afterValues = ['edited_data' => $correctionData];
            } elseif ($overrideType === AdminOverride::TYPE_CANCEL) {
                $afterValues = ['cancelled' => true];
            }

            $actionLog->markOverridden($adminId, TechnicianActionLog::STATUS_CORRECTED);

            $override = AdminOverride::createOverride(
                $adminId,
                $actionLog,
                $overrideType,
                $afterValues,
                $reason
            );

            return [
                'success' => true,
                'override_id' => $override->id,
            ];
        });
    }

    public function overrideNetworkAction(
        TechnicianActionLog $actionLog,
        int $adminId,
        string $overrideType,
        array $correctionData,
        string $reason
    ): array {
        return DB::transaction(function () use ($actionLog, $adminId, $overrideType, $correctionData, $reason) {
            $client = Client::find($actionLog->client_id);
            $afterValues = [];
            $networkSyncRequired = true;

            if ($overrideType === AdminOverride::TYPE_REVERSE) {
                $afterValues = ['reversed' => true, 'original_action' => $actionLog->action_type];
            } elseif ($overrideType === AdminOverride::TYPE_CANCEL) {
                $afterValues = ['cancelled' => true];
                $networkSyncRequired = false;
            }

            $actionLog->markOverridden($adminId, TechnicianActionLog::STATUS_REVERSED);

            $override = AdminOverride::createOverride(
                $adminId,
                $actionLog,
                $overrideType,
                $afterValues,
                $reason,
                0,
                $networkSyncRequired
            );

            if ($networkSyncRequired && $client && $this->mikrotikService) {
                $this->syncClientNetwork($client, $override);
            }

            return [
                'success' => true,
                'override_id' => $override->id,
                'network_action_triggered' => $networkSyncRequired,
            ];
        });
    }

    protected function updateStaffDue(int $userId, float $amount): void
    {
        if ($amount == 0) return;

        $staffDue = StaffDue::firstOrCreate(
            ['user_id' => $userId],
            ['current_due' => 0, 'total_collected' => 0, 'total_submitted' => 0]
        );

        if ($amount > 0) {
            $staffDue->increment('current_due', $amount);
        } else {
            $staffDue->decrement('current_due', abs($amount));
        }
    }

    protected function syncClientNetwork(Client $client, AdminOverride $override): void
    {
        try {
            if ($client->status === 'active') {
                $result = $this->mikrotikService->restoreClient($client, 'admin_override', $override->admin_id, false);
            } else {
                $result = $this->mikrotikService->suspendClient($client, 'admin_override', $override->admin_id, false);
            }

            if ($result['success'] ?? false) {
                $override->markNetworkSynced();
            }
        } catch (\Exception $e) {
            Log::error("Network sync failed for override {$override->id}: " . $e->getMessage());
        }
    }

    public function getOverrideHistory(?int $clientId = null, ?int $technicianId = null, int $limit = 50): array
    {
        $query = AdminOverride::with(['admin', 'technicianActionLog.technician', 'client'])
            ->orderBy('created_at', 'desc');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($technicianId) {
            $query->whereHas('technicianActionLog', function ($q) use ($technicianId) {
                $q->where('technician_id', $technicianId);
            });
        }

        return $query->limit($limit)->get()->toArray();
    }

    public function getPendingNetworkSyncs(): array
    {
        return AdminOverride::pendingNetworkSync()
            ->with(['client', 'technicianActionLog'])
            ->get()
            ->toArray();
    }
}
