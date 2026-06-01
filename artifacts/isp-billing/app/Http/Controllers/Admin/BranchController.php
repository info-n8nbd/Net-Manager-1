<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    protected BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function index()
    {
        $branches = Branch::withCount(['clients', 'pops', 'zones'])
            ->with('manager')
            ->latest()
            ->get();

        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        $managers = User::where('user_type', 'manager')->orWhere('user_type', 'admin')->get();
        return view('admin.branches.create', compact('managers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        Branch::create($request->only(['name', 'code', 'address', 'phone', 'email', 'contact_person', 'manager_id', 'is_main']));

        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully');
    }

    public function show(Branch $branch)
    {
        $branch->load(['pops.zone', 'pops.mikrotikDevice', 'zones', 'manager']);
        $stats = $this->branchService->getBranchStats($branch->id);
        return view('admin.branches.show', compact('branch', 'stats'));
    }

    public function edit(Branch $branch)
    {
        $managers = User::where('user_type', 'manager')->orWhere('user_type', 'admin')->get();
        return view('admin.branches.edit', compact('branch', 'managers'));
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches,code,' . $branch->id,
        ]);

        $branch->update($request->only(['name', 'code', 'address', 'phone', 'email', 'contact_person', 'manager_id', 'is_main', 'is_active']));

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return redirect()->route('admin.branches.index')->with('success', 'Branch deleted successfully');
    }
}
