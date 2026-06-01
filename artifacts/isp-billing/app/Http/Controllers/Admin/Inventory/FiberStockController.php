<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FiberStock;
use App\Models\FiberUsage;
use App\Models\FiberHistory;
use App\Models\Vendor;
use App\Models\Client;
use App\Models\User;
use App\Models\Employee;

class FiberStockController extends Controller
{
    public function index()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $stocks = FiberStock::with('vendor')
            ->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->latest()->get();
        $vendors = Vendor::where('is_active', true)->get();
        $employees = Employee::orderBy('name')->get();
        return view('admin.inventory.fiber.index', compact('stocks', 'vendors', 'employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_id'                => 'required|exists:vendors,id',
            'fibers'                   => 'required|array|min:1',
            'fibers.*.fiber_code'      => 'required|distinct|unique:fiber_stocks,fiber_code',
            'fibers.*.fiber_type'      => 'required|in:Single Mode,Multi Mode',
            'fibers.*.core'            => 'required|integer|min:1',
            'fibers.*.start_meter'     => 'required|numeric|min:0',
            'fibers.*.end_meter'       => 'required|numeric|gt:fibers.*.start_meter',
            'stock_date'               => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($request->fibers as $fiberData) {
                $total = $fiberData['end_meter'] - $fiberData['start_meter'];
                if ($total <= 0) {
                    throw new \Exception('End meter must be greater than start meter for fiber: ' . $fiberData['fiber_code']);
                }

                $perMeterPrice = !empty($fiberData['per_meter_price']) ? (float)$fiberData['per_meter_price'] : null;
                $totalCost = $perMeterPrice ? round($perMeterPrice * $total, 2) : null;

                $stock = FiberStock::create([
                    'vendor_id'       => $request->vendor_id,
                    'purchaser'       => $request->purchaser,
                    'invoice_number'  => $request->invoice_number,
                    'fiber_code'      => $fiberData['fiber_code'],
                    'brand_name'      => $fiberData['brand_name'] ?? null,
                    'fiber_type'      => $fiberData['fiber_type'],
                    'core'            => $fiberData['core'],
                    'start_meter'     => $fiberData['start_meter'],
                    'end_meter'       => $fiberData['end_meter'],
                    'total_meter'     => $total,
                    'remaining_meter' => $total,
                    'per_meter_price' => $perMeterPrice,
                    'total_cost'      => $totalCost,
                    'stock_date'      => $request->stock_date,
                    'notes'           => $fiberData['notes'] ?? null,
                    'status'          => 'active',
                ]);

                $vendor = \App\Models\Vendor::find($request->vendor_id);
                FiberHistory::create([
                    'type'           => 'stock_in',
                    'fiber_stock_id' => $stock->id,
                    'meter'          => $total,
                    'details'        => 'Stock In: ' . $fiberData['fiber_code']
                        . ' | Vendor: ' . ($vendor->name ?? 'N/A')
                        . ($request->invoice_number ? ' | Invoice: ' . $request->invoice_number : '')
                        . ($request->purchaser ? ' | Purchaser: ' . $request->purchaser : '')
                        . ' | Date: ' . $request->stock_date,
                    'date'           => $request->stock_date,
                ]);

                $count++;
            }

            DB::commit();
            $msg = $count === 1 ? 'Fiber stock added successfully' : $count . ' fiber stocks added successfully';
            return redirect()->route('admin.inventory.fiber.index')
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $stock = FiberStock::findOrFail($id);
        $vendors = Vendor::where('is_active', true)->get();
        $employees = Employee::orderBy('name')->get();
        return view('admin.inventory.fiber.edit', compact('stock', 'vendors', 'employees'));
    }

    public function update(Request $request, $id)
    {
        $stock = FiberStock::findOrFail($id);

        $request->validate([
            'vendor_id'   => 'required|exists:vendors,id',
            'fiber_code'  => 'required|unique:fiber_stocks,fiber_code,' . $stock->id,
            'fiber_type'  => 'required|in:Single Mode,Multi Mode',
            'core'        => 'required|integer|min:1',
            'start_meter' => 'required|numeric|min:0',
            'end_meter'   => 'required|numeric|gt:start_meter',
            'stock_date'  => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $newTotal = $request->end_meter - $request->start_meter;
            $usedAmount = $stock->total_meter - $stock->remaining_meter;
            $newRemaining = $newTotal - $usedAmount;

            if ($newRemaining < 0) {
                return back()->with('error', "Cannot update: new total meter ({$newTotal}m) is less than already used amount ({$usedAmount}m).")->withInput();
            }

            $perMeterPrice = $request->per_meter_price ? (float)$request->per_meter_price : null;
            $totalCost = $perMeterPrice ? round($perMeterPrice * $newTotal, 2) : $request->total_cost;

            $stock->update([
                'vendor_id'       => $request->vendor_id,
                'purchaser'       => $request->purchaser,
                'invoice_number'  => $request->invoice_number,
                'fiber_code'      => $request->fiber_code,
                'brand_name'      => $request->brand_name,
                'fiber_type'      => $request->fiber_type,
                'core'            => $request->core,
                'start_meter'     => $request->start_meter,
                'end_meter'       => $request->end_meter,
                'total_meter'     => $newTotal,
                'remaining_meter' => $newRemaining,
                'per_meter_price' => $perMeterPrice,
                'total_cost'      => $totalCost,
                'stock_date'      => $request->stock_date,
                'notes'           => $request->notes,
                'status'          => $request->status ?? ($newRemaining <= 0 ? 'finished' : 'active'),
            ]);

            DB::commit();
            return redirect()->route('admin.inventory.fiber.index')
                ->with('success', 'Fiber stock updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $stock = FiberStock::findOrFail($id);

        $hasHistory = FiberHistory::where('fiber_stock_id', $id)
            ->whereIn('type', ['fiber_out', 'usage'])
            ->exists();

        if ($hasHistory) {
            return back()->with('error', 'Cannot delete fiber that has usage/out records');
        }

        DB::beginTransaction();
        try {
            FiberHistory::where('fiber_stock_id', $id)->delete();
            $stock->delete();
            DB::commit();
            return redirect()->route('admin.inventory.fiber.index')
                ->with('success', 'Fiber stock deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $stock = FiberStock::with(['vendor', 'usages.client', 'usages.technician'])->findOrFail($id);
        $histories = FiberHistory::where('fiber_stock_id', $id)->latest()->get();
        return view('admin.inventory.fiber.show', compact('stock', 'histories'));
    }

    public function usage()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $stocks = FiberStock::active()
            ->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->get();
        $clients = Client::select('id', 'name', 'client_id')->orderBy('name')->get();
        $technicians = User::whereHas('roles', function($q) { $q->where('name', 'Field Technician'); })->select('id', 'name')->orderBy('name')->get();
        $usages = FiberUsage::with(['fiberStock', 'client', 'technician'])
            ->when($_cid, fn($q) => $q->whereHas('fiberStock', fn($fq) => $fq->where('company_id', $_cid)))
            ->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.inventory.fiber.usage', compact('stocks', 'clients', 'technicians', 'usages'));
    }

    public function storeUsage(Request $request)
    {
        $request->validate([
            'fiber_stock_id' => 'required|exists:fiber_stocks,id',
            'used_meter'     => 'required|numeric|min:0.01',
            'used_date'      => 'required|date',
        ]);

        $stock = FiberStock::findOrFail($request->fiber_stock_id);

        if ($request->used_meter > $stock->remaining_meter) {
            return back()->with('error', "Cannot use {$request->used_meter}m. Only {$stock->remaining_meter}m remaining in {$stock->fiber_code}.")->withInput();
        }

        DB::beginTransaction();
        try {
            FiberUsage::create([
                'fiber_stock_id' => $stock->id,
                'used_meter'     => $request->used_meter,
                'client_id'      => $request->client_id,
                'technician_id'  => $request->technician_id,
                'note'           => $request->note,
                'used_date'      => $request->used_date,
            ]);

            $stock->remaining_meter -= $request->used_meter;
            if ($stock->remaining_meter <= 0) {
                $stock->status = 'finished';
            }
            $stock->save();

            $usageClient = $request->client_id ? Client::find($request->client_id) : null;
            $clientName  = $usageClient ? $usageClient->name : 'N/A';
            $clientInfo  = $usageClient ? $usageClient->name . ' (' . $usageClient->username . ')' : '';
            FiberHistory::create([
                'type'           => 'usage',
                'fiber_stock_id' => $stock->id,
                'meter'          => $request->used_meter,
                'details'        => "Used {$request->used_meter}m for client: {$clientName}",
                'date'           => $request->used_date,
            ]);

            DB::commit();

            try {
                $fiberType = $stock->fiber_type . ' ' . $stock->core . '-core';
                $techName = '';
                if ($request->technician_id) {
                    $tech = User::find($request->technician_id);
                    if ($tech) $techName = $tech->name;
                }
                (new \App\Services\TelegramService())->sendFiberOutNotification(
                    $fiberType,
                    (float) $request->used_meter,
                    'Client Usage' . ($request->note ? ' - ' . $request->note : ''),
                    auth()->user()->name ?? 'System',
                    $stock->brand_name ?? '',
                    $techName,
                    $stock->fiber_code ?? '',
                    $clientInfo ?? ''
                );
            } catch (\Throwable $e) {
            }

            return redirect()->route('admin.inventory.fiber.usage')
                ->with('success', "Fiber usage recorded: {$request->used_meter}m from {$stock->fiber_code}");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function history()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $histories = DB::table('fiber_histories')
            ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
            ->select('fiber_histories.*', 'fiber_stocks.fiber_code')
            ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
            ->latest('fiber_histories.created_at')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25);

        return view('admin.inventory.fiber.history', compact('histories'));
    }

    public function fiberInHistory()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $histories = DB::table('fiber_histories')
            ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
            ->leftJoin('vendors', 'fiber_stocks.vendor_id', '=', 'vendors.id')
            ->select(
                'fiber_histories.*',
                'fiber_stocks.fiber_code',
                'fiber_stocks.name as stock_name',
                'fiber_stocks.brand_name',
                'fiber_stocks.fiber_type',
                'fiber_stocks.core',
                'fiber_stocks.total_cost',
                'vendors.name as vendor_name'
            )
            ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
            ->whereIn('fiber_histories.type', ['stock_in', 'fiber_in'])
            ->latest('fiber_histories.created_at')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25);

        foreach ($histories as $h) {
            $h->invoice_number = null;
            if ($h->details && preg_match('/Invoice:\s*([^\s|]+)/', $h->details, $m)) {
                $h->invoice_number = trim($m[1]);
            }
        }

        $totalMeterIn = DB::table('fiber_histories')
            ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
            ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
            ->whereIn('fiber_histories.type', ['stock_in', 'fiber_in'])
            ->sum('fiber_histories.meter');

        return view('admin.inventory.fiber.in_history', compact('histories', 'totalMeterIn'));
    }


    public function combinedHistory()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $tab = request('tab', 'in');
        $inHistories = null;
        $outHistories = null;
        $totalMeterIn = 0;
        $totalMeterOut = 0;

        if ($tab === 'in') {
            $inHistories = DB::table('fiber_histories')
                ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
                ->select('fiber_histories.*','fiber_stocks.fiber_code','fiber_stocks.name as stock_name','fiber_stocks.brand_name','fiber_stocks.fiber_type','fiber_stocks.core')
                ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
                ->whereIn('fiber_histories.type', ['stock_in','fiber_in'])
                ->latest('fiber_histories.created_at')
                ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25);

            foreach ($inHistories as $h) {
                $h->invoice_number = null;
                if ($h->details && preg_match('/Invoice:\s*([^\s|]+)/', $h->details, $m)) $h->invoice_number = trim($m[1]);
            }

            $totalMeterIn = DB::table('fiber_histories')
                ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
                ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
                ->whereIn('fiber_histories.type', ['stock_in','fiber_in'])->sum('fiber_histories.meter');
        } else {
            $outHistories = DB::table('fiber_histories')
                ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
                ->select('fiber_histories.*','fiber_stocks.fiber_code','fiber_stocks.name as stock_name')
                ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
                ->whereIn('fiber_histories.type', ['fiber_out','usage'])
                ->latest('fiber_histories.created_at')
                ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25);

            foreach ($outHistories as $h) {
                $h->reason = '-'; $h->pppoe_id = '-'; $h->technician_name = '-';
                if ($h->details) {
                    if (preg_match('/Reason:\s*([^|]+)/', $h->details, $m)) $h->reason = trim($m[1]);
                    if (preg_match('/PPPoE:\s*([^|]+)/', $h->details, $m)) $h->pppoe_id = trim($m[1]);
                    if (preg_match('/Technician:\s*(.+)$/', $h->details, $m)) $h->technician_name = trim($m[1]);
                    if (preg_match('/^Used\s+[\d.]+m\s+for\s+client:\s*(.+)$/i', $h->details, $m)) { $h->reason = 'Client Usage'; $h->pppoe_id = trim($m[1]); }
                }
            }

            $totalMeterOut = DB::table('fiber_histories')
                ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
                ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
                ->whereIn('fiber_histories.type', ['fiber_out','usage'])->sum('fiber_histories.meter');
        }

        return view('admin.inventory.fiber.combined_history', compact('tab', 'inHistories', 'outHistories', 'totalMeterIn', 'totalMeterOut'));
    }

