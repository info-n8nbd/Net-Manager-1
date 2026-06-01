<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MobileApiController extends Controller
{
    /* ───── AUTH ───── */

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->whereIn('user_type', ['super_admin', 'admin', 'manager'])
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email বা password সঠিক নয়।'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account টি inactive।'], 403);
        }

        $token = $user->createToken('mobile-admin')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userArray($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function profile(Request $request)
    {
        return response()->json($this->userArray($request->user()));
    }

    private function userArray(User $user): array
    {
        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'phone'     => $user->phone ?? '',
            'user_type' => $user->user_type,
            'avatar'    => $user->avatar ?? null,
        ];
    }

    /* ───── DASHBOARD ───── */

    public function dashboard(Request $request)
    {
        $today     = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();

        $stats = DB::table('clients')->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_clients,
                SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_clients,
                SUM(CASE WHEN status='inactive' THEN 1 ELSE 0 END) as inactive_clients,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_clients,
                SUM(CASE WHEN status='active' AND connection_status='online' THEN 1 ELSE 0 END) as online_clients,
                SUM(CASE WHEN status='active' AND connection_status!='online' THEN 1 ELSE 0 END) as offline_clients,
                SUM(CASE WHEN due_amount>0 AND status='active' THEN 1 ELSE 0 END) as due_clients,
                COALESCE(SUM(due_amount),0) as total_due_amount
            ")->first();

        $todayCollection   = DB::table('payments')->whereNull('deleted_at')->whereDate('payment_date', $today)->sum('amount');
        $monthlyCollection = DB::table('payments')->whereNull('deleted_at')->where('payment_date', '>=', $thisMonth)->sum('amount');
        $totalResellers    = DB::table('resellers')->whereNull('deleted_at')->where('status', 'active')->count();
        $openTickets       = DB::table('tickets')->whereNull('deleted_at')->whereIn('status', ['open', 'in_progress'])->count();
        $pendingApps       = DB::table('new_connection_applications')->where('status', 'submitted')->count();

        // Last 7 days collection chart
        $chartData = DB::table('payments')
            ->whereNull('deleted_at')
            ->whereDate('payment_date', '>=', now()->subDays(6)->toDateString())
            ->selectRaw("payment_date::date as day, SUM(amount) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $chart[] = [
                'day'   => now()->subDays($i)->format('D'),
                'total' => isset($chartData[$d]) ? (float) $chartData[$d]->total : 0.0,
            ];
        }

        // Recent payments (last 8)
        $recentPayments = DB::table('payments as p')
            ->join('clients as c', 'p.client_id', '=', 'c.id')
            ->leftJoin('payment_methods as pm', 'p.payment_method_id', '=', 'pm.id')
            ->select('p.id', 'c.name as client_name', 'c.phone as client_phone', 'p.amount', 'p.payment_date',
                DB::raw("COALESCE(pm.name,'Cash') as payment_method"))
            ->whereNull('p.deleted_at')
            ->orderByDesc('p.payment_date')->orderByDesc('p.id')
            ->limit(8)->get();

        // Due alerts top 5
        $dueAlerts = DB::table('clients')->whereNull('deleted_at')
            ->where('status', 'active')->where('due_amount', '>', 0)
            ->select('id', 'name', 'phone', 'due_amount')
            ->orderByDesc('due_amount')->limit(5)->get();

        return response()->json([
            'stats' => [
                'total_clients'      => (int)$stats->total_clients,
                'active_clients'     => (int)$stats->active_clients,
                'inactive_clients'   => (int)$stats->inactive_clients,
                'pending_clients'    => (int)$stats->pending_clients,
                'online_clients'     => (int)$stats->online_clients,
                'offline_clients'    => (int)$stats->offline_clients,
                'due_clients'        => (int)$stats->due_clients,
                'total_due_amount'   => (float)$stats->total_due_amount,
                'today_collection'   => (float)$todayCollection,
                'monthly_collection' => (float)$monthlyCollection,
                'total_resellers'    => (int)$totalResellers,
                'open_tickets'       => (int)$openTickets,
                'pending_apps'       => (int)$pendingApps,
            ],
            'chart'          => $chart,
            'recent_payments'=> $recentPayments,
            'due_alerts'     => $dueAlerts,
        ]);
    }

    /* ───── CLIENTS ───── */

    public function clients(Request $request)
    {
        $q = DB::table('clients as c')
            ->leftJoin('zones as z', 'c.zone_id', '=', 'z.id')
            ->leftJoin('areas as a', 'c.area_id', '=', 'a.id')
            ->leftJoin('resellers as r', 'c.reseller_id', '=', 'r.id')
            ->leftJoin('packages as pk', 'c.package_id', '=', 'pk.id')
            ->whereNull('c.deleted_at')
            ->select(
                'c.id', 'c.name', 'c.phone', 'c.email',
                DB::raw("COALESCE(c.client_id,'') as client_code"),
                DB::raw("COALESCE(c.username,'') as connection_id"),
                'c.status', 'c.connection_status',
                DB::raw("COALESCE(c.due_amount,0) as due_amount"),
                'c.monthly_fee',
                DB::raw("COALESCE(pk.name,'') as package_name"),
                DB::raw("COALESCE(z.name,'') as zone_name"),
                DB::raw("COALESCE(r.name,'') as reseller_name"),
                DB::raw("COALESCE(c.session_ip, c.ip_address,'') as ip_address"),
                DB::raw("COALESCE(c.session_uptime, c.uptime,'') as uptime")
            );

        if ($s = $request->get('search')) {
            $q->where(fn($x) => $x->where('c.name','ilike',"%$s%")->orWhere('c.phone','ilike',"%$s%")->orWhere('c.username','ilike',"%$s%"));
        }
        if ($st = $request->get('status')) $q->where('c.status', $st);

        $perPage = 20; $page = (int)$request->get('page',1);
        $total = $q->count();
        $data  = $q->orderBy('c.name')->offset(($page-1)*$perPage)->limit($perPage)->get();

        return response()->json([
            'data'=>$data,'total'=>$total,'per_page'=>$perPage,
            'current_page'=>$page,'last_page'=>max(1,(int)ceil($total/$perPage)),
        ]);
    }

    public function clientDetail(int $id)
    {
        $client = DB::table('clients as c')
            ->leftJoin('zones as z','c.zone_id','=','z.id')
            ->leftJoin('areas as a','c.area_id','=','a.id')
            ->leftJoin('resellers as r','c.reseller_id','=','r.id')
            ->leftJoin('packages as pk','c.package_id','=','pk.id')
            ->leftJoin(DB::raw('(SELECT client_id,MAX(payment_date) as lpd FROM payments WHERE deleted_at IS NULL GROUP BY client_id) as lp'),'c.id','=','lp.client_id')
            ->whereNull('c.deleted_at')->where('c.id',$id)
            ->select(
                'c.id','c.name','c.phone','c.email',
                DB::raw("COALESCE(c.client_id,'') as client_code"),
                DB::raw("COALESCE(c.username,'') as connection_id"),
                'c.status','c.connection_status',
                DB::raw("COALESCE(c.due_amount,0) as due_amount"),
                DB::raw("COALESCE(c.balance,0) as balance"),
                'c.monthly_fee','c.expire_date','c.billing_date','c.address',
                DB::raw("COALESCE(c.billing_cycle,'monthly') as billing_cycle"),
                DB::raw("COALESCE(c.connection_type,'pppoe') as connection_type"),
                'c.connection_date',
                DB::raw("COALESCE(pk.name,'') as package_name"),
                DB::raw("COALESCE(z.name,'') as zone_name"),
                DB::raw("COALESCE(a.name,'') as area_name"),
                DB::raw("COALESCE(r.name,'') as reseller_name"),
                'lp.lpd as last_payment_date',
                DB::raw("COALESCE(c.session_ip, c.ip_address,'') as ip_address"),
                DB::raw("COALESCE(c.session_mac, c.mac_address,'') as mac_address"),
                DB::raw("COALESCE(c.session_uptime, c.uptime,'') as uptime"),
                DB::raw("COALESCE(c.download_speed,'') as download_speed"),
                DB::raw("COALESCE(c.upload_speed,'') as upload_speed"),
                DB::raw("COALESCE(c.download_usage,'') as download_usage"),
                DB::raw("COALESCE(c.upload_usage,'') as upload_usage"),
                DB::raw("COALESCE(c.last_logout::text,'') as last_logout"),
                'c.last_online',
                DB::raw("COALESCE(c.total_upload_bytes,0) as total_upload_bytes"),
                DB::raw("COALESCE(c.total_download_bytes,0) as total_download_bytes"),
                DB::raw("COALESCE(c.mikrotik_profile_name,'') as mikrotik_profile"),
                DB::raw("COALESCE(c.router_brand,'') as router_brand"),
                DB::raw("COALESCE(c.nid,'') as nid"),
                DB::raw("COALESCE(c.gender,'') as gender"),
                'c.date_of_birth',
                DB::raw("COALESCE(c.alt_phone,'') as alt_phone"),
                DB::raw("COALESCE(c.occupation,'') as occupation"),
                DB::raw("COALESCE(c.father_husband_name,'') as father_name"),
                DB::raw("COALESCE(c.remarks,'') as remarks"),
                DB::raw("COALESCE(c.onu_serial,'') as onu_serial"),
                DB::raw("COALESCE(c.onu_status,'') as onu_status"),
                'c.onu_rx_power','c.onu_tx_power',
                DB::raw("COALESCE(c.onu_signal_quality,'') as onu_signal_quality"),
                DB::raw("COALESCE(c.fiber_code,'') as fiber_code"),
                DB::raw("COALESCE(c.splitter_number,'') as splitter_number"),
                DB::raw("COALESCE(c.installation_charge,0) as installation_charge"),
                DB::raw("COALESCE(c.signup_fee_paid,false) as signup_fee_paid")
            )->first();

        if (!$client) return response()->json(['message'=>'Client not found'],404);

        // Recent bills
        $bills = DB::table('bills')->where('client_id',$id)->whereNull('deleted_at')
            ->select('id','bill_no','billing_month','billing_year','total_amount','paid_amount','due_amount','status')
            ->orderByDesc('billing_year')->orderByDesc('billing_month')->limit(6)->get();

        // Recent payments
        $payments = DB::table('payments as p')
            ->leftJoin('payment_methods as pm','p.payment_method_id','=','pm.id')
            ->where('p.client_id',$id)->whereNull('p.deleted_at')
            ->select('p.id','p.amount','p.payment_date',DB::raw("COALESCE(pm.name,'Cash') as method"))
            ->orderByDesc('p.payment_date')->limit(5)->get();

        // Tickets
        $tickets = DB::table('tickets')
            ->where('client_id', $id)->whereNull('deleted_at')
            ->select('id','ticket_no','subject','priority','status','created_at','description')
            ->orderByDesc('created_at')->limit(10)->get();

        return response()->json(['client'=>$client,'bills'=>$bills,'payments'=>$payments,'tickets'=>$tickets]);
    }

    public function collectPayment(Request $request, int $id)
    {
        $request->validate([
            'amount'            => 'required|numeric|min:1',
            'payment_method_id' => 'required|integer',
        ]);

        $client = DB::table('clients')->whereNull('deleted_at')->where('id',$id)->first();
        if (!$client) return response()->json(['message'=>'Client not found'],404);

        $billingService = app(BillingService::class);
        $clientModel = \App\Models\Client::findOrFail($id);

        $payment = $billingService->recordPayment($clientModel, [
            'amount'            => $request->amount,
            'payment_method_id' => $request->payment_method_id,
            'payment_date'      => now(),
            'remarks'           => $request->get('remarks','Mobile collection'),
            'transaction_id'    => $request->get('transaction_id',''),
            'sender_number'     => '',
        ], $request->user()->id, 'admin');

        return response()->json([
            'success'    => true,
            'message'    => '৳'.number_format($request->amount).' সফলভাবে collect হয়েছে',
            'payment_no' => $payment->payment_no ?? '',
        ]);
    }

    public function paymentMethods()
    {
        $methods = DB::table('payment_methods')
            ->where('is_active', true)
            ->select('id','name','code')
            ->orderBy('name')->get();
        return response()->json(['data'=>$methods]);
    }

    /* ───── BILLING ───── */

    public function billing(Request $request)
    {
        $perPage = 20; $page = (int)$request->get('page',1);
        $status  = $request->get('status','');

        $q = DB::table('bills as b')
            ->join('clients as c','b.client_id','=','c.id')
            ->whereNull('b.deleted_at')
            ->select(
                'b.id','b.bill_no','c.name as client_name','c.phone as client_phone',
                'b.billing_month','b.billing_year',
                'b.total_amount','b.paid_amount','b.due_amount','b.status',
                'b.due_date'
            );

        if ($status) $q->where('b.status',$status);
        if ($s = $request->get('search')) {
            $q->where(fn($x)=>$x->where('c.name','ilike',"%$s%")->orWhere('c.phone','ilike',"%$s%")->orWhere('b.bill_no','ilike',"%$s%"));
        }

        $total = $q->count();
        $data  = $q->orderByDesc('b.billing_year')->orderByDesc('b.billing_month')->orderByDesc('b.id')
            ->offset(($page-1)*$perPage)->limit($perPage)->get();

        // Summary
        $summary = DB::table('bills')->whereNull('deleted_at')
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status!='paid' THEN due_amount ELSE 0 END) as total_due, SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as paid_count, SUM(CASE WHEN status='unpaid' THEN 1 ELSE 0 END) as unpaid_count")
            ->first();

        return response()->json([
            'data'=>$data,'total'=>$total,'per_page'=>$perPage,
            'current_page'=>$page,'last_page'=>max(1,(int)ceil($total/$perPage)),
            'summary'=>$summary,
        ]);
    }

    /* ───── PAYMENTS ───── */

    public function payments(Request $request)
    {
        $perPage = 20; $page = (int)$request->get('page',1);
        $q = DB::table('payments as p')
            ->join('clients as c','p.client_id','=','c.id')
            ->leftJoin('payment_methods as pm','p.payment_method_id','=','pm.id')
            ->leftJoin('users as u','p.collected_by','=','u.id')
            ->whereNull('p.deleted_at')
            ->select(
                'p.id','c.name as client_name','c.phone as client_phone',
                'p.amount','p.payment_date',
                DB::raw("COALESCE(pm.name,'Cash') as payment_method"),
                DB::raw("TO_CHAR(p.payment_date::date,'Mon YYYY') as month"),
                DB::raw("COALESCE(u.name,'') as collected_by")
            )
            ->orderByDesc('p.payment_date')->orderByDesc('p.id');

        $total = $q->count();
        $data  = $q->offset(($page-1)*$perPage)->limit($perPage)->get();

        return response()->json([
            'data'=>$data,'total'=>$total,'per_page'=>$perPage,
            'current_page'=>$page,'last_page'=>max(1,(int)ceil($total/$perPage)),
        ]);
    }

    /* ───── TICKETS ───── */

    public function tickets(Request $request)
    {
        $perPage = 20; $page = (int)$request->get('page',1);
        $status  = $request->get('status','');

        $q = DB::table('tickets as t')
            ->leftJoin('clients as c','t.client_id','=','c.id')
            ->leftJoin('users as u','t.assigned_to','=','u.id')
            ->whereNull('t.deleted_at')
            ->select(
                't.id','t.ticket_no','t.subject','t.priority','t.status','t.created_at',
                DB::raw("COALESCE(c.name,'') as client_name"),
                DB::raw("COALESCE(c.phone,'') as client_phone"),
                DB::raw("COALESCE(u.name,'') as assigned_to")
            );

        if ($status) $q->where('t.status',$status);
        if ($s = $request->get('search')) {
            $q->where(fn($x)=>$x->where('t.subject','ilike',"%$s%")->orWhere('t.ticket_no','ilike',"%$s%")->orWhere('c.name','ilike',"%$s%"));
        }

        $total = $q->count();
        $data  = $q->orderByDesc('t.created_at')->offset(($page-1)*$perPage)->limit($perPage)->get();

        return response()->json([
            'data'=>$data,'total'=>$total,'per_page'=>$perPage,
            'current_page'=>$page,'last_page'=>max(1,(int)ceil($total/$perPage)),
        ]);
    }

    public function ticketDetail(int $id)
    {
        $ticket = DB::table('tickets as t')
            ->leftJoin('clients as c','t.client_id','=','c.id')
            ->leftJoin('users as u','t.assigned_to','=','u.id')
            ->whereNull('t.deleted_at')->where('t.id',$id)
            ->select('t.*',
                DB::raw("COALESCE(c.name,'') as client_name"),
                DB::raw("COALESCE(c.phone,'') as client_phone"),
                DB::raw("COALESCE(u.name,'') as assigned_to_name"))
            ->first();

        if (!$ticket) return response()->json(['message'=>'Ticket not found'],404);

        $messages = DB::table('ticket_messages')
            ->leftJoin('users as u','ticket_messages.user_id','=','u.id')
            ->where('ticket_messages.ticket_id',$id)
            ->select('ticket_messages.id','ticket_messages.message','ticket_messages.created_at',
                DB::raw("COALESCE(u.name,'System') as sender"))
            ->orderBy('ticket_messages.created_at')->get();

        return response()->json(['ticket'=>$ticket,'messages'=>$messages]);
    }

    /* ───── RESELLERS ───── */

    public function resellers(Request $request)
    {
        $data = DB::table('resellers as r')
            ->whereNull('r.deleted_at')
            ->leftJoin(DB::raw("(SELECT reseller_id, COUNT(*) as total_clients, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_clients FROM clients WHERE deleted_at IS NULL GROUP BY reseller_id) as cs"),'r.id','=','cs.reseller_id')
            ->leftJoin(DB::raw("(SELECT c2.reseller_id, SUM(p2.amount) as monthly_revenue FROM payments p2 JOIN clients c2 ON p2.client_id=c2.id WHERE p2.deleted_at IS NULL AND p2.payment_date>=date_trunc('month',CURRENT_DATE) GROUP BY c2.reseller_id) as ms"),'r.id','=','ms.reseller_id')
            ->select('r.id','r.name','r.phone',
                DB::raw("COALESCE(cs.total_clients,0) as total_clients"),
                DB::raw("COALESCE(cs.active_clients,0) as active_clients"),
                DB::raw("COALESCE(ms.monthly_revenue,0) as monthly_revenue"),
                'r.status')
            ->orderBy('r.name')->get();

        return response()->json(['data'=>$data]);
    }

    /* ───── APPLICATIONS ───── */

    public function applications(Request $request)
    {
        $perPage = 20; $page = (int)$request->get('page',1);
        $status  = $request->get('status','submitted');

        $q = DB::table('new_connection_applications')
            ->select('id','application_number','full_name_english','mobile_number','status','created_at','zone_id')
            ->where('status',$status)
            ->orderByDesc('created_at');

        $total = $q->count();
        $data  = $q->offset(($page-1)*$perPage)->limit($perPage)->get();

        $counts = DB::table('new_connection_applications')
            ->selectRaw("SUM(CASE WHEN status='submitted' THEN 1 ELSE 0 END) as submitted, SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected")
            ->first();

        return response()->json([
            'data'=>$data,'total'=>$total,'per_page'=>$perPage,
            'current_page'=>$page,'last_page'=>max(1,(int)ceil($total/$perPage)),
            'counts'=>$counts,
        ]);
    }

    /* ───── NETWORK ───── */

    public function network()
    {
        $devices = DB::table('mikrotik_devices')
            ->whereNull('deleted_at')
            ->select('id','name','host','is_active','last_connected_at','last_cpu_load','last_memory_usage')
            ->orderBy('name')->get();

        $olts = DB::table('olt_devices')
            ->select('id','name','ip_address' . ' as host','is_active','last_sync' . ' as last_connected_at')
            ->orderBy('name')->get();

        $totalClients    = DB::table('clients')->whereNull('deleted_at')->where('status','active')->count();
        $onlineClients   = DB::table('clients')->whereNull('deleted_at')->where('connection_status','online')->count();

        return response()->json([
            'mikrotik' => $devices,
            'olt'      => $olts,
            'summary'  => [
                'total_active'  => (int)$totalClients,
                'online'        => (int)$onlineClients,
                'offline'       => (int)($totalClients - $onlineClients),
            ],
        ]);
    }

    /* ───── REPORTS ───── */

    public function reports(Request $request)
    {
        $month = (int)$request->get('month', now()->month);
        $year  = (int)$request->get('year', now()->year);

        // Monthly collection by day
        $daily = DB::table('payments')
            ->whereNull('deleted_at')
            ->whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year)
            ->selectRaw("payment_date::date as day, SUM(amount) as total, COUNT(*) as count")
            ->groupBy('day')->orderBy('day')->get();

        // By payment method
        $byMethod = DB::table('payments as p')
            ->leftJoin('payment_methods as pm','p.payment_method_id','=','pm.id')
            ->whereNull('p.deleted_at')
            ->whereMonth('p.payment_date',$month)->whereYear('p.payment_date',$year)
            ->selectRaw("COALESCE(pm.name,'Cash') as method, SUM(p.amount) as total, COUNT(*) as count")
            ->groupBy('pm.name')->orderByDesc('total')->get();

        // By reseller
        $byReseller = DB::table('payments as p')
            ->join('clients as c','p.client_id','=','c.id')
            ->leftJoin('resellers as r','c.reseller_id','=','r.id')
            ->whereNull('p.deleted_at')
            ->whereMonth('p.payment_date',$month)->whereYear('p.payment_date',$year)
            ->selectRaw("COALESCE(r.name,'Direct') as reseller, SUM(p.amount) as total, COUNT(*) as count")
            ->groupBy('r.name')->orderByDesc('total')->get();

        $totalCollection = DB::table('payments')->whereNull('deleted_at')
            ->whereMonth('payment_date',$month)->whereYear('payment_date',$year)->sum('amount');

        return response()->json([
            'month'          => $month,
            'year'           => $year,
            'total'          => (float)$totalCollection,
            'daily'          => $daily,
            'by_method'      => $byMethod,
            'by_reseller'    => $byReseller,
        ]);
    }
}
