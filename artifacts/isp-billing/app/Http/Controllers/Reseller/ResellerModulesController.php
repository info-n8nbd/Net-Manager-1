<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Box;
use App\Models\Package;
use App\Models\Reseller;
use App\Models\User;
use App\Models\MikrotikDevice;
use App\Models\OltDevice;
use App\Models\Event;
use App\Models\Task;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\BillingService;
use App\Services\SmsService;
use App\Services\TelegramService;
use App\Services\ClientActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ResellerModulesController extends Controller
{
    protected function getReseller()
    {
        return Reseller::where('user_id', Auth::id())->firstOrFail();
    }

    // ==================== CLIENT RECHARGE ====================
    public function rechargeClient(Request $request)
    {
        $reseller = $this->getReseller();

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'days' => 'nullable|integer|min:1|max:365',
            'start_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        $client = Client::where('id', $request->client_id)
            ->where('reseller_id', $reseller->id)
            ->firstOrFail();

        $data = [
            'start_date' => $request->start_date ?? now()->toDateString(),
            'remarks' => $request->remarks,
        ];

        if ($request->filled('days')) {
            $data['days'] = (int)$request->days;
        }

        try {
            $billingService = new \App\Services\ResellerBillingService();
            $recharge = $billingService->rechargeClient($reseller, $client, $data);

            $msg = "Recharge successful for {$client->username} — ৳" . number_format($recharge->recharge_amount, 2) . " ({$recharge->days} days)";
            if ($reseller->billing_type === 'prepaid') {
                $msg .= ". Wallet balance: ৳" . number_format($reseller->fresh()->wallet_balance, 2);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            \Log::warning('Reseller client recharge failed', [
                'reseller_id' => $reseller->id,
                'client_id' => $request->client_id,
                'error' => $e->getMessage(),
            ]);

            $safeMsg = str_contains($e->getMessage(), 'Insufficient wallet balance')
                ? $e->getMessage()
                : 'Recharge failed. Please try again or contact support.';

            return back()->with('error', $safeMsg);
        }
    }

    // ==================== BULK CLIENT RECHARGE ====================
    public function bulkRechargeClients(Request $request)
    {
        $reseller = $this->getReseller();

        $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
            'days' => 'nullable|integer|min:1|max:365',
            'start_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        $clientIds = array_unique($request->client_ids);
        $clients = Client::whereIn('id', $clientIds)
            ->where('reseller_id', $reseller->id)
            ->get();

        if ($clients->isEmpty()) {
            return back()->with('error', 'No valid clients selected.');
        }

        if ($clients->count() !== count($clientIds)) {
            return back()->with('error', 'Some selected clients do not belong to your account.');
        }

        $billingService = new \App\Services\ResellerBillingService();
        $successCount = 0;
        $failedCount = 0;
        $totalAmount = 0;
        $totalClients = $clients->count();

        foreach ($clients as $client) {
            $data = [
                'start_date' => $request->start_date ?? now()->toDateString(),
                'remarks' => $request->remarks ?? 'Bulk recharge',
            ];
            if ($request->filled('days')) {
                $data['days'] = (int) $request->days;
            }

            try {
                $recharge = $billingService->rechargeClient($reseller, $client, $data);
                $successCount++;
                $totalAmount += $recharge->recharge_amount;
                $reseller = $reseller->fresh();
            } catch (\Exception $e) {
                $failedCount++;
                \Log::warning("Bulk recharge failed for client #{$client->id}: {$e->getMessage()}");
                if (str_contains($e->getMessage(), 'Insufficient wallet balance')) {
                    $remaining = $totalClients - $successCount - $failedCount;
                    if ($remaining > 0) {
                        $failedCount += $remaining;
                    }
                    break;
                }
            }
        }

        $msg = "Bulk recharge complete: {$successCount} successful";
        if ($totalAmount > 0) {
            $msg .= " (Total: ৳" . number_format($totalAmount, 2) . ")";
        }
        if ($failedCount > 0) {
            $msg .= ", {$failedCount} failed";
        }
        if ($reseller->billing_type === 'prepaid') {
            $msg .= ". Wallet: ৳" . number_format($reseller->fresh()->wallet_balance, 2);
        }

        if ($failedCount > 0 && $successCount === 0) {
            return back()->with('error', $msg . '. Please check client details or wallet balance.');
        }

        $flashType = $failedCount > 0 ? 'warning' : 'success';
        return back()->with($flashType, $msg);
    }

    // ==================== CLIENT TRANSACTIONS ====================
    public function clientTransactions(Request $request)
    {
        $reseller = $this->getReseller();
        $month = $request->month ? (int)$request->month : now()->month;
        $year = $request->year ? (int)$request->year : now()->year;

        $query = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->with(['client', 'package', 'rechargedByUser']);

        if ($request->filled('month') || $request->filled('year')) {
            $query->where('bill_month', $month)->where('bill_year', $year);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($cq) use ($search) {
                    $cq->where('username', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%");
                })->orWhere('package_name', 'ilike', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        $refundedIds = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->where('transaction_type', 'recharge_refund')
            ->pluck('remarks')
            ->map(function ($remark) {
                if (preg_match('/Refund for recharge #(\d+)/i', $remark ?? '', $m)) {
                    return (int) $m[1];
                }
                return null;
            })
            ->filter()
            ->unique()
            ->flip()
            ->toArray();

        $reversedIds = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->where('transaction_type', 'client_left_reversal')
            ->pluck('remarks')
            ->map(function ($remark) {
                if (preg_match('/reversal for Recharge #(\d+)/i', $remark ?? '', $m)) {
                    return (int) $m[1];
                }
                return null;
            })
            ->filter()->unique()->flip()->toArray();

        return view('reseller.client-transactions', compact('reseller', 'transactions', 'month', 'year', 'refundedIds', 'reversedIds'));
    }

    public function curtailClient(Request $request)
    {
        $reseller = $this->getReseller();

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'remarks' => 'nullable|string|max:500',
        ]);

        $client = Client::where('id', $request->client_id)
            ->where('reseller_id', $reseller->id)
            ->firstOrFail();

        $billingService = new \App\Services\ResellerBillingService();
        $billingService->curtailClient($reseller, $client, $request->only(['start_date', 'end_date', 'remarks']));

        return back()->with('success', "Curtailed transaction created for {$client->username}.");
    }

    // ==================== RECHARGE CORRECTION ====================
    public function correctRecharge(Request $request)
    {
        $reseller = $this->getReseller();

        $request->validate([
            'recharge_id'    => 'required|exists:reseller_client_recharges,id',
            'new_start_date' => 'required|date',
            'new_days'       => 'required|integer|min:1|max:365',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $original = \App\Models\ResellerClientRecharge::where('id', $request->recharge_id)
            ->where('reseller_id', $reseller->id)
            ->where('transaction_type', 'recharge')
            ->firstOrFail();

        $client = Client::where('id', $original->client_id)
            ->where('reseller_id', $reseller->id)
            ->firstOrFail();

        \DB::transaction(function () use ($reseller, $client, $original, $request) {
            // 1. Create reversal entry to void the wrong recharge
            \App\Models\ResellerClientRecharge::create([
                'reseller_id'      => $reseller->id,
                'client_id'        => $client->id,
                'bill_month'       => now()->month,
                'bill_year'        => now()->year,
                'package_id'       => $original->package_id,
                'package_name'     => $original->package_name,
                'package_price'    => $original->package_price,
                'recharge_amount'  => $original->recharge_amount,
                'daily_rate'       => $original->daily_rate,
                'credited_amount'  => -$original->credited_amount,
                'start_date'       => $original->start_date,
                'end_date'         => $original->end_date,
                'days'             => $original->days,
                'status'           => 'reversed',
                'transaction_type' => 'recharge_reversal',
                'recharged_by'     => auth()->id(),
                'remarks'          => "Correction reversal for Recharge #{$original->id}" . ($request->remarks ? " — {$request->remarks}" : ''),
                'company_id'       => $reseller->company_id,
            ]);

            // 2. Credit wallet back (prepaid only)
            if ($reseller->billing_type === 'prepaid') {
                $reseller->creditWallet(
                    (float) $original->recharge_amount,
                    "Correction reversal: {$client->username} Recharge #{$original->id}",
                    "reversal:{$original->id}",
                    auth()->id()
                );
                $reseller->refresh();
            }

            // 3. Apply the new correct recharge using billing service
            $billingService = new \App\Services\ResellerBillingService();
            $newRecharge = $billingService->rechargeClient($reseller, $client, [
                'start_date' => $request->new_start_date,
                'days'       => (int) $request->new_days,
                'remarks'    => "Correction recharge (replaces #{$original->id})" . ($request->remarks ? " — {$request->remarks}" : ''),
            ]);

            // 4. Mark original as corrected in remarks
            $original->update(['remarks' => ($original->remarks ? $original->remarks . ' ' : '') . "[Corrected → Recharge #{$newRecharge->id}]"]);
        });

        return back()->with('success', "Recharge #{$original->id} for {$client->username} has been corrected. Original voided, new recharge applied.");
    }


    // ==================== REVERSE RECHARGE (Client Left) ====================
    public function reverseRecharge(Request $request)
    {
        $reseller = $this->getReseller();

        $request->validate([
            'recharge_id'  => 'required|exists:reseller_client_recharges,id',
            'reverse_from' => 'required|date',
            'remarks'      => 'nullable|string|max:500',
        ]);

        $original = \App\Models\ResellerClientRecharge::where('id', $request->recharge_id)
            ->where('reseller_id', $reseller->id)
            ->where('transaction_type', 'recharge')
            ->firstOrFail();

        $client = Client::where('id', $original->client_id)
            ->where('reseller_id', $reseller->id)
            ->firstOrFail();

        $billingService = new \App\Services\ResellerBillingService();
        $reversal = $billingService->reverseClientRecharge($reseller, $original, $request->only(['reverse_from', 'remarks']));

        $msg = "Recharge #{$original->id} for {$client->username} reversed. {$reversal->days} unused days";
        if ($reseller->billing_type === 'prepaid') {
            $msg .= " — ৳" . number_format($reversal->recharge_amount, 2) . " credited to wallet.";
        } else {
            $msg .= " — ৳" . number_format($reversal->recharge_amount, 2) . " deducted from due.";
        }

        return back()->with('success', $msg);
    }

    // ==================== REVERSED TRANSACTIONS LIST ====================
    public function reversedTransactions(Request $request)
    {
        $reseller = $this->getReseller();

        $query = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->whereIn('transaction_type', ['client_left_reversal', 'recharge_reversal', 'recharge_refund'])
            ->with(['client', 'rechargedByUser']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($cq) use ($search) {
                    $cq->where('username', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%");
                })->orWhere('package_name', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        $reversals = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        return view('reseller.reversed-transactions', compact('reseller', 'reversals'));
    }

    // ==================== BILLING ====================
    public function bills(Request $request)
    {
        $reseller = $this->getReseller();

        if ($reseller->type === 'bandwidth') {
            return $this->bandwidthBills($request, $reseller);
        }

        $clientIds = $reseller->clients()->pluck('id');
        
        $query = Bill::with(['client'])
            ->whereIn('client_id', $clientIds);
        
        if ($request->month) {
            $query->where('billing_month', $request->month);
        }
        if ($request->year) {
            $query->where('billing_year', $request->year);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $bills = $query->orderBy('bill_date', 'desc')->paginate(20);
        
        $totalBills = Bill::whereIn('client_id', $clientIds)->count();
        $paidBills = Bill::whereIn('client_id', $clientIds)->where('status', 'paid')->count();
        $unpaidBills = Bill::whereIn('client_id', $clientIds)->where('status', 'unpaid')->count();
        $partialBills = Bill::whereIn('client_id', $clientIds)->where('status', 'partial')->count();
        
        return view('reseller.bills', compact('reseller', 'bills', 'totalBills', 'paidBills', 'unpaidBills', 'partialBills'));
    }

    private function bandwidthBills(Request $request, $reseller)
    {
        $billQuery = \App\Models\ResellerBill::where('reseller_id', $reseller->id);
        if ($request->filled('month')) $billQuery->where('bill_month', $request->month);
        if ($request->filled('year'))  $billQuery->where('bill_year', $request->year);
        if ($request->filled('status')) $billQuery->where('status', $request->status);
        $bills = $billQuery->orderBy('bill_year', 'desc')->orderBy('bill_month', 'desc')->paginate(12);

        $paymentQuery = \App\Models\ResellerBillPayment::where('reseller_id', $reseller->id);
        if ($request->filled('month')) $paymentQuery->whereMonth('payment_date', $request->month);
        if ($request->filled('year'))  $paymentQuery->whereYear('payment_date', $request->year);
        $payments = $paymentQuery->orderBy('payment_date', 'desc')->paginate(20, ['*'], 'ppage');

        $totalBilled = \App\Models\ResellerBill::where('reseller_id', $reseller->id)->sum('total_recharge');
        $totalPaid   = \App\Models\ResellerBill::where('reseller_id', $reseller->id)->sum('total_paid');
        $totalDue    = \App\Models\ResellerBill::where('reseller_id', $reseller->id)->sum('due_amount');
        $paidCount   = \App\Models\ResellerBill::where('reseller_id', $reseller->id)->where('status', 'paid')->count();
        $unpaidCount = \App\Models\ResellerBill::where('reseller_id', $reseller->id)->where('status', 'unpaid')->count();
        $partialCount= \App\Models\ResellerBill::where('reseller_id', $reseller->id)->where('status', 'partial')->count();

        return view('reseller.bandwidth-bills', compact(
            'reseller', 'bills', 'payments',
            'totalBilled', 'totalPaid', 'totalDue',
            'paidCount', 'unpaidCount', 'partialCount'
        ));
    }

    public function generateBills(Request $request)
    {
        $reseller = $this->getReseller();
        $billingService = app(BillingService::class);
        
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;
        
        $clients = $reseller->clients()->where('status', 'active')->with('package')->get();
        $generated = 0;
        $errors = [];

        $resellerPackages = \App\Models\ResellerPackage::where('reseller_id', $reseller->id)
            ->where('is_active', true)
            ->pluck('selling_price', 'package_id');
        
        foreach ($clients as $client) {
            try {
                $existingBill = Bill::where('client_id', $client->id)
                    ->where('billing_month', $month)
                    ->where('billing_year', $year)
                    ->first();
                
                if (!$existingBill) {
                    if ($client->package_id && $resellerPackages->has($client->package_id)) {
                        $sellingPrice = $resellerPackages->get($client->package_id);
                        if ($sellingPrice > 0 && (float)$client->monthly_fee !== (float)$sellingPrice) {
                            $client->update(['monthly_fee' => $sellingPrice]);
                            $client->refresh();
                        }
                    }
                    $billingService->generateBill($client, $month, $year);
                    $generated++;
                }
            } catch (\Exception $e) {
                $errors[] = $client->name . ': ' . $e->getMessage();
            }
        }
        
        $message = "Generated {$generated} bills successfully.";
        if (count($errors) > 0) {
            $message .= ' ' . count($errors) . ' errors occurred.';
        }
        
        return back()->with('success', $message);
    }

    // ==================== CLIENT MANAGEMENT ====================
    public function createClient()
    {
        $reseller = $this->getReseller();
        $zones = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
            ->where('reseller_id', $reseller->id)
            ->where('is_active', true)
            ->with(['areas' => function($q) use ($reseller) {
                $q->where('reseller_id', $reseller->id);
            }])->get();

        // Only show packages assigned to this reseller
        $resellerPackages = \App\Models\ResellerPackage::where('reseller_id', $reseller->id)
            ->where('is_active', true)
            ->with('package')
            ->get()
            ->filter(fn($rp) => $rp->package && $rp->package->is_active);
        $packages = $resellerPackages->map(function ($rp) {
            $pkg = $rp->package;
            $pkg->display_price = $rp->selling_price > 0 ? $rp->selling_price : $pkg->price;
            return $pkg;
        });

        // Only show boxes in reseller's own zones
        $zoneIds = $zones->pluck('id');
        $boxes = Box::withoutGlobalScope('hide-reseller-boxes')
            ->with('zone')
            ->whereIn('zone_id', $zoneIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $districts = \App\Models\District::where('is_active', true)->orderBy('name')->get();
        $upazilas = \App\Models\Upazila::where('is_active', true)->orderBy('name')->get();

        $availableOlts = OltDevice::where('is_active', true)->orderBy('name')->get();

        return view('reseller.clients.create', compact('reseller', 'zones', 'packages', 'boxes', 'districts', 'upazilas', 'availableOlts'));
    }

    public function storeClient(Request $request)
    {
        $reseller = $this->getReseller();

        if ($reseller->billing_type === 'prepaid' && $reseller->wallet_balance <= 0) {
            return back()->withInput()->with('error', 'Insufficient wallet balance. Pre-paid resellers cannot add new clients when balance is ৳0.00. Please recharge your wallet first.');
        }

        $pppShortCode = $reseller->ppp_short_code ?? '';
        $fullUsername = $pppShortCode . $request->username;

        $request->merge(['full_username' => $fullUsername]);

        $maxUsernameLen = 255 - strlen($pppShortCode);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'username' => 'required|string|max:' . $maxUsernameLen,
            'full_username' => 'unique:clients,username',
            'zone_id' => 'required|exists:zones,id',
            'area_id' => 'nullable|exists:areas,id',
            'box_id' => 'nullable|exists:boxes,id',
            'package_id' => 'required|exists:packages,id',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'recharge_days' => 'nullable|integer|min:1|max:365',
            'profile_picture' => 'nullable|image|max:5120',
            'nid_front_image' => 'nullable|image|max:5120',
            'nid_back_image' => 'nullable|image|max:5120',
        ], [
            'full_username.unique' => 'The PPPoE username "' . $fullUsername . '" is already taken.',
        ]);

        $resellerZoneIds = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
            ->where('reseller_id', $reseller->id)->pluck('id');
        if (!$resellerZoneIds->contains($request->zone_id)) {
            return back()->withInput()->withErrors(['zone_id' => 'Invalid zone selected.']);
        }
        if ($request->area_id) {
            $areaValid = Area::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
                ->where('id', $request->area_id)->where('reseller_id', $reseller->id)->exists();
            if (!$areaValid) {
                return back()->withInput()->withErrors(['area_id' => 'Invalid area selected.']);
            }
        }
        if ($request->box_id) {
            $boxValid = Box::withoutGlobalScope('hide-reseller-boxes')
                ->where('id', $request->box_id)->whereIn('zone_id', $resellerZoneIds)->exists();
            if (!$boxValid) {
                return back()->withInput()->withErrors(['box_id' => 'Invalid box selected.']);
            }
        }
        $resellerPkgIds = \App\Models\ResellerPackage::where('reseller_id', $reseller->id)
            ->where('is_active', true)->pluck('package_id');
        if (!$resellerPkgIds->contains($request->package_id)) {
            return back()->withInput()->withErrors(['package_id' => 'Invalid package selected.']);
        }

        $rechargeDays = (int) ($request->recharge_days ?: ($reseller->minimum_recharge_days ?? 30));

        DB::beginTransaction();
        try {
            $user = null;
            if ($request->email) {
                $generatedPassword = $request->password ?? \Illuminate\Support\Str::random(10);
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($generatedPassword),
                    'phone' => $request->phone,
                    'user_type' => 'client',
                    'company_id' => $reseller->company_id,
                ]);
            }

            $package = Package::find($request->package_id);

            $resellerPackage = \App\Models\ResellerPackage::where('reseller_id', $reseller->id)
                ->where('package_id', $package->id)
                ->where('is_active', true)
                ->first();
            $monthlyFee = ($resellerPackage && $resellerPackage->selling_price > 0) ? $resellerPackage->selling_price : $package->price;
            if ($request->filled('monthly_fee') && (float)$request->monthly_fee > 0) {
                $monthlyFee = (float)$request->monthly_fee;
            }

            $profilePicture = null;
            $nidFrontImage = null;
            $nidBackImage = null;
            if ($request->hasFile('profile_picture')) {
                $profilePicture = $request->file('profile_picture')->store('clients/profile', 'public');
            }
            if ($request->hasFile('nid_front_image')) {
                $nidFrontImage = $request->file('nid_front_image')->store('nid_images', 'public');
            }
            if ($request->hasFile('nid_back_image')) {
                $nidBackImage = $request->file('nid_back_image')->store('nid_images', 'public');
            }

            $connectionDate = $request->connection_date ?? now()->toDateString();
            $expireDate = \Carbon\Carbon::parse($connectionDate)->addDays($rechargeDays)->toDateString();

            $client = Client::create([
                'user_id' => $user?->id,
                'reseller_id' => $reseller->id,
                'name' => $request->name,
                'profile_picture' => $profilePicture,
                'email' => $request->email,
                'phone' => $request->phone,
                'alt_phone' => $request->alt_phone,
                'address' => $request->address,
                'nid' => $request->nid,
                'occupation' => $request->occupation,
                'father_husband_name' => $request->father_husband_name,
                'mother_name' => $request->mother_name,
                'date_of_birth' => $request->date_of_birth ?: null,
                'gender' => $request->gender,
                'nid_front_image' => $nidFrontImage,
                'nid_back_image' => $nidBackImage,
                'district_id' => $request->district_id,
                'upazila_id' => $request->upazila_id,
                'zone_id' => $request->zone_id,
                'area_id' => $request->area_id,
                'box_id' => $request->box_id,
                'package_id' => $request->package_id,
                'mikrotik_device_id' => $reseller->mikrotik_device_id,
                'mikrotik_profile_name' => $request->mikrotik_profile_name ?? $package->mikrotik_profile,
                'username' => $fullUsername,
                'pppoe_password' => $request->pppoe_password,
                'mac_address' => $request->mac_address,
                'ip_address' => $request->ip_address,
                'mac_binding_enabled' => $request->has('mac_binding_enabled'),
                'connection_type' => $request->connection_type ?? 'pppoe',
                'connection_medium' => $request->connection_medium ?? 'utp',
                'connection_date' => $connectionDate,
                'expire_date' => $expireDate,
                'billing_date' => $request->billing_date ?? 1,
                'payment_deadline' => $request->payment_deadline ?? 10,
                'auto_disconnect_days' => $request->auto_disconnect_days ?? 15,
                'monthly_fee' => $monthlyFee,
                'auto_renewal' => $request->boolean('auto_renewal'),
                'permanent_discount' => $request->permanent_discount ?? 0,
                'signup_fee' => 0,
                'signup_fee_paid' => false,
                'installation_charge' => $request->installation_charge ?? 0,
                'fiber_code' => $request->fiber_code,
                'fiber_color' => $request->fiber_color,
                'splitter_number' => $request->splitter_number,
                'splitter_color' => $request->splitter_color,
                'olt_device_id' => $request->filled('olt_device_id') ? (int) $request->olt_device_id : null,
                'onu_serial' => $request->onu_serial ?: null,
                'onu_model' => $request->onu_model ?: null,
                'olt_port' => $request->olt_port ?: null,
                'onu_mac' => $request->onu_mac,
                'onu_brand' => $request->onu_brand,
                'fiber_vendor' => $request->fiber_vendor,
                'fiber_core_type' => $request->fiber_core_type,
                'google_map_location' => $request->google_map_location,
                'google_map_lat' => $request->google_map_lat,
                'google_map_lng' => $request->google_map_lng,
                'notes' => $request->notes,
                'remarks' => $request->remarks,
                'status' => 'active',
            ]);

            // Create installation charge record (same as admin panel)
            $installAmount = (float)$client->installation_charge ?: (float)$client->signup_fee ?: 0;
            $existingInstallCharge = \App\Models\ClientServiceCharge::where('client_id', $client->id)
                ->where('charge_type', 'Installation Charge')
                ->whereNull('paid_by')
                ->first();
            if (!$existingInstallCharge) {
                \App\Models\ClientServiceCharge::create([
                    'client_id' => $client->id,
                    'charge_type' => 'Installation Charge',
                    'amount' => $installAmount,
                    'description' => $installAmount > 0 ? 'Installation charge' : 'Free installation',
                    'payment_method' => null,
                    'paid_by' => null,
                    'charge_date' => now()->toDateString(),
                ]);
            }

            // Log connection activity (same as admin panel)
            ClientActivityService::logConnection($client, 'New reseller client connection activated');

            // Send new client credentials SMS (same as admin panel)
            if ($client->phone) {
                try {
                    $smsService = app(SmsService::class);
                    $smsService->sendNewClientCredentials($client, $request->pppoe_password);
                } catch (\Exception $smsError) {
                    Log::warning("New reseller client SMS failed for {$client->name}: " . $smsError->getMessage());
                }
            }

            // Record initial billing for reseller tracking (directly, no hidden service extension)
            $billingMonth = \Carbon\Carbon::parse($connectionDate)->month;
            $billingYear  = \Carbon\Carbon::parse($connectionDate)->year;
            try {
                $package = $package ?? \App\Models\Package::find($client->package_id);
                $resellerPkg = $reseller->packages()->where('package_id', $client->package_id)->where('is_active', true)->first();
                $basePrice = $resellerPkg ? $resellerPkg->reseller_price : ($package ? $package->price : $client->monthly_fee);
                $rechargeAmount = round($basePrice * $rechargeDays / 30, 2);
                $packagePrice = $package ? $package->price : $client->monthly_fee;
                $dailyRate = $packagePrice > 0 ? round($packagePrice / 30, 4) : 0;
                \App\Models\ResellerClientRecharge::create([
                    'reseller_id'    => $reseller->id,
                    'client_id'      => $client->id,
                    'bill_month'     => $billingMonth,
                    'bill_year'      => $billingYear,
                    'package_id'     => $client->package_id,
                    'package_name'   => ($package ? $package->name . '-' . number_format($basePrice, 2) . 'TK' : 'N/A'),
                    'package_price'  => $packagePrice,
                    'recharge_amount' => $rechargeAmount,
                    'daily_rate'     => $dailyRate,
                    'credited_amount' => $rechargeAmount,
                    'start_date'     => $connectionDate,
                    'end_date'       => \Carbon\Carbon::parse($connectionDate)->addDays($rechargeDays - 1)->toDateString(),
                    'days'           => $rechargeDays,
                    'status'         => 'active',
                    'transaction_type' => 'recharge',
                    'recharged_by'   => auth()->id(),
                    'remarks'        => 'Initial connection',
                    'company_id'     => $reseller->company_id,
                ]);

                $resellerBillingService = new \App\Services\ResellerBillingService();
                $resellerBillingService->updateResellerBillOnRecharge($reseller, $billingMonth, $billingYear);
                $resellerBillingService->recalculateResellerOverallDue($reseller);
            } catch (\Exception $billingError) {
                Log::warning("Initial billing record failed for {$client->name}: " . $billingError->getMessage());
            }

            try {
                $billingService = app(\App\Services\BillingService::class);
                if (!$billingService->hasBillForMonth($client, $billingMonth, $billingYear)) {
                    $billingService->generateBill($client, $billingMonth, $billingYear, true);
                }
            } catch (\Exception $billError) {
                Log::warning("Client bill generation failed for {$client->name} on creation: " . $billError->getMessage());
            }

            DB::commit();

            // Send Telegram notification (same as admin panel, after commit)
            try {
                (new TelegramService())->sendClientCreationNotification(
                    $client->client_id ?? (string) $client->id,
                    $client->name ?? '',
                    $package->name ?? 'N/A',
                    $client->zone?->name ?? 'N/A',
                    auth()->user()->name ?? 'Reseller',
                    $client->phone,
                    $client->username ?? '',
                    $client->address ?? '',
                    $client->connection_type ?? '',
                    (float) ($monthlyFee ?? 0),
                    $reseller->mikrotikDevice?->identity ?? $reseller->mikrotikDevice?->host ?? '',
                    (int) ($client->payment_deadline ?? 0),
                    $installAmount
                );
            } catch (\Throwable $telegramError) {
                Log::warning('Telegram client creation notification failed (reseller): ' . $telegramError->getMessage());
            }

            // Reseller SMS: Line Activated
            try {
                (new \App\Services\ResellerSmsService($reseller))->sendToClient($client, 'line_activated', [
                    'amount' => $monthlyFee ?? 0,
                ]);
            } catch (\Throwable $smsErr) {
                Log::warning('Reseller SMS (line_activated) failed: ' . $smsErr->getMessage());
            }

            // MikroTik provisioning (after commit to not rollback on MT failure)
            $mikrotikStatus = '';
            try {
                $device = $reseller->mikrotikDevice;
                if ($device) {
                    $mikrotik = new \App\Services\SimpleMikroTikService();
                    if ($mikrotik->connectFromDevice($device)) {
                        $pppPassword = $request->pppoe_password ?? $client->username;
                        $result = $mikrotik->createClientByType($client, $pppPassword);
                        if ($result['success']) {
                            $mikrotikStatus = ' MikroTik user created.';
                        } else {
                            $mikrotikStatus = ' MikroTik user creation failed: ' . $result['message'];
                            Log::warning("MikroTik user creation failed for client {$client->username}: " . $result['message']);
                        }
                    } else {
                        $mikrotikStatus = ' Could not connect to MikroTik router.';
                        Log::warning("Cannot connect to MikroTik device {$device->name} for client {$client->username}");
                    }
                }
            } catch (\Exception $mtError) {
                $mikrotikStatus = ' MikroTik error: ' . $mtError->getMessage();
                Log::error("MikroTik creation error for client {$client->username}: " . $mtError->getMessage());
            }

            $message = 'Client created successfully.' . $mikrotikStatus;
            return redirect()->route('reseller.clients.show', $client)->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create client: ' . $e->getMessage());
        }
    }

    public function editClient(Client $client)
    {
        $reseller = $this->getReseller();
        if ($client->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        
        $zones = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
            ->where('reseller_id', $reseller->id)
            ->where('is_active', true)
            ->with(['areas' => function($q) use ($reseller) {
                $q->where('reseller_id', $reseller->id);
            }])->get();
        // Include client's current zone even if it belongs to main company (backward compat)
        if ($client->zone_id && !$zones->contains('id', $client->zone_id)) {
            $currentZone = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])->with('areas')->find($client->zone_id);
            if ($currentZone) {
                $zones->push($currentZone);
            }
        }
        $resellerPkgRows = \App\Models\ResellerPackage::where('reseller_id', $reseller->id)
            ->where('is_active', true)->with('package')->get()
            ->filter(fn($rp) => $rp->package && $rp->package->is_active);
        $packages = $resellerPkgRows->map(function ($rp) {
            $pkg = $rp->package;
            $pkg->display_price = $rp->selling_price > 0 ? $rp->selling_price : $pkg->price;
            return $pkg;
        });
        $resellerZoneIds = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
            ->where('reseller_id', $reseller->id)->pluck('id');
        $boxes = Box::withoutGlobalScope('hide-reseller-boxes')->with('zone')
            ->where('status', 'active')
            ->whereIn('zone_id', $resellerZoneIds)
            ->orderBy('name')->get();
        $districts = \App\Models\District::where('is_active', true)->orderBy('name')->get();
        $upazilas = \App\Models\Upazila::where('is_active', true)->orderBy('name')->get();
        $agents = \App\Models\Agent::where('status', 'active')->orderBy('name')->get();
        $divisions = \App\Models\District::where('is_active', true)
            ->whereNotNull('division')->where('division', '!=', '')
            ->distinct()->orderBy('division')->pluck('division');

        $availableOlts = OltDevice::where('is_active', true)->orderBy('name')->get();

        return view('reseller.clients.edit', compact('reseller', 'client', 'zones', 'packages', 'boxes', 'districts', 'upazilas', 'agents', 'divisions', 'availableOlts'));
    }

    public function updateClient(Request $request, Client $client)
    {
        $reseller = $this->getReseller();
        if ($client->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'username' => 'required|string|unique:clients,username,' . $client->id,
            'zone_id' => 'required|exists:zones,id',
            'package_id' => 'required|exists:packages,id',
            'profile_picture' => 'nullable|image|max:5120',
            'nid_front_image' => 'nullable|image|max:5120',
            'nid_back_image' => 'nullable|image|max:5120',
            'date_of_birth' => 'nullable|date',
            'connection_date' => 'nullable|date',
            'expire_date' => 'nullable|date',
            'monthly_fee' => 'nullable|numeric|min:0',
            'installation_charge' => 'nullable|numeric|min:0',
            'cable_length_meters' => 'nullable|numeric|min:0',
            'remaining_days' => 'nullable|integer|min:0|max:365',
            'status' => 'nullable|in:active,inactive',
            'effective_to' => 'nullable|date',
            'auto_renewal' => 'nullable|boolean',
        ]);

        $resellerZoneIds = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
            ->where('reseller_id', $reseller->id)->pluck('id');
        if (!$resellerZoneIds->contains($request->zone_id)) {
            return back()->withInput()->withErrors(['zone_id' => 'Invalid zone selected.']);
        }
        if ($request->area_id) {
            $areaValid = Area::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
                ->where('id', $request->area_id)->where('reseller_id', $reseller->id)->exists();
            if (!$areaValid) {
                return back()->withInput()->withErrors(['area_id' => 'Invalid area selected.']);
            }
        }
        if ($request->box_id) {
            $boxValid = Box::withoutGlobalScope('hide-reseller-boxes')
                ->where('id', $request->box_id)->whereIn('zone_id', $resellerZoneIds)->exists();
            if (!$boxValid) {
                return back()->withInput()->withErrors(['box_id' => 'Invalid box selected.']);
            }
        }
        $resellerPkgIds = \App\Models\ResellerPackage::where('reseller_id', $reseller->id)
            ->where('is_active', true)->pluck('package_id');
        if (!$resellerPkgIds->contains((int) $request->package_id)) {
            return back()->withInput()->withErrors(['package_id' => 'Invalid package selected.']);
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'alt_phone' => $request->alt_phone,
                'address' => $request->address,
                'nid' => $request->nid,
                'zone_id' => $request->zone_id,
                'area_id' => $request->area_id,
                'box_id' => $request->box_id,
                'package_id' => $request->package_id,
                'mikrotik_device_id' => $reseller->mikrotik_device_id,
                'username' => $request->username,
                'pppoe_password' => $request->pppoe_password,
                'mac_address' => $request->mac_address,
                'ip_address' => $request->ip_address,
                'mac_binding_enabled' => $request->has('mac_binding_enabled'),
                'connection_type' => $request->connection_type ?? 'pppoe',
                'connection_medium' => $request->connection_medium,
                'billing_date' => $request->billing_date ?? 1,
                'payment_deadline' => $request->payment_deadline ?? 10,
                'auto_disconnect_days' => $request->auto_disconnect_days ?? 15,
                'permanent_discount' => $request->permanent_discount ?? 0,
                'google_map_location' => $request->google_map_location,
                'google_map_lat' => $request->google_map_lat,
                'google_map_lng' => $request->google_map_lng,
                'notes' => $request->notes,
                'remarks' => $request->remarks,
                'father_husband_name' => $request->father_husband_name,
                'mother_name' => $request->mother_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'occupation' => $request->occupation,
                'client_type' => $request->client_type,
                'olt_device_id' => $request->filled('olt_device_id') ? (int) $request->olt_device_id : null,
                'onu_serial' => $request->onu_serial,
                'onu_model' => $request->onu_model,
                'olt_port' => $request->olt_port,
                'onu_brand' => $request->onu_brand,
                'onu_mac' => $request->onu_mac,
                'fiber_vendor' => $request->fiber_vendor,
                'fiber_code' => $request->fiber_code,
                'fiber_core_type' => $request->fiber_core_type,
                'fiber_color' => $request->fiber_color,
                'splitter_number' => $request->splitter_number,
                'splitter_color' => $request->splitter_color,
                'connection_date' => $request->connection_date,
                'expire_date' => ($request->boolean('prd_change') && $request->filled('remaining_days'))
                    ? now()->addDays(max(0, (int)$request->remaining_days))->format('Y-m-d')
                    : $request->expire_date,
                'is_free' => $request->boolean('is_free'),
                'auto_renewal' => $request->boolean('auto_renewal'),
                'free_reason' => $request->free_reason,
                'effective_to' => $request->effective_to,
                'status' => in_array($request->status, ['active', 'inactive']) ? $request->status : $client->status,
                'installation_charge' => $request->installation_charge,
                'cable_length_meters' => $request->cable_length_meters,
                'onu_required' => $request->has('onu_required'),
                'router_required' => $request->has('router_required'),
                'router_brand' => $request->router_brand ?: null,
                'fiber_required' => $request->has('fiber_required'),
            ];

            if ($request->has('monthly_fee') && $request->monthly_fee !== null) {
                $updateData['monthly_fee'] = $request->monthly_fee;
            }

            $nullableFields = ['area_id', 'box_id', 'fiber_code', 'fiber_color', 'splitter_number', 'splitter_color', 'onu_mac', 'onu_brand', 'fiber_vendor', 'fiber_core_type', 'free_reason', 'remarks', 'father_husband_name', 'mother_name', 'gender', 'connection_medium', 'onu_serial', 'onu_model', 'olt_port', 'occupation', 'client_type', 'date_of_birth'];
            foreach ($nullableFields as $field) {
                if (array_key_exists($field, $updateData) && ($updateData[$field] === '' || $updateData[$field] === null)) {
                    $updateData[$field] = null;
                }
            }

            if ($request->hasFile('profile_picture')) {
                $updateData['profile_picture'] = $request->file('profile_picture')->store('clients/profile', 'public');
            }
            if ($request->hasFile('nid_front_image')) {
                $updateData['nid_front_image'] = $request->file('nid_front_image')->store('nid_images', 'public');
            }
            if ($request->hasFile('nid_back_image')) {
                $updateData['nid_back_image'] = $request->file('nid_back_image')->store('nid_images', 'public');
            }

            if ($request->boolean('prd_change') && $request->filled('package_id') && $request->package_id != $client->package_id) {
                $newPackage = Package::find($request->package_id);
                if ($newPackage) {
                    $resellerPackage = \App\Models\ResellerPackage::where('reseller_id', $reseller->id)
                        ->where('package_id', $newPackage->id)
                        ->where('is_active', true)
                        ->first();
                    $updateData['monthly_fee'] = ($resellerPackage && $resellerPackage->selling_price > 0) ? $resellerPackage->selling_price : $newPackage->price;
                    $updateData['mikrotik_profile_name'] = $newPackage->mikrotik_profile;
                }
            }

            $oldInstallationCharge = (float) $client->getOriginal('installation_charge');
            $client->update($updateData);

            // Sync ClientServiceCharge base record if installation_charge changed
            if (array_key_exists('installation_charge', $updateData)) {
                $newInstallCharge = (float) ($updateData['installation_charge'] ?? 0);
                if ($newInstallCharge !== $oldInstallationCharge) {
                    $baseRecord = \App\Models\ClientServiceCharge::where('client_id', $client->id)
                        ->where('charge_type', 'Installation Charge')
                        ->whereNull('paid_by')
                        ->first();
                    if ($baseRecord) {
                        $baseRecord->update(['amount' => $newInstallCharge]);
                    } elseif ($newInstallCharge > 0) {
                        \App\Models\ClientServiceCharge::create([
                            'client_id'   => $client->id,
                            'charge_type' => 'Installation Charge',
                            'amount'      => $newInstallCharge,
                            'description' => 'Installation charge (updated via reseller client edit)',
                            'charge_date' => $client->connection_date ?? now()->toDateString(),
                        ]);
                    }
                    $totalPaid = (float) \App\Models\ClientServiceCharge::where('client_id', $client->id)
                        ->where('charge_type', 'Installation Charge')
                        ->whereNotNull('paid_by')
                        ->where('amount', '>', 0)
                        ->sum('amount');
                    $client->update([
                        'signup_fee'      => $newInstallCharge,
                        'signup_fee_paid' => $newInstallCharge > 0 && $totalPaid >= $newInstallCharge,
                    ]);
                }
            }

            if (!$client->router_brand) {
                $mac = $request->mac_address ?: ($client->mac_address ?: ($client->fresh()->session_mac ?? null));
                if ($mac) {
                    $vendor = \App\Services\MacVendorService::lookup($mac);
                    if ($vendor) {
                        $client->update(['router_brand' => $vendor]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('reseller.clients.show', $client)->with('success', 'Client updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update client: ' . $e->getMessage());
        }
    }

    public function deleteClient(Client $client)
    {
        $reseller = $this->getReseller();
        if ($client->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }

        DB::beginTransaction();
        try {
            $oldStatus = $client->status;

            $mikrotikMsg = '';
            try {
                $device = $reseller->mikrotikDevice;
                if ($device) {
                    $mikrotik = new \App\Services\SimpleMikroTikService();
                    if ($mikrotik->connectFromDevice($device)) {
                        $username = $client->pppoe_username ?? $client->username;
                        $connectionType = $client->connection_type ?? 'pppoe';
                        $result = ['success' => false, 'message' => 'Unknown type'];
                        switch ($connectionType) {
                            case 'pppoe':
                                $result = $mikrotik->deletePPPoESecret($username);
                                break;
                            case 'hotspot':
                                $result = $mikrotik->deleteHotspotUser($username);
                                break;
                            case 'static':
                            case 'dhcp':
                                $queueName = $username ?: "client-{$client->id}";
                                $result = $mikrotik->deleteSimpleQueue($queueName);
                                if ($connectionType === 'dhcp' && $client->mac_address) {
                                    $leaseResult = $mikrotik->deleteDhcpLease($client->mac_address);
                                    if (!$leaseResult['success']) {
                                        \Log::warning("DHCP lease delete failed for {$client->name}: " . $leaseResult['message']);
                                    }
                                }
                                break;
                        }
                        if ($result['success']) {
                            $mikrotikMsg = ' MikroTik secret deleted.';
                        } else {
                            $mikrotikMsg = ' MikroTik delete failed: ' . $result['message'];
                            \Log::warning("MikroTik delete failed for client {$client->username}: " . $result['message']);
                        }
                    } else {
                        $mikrotikMsg = ' Could not connect to MikroTik.';
                        \Log::warning("Cannot connect to MikroTik device for client delete {$client->username}");
                    }
                }
            } catch (\Exception $e) {
                $mikrotikMsg = ' MikroTik error: ' . $e->getMessage();
                \Log::warning("MikroTik delete failed on client delete {$client->name}: " . $e->getMessage());
            }

            $client->update([
                'status' => 'deleted',
                'connection_status' => 'offline',
                'deleted_reason' => 'Deleted by reseller',
                'deleted_by' => auth()->id(),
                'deleted_at_custom' => now(),
                'previous_mikrotik_profile' => $client->mikrotik_profile_name,
            ]);

            \App\Services\ClientActivityService::logStatusChange($client, $oldStatus, 'deleted', 'Deleted by reseller', 'manual');

            DB::commit();
            return redirect()->route('reseller.clients')->with('success', 'Client "' . $client->name . '" deleted successfully.' . $mikrotikMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete client: ' . $e->getMessage());
        }
    }

    public function restoreClient($clientId)
    {
        $reseller = $this->getReseller();
        $client = Client::withoutGlobalScope('exclude_deleted')->findOrFail($clientId);
        if ($client->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        if ($client->status !== 'deleted') {
            return back()->with('error', 'Client is not deleted.');
        }

        DB::beginTransaction();
        try {
            $profileToRestore = $client->previous_mikrotik_profile ?: $client->mikrotik_profile_name;

            $client->update([
                'status' => 'inactive',
                'deleted_reason' => null,
                'deleted_by' => null,
                'deleted_at_custom' => null,
                'mikrotik_profile_name' => $profileToRestore,
            ]);

            $mikrotikMsg = '';
            try {
                $device = $reseller->mikrotikDevice;
                if ($device) {
                    $mikrotik = new \App\Services\SimpleMikroTikService();
                    if ($mikrotik->connectFromDevice($device)) {
                        $pppPassword = $client->pppoe_password ?? $client->username;
                        $result = $mikrotik->createClientByType($client, $pppPassword);
                        if ($result['success']) {
                            $mikrotikMsg = ' MikroTik secret re-created with profile: ' . ($profileToRestore ?? 'default') . '.';
                        } else {
                            $mikrotikMsg = ' MikroTik secret creation failed: ' . $result['message'];
                            \Log::warning("MikroTik re-create failed for restored client {$client->username}: " . $result['message']);
                        }
                    } else {
                        $mikrotikMsg = ' Could not connect to MikroTik.';
                    }
                }
            } catch (\Exception $e) {
                $mikrotikMsg = ' MikroTik error: ' . $e->getMessage();
                \Log::warning("MikroTik restore failed for client {$client->name}: " . $e->getMessage());
            }

            \App\Services\ClientActivityService::logStatusChange($client, 'deleted', 'inactive', 'Restored by reseller', 'manual');

            DB::commit();
            return back()->with('success', 'Client "' . $client->name . '" restored successfully.' . $mikrotikMsg . ' You can now enable them.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to restore client: ' . $e->getMessage());
        }
    }

    // ==================== ZONE/SUBZONE/TJ MANAGEMENT ====================
    public function zones()
    {
        $reseller = $this->getReseller();
        $zones = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
            ->where('reseller_id', $reseller->id)
            ->withCount(['clients' => function($q) use ($reseller) {
                $q->where('reseller_id', $reseller->id);
            }])->with(['areas.boxes'])->get();
        
        return view('reseller.zones', compact('reseller', 'zones'));
    }

    public function storeZone(Request $request)
    {
        $reseller = $this->getReseller();
        $request->validate(['name' => 'required|string|max:255']);
        
        Zone::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => true,
            'reseller_id' => $reseller->id,
            'company_id' => $reseller->company_id,
        ]);
        
        return back()->with('success', 'Zone created successfully.');
    }

    public function updateZone(Request $request, Zone $zone)
    {
        $reseller = $this->getReseller();
        if ($zone->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        $request->validate(['name' => 'required|string|max:255']);
        
        $zone->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);
        
        return back()->with('success', 'Zone updated successfully.');
    }

    public function deleteZone(Zone $zone)
    {
        $reseller = $this->getReseller();
        if ($zone->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        if ($zone->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete zone with clients.');
        }
        
        DB::beginTransaction();
        try {
            $zone->areas()->each(function($area) {
                $area->boxes()->delete();
            });
            $zone->areas()->delete();
            $zone->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete zone: ' . $e->getMessage());
        }
        
        return back()->with('success', 'Zone deleted successfully.');
    }

    public function storeSubzone(Request $request)
    {
        $reseller = $this->getReseller();
        $request->validate([
            'name' => 'required|string|max:255',
            'zone_id' => 'required|exists:zones,id',
        ]);
        
        $zone = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])->where('id', $request->zone_id)->where('reseller_id', $reseller->id)->firstOrFail();
        
        Area::create([
            'name' => $request->name,
            'zone_id' => $zone->id,
            'description' => $request->description,
            'reseller_id' => $reseller->id,
            'company_id' => $reseller->company_id,
        ]);
        
        return back()->with('success', 'Subzone created successfully.');
    }

    public function updateSubzone(Request $request, Area $area)
    {
        $reseller = $this->getReseller();
        if ($area->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        $request->validate(['name' => 'required|string|max:255']);
        
        $area->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);
        
        return back()->with('success', 'Subzone updated successfully.');
    }

    public function deleteSubzone(Area $area)
    {
        $reseller = $this->getReseller();
        if ($area->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        if ($area->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete subzone with clients.');
        }
        
        DB::beginTransaction();
        try {
            $area->boxes()->delete();
            $area->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete subzone: ' . $e->getMessage());
        }
        
        return back()->with('success', 'Subzone deleted successfully.');
    }

    public function storeTj(Request $request)
    {
        $reseller = $this->getReseller();
        $request->validate([
            'name' => 'required|string|max:255',
            'zone_id' => 'required|exists:zones,id',
        ]);

        // Verify zone belongs to this reseller
        $zone = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
            ->where('id', $request->zone_id)
            ->where('reseller_id', $reseller->id)
            ->firstOrFail();

        Box::create([
            'name' => $request->name,
            'zone_id' => $zone->id,
            'area_id' => $request->area_id,
            'description' => $request->description,
            'capacity' => $request->capacity ?? 0,
            'status' => 'active',
            'company_id' => $reseller->company_id,
        ]);

        return back()->with('success', 'TJ/Box created successfully.');
    }

    public function updateTj(Request $request, Box $box)
    {
        $reseller = $this->getReseller();
        // Verify box belongs to reseller's zone
        $ownsBox = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
            ->where('id', $box->zone_id)
            ->where('reseller_id', $reseller->id)
            ->exists();
        if (!$ownsBox) {
            abort(403, 'Unauthorized');
        }

        $request->validate(['name' => 'required|string|max:255']);

        $box->update([
            'name' => $request->name,
            'description' => $request->description,
            'capacity' => $request->capacity ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return back()->with('success', 'TJ/Box updated successfully.');
    }

    public function deleteTj(Box $box)
    {
        $reseller = $this->getReseller();
        // Verify box belongs to reseller's zone
        $ownsBox = Zone::withoutGlobalScopes(['tenant', 'hide-reseller-zones'])
            ->where('id', $box->zone_id)
            ->where('reseller_id', $reseller->id)
            ->exists();
        if (!$ownsBox) {
            abort(403, 'Unauthorized');
        }

        if ($box->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete TJ with clients.');
        }

        $box->delete();

        return back()->with('success', 'TJ/Box deleted successfully.');
    }

    // ==================== PACKAGE MANAGEMENT ====================
    public function packagesManage()
    {
        $reseller = $this->getReseller();
        // Resellers only manage their own assigned packages (selling price)
        $packages = \App\Models\ResellerPackage::where('reseller_id', $reseller->id)
            ->with('package')
            ->orderBy('id')
            ->get()
            ->filter(fn($rp) => $rp->package !== null);

        return view('reseller.packages-manage', compact('reseller', 'packages'));
    }

    public function storePackage(Request $request)
    {
        // Resellers cannot create global packages — contact admin to assign packages
        return back()->with('error', 'Package creation is not allowed. Please contact admin to assign packages to your account.');
    }

    public function updatePackage(Request $request, \App\Models\ResellerPackage $resellerPackage)
    {
        $reseller = $this->getReseller();
        if ($resellerPackage->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'selling_price' => 'required|numeric|min:0',
        ]);

        $resellerPackage->update([
            'selling_price' => $request->selling_price,
        ]);

        return back()->with('success', 'Client price updated successfully.');
    }

    public function deletePackage(\App\Models\ResellerPackage $resellerPackage)
    {
        // Resellers cannot remove package assignments — contact admin
        return back()->with('error', 'Package removal is not allowed. Please contact admin.');
    }

    // ==================== TICKET MANAGEMENT ====================
    public function ticketsManage()
    {
        $reseller = $this->getReseller();
        $clientIds = $reseller->clients()->pluck('id');
        
        $tickets = Ticket::with(['client', 'category', 'assignedTo'])
            ->where(function($q) use ($clientIds) {
                $q->whereIn('client_id', $clientIds)
                  ->orWhereNull('client_id');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $clients = $reseller->clients()->orderBy('name')->get();
        $categories = TicketCategory::where('is_active', true)->get();
        $technicians = User::role(['technician'])->where('company_id', $reseller->company_id)->get();
        
        $stats = [
            'open' => Ticket::whereIn('client_id', $clientIds)->where('status', 'open')->count(),
            'in_progress' => Ticket::whereIn('client_id', $clientIds)->where('status', 'in_progress')->count(),
            'resolved' => Ticket::whereIn('client_id', $clientIds)->where('status', 'resolved')->count(),
            'closed' => Ticket::whereIn('client_id', $clientIds)->where('status', 'closed')->count(),
        ];
        
        return view('reseller.tickets-manage', compact('reseller', 'tickets', 'clients', 'categories', 'technicians', 'stats'));
    }

    public function storeTicket(Request $request)
    {
        $reseller = $this->getReseller();
        
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:ticket_categories,id',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $clientId = $request->client_id ?: null;
        if ($clientId) {
            $clientBelongsToReseller = Client::where('id', $clientId)
                ->where('reseller_id', $reseller->id)
                ->exists();
            if (!$clientBelongsToReseller) {
                return back()->withErrors(['client_id' => 'Invalid client selected.']);
            }
        }
        
        $assignedTo = $request->assigned_to;
        if ($assignedTo) {
            $validTech = User::where('id', $assignedTo)->where('company_id', $reseller->company_id)->exists();
            if (!$validTech) {
                $assignedTo = null;
            }
        }

        Ticket::create([
            'title' => $request->title,
            'description' => $request->description,
            'client_id' => $clientId,
            'category_id' => $request->category_id,
            'priority' => $request->priority,
            'assigned_to' => $assignedTo,
            'status' => 'open',
            'created_by' => Auth::id(),
        ]);
        
        return back()->with('success', 'Ticket created successfully.');
    }

    public function updateTicket(Request $request, Ticket $ticket)
    {
        $reseller = $this->getReseller();
        $clientIds = $reseller->clients()->pluck('id');

        if ($ticket->client_id && !$clientIds->contains($ticket->client_id)) {
            abort(403, 'Unauthorized to update this ticket.');
        }

        $request->validate([
            'status' => 'nullable|in:open,in_progress,resolved,closed',
            'priority' => 'nullable|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $assignedTo = $request->assigned_to;
        if ($assignedTo) {
            $validTech = User::where('id', $assignedTo)->where('company_id', $reseller->company_id)->exists();
            if (!$validTech) {
                $assignedTo = $ticket->assigned_to;
            }
        }

        $ticket->update([
            'status' => $request->status,
            'assigned_to' => $assignedTo,
            'priority' => $request->priority,
        ]);
        
        return back()->with('success', 'Ticket updated successfully.');
    }

    public function deleteTicket(Ticket $ticket)
    {
        $reseller = $this->getReseller();
        $clientIds = $reseller->clients()->pluck('id');
        
        if ($ticket->client_id && !$clientIds->contains($ticket->client_id)) {
            abort(403, 'Unauthorized to delete this ticket.');
        }
        
        DB::beginTransaction();
        try {
            $ticket->messages()->delete();
            $ticket->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete ticket: ' . $e->getMessage());
        }
        
        return back()->with('success', 'Ticket deleted successfully.');
    }


    // ==================== INSTALLATION CHARGES ====================
    public function installationCharges(Request $request)
    {
        $reseller = $this->getReseller();

        $query = \App\Models\ClientServiceCharge::with(["client", "paidByUser"])
            ->where("charge_type", "Installation Charge")
            ->whereNull("paid_by")
            ->whereHas("client", fn($q) => $q->where("reseller_id", $reseller->id));

        if ($request->date_from) {
            $query->whereDate("charge_date", ">=", $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate("charge_date", "<=", $request->date_to);
        }
        if ($request->search) {
            $query->whereHas("client", function ($q) use ($request) {
                $q->where("name", "ilike", "%" . $request->search . "%")
                  ->orWhere("client_id", "ilike", "%" . $request->search . "%");
            });
        }

        $partiallyPaidClientIds = \App\Models\ClientServiceCharge::where("charge_type", "Installation Charge")
            ->whereNotNull("paid_by")
            ->where("amount", ">", 0)
            ->whereHas("client", fn($q) => $q->where("reseller_id", $reseller->id))
            ->pluck("client_id")
            ->toArray();

        if ($request->status === "paid") {
            $query->where("amount", ">", 0)->whereIn("client_id", function($sub){
                $sub->select("id")->from("clients")->where("signup_fee_paid", true);
            });
        } elseif ($request->status === "partial") {
            $query->where("amount", ">", 0)
                ->whereIn("client_id", function($sub){
                    $sub->select("id")->from("clients")->where("signup_fee_paid", false);
                })
                ->whereIn("client_id", $partiallyPaidClientIds ?: [0]);
        } elseif ($request->status === "due") {
            $query->where("amount", ">", 0)
                ->whereIn("client_id", function($sub){
                    $sub->select("id")->from("clients")->where("signup_fee_paid", false);
                })
                ->whereNotIn("client_id", $partiallyPaidClientIds ?: [0]);
        } elseif ($request->status === "free") {
            $query->where(function($q){ $q->where("amount", "<=", 0)->orWhereNull("amount"); });
        }

        $charges  = $query->orderBy("charge_date", "desc")->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25);

        $totalAmount = \App\Models\ClientServiceCharge::where("charge_type","Installation Charge")->whereNull("paid_by")->where("amount",">",0)->whereHas("client", fn($q) => $q->where("reseller_id", $reseller->id))->sum("amount");
        $totalPaid   = \App\Models\ClientServiceCharge::where("charge_type","Installation Charge")->whereNotNull("paid_by")->where("amount",">",0)->whereHas("client", fn($q) => $q->where("reseller_id", $reseller->id))->sum("amount");
        $totalDue    = max(0, $totalAmount - $totalPaid);
        $total       = $totalAmount;

        $paymentMethods = auth()->user()->allowedPaymentMethods();
        $paymentReasons = \App\Models\DropdownOption::getOptions("payment_reasons");

        return view("reseller.installation-charges", compact("reseller", "charges", "total", "totalPaid", "totalDue", "paymentMethods", "paymentReasons"));
    }

    public function payInstallationCharge(Request $request, Client $client)
    {
        $reseller = $this->getReseller();
        if ($client->reseller_id !== $reseller->id) {
            abort(403, "Unauthorized");
        }

        $request->validate([
            "amount"            => "required|numeric|min:0.01",
            "payment_method_id" => "required|exists:payment_methods,id",
            "transaction_id"    => "nullable|string|max:255",
            "sender_number"     => "nullable|string|max:50",
            "money_receipt_no"  => "nullable|string|max:100",
            "notes"             => "nullable|string|max:500",
            "notes_reason"      => "nullable|string|max:500",
        ]);

        $amount = (float)$request->amount;
        $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);

        \App\Models\ClientServiceCharge::create([
            "client_id"        => $client->id,
            "charge_type"      => "Installation Charge",
            "amount"           => $amount,
            "charge_date"      => now()->toDateString(),
            "paid_by"          => auth()->id(),
            "payment_method"   => $paymentMethod->name,
            "transaction_id"   => $request->transaction_id,
            "sender_number"    => $request->sender_number,
            "money_receipt_no" => $request->money_receipt_no,
            "notes"            => trim(($request->notes_reason ? "Reason: " . $request->notes_reason . ". " : "") . ($request->notes ?? "")),
            "company_id"       => $client->company_id,
        ]);

        $installCharge = \App\Models\ClientServiceCharge::where("client_id", $client->id)
            ->where("charge_type", "Installation Charge")
            ->whereNull("paid_by")->first();
        $baseAmount = $installCharge ? (float)$installCharge->amount : 0;
        $totalPaid  = \App\Models\ClientServiceCharge::where("client_id", $client->id)
            ->where("charge_type", "Installation Charge")
            ->whereNotNull("paid_by")->where("amount", ">", 0)->sum("amount");

        if ($baseAmount > 0 && $totalPaid >= $baseAmount) {
            $client->update(["signup_fee_paid" => true]);
        }

        if ($request->send_sms) {
            try {
                app(SmsService::class)->sendInstallationPaymentSms($client, $amount, $paymentMethod->name);
            } catch (\Exception $e) {
                Log::warning("SMS failed for installation payment: " . $e->getMessage());
            }
        }

        return back()->with("success", "Installation payment of ৳" . number_format($amount, 2) . " recorded for " . $client->name . ".");
    }

    public function updateInstallationCharge(Request $request, \App\Models\ClientServiceCharge $charge)
    {
        $reseller = $this->getReseller();
        if (!$charge->client || $charge->client->reseller_id !== $reseller->id) {
            abort(403, "Unauthorized");
        }

        $request->validate([
            "amount"      => "required|numeric|min:0",
            "charge_date" => "required|date",
            "description" => "nullable|string|max:500",
        ]);

        $charge->update([
            "amount"      => $request->amount,
            "charge_date" => $request->charge_date,
            "description" => $request->description,
        ]);

        if ($charge->client_id) {
            $charge->client?->update(["installation_charge" => $request->amount]);
        }

        return back()->with("success", "Installation charge updated.");
    }

    // ==================== PAYMENT COLLECTION ====================
    public function collectPaymentForm(Client $client)
    {
        $reseller = $this->getReseller();
        if ($client->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        
        $unpaidBills = $client->bills()->where('status', '!=', 'paid')->orderBy('bill_date')->get();
        $paymentMethods = auth()->user()->allowedPaymentMethods();
        
        return view('reseller.collect-payment', compact('reseller', 'client', 'unpaidBills', 'paymentMethods'));
    }

    // ==================== NETWORK ====================
    public function networkDiagram()
    {
        $reseller = $this->getReseller();
        $resellerDeviceIds = $reseller->clients()->whereNotNull('mikrotik_device_id')->pluck('mikrotik_device_id')->unique()->filter()->values();
        $devices = $resellerDeviceIds->isNotEmpty()
            ? MikrotikDevice::whereIn('id', $resellerDeviceIds)->where('is_active', true)->get()
            : collect();
        $resellerOltIds = $reseller->clients()->whereNotNull('olt_device_id')->pluck('olt_device_id')->unique()->filter()->values();
        $olts = $resellerOltIds->isNotEmpty()
            ? OltDevice::whereIn('id', $resellerOltIds)->where('is_active', true)->get()
            : collect();
        $clientCount = $reseller->clients()->count();
        
        return view('reseller.network-diagram', compact('reseller', 'devices', 'olts', 'clientCount'));
    }

    public function oltManagement()
    {
        $reseller = $this->getReseller();
        $resellerOltIds = $reseller->clients()->whereNotNull('olt_device_id')->pluck('olt_device_id')->unique()->filter()->values();
        $olts = $resellerOltIds->isNotEmpty()
            ? OltDevice::whereIn('id', $resellerOltIds)->where('is_active', true)->get()
            : collect();
        $clientIds = $reseller->clients()->pluck('id');

        // Load clients per OLT with ONU data
        $clientsByOlt = [];
        foreach ($olts as $olt) {
            $clients = $reseller->clients()
                ->with('package', 'zone')
                ->where('olt_device_id', $olt->id)
                ->get();

            $onuSerials = $clients->pluck('onu_serial')->filter()->values()->all();
            $onuData = collect(\Illuminate\Support\Facades\DB::table('onus')
                ->where('olt_device_id', $olt->id)
                ->whereIn('serial_number', $onuSerials)
                ->get())
                ->keyBy('serial_number');

            $clientsByOlt[$olt->id] = $clients->map(function ($client) use ($onuData) {
                $onu = $onuData->get($client->onu_serial);
                $client->onu_live_status  = $onu ? $onu->status : null;
                $client->onu_live_rx      = $onu ? $onu->rx_power : null;
                $client->onu_live_tx      = $onu ? $onu->tx_power : null;
                $client->onu_live_quality = $onu ? $onu->signal_quality : null;
                $client->onu_down_reason  = $onu ? $onu->down_reason : null;
                return $client;
            });
        }

        // Clients without OLT assignment
        $unassignedClients = $reseller->clients()->whereNull('olt_device_id')->with('package', 'zone')->get();

        return view('reseller.olt-management', compact('reseller', 'olts', 'clientIds', 'clientsByOlt', 'unassignedClients'));
    }

    // ==================== EVENTS ====================
    public function events()
    {
        $reseller = $this->getReseller();
        $companyUserIds = \App\Models\User::where('company_id', $reseller->company_id)->pluck('id');
        $events = Event::where(function($q) use ($reseller, $companyUserIds) {
            $q->where(function($inner) use ($companyUserIds) {
                $inner->whereNull('reseller_id')
                      ->whereIn('created_by', $companyUserIds);
            })->orWhere('reseller_id', $reseller->id);
        })->orderBy('event_date', 'desc')->paginate(20);
        
        $upcomingEvents = Event::where('event_date', '>=', now())
            ->where(function($q) use ($reseller, $companyUserIds) {
                $q->where(function($inner) use ($companyUserIds) {
                    $inner->whereNull('reseller_id')
                          ->whereIn('created_by', $companyUserIds);
                })->orWhere('reseller_id', $reseller->id);
            })
            ->orderBy('event_date')->limit(5)->get();
        
        return view('reseller.events', compact('reseller', 'events', 'upcomingEvents'));
    }

    public function storeEvent(Request $request)
    {
        $reseller = $this->getReseller();
        
        $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'type' => 'required|in:holiday,event,maintenance,other',
        ]);
        
        Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'type' => $request->type,
            'is_holiday' => $request->type === 'holiday',
            'reseller_id' => $reseller->id,
            'created_by' => Auth::id(),
        ]);
        
        return back()->with('success', 'Event created successfully.');
    }

    public function deleteEvent(Event $event)
    {
        $reseller = $this->getReseller();
        if ($event->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        
        $event->delete();
        return back()->with('success', 'Event deleted successfully.');
    }

    // ==================== TASKS ====================
    public function tasks(Request $request)
    {
        $reseller = $this->getReseller();
        
        $query = Task::where(function($q) use ($reseller) {
            $q->where('assigned_to', Auth::id())
              ->orWhere('created_by', Auth::id());
        });
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $tasks = $query->orderBy('due_date')->paginate(20);
        
        $pendingTasks = Task::where('assigned_to', Auth::id())->where('status', 'pending')->count();
        $completedTasks = Task::where('assigned_to', Auth::id())->where('status', 'completed')->count();
        
        return view('reseller.tasks', compact('reseller', 'tasks', 'pendingTasks', 'completedTasks'));
    }

    public function storeTask(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
        ]);
        
        Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status' => 'pending',
            'assigned_to' => Auth::id(),
            'created_by' => Auth::id(),
        ]);
        
        return back()->with('success', 'Task created successfully.');
    }

    public function updateTaskStatus(Request $request, Task $task)
    {
        if ($task->assigned_to !== Auth::id() && $task->created_by !== Auth::id()) {
            abort(403);
        }
        
        $task->update([
            'status' => $request->status,
            'completed_at' => $request->status === 'completed' ? now() : null,
        ]);
        
        return back()->with('success', 'Task status updated.');
    }

    public function deleteTask(Task $task)
    {
        if ($task->created_by !== Auth::id()) {
            abort(403);
        }
        
        $task->delete();
        return back()->with('success', 'Task deleted successfully.');
    }

    // ==================== PURCHASE/PRODUCTS ====================
    public function purchases()
    {
        $reseller = $this->getReseller();
        $purchases = Purchase::where('created_by', auth()->id())
            ->with('vendor')
            ->orderBy('purchase_date', 'desc')
            ->paginate(20);
        
        return view('reseller.purchases', compact('reseller', 'purchases'));
    }

    public function products()
    {
        $reseller = $this->getReseller();
        $products = Product::where('is_active', true)->paginate(20);
        
        return view('reseller.products', compact('reseller', 'products'));
    }

    public function sales()
    {
        $reseller = $this->getReseller();
        return view('reseller.sales', compact('reseller'));
    }
}
