<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotspotTemplate;
use App\Models\MikrotikDevice;
use App\Services\HotspotTemplateService;
use Illuminate\Http\Request;

class HotspotEditorController extends Controller
{
    protected HotspotTemplateService $hotspotTemplateService;

    public function __construct(HotspotTemplateService $hotspotTemplateService)
    {
        $this->hotspotTemplateService = $hotspotTemplateService;
    }

    public function index()
    {
        $templates = HotspotTemplate::with('mikrotikDevice')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.hotspot.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.hotspot.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'nullable|string',
            'css_content' => 'nullable|string',
            'js_content' => 'nullable|string',
        ]);

        $template = $this->hotspotTemplateService->createTemplate($request->only([
            'name', 'slug', 'html_content', 'css_content', 'js_content', 'settings',
        ]));

        return redirect()->route('admin.hotspot.edit', $template)
            ->with('success', 'Hotspot template created successfully.');
    }

    public function edit(HotspotTemplate $template)
    {
        return view('admin.hotspot.edit', compact('template'));
    }

    public function update(Request $request, HotspotTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'nullable|string',
            'css_content' => 'nullable|string',
            'js_content' => 'nullable|string',
        ]);

        $this->hotspotTemplateService->updateTemplate($template, $request->only([
            'name', 'html_content', 'css_content', 'js_content', 'settings',
        ]));

        return back()->with('success', 'Hotspot template updated successfully.');
    }

    public function destroy(HotspotTemplate $template)
    {
        if ($template->is_default) {
            return back()->with('error', 'Cannot delete the default template.');
        }

        $this->hotspotTemplateService->deleteTemplate($template);

        return redirect()->route('admin.hotspot.index')
            ->with('success', 'Hotspot template deleted successfully.');
    }

    public function preview(HotspotTemplate $template)
    {
        $html = $this->hotspotTemplateService->previewHtml($template);
        return response($html)->header('Content-Type', 'text/html');
    }

    public function setDefault(HotspotTemplate $template)
    {
        $this->hotspotTemplateService->setDefault($template);

        return back()->with('success', "'{$template->name}' has been set as the default template.");
    }

    public function deploy(Request $request, HotspotTemplate $template)
    {
        $request->validate([
            'mikrotik_device_id' => 'required|exists:mikrotik_devices,id',
        ]);

        $device = MikrotikDevice::findOrFail($request->mikrotik_device_id);
        $success = $this->hotspotTemplateService->deployToRouter($template, $device);

        if ($success) {
            return back()->with('success', "Template deployed to '{$device->name}' successfully.");
        }

        return back()->with('error', 'Failed to deploy template to router.');
    }

    public function duplicate(HotspotTemplate $template)
    {
        $newName = $template->name . ' (Copy)';
        $this->hotspotTemplateService->duplicateTemplate($template, $newName);

        return back()->with('success', 'Template duplicated successfully.');
    }
}
