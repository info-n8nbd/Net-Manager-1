<?php
/**
 * System Log Controller
 * 
 * Provides admin access to view system logs for debugging
 * SECURITY: Restricted to super-admin and admin roles
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SystemLogController extends Controller
{
    /**
     * Check admin access (super_admin or admin user_type, or Super Admin / Administrator role)
     */
    private function checkAccess()
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Access denied.');
        }

        $allowedUserTypes = ['super_admin', 'admin'];
        $allowedRoles = ['Super Admin', 'Administrator', 'superadmin', 'admin'];

        $hasUserType = in_array($user->user_type, $allowedUserTypes);
        $hasRole = $user->roles->whereIn('name', $allowedRoles)->isNotEmpty();

        if (!$hasUserType && !$hasRole) {
            abort(403, 'Access denied. Admin access required.');
        }
    }

    /**
     * Display system logs (last 500 lines only, sanitized)
     */
    public function index(Request $request)
    {
        $this->checkAccess();
        
        $logFile = storage_path('logs/laravel.log');
        $logs = [];
        $errorCount = 0;
        $warningCount = 0;
        $infoCount = 0;

        if (File::exists($logFile)) {
            $content = File::get($logFile);
            $lines = explode("\n", $content);
            
            // Parse last 500 lines
            $lines = array_slice($lines, -500);
            
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $type = 'info';
                if (strpos($line, '.ERROR:') !== false || strpos($line, 'error') !== false) {
                    $type = 'error';
                    $errorCount++;
                } elseif (strpos($line, '.WARNING:') !== false || strpos($line, 'warning') !== false) {
                    $type = 'warning';
                    $warningCount++;
                } elseif (strpos($line, '.INFO:') !== false) {
                    $infoCount++;
                }
                
                // Parse date if present
                $date = '';
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $date = $matches[1];
                }
                
                $logs[] = [
                    'type' => $type,
                    'date' => $date,
                    'message' => $line,
                ];
            }
            
            // Reverse to show newest first
            $logs = array_reverse($logs);
        }

        $stats = [
            'errors' => $errorCount,
            'warnings' => $warningCount,
            'info' => $infoCount,
            'file_size' => File::exists($logFile) ? $this->formatBytes(File::size($logFile)) : '0 KB',
        ];

        return view('admin.system.logs', compact('logs', 'stats'));
    }

    /**
     * Clear log file
     */
    public function clear(Request $request)
    {
        $this->checkAccess();
        $request->validate(['keep_days' => 'nullable|integer|min:1|max:365']);
        
        $logFile = storage_path('logs/laravel.log');
        
        if (File::exists($logFile)) {
            File::put($logFile, '');
        }
        
        return redirect()->route('admin.system.logs')->with('success', 'Log file cleared successfully');
    }

    /**
     * Download log file
     */
    public function download()
    {
        $this->checkAccess();
        
        $logFile = storage_path('logs/laravel.log');
        
        if (File::exists($logFile)) {
            return response()->download($logFile, 'laravel-' . date('Y-m-d') . '.log');
        }
        
        return redirect()->route('admin.system.logs')->with('error', 'Log file not found');
    }

    /**
     * Test logging - write a test entry
     */
    public function test()
    {
        $this->checkAccess();
        
        \Log::info('System log test - Admin tested logging at ' . now());
        \Log::warning('This is a test warning message');
        
        return redirect()->route('admin.system.logs')->with('success', 'Test log entries written');
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}
