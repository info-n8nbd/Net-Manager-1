<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductSerial;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SerialAssignController extends Controller
{
    public function assign(Request $request)
    {
        $request->validate([
            'serial_id' => 'required|exists:product_serials,id',
            'client_id' => 'required|integer'
        ]);

        $serial = ProductSerial::with('product')->findOrFail($request->serial_id);

        if ($serial->status !== 'stock') {
            return back()->with('error', 'Serial is not available in stock. Current status: ' . $serial->status);
        }

        $product = $serial->product;
        $warrantyMonths = $product ? $product->warranty_months : 0;
        $warrantyStart = Carbon::now();
        $warrantyEnd = $warrantyMonths ? $warrantyStart->copy()->addMonths($warrantyMonths) : null;

        DB::beginTransaction();
        try {
            $serial->update([
                'status' => 'assigned',
                'client_id' => $request->client_id,
                'warranty_start' => $warrantyStart,
                'warranty_end' => $warrantyEnd,
                'remarks' => $request->remarks ?? $serial->remarks,
            ]);

            $currentStock = $product ? $product->stock_quantity : 0;

            DB::table('stock_movements')->insert([
                'product_id' => $serial->product_id,
                'type' => 'out',
                'quantity' => 1,
                'stock_before' => $currentStock,
                'stock_after' => $currentStock - 1,
                'reference' => 'serial_id:' . $serial->id . '|action:assign|client_id:' . $request->client_id,
                'description' => 'Serial #' . $serial->serial_number . ' assigned to client #' . $request->client_id,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($product) {
                $product->decrement('stock_quantity');
            }

            DB::commit();
            return back()->with('success', 'Serial assigned successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to assign serial: ' . $e->getMessage());
        }
    }

    public function returnDevice(Request $request)
    {
        $request->validate([
            'serial_id' => 'required|exists:product_serials,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        $serial = ProductSerial::with('product')->findOrFail($request->serial_id);

        if ($serial->status !== 'assigned') {
            return back()->with('error', 'Only assigned devices can be returned. Current status: ' . $serial->status);
        }

        $previousClientId = $serial->client_id;
        $product = $serial->product;

        DB::beginTransaction();
        try {
            $serial->update([
                'status' => 'returned',
                'client_id' => null,
                'remarks' => $request->remarks ?? 'Returned to stock',
            ]);

            $currentStock = $product ? $product->stock_quantity : 0;

            DB::table('stock_movements')->insert([
                'product_id' => $serial->product_id,
                'type' => 'in',
                'quantity' => 1,
                'stock_before' => $currentStock,
                'stock_after' => $currentStock + 1,
                'reference' => 'serial_id:' . $serial->id . '|action:return|prev_client_id:' . $previousClientId,
                'description' => 'Serial #' . $serial->serial_number . ' returned from client #' . $previousClientId,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($product) {
                $product->increment('stock_quantity');
            }

            DB::commit();
            return back()->with('success', 'Device returned to stock successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to return device: ' . $e->getMessage());
        }
    }

    public function reassign(Request $request)
    {
        $request->validate([
            'serial_id' => 'required|exists:product_serials,id',
            'new_client_id' => 'required|integer',
            'remarks' => 'nullable|string|max:500',
        ]);

        $serial = ProductSerial::with('product')->findOrFail($request->serial_id);

        if ($serial->status !== 'assigned') {
            return back()->with('error', 'Only assigned devices can be reassigned. Current status: ' . $serial->status);
        }

        if ($serial->client_id == $request->new_client_id) {
            return back()->with('error', 'Device is already assigned to this client.');
        }

        $previousClientId = $serial->client_id;
        $product = $serial->product;

        DB::beginTransaction();
        try {
            $serial->update([
                'status' => 'assigned',
                'client_id' => $request->new_client_id,
                'remarks' => $request->remarks ?? $serial->remarks,
            ]);

            $currentStock = $product ? $product->stock_quantity : 0;

            DB::table('stock_movements')->insert([
                'product_id' => $serial->product_id,
                'type' => 'out',
                'quantity' => 1,
                'stock_before' => $currentStock,
                'stock_after' => $currentStock,
                'reference' => 'serial_id:' . $serial->id . '|action:reassign|prev_client_id:' . $previousClientId . '|new_client_id:' . $request->new_client_id,
                'description' => 'Serial #' . $serial->serial_number . ' reassigned from client #' . $previousClientId . ' to client #' . $request->new_client_id,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return back()->with('success', 'Device reassigned successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reassign device: ' . $e->getMessage());
        }
    }

    public function markDamaged(Request $request)
    {
        $request->validate([
            'serial_id' => 'required|exists:product_serials,id',
            'remarks' => 'required|string|max:500',
        ]);

        $serial = ProductSerial::with('product')->findOrFail($request->serial_id);

        if ($serial->status === 'damaged') {
            return back()->with('error', 'Device is already marked as damaged.');
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
            $stockChange = in_array($previousStatus, ['stock', 'returned']) ? -1 : 0;

            DB::table('stock_movements')->insert([
                'product_id' => $serial->product_id,
                'type' => 'out',
                'quantity' => 1,
                'stock_before' => $currentStock,
                'stock_after' => $currentStock + $stockChange,
                'reference' => 'serial_id:' . $serial->id . '|action:damage|prev_status:' . $previousStatus . ($previousClientId ? '|prev_client_id:' . $previousClientId : ''),
                'description' => 'Serial #' . $serial->serial_number . ' marked as damaged. Previous status: ' . $previousStatus . '. Reason: ' . $request->remarks,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($product && $stockChange !== 0) {
                $product->decrement('stock_quantity', abs($stockChange));
            }

            DB::commit();
            return back()->with('success', 'Device marked as damaged successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to mark device as damaged: ' . $e->getMessage());
        }
    }
}
