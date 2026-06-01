<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyCollection;
use App\Models\Client;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\PaymentMethod;
use App\Models\StaffDue;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyCollectionController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date ?? today()->format('Y-m-d');
        $userId = $request->user_id;
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        $query = DailyCollection::with(['user', 'client', 'payment'])
            ->whereDate('collection_date', $date);

        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $collections = $query->orderBy('created_at', 'desc')->get();
        $totalAmount = $collections->sum('amount');
        $totalCount = $collections->count();
        $approvedAmount = $collections->where('status', 'approved')->sum('amount');
        $pendingAmount = $collections->where('status', 'pending')->sum('amount');

        $collectors = $isSuperAdmin
            ? User::whereHas('dailyCollections')->orderBy('name')->get()
            : collect();

        $dailySummary = collect();
        if ($isSuperAdmin) {
            $dailySummary = DailyCollection::with('user')
                ->whereDate('collection_date', $date)
                ->select('user_id', DB::raw('COUNT(*) as total_bills'), DB::raw('SUM(amount) as total_amount'))
                ->groupBy('user_id')
                ->get();
        }

        return view('admin.billing.daily-collections', compact(
            'collections', 'date', 'totalAmount', 'totalCount',
            'approvedAmount', 'pendingAmount', 'collectors', 'dailySummary', 'isSuperAdmin'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:1',
            'collection_date' => 'required|date',
            'payment_method' => 'required|string',
            'remarks' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $client = Client::findOrFail($request->client_id);

        DailyCollection::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'amount' => $request->amount,
            'collection_date' => $request->collection_date,
            'payment_method' => $request->payment_method,
            'receipt_no' => 'COL-' . now()->format('YmdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT),
            'remarks' => $request->remarks,
            'status' => 'pending',
            'company_id' => $user->company_id ?? 1,
        ]);

        ActivityLog::log('create', 'daily_collections', "Bill collected ৳{$request->amount} from {$client->name} ({$client->client_id})");
        return back()->with('success', "Collection of ৳{$request->amount} from {$client->name} recorded successfully.");
    }

    public function approve(Request $request, DailyCollection $collection)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin')) abort(403);

        if ($collection->status !== 'pending') {
            return back()->with('error', 'This collection is already ' . $collection->status . '.');
        }

        try {
            DB::transaction(function () use ($collection, $user) {
                // Find payment method by name
                $paymentMethod = PaymentMethod::where('name', 'ilike', $collection->payment_method)
                    ->orWhere('code', 'ilike', $collection->payment_method)
                    ->first();

                // Get client
                $client = Client::findOrFail($collection->client_id);

                // Create payment via BillingService (applies to bills, recalculates balance)
                $billingService = app(BillingService::class);
                $payment = $billingService->recordPayment($client, [
                    'amount'            => $collection->amount,
                    'payment_method_id' => $paymentMethod?->id,
                    'payment_date'      => $collection->collection_date,
                    'remarks'           => 'Daily Collection ' . $collection->receipt_no
                                          . ($collection->remarks ? ' — ' . $collection->remarks : ''),
                ], $collection->user_id, 'daily_collection');

                // Update StaffDue — employee has cash in hand until submitted to admin
                $staffDue = StaffDue::firstOrCreate(
                    ['user_id' => $collection->user_id, 'company_id' => $collection->company_id],
                    ['current_due' => 0, 'total_collected' => 0, 'total_submitted' => 0, 'company_id' => $collection->company_id]
                );
                $staffDue->current_due    = $staffDue->current_due + $collection->amount;
                $staffDue->total_collected = $staffDue->total_collected + $collection->amount;
                $staffDue->last_collection_date = $collection->collection_date;
                $staffDue->save();

                // Link payment and update status
                $collection->update([
                    'status'      => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'payment_id'  => $payment->id,
                ]);

                ActivityLog::log('update', 'daily_collections',
                    "Approved collection #{$collection->id} ৳{$collection->amount} — Payment {$payment->payment_no} created for {$client->name}");
            });

            return back()->with('success', 'Collection approved and payment recorded successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, DailyCollection $collection)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin')) abort(403);
        $collection->update(['status' => 'rejected', 'approved_by' => $user->id, 'approved_at' => now()]);
        return back()->with('success', 'Collection rejected.');
    }

    public function report(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate   = $request->end_date   ?? today()->format('Y-m-d');
        $userId    = $request->user_id;
        $user      = auth()->user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        $query = \App\Models\Payment::with(['client', 'collectedBy', 'paymentMethod'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'approved']);

        if (!$isSuperAdmin) {
            $query->where('collected_by', $user->id);
        } elseif ($userId) {
            $query->where('collected_by', $userId);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        $collections = $payments->map(function ($p) {
            return (object) [
                'collection_date' => $p->payment_date,
                'user'            => $p->collectedBy,
                'client'          => $p->client,
                'amount'          => $p->amount,
                'payment_method'  => optional($p->paymentMethod)->name ?? ($p->transaction_id ? 'Online' : 'Cash'),
                'status'          => 'approved',
                'receipt_no'      => $p->payment_no ?? $p->money_receipt_no ?? '',
            ];
        });

        $summary = $payments->groupBy('collected_by')->map(function ($group) {
            return (object) [
                'user'            => $group->first()->collectedBy,
                'total_bills'     => $group->count(),
                'total_amount'    => $group->sum('amount'),
                'approved_amount' => $group->sum('amount'),
                'pending_amount'  => 0,
            ];
        })->values();

        $collectors = $isSuperAdmin
            ? \App\Models\User::whereHas('collectedPayments')->orderBy('name')->get()
            : collect();

        return view('admin.billing.collection-report', compact(
            'collections', 'summary', 'startDate', 'endDate', 'collectors', 'isSuperAdmin'
        ));
    }

    public function searchClients(Request $request)
    {
        $search = $request->q;
        $clients = Client::where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('client_id', 'ILIKE', "%{$search}%")
              ->orWhere('phone', 'ILIKE', "%{$search}%");
        })->with('package')->limit(20)->get()
          ->map(fn($c) => [
              'id' => $c->id,
              'text' => "{$c->client_id} - {$c->name} ({$c->phone})",
              'due' => $c->due_amount ?? 0,
              'package' => $c->package->name ?? 'N/A',
          ]);

        return response()->json(['results' => $clients]);
    }
}
