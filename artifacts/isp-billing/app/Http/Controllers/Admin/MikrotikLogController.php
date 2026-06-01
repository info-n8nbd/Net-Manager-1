<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikActionLog;
use App\Services\MikroTikService;
use Illuminate\Http\Request;

class MikrotikLogController extends Controller
{
    public function index(Request $request)
    {
        $query = MikrotikActionLog::with(['client', 'device', 'triggeredBy'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trigger_source')) {
            $query->where('trigger_source', $request->trigger_source);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('router_ip', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50)->appends($request->query());

        $stats = [
            'total' => MikrotikActionLog::count(),
            'success' => MikrotikActionLog::where('status', 'success')->count(),
            'failed' => MikrotikActionLog::where('status', 'failed')->count(),
            'pending' => MikrotikActionLog::where('status', 'pending')->count(),
            'today' => MikrotikActionLog::whereDate('created_at', today())->count(),
            'failed_today' => MikrotikActionLog::where('status', 'failed')
                ->whereDate('created_at', today())->count(),
        ];

        $triggerSources = [
            MikrotikActionLog::TRIGGER_AUTO_SUSPEND => 'Auto Suspend',
            MikrotikActionLog::TRIGGER_AUTO_RESTORE => 'Auto Restore',
            MikrotikActionLog::TRIGGER_PAYMENT => 'Payment',
            MikrotikActionLog::TRIGGER_ADMIN => 'Admin',
            MikrotikActionLog::TRIGGER_MANUAL => 'Manual',
            MikrotikActionLog::TRIGGER_BILLING => 'Billing',
            MikrotikActionLog::TRIGGER_DUE_EXTENSION => 'Due Extension',
        ];

        return view('admin.mikrotik.logs', compact('logs', 'stats', 'triggerSources'));
    }

    public function retryFailed()
    {
        $mikrotikService = app(MikroTikService::class);
        $result = $mikrotikService->retryFailedActions();

        return back()->with('success', 
            "Retried {$result['retried']} actions. Success: {$result['success']}, Still failed: {$result['still_failed']}"
        );
    }

    public function show(MikrotikActionLog $log)
    {
        $log->load(['client', 'device', 'triggeredBy']);
        
        return response()->json($log);
    }

    public function stats()
    {
        return response()->json([
            'failed_count' => MikroTikService::getFailedActionCount(),
            'pending_count' => MikroTikService::getPendingActionCount(),
        ]);
    }
}
