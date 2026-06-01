<?php

namespace App\Services;

use App\Models\CardDistributor;
use App\Models\CardTransaction;
use App\Models\Client;
use App\Models\Payment;
use App\Models\ScratchCard;
use App\Models\ScratchCardBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CardBillingService
{
    public function createBatch(array $data): ScratchCardBatch
    {
        return DB::transaction(function () use ($data) {
            $batch = ScratchCardBatch::create([
                'batch_no' => ScratchCardBatch::generateBatchNo(),
                'name' => $data['name'],
                'package_id' => $data['package_id'] ?? null,
                'amount' => $data['amount'],
                'validity_days' => $data['validity_days'] ?? 30,
                'total_cards' => $data['total_cards'],
                'active_cards' => $data['total_cards'],
                'status' => 'active',
                'created_by' => auth()->id(),
                'expires_at' => isset($data['expires_at']) ? $data['expires_at'] : (($data['validity_days'] ?? null) && ($data['validity_days'] ?? 30) != 30 ? now()->addDays($data['validity_days']) : now()->addMonthNoOverflow()),
            ]);

            for ($i = 0; $i < $data['total_cards']; $i++) {
                $pin = ScratchCard::generatePin();
                ScratchCard::create([
                    'batch_id' => $batch->id,
                    'card_number' => ScratchCard::generateCardNumber(),
                    'pin' => Hash::make($pin),
                    'serial_number' => ScratchCard::generateSerial(),
                    'amount' => $data['amount'],
                    'validity_days' => $data['validity_days'] ?? 30,
                    'package_id' => $data['package_id'] ?? null,
                    'status' => 'active',
                    'expires_at' => $batch->expires_at,
                ]);
            }

            return $batch;
        });
    }

    public function redeemCard(string $cardNumber, string $pin, Client $client): array
    {
        return DB::transaction(function () use ($cardNumber, $pin, $client) {
            $card = ScratchCard::where('card_number', $cardNumber)->lockForUpdate()->first();

            if (!$card) {
                return ['success' => false, 'error' => 'Invalid card number.'];
            }

            if (!Hash::check($pin, $card->pin)) {
                return ['success' => false, 'error' => 'Invalid PIN.'];
            }

            if ($card->status !== 'active') {
                return ['success' => false, 'error' => 'This card has already been ' . $card->status . '.'];
            }

            if ($card->isExpired()) {
                $card->update(['status' => 'expired']);
                return ['success' => false, 'error' => 'This card has expired.'];
            }

            $card->update([
                'status' => 'used',
                'used_by_client_id' => $client->id,
                'used_at' => now(),
            ]);

            $card->batch->increment('used_cards');
            $card->batch->decrement('active_cards');

            $payment = Payment::create([
                'client_id' => $client->id,
                'company_id' => $client->company_id,
                'payment_no' => 'CARD-' . time() . '-' . mt_rand(100, 999),
                'amount' => $card->amount,
                'payment_date' => now(),
                'payment_method_id' => $this->getCardPaymentMethodId(),
                'collected_by' => auth()->id(),
                'status' => 'approved',
                'source' => 'scratch_card',
                'remarks' => 'Scratch card payment: ' . $cardNumber,
            ]);

            CardTransaction::create([
                'scratch_card_id' => $card->id,
                'client_id' => $client->id,
                'transaction_type' => 'redemption',
                'amount' => $card->amount,
                'status' => 'success',
                'notes' => 'Card redeemed by client',
            ]);

            if ($card->distributor_id) {
                $distributor = $card->distributor;
                if ($distributor) {
                    $commission = $card->amount * ($distributor->commission_rate / 100);
                    $distributor->increment('total_sales', $card->amount);
                    $distributor->increment('total_commission', $commission);
                    $distributor->increment('balance', $commission);
                }
            }

            return [
                'success' => true,
                'message' => 'Card redeemed successfully! ৳' . number_format($card->amount, 2) . ' added to your account.',
                'amount' => $card->amount,
                'payment' => $payment,
            ];
        });
    }

    public function distributeCards(array $cardIds, int $distributorId): int
    {
        $count = 0;
        $now = now();

        foreach ($cardIds as $cardId) {
            $updated = ScratchCard::where('id', $cardId)
                ->where('status', 'active')
                ->whereNull('distributor_id')
                ->update([
                    'distributor_id' => $distributorId,
                    'distributed_at' => $now,
                ]);
            $count += $updated;
        }

        return $count;
    }

    public function disableCard(ScratchCard $card, string $reason = ''): void
    {
        $card->update(['status' => 'disabled']);

        CardTransaction::create([
            'scratch_card_id' => $card->id,
            'transaction_type' => 'disable',
            'amount' => $card->amount,
            'status' => 'success',
            'notes' => $reason,
        ]);

        $card->batch->decrement('active_cards');
    }

    public function getBatchStats(ScratchCardBatch $batch): array
    {
        return [
            'total' => $batch->total_cards,
            'active' => $batch->cards()->where('status', 'active')->count(),
            'used' => $batch->cards()->where('status', 'used')->count(),
            'expired' => $batch->cards()->where('status', 'expired')->count(),
            'disabled' => $batch->cards()->where('status', 'disabled')->count(),
            'distributed' => $batch->cards()->whereNotNull('distributor_id')->count(),
            'total_revenue' => $batch->cards()->where('status', 'used')->sum('amount'),
        ];
    }

    protected function getCardPaymentMethodId(): int
    {
        $method = \App\Models\PaymentMethod::firstOrCreate(
            ['code' => 'scratch_card'],
            ['name' => 'Scratch Card', 'is_active' => true]
        );
        return $method->id;
    }
}