    public function fiberOut()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $stocks = FiberStock::active()
            ->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->get();
        $technicians = User::whereHas('roles', function($q) { $q->where('name', 'Field Technician'); })->select('id', 'name')->orderBy('name')->get();
        return view('admin.inventory.fiber.out', compact('stocks', 'technicians'));
    }

    public function searchClients(Request $request)
    {
        $search = trim($request->input('q', ''));
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $clients = \DB::table('clients')
            ->where(function($query) use ($search) {
                $query->where('name', 'ilike', '%' . $search . '%')
                      ->orWhere('username', 'ilike', '%' . $search . '%')
                      ->orWhere('client_id', 'ilike', '%' . $search . '%');
            })
            ->whereNull('deleted_at')
            ->select('id', 'name', 'username', 'client_id', 'phone')
            ->orderBy('name')
            ->limit(15)
            ->get();

        return response()->json($clients);
    }

    public function storeFiberOut(Request $request)
    {
        $request->validate([
            'fiber_stock_id' => 'required|exists:fiber_stocks,id',
            'out_meter'      => 'required|numeric|min:0.01',
            'out_date'       => 'required|date',
            'reason'         => 'required|string|max:255',
            'note'           => 'nullable|string|max:1000',
        ]);

        $stock = FiberStock::findOrFail($request->fiber_stock_id);

        if ($request->out_meter > $stock->remaining_meter) {
            return back()->with('error', "Cannot take out {$request->out_meter}m. Only {$stock->remaining_meter}m remaining in {$stock->fiber_code}.")->withInput();
        }

        DB::beginTransaction();
        try {
            $stock->remaining_meter -= $request->out_meter;
            if ($stock->remaining_meter <= 0) {
                $stock->status = 'finished';
            }
            $stock->save();

            $outClient  = $request->client_id ? Client::find($request->client_id) : null;
            $pppoeId    = $outClient ? $outClient->username : 'N/A';
            $clientInfo = $outClient ? $outClient->name . ' (' . $outClient->username . ')' : '';
            $techName = $request->technician_id ? optional(User::find($request->technician_id))->name : 'N/A';
            $assignedBy = auth()->user()->name ?? 'N/A';

            FiberHistory::create([
                'type'           => 'fiber_out',
                'fiber_stock_id' => $stock->id,
                'meter'          => $request->out_meter,
                'details'        => "Out: {$request->out_meter}m | Reason: {$request->reason} | PPPoE: {$pppoeId} | Technician: {$techName} | Assigned By: {$assignedBy}",
                'note'           => $request->note,
                'date'           => $request->out_date,
            ]);

            DB::commit();

            try {
                $fiberType = $stock->fiber_type . ' ' . $stock->core . '-core';
                (new \App\Services\TelegramService())->sendFiberOutNotification(
                    $fiberType,
                    (float) $request->out_meter,
                    $request->reason,
                    $assignedBy,
                    $stock->brand_name ?? '',
                    $techName !== 'N/A' ? $techName : '',
                    $stock->fiber_code ?? '',
                    $clientInfo ?? ''
                );
            } catch (\Throwable $e) {
            }

            return redirect()->route('admin.inventory.fiber.out')
                ->with('success', "Fiber out recorded: {$request->out_meter}m from {$stock->fiber_code}");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function fiberOutHistory()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $histories = DB::table('fiber_histories')
            ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
            ->select('fiber_histories.*', 'fiber_stocks.fiber_code', 'fiber_stocks.name as stock_name')
            ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
            ->whereIn('fiber_histories.type', ['fiber_out', 'usage'])
            ->latest('fiber_histories.created_at')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25);

        foreach ($histories as $h) {
            $h->reason = '-';
            $h->pppoe_id = '-';
            $h->technician_name = '-';
            if ($h->details) {
                if (preg_match('/Reason:\s*([^|]+)/', $h->details, $m)) {
                    $h->reason = trim($m[1]);
                }
                if (preg_match('/PPPoE:\s*([^|]+)/', $h->details, $m)) {
                    $h->pppoe_id = trim($m[1]);
                }
                if (preg_match('/Technician:\s*(.+)$/', $h->details, $m)) {
                    $h->technician_name = trim($m[1]);
                }
                if (preg_match('/^Used\s+[\d.]+m\s+for\s+client:\s*(.+)$/i', $h->details, $m)) {
                    $h->reason = 'Client Usage';
                    $h->pppoe_id = trim($m[1]);
                }
            }
        }

        $totalMeterOut = DB::table('fiber_histories')
            ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
            ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
            ->whereIn('fiber_histories.type', ['fiber_out', 'usage'])
            ->sum('fiber_histories.meter');

        return view('admin.inventory.fiber.out_history', compact('histories', 'totalMeterOut'));
    }

    public function editFiberOut($id)
    {
        $history = FiberHistory::whereIn('type', ['fiber_out', 'usage'])->findOrFail($id);

        $reason = '';
        $pppoe = '';
        $technician_name = '';
        $client_id = null;
        $technician_id = null;

        if ($history->details) {
            if (preg_match('/Reason:\s*([^|]+)/', $history->details, $m)) {
                $reason = trim($m[1]);
            }
            if (preg_match('/PPPoE:\s*([^|]+)/', $history->details, $m)) {
                $pppoe = trim($m[1]);
            }
            if (preg_match('/Technician:\s*(.+)$/', $history->details, $m)) {
                $technician_name = trim($m[1]);
            }
            if (preg_match('/^Used\s+[\d.]+m\s+for\s+client:\s*(.+)$/i', $history->details, $m)) {
                $reason = 'Client Usage';
                $pppoe = trim($m[1]);
            }
        }

        if ($pppoe && $pppoe != 'N/A') {
            $client = Client::where('username', $pppoe)->first();
            if ($client) {
                $client_id = $client->id;
            }
        }

        if ($technician_name && $technician_name != 'N/A') {
            $tech = User::where('name', $technician_name)->first();
            if ($tech) {
                $technician_id = $tech->id;
            }
        }

        $stocks = FiberStock::all();
        $selectedClient = $client_id ? Client::select('id', 'name', 'username', 'client_id', 'phone')->find($client_id) : null;
        $technicians = User::whereHas('roles', function($q) { $q->where('name', 'Field Technician'); })->select('id', 'name')->orderBy('name')->get();

        return view('admin.inventory.fiber.out_edit', compact(
            'history', 'stocks', 'selectedClient', 'technicians',
            'reason', 'client_id', 'technician_id'
        ));
    }

    public function updateFiberOut(Request $request, $id)
    {
        $request->validate([
            'fiber_stock_id' => 'required|exists:fiber_stocks,id',
            'out_meter'      => 'required|numeric|min:0.01',
            'out_date'       => 'required|date',
            'reason'         => 'required|string|max:255',
            'note'           => 'nullable|string|max:1000',
        ]);

        $history = FiberHistory::whereIn('type', ['fiber_out', 'usage'])->findOrFail($id);
        $stock = FiberStock::findOrFail($history->fiber_stock_id);

        DB::beginTransaction();
        try {
            $oldMeter = (float) $history->meter;
            $newMeter = (float) $request->out_meter;
            $meterDiff = $newMeter - $oldMeter;

            if ($meterDiff > 0 && $meterDiff > $stock->remaining_meter) {
                return back()->with('error', "Cannot increase by {$meterDiff}m. Only {$stock->remaining_meter}m remaining in {$stock->fiber_code}.")->withInput();
            }

            if ($request->fiber_stock_id != $history->fiber_stock_id) {
                $stock->remaining_meter += $oldMeter;
                if ($stock->remaining_meter > 0) {
                    $stock->status = 'active';
                }
                $stock->save();

                $newStock = FiberStock::findOrFail($request->fiber_stock_id);
                if ($newMeter > $newStock->remaining_meter) {
                    DB::rollback();
                    return back()->with('error', "Cannot take out {$newMeter}m. Only {$newStock->remaining_meter}m remaining in {$newStock->fiber_code}.")->withInput();
                }
                $newStock->remaining_meter -= $newMeter;
                if ($newStock->remaining_meter <= 0) {
                    $newStock->status = 'finished';
                } else {
                    $newStock->status = 'active';
                }
                $newStock->save();
            } else {
                $stock->remaining_meter -= $meterDiff;
                if ($stock->remaining_meter <= 0) {
                    $stock->status = 'finished';
                } else {
                    $stock->status = 'active';
                }
                $stock->save();
            }

            $outClient  = $request->client_id ? Client::find($request->client_id) : null;
            $pppoeId    = $outClient ? $outClient->username : 'N/A';
            $clientInfo = $outClient ? $outClient->name . ' (' . $outClient->username . ')' : '';
            $techName = $request->technician_id ? optional(User::find($request->technician_id))->name : 'N/A';
            $assignedBy = auth()->user()->name ?? 'N/A';

            $history->update([
                'fiber_stock_id' => $request->fiber_stock_id,
                'meter'          => $newMeter,
                'details'        => "Out: {$newMeter}m | Reason: {$request->reason} | PPPoE: {$pppoeId} | Technician: {$techName} | Assigned By: {$assignedBy}",
                'note'           => $request->note,
                'date'           => $request->out_date,
            ]);

            DB::commit();
            return redirect()->route('admin.inventory.fiber.out_history')
                ->with('success', 'Fiber out record updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function destroyFiberOut($id)
    {
        $history = FiberHistory::whereIn('type', ['fiber_out', 'usage'])->findOrFail($id);

        DB::beginTransaction();
        try {
            $stock = FiberStock::findOrFail($history->fiber_stock_id);
            $stock->remaining_meter += (float) $history->meter;
            if ($stock->remaining_meter > 0) {
                $stock->status = 'active';
            }
            $stock->save();

            $history->delete();

            DB::commit();
            return redirect()->route('admin.inventory.fiber.out_history')
                ->with('success', 'Fiber out record deleted and meters restored successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function analytics()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $totalStocks = FiberStock::when($_cid, fn($q) => $q->where('company_id', $_cid))->count();
        $activeStocks = FiberStock::when($_cid, fn($q) => $q->where('company_id', $_cid))->where('status', 'active')->count();
        $finishedStocks = FiberStock::when($_cid, fn($q) => $q->where('company_id', $_cid))->where('status', 'finished')->count();
        $totalMeter = FiberStock::when($_cid, fn($q) => $q->where('company_id', $_cid))->sum('total_meter');
        $usedMeter = FiberStock::when($_cid, fn($q) => $q->where('company_id', $_cid))->sum('total_meter') - FiberStock::when($_cid, fn($q) => $q->where('company_id', $_cid))->sum('remaining_meter');
        $remainingMeter = FiberStock::when($_cid, fn($q) => $q->where('company_id', $_cid))->sum('remaining_meter');

        $usageByTechnician = DB::table('fiber_usages')
            ->leftJoin('fiber_stocks', 'fiber_usages.fiber_stock_id', '=', 'fiber_stocks.id')
            ->leftJoin('users', 'fiber_usages.technician_id', '=', 'users.id')
            ->select('users.name as technician_name', DB::raw('SUM(fiber_usages.used_meter) as total_used'))
            ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
            ->whereNotNull('fiber_usages.technician_id')
            ->groupBy('users.name')
            ->orderByDesc('total_used')
            ->get();

        $usageByMonth = DB::table('fiber_usages')
            ->leftJoin('fiber_stocks', 'fiber_usages.fiber_stock_id', '=', 'fiber_stocks.id')
            ->select(DB::raw("TO_CHAR(used_date, 'YYYY-MM') as month"), DB::raw('SUM(fiber_usages.used_meter) as total_used'))
            ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return view('admin.inventory.fiber.analytics', compact(
            'totalStocks', 'activeStocks', 'finishedStocks',
            'totalMeter', 'usedMeter', 'remainingMeter',
            'usageByTechnician', 'usageByMonth'
        ));
    }
}
