<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentReceiptController extends Controller
{
    protected function authorizeReceipt(Payment $payment): void
    {
        $user = auth()->user();
        
        // Super admin and admin can access all receipts
        if ($user->hasRole('Super Admin') || $user->hasRole('Super Admin') || $user->hasRole('Administrator')) {
            return;
        }
        
        // Technicians/staff can only access receipts they collected
        if ($user->hasRole('Field Technician') || $user->hasRole('Billing Officer') || $user->employee) {
            if ($payment->collected_by !== $user->id) {
                abort(403, 'You can only access receipts for payments you collected.');
            }
            return;
        }
        
        // Resellers can access receipts for their clients
        if ($user->hasRole('Reseller')) {
            $client = $payment->client;
            if ($client && $client->reseller_id !== $user->id) {
                abort(403, 'You can only access receipts for your clients.');
            }
            return;
        }
        
        // Default deny
        abort(403, 'Unauthorized access to receipt.');
    }

    public function show(Payment $payment)
    {
        $this->authorizeReceipt($payment);
        $payment->load(['client.zone', 'client.package', 'collectedBy']);
        $settings = $this->getCompanySettings();
        
        return view('admin.payments.receipt', compact('payment', 'settings'));
    }

    public function print(Payment $payment)
    {
        $this->authorizeReceipt($payment);
        $payment->load(['client.zone', 'client.package', 'collectedBy']);
        $settings = $this->getCompanySettings();
        
        return view('admin.payments.receipt-print', compact('payment', 'settings'));
    }

    public function printThermal(Payment $payment)
    {
        $this->authorizeReceipt($payment);
        $payment->load(['client.zone', 'client.package', 'collectedBy']);
        $settings = $this->getCompanySettings();
        
        return view('admin.payments.receipt-thermal', compact('payment', 'settings'));
    }

    public function download(Payment $payment)
    {
        $this->authorizeReceipt($payment);
        $payment->load(['client.zone', 'client.package', 'collectedBy']);
        $settings = $this->getCompanySettings();
        
        $pdf = Pdf::loadView('admin.payments.receipt-pdf', compact('payment', 'settings'));
        $pdf->setPaper('a4', 'portrait');
        
        $filename = 'receipt-' . ($payment->receipt_no ?? 'RCP-' . $payment->id) . '.pdf';
        return $pdf->download($filename);
    }

    public function receiptData(Payment $payment)
    {
        $this->authorizeReceipt($payment);
        $payment->load(['client.zone', 'client.package', 'collectedBy']);
        $settings = $this->getCompanySettings();
        
        return response()->json([
            'success' => true,
            'receipt' => [
                'id' => $payment->id,
                'receipt_no' => $payment->receipt_no ?? 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                'client_id' => $payment->client?->client_id,
                'client_name' => $payment->client?->name,
                'client_phone' => $payment->client?->phone,
                'client_address' => $payment->client?->address,
                'zone' => $payment->client?->zone?->name,
                'package' => $payment->client?->package?->name,
                'amount' => $payment->amount,
                'amount_formatted' => '৳' . number_format($payment->amount, 0),
                'payment_date' => $payment->payment_date->format('d-m-Y'),
                'payment_time' => $payment->created_at->format('h:i A'),
                'payment_method' => ucfirst($payment->payment_method ?? 'Cash'),
                'collected_by' => $payment->collectedBy?->name ?? $payment->receiver_name ?? 'Office',
                'due_after_payment' => $payment->client?->due_amount ?? 0,
                'due_formatted' => '৳' . number_format($payment->client?->due_amount ?? 0, 0),
                'advance_amount' => $payment->client?->advance_amount ?? 0,
                'advance_formatted' => '৳' . number_format($payment->client?->advance_amount ?? 0, 0),
                'for_month' => $payment->for_month ?? $payment->payment_date->format('F Y'),
                'transaction_id' => $payment->transaction_id,
                'notes' => $payment->notes,
                'timestamp' => $payment->created_at->format('d-m-y h:i:s A'),
            ],
            'company' => $settings,
            'print_url' => route('admin.payments.receipt.print', $payment),
            'thermal_url' => route('admin.payments.receipt.thermal', $payment),
            'download_url' => route('admin.payments.receipt.download', $payment),
        ]);
    }

    protected function getCompanySettings(): array
    {
        return [
            'name' => SystemSetting::get('company_name', config('app.name', 'ISP Company')),
            'address' => SystemSetting::get('company_address', ''),
            'phone' => SystemSetting::get('company_phone', ''),
            'email' => SystemSetting::get('company_email', ''),
            'website' => SystemSetting::get('company_website', ''),
            'logo' => SystemSetting::get('company_logo', ''),
            'footer_text' => SystemSetting::get('invoice_footer', 'Thank you for your payment!'),
        ];
    }
}
