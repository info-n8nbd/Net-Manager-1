<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('device_operation_logs')
            ->leftJoin('clients', 'device_operation_logs.client_id', '=', 'clients.id')
            ->leftJoin('users', 'device_operation_logs.performed_by', '=', 'users.id')
            ->select('device_operation_logs.*', 'clients.name as client_name', 'clients.client_id as client_code', 'users.name as user_name');

        if ($request->device_type) {
            $query->where('device_operation_logs.device_type', $request->device_type);
        }
        if ($request->operation) {
            $query->where('device_operation_logs.operation', $request->operation);
        }
        if ($request->result) {
            $query->where('device_operation_logs.result', $request->result);
        }
        if ($request->date_from) {
            $query->whereDate('device_operation_logs.created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('device_operation_logs.created_at', '<=', $request->date_to);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('clients.name', 'ilike', "%{$request->search}%")
                  ->orWhere('clients.client_id', 'ilike', "%{$request->search}%")
                  ->orWhere('device_operation_logs.result_message', 'ilike', "%{$request->search}%");
            });
        }

        $logs = $query->orderByDesc('device_operation_logs.created_at')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        $stats = [
            'total' => DB::table('device_operation_logs')->count(),
            'success' => DB::table('device_operation_logs')->where('result', 'success')->count(),
            'failed' => DB::table('device_operation_logs')->where('result', 'failed')->count(),
            'today' => DB::table('device_operation_logs')->whereDate('created_at', today())->count(),
        ];

        return view('admin.network.device-logs', compact('logs', 'stats'));
    }
}
