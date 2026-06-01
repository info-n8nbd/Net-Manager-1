<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory\Vendor;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:15);
        return view('admin.inventory.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.inventory.vendors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        Vendor::create([
            'name' => $request->name,
            'company' => $request->company_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'contact_person' => $request->contact_person,
            'balance' => $request->opening_balance ?? 0,
            'opening_balance' => $request->opening_balance ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.inventory.vendors.index')
            ->with('success', 'Vendor created successfully');
    }

    public function edit(Vendor $vendor)
    {
        return view('admin.inventory.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $vendor->update([
            'name' => $request->name,
            'company' => $request->company_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'contact_person' => $request->contact_person,
            'opening_balance' => $request->opening_balance ?? $vendor->opening_balance,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.inventory.vendors.index')
            ->with('success', 'Vendor updated successfully');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
        return back()->with('success', 'Vendor deleted successfully');
    }
}
