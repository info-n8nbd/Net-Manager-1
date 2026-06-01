<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\NewConnectionApplication;
use App\Models\Package;
use App\Models\Zone;
use App\Models\Box;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewClientApplicationController extends Controller
{
    public function create()
    {
        $packages = Package::where('is_active', true)->where('show_in_public', true)->orderBy('name')->get();
        $zones = Zone::where('is_active', true)->with('areas')->orderBy('name')->get();
        $boxes = Box::where('status', 'active')->orderBy('name')->get();
        $technicians = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Field Technician', 'Support Executive']))
            ->orderBy('name')->get();
        $divisions = \App\Models\District::where('is_active', true)
            ->whereNotNull('division')->where('division', '!=', '')
            ->distinct()->orderBy('division')->pluck('division');
        $districts = \App\Models\District::where('is_active', true)->orderBy('name')->get();

        return view('public.new-client-application', compact('packages', 'zones', 'boxes', 'technicians', 'divisions', 'districts'));
    }

    public function store(Request $request)
    {
        if ($request->expire_date === '' || !$request->expire_date) {
            $request->merge(['expire_date' => null]);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'father_husband_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'occupation' => 'nullable|string|max:100',
            'mobile_number' => 'required|string|max:20',
            'alternative_mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'nid_number' => 'required|string|max:20',
            'date_of_birth' => 'nullable|date',
            'house_road_floor' => 'required|string',
            'zone_id' => 'nullable|exists:zones,id',
            'nearby_landmark' => 'nullable|string|max:255',
            'package_id' => ['required', \Illuminate\Validation\Rule::exists('packages', 'id')->where('is_active', true)->where('show_in_public', true)],
            'connection_type' => 'nullable|in:PPPoE,Static IP,DHCP',
            'billing_cycle' => 'nullable|in:monthly,quarterly,half_yearly,yearly',
            'installation_charge' => 'nullable|numeric|min:0',
            'cable_length_meters' => 'nullable|numeric|min:0',
            'onu_required' => 'nullable|boolean',
            'connection_medium' => 'nullable|in:utp,fiber',
            'fiber_code' => 'nullable|string|max:100',
            'fiber_color' => 'nullable|string|max:100',
            'splitter_number' => 'nullable|string|max:100',
            'splitter_color' => 'nullable|string|max:100',
            'onu_mac' => 'nullable|string|max:100',
            'onu_brand' => 'nullable|string|max:100',
            'fiber_vendor' => 'nullable|string|max:100',
            'fiber_core_type' => 'nullable|string|max:100',
            'technician_note' => 'nullable|string',
            'sub_zone_id' => 'nullable|exists:areas,id',
            'box_id' => 'nullable|exists:boxes,id',
            'division' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'thana_upazila' => 'nullable|string|max:255',
            'district_id' => 'nullable|exists:districts,id',
            'upazila_id' => 'nullable|exists:upazilas,id',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
            'payment_method' => 'required|in:cash,bkash,nagad,rocket,bank,others',
            'next_billing_date' => 'nullable|date',
            'auto_inactive_day' => 'required|integer|min:1|max:31',
            'expire_date' => 'nullable|date',
            'google_map_lat' => 'nullable|numeric|between:-90,90',
            'google_map_lng' => 'nullable|numeric|between:-180,180',
            'profile_picture' => 'nullable|image|max:5120',
            'nid_front_image' => 'nullable|image|max:5120',
            'nid_back_image' => 'nullable|image|max:5120',
        ]);

        $package = Package::find($validated['package_id']);
        $nextBillingDate = $validated['next_billing_date'] ?? now()->addMonth()->startOfMonth()->format('Y-m-d');

        $profilePicturePath = null;
        $nidFrontPath = null;
        $nidBackPath = null;

        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('clients/profile', 'public');
        }
        if ($request->hasFile('nid_front_image')) {
            $nidFrontPath = $request->file('nid_front_image')->store('clients/nid', 'public');
        }
        if ($request->hasFile('nid_back_image')) {
            $nidBackPath = $request->file('nid_back_image')->store('clients/nid', 'public');
        }

        $application = NewConnectionApplication::create([
            'application_number' => NewConnectionApplication::generateApplicationNumber(),
            'full_name_bangla' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'full_name_english' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'father_husband_name' => $validated['father_husband_name'] ?? null,
            'mother_name' => $validated['mother_name'] ?? null,
            'mobile_number' => $validated['mobile_number'],
            'alternative_mobile' => $validated['alternative_mobile'] ?? null,
            'email' => $validated['email'] ?? null,
            'nid_number' => $validated['nid_number'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'house_road_floor' => $validated['house_road_floor'],
            'zone_id' => $validated['zone_id'],
            'sub_zone_id' => $validated['sub_zone_id'] ?? null,
            'box_id' => $validated['box_id'] ?? null,
            'nearby_landmark' => $validated['nearby_landmark'] ?? null,
            'package_id' => $validated['package_id'],
            'connection_type' => $validated['connection_type'] ?? 'PPPoE',
            'billing_cycle' => $validated['billing_cycle'] ?? 'monthly',
            'installation_charge' => $validated['installation_charge'] ?? 0,
            'cable_length_meters' => $validated['cable_length_meters'] ?? 0,
            'onu_required' => $request->input('onu_required') == '1',
            'router_required' => $request->input('router_required') == '1',
            'fiber_required' => $request->input('fiber_required') == '1',
            'connection_medium' => $request->input('connection_medium', 'fiber'),
            'fiber_code' => $validated['fiber_code'] ?? null,
            'fiber_color' => $validated['fiber_color'] ?? null,
            'splitter_number' => $validated['splitter_number'] ?? null,
            'splitter_color' => $validated['splitter_color'] ?? null,
            'onu_mac' => $validated['onu_mac'] ?? null,
            'onu_brand' => $validated['onu_brand'] ?? null,
            'fiber_vendor' => $validated['fiber_vendor'] ?? null,
            'fiber_core_type' => $validated['fiber_core_type'] ?? null,
            'technician_note' => $this->buildTechnicianNote($validated),
            'payment_method' => $validated['payment_method'],
            'next_billing_date' => $nextBillingDate,
            'status' => 'submitted',
            'division' => $validated['division'] ?? null,
            'district' => $validated['district'] ?? null,
            'thana_upazila' => $validated['thana_upazila'] ?? null,
            'district_id' => $validated['district_id'] ?? null,
            'upazila_id' => $validated['upazila_id'] ?? null,
            'technician_id' => !empty($validated['technician_ids']) ? $validated['technician_ids'][0] : null,
            'technician_assigned_area' => !empty($validated['technician_ids']) ? implode(',', $validated['technician_ids']) : null,
            'gender' => $validated['gender'] ?? 'male',
            'ip_type' => 'dynamic',
            'house_owner_permission' => true,
            'latitude' => $validated['google_map_lat'] ?? null,
            'longitude' => $validated['google_map_lng'] ?? null,
            'nid_front_image' => $nidFrontPath,
            'nid_back_image' => $nidBackPath,
            'installation_place_photo' => $profilePicturePath,
            'expire_date' => $validated['expire_date'] ?? null,
            'auto_inactive_day' => $validated['auto_inactive_day'] ?? null,
            'mikrotik_profile_name' => $package ? $package->mikrotik_profile : null,
        ]);

        // Send Telegram notification to public_form channel
        try {
            $botToken = \App\Models\SystemSetting::get('telegram_public_form_bot_token')
                     ?: \App\Models\SystemSetting::get('telegram_alert_bot_token');
            $chatId   = \App\Models\SystemSetting::get('telegram_public_form_group_id')
                     ?: \App\Models\SystemSetting::get('telegram_alert_group_id');

            if ($botToken && $chatId) {
                $pkgName = $package ? $package->name : 'N/A';
                $address = $validated['house_road_floor'] ?? 'N/A';
                if (!empty($validated['thana_upazila'])) $address .= ', ' . $validated['thana_upazila'];
                if (!empty($validated['district']))      $address .= ', ' . $validated['district'];
                $bdTime  = now()->timezone('Asia/Dhaka')->format('d-m-y h:i A');
                $msg  = "\xF0\x9F\x93\x8B *NEW CONNECTION APPLICATION*\n";
                $msg .= "\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\n";
                $msg .= "\xF0\x9F\x86\x94 *App No:* `{$application->application_number}`\n";
                $msg .= "\xF0\x9F\x91\xA4 *Name:* {$application->full_name_english}\n";
                $msg .= "\xF0\x9F\x93\x9E *Mobile:* `{$application->mobile_number}`\n";
                $msg .= "\xF0\x9F\x93\x8D *Address:* {$address}\n";
                $msg .= "\xF0\x9F\x93\xA6 *Package:* {$pkgName}\n";
                $msg .= "\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\n";
                $msg .= "\xe2\x8f\xb0 *Time:* {$bdTime}\n";
                $msg .= "\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\xe2\x94\x81\n";
                $msg .= "\xF0\x9F\x8C\x90 _SOFTYNEX Management System_";
                \Illuminate\Support\Facades\Http::post(
                    "https://api.telegram.org/bot{$botToken}/sendMessage",
                    ['chat_id' => $chatId, 'text' => $msg, 'parse_mode' => 'Markdown']
                );
            }
        } catch (\Exception $e) {
            \Log::error('Public form Telegram notification failed: ' . $e->getMessage());
        }

        return redirect()->route('public.application.success', $application->application_number);
    }

    public function success($applicationNumber)
    {
        $application = NewConnectionApplication::where('application_number', $applicationNumber)->firstOrFail();
        return view('public.application-success', compact('application'));
    }

    private function buildTechnicianNote($validated)
    {
        $note = $validated['technician_note'] ?? '';
        return $note ?: null;
    }
}
