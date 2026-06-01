<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Client;
use App\Models\BillCorrection;
use App\Models\PaymentCorrection;
use App\Models\LedgerEntry;
use App\Services\LedgerService;
use App\Services\BillCorrectionService;
use App\Services\PaymentCorrectionService;
use Illuminate\Http\Request;
use Exception;

class CorrectionController extends Controller
{
    protected LedgerService $ledgerService;
    protected BillCorrectionService $billCorrectionService;
    protected PaymentCorrectionService $paymentCorrectionService;

    public function __construct(
        LedgerService $ledgerService,
        BillCorrectionService $billCorrectionService,
        PaymentCorrectionService $paymentCorrectionService
    ) {
        $this->ledgerService = $ledgerService;
        $this->billCorrectionService = $billCorrectionService;
        $this->paymentCorrectionService = $paymentCorrectionService;
    }

    public function index()
    {
        $billCorrections = BillCorrection::with(['bill', 'client', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $paymentCorrections = PaymentCorrection::with(['payment', 'client', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        return view('admin.corrections.index', compact('billCorrections', 'paymentCorrections'));
    }

    public function showBillCorrection(Bill $bill)
    {
        $bill->load(['client', 'payments']);
        $corrections = $this->billCorrectionService->getBillCorrectionHistory($bill);
        $ledgerEntries = LedgerEntry::where('reference_type', 'Bill')
            ->where('reference_id', $bill->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.corrections.bill-correction', compact('bill', 'corrections', 'ledgerEntries'));
    }

    public function correctBillAmount(Request $request, Bill $bill)
    {
        $request->validate([
            'new_package_amount' => 'required|numeric|min:0',
            'new_discount' => 'nullable|numeric|min:0',
            'reason' => 'required|string|min:10|max:500',
        ], [
            'reason.required' => 'A detailed reason for this correction is mandatory.',
            'reason.min' => 'Please provide a detailed reason (at least 10 characters).',
        ]);

        try {
            $correction = $this->billCorrectionService->correctAmount(
                $bill,
                $request->new_package_amount,
                $request->new_discount ?? 0,
                $request->reason
            );

            return redirect()->route('admin.clients.show', $bill->client_id)
                ->with('success', "Bill amount corrected successfully. Correction #{$correction->correction_no}");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function cancelBill(Request $request, Bill $bill)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ], [
            'reason.required' => 'A detailed reason for cancellation is mandatory.',
            'reason.min' => 'Please provide a detailed reason (at least 10 characters).',
        ]);

        try {
            $correction = $this->billCorrectionService->cancelBill($bill, $request->reason);

            return redirect()->route('admin.clients.show', $bill->client_id)
                ->with('success', "Bill cancelled successfully. Correction #{$correction->correction_no}");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function showPaymentCorrection(Payment $payment)
    {
        $payment->load(['client', 'bill', 'collectedBy']);
        $corrections = $this->paymentCorrectionService->getPaymentCorrectionHistory($payment);
        $ledgerEntries = LedgerEntry::where('reference_type', 'Payment')
            ->where('reference_id', $payment->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $duplicates = $this->paymentCorrectionService->checkForDuplicates(
            $payment->client_id,
            $payment->amount,
            $payment->transaction_id
        );

        return view('admin.corrections.payment-correction', compact('payment', 'corrections', 'ledgerEntries', 'duplicates'));
    }

    public function reversePayment(Request $request, Payment $payment)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ], [
            'reason.required' => 'A detailed reason for reversal is mandatory.',
            'reason.min' => 'Please provide a detailed reason (at least 10 characters).',
        ]);

        try {
            $correction = $this->paymentCorrectionService->reversePayment($payment, $request->reason);

            return redirect()->route('admin.clients.show', $payment->client_id)
                ->with('success', "Payment reversed successfully. Correction #{$correction->correction_no}");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function markDuplicate(Request $request, Payment $payment)
    {
        $request->validate([
            'original_payment_id' => 'required|exists:payments,id',
            'reason' => 'required|string|min:10|max:500',
        ], [
            'reason.required' => 'A detailed reason for marking as duplicate is mandatory.',
        ]);

        try {
            $originalPayment = Payment::findOrFail($request->original_payment_id);
            $correction = $this->paymentCorrectionService->markAsDuplicate($payment, $originalPayment, $request->reason);

            return redirect()->route('admin.clients.show', $payment->client_id)
                ->with('success', "Payment marked as duplicate. Correction #{$correction->correction_no}");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function clientLedger(Client $client)
    {
        $ledgerEntries = $this->ledgerService->getClientLedger($client->id);
        $currentBalance = $this->ledgerService->getClientBalance($client->id);
        
        $billCorrections = $this->billCorrectionService->getClientCorrectionHistory($client->id);
        $paymentCorrections = $this->paymentCorrectionService->getClientPaymentCorrectionHistory($client->id);

        return view('admin.corrections.client-ledger', compact(
            'client', 'ledgerEntries', 'currentBalance', 'billCorrections', 'paymentCorrections'
        ));
    }

    public function syncClientLedger(Client $client)
    {
        try {
            $billCount = $this->ledgerService->syncExistingBillsToLedger($client->id);
            $paymentCount = $this->ledgerService->syncExistingPaymentsToLedger($client->id);
            $this->ledgerService->recalculateRunningBalances($client->id);

            return redirect()->route('admin.corrections.client-ledger', $client)
                ->with('success', "Synced {$billCount} bills and {$paymentCount} payments to ledger.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
