<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikDevice;
use App\Models\MikrotikSyncRun;
use App\Models\MikrotikSyncItem;
use App\Services\MikroTikSyncService;
use Illuminate\Http\Request;

class MikrotikSyncController extends Controller
{
    public function index()
    {
        $runs = MikrotikSyncRun::with(['device', 'initiator'])
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        $devices = MikrotikDevice::where('is_active', true)->orderBy('name')->get();

        return view('admin.network.mikrotik-sync.index', compact('runs', 'devices'));
    }

    public function show(MikrotikSyncRun $run)
    {
        $run->load(['device', 'initiator', 'items' => function ($q) {
            $q->orderByRaw("CASE diff_type 
                WHEN 'only_on_router' THEN 1 
                WHEN 'mismatch' THEN 2 
                WHEN 'only_in_billing' THEN 3 
                WHEN 'synced' THEN 4 
                ELSE 5 END");
        }]);

        $stats = [
            'synced' => $run->items->where('diff_type', 'synced')->count(),
            'only_on_router' => $run->items->where('diff_type', 'only_on_router')->count(),
            'only_in_billing' => $run->items->where('diff_type', 'only_in_billing')->count(),
            'mismatch' => $run->items->where('diff_type', 'mismatch')->count(),
            'pending' => $run->items->where('action_status', 'pending')->count(),
            'applied' => $run->items->where('action_status', 'applied')->count(),
            'failed' => $run->items->where('action_status', 'failed')->count(),
            'skipped' => $run->items->where('action_status', 'skipped')->count(),
        ];

        return view('admin.network.mikrotik-sync.detail', compact('run', 'stats'));
    }

    public function runSync(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:mikrotik_devices,id',
            'mode' => 'required|in:dry_run,apply',
        ]);

        $device = MikrotikDevice::findOrFail($request->device_id);
        $service = new MikroTikSyncService();
        $run = $service->runSync($device, $request->mode, auth()->id());

        if ($run->status === 'failed') {
            return redirect()->route('admin.mikrotik-sync.index')
                ->with('error', 'Sync failed: ' . $run->error_message);
        }

        return redirect()->route('admin.mikrotik-sync.show', $run)
            ->with('success', 'Sync completed successfully!');
    }

    public function applyAction(Request $request, MikrotikSyncItem $item)
    {
        $request->validate([
            'action' => 'nullable|string|in:create_on_router,create_disabled_on_router,update_profile,enable_on_router,disable_on_router,update_profile_and_status,review',
        ]);

        $service = new MikroTikSyncService();
        $result = $service->applySingleAction($item, $request->action);

        $item->refresh();

        if ($result) {
            return redirect()->back()->with('success', "Action applied successfully for {$item->username}");
        }

        return redirect()->back()->with('error', "Action failed for {$item->username}: " . ($item->action_message ?? 'Unknown error'));
    }

    public function skipAction(MikrotikSyncItem $item)
    {
        $service = new MikroTikSyncService();
        $service->skipItem($item, 'Manually skipped by admin');

        return redirect()->back()->with('success', "{$item->username} marked as skipped");
    }

    public function applyAll(MikrotikSyncRun $run)
    {
        $device = $run->device;
        $service = new MikroTikSyncService();

        $mikrotik = new \App\Services\SimpleMikroTikService();
        $connected = $mikrotik->connectFromDevice($device);

        if (!$connected) {
            return redirect()->back()->with('error', 'Failed to connect to router');
        }

        $results = $service->applyActions($run);

        $mikrotik->disconnect();

        return redirect()->back()->with('success', 
            "Applied: {$results['applied']}, Failed: {$results['failed']}, Skipped: {$results['skipped']}");
    }
}
