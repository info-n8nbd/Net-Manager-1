<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSelfieAttendance;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\Branch;
use App\Models\Pop;
use App\Models\ManagerAssignment;
use App\Models\Reseller;
use App\Services\BranchService;
use App\Services\BillingService;
use App\Services\LedgerService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerDashboardController extends Controller
{
    protected BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    protected function getManagerAssignment()
    {
        return ManagerAssignment::withoutGlobalScopes()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();
    }

    protected function getBranch()
    {
        $assignment = $this->getManagerAssignment();
        return Branch::findOrFail($assignment->branch_id);
    }

    protected function branchClients(int $branchId)
    {
        return Client::where('branch_id', $branchId)->whereNull('reseller_id');
    }

    public function index()
    {
        $assignment = $this->getManagerAssignment();
        $branch = Branch::find($assignment->branch_id);
        $stats = $this->branchService->getBranchStats($assignment->branch_id);

        $dueAmount = $this->branchClients($assignment->branch_id)
            ->where('status', 'active')
            ->sum('due_amount');

        $recentPayments = Payment::with('client')
            ->whereHas('client', fn($q) => $q->where('branch_id', $assignment->branch_id)->whereNull('reseller_id'))
            ->latest('payment_date')
            ->limit(10)
            ->get();

        return view('manager.dashboard', compact('assignment', 'branch', 'stats', 'dueAmount', 'recentPayments'));
    }

    public function clients(Request $request)
    {
        $assignment = $this->getManagerAssignment();

        $query = $this->branchClients($assignment->branch_id)->with('package');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('username', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) $query->where('status', $request->status);
        if ($request->zone_id) $query->where('zone_id', $request->zone_id);

        $clients = $query->latest()->paginate(20);
        $zones = \App\Models\Zone::where('branch_id', $assignment->branch_id)->get();

        return view('manager.clients', compact('assignment', 'clients', 'zones'));
    }

    public function billing(Request $request)
    {
        $assignment = $this->getManagerAssignment();

        if (!$assignment->can_manage_billing) {
            return redirect()->route('manager.dashboard')->with('error', 'You do not have billing permissions');
        }

        $clientIds = $this->branchClients($assignment->branch_id)->pluck('id');

        $bills = Bill::whereIn('client_id', $clientIds)
            ->with('client')
            ->latest()
            ->paginate(20);

        return view('manager.billing', compact('assignment', 'bills'));
    }

    public function reports()
    {
        $assignment = $this->getManagerAssignment();

        if (!$assignment->can_view_reports) {
            return redirect()->route('manager.dashboard')->with('error', 'You do not have report permissions');
        }

        $branchId = $assignment->branch_id;

        $monthlyRevenue = Payment::whereHas('client', fn($q) => $q->where('branch_id', $branchId)->whereNull('reseller_id'))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $clientsByStatus = $this->branchClients($branchId)
            ->select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $collectionTrend = Payment::whereHas('client', fn($q) => $q->where('branch_id', $branchId)->whereNull('reseller_id'))
            ->whereYear('created_at', now()->year)
            ->select(\DB::raw('EXTRACT(MONTH FROM payment_date) as month'), \DB::raw('SUM(amount) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('manager.reports', compact('assignment', 'monthlyRevenue', 'clientsByStatus', 'collectionTrend'));
    }

    public function pops()
    {
        $assignment = $this->getManagerAssignment();
        $pops = Pop::where('branch_id', $assignment->branch_id)
            ->withCount('clients')
            ->paginate(20);

        return view('manager.pops', compact('assignment', 'pops'));
    }

    public function resellers()
    {
        $assignment = $this->getManagerAssignment();

        if (!$assignment->can_manage_resellers) {
            return redirect()->route('manager.dashboard')->with('error', 'You do not have reseller management permissions');
        }

        $resellers = Reseller::where('branch_id', $assignment->branch_id)
            ->with('user')
            ->paginate(20);

        return view('manager.resellers', compact('assignment', 'resellers'));
    }

    public function collectPayment(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        $assignment = $this->getManagerAssignment();
        $client = $this->branchClients($assignment->branch_id)
            ->where('id', $request->client_id)
            ->firstOrFail();

        try {
            $billingService = app(BillingService::class);
            $ledgerService = app(LedgerService::class);

            $payment = $billingService->recordPayment($client, [
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'payment_date' => now(),
                'remarks' => $request->notes ?? 'Collected by manager',
                'money_receipt_no' => $request->money_receipt_no ?: null,
            ], Auth::id(), 'manager');

            $ledgerService->createPaymentEntry($payment);

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
        return view('manager.attendance', compact('todayCheckin', 'todayCheckout', 'history'));
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
