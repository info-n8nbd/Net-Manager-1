<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory\ProductCategory;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::latest()->get();

        $_cid = (auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null;
        $typesByCategory = \DB::table('instruments')
            ->select('category', 'sub_category', \DB::raw('count(*) as count'))
            ->whereNotNull('sub_category')
            ->when($_cid, fn($q) => $q->where('company_id', $_cid))
            ->groupBy('category', 'sub_category')
            ->orderBy('sub_category')
            ->get()
            ->groupBy('category');

        return view('admin.inventory.product_categories.index', compact('categories', 'typesByCategory'));
    }

    public function create()
    {
        return view('admin.inventory.product_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        ProductCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.inventory.product-categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit($id)
    {
        $category = ProductCategory::findOrFail($id);
        return view('admin.inventory.product_categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.inventory.product-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        ProductCategory::findOrFail($id)->delete();

        return redirect()->route('admin.inventory.product-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
