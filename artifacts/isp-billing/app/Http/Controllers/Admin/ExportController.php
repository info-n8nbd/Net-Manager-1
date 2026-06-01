<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExportJob;
use App\Jobs\ExportDataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function index()
    {
        $exports = ExportJob::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.exports.index', compact('exports'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'type' => 'required|in:clients,bills,payments,due_clients,collection_report',
            'format' => 'required|in:xlsx,csv',
            'filters' => 'nullable|array',
        ]);

        $export = ExportJob::create([
            'name' => ucfirst(str_replace('_', ' ', $request->type)) . ' Export',
            'data_types' => [$request->type],
            'file_format' => $request->format,
            'export_options' => $request->filters ?? [],
            'status' => 'pending',
            'user_id' => auth()->id(),
        ]);

        if (config('queue.default') === 'sync') {
            dispatch_sync(new ExportDataJob($export->id));
        } else {
            ExportDataJob::dispatch($export->id);
        }

        return response()->json([
            'success' => true,
            'export_id' => $export->id,
            'message' => 'Export started. You will be notified when ready.',
        ]);
    }

    public function status(ExportJob $export)
    {
        $user = auth()->user();
        if ($export->user_id !== auth()->id() && (!$user || !$user->hasRole('Super Admin'))) {
            abort(403);
        }

        $summary = $export->export_summary ?? [];
        $totalRecords = is_array($summary) ? array_sum($summary) : 0;
        $progress = match($export->status) {
            'completed' => 100,
            'processing' => 50,
            'failed' => 0,
            default => 0,
        };

        return response()->json([
            'id' => $export->id,
            'status' => $export->status,
            'progress' => $progress,
            'file_ready' => $export->status === 'completed' && $export->file_path,
            'error' => $export->error_message,
            'download_url' => $export->status === 'completed' && $export->file_path 
                ? route('admin.exports.download', $export) 
                : null,
        ]);
    }

    public function download(ExportJob $export)
    {
        $user = auth()->user();
        if ($export->user_id !== auth()->id() && (!$user || !$user->hasRole('Super Admin'))) {
            abort(403);
        }

        if (!$export->file_path || !Storage::exists($export->file_path)) {
            return back()->with('error', 'File not found or has expired.');
        }

        $filename = $export->file_name ?? ($export->name . '.' . $export->file_format);
        return Storage::download($export->file_path, $filename);
    }

    public function quickExport(Request $request, string $type, string $format)
    {
        $validTypes = ['clients', 'bills', 'payments', 'due_clients', 'collection_report'];
        $validFormats = ['xlsx', 'csv'];

        if (!in_array($type, $validTypes) || !in_array($format, $validFormats)) {
            return back()->with('error', 'Invalid export type or format.');
        }

        $allowedFilters = ['zone_id', 'area_id', 'package_id', 'status', 'reseller_id',
            'date_from', 'date_to', 'start_date', 'end_date', 'month', 'year',
            'payment_method', 'collected_by'];
        $export = ExportJob::create([
            'name' => ucfirst(str_replace('_', ' ', $type)) . ' Export',
            'data_types' => [$type],
            'file_format' => $format,
            'export_options' => $request->only($allowedFilters),
            'status' => 'pending',
            'user_id' => auth()->id(),
        ]);

        dispatch_sync(new ExportDataJob($export->id));
        
        $export->refresh();

        if ($export->status === 'completed' && $export->file_path) {
            $filename = $export->file_name ?? ($export->name . '.' . $export->file_format);
            return Storage::download($export->file_path, $filename);
        }

        return back()->with('error', $export->error_message ?? 'Export failed.');
    }
}
