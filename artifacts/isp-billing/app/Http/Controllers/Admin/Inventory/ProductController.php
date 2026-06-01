<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use App\Models\ProductSerial;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->get();
        return view('admin.inventory.products.index', compact('products'));
    }

    public function create()
    {
        $categories = ProductCategory::where('is_active', true)->get();
        return view('admin.inventory.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'code' => 'required|unique:products,code',
            'category_id' => 'required',
            'type' => 'required',
            'unit' => 'required',
            'initial_stock_quantity' => 'nullable|integer|min:0',
        ]);

        $productFields = $request->only([
            'name', 'code', 'category_id', 'type', 'unit', 'brand', 'model',
            'description', 'purchase_price', 'selling_price', 'alert_quantity',
            'warranty_months', 'barcode_prefix', 'has_serial'
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create($productFields);

            $stockInQty = 0;

            if (!$request->has_serial && $request->initial_stock_quantity > 0) {
                $stockInQty = (int) $request->initial_stock_quantity;
            }

            if ($request->has('serials') && $request->has_serial && $request->unit === 'pcs') {
                $serials = array_filter($request->serials, fn($s) => !empty(trim($s)));
                $serials = array_map('trim', $serials);

                $uniqueSerials = array_unique(array_map('strtolower', $serials));
                if (count($uniqueSerials) !== count($serials)) {
                    throw new \Exception('Duplicate serial numbers found in your submission.');
                }

                $existingCount = ProductSerial::whereIn(DB::raw('LOWER(serial_number)'), $uniqueSerials)->count();
                if ($existingCount > 0) {
                    throw new \Exception('One or more serial numbers already exist in the database.');
                }

                $warrantyMonths = (int)$request->warranty_months;
                foreach ($serials as $serial) {
                    ProductSerial::create([
                        'product_id' => $product->id,
                        'serial_number' => $serial,
                        'status' => 'in_stock',
                        'warranty_start' => now(),
                        'warranty_end' => $warrantyMonths > 0 ? now()->addMonths($warrantyMonths) : null,
                    ]);
                }

                $stockInQty = count($serials);
            }

            if ($stockInQty > 0) {
                DB::table('stocks')->insert([
                    'company_id' => auth()->user()->company_id ?? null,
                    'product_id' => $product->id,
                    'quantity' => $stockInQty,
                    'type' => 'in',
                    'reference_type' => 'product_create',
                    'reference_id' => $product->id,
                    'description' => 'Initial stock added with product creation',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $product->update(['stock_quantity' => $stockInQty]);
            }

            DB::commit();
            return redirect()->route('admin.inventory.products.index')
                ->with('success', 'Product created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }

    public function checkSerialDuplicate(Request $request)
    {
        $serial = trim($request->input('serial', ''));
        if (empty($serial)) {
            return response()->json(['exists' => false]);
        }
        $exists = ProductSerial::whereRaw('LOWER(serial_number) = ?', [strtolower($serial)])->exists();
        return response()->json(['exists' => $exists]);
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = ProductCategory::where('is_active', true)->get();

        return view('admin.inventory.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'category_id' => 'required',
            'type' => 'required',
            'unit' => 'required'
        ]);

        $product->update($request->only([
            'name', 'code', 'category_id', 'type', 'unit', 'brand', 'model',
            'description', 'purchase_price', 'selling_price', 'alert_quantity',
            'warranty_months', 'barcode_prefix', 'has_serial'
        ]));

        return redirect()->route('admin.inventory.products.index')
            ->with('success', 'Product updated successfully');
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return back()->with('success', 'Product deleted');
    }


    public function combinedHistory()
    {
        $tab = request('tab', 'in');
        $inHistories = null;
        $outHistories = null;
        $instrumentsMap = [];
        $clientNameMap = [];

        if ($tab === 'in') {
            $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
            $stockRecords = DB::table('stocks')
                ->leftJoin('products', 'stocks.product_id', '=', 'products.id')
                ->leftJoin('users', 'stocks.created_by', '=', 'users.id')
                ->when($_cid, fn($q) => $q->where('stocks.company_id', $_cid))
                ->where('stocks.type', 'in')
                ->select('stocks.id','stocks.created_at','products.name as product_name','products.code as product_code','products.unit as product_unit','products.brand as product_brand','stocks.quantity','stocks.reference_type','stocks.reference_id','stocks.description','users.name as created_by_name')
                ->orderBy('stocks.created_at', 'desc')
                ->get()
                ->map(function ($row) { $row->source = 'stock'; $row->products_detail = null; return $row; });

            $instrumentGroups = DB::table('instruments')
                ->when($_cid, fn($q) => $q->where('company_id', $_cid))
                ->select('invoice_number','category','sub_category','brand','model','purchaser',DB::raw('SUM(COALESCE(quantity, 1)) as total_qty'),DB::raw('COUNT(*) as item_count'),DB::raw("STRING_AGG(DISTINCT serial_number, ', ' ORDER BY serial_number) as serials"),DB::raw('MIN(created_at) as created_at'),DB::raw('SUM(COALESCE(purchase_price, 0) * COALESCE(quantity, 1)) as total_price'),DB::raw('MIN(notes) as notes_sample'))
                ->groupBy('invoice_number','category','sub_category','brand','model','purchaser')
                ->orderBy(DB::raw('MIN(created_at)'), 'desc')
                ->get()
                ->map(function ($row) {
                    $productName = $row->category;
                    if ($row->sub_category) $productName .= ' - ' . $row->sub_category;
                    $detail = $productName;
                    if ($row->brand) $detail .= ' | ' . $row->brand;
                    if ($row->model) $detail .= ' ' . $row->model;
                    $detail .= ' (Qty: ' . $row->total_qty . ')';
                    if ($row->serials) $detail .= ' | Serials: ' . $row->serials;
                    return (object) ['id'=>null,'created_at'=>$row->created_at,'product_name'=>$productName,'product_code'=>$row->invoice_number??'-','product_unit'=>'pcs','product_brand'=>$row->brand,'quantity'=>$row->total_qty,'reference_type'=>(!empty($row->invoice_number)?'instrument':(str_contains($row->notes_sample??'','Returned from client')?'restock':(str_contains($row->notes_sample??'','Restocked')?'restock':'instrument'))),'reference_id'=>null,'description'=>$detail.(!empty($row->notes_sample)&&str_contains($row->notes_sample,'Returned from client')?' | Source: '.preg_replace('/\..*/','',$row->notes_sample):''),'created_by_name'=>$row->purchaser??($row->notes_sample&&str_contains($row->notes_sample,'Restocked')?'Restock':'-'),'source'=>'instrument','products_detail'=>$detail,'total_price'=>$row->total_price];
                });

            $allRecords = $stockRecords->concat($instrumentGroups)->sortByDesc('created_at')->values();
            $page = request()->get('page', 1);
            $perPage = 25;
            $total = $allRecords->count();
            $items = $allRecords->slice(($page - 1) * $perPage, $perPage)->values();
            $inHistories = new \Illuminate\Pagination\LengthAwarePaginator($items, $total, $perPage, $page, ['path'=>request()->url(),'query'=>request()->query()]);
        } else {
            $outHistories = DB::table('instrument_out_logs')
                ->leftJoin('users', 'instrument_out_logs.created_by', '=', 'users.id')
                ->select('instrument_out_logs.*','users.name as created_by_name')
                ->when($_cid, fn($q) => $q->where('instrument_out_logs.company_id', $_cid))
                ->orderBy('instrument_out_logs.created_at', 'desc')
                ->paginate(25);

            $allInstrumentIds = [];
            foreach ($outHistories as $h) {
                if ($h->instrument_ids) { $ids = json_decode($h->instrument_ids, true); if (is_array($ids)) $allInstrumentIds = array_merge($allInstrumentIds, array_map('intval', $ids)); }
            }
            $allInstrumentIds = array_unique($allInstrumentIds);
            if (!empty($allInstrumentIds)) {
                $instruments = DB::table('instruments')->whereIn('id', $allInstrumentIds)->select('id','name','model','brand','serial_number','category','sub_category')->get();
                foreach ($instruments as $inst) $instrumentsMap[$inst->id] = $inst;
            }
            $numericIssuedIds = [];
            foreach ($outHistories as $h) { if ($h->issued_to && is_numeric($h->issued_to)) $numericIssuedIds[] = (int)$h->issued_to; }
            if (!empty($numericIssuedIds)) {
                $clients = \App\Models\Client::whereIn('id', array_unique($numericIssuedIds))->select('id','name','username')->get();
                foreach ($clients as $c) $clientNameMap[$c->id] = $c->name . ' (' . $c->username . ')';
            }
        }

        return view('admin.inventory.products.combined_history', compact('tab', 'inHistories', 'outHistories', 'instrumentsMap', 'clientNameMap'));
    }

    public function inHistory()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $stockRecords = DB::table('stocks')
            ->leftJoin('products', 'stocks.product_id', '=', 'products.id')
            ->leftJoin('users', 'stocks.created_by', '=', 'users.id')
            ->when($_cid, fn($q) => $q->where('stocks.company_id', $_cid))
            ->where('stocks.type', 'in')
            ->select(
                'stocks.id',
                'stocks.created_at',
                'products.name as product_name',
                'products.code as product_code',
                'products.unit as product_unit',
                'products.brand as product_brand',
                'stocks.quantity',
                'stocks.reference_type',
                'stocks.reference_id',
                'stocks.description',
                'users.name as created_by_name'
            )
            ->orderBy('stocks.created_at', 'desc')
            ->get()
            ->map(function ($row) {
                $row->source = 'stock';
                $row->products_detail = null;
                return $row;
            });

        $instrumentGroups = DB::table('instruments')
            ->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->select(
                'invoice_number',
                'category',
                'sub_category',
                'brand',
                'model',
                'purchaser',
                DB::raw('SUM(COALESCE(quantity, 1)) as total_qty'),
                DB::raw('COUNT(*) as item_count'),
                DB::raw("STRING_AGG(DISTINCT serial_number, ', ' ORDER BY serial_number) as serials"),
                DB::raw('MIN(created_at) as created_at'),
                DB::raw('SUM(COALESCE(purchase_price, 0) * COALESCE(quantity, 1)) as total_price'),
                DB::raw('MIN(notes) as notes_sample')
            )
            ->groupBy('invoice_number', 'category', 'sub_category', 'brand', 'model', 'purchaser')
            ->orderBy(DB::raw('MIN(created_at)'), 'desc')
            ->get()
            ->map(function ($row) {
                $productName = $row->category;
                if ($row->sub_category) $productName .= ' - ' . $row->sub_category;

                $detail = $productName;
                if ($row->brand) $detail .= ' | ' . $row->brand;
                if ($row->model) $detail .= ' ' . $row->model;
                $detail .= ' (Qty: ' . $row->total_qty . ')';
                if ($row->serials) $detail .= ' | Serials: ' . $row->serials;

                return (object) [
                    'id' => null,
                    'created_at' => $row->created_at,
                    'product_name' => $productName,
                    'product_code' => $row->invoice_number ?? '-',
                    'product_unit' => 'pcs',
                    'product_brand' => $row->brand,
                    'quantity' => $row->total_qty,
                    'reference_type' => (!empty($row->invoice_number) ? 'instrument' : (str_contains($row->notes_sample ?? '', 'Returned from client') ? 'restock' : (str_contains($row->notes_sample ?? '', 'Restocked') ? 'restock' : 'instrument'))),
                    'reference_id' => null,
                    'description' => $detail . (!empty($row->notes_sample) && str_contains($row->notes_sample, 'Returned from client') ? ' | Source: ' . preg_replace('/\..*/', '', $row->notes_sample) : ''),
                    'created_by_name' => $row->purchaser ?? ($row->notes_sample && str_contains($row->notes_sample, 'Restocked') ? 'Restock' : '-'),
                    'source' => 'instrument',
                    'products_detail' => $detail,
                    'total_price' => $row->total_price,
                ];
            });

        $allRecords = $stockRecords->concat($instrumentGroups)
            ->sortByDesc('created_at')
            ->values();

        $page = request()->get('page', 1);
        $perPage = 25;
        $total = $allRecords->count();
        $items = $allRecords->slice(($page - 1) * $perPage, $perPage)->values();
        $histories = new \Illuminate\Pagination\LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        return view('admin.inventory.products.in_history', compact('histories'));
    }

    public function productOut()
    {
        $instruments = \App\Models\Instrument::where('status', 'available')->orderBy('category')->get();

        $catSerialMap = \App\Models\InstrumentCategory::whereNull('parent_id')
            ->pluck('has_serial', 'name')
            ->mapWithKeys(fn($v, $k) => [$k => (bool) $v])
            ->toArray();

        $stockSummary = $instruments->groupBy(function($item) {
            return $item->category . '||' . ($item->sub_category ?? '') . '||' . ($item->brand ?? '') . '||' . ($item->model ?? '');
        })->map(function($items, $key) use ($catSerialMap) {
            $parts = explode('||', $key);
            $catName = $parts[0];
            $hasSerial = $catSerialMap[$catName] ?? true;

            if ($hasSerial) {
                $stock = $items->count();
                $serials = $items->map(fn($i) => ['id' => $i->id, 'serial' => $i->serial_number ?? 'No Serial'])->values();
            } else {
                $stock = $items->sum('quantity');
                $serials = $items->map(fn($i) => ['id' => $i->id, 'serial' => 'No Serial', 'quantity' => $i->quantity])->values();
            }

            return [
                'key' => $key,
                'category' => $catName,
                'type' => $parts[1] ?? '',
                'brand' => $parts[2] ?? '',
                'model' => $parts[3] ?? '',
                'stock' => $stock,
                'has_serial' => $hasSerial,
                'serials' => $serials,
            ];
        })->values();

        $technicians = User::whereHas('roles', function($q) {
            $q->where('name', 'Field Technician');
        })->select('id', 'name')->orderBy('name')->get();

        return view('admin.inventory.products.out', compact('stockSummary', 'technicians'));
    }

    public function getAvailableSerials(Request $request)
    {
        $key = $request->input('key', '');
        $parts = explode('||', $key);
        $query = \App\Models\Instrument::where('status', 'available')
            ->where('category', $parts[0] ?? '');

        if (!empty($parts[1])) $query->where('sub_category', $parts[1]);
        else $query->whereNull('sub_category');

        if (!empty($parts[2])) $query->where('brand', $parts[2]);
        else $query->whereNull('brand');

        if (!empty($parts[3])) $query->where('model', $parts[3]);
        else $query->whereNull('model');

        $serials = $query->get(['id', 'serial_number'])->map(fn($i) => ['id' => $i->id, 'serial' => $i->serial_number ?? 'No Serial']);
        return response()->json($serials);
    }

    public function searchClients(Request $request)
    {
        $search = trim($request->input('q', ''));
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $clients = DB::table('clients')
            ->where(function($query) use ($search) {
                $query->where('name', 'ilike', '%' . $search . '%')
                      ->orWhere('username', 'ilike', '%' . $search . '%')
                      ->orWhere('client_id', 'ilike', '%' . $search . '%');
            })
            ->whereNull('deleted_at')
            ->select('id', 'name', 'username', 'client_id', 'phone', 'address')
            ->orderBy('name')
            ->limit(15)
            ->get();

        return response()->json($clients);
    }

    public function storeProductOut(Request $request)
    {
        $request->validate([
            'reason'           => 'required|string|max:255',
            'out_date'         => 'required|date',
            'issued_to'        => 'nullable|string|max:255',
            'items'            => 'required|array|min:1',
            'items.*.type'     => 'required|in:bulk,serial',
        ]);

        DB::beginTransaction();
        try {
            $issuedToLabel = $request->issued_to;
            $issuedClientId = null;
            if ($issuedToLabel && is_numeric($issuedToLabel)) {
                $issuedClient = \App\Models\Client::find($issuedToLabel);
                if ($issuedClient) {
                    $issuedClientId = $issuedClient->id;
                    $issuedToLabel = $issuedClient->name . ' (' . $issuedClient->username . ')';
                }
            }

            $totalOutQty = 0;
            $processedCount = 0;
            $allLogIds = [];
            $allProductInfos = [];
            $allSerials = [];
            $description = 'Reason: ' . $request->reason
                . ($issuedToLabel ? ' | Issued To: ' . $issuedToLabel : '')
                . ($request->technician_id ? ' | Technician ID: ' . $request->technician_id : '')
                . ($request->remarks ? ' | Remarks: ' . $request->remarks : '');

            $bulkTotals = [];
            foreach ($request->items as $item) {
                if (($item['type'] ?? '') === 'bulk') {
                    $bid = $item['bulk_out_id'] ?? null;
                    if ($bid) {
                        $bulkTotals[$bid] = ($bulkTotals[$bid] ?? 0) + (int)($item['bulk_out_qty'] ?? 0);
                    }
                }
            }

            foreach ($request->items as $item) {
                if (($item['type'] ?? '') === 'bulk') {
                    if (empty($item['bulk_out_id']) || empty($item['bulk_out_qty'])) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Invalid bulk product data. Please refresh and try again.');
                    }

                    $instrument = \App\Models\Instrument::where('id', $item['bulk_out_id'])
                        ->where('status', 'available')
                        ->first();

                    if (!$instrument) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'A product is no longer available in stock. Please refresh and try again.');
                    }

                    $outQty = (int) ($item['bulk_out_qty'] ?? 0);
                    if ($outQty <= 0 || $outQty > $instrument->quantity) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Invalid quantity for ' . ($instrument->category ?? 'product') . '. Available: ' . $instrument->quantity);
                    }

                    $outInstrument = $instrument->replicate();
                    $outInstrument->quantity = $outQty;
                    $outInstrument->status = 'in_use';
                    $outInstrument->assigned_to = $issuedToLabel;
                    $outInstrument->client_id = $issuedClientId;
                    $outInstrument->save();

                    $instrument->quantity -= $outQty;
                    if ($instrument->quantity <= 0) {
                        $instrument->delete();
                    } else {
                        $instrument->save();
                    }

                    $totalOutQty += $outQty;
                    $allLogIds[] = $outInstrument->id;
                    $productInfo = ($instrument->category ?? '') . ($instrument->sub_category ? ' - '.$instrument->sub_category : '') . ($instrument->brand ? ' | '.$instrument->brand : '') . ($instrument->model ? ' '.$instrument->model : '');
                    $allProductInfos[] = $productInfo . ' x' . $outQty;

                    $processedCount++;

                } else {
                    $instrumentIds = array_unique($item['instrument_ids'] ?? []);
                    if (empty($instrumentIds)) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'No serials selected for a serial product. Please try again.');
                    }

                    $instruments = \App\Models\Instrument::whereIn('id', $instrumentIds)
                        ->where('status', 'available')
                        ->get();

                    if ($instruments->count() !== count($instrumentIds)) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Some selected serial items are no longer available in stock.');
                    }

                    foreach ($instruments as $inst) {
                        $inst->update([
                            'status' => 'in_use',
                            'assigned_to' => $issuedToLabel,
                            'client_id'   => $issuedClientId,
                        ]);
                    }

                    $totalOutQty += count($instrumentIds);
                    $allLogIds = array_merge($allLogIds, $instrumentIds);
                    $firstInst = $instruments->first();
                    $productInfo = ($firstInst->category ?? '') . ($firstInst->sub_category ? ' - '.$firstInst->sub_category : '') . ($firstInst->brand ? ' | '.$firstInst->brand : '') . ($firstInst->model ? ' '.$firstInst->model : '');
                    $serialList = $instruments->pluck('serial_number')->filter()->implode(', ');
                    if ($serialList) {
                        $allSerials[] = $serialList;
                    }
                    $allProductInfos[] = $productInfo . ' [' . ($serialList ?: count($instrumentIds) . ' pcs') . ']';
                    $processedCount++;
                }
            }

            $chargeType    = $request->input('charge_type', 'free');
            $chargeAmount  = (float) $request->input('charge_amount', 0);
            $paymentStatus = $request->input('payment_status', 'na');

            DB::table('instrument_out_logs')->insert([
                'company_id'     => auth()->user()->company_id ?? null,
                'instrument_ids' => json_encode($allLogIds),
                'quantity'       => $totalOutQty,
                'reason'         => $request->reason,
                'issued_to'      => $issuedToLabel,
                'client_id'      => $issuedClientId,
                'technician_id'  => $request->technician_id,
                'remarks'        => $request->remarks,
                'out_date'       => $request->out_date,
                'description'    => $description . ' | Products: ' . implode('; ', $allProductInfos),
                'charge_type'    => $chargeType,
                'charge_amount'  => $chargeAmount,
                'payment_status' => $paymentStatus,
                'created_by'     => auth()->id(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Auto-create service charge record if applicable
            if ($chargeAmount > 0 && $issuedClientId) {
                $chargeLabels = [
                    'monthly_rental'     => 'Monthly Equipment Rental',
                    'one_time_sale'      => 'Equipment Sale',
                    'refundable_deposit' => 'Equipment Deposit',
                ];
                $chargeLabel = $chargeLabels[$chargeType] ?? 'Equipment Charge';
                $productDesc = 'Equipment: ' . implode(', ', $allProductInfos);

                if ($chargeType === 'monthly_rental') {
                    // Set/update client's monthly ONU rental charge
                    \App\Models\Client::where('id', $issuedClientId)
                        ->update(['onu_monthly_charge' => $chargeAmount]);
                }

                if ($paymentStatus === 'paid') {
                    \App\Models\ClientServiceCharge::create([
                        'client_id'   => $issuedClientId,
                        'charge_type' => $chargeLabel,
                        'amount'      => $chargeAmount,
                        'description' => $productDesc,
                        'charge_date' => $request->out_date,
                        'paid_by'     => auth()->id(),
                    ]);
                } elseif ($paymentStatus === 'due') {
                    \DB::table('client_service_charges')->insert([
                        'client_id'   => $issuedClientId,
                        'charge_type' => $chargeLabel . ' (Due)',
                        'amount'      => $chargeAmount,
                        'description' => $productDesc,
                        'charge_date' => $request->out_date,
                        'paid_by'     => null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            DB::commit();

            try {
                $productSummary = implode(",\n", $allProductInfos);
                $serialSummary = implode(', ', $allSerials);
                $technicianName = '';
                if ($request->technician_id) {
                    $tech = \App\Models\User::find($request->technician_id);
                    if ($tech) $technicianName = $tech->name;
                }
                (new \App\Services\TelegramService())->sendProductOutNotification(
                    $productSummary,
                    $totalOutQty,
                    $request->reason,
                    $issuedToLabel ?? 'N/A',
                    auth()->user()->name ?? 'System',
                    $serialSummary,
                    $technicianName
                );
            } catch (\Throwable $e) {
            }

            return redirect()->route('admin.inventory.products.out')
                ->with('success', $totalOutQty . ' item(s) across ' . $processedCount . ' product(s) marked as out successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to record product out: ' . $e->getMessage());
        }
    }

    public function outHistory()
    {
        $histories = DB::table('instrument_out_logs')
            ->leftJoin('users', 'instrument_out_logs.created_by', '=', 'users.id')
            ->select(
                'instrument_out_logs.*',
                'users.name as created_by_name'
            )
            ->when(isset($_cid_oh) ? $_cid_oh : ((auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null), fn($q,$id) => $q->where('instrument_out_logs.company_id', $id))
            ->orderBy('instrument_out_logs.created_at', 'desc')
            ->paginate(25);

        $allInstrumentIds = [];
        foreach ($histories as $h) {
            if ($h->instrument_ids) {
                $ids = json_decode($h->instrument_ids, true);
                if (is_array($ids)) {
                    $allInstrumentIds = array_merge($allInstrumentIds, array_map('intval', $ids));
                }
            }
        }
        $allInstrumentIds = array_unique($allInstrumentIds);

        $instrumentsMap = [];
        if (!empty($allInstrumentIds)) {
            $instruments = DB::table('instruments')
                ->whereIn('id', $allInstrumentIds)
                ->select('id', 'name', 'model', 'brand', 'serial_number', 'category', 'sub_category')
                ->get();
            foreach ($instruments as $inst) {
                $instrumentsMap[$inst->id] = $inst;
            }
        }

        $numericIssuedIds = [];
        foreach ($histories as $h) {
            if ($h->issued_to && is_numeric($h->issued_to)) {
                $numericIssuedIds[] = (int) $h->issued_to;
            }
        }
        $clientNameMap = [];
        if (!empty($numericIssuedIds)) {
            $clients = \App\Models\Client::whereIn('id', array_unique($numericIssuedIds))
                ->select('id', 'name', 'username')
                ->get();
            foreach ($clients as $c) {
                $clientNameMap[$c->id] = $c->name . ' (' . $c->username . ')';
            }
        }

        return view('admin.inventory.products.out_history', compact('histories', 'instrumentsMap', 'clientNameMap'));
    }

    public function editOutHistory($id)
    {
        $cid4 = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $log = DB::table('instrument_out_logs')->where('id', $id)->when($cid4, fn($q) => $q->where('company_id', $cid4))->first();
        if (!$log) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        $clientId = null;
        $clientLabel = $log->issued_to ?? '';
        if ($clientLabel && is_numeric($clientLabel)) {
            $client = \App\Models\Client::find((int)$clientLabel);
            if ($client) {
                $clientId = $client->id;
                $clientLabel = $client->name . ' (' . $client->username . ')';
            }
        } elseif ($clientLabel && preg_match('/\(([^)]+)\)$/', $clientLabel, $m)) {
            $client = \App\Models\Client::where('username', $m[1])->first();
            if ($client) {
                $clientId = $client->id;
            }
        }

        $technicians = User::whereHas('roles', function($q) {
            $q->where('name', 'Field Technician');
        })->select('id', 'name')->orderBy('name')->get();

        $instrumentIds = json_decode($log->instrument_ids ?? '[]', true) ?: [];
        $products = [];
        if (!empty($instrumentIds)) {
            $instruments = \App\Models\Instrument::whereIn('id', $instrumentIds)
                ->select('id', 'category', 'sub_category', 'brand', 'model', 'serial_number', 'quantity', 'status')
                ->get();
            foreach ($instruments as $inst) {
                $label = $inst->category ?? '';
                if ($inst->sub_category) $label .= ' - ' . $inst->sub_category;
                $modelBrand = trim(($inst->brand ?? '') . ' ' . ($inst->model ?? ''));
                $products[] = [
                    'id' => $inst->id,
                    'label' => $label,
                    'model_brand' => $modelBrand,
                    'serial_number' => $inst->serial_number,
                    'quantity' => $inst->quantity,
                    'has_serial' => !empty($inst->serial_number),
                ];
            }
        }

        return response()->json([
            'log' => $log,
            'client_id' => $clientId,
            'client_label' => $clientLabel,
            'technicians' => $technicians,
            'products' => $products,
        ]);
    }

    public function updateOutHistory(Request $request, $id)
    {
        $request->validate([
            'reason'    => 'required|string|max:255',
            'out_date'  => 'required|date',
            'issued_to' => 'nullable|string|max:255',
            'removed_product_ids' => 'nullable|array',
            'removed_product_ids.*' => 'integer',
        ]);

        $log = DB::table('instrument_out_logs')->where('id', $id)->when((auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null, fn($q, $c) => $q->where('company_id', $c))->first();
        if (!$log) {
            return back()->with('error', 'Record not found.');
        }

        DB::beginTransaction();
        try {
            $newIssuedTo = $request->issued_to;
            if ($newIssuedTo && is_numeric($newIssuedTo)) {
                $client = \App\Models\Client::find($newIssuedTo);
                if ($client) {
                    $newIssuedTo = $client->name . ' (' . $client->username . ')';
                }
            }

            $oldIssuedTo = $log->issued_to;
            $currentIds = json_decode($log->instrument_ids ?? '[]', true) ?: [];
            $removedIds = $request->removed_product_ids ?? [];
            $removedIds = array_map('intval', $removedIds);

            if (!empty($removedIds)) {
                $this->reverseInstruments($removedIds);
                $currentIds = array_values(array_diff($currentIds, $removedIds));
            }

            if (empty($currentIds)) {
                DB::table('instrument_out_logs')->where('id', $id)->delete();
                DB::commit();
                return redirect()->route('admin.inventory.products.out_history')
                    ->with('success', 'All products removed. Out record deleted.');
            }

            $newQty = 0;
            $remainingInstruments = \App\Models\Instrument::whereIn('id', $currentIds)->get();
            foreach ($remainingInstruments as $inst) {
                if ($inst->serial_number) {
                    $newQty += 1;
                } else {
                    $newQty += $inst->quantity;
                }
            }

            DB::table('instrument_out_logs')->where('id', $id)->update([
                'reason'         => $request->reason,
                'issued_to'      => $newIssuedTo,
                'technician_id'  => $request->technician_id,
                'remarks'        => $request->remarks,
                'out_date'       => $request->out_date,
                'instrument_ids' => json_encode(array_values($currentIds)),
                'quantity'       => $newQty,
                'updated_at'     => now(),
            ]);

            if ($oldIssuedTo !== $newIssuedTo) {
                if (!empty($currentIds)) {
                    \App\Models\Instrument::whereIn('id', $currentIds)
                        ->where('status', 'in_use')
                        ->update(['assigned_to' => $newIssuedTo]);
                }
            }

            DB::commit();
            return redirect()->route('admin.inventory.products.out_history')
                ->with('success', 'Product out record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function deleteOutHistory($id)
    {
        $log = DB::table('instrument_out_logs')->where('id', $id)->when((auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null, fn($q, $c) => $q->where('company_id', $c))->first();
        if (!$log) {
            return back()->with('error', 'Record not found.');
        }

        DB::beginTransaction();
        try {
            $instrumentIds = json_decode($log->instrument_ids ?? '[]', true) ?: [];

            if (!empty($instrumentIds)) {
                $this->reverseInstruments($instrumentIds);
            }

            DB::table('instrument_out_logs')->where('id', $id)->delete();

            DB::commit();
            return redirect()->route('admin.inventory.products.out_history')
                ->with('success', 'Product out record deleted and stock restored successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    private function reverseInstruments(array $instrumentIds)
    {
        \App\Models\Instrument::whereIn('id', $instrumentIds)->update([
            'status' => 'available',
            'assigned_to' => null,
        ]);
    }
}
