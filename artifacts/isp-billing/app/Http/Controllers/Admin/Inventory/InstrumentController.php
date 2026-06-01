<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instrument;
use App\Models\Vendor;
use App\Models\InstrumentCategory;
use App\Models\Employee;

class InstrumentController extends Controller
{
    public function index()
    {
        $instruments = Instrument::with('vendor')->latest()->get();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $categories = InstrumentCategory::where('is_active', true)->orderBy('name')->get();
        $parentCategories = InstrumentCategory::parentCategories()->active()->with('children')->orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();
        return view('admin.inventory.instruments.index', compact('instruments', 'vendors', 'categories', 'parentCategories', 'employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string|max:255',
            'purchase_date'  => 'nullable|date',
            'vendor_id'      => 'nullable|exists:vendors,id',
            'products'       => 'required|array|min:1',
            'products.*.category'       => 'required|string|max:255',
            'products.*.sub_category'   => 'nullable|string|max:255',
            'products.*.brand'          => 'nullable|string|max:255',
            'products.*.model'          => 'nullable|string|max:255',
            'products.*.quantity'       => 'required|integer|min:1',
            'products.*.purchase_price' => 'nullable|numeric|min:0',
            'products.*.serials'        => 'nullable|array',
            'products.*.serials.*'      => 'nullable|string|max:255',
        ]);

        try {
            $invoiceNumber = $request->invoice_number;
            $purchaseDate = $request->purchase_date;
            $vendorId = $request->vendor_id;
            $purchaser = $request->purchaser;
            $totalCreated = 0;

            $allSerials = [];
            foreach ($request->products as $product) {
                $serials = array_filter($product['serials'] ?? [], fn($s) => !empty(trim($s)));
                foreach ($serials as $s) {
                    $lower = strtolower(trim($s));
                    if (in_array($lower, $allSerials)) {
                        return back()->with('error', 'Duplicate serial "' . $s . '" found in your submission.')->withInput();
                    }
                    $allSerials[] = $lower;
                }
            }

            if (!empty($allSerials)) {
                $existingCount = Instrument::whereRaw('LOWER(serial_number) IN (' . implode(',', array_fill(0, count($allSerials), '?')) . ')', $allSerials)->count();
                if ($existingCount > 0) {
                    return back()->with('error', 'One or more serial numbers already exist in the database.')->withInput();
                }
            }

            foreach ($request->products as $product) {
                $mainCategory = $product['category'];
                $subCategory = !empty($product['sub_category']) ? $product['sub_category'] : null;

                $serials = array_values(array_filter($product['serials'] ?? [], fn($s) => !empty(trim($s))));

                $baseData = [
                    'name'           => $mainCategory,
                    'invoice_number' => $invoiceNumber,
                    'category'       => $mainCategory,
                    'sub_category'   => $subCategory,
                    'brand'          => $product['brand'] ?? null,
                    'model'          => $product['model'] ?? null,
                    'purchase_price' => $product['purchase_price'] ?? null,
                    'purchase_date'  => $purchaseDate,
                    'vendor_id'      => $vendorId,
                    'purchaser'      => $purchaser,
                    'condition'      => $product['condition'] ?? 'new',
                    'status'         => 'available',
                ];

                if (!empty($serials)) {
                    foreach ($serials as $serial) {
                        Instrument::create(array_merge($baseData, ['serial_number' => trim($serial), 'quantity' => 1]));
                        $totalCreated++;
                    }
                } else {
                    $qty = (int) ($product['quantity'] ?? 1);
                    Instrument::create(array_merge($baseData, ['quantity' => $qty]));
                    $totalCreated++;
                }
            }

            return redirect()->route('admin.inventory.instruments.index')
                ->with('success', $totalCreated . ' product(s) added under invoice "' . $invoiceNumber . '".');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $instrument = Instrument::findOrFail($id);
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $categories = InstrumentCategory::where('is_active', true)->orderBy('name')->get();
        $parentCategories = InstrumentCategory::parentCategories()->active()->orderBy('name')->get();
        return view('admin.inventory.instruments.edit', compact('instrument', 'vendors', 'categories', 'parentCategories'));
    }

    public function update(Request $request, $id)
    {
        $instrument = Instrument::findOrFail($id);

        $request->validate([
            'invoice_number' => 'nullable|string|max:255',
            'model'          => 'nullable|string|max:255',
            'brand'          => 'nullable|string|max:255',
            'serial_number'  => 'nullable|string|max:255',
            'category'       => 'nullable|string|max:255',
            'sub_category'   => 'nullable|string|max:255',
            'purchase_price' => 'nullable|numeric|min:0',
            'quantity'       => 'nullable|integer|min:1',
            'purchase_date'  => 'nullable|date',
            'vendor_id'      => 'nullable|exists:vendors,id',
            'condition'      => 'required|in:good,fair,poor,damaged,new,used,replace,replaced,dead',
            'status'         => 'required|in:available,in_use,under_repair,retired',
            'assigned_to'    => 'nullable|string|max:255',
            'warranty_expiry' => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        try {
            $data = $request->only([
                'invoice_number', 'model', 'brand', 'serial_number', 'category', 'sub_category',
                'purchase_price', 'quantity', 'purchase_date', 'vendor_id', 'condition',
                'status', 'assigned_to', 'warranty_expiry', 'notes'
            ]);
            if (!empty($data['category'])) {
                $data['name'] = $data['category'];
            }
            $instrument->update($data);
            return redirect()->route('admin.inventory.instruments.index')
                ->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $instrument = Instrument::findOrFail($id);
            $instrument->delete();
            return redirect()->route('admin.inventory.instruments.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroyGroup(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return back()->with('error', 'No items selected for deletion.');
            }
            $count = Instrument::whereIn('id', $ids)->delete();
            return redirect()->route('admin.inventory.instruments.index')
                ->with('success', $count . ' product(s) deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function checkSerialDuplicate(Request $request)
    {
        $serial = trim($request->input('serial', ''));
        if (empty($serial)) {
            return response()->json(['exists' => false]);
        }
        $exists = Instrument::whereRaw('LOWER(serial_number) = ?', [strtolower($serial)])->exists();
        return response()->json(['exists' => $exists]);
    }

    public function categoriesPage()
    {
        $categories = InstrumentCategory::parentCategories()->with('children')->orderBy('name')->get();
        return view('admin.inventory.instruments.categories', compact('categories'));
    }

    public function categoryList()
    {
        $categories = InstrumentCategory::orderBy('name')->get();
        return response()->json($categories);
    }

    public function categoryStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:instrument_categories,id',
            'types' => 'nullable|array',
            'types.*' => 'nullable|string|max:255',
        ]);

        $category = InstrumentCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'is_active' => true,
            'has_serial' => $request->has('has_serial') ? true : false,
        ]);

        if ($request->has('types') && !$request->parent_id) {
            $types = array_filter($request->types, fn($t) => !empty(trim($t)));
            foreach ($types as $typeName) {
                InstrumentCategory::create([
                    'name' => trim($typeName),
                    'parent_id' => $category->id,
                    'is_active' => true,
                ]);
            }
        }

        $msg = 'Category "' . $category->name . '" added successfully.';
        if (!empty($types)) {
            $msg .= ' ' . count($types) . ' type(s) added.';
        }

        return redirect()->route('admin.inventory.instrument-categories.page')
            ->with('success', $msg);
    }

    public function categoryUpdate(Request $request, $id)
    {
        $cat = InstrumentCategory::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255|unique:instrument_categories,name,' . $id,
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:instrument_categories,id',
        ]);

