<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpeedTestLog;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpeedTestController extends Controller
{
    public function download(Request $request)
    {
        $size = max(1, min((int) $request->get('size', 102400), 1024000));
        
        $data = str_repeat('0', $size);
        
        return response($data)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Length', strlen($data))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function upload(Request $request)
    {
        $size = 0;
        
        if ($request->hasFile('data')) {
            $size = $request->file('data')->getSize();
        }
        
        return response()->json([
            'success' => true,
            'bytes_received' => $size,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function resolveClientId(): ?int
    {
        try {
            if (Auth::guard('client')->check()) {
                return Auth::guard('client')->id();
            }
        } catch (\Exception $e) {}

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->user_type === 'client') {
                $client = Client::where('user_id', $user->id)->first();
                return $client?->id;
            }
        }
        return null;
    }

    public function save(Request $request)
    {
        $request->validate([
            'download_speed' => 'required|numeric|min:0|max:10000',
            'upload_speed' => 'required|numeric|min:0|max:10000',
            'ping' => 'required|integer|min:0|max:5000',
        ]);

        $clientId = $this->resolveClientId();

        if (!$clientId) {
            return response()->json([
                'success' => false,
                'message' => 'Client not authenticated',
            ], 401);
        }

        $log = SpeedTestLog::create([
            'client_id' => $clientId,
            'download_speed' => $request->download_speed,
            'upload_speed' => $request->upload_speed,
            'ping_ms' => $request->ping,
        ]);

        return response()->json([
            'success' => true,
            'id' => $log->id,
            'message' => 'Speed test results saved',
        ]);
    }

    public function ping()
    {
        return response('pong', 200)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function history(Request $request)
    {
        $clientId = $this->resolveClientId();

        if (!$clientId) {
            return response()->json([
                'success' => false,
                'message' => 'Client not authenticated',
            ], 401);
        }

        $tests = SpeedTestLog::where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->take(20)
            ->get()
            ->map(function ($test) {
                return [
                    'id' => $test->id,
                    'download' => round($test->download_speed, 1),
                    'upload' => round($test->upload_speed, 1),
                    'ping' => $test->ping_ms,
                    'date' => $test->created_at->format('d-m-y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'tests' => $tests,
        ]);
    }
}
