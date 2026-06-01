<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewConnectionApplication;
use App\Models\Package;
use App\Models\Zone;
use App\Models\MikrotikDevice;
use App\Services\NewConnectionApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewConnectionController extends Controller
{
    protected NewConnectionApplicationService $applicationService;

    public function __construct(NewConnectionApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    public function index(Request $request)
    {
        $authUser = Auth::user();
        // Super Admin sees all companies; everyone else only sees their own company's records
        if ($authUser && $authUser->isSuperAdmin()) {
            $query = NewConnectionApplication::withoutGlobalScope('tenant')
                ->with(['technician', 'zone', 'package', 'reviewer'])
                ->orderBy('created_at', 'desc');
        } else {
            $companyId = $authUser ? $authUser->company_id : 0;
            $query = NewConnectionApplication::query()
                ->with(['technician', 'zone', 'package', 'reviewer'])
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhere('full_name_english', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('nid_number', 'like', "%{$search}%");
            });
        }

        $applications = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $stats = $this->applicationService->getApplicationStats();
        $zones = Zone::orderBy('name')->get();
        $technicians = \App\Models\User::where('user_type', 'employee')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.applications.index', compact('applications', 'stats', 'zones', 'technicians'));
    }

    public function create()
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $zones = Zone::where('is_active', true)->orderBy('name')->get();

        return view('admin.applications.create', compact('packages', 'zones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name_bangla'         => 'required|string|max:255',
            'full_name_english'        => 'required|string|max:255',
            'mobile_number'            => 'required|string|max:20',
            'nid_number'               => 'required|string|max:20',
            'nid_front_image'          => 'required|image|max:5120',
            'nid_back_image'           => 'required|image|max:5120',
            'zone_id'                  => 'nullable|exists:zones,id',
            'package_id'               => 'nullable|exists:packages,id',
            'connection_type'          => 'required|in:PPPoE,Static IP,DHCP',
            'installation_feasibility' => 'required|boolean',
        ]);

        try {
            $application = $this->applicationService->createApplication(
                $request->only([
                    'full_name_bangla', 'full_name_english', 'mobile_number', 'nid_number',
                    'nid_front_image', 'nid_back_image', 'zone_id', 'package_id',
                    'connection_type', 'installation_feasibility', 'address', 'email',
                    'house_owner_permission', 'installation_place_photo', 'customer_signature',
                    'father_name', 'mother_name', 'date_of_birth', 'gender', 'occupation',
                    'present_address', 'permanent_address', 'district', 'upazila', 'notes',
                ]),
                Auth::id()
            );

            return redirect()
                ->route('admin.applications.show', $application)
                ->with('success', 'Application submitted successfully. Number: ' . $application->application_number);

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to submit application: ' . $e->getMessage());
        }
    }

    public function myApplications(Request $request)
    {
        $query = NewConnectionApplication::where('technician_id', Auth::id())
            ->with(['zone', 'package'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhere('full_name_english', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        $applications = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.applications.my-applications', compact('applications'));
    }

    public function pending()
    {
        $applications = NewConnectionApplication::pending()
            ->with(['technician', 'zone', 'package'])
            ->orderBy('created_at', 'asc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        $stats = $this->applicationService->getApplicationStats();

        return view('admin.applications.pending', compact('applications', 'stats'));
    }

    public function show(NewConnectionApplication $application)
    {
        $application->load([
            'technician',
            'zone',
            'package',
            'mikrotikDevice',
            'reviewer',
            'approver',
            'createdClient',
            'documents.uploader',
            'statusLogs.changedByUser',
            'nidVerification.verifier',
        ]);

        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $zones = Zone::orderBy('name')->get();
        $mikrotikDevices = MikrotikDevice::where('is_active', true)->orderBy('name')->get();
        $availableProfiles = \App\Models\MikrotikProfile::where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

                $oltDevices = \App\Models\OltDevice::where('is_active', true)->orderBy('name')->get();
        return view('admin.applications.show', compact('application', 'packages', 'zones', 'mikrotikDevices', 'availableProfiles', 'oltDevices'));
    }

    public function edit(NewConnectionApplication $application)
    {
        $application->load(['zone', 'package']);

        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $zones = Zone::where('is_active', true)->orderBy('name')->get();
        $divisions = \App\Models\District::where('is_active', true)
            ->whereNotNull('division')->where('division', '!=', '')
            ->distinct()->orderBy('division')->pluck('division');
        $districts = \App\Models\District::where('is_active', true)->orderBy('name')->get();
        $upazilas = \App\Models\Upazila::where('is_active', true)->orderBy('name')->get();
        $areas = \App\Models\Area::where('is_active', true)->orderBy('name')->get();
        $boxes = \App\Models\Box::where('status', 'active')->orderBy('name')->get();

        $agents = \App\Models\Agent::where('status', 'active')->orderBy('name')->get();
        $availableProfiles = \App\Models\MikrotikProfile::where('is_active', true)->orderBy('name')->pluck('name');

        return view('admin.applications.edit', compact('application', 'packages', 'zones', 'divisions', 'districts', 'upazilas', 'areas', 'boxes', 'agents', 'availableProfiles'));
    }

    public function update(Request $request, NewConnectionApplication $application)
    {
        $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'mobile_number'  => 'required|string|max:20',
            'zone_id'        => 'nullable|exists:zones,id',
            'package_id'     => 'nullable|exists:packages,id',
            'upazila_id'     => 'nullable|exists:upazilas,id',
            'sub_zone_id'    => 'nullable|exists:areas,id',
            'box_id'         => 'nullable|exists:boxes,id',
            'agent_id'       => 'nullable|exists:agents,id',
            'admin_note'     => 'nullable|string',
        ]);

        $request->merge([
            'full_name_english' => trim($request->first_name . ' ' . $request->last_name),
            'full_name_bangla'  => trim($request->first_name . ' ' . $request->last_name),
        ]);

        try {
            $data = $request->only([
                'full_name_bangla', 'full_name_english', 'first_name', 'last_name',
                'mobile_number', 'alternative_mobile', 'nid_number', 'email',
                'father_husband_name', 'mother_name', 'date_of_birth', 'gender', 'occupation',
                'division', 'district', 'thana_upazila',
                'district_id', 'upazila_id', 'zone_id', 'sub_zone_id', 'box_id',
                'house_road_floor',
                'connection_type', 'package_id', 'mikrotik_profile_name',
                'billing_cycle', 'installation_charge', 'cable_length_meters',
                'connection_medium', 'ip_type',
                'agent_id', 'commission_percent',
                'fiber_vendor', 'fiber_code', 'fiber_core_type', 'fiber_color',
                'splitter_number', 'splitter_color',
                'onu_brand', 'onu_mac', 'onu_serial',
                'admin_note', 'free_reason',
                'nid_front_image', 'nid_back_image',
                'house_owner_permission', 'installation_place_photo', 'customer_signature',
            ]);
            $data['onu_required']    = $request->has('onu_required') ? 1 : 0;
            $data['router_required'] = $request->has('router_required') ? 1 : 0;
            $data['fiber_required']  = $request->has('fiber_required') ? 1 : 0;
            $data['is_free']         = $request->input('is_free', 0) ? true : false;
            $data['free_reason']     = $data['is_free'] ? ($data['free_reason'] ?? null) : null;
            $this->applicationService->updateApplication($application, $data, Auth::id(), true);

            return redirect()
                ->route('admin.applications.show', $application)
                ->with('success', 'Application updated successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update application: ' . $e->getMessage());
        }
    }

    public function takeUnderReview(NewConnectionApplication $application)
    {
        try {
            $this->applicationService->takeUnderReview($application, Auth::id());

            return redirect()
                ->route('admin.applications.show', $application)
                ->with('success', 'Application is now under review.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function requestCorrection(Request $request, NewConnectionApplication $application)
    {
        $request->validate([
            'correction_note' => 'required|string|max:1000',
        ]);

        try {
            $this->applicationService->requestCorrection(
                $application,
                $request->correction_note,
                Auth::id()
            );

            return redirect()
                ->route('admin.applications.show', $application)
                ->with('success', 'Correction request sent to technician.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, NewConnectionApplication $application)
    {
        $validator = \Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('clients', 'username')],
            'password' => 'required|string|max:100',
            'mikrotik_device_id' => 'required|exists:mikrotik_devices,id',
            'admin_note' => 'nullable|string|max:1000',
            'client_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $application->update([
            'mikrotik_device_id' => $request->mikrotik_device_id,
            'onu_serial'   => $request->onu_serial   ?: $application->onu_serial,
            'onu_mac'      => $request->onu_mac       ?: $application->onu_mac,
            'onu_model'    => $request->onu_model     ?: $application->onu_model,
            'olt_port'     => $request->olt_port      ?: $application->olt_port,
            'olt_device_id'=> $request->olt_device_id ?: $application->olt_device_id,
        ]);

        try {
            $result = $this->applicationService->approveApplication(
                $application,
                Auth::id(),
                $request->admin_note,
                $request->username,
                $request->password,
                $request->mikrotik_profile_name,
                $request->client_code
            );

            $client = $result['client'];
            $mikrotik = $result['mikrotik'];

            $message = "Application approved successfully! Client ID: {$client->client_id}";
            $redirect = redirect()->route('admin.applications.show', $application);
            
            if ($mikrotik['success']) {
                $message .= ", MikroTik Username: {$mikrotik['username']}";
                return $redirect->with('success', $message);
            } else {
                return $redirect
                    ->with('success', $message)
                    ->with('mikrotik_error', "MikroTik user creation failed - {$mikrotik['error']}");
            }

        } catch (\Exception $e) {
            \Log::error("Application approval failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Approval failed: ' . $e->getMessage())->withInput();
        }
    }

    public function reject(Request $request, NewConnectionApplication $application)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            $this->applicationService->rejectApplication(
                $application,
                $request->rejection_reason,
                Auth::id()
            );

            return redirect()
                ->route('admin.applications.show', $application)
                ->with('success', 'Application rejected.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function verifyNid(Request $request, NewConnectionApplication $application)
    {
        $request->validate([
            'verification_status' => 'required|in:verified,rejected',
            'verification_note' => 'nullable|string|max:500',
        ]);

        $application->nidVerification?->update([
            'verification_status' => $request->verification_status,
            'verification_note' => $request->verification_note,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        $application->update([
            'nid_verification_status' => $request->verification_status,
        ]);

        return back()->with('success', 'NID verification status updated.');
    }

    public function statusLogs(NewConnectionApplication $application)
    {
        $logs = $application->statusLogs()
            ->with('changedByUser')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.applications.logs', compact('application', 'logs'));
    }

    public function export(Request $request)
    {
        $query = NewConnectionApplication::with(['technician', 'zone', 'package']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $applications = $query->orderBy('created_at', 'desc')->get();

        return view('admin.applications.export', compact('applications'));
    }

    public function destroy(NewConnectionApplication $application)
    {
        $application->delete();
        return redirect()->route('admin.applications.index')
            ->with('success', 'Application deleted successfully.');
    }
}
