<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Models\CompanyProfile;
use App\Models\Package;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Box;
use App\Models\District;
use App\Models\Upazila;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodPerm;
use App\Models\SystemSetting;
use Spatie\Permission\Models\Role;
use App\Services\PackageProfileSyncService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function company()
    {
        $companyId = auth()->check() && auth()->user()->company_id ? auth()->user()->company_id : 1;
        $company = CompanyProfile::where('company_id', $companyId)->first();
        $parentCompany = \App\Models\Company::find($companyId);
        if (!$parentCompany) {
            $parentCompany = \App\Services\TenantContext::get();
        }
        if (!$company) {
            $company = new CompanyProfile(['company_id' => $companyId]);
        }
        if ($parentCompany && empty($company->name)) {
            $company->name = $parentCompany->name;
            $company->email = $company->email ?: $parentCompany->email;
            $company->phone = $company->phone ?: $parentCompany->phone;
            $company->alt_phone = $company->alt_phone ?: $parentCompany->alt_phone;
            $company->address = $company->address ?: $parentCompany->address;
            $company->website = $company->website ?: $parentCompany->website;
            $company->btrc_license = $company->btrc_license ?: $parentCompany->btrc_license;
            $company->bin_no = $company->bin_no ?: $parentCompany->bin_no;
            $company->trade_license = $company->trade_license ?: $parentCompany->trade_license;
        }
        $logo = SystemSetting::get('site_logo', '');
        $logoLight = SystemSetting::get('site_logo_light', '');
        $favicon = SystemSetting::get('site_favicon', '');
        $appIcon = SystemSetting::get('site_app_icon', '');
        return view('admin.settings.company', compact('company', 'logo', 'logoLight', 'favicon', 'appIcon'));
    }

    public function updateCompany(Request $request)
    {
        $companyIdForProfile = auth()->user()->company_id ?? 1;
        $company = CompanyProfile::where('company_id', $companyIdForProfile)->first()
            ?? new CompanyProfile(['company_id' => $companyIdForProfile]);
        $company->fill($request->only([
            'name', 'email', 'phone', 'alt_phone', 'address', 'website',
            'currency_symbol', 'currency_code', 'btrc_license', 'bin_no',
            'trade_license', 'invoice_footer', 'terms_conditions'
        ]));
        $company->save();
        if ($request->filled('name')) {
            SystemSetting::set('company_name', $request->name);
        }
        if ($request->has('company_tagline')) {
            SystemSetting::set('company_tagline', $request->company_tagline);
        }
        if ($request->has('ticker_label')) {
            SystemSetting::set('ticker_label', $request->ticker_label);
        }
        if ($request->has('ticker_text')) {
            SystemSetting::set('ticker_text', $request->ticker_text);
        }


        $companyLogoUpdate = [];
        $companyId = auth()->user()->company_id ?? \App\Services\TenantContext::id() ?? 1;
        $logoDir = public_path('images/company-' . $companyId);
        if (!is_dir($logoDir)) { mkdir($logoDir, 0755, true); }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo.' . $file->getClientOriginalExtension();
            $file->move($logoDir, $filename);
            $logoPath = '/images/company-' . $companyId . '/' . $filename;
            SystemSetting::set('site_logo', $logoPath, 'branding');
            $companyLogoUpdate['logo'] = $logoPath;
        }
        if ($request->hasFile('logo_light')) {
            $file = $request->file('logo_light');
            $filename = 'logo-light.' . $file->getClientOriginalExtension();
            $file->move($logoDir, $filename);
            $logoLightPath = '/images/company-' . $companyId . '/' . $filename;
            SystemSetting::set('site_logo_light', $logoLightPath, 'branding');
            $companyLogoUpdate['logo_light'] = $logoLightPath;
        }
        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            $filename = 'favicon.' . $file->getClientOriginalExtension();
            $file->move($logoDir, $filename);
            $faviconFullPath = $logoDir . '/'. $filename;
            try {
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['png','jpg', 'jpeg', 'gif']) && function_exists('imagecreatefrompng')) {
                    $createFn = match($ext) { 'gif' => 'imagecreatefromgif', 'png' => 'imagecreatefrompng', default => 'imagecreatefromjpeg' };
                    $srcImg = $createFn($faviconFullPath);
                    if ($srcImg) {
                        $resized = imagecreatetruecolor(64, 64);
                        imagealphablending($resized, false); imagesavealpha($resized, true);
                        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                        imagefill($resized, 0, 0, $transparent);
                        imagecopyresampled($resized, $srcImg, 0, 0, 0, 0, 64, 64, imagesx($srcImg), imagesy($srcImg));
                        $saveFn = match($ext) { 'gif' => 'imagegif', 'png' => 'imagepng', default => 'imagejpeg' };
                        $saveFn($resized, $faviconFullPath);
                        imagedestroy($srcImg); imagedestroy($resized);
                    }
                }
            } catch (\Throwable $e) {}
            SystemSetting::set('site_favicon', '/images/company-' . $companyId . '/' . $filename, 'branding');
        }
        if ($request->hasFile('app_icon')) {
            $file = $request->file('app_icon');
            $filename = 'app-icon.' . $file->getClientOriginalExtension();
            $file->move($logoDir, $filename);
            SystemSetting::set('site_app_icon', '/images/company-' . $companyId . '/' . $filename, 'branding');
        }

        $companyId = auth()->user()->company_id ?? \App\Services\TenantContext::id();
        if ($companyId) {
            $updateData = [];
            if ($request->filled('name')) $updateData['name'] = $request->name;
            if ($request->has('company_tagline')) $updateData['slogan'] = $request->company_tagline;
            if ($request->filled('email')) $updateData['email'] = $request->email;
            if ($request->filled('phone')) $updateData['phone'] = $request->phone;
            if ($request->filled('alt_phone')) $updateData['alt_phone'] = $request->alt_phone;
            if ($request->filled('address')) $updateData['address'] = $request->address;
            if ($request->filled('website')) $updateData['website'] = $request->website;
            if ($request->filled('btrc_license')) $updateData['btrc_license'] = $request->btrc_license;
            if ($request->filled('bin_no')) $updateData['bin_no'] = $request->bin_no;
            if ($request->filled('trade_license')) $updateData['trade_license'] = $request->trade_license;
            if ($request->filled('invoice_footer')) $updateData['invoice_footer'] = $request->invoice_footer;
            if ($request->has('ticker_label')) $updateData['ticker_label'] = $request->ticker_label;
            if ($request->has('ticker_text')) $updateData['ticker_text'] = $request->ticker_text;
            $updateData = array_merge($updateData, $companyLogoUpdate);
            if (!empty($updateData)) {
                \App\Models\Company::where('id', $companyId)->update($updateData);
            }
        }

        return back()->with('success', 'Company profile updated successfully.');
    }

    public function packages()
    {
        $packages = Package::withCount('clients')->with('boundProfile')->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $profiles = \App\Models\MikrotikProfile::withCount('clients')->orderBy('name')->get();
        $routers = \App\Models\MikrotikDevice::where('is_active', true)->orderBy('name')->get();
        return view('admin.settings.packages', compact('packages', 'profiles', 'routers'));
    }

    public function storePackage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'download_speed' => 'required|integer|min:1',
            'upload_speed' => 'required|integer|min:1',
        ]);

        $code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $request->name));
        $codeBase = $code;
        $counter = 1;
        while (Package::where('code', $code)->exists()) {
            $code = $codeBase . '_' . $counter;
            $counter++;
        }

        Package::create([
            'name' => $request->name,
            'code' => $code,
            'price' => $request->price,
            'bandwidth' => $request->download_speed . '/' . $request->upload_speed . ' ' . ($request->speed_unit ?? 'Mbps'),
            'download_speed' => $request->download_speed,
            'upload_speed' => $request->upload_speed,
            'speed_unit' => $request->speed_unit ?? 'Mbps',
            'connection_type' => $request->connection_type ?? 'PPPoE',
            'validity_days' => $request->validity_days ?? 30,
            'description' => $request->description,
            'btrc_amount' => $request->btrc_amount ?: null,
            'is_active' => $request->has('is_active'),
            'show_in_public' => $request->has('show_in_public'),
            'mikrotik_profile' => $request->mikrotik_profile,
        ]);

        return back()->with('success', 'Package created successfully.');
    }

    public function updatePackage(Request $request, Package $package)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'download_speed' => 'required|integer|min:0',
            'upload_speed' => 'required|integer|min:0',
        ]);

        $package->update([
            'name' => $request->name,
            'code' => $package->code,
            'price' => $request->price,
            'bandwidth' => $request->download_speed . '/' . $request->upload_speed . ' ' . ($request->speed_unit ?? 'Mbps'),
            'download_speed' => $request->download_speed,
            'upload_speed' => $request->upload_speed,
            'speed_unit' => $request->speed_unit ?? 'Mbps',
            'connection_type' => $request->connection_type ?? 'PPPoE',
            'validity_days' => $request->validity_days ?? 30,
            'description' => $request->description,
            'btrc_amount' => $request->btrc_amount ?: null,
            'is_active' => $request->has('is_active'),
            'show_in_public' => $request->has('show_in_public'),
            'mikrotik_profile' => $request->mikrotik_profile,
        ]);

        return back()->with('success', 'Package updated successfully.');
    }

    public function importPackagesFromRouter()
    {
        try {
            $syncService = app(PackageProfileSyncService::class);
            $result = $syncService->importProfilesFromRouters();

            $msg = "Import complete: {$result['imported']} new package(s) added";
            if ($result['updated'] > 0) {
                $msg .= ", {$result['updated']} updated";
            }
            if ($result['skipped'] > 0) {
                $msg .= ", {$result['skipped']} already exist (unchanged)";
            }
            if (!empty($result['removed']) && $result['removed'] > 0) {
                $msg .= ", {$result['removed']} removed (not on router)";
            }
            if (!empty($result['deactivated']) && $result['deactivated'] > 0) {
                $msg .= ", {$result['deactivated']} deactivated (not on router but has clients)";
            }
            if (!empty($result['errors'])) {
                $msg .= ". Warnings: " . implode('; ', $result['errors']);
            }
            if (!empty($result['profiles_found'])) {
                $msg .= ". Profiles found: " . implode(', ', $result['profiles_found']);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to import packages from router: ' . $e->getMessage());
        }
    }

    public function syncPackagesToRouter()
    {
        try {
            $syncService = app(PackageProfileSyncService::class);
            $result = $syncService->syncAllPackages();

            $msg = "Sync complete: {$result['synced']} package(s) synced to routers";
            if ($result['created'] > 0) {
                $msg .= " ({$result['created']} created)";
            }
            if ($result['updated'] > 0) {
                $msg .= " ({$result['updated']} updated)";
            }
            if (!empty($result['errors'])) {
                $msg .= ". Warnings: " . implode('; ', $result['errors']);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to sync packages to routers: ' . $e->getMessage());
        }
    }

    public function destroyPackage(Package $package)
    {
        if ($package->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete package with assigned clients.');
        }
        $package->delete();
        return back()->with('success', 'Package deleted successfully.');
    }

    public function bulkDeletePackages(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No packages selected.');
        }
        
        $deleted = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            $package = Package::find($id);
            if ($package) {
                if ($package->clients()->count() > 0) {
                    $skipped++;
                } else {
                    $package->delete();
                    $deleted++;
                }
            }
        }
        
        $msg = "$deleted package(s) deleted.";
        if ($skipped > 0) $msg .= " $skipped skipped (have clients).";
        return back()->with('success', $msg);
    }

    public function zones()
    {
        $zones = Zone::with(['areas', 'upazila.district'])->whereNull('reseller_id')->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $divisions = \App\Models\District::where('is_active', true)
            ->whereNotNull('division')->where('division', '!=', '')
            ->distinct()->orderBy('division')->pluck('division');
        $districts = \App\Models\District::where('is_active', true)->orderBy('name')->get();
        $upazilas = \App\Models\Upazila::with('district')->where('is_active', true)->orderBy('name')->get();
        return view('admin.settings.zones', compact('zones', 'divisions', 'districts', 'upazilas'));
    }

    public function storeZone(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'upazila_id' => 'nullable|exists:upazilas,id',
        ]);
        Zone::create([
            'name' => $request->name,
            'description' => $request->description,
            'upazila_id' => $request->upazila_id ?: null,
            'is_active' => true,
        ]);
        return back()->with('success', 'Zone created successfully.');
    }

    public function updateZone(Request $request, Zone $zone)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'upazila_id' => 'nullable|exists:upazilas,id',
        ]);
        $zone->update([
            'name' => $request->name,
            'description' => $request->description,
            'upazila_id' => $request->upazila_id ?: null,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'Zone updated successfully.');
    }

    public function destroyZone(Zone $zone)
    {
        if ($zone->areas()->count() > 0) {
            return back()->with('error', 'Cannot delete zone with assigned sub zones. Please delete or move the sub zones first.');
        }
        $activeClients = $zone->clients()->where('status', '!=', 'deleted')->count();
        if ($activeClients > 0) {
            return back()->with('error', 'Cannot delete zone with ' . $activeClients . ' active client(s). Please move or delete them first.');
        }
        $this->cleanupZoneReferences($zone);
        $zone->delete();
        return back()->with('success', 'Zone deleted successfully.');
    }

    private function cleanupZoneReferences(Zone $zone): void
    {
        $zoneId = $zone->id;

        // Null out zone_id in all FK-referencing tables
        \DB::table('clients')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('boxes')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('ip_pools')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('pops')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('mikrotik_devices')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('collection_targets')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('planned_maintenances')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('maintenance_modes')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('reseller_boundaries')->where('zone_id', $zoneId)->delete();
        \DB::table('area_outage_logs')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('new_connection_applications')->where('zone_id', $zoneId)->update(['zone_id' => null]);
        \DB::table('area_profitability')->where('zone_id', $zoneId)->delete();

        // Remove zone from employee zone_ids JSON arrays
        $employees = \DB::table('employees')->whereNotNull('zone_ids')->get();
        foreach ($employees as $emp) {
            $ids = json_decode($emp->zone_ids, true);
            if (is_array($ids) && in_array($zoneId, $ids)) {
                $updated = array_values(array_filter($ids, fn($id) => $id != $zoneId));
                \DB::table('employees')->where('id', $emp->id)->update([
                    'zone_ids' => empty($updated) ? null : json_encode($updated),
                ]);
            }
        }
    }

    public function bulkDeleteZones(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No zones selected.');
        }
        
        $deleted = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            $zone = Zone::find($id);
            if ($zone) {
                $activeClients = $zone->clients()->where('status', '!=', 'deleted')->count();
                if ($zone->areas()->count() > 0 || $activeClients > 0) {
                    $skipped++;
                } else {
                    $this->cleanupZoneReferences($zone);
                    $zone->delete();
                    $deleted++;
                }
            }
        }
        
        $msg = "$deleted zone(s) deleted.";
        if ($skipped > 0) $msg .= " $skipped skipped (have sub zones/clients).";
        if ($request->expectsJson()) return response()->json(['message' => $msg]);
        return back()->with('success', $msg);
    }

    public function paymentMethods()
    {
        $methods = PaymentMethod::withCount('payments')
            ->where('company_id', auth()->user()->company_id)
            ->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $allMethods = PaymentMethod::where('is_active', true)
            ->where('company_id', auth()->user()->company_id)
            ->orderBy('name')->get();
        $roles = Role::whereNotIn('name', ['Super Admin'])->orderBy('name')->get();
        $rolePerms = PaymentMethodPerm::whereNotNull('role_id')->whereNull('user_id')
            ->get()->groupBy('role_id');
        return view('admin.settings.payment-methods', compact('methods', 'allMethods', 'roles', 'rolePerms'));
    }

    public function savePaymentMethodRolePerms(Request $request)
    {
        $roles = Role::whereNotIn('name', ['Super Admin'])->get();
        $allMethods = PaymentMethod::where('is_active', true)
            ->where('company_id', auth()->user()->company_id)
            ->pluck('id');
        $submitted = $request->input('role_method', []);

        foreach ($roles as $role) {
            PaymentMethodPerm::where('role_id', $role->id)->whereNull('user_id')->delete();
            $allowed = $submitted[$role->id] ?? [];
            foreach ($allowed as $methodId) {
                if ($allMethods->contains($methodId)) {
                    PaymentMethodPerm::create([
                        'role_id' => $role->id,
                        'user_id' => null,
                        'payment_method_id' => $methodId,
                    ]);
                }
            }
        }
        return back()->with('success', 'Payment method permissions saved.');
    }

    public function storePaymentMethod(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:payment_methods,code',
        ]);
        $code = $request->code ?: \Str::slug($request->name, '_');
        $baseCode = $code;
        $i = 1;
        while (PaymentMethod::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $i++;
        }
        PaymentMethod::create([
            'name' => $request->name,
            'code' => $code,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'Payment method created successfully.');
    }

    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:payment_methods,code,' . $paymentMethod->id,
        ]);
        $paymentMethod->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'Payment method updated successfully.');
    }

    public function destroyPaymentMethod(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->payments()->count() > 0) {
            return back()->with('error', 'Cannot delete payment method with existing payments.');
        }
        $paymentMethod->delete();
        return back()->with('success', 'Payment method deleted successfully.');
    }

    public function bulkDeletePaymentMethods(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No payment methods selected.');
        }
        
        $deleted = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            $method = PaymentMethod::find($id);
            if ($method) {
                if ($method->payments()->count() > 0) {
                    $skipped++;
                } else {
                    $method->delete();
                    $deleted++;
                }
            }
        }
        
        $msg = "$deleted payment method(s) deleted.";
        if ($skipped > 0) $msg .= " $skipped skipped (have payments).";
        return back()->with('success', $msg);
    }

    public function system()
    {
        $companyId = auth()->user()->company_id;
        $settings = SystemSetting::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->get()
            ->pluck('value', 'key');
        return view('admin.settings.system', compact('settings'));
    }

    public function updateSystem(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            SystemSetting::set($key, $value);
        }
        return back()->with('success', 'System settings updated successfully.');
    }

    public function testTelegram(Request $request)
    {
        $channel = $request->get('channel', 'alert');
        $telegramService = new \App\Services\TelegramService();
        $result = $telegramService->sendTestMessage($channel);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }

    public function areas()
    {
        $areas = Area::with(['zone.upazila.district'])->whereNull('reseller_id')->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $divisions = \App\Models\District::where('is_active', true)
            ->whereNotNull('division')->where('division', '!=', '')
            ->distinct()->orderBy('division')->pluck('division');
        $districts = \App\Models\District::where('is_active', true)->orderBy('name')->get();
        $upazilas = \App\Models\Upazila::with('district')->where('is_active', true)->orderBy('name')->get();
        $zones = Zone::with('upazila.district')->whereNull('reseller_id')->where('is_active', true)->orderBy('name')->get();
        return view('admin.settings.areas', compact('areas', 'divisions', 'districts', 'upazilas', 'zones'));
    }

    public function storeArea(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'zone_id' => ['required', \Illuminate\Validation\Rule::exists('zones', 'id')->where('company_id', auth()->user()->company_id)],
        ]);
        Area::create([
            'name' => $request->name,
            'zone_id' => $request->zone_id,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? true : true,
        ]);
        return back()->with('success', 'Sub Zone created successfully.');
    }

    public function updateArea(Request $request, Area $area)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'zone_id' => ['required', \Illuminate\Validation\Rule::exists('zones', 'id')->where('company_id', auth()->user()->company_id)],
        ]);
        $area->update([
            'name' => $request->name,
            'zone_id' => $request->zone_id,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'Sub Zone updated successfully.');
    }

    public function destroyArea(Area $area)
    {
        if ($area->boxes()->count() > 0) {
            return back()->with('error', 'Cannot delete sub zone with assigned TJs.');
        }
        if ($area->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete sub zone with assigned clients.');
        }
        $area->delete();
        return back()->with('success', 'Sub Zone deleted successfully.');
    }

    public function bulkDeleteAreas(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No sub zones selected.');
        }
        
        $deleted = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            $area = Area::find($id);
            if ($area) {
                if ($area->boxes()->count() > 0 || $area->clients()->count() > 0) {
                    $skipped++;
                } else {
                    $area->delete();
                    $deleted++;
                }
            }
        }
        
        $msg = "$deleted sub zone(s) deleted.";
        if ($skipped > 0) $msg .= " $skipped skipped (have TJs/clients).";
        if ($request->expectsJson()) return response()->json(['message' => $msg]);
        return back()->with('success', $msg);
    }

    public function box()
    {
        $boxes = Box::with(['zone', 'area', 'clients' => function($q) {
            $q->orderBy('name');
        }])->withCount(['clients' => function($q) {
            $q->where('status', 'active');
        }])->orderBy('name')->get();
        $zones = Zone::orderBy('name')->get();
        $areas = Area::orderBy('name')->get();
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $upazilas = Upazila::where('is_active', true)->orderBy('name')->get();
        $divisions = $districts->pluck('division')->unique()->sort()->filter()->values();
        return view('admin.config.box', compact('boxes', 'zones', 'areas', 'districts', 'upazilas', 'divisions'));
    }

    public function storeBox(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'zone_id' => ['nullable', \Illuminate\Validation\Rule::exists('zones', 'id')->where('company_id', auth()->user()->company_id)],
            'area_id' => ['nullable', \Illuminate\Validation\Rule::exists('areas', 'id')->where('company_id', auth()->user()->company_id)],
            'location' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1|max:1000',
            'description' => 'nullable|string|max:500',
        ]);

        Box::create($request->only(['name', 'zone_id', 'area_id', 'location', 'capacity', 'description']));
        return back()->with('success', 'TJ created successfully.');
    }

    public function updateBox(Request $request, Box $box)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'zone_id' => ['nullable', \Illuminate\Validation\Rule::exists('zones', 'id')->where('company_id', auth()->user()->company_id)],
            'area_id' => ['nullable', \Illuminate\Validation\Rule::exists('areas', 'id')->where('company_id', auth()->user()->company_id)],
            'location' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1|max:1000',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        $box->update($request->only(['name', 'zone_id', 'area_id', 'location', 'capacity', 'description', 'status']));
        return back()->with('success', 'TJ updated successfully.');
    }

    public function destroyBox(Box $box)
    {
        if ($box->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete TJ with assigned clients.');
        }
        $box->delete();
        return back()->with('success', 'TJ deleted successfully.');
    }

    public function bulkDeleteBoxes(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No TJs selected.');
        }
        
        $deleted = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            $box = Box::find($id);
            if ($box) {
                if ($box->clients()->count() > 0) {
                    $skipped++;
                } else {
                    $box->delete();
                    $deleted++;
                }
            }
        }
        
        $msg = "$deleted TJ(s) deleted.";
        if ($skipped > 0) $msg .= " $skipped skipped (have clients).";
        if ($request->expectsJson()) return response()->json(['message' => $msg]);
        return back()->with('success', $msg);
    }

    public function connectionType()
    {
        return view('admin.config.connection-type');
    }

    public function storeConnectionType(Request $request)
    {
        return back()->with('success', 'Connection type created successfully.');
    }

    public function clientType()
    {
        return view('admin.config.client-type');
    }

    public function storeClientType(Request $request)
    {
        return back()->with('success', 'Client type created successfully.');
    }

    public function protocolType()
    {
        return view('admin.config.protocol-type');
    }

    public function storeProtocolType(Request $request)
    {
        return back()->with('success', 'Protocol type created successfully.');
    }

    public function areaManagement()
    {
        $districts = District::withCount('upazilas')->where('is_configured', true)->orderBy('division')->orderBy('name')->get();
        $upazilas = Upazila::with('district')->whereHas('district', fn($q) => $q->where('is_configured', true))->orderBy('name')->get();
        $zones = Zone::with(['areas', 'upazila.district'])->whereHas('upazila.district', fn($q) => $q->where('is_configured', true))->orderBy('name')->get();
        $allAreas = Area::with(['zone.upazila.district'])->whereHas('zone.upazila.district', fn($q) => $q->where('is_configured', true))->orderBy('name')->get();
        $boxes = Box::with(['zone', 'area'])->withCount(['clients' => function($q) {
            $q->where('status', 'active');
        }])->orderBy('name')->get();
        $divisions = District::where('is_active', true)
            ->whereNotNull('division')->where('division', '!=', '')
            ->distinct()->orderBy('division')->pluck('division');
        $configuredDivisions = District::where('is_active', true)->where('is_configured', true)
            ->whereNotNull('division')->where('division', '!=', '')
            ->distinct()->orderBy('division')->pluck('division');

        return view('admin.config.area', compact('districts', 'upazilas', 'zones', 'allAreas', 'boxes', 'divisions', 'configuredDivisions'));
    }

    public function district()
    {
        $districts = District::withCount('upazilas')->orderBy('division')->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.config.district', compact('districts'));
    }

    public function storeDistrict(Request $request)
    {
        $request->validate([
            'district_id' => 'required|exists:districts,id',
        ]);
        $district = District::findOrFail($request->district_id);
        if ($district->is_configured) {
            return back()->with('error', 'This district is already added.');
        }
        $district->update(['is_configured' => true, 'is_active' => true]);
        return back()->with('success', 'District "' . $district->name . '" added successfully.');
    }

    public function updateDistrict(Request $request, District $district)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:districts,name,' . $district->id,
            'division' => 'nullable|string|max:255',
        ]);
        $district->update([
            'name' => $request->name,
            'division' => $request->division,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'District updated successfully.');
    }

    public function destroyDistrict(District $district)
    {
        if ($district->upazilas()->count() > 0) {
            return back()->with('error', 'Cannot remove district with assigned upazilas. Please remove the upazilas first.');
        }
        $district->update(['is_configured' => false]);
        return back()->with('success', 'District removed successfully.');
    }

    public function bulkDeleteDistricts(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $districts = District::whereIn('id', $request->ids)->withCount('upazilas')->get();
        $hasUpazilas = $districts->filter(fn($d) => $d->upazilas_count > 0);
        if ($hasUpazilas->isNotEmpty()) {
            return response()->json(['message' => 'Cannot remove districts with assigned upazilas: ' . $hasUpazilas->pluck('name')->join(', ')], 422);
        }
        District::whereIn('id', $request->ids)->update(['is_configured' => false]);
        return response()->json(['message' => 'Removed ' . count($request->ids) . ' district(s) successfully.']);
    }

    public function bulkDeleteUpazilas(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        Upazila::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Deleted ' . count($request->ids) . ' upazila(s) successfully.']);
    }

    public function upazila()
    {
        $upazilas = Upazila::with('district')
            ->join('districts', 'upazilas.district_id', '=', 'districts.id')
            ->orderBy('districts.division')
            ->orderBy('districts.name')
            ->orderBy('upazilas.name')
            ->select('upazilas.*')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $districts = District::where('is_active', true)->orderBy('division')->orderBy('name')->get();
        return view('admin.config.upazila', compact('upazilas', 'districts'));
    }

    public function storeUpazila(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('upazilas')->where('district_id', $request->district_id)],
            'district_id' => 'required|exists:districts,id',
        ]);
        Upazila::create([
            'name' => $request->name,
            'district_id' => $request->district_id,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'Upazila created successfully.');
    }

    public function updateUpazila(Request $request, Upazila $upazila)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('upazilas')->where('district_id', $request->district_id)->ignore($upazila->id)],
            'district_id' => 'required|exists:districts,id',
        ]);
        $upazila->update([
            'name' => $request->name,
            'district_id' => $request->district_id,
            'is_active' => $request->has('is_active'),
        ]);
        return back()->with('success', 'Upazila updated successfully.');
    }

    public function destroyUpazila(Upazila $upazila)
    {
        $upazila->delete();
        return back()->with('success', 'Upazila deleted successfully.');
    }

    public function events(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $query = \App\Models\Event::orderBy('event_date', 'desc');

        if ($filter === 'upcoming') {
            $query->where('event_date', '>=', now()->toDateString());
            $query->reorder('event_date', 'asc');
        } elseif ($filter === 'past') {
            $query->where('event_date', '<', now()->toDateString());
        }

        $events = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        $upcomingEvents = \App\Models\Event::where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')->limit(10)->get();

        return view('admin.events.index', compact('events', 'upcomingEvents', 'filter'));
    }

    public function storeEvent(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'type' => 'required|in:holiday,event,maintenance,other',
            'description' => 'nullable|string|max:1000',
        ]);

        \App\Models\Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'type' => $request->type,
            'is_holiday' => $request->type === 'holiday',
            'created_by' => auth()->id(),
            'company_id' => auth()->user()->company_id,
        ]);

        return back()->with('success', 'Event created successfully.');
    }

    public function updateEvent(Request $request, \App\Models\Event $event)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'type' => 'required|in:holiday,event,maintenance,other',
            'description' => 'nullable|string|max:1000',
        ]);

        $event->update([
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'type' => $request->type,
            'is_holiday' => $request->type === 'holiday',
        ]);

        return back()->with('success', 'Event updated successfully.');
    }

    public function deleteEvent(\App\Models\Event $event)
    {
        $event->delete();
        return back()->with('success', 'Event deleted successfully.');
    }

    public function holidays()
    {
        $holidays = \App\Models\Event::where('is_holiday', true)
            ->orderBy('event_date', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.events.holidays', compact('holidays'));
    }

    public function storeHoliday(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        \App\Models\Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'type' => 'holiday',
            'is_holiday' => true,
            'created_by' => auth()->id(),
            'company_id' => auth()->user()->company_id,
        ]);

        return back()->with('success', 'Holiday created successfully.');
    }

    public function tasks(Request $request)
    {
        $query = \App\Models\Task::with(['assignedTo', 'createdBy'])
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->orderBy('due_date', 'asc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tasks = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->orderBy('name')->get(['id', 'name']);

        $stats = [
            'total' => \App\Models\Task::count(),
            'pending' => \App\Models\Task::where('status', 'pending')->count(),
            'in_progress' => \App\Models\Task::where('status', 'in_progress')->count(),
            'completed' => \App\Models\Task::where('status', 'completed')->count(),
            'overdue' => \App\Models\Task::where('status', '!=', 'completed')
                ->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count(),
        ];

        return view('admin.tasks.index', compact('tasks', 'users', 'stats'));
    }

    public function createTask()
    {
        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->orderBy('name')->get(['id', 'name']);
        return view('admin.tasks.create', compact('users'));
    }

    public function storeTask(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        \App\Models\Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status' => 'pending',
            'assigned_to' => $request->assigned_to,
            'created_by' => auth()->id(),
            'company_id' => auth()->user()->company_id,
        ]);

        return redirect()->route('admin.tasks.index')->with('success', 'Task created successfully.');
    }

    public function showTask(\App\Models\Task $task)
    {
        $task->load(['assignedTo', 'createdBy']);
        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->orderBy('name')->get(['id', 'name']);
        return view('admin.tasks.show', compact('task', 'users'));
    }

    public function updateTask(Request $request, \App\Models\Task $task)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $data = $request->only(['title', 'description', 'due_date', 'priority', 'status', 'assigned_to', 'notes']);

        if ($request->status === 'completed' && $task->status !== 'completed') {
            $data['completed_at'] = now();
        } elseif ($request->status !== 'completed') {
            $data['completed_at'] = null;
        }

        $task->update($data);

        return back()->with('success', 'Task updated successfully.');
    }

    public function deleteTask(\App\Models\Task $task)
    {
        $task->delete();
        return redirect()->route('admin.tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function toggleTaskStatus(\App\Models\Task $task)
    {
        if ($task->status === 'completed') {
            $task->update(['status' => 'pending', 'completed_at' => null]);
        } else {
            $task->update(['status' => 'completed', 'completed_at' => now()]);
        }
        return back()->with('success', 'Task status updated.');
    }

    public function taskCalendar()
    {
        $tasks = \App\Models\Task::with(['assignedTo'])
            ->whereNotNull('due_date')
            ->get();

        $colors = ['urgent' => '#dc3545', 'high' => '#fd7e14', 'medium' => '#007bff', 'low' => '#28a745'];
        $calendarEvents = $tasks->map(function ($task) use ($colors) {
            return [
                'id' => $task->id,
                'title' => $task->title . ($task->assignedTo ? ' (' . $task->assignedTo->name . ')' : ''),
                'start' => $task->due_date->format('Y-m-d'),
                'color' => $colors[$task->priority] ?? '#6c757d',
                'textColor' => '#fff',
                'url' => route('admin.tasks.show', $task),
                'className' => $task->status === 'completed' ? 'task-completed' : '',
            ];
        })->values();

        return view('admin.tasks.calendar', compact('tasks', 'calendarEvents'));
    }

    public function taskCalendarJson()
    {
        $tasks = \App\Models\Task::with(['assignedTo'])
            ->whereNotNull('due_date')
            ->get();

        $events = $tasks->map(function ($task) {
            $colors = [
                'urgent' => '#dc3545',
                'high' => '#fd7e14',
                'medium' => '#007bff',
                'low' => '#28a745',
            ];
            return [
                'id' => $task->id,
                'title' => $task->title,
                'start' => $task->due_date->format('Y-m-d'),
                'color' => $colors[$task->priority] ?? '#6c757d',
                'textColor' => '#fff',
                'url' => route('admin.tasks.show', $task),
                'className' => $task->status === 'completed' ? 'task-completed' : '',
            ];
        });

        return response()->json($events);
    }

    public function logo()
    {
        $logo     = SystemSetting::get('site_logo', '');
        $logoLight = SystemSetting::get('site_logo_light', '');
        $favicon  = SystemSetting::get('site_favicon', '');
        $appIcon  = SystemSetting::get('site_app_icon', '');
        return view('admin.settings.logo', compact('logo', 'logoLight', 'favicon', 'appIcon'));
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'logo_light' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);


        $companyLogoUpdate = [];
        $companyId = auth()->user()->company_id ?? \App\Services\TenantContext::id() ?? 1;
        $logoDir = public_path('images/company-' . $companyId);
        if (!is_dir($logoDir)) { mkdir($logoDir, 0755, true); }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo.' . $file->getClientOriginalExtension();
            $file->move($logoDir, $filename);
            $logoPath = '/images/company-' . $companyId . '/' . $filename;
            SystemSetting::set('site_logo', $logoPath, 'branding');
            $companyLogoUpdate['logo'] = $logoPath;
        }

        if ($request->hasFile('logo_light')) {
            $file = $request->file('logo_light');
            $filename = 'logo-light.' . $file->getClientOriginalExtension();
            $file->move($logoDir, $filename);
            $logoLightPath = '/images/company-' . $companyId . '/' . $filename;
            SystemSetting::set('site_logo_light', $logoLightPath, 'branding');
            $companyLogoUpdate['logo_light'] = $logoLightPath;
        }

        if (!empty($companyLogoUpdate)) {
            $tenantCompany = \App\Services\TenantContext::get();
            if ($tenantCompany) {
                \App\Models\Company::where('id', $tenantCompany->id)->update($companyLogoUpdate);
            }
        }

        return back()->with('success', 'Logo updated successfully. Changes will appear across all panels.');
    }


    public function removeLogo(Request $request, string $type)
    {
        $allowed = ["logo", "logo_light", "favicon", "app_icon"];
        if (!in_array($type, $allowed)) {
            return back()->with("error", "Invalid logo type.");
        }

        $keyMap = [
            "logo"       => "site_logo",
            "logo_light" => "site_logo_light",
            "favicon"    => "site_favicon",
            "app_icon"   => "site_app_icon",
        ];

        $companyKey = [
            "logo"       => "logo",
            "logo_light" => "logo_light",
            "favicon"    => null,
            "app_icon"   => null,
        ];

        $settingKey = $keyMap[$type];
        $currentPath = SystemSetting::get($settingKey, "");

        if ($currentPath && file_exists(public_path($currentPath))) {
            @unlink(public_path($currentPath));
        }

        $existing = \App\Models\SystemSetting::where("key", $settingKey)->first();
        if ($existing) {
            $existing->value = "";
            $existing->save();
        }

        if ($companyKey[$type]) {
            $companyId = auth()->user()->company_id ?? \App\Services\TenantContext::id() ?? 1;
            \App\Models\Company::where("id", $companyId)->update([$companyKey[$type] => null]);
        }

        $label = ucfirst(str_replace("_", " ", $type));
        return back()->with("success", "{$label} removed successfully.");
    }

    public function paymentSettings()
    {
        $bkashNumber = SystemSetting::get('bkash_merchant_number', '');
        $nagadNumber = SystemSetting::get('nagad_merchant_number', '');
        $rocketNumber = SystemSetting::get('rocket_merchant_number', '');
        $bkashGatewayType = SystemSetting::get('bkash_gateway_type', '');
        $bkashAppKey = SystemSetting::get('bkash_app_key', '');
        $bkashAppSecret = SystemSetting::get('bkash_app_secret', '');
        $bkashUsername = SystemSetting::get('bkash_username', '');
        $bkashPassword = SystemSetting::get('bkash_password', '');
        $bkashSandbox = SystemSetting::get('bkash_sandbox', '1');
        $bkashEnabled = SystemSetting::get('bkash_enabled', '0');
        $bkashActivationDate = SystemSetting::get('bkash_activation_date', '');
        $bkashExpiryDate = SystemSetting::get('bkash_expiry_date', '');
        $bkashApplicableAdmin = SystemSetting::get('bkash_applicable_admin', '0');
        $bkashApplicablePop = SystemSetting::get('bkash_applicable_pop', '0');
        $bkashApplicableBandwidthPop = SystemSetting::get('bkash_applicable_bandwidth_pop', '0');
        $bkashApplicablePopClient = SystemSetting::get('bkash_applicable_pop_client', '0');

        $sslStoreId = SystemSetting::get('sslcommerz_store_id', '');
        $sslStorePassword = SystemSetting::get('sslcommerz_store_password', '');
        $sslSandbox = SystemSetting::get('sslcommerz_sandbox', '1');
        $sslEnabled = SystemSetting::get('sslcommerz_enabled', '0');
        $sslActivationDate = SystemSetting::get('sslcommerz_activation_date', '');
        $sslExpiryDate = SystemSetting::get('sslcommerz_expiry_date', '');
        $sslApplicableAdmin = SystemSetting::get('sslcommerz_applicable_admin', '0');
        $sslApplicablePop = SystemSetting::get('sslcommerz_applicable_pop', '0');
        $sslApplicableBandwidthPop = SystemSetting::get('sslcommerz_applicable_bandwidth_pop', '0');
        $sslApplicablePopClient = SystemSetting::get('sslcommerz_applicable_pop_client', '0');

        $nagadMerchantId = SystemSetting::get('nagad_merchant_id', '');
        $nagadMerchantPhone = SystemSetting::get('nagad_merchant_phone', '');
        $nagadPgPublicKey = SystemSetting::get('nagad_pg_public_key', '');
        $nagadMerchantPrivateKey = SystemSetting::get('nagad_merchant_private_key', '');
        $nagadSandbox = SystemSetting::get('nagad_sandbox', '1');
        $nagadEnabled = SystemSetting::get('nagad_enabled', '0');
        $nagadActivationDate = SystemSetting::get('nagad_activation_date', '');
        $nagadExpiryDate = SystemSetting::get('nagad_expiry_date', '');
        $nagadApplicableAdmin = SystemSetting::get('nagad_applicable_admin', '0');
        $nagadApplicablePop = SystemSetting::get('nagad_applicable_pop', '0');
        $nagadApplicableBandwidthPop = SystemSetting::get('nagad_applicable_bandwidth_pop', '0');
        $nagadApplicablePopClient = SystemSetting::get('nagad_applicable_pop_client', '0');

        $webhookGateways = ['bkash', 'sslcommerz', 'nagad', 'rocket'];
        $webhookSettings = [];
        foreach ($webhookGateways as $gw) {
            $webhookSettings[$gw] = [
                'webhook_url' => SystemSetting::get($gw . '_webhook_url', ''),
                'webhook_secret' => SystemSetting::get($gw . '_webhook_secret', ''),
                'webhook_enabled' => SystemSetting::get($gw . '_webhook_enabled', '0'),
                'ipn_url' => SystemSetting::get($gw . '_ipn_url', ''),
            ];
        }

        $activeTab = request('tab', session('_old_input.tab', 'sslcommerz'));
        
        return view('admin.settings.payment', compact(
            'bkashNumber', 'nagadNumber', 'rocketNumber',
            'bkashGatewayType', 'bkashAppKey', 'bkashAppSecret', 'bkashUsername', 'bkashPassword', 'bkashSandbox',
            'bkashEnabled', 'bkashActivationDate', 'bkashExpiryDate',
            'bkashApplicableAdmin', 'bkashApplicablePop', 'bkashApplicableBandwidthPop', 'bkashApplicablePopClient',
            'sslStoreId', 'sslStorePassword', 'sslSandbox', 'sslEnabled',
            'sslActivationDate', 'sslExpiryDate',
            'sslApplicableAdmin', 'sslApplicablePop', 'sslApplicableBandwidthPop', 'sslApplicablePopClient',
            'nagadMerchantId', 'nagadMerchantPhone', 'nagadPgPublicKey', 'nagadMerchantPrivateKey', 'nagadSandbox',
            'nagadEnabled', 'nagadActivationDate', 'nagadExpiryDate',
            'nagadApplicableAdmin', 'nagadApplicablePop', 'nagadApplicableBandwidthPop', 'nagadApplicablePopClient',
            'webhookSettings', 'activeTab'
        ));
    }

    public function updatePaymentSettings(Request $request)
    {
        $request->validate([
            'bkash_merchant_number' => 'nullable|string|max:20',
            'nagad_merchant_number' => 'nullable|string|max:20',
            'rocket_merchant_number' => 'nullable|string|max:20',
        ]);

        SystemSetting::set('bkash_merchant_number', $request->bkash_merchant_number ?? '', 'payment');
        SystemSetting::set('nagad_merchant_number', $request->nagad_merchant_number ?? '', 'payment');
        SystemSetting::set('rocket_merchant_number', $request->rocket_merchant_number ?? '', 'payment');

        return back()->with('success', 'Payment settings updated successfully.')->withInput(['tab' => 'manual']);
    }

    public function updatePaymentApiSettings(Request $request)
    {
        $request->validate([
            'bkash_gateway_type' => 'required|string|in:platform_connector,checkout_popup,tokenized_web',
            'bkash_app_key' => 'required|string|max:200',
            'bkash_app_secret' => 'required|string|max:200',
            'bkash_username' => 'required|string|max:100',
            'bkash_password' => 'required|string|max:200',
            'bkash_activation_date' => 'required|date_format:Y-m-d',
            'bkash_expiry_date' => 'nullable|date_format:Y-m-d',
        ]);

        SystemSetting::set('bkash_gateway_type', $request->bkash_gateway_type, 'payment_api');
        SystemSetting::set('bkash_app_key', $request->bkash_app_key ?? '', 'payment_api');
        SystemSetting::set('bkash_app_secret', $request->bkash_app_secret ?? '', 'payment_api');
        SystemSetting::set('bkash_username', $request->bkash_username ?? '', 'payment_api');
        SystemSetting::set('bkash_password', $request->bkash_password ?? '', 'payment_api');
        SystemSetting::set('bkash_sandbox', $request->input('bkash_sandbox', '0'), 'payment_api');
        SystemSetting::set('bkash_enabled', $request->input('bkash_enabled', '0'), 'payment_api');
        SystemSetting::set('bkash_activation_date', $request->bkash_activation_date ?? '', 'payment_api');
        SystemSetting::set('bkash_expiry_date', $request->bkash_expiry_date ?? '', 'payment_api');
        SystemSetting::set('bkash_applicable_admin', $request->has('bkash_applicable_admin') ? '1' : '0', 'payment_api');
        SystemSetting::set('bkash_applicable_pop', $request->has('bkash_applicable_pop') ? '1' : '0', 'payment_api');
        SystemSetting::set('bkash_applicable_bandwidth_pop', $request->has('bkash_applicable_bandwidth_pop') ? '1' : '0', 'payment_api');
        SystemSetting::set('bkash_applicable_pop_client', $request->has('bkash_applicable_pop_client') ? '1' : '0', 'payment_api');

        return back()->with('success', 'bKash gateway settings updated successfully.')->withInput(['tab' => 'bkash']);
    }

    public function updateSslCommerzSettings(Request $request)
    {
        $request->validate([
            'sslcommerz_store_id' => 'required|string|max:100',
            'sslcommerz_store_password' => 'required|string|max:200',
            'sslcommerz_activation_date' => 'required|date_format:Y-m-d',
            'sslcommerz_expiry_date' => 'nullable|date_format:Y-m-d',
        ]);

        SystemSetting::set('sslcommerz_store_id', $request->sslcommerz_store_id, 'payment_gateway');
        SystemSetting::set('sslcommerz_store_password', $request->sslcommerz_store_password, 'payment_gateway');
        SystemSetting::set('sslcommerz_sandbox', $request->input('sslcommerz_sandbox', '0'), 'payment_gateway');
        SystemSetting::set('sslcommerz_enabled', $request->input('sslcommerz_enabled', '0'), 'payment_gateway');
        SystemSetting::set('sslcommerz_activation_date', $request->sslcommerz_activation_date ?? '', 'payment_gateway');
        SystemSetting::set('sslcommerz_expiry_date', $request->sslcommerz_expiry_date ?? '', 'payment_gateway');
        SystemSetting::set('sslcommerz_applicable_admin', $request->has('sslcommerz_applicable_admin') ? '1' : '0', 'payment_gateway');
        SystemSetting::set('sslcommerz_applicable_pop', $request->has('sslcommerz_applicable_pop') ? '1' : '0', 'payment_gateway');
        SystemSetting::set('sslcommerz_applicable_bandwidth_pop', $request->has('sslcommerz_applicable_bandwidth_pop') ? '1' : '0', 'payment_gateway');
        SystemSetting::set('sslcommerz_applicable_pop_client', $request->has('sslcommerz_applicable_pop_client') ? '1' : '0', 'payment_gateway');

        return back()->with('success', 'SSLCommerz gateway settings updated successfully.')->withInput(['tab' => 'sslcommerz']);
    }

    public function updateNagadSettings(Request $request)
    {
        $request->validate([
            'nagad_merchant_id' => 'required|string|max:100',
            'nagad_merchant_phone' => 'required|string|max:20',
            'nagad_pg_public_key' => 'required|string',
            'nagad_merchant_private_key' => 'required|string',
            'nagad_activation_date' => 'required|date_format:Y-m-d',
            'nagad_expiry_date' => 'nullable|date_format:Y-m-d',
        ]);

        SystemSetting::set('nagad_merchant_id', $request->nagad_merchant_id, 'payment_gateway');
        SystemSetting::set('nagad_merchant_phone', $request->nagad_merchant_phone, 'payment_gateway');
        SystemSetting::set('nagad_pg_public_key', $request->nagad_pg_public_key, 'payment_gateway');
        SystemSetting::set('nagad_merchant_private_key', $request->nagad_merchant_private_key, 'payment_gateway');
        SystemSetting::set('nagad_sandbox', $request->input('nagad_sandbox', '0'), 'payment_gateway');
        SystemSetting::set('nagad_enabled', $request->input('nagad_enabled', '0'), 'payment_gateway');
        SystemSetting::set('nagad_activation_date', $request->nagad_activation_date ?? '', 'payment_gateway');
        SystemSetting::set('nagad_expiry_date', $request->nagad_expiry_date ?? '', 'payment_gateway');
        SystemSetting::set('nagad_applicable_admin', $request->has('nagad_applicable_admin') ? '1' : '0', 'payment_gateway');
        SystemSetting::set('nagad_applicable_pop', $request->has('nagad_applicable_pop') ? '1' : '0', 'payment_gateway');
        SystemSetting::set('nagad_applicable_bandwidth_pop', $request->has('nagad_applicable_bandwidth_pop') ? '1' : '0', 'payment_gateway');
        SystemSetting::set('nagad_applicable_pop_client', $request->has('nagad_applicable_pop_client') ? '1' : '0', 'payment_gateway');

        return back()->with('success', 'Nagad gateway settings updated successfully.')->withInput(['tab' => 'nagad']);
    }

    public function testBkashConnection()
    {
        $gatewayService = app(\App\Services\PaymentGatewayService::class);
        $result = $gatewayService->testBkashConnection();

        if ($result['success']) {
            return back()->with('success', $result['message'] . ' (Mode: ' . $result['mode'] . ', Username: ' . ($result['merchant'] ?? ''). ')')->withInput(['tab' => 'bkash']);
        }

        return back()->with('error', $result['message'] . ' (Mode: ' . ($result['mode'] ?? 'Unknown') . ')')->withInput(['tab' => 'bkash']);
    }

    public function testNagadConnection()
    {
        $gatewayService = app(\App\Services\PaymentGatewayService::class);
        $result = $gatewayService->testNagadConnection();

        if ($result['success']) {
            return back()->with('success', $result['message'] . ' (Mode: ' . $result['mode'] . ', Merchant: ' . ($result['merchant_id'] ?? '') . ')')->withInput(['tab' => 'nagad']);
        }

        return back()->with('error', $result['message'] . ' (Mode: ' . ($result['mode'] ?? 'Unknown') . ')')->withInput(['tab' => 'nagad']);
    }

    public function updateWebhookSettings(Request $request)
    {
        $request->validate([
            'gateway' => 'required|string|in:bkash,sslcommerz,nagad,rocket',
            'webhook_url' => 'nullable|url|max:500',
            'webhook_secret' => 'nullable|string|max:200',
            'ipn_url' => 'nullable|url|max:500',
        ]);

        $gw = $request->gateway;
        SystemSetting::set($gw . '_webhook_url', $request->webhook_url ?? '', 'payment_webhook');
        SystemSetting::set($gw . '_webhook_secret', $request->webhook_secret ?? '', 'payment_webhook');
        SystemSetting::set($gw . '_webhook_enabled', $request->has('webhook_enabled') ? '1' : '0', 'payment_webhook');
        SystemSetting::set($gw . '_ipn_url', $request->ipn_url ?? '', 'payment_webhook');

        return back()->with('success', ucfirst($gw) . ' webhook settings updated successfully.')->withInput(['tab' => 'webhook', 'gateway' => $gw]);
    }

    public function webhookPaymentLogs()
    {
        return redirect()->route('admin.billing.online-payments', ['tab' => 'webhook']);
    }

    public function testSslConnection()
    {
        $gatewayService = app(\App\Services\PaymentGatewayService::class);
        $result = $gatewayService->testSslConnection();

        if ($result['success']) {
            return back()->with('success', $result['message'] . ' (Mode: ' . $result['mode'] . ', Store: ' . $result['store_id'] . ')')->withInput(['tab' => 'sslcommerz']);
        }

        return back()->with('error', $result['message'] . ' (Mode: ' . ($result['mode'] ?? 'Unknown') . ')')->withInput(['tab' => 'sslcommerz']);
    }
}
