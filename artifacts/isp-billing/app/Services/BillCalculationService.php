<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Client;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillCalculationService
{
    /**
     * Calculate pro-rated bill for partial month usage
     * 
     * @param float $packagePrice Monthly package price
     * @param Carbon $startDate Start date of usage
     * @param Carbon $endDate End date of usage
     * @return array Calculation details
     */
    public function calculateProRatedBill(float $packagePrice, Carbon $startDate, Carbon $endDate): array
    {
        $totalDaysInMonth = $endDate->daysInMonth;
        $daysUsed = $startDate->diffInDays($endDate) + 1;
        
        // Ensure days don't exceed month
        $daysUsed = min($daysUsed, $totalDaysInMonth);
        
        // Calculate daily rate with 6 decimal precision for accuracy
        $dailyRate = round($packagePrice / $totalDaysInMonth, 6);
        
        // Calculate pro-rated amount
        $proRatedAmount = round($dailyRate * $daysUsed, 2);
        
        return [
            'package_price' => $packagePrice,
            'total_days_in_month' => $totalDaysInMonth,
            'days_used' => $daysUsed,
            'daily_rate' => $dailyRate,
            'pro_rated_amount' => $proRatedAmount,
            'calculation_formula' => "($packagePrice / $totalDaysInMonth) * $daysUsed = $proRatedAmount"
        ];
    }

    /**
     * Calculate full monthly bill with all charges
     * 
     * @param Client $client
     * @param int $billingMonth
     * @param int $billingYear
     * @param array $additionalCharges
     * @return array
     */
    public function calculateMonthlyBill(Client $client, int $billingMonth, int $billingYear, array $additionalCharges = []): array
    {
        $packagePrice = $client->package->price ?? 0;
        $connectionDate = $client->connection_date ? Carbon::parse($client->connection_date) : null;
        
        $billStartDate = Carbon::create($billingYear, $billingMonth, 1);
        $billEndDate = $billStartDate->copy()->endOfMonth();
        
        // Determine if pro-rating is needed
        $isProRated = false;
        $proRatedDetails = null;
        
        if ($connectionDate && $connectionDate->year == $billingYear && $connectionDate->month == $billingMonth) {
            // New connection in billing month - pro-rate
            $isProRated = true;
            $proRatedDetails = $this->calculateProRatedBill($packagePrice, $connectionDate, $billEndDate);
            $baseAmount = $proRatedDetails['pro_rated_amount'];
        } else {
            $baseAmount = $packagePrice;
        }
        
        // Sum additional charges
        $totalAdditionalCharges = 0;
        foreach ($additionalCharges as $charge) {
            $totalAdditionalCharges += round($charge['amount'] ?? 0, 2);
        }
        
        // Apply discounts if any
        $discountAmount = round($client->discount ?? 0, 2);
        
        // Calculate total
        $subtotal = $baseAmount + $totalAdditionalCharges;
        $totalAmount = max(0, $subtotal - $discountAmount); // Never negative
        
        return [
            'client_id' => $client->id,
            'billing_month' => $billingMonth,
            'billing_year' => $billingYear,
            'package_price' => $packagePrice,
            'base_amount' => $baseAmount,
            'is_pro_rated' => $isProRated,
            'pro_rated_details' => $proRatedDetails,
            'additional_charges' => $additionalCharges,
            'total_additional_charges' => $totalAdditionalCharges,
            'discount_amount' => $discountAmount,
            'subtotal' => $subtotal,
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Calculate total amount from package, previous due, and discount
     * Ensures mathematical accuracy with precision handling
     * 
     * @param float $packageAmount Package/fee amount
     * @param float $previousDue Previous unpaid balance
     * @param float $discount Discount amount to apply
     * @return float Total amount (never negative)
     */
    public function calculateTotalAmount(float $packageAmount, float $previousDue, float $discount = 0): float
    {
        // Round all inputs to 2 decimal places for consistency
        $packageAmount = round($packageAmount, 2);
        $previousDue = round($previousDue, 2);
        $discount = round($discount, 2);
        
        // Use bcmath for precision if available
        if (function_exists('bcadd') && function_exists('bcsub')) {
            $subtotal = bcadd((string)$packageAmount, (string)$previousDue, 2);
            $total = bcsub($subtotal, (string)$discount, 2);
            return max(0, (float)$total);
        }
        
        // Fallback to regular calculation with rounding
        $total = round($packageAmount + $previousDue - $discount, 2);
        return max(0, $total);
    }

    /**
     * Calculate due amount for a bill
     * Ensures mathematical accuracy
     * 
     * @param float $totalAmount
     * @param float $paidAmount
     * @return float
     */
    public function calculateDueAmount(float $totalAmount, float $paidAmount): float
    {
        // Use bcmath for precision if available
        if (function_exists('bcsub')) {
            $due = bcsub((string)$totalAmount, (string)$paidAmount, 2);
            return max(0, (float)$due);
        }
        
        // Fallback to regular calculation with rounding
        $due = round($totalAmount - $paidAmount, 2);
        return max(0, $due);
    }

    /**
     * Validate bill calculation accuracy
     * 
     * @param Bill $bill
     * @return array
     */
    public function validateBillAccuracy(Bill $bill): array
    {
        $errors = [];
        $warnings = [];
        
        // Check 1: Total amount should not be negative
        if ($bill->total_amount < 0) {
            $errors[] = "Bill total amount is negative: {$bill->total_amount}";
        }
        
        // Check 2: Paid amount should not exceed total
        if ($bill->paid_amount > $bill->total_amount) {
            $errors[] = "Paid amount ({$bill->paid_amount}) exceeds total amount ({$bill->total_amount})";
        }
        
        // Check 3: Due amount should match calculation
        $expectedDue = $this->calculateDueAmount($bill->total_amount, $bill->paid_amount);
        $actualDue = round($bill->due_amount, 2);
        
        if (abs($actualDue - $expectedDue) > 0.01) {
            $errors[] = "Due amount mismatch: expected {$expectedDue}, actual {$actualDue}";
        }
        
        // Check 4: Verify against actual payments - STRICT accuracy enforcement
        $totalPayments = round(Payment::where('bill_id', $bill->id)->sum('amount'), 2);
        if (abs($totalPayments - $bill->paid_amount) > 0.01) {
            $errors[] = "Paid amount mismatch: bill shows {$bill->paid_amount}, but actual payments total {$totalPayments}";
        }
        
        // Check 5: Status consistency - STRICT enforcement
        if ($bill->due_amount <= 0.01 && $bill->status !== 'paid') {
            $errors[] = "Status mismatch: Bill has no due amount ({$bill->due_amount}) but status is '{$bill->status}' instead of 'paid'";
        }
        if ($bill->due_amount > 0.01 && $bill->paid_amount > 0.01 && $bill->status !== 'partial') {
            $errors[] = "Status mismatch: Bill has partial payment (paid: {$bill->paid_amount}, due: {$bill->due_amount}) but status is '{$bill->status}' instead of 'partial'";
        }
        if ($bill->due_amount > 0.01 && $bill->paid_amount <= 0.01 && $bill->status !== 'unpaid') {
            $errors[] = "Status mismatch: Bill has no payment but status is '{$bill->status}' instead of 'unpaid'";
        }
        
        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
            'calculated_values' => [
                'expected_due' => $expectedDue,
                'actual_due' => $actualDue,
                'total_payments' => $totalPayments,
            ]
        ];
    }

    /**
     * Recalculate and fix bill amounts
     * 
     * @param Bill $bill
     * @return Bill
     */
    public function recalculateBill(Bill $bill): Bill
    {
        DB::beginTransaction();
        try {
            // Get total payments for this bill
            $totalPayments = Payment::where('bill_id', $bill->id)->sum('amount');
            $totalPayments = round($totalPayments, 2);
            
            // Calculate due amount
            $dueAmount = $this->calculateDueAmount($bill->total_amount, $totalPayments);
            
            // Determine status
            $status = 'unpaid';
            if ($dueAmount <= 0) {
                $status = 'paid';
            } elseif ($totalPayments > 0) {
                $status = 'partial';
            }
            
            // Update bill
            $bill->paid_amount = $totalPayments;
            $bill->due_amount = $dueAmount;
            $bill->status = $status;
            $bill->save();
            
            // Update client due amount
            $this->updateClientDueAmount($bill->client_id);
            
            DB::commit();
            
            Log::info("Bill #{$bill->id} recalculated: paid={$totalPayments}, due={$dueAmount}, status={$status}");
            
            return $bill->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Bill recalculation failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update client's total due amount
     * 
     * @param int $clientId
     * @return void
     */
    public function updateClientDueAmount(int $clientId): void
    {
        $client = Client::find($clientId);
        if ($client) {
            app(BillingService::class)->recalculateClientBalance($client);
        }
    }

    /**
     * Generate bill number
     * 
     * @param int $clientId
     * @param int $month
     * @param int $year
     * @return string
     */
    public function generateBillNumber(int $clientId, int $month, int $year): string
    {
        return sprintf("INV-%06d-%02d%04d", $clientId, $month, $year);
    }

    /**
     * Batch recalculate all bills for accuracy
     * 
     * @return array
     */
    public function batchRecalculateAllBills(): array
    {
        $fixed = 0;
        $errors = [];
        
        Bill::chunk(100, function ($bills) use (&$fixed, &$errors) {
            foreach ($bills as $bill) {
                try {
                    $validation = $this->validateBillAccuracy($bill);
                    if (!$validation['is_valid']) {
                        $this->recalculateBill($bill);
                        $fixed++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Bill #{$bill->id}: " . $e->getMessage();
                }
            }
        });
        
        return [
            'bills_fixed' => $fixed,
            'errors' => $errors,
            'success' => count($errors) === 0
        ];
    }
}
