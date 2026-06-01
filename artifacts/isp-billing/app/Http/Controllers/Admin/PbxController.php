<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoiceCallLog;
use App\Models\VoiceCallSetting;
use Illuminate\Http\Request;

class PbxController extends Controller
{
    /**
     * Return SIP/WebRTC credentials for the browser softphone (JsSIP).
     * Only authenticated admins can fetch this.
     */
    public function credentials(): \Illuminate\Http\JsonResponse
    {
        $settings = VoiceCallSetting::first();

        $host    = parse_url(config('app.url'), PHP_URL_HOST) ?? 'softynex.me';
        $wsUrl   = $settings?->pbx_ws_url   ?: 'wss://' . $host . '/pbx/ws';
        $ext     = $settings?->pbx_extension ?: 'softynex_admin';
        $pass    = $settings?->pbx_password  ?: 'PbxSoft@2024!';
        $active  = $settings?->provider === 'pbx' && $settings?->is_active;

        return response()->json([
            'enabled'    => true,        // always show the softphone widget
            'ws_url'     => $wsUrl,
            'extension'  => $ext,
            'password'   => $pass,
            'sip_uri'    => 'sip:' . $ext . '@' . $host,
            'display'    => 'SOFTYNEX',
        ]);
    }

    /**
     * Log a call made directly via the browser softphone (JsSIP).
     */
    public function logCall(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'phone'     => 'required|string',
            'status'    => 'required|string',
            'duration'  => 'nullable|integer',
            'client_id' => 'nullable|integer',
        ]);

        $statusMap = [
            'confirmed'   => 'answered',
            'ended'       => 'completed',
            'failed'      => 'failed',
            'terminated'  => 'completed',
            'rejected'    => 'failed',
            'busy'        => 'busy',
            'no_answer'   => 'no_answer',
        ];

        $mapped = $statusMap[$request->status] ?? 'completed';

        $log = VoiceCallLog::create([
            'phone'             => $request->phone,
            'client_id'         => $request->client_id ?: null,
            'call_type'         => 'pbx_direct',
            'message_text'      => '[WebRTC softphone call]',
            'provider'          => 'pbx',
            'provider_call_id'  => 'webrtc_' . uniqid(),
            'status'            => $mapped,
            'duration_seconds'  => (int) ($request->duration ?? 0),
            'cost'              => 0,
            'provider_response' => ['source' => 'jssip_webrtc'],
        ]);

        return response()->json(['ok' => true, 'id' => $log->id]);
    }
}
