<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSerial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function summary()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $instrumentGroups = DB::table('instruments')
            ->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->select(
                'category',
                'sub_category',
                DB::raw("SUM(COALESCE(quantity, 1)) as total"),
                DB::raw("SUM(CASE WHEN status = 'available' THEN COALESCE(quantity, 1) ELSE 0 END) as available"),
                DB::raw("SUM(CASE WHEN status = 'in_use' THEN COALESCE(quantity, 1) ELSE 0 END) as in_use"),
                DB::raw("SUM(CASE WHEN status = 'damaged' OR \"condition\" = 'damaged' THEN COALESCE(quantity, 1) ELSE 0 END) as damaged")
            )
            ->groupBy('category', 'sub_category')
            ->orderBy('category')
            ->orderBy('sub_category')
            ->get()
            ->map(function ($row) {
                return [
                    'category' => $row->category ?? 'Uncategorized',
                    'sub_category' => $row->sub_category,
                    'total' => (int)$row->total,
                    'available' => (int)$row->available,
                    'in_use' => (int)$row->in_use,
                    'damaged' => (int)$row->damaged,
                ];
            })
            ->toArray();

        $fibers = DB::table('fiber_stocks')->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->select(
                'id as product_id',
                'fiber_code as item_name',
                'fiber_code as product_code',
                'brand_name',
                'core',
                'total_meter as stock_quantity',
                DB::raw("0 as alert_quantity"),
                'total_meter as stock_in',
                DB::raw("(total_meter - remaining_meter) as stock_out"),
                'remaining_meter as remaining'
            )
            ->get();

        $fiberCoreGroups = $fibers->groupBy('core')->map(function ($group, $core) {
            return (object) [
                'core' => $core,
                'total' => $group->sum('stock_in'),
                'used' => $group->sum('stock_out'),
                'remaining' => $group->sum('remaining'),
            ];
        })->sortKeys();

        $totalProducts = Product::where('is_active', true)->count();
        $instrumentCount = DB::table('instruments')->when($_cid, fn($q) => $q->where('company_id', $_cid))->sum(DB::raw('COALESCE(quantity, 1)'));
        $fiberStockCount = DB::table('fiber_stocks')->when($_cid, fn($q) => $q->where('company_id', $_cid))->count();
        $totalInStock = ProductSerial::whereIn('status', ['in_stock', 'stock'])->count();
        $totalAssigned = ProductSerial::where('status', 'assigned')->count();
        $totalDamaged = ProductSerial::where('status', 'damaged')->count();
        $totalReturned = ProductSerial::where('status', 'returned')->count();

        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'alert_quantity')
            ->where('alert_quantity', '>', 0)
            ->select('id', 'name', 'code', 'stock_quantity', 'alert_quantity')
            ->get();

        $warrantyExpiringSoon = ProductSerial::with('product')
            ->where('status', 'assigned')
            ->whereNotNull('warranty_end')
            ->whereBetween('warranty_end', [now(), now()->addDays(30)])
            ->count();

        $totalFiberMeter = DB::table('fiber_stocks')->when($_cid, fn($q) => $q->where('company_id', $_cid))->sum('total_meter');
        $remainingFiberMeter = DB::table('fiber_stocks')->when($_cid, fn($q) => $q->where('company_id', $_cid))->sum('remaining_meter');
        $instrumentStockValue = DB::table('instruments')->when($_cid, fn($q) => $q->where('company_id', $_cid))->sum(DB::raw('COALESCE(purchase_price, 0) * COALESCE(quantity, 1)')) ?? 0;
        $purchaseStockValue = DB::table('purchases')->sum('total') ?? 0;
        $fiberStockValue = DB::table('fiber_stocks')->when($_cid, fn($q) => $q->where('company_id', $_cid))->sum('total_cost') ?? 0;
        $totalStockValue = $instrumentStockValue + $purchaseStockValue + $fiberStockValue;

        $fiberMovements = DB::table('fiber_histories')
            ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
            ->leftJoin('vendors', 'fiber_stocks.vendor_id', '=', 'vendors.id')
            ->when($_cid, fn($q) => $q->where('fiber_stocks.company_id', $_cid))
            ->select(
                'fiber_histories.created_at',
                DB::raw("COALESCE(fiber_stocks.fiber_code, 'Unknown') as product_name"),
                'fiber_histories.type',
                'fiber_histories.meter as quantity',
                DB::raw("'m' as unit"),
                DB::raw("CASE
                    WHEN fiber_histories.type IN ('stock_in', 'fiber_in')
                    THEN CONCAT(
                        'Stock In: ', COALESCE(fiber_stocks.fiber_code, 'N/A'),
                        CASE WHEN vendors.name IS NOT NULL THEN CONCAT(' | Vendor: ', vendors.name) ELSE '' END,
                        CASE WHEN fiber_stocks.purchaser IS NOT NULL AND fiber_stocks.purchaser != '' THEN CONCAT(' | Purchaser: ', fiber_stocks.purchaser) ELSE '' END,
                        CASE WHEN fiber_stocks.invoice_number IS NOT NULL AND fiber_stocks.invoice_number != '' THEN CONCAT(' | Invoice: ', fiber_stocks.invoice_number) ELSE '' END,
                        CASE WHEN fiber_stocks.stock_date IS NOT NULL THEN CONCAT(' | Date: ', TO_CHAR(fiber_stocks.stock_date::date, 'DD-MM-YYYY')) ELSE '' END
                    )
                    ELSE fiber_histories.details
                END as description"),
                DB::raw("CASE
                    WHEN fiber_histories.type IN ('stock_in', 'fiber_in') AND fiber_stocks.purchaser IS NOT NULL AND fiber_stocks.purchaser != ''
                    THEN fiber_stocks.purchaser
                    WHEN fiber_histories.details LIKE '%Assigned By: %'
                    THEN TRIM(SPLIT_PART(SPLIT_PART(fiber_histories.details, 'Assigned By: ', 2), ' | ', 1))
                    ELSE 'System'
                END as created_by_name"),
                DB::raw("CASE
                    WHEN fiber_histories.type = 'fiber_in' AND fiber_histories.details LIKE 'Returned by client%'
                    THEN TRIM(SPLIT_PART(SPLIT_PART(fiber_histories.details, 'Returned by client ', 2), ' —', 1))
                    WHEN fiber_histories.type = 'usage' AND fiber_histories.details LIKE 'Used%for client:%'
                    THEN TRIM(SPLIT_PART(fiber_histories.details, 'for client: ', 2))
                    WHEN fiber_histories.type = 'fiber_out' AND fiber_histories.details LIKE '%PPPoE: %'
                    THEN TRIM(SPLIT_PART(SPLIT_PART(fiber_histories.details, 'PPPoE: ', 2), ' |', 1))
                    ELSE NULL
                END as client_info"),
                'fiber_histories.note'
            );

        $instrumentRestocks = DB::table('instruments')
            ->leftJoin('returned_equipments', function($join) {
                $join->on('returned_equipments.restock_ref_id', '=', 'instruments.id')
                     ->where('returned_equipments.restock_type', '=', 'instrument');
            })
            ->leftJoin('users', 'returned_equipments.restocked_by', '=', 'users.id')
            ->when($_cid, fn($q) => $q->where('instruments.company_id', $_cid))
            ->whereNotNull('instruments.notes')
            ->where(function($q) {
                $q->where('instruments.notes', 'LIKE', '%Restocked on%')
                  ->orWhere('instruments.notes', 'LIKE', '%Returned from client%');
            })
            ->select(
                'instruments.updated_at as created_at',
                DB::raw("CONCAT(COALESCE(instruments.category,''), CASE WHEN instruments.sub_category IS NOT NULL THEN CONCAT(' - ', instruments.sub_category) ELSE '' END) as product_name"),
                DB::raw("'product_in' as type"),
                DB::raw("COALESCE(instruments.quantity,1) as quantity"),
                DB::raw("'pcs' as unit"),
                DB::raw("COALESCE(instruments.notes, 'Product restock') as description"),
                DB::raw("COALESCE(users.name, 'System') as created_by_name"),
                DB::raw("CASE
                    WHEN instruments.notes LIKE 'Returned from client%'
                    THEN TRIM(SPLIT_PART(SPLIT_PART(instruments.notes, 'Returned from client ', 2), ' on ', 1))
                    ELSE NULL
                END as client_info"),
                DB::raw("instruments.notes as note")
            );

        $recentMovements = $fiberMovements->union($instrumentRestocks)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $instrumentDamaged = DB::table('instruments')->when($_cid, fn($q) => $q->where('company_id', $_cid))->where('condition', 'damaged')->orWhere('status', 'damaged')->sum(DB::raw('COALESCE(quantity, 1)'));

        $dashboard = [
            'total_products' => $totalProducts + $instrumentCount + $fiberStockCount,
            'total_stock_value' => $totalStockValue,
            'total_in_stock' => $totalInStock,
            'total_assigned' => $totalAssigned,
            'total_damaged' => $totalDamaged + $instrumentDamaged,
            'total_returned' => $totalReturned,
            'low_stock_count' => $lowStockProducts->count(),
            'warranty_expiring_soon' => $warrantyExpiringSoon,
            'total_fiber_meter' => $totalFiberMeter,
            'remaining_fiber_meter' => $remainingFiberMeter,
            'recent_movements' => $recentMovements,
        ];

        return view('admin.inventory.stock_summary', compact(
            'instrumentGroups',
            'fibers',
            'fiberCoreGroups',
            'lowStockProducts',
            'dashboard'
        ));
    }


    public function productAnalyse()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $instrumentGroups = DB::table('instruments')
            ->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->select(
                'category',
                'sub_category',
                DB::raw("SUM(COALESCE(quantity,1)) as total"),
                DB::raw("SUM(CASE WHEN status = 'available' THEN COALESCE(quantity,1) ELSE 0 END) as available"),
                DB::raw("SUM(CASE WHEN status = 'in_use' THEN COALESCE(quantity,1) ELSE 0 END) as in_use"),
                DB::raw("SUM(CASE WHEN condition = 'damaged' OR status = 'damaged' THEN COALESCE(quantity,1) ELSE 0 END) as damaged")
            )
            ->groupBy('category', 'sub_category')
            ->orderBy('category')
            ->get();

        $products = DB::table('products')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->select(
                'products.id',
                'products.name',
                'products.code',
                'products.stock_quantity',
                'products.alert_quantity',
                'products.purchase_price',
                'product_categories.name as category_name'
            )
            ->when($_cid, fn($q) => $q->where('products.company_id', $_cid))
            ->whereNull('products.deleted_at')
            ->orderBy('product_categories.name')
            ->orderBy('products.name')
            ->get();

        $lowStockProducts = $products->filter(function($p) {
            return $p->stock_quantity <= $p->alert_quantity;
        });

        $productsByCategory = DB::table('products')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->select(
                'product_categories.name as category_name',
                DB::raw('COUNT(products.id) as item_count'),
                DB::raw('SUM(products.stock_quantity) as total_stock'),
                DB::raw('SUM(products.stock_quantity * products.purchase_price) as total_value')
            )
            ->when($_cid, fn($q) => $q->where('products.company_id', $_cid))
            ->whereNull('products.deleted_at')
            ->groupBy('product_categories.name')
            ->orderBy('total_value', 'desc')
            ->get();

        $fibers = DB::table('fiber_stocks')->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->leftJoin('fiber_histories', 'fiber_stocks.id', '=', 'fiber_histories.fiber_stock_id')
            ->select(
                'fiber_stocks.id',
                'fiber_stocks.fiber_code',
                'fiber_stocks.brand_name',
                'fiber_stocks.core',
                DB::raw("SUM(CASE WHEN fiber_histories.type IN ('stock_in','fiber_in') THEN fiber_histories.meter ELSE 0 END) as stock_in"),
                DB::raw("SUM(CASE WHEN fiber_histories.type IN ('fiber_out','usage') THEN fiber_histories.meter ELSE 0 END) as stock_out"),
                DB::raw("SUM(CASE WHEN fiber_histories.type IN ('stock_in','fiber_in') THEN fiber_histories.meter ELSE 0 END) - SUM(CASE WHEN fiber_histories.type IN ('fiber_out','usage') THEN fiber_histories.meter ELSE 0 END) as remaining")
            )
            ->groupBy('fiber_stocks.id', 'fiber_stocks.fiber_code', 'fiber_stocks.brand_name', 'fiber_stocks.core')
            ->orderBy('fiber_stocks.fiber_code')
            ->get();

        $totalProductTypes = $products->count() + $instrumentGroups->count();
        $totalAvailable = $instrumentGroups->sum('available') + $products->sum('stock_quantity');
        $totalInUse = $instrumentGroups->sum('in_use');
        $totalDamaged = $instrumentGroups->sum('damaged');
        $lowStockCount = $lowStockProducts->count();

        return view('admin.inventory.product-analyse', compact(
            'instrumentGroups', 'products', 'lowStockProducts', 'productsByCategory',
            'fibers', 'totalProductTypes', 'totalAvailable', 'totalInUse',
            'totalDamaged', 'lowStockCount'
        ));
    }


    public function serialHistory($productId)
    {
        $product = Product::findOrFail($productId);

        $serials = ProductSerial::where('product_id', $productId)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get();

        $movements = DB::table('stock_movements')
            ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
            ->select('stock_movements.*', 'users.name as created_by_name')
            ->where('stock_movements.product_id', $productId)
            ->orderBy('stock_movements.created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        $serialSummary = [
            'total' => $serials->count(),
            'in_stock' => $serials->whereIn('status', ['in_stock', 'stock'])->count(),
            'assigned' => $serials->where('status', 'assigned')->count(),
            'damaged' => $serials->where('status', 'damaged')->count(),
            'returned' => $serials->where('status', 'returned')->count(),
        ];

        return view('admin.inventory.stock.serial_history', compact(
            'product',
            'serials',
            'movements',
            'serialSummary'
        ));
    }

    public function availability()
    {
        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $instruments = DB::table('instruments')
            ->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->select(
                'category',
                'sub_category',
                'brand',
                'name',
                DB::raw("SUM(COALESCE(quantity, 1)) as total_count"),
                DB::raw("SUM(CASE WHEN status = 'available' THEN COALESCE(quantity, 1) ELSE 0 END) as stock_count"),
                DB::raw("SUM(CASE WHEN status = 'in_use' THEN COALESCE(quantity, 1) ELSE 0 END) as assigned_count"),
                DB::raw("SUM(CASE WHEN status = 'damaged' OR \"condition\" = 'damaged' THEN COALESCE(quantity, 1) ELSE 0 END) as damaged_count"),
                DB::raw("SUM(CASE WHEN status = 'returned' THEN COALESCE(quantity, 1) ELSE 0 END) as returned_count")
            )
            ->groupBy('category', 'sub_category', 'brand', 'name')
            ->orderBy('category')
            ->orderBy('sub_category')
            ->orderBy('brand')
            ->get();

        $overallStats = [
            'total_products' => $instruments->count(),
            'total_serials' => $instruments->sum('total_count'),
            'total_in_stock' => $instruments->sum('stock_count'),
            'total_assigned' => $instruments->sum('assigned_count'),
            'total_damaged' => $instruments->sum('damaged_count'),
            'total_returned' => $instruments->sum('returned_count'),
        ];

        $recentInstruments = DB::table('instruments')
            ->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->select('id', 'name', 'category', 'sub_category', 'brand', 'serial_number', 'status', 'condition', 'assigned_to', 'created_at', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.inventory.stock.availability', compact(
            'instruments',
            'overallStats',
            'recentInstruments'
        ));
    }
}
