<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\StockMovement;
use App\Models\DamagedItem;
use App\Models\Purchase;

class InventoryService
{
    public function recordStockIn(Product $product, int $quantity, ?string $reference = null, ?string $description = null): StockMovement
    {
        $stockBefore = $product->stock_quantity ?? 0;
        $stockAfter = $stockBefore + $quantity;
        
        $product->stock_quantity = $stockAfter;
        $product->save();
        
        return StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference' => $reference,
            'description' => $description,
            'created_by' => auth()->id()
        ]);
    }

    public function recordStockOut(Product $product, int $quantity, ?string $reference = null, ?string $description = null): ?StockMovement
    {
        $stockBefore = $product->stock_quantity ?? 0;
        
        if ($stockBefore < $quantity) {
            return null;
        }
        
        $stockAfter = $stockBefore - $quantity;
        
        $product->stock_quantity = $stockAfter;
        $product->save();
        
        return StockMovement::create([
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference' => $reference,
            'description' => $description,
            'created_by' => auth()->id()
        ]);
    }

    public function addSerial(Product $product, string $serialNumber, ?string $macAddress = null, ?Purchase $purchase = null): ProductSerial
    {
        $warrantyStart = $purchase ? $purchase->purchase_date : now();
        $warrantyEnd = $product->warranty_months > 0 
            ? $warrantyStart->copy()->addMonths($product->warranty_months) 
            : null;
        
        return ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => $serialNumber,
            'mac_address' => $macAddress,
            'status' => 'in_stock',
            'purchase_id' => $purchase?->id,
            'warranty_start' => $warrantyStart,
            'warranty_end' => $warrantyEnd
        ]);
    }

    public function assignSerialToClient(ProductSerial $serial, int $clientId): ProductSerial
    {
        $serial->client_id = $clientId;
        $serial->status = 'assigned';
        $serial->save();
        
        $this->recordStockOut(
            $serial->product,
            1,
            $serial->serial_number,
            "Assigned to client #{$clientId}"
        );
        
        return $serial;
    }

    public function reportDamage(Product $product, int $quantity, string $reason, ?ProductSerial $serial = null): DamagedItem
    {
        $this->recordStockOut($product, $quantity, null, "Damaged: {$reason}");
        
        if ($serial) {
            $serial->status = 'damaged';
            $serial->save();
        }
        
        return DamagedItem::create([
            'product_id' => $product->id,
            'serial_id' => $serial?->id,
            'quantity' => $quantity,
            'damage_date' => now(),
            'damage_reason' => $reason,
            'reported_by' => auth()->id()
        ]);
    }

    public function getStockReport(): array
    {
        return Product::with(['category'])
            ->select('products.*')
            ->selectRaw("COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE stock_movements.product_id = products.id AND type = 'in'), 0) as total_in")
            ->selectRaw("COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE stock_movements.product_id = products.id AND type = 'out'), 0) as total_out")
            ->selectRaw("COALESCE((SELECT COUNT(*) FROM product_serials WHERE product_serials.product_id = products.id AND product_serials.status IN ('in_stock','stock')), 0) as serial_in_stock")
            ->get()
            ->map(function ($product) {
                $currentStock = $product->has_serial
                    ? (int)($product->serial_in_stock ?? 0)
                    : ($product->stock_quantity ?? 0);
                return [
                    'id'             => $product->id,
                    'product_name'   => $product->name,
                    'name'           => $product->name,
                    'code'           => $product->code ?? '-',
                    'category'       => $product->category?->name ?? 'N/A',
                    'brand'          => $product->brand,
                    'model'          => $product->model,
                    'unit'           => $product->unit,
                    'stock_quantity' => $product->stock_quantity,
                    'current_stock'  => $currentStock,
                    'min_stock'      => $product->alert_quantity ?? 0,
                    'purchase_price' => (float)($product->purchase_price ?? 0),
                    'selling_price'  => (float)($product->selling_price ?? 0),
                    'total_in'       => (int)($product->total_in ?? 0),
                    'total_out'      => (int)($product->total_out ?? 0),
                    'has_serial'     => $product->has_serial,
                    'warranty_months'=> $product->warranty_months,
                ];
            })
            ->toArray();
    }

    public function getWarrantyExpiringItems(int $days = 30): array
    {
        return ProductSerial::with(['product', 'client'])
            ->where('warranty_end', '>=', now())
            ->where('warranty_end', '<=', now()->addDays($days))
            ->whereIn('status', ['assigned', 'sold'])
            ->get()
            ->toArray();
    }
}
