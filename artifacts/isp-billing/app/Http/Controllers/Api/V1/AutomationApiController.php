<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Package;
use App\Models\Bill;
use App\Models\Ticket;
use App\Models\Zone;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutomationApiController extends Controller
{
    // ─── Helper ────────────────────────────────────────────
    private function companyId()
    {
        return auth()->user()->company_id;
    }

    private function ok($data, $meta = [])
    {
        return response()->json(array_merge(['success' => true, 'data' => $data], $meta));
    }

    private function err($msg, $code = 400)
    {
        return response()->json(['success' => false, 'error' => $msg], $code);
    }

    // ─── CLIENTS ───────────────────────────────────────────
    public function listClients(Request $request)
    {
        $q = Client::with(['package:id,name,price,download_speed,upload_speed,speed_unit', 'zone:id,name', 'area:id,name'])
            ->where('clients.company_id', $this->companyId());

        if ($request->status)           $q->where('status', $request->status);
        if ($request->zone_id)          $q->where('zone_id', $request->zone_id);
        if ($request->area_id)          $q->where('area_id', $request->area_id);
        if ($request->package_id)       $q->where('package_id', $request->package_id);
        if ($request->search)           $q->where(function($s) use ($request) {
            $s->where('name','ilike','%'.$request->search.'%')
              ->orWhere('username','ilike','%'.$request->search.'%')
              ->orWhere('phone','ilike','%'.$request->search.'%')
              ->orWhere('email','ilike','%'.$request->search.'%');
        });
        if ($request->due_only)         $q->where('due_amount', '>', 0);

        $perPage = in_array((int)$request->per_page,[10,20,50,100]) ? (int)$request->per_page : 20;
        $clients = $q->orderBy('created_at','desc')->paginate($perPage);

        return $this->ok($clients->items(), [
            'total'        => $clients->total(),
            'per_page'     => $clients->perPage(),
            'current_page' => $clients->currentPage(),
            'last_page'    => $clients->lastPage(),
        ]);
    }

    public function getClient($id)
    {
        $client = Client::with(['package','zone','area'])
            ->where('company_id', $this->companyId())
            ->find($id);
        if (!$client) return $this->err('Client not found', 404);

        return $this->ok([
            'id'              => $client->id,
            'client_id'       => $client->client_id,
            'name'            => $client->name,
            'username'        => $client->username,
            'phone'           => $client->phone,
            'email'           => $client->email,
            'address'         => $client->address,
            'status'          => $client->status,
            'connection_status'=> $client->connection_status,
            'connection_type' => $client->connection_type,
            'due_amount'      => $client->due_amount ?? 0,
            'balance'         => $client->balance ?? 0,
            'expire_date'     => $client->expire_date,
            'package'         => $client->package ? [
                'id' => $client->package->id, 'name' => $client->package->name,
                'price' => $client->package->price, 'download_speed' => $client->package->download_speed, 'upload_speed' => $client->package->upload_speed, 'speed_unit' => $client->package->speed_unit,
            ] : null,
            'zone'            => $client->zone ? ['id'=>$client->zone->id,'name'=>$client->zone->name] : null,
            'area'            => $client->area ? ['id'=>$client->area->id,'name'=>$client->area->name] : null,
            'created_at'      => $client->created_at,
        ]);
    }

    public function activateClient(Request $request, $id)
    {
        $apiToken = $request->attributes->get('api_token');
        if (!$apiToken || !$apiToken->hasAbility('write')) {
            return $this->err('Write permission required', 403);
        }
        $client = Client::where('company_id', $this->companyId())->find($id);
        if (!$client) return $this->err('Client not found', 404);
        $client->update(['status' => 'active']);
        return $this->ok(['id' => $client->id, 'status' => 'active'], ['message' => 'Client activated']);
    }

    public function suspendClient(Request $request, $id)
    {
        $apiToken = $request->attributes->get('api_token');
        if (!$apiToken || !$apiToken->hasAbility('write')) {
            return $this->err('Write permission required', 403);
        }
        $client = Client::where('company_id', $this->companyId())->find($id);
        if (!$client) return $this->err('Client not found', 404);
        $client->update(['status' => 'inactive']);
        return $this->ok(['id' => $client->id, 'status' => 'inactive'], ['message' => 'Client suspended']);
    }

    // ─── PAYMENTS ──────────────────────────────────────────
    public function listPayments(Request $request)
    {
        $q = Payment::with('client:id,name,username')
            ->whereHas('client', fn($c) => $c->where('company_id', $this->companyId()));

        if ($request->client_id)    $q->where('client_id', $request->client_id);
        if ($request->date_from)    $q->where('payment_date','>=', $request->date_from);
        if ($request->date_to)      $q->where('payment_date','<=', $request->date_to);
        if ($request->min_amount)   $q->where('amount','>=', $request->min_amount);

        $perPage = in_array((int)$request->per_page,[10,20,50,100]) ? (int)$request->per_page : 20;
        $payments = $q->orderBy('payment_date','desc')->paginate($perPage);

        return $this->ok($payments->items(), [
            'total'        => $payments->total(),
            'per_page'     => $payments->perPage(),
            'current_page' => $payments->currentPage(),
            'last_page'    => $payments->lastPage(),
        ]);
    }

    public function createPayment(Request $request)
    {
        $apiToken = $request->attributes->get('api_token');
        if (!$apiToken || !$apiToken->hasAbility('billing')) {
            return $this->err('Billing permission required', 403);
        }

        $request->validate([
            'client_id'    => 'required|integer',
            'amount'       => 'required|numeric|min:1',
            'payment_date' => 'nullable|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        $client = Client::where('company_id', $this->companyId())->find($request->client_id);
        if (!$client) return $this->err('Client not found', 404);

        $payment = Payment::create([
            'client_id'    => $client->id,
            'amount'       => $request->amount,
            'payment_date' => $request->payment_date ?? now()->toDateString(),
            'notes'        => $request->notes ?? 'API payment',
            'received_by'  => auth()->id(),
            'company_id'   => $this->companyId(),
        ]);

        // Update client due_amount
        $client->decrement('due_amount', min($request->amount, $client->due_amount ?? 0));

        return $this->ok($payment, ['message' => 'Payment recorded']);
    }

    // ─── PACKAGES ──────────────────────────────────────────
    public function listPackages(Request $request)
    {
        $packages = Package::where('company_id', $this->companyId())
            ->where('is_active', true)
            ->select('id','name','price','download_speed','upload_speed','speed_unit','bandwidth','description','connection_type')
            ->orderBy('name')
            ->get();
        return $this->ok($packages);
    }

    // ─── BILLS ─────────────────────────────────────────────
    public function listBills(Request $request)
    {
        $q = Bill::with('client:id,name,username')
            ->whereHas('client', fn($c) => $c->where('company_id', $this->companyId()));

        if ($request->client_id)  $q->where('client_id', $request->client_id);
        if ($request->status)     $q->where('status', $request->status);
        if ($request->date_from)  $q->where('created_at','>=', $request->date_from);
        if ($request->date_to)    $q->where('created_at','<=', $request->date_to);

        $perPage = in_array((int)$request->per_page,[10,20,50,100]) ? (int)$request->per_page : 20;
        $bills = $q->orderBy('created_at','desc')->paginate($perPage);

        return $this->ok($bills->items(), [
            'total'        => $bills->total(),
            'current_page' => $bills->currentPage(),
            'last_page'    => $bills->lastPage(),
        ]);
    }

    // ─── TICKETS ───────────────────────────────────────────
    public function listTickets(Request $request)
    {
        $q = Ticket::with(['client:id,name,username','category:id,name'])
            ->where('company_id', $this->companyId());

        if ($request->status)    $q->where('status', $request->status);
        if ($request->priority)  $q->where('priority', $request->priority);
        if ($request->client_id) $q->where('client_id', $request->client_id);

        $perPage = in_array((int)$request->per_page,[10,20,50,100]) ? (int)$request->per_page : 20;
        $tickets = $q->orderBy('created_at','desc')->paginate($perPage);

        return $this->ok($tickets->items(), [
            'total'        => $tickets->total(),
            'current_page' => $tickets->currentPage(),
            'last_page'    => $tickets->lastPage(),
        ]);
    }

    public function createTicket(Request $request)
    {
        $apiToken = $request->attributes->get('api_token');
        if (!$apiToken || !$apiToken->hasAbility('write')) {
            return $this->err('Write permission required', 403);
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'client_id'   => 'nullable|integer',
            'priority'    => 'nullable|in:low,medium,high,urgent',
            'category_id' => 'nullable|integer',
        ]);

        $ticket = Ticket::create([
            'title'       => $request->title,
            'description' => $request->description,
            'client_id'   => $request->client_id,
            'priority'    => $request->priority ?? 'medium',
            'category_id' => $request->category_id,
            'status'      => 'open',
            'company_id'  => $this->companyId(),
            'created_by'  => auth()->id(),
        ]);

        return $this->ok($ticket, ['message' => 'Ticket created']);
    }

    // ─── STATS ─────────────────────────────────────────────
    public function stats()
    {
        $cid = $this->companyId();

        $totalClients   = Client::where('company_id', $cid)->count();
        $activeClients  = Client::where('company_id', $cid)->where('status','active')->count();
        $inactiveClients= Client::where('company_id', $cid)->where('status','inactive')->count();
        $totalDue       = Client::where('company_id', $cid)->sum('due_amount');

        $todayPayments  = Payment::whereHas('client', fn($c) => $c->where('company_id',$cid))
            ->whereDate('payment_date', today())->sum('amount');
        $monthPayments  = Payment::whereHas('client', fn($c) => $c->where('company_id',$cid))
            ->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount');

        $openTickets    = Ticket::where('company_id',$cid)->where('status','open')->count();

        return $this->ok([
            'clients' => [
                'total'    => $totalClients,
                'active'   => $activeClients,
                'inactive' => $inactiveClients,
                'total_due'=> round($totalDue, 2),
            ],
            'payments' => [
                'today_amount' => round($todayPayments, 2),
                'month_amount' => round($monthPayments, 2),
            ],
            'tickets' => [
                'open' => $openTickets,
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    // ─── ZONES & AREAS ─────────────────────────────────────
    public function listZones()
    {
        $zones = Zone::where('company_id', $this->companyId())
            ->select('id','name')->orderBy('name')->get();
        return $this->ok($zones);
    }

    public function listAreas(Request $request)
    {
        $q = Area::where('company_id', $this->companyId())->select('id','name','zone_id');
        if ($request->zone_id) $q->where('zone_id', $request->zone_id);
        return $this->ok($q->orderBy('name')->get());
    }
}
