<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\DeviceControlService;
use App\Services\ClientActivityService;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class DeviceControlController extends Controller
{
    protected DeviceControlService $deviceService;

    public function __construct(DeviceControlService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    public function syncClient(Request $request, Client $client)
    {
        $mikrotikResult = $this->deviceService->syncClientFromMikrotik($client);
        $oltResult = $this->deviceService->syncClientFromOlt($client);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $mikrotikResult['success'] || $oltResult['success'],
                'mikrotik' => $mikrotikResult,
                'olt' => $oltResult,
                'client' => $client->fresh(),
            ]);
        }

        $success = $mikrotikResult['success'] || $oltResult['success'];
        $bothNotConfigured = ($mikrotikResult['not_configured'] ?? false) && ($oltResult['not_configured'] ?? false);

        if ($mikrotikResult['success']) {
            $data = $mikrotikResult['data'] ?? [];
            $status = isset($data['name']) ? 'Online' : 'Offline';
            $uptime = $data['uptime'] ?? '';
            $ip = $data['address'] ?? '';
            $message = "Client synced - Status: {$status}";
            if ($uptime) $message .= ", Uptime: {$uptime}";
            if ($ip) $message .= ", IP: {$ip}";
        } elseif ($oltResult['success']) {
            $message = $oltResult['message'] ?? 'OLT sync successful';
        } elseif ($bothNotConfigured) {
            $message = 'No MikroTik or OLT device is assigned to this client.';
        } else {
            $message = $mikrotikResult['message'] ?? ($oltResult['message'] ?? 'Sync failed');
        }

        if ($success) {
            return back()->with('success', $message);
        } elseif ($bothNotConfigured) {
            return back()->with('warning', $message);
        } else {
            return back()->with('error', $message);
        }
    }

    public function disconnectClient(Request $request, Client $client)
    {
        $reason = $request->input('reason', 'Manual disconnect by admin');
        $result = $this->deviceService->disconnectClient($client, $reason);

        if ($result['success']) {
            ClientActivityService::log(
                $client,
                'disconnected',
                $client->connection_status,
                'offline',
                $reason,
                'manual'
            );
            
            $telegram = new TelegramService();
            $telegram->sendClientDisconnected(
                $client->client_id ?? $client->id,
                $reason,
                null,
                'Offline',
                'Manual disconnect',
                $client->name ?? '',
                $client->username ?? ''
            );
        }

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function enableClient(Request $request, Client $client)
    {
        $sendSms = $request->boolean('send_sms', true);
        $result = $this->deviceService->enableClient($client, $sendSms);

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function disableClient(Request $request, Client $client)
    {
        $reason = $request->input('reason', 'Manual disable by admin');
        $sendSms = $request->boolean('send_sms', true);
        $result = $this->deviceService->disableClient($client, $reason, $sendSms);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function rebootOnu(Client $client)
    {
        $result = $this->deviceService->rebootOnu($client);

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function updateSpeed(Request $request, Client $client)
    {
        $request->validate([
            'download_mbps' => 'required|integer|min:1|max:1000',
            'upload_mbps' => 'required|integer|min:1|max:1000',
        ]);

        $result = $this->deviceService->updateClientSpeed(
            $client,
            $request->input('download_mbps'),
            $request->input('upload_mbps')
        );

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
