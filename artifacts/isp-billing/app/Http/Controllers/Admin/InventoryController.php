<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\StockMovement;
use App\Models\DamagedItem;
use App\Models\Purchase;
use App\Models\Vendor;
use App\Services\InventoryService;
use App\Models\Client;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function serials(?Product $product = null)
    {
        $query = ProductSerial::with(['product', 'client', 'purchase']);
        if ($product) {
            $query->where('product_id', $product->id);
        }
        $serials = $query->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $products = Product::where('has_serial', true)->get();
        return view('admin.inventory.serials', compact('serials', 'products', 'product'));
    }

    public function storeSerial(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'serial_number' => 'required|string|unique:product_serials',
            'mac_address' => 'nullable|string',
            'purchase_id' => 'nullable|exists:purchases,id'
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $purchase = $validated['purchase_id'] ? Purchase::find($validated['purchase_id']) : null;

        $this->inventoryService->addSerial(
            $product,
            $validated['serial_number'],
            $validated['mac_address'],
            $purchase
        );

        $this->inventoryService->recordStockIn($product, 1, $validated['serial_number'], 'Serial added');

        return redirect()->back()->with('success', 'Serial number added successfully.');
    }

    public function assignSerial(Request $request, ProductSerial $serial)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id'
        ]);

        $this->inventoryService->assignSerialToClient($serial, $validated['client_id']);

        return redirect()->back()->with('success', 'Serial assigned to client.');
    }

    public function stockMovements(?Product $product = null)
    {
        $query = StockMovement::with(['product', 'creator']);
        if ($product) {
            $query->where('product_id', $product->id);
        }
        $movements = $query->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.inventory.stock-movements', compact('movements', 'product'));
    }

    public function storeStockMovement(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($validated['type'] === 'in') {
            $this->inventoryService->recordStockIn($product, $validated['quantity'], $validated['reference'], $validated['description']);
        } elseif ($validated['type'] === 'out') {
            $result = $this->inventoryService->recordStockOut($product, $validated['quantity'], $validated['reference'], $validated['description']);
            if (!$result) {
                return redirect()->back()->with('error', 'Insufficient stock.');
            }
        }

        return redirect()->back()->with('success', 'Stock movement recorded.');
    }

    public function damagedItems()
    {
        $items = DamagedItem::with(['product', 'serial', 'reporter'])->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.inventory.damaged-items', compact('items'));
    }

    public function reportDamage(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'serial_id' => 'nullable|exists:product_serials,id',
            'quantity' => 'required|integer|min:1',
            'damage_reason' => 'required|string'
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $serial = $validated['serial_id'] ? ProductSerial::find($validated['serial_id']) : null;

        $this->inventoryService->reportDamage($product, $validated['quantity'], $validated['damage_reason'], $serial);

        return redirect()->back()->with('success', 'Damage reported successfully.');
    }

    public function warrantyReport()
    {
        $serials = \App\Models\ProductSerial::with(['product', 'client'])
            ->whereNotNull('warranty_end')
            ->orderBy('warranty_end')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);
        return view('admin.inventory.warranty-report', compact('serials'));
    }

    public function vendorPurchaseHistory(Vendor $vendor)
    {
        $purchases = $vendor->purchases()->with('items.product')->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $ledger = $vendor->ledgerEntries()->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.inventory.vendor-history', compact('vendor', 'purchases', 'ledger'));
    }

    public function stockReport()
    {
        $report = $this->inventoryService->getStockReport();
        return view('admin.inventory.stock-report', compact('report'));
    }

    public function categories()
    {
        $categories = \App\Models\ProductCategory::orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.inventory.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        \App\Models\ProductCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => true,
        ]);
        return back()->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $category = \App\Models\ProductCategory::findOrFail($id);
        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'Category updated successfully.');
    }

    public function destroyCategory($id)
    {
        $category = \App\Models\ProductCategory::findOrFail($id);
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Cannot delete category with existing products. Remove products first.');
        }
        $category->delete();
        return back()->with('success', 'Category deleted successfully.');
    }

    public function inactiveClientHistory(Request $request)
    {
        // Inactive clients (status = inactive, excluding deleted)
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && $authUser->isSuperAdmin();
        $companyId = $authUser ? $authUser->company_id : 0;

        $query = $isSuperAdmin
            ? Client::withoutGlobalScopes()->where('status', 'inactive')->with(['zone', 'package'])
            : Client::withoutGlobalScopes()->where('status', 'inactive')->where('company_id', $companyId)->with(['zone', 'package']);

        if ($request->filled('search')) {
            $search = $request->search;

            // Product/instrument search — handles both client_id FK and assigned_to text pattern
            $matchingInstruments = \App\Models\Instrument::withoutGlobalScopes()
                ->where('status', 'in_use')
                ->where(function($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('category', 'ilike', "%{$search}%")
                      ->orWhere('sub_category', 'ilike', "%{$search}%")
                      ->orWhere('brand', 'ilike', "%{$search}%")
                      ->orWhere('model', 'ilike', "%{$search}%")
                      ->orWhere('serial_number', 'ilike', "%{$search}%");
                })
                ->get(['id', 'client_id', 'assigned_to']);

            $instrumentClientIds = collect();
            foreach ($matchingInstruments as $inst) {
                if ($inst->client_id) {
                    $instrumentClientIds->push($inst->client_id);
                } elseif ($inst->assigned_to) {
                    // Extract username from "Name (username)" pattern
                    if (preg_match('/\(([^)]+)\)$/', $inst->assigned_to, $m)) {
                        $uname = trim($m[1]);
                        $cid = Client::withoutGlobalScopes()
                            ->where('username', $uname)
                            ->where('status', 'inactive')
                            ->value('id');
                        if ($cid) $instrumentClientIds->push($cid);
                    }
                }
            }
            $instrumentClientIds = $instrumentClientIds->unique();

            // Fiber/cable search
            $fiberStockIds = \Illuminate\Support\Facades\DB::table('fiber_stocks')
                ->where(function($q) use ($search) {
                    $q->where('fiber_code', 'ilike', "%{$search}%")
                      ->orWhere('fiber_type', 'ilike', "%{$search}%")
                      ->orWhere('name', 'ilike', "%{$search}%")
                      ->orWhere('brand_name', 'ilike', "%{$search}%");
                })
                ->pluck('id');

            $fiberClientIds = collect();
            if ($fiberStockIds->isNotEmpty()) {
                $fromUsages = \Illuminate\Support\Facades\DB::table('fiber_usages')
                    ->whereIn('fiber_stock_id', $fiberStockIds)
                    ->whereNotNull('client_id')
                    ->pluck('client_id');

                $fiberDetails = \Illuminate\Support\Facades\DB::table('fiber_histories')
                    ->whereIn('fiber_stock_id', $fiberStockIds)
                    ->where('type', 'fiber_out')
                    ->whereNotNull('details')
                    ->pluck('details');

                $fiberUsernames = collect();
                foreach ($fiberDetails as $detail) {
                    if (preg_match('/PPPoE:\s*(\S+)/', $detail, $m)) {
                        $fiberUsernames->push($m[1]);
                    }
                }
                $fromHistories = $fiberUsernames->isNotEmpty()
                    ? Client::withoutGlobalScopes()->whereIn('username', $fiberUsernames)->pluck('id')
                    : collect();

                $fiberClientIds = $fromUsages->merge($fromHistories)->unique();
            }

            $query->where(function($q) use ($search, $instrumentClientIds, $fiberClientIds) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('client_id', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhereIn('id', $instrumentClientIds)
                  ->orWhereIn('id', $fiberClientIds);
            });
        }

        // Free-clients-only filter
        if ($request->filled('free_only') && $request->free_only === '1') {
            $query->where('is_free', true);
        }

        // Count of free inactive clients in this company
        $freeCount = $isSuperAdmin
            ? Client::withoutGlobalScopes()->where('status', 'inactive')->where('is_free', true)->count()
            : Client::withoutGlobalScopes()->where('status', 'inactive')->where('is_free', true)->where('company_id', $companyId)->count();

        $inactiveClients = $query->orderBy('updated_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        $inactiveClientIds = $inactiveClients->pluck('id');

        // Assigned instruments — match by client_id OR assigned_to pattern (same as client profile)
        $instrumentQuery = \App\Models\Instrument::withoutGlobalScope('tenant')
            ->where('status', 'in_use')
            ->where(function($q) use ($inactiveClientIds, $inactiveClients) {
                $q->whereIn('client_id', $inactiveClientIds);
                foreach ($inactiveClients as $c) {
                    if ($c->username) {
                        $q->orWhere('assigned_to', 'LIKE', '%(' . $c->username . ')%');
                    }
                    $q->orWhere('assigned_to', (string) $c->id)
                      ->orWhere('assigned_to', $c->name . ' (' . $c->username . ')');
                }
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        // Group instruments by client — resolve client_id=null via assigned_to pattern
        $usernameToClientId = $inactiveClients->keyBy('username');
        $assignedInstruments = collect();
        $grouped = [];
        foreach ($instrumentQuery as $inst) {
            $cid = $inst->client_id;
            if (!$cid && $inst->assigned_to) {
                // Extract username from "Name (username)" pattern
                if (preg_match('/\(([^)]+)\)$/', $inst->assigned_to, $m)) {
                    $uname = trim($m[1]);
                    $cid = $usernameToClientId->has($uname) ? $usernameToClientId[$uname]->id : null;
                }
            }
            if ($cid) {
                $grouped[$cid][] = $inst;
            }
        }
        $assignedInstruments = collect(array_map(fn($rows) => collect($rows), $grouped));

        // Fiber history from fiber_histories matched by client username
        $fiberHistories = collect();
        if ($inactiveClients->count() > 0) {
            $clientMap = $inactiveClients->keyBy('id');
            $allFiberRows = \Illuminate\Support\Facades\DB::table('fiber_histories')
                ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
                ->where('fiber_histories.type', 'fiber_out')
                ->select('fiber_histories.*', 'fiber_stocks.fiber_code', 'fiber_stocks.fiber_type', 'fiber_stocks.core')
                ->orderBy('fiber_histories.date', 'desc')
                ->get();

            $fiberByClient = [];
            foreach ($allFiberRows as $row) {
                foreach ($clientMap as $clientId => $client) {
                    if ($client->username && str_contains($row->details ?? '', 'PPPoE: ' . $client->username)) {
                        $fiberByClient[$clientId][] = $row;
                        break;
                    }
                }
            }
            $fiberHistories = collect(array_map(fn($rows) => collect($rows), $fiberByClient));
        }

        // FiberUsage table (secondary source)
        $fiberUsages = collect();
        try {
            if (class_exists(\App\Models\FiberUsage::class)) {
                $fiberUsages = \App\Models\FiberUsage::whereIn('client_id', $inactiveClientIds)
                    ->with(['fiberStock'])
                    ->get()
                    ->groupBy('client_id');
            }
        } catch (\Throwable $e) {
            $fiberUsages = collect();
        }

        $staffUsers = \App\Models\User::orderBy('name')->get(['id', 'name']);
        return view('admin.inventory.inactive-client-history', compact(
            'inactiveClients', 'assignedInstruments', 'fiberHistories', 'fiberUsages', 'staffUsers', 'freeCount'
        ));
    }

    public function recoverInstrument(Request $request, \App\Models\Instrument $instrument)
    {
        $client = $instrument->client_id
            ? Client::withoutGlobalScopes()->find($instrument->client_id)
            : null;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            \App\Models\ReturnedEquipment::create([
                'client_id'        => $client?->id,
                'client_name'      => $client?->name ?? $instrument->assigned_to ?? 'Unknown',
                'client_client_id' => $client?->client_id ?? null,
                'equipment_type'   => $instrument->category ?? 'Instrument',
                'equipment_name'   => $instrument->name,
                'serial_number'    => $instrument->serial_number,
                'mac_address'      => null,
                'model'            => $instrument->model,
                'brand'            => $instrument->brand,
                'condition'        => $request->input('condition', 'good'),
                'remarks'          => $request->input('remarks'),
                'returned_by'      => auth()->id(),
                'received_by'      => $request->input('staff_id') ? (int)$request->input('staff_id') : auth()->id(),
                'return_date'      => today(),
                'company_id'       => auth()->user()->company_id ?? 1,
                'remarks'          => ($request->input('remarks') ? $request->input('remarks') . ' ' : '') . ($request->input('staff_id') ? '| Collected by: ' . (\App\Models\User::find($request->input('staff_id'))?->name ?? 'Staff') : ''),
            ]);

            $instrument->update([
                'status'      => 'available',
                'client_id'   => null,
                'assigned_to' => null,
            ]);

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', 'Equipment "' . $instrument->name . '" marked as recovered and returned to inventory.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function exportInactiveClientHistory(Request $request)
    {
        $authUserExp = auth()->user();
        $isSuperAdminExp = $authUserExp && $authUserExp->isSuperAdmin();
        $companyIdExp = $authUserExp ? $authUserExp->company_id : 0;

        $query = $isSuperAdminExp
            ? Client::withoutGlobalScopes()->where('status', 'inactive')->with(['zone', 'package'])
            : Client::withoutGlobalScopes()->where('status', 'inactive')->where('company_id', $companyIdExp)->with(['zone', 'package']);

        if ($request->filled('search')) {
            $search = $request->search;
            $matchingInstruments = \App\Models\Instrument::withoutGlobalScopes()
                ->where('status', 'in_use')
                ->where(function($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('category', 'ilike', "%{$search}%")
                      ->orWhere('serial_number', 'ilike', "%{$search}%");
                })->get(['id', 'client_id', 'assigned_to']);
            $instrumentClientIds = collect();
            foreach ($matchingInstruments as $inst) {
                if ($inst->client_id) { $instrumentClientIds->push($inst->client_id); }
                elseif ($inst->assigned_to && preg_match('/\(([^)]+)\)$/', $inst->assigned_to, $m)) {
                    $cid = Client::withoutGlobalScopes()->where('username', trim($m[1]))->where('status', 'inactive')->value('id');
                    if ($cid) $instrumentClientIds->push($cid);
                }
            }
            $query->where(function($q) use ($search, $instrumentClientIds) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('client_id', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhereIn('id', $instrumentClientIds);
            });
        }

        $clients = $query->orderBy('updated_at', 'desc')->get();
        $clientIds = $clients->pluck('id');

        $instruments = \App\Models\Instrument::withoutGlobalScopes()
            ->where('status', 'in_use')
            ->where(function($q) use ($clientIds, $clients) {
                $q->whereIn('client_id', $clientIds);
                foreach ($clients as $c) {
                    if ($c->username) $q->orWhere('assigned_to', 'LIKE', '%(' . $c->username . ')%');
                }
            })->get()->groupBy(function($i) use ($clients) {
                if ($i->client_id) return $i->client_id;
                if ($i->assigned_to && preg_match('/\(([^)]+)\)$/', $i->assigned_to, $m)) {
                    $c = $clients->firstWhere('username', trim($m[1]));
                    return $c ? $c->id : null;
                }
                return null;
            });

        $filename = 'inactive-client-inventory-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($clients, $instruments) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, ['Client Name', 'Client ID', 'Phone', 'Username', 'Zone', 'Package', 'Inactive Since', 'Product', 'Category', 'Brand', 'Model', 'Serial No', 'Assigned Date']);
            foreach ($clients as $client) {
                $clientInstruments = $instruments[$client->id] ?? collect();
                if ($clientInstruments->isEmpty()) {
                    fputcsv($handle, [
                        $client->name, $client->client_id, $client->phone, $client->username,
                        $client->zone->name ?? '', $client->package->name ?? '',
                        $client->updated_at ? $client->updated_at->format('d-m-Y') : '',
                        '', '', '', '', '', ''
                    ]);
                } else {
                    foreach ($clientInstruments as $inst) {
                        fputcsv($handle, [
                            $client->name, $client->client_id, $client->phone, $client->username,
                            $client->zone->name ?? '', $client->package->name ?? '',
                            $client->updated_at ? $client->updated_at->format('d-m-Y') : '',
                            $inst->name, $inst->category, $inst->brand, $inst->model,
                            $inst->serial_number, $inst->updated_at ? $inst->updated_at->format('d-m-Y') : ''
                        ]);
                    }
                }
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function recoverFiberUsage(\Illuminate\Http\Request $request, $id)
    {
        $usage = \App\Models\FiberUsage::find($id);
        if (!$usage) {
            return redirect()->back()->with("error", "Fiber usage record not found.");
        }
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $stock = \App\Models\FiberStock::find($usage->fiber_stock_id);
            if ($stock) {
                $stock->increment("remaining_meter", $usage->used_meter);
            }
            \App\Models\FiberHistory::create([
                "type"           => "fiber_in",
                "fiber_stock_id" => $usage->fiber_stock_id,
                "meter"          => $usage->used_meter,
                "note"           => ($request->input('staff_id') ? 'Collected by: ' . (\App\Models\User::find($request->input('staff_id'))?->name ?? 'Staff') . ' — ' : '') . $request->input("remarks", "Recovered from inactive client"),
                "date"           => today(),
                "details"        => "Recovered: " . number_format($usage->used_meter, 1) . " m — Client ID: " . ($usage->client_id ?? "N/A") . ($request->input('staff_id') ? " | Collected by: " . (\App\Models\User::find($request->input('staff_id'))?->name ?? 'Staff') : ''),
            ]);
            $usage->delete();
            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with("success", "Fiber cable (" . number_format($usage->used_meter, 1) . " m) successfully recovered and returned to stock.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with("error", "Failed: " . $e->getMessage());
        }
    }

    public function recoverFiberHistory(\Illuminate\Http\Request $request, $id)
    {
        $history = \Illuminate\Support\Facades\DB::table("fiber_histories")->where("id", $id)->first();
        if (!$history) {
            return redirect()->back()->with("error", "Fiber history record not found.");
        }
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $stock = \App\Models\FiberStock::find($history->fiber_stock_id);
            if ($stock) {
                $stock->increment("remaining_meter", $history->meter);
            }
            \App\Models\FiberHistory::create([
                "type"           => "fiber_in",
                "fiber_stock_id" => $history->fiber_stock_id,
                "meter"          => $history->meter,
                "note"           => ($request->input('staff_id') ? 'Collected by: ' . (\App\Models\User::find($request->input('staff_id'))?->name ?? 'Staff') . ' — ' : '') . $request->input("remarks", "Recovered from inactive client"),
                "date"           => today(),
                "details"        => "Recovered: " . number_format($history->meter, 1) . " m" . ($history->note && str_contains($history->note, 'Client:') ? ' — ' . $history->note : '') . ($request->input('staff_id') ? " | Collected by: " . (\App\Models\User::find($request->input('staff_id'))?->name ?? 'Staff') : ''),
            ]);
            \Illuminate\Support\Facades\DB::table("fiber_histories")->where("id", $id)->delete();
            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with("success", "Fiber cable (" . number_format($history->meter, 1) . " m) successfully recovered and returned to stock.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with("error", "Failed: " . $e->getMessage());
        }
    }

}