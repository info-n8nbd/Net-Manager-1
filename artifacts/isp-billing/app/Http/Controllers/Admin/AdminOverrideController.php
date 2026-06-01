<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TechnicianActionLog;
use App\Models\AdminOverride;
use App\Services\SuperAdminGuardService;
use App\Services\TechnicianOverrideService;
use Illuminate\Http\Request;

class AdminOverrideController extends Controller
{
    protected TechnicianOverrideService $overrideService;

    public function __construct(TechnicianOverrideService $overrideService)
    {
        $this->overrideService = $overrideService;
    }

    private function requireSuperAdmin(): void
    {
        if (!SuperAdminGuardService::isSuperAdmin(auth()->user())) {
            abort(403, 'Only Super Admins can perform override actions.');
        }
    }

    public function index(Request $request)
    {
        $query = TechnicianActionLog::with(['technician', 'client'])
            ->orderBy('created_at', 'desc');

        if ($request->technician_id) {
            $query->where('technician_id', $request->technician_id);
        }

        if ($request->category) {
            $query->where('action_category', $request->category);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->overrideable_only) {
            $query->overrideable();
        }

        $actions = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'actions' => $actions,
            ]);
        }

        return view('admin.overrides.index', compact('actions'));
    }

    public function show(TechnicianActionLog $actionLog)
    {
        $actionLog->load(['technician', 'client', 'adminOverride.admin']);

        return response()->json([
            'success' => true,
            'action' => $actionLog,
        ]);
    }

    public function overridePayment(Request $request, TechnicianActionLog $actionLog)
    {
        $this->requireSuperAdmin();
        $request->validate([
            'override_type' => 'required|in:edit,cancel,reverse',
            'reason' => 'required|string|min:10',
            'amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
        ]);

        if (!auth()->user()->can('technician.payment.correct')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($actionLog->action_category !== TechnicianActionLog::CATEGORY_PAYMENT) {
            return response()->json(['success' => false, 'message' => 'Invalid action category for payment override'], 400);
        }

        if ($actionLog->is_overridden) {
            return response()->json(['success' => false, 'message' => 'This action has already been overridden'], 400);
        }

        try {
            $result = $this->overrideService->overridePayment(
                $actionLog,
                auth()->id(),
                $request->override_type,
                $request->only(['amount', 'payment_method']),
                $request->reason
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function overrideCashSettlement(Request $request, TechnicianActionLog $actionLog)
    {
        $this->requireSuperAdmin();
        $request->validate([
            'override_type' => 'required|in:correct,cancel',
            'reason' => 'required|string|min:10',
            'corrected_amount' => 'nullable|numeric|min:0',
        ]);

        if (!auth()->user()->can('technician.cash.adjust')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($actionLog->action_category !== TechnicianActionLog::CATEGORY_CASH) {
            return response()->json(['success' => false, 'message' => 'Invalid action category for cash override'], 400);
        }

        if ($actionLog->is_overridden) {
            return response()->json(['success' => false, 'message' => 'This action has already been overridden'], 400);
        }

        try {
            $result = $this->overrideService->overrideCashSettlement(
                $actionLog,
                auth()->id(),
                $request->override_type,
                $request->only(['corrected_amount']),
                $request->reason
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function overrideBill(Request $request, TechnicianActionLog $actionLog)
    {
        $this->requireSuperAdmin();
        $request->validate([
            'override_type' => 'required|in:edit,cancel',
            'reason' => 'required|string|min:10',
            'amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        if (!auth()->user()->can('technician.bill.correct')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($actionLog->action_category !== TechnicianActionLog::CATEGORY_BILLING) {
            return response()->json(['success' => false, 'message' => 'Invalid action category for bill override'], 400);
        }

        if ($actionLog->is_overridden) {
            return response()->json(['success' => false, 'message' => 'This action has already been overridden'], 400);
        }

        try {
            $result = $this->overrideService->overrideBill(
                $actionLog,
                auth()->id(),
                $request->override_type,
                $request->only(['amount', 'due_date']),
                $request->reason
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function overrideClientStatus(Request $request, TechnicianActionLog $actionLog)
    {
        $this->requireSuperAdmin();
        $request->validate([
            'override_type' => 'required|in:reverse,correct',
            'reason' => 'required|string|min:10',
            'status' => 'nullable|string',
        ]);

        if (!auth()->user()->can('technician.status.override')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($actionLog->action_category !== TechnicianActionLog::CATEGORY_CLIENT_STATUS) {
            return response()->json(['success' => false, 'message' => 'Invalid action category for status override'], 400);
        }

        if ($actionLog->is_overridden) {
            return response()->json(['success' => false, 'message' => 'This action has already been overridden'], 400);
        }

        try {
            $result = $this->overrideService->overrideClientStatus(
                $actionLog,
                auth()->id(),
                $request->override_type,
                $request->only(['status']),
                $request->reason
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function overrideClientData(Request $request, TechnicianActionLog $actionLog)
    {
        $this->requireSuperAdmin();
        $request->validate([
            'override_type' => 'required|in:edit,cancel',
            'reason' => 'required|string|min:10',
            'data' => 'nullable|array',
        ]);

        if (!auth()->user()->can('technician.action.override')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($actionLog->action_category !== TechnicianActionLog::CATEGORY_CLIENT_DATA) {
            return response()->json(['success' => false, 'message' => 'Invalid action category for data override'], 400);
        }

        if ($actionLog->is_overridden) {
            return response()->json(['success' => false, 'message' => 'This action has already been overridden'], 400);
        }

        try {
            $result = $this->overrideService->overrideClientData(
                $actionLog,
                auth()->id(),
                $request->override_type,
                $request->input('data', []),
                $request->reason
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function overrideNetworkAction(Request $request, TechnicianActionLog $actionLog)
    {
        $this->requireSuperAdmin();
        $request->validate([
            'override_type' => 'required|in:reverse,cancel',
            'reason' => 'required|string|min:10',
        ]);

        if (!auth()->user()->can('technician.action.override')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($actionLog->action_category !== TechnicianActionLog::CATEGORY_NETWORK) {
            return response()->json(['success' => false, 'message' => 'Invalid action category for network override'], 400);
        }

        if ($actionLog->is_overridden) {
            return response()->json(['success' => false, 'message' => 'This action has already been overridden'], 400);
        }

        try {
            $result = $this->overrideService->overrideNetworkAction(
                $actionLog,
                auth()->id(),
                $request->override_type,
                [],
                $request->reason
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function overrideHistory(Request $request)
    {
        $history = $this->overrideService->getOverrideHistory(
            $request->client_id,
            $request->technician_id,
            $request->limit ?? 50
        );

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    public function pendingNetworkSyncs()
    {
        $pending = $this->overrideService->getPendingNetworkSyncs();

        return response()->json([
            'success' => true,
            'pending_syncs' => $pending,
        ]);
    }

    public function auditLog(Request $request)
    {
        $query = AdminOverride::with(['admin', 'technicianActionLog.technician', 'client'])
            ->orderBy('created_at', 'desc');

        if ($request->admin_id) {
            $query->where('admin_id', $request->admin_id);
        }

        if ($request->category) {
            $query->where('action_category', $request->category);
        }

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'logs' => $logs,
            ]);
        }

        return view('admin.overrides.audit-log', compact('logs'));
    }
}
