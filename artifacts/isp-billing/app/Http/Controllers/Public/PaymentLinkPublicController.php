<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PaymentLinkService;
use Illuminate\Http\Request;

class PaymentLinkPublicController extends Controller
{
    protected PaymentLinkService $paymentLinkService;

    public function __construct(PaymentLinkService $paymentLinkService)
    {
        $this->paymentLinkService = $paymentLinkService;
    }

    public function show(string $token)
    {
        $link = $this->paymentLinkService->getByToken($token);

        if (!$link) {
            return view('public.payment-link-invalid');
        }

        if ($link->status === 'paid') {
            return view('public.payment-link-paid', compact('link'));
        }

        if ($link->status === 'expired' || $link->expires_at < now()) {
            return view('public.payment-link-expired', compact('link'));
        }

        $link->load('client');

        return view('public.payment-link', compact('link'));
    }

    public function processPayment(Request $request, string $token)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        try {
            $link = $this->paymentLinkService->processPayment($token, $request->only(['payment_method', 'payment_id']));
            return view('public.payment-link-success', compact('link'));
        } catch (\Exception $e) {
            return back()->with('error', 'Payment failed. Please try again.');
        }
    }
}
