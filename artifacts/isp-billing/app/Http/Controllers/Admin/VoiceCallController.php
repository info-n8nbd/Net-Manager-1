<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoiceCallLog;
use App\Models\VoiceCallSetting;
use App\Services\VoiceCallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VoiceCallController extends Controller
{
    protected VoiceCallService $voiceCallService;

    public function __construct(VoiceCallService $voiceCallService)
    {
        $this->voiceCallService = $voiceCallService;
    }

    public function index(Request $request)
    {
        $logs      = $this->voiceCallService->getCallHistory($request->only(['client_id', 'call_type', 'status', 'date_from', 'date_to']));
        $settings  = $this->voiceCallService->getSettings();
        $stats     = $this->getStats();
        $chartData = $this->getChartData();

        return view('admin.voice-calls.index', compact('logs', 'settings', 'stats', 'chartData'));
    }

    protected function getStats(): array
    {
        $total     = VoiceCallLog::count();
        $answered  = VoiceCallLog::where('status', 'completed')->count();
        $noAnswer  = VoiceCallLog::whereIn('status', ['no_answer', 'busy'])->count();
        $failed    = VoiceCallLog::where('status', 'failed')->count();
        $initiated = VoiceCallLog::whereIn('status', ['initiated', 'queued', 'ringing', 'answered'])->count();
        $totalCost = VoiceCallLog::sum('cost');
        $todayTotal = VoiceCallLog::whereDate('created_at', today())->count();

        return compact('total', 'answered', 'noAnswer', 'failed', 'initiated', 'totalCost', 'todayTotal');
    }

    protected function getChartData(): array
    {
        $rows = VoiceCallLog::select(
                DB::raw("DATE(created_at) as day"),
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as answered"),
                DB::raw("SUM(CASE WHEN status IN ('no_answer','busy') THEN 1 ELSE 0 END) as no_answer"),
                DB::raw("SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed")
            )
            ->where('created_at', '>=', now()->subDays(13))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $labels   = [];
        $totals   = [];
        $answered = [];
        $noAnswer = [];
        $failed   = [];

        foreach ($rows as $row) {
            $labels[]   = \Carbon\Carbon::parse($row->day)->format('d M');
            $totals[]   = (int) $row->total;
            $answered[] = (int) $row->answered;
            $noAnswer[] = (int) $row->no_answer;
            $failed[]   = (int) $row->failed;
        }

        return compact('labels', 'totals', 'answered', 'noAnswer', 'failed');
    }

    public function settings()
    {
        $settings = VoiceCallSetting::first() ?? new VoiceCallSetting();
        return view('admin.voice-calls.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'provider'    => 'required|string',
            'from_number' => 'nullable|string',
        ]);

        VoiceCallSetting::updateOrCreate(
            ['id' => 1],
            [
                'provider'                  => $request->provider,
                'credentials'               => $request->credentials ? json_decode($request->credentials, true) : [],
                'from_number'               => $request->from_number,
                'is_active'                 => $request->boolean('is_active'),
                'auto_bill_reminder'        => $request->boolean('auto_bill_reminder'),
                'auto_expiry_warning'       => $request->boolean('auto_expiry_warning'),
                'auto_payment_confirmation' => $request->boolean('auto_payment_confirmation'),
                'reminder_days_before'      => $request->reminder_days_before ?? 3,
            ]
        );

        return back()->with('success', 'Voice call settings updated');
    }

    public function makeCall(Request $request)
    {
        $request->validate([
            'phone'     => 'required|string',
            'call_type' => 'required|string',
        ]);

        $audioUrl = null;
        $message  = '';

        if ($request->hasFile('audio_file')) {
            $file     = $request->file('audio_file');
            $filename = 'voice-audio/' . uniqid('call_') . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->put($filename, file_get_contents($file->getRealPath()));
            $audioUrl = asset('storage/' . $filename);
        } else {
            $request->validate(['message' => 'required|string|max:1000']);
            $message = $request->message;
        }

        $log = $this->voiceCallService->makeCall(
            phone:    $request->phone,
            message:  $message,
            callType: $request->call_type,
            clientId: $request->client_id ?: null,
            language: $request->language ?? 'en-US',
            audioUrl: $audioUrl,
        );

        if ($request->ajax()) {
            return response()->json([
                'status'  => $log->status,
                'call_id' => $log->provider_call_id,
                'message' => 'Call status: ' . $log->status,
            ]);
        }

        $msg = $log->status === 'initiated'
            ? 'Call initiated successfully to ' . $request->phone
            : 'Call status: ' . $log->status . (isset($log->provider_response['error']) ? ' — ' . $log->provider_response['error'] : '');

        return back()->with($log->status === 'initiated' ? 'success' : 'error', $msg);
    }

    public function statusCallback(Request $request)
    {
        $callSid    = $request->input('CallSid');
        $callStatus = $request->input('CallStatus');
        $duration   = $request->input('CallDuration', 0);

        $statusMap = [
            'completed'   => 'completed',
            'failed'      => 'failed',
            'busy'        => 'busy',
            'no-answer'   => 'no_answer',
            'canceled'    => 'failed',
            'in-progress' => 'answered',
            'ringing'     => 'ringing',
            'initiated'   => 'initiated',
            'queued'      => 'queued',
        ];

        $mapped = $statusMap[$callStatus] ?? 'initiated';

        if ($callSid) {
            VoiceCallLog::where('provider_call_id', $callSid)->update([
                'status'           => $mapped,
                'duration_seconds' => (int) $duration,
                'updated_at'       => now(),
            ]);
        }

        return response('<Response></Response>', 200)->header('Content-Type', 'text/xml');
    }
    public function updatePbxSettings(Request $request)
    {
        $request->validate([
            'pbx_ws_url'    => 'nullable|string',
            'pbx_extension' => 'nullable|string',
            'pbx_password'  => 'nullable|string',
            'pbx_trunk'     => 'nullable|string',
        ]);

        VoiceCallSetting::updateOrCreate(
            ['id' => 1],
            [
                'pbx_ws_url'    => $request->pbx_ws_url ?: null,
                'pbx_extension' => $request->pbx_extension ?: null,
                'pbx_password'  => $request->pbx_password ?: null,
                'pbx_trunk'     => $request->pbx_trunk ? json_decode($request->pbx_trunk, true) : null,
            ]
        );

        return back()->with('success', 'PBX settings saved successfully!');
    }

}