<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DropdownOption;
use Illuminate\Http\Request;

class DropdownOptionController extends Controller
{
    public function index()
    {
        $groupLabels = DropdownOption::getGroupLabels();
        $groups = [];

        foreach ($groupLabels as $key => $label) {
            $groups[$key] = [
                'label' => $label,
                'options' => DropdownOption::where('group_name', $key)
                    ->orderBy('sort_order')
                    ->get(),
            ];
        }

        return view('admin.settings.dropdown-options', compact('groups', 'groupLabels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'group_name' => 'required|string|max:100',
            'label' => 'required|string|max:255',
        ]);

        $groupLabels = DropdownOption::getGroupLabels();
        if (!array_key_exists($request->group_name, $groupLabels)) {
            return back()->with('error', 'Invalid group.');
        }

        $maxSort = DropdownOption::where('group_name', $request->group_name)->max('sort_order') ?? -1;

        DropdownOption::create([
            'group_name' => $request->group_name,
            'label' => $request->label,
            'sort_order' => $maxSort + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Option added successfully.');
    }

    public function update(Request $request, DropdownOption $dropdownOption)
    {
        $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $dropdownOption->update([
            'label' => $request->label,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'label' => $dropdownOption->label]);
        }

        return back()->with('success', 'Option updated successfully.');
    }

    public function toggleActive(DropdownOption $dropdownOption)
    {
        $dropdownOption->update(['is_active' => !$dropdownOption->is_active]);

        return back()->with('success', 'Option ' . ($dropdownOption->is_active ? 'enabled' : 'disabled') . '.');
    }

    public function destroy(DropdownOption $dropdownOption)
    {
        $dropdownOption->delete();

        return back()->with('success', 'Option deleted.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:dropdown_options,id',
        ]);

        foreach ($request->ids as $index => $id) {
            DropdownOption::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