        if ($request->parent_id == $id) {
            return back()->with('error', 'A category cannot be its own parent.');
        }

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
        ];
        if (!$request->parent_id) {
            $updateData['has_serial'] = $request->has('has_serial') ? true : false;
        }
        $cat->update($updateData);

        return redirect()->route('admin.inventory.instrument-categories.page')
            ->with('success', 'Category updated successfully.');
    }

    public function categoryDestroy($id)
    {
        $cat = InstrumentCategory::findOrFail($id);

        $names = collect([$cat->name]);
        if ($cat->children->count() > 0) {
            $names = $names->merge($cat->children->pluck('name'));
        }
        $usedCount = Instrument::whereIn('category', $names)->count();
        if ($usedCount > 0) {
            return redirect()->route('admin.inventory.instrument-categories.page')
                ->with('error', 'Cannot delete "' . $cat->name . '" — ' . $usedCount . ' product(s) are using this category.');
        }

        $cat->delete();
        return redirect()->route('admin.inventory.instrument-categories.page')
            ->with('success', 'Category deleted successfully.');
    }

    public function getSubCategories($parentId)
    {
        $subs = InstrumentCategory::where('parent_id', $parentId)->where('is_active', true)->orderBy('name')->get();
        return response()->json($subs);
    }

    public function setInstalledBy(\Illuminate\Http\Request $request, $id)
    {
        $request->validate(["technician_id" => "required|exists:users,id"]);
        $_icid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        \DB::table("instrument_out_logs")
            ->where("id", $id)
            ->when($_icid, fn($q) => $q->where("company_id", $_icid))
            ->update(["technician_id" => $request->technician_id]);
        return back()->with("success", "Installed By updated successfully.");
    }

}
