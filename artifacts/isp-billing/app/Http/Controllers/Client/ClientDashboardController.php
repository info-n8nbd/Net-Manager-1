<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\MikrotikDevice;
use App\Services\MikroTikService;
use App\Services\OltService;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ClientDashboardController extends Controller
{
    protected function getClient()
    {
        $client = auth()->user()->client;
        if ($client) {
            $client->refresh();
        }
        return $client;
    }

    protected function requireClient()
    {
        $client = $this->getClient();
        if (!$client) {
            abort(response()->view('client.no-profile', [], 403));
        }
        return $client;
    }

    public function index()
    {
        $client = $this->getClient();
        if (!$client) {
            return redirect()->route('login')->with('error', 'Client profile not found.');
        }

        $latestBill = Bill::where('client_id', $client->id)->latest()->first();
        $totalDue = $client->due_amount;
        $recentPayments = Payment::where('client_id', $client->id)->where(function($q){ $q->whereNull('transaction_id')->orWhereRaw("transaction_id NOT LIKE 'DEMO%'"); })->latest()->take(5)->get();
        $openTickets = Ticket::where('client_id', $client->id)->where('status', '!=', 'closed')->count();
        $unpaidBills = Bill::where('client_id', $client->id)->where('is_cancelled', false)->whereIn('status', ['unpaid', 'partial'])->orderBy('billing_year')->orderBy('billing_month')->get();
        $bkashNumber = \App\Models\SystemSetting::get('bkash_merchant_number', '01XXXXXXXXX');
        $nagadNumber = \App\Models\SystemSetting::get('nagad_merchant_number', '01XXXXXXXXX');
        $rocketNumber = \App\Models\SystemSetting::get('rocket_merchant_number', '01XXXXXXXXX');

        $availablePackages = \App\Models\Package::where('is_active', true)
            ->where('company_id', $client->company_id)
            ->where('id', '!=', $client->package_id)
            ->orderBy('price')->get();
        $pendingPkgRequest = \App\Models\PackageChangeRequest::where('client_id', $client->id)
            ->where('status', 'pending')->exists();

        return view('client.dashboard', compact('client', 'latestBill', 'totalDue', 'recentPayments', 'openTickets', 'unpaidBills', 'bkashNumber', 'nagadNumber', 'rocketNumber', 'availablePackages', 'pendingPkgRequest'));
    }

    public function chartData()
    {
        $client = $this->getClient();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        // Payment history for last 12 months
        $monthlyPayments = Payment::where('client_id', $client->id)
            ->where('payment_date', '>=', now()->subMonths(12))
            ->selectRaw('EXTRACT(YEAR FROM payment_date) as year, EXTRACT(MONTH FROM payment_date) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $monthLabels = [];
        $monthAmounts = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthLabels[] = $date->format('M Y');
            $found = $monthlyPayments->first(function($item) use ($date) {
                return $item->year == $date->year && $item->month == $date->month;
            });
            $monthAmounts[] = $found ? (float)$found->total : 0;
        }

        // Bill vs Payment comparison for last 6 months
        $billData = Bill::where('client_id', $client->id)
            ->where('bill_date', '>=', now()->subMonths(6))
            ->selectRaw('EXTRACT(YEAR FROM bill_date) as year, EXTRACT(MONTH FROM bill_date) as month, SUM(total_amount) as total')
            ->groupBy('year', 'month')
            ->get();

        $compLabels = [];
        $billAmounts = [];
        $paidAmounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $compLabels[] = $date->format('M');
            $bill = $billData->first(fn($item) => $item->year == $date->year && $item->month == $date->month);
            $payment = $monthlyPayments->first(fn($item) => $item->year == $date->year && $item->month == $date->month);
            $billAmounts[] = $bill ? (float)$bill->total : 0;
            $paidAmounts[] = $payment ? (float)$payment->total : 0;
        }

        return response()->json([
            'monthly' => ['labels' => $monthLabels, 'data' => $monthAmounts],
            'comparison' => ['labels' => $compLabels, 'bills' => $billAmounts, 'paid' => $paidAmounts],
        ]);
    }

    public function bills()
    {
        $client = $this->requireClient();
        $bills = Bill::where('client_id', $client->id)->orderBy('created_at', 'desc')->paginate(10);
        return view('client.bills', compact('bills'));
    }

    public function downloadInvoice(Bill $bill)
    {
        $client = $this->requireClient();
        if ($bill->client_id !== $client->id) {
            abort(403);
        }
        $bill->load(['client.zone', 'client.package']);
        
        $logo = SystemSetting::get('site_logo', '');
        $companyName = SystemSetting::get('company_name', config('app.name', 'ISP Billing System'));
        $companyAddress = SystemSetting::get('company_address', '');
        $companyPhone = SystemSetting::get('company_phone', '');
        $companyEmail = SystemSetting::get('company_email', '');
        
        $pdf = Pdf::loadView('admin.billing.invoice', compact('bill', 'logo', 'companyName', 'companyAddress', 'companyPhone', 'companyEmail'));
        return $pdf->download("invoice-{$bill->bill_no}.pdf");
    }

    public function payments()
    {
        $client = $this->requireClient();
        $payments = Payment::where('client_id', $client->id)->where(function($q){ $q->whereNull('transaction_id')->orWhereRaw("transaction_id NOT LIKE 'DEMO%'"); })->orderBy('payment_date', 'desc')->paginate(10);
        return view('client.payments', compact('payments'));
    }

    public function speedTest()
    {
        $client = $this->getClient();
        if (!$client) {
            return redirect()->route('login')->with('error', 'Client profile not found.');
        }

        $recentTests = \App\Models\SpeedTestLog::where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('client.portal.speed-test', compact('client', 'recentTests'));
    }

    public function tickets()
    {
        $client = $this->requireClient();
        $tickets = Ticket::where('client_id', $client->id)->orderBy('created_at', 'desc')->paginate(10);
        $categories = TicketCategory::where('is_active', true)->get();
        return view('client.tickets', compact('tickets', 'categories'));
    }

    public function createTicket(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:ticket_categories,id',
        ]);

        $client = $this->requireClient();
        $ticket = Ticket::create([
            'client_id' => $client->id,
            'category_id' => $request->category_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => 'medium',
            'status' => 'open',
        ]);

        try {
            $telegram = new \App\Services\TelegramService();
            $categoryName = '';
            if ($request->category_id) {
                $cat = \App\Models\TicketCategory::find($request->category_id);
                if ($cat) $categoryName = $cat->name;
            }
            $telegram->sendTicketNotification(
                $ticket->ticket_no ?? 'ISP-' . str_pad($ticket->id, 5, '0', STR_PAD_LEFT),
                $request->subject,
                $client->client_id,
                $client->name,
                'medium',
                null,
                $client->address,
                $client->phone,
                $client->name,
                $client->username ?? '',
                $categoryName
            );
        } catch (\Exception $e) {
            \Log::error('Telegram ticket notification failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Ticket submitted successfully.');
    }

    public function profile()
    {
        $client = $this->requireClient();
        $bills = Bill::where('client_id', $client->id)->orderBy('created_at', 'desc')->take(10)->get();
        $payments = Payment::where('client_id', $client->id)->where(function($q){ $q->whereNull('transaction_id')->orWhereRaw("transaction_id NOT LIKE 'DEMO%'"); })->orderBy('payment_date', 'desc')->take(10)->get();
        $tickets = Ticket::where('client_id', $client->id)->orderBy('created_at', 'desc')->take(10)->get();
        return view('client.profile', compact('client', 'bills', 'payments', 'tickets'));
    }

    public function liveStats()
    {
        $client = $this->getClient();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }
        
        $recentPayments = Payment::where('client_id', $client->id)
            ->where(function($q) {
                $q->whereNull('transaction_id')
                  ->orWhereRaw("transaction_id NOT LIKE 'DEMO%'");
            })
            ->orderBy('payment_date', 'desc')
            ->take(5)
            ->get()
            ->map(function($payment) {
                $collector = $payment->collectedBy;
                return [
                    'amount' => $payment->amount,
                    'amount_formatted' => '৳' . number_format($payment->amount, 0),
                    'date' => $payment->payment_date->format('d-m-Y'),
                    'time' => $payment->created_at->format('h:i A'),
                    'month' => $payment->payment_date->format('F'),
                    'year' => $payment->payment_date->format('Y'),
                    'method' => ucfirst($payment->payment_method ?? 'Cash'),
                    'received_by' => $payment->receiver_name ?? ($collector ? $collector->name : 'Office'),
                    'submitted_at' => $payment->submitted_at ? $payment->submitted_at->format('d-m-y h:i:s A') : $payment->created_at->format('d-m-y h:i:s A'),
                ];
            });
        
        $openTickets = Ticket::where('client_id', $client->id)
            ->where('status', '!=', 'closed')
            ->count();
        
        $latestBill = Bill::where('client_id', $client->id)->latest()->first();
        
        $dueDate = $client->due_date ? \Carbon\Carbon::parse($client->due_date) : null;
        $daysRemaining = $dueDate ? now()->diffInDays($dueDate, false) : null;
        
        $speedQuality = $this->calculateSpeedQuality($client);
        
        $areaOutage = $this->checkAreaOutage($client->zone_id);
        
        return response()->json([
            'due_amount' => $client->due_amount,
            'due_formatted' => '৳' . number_format($client->due_amount, 0),
            'connection_status' => $client->connection_status ?? 'offline',
            'status' => $client->status,
            'open_tickets' => $openTickets,
            'recent_payments' => $recentPayments,
            'latest_bill' => $latestBill ? [
                'month' => $latestBill->billing_month . '/' . $latestBill->billing_year,
                'amount' => '৳' . number_format($latestBill->total_amount, 0),
                'status' => $latestBill->status,
            ] : null,
            'due_countdown' => [
                'date' => $dueDate?->format('d-m-Y'),
                'days_remaining' => $daysRemaining,
                'is_overdue' => $daysRemaining !== null && $daysRemaining < 0,
                'urgency' => $daysRemaining !== null ? ($daysRemaining <= 0 ? 'critical' : ($daysRemaining <= 3 ? 'warning' : 'normal')) : 'normal',
            ],
            'speed_quality' => $speedQuality,
            'area_outage' => $areaOutage,
            'usage' => [
                'today' => $this->formatBytes($client->today_usage_bytes ?? 0),
                'month' => $this->formatBytes($client->month_usage_bytes ?? 0),
            ],
            'session' => [
                'uptime' => $this->formatUptime($client->session_uptime_seconds),
                'last_seen' => $client->last_seen_at ? \Carbon\Carbon::parse($client->last_seen_at)->diffForHumans() : 'N/A',
            ],
            'timestamp' => now()->format('h:i:s A'),
            'server_time' => now()->format('d-m-y h:i:s A') . ' (BDT)',
        ]);
    }
    
    protected function calculateSpeedQuality($client): array
    {
        $package = $client->package;
        if (!$package) {
            return ['quality' => 'unknown', 'score' => 0, 'color' => 'secondary'];
        }

        preg_match('/(\d+)\s*(M|Mbps)?/i', $package->speed ?? '0', $matches);
        $expectedSpeed = (float) ($matches[1] ?? 0) * 1000000;
        $currentSpeed = max($client->current_rx_speed ?? 0, $client->current_tx_speed ?? 0);
        
        if ($expectedSpeed <= 0 || $currentSpeed <= 0) {
            return ['quality' => 'unknown', 'score' => 0, 'color' => 'secondary'];
        }

        $ratio = $currentSpeed / $expectedSpeed;
        
        if ($ratio >= 0.9) return ['quality' => 'Excellent', 'score' => 100, 'color' => 'success'];
        if ($ratio >= 0.7) return ['quality' => 'Good', 'score' => 80, 'color' => 'info'];
        if ($ratio >= 0.5) return ['quality' => 'Fair', 'score' => 60, 'color' => 'warning'];
        return ['quality' => 'Poor', 'score' => 30, 'color' => 'danger'];
    }
    
    protected function checkAreaOutage(?int $zoneId): ?array
    {
        if (!$zoneId) return null;
        
        $outage = \DB::table('network_event_logs')
            ->where('event_type', 'area_outage')
            ->where('zone_id', $zoneId)
            ->where('is_resolved', false)
            ->orderByDesc('created_at')
            ->first();
            
        if ($outage) {
            return [
                'active' => true,
                'message' => 'Area outage detected in your zone. Our team is working on it.',
                'since' => \Carbon\Carbon::parse($outage->created_at)->diffForHumans(),
                'affected_count' => $outage->affected_count,
            ];
        }
        
        return ['active' => false];
    }
    
    protected function formatBytes(?int $bytes): string
    {
        if (!$bytes) return '0 B';
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
    
    protected function formatUptime(?int $seconds): string
    {
        if (!$seconds) return 'N/A';
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        if ($days > 0) return "{$days}d {$hours}h {$minutes}m";
        if ($hours > 0) return "{$hours}h {$minutes}m";
        return "{$minutes}m";
    }

    public function liveStatus()
    {
        $client = $this->getClient();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        $data = [
            'online' => false,
            'ip_address' => $client->ip_address ?? 'Dynamic',
            'uptime' => $client->uptime ?? 'N/A',
            'download_speed' => $client->download_speed ?? '0',
            'upload_speed' => $client->upload_speed ?? '0',
            'last_logout' => $client->last_logout ?? 'N/A',
            'onu_signal' => $client->onu_signal,
        ];

        try {
            $mikrotik = MikrotikDevice::where('is_active', true)->first();
            if ($mikrotik && $client->pppoe_username) {
                $api = MikroTikService::fromDevice($mikrotik);
                if ($api->connect()) {
                    $isOnline = $api->isUserOnline($client->pppoe_username);
                    $data['online'] = $isOnline;

                    if ($isOnline) {
                        $userInfo = $api->getUserInfo($client->pppoe_username);
                        if ($userInfo) {
                            $data['ip_address'] = $userInfo['address'] ?? $data['ip_address'];
                            $data['uptime'] = $userInfo['uptime'] ?? $data['uptime'];
                        }
                        $traffic = $api->getUserTraffic($client->pppoe_username);
                        $data['download_speed'] = $traffic['download_speed'] ?? '0';
                        $data['upload_speed'] = $traffic['upload_speed'] ?? '0';
                    }
                    $api->disconnect();
                }
            }

            if ($client->olt && $client->onu_serial) {
                $oltApi = OltService::fromDevice($client->olt);
                if ($oltApi->connect()) {
                    $onuInfo = $oltApi->getOnuInfo($client->onu_serial);
                    if ($onuInfo) {
                        $data['onu_signal'] = $onuInfo['signal'] ?? null;
                        if (isset($onuInfo['status'])) {
                            $data['online'] = $onuInfo['status'] === 'online';
                        }
                        if (isset($onuInfo['last_down'])) {
                            $data['last_logout'] = $onuInfo['last_down'];
                        }
                    }
                    $oltApi->disconnect();
                }
            }
        } catch (\Exception $e) {
            \Log::error('Live status error: ' . $e->getMessage());
        }

        return response()->json($data);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
        ]);

        $client = $this->requireClient();
        $client->update($request->only(['phone', 'alt_phone', 'address']));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = auth()->user();
        
        if (!\Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $user->update(['password' => bcrypt($request->password)]);
        return back()->with('success', 'Password updated successfully.');
    }

    public function showTicket(Ticket $ticket)
    {
        $client = $this->requireClient();
        if ($ticket->client_id !== $client->id) {
            abort(403);
        }
        $ticket->load('messages.user');
        return view('client.ticket-show', compact('ticket'));
    }

    public function replyTicket(Request $request, Ticket $ticket)
    {
        $client = $this->requireClient();
        if ($ticket->client_id !== $client->id) {
            abort(403);
        }
        
        $request->validate(['message' => 'required|string']);
        
        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        return back()->with('success', 'Reply sent successfully.');
    }

    public function rateTicket(Request $request, Ticket $ticket)
    {
        $client = $this->requireClient();
        if ($ticket->client_id !== $client->id) {
            abort(403);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:500'
        ]);

        $ticket->update([
            'satisfaction_rating' => $request->rating,
            'satisfaction_feedback' => $request->feedback
        ]);

        return back()->with('success', 'Thank you for your feedback!');
    }

    public function connection()
    {
        $client = $this->requireClient();
        return view('client.connection', compact('client'));
    }

    public function usage()
    {
        $client = $this->requireClient();
        return view('client.usage', compact('client'));
    }


    public function selfHelp()
    {
        $client = $this->requireClient();
        return view('client.self-help', compact('client'));
    }

    public function payNow()
    {
        $client = $this->requireClient();
        $unpaidBills = Bill::where('client_id', $client->id)
            ->where('status', '!=', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();
        $totalDue = $client->due_amount;
        $recentPayments = Payment::where('client_id', $client->id)
            ->where(function($q) {
                $q->whereNull('transaction_id')
                  ->orWhereRaw("transaction_id NOT LIKE 'DEMO%'");
            })
            ->orderBy('payment_date', 'desc')
            ->take(10)
            ->get();
        
        $bkashNumber = \App\Models\SystemSetting::get('bkash_merchant_number', '01XXXXXXXXX');
        $nagadNumber = \App\Models\SystemSetting::get('nagad_merchant_number', '01XXXXXXXXX');
        $rocketNumber = \App\Models\SystemSetting::get('rocket_merchant_number', '01XXXXXXXXX');

        $paymentService = app(\App\Services\PaymentGatewayService::class);
        $enabledGateways = $paymentService->getEnabledGateways();
        
        return view('client.pay-now', compact('client', 'unpaidBills', 'totalDue', 'recentPayments', 'bkashNumber', 'nagadNumber', 'rocketNumber', 'enabledGateways'));
    }

    public function initiateBkashPayment(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);
        
        $client = $this->requireClient();
        $amount = $request->amount;

        $paymentService = app(\App\Services\PaymentGatewayService::class);
        $result = $paymentService->initiateBkashPaymentWithAmount($client, $amount);

        if ($result['success']) {
            if (isset($result['demo_mode']) && $result['demo_mode']) {
                return redirect($result['redirect_url']);
            }
            if (isset($result['bkashURL'])) {
                return redirect($result['bkashURL']);
            }
        }

        return back()->with('error', $result['message'] ?? 'Failed to initiate bKash payment.');
    }

    public function initiateSslPayment(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);
        
        $client = $this->requireClient();
        $amount = $request->amount;

        $unpaidBill = Bill::where('client_id', $client->id)
            ->where('status', '!=', 'paid')
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$unpaidBill) {
            // Advance payment: create temporary bill object (not saved)
            $unpaidBill = new Bill([
                'client_id' => $client->id,
                'bill_no' => 'ADV-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'total_amount' => $amount,
                'status' => 'unpaid',
                'billing_month' => now()->month,
                'billing_year' => now()->year,
            ]);
            $unpaidBill->id = 0;
        } else {
            $unpaidBill->total_amount = $amount;
        }

        $paymentService = app(\App\Services\PaymentGatewayService::class);
        $result = $paymentService->initiateSslPayment($unpaidBill, $client);

        if ($result['success'] && isset($result['redirect_url'])) {
            return redirect()->away($result['redirect_url']);
        }

        return back()->with('error', $result['message'] ?? 'Failed to initiate SSLCommerz payment.');
    }

    public function initiateNagadPayment(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);
        
        $client = $this->requireClient();
        $amount = $request->amount;

        $unpaidBill = Bill::where('client_id', $client->id)
            ->where('status', '!=', 'paid')
            ->orderBy('created_at', 'asc')
            ->first();

        $billId = $unpaidBill?->id;

        $paymentService = app(\App\Services\PaymentGatewayService::class);
        $result = $paymentService->initiateNagadPayment($client, $amount, $billId);

        if ($result['success'] && isset($result['redirect_url'])) {
            return redirect()->away($result['redirect_url']);
        }

        return back()->with('error', $result['message'] ?? 'Failed to initiate Nagad payment.');
    }

    public function submitPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'transaction_id' => 'required|string|max:50',
            'payment_method' => 'required|in:bkash,nagad,rocket,cash'
        ]);

        $client = $this->requireClient();

        $existingPayment = Payment::where('transaction_id', $request->transaction_id)->first();
        if ($existingPayment) {
            return back()->with('error', 'This transaction ID has already been used.');
        }

        $paymentMethodMap = [
            'bkash' => 2,
            'nagad' => 3,
            'rocket' => 4,
            'cash' => 1,
        ];
        $paymentMethodId = $paymentMethodMap[$request->payment_method] ?? 1;

        $payment = Payment::create([
            'payment_no' => 'PAY-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'client_id' => $client->id,
            'amount' => $request->amount,
            'payment_method_id' => $paymentMethodId,
            'payment_date' => now(),
            'transaction_id' => $request->transaction_id,
            'remarks' => 'Manual payment via ' . ucfirst($request->payment_method) . ' - Pending admin verification',
            'collected_by' => auth()->id(),
            'status' => 'pending',
            'source' => 'client',
            'submitted_to_admin' => false,
        ]);

        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->sendPaymentNotificationToAdmin([
            'client_name' => $client->name,
            'amount' => $request->amount,
            'method' => ucfirst($request->payment_method),
            'transaction_id' => $request->transaction_id,
            'status' => 'Pending Approval'
        ]);

        return back()->with('success', 'Payment of ৳' . number_format($request->amount, 2) . ' submitted successfully! It will be applied after admin verifies your transaction ID.');
    }

    public function notifications()
    {
        $client = $this->requireClient();
        $notifications = \DB::table('notification_queue')
            ->where('recipient_type', 'client')
            ->where('recipient_id', $client->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('client.notifications', compact('notifications'));
    }

    public function settings()
    {
        $client = $this->requireClient();
        return view('client.settings', compact('client'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'notification_sms' => 'boolean',
            'notification_email' => 'boolean',
            'language' => 'in:en,bn'
        ]);

        $client = $this->requireClient();
        $client->update([
            'notification_preferences' => json_encode([
                'sms' => $request->boolean('notification_sms'),
                'email' => $request->boolean('notification_email'),
            ]),
            'language' => $request->language ?? 'en'
        ]);

        return back()->with('success', 'Settings updated successfully.');
    }

    public function help()
    {
        return view('client.help');
    }

    public function serviceHistory()
    {
        $client = $this->requireClient();
        
        $activityLogs = \App\Models\ClientActivityLog::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $reconnections = \App\Models\ClientReconnection::where('client_id', $client->id)
            ->orderBy('reconnect_date', 'desc')
            ->get();

        $lineShifts = \App\Models\ClientLineShift::where('client_id', $client->id)
            ->orderBy('shift_date', 'desc')
            ->get();

        return view('client.service-history', compact('activityLogs', 'reconnections', 'lineShifts'));
    }

    public function selfServiceReconnect(Request $request)
    {
        $client = $this->requireClient();

        if ($client->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your connection is already active.',
            ], 400);
        }

        if ($client->due_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please clear your outstanding dues before requesting reconnection. Due amount: ' . number_format($client->due_amount, 2) . ' BDT',
            ], 400);
        }

        $recentReconnect = \App\Models\ClientReconnection::where('client_id', $client->id)
            ->where('reconnect_date', '>=', now()->subHours(24))
            ->where('reconnection_type', 'self_service')
            ->exists();

        if ($recentReconnect) {
            return response()->json([
                'success' => false,
                'message' => 'A self-service reconnection was already processed in the last 24 hours. Please wait or contact support.',
            ], 400);
        }

        try {
            $connectionService = app(\App\Services\ClientConnectionService::class);
            $result = $connectionService->enableClient($client, 'self_service_reconnect');

            if ($result['success']) {
                \App\Models\ClientReconnection::create([
                    'client_id' => $client->id,
                    'reconnect_date' => now(),
                    'reconnection_type' => 'self_service',
                    'reconnection_fee' => 0,
                    'notes' => 'Self-service reconnection via client portal',
                    'processed_by' => $client->user_id,
                ]);

                \App\Models\ClientActivityLog::log(
                    $client->id,
                    'connection',
                    'self_service_reconnect',
                    'Client initiated self-service reconnection',
                    ['method' => 'client_portal'],
                    auth()->id()
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Your connection has been restored successfully!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to process reconnection. Please contact support.',
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Self-service reconnect error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please contact support.',
            ], 500);
        }
    }

    public function getActivityTimeline()
    {
        $client = $this->requireClient();
        
        $timelineService = app(\App\Services\ClientTimelineService::class);
        $timeline = $timelineService->getClientTimeline($client->id, 30);

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
        ]);
    }

    public function connectionDetails()
    {
        $client = $this->requireClient();
        
        return response()->json([
            'success' => true,
            'connection' => [
                'client_id' => $client->client_id,
                'name' => $client->name,
                'package' => $client->package?->name ?? 'N/A',
                'speed' => ($client->package?->download_speed ?? 0) . ' Mbps / ' . ($client->package?->upload_speed ?? 0) . ' Mbps',
                'monthly_fee' => $client->package?->price ?? $client->monthly_fee,
                'status' => $client->status,
                'connection_type' => $client->connection_type,
                'pppoe_username' => $client->pppoe_username,
                'ip_mode' => $client->ip_mode,
                'connection_date' => $client->connection_date?->format('d-m-Y'),
                'zone' => $client->zone?->name ?? 'N/A',
                'due_amount' => $client->due_amount,
                'balance' => $client->balance,
            ],
        ]);
    }

    public function packageChange()
    {
        $client = $this->requireClient();
        $currentPackage = $client->package;
        $packages = \App\Models\Package::where('is_active', true)
            ->where('id', '!=', $client->package_id)
            ->orderBy('price')
            ->get();
        
        return view('client.package-change', compact('client', 'currentPackage', 'packages'));
    }

    public function submitPackageChange(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $client = $this->requireClient();
        $newPackage = \App\Models\Package::find($request->package_id);

        // Check for existing pending request
        $existingRequest = \App\Models\PackageChangeRequest::where('client_id', $client->id)
            ->where('status', 'pending')
            ->first();
        
        if ($existingRequest) {
            return back()->with('error', 'You already have a pending package change request.');
        }

        // Create package change request
        \App\Models\PackageChangeRequest::create([
            'client_id' => $client->id,
            'current_package_id' => $client->package_id,
            'requested_package_id' => $request->package_id,
            'request_date' => now(),
            'status' => 'pending',
            'client_remarks' => $request->reason,
        ]);

        return back()->with('success', 'Package change request submitted successfully. Our team will review and contact you shortly.');
    }

    public function connectivityTest()
    {
        $client = $this->getClient();
        if (!$client) {
            return redirect()->route('login')->with('error', 'Client profile not found.');
        }
        return view('client.connectivity-test', compact('client'));
    }

    public function runConnectivityTest(Request $request)
    {
        $client = $this->getClient();
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client profile not found']);
        }

        if (!$client->ip_address) {
            return response()->json(['success' => false, 'message' => 'No IP address assigned to your account']);
        }

        try {
            $device = $client->mikrotik_device_id
                ? MikrotikDevice::find($client->mikrotik_device_id)
                : MikrotikDevice::where('is_active', true)->first();

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'No network device configured']);
            }

            $mikrotik = new \App\Services\SimpleMikroTikService();
            if (!$mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))) {
                return response()->json(['success' => false, 'message' => 'Cannot connect to network device']);
            }

            $result = $mikrotik->sendCommand('/ping', [
                'address' => $client->ip_address,
                'count' => '4'
            ]);

            return response()->json(['success' => true, 'data' => $result, 'message' => 'Connectivity test completed']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Test failed: ' . $e->getMessage()]);
        }
    }

    protected function connectToMikrotikForClient($client, int $timeout = 3): ?\App\Services\SimpleMikroTikService
    {
        $mikrotik = new \App\Services\SimpleMikroTikService();
        $triedIds = [];

        if ($client->mikrotik_device_id) {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if ($device && $device->is_active) {
                $triedIds[] = $device->id;
                $host = $device->getRawOriginal('host') ?? $device->host;
                $port = (int)($device->getRawOriginal('port') ?? $device->port ?? 8728);
                if ($mikrotik->connect($host, $device->username, $device->password, $port, $timeout)) {
                    return $mikrotik;
                }
            }
        }

        if ($client->zone_id) {
            $device = MikrotikDevice::where('zone_id', $client->zone_id)->where('is_active', true)->first();
            if ($device && !in_array($device->id, $triedIds)) {
                $triedIds[] = $device->id;
                $host = $device->getRawOriginal('host') ?? $device->host;
                $port = (int)($device->getRawOriginal('port') ?? $device->port ?? 8728);
                if ($mikrotik->connect($host, $device->username, $device->password, $port, $timeout)) {
                    return $mikrotik;
                }
            }
        }

        $allDevices = MikrotikDevice::where('is_active', true)->whereNotIn('id', $triedIds)->get();
        foreach ($allDevices as $device) {
            $host = $device->getRawOriginal('host') ?? $device->host;
            $port = (int)($device->getRawOriginal('port') ?? $device->port ?? 8728);
            if ($mikrotik->connect($host, $device->username, $device->password, $port, $timeout)) {
                return $mikrotik;
            }
        }

        return null;
    }

    public function usageLive()
    {
        $client = $this->getClient();
        if (!$client) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $rxMbps = 0; $txMbps = 0; $rxKbps = 0; $txKbps = 0;
        $uptimeStr = 'N/A';
        $dlGb = 0; $ulGb = 0;

        // --- Try real-time from MikroTik ---
        try {
            $mikrotik = $this->connectToMikrotikForClient($client, 3);
            if ($mikrotik) {
                $username = $client->pppoe_username ?? $client->username;
                $connectionType = strtolower($client->connection_type ?? 'pppoe');

                $rxBytes = 0; $txBytes = 0; $session = null;

                try {
                    if ($connectionType === 'hotspot') {
                        $session = $mikrotik->getHotspotSession($username);
                        if ($session) {
                            $rxBytes = (int)($session['bytes-in']  ?? 0);
                            $txBytes = (int)($session['bytes-out'] ?? 0);
                            $uptimeStr = $session['uptime'] ?? 'N/A';
                        }
                    } else {
                        $session = $mikrotik->getPPPoESession($username);
                        if ($session) {
                            $rxBytes = (int)($session['tx-bytes'] ?? $session['bytes-out'] ?? 0);
                            $txBytes = (int)($session['rx-bytes'] ?? $session['bytes-in']  ?? 0);
                            $uptimeStr = $session['uptime'] ?? 'N/A';
                        }
                    }
                } finally {
                    try { $mikrotik->disconnect(); } catch (\Exception $ex) {}
                }

                if ($session && ($rxBytes > 0 || $txBytes > 0)) {
                    $cacheKey = "client_bw_live_{$client->id}";
                    $now = microtime(true);
                    $prev = cache()->get($cacheKey);
                    cache()->put($cacheKey, ['rx' => $rxBytes, 'tx' => $txBytes, 'time' => $now], now()->addMinutes(2));

                    if ($prev && isset($prev['time'])) {
                        $elapsed = $now - $prev['time'];
                        if ($elapsed >= 0.5) {
                            $rxDelta = max(0, $rxBytes - ($prev['rx'] ?? 0));
                            $txDelta = max(0, $txBytes - ($prev['tx'] ?? 0));
                            $rxBps = ($rxDelta * 8) / $elapsed;
                            $txBps = ($txDelta * 8) / $elapsed;
                            $rxMbps = round($rxBps / 1000000, 2);
                            $txMbps = round($txBps / 1000000, 2);
                            $rxKbps = (int)round($rxBps / 1000);
                            $txKbps = (int)round($txBps / 1000);
                        }
                    }

                    $dlGb = round($rxBytes / 1073741824, 3);
                    $ulGb = round($txBytes / 1073741824, 3);
                }
            }
        } catch (\Exception $e) {
            \Log::debug("Client usageLive MikroTik error for client {$client->id}: " . $e->getMessage());
        }

        // --- Fallback to DB values if MikroTik gave nothing ---
        if ($rxMbps == 0 && $txMbps == 0) {
            $dbRx = $client->current_rx_speed ?? 0;
            $dbTx = $client->current_tx_speed ?? 0;
            $rxMbps = round($dbRx / 1000000, 2);
            $txMbps = round($dbTx / 1000000, 2);
            $rxKbps = (int)round($dbRx / 1000);
            $txKbps = (int)round($dbTx / 1000);
        }
        if ($dlGb == 0) $dlGb = round(($client->download_usage    ?? 0) / 1073741824, 3);
        if ($ulGb == 0) $ulGb = round(($client->upload_usage      ?? 0) / 1073741824, 3);
        if ($uptimeStr === 'N/A') $uptimeStr = $this->formatUptime($client->session_uptime_seconds ?? 0);

        $todayGb = round(($client->today_usage_bytes ?? 0) / 1073741824, 3);
        $monthGb = round(($client->month_usage_bytes ?? 0) / 1073741824, 3);

        return response()->json([
            'rx_mbps'      => $rxMbps,
            'tx_mbps'      => $txMbps,
            'rx_kbps'      => $rxKbps,
            'tx_kbps'      => $txKbps,
            'today_gb'     => $todayGb,
            'month_gb'     => $monthGb,
            'dl_gb'        => $dlGb,
            'ul_gb'        => $ulGb,
            'uptime'       => $uptimeStr,
            'last_updated' => $client->bandwidth_last_updated
                ? \Carbon\Carbon::parse($client->bandwidth_last_updated)->diffForHumans()
                : 'N/A',
            'timestamp'    => now()->format('H:i:s'),
        ]);
    }


    public function mediaServers()
    {
        $client = $this->getClient();
        $servers = \App\Models\MediaServer::when($client && $client->company_id, fn($q) => $q->where("company_id", $client->company_id))
            ->where("is_active", true)
            ->orderBy("sort_order")->orderBy("name")->get();
        return view("client.media-servers", compact("servers"));
    }
}