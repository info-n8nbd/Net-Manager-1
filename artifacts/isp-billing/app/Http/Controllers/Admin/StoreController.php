<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientServiceCharge;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Vendor;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\VendorLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function categories()
    {
        $categories = ProductCategory::withCount('products')->orderBy('name')->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 20);
        return view('admin.store.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);
        ProductCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, ProductCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);
        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(ProductCategory $category)
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Cannot delete category with products. Remove products first.');
        }
        $category->delete();
        return back()->with('success', 'Category deleted successfully.');
    }

    public function products(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%")
                  ->orWhere('brand', 'ilike', "%{$search}%");
            });
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') $query->where('is_active', true);
            elseif ($request->status === 'inactive') $query->where('is_active', false);
            elseif ($request->status === 'low_stock') $query->where('is_active', true)->whereRaw('stock_quantity <= alert_quantity AND stock_quantity > 0');
            elseif ($request->status === 'out_of_stock') $query->where('stock_quantity', '<=', 0);
        }

        $totalProducts = Product::count();
        $lowStockCount = Product::where('is_active', true)->whereRaw('stock_quantity <= alert_quantity AND stock_quantity > 0')->count();
        $outOfStockCount = Product::where('is_active', true)->where('stock_quantity', '<=', 0)->count();
        $totalValue = Product::where('is_active', true)->selectRaw('SUM(stock_quantity * selling_price) as total')->value('total') ?? 0;

        $products = $query->orderBy('name')->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 20)->appends($request->query());
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.store.products', compact('products', 'categories', 'totalProducts', 'lowStockCount', 'outOfStockCount', 'totalValue'));
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:products,code',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $data = $request->only([
            'category_id', 'name', 'code', 'unit', 'purchase_price',
            'selling_price', 'stock_quantity', 'alert_quantity', 'description',
            'type', 'brand', 'model', 'warranty_months', 'barcode_prefix'
        ]);
        $data['is_active'] = $request->has('is_active');
        $data['has_serial'] = $request->has('has_serial');
        $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        $data['alert_quantity'] = $data['alert_quantity'] ?? 5;

        Product::create($data);
        return back()->with('success', 'Product added successfully.');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:products,code,' . $product->id,
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $data = $request->only([
            'category_id', 'name', 'code', 'unit', 'purchase_price',
            'selling_price', 'alert_quantity', 'description',
            'type', 'brand', 'model', 'warranty_months', 'barcode_prefix'
        ]);
        $data['is_active'] = $request->has('is_active');
        $data['has_serial'] = $request->has('has_serial');

        $product->update($data);
        return back()->with('success', 'Product updated successfully.');
    }

    public function destroyProduct(Product $product)
    {
        if ($product->stock_quantity > 0) {
            return back()->with('error', 'Cannot delete product with stock. Clear stock first.');
        }
        $product->delete();
        return back()->with('success', 'Product deleted successfully.');
    }

    public function purchases(Request $request)
    {
        $query = Purchase::with(['vendor', 'items']);

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        $summaryQuery = (clone $query);
        $totalAmount = $summaryQuery->sum('total');
        $totalPaid = $summaryQuery->sum('paid');
        $totalDue = $summaryQuery->sum('due');

        $purchases = $query->withCount('items')->orderBy('purchase_date', 'desc')->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 20);
        $vendors = Vendor::where('is_active', true)->get();

        return view('admin.store.purchases', compact('purchases', 'vendors', 'totalAmount', 'totalPaid', 'totalDue'));
    }

    public function stock()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->selectRaw('products.*,
                COALESCE((SELECT SUM(si.quantity) FROM sale_items si WHERE si.product_id = products.id), 0) as total_sold,
                COALESCE((SELECT SUM(pi.quantity) FROM purchase_items pi WHERE pi.product_id = products.id), 0) as total_purchased
            ')
            ->orderBy('name')
            ->get();

        $summary = [
            'total_products'   => $products->count(),
            'total_in_stock'   => $products->sum('stock_quantity'),
            'total_sold'       => $products->sum('total_sold'),
            'total_purchased'  => $products->sum('total_purchased'),
            'low_stock'        => $products->filter(fn($p) => $p->stock_quantity > 0 && $p->stock_quantity <= $p->alert_quantity)->count(),
            'out_of_stock'     => $products->filter(fn($p) => $p->stock_quantity <= 0)->count(),
            'stock_value'      => $products->sum(fn($p) => $p->stock_quantity * $p->purchase_price),
        ];

        return view('admin.store.stock', compact('products', 'summary'));
    }

    public function showPurchase(Purchase $purchase)
    {
        $purchase->load(['vendor', 'items.product', 'createdBy']);
        return view('admin.store.purchase-show', compact('purchase'));
    }

    public function destroyPurchase(Purchase $purchase)
    {
        foreach ($purchase->items as $item) {
            if ($item->product) {
                $item->product->decrement('stock_quantity', $item->quantity);
            }
        }
        $purchase->items()->delete();
        $purchase->delete();
        return redirect()->route('admin.store.purchases')->with('success', 'Purchase deleted successfully.');
    }

    public function recordPurchasePayment(Request $request, Purchase $purchase)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $purchase->due,
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        $amount = (float) $request->amount;

        $purchase->paid += $amount;
        $purchase->due -= $amount;
        if ($purchase->due <= 0) {
            $purchase->due = 0;
            $purchase->status = 'received';
        } else {
            $purchase->status = 'partial';
        }
        $purchase->save();

        if ($purchase->vendor) {
            $purchase->vendor->addLedgerEntry(
                'payment',
                $amount,
                0,
                'Payment for ' . $purchase->purchase_no . ($request->note ? ' - ' . $request->note : ''),
                $purchase->purchase_no . '/' . $request->payment_method,
                Auth::id()
            );
        }

        return back()->with('success', 'Payment of ৳' . number_format($amount, 2) . ' recorded successfully.');
    }

    public function createPurchase()
    {
        $vendors = Vendor::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        return view('admin.store.purchase-create', compact('vendors', 'products'));
    }

    public function storePurchase(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $discount = (float) ($request->discount ?? 0);
            $taxPercent = (float) ($request->tax_percent ?? 0);
            $taxAmount = ($subtotal - $discount) * ($taxPercent / 100);
            $total = $subtotal - $discount + $taxAmount;
            $paid = (float) ($request->paid_amount ?? $request->paid ?? 0);
            $due = $total - $paid;

            $status = 'pending';
            if ($paid >= $total) $status = 'received';
            elseif ($paid > 0) $status = 'partial';

            $purchase = Purchase::create([
                'vendor_id' => $request->vendor_id,
                'purchase_date' => $request->purchase_date,
                'invoice_number' => $request->invoice_number,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $taxAmount,
                'total' => $total,
                'paid' => $paid,
                'due' => max(0, $due),
                'status' => $status,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $itemTotal,
                ]);

                Product::where('id', $item['product_id'])->increment('stock_quantity', $item['quantity']);
            }

            $vendor = Vendor::find($request->vendor_id);
            if ($vendor) {
                $vendor->addLedgerEntry(
                    'purchase',
                    0,
                    $total,
                    'Purchase ' . $purchase->purchase_no . ($request->invoice_number ? ' (Inv: ' . $request->invoice_number . ')' : ''),
                    $purchase->purchase_no,
                    Auth::id()
                );

                if ($paid > 0) {
                    $vendor->addLedgerEntry(
                        'payment',
                        $paid,
                        0,
                        'Payment for ' . $purchase->purchase_no,
                        $purchase->purchase_no,
                        Auth::id()
                    );
                }
            }

            DB::commit();

            try {
                $vendorName = $vendor ? $vendor->name : 'N/A';
                $purchaseItems = [];
                foreach ($request->items as $itm) {
                    $p = Product::find($itm['product_id']);
                    if ($p) $purchaseItems[] = $p->name . ' x' . $itm['quantity'];
                }
                $purchaseItemsList = implode(', ', array_slice($purchaseItems, 0, 5));
                if (count($purchaseItems) > 5) $purchaseItemsList .= '...';
                (new \App\Services\TelegramService())->sendPurchaseNotification(
                    $purchase->purchase_no ?? ('PUR-' . $purchase->id),
                    $vendorName,
                    (float) $total,
                    count($request->items),
                    Auth::user()->name ?? 'System',
                    $purchaseItemsList,
                    $request->purchase_date ?? '',
                    (float) $paid
                );
            } catch (\Throwable $e) {
            }

            return redirect()->route('admin.store.purchases')->with('success', 'Purchase #' . $purchase->purchase_no . ' created successfully. Stock updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create purchase: ' . $e->getMessage());
        }
    }

    public function vendors()
    {
        $vendors = Vendor::orderBy('name')->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 20);
        return view('admin.store.vendors', compact('vendors'));
    }

    public function storeVendor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric',
        ]);

        $vendor = Vendor::create($request->only([
            'name', 'company', 'contact_person', 'email', 'phone', 'address', 'opening_balance', 'is_active'
        ]));

        $openingBalance = (float) ($request->opening_balance ?? 0);
        if ($openingBalance != 0) {
            $vendor->update(['balance' => $openingBalance]);
            $vendor->ledgerEntries()->create([
                'type' => 'opening',
                'debit' => $openingBalance > 0 ? $openingBalance : 0,
                'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
                'balance' => $openingBalance,
                'description' => 'Opening balance',
                'transaction_date' => now(),
                'created_by' => Auth::id(),
            ]);
        }

        return back()->with('success', 'Vendor created successfully.');
    }

    public function updateVendor(Request $request, Vendor $vendor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric',
        ]);

        $vendor->update($request->only([
            'name', 'company', 'contact_person', 'email', 'phone', 'address', 'opening_balance', 'is_active'
        ]));

        return back()->with('success', 'Vendor updated successfully.');
    }

    public function destroyVendor(Vendor $vendor)
    {
        if ($vendor->purchases()->count() > 0) {
            return back()->with('error', 'Cannot delete vendor with purchase history.');
        }
        $vendor->delete();
        return back()->with('success', 'Vendor deleted successfully.');
    }

    public function vendorLedger(Vendor $vendor)
    {
        $ledgerEntries = $vendor->ledgerEntries()->orderBy('created_at', 'desc')->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 30);
        $totalDebit = $vendor->ledgerEntries()->sum('debit');
        $totalCredit = $vendor->ledgerEntries()->sum('credit');

        return view('admin.store.vendor-ledger', compact('vendor', 'ledgerEntries', 'totalDebit', 'totalCredit'));
    }

    public function vendorPayment(Request $request, Vendor $vendor)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $description = $request->description ?: 'Payment via ' . ($request->payment_method ?? 'cash');

        $vendor->addLedgerEntry(
            'payment',
            $request->amount,
            0,
            $description,
            $request->reference,
            Auth::id()
        );

        return back()->with('success', 'Payment of ৳' . number_format($request->amount, 2) . ' recorded successfully.');
    }

    public function sales(Request $request)
    {
        $query = Sale::with(['client', 'items', 'creator']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'ilike', "%{$search}%")
                  ->orWhere('customer_name', 'ilike', "%{$search}%")
                  ->orWhere('customer_phone', 'ilike', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'ilike', "%{$search}%")
                         ->orWhere('client_id', 'ilike', "%{$search}%");
                  });
            });
        }

        $totalSales = Sale::count();
        $totalAmount = Sale::sum('total');
        $totalPaid = Sale::sum('paid');
        $totalDue = Sale::sum('due');

        $sales = $query->orderByDesc('sale_date')->orderByDesc('id')->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 20)->appends($request->query());

        return view('admin.store.sales', compact('sales', 'totalSales', 'totalAmount', 'totalPaid', 'totalDue'));
    }

    public function createSale()
    {
        $products = Product::where('is_active', true)->where('stock_quantity', '>', 0)->orderBy('name')->get();
        return view('admin.store.sale-create', compact('products'));
    }

    public function storeSale(Request $request)
    {
        $request->validate([
            'customer_type' => 'required|in:client,walk_in',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if ($request->customer_type === 'client') {
            $request->validate(['client_id' => 'required|exists:clients,id']);
        }

        try {
            DB::beginTransaction();

            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $discount = (float) ($request->discount ?? 0);
            $taxPercent = (float) ($request->tax_percent ?? 0);
            $taxAmount = ($subtotal - $discount) * ($taxPercent / 100);
            $total = $subtotal - $discount + $taxAmount;
            $paid = (float) ($request->paid_amount ?? 0);
            $due = $total - $paid;

            $status = 'unpaid';
            if ($paid >= $total) $status = 'paid';
            elseif ($paid > 0) $status = 'partial';

            $lastSale = Sale::withoutGlobalScopes()->orderByDesc('id')->first();
            $nextNum = $lastSale ? ((int) preg_replace('/\D/', '', $lastSale->invoice_number ?? '0')) + 1 : 1;
            $invoiceNumber = 'SL-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
            while (Sale::withoutGlobalScopes()->where("invoice_number", $invoiceNumber)->exists()) {
                $nextNum++;
                $invoiceNumber = "SL-" . str_pad($nextNum, 6, "0", STR_PAD_LEFT);
            }

            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'client_id' => $request->customer_type === 'client' ? $request->client_id : null,
                'customer_name' => $request->customer_type === 'walk_in' ? $request->customer_name : null,
                'customer_phone' => $request->customer_type === 'walk_in' ? $request->customer_phone : null,
                'customer_type' => $request->customer_type,
                'sale_date' => $request->sale_date,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'paid' => $paid,
                'due' => $due,
                'status' => $status,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock_quantity}, Requested: {$item['quantity']}");
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'serial_number' => $item['serial_number'] ?? null,
                    'brand' => $product->brand,
                    'model' => $product->model,
                ]);

                $product->decrement('stock_quantity', $item['quantity']);
            }

            DB::commit();

            try {
                $saleClient = $request->customer_type === 'client' ? \App\Models\Client::find($request->client_id) : null;
                $customerName = $saleClient ? $saleClient->name : ($request->customer_name ?? 'Walk-in');
                $pppoeUser = $saleClient ? ($saleClient->username ?? '') : '';
                $itemDetails = [];
                foreach ($request->items as $itm) {
                    $p = Product::find($itm['product_id']);
                    if ($p) {
                        $label = $p->name;
                        $extra = array_filter([$p->brand, $p->model]);
                        if ($extra) $label .= ' (' . implode(' ', $extra) . ')';
                        $itemDetails[] = $label . ' x' . $itm['quantity'];
                    }
                }
                $itemsList = implode("\n         ", array_slice($itemDetails, 0, 5));
                if (count($itemDetails) > 5) $itemsList .= "\n         ...";
                (new \App\Services\TelegramService())->sendSaleNotification(
                    $invoiceNumber,
                    $customerName,
                    (float) $total,
                    count($request->items),
                    Auth::user()->name ?? 'System',
                    $itemsList,
                    (float) $paid,
                    (float) $due,
                    ucfirst($request->payment_method ?? ''),
                    $status,
                    $request->sale_date ?? now()->format('d-m-Y'),
                    $pppoeUser,
                    $request->payment_reference ?? '',
                    $request->sender_number ?? ''
                );
            } catch (\Throwable $e) {
            }

            return redirect()->route('admin.sales.show', $sale)->with('success', 'Sale #' . $invoiceNumber . ' created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create sale: ' . $e->getMessage());
        }
    }

    public function showSale(Sale $sale)
    {
        $sale->load(['client', 'items.product', 'creator']);
        return view('admin.store.sale-show', compact('sale'));
    }

    public function destroySale(Sale $sale)
    {
        try {
            DB::beginTransaction();

            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
                }
            }

            $sale->items()->delete();
            $sale->delete();

            DB::commit();
            return redirect()->route('admin.sales.index')->with('success', 'Sale deleted and stock restored.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete sale: ' . $e->getMessage());
        }
    }

    public function recordSalePayment(Request $request, Sale $sale)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $sale->due,
        ]);

        $sale->paid += $request->amount;
        $sale->due = $sale->total - $sale->paid;
        $sale->status = $sale->due <= 0 ? 'paid' : 'partial';
        $sale->save();

        return back()->with('success', 'Payment of ৳' . number_format($request->amount, 2) . ' recorded.');
    }

    public function services(Request $request)
    {
        $query = ClientServiceCharge::with(['client', 'paidByUser'])->orderByDesc('created_at');

        $query->where('charge_type', '!=', 'Installation Charge');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->charge_type) {
            $query->where('charge_type', $request->charge_type);
        }

        if ($request->status) {
            if ($request->status === 'paid') {
                $query->whereNotNull('paid_by');
            } elseif ($request->status === 'due') {
                $query->whereNull('paid_by');
            }
        }

        $charges = $query->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 20)->appends($request->query());

        return view('admin.store.services', compact('charges'));
    }

    public function storeService(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'charge_type' => 'required|string|max:100',
            'custom_charge_type' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:cash,bkash,nagad,rocket,bank',
            'charge_date' => 'nullable|date',
        ]);

        $chargeType = $request->charge_type;
        if ($chargeType === 'Other' && $request->filled('custom_charge_type')) {
            $chargeType = $request->custom_charge_type;
        }

        ClientServiceCharge::create([
            'client_id' => $request->client_id,
            'charge_type' => $chargeType,
            'amount' => $request->amount,
            'description' => $request->description,
            'payment_method' => $request->payment_method,
            'charge_date' => $request->charge_date ?? now()->toDateString(),
            'paid_by' => Auth::id(),
        ]);

        $this->syncInstallationCharge($request->client_id);

        return back()->with('success', 'Service charge added successfully.');
    }

    public function updateService(Request $request, ClientServiceCharge $charge)
    {
        $isBaseInstall = $charge->charge_type === 'Installation Charge' && is_null($charge->paid_by);

        if ($isBaseInstall) {
            $request->validate([
                'amount' => 'required|numeric|min:0',
            ]);

            $newAmount = (float) $request->amount;
            $clientId = $charge->client_id;

            $charge->update([
                'amount' => $newAmount,
                'description' => $newAmount > 0 ? 'Installation charge' : 'Free installation',
            ]);

            $client = \App\Models\Client::find($clientId);
            if ($client) {
                $client->update([
                    'installation_charge' => $newAmount,
                ]);
            }

            return back()->with('success', 'Installation charge amount updated.');
        }

        $allowedMethods = \App\Models\PaymentMethod::where('is_active', true)
            ->where('company_id', auth()->user()->company_id)
            ->pluck('name')
            ->map(fn($n) => strtolower($n))
            ->toArray();

        $request->validate([
            'charge_type' => 'required|string|max:100',
            'custom_charge_type' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:' . implode(',', $allowedMethods),
            'transaction_id' => 'nullable|string|max:100',
            'sender_number' => 'nullable|string|max:50',
            'money_receipt_no' => 'nullable|string|max:100',
            'charge_date' => 'nullable|date',
        ]);

        $chargeType = $request->charge_type;
        if ($chargeType === 'Other' && $request->filled('custom_charge_type')) {
            $chargeType = $request->custom_charge_type;
        }

        $oldChargeType = $charge->charge_type;
        $oldAmount = (float) $charge->amount;
        $clientId = $charge->client_id;

        $charge->update([
            'charge_type' => $chargeType,
            'amount' => $request->amount,
            'description' => $request->description,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'sender_number' => $request->sender_number,
            'money_receipt_no' => $request->money_receipt_no,
            'charge_date' => $request->charge_date ?? now()->toDateString(),
        ]);

        $this->syncInstallationCharge($clientId);

        return back()->with('success', 'Service charge updated successfully.');
    }

    public function deleteService(ClientServiceCharge $charge)
    {
        $clientId = $charge->client_id;
        $charge->delete();
        $this->syncInstallationCharge($clientId);
        return back()->with('success', 'Service charge deleted.');
    }

    private function syncInstallationCharge($clientId)
    {
        $client = \App\Models\Client::find($clientId);
        if (!$client) return;

        $installTotal = (float) ClientServiceCharge::where('client_id', $clientId)
            ->where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')
            ->sum('amount');

        if ($installTotal > 0) {
            $client->update(['installation_charge' => $installTotal]);
        }
    }
}
