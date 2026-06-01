<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Zone;
use App\Models\Package;
use App\Services\DeviceControlService;
use App\Services\ClientAutoProvisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BulkOperationsController extends Controller
{
    public function index()
    {
        $zones = Zone::where('is_active', true)->get();
        $packages = Package::where('is_active', true)->get();
        $clients = Client::with(['zone', 'package'])
            ->whereIn('status', ['active', 'inactive', 'pending'])
            ->orderBy('name')
            ->get();

        return view('admin.network.bulk-operations', compact('zones', 'packages', 'clients'));
    }

    public function bulkEnable(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $deviceControl = new DeviceControlService();
        $results = [];

        $clients = Client::with('package')->whereIn('id', $request->client_ids)->get();

        foreach ($clients as $client) {
            try {
                $result = $deviceControl->enableClient($client);
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => $result['success'],
                    'message' => $result['message'],
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'total' => count($results),
            'successful' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count(),
        ]);
    }

    public function bulkDisable(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $deviceControl = new DeviceControlService();
        $results = [];

        $clients = Client::with('package')->whereIn('id', $request->client_ids)->get();

        foreach ($clients as $client) {
            try {
                $result = $deviceControl->disableClient($client, 'Bulk disable operation');
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => $result['success'],
                    'message' => $result['message'],
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'total' => count($results),
            'successful' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count(),
        ]);
    }

    public function bulkSync(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $deviceControl = new DeviceControlService();
        $results = [];

        $clients = Client::with('package')->whereIn('id', $request->client_ids)->get();

        foreach ($clients as $client) {
            try {
                $result = $deviceControl->syncClientFromMikrotik($client);
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => $result['success'],
                    'message' => $result['message'],
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'total' => count($results),
            'successful' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count(),
        ]);
    }

    public function bulkDisconnect(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $deviceControl = new DeviceControlService();
        $results = [];

        $clients = Client::with('package')->whereIn('id', $request->client_ids)->get();

        foreach ($clients as $client) {
            try {
                $result = $deviceControl->disconnectClient($client, 'Bulk disconnect operation');
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => $result['success'],
                    'message' => $result['message'],
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'total' => count($results),
            'successful' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count(),
        ]);
    }

    public function bulkProvision(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $provisionService = new ClientAutoProvisionService();
        $results = [];

        $clients = Client::with('package')->whereIn('id', $request->client_ids)->get();

        foreach ($clients as $client) {
            try {
                $result = $provisionService->provisionNewClient($client);
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => $result['success'],
                    'message' => $result['message'],
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'total' => count($results),
            'successful' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count(),
        ]);
    }
}
