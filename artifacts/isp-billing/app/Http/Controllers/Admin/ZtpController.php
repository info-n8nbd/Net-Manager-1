<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ZeroTouchProvisioningJob;
use App\Models\Client;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZtpController extends Controller
{
    public function provision(Request $request, Client $client)
    {
        try {
            $client->update([
                'provision_status' => 'pending',
                'provision_log' => null,
                'provision_attempted_at' => null,
            ]);
            ZeroTouchProvisioningJob::dispatch($client->id, 'manual_trigger')->onQueue('default');
            Log::info('[ZTP] Manual trigger for client #' . $client->id . ' (' . $client->name . ') by user #' . auth()->id());
            return back()->with('success', 'ZTP started for ' . $client->name . '. Refresh in a few seconds.');
        } catch (\Exception $e) {
            Log::error('[ZTP] Manual trigger failed: ' . $e->getMessage());
            return back()->with('error', 'ZTP trigger failed: ' . $e->getMessage());
        }
    }

    public function settings()
    {
        $ztpEnabled  = SystemSetting::get('ztp_enabled', '1') === '1';
        $ztpMikrotik = SystemSetting::get('ztp_mikrotik_enabled', '1') === '1';
        $ztpOnu      = SystemSetting::get('ztp_onu_enabled', '1') === '1';
        return view('admin.settings.ztp', compact('ztpEnabled', 'ztpMikrotik', 'ztpOnu'));
    }

    public function saveSettings(Request $request)
    {
        SystemSetting::set('ztp_enabled',          $request->boolean('ztp_enabled') ? '1' : '0', 'ztp');
        SystemSetting::set('ztp_mikrotik_enabled', $request->boolean('ztp_mikrotik_enabled') ? '1' : '0', 'ztp');
        SystemSetting::set('ztp_onu_enabled',      $request->boolean('ztp_onu_enabled') ? '1' : '0', 'ztp');
        return back()->with('success', 'ZTP settings saved successfully.');
    }

    public function logs(Request $request)
    {
        $query = Client::whereNotNull('provision_status')
            ->whereNotNull('provision_attempted_at')
            ->orderBy('provision_attempted_at', 'desc');

        if ($request->filled('status')) {
            $query->where('provision_status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('client_id', 'ilike', '%' . $request->search . '%');
            });
        }

        $logs = $query->paginate(25)->withQueryString();
        return view('admin.settings.ztp-logs', compact('logs'));
    }
}
