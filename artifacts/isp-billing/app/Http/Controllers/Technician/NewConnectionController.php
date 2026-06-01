<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\NewConnectionApplication;
use App\Models\Package;
use App\Models\Zone;
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
        $technicianId = Auth::id();
        $query = NewConnectionApplication::byTechnician($technicianId)
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
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('nid_number', 'like', "%{$search}%");
            });
        }

        $applications = $query->paginate(15);
        $stats = $this->applicationService->getApplicationStats($technicianId);

        return view('technician.applications.index', compact('applications', 'stats'));
    }

    public function create()
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $zones = Zone::orderBy('name')->get();
        $oltDevices = \App\Models\OltDevice::where('is_active', true)->orderBy('name')->get();
        $availableProfiles = \App\Models\MikrotikProfile::where('is_active', true)
            ->orderBy('name')
            ->pluck('name');
        
        $technician = Auth::user();
        $assignedZones = $technician->zones ?? collect();

        return view('technician.applications.create', compact('packages', 'zones', 'assignedZones', 'oltDevices', 'availableProfiles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name_bangla' => 'required|string|max:255',
            'full_name_english' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:20',
            'nid_number' => 'required|string|max:20',
            'nid_front_image' => 'required|image|max:5120',
            'nid_back_image' => 'required|image|max:5120',
            'zone_id' => 'nullable|exists:zones,id',
            'package_id' => 'nullable|exists:packages,id',
            'connection_type' => 'required|in:PPPoE,Static IP,DHCP',
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
                ->route('technician.applications.show', $application)
                ->with('success', 'Application created successfully. Application Number: ' . $application->application_number);

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create application: ' . $e->getMessage());
        }
    }

    public function show(NewConnectionApplication $application)
    {
        $this->authorizeApplication($application);
        
        $application->load(['zone', 'package', 'documents', 'statusLogs.changedByUser', 'nidVerification']);

        return view('technician.applications.show', compact('application'));
    }

    public function edit(NewConnectionApplication $application)
    {
        $this->authorizeApplication($application);

        if (!$application->canBeEdited()) {
            return redirect()
                ->route('technician.applications.show', $application)
                ->with('error', 'This application cannot be edited in its current status.');
        }

        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $zones = Zone::orderBy('name')->get();
        $availableProfiles = \App\Models\MikrotikProfile::where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

        return view('technician.applications.edit', compact('application', 'packages', 'zones', 'availableProfiles'));
    }

    public function update(Request $request, NewConnectionApplication $application)
    {
        $this->authorizeApplication($application);

        if (!$application->canBeEdited()) {
            return redirect()
                ->route('technician.applications.show', $application)
                ->with('error', 'This application cannot be edited in its current status.');
        }

        $request->validate([
            'full_name_bangla' => 'required|string|max:255',
            'full_name_english' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:20',
            'nid_number' => 'required|string|max:20',
            'nid_front_image' => 'nullable|image|max:5120',
            'nid_back_image' => 'nullable|image|max:5120',
        ]);

        try {
            $this->applicationService->updateApplication($application, $request->only([
                'full_name_bangla', 'full_name_english', 'mobile_number', 'nid_number',
                'nid_front_image', 'nid_back_image', 'zone_id', 'package_id',
                'connection_type', 'installation_feasibility', 'address', 'email',
                'house_owner_permission', 'installation_place_photo', 'customer_signature',
                'father_name', 'mother_name', 'date_of_birth', 'gender', 'occupation',
                'present_address', 'permanent_address', 'district', 'upazila', 'notes',
            ]), Auth::id());

            return redirect()
                ->route('technician.applications.show', $application)
                ->with('success', 'Application updated successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update application: ' . $e->getMessage());
        }
    }

    public function submit(NewConnectionApplication $application)
    {
        $this->authorizeApplication($application);

        try {
            $this->applicationService->submitApplication($application, Auth::id());

            return redirect()
                ->route('technician.applications.show', $application)
                ->with('success', 'Application submitted for review successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit application: ' . $e->getMessage());
        }
    }

    public function uploadDocument(Request $request, NewConnectionApplication $application)
    {
        $this->authorizeApplication($application);

        $request->validate([
            'document' => 'required|file|max:10240',
            'document_type' => 'required|string|max:100',
        ]);

        try {
            $this->applicationService->uploadDocument(
                $application,
                $request->file('document'),
                $request->document_type,
                Auth::id()
            );

            return back()->with('success', 'Document uploaded successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    public function getSubZones(Request $request)
    {
        return response()->json([]);
    }

    protected function authorizeApplication(NewConnectionApplication $application): void
    {
        if ($application->technician_id !== Auth::id()) {
            abort(403, 'You are not authorized to access this application.');
        }
    }
}
