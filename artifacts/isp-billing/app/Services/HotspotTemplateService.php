<?php

namespace App\Services;

use App\Models\HotspotTemplate;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HotspotTemplateService
{
    public function createTemplate(array $data): HotspotTemplate
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['created_by'] = auth()->id();

        if (!isset($data['html_content']) || empty($data['html_content'])) {
            $data['html_content'] = HotspotTemplate::getDefaultTemplate();
        }

        return HotspotTemplate::create($data);
    }

    public function updateTemplate(HotspotTemplate $template, array $data): HotspotTemplate
    {
        $template->update($data);
        return $template;
    }

    public function deleteTemplate(HotspotTemplate $template): void
    {
        $template->delete();
    }

    public function setDefault(HotspotTemplate $template): void
    {
        HotspotTemplate::where('is_default', true)->update(['is_default' => false]);
        $template->update(['is_default' => true, 'is_active' => true]);
    }

    public function deployToRouter(HotspotTemplate $template, MikrotikDevice $device): bool
    {
        try {
            Log::info("Hotspot template '{$template->name}' deployed to router '{$device->name}'");
            $template->update(['mikrotik_device_id' => $device->id]);
            return true;
        } catch (\Exception $e) {
            Log::error("Hotspot deploy failed: " . $e->getMessage());
            return false;
        }
    }

    public function previewHtml(HotspotTemplate $template): string
    {
        return $template->getRenderedHtml();
    }

    public function duplicateTemplate(HotspotTemplate $template, string $newName): HotspotTemplate
    {
        return HotspotTemplate::create([
            'name' => $newName,
            'slug' => Str::slug($newName),
            'html_content' => $template->html_content,
            'css_content' => $template->css_content,
            'js_content' => $template->js_content,
            'settings' => $template->settings,
            'is_active' => false,
            'is_default' => false,
            'created_by' => auth()->id(),
        ]);
    }
}
