<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MikrotikActionLog;
use App\Models\MikrotikDevice;
use App\Services\NetworkMonitoringService;
use Illuminate\Http\Request;

class NetworkMonitoringController extends Controller
{
    protected NetworkMonitoringService $monitoringService;

    public function __construct(NetworkMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    public function popStats()
    {
        $stats = $this->monitoringService->getPopStats();
        
        return response()->json([
            'success' => true,
            'stats' => $stats,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function syncClientSpeed(Request $request, Client $client)
    {
        $result = $this->monitoringService->syncClientSpeed($client);
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 400);
    }

    public function bulkSpeedSync(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $results = [];
        foreach ($request->client_ids as $clientId) {
            $client = Client::find($clientId);
            if ($client) {
                $results[$clientId] = $this->monitoringService->syncClientSpeed($client);
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    public function runAutoDowngrade()
    {
        $result = $this->monitoringService->autoDowngradeOverdueClients();

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    public function restoreClientSpeed(Request $request, Client $client)
    {
        $result = $this->monitoringService->restoreClientSpeed($client);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Speed restored successfully' : 'Failed to restore speed',
        ], $result ? 200 : 400);
    }

    public function bandwidthStats()
    {
        $stats = $this->monitoringService->getBandwidthStats();

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    public function retryStats()
    {
        $stats = $this->monitoringService->getMikrotikRetryStats();

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    public function failedActions()
    {
        $actions = MikrotikActionLog::where('status', 'failed')
            ->with(['client', 'device'])
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return response()->json([
            'success' => true,
            'actions' => $actions,
        ]);
    }

    public function retryFailedAction(Request $request, $id)
    {
        $actionLog = MikrotikActionLog::findOrFail($id);

        if ($actionLog->status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Only failed actions can be retried',
            ], 400);
        }

        try {
            $client = $actionLog->client;
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found for this action',
                ], 400);
            }

            $mikrotik = new \App\Services\MikroTikService();
            
            $result = match ($actionLog->action) {
                MikrotikActionLog::ACTION_ENABLE => $mikrotik->restoreClient($client, 'manual_retry', auth()->id(), false),
                MikrotikActionLog::ACTION_DISABLE => $mikrotik->suspendClient($client, 'manual_retry', auth()->id(), false),
                default => ['success' => false, 'error' => 'Unknown action type'],
            };

            if ($result['success']) {
                $actionLog->markSuccess(['retried_manually' => true, 'retried_by' => auth()->id()]);
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Action retried successfully' : ($result['error'] ?? 'Retry failed'),
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            \Log::error("Manual retry failed for action {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Retry failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deviceOverview()
    {
        $devices = MikrotikDevice::withCount([
            'clients' => fn($q) => $q->where('status', 'active'),
        ])
        ->get()
        ->map(function ($device) {
            return [
                'id' => $device->id,
                'name' => $device->name,
                'ip_address' => $device->ip_address,
                'location' => $device->location,
                'is_active' => $device->is_active,
                'active_clients' => $device->clients_count,
                'last_seen' => $device->last_seen,
            ];
        });

        return response()->json([
            'success' => true,
            'devices' => $devices,
        ]);
    }

    public function limitedClients()
    {
        $clients = Client::where('status', 'limited')
            ->with(['zone', 'package'])
            ->orderBy('updated_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return response()->json([
            'success' => true,
            'clients' => $clients,
        ]);
    }
}
