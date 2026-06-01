<?php

namespace App\Http\Controllers\CardDistributor;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSelfieAttendance;
use App\Models\CardDistributor;
use App\Models\CardTransaction;
use App\Models\ScratchCard;
use App\Models\ScratchCardBatch;
use App\Services\CardBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CardDistributorDashboardController extends Controller
{
    protected function getDistributor()
    {
        return CardDistributor::where('user_id', Auth::id())->firstOrFail();
    }

    public function index()
    {
        $distributor = $this->getDistributor();

        $totalCards = ScratchCard::where('distributor_id', $distributor->id)->count();
        $soldCards = ScratchCard::where('distributor_id', $distributor->id)->where('status', 'used')->count();
        $availableCards = ScratchCard::where('distributor_id', $distributor->id)->where('status', 'active')->count();

        $totalCommission = CardTransaction::where('distributor_id', $distributor->id)->sum('commission');
        $monthlyCommission = CardTransaction::where('distributor_id', $distributor->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('commission');

        $totalSales = CardTransaction::where('distributor_id', $distributor->id)->sum('amount');

        $recentTransactions = CardTransaction::where('distributor_id', $distributor->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('card-distributor.dashboard', compact(
            'distributor', 'totalCards', 'soldCards', 'availableCards',
            'totalCommission', 'monthlyCommission', 'totalSales', 'recentTransactions'
        ));
    }

    public function cards(Request $request)
    {
        $distributor = $this->getDistributor();

        $query = ScratchCard::where('distributor_id', $distributor->id);

        if ($request->status) $query->where('status', $request->status);
        if ($request->batch_id) $query->where('batch_id', $request->batch_id);

        $cards = $query->latest()->paginate(20);
        $batches = ScratchCardBatch::whereHas('cards', fn($q) => $q->where('distributor_id', $distributor->id))
            ->get();

        return view('card-distributor.cards', compact('distributor', 'cards', 'batches'));
    }

    public function sales(Request $request)
    {
        $distributor = $this->getDistributor();

        $query = CardTransaction::where('distributor_id', $distributor->id);

        if ($request->date_from) $query->where('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->where('created_at', '<=', $request->date_to);

        $transactions = $query->latest()->paginate(20);

        $totalSales = (clone $query)->sum('amount');
        $totalCommission = (clone $query)->sum('commission');

        return view('card-distributor.sales', compact('distributor', 'transactions', 'totalSales', 'totalCommission'));
    }

    public function sellCard(Request $request)
    {
        $request->validate([
            'card_code' => 'required|string',
            'customer_phone' => 'required|string',
        ]);

        $distributor = $this->getDistributor();

        $card = ScratchCard::where('card_number', $request->card_code)
            ->where('distributor_id', $distributor->id)
            ->where('status', 'active')
            ->first();

        if (!$card) {
            return back()->with('error', 'Card not found or already used');
        }

        DB::transaction(function () use ($card, $distributor, $request) {
            $card->update(['status' => 'used', 'used_at' => now()]);

            CardTransaction::create([
                'distributor_id' => $distributor->id,
                'card_id' => $card->id,
                'amount' => $card->amount ?? 0,
                'commission' => ($card->amount ?? 0) * ($distributor->commission_rate / 100),
                'customer_phone' => $request->customer_phone,
            ]);
        });

        return back()->with('success', 'Card sold successfully');
    }

    public function commissions()
    {
        $distributor = $this->getDistributor();

        $commissions = CardTransaction::where('distributor_id', $distributor->id)
            ->where('commission', '>', 0)
            ->latest()
            ->paginate(20);

        $totalCommission = CardTransaction::where('distributor_id', $distributor->id)->sum('commission');
        $monthlyCommission = CardTransaction::where('distributor_id', $distributor->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('commission');

        return view('card-distributor.commissions', compact('distributor', 'commissions', 'totalCommission', 'monthlyCommission'));
    }

    public function wallet()
    {
        $distributor = $this->getDistributor();

        return view('card-distributor.wallet', compact('distributor'));
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
        return view('card-distributor.attendance', compact('todayCheckin', 'todayCheckout', 'history'));
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
