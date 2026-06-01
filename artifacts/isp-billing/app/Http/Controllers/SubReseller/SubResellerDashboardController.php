<?php

namespace App\Http\Controllers\SubReseller;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSelfieAttendance;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Package;
use App\Models\Reseller;
use App\Models\ResellerClient;
use App\Models\SubResellerCommission;
use App\Services\SubResellerService;
use App\Services\BillingService;
use App\Services\LedgerService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubResellerDashboardController extends Controller
{
    protected SubResellerService $subResellerService;

    public function __construct(SubResellerService $subResellerService)
    {
        $this->subResellerService = $subResellerService;
    }

    protected function getReseller()
    {
        return Reseller::where('user_id', Auth::id())
            ->where('reseller_level', 'sub_reseller')
            ->firstOrFail();
    }

    public function index()
    {
        $reseller = $this->getReseller();
        $stats = $this->subResellerService->getSubResellerStats($reseller->id);

        $clientIds = ResellerClient::where('reseller_id', $reseller->id)->pluck('client_id');

        $thisMonthCollection = Payment::whereIn('client_id', $clientIds)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $recentPayments = Payment::with('client')
            ->whereIn('client_id', $clientIds)
            ->latest('payment_date')
            ->limit(10)
            ->get();

        $recentClients = Client::whereIn('id', $clientIds)
            ->with('package')
            ->latest()
            ->limit(5)
            ->get();

        return view('sub-reseller.dashboard', compact(
            'reseller', 'stats', 'thisMonthCollection', 'recentPayments', 'recentClients'
        ));
    }

    public function clients(Request $request)
    {
        $reseller = $this->getReseller();
        $clientIds = ResellerClient::where('reseller_id', $reseller->id)->pluck('client_id');

        $query = Client::whereIn('id', $clientIds)->with('package');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('username', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $clients = $query->latest()->paginate(20);

        return view('sub-reseller.clients', compact('reseller', 'clients'));
    }

    public function createClient()
    {
        $reseller = $this->getReseller();
        $packages = Package::where('is_active', true)->get();
        return view('sub-reseller.create-client', compact('reseller', 'packages'));
    }

    public function storeClient(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'username' => 'required|string|max:50|unique:clients',
            'package_id' => 'required|exists:packages,id',
        ]);

        $reseller = $this->getReseller();

        DB::transaction(function () use ($request, $reseller) {
            $package = Package::findOrFail($request->package_id);

            $client = Client::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'username' => $request->username,
                'password' => $request->password ?? 'default123',
                'package_id' => $request->package_id,
                'monthly_fee' => $package->price,
                'connection_type' => $request->connection_type ?? 'pppoe',
                'status' => 'active',
                'branch_id' => $reseller->branch_id,
            ]);

            ResellerClient::create([
                'reseller_id' => $reseller->id,
                'client_id' => $client->id,
            ]);
        });

        return redirect()->route('sub-reseller.clients')->with('success', 'Client created successfully');
    }

    public function billing()
    {
        $reseller = $this->getReseller();
        $clientIds = ResellerClient::where('reseller_id', $reseller->id)->pluck('client_id');

        $payments = Payment::with('client')
            ->whereIn('client_id', $clientIds)
            ->latest('payment_date')
            ->paginate(20);

        return view('sub-reseller.billing', compact('reseller', 'payments'));
    }

    public function wallet()
    {
        $reseller = $this->getReseller();

        $transactions = DB::table('reseller_wallets')
            ->where('reseller_id', $reseller->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('sub-reseller.wallet', compact('reseller', 'transactions'));
    }

    public function commissions()
    {
        $reseller = $this->getReseller();

        $commissions = SubResellerCommission::where('sub_reseller_id', $reseller->id)
            ->with(['client', 'payment'])
            ->latest()
            ->paginate(20);

        $totalCommission = SubResellerCommission::where('sub_reseller_id', $reseller->id)->sum('sub_reseller_commission');
        $monthlyCommission = SubResellerCommission::where('sub_reseller_id', $reseller->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('sub_reseller_commission');

        return view('sub-reseller.commissions', compact('reseller', 'commissions', 'totalCommission', 'monthlyCommission'));
    }

    public function collectPayment(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        $reseller = $this->getReseller();
        $clientIds = ResellerClient::where('reseller_id', $reseller->id)->pluck('client_id');

        if (!$clientIds->contains($request->client_id)) {
            return back()->with('error', 'Client not found');
        }

        try {
            $client = Client::findOrFail($request->client_id);
            $billingService = app(BillingService::class);
            $ledgerService = app(LedgerService::class);

            $payment = DB::transaction(function () use ($request, $reseller, $client, $billingService, $ledgerService) {
                $payment = $billingService->recordPayment($client, [
                    'amount' => $request->amount,
                    'payment_method_id' => $request->payment_method_id,
                    'payment_date' => now(),
                    'remarks' => $request->notes ?? 'Collected by sub-reseller',
                    'money_receipt_no' => $request->money_receipt_no ?: null,
                ], Auth::id(), 'sub-reseller');

                $ledgerService->createPaymentEntry($payment);
                $this->subResellerService->recordCommission($reseller, $request->client_id, $payment->id, $request->amount);
                return $payment;
            });

            $sendSms = $request->boolean('send_sms', true);
            app(SmsService::class)->sendPaymentConfirmation($client, $request->amount, $payment->payment_no ?? '', $sendSms);

            return back()->with('success', 'Payment of ৳' . number_format($request->amount, 2) . ' collected successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to collect payment: ' . $e->getMessage());
        }
    }
    // ─── Selfie Attendance ───────────────────────────────────────────────────
    public function myAttendance()
    {
        $user = Auth::user();
        $todayCheckin  = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', 'check_in')->first();
        $todayCheckout = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', 'check_out')->first();
        $history = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->orderBy('attendance_date', 'desc')->orderBy('check_in_time', 'asc')
            ->get()->groupBy(fn($r) => $r->attendance_date->format('Y-m-d'));
        return view('sub-reseller.attendance', compact('todayCheckin', 'todayCheckout', 'history'));
    }

    public function storeAttendance(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'selfie'           => 'required|string',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'type'             => 'required|in:check_in,check_out',
            'location_address' => 'nullable|string|max:500',
        ]);
        $user = Auth::user();
        $alreadyExists = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', $request->type)->exists();
        if ($alreadyExists) {
            $label = $request->type === 'check_in' ? 'Check In' : 'Check Out';
            return response()->json(['error' => "You have already done {$label} today."], 422);
        }
        $imageData = $request->selfie;
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $m)) {
            $ext = $m[1]; $imageData = substr($imageData, strpos($imageData, ',') + 1);
        } else { $ext = 'jpg'; }
        $decoded = base64_decode($imageData);
        if (!$decoded) return response()->json(['error' => 'Invalid image data.'], 422);
        $fileName = 'selfie_' . $user->id . '_' . now()->format('Ymd_His') . '.' . $ext;
        $path     = 'attendances/selfies/' . now()->format('Y/m');
        $fullDir  = storage_path('app/public/' . $path);
        if (!is_dir($fullDir)) { mkdir($fullDir, 0775, true); @chown($fullDir, 'www-data'); }
        file_put_contents($fullDir . '/' . $fileName, $decoded);
        $employee = \App\Models\Employee::where('user_id', $user->id)->first();
        EmployeeSelfieAttendance::create([
            'user_id'          => $user->id,
            'employee_id'      => $employee?->id,
            'attendance_date'  => today(),
            'check_in_time'    => now()->format('H:i:s'),
            'selfie_path'      => $path . '/' . $fileName,
            'latitude'         => $request->latitude,
            'longitude'        => $request->longitude,
            'location_address' => $request->location_address,
            'ip_address'       => $request->ip(),
            'device_info'      => substr($request->userAgent() ?? '', 0, 255),
            'type'             => $request->type,
            'company_id'       => $user->company_id ?? 1,
        ]);
        $typeLabel = $request->type === 'check_in' ? 'Check In' : 'Check Out';
        return response()->json(['success' => true, 'message' => "{$typeLabel} recorded at " . now()->format('h:i:s A')]);
    }

}
