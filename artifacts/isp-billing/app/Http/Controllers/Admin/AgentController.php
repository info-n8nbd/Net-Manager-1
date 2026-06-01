<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::orderBy('created_at', 'desc')->get();

        $resellerCounts = \App\Models\Reseller::selectRaw('agent_id, count(*) as count')
            ->whereNotNull('agent_id')
            ->groupBy('agent_id')
            ->pluck('count', 'agent_id');

        $clientCounts = \App\Models\Client::selectRaw('agent_id, count(*) as count')
            ->whereNotNull('agent_id')
            ->whereNull('reseller_id')
            ->groupBy('agent_id')
            ->pluck('count', 'agent_id');

        return view('admin.agent.index', compact('agents', 'resellerCounts', 'clientCounts'));
    }

    public function create()
    {
        $nextId = $this->generateAgentId();
        return view('admin.agent.create', compact('nextId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:agents,username',
            'password' => 'required|string|min:4',
            'default_commission' => 'required|numeric|min:0|max:100',
        ]);

        $agentId = $this->generateAgentId();

        Agent::create([
            'agent_id' => $agentId,
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'default_commission' => $request->default_commission,
            'father_name' => $request->father_name,
            'mother_name' => $request->mother_name,
            'date_of_birth' => $request->date_of_birth,
            'joining_date' => $request->joining_date ?? now()->toDateString(),
            'gender' => $request->gender ?? 'male',
            'marital_status' => $request->marital_status ?? 'unmarried',
            'blood_group' => $request->blood_group,
            'national_id' => $request->national_id,
            'present_address' => $request->present_address,
            'permanent_address' => $request->permanent_address,
            'personal_contact' => $request->personal_contact,
            'whatsapp_no' => $request->whatsapp_no,
            'office_contact' => $request->office_contact,
            'family_contact' => $request->family_contact,
            'first_reference_no' => $request->first_reference_no,
            'second_reference_no' => $request->second_reference_no,
            'email' => $request->email,
            'company_id' => auth()->user()->company_id ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.agent.index')->with('success', 'Agent created successfully.');
    }

    public function edit($id)
    {
        $agent = Agent::findOrFail($id);
        return view('admin.agent.edit', compact('agent'));
    }

    public function update(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:agents,username,' . $agent->id,
            'default_commission' => 'required|numeric|min:0|max:100',
        ]);

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'default_commission' => $request->default_commission,
            'father_name' => $request->father_name,
            'mother_name' => $request->mother_name,
            'date_of_birth' => $request->date_of_birth,
            'joining_date' => $request->joining_date,
            'gender' => $request->gender ?? 'male',
            'marital_status' => $request->marital_status ?? 'unmarried',
            'blood_group' => $request->blood_group,
            'national_id' => $request->national_id,
            'present_address' => $request->present_address,
            'permanent_address' => $request->permanent_address,
            'personal_contact' => $request->personal_contact,
            'whatsapp_no' => $request->whatsapp_no,
            'office_contact' => $request->office_contact,
            'family_contact' => $request->family_contact,
            'first_reference_no' => $request->first_reference_no,
            'second_reference_no' => $request->second_reference_no,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $agent->update($data);

        return redirect()->route('admin.agent.index')->with('success', 'Agent updated successfully.');
    }

    public function destroy($id)
    {
        $agent = Agent::findOrFail($id);
        $agent->delete();
        return redirect()->route('admin.agent.index')->with('success', 'Agent deleted successfully.');
    }

    public function commission($id, Request $request)
    {
        $agent = Agent::findOrFail($id);
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $commissions = AgentCommission::where('agent_id', $agent->id)
            ->where('type', 'commission')
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('date', 'desc')
            ->get();

        $totalAmount = $commissions->sum('commission_amount');
        $clientCount = \App\Models\Client::where('agent_id', $agent->id)->count();

        $monthName = \Carbon\Carbon::createFromDate($year, $month, 1)->format('M-Y');

        return view('admin.agent.commission', compact('agent', 'commissions', 'totalAmount', 'clientCount', 'month', 'year', 'monthName'));
    }

    public function storeCommission(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        if ($agent->status !== 'active') {
            return redirect()->back()->with('error', 'Commission cannot be added. Agent "' . $agent->name . '" is currently OFF/Inactive.');
        }

        $request->validate([
            'client_name' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'payment_amount' => 'required|numeric|min:0',
            'commission_percent' => 'required|numeric|min:0|max:100',
            'date' => 'required|date',
        ]);

        $commissionAmount = ($request->payment_amount * $request->commission_percent) / 100;
        $date = \Carbon\Carbon::parse($request->date);

        AgentCommission::create([
            'agent_id' => $agent->id,
            'type' => 'commission',
            'client_name' => $request->client_name,
            'client_pppoe_id' => $request->client_pppoe_id,
            'zone_address' => $request->zone_address,
            'purpose' => $request->purpose,
            'note' => $request->note,
            'date' => $request->date,
            'payment_amount' => $request->payment_amount,
            'commission_percent' => $request->commission_percent,
            'commission_amount' => $commissionAmount,
            'month' => $date->month,
            'year' => $date->year,
            'company_id' => auth()->user()->company_id ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.agent.commission', ['id' => $agent->id, 'month' => $date->month, 'year' => $date->year])
            ->with('success', 'Commission added successfully.');
    }

    public function destroyCommission($id)
    {
        $commission = AgentCommission::findOrFail($id);
        $agentId = $commission->agent_id;
        $month = $commission->month;
        $year = $commission->year;
        $commission->delete();

        return redirect()->route('admin.agent.commission', ['id' => $agentId, 'month' => $month, 'year' => $year])
            ->with('success', 'Commission record deleted.');
    }

    public function toggleStatus($id)
    {
        $agent = Agent::findOrFail($id);
        $agent->status = $agent->status === 'active' ? 'inactive' : 'active';
        $agent->save();

        $statusText = $agent->status === 'active' ? 'ON' : 'OFF';
        return response()->json([
            'success' => true,
            'status' => $agent->status,
            'message' => "Agent {$agent->name} commission is now {$statusText}.",
        ]);
    }

    public function allLedgers()
    {
        $agents = Agent::orderBy('name')->get()->map(function ($agent) {
            $agent->total_commission = AgentCommission::where('agent_id', $agent->id)->where('type', 'commission')->sum('commission_amount');
            $agent->total_paid = AgentCommission::where('agent_id', $agent->id)->where('type', 'payout')->sum('commission_amount');
            $agent->current_balance = $agent->total_commission - $agent->total_paid;
            $agent->total_entries = AgentCommission::where('agent_id', $agent->id)->where('type', 'commission')->count();
            $agent->client_count = \App\Models\Client::where('agent_id', $agent->id)->count();
            $latestCommission = AgentCommission::where('agent_id', $agent->id)->orderByDesc('date')->first();
            $agent->last_commission_date = $latestCommission ? $latestCommission->date : null;
            return $agent;
        });

        $grandTotal = $agents->sum('total_commission');
        $totalEntries = $agents->sum('total_entries');

        return view('admin.agent.all_ledgers', compact('agents', 'grandTotal', 'totalEntries'));
    }

    public function ledger($id)
    {
        $agent = Agent::findOrFail($id);
        $entries = AgentCommission::where('agent_id', $agent->id)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalEarned = $entries->where('type', 'commission')->sum('commission_amount');
        $totalPaid = $entries->where('type', 'payout')->sum('commission_amount');
        $currentBalance = $totalEarned - $totalPaid;

        $balance = 0;
        foreach ($entries as $entry) {
            if ($entry->type === 'commission') {
                $balance += $entry->commission_amount;
            } else {
                $balance -= $entry->commission_amount;
            }
            $entry->running_balance = $balance;
        }

        $entriesDesc = $entries->reverse()->values();

        $monthlyTotals = AgentCommission::where('agent_id', $agent->id)
            ->where('type', 'commission')
            ->selectRaw('month, year, SUM(commission_amount) as total, COUNT(*) as entries')
            ->groupBy('month', 'year')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('admin.agent.ledger', compact('agent', 'entries', 'entriesDesc', 'monthlyTotals', 'totalEarned', 'totalPaid', 'currentBalance'));
    }

    public function payAgent(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $agent) {
            $totalEarned = AgentCommission::where('agent_id', $agent->id)->where('type', 'commission')->sum('commission_amount');
            $totalPaid = AgentCommission::where('agent_id', $agent->id)->where('type', 'payout')->sum('commission_amount');
            $currentBalance = $totalEarned - $totalPaid;

            if ($request->amount > $currentBalance) {
                return redirect()->back()->with('error', 'Payment amount (৳' . number_format($request->amount, 2) . ') exceeds available balance (৳' . number_format($currentBalance, 2) . ').');
            }

            $date = \Carbon\Carbon::parse($request->date);

            AgentCommission::create([
                'agent_id' => $agent->id,
                'type' => 'payout',
                'client_name' => 'Agent Payment',
                'purpose' => 'Payout',
                'note' => $request->note ?? 'Paid to agent',
                'date' => $request->date,
                'payment_amount' => 0,
                'commission_percent' => 0,
                'commission_amount' => $request->amount,
                'month' => $date->month,
                'year' => $date->year,
                'company_id' => auth()->user()->company_id ?? null,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.agent.ledger', $agent->id)
                ->with('success', '৳' . number_format($request->amount, 2) . ' paid to agent ' . $agent->name . ' successfully.');
        });
    }

    public function resellers($id)
    {
        $agent = Agent::findOrFail($id);
        $resellers = \App\Models\Reseller::where('agent_id', $agent->id)
            ->withCount('clients')
            ->get();

        $totalBills = 0;
        $totalCommission = 0;
        foreach ($resellers as $reseller) {
            $reseller->total_bill = \App\Models\ResellerBill::where('reseller_id', $reseller->id)->sum('total_recharge');
            $reseller->total_paid = \App\Models\ResellerBill::where('reseller_id', $reseller->id)->sum('total_paid');
            $totalBills += $reseller->total_bill;
        }

        $totalCommission = AgentCommission::where('agent_id', $agent->id)
            ->where('type', 'commission')
            ->whereIn('purpose', ['Reseller Bill Payment', 'BW Monthly Bill'])
            ->sum('commission_amount');

        return view('admin.agent.resellers', compact('agent', 'resellers', 'totalBills', 'totalCommission'));
    }

    public function clients($id)
    {
        $agent = Agent::findOrFail($id);
        $clients = \App\Models\Client::where('agent_id', $agent->id)
            ->whereNull('reseller_id')
            ->orderBy('name')
            ->get();

        $totalCommission = AgentCommission::where('agent_id', $agent->id)
            ->where('type', 'commission')
            ->where('purpose', '!=', 'Reseller Bill Payment')
            ->sum('commission_amount');

        return view('admin.agent.clients', compact('agent', 'clients', 'totalCommission'));
    }

    private function generateAgentId(): string
    {
        // withoutGlobalScopes ensures ALL companies checked, not just current tenant
        $last = Agent::withoutGlobalScopes()
            ->orderByRaw("CAST(NULLIF(regexp_replace(agent_id, '[^0-9]', '', 'g'), '') AS BIGINT) DESC NULLS LAST")
            ->first();
        if ($last) {
            $num = intval(preg_replace('/[^0-9]/', '', $last->agent_id)) + 1;
        } else {
            $num = 14616;
        }
        // Collision guard
        while (Agent::withoutGlobalScopes()->where('agent_id', (string) $num)->exists()) {
            $num++;
        }
        return (string) $num;
    }
}
