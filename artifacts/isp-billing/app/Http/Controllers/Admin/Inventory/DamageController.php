<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductSerial;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DamageController extends Controller
{
    public function index()
    {
        $damages = ProductSerial::with(['product', 'client'])
            ->where('status', 'damaged')
            ->orderBy('updated_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25);

        $totalDamaged = ProductSerial::where('status', 'damaged')->count();

        $damagedByProduct = DB::table('product_serials')
            ->join('products', 'product_serials.product_id', '=', 'products.id')
            ->where('product_serials.status', 'damaged')
            ->select('products.name as product_name', DB::raw('COUNT(*) as count'))
            ->groupBy('products.name')
            ->orderByDesc('count')
            ->get();

        return view('admin.inventory.damage-products', compact('damages', 'totalDamaged', 'damagedByProduct'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $serials = ProductSerial::with('product')
            ->whereIn('status', ['stock', 'in_stock', 'assigned', 'returned'])
            ->orderBy('serial_number')
            ->get();

        return view('admin.inventory.damage-products-create', compact('products', 'serials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'serial_id' => 'required|exists:product_serials,id',
            'remarks' => 'required|string|max:500',
        ]);

        $serial = ProductSerial::with('product')->findOrFail($request->serial_id);

        if ($serial->status === 'damaged') {
            return back()->with('error', 'This device is already marked as damaged.');
        }

        $previousStatus = $serial->status;
        $previousClientId = $serial->client_id;
        $product = $serial->product;

        DB::beginTransaction();
        try {
            $serial->update([
                'status' => 'damaged',
                'client_id' => null,
                'remarks' => $request->remarks,
            ]);

            $currentStock = $product ? $product->stock_quantity : 0;
            $stockChange = in_array($previousStatus, ['stock', 'in_stock', 'returned']) ? -1 : 0;

            DB::table('stock_movements')->insert([
                'product_id' => $serial->product_id,
                'type' => 'out',
                'quantity' => 1,
                'stock_before' => $currentStock,
                'stock_after' => $currentStock + $stockChange,
                'reference' => 'serial_id:' . $serial->id . '|action:damage|prev_status:' . $previousStatus . ($previousClientId ? '|prev_client_id:' . $previousClientId : ''),
                'description' => 'Serial #' . $serial->serial_number . ' reported as damaged. Previous status: ' . $previousStatus . '. Reason: ' . $request->remarks,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($product && $stockChange !== 0) {
                $product->decrement('stock_quantity', abs($stockChange));
            }

            DB::commit();
            return redirect()->route('admin.inventory.damage.index')->with('success', 'Device marked as damaged successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to report damage: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $serial = ProductSerial::with(['product', 'client'])->findOrFail($id);

        $damageHistory = DB::table('stock_movements')
            ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
            ->select('stock_movements.*', 'users.name as created_by_name')
            ->where('stock_movements.reference', 'like', '%serial_id:' . $serial->id . '%')
            ->where('stock_movements.description', 'like', '%damage%')
            ->orderBy('stock_movements.created_at', 'desc')
            ->get();

        $allHistory = DB::table('stock_movements')
            ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
            ->select('stock_movements.*', 'users.name as created_by_name')
            ->where('stock_movements.reference', 'like', '%serial_id:' . $serial->id . '%')
            ->orderBy('stock_movements.created_at', 'desc')
            ->get();

        return view('admin.inventory.damaged-items', compact('serial', 'damageHistory', 'allHistory'));
    }

    public function edit($id)
    {
        $serial = ProductSerial::with('product')
            ->where('status', 'damaged')
            ->findOrFail($id);

        return view('admin.inventory.damage-products-edit', compact('serial'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        $serial = ProductSerial::where('status', 'damaged')->findOrFail($id);
        $serial->update(['remarks' => $request->remarks]);

        return redirect()->route('admin.inventory.damage.index')->with('success', 'Damage remarks updated successfully.');
    }

    public function destroy($id)
    {
        $serial = ProductSerial::with('product')->where('status', 'damaged')->findOrFail($id);

        DB::beginTransaction();
        try {
            $movement = DB::table('stock_movements')
                ->where('reference', 'like', '%serial_id:' . $serial->id . '%')
                ->where('description', 'like', '%damage%')
                ->orderBy('created_at', 'desc')
                ->first();

            $prevStatus = 'stock';
            $prevClientId = null;

            if ($movement && $movement->reference) {
                if (preg_match('/prev_status:(\w+)/', $movement->reference, $matches)) {
                    $prevStatus = $matches[1];
                }
                if (preg_match('/prev_client_id:(\d+)/', $movement->reference, $matches)) {
                    $prevClientId = $matches[1];
                }
            }

            $serial->update([
                'status' => $prevStatus,
                'client_id' => $prevClientId,
            ]);

            if ($serial->product && in_array($prevStatus, ['stock', 'in_stock', 'returned'])) {
                $serial->product->increment('stock_quantity');
            }

            if ($movement) {
                DB::table('stock_movements')->where('id', $movement->id)->delete();
            }

            DB::commit();
            return redirect()->route('admin.inventory.damage.index')->with('success', 'Damage record reversed. Serial restored to previous status.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reverse damage: ' . $e->getMessage());
        }
    }
}
