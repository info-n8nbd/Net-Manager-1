<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EmployeeSelfieAttendance;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Package;
use App\Models\MikrotikDevice;
use App\Models\Employee;
use App\Models\StaffDue;
use App\Services\ClientActivityService;
use App\Services\ClientFilterService;
use App\Services\LedgerService;
use App\Services\SmsService;
use App\Services\BillingService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechnicianDashboardController extends Controller
{
    protected function getTechnicianZones(): array
    {
        $user = Auth::user();
        $employee = Employee::withoutGlobalScopes()->where('user_id', $user->id)->first();
        if ($employee && !empty($employee->zone_ids)) {
            $ids = array_filter(array_map('intval', (array) $employee->zone_ids));
            return array_values($ids);
        }
        return [];
    }

    protected function authorizeClientZone(Client $client, bool $jsonResponse = false)
    {
        $zoneIds = $this->getTechnicianZones();
        if (empty($zoneIds) || !in_array($client->zone_id, $zoneIds)) {
            if ($jsonResponse) {
                abort(response()->json(['success' => false, 'message' => 'Client not in your assigned zone'], 403));
            }
            abort(403, 'Client not in your assigned zone.');
        }
    }

    public function index()
    {
        $zoneIds = $this->getTechnicianZones();
        
        $totalClients = !empty($zoneIds) ? Client::whereIn('zone_id', $zoneIds)->count() : 0;
        $activeClients = !empty($zoneIds) ? Client::where('status', 'active')->whereIn('zone_id', $zoneIds)->count() : 0;
        $inactiveClients = !empty($zoneIds) ? Client::where('status', 'inactive')->whereIn('zone_id', $zoneIds)->count() : 0;
        $todayCollection = Payment::whereDate('created_at', today())
            ->where('collected_by', Auth::id())
            ->sum('amount');
        
        $monthlyCollection = Payment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('collected_by', Auth::id())
            ->sum('amount');
        
        $allTimeCollection = Payment::where('collected_by', Auth::id())->sum('amount');
        
        $totalDue = !empty($zoneIds) ? Client::whereIn('zone_id', $zoneIds)->sum('due_amount') : 0;
        
        $assignedTickets = Ticket::where('technician_id', Auth::id())
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
        
        $recentClients = !empty($zoneIds) ? Client::with(['zone', 'package'])
            ->whereIn('zone_id', $zoneIds)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get() : collect();
        
        $zones = !empty($zoneIds) ? Zone::withoutGlobalScopes()->whereIn('id', $zoneIds)->get() : collect();

        // Zone-wise stats for dashboard
        $zoneStats = collect();
        foreach ($zones as $zone) {
            $zoneClients = Client::where('zone_id', $zone->id);
            $zoneStats->push([
                'name'          => $zone->name,
                'total'         => (clone $zoneClients)->count(),
                'active'        => (clone $zoneClients)->where('status', 'active')->count(),
                'inactive'      => (clone $zoneClients)->where('status', 'inactive')->count(),
                'monthly_bill'  => (clone $zoneClients)->sum('monthly_fee'),
            ]);
        }
        
        $todayPayments = Payment::with(['client'])
            ->where('collected_by', Auth::id())
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();
        
        $user = Auth::user();
        
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
        
        return view('technician.dashboard', compact(
            'totalClients', 'activeClients', 'inactiveClients',
            'todayCollection', 'monthlyCollection', 'allTimeCollection', 'totalDue', 'assignedTickets',
            'recentClients', 'zones', 'zoneStats', 'todayPayments', 'user', 'myDueToAdmin', 'totalSubmitted', 'unsubmittedPayments', 'pendingSubmissions', 'pendingAmount'
        ));
    }

    public function chartData()
    {
        $zoneIds = $this->getTechnicianZones();
        $userId = Auth::id();

        // Daily collection for last 30 days (using created_at for consistency with dashboard)
        $dailyCollection = Payment::where('collected_by', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $dates = [];
        $amounts = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = now()->subDays($i)->format('d M');
            $amounts[] = isset($dailyCollection[$date]) ? (float)$dailyCollection[$date] : 0;
        }

        // Monthly collection for last 6 months (using created_at for consistency with dashboard)
        $monthlyCollection = Payment::where('collected_by', $userId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('EXTRACT(YEAR FROM created_at) as year, EXTRACT(MONTH FROM created_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $monthLabels = [];
        $monthAmounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthLabels[] = $date->format('M Y');
            $found = $monthlyCollection->first(function($item) use ($date) {
                return $item->year == $date->year && $item->month == $date->month;
            });
            $monthAmounts[] = $found ? (float)$found->total : 0;
        }

        // Client status in zone
        $statusDistribution = [
            'active' => !empty($zoneIds) ? Client::where('status', 'active')->whereIn('zone_id', $zoneIds)->count() : 0,
            'inactive' => !empty($zoneIds) ? Client::where('status', 'inactive')->whereIn('zone_id', $zoneIds)->count() : 0,
        ];

        // Ticket stats
        $ticketStats = [
            'open' => Ticket::where('assigned_to', $userId)->where('status', 'open')->count(),
            'in_progress' => Ticket::where('assigned_to', $userId)->where('status', 'in_progress')->count(),
            'closed' => Ticket::where('assigned_to', $userId)->where('status', 'closed')->count(),
        ];

        return response()->json([
            'daily' => ['labels' => $dates, 'data' => $amounts],
            'monthly' => ['labels' => $monthLabels, 'data' => $monthAmounts],
            'status' => $statusDistribution,
            'tickets' => $ticketStats,
        ]);
    }

    public function clients(Request $request)
    {
        session(['last_client_list_url' => url()->current()]);
        
        $zoneIds = $this->getTechnicianZones();
        $isSearching = !empty($request->search);
        
        $query = Client::with(['zone', 'area', 'box', 'package', 'mikrotikDevice']);
        
        if (!empty($zoneIds)) {
            $query->whereIn('zone_id', $zoneIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($isSearching) {
            $query->where(function($q) use ($request) {
                $search = $request->search;
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('client_id', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhere('address', 'ilike', "%{$search}%")
                  ->orWhereHas('zone', function($sq) use ($search) {
                      $sq->where('name', 'ilike', "%{$search}%");
                  })
                  ->orWhereHas('area', function($sq) use ($search) {
                      $sq->where('name', 'ilike', "%{$search}%");
                  })
                  ->orWhereHas('box', function($sq) use ($search) {
                      $sq->where('name', 'ilike', "%{$search}%");
                  });
            });
        }
        
        if ($request->status === 'due') {
            $query->where('due_amount', '>', 0);
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->zone_id) {
            $query->where('zone_id', $request->zone_id);
        }
        if ($request->area_id) {
            $query->where('area_id', $request->area_id);
        }
        if ($request->package_id) {
            $query->where('package_id', $request->package_id);
        }
        if ($request->connection_type) {
            $query->where('connection_type', $request->connection_type);
        }
        if ($request->connection_medium) {
            $query->where('connection_medium', $request->connection_medium);
        }
        if ($request->mikrotik_device_id) {
            $query->where('mikrotik_device_id', $request->mikrotik_device_id);
        }
        if ($request->billing_date) {
            $query->where('billing_date', $request->billing_date);
        }
        if ($request->due_filter == 'with_due') {
            $query->where('due_amount', '>', 0);
        } elseif ($request->due_filter == 'no_due') {
            $query->where(function($q) {
                $q->where('due_amount', '<=', 0)->orWhereNull('due_amount');
            });
        }
        if ($request->mac_filter == 'with_mac') {
            $query->whereNotNull('mac_address')->where('mac_address', '!=', '');
        } elseif ($request->mac_filter == 'no_mac') {
            $query->where(function($q) {
                $q->whereNull('mac_address')->orWhere('mac_address', '');
            });
        }
        if ($request->free_filter == 'free') {
            $query->where('is_free', true);
        } elseif ($request->free_filter == 'paid') {
            $query->where(function($q) {
                $q->where('is_free', false)->orWhereNull('is_free');
            });
        }
        if ($request->onu_filter) {
            if ($request->onu_filter == 'company') {
                $query->where('onu_required', true);
            } elseif ($request->onu_filter == 'client') {
                $query->where(function($q) {
                    $q->where('onu_required', false)->orWhereNull('onu_required');
                });
            }
        }
        if ($request->billing_status) {
            $currentMonth = now()->month;
            $currentYear = now()->year;
            if ($request->billing_status == 'generated') {
                $query->whereHas('bills', function($q) use ($currentMonth, $currentYear) {
                    $q->whereMonth('bill_date', $currentMonth)->whereYear('bill_date', $currentYear);
                });
            } elseif ($request->billing_status == 'not_generated') {
                $query->whereDoesntHave('bills', function($q) use ($currentMonth, $currentYear) {
                    $q->whereMonth('bill_date', $currentMonth)->whereYear('bill_date', $currentYear);
                });
            }
        }
        if ($request->payment_status) {
            switch ($request->payment_status) {
                case 'paid':
                    $query->where(function($q) { $q->where('due_amount', '<=', 0)->orWhereNull('due_amount'); });
                    break;
                case 'unpaid':
                    $query->where('due_amount', '>', 0);
                    break;
                case 'partial':
                    $query->where('due_amount', '>', 0)->where('balance', '>', 0);
                    break;
                case 'advance':
                    $query->where('balance', '>', 0)->where(function($q) { $q->where('due_amount', '<=', 0)->orWhereNull('due_amount'); });
                    break;
                case 'overdue':
                    $query->where('due_amount', '>', 0)->where('status', 'active');
                    break;
            }
        }
        if ($request->client_type) {
            $query->where('client_type', $request->client_type);
        }
        if ($request->expire_from) {
            $query->whereDate('expire_date', '>=', $request->expire_from);
        }
        if ($request->expire_to) {
            $query->whereDate('expire_date', '<=', $request->expire_to);
        }
        
        $perPage = in_array($request->per_page, [20, 50, 100, 200]) ? (int)$request->per_page : 20;
        $clients = $query->orderBy('name')->paginate($perPage);
        
        $zones = $isSearching ? Zone::orderBy('name')->get() : (!empty($zoneIds) ? Zone::withoutGlobalScopes()->whereIn('id', $zoneIds)->get() : collect());
        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $mikrotikDevices = MikrotikDevice::where('is_active', true)->orderBy('name')->get();
        $areas = $request->zone_id ? Area::where('zone_id', $request->zone_id)->where('is_active', true)->get() : collect();
        
        $allFilterKeys = ['zone_id','area_id','package_id','connection_type','connection_medium','mikrotik_device_id','billing_status','payment_status','client_type','billing_date','due_filter','mac_filter','free_filter','onu_filter','expire_from','expire_to','per_page'];
        $activeFilterCount = collect($allFilterKeys)->filter(fn($f) => $request->filled($f))->count();
        $filteredCount = $activeFilterCount > 0 ? $clients->total() : null;
        
        $totalClients = !empty($zoneIds) ? Client::whereIn('zone_id', $zoneIds)->count() : 0;
        $activeClients = !empty($zoneIds) ? Client::where('status', 'active')->whereIn('zone_id', $zoneIds)->count() : 0;
        $inactiveClients = !empty($zoneIds) ? Client::where('status', 'inactive')->whereIn('zone_id', $zoneIds)->count() : 0;
        $dueClients = !empty($zoneIds) ? Client::where('due_amount', '>', 0)->whereIn('zone_id', $zoneIds)->count() : 0;
        return view('technician.clients', compact(
            'clients', 'zones', 'totalClients', 'activeClients', 'inactiveClients', 'dueClients', 'isSearching',
            'packages', 'mikrotikDevices', 'areas', 'filteredCount', 'activeFilterCount', 'perPage', 'zoneIds'
        ));
    }

    public function showClient(Client $client)
    {
        $zoneIds = $this->getTechnicianZones();
        $isAssignedZone = !empty($zoneIds) && in_array($client->zone_id, $zoneIds);
        
        // Allow view-only access for out-of-zone clients found via search
        // (Edit/payment actions will be disabled in the view for !$isAssignedZone)

        $client->load(['zone', 'area', 'package', 'assignedTo', 'application.technician', 'bills.payments.paymentMethod', 'bills.payments.collectedBy', 'payments.paymentMethod', 'payments.collectedBy', 'activityLogs.performedByUser', 'serviceCharges.paidByUser']);
        $canEditClient = auth()->user()->can('technician.client_edit') && $isAssignedZone;
        $canDeviceControl = auth()->user()->can('technician.device_control') && $isAssignedZone;

        $clientLabel = $client->name . ' (' . $client->username . ')';
        $assignedProducts = \App\Models\Instrument::where('status', 'in_use')
            ->where(function ($q) use ($client, $clientLabel) {
                $q->where('assigned_to', (string) $client->id)->orWhere('assigned_to', $clientLabel);
            })->orderBy('updated_at', 'desc')->get();

        $outLogs = \DB::table('instrument_out_logs')
            ->leftJoin('users as creator', 'instrument_out_logs.created_by', '=', 'creator.id')
            ->leftJoin('users as tech', 'instrument_out_logs.technician_id', '=', 'tech.id')
            ->where(function ($q) use ($client, $clientLabel) {
                $q->where('instrument_out_logs.issued_to', (string) $client->id)->orWhere('instrument_out_logs.issued_to', $clientLabel);
            })
            ->select('instrument_out_logs.*', 'creator.name as assigned_by_name', 'tech.name as technician_name')
            ->orderBy('out_date', 'desc')->orderBy('instrument_out_logs.created_at', 'desc')->get();

        $assignedByMap = [];
        $installedByMap = [];
        foreach ($outLogs as $log) {
            if ($log->instrument_ids) {
                $ids = json_decode($log->instrument_ids, true) ?? [];
                foreach ($ids as $id) {
                    if ($log->assigned_by_name) $assignedByMap[$id] = $log->assigned_by_name;
                    if ($log->technician_name) $installedByMap[$id] = $log->technician_name;
                }
            }
        }

        $fiberUsages = \App\Models\FiberUsage::with(['fiberStock', 'technician'])
            ->where('client_id', $client->id)->orderBy('used_date', 'desc')->get();

        $fiberOutLogs = \DB::table('fiber_histories')
            ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
            ->where('fiber_histories.type', 'fiber_out')
            ->where(function ($q) use ($client) {
                $q->where('fiber_histories.details', 'LIKE', '%PPPoE: ' . $client->username . '%')
                  ->orWhere('fiber_histories.details', 'LIKE', '%client: ' . $client->name . '%');
            })
            ->select('fiber_histories.*', 'fiber_stocks.fiber_code', 'fiber_stocks.fiber_type', 'fiber_stocks.core')
            ->orderBy('fiber_histories.date', 'desc')->get();

        foreach ($fiberOutLogs as $fLog) {
            $fLog->technician_name = null;
            $fLog->assigned_by_name = null;
            if ($fLog->details) {
                if (preg_match('/Technician:\s*([^|]+)/', $fLog->details, $m)) $fLog->technician_name = trim($m[1]);
                if (preg_match('/Assigned By:\s*([^|]+)/', $fLog->details, $m)) $fLog->assigned_by_name = trim($m[1]);
            }
        }

        $onuRecord = null;
        $oltDevice = null;
        if ($client->olt_device_id) {
            $oltDevice = \App\Models\OltDevice::find($client->olt_device_id);
        }
        if ($client->onu_serial || $client->onu_mac) {
            $onuRecord = \DB::table('onus')->where(function ($q) use ($client) {
                if ($client->onu_serial) $q->orWhere('serial_number', $client->onu_serial);
                if ($client->onu_mac) $q->orWhere('mac_address', $client->onu_mac);
                $q->orWhere('client_id', $client->id);
            })->first();
        }
        if (!$onuRecord && $client->id) {
            $onuRecord = \DB::table('onus')->where('client_id', $client->id)->first();
        }

        $deadlineReasons = \App\Models\DropdownOption::getOptions('deadline_extend_reasons');
        $addPaymentMethods = auth()->user()->allowedPaymentMethods();
        $paymentReasons = \App\Models\DropdownOption::getOptions('payment_reasons');

        $user = auth()->user();
        $canActivateClient   = $user->can('technician.activate_client') && $isAssignedZone;
        $canCollectPayment   = $user->can('technician.collection')      && $isAssignedZone;
        $canGenerateBill     = $user->can('technician.generate_bill')   && $isAssignedZone;
        $canPayInstallation  = $user->can('technician.pay_installation') && $isAssignedZone;
        $canExtendDeadline   = $user->can('technician.extend_deadline') && $isAssignedZone;
        $canSendSms          = $user->can('technician.send_sms')        && $isAssignedZone;

        return view('technician.client-show', compact(
            'client', 'canEditClient', 'canDeviceControl', 'canActivateClient', 'isAssignedZone',
            'assignedProducts', 'outLogs', 'fiberUsages', 'fiberOutLogs', 'assignedByMap', 'installedByMap',
            'onuRecord', 'oltDevice', 'deadlineReasons', 'addPaymentMethods', 'paymentReasons',
            'canCollectPayment', 'canGenerateBill', 'canPayInstallation', 'canExtendDeadline', 'canSendSms'
        ));
    }

    public function payInstallationCharge(Request $request, Client $client)
    {
        $this->authorizeClientZone($client);

        $payMethod = \App\Models\PaymentMethod::find($request->payment_method_id);
        $isCash = $payMethod && stripos($payMethod->name, 'cash') !== false;

        $request->validate([
            'amount'            => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'money_receipt_no'  => 'nullable|string|max:100',
            'transaction_id'    => $isCash ? 'nullable|string|max:100' : 'required|string|max:100',
            'sender_number'     => $isCash ? 'nullable|string|max:50'  : 'required|string|max:50',
            'notes_reason'      => 'nullable|string|max:255',
            'notes'             => 'nullable|string|max:500',
        ]);

        $installCharge = (float) $client->installation_charge ?: (float) $client->signup_fee ?: 0;
        if ($installCharge <= 0) return back()->with('warning', 'No installation charge to pay.');

        $totalPaid = (float) \App\Models\ClientServiceCharge::where('client_id', $client->id)
            ->where('charge_type', 'Installation Charge')->whereNotNull('paid_by')->where('amount', '>', 0)->sum('amount');

        $remaining = $installCharge - $totalPaid;
        if ($remaining <= 0) return back()->with('warning', 'Installation charge is already fully paid.');

        $amount = (float) $request->amount;
        $note   = trim(($request->notes_reason ? $request->notes_reason . '. ' : '') . ($request->notes ?: ''));

        \DB::beginTransaction();
        try {
            \App\Models\ClientServiceCharge::create([
                'client_id'         => $client->id,
                'charge_type'       => 'Installation Charge',
                'amount'            => $amount,
                'description'       => $note ?: 'Installation charge payment',
                'payment_method'    => $payMethod->name ?? 'Cash',
                'payment_method_id' => $request->payment_method_id,
                'money_receipt_no'  => $request->money_receipt_no,
                'transaction_id'    => $request->transaction_id,
                'sender_number'     => $request->sender_number,
                'paid_by'           => auth()->id(),
                'charge_date'       => now()->toDateString(),
            ]);
            $newTotal = $totalPaid + $amount;
            if ($newTotal >= $installCharge) $client->update(['signup_fee_paid' => true]);

            if ($request->send_sms && $client->phone) {
                try { app(\App\Services\SmsService::class)->sendPaymentConfirmation($client, $amount, $request->money_receipt_no ?: 'IC-' . $client->client_id); } catch (\Exception $e) {}
            }
            \DB::commit();
            $msg = 'Installation charge payment of ৳' . number_format($amount) . ' recorded.';
            if ($newTotal >= $installCharge) $msg .= ' Fully paid!';
            else $msg .= ' Remaining: ৳' . number_format($installCharge - $newTotal) . '.';
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function applyTempDeadline(Request $request, Client $client)
    {
        $this->authorizeClientZone($client, $request->ajax());

        $request->validate([
            'deadline_date' => 'required|date|after_or_equal:today',
            'reason'        => 'required|string|max:255',
        ]);
        try {
            $result = app(\App\Services\BillingService::class)->applyTemporaryDeadline(
                $client, $request->deadline_date, $request->reason, auth()->id()
            );
            if ($request->ajax()) {
                $client->refresh();
                return response()->json(['success' => true, 'message' => $result['message'], 'new_status' => $client->status]);
            }
            return back()->with('success', $result['message']);
        } catch (\Exception $e) {
            if ($request->ajax()) return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function clearTempDeadline(Request $request, Client $client)
    {
        $this->authorizeClientZone($client, $request->ajax());

        try {
            $billingService = app(\App\Services\BillingService::class);
            $billingService->clearTemporaryDeadline($client, auth()->id());
            if ($request->ajax()) return response()->json(['success' => true, 'message' => 'Deadline cleared.']);
            return back()->with('success', 'Deadline cleared.');
        } catch (\Exception $e) {
            if ($request->ajax()) return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function sendSms(Request $request, Client $client)
    {
        $this->authorizeClientZone($client);

        $request->validate(['message' => 'required|string|max:1000']);
        if (!$client->phone) return back()->with('warning', 'Client has no phone number.');
        try {
            app(\App\Services\SmsService::class)->sendSms($client->phone, $request->message);
            return back()->with('success', 'SMS sent to ' . $client->phone . '.');
        } catch (\Exception $e) {
            return back()->with('error', 'SMS failed: ' . $e->getMessage());
        }
    }

    public function paymentHistory(Client $client)
    {
        $this->authorizeClientZone($client);
        $isAssignedZone = true;
        
        $bills = $client->bills()
            ->with(['payments.paymentMethod', 'payments.collectedBy'])
            ->orderBy('bill_date', 'desc')
            ->get();
        
        $totalPaid = $client->payments()->sum('amount');
        $totalBills = $client->bills()->sum('total_amount');
        
        return view('technician.payment-history', compact('client', 'bills', 'totalPaid', 'totalBills', 'isAssignedZone'));
    }

    public function additionalPayments(Client $client)
    {
        $payments = $client->additionalPayments()
            ->with(['paymentMethod', 'collector'])
            ->orderBy('payment_date', 'desc')
            ->get();
        
        return view('technician.additional-payments', compact('client', 'payments'));
    }

    public function dueClients(Request $request)
    {
        session(['last_client_list_url' => url()->current()]);
        
        $zoneIds = $this->getTechnicianZones();
        $isSearching = !empty($request->search);
        
        $query = Client::with(['zone', 'area', 'package'])
            ->where('due_amount', '>', 0);
        
        if (!empty($zoneIds)) {
            $query->whereIn('zone_id', $zoneIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($isSearching) {
            $query->where(function($q) use ($request) {
                $search = $request->search;
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('client_id', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhere('address', 'ilike', "%{$search}%")
                  ->orWhereHas('zone', function($sq) use ($search) {
                      $sq->where('name', 'ilike', "%{$search}%");
                  })
                  ->orWhereHas('area', function($sq) use ($search) {
                      $sq->where('name', 'ilike', "%{$search}%");
                  })
                  ->orWhereHas('box', function($sq) use ($search) {
                      $sq->where('name', 'ilike', "%{$search}%");
                  });
            });
        }
        
        if ($request->zone_id) {
            $query->where('zone_id', $request->zone_id);
        }
        
        $clients = $query->orderBy('due_amount', 'desc')->paginate(20);
        
        $zones = !empty($zoneIds) ? Zone::withoutGlobalScopes()->whereIn('id', $zoneIds)->get() : collect();

        // Zone-wise stats for dashboard
        $zoneStats = collect();
        foreach ($zones as $zone) {
            $zoneClients = Client::where('zone_id', $zone->id);
            $zoneStats->push([
                'name'          => $zone->name,
                'total'         => (clone $zoneClients)->count(),
                'active'        => (clone $zoneClients)->where('status', 'active')->count(),
                'inactive'      => (clone $zoneClients)->where('status', 'inactive')->count(),
                'monthly_bill'  => (clone $zoneClients)->sum('monthly_fee'),
            ]);
        }
        
        $totalDue = !empty($zoneIds) ? Client::where('due_amount', '>', 0)->whereIn('zone_id', $zoneIds)->sum('due_amount') : 0;
        $dueCount = !empty($zoneIds) ? Client::where('due_amount', '>', 0)->whereIn('zone_id', $zoneIds)->count() : 0;
        
        return view('technician.due-clients', compact('clients', 'zones', 'totalDue', 'dueCount', 'isSearching'));
    }

    public function dueReport(Request $request)
    {
        $zoneIds = $this->getTechnicianZones();
        
        $query = Client::with(['zone', 'package'])
            ->where('due_amount', '>', 0);
        
        if (!empty($zoneIds)) {
            $query->whereIn('zone_id', $zoneIds);
        } else {
            $query->whereRaw('1 = 0');
        }
        
        if ($request->zone_id && in_array($request->zone_id, $zoneIds)) {
            $query->where('zone_id', $request->zone_id);
        }
        
        $clients = $query->orderBy('due_amount', 'desc')->paginate(20);
        $zones = !empty($zoneIds) ? Zone::whereIn('id', $zoneIds)->get() : collect();
        
        $totalDue = !empty($zoneIds) ? Client::whereIn('zone_id', $zoneIds)->sum('due_amount') : 0;
        
        return view('technician.due-report', compact('clients', 'zones', 'totalDue'));
    }

    public function collectionReport(Request $request)
    {
        $startDate = $request->start_date ?? today()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? today()->format('Y-m-d');
        
        $payments = Payment::with(['client', 'client.zone'])
            ->where('collected_by', Auth::id())
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->paginate(20);
        
        $totalCollection = Payment::where('collected_by', Auth::id())
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
        
        return view('technician.collection-report', compact('payments', 'totalCollection', 'startDate', 'endDate'));
    }

    public function tickets()
    {
        $assignedTicketIds = \App\Models\TechnicianAssignment::where('technician_id', Auth::id())
            ->pluck('ticket_id')->toArray();
        
        $tickets = Ticket::with(['client', 'client.zone'])
            ->where(function($q) use ($assignedTicketIds) {
                $q->where('technician_id', Auth::id())
                  ->orWhereIn('id', $assignedTicketIds);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $openTickets = Ticket::where(function($q) use ($assignedTicketIds) {
            $q->where('technician_id', Auth::id())->orWhereIn('id', $assignedTicketIds);
        })->where('status', 'open')->count();
        
        $inProgressTickets = Ticket::where(function($q) use ($assignedTicketIds) {
            $q->where('technician_id', Auth::id())->orWhereIn('id', $assignedTicketIds);
        })->where('status', 'in_progress')->count();
        
        $resolvedTickets = Ticket::where(function($q) use ($assignedTicketIds) {
            $q->where('technician_id', Auth::id())->orWhereIn('id', $assignedTicketIds);
        })->where('status', 'resolved')->count();

        $zoneIds = $this->getTechnicianZones();
        $clients = Client::whereIn('status', ['active', 'inactive'])
            ->when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->orderBy('name')->get();
        $categories = \App\Models\TicketCategory::where('is_active', true)->get();
        
        // Get technicians - safely handle missing roles
        $technicianRoles = ['technician'];
        $roleModel = \Spatie\Permission\Models\Role::class;
        if ($roleModel::where('name', 'employee')->where('guard_name', 'web')->exists()) {
            $technicianRoles[] = 'employee';
        }
        if ($roleModel::where('name', 'Support Staff')->where('guard_name', 'web')->exists()) {
            $technicianRoles[] = 'Support Staff';
        }
        $technicians = \App\Models\User::role($technicianRoles)->get();
        
        $packages = \App\Models\Package::where('is_active', true)->orderBy('name')->get();
        
        return view('technician.tickets', compact('tickets', 'openTickets', 'inProgressTickets', 'resolvedTickets', 'clients', 'categories', 'technicians', 'packages'));
    }

    public function createTicket(Request $request)
    {
        $category = \App\Models\TicketCategory::find($request->category_id);
        $isNewConnection = $category && (
            stripos($category->name, 'new connection') !== false || 
            stripos($category->name, 'installation') !== false
        );

        if ($isNewConnection) {
            $validated = $request->validate([
                'category_id' => 'required|exists:ticket_categories,id',
                'priority' => 'required|in:low,medium,high,urgent',
                'technician_ids' => 'required|array|min:1',
                'technician_ids.*' => 'exists:users,id',
                'new_customer_name' => 'nullable|string|max:255',
                'new_customer_phone' => 'nullable|string|max:20',
                'new_customer_address' => 'nullable|string',
                'package_id' => 'nullable|exists:packages,id',
                'installation_charge' => 'nullable|string|max:100',
                'connection_time' => 'nullable|date',
            ]);
        } else {
            $validated = $request->validate([
                'client_id' => 'required|exists:clients,id',
                'category_id' => 'required|exists:ticket_categories,id',
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'technician_ids' => 'required|array|min:1',
                'technician_ids.*' => 'exists:users,id',
            ]);
        }

        if ($isNewConnection) {
            $packageName = null;
            if (!empty($validated['package_id'])) {
                $package = \App\Models\Package::find($validated['package_id']);
                $packageName = $package ? $package->name : null;
            }
            
            $metadata = [
                'customer_name' => $validated['new_customer_name'] ?? null,
                'customer_phone' => $validated['new_customer_phone'] ?? null,
                'customer_address' => $validated['new_customer_address'] ?? null,
                'package_id' => $validated['package_id'] ?? null,
                'package' => $packageName,
                'installation_charge' => $validated['installation_charge'] ?? null,
                'connection_time' => $validated['connection_time'] ?? null,
            ];
            
            $ticket = Ticket::create([
                'client_id' => null,
                'category_id' => $validated['category_id'],
                'subject' => 'New Connection',
                'description' => 'New connection request for: ' . ($validated['new_customer_name'] ?? 'N/A'),
                'priority' => $validated['priority'],
                'status' => 'open',
                'metadata' => json_encode($metadata),
            ]);
            
            $clientName = $validated['new_customer_name'] ?? 'N/A';
            $clientPhone = $validated['new_customer_phone'] ?? 'N/A';
            $clientAddress = $validated['new_customer_address'] ?? 'N/A';
            $clientId = null;
        } else {
            $ticket = Ticket::create([
                'client_id' => $validated['client_id'],
                'category_id' => $validated['category_id'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'status' => 'open',
            ]);
            
            $client = Client::find($validated['client_id']);
            $clientName = $client->name ?? 'N/A';
            $clientPhone = $client->phone ?? 'N/A';
            $clientAddress = $client->address ?? 'N/A';
            $clientId = $client->client_id ?? 'N/A';
        }

        $ticket->setSlaDeadline();

        $technicianIds = $validated['technician_ids'];
        foreach ($technicianIds as $techId) {
            \App\Models\TechnicianAssignment::create([
                'ticket_id' => $ticket->id,
                'technician_id' => $techId,
                'assigned_at' => now(),
                'status' => 'assigned',
            ]);
        }

        $ticket->technician_id = $technicianIds[0];
        $ticket->status = 'in_progress';
        $ticket->save();

        $packageName = null;
        if (!empty($validated['package_id'])) {
            $package = \App\Models\Package::find($validated['package_id']);
            $packageName = $package ? $package->name . ' - ৳' . number_format($package->price) : null;
        }

        $ticketData = [
            'ticket_no' => $ticket->ticket_no,
            'client_name' => $clientName,
            'user_id' => $clientId,
            'pppoe_user' => isset($client) ? ($client->username ?? '') : '',
            'subject' => $isNewConnection ? 'New Connection' : $validated['subject'],
            'description' => $isNewConnection ? '' : ($validated['description'] ?? ''),
            'address' => $clientAddress,
            'phone' => $clientPhone,
            'priority' => $validated['priority'],
            'opened_by' => auth()->user()->name ?? 'System',
            'installation_charge' => $validated['installation_charge'] ?? 0,
            'package' => $packageName,
            'connection_time' => !empty($validated['connection_time']) 
                ? \Carbon\Carbon::parse($validated['connection_time'])->timezone('Asia/Dhaka')->format('d-m-y h:i A') 
                : null,
        ];

        $notificationService = app(\App\Services\NotificationService::class);
        if ($isNewConnection) {
            $notificationService->sendNewConnectionTicketNotification($technicianIds, $ticketData);
        } else {
            $notificationService->sendMultiTechnicianTicketNotification($technicianIds, $ticketData);
        }

        return redirect()->back()->with('success', 'Ticket created successfully.');
    }

    public function viewTicket(Ticket $ticket)
    {
        $assignment = \App\Models\TechnicianAssignment::where('ticket_id', $ticket->id)
            ->where('technician_id', Auth::id())
            ->first();
        
        if (!$assignment && $ticket->technician_id !== Auth::id()) {
            return redirect()->route('technician.tickets')->with('error', 'Access denied.');
        }
        
        $ticket->load(['client', 'client.zone', 'client.package', 'category', 'messages.user']);
        
        $assignments = \App\Models\TechnicianAssignment::with('technician')
            ->where('ticket_id', $ticket->id)
            ->get();
        
        $ticketMetadata = json_decode($ticket->metadata ?? '{}', true);
        
        return view('technician.ticket-view', compact('ticket', 'assignments', 'ticketMetadata'));
    }
    
    public function updateTicketStatus(Request $request, Ticket $ticket)
    {
        $assignment = \App\Models\TechnicianAssignment::where('ticket_id', $ticket->id)
            ->where('technician_id', Auth::id())
            ->first();
        
        if (!$assignment && $ticket->technician_id !== Auth::id()) {
            return redirect()->route('technician.tickets')->with('error', 'Access denied.');
        }
        
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed'
        ]);
        
        $ticket->status = $request->status;
        if ($request->status === 'resolved') {
            $ticket->resolved_at = now();
        }
        if ($request->status === 'closed') {
            $ticket->closed_at = now();
        }
        $ticket->save();
        
        if ($assignment) {
            $assignment->status = $request->status === 'resolved' ? 'completed' : $request->status;
            if ($request->status === 'resolved') {
                $assignment->completed_at = now();
            }
            $assignment->save();
        }
        
        return back()->with('success', 'Ticket status updated to ' . ucfirst(str_replace('_', ' ', $request->status)));
    }
    
    public function addTicketNote(Request $request, Ticket $ticket)
    {
        $assignment = \App\Models\TechnicianAssignment::where('ticket_id', $ticket->id)
            ->where('technician_id', Auth::id())
            ->first();
        
        if (!$assignment && $ticket->technician_id !== Auth::id()) {
            return redirect()->route('technician.tickets')->with('error', 'Access denied.');
        }
        
        $request->validate([
            'message' => 'required|string'
        ]);
        
        \App\Models\TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'is_staff_reply' => true
        ]);
        
        return back()->with('success', 'Note added successfully.');
    }

    public function collectPayment(Request $request, Client $client)
    {
        $this->authorizeClientZone($client);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'bill_id' => 'nullable|exists:bills,id',
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Get the bill - if not provided, auto-select oldest unpaid bill (Quick Pay mode)
        $bill = null;
        if ($request->bill_id) {
            $bill = \App\Models\Bill::where('id', $request->bill_id)
                ->where('client_id', $client->id)
                ->firstOrFail();
        } else {
            $bill = \App\Models\Bill::where('client_id', $client->id)
                ->where('is_cancelled', false)
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('billing_month', 'asc')
                ->first();
        }

        // Combine notes from reason and details
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
                    'reason' => $request->filled('notes_reason') ? $request->notes_reason : null,
                    'bill_id' => $bill ? $bill->id : null,
                    'transaction_id' => $request->transaction_id,
                    'money_receipt_no' => $request->money_receipt_no ?: null,
                ], Auth::id(), 'technician');

                app(LedgerService::class)->createPaymentEntry($payment, Auth::id(), 'technician');

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

            // Dispatch SMS to background queue — do NOT block the HTTP response
            $techSmsClientId  = $client->id;
            $techSmsAmount    = (float) $request->amount;
            $techSmsPaymentNo = $payment->payment_no;
            $techSmsSend      = $request->boolean('send_sms', true);
            dispatch(function () use ($techSmsClientId, $techSmsAmount, $techSmsPaymentNo, $techSmsSend) {
                try {
                    $cl = \App\Models\Client::find($techSmsClientId);
                    if ($cl) {
                        app(\App\Services\SmsService::class)->sendPaymentConfirmation($cl, $techSmsAmount, $techSmsPaymentNo, $techSmsSend);
                    }
                } catch (\Exception $ex) {
                    \Illuminate\Support\Facades\Log::warning("Async technician payment SMS failed for client {$techSmsClientId}: " . $ex->getMessage());
                }
            })->onQueue('default');

            $dateTime = now()->format('d-m-y h:i:s A') . ' (BDT GMT+6)';
            
            // Return JSON for AJAX requests (instant receipt)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment of ৳' . number_format($request->amount) . ' collected successfully',
                    'payment_id' => $payment->id,
                    'payment_no' => $payment->payment_no,
                    'receipt_url' => route('technician.payments.receipt', $payment),
                    'print_url' => route('technician.payments.receipt.print', $payment),
                    'thermal_url' => route('technician.payments.receipt.thermal', $payment),
                    'download_url' => route('technician.payments.receipt.download', $payment),
                ]);
            }
            
            $successMsg = 'Payment of ৳' . number_format($request->amount) . ' collected successfully on ' . $dateTime;
            
            $redirectTo = $request->input('redirect_to');
            if ($redirectTo && !empty($redirectTo) && $this->isSafeRedirect($redirectTo)) {
                return redirect()->to($redirectTo)->with('success', $successMsg);
            }
            
            return redirect()->to(route('technician.clients'))->with('success', $successMsg);
        } catch (\Exception $e) {
            Log::error('Technician payment collection failed: ' . $e->getMessage(), [
                'client_id' => $client->id,
                'amount' => $request->amount,
                'technician_id' => Auth::id(),
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error processing payment. Please try again.',
                ], 422);
            }
            
            return back()->with('error', 'Error processing payment. Please try again.')->withInput();
        }
    }
    
    public function liveStats()
    {
        $zoneIds = $this->getTechnicianZones();
        $user = Auth::user();
        
        $todayCollection = Payment::whereDate('created_at', today())
            ->where('collected_by', $user->id)
            ->sum('amount');
        
        $todayPaymentsCount = Payment::whereDate('created_at', today())
            ->where('collected_by', $user->id)
            ->count();
        
        $activeClients = Client::where('status', 'active')
            ->when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->count();
        
        $inactiveClients = Client::where('status', 'inactive')
            ->when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->count();
        
        $pendingTickets = Ticket::where('technician_id', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
        
        $staffDue = StaffDue::where('user_id', $user->id)->first();
        $myDueToAdmin = $staffDue ? $staffDue->current_due : 0;
        
        $recentPayments = Payment::with(['client'])
            ->where('collected_by', $user->id)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function($payment) {
                return [
                    'client_name' => $payment->client->name ?? 'N/A',
                    'client_id' => $payment->client->client_id ?? 'N/A',
                    'amount' => $payment->amount,
                    'amount_formatted' => '৳' . number_format($payment->amount, 0),
                    'method' => ucfirst($payment->payment_method ?? 'Cash'),
                    'time' => $payment->created_at->format('h:i A'),
                    'date' => $payment->created_at->format('d-m-Y'),
                    'submitted_by' => $payment->collector_name ?? $user->name,
                    'submitted_at' => $payment->submitted_at ? $payment->submitted_at->format('d-m-y h:i:s A') : null,
                ];
            });
        
        $onlineClients = Client::where('connection_status', 'online')
            ->when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->count();
        
        $offlineClients = Client::where('connection_status', 'offline')
            ->where('status', 'active')
            ->when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->count();
        
        $alarmPriorityList = $this->getAlarmPriorityList($zoneIds);
        $areaOutages = $this->getAreaOutages($zoneIds);
        $assignedJobs = $this->getAssignedJobs($user->id);
        
        return response()->json([
            'today_collection' => $todayCollection,
            'today_collection_formatted' => '৳' . number_format($todayCollection, 0),
            'today_payments_count' => $todayPaymentsCount,
            'active_clients' => $activeClients,
            'inactive_clients' => $inactiveClients,
            'online_clients' => $onlineClients,
            'offline_clients' => $offlineClients,
            'pending_tickets' => $pendingTickets,
            'my_due_to_admin' => $myDueToAdmin,
            'my_due_formatted' => '৳' . number_format($myDueToAdmin, 0),
            'recent_payments' => $recentPayments,
            'alarm_priority_list' => $alarmPriorityList,
            'area_outages' => $areaOutages,
            'assigned_jobs' => $assignedJobs,
            'timestamp' => now()->format('h:i:s A'),
            'server_time' => now()->format('d-m-y h:i:s A') . ' (BDT)',
        ]);
    }
    
    protected function getAlarmPriorityList(array $zoneIds): array
    {
        $alarms = \DB::table('onu_alarm_logs')
            ->join('onus', 'onu_alarm_logs.onu_id', '=', 'onus.id')
            ->join('clients', 'onus.client_id', '=', 'clients.id')
            ->where('onu_alarm_logs.is_resolved', false)
            ->when(!empty($zoneIds), fn($q) => $q->whereIn('clients.zone_id', $zoneIds))
            ->select('onu_alarm_logs.*', 'clients.name as client_name', 'clients.client_id as client_code', 
                     'clients.address', 'clients.phone', 'onus.serial_number')
            ->orderByRaw("CASE WHEN alarm_type IN ('LOS', 'LOF') THEN 0 ELSE 1 END")
            ->orderByDesc('onu_alarm_logs.created_at')
            ->limit(20)
            ->get()
            ->map(function($alarm) {
                return [
                    'id' => $alarm->id,
                    'client_name' => $alarm->client_name,
                    'client_code' => $alarm->client_code,
                    'address' => $alarm->address,
                    'phone' => $alarm->phone,
                    'serial_number' => $alarm->serial_number,
                    'alarm_type' => $alarm->alarm_type,
                    'severity' => $alarm->severity,
                    'rx_power' => $alarm->rx_power,
                    'is_los' => in_array($alarm->alarm_type, ['LOS', 'LOF']),
                    'time_ago' => \Carbon\Carbon::parse($alarm->created_at)->diffForHumans(),
                ];
            })
            ->toArray();
        
        return $alarms;
    }
    
    protected function getAreaOutages(array $zoneIds): array
    {
        return \DB::table('network_event_logs')
            ->where('event_type', 'area_outage')
            ->where('is_resolved', false)
            ->when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function($outage) {
                $zone = \DB::table('zones')->find($outage->zone_id);
                return [
                    'id' => $outage->id,
                    'zone_name' => $zone->name ?? 'Unknown Zone',
                    'affected_count' => $outage->affected_count,
                    'description' => $outage->description,
                    'since' => \Carbon\Carbon::parse($outage->created_at)->diffForHumans(),
                    'severity' => $outage->severity,
                ];
            })
            ->toArray();
    }
    
    protected function getAssignedJobs(int $userId): array
    {
        return Ticket::with(['client:id,name,client_id,address,phone'])
            ->where('technician_id', $userId)
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 WHEN priority = 'high' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(function($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'client_name' => $ticket->client->name ?? 'N/A',
                    'client_address' => $ticket->client->address ?? 'N/A',
                    'client_phone' => $ticket->client->phone ?? 'N/A',
                    'created_ago' => $ticket->created_at->diffForHumans(),
                    'sla_status' => $this->getTicketSlaStatus($ticket),
                ];
            })
            ->toArray();
    }
    
    protected function getTicketSlaStatus($ticket): array
    {
        if (!$ticket->sla_resolution_due_at) {
            return ['status' => 'unknown', 'remaining' => null];
        }
        
        $remaining = now()->diffInMinutes($ticket->sla_resolution_due_at, false);
        
        if ($remaining < 0) {
            return ['status' => 'breached', 'remaining' => abs($remaining) . 'm overdue', 'color' => 'danger'];
        } elseif ($remaining < 60) {
            return ['status' => 'urgent', 'remaining' => $remaining . 'm left', 'color' => 'warning'];
        }
        
        return ['status' => 'ok', 'remaining' => round($remaining / 60, 1) . 'h left', 'color' => 'success'];
    }

    public function profile()
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        $stats = [
            'total_collections' => Payment::where('collected_by', $user->id)->count(),
            'total_amount' => Payment::where('collected_by', $user->id)->sum('amount'),
            'this_month' => Payment::where('collected_by', $user->id)
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),
            'tickets_resolved' => Ticket::where('technician_id', $user->id)
                ->where('status', 'resolved')
                ->count(),
        ];
        
        return view('technician.profile', compact('user', 'employee', 'stats'));
    }
    
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);
        
        $user = Auth::user();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->save();
        
        if ($user->employee) {
            $user->employee->update([
                'address' => $request->address,
            ]);
        }
        
        return back()->with('success', 'Profile updated successfully.');
    }
    
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);
        
        $user = Auth::user();
        
        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
        
        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();
        
        return back()->with('success', 'Password changed successfully.');
    }
    
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);
        
        $user = Auth::user();
        
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/avatars'), $filename);
            
            if ($user->avatar && file_exists(public_path('uploads/avatars/' . $user->avatar))) {
                unlink(public_path('uploads/avatars/' . $user->avatar));
            }
            
            $user->avatar = $filename;
            $user->save();
        }
        
        return back()->with('success', 'Profile picture updated successfully.');
    }
    
    public function rebootOnu(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'reason' => 'nullable|string|max:500',
        ]);
        
        $client = Client::find($request->client_id);
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found'], 404);
        }
        $this->authorizeClientZone($client, true);
        
        $onu = \DB::table('onus')->where('client_id', $client->id)->first();
        if (!$onu) {
            return response()->json(['success' => false, 'message' => 'No ONU linked to this client'], 404);
        }
        
        $onuService = app(\App\Services\OnuManagementService::class);
        $result = $onuService->rebootOnu($onu->id, Auth::id(), $request->reason ?? 'Technician reboot');
        
        return response()->json($result);
    }
    
    public function resolveAlarm(Request $request)
    {
        $request->validate(['alarm_id' => 'required|integer']);
        
        $alarm = \DB::table('onu_alarms')->find($request->alarm_id);
        if ($alarm) {
            $onu = \DB::table('onus')->find($alarm->onu_id ?? 0);
            if ($onu && $onu->client_id) {
                $client = Client::find($onu->client_id);
                if ($client) {
                    $this->authorizeClientZone($client, true);
                }
            }
        }

        $onuService = app(\App\Services\OnuManagementService::class);
        $onuService->resolveOnuAlarm($request->alarm_id);
        
        return response()->json(['success' => true, 'message' => 'Alarm resolved']);
    }
    
    public function updateJobStatus(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:in_progress,resolved',
            'notes' => 'nullable|string',
        ]);
        
        if ($ticket->technician_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Not your assigned job'], 403);
        }
        
        $ticket->update([
            'status' => $request->status,
        ]);
        
        if ($request->status === 'resolved') {
            $slaService = app(\App\Services\TicketSlaService::class);
            $slaService->recordResolution($ticket);
        }
        
        if ($request->notes) {
            $ticket->messages()->create([
                'user_id' => Auth::id(),
                'message' => $request->notes,
            ]);
        }
        
        $alertService = app(\App\Services\RealTimeAlertService::class);
        $alertService->createAlert([
            'alert_type' => 'ticket_status_updated',
            'severity' => 'info',
            'title' => 'Ticket Status Updated',
            'message' => "Ticket #{$ticket->id} status changed to {$request->status}",
            'client_id' => $ticket->client_id,
            'target_role' => 'client',
            'target_user_id' => $ticket->client->user_id ?? null,
        ]);
        
        return response()->json(['success' => true, 'message' => 'Job status updated']);
    }

    public function createClient()
    {
        $zoneIds = $this->getTechnicianZones();
        $zones = \App\Models\Zone::where('is_active', true);
        if (!empty($zoneIds)) {
            $zones = $zones->whereIn('id', $zoneIds);
        }
        $zones = $zones->with('areas')->get();
        $packages = \App\Models\Package::where('is_active', true)->get();
        $availableProfiles = \App\Models\MikrotikProfile::where('is_active', true)
            ->orderBy('name')
            ->pluck('name');
        return view('technician.client-create', compact('zones', 'packages', 'availableProfiles'));
    }

    public function storeClient(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'zone_id' => 'required|exists:zones,id',
            'package_id' => 'required|exists:packages,id',
            'username' => 'required|string|unique:clients,username',
        ]);

        $zoneIds = $this->getTechnicianZones();
        if (empty($zoneIds) || !in_array($request->zone_id, $zoneIds)) {
            return back()->with('error', 'Access denied to this zone.');
        }

        $package = \App\Models\Package::findOrFail($request->package_id);
        
        $client = Client::create([
            // client_id auto-generated by Client model creating hook (withoutGlobalScopes safe)
            'name' => $request->name,
            'phone' => $request->phone,
            'alt_phone' => $request->alt_phone,
            'email' => $request->email,
            'address' => $request->address,
            'zone_id' => $request->zone_id,
            'area_id' => $request->area_id,
            'package_id' => $request->package_id,
            'mikrotik_profile_name' => $request->mikrotik_profile_name,
            'username' => $request->username,
            'pppoe_password' => $request->pppoe_password ?? \Illuminate\Support\Str::random(8),
            'monthly_fee' => $package->price,
            'billing_date' => $request->billing_date ?? 1,
            'connection_date' => now(),
            'status' => 'active',
            'created_by' => auth()->id(),
            'google_map_lat' => $request->google_map_lat,
            'google_map_lng' => $request->google_map_lng,
        ]);

        // Prepaid system: Auto-generate first month bill for new client
        try {
            $billingService = app(\App\Services\BillingService::class);
            $billingService->generateBill($client, now()->month, now()->year);
            \Illuminate\Support\Facades\Log::info("Auto-generated first bill for new client (technician): {$client->name}");
        } catch (\Exception $billError) {
            \Illuminate\Support\Facades\Log::warning("Failed to auto-generate bill for new client {$client->name}: " . $billError->getMessage());
        }

        return redirect()->route('technician.clients.show', $client)->with('success', 'Client created successfully with first month bill (Prepaid).');
    }

    public function editClient(Client $client)
    {
        $this->authorizeClientZone($client);
        $client->load(['zone', 'area', 'package']);
        $packages = \App\Models\Package::where('is_active', true)->get();
        return view('technician.client-edit', compact('client', 'packages'));
    }

    public function updateClient(Request $request, Client $client)
    {
        $this->authorizeClientZone($client);
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'google_map_lat' => 'nullable|numeric|between:-90,90',
            'google_map_lng' => 'nullable|numeric|between:-180,180',
        ]);
        $client->update($request->only(['name', 'phone', 'email', 'address', 'alt_phone', 'remarks', 'google_map_lat', 'google_map_lng']));
        return redirect()->route('technician.clients.show', $client)->with('success', 'Client updated successfully.');
    }

    public function syncClient(Client $client)
    {
        $this->authorizeClientZone($client);
        $deviceService = app(\App\Services\DeviceControlService::class);
        $mikrotikResult = $deviceService->syncClientFromMikrotik($client);
        $oltResult = $deviceService->syncClientFromOlt($client);
        return back()->with($mikrotikResult['success'] || $oltResult['success'] ? 'success' : 'error', 
            $mikrotikResult['success'] || $oltResult['success'] ? 'Client synced successfully.' : 'Sync failed.');
    }

    public function enableClient(Client $client)
    {
        $this->authorizeClientZone($client);
        $oldStatus = $client->status;
        $deviceService = app(\App\Services\DeviceControlService::class);
        $result = $deviceService->enableClient($client);
        if ($result['success']) {
            $client->refresh();
            ClientActivityService::logStatusChange($client, $oldStatus, $client->status, 'Manual enable by technician', 'manual');
            
            $telegram = new TelegramService();
            $telegram->sendClientConnected($client->client_id ?? $client->id, null, 'Online', $client->name ?? '', $client->username ?? '');
        }
        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function disableClient(Request $request, Client $client)
    {
        $this->authorizeClientZone($client);
        $oldStatus = $client->status;
        $reason = $request->input('reason', 'Manual disable by technician');
        $deviceService = app(\App\Services\DeviceControlService::class);
        $result = $deviceService->disableClient($client, $reason);
        if ($result['success']) {
            $client->refresh();
            ClientActivityService::logStatusChange($client, $oldStatus, $client->status, $reason, 'manual');
            
            $telegram = new TelegramService();
            $telegram->sendClientDisconnected($client->client_id ?? $client->id, $reason, null, 'Offline', 'Account disabled', $client->name ?? '', $client->username ?? '');
        }
        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function disconnectClient(Request $request, Client $client)
    {
        $this->authorizeClientZone($client);
        $reason = $request->input('reason', 'Manual disconnect by technician');
        $deviceService = app(\App\Services\DeviceControlService::class);
        $result = $deviceService->disconnectClient($client, $reason);
        if ($result['success']) {
            ClientActivityService::log($client, 'disconnected', $client->connection_status, 'offline', $reason, 'manual');
            
            $telegram = new TelegramService();
            $telegram->sendClientDisconnected($client->client_id ?? $client->id, $reason, null, 'Offline', 'Manual disconnect', $client->name ?? '', $client->username ?? '');
        }
        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function generateClientBill(Request $request, Client $client)
    {
        $this->authorizeClientZone($client);

        $request->validate([
            'billing_month' => 'required|integer|min:1|max:12',
            'billing_year' => 'required|integer|min:2020',
        ]);

        $month = $request->billing_month;
        $year = $request->billing_year;

        try {
            $billingService = app(BillingService::class);
            $bill = $billingService->generateBill($client, $month, $year);
            
            $message = "Bill generated successfully for {$month}/{$year}";
            if ($bill->advance_used > 0) {
                $message .= " (৳" . number_format($bill->advance_used) . " auto-paid from advance)";
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating bill: ' . $e->getMessage());
        }
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

        // Check if there's already a pending submission
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

        return redirect()->route('technician.dashboard')
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

        return view('technician.submission-history', compact('submissions', 'staffDue', 'pendingAmount'));
    }

    // ─── Selfie Attendance ──────────────────────────────────────────────────
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

        return view('technician.attendance', compact('todayCheckin', 'todayCheckout', 'history'));
    }

    public function storeAttendance(Request $request)
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
            ->whereDate('attendance_date', today())
            ->where('type', $request->type)
            ->exists();

        if ($alreadyExists) {
            $label = $request->type === 'check_in' ? 'Check In' : 'Check Out';
            return response()->json(['error' => "You have already done {$label} today."], 422);
        }

        $imageData = $request->selfie;
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $m)) {
            $ext       = $m[1];
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        } else { $ext = 'jpg'; }

        $decoded = base64_decode($imageData);
        if (!$decoded) {
            return response()->json(['error' => 'Invalid selfie image data.'], 422);
        }

        $fileName = 'selfie_' . $user->id . '_' . now()->format('Ymd_His') . '.' . $ext;
        $path     = 'attendances/selfies/' . now()->format('Y/m');
        $fullDir  = storage_path('app/public/' . $path);
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0775, true);
            @chown($fullDir, 'www-data');
        }
        file_put_contents($fullDir . '/' . $fileName, $decoded);

        $employee = Employee::where('user_id', $user->id)->first();

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
        return response()->json([
            'success' => true,
            'message' => "{$typeLabel} recorded at " . now()->format('h:i:s A'),
        ]);
    }

}