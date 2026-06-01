<?php

namespace App\Http\Controllers\Billman;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSelfieAttendance;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Zone;
use App\Models\StaffDue;
use App\Services\BillingService;
use App\Services\LedgerService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillmanController extends Controller
{
    public function dashboard()
    {
        $todayCollection = Payment::whereDate('created_at', today())
            ->where('collected_by', Auth::id())
            ->sum('amount');

        $monthlyCollection = Payment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('collected_by', Auth::id())
            ->sum('amount');

        $allTimeCollection = Payment::where('collected_by', Auth::id())->sum('amount');

        $todayCount = Payment::whereDate('created_at', today())
            ->where('collected_by', Auth::id())
            ->count();

        $monthlyCount = Payment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('collected_by', Auth::id())
            ->count();

        $staffDue = StaffDue::where('user_id', Auth::id())->first();
        $myDueToAdmin = $staffDue ? $staffDue->current_due : 0;
        $totalSubmitted = $staffDue ? $staffDue->total_submitted : 0;

        $pendingSubmissions = \App\Models\StaffSubmission::where('staff_user_id', Auth::id())
            ->where('status', 'pending')
            ->orderByDesc('submission_date')
            ->get();
        $pendingAmount = $pendingSubmissions->sum('amount');

        $unsubmittedPayments = Payment::with(['client'])
            ->where('collected_by', Auth::id())
            ->where('submitted_to_admin', false)
            ->orderBy('created_at', 'desc')
            ->get();

        $todayPayments = Payment::with(['client'])
            ->where('collected_by', Auth::id())
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('billman.dashboard', compact(
            'todayCollection', 'monthlyCollection', 'allTimeCollection',
            'todayCount', 'monthlyCount',
            'myDueToAdmin', 'totalSubmitted',
            'pendingSubmissions', 'pendingAmount',
            'unsubmittedPayments', 'todayPayments'
        ));
    }

    public function quickSearch(Request $request)
    {
        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $clients = Client::with(['zone', 'package'])
            ->where(function($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('client_id', 'ilike', "%{$query}%")
                  ->orWhere('phone', 'ilike', "%{$query}%")
                  ->orWhere('username', 'ilike', "%{$query}%")
                  ->orWhere('address', 'ilike', "%{$query}%");
            })
            ->limit(15)
            ->get()
            ->map(function($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'client_id' => $client->client_id,
                    'phone' => $client->phone ?? 'N/A',
                    'username' => $client->username,
                    'zone' => $client->zone->name ?? 'N/A',
                    'package' => $client->package->name ?? 'N/A',
                    'status' => $client->status,
                    'due_amount' => $client->due_amount ?? 0,
                    'connection_status' => $client->connection_status ?? 'offline',
                    'url' => route('billman.client.show', $client),
                ];
            });

        return response()->json($clients);
    }

    public function showClient(Client $client)
    {
        $client->load(['zone', 'area', 'package', 'assignedTo', 'application.technician', 'bills' => function($q) {
            $q->orderBy('billing_month', 'desc');
        }, 'bills.payments.paymentMethod', 'bills.payments.collectedBy', 'payments.paymentMethod', 'payments.collectedBy']);

        $onuRecord = null;
        if ($client->olt_device_id || $client->onu_serial || $client->onu_mac) {
            $onuRecord = \DB::table('onus')->where(function($q) use ($client) {
                if ($client->onu_serial) $q->orWhere('serial_number', $client->onu_serial);
                if ($client->onu_mac)    $q->orWhere('mac_address', $client->onu_mac);
            })->first();
            if (!$onuRecord && $client->id) {
                $onuRecord = \DB::table('onus')->where('client_id', $client->id)->first();
            }
        }
        return view('billman.client-show', compact('client', 'onuRecord'));
    }

    public function collectPayment(Request $request, Client $client)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'bill_id' => 'nullable|exists:bills,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $bill = null;
        if ($request->bill_id) {
            $bill = Bill::where('id', $request->bill_id)
                ->where('client_id', $client->id)
                ->firstOrFail();
        } else {
            $bill = Bill::where('client_id', $client->id)
                ->where('is_cancelled', false)
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('billing_month', 'asc')
                ->first();
        }

        $notes = $request->notes_reason ? $request->notes_reason : '';
        if ($request->notes) {
            $notes .= ($notes ? ' - ' : '') . $request->notes;
        }
        if ($request->filled('mobile_payment_number')) {
            $methodName = \App\Models\PaymentMethod::find($request->payment_method_id)?->name ?? 'Mobile';
            $mobileInfo = "{$methodName} Number: {$request->mobile_payment_number}";
            $notes = $notes ? "{$mobileInfo} | {$notes}" : $mobileInfo;
        }

        try {
            $payment = DB::transaction(function () use ($client, $request, $notes, $bill) {
                $billingService = app(BillingService::class);

                $payment = $billingService->recordPayment($client, [
                    'amount' => $request->amount,
                    'payment_method_id' => $request->payment_method_id,
                    'payment_date' => now(),
                    'remarks' => $notes ?: ($bill ? null : 'Advance Payment'),
                    'bill_id' => $bill ? $bill->id : null,
                    'transaction_id' => $request->transaction_id,
                    'money_receipt_no' => $request->money_receipt_no ?: null,
                    'reason' => $request->filled('notes_reason') ? $request->notes_reason : null,
                ], Auth::id(), 'billman');

                app(LedgerService::class)->createPaymentEntry($payment, Auth::id(), 'billman');

                StaffDue::lockForUpdate()->firstOrCreate(
                    ['user_id' => Auth::id()],
                    ['total_collected' => 0, 'total_submitted' => 0, 'current_due' => 0]
                );
                StaffDue::where('user_id', Auth::id())
                    ->increment('total_collected', $request->amount);
                StaffDue::where('user_id', Auth::id())
                    ->increment('current_due', $request->amount);
                StaffDue::where('user_id', Auth::id())
                    ->update(['last_collection_date' => now()]);

                return $payment;
            });

            $sendSms = $request->boolean('send_sms', true);
            app(SmsService::class)->sendPaymentConfirmation($client, $request->amount, $payment->payment_no, $sendSms);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment of ৳' . number_format($request->amount) . ' collected successfully',
                    'payment_id' => $payment->id,
                    'payment_no' => $payment->payment_no,
                    'receipt_url' => route('billman.payments.receipt', $payment),
                    'print_url' => route('billman.payments.receipt.print', $payment),
                    'thermal_url' => route('billman.payments.receipt.thermal', $payment),
                    'download_url' => route('billman.payments.receipt.download', $payment),
                ]);
            }

            $dateTime = now()->format('d-m-y h:i:s A') . ' (BDT GMT+6)';
            return redirect()->route('billman.client.show', $client)
                ->with('success', 'Payment of ৳' . number_format($request->amount) . ' collected on ' . $dateTime);
        } catch (\Exception $e) {
            Log::error('Billman payment collection failed: ' . $e->getMessage(), [
                'client_id' => $client->id,
                'amount' => $request->amount,
                'billman_id' => Auth::id(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    public function collectionReport(Request $request)
    {
        $startDate = $request->start_date ?? today()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? today()->format('Y-m-d');

        $payments = Payment::with(['client', 'client.zone', 'paymentMethod'])
            ->where('collected_by', Auth::id())
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        $totalCollection = Payment::where('collected_by', Auth::id())
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $totalCount = Payment::where('collected_by', Auth::id())
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->count();

        return view('billman.collection-report', compact('payments', 'totalCollection', 'totalCount', 'startDate', 'endDate'));
    }

    public function submitToAdmin(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $staffDue = StaffDue::where('user_id', Auth::id())->first();
        if (!$staffDue || $staffDue->current_due < $request->amount) {
            return redirect()->back()->with('error', 'Submission amount cannot exceed your current due.');
        }

        // Check if there is already a pending submission
        $existingPending = \App\Models\StaffSubmission::where('staff_user_id', Auth::id())
            ->where('status', 'pending')
            ->sum('amount');

        $availableDue = $staffDue->current_due - $existingPending;
        if ($availableDue < $request->amount) {
            return redirect()->back()->with('error', 'You already have ৳' . number_format($existingPending, 0) . ' pending admin approval. Available to submit: ৳' . number_format($availableDue, 0));
        }

        // Create pending submission — do NOT decrement current_due yet
        // current_due will be decremented when admin APPROVES
        \App\Models\StaffSubmission::create([
            'staff_user_id' => Auth::id(),
            'amount' => $request->amount,
            'remarks' => $request->notes,
            'submission_date' => now(),
            'status' => 'pending',
        ]);

        return redirect()->route('billman.dashboard')
            ->with('success', 'Amount ৳' . number_format($request->amount) . ' submitted to admin. Waiting for approval.');
    }

    public function submissionHistory()
    {
        $submissions = \App\Models\StaffSubmission::where('staff_user_id', Auth::id())
            ->orderBy('submission_date', 'desc')
            ->paginate(20);

        $staffDue = StaffDue::where('user_id', Auth::id())->first();

        $pendingAmount = \App\Models\StaffSubmission::where('staff_user_id', Auth::id())
            ->where('status', 'pending')
            ->sum('amount');

        return view('billman.submission-history', compact('submissions', 'staffDue', 'pendingAmount'));
    }

    public function profile()
    {
        $user = Auth::user();
        $user->load('employee');
        return view('billman.profile', compact('user'));
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
        return view('billman.attendance', compact('todayCheckin', 'todayCheckout', 'history'));
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
