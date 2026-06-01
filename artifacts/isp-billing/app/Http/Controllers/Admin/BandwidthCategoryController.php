<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BandwidthCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BandwidthCategoryController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $categories = BandwidthCategory::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->orderBy('sort_order')->orderBy('id')->get();
        return view('admin.bandwidth-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $request->validate([
            'label'      => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:1|max:999',
        ]);

        // Auto-generate unique key from label
        $baseKey = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $request->label));
        $baseKey = trim($baseKey, '_');
        $key = $baseKey;
        $i = 2;
        while (DB::table('bandwidth_categories')->where('company_id', $companyId)->where('key', $key)->exists()) {
            $key = $baseKey . '_' . $i++;
        }

        BandwidthCategory::create([
            'company_id' => $companyId,
            'key'        => $key,
            'label'      => $request->label,
            'icon'       => 'fas fa-circle',
            'color'      => 'secondary',
            'sort_order' => $request->sort_order ?? 99,
            'is_system'  => false,
            'mbps_field' => null,
            'rate_field' => null,
        ]);

        return back()->with('success', 'Bandwidth category "' . $request->label . '" created successfully.');
    }

    public function update(Request $request, BandwidthCategory $bandwidthCategory)
    {
        $companyId = auth()->user()->company_id;
        $rules = [
            'label'      => 'required|string|max:100',
            'icon'       => 'required|string|max:60',
            'color'      => 'required|string|max:30',
            'sort_order' => 'nullable|integer|min:1|max:999',
        ];
        if (!$bandwidthCategory->is_system) {
            $rules['key'] = ['required','string','max:50','alpha_dash',
                              Rule::unique('bandwidth_categories')->where('company_id', $companyId)->ignore($bandwidthCategory->id)];
        }
        $request->validate($rules);

        $data = [
            'label'      => $request->label,
            'icon'       => $request->icon,
            'color'      => $request->color,
            'sort_order' => $request->sort_order ?? $bandwidthCategory->sort_order,
        ];
        if (!$bandwidthCategory->is_system) {
            $data['key'] = strtolower($request->key);
        }

        $bandwidthCategory->update($data);
        return back()->with('success', 'Category "' . $bandwidthCategory->label . '" updated.');
    }

    public function destroy(BandwidthCategory $bandwidthCategory)
    {
        if ($bandwidthCategory->is_system) {
            return back()->with('error', 'System categories cannot be deleted.');
        }
        $name = $bandwidthCategory->label;
        $bandwidthCategory->delete();
        return back()->with('success', 'Category "' . $name . '" deleted.');
    }
}
