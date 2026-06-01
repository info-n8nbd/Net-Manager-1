<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Vendor;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::latest()->get();
        return view('admin.inventory.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $vendors = Vendor::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.inventory.purchases.create', compact('vendors', 'products'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::create([
                'vendor_id' => $request->vendor_id,
                'purchase_date' => $request->purchase_date,
                'subtotal' => $request->subtotal ?? 0,
                'total' => $request->total ?? 0,
                'status' => 'completed'
            ]);

            foreach ($request->items as $item) {

                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total' => $item['total'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('stocks')->insert([
                    'company_id' => auth()->user()->company_id ?? null,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'type' => 'in',
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'description' => 'Stock added via purchase',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $product = Product::find($item['product_id']);

                if ($product) {
                    $product->increment('stock_quantity', $item['quantity']);
                }

                if ($product && $product->has_serial) {
                    for ($i = 1; $i <= $item['quantity']; $i++) {
                        DB::table('product_serials')->insert([
                            'product_id' => $product->id,
                            'serial_number' => strtoupper($product->code) . '-' . uniqid(),
                            'status' => 'stock',
                            'purchase_id' => $purchase->id,
                            'warranty_start' => $request->purchase_date,
                            'warranty_end' => now()->addMonths($product->warranty_months ?? 0),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();

            try {
                $vendorName = $purchase->vendor ? $purchase->vendor->name : ($request->vendor_id ?? 'N/A');
                $vendor = \App\Models\Vendor::find($request->vendor_id);
                if ($vendor) $vendorName = $vendor->name;
                $purchaseItems = [];
                foreach ($request->items as $itm) {
                    $p = \App\Models\Product::find($itm['product_id']);
                    if ($p) $purchaseItems[] = $p->name . ' x' . $itm['quantity'];
                }
                $purchaseItemsList = implode(', ', array_slice($purchaseItems, 0, 5));
                if (count($purchaseItems) > 5) $purchaseItemsList .= '...';
                (new \App\Services\TelegramService())->sendPurchaseNotification(
                    $purchase->purchase_no ?? ('PUR-' . $purchase->id),
                    $vendorName,
                    (float) ($request->total ?? 0),
                    count($request->items),
                    auth()->user()->name ?? 'System',
                    $purchaseItemsList,
                    $request->purchase_date ?? ''
                );
            } catch (\Throwable $e) {
            }

            return redirect()->route('admin.inventory.purchases.index')
                ->with('success', 'Purchase saved successfully with stock + serial.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $purchase = Purchase::with(['vendor', 'items'])->findOrFail($id);
        return view('admin.inventory.purchases.show', compact('purchase'));
    }

    public function edit($id)
    {
        $purchase = Purchase::findOrFail($id);
        $purchaseItems = DB::table('purchase_items')
            ->where('purchase_id', $purchase->id)
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->select('purchase_items.*', 'products.name as product_name')
            ->get();
        $vendors = Vendor::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.inventory.purchases.edit', compact('purchase', 'purchaseItems', 'vendors', 'products'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_date' => 'required|date',
            'subtotal' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,completed,received,cancelled,partial',
        ]);

        $purchase = Purchase::findOrFail($id);
        $purchase->update([
            'vendor_id' => $request->vendor_id,
            'purchase_date' => $request->purchase_date,
            'subtotal' => $request->subtotal ?? $purchase->subtotal,
            'total' => $request->total ?? $purchase->total,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.inventory.purchases.index')
            ->with('success', 'Purchase updated successfully.');
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::findOrFail($id);

            $assignedSerials = DB::table('product_serials')
                ->where('purchase_id', $id)
                ->whereIn('status', ['assigned', 'damaged'])
                ->count();

            if ($assignedSerials > 0) {
                return redirect()->route('admin.inventory.purchases.index')
                    ->with('error', 'Cannot delete purchase - ' . $assignedSerials . ' serial(s) are currently assigned or damaged.');
            }

            $purchaseItems = DB::table('purchase_items')->where('purchase_id', $id)->get();
            foreach ($purchaseItems as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
            }

            DB::table('product_serials')->where('purchase_id', $id)->delete();

            DB::table('stocks')
                ->where('reference_type', 'purchase')
                ->where('reference_id', $id)
                ->delete();

            DB::table('purchase_items')->where('purchase_id', $id)->delete();

            $purchase->delete();

            DB::commit();

            return redirect()->route('admin.inventory.purchases.index')
                ->with('success', 'Purchase deleted successfully. Stock quantities and serials have been reversed.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to delete purchase: ' . $e->getMessage());
        }
    }
}
