<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductSerial;
use App\Models\Instrument;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SerialSearchController extends Controller
{
    public function index()
    {
        return view('admin.inventory.serial-search');
    }

    public function search(Request $request)
    {
        $request->validate([
            'serial' => 'required|string'
        ]);

        $serial = trim($request->serial);
        $data = null;
        $source = null;

        // Search product_serials first
        $productSerial = ProductSerial::with(['product', 'client'])
            ->where('serial_number', $serial)
            ->orWhere('barcode', $serial)
            ->orWhere('mac_address', $serial)
            ->first();

        if ($productSerial) {
            $data = $productSerial;
            $source = 'product_serial';
        } else {
            // Search instruments table
            $instrument = Instrument::where('serial_number', $serial)->first();
            if ($instrument) {
                // Normalize instrument data to match expected view structure
                $statusMap = ['available' => 'stock', 'in_use' => 'assigned', 'damaged' => 'damaged', 'returned' => 'returned'];
                $instrument->status = $statusMap[$instrument->status] ?? $instrument->status;
                // Add virtual fields expected by view
                $instrument->serial_number = $instrument->serial_number;
                $instrument->barcode = null;
                $instrument->mac_address = null;
                $instrument->warranty_start = null;
                $instrument->warranty_end = $instrument->warranty_expiry ?? null;
                // Load client relation if assigned
                if ($instrument->client_id) {
                    $instrument->client = Client::find($instrument->client_id);
                } else {
                    $instrument->client = null;
                }
                // Fake product relation
                $instrument->product = (object)['name' => ($instrument->name ?? 'N/A') . ($instrument->model ? ' (' . $instrument->model . ')' : '')];
                $data = $instrument;
                $source = 'instrument';
            }
        }

        if ($data) {
            if ($source === 'product_serial') {
                $history = DB::table('stock_movements')
                    ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
                    ->select('stock_movements.*', 'users.name as created_by_name')
                    ->where('stock_movements.reference', 'serial:' . $data->id)
                    ->orWhere('stock_movements.reference', 'like', '%serial_id:' . $data->id . '%')
                    ->orderBy('stock_movements.created_at', 'desc')
                    ->get();
            } else {
                // Get instrument out logs as history (instrument_ids stored as JSON array)
                $history = DB::table('instrument_out_logs')
                    ->leftJoin('users', 'instrument_out_logs.created_by', '=', 'users.id')
                    ->select('instrument_out_logs.*', 'users.name as created_by_name')
                    ->when((auth()->check() && !auth()->user()->isSuperAdmin()) ? auth()->user()->company_id : null, fn($q, $id) => $q->where('instrument_out_logs.company_id', $id))
                    ->whereRaw("instrument_out_logs.instrument_ids::text LIKE ?", ['%' . $data->id . '%'])
                    ->orderBy('instrument_out_logs.created_at', 'desc')
                    ->get()
                    ->filter(function($log) use ($data) {
                        // Double-check: decode JSON and verify exact id match
                        $ids = json_decode($log->instrument_ids, true) ?? [];
                        return in_array((string)$data->id, array_map('strval', $ids)) || in_array((int)$data->id, $ids);
                    })
                    ->map(function($log) {
                        $log->action = $log->reason ?? 'issued';
                        $log->details = ($log->issued_to ? 'Issued to: ' . $log->issued_to : '') . ($log->description ? ' | ' . $log->description : '');
                        return $log;
                    });
            }
            $warrantyStatus = $this->getWarrantyStatusFromDates($data->warranty_start ?? null, $data->warranty_end ?? null);
        } else {
            $history = collect();
            $warrantyStatus = null;
        }

        $searched = true;
        return view('admin.inventory.serial-search', compact('data', 'history', 'warrantyStatus', 'searched', 'source'));
    }

    public function show($id)
    {
        $serial = ProductSerial::with(['product', 'client', 'purchase'])->findOrFail($id);

        $history = DB::table('stock_movements')
            ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
            ->select('stock_movements.*', 'users.name as created_by_name')
            ->where('stock_movements.reference', 'serial:' . $serial->id)
            ->orWhere('stock_movements.reference', 'like', '%serial_id:' . $serial->id . '%')
            ->orderBy('stock_movements.created_at', 'desc')
            ->get();

        $assignmentHistory = DB::table('stock_movements')
            ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
            ->select('stock_movements.*', 'users.name as created_by_name')
            ->where('stock_movements.product_id', $serial->product_id)
            ->where('stock_movements.reference', 'like', '%serial_id:' . $serial->id . '%')
            ->where('stock_movements.description', 'like', '%assign%')
            ->orderBy('stock_movements.created_at', 'desc')
            ->get();

        $returnHistory = DB::table('stock_movements')
            ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
            ->select('stock_movements.*', 'users.name as created_by_name')
            ->where('stock_movements.product_id', $serial->product_id)
            ->where('stock_movements.reference', 'like', '%serial_id:' . $serial->id . '%')
            ->where('stock_movements.description', 'like', '%return%')
            ->orderBy('stock_movements.created_at', 'desc')
            ->get();

        $damageHistory = DB::table('stock_movements')
            ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
            ->select('stock_movements.*', 'users.name as created_by_name')
            ->where('stock_movements.product_id', $serial->product_id)
            ->where('stock_movements.reference', 'like', '%serial_id:' . $serial->id . '%')
            ->where('stock_movements.description', 'like', '%damage%')
            ->orderBy('stock_movements.created_at', 'desc')
            ->get();

        $warrantyStatus = $this->getWarrantyStatus($serial);

        return view('admin.inventory.serial-detail', compact(
            'serial',
            'history',
            'assignmentHistory',
            'returnHistory',
            'damageHistory',
            'warrantyStatus'
        ));
    }

    private function getWarrantyStatusFromDates($warrantyStart, $warrantyEnd): array
    {
        if (!$warrantyEnd) {
            return ['status' => 'unknown', 'label' => 'No Warranty Info', 'color' => 'secondary', 'days_remaining' => null, 'expiry_date' => null];
        }
        $now = Carbon::now();
        $end = Carbon::parse($warrantyEnd);
        $days = (int) $now->diffInDays($end, false);
        if ($days < 0) return ['status' => 'expired', 'label' => 'Warranty Expired', 'color' => 'danger', 'days_remaining' => 0, 'expiry_date' => $end->format('Y-m-d')];
        if ($days <= 30) return ['status' => 'expiring_soon', 'label' => 'Expiring Soon (' . $days . ' days)', 'color' => 'warning', 'days_remaining' => $days, 'expiry_date' => $end->format('Y-m-d')];
        return ['status' => 'active', 'label' => 'Under Warranty (' . $days . ' days left)', 'color' => 'success', 'days_remaining' => $days, 'expiry_date' => $end->format('Y-m-d')];
    }

    private function getWarrantyStatus(ProductSerial $serial): array
    {
        if (!$serial->warranty_end) {
            return [
                'status' => 'unknown',
                'label' => 'No Warranty Info',
                'color' => 'secondary',
                'days_remaining' => null,
                'expiry_date' => null,
            ];
        }

        $now = Carbon::now();
        $warrantyEnd = Carbon::parse($serial->warranty_end);
        $daysRemaining = (int) $now->diffInDays($warrantyEnd, false);

        if ($daysRemaining < 0) {
            return [
                'status' => 'expired',
                'label' => 'Warranty Expired',
                'color' => 'danger',
                'days_remaining' => 0,
                'expiry_date' => $warrantyEnd->format('Y-m-d'),
            ];
        }

        if ($daysRemaining <= 30) {
            return [
                'status' => 'expiring_soon',
                'label' => 'Expiring Soon (' . $daysRemaining . ' days)',
                'color' => 'warning',
                'days_remaining' => $daysRemaining,
                'expiry_date' => $warrantyEnd->format('Y-m-d'),
            ];
        }

        return [
            'status' => 'active',
            'label' => 'Under Warranty (' . $daysRemaining . ' days left)',
            'color' => 'success',
            'days_remaining' => $daysRemaining,
            'expiry_date' => $warrantyEnd->format('Y-m-d'),
        ];
    }
}
