<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Client;
use App\Models\PaymentGatewayTransaction;
use App\Services\PaymentGatewayService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    protected PaymentGatewayService $gatewayService;
    protected SmsService $smsService;

    public function __construct(PaymentGatewayService $gatewayService, SmsService $smsService)
    {
        $this->gatewayService = $gatewayService;
        $this->smsService = $smsService;
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'gateway' => 'required|in:bkash,nagad,rocket,ipay,ssl',
        ]);

        $bill = Bill::findOrFail($request->bill_id);
        $client = $bill->client;

        $result = match($request->gateway) {
            'bkash' => $this->gatewayService->initiateBkashPayment($bill, $client),
            'nagad' => $this->gatewayService->initiateNagadPayment($client, $bill->total_amount, $bill->id),
            'rocket' => $this->gatewayService->initiateRocketPayment($bill, $client),
            'ipay' => $this->gatewayService->initiateIpayPayment($bill, $client),
            'ssl' => $this->gatewayService->initiateSslPayment($bill, $client),
        };

        if ($result['success'] && isset($result['redirect_url'])) {
            return redirect()->away($result['redirect_url']);
        }

        if ($result['success'] && isset($result['bkashURL'])) {
            return redirect()->away($result['bkashURL']);
        }

        return back()->with('error', $result['message'] ?? 'Payment initiation failed');
    }

    public function bkashCallback(Request $request)
    {
        $paymentId = $request->paymentID;
        $status = $request->status;

        if ($status === 'success') {
            $result = $this->gatewayService->executeBkashPayment($paymentId);
            
            if ($result['success']) {
                $data = $result['data'];
                $transaction = PaymentGatewayTransaction::where('gateway_response->paymentID', $paymentId)->first();
                
                if ($transaction) {
                    $this->gatewayService->verifyPayment('bkash', $transaction->transaction_id, $data);
                    $this->sendPaymentConfirmation($transaction);
                    $client = \App\Models\Client::find($transaction->client_id);
                    if ($client && $client->user_id) { \Auth::loginUsingId($client->user_id); }
                    return redirect()->route('client.pay-now')->with('success', 'Payment successful! TrxID: ' . ($data['trxID'] ?? ''));
                }
            }
        }

        return redirect()->route('client.pay-now')->with('error', 'Payment failed or cancelled');
    }

    public function sslSuccess(Request $request)
    {
        $transactionId = $request->tran_id;
        $valId = $request->val_id;

        if ($this->gatewayService->verifyPayment('sslcommerz', $transactionId, $request->all())) {
            $transaction = PaymentGatewayTransaction::where('transaction_id', $transactionId)->first();
            if ($transaction) {
                $this->sendPaymentConfirmation($transaction);
                $client = \App\Models\Client::find($transaction->client_id);
                if ($client && $client->user_id) { \Auth::loginUsingId($client->user_id); }
            }
            return redirect()->route('client.pay-now')->with('success', 'Payment successful! Thank you.');
        }

        return redirect()->route('client.pay-now')->with('error', 'Payment verification failed');
    }

    public function sslFail(Request $request)
    {
        $transactionId = $request->tran_id;
        PaymentGatewayTransaction::where('transaction_id', $transactionId)->update(['status' => 'failed']);
        
        return redirect()->route('client.pay-now')->with('error', 'Payment failed');
    }

    public function sslCancel(Request $request)
    {
        $transactionId = $request->tran_id;
        PaymentGatewayTransaction::where('transaction_id', $transactionId)->update(['status' => 'cancelled']);
        
        return redirect()->route('client.pay-now')->with('info', 'Payment cancelled');
    }

    public function nagadCallback(Request $request)
    {
        $paymentRefId = $request->payment_ref_id;
        $status = $request->status;
        $orderId = $request->order_id;

        if ($status === 'Success' && $paymentRefId) {
            $verifyResult = $this->gatewayService->verifyNagadPayment($paymentRefId);

            if ($verifyResult['success']) {
                $verifyData = $verifyResult['data'] ?? [];
                $transactionOrderId = $verifyData['orderId'] ?? $orderId;

                $transaction = PaymentGatewayTransaction::where('transaction_id', $transactionOrderId)
                    ->where('gateway', 'nagad')
                    ->first();

                if ($transaction) {
                    $verifiedAmount = (float) ($verifyData['amount'] ?? 0);
                    if ($verifiedAmount > 0 && abs($verifiedAmount - $transaction->amount) > 0.01) {
                        \Illuminate\Support\Facades\Log::warning('Nagad callback: Amount mismatch', [
                            'transaction_id' => $transactionOrderId,
                            'expected' => $transaction->amount,
                            'received' => $verifiedAmount,
                        ]);
                        $transaction->update([
                            'status' => 'failed',
                            'gateway_response' => array_merge($transaction->gateway_response ?? [], ['callback_error' => 'Amount mismatch']),
                        ]);
                        return redirect()->route('client.bills')->with('error', 'Payment verification failed: amount mismatch.');
                    }

                    $this->gatewayService->verifyPayment('nagad', $transaction->transaction_id, array_merge(
                        $verifyData,
                        ['payment_ref_id' => $paymentRefId, 'nagad_trx_id' => $verifyData['issuerPaymentRefNo'] ?? '']
                    ));
                    $this->sendPaymentConfirmation($transaction->fresh());
                    return redirect()->route('client.bills')->with('success', 'Nagad payment successful! Ref: ' . $paymentRefId);
                }
            }
        }

        if ($orderId) {
            $failedTx = PaymentGatewayTransaction::where('transaction_id', $orderId)
                ->where('gateway', 'nagad')
                ->first();
            if ($failedTx) {
                $failedTx->update([
                    'status' => 'failed',
                    'gateway_response' => array_merge($failedTx->gateway_response ?? [], ['callback_status' => $status]),
                ]);
            }
        }

        return redirect()->route('client.pay-now')->with('error', 'Nagad payment ' . ($status === 'Aborted' ? 'cancelled' : 'failed'));
    }

    public function ipaySuccess(Request $request)
    {
        $orderId = $request->order_id;
        $transaction = PaymentGatewayTransaction::where('gateway', 'ipay')
            ->whereJsonContains('gateway_response->order_id', $orderId)
            ->first();

        if ($transaction && $this->gatewayService->verifyPayment('ipay', $transaction->transaction_id, $request->all())) {
            $this->sendPaymentConfirmation($transaction);
            return redirect()->route('client.bills')->with('success', 'Payment successful!');
        }

        return redirect()->route('client.bills')->with('error', 'Payment verification failed');
    }

    public function ipayCancel(Request $request)
    {
        return redirect()->route('client.pay-now')->with('info', 'Payment cancelled');
    }

    public function demoConfirm(Request $request, string $transaction_id)
    {
        $transaction = PaymentGatewayTransaction::where('transaction_id', $transaction_id)->first();

        if (!$transaction) {
            return redirect()->route('client.bills')->with('error', 'Transaction not found.');
        }

        $user = auth()->user();
        $clientId = null;
        
        if ($user->user_type === 'client') {
            $client = \App\Models\Client::where('user_id', $user->id)->first();
            $clientId = $client?->id;
        }
        
        if (!$clientId || $transaction->client_id !== $clientId) {
            abort(403, 'You are not authorized to access this transaction.');
        }

        return view('client.payment-demo', [
            'transaction' => $transaction,
            'bill' => $transaction->bill,
            'client' => $transaction->client,
        ]);
    }

    public function demoProcess(Request $request, string $transaction_id)
    {
        $transaction = PaymentGatewayTransaction::where('transaction_id', $transaction_id)->first();

        if (!$transaction) {
            return redirect()->route('client.bills')->with('error', 'Transaction not found.');
        }

        $user = auth()->user();
        $clientId = null;
        
        if ($user->user_type === 'client') {
            $client = \App\Models\Client::where('user_id', $user->id)->first();
            $clientId = $client?->id;
        }
        
        if (!$clientId || $transaction->client_id !== $clientId) {
            abort(403, 'You are not authorized to process this transaction.');
        }

        $action = $request->input('action', 'confirm');

        if ($action === 'cancel') {
            $transaction->update(['status' => 'cancelled']);
            return redirect()->route('client.pay-now')->with('info', 'Payment cancelled.');
        }

        $demoTrxId = 'DEMO' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 8));
        
        $verified = $this->gatewayService->verifyPayment($transaction->gateway, $transaction->transaction_id, [
            'trxID' => $demoTrxId,
            'demo_mode' => true,
            'is_demo' => true,
            'completed_at' => now()->toIso8601String(),
        ]);

        if (!$verified) {
            return redirect()->route('client.pay-now')->with('error', 'Payment verification failed. Please try again.');
        }

        $this->sendPaymentConfirmation($transaction->fresh());

        return redirect()->route('client.pay-now')->with('success', 'Payment successful! Demo TrxID: ' . $demoTrxId);
    }

    protected function sendPaymentConfirmation(PaymentGatewayTransaction $transaction): void
    {
        $client = $transaction->client;
        if ($client && $client->phone) {
            $message = "Dear {$client->name}, your payment of BDT {$transaction->amount} via " . 
                strtoupper($transaction->gateway) . " has been received. TrxID: {$transaction->transaction_id}. Thank you!";
            $this->smsService->sendSms($client->phone, $message, 'payment_confirmation', $client->id);
        }

        if ($client) {
            try {
                $client->refresh();
                (new \App\Services\TelegramService())->sendMfsPaymentNotification(
                    $client->client_id,
                    $client->name,
                    (float) $transaction->amount,
                    strtoupper($transaction->gateway),
                    $transaction->transaction_id,
                    $client->username ?? '',
                    (float) ($client->due_amount ?? 0),
                    $client->package?->name ?? ''
                );
            } catch (\Throwable $e) {
            }
        }
    }
}
