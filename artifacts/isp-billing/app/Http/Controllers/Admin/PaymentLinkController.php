<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use App\Models\Client;
use App\Models\Bill;
use App\Models\SystemSetting;
use App\Services\PaymentLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentLinkController extends Controller
{
    protected PaymentLinkService $paymentLinkService;

    public function __construct(PaymentLinkService $paymentLinkService)
    {
        $this->paymentLinkService = $paymentLinkService;
    }

    public function index()
    {
        $links = PaymentLink::with(['client', 'creator'])
            ->latest()
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        $settings = [
            'payment_link_base_url' => SystemSetting::get('payment_link_base_url', ''),
            'payment_link_expiry_hours' => SystemSetting::get('payment_link_expiry_hours', '72'),
        ];

        $stats = [
            'total' => PaymentLink::count(),
            'active' => PaymentLink::where('status', 'active')->count(),
            'paid' => PaymentLink::where('status', 'paid')->count(),
            'expired' => PaymentLink::where('status', 'expired')->count(),
        ];

        $dueClients = Client::where('status', 'active')
            ->where('due_amount', '>', 0)
            ->select('id', 'name', 'username', 'client_id', 'due_amount', 'phone')
            ->orderBy('name')
            ->get();

        return view('admin.payment-links.index', compact('links', 'settings', 'stats', 'dueClients'));
    }

    public function create(Request $request)
    {
        $clients = Client::where('status', 'active')
            ->select('id', 'name', 'username', 'client_id', 'phone', 'due_amount')
            ->orderBy('name')
            ->get();

        $selectedClient = null;
        $autoAmount = null;

        if ($request->has('client_id')) {
            $selectedClient = Client::find($request->client_id);
            if ($selectedClient && $selectedClient->due_amount > 0) {
                $autoAmount = $selectedClient->due_amount;
            }
        }

        return view('admin.payment-links.create', compact('clients', 'selectedClient', 'autoAmount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $link = $this->paymentLinkService->generateLink($request->only(['client_id', 'bill_id', 'amount', 'description']));

        $payUrl = $this->paymentLinkService->getPaymentUrl($link);

        if ($request->send_sms) {
            $this->paymentLinkService->sendViaSms($link);
        }

        if ($request->send_whatsapp) {
            $this->paymentLinkService->sendViaWhatsApp($link);
        }

        return redirect()->route('admin.payment-links.index')
            ->with('success', 'Payment link created: ' . $payUrl);
    }

    public function cancel(PaymentLink $paymentLink)
    {
        $this->paymentLinkService->cancelLink($paymentLink->id);
        return back()->with('success', 'Payment link cancelled');
    }

    public function resend(PaymentLink $paymentLink, Request $request)
    {
        if ($request->via === 'whatsapp') {
            $this->paymentLinkService->sendViaWhatsApp($paymentLink);
        } else {
            $this->paymentLinkService->sendViaSms($paymentLink);
        }

        return back()->with('success', 'Payment link resent');
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'payment_link_base_url' => 'nullable|url|max:255',
            'payment_link_expiry_hours' => 'nullable|integer|min:1|max:720',
        ]);

        $baseUrl = rtrim($request->payment_link_base_url ?? '', '/');
        SystemSetting::set('payment_link_base_url', $baseUrl);
        SystemSetting::set('payment_link_expiry_hours', $request->payment_link_expiry_hours ?? '72');

        return back()->with('success', 'Payment link settings saved successfully.');
    }

    public function autoGenerate(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
        ]);

        $client = Client::findOrFail($request->client_id);

        if ($client->due_amount <= 0) {
            return back()->with('error', 'Client has no outstanding due amount.');
        }

        $existingActive = PaymentLink::where('client_id', $client->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingActive) {
            $payUrl = $this->paymentLinkService->getPaymentUrl($existingActive);
            return back()->with('success', 'Active payment link already exists: ' . $payUrl);
        }

        $link = $this->paymentLinkService->generateLink([
            'client_id' => $client->id,
            'amount' => $client->due_amount,
            'description' => 'Due payment - Auto generated',
            'sent_via' => $request->send_sms ? 'sms' : null,
        ]);

        $payUrl = $this->paymentLinkService->getPaymentUrl($link);

        if ($request->send_sms) {
            $this->paymentLinkService->sendViaSms($link);
        }

        return back()->with('success', 'Payment link auto-generated for ' . $client->name . ' (৳' . number_format($client->due_amount, 2) . '): ' . $payUrl);
    }

    public function bulkAutoGenerate(Request $request)
    {
        $request->validate([
            'min_due' => 'nullable|numeric|min:0',
            'send_sms' => 'nullable|boolean',
        ]);

        $minDue = $request->min_due ?? 1;

        $clients = Client::where('status', 'active')
            ->where('due_amount', '>=', $minDue)
            ->whereNotNull('phone')
            ->get();

        $generated = 0;
        $skipped = 0;

        foreach ($clients as $client) {
            $existingActive = PaymentLink::where('client_id', $client->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->exists();

            if ($existingActive) {
                $skipped++;
                continue;
            }

            $link = $this->paymentLinkService->generateLink([
                'client_id' => $client->id,
                'amount' => $client->due_amount,
                'description' => 'Due payment - Bulk auto generated',
                'sent_via' => $request->send_sms ? 'sms' : null,
            ]);

            if ($request->send_sms) {
                $this->paymentLinkService->sendViaSms($link);
            }

            $generated++;
        }

        return back()->with('success', "Bulk payment links generated: {$generated} created, {$skipped} skipped (already have active links).");
    }

    public function getClientDue(Request $request)
    {
        $client = Client::find($request->client_id);

        if (!$client) {
            return response()->json(['due_amount' => 0]);
        }

        return response()->json([
            'due_amount' => max(0, (float) $client->due_amount),
            'name' => $client->name,
            'phone' => $client->phone,
            'username' => $client->username ?? $client->client_id,
        ]);
    }
}
