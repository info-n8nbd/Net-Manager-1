<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAdditionalPayment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class ClientAdditionalPaymentController extends Controller
{
    public function index(Client $client)
    {
        $payments = $client->additionalPayments()
            ->with(['paymentMethod', 'collector'])
            ->orderBy('payment_date', 'desc')
            ->get();
        
        return view('admin.clients.additional-payments.index', compact('client', 'payments'));
    }

    public function create(Client $client)
    {
        $paymentTypes = ClientAdditionalPayment::getTypes();
        $paymentMethods = auth()->user()->allowedPaymentMethods();
        
        return view('admin.clients.additional-payments.create', compact('client', 'paymentTypes', 'paymentMethods'));
    }

    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'payment_type' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'transaction_id' => 'nullable|string|max:100',
            'sender_number' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:500',
        ]);

        $payment = new ClientAdditionalPayment($validated);
        $payment->client_id = $client->id;
        $payment->collected_by = auth()->id();
        $payment->status = 'paid';
        $payment->receipt_no = $payment->generateReceiptNo();
        $payment->money_receipt_no = $request->money_receipt_no;
        $payment->save();

        try {
            $pm = PaymentMethod::find($request->payment_method_id);
            $methodName = $pm->name ?? 'N/A';
            $methodLower = strtolower($methodName);
            $paymentRef = '';
            if (in_array($methodLower, ['cash', 'hand_cash'])) {
                $paymentRef = $request->money_receipt_no ?? '';
            } elseif (in_array($methodLower, ['bank', 'bank_transfer'])) {
                $paymentRef = $request->transaction_id ?? $request->money_receipt_no ?? '';
            } else {
                $paymentRef = $request->transaction_id ?? '';
            }
            $telegram = new \App\Services\TelegramService();
            $telegram->sendPaymentNotification(
                $client->client_id ?? $client->id,
                $client->name,
                (float) $request->amount,
                $methodName,
                $client->username ?? '',
                auth()->user()->name ?? 'System',
                $request->payment_type ?? 'Additional Payment',
                0,
                $request->payment_date ?? now()->format('d-m-Y'),
                $paymentRef,
                $request->sender_number ?? ''
            );
        } catch (\Throwable $e) {
        }

        $successMsg = 'Additional payment recorded successfully. Receipt: ' . $payment->receipt_no;

        if ($request->has('redirect_to') && $request->redirect_to) {
            return redirect()->to($request->redirect_to)->with('success', $successMsg);
        }

        return redirect()
            ->route('admin.clients.additional-payments.index', $client)
            ->with('success', $successMsg);
    }

    public function destroy(Client $client, ClientAdditionalPayment $payment)
    {
        if ($payment->client_id !== $client->id) {
            abort(403);
        }

        $payment->delete();

        return redirect()
            ->route('admin.clients.additional-payments.index', $client)
            ->with('success', 'Payment record deleted.');
    }
}
