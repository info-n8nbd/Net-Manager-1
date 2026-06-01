<?php

namespace App\Services;

use App\Models\NewConnectionApplication;
use App\Models\ApplicationStatusLog;
use App\Models\ApplicationDocument;
use App\Models\NidVerification;
use App\Models\Client;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class NewConnectionApplicationService
{
    protected MikroTikUserCreationService $mikrotikService;
    protected SmsService $smsService;
    protected BillingService $billingService;

    public function __construct(MikroTikUserCreationService $mikrotikService, SmsService $smsService, BillingService $billingService)
    {
        $this->mikrotikService = $mikrotikService;
        $this->smsService = $smsService;
        $this->billingService = $billingService;
    }

    public function createApplication(array $data, int $technicianId): NewConnectionApplication
    {
        DB::beginTransaction();
        try {
            $data['application_number'] = NewConnectionApplication::generateApplicationNumber();
            $data['technician_id'] = $technicianId;
            $data['status'] = 'draft';

            if (isset($data['nid_front_image']) && $data['nid_front_image'] instanceof UploadedFile) {
                $data['nid_front_image'] = $this->uploadFile($data['nid_front_image'], 'applications/nid');
            }
            if (isset($data['nid_back_image']) && $data['nid_back_image'] instanceof UploadedFile) {
                $data['nid_back_image'] = $this->uploadFile($data['nid_back_image'], 'applications/nid');
            }
            if (isset($data['house_owner_permission']) && $data['house_owner_permission'] instanceof UploadedFile) {
                $data['house_owner_permission'] = $this->uploadFile($data['house_owner_permission'], 'applications/documents');
            }
            if (isset($data['installation_place_photo']) && $data['installation_place_photo'] instanceof UploadedFile) {
                $data['installation_place_photo'] = $this->uploadFile($data['installation_place_photo'], 'applications/photos');
            }
            if (isset($data['customer_signature']) && $data['customer_signature'] instanceof UploadedFile) {
                $data['customer_signature'] = $this->uploadFile($data['customer_signature'], 'applications/signatures');
            }

            $application = NewConnectionApplication::create($data);

            NidVerification::create([
                'application_id' => $application->id,
                'nid_number' => $data['nid_number'],
                'verification_status' => 'pending',
            ]);

            $this->logStatusChange($application, null, 'draft', 'Application created', $technicianId);

            DB::commit();
            return $application;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create application: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateApplication(NewConnectionApplication $application, array $data, int $userId, bool $isAdmin = false): NewConnectionApplication
    {
        if (!$application->canBeEdited($isAdmin)) {
            throw new \Exception("Application cannot be edited in current status: {$application->status}");
        }

        DB::beginTransaction();
        try {
            $originalData = $application->toArray();

            if (isset($data['nid_front_image']) && $data['nid_front_image'] instanceof UploadedFile) {
                $data['nid_front_image'] = $this->uploadFile($data['nid_front_image'], 'applications/nid');
            }
            if (isset($data['nid_back_image']) && $data['nid_back_image'] instanceof UploadedFile) {
                $data['nid_back_image'] = $this->uploadFile($data['nid_back_image'], 'applications/nid');
            }

            $application->update($data);

            if ($application->created_client_id) {
                $syncFields = [
                    'full_name_english' => 'name',
                    'father_husband_name' => 'father_husband_name',
                    'mother_name' => 'mother_name',
                    'date_of_birth' => 'date_of_birth',
                    'gender' => 'gender',
                    'occupation' => 'occupation',
                    'email' => 'email',
                    'mobile_number' => 'phone',
                    'alternative_mobile' => 'alt_phone',
                    'nid_number' => 'nid',
                    'agent_id' => 'agent_id',
                    'commission_percent' => 'commission_percent',
                ];
                $clientUpdate = [];
                foreach ($syncFields as $appField => $clientField) {
                    if (array_key_exists($appField, $data)) {
                        $clientUpdate[$clientField] = $application->$appField;
                    }
                }
                if (!empty($clientUpdate)) {
                    Client::where('id', $application->created_client_id)->update($clientUpdate);
                }
            }

            $changes = $this->detectChanges($originalData, $application->toArray());
            if (!empty($changes)) {
                $this->logStatusChange($application, $application->status, $application->status, 'Application updated', $userId, $changes);
            }

            DB::commit();
            return $application->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update application: " . $e->getMessage());
            throw $e;
        }
    }

    public function submitApplication(NewConnectionApplication $application, int $technicianId): NewConnectionApplication
    {
        if (!$application->canBeSubmitted()) {
            throw new \Exception("Application cannot be submitted in current status: {$application->status}");
        }

        $this->validateForSubmission($application);

        DB::beginTransaction();
        try {
            $fromStatus = $application->status;
            $application->update(['status' => 'submitted']);

            $this->logStatusChange($application, $fromStatus, 'submitted', 'Application submitted for review', $technicianId);

            $this->smsService->sendApplicationSubmitted($application);

            DB::commit();
            return $application->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to submit application: " . $e->getMessage());
            throw $e;
        }
    }

    public function takeUnderReview(NewConnectionApplication $application, int $adminId): NewConnectionApplication
    {
        if ($application->status !== 'submitted') {
            throw new \Exception("Only submitted applications can be taken under review");
        }

        DB::beginTransaction();
        try {
            $application->update([
                'status' => 'under_review',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            $this->logStatusChange($application, 'submitted', 'under_review', 'Application taken under review', $adminId);

            DB::commit();
            return $application->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function requestCorrection(NewConnectionApplication $application, string $correctionNote, int $adminId): NewConnectionApplication
    {
        if (!in_array($application->status, ['submitted', 'under_review'])) {
            throw new \Exception("Cannot request correction for application in current status");
        }

        DB::beginTransaction();
        try {
            $fromStatus = $application->status;
            $application->update([
                'status' => 'need_correction',
                'correction_request' => $correctionNote,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            $this->logStatusChange($application, $fromStatus, 'need_correction', $correctionNote, $adminId);

            $this->smsService->sendCorrectionRequested($application);

            DB::commit();
            return $application->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approveApplication(NewConnectionApplication $application, int $adminId, ?string $adminNote = null, ?string $username = null, ?string $password = null, ?string $mikrotikProfileName = null, ?string $clientCode = null): array
    {
        if (!$application->canBeApproved()) {
            throw new \Exception("Application cannot be approved in current status: {$application->status}");
        }

        DB::beginTransaction();
        try {
            $fromStatus = $application->status;

            $client = $this->createClientFromApplication($application, $username, $password, $mikrotikProfileName, $clientCode);

            $mikrotikResult = $this->mikrotikService->createUserFromApplication($application, $client);

            $application->nidVerification?->update([
                'verification_status' => 'verified',
                'verified_by' => $adminId,
                'verified_at' => now(),
            ]);

            $application->update([
                'status' => 'approved',
                'nid_verification_status' => 'verified',
                'admin_note' => $adminNote,
                'approved_by' => $adminId,
                'approved_at' => now(),
                'created_client_id' => $client->id,
                'mikrotik_username' => $mikrotikResult['username'] ?? null,
                'mikrotik_device_id' => $mikrotikResult['device_id'] ?? null,
                'mikrotik_created' => $mikrotikResult['success'] ?? false,
                'mikrotik_creation_log' => json_encode($mikrotikResult),
            ]);

            $this->logStatusChange($application, $fromStatus, 'approved', 'Application approved. Client ID: ' . $client->client_id, $adminId);

            if (!$client->is_free) {
                try {
                    $this->billingService->generateBill($client, now()->month, now()->year, suppressSms: true);
                    Log::info("Auto-generated first bill for approved client: {$client->name}");
                } catch (\Exception $billError) {
                    Log::warning("Failed to auto-generate bill for approved client {$client->name}: " . $billError->getMessage());
                }
            }

            DB::commit();

            try {
                $this->smsService->sendApprovalNotification($application, $client, $mikrotikResult);
            } catch (\Exception $e) {
                \Log::warning('SMS approval notification failed: ' . $e->getMessage());
            }

            try {
                $package = \App\Models\Package::find($application->package_id);
                $zone = \App\Models\Zone::find($application->zone_id);
                $approver = \App\Models\User::find($adminId);
                $mikrotikDevice = $client->mikrotik_device_id ? \App\Models\MikrotikDevice::find($client->mikrotik_device_id) : null;
                (new TelegramService())->sendClientCreationNotification(
                    $client->client_id ?? (string) $client->id,
                    $client->name ?? '',
                    $package?->name ?? 'N/A',
                    $zone?->name ?? 'N/A',
                    $approver?->name ?? 'System',
                    $client->phone,
                    $client->username ?? '',
                    $client->address ?? '',
                    $client->connection_type ?? '',
                    (float) ($package?->price ?? 0),
                    $mikrotikDevice?->identity ?? $mikrotikDevice?->host ?? '',
                    (int) ($client->payment_deadline ?? 0),
                    (float)$client->installation_charge ?: (float)$client->signup_fee ?: 0
                );
            } catch (\Throwable $e) {
                \Log::warning('Telegram client creation notification failed: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'application' => $application->fresh(),
                'client' => $client,
                'mikrotik' => $mikrotikResult,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to approve application {$application->id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function rejectApplication(NewConnectionApplication $application, string $reason, int $adminId): NewConnectionApplication
    {
        if (!in_array($application->status, ['submitted', 'under_review', 'need_correction'])) {
            throw new \Exception("Cannot reject application in current status");
        }

        DB::beginTransaction();
        try {
            $fromStatus = $application->status;
            $application->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            $this->logStatusChange($application, $fromStatus, 'rejected', $reason, $adminId);

            DB::commit();

            try {
                $this->smsService->sendRejectionNotification($application);
            } catch (\Exception $e) {
                \Log::warning('SMS rejection notification failed: ' . $e->getMessage());
            }

            return $application->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function createClientFromApplication(NewConnectionApplication $application, ?string $username = null, ?string $password = null, ?string $mikrotikProfileName = null, ?string $clientCode = null): Client
    {
        $clientId = !empty($clientCode) ? $clientCode : Client::generateClientId($application->zone_id);
        
        if (!$username || !$password) {
            $serialNumber = $this->getNextSerialNumber();
            $username = $username ?: $this->generatePppoeUsername($application->full_name_english, $serialNumber);
            $password = $password ?: (string) $serialNumber;
        }
        
        $package = Package::find($application->package_id);
        $monthlyFee = $package ? $package->price : 0;

        $expireDate = $application->expire_date;
        if (!$expireDate || $expireDate->lte(now()->startOfDay())) {
            $cycle = $application->billing_cycle ?? 'monthly';
            if ($cycle === 'quarterly') {
                $expireDate = now()->addMonths(3);
            } elseif ($cycle === 'yearly') {
                $expireDate = now()->addYear();
            } else {
                $expireDate = now()->addMonth();
            }
        }

        $billingDate = $application->auto_inactive_day ?: (int) $expireDate->format('d');

        $client = Client::create([
            'application_id' => $application->id,
            'client_id' => $clientId,
            'name' => $application->full_name_english,
            'father_husband_name' => $application->father_husband_name,
            'mother_name' => $application->mother_name,
            'date_of_birth' => $application->date_of_birth,
            'gender' => $application->gender,
            'occupation' => $application->occupation,
            'email' => $application->email,
            'phone' => $application->mobile_number,
            'alt_phone' => $application->alternative_mobile,
            'nid' => $application->nid_number,
            'profile_picture' => $application->installation_place_photo,
            'nid_front_image' => $application->nid_front_image,
            'nid_back_image' => $application->nid_back_image,
            'address' => $application->house_road_floor ?? $application->full_name_english,
            'nearby_landmark' => $application->nearby_landmark,
            'zone_id' => $application->zone_id,
            'area_id' => $application->sub_zone_id,
            'box_id' => $application->box_id,
            'district_id' => $application->district_id,
            'upazila_id' => $application->upazila_id,
            'package_id' => $application->package_id,
            'connection_type' => 'pppoe',
            'connection_medium' => $application->connection_medium ?? 'fiber',
            'billing_cycle' => $application->billing_cycle ?? 'monthly',
            'installation_charge' => $application->installation_charge ?? 0,
            'signup_fee' => 0,
            'signup_fee_paid' => false,
            'cable_length_meters' => $application->cable_length_meters ?? 0,
            'onu_required' => $application->onu_required ?? false,
            'router_required' => $application->router_required ?? false,
            'fiber_required' => $application->fiber_required ?? true,
            'preferred_payment_method' => $application->payment_method,
            'technician_note' => $application->technician_note,
            'assigned_to' => $application->technician_id,
            'username' => $username,
            'pppoe_password' => $password,
            'google_map_lat' => $application->latitude,
            'google_map_lng' => $application->longitude,
            'monthly_fee' => $monthlyFee,
            'billing_date' => $billingDate,
            'payment_deadline' => $billingDate,
            'status' => 'active',
            'connection_status' => 'offline',
            'connection_date' => now(),
            'expire_date' => $expireDate,
            'fiber_code' => $application->fiber_code,
            'fiber_color' => $application->fiber_color,
            'splitter_number' => $application->splitter_number,
            'splitter_color' => $application->splitter_color,
            'onu_mac'      => $application->onu_mac,
            'onu_brand'    => $application->onu_brand,
            'onu_serial'   => $application->onu_serial,
            'onu_model'    => $application->onu_model,
            'olt_port'     => $application->olt_port,
            'olt_device_id'=> $application->olt_device_id,
            'fiber_vendor' => $application->fiber_vendor,
            'fiber_core_type' => $application->fiber_core_type,
            'mikrotik_profile_name' => $mikrotikProfileName,
            'mikrotik_device_id' => $application->mikrotik_device_id,
            'agent_id' => $application->agent_id,
            'commission_percent' => $application->commission_percent,
            'is_free' => $application->is_free ?? false,
            'free_reason' => $application->free_reason,
        ]);

        $installAmount = (float)$client->installation_charge ?: (float)$client->signup_fee ?: 0;
        $existingBaseCharge = \App\Models\ClientServiceCharge::where('client_id', $client->id)
            ->where('charge_type', 'Installation Charge')
            ->whereNull('paid_by')
            ->first();
        if (!$existingBaseCharge) {
            \App\Models\ClientServiceCharge::create([
                'client_id' => $client->id,
                'charge_type' => 'Installation Charge',
                'amount' => $installAmount,
                'description' => $installAmount > 0 ? 'Installation charge' : 'Free installation',
                'payment_method' => null,
                'paid_by' => null,
                'charge_date' => now()->toDateString(),
            ]);
        }

        return $client;
    }
    
    protected function getNextSerialNumber(): int
    {
        $lastClient = Client::orderBy('id', 'desc')->first();
        return $lastClient ? $lastClient->id + 1 : 1;
    }
    
    protected function generatePppoeUsername(string $name, int $serialNumber): string
    {
        $cleanName = strtolower(preg_replace('/[^a-zA-Z]/', '', $name));
        $cleanName = $cleanName ?: 'user';
        $username = $cleanName . $serialNumber;
        
        $counter = 1;
        $baseUsername = $username;
        while (Client::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $counter;
            $counter++;
        }
        
        return $username;
    }

    protected function validateForSubmission(NewConnectionApplication $application): void
    {
        $required = ['full_name_english', 'mobile_number', 'nid_number', 'nid_front_image', 'nid_back_image'];
        $missing = [];

        foreach ($required as $field) {
            if (empty($application->$field)) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \Exception("Missing required fields: " . implode(', ', $missing));
        }
    }

    protected function logStatusChange(
        NewConnectionApplication $application,
        ?string $fromStatus,
        string $toStatus,
        ?string $note,
        int $userId,
        ?array $changes = null
    ): void {
        ApplicationStatusLog::create([
            'application_id' => $application->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'changed_by' => $userId,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected function uploadFile(UploadedFile $file, string $directory): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $disk = str_contains($directory, 'nid') ? 'local' : 'public';
        return $file->storeAs($directory, $filename, $disk);
    }

    protected function detectChanges(array $original, array $new): array
    {
        $changes = [];
        $trackFields = ['full_name_english', 'mobile_number', 'package_id', 'zone_id', 'status'];
        
        foreach ($trackFields as $field) {
            if (isset($original[$field]) && isset($new[$field]) && $original[$field] != $new[$field]) {
                $changes[$field] = [
                    'from' => $original[$field],
                    'to' => $new[$field],
                ];
            }
        }
        
        return $changes;
    }

    public function uploadDocument(NewConnectionApplication $application, UploadedFile $file, string $documentType, int $userId): ApplicationDocument
    {
        $path = $this->uploadFile($file, 'applications/documents');
        
        return ApplicationDocument::create([
            'application_id' => $application->id,
            'document_type' => $documentType,
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $userId,
        ]);
    }

    public function getApplicationStats(?int $technicianId = null): array
    {
        $authUser = auth()->user();
        // Super Admin sees all companies; everyone else only sees their own company
        if ($authUser && $authUser->isSuperAdmin()) {
            $query = NewConnectionApplication::withoutGlobalScope("tenant");
        } else {
            $companyId = $authUser ? $authUser->company_id : 0;
            $query = NewConnectionApplication::query()->where('company_id', $companyId);
        }

        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        return [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'submitted' => (clone $query)->where('status', 'submitted')->count(),
            'under_review' => (clone $query)->where('status', 'under_review')->count(),
            'need_correction' => (clone $query)->where('status', 'need_correction')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'pending_count' => (clone $query)->whereIn('status', ['submitted', 'under_review'])->count(),
        ];
    }
}
