#!/bin/bash
# ============================================================
#  Smart Online ISP — 3 Feature Install Script
#  VPS Path: /var/www/smart-isp/isp-app
#  Features:
#   1. Daily Bill Collection
#   2. Employee Selfie Attendance
#   3. Client Delete Warning + Equipment Return
# ============================================================

PROJECT="/var/www/smart-isp/isp-app"
cd "$PROJECT" || { echo "ERROR: Project folder not found at $PROJECT"; exit 1; }

echo "======================================================"
echo "  Installing 3 new features to Smart Online ISP..."
echo "======================================================"

# ── 1. MIGRATION: daily_collections ───────────────────────
cat > database/migrations/2025_01_20_000001_create_daily_collections_table.php << 'PHPEOF'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id');
            $table->decimal('amount', 12, 2);
            $table->date('collection_date');
            $table->string('payment_method', 50)->default('Cash');
            $table->string('receipt_no', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('company_id')->default(1);
            $table->timestamps();
            $table->index(['user_id', 'collection_date']);
            $table->index(['client_id']);
            $table->index(['status']);
            $table->index(['collection_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_collections');
    }
};
PHPEOF

# ── 2. MIGRATION: employee_selfie_attendances ──────────────
cat > database/migrations/2025_01_20_000002_create_employee_selfie_attendances_table.php << 'PHPEOF'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_selfie_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('attendance_date');
            $table->time('check_in_time');
            $table->string('selfie_path', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_address', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info', 255)->nullable();
            $table->string('type', 20)->default('check_in');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('company_id')->default(1);
            $table->timestamps();
            $table->index(['user_id', 'attendance_date']);
            $table->index(['attendance_date']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_selfie_attendances');
    }
};
PHPEOF

# ── 3. MIGRATION: returned_equipments ─────────────────────
cat > database/migrations/2025_01_20_000003_create_returned_equipments_table.php << 'PHPEOF'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returned_equipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_client_id', 50)->nullable();
            $table->string('equipment_type', 50);
            $table->string('equipment_name');
            $table->string('serial_number', 100)->nullable();
            $table->string('mac_address', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('pon_port', 50)->nullable();
            $table->string('onu_index', 50)->nullable();
            $table->string('condition', 20)->default('unknown');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->date('return_date');
            $table->unsignedBigInteger('company_id')->default(1);
            $table->timestamps();
            $table->index(['client_id']);
            $table->index(['equipment_type']);
            $table->index(['return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returned_equipments');
    }
};
PHPEOF

echo "✓ Migrations created"

# ── 4. MODEL: DailyCollection ──────────────────────────────
cat > app/Models/DailyCollection.php << 'PHPEOF'
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class DailyCollection extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'user_id', 'client_id', 'payment_id', 'amount', 'collection_date',
        'payment_method', 'receipt_no', 'remarks', 'status',
        'approved_by', 'approved_at', 'company_id'
    ];

    protected $casts = [
        'collection_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
}
PHPEOF

# ── 5. MODEL: EmployeeSelfieAttendance ─────────────────────
cat > app/Models/EmployeeSelfieAttendance.php << 'PHPEOF'
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class EmployeeSelfieAttendance extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'user_id', 'employee_id', 'attendance_date', 'check_in_time',
        'selfie_path', 'latitude', 'longitude', 'location_address',
        'ip_address', 'device_info', 'type', 'remarks', 'company_id'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function employee() { return $this->belongsTo(Employee::class); }

    public function getSelfieUrlAttribute()
    {
        return $this->selfie_path ? asset('storage/' . $this->selfie_path) : null;
    }

    public function getGoogleMapsUrlAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        return null;
    }
}
PHPEOF

# ── 6. MODEL: ReturnedEquipment ────────────────────────────
cat > app/Models/ReturnedEquipment.php << 'PHPEOF'
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ReturnedEquipment extends Model
{
    use HasFactory, TenantAware;

    protected $table = 'returned_equipments';

    protected $fillable = [
        'client_id', 'client_name', 'client_client_id', 'equipment_type',
        'equipment_name', 'serial_number', 'mac_address', 'model', 'brand',
        'pon_port', 'onu_index', 'condition', 'remarks',
        'returned_by', 'received_by', 'return_date', 'company_id'
    ];

    protected $casts = ['return_date' => 'date'];

    public function client() { return $this->belongsTo(Client::class); }
    public function returnedByUser() { return $this->belongsTo(User::class, 'returned_by'); }
    public function receivedByUser() { return $this->belongsTo(User::class, 'received_by'); }
}
PHPEOF

echo "✓ Models created"

# ── 7. CONTROLLER: DailyCollectionController ──────────────
cat > app/Http/Controllers/Admin/DailyCollectionController.php << 'PHPEOF'
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyCollection;
use App\Models\Client;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyCollectionController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date ?? today()->format('Y-m-d');
        $userId = $request->user_id;
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        $query = DailyCollection::with(['user', 'client', 'payment'])
            ->whereDate('collection_date', $date);

        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $collections = $query->orderBy('created_at', 'desc')->get();
        $totalAmount = $collections->sum('amount');
        $totalCount = $collections->count();
        $approvedAmount = $collections->where('status', 'approved')->sum('amount');
        $pendingAmount = $collections->where('status', 'pending')->sum('amount');

        $collectors = $isSuperAdmin
            ? User::whereHas('dailyCollections')->orderBy('name')->get()
            : collect();

        $dailySummary = collect();
        if ($isSuperAdmin) {
            $dailySummary = DailyCollection::with('user')
                ->whereDate('collection_date', $date)
                ->select('user_id', DB::raw('COUNT(*) as total_bills'), DB::raw('SUM(amount) as total_amount'))
                ->groupBy('user_id')
                ->get();
        }

        return view('admin.billing.daily-collections', compact(
            'collections', 'date', 'totalAmount', 'totalCount',
            'approvedAmount', 'pendingAmount', 'collectors', 'dailySummary', 'isSuperAdmin'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:1',
            'collection_date' => 'required|date',
            'payment_method' => 'required|string',
            'remarks' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $client = Client::findOrFail($request->client_id);

        DailyCollection::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'amount' => $request->amount,
            'collection_date' => $request->collection_date,
            'payment_method' => $request->payment_method,
            'receipt_no' => 'COL-' . now()->format('YmdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT),
            'remarks' => $request->remarks,
            'status' => 'pending',
            'company_id' => $user->company_id ?? 1,
        ]);

        ActivityLog::log('create', 'daily_collections', "Bill collected ৳{$request->amount} from {$client->name} ({$client->client_id})");
        return back()->with('success', "Collection of ৳{$request->amount} from {$client->name} recorded successfully.");
    }

    public function approve(Request $request, DailyCollection $collection)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin')) abort(403);
        $collection->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);
        ActivityLog::log('update', 'daily_collections', "Approved collection #{$collection->id} ৳{$collection->amount}");
        return back()->with('success', 'Collection approved successfully.');
    }

    public function reject(Request $request, DailyCollection $collection)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin')) abort(403);
        $collection->update(['status' => 'rejected', 'approved_by' => $user->id, 'approved_at' => now()]);
        return back()->with('success', 'Collection rejected.');
    }

    public function report(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? today()->format('Y-m-d');
        $userId = $request->user_id;
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        $query = DailyCollection::with(['user', 'client'])
            ->whereBetween('collection_date', [$startDate, $endDate]);

        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $collections = $query->orderBy('collection_date', 'desc')->get();

        $summary = DailyCollection::with('user')
            ->whereBetween('collection_date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($q) => $q->where('user_id', $user->id))
            ->when($userId && $isSuperAdmin, fn($q) => $q->where('user_id', $userId))
            ->select('user_id', DB::raw('COUNT(*) as total_bills'), DB::raw('SUM(amount) as total_amount'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount"))
            ->groupBy('user_id')
            ->get();

        $collectors = $isSuperAdmin
            ? User::whereHas('dailyCollections')->orderBy('name')->get()
            : collect();

        return view('admin.billing.collection-report', compact(
            'collections', 'summary', 'startDate', 'endDate', 'collectors', 'isSuperAdmin'
        ));
    }

    public function searchClients(Request $request)
    {
        $search = $request->q;
        $clients = Client::where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('client_id', 'ILIKE', "%{$search}%")
              ->orWhere('phone', 'ILIKE', "%{$search}%");
        })->with('package')->limit(20)->get()
          ->map(fn($c) => [
              'id' => $c->id,
              'text' => "{$c->client_id} - {$c->name} ({$c->phone})",
              'due' => $c->due_amount ?? 0,
              'package' => $c->package->name ?? 'N/A',
          ]);

        return response()->json(['results' => $clients]);
    }
}
PHPEOF

# ── 8. CONTROLLER: SelfieAttendanceController ─────────────
cat > app/Http/Controllers/Admin/SelfieAttendanceController.php << 'PHPEOF'
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSelfieAttendance;
use App\Models\Employee;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SelfieAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date ?? today()->format('Y-m-d');
        $userId = $request->user_id;
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        $query = EmployeeSelfieAttendance::with(['user', 'employee'])
            ->whereDate('attendance_date', $date);

        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->orderBy('check_in_time', 'asc')->get();

        $todaySummary = [
            'total_checkins' => $attendances->where('type', 'check_in')->count(),
            'total_checkouts' => $attendances->where('type', 'check_out')->count(),
            'unique_users' => $attendances->unique('user_id')->count(),
        ];

        $hasCheckedInToday = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', 'check_in')->exists();

        $hasCheckedOutToday = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', 'check_out')->exists();

        $employees = $isSuperAdmin
            ? Employee::where('status', 'active')->with('user')->orderBy('name')->get()
            : collect();

        return view('admin.hrm.selfie-attendance', compact(
            'attendances', 'date', 'todaySummary', 'hasCheckedInToday',
            'hasCheckedOutToday', 'employees', 'isSuperAdmin'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'selfie' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'type' => 'required|in:check_in,check_out',
            'location_address' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        $alreadyExists = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', $request->type)->exists();

        if ($alreadyExists) {
            $typeLabel = $request->type === 'check_in' ? 'Check In' : 'Check Out';
            return response()->json(['error' => "You have already done {$typeLabel} today."], 422);
        }

        $imageData = $request->selfie;
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $extension = $matches[1];
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        } else {
            $extension = 'jpg';
        }

        $decodedImage = base64_decode($imageData);
        if (!$decodedImage) {
            return response()->json(['error' => 'Invalid selfie image data.'], 422);
        }

        $fileName = 'selfie_' . $user->id . '_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'attendances/selfies/' . now()->format('Y/m');
        Storage::disk('public')->makeDirectory($path);
        Storage::disk('public')->put($path . '/' . $fileName, $decodedImage);

        $employee = Employee::where('user_id', $user->id)->first();

        $attendance = EmployeeSelfieAttendance::create([
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'attendance_date' => today(),
            'check_in_time' => now()->format('H:i:s'),
            'selfie_path' => $path . '/' . $fileName,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_address' => $request->location_address ? strip_tags($request->location_address) : null,
            'ip_address' => $request->ip(),
            'device_info' => substr($request->userAgent(), 0, 255),
            'type' => $request->type,
            'remarks' => $request->remarks,
            'company_id' => $user->company_id ?? 1,
        ]);

        $typeLabel = $request->type === 'check_in' ? 'Check In' : 'Check Out';
        ActivityLog::log('create', 'selfie_attendance', "{$typeLabel} recorded for {$user->name} at " . now()->format('h:i A'));

        return response()->json([
            'success' => true,
            'message' => "{$typeLabel} recorded successfully at " . now()->format('h:i:s A'),
            'attendance' => $attendance,
        ]);
    }

    public function report(Request $request)
    {
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
        $userId = $request->user_id;
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        $query = EmployeeSelfieAttendance::with(['user', 'employee'])
            ->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year);

        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->orderBy('check_in_time', 'asc')->get();
        $grouped = $attendances->groupBy(fn($a) => $a->attendance_date->format('Y-m-d'));

        $employees = $isSuperAdmin
            ? Employee::where('status', 'active')->with('user')->orderBy('name')->get()
            : collect();

        $monthlySummary = collect();
        if ($isSuperAdmin) {
            $monthlySummary = EmployeeSelfieAttendance::with('user')
                ->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)
                ->where('type', 'check_in')
                ->select('user_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_days'))
                ->groupBy('user_id')->get();
        }

        return view('admin.hrm.selfie-attendance-report', compact(
            'attendances', 'grouped', 'month', 'year', 'employees', 'monthlySummary', 'isSuperAdmin'
        ));
    }
}
PHPEOF

# ── 9. CONTROLLER: ReturnedEquipmentController ────────────
cat > app/Http/Controllers/Admin/ReturnedEquipmentController.php << 'PHPEOF'
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnedEquipment;
use App\Models\Client;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnedEquipmentController extends Controller
{
    private function checkAdminAccess()
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->hasRole('Admin'))) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAdminAccess();
        $type = $request->type;

        $query = ReturnedEquipment::with(['returnedByUser', 'receivedByUser'])->orderBy('return_date', 'desc');
        if ($type) $query->where('equipment_type', $type);

        $equipments = $query->paginate(25);
        $typeCounts = ReturnedEquipment::select('equipment_type', DB::raw('COUNT(*) as count'))
            ->groupBy('equipment_type')->pluck('count', 'equipment_type');

        return view('admin.inventory.returned-equipments', compact('equipments', 'typeCounts', 'type'));
    }

    public function show(ReturnedEquipment $returnedEquipment)
    {
        $this->checkAdminAccess();
        $returnedEquipment->load(['returnedByUser', 'receivedByUser', 'client']);
        return view('admin.inventory.returned-equipment-detail', compact('returnedEquipment'));
    }

    public function getClientEquipment(Client $client)
    {
        $equipment = [];

        $onu = DB::table('onus')->where('client_id', $client->id)->first();
        if ($onu) {
            $equipment[] = [
                'type' => 'ONU', 'name' => ($onu->vendor ?? '') . ' ' . ($onu->model ?? 'ONU Device'),
                'serial' => $onu->serial_number ?? $onu->mac_address ?? 'N/A',
                'mac' => $onu->mac_address ?? 'N/A', 'model' => $onu->model ?? 'N/A',
                'brand' => $onu->vendor ?? 'N/A', 'pon_port' => $onu->pon_port ?? 'N/A',
                'onu_index' => $onu->onu_index ?? 'N/A', 'source' => 'onus_table', 'source_id' => $onu->id,
            ];
        }

        if ($client->onu_serial) {
            $alreadyHasOnu = collect($equipment)->where('type', 'ONU')->where('serial', $client->onu_serial)->isNotEmpty();
            if (!$alreadyHasOnu) {
                $equipment[] = [
                    'type' => 'ONU', 'name' => ($client->onu_brand ?? '') . ' ' . ($client->onu_model ?? 'ONU'),
                    'serial' => $client->onu_serial, 'mac' => $client->onu_mac ?? 'N/A',
                    'model' => $client->onu_model ?? 'N/A', 'brand' => $client->onu_brand ?? 'N/A',
                    'pon_port' => $client->pon_port ?? 'N/A', 'onu_index' => 'N/A',
                    'source' => 'client_record', 'source_id' => $client->id,
                ];
            }
        }

        $serials = DB::table('product_serials')->where('client_id', $client->id)
            ->leftJoin('products', 'product_serials.product_id', '=', 'products.id')
            ->select('product_serials.*', 'products.name as product_name')->get();

        foreach ($serials as $serial) {
            $equipment[] = [
                'type' => 'Device', 'name' => $serial->product_name ?? 'Product',
                'serial' => $serial->serial_number ?? 'N/A', 'mac' => $serial->mac_address ?? 'N/A',
                'model' => $serial->model ?? 'N/A', 'brand' => $serial->brand ?? 'N/A',
                'pon_port' => 'N/A', 'onu_index' => 'N/A', 'source' => 'product_serials', 'source_id' => $serial->id,
            ];
        }

        if ($client->connection_type) {
            $equipment[] = [
                'type' => 'Fiber/Cable', 'name' => 'Fiber Connection (' . ucfirst($client->connection_type) . ')',
                'serial' => 'N/A', 'mac' => 'N/A', 'model' => $client->connection_type,
                'brand' => 'N/A', 'pon_port' => 'N/A', 'onu_index' => 'N/A',
                'source' => 'client_record', 'source_id' => $client->id,
            ];
        }

        $equipment[] = [
            'type' => 'Patchcord', 'name' => 'Patchcord', 'serial' => 'N/A', 'mac' => 'N/A',
            'model' => 'Standard', 'brand' => 'N/A', 'pon_port' => 'N/A', 'onu_index' => 'N/A',
            'source' => 'assumed', 'source_id' => null,
        ];

        return response()->json(['equipment' => $equipment, 'client' => [
            'id' => $client->id, 'name' => $client->name,
            'client_id' => $client->client_id, 'phone' => $client->phone,
        ]]);
    }

    public function returnEquipment(Request $request, Client $client)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->hasRole('Admin'))) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'equipment' => 'required|array|min:1',
            'equipment.*.type' => 'required|string',
            'equipment.*.name' => 'required|string',
            'equipment.*.condition' => 'required|in:good,damaged,faulty,unknown',
        ]);

        $returnedItems = [];
        DB::beginTransaction();
        try {
            foreach ($request->equipment as $item) {
                $returned = ReturnedEquipment::create([
                    'client_id' => $client->id, 'client_name' => $client->name,
                    'client_client_id' => $client->client_id, 'equipment_type' => $item['type'],
                    'equipment_name' => $item['name'], 'serial_number' => $item['serial'] ?? null,
                    'mac_address' => $item['mac'] ?? null, 'model' => $item['model'] ?? null,
                    'brand' => $item['brand'] ?? null, 'pon_port' => $item['pon_port'] ?? null,
                    'onu_index' => $item['onu_index'] ?? null, 'condition' => $item['condition'],
                    'remarks' => $item['remarks'] ?? null, 'returned_by' => $user->id,
                    'received_by' => $user->id, 'return_date' => today(),
                    'company_id' => $user->company_id ?? 1,
                ]);
                $returnedItems[] = $returned;

                if ($item['type'] === 'ONU' && isset($item['source']) && $item['source'] === 'onus_table' && isset($item['source_id'])) {
                    DB::table('onus')->where('id', $item['source_id'])->update(['client_id' => null]);
                }
                if (isset($item['source']) && $item['source'] === 'product_serials' && isset($item['source_id'])) {
                    DB::table('product_serials')->where('id', $item['source_id'])->update(['client_id' => null, 'status' => 'in_stock']);
                }
            }
            DB::commit();
            ActivityLog::log('create', 'returned_equipments', "Returned " . count($returnedItems) . " equipment(s) from client {$client->name} ({$client->client_id})");
            return response()->json(['success' => true, 'message' => count($returnedItems) . ' equipment(s) returned to inventory successfully.', 'returned' => $returnedItems]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to return equipment: ' . $e->getMessage()], 500);
        }
    }
}
PHPEOF

echo "✓ Controllers created"

# ── 10. VIEWS ─────────────────────────────────────────────
mkdir -p resources/views/admin/billing
mkdir -p resources/views/admin/hrm
mkdir -p resources/views/admin/inventory

# View: daily-collections
cat > resources/views/admin/billing/daily-collections.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('title', 'Daily Collections')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.billing.daily-collections') }}">Billing</a></li>
<li class="breadcrumb-item active">Daily Collections</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-3"><div class="small-box bg-info"><div class="inner"><h3>৳{{ number_format($totalAmount) }}</h3><p>Total Collection</p></div><div class="icon"><i class="fas fa-money-bill-wave"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-success"><div class="inner"><h3>{{ $totalCount }}</h3><p>Total Bills Collected</p></div><div class="icon"><i class="fas fa-receipt"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-warning"><div class="inner"><h3>৳{{ number_format($pendingAmount) }}</h3><p>Pending Approval</p></div><div class="icon"><i class="fas fa-clock"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-primary"><div class="inner"><h3>৳{{ number_format($approvedAmount) }}</h3><p>Approved</p></div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Submit Collection</h3></div>
                <form action="{{ route('admin.billing.daily-collections.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group"><label>Client <span class="text-danger">*</span></label><select class="form-control" id="clientSearch" name="client_id" required></select></div>
                        <div id="clientInfo" class="alert alert-light d-none mb-3 p-2"><small><strong>Package:</strong> <span id="ciPackage"></span> | <strong>Due:</strong> ৳<span id="ciDue"></span></small></div>
                        <div class="form-group"><label>Amount (৳) <span class="text-danger">*</span></label><input type="number" name="amount" class="form-control" step="1" min="1" required placeholder="Enter amount"></div>
                        <div class="form-group"><label>Collection Date</label><input type="date" name="collection_date" class="form-control" value="{{ $date }}"></div>
                        <div class="form-group"><label>Payment Method</label><select name="payment_method" class="form-control"><option value="Cash">Cash</option><option value="bKash">bKash</option><option value="Nagad">Nagad</option><option value="Rocket">Rocket</option><option value="Bank">Bank Transfer</option></select></div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea></div>
                    </div>
                    <div class="card-footer"><button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-2"></i>Submit Collection</button></div>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i>Collections for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
                    <div class="card-tools">
                        <form class="form-inline" method="GET">
                            <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}">
                            @if($isSuperAdmin && $collectors->count() > 0)
                            <select name="user_id" class="form-control form-control-sm mr-2"><option value="">All Employees</option>@foreach($collectors as $c)<option value="{{ $c->id }}" {{ request('user_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select>
                            @endif
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="bg-light"><tr><th>#</th><th>Employee</th><th>Client</th><th>Amount</th><th>Method</th><th>Time</th><th>Status</th>@if($isSuperAdmin)<th>Action</th>@endif</tr></thead>
                        <tbody>
                            @forelse($collections as $i => $col)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><i class="fas fa-user-tie mr-1 text-muted"></i>{{ $col->user->name ?? 'N/A' }}</td>
                                <td><strong>{{ $col->client->client_id ?? '' }}</strong><br><small class="text-muted">{{ $col->client->name ?? 'N/A' }}</small></td>
                                <td class="text-success font-weight-bold">৳{{ number_format($col->amount) }}</td>
                                <td><span class="badge badge-secondary">{{ $col->payment_method }}</span></td>
                                <td><small>{{ $col->created_at->format('h:i A') }}</small></td>
                                <td>
                                    @if($col->status == 'approved')<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Approved</span>
                                    @elseif($col->status == 'rejected')<span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Rejected</span>
                                    @else<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>@endif
                                </td>
                                @if($isSuperAdmin)
                                <td>
                                    @if($col->status == 'pending')
                                    <form action="{{ route('admin.billing.daily-collections.approve', $col) }}" method="POST" class="d-inline">@csrf<button class="btn btn-success btn-xs" title="Approve"><i class="fas fa-check"></i></button></form>
                                    <form action="{{ route('admin.billing.daily-collections.reject', $col) }}" method="POST" class="d-inline">@csrf<button class="btn btn-danger btn-xs" title="Reject"><i class="fas fa-times"></i></button></form>
                                    @else<small class="text-muted">{{ $col->approvedBy->name ?? '' }}</small>@endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No collections found for this date</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($isSuperAdmin && $dailySummary->count() > 0)
            <div class="card">
                <div class="card-header bg-gradient-info"><h3 class="card-title text-white"><i class="fas fa-chart-bar mr-2"></i>Employee Summary — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="bg-light"><tr><th>Employee</th><th>Bills Collected</th><th>Total Amount</th></tr></thead>
                        <tbody>@foreach($dailySummary as $s)<tr><td><i class="fas fa-user mr-1"></i>{{ $s->user->name ?? 'N/A' }}</td><td><span class="badge badge-info">{{ $s->total_bills }}</span></td><td class="text-success font-weight-bold">৳{{ number_format($s->total_amount) }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){
    $('#clientSearch').select2({
        theme: 'bootstrap4', placeholder: 'Search client by name, ID, phone...',
        ajax: { url: '{{ route("admin.billing.daily-collections.search-clients") }}', dataType: 'json', delay: 300,
            data: function(params){ return { q: params.term }; },
            processResults: function(data){ return { results: data.results }; }, minimumInputLength: 2 }
    }).on('select2:select', function(e){
        var d = e.params.data; $('#ciPackage').text(d.package); $('#ciDue').text(d.due); $('#clientInfo').removeClass('d-none');
    });
});
</script>
@endpush
PHPEOF

# View: collection-report
cat > resources/views/admin/billing/collection-report.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('title', 'Collection Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.billing.daily-collections') }}">Billing</a></li>
<li class="breadcrumb-item active">Collection Report</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Collection Report</h3>
            <div class="card-tools">
                <form class="form-inline" method="GET">
                    <input type="date" name="start_date" class="form-control form-control-sm mr-2" value="{{ $startDate }}">
                    <input type="date" name="end_date" class="form-control form-control-sm mr-2" value="{{ $endDate }}">
                    @if($isSuperAdmin && $collectors->count() > 0)
                    <select name="user_id" class="form-control form-control-sm mr-2"><option value="">All Employees</option>@foreach($collectors as $c)<option value="{{ $c->id }}" {{ request('user_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select>
                    @endif
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Filter</button>
                </form>
            </div>
        </div>
    </div>
    @if($isSuperAdmin && $summary->count() > 0)
    <div class="card">
        <div class="card-header bg-gradient-primary"><h3 class="card-title text-white"><i class="fas fa-users mr-2"></i>Employee-wise Summary</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="bg-light"><tr><th>Employee</th><th class="text-center">Total Bills</th><th class="text-right">Total Amount</th><th class="text-right">Approved</th><th class="text-right">Pending</th></tr></thead>
                <tbody>
                    @foreach($summary as $s)
                    <tr><td><i class="fas fa-user-tie mr-1 text-primary"></i>{{ $s->user->name ?? 'N/A' }}</td><td class="text-center"><span class="badge badge-info">{{ $s->total_bills }}</span></td><td class="text-right font-weight-bold">৳{{ number_format($s->total_amount) }}</td><td class="text-right text-success">৳{{ number_format($s->approved_amount) }}</td><td class="text-right text-warning">৳{{ number_format($s->pending_amount) }}</td></tr>
                    @endforeach
                    <tr class="font-weight-bold bg-light"><td>Total</td><td class="text-center">{{ $summary->sum('total_bills') }}</td><td class="text-right">৳{{ number_format($summary->sum('total_amount')) }}</td><td class="text-right text-success">৳{{ number_format($summary->sum('approved_amount')) }}</td><td class="text-right text-warning">৳{{ number_format($summary->sum('pending_amount')) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-list mr-2"></i>All Collections ({{ $collections->count() }})</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead class="bg-light"><tr><th>#</th><th>Date</th><th>Employee</th><th>Client</th><th>Amount</th><th>Method</th><th>Status</th><th>Receipt</th></tr></thead>
                <tbody>
                    @forelse($collections as $i => $col)
                    <tr><td>{{ $i + 1 }}</td><td>{{ $col->collection_date->format('d-m-Y') }}</td><td>{{ $col->user->name ?? 'N/A' }}</td><td>{{ $col->client->client_id ?? '' }} — {{ $col->client->name ?? 'N/A' }}</td><td class="text-success font-weight-bold">৳{{ number_format($col->amount) }}</td><td><span class="badge badge-secondary">{{ $col->payment_method }}</span></td>
                    <td>@if($col->status == 'approved')<span class="badge badge-success">Approved</span>@elseif($col->status == 'rejected')<span class="badge badge-danger">Rejected</span>@else<span class="badge badge-warning">Pending</span>@endif</td>
                    <td><small class="text-muted">{{ $col->receipt_no }}</small></td></tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No collections found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
PHPEOF

# View: selfie-attendance
cat > resources/views/admin/hrm/selfie-attendance.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('title', 'Selfie Attendance')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.selfie-attendance') }}">HRM</a></li>
<li class="breadcrumb-item active">Selfie Attendance</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-3"><div class="small-box bg-success"><div class="inner"><h3>{{ $todaySummary['total_checkins'] }}</h3><p>Check-ins Today</p></div><div class="icon"><i class="fas fa-sign-in-alt"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-info"><div class="inner"><h3>{{ $todaySummary['total_checkouts'] }}</h3><p>Check-outs Today</p></div><div class="icon"><i class="fas fa-sign-out-alt"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-primary"><div class="inner"><h3>{{ $todaySummary['unique_users'] }}</h3><p>Employees Present</p></div><div class="icon"><i class="fas fa-users"></i></div></div></div>
        <div class="col-md-3"><div class="small-box {{ $hasCheckedInToday ? 'bg-success' : 'bg-warning' }}"><div class="inner"><h3>{{ $hasCheckedInToday ? 'Done' : 'Pending' }}</h3><p>Your Check-in</p></div><div class="icon"><i class="fas fa-camera"></i></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-5">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-camera mr-2"></i>Take Selfie Attendance</h3></div>
                <div class="card-body text-center">
                    <div id="cameraSection">
                        <video id="videoPreview" width="100%" autoplay playsinline style="border-radius:10px;max-height:300px;background:#000;display:none;"></video>
                        <canvas id="selfieCanvas" style="display:none;"></canvas>
                        <img id="selfiePreview" style="display:none;border-radius:10px;max-height:300px;width:100%;object-fit:cover;" />
                        <div id="locationInfo" class="alert alert-light mt-3 text-left d-none">
                            <small><i class="fas fa-map-marker-alt text-danger mr-1"></i> <span id="locText">Fetching location...</span><br>
                            <i class="fas fa-globe text-primary mr-1"></i> Lat: <span id="latText">—</span>, Lng: <span id="lngText">—</span><br>
                            <i class="fas fa-clock text-info mr-1"></i> <span id="timeText">—</span></small>
                        </div>
                        <div class="mt-3">
                            <button id="btnStartCamera" class="btn btn-info btn-lg" onclick="startCamera()"><i class="fas fa-camera mr-2"></i>Open Camera</button>
                            <button id="btnCapture" class="btn btn-warning btn-lg d-none" onclick="capturePhoto()"><i class="fas fa-camera-retro mr-2"></i>Capture Selfie</button>
                            <button id="btnRetake" class="btn btn-secondary d-none" onclick="retakePhoto()"><i class="fas fa-redo mr-2"></i>Retake</button>
                        </div>
                        <div id="submitSection" class="mt-3 d-none">
                            <div class="btn-group btn-group-lg">
                                @if(!$hasCheckedInToday)<button class="btn btn-success" onclick="submitAttendance('check_in')" id="btnCheckIn"><i class="fas fa-sign-in-alt mr-2"></i>Check In</button>@endif
                                @if($hasCheckedInToday && !$hasCheckedOutToday)<button class="btn btn-danger" onclick="submitAttendance('check_out')" id="btnCheckOut"><i class="fas fa-sign-out-alt mr-2"></i>Check Out</button>@endif
                            </div>
                            @if($hasCheckedInToday && $hasCheckedOutToday)<div class="alert alert-success mt-2"><i class="fas fa-check-circle mr-2"></i>You have completed both Check-in and Check-out today.</div>@endif
                        </div>
                        <div id="resultMsg" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i>Attendance — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
                    <div class="card-tools">
                        <form class="form-inline" method="GET">
                            <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}">
                            @if($isSuperAdmin)<select name="user_id" class="form-control form-control-sm mr-2"><option value="">All Employees</option>@foreach($employees as $emp)@if($emp->user)<option value="{{ $emp->user->id }}" {{ request('user_id') == $emp->user->id ? 'selected' : '' }}>{{ $emp->name }}</option>@endif @endforeach</select>@endif
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light"><tr><th width="60">Photo</th><th>Employee</th><th>Type</th><th>Time</th><th>Location</th></tr></thead>
                        <tbody>
                            @forelse($attendances as $att)
                            <tr>
                                <td>@if($att->selfie_path)<img src="{{ asset('storage/' . $att->selfie_path) }}" class="img-circle" width="45" height="45" style="object-fit:cover;cursor:pointer;" onclick="viewSelfie(this.src,'{{ $att->user->name ?? '' }}','{{ $att->check_in_time }}','{{ $att->latitude }}','{{ $att->longitude }}','{{ $att->location_address }}')">@else<div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px;"><i class="fas fa-user text-white"></i></div>@endif</td>
                                <td><strong>{{ $att->user->name ?? 'N/A' }}</strong>@if($att->employee)<br><small class="text-muted">{{ $att->employee->employee_id ?? '' }}</small>@endif</td>
                                <td>@if($att->type === 'check_in')<span class="badge badge-success"><i class="fas fa-sign-in-alt mr-1"></i>In</span>@else<span class="badge badge-danger"><i class="fas fa-sign-out-alt mr-1"></i>Out</span>@endif</td>
                                <td><strong>{{ \Carbon\Carbon::parse($att->check_in_time)->format('h:i:s A') }}</strong></td>
                                <td>@if($att->latitude && $att->longitude)<a href="{{ $att->google_maps_url }}" target="_blank" class="text-primary" title="{{ $att->location_address }}"><i class="fas fa-map-marker-alt mr-1"></i>{{ Str::limit($att->location_address ?? $att->latitude.','.$att->longitude, 30) }}</a>@else<span class="text-muted">N/A</span>@endif</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>No attendance records for this date</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="selfieModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary"><h5 class="modal-title text-white"><i class="fas fa-camera mr-2"></i>Attendance Details</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div><div class="modal-body text-center"><img id="modalSelfie" src="" class="img-fluid rounded mb-3" style="max-height:400px;"><div id="modalInfo" class="text-left"></div></div></div></div></div>
@endsection
@push('scripts')
<script>
let stream=null,capturedImage=null,currentLat=null,currentLng=null,currentAddress=null;
function startCamera(){
    if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){alert('Camera not available on this device/browser.');return;}
    navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:640,height:480}})
    .then(function(s){stream=s;let v=document.getElementById('videoPreview');v.srcObject=s;v.style.display='block';document.getElementById('btnStartCamera').classList.add('d-none');document.getElementById('btnCapture').classList.remove('d-none');getLocation();})
    .catch(function(err){alert('Camera error: '+err.message);});
}
function getLocation(){
    document.getElementById('locationInfo').classList.remove('d-none');
    document.getElementById('locText').textContent='Fetching location...';
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(function(pos){
            currentLat=pos.coords.latitude;currentLng=pos.coords.longitude;
            document.getElementById('latText').textContent=currentLat.toFixed(6);
            document.getElementById('lngText').textContent=currentLng.toFixed(6);
            document.getElementById('timeText').textContent=new Date().toLocaleTimeString('en-US');
            fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+currentLat+'&lon='+currentLng)
            .then(r=>r.json()).then(data=>{currentAddress=data.display_name||(currentLat+','+currentLng);document.getElementById('locText').textContent=currentAddress.substring(0,80);})
            .catch(()=>{currentAddress=currentLat+','+currentLng;document.getElementById('locText').textContent=currentAddress;});
        },function(err){document.getElementById('locText').textContent='Location error: '+err.message;},{enableHighAccuracy:true,timeout:10000});
    }
}
function capturePhoto(){
    let v=document.getElementById('videoPreview'),c=document.getElementById('selfieCanvas');
    c.width=v.videoWidth;c.height=v.videoHeight;c.getContext('2d').drawImage(v,0,0);
    capturedImage=c.toDataURL('image/jpeg',0.8);
    document.getElementById('selfiePreview').src=capturedImage;document.getElementById('selfiePreview').style.display='block';v.style.display='none';
    document.getElementById('btnCapture').classList.add('d-none');document.getElementById('btnRetake').classList.remove('d-none');document.getElementById('submitSection').classList.remove('d-none');
    document.getElementById('timeText').textContent=new Date().toLocaleTimeString('en-US');
    if(stream){stream.getTracks().forEach(t=>t.stop());}
}
function retakePhoto(){capturedImage=null;document.getElementById('selfiePreview').style.display='none';document.getElementById('btnRetake').classList.add('d-none');document.getElementById('submitSection').classList.add('d-none');startCamera();}
function submitAttendance(type){
    if(!capturedImage){alert('Please capture a selfie first.');return;}
    if(!currentLat||!currentLng){alert('Location not available. Please enable location services.');return;}
    let btn=type==='check_in'?document.getElementById('btnCheckIn'):document.getElementById('btnCheckOut');
    if(btn){btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';}
    fetch('{{ route("admin.hrm.selfie-attendance.store") }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
    body:JSON.stringify({selfie:capturedImage,latitude:currentLat,longitude:currentLng,location_address:currentAddress,type:type})})
    .then(r=>r.json()).then(data=>{
        if(data.success){document.getElementById('resultMsg').innerHTML='<div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>'+data.message+'</div>';setTimeout(()=>location.reload(),1500);}
        else{document.getElementById('resultMsg').innerHTML='<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>'+(data.error||'Failed')+'</div>';if(btn){btn.disabled=false;btn.innerHTML=type==='check_in'?'<i class="fas fa-sign-in-alt mr-2"></i>Check In':'<i class="fas fa-sign-out-alt mr-2"></i>Check Out';}}
    }).catch(err=>{document.getElementById('resultMsg').innerHTML='<div class="alert alert-danger">Error: '+err.message+'</div>';if(btn){btn.disabled=false;}});
}
function escHtml(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function viewSelfie(src,name,time,lat,lng,address){
    document.getElementById('modalSelfie').src=src;
    var sn=escHtml(name),st=escHtml(time),sl=escHtml(lat),sg=escHtml(lng),sa=escHtml(address||(lat+','+lng));
    document.getElementById('modalInfo').innerHTML='<table class="table table-sm table-bordered"><tr><th width="120">Employee</th><td>'+sn+'</td></tr><tr><th>Time</th><td>'+st+'</td></tr><tr><th>Location</th><td>'+sa+'</td></tr><tr><th>Coordinates</th><td><a href="https://www.google.com/maps?q='+sl+','+sg+'" target="_blank">'+sl+','+sg+' <i class="fas fa-external-link-alt ml-1"></i></a></td></tr></table>';
    $('#selfieModal').modal('show');
}
</script>
@endpush
PHPEOF

# View: selfie-attendance-report
cat > resources/views/admin/hrm/selfie-attendance-report.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('title', 'Selfie Attendance Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.selfie-attendance') }}">HRM</a></li>
<li class="breadcrumb-item active">Selfie Attendance Report</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>Monthly Selfie Attendance Report</h3>
            <div class="card-tools">
                <form class="form-inline" method="GET">
                    <select name="month" class="form-control form-control-sm mr-2">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" {{ $month==$m?'selected':'' }}>{{ date('F',mktime(0,0,0,$m)) }}</option>@endfor</select>
                    <select name="year" class="form-control form-control-sm mr-2">@for($y=date('Y');$y>=date('Y')-2;$y--)<option value="{{ $y }}" {{ $year==$y?'selected':'' }}>{{ $y }}</option>@endfor</select>
                    @if($isSuperAdmin)<select name="user_id" class="form-control form-control-sm mr-2"><option value="">All Employees</option>@foreach($employees as $emp)@if($emp->user)<option value="{{ $emp->user->id }}" {{ request('user_id')==$emp->user->id?'selected':'' }}>{{ $emp->name }}</option>@endif @endforeach</select>@endif
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Filter</button>
                </form>
            </div>
        </div>
    </div>
    @if($isSuperAdmin && $monthlySummary->count() > 0)
    <div class="card">
        <div class="card-header bg-gradient-success"><h3 class="card-title text-white"><i class="fas fa-chart-bar mr-2"></i>Monthly Summary</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0"><thead class="bg-light"><tr><th>Employee</th><th>Days Present</th></tr></thead>
            <tbody>@foreach($monthlySummary as $s)<tr><td><i class="fas fa-user mr-1"></i>{{ $s->user->name ?? 'N/A' }}</td><td><span class="badge badge-success badge-lg">{{ $s->total_days }} days</span></td></tr>@endforeach</tbody></table>
        </div>
    </div>
    @endif
    @foreach($grouped as $dateKey => $dayAttendances)
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-day mr-2"></i>{{ \Carbon\Carbon::parse($dateKey)->format('l, d M Y') }}</h3><span class="badge badge-info float-right">{{ $dayAttendances->count() }} records</span></div>
        <div class="card-body p-0"><div class="row p-3">
            @foreach($dayAttendances as $att)
            <div class="col-md-4 col-lg-3 mb-3">
                <div class="card card-outline {{ $att->type==='check_in'?'card-success':'card-danger' }} mb-0">
                    <div class="card-body p-2 text-center">
                        @if($att->selfie_path)<img src="{{ asset('storage/'.$att->selfie_path) }}" class="rounded mb-2" style="width:100%;height:120px;object-fit:cover;">@endif
                        <h6 class="mb-1">{{ $att->user->name ?? 'N/A' }}</h6>
                        <span class="badge {{ $att->type==='check_in'?'badge-success':'badge-danger' }}">{{ $att->type==='check_in'?'Check In':'Check Out' }}</span>
                        <p class="mb-0 mt-1"><strong>{{ \Carbon\Carbon::parse($att->check_in_time)->format('h:i:s A') }}</strong></p>
                        @if($att->latitude)<a href="{{ $att->google_maps_url }}" target="_blank" class="text-xs text-primary"><i class="fas fa-map-marker-alt"></i> View Location</a>@endif
                    </div>
                </div>
            </div>
            @endforeach
        </div></div>
    </div>
    @endforeach
    @if($grouped->isEmpty())<div class="card"><div class="card-body text-center py-5 text-muted"><i class="fas fa-calendar-times fa-3x mb-3"></i><p>No selfie attendance records found for this period.</p></div></div>@endif
</div>
@endsection
PHPEOF

# View: returned-equipments
cat > resources/views/admin/inventory/returned-equipments.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('title', 'Returned Equipment')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.returned-equipments') }}">Inventory</a></li>
<li class="breadcrumb-item active">Returned Equipment</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        @foreach($typeCounts as $eqType => $count)
        <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-{{ $eqType==='ONU'?'primary':($eqType==='Fiber/Cable'?'success':($eqType==='Patchcord'?'warning':'info')) }}"><i class="fas fa-{{ $eqType==='ONU'?'microchip':($eqType==='Fiber/Cable'?'ethernet':($eqType==='Patchcord'?'plug':'box')) }}"></i></span><div class="info-box-content"><span class="info-box-text">{{ $eqType }}</span><span class="info-box-number">{{ $count }}</span></div></div></div>
        @endforeach
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-undo-alt mr-2"></i>Returned Equipment List</h3>
            <div class="card-tools">
                <form class="form-inline" method="GET">
                    <select name="type" class="form-control form-control-sm mr-2"><option value="">All Types</option>@foreach($typeCounts->keys() as $t)<option value="{{ $t }}" {{ $type==$t?'selected':'' }}>{{ $t }}</option>@endforeach</select>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped table-hover mb-0">
                <thead class="bg-light"><tr><th>#</th><th>Return Date</th><th>Client</th><th>Type</th><th>Equipment</th><th>Serial/MAC</th><th>Condition</th><th>Received By</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($equipments as $i => $eq)
                    <tr>
                        <td>{{ $equipments->firstItem()+$i }}</td>
                        <td>{{ $eq->return_date->format('d-m-Y') }}</td>
                        <td><strong>{{ $eq->client_client_id }}</strong><br><small class="text-muted">{{ $eq->client_name }}</small></td>
                        <td><span class="badge badge-{{ $eq->equipment_type==='ONU'?'primary':($eq->equipment_type==='Fiber/Cable'?'success':'secondary') }}">{{ $eq->equipment_type }}</span></td>
                        <td>{{ $eq->equipment_name }}</td>
                        <td>@if($eq->serial_number&&$eq->serial_number!='N/A')<small>S/N: {{ $eq->serial_number }}</small>@endif @if($eq->mac_address&&$eq->mac_address!='N/A')<br><small>MAC: {{ $eq->mac_address }}</small>@endif</td>
                        <td><span class="badge badge-{{ $eq->condition==='good'?'success':($eq->condition==='damaged'?'danger':($eq->condition==='faulty'?'warning':'secondary')) }}">{{ ucfirst($eq->condition) }}</span></td>
                        <td>{{ $eq->receivedByUser->name ?? 'N/A' }}</td>
                        <td><a href="{{ route('admin.inventory.returned-equipments.show', $eq) }}" class="btn btn-info btn-xs" title="View Details"><i class="fas fa-eye"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-box-open fa-2x mb-2 d-block"></i>No returned equipment found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($equipments->hasPages())<div class="card-footer">{{ $equipments->withQueryString()->links() }}</div>@endif
    </div>
</div>
@endsection
PHPEOF

# View: returned-equipment-detail
cat > resources/views/admin/inventory/returned-equipment-detail.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('title', 'Returned Equipment Detail')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.returned-equipments') }}">Returned Equipment</a></li>
<li class="breadcrumb-item active">Detail</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Equipment Information</h3></div>
                <div class="card-body">
                    <table class="table table-sm table-bordered">
                        <tr><th width="35%">Type</th><td><span class="badge badge-primary">{{ $returnedEquipment->equipment_type }}</span></td></tr>
                        <tr><th>Equipment Name</th><td>{{ $returnedEquipment->equipment_name }}</td></tr>
                        <tr><th>Serial Number</th><td>{{ $returnedEquipment->serial_number ?? 'N/A' }}</td></tr>
                        <tr><th>MAC Address</th><td>{{ $returnedEquipment->mac_address ?? 'N/A' }}</td></tr>
                        <tr><th>Model</th><td>{{ $returnedEquipment->model ?? 'N/A' }}</td></tr>
                        <tr><th>Brand</th><td>{{ $returnedEquipment->brand ?? 'N/A' }}</td></tr>
                        <tr><th>PON Port</th><td>{{ $returnedEquipment->pon_port ?? 'N/A' }}</td></tr>
                        <tr><th>ONU Index</th><td>{{ $returnedEquipment->onu_index ?? 'N/A' }}</td></tr>
                        <tr><th>Condition</th><td><span class="badge badge-{{ $returnedEquipment->condition==='good'?'success':($returnedEquipment->condition==='damaged'?'danger':'warning') }} badge-lg">{{ ucfirst($returnedEquipment->condition) }}</span></td></tr>
                        <tr><th>Remarks</th><td>{{ $returnedEquipment->remarks ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-info card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-user mr-2"></i>Return Details</h3></div>
                <div class="card-body">
                    <table class="table table-sm table-bordered">
                        <tr><th width="35%">Client ID</th><td>{{ $returnedEquipment->client_client_id }}</td></tr>
                        <tr><th>Client Name</th><td>{{ $returnedEquipment->client_name }}</td></tr>
                        <tr><th>Return Date</th><td>{{ $returnedEquipment->return_date->format('d M Y') }}</td></tr>
                        <tr><th>Received By</th><td>{{ $returnedEquipment->receivedByUser->name ?? 'N/A' }}</td></tr>
                        <tr><th>Returned By</th><td>{{ $returnedEquipment->returnedByUser->name ?? 'N/A' }}</td></tr>
                        <tr><th>Record Created</th><td>{{ $returnedEquipment->created_at->format('d M Y h:i A') }}</td></tr>
                    </table>
                </div>
            </div>
            <a href="{{ route('admin.inventory.returned-equipments') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Back to List</a>
        </div>
    </div>
</div>
@endsection
PHPEOF

# View: confirm-delete (backup old first)
cp resources/views/admin/clients/confirm-delete.blade.php resources/views/admin/clients/confirm-delete.blade.php.bak 2>/dev/null || true

cat > resources/views/admin/clients/confirm-delete.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('title', 'Delete Client')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.show', $client) }}">{{ $client->client_id }}</a></li>
<li class="breadcrumb-item active">Delete</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card card-danger card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-exclamation-triangle text-danger mr-2"></i>Delete Client</h3></div>
                <div class="card-body">
                    <div class="alert alert-warning"><i class="fas fa-exclamation-circle mr-2"></i><strong>Warning!</strong> You are about to delete this client. The client and their data will be preserved and can be recovered from <strong>Client Recovery</strong>.</div>
                    <table class="table table-sm table-bordered">
                        <tr><th width="35%">Client ID</th><td><strong>{{ $client->client_id }}</strong></td></tr>
                        <tr><th>Name</th><td>{{ $client->name }}</td></tr>
                        <tr><th>Phone</th><td>{{ $client->phone }}</td></tr>
                        <tr><th>Package</th><td>{{ $client->package->name ?? 'N/A' }}</td></tr>
                        <tr><th>Zone</th><td>{{ $client->zone->name ?? 'N/A' }}</td></tr>
                        <tr><th>Status</th><td><span class="badge badge-{{ $client->status=='active'?'success':($client->status=='inactive'?'danger':'secondary') }}">{{ ucfirst($client->status) }}</span></td></tr>
                        <tr><th>Total Bills</th><td><span class="badge badge-info">{{ $bills->count() }}</span></td></tr>
                        <tr><th>Total Payments</th><td><span class="badge badge-success">{{ $payments->count() }}</span></td></tr>
                    </table>
                    @if(isset($clientEquipment) && count($clientEquipment) > 0)
                    <div class="card card-warning card-outline mt-3 mb-3">
                        <div class="card-header bg-warning"><h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Equipment Assigned to This Client</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="bg-light"><tr><th><input type="checkbox" id="selectAllEquip" checked></th><th>Type</th><th>Equipment</th><th>Serial/MAC</th><th>Condition</th></tr></thead>
                                <tbody>
                                    @foreach($clientEquipment as $idx => $eq)
                                    <tr>
                                        <td><input type="checkbox" class="equip-check" data-index="{{ $idx }}" checked></td>
                                        <td><span class="badge badge-{{ $eq['type']==='ONU'?'primary':($eq['type']==='Fiber/Cable'?'success':'secondary') }}">{{ $eq['type'] }}</span></td>
                                        <td>{{ $eq['name'] }}</td>
                                        <td><small>{{ $eq['serial']!=='N/A'?$eq['serial']:($eq['mac']!=='N/A'?$eq['mac']:'—') }}</small></td>
                                        <td><select class="form-control form-control-sm equip-condition" data-index="{{ $idx }}" style="width:100px;"><option value="good">Good</option><option value="damaged">Damaged</option><option value="faulty">Faulty</option><option value="unknown" selected>Unknown</option></select></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-warning btn-block" id="btnReturnEquipment" onclick="returnEquipmentToInventory()"><i class="fas fa-undo-alt mr-2"></i>Return Selected Equipment to Inventory</button>
                            <div id="returnResult" class="mt-2"></div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-info mt-3"><i class="fas fa-info-circle mr-2"></i>No equipment found assigned to this client.</div>
                    @endif
                    <div class="mt-4">
                        <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" id="deleteForm" onsubmit="return confirmDeleteWithCheck();">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="equipment_returned" id="equipmentReturned" value="0">
                            <div class="form-group mb-3">
                                <label><strong>Reason for deletion</strong></label>
                                <select class="form-control mb-2" id="deleteReasonPreset" onchange="if(this.value){document.getElementById('delete_reason').value=this.value;}">
                                    <option value="">-- Select reason --</option>
                                    @foreach($deleteReasons as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                                </select>
                                <input type="text" name="delete_reason" id="delete_reason" class="form-control" placeholder="Or type a reason..." value="Deleted by admin">
                            </div>
                            <button type="submit" class="btn btn-danger btn-lg btn-block"><i class="fas fa-trash mr-2"></i>Delete Client</button>
                        </form>
                        <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-secondary btn-block mt-2"><i class="fas fa-arrow-left mr-2"></i>Cancel</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info"><h3 class="card-title text-white"><i class="fas fa-file-invoice mr-2"></i>Client Bills ({{ $bills->count() }})</h3></div>
                <div class="card-body p-0" style="max-height:400px;overflow-y:auto;">
                    @if($bills->count() > 0)
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light sticky-top"><tr><th>Bill No</th><th>Month</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            @foreach($bills as $bill)
                            <tr>
                                <td>{{ $bill->bill_no }}</td><td>{{ $bill->billing_month }}/{{ $bill->billing_year }}</td>
                                <td>৳{{ number_format($bill->total_amount ?? 0) }}</td>
                                <td><span class="badge badge-{{ $bill->status=='paid'?'success':($bill->status=='partial'?'warning':'danger') }}">{{ ucfirst($bill->status) }}</span></td>
                                <td><form action="{{ route('admin.clients.delete-bill', [$client, $bill]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this bill?')">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-xs" title="Delete Bill"><i class="fas fa-trash"></i></button></form></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else<div class="text-center py-4 text-muted"><i class="fas fa-file-invoice fa-2x mb-2"></i><p>No bills found</p></div>@endif
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header bg-success"><h3 class="card-title text-white"><i class="fas fa-money-bill-wave mr-2"></i>Client Payments ({{ $payments->count() }})</h3></div>
                <div class="card-body p-0" style="max-height:300px;overflow-y:auto;">
                    @if($payments->count() > 0)
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light sticky-top"><tr><th>Payment No</th><th>Date</th><th>Amount</th><th>Method</th></tr></thead>
                        <tbody>@foreach($payments as $payment)<tr><td>{{ $payment->payment_no }}</td><td>{{ $payment->payment_date->format('d-m-Y') }}</td><td class="text-success">৳{{ number_format($payment->amount ?? 0) }}</td><td>{{ $payment->paymentMethod->name ?? 'Cash' }}</td></tr>@endforeach</tbody>
                    </table>
                    @else<div class="text-center py-4 text-muted"><i class="fas fa-money-bill-wave fa-2x mb-2"></i><p>No payments found</p></div>@endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
var clientEquipmentData=@json($clientEquipment ?? []);
var equipmentReturned=false;
document.getElementById('selectAllEquip')?.addEventListener('change',function(){document.querySelectorAll('.equip-check').forEach(function(cb){cb.checked=this.checked;}.bind(this));});
function returnEquipmentToInventory(){
    var selectedEquip=[];
    document.querySelectorAll('.equip-check:checked').forEach(function(cb){var idx=cb.getAttribute('data-index');var condition=document.querySelector('.equip-condition[data-index="'+idx+'"]').value;var item=Object.assign({},clientEquipmentData[idx]);item.condition=condition;selectedEquip.push(item);});
    if(selectedEquip.length===0){alert('Please select at least one equipment to return.');return;}
    if(!confirm('Return '+selectedEquip.length+' equipment(s) to inventory?'))return;
    var btn=document.getElementById('btnReturnEquipment');btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    fetch('{{ route("admin.clients.return-equipment", $client) }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({equipment:selectedEquip})})
    .then(function(r){return r.json();}).then(function(data){
        if(data.success){equipmentReturned=true;document.getElementById('equipmentReturned').value='1';document.getElementById('returnResult').innerHTML='<div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>'+data.message+'</div>';btn.innerHTML='<i class="fas fa-check mr-2"></i>Equipment Returned';btn.classList.remove('btn-warning');btn.classList.add('btn-success');document.querySelectorAll('.equip-check').forEach(function(cb){cb.disabled=true;});document.querySelectorAll('.equip-condition').forEach(function(sel){sel.disabled=true;});}
        else{document.getElementById('returnResult').innerHTML='<div class="alert alert-danger"><i class="fas fa-times-circle mr-2"></i>'+(data.error||'Failed')+'</div>';btn.disabled=false;btn.innerHTML='<i class="fas fa-undo-alt mr-2"></i>Retry Return Equipment';}
    }).catch(function(err){document.getElementById('returnResult').innerHTML='<div class="alert alert-danger">Error: '+err.message+'</div>';btn.disabled=false;btn.innerHTML='<i class="fas fa-undo-alt mr-2"></i>Retry Return Equipment';});
}
function confirmDeleteWithCheck(){
    @if(isset($clientEquipment) && count($clientEquipment) > 0)
    if(!equipmentReturned){return confirm('WARNING: You have NOT returned the equipment to inventory yet!\n\nAre you sure you want to delete this client without returning equipment?\n\nClick OK to proceed anyway, or Cancel to return equipment first.');}
    @endif
    return confirm('Are you sure you want to delete this client? You can recover them later from Client Recovery.');
}
</script>
@endpush
PHPEOF

echo "✓ Views created"

# ── 11. ROUTES — add to web.php ────────────────────────────
echo ""
echo "======================================================"
echo "  Now adding routes to web.php..."
echo "======================================================"

# Check if routes already exist
if grep -q "admin.billing.daily-collections" routes/web.php; then
    echo "⚠ Daily Collection routes already exist — skipping"
else
    # Add before the {bill} wildcard route
    BILLING_ROUTE_LINE=$(grep -n "Route::get('/billing/{bill}'" routes/web.php | head -1 | cut -d: -f1)
    if [ -n "$BILLING_ROUTE_LINE" ]; then
        ROUTES_TO_ADD="    Route::get('/billing/daily-collections', [\\\App\\\Http\\\Controllers\\\Admin\\\DailyCollectionController::class, 'index'])->name('admin.billing.daily-collections');\n    Route::post('/billing/daily-collections', [\\\App\\\Http\\\Controllers\\\Admin\\\DailyCollectionController::class, 'store'])->name('admin.billing.daily-collections.store');\n    Route::get('/billing/daily-collections/search-clients', [\\\App\\\Http\\\Controllers\\\Admin\\\DailyCollectionController::class, 'searchClients'])->name('admin.billing.daily-collections.search-clients');\n    Route::post('/billing/daily-collections/{collection}/approve', [\\\App\\\Http\\\Controllers\\\Admin\\\DailyCollectionController::class, 'approve'])->name('admin.billing.daily-collections.approve');\n    Route::post('/billing/daily-collections/{collection}/reject', [\\\App\\\Http\\\Controllers\\\Admin\\\DailyCollectionController::class, 'reject'])->name('admin.billing.daily-collections.reject');\n    Route::get('/billing/collection-report', [\\\App\\\Http\\\Controllers\\\Admin\\\DailyCollectionController::class, 'report'])->name('admin.billing.collection-report');\n"
        sed -i "${BILLING_ROUTE_LINE}i\\${ROUTES_TO_ADD}" routes/web.php
        echo "✓ Daily Collection routes added before line $BILLING_ROUTE_LINE"
    else
        echo "⚠ Could not find billing wildcard route — append daily collection routes manually"
    fi
fi

if grep -q "admin.inventory.returned-equipments" routes/web.php; then
    echo "⚠ Returned Equipment routes already exist — skipping"
else
    echo "" >> routes/web.php
    echo "    // Returned Equipment" >> routes/web.php
    echo "    Route::get('/inventory/returned-equipments', [\App\Http\Controllers\Admin\ReturnedEquipmentController::class, 'index'])->name('admin.inventory.returned-equipments');" >> routes/web.php
    echo "    Route::get('/inventory/returned-equipments/{returnedEquipment}', [\App\Http\Controllers\Admin\ReturnedEquipmentController::class, 'show'])->name('admin.inventory.returned-equipments.show');" >> routes/web.php
    echo "✓ Returned Equipment routes added"
fi

if grep -q "admin.hrm.selfie-attendance" routes/web.php; then
    echo "⚠ Selfie Attendance routes already exist — skipping"
else
    echo "" >> routes/web.php
    echo "    // Selfie Attendance" >> routes/web.php
    echo "    Route::get('/hrm/selfie-attendance', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'index'])->name('admin.hrm.selfie-attendance');" >> routes/web.php
    echo "    Route::post('/hrm/selfie-attendance', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'store'])->name('admin.hrm.selfie-attendance.store');" >> routes/web.php
    echo "    Route::get('/hrm/selfie-attendance-report', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'report'])->name('admin.hrm.selfie-attendance.report');" >> routes/web.php
    echo "✓ Selfie Attendance routes added"
fi

if grep -q "admin.clients.confirm-delete" routes/web.php; then
    echo "⚠ Confirm-delete route already exists — skipping"
else
    # Add confirm-delete route near other client routes
    CLIENT_DESTROY_LINE=$(grep -n "admin.clients.destroy\|clients.destroy" routes/web.php | head -1 | cut -d: -f1)
    if [ -n "$CLIENT_DESTROY_LINE" ]; then
        sed -i "${CLIENT_DESTROY_LINE}i\\    Route::get('/clients/{client}/confirm-delete', [\\\App\\\Http\\\Controllers\\\Admin\\\ClientController::class, 'confirmDelete'])->name('admin.clients.confirm-delete');" routes/web.php
        echo "✓ Confirm-delete route added"
    fi
fi

if grep -q "admin.clients.return-equipment" routes/web.php; then
    echo "⚠ Return-equipment route already exists — skipping"
else
    echo "    Route::post('/clients/{client}/return-equipment', [\App\Http\Controllers\Admin\ReturnedEquipmentController::class, 'returnEquipment'])->name('admin.clients.return-equipment');" >> routes/web.php
    echo "✓ Return-equipment route added"
fi

# ── 12. MENU — add to admin.blade.php ─────────────────────
echo ""
echo "======================================================"
echo "  Adding menu items to sidebar..."
echo "======================================================"

ADMIN_LAYOUT="resources/views/layouts/admin.blade.php"

if grep -q "admin.billing.daily-collections" "$ADMIN_LAYOUT"; then
    echo "⚠ Daily Collection menu already exists — skipping"
else
    # Find billing section and add menu items
    BILLING_MENU_LINE=$(grep -n "admin.billing" "$ADMIN_LAYOUT" | head -1 | cut -d: -f1)
    if [ -n "$BILLING_MENU_LINE" ]; then
        MENU_ITEMS="                            <li class=\"nav-item\"><a href=\"{{ route('admin.billing.daily-collections') }}\" class=\"nav-link {{ request()->is('admin/billing/daily-collections*') ? 'active' : '' }}\"><i class=\"fas fa-money-bill-wave nav-icon\"></i><p>Daily Collections<\/p><\/a><\/li>\n                            <li class=\"nav-item\"><a href=\"{{ route('admin.billing.collection-report') }}\" class=\"nav-link {{ request()->is('admin/billing/collection-report*') ? 'active' : '' }}\"><i class=\"fas fa-chart-line nav-icon\"></i><p>Collection Report<\/p><\/a><\/li>"
        sed -i "${BILLING_MENU_LINE}i\\${MENU_ITEMS}" "$ADMIN_LAYOUT"
        echo "✓ Billing menu items added"
    fi
fi

if grep -q "admin.inventory.returned-equipments" "$ADMIN_LAYOUT"; then
    echo "⚠ Returned Equipment menu already exists — skipping"
else
    INVENTORY_MENU_LINE=$(grep -n "admin.inventory" "$ADMIN_LAYOUT" | head -1 | cut -d: -f1)
    if [ -n "$INVENTORY_MENU_LINE" ]; then
        sed -i "${INVENTORY_MENU_LINE}i\\                            <li class=\"nav-item\"><a href=\"{{ route('admin.inventory.returned-equipments') }}\" class=\"nav-link {{ request()->is('admin/inventory/returned-equipments*') ? 'active' : '' }}\"><i class=\"fas fa-undo-alt nav-icon\"><\/i><p>Returned Equipment<\/p><\/a><\/li>" "$ADMIN_LAYOUT"
        echo "✓ Inventory menu item added"
    fi
fi

if grep -q "admin.hrm.selfie-attendance" "$ADMIN_LAYOUT"; then
    echo "⚠ Selfie Attendance menu already exists — skipping"
else
    HRM_MENU_LINE=$(grep -n "admin.hrm\|'hrm'" "$ADMIN_LAYOUT" | head -1 | cut -d: -f1)
    if [ -n "$HRM_MENU_LINE" ]; then
        HRM_ITEMS="                            <li class=\"nav-item\"><a href=\"{{ route('admin.hrm.selfie-attendance') }}\" class=\"nav-link {{ request()->is('admin/hrm/selfie-attendance') ? 'active' : '' }}\"><i class=\"fas fa-camera nav-icon\"><\/i><p>Selfie Attendance<\/p><\/a><\/li>\n                            <li class=\"nav-item\"><a href=\"{{ route('admin.hrm.selfie-attendance.report') }}\" class=\"nav-link {{ request()->is('admin/hrm/selfie-attendance-report') ? 'active' : '' }}\"><i class=\"fas fa-calendar-check nav-icon\"><\/i><p>Selfie Report<\/p><\/a><\/li>"
        sed -i "${HRM_MENU_LINE}i\\${HRM_ITEMS}" "$ADMIN_LAYOUT"
        echo "✓ HRM menu items added"
    fi
fi

# ── 13. ClientController — add confirmDelete method ────────
if grep -q "confirmDelete" app/Http/Controllers/Admin/ClientController.php; then
    echo "⚠ confirmDelete method already exists in ClientController — skipping"
else
    # Insert confirmDelete method before the last closing brace
    LAST_BRACE=$(grep -n "^}" app/Http/Controllers/Admin/ClientController.php | tail -1 | cut -d: -f1)
    METHOD='
    /**
     * Show delete confirmation page with all client bills
     */
    public function confirmDelete(Client $client)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('"'"'Super Admin'"'"') && !\App\Services\MenuPermissionService::canAction($user, '"'"'client_list'"'"', '"'"'delete'"'"')) {
            abort(403, '"'"'You do not have permission to delete clients.'"'"');
        }
        $client->load(['"'"'zone'"'"', '"'"'area'"'"', '"'"'box'"'"', '"'"'package'"'"', '"'"'bills'"'"', '"'"'payments'"'"']);
        $bills = $client->bills()->orderBy('"'"'billing_year'"'"', '"'"'desc'"'"')->orderBy('"'"'billing_month'"'"', '"'"'desc'"'"')->get();
        $payments = $client->payments()->orderBy('"'"'payment_date'"'"', '"'"'desc'"'"')->get();
        $deleteReasons = \App\Models\DropdownOption::getOptions('"'"'client_delete_reasons'"'"');
        $equipmentController = new \App\Http\Controllers\Admin\ReturnedEquipmentController();
        $equipmentResponse = $equipmentController->getClientEquipment($client);
        $clientEquipment = json_decode($equipmentResponse->getContent(), true)['"'"'equipment'"'"'] ?? [];
        return view('"'"'admin.clients.confirm-delete'"'"', compact('"'"'client'"'"', '"'"'bills'"'"', '"'"'payments'"'"', '"'"'deleteReasons'"'"', '"'"'clientEquipment'"'"'));
    }
'
    sed -i "${LAST_BRACE}i\\${METHOD}" app/Http/Controllers/Admin/ClientController.php
    echo "✓ confirmDelete method added to ClientController"
fi

# ── 14. Run migrations ─────────────────────────────────────
echo ""
echo "======================================================"
echo "  Running database migrations..."
echo "======================================================"
php artisan migrate --force

# ── 15. Setup storage & clear caches ──────────────────────
echo ""
echo "======================================================"
echo "  Setting up storage and clearing caches..."
echo "======================================================"
mkdir -p storage/app/public/attendances/selfies
chmod -R 775 storage/app/public
php artisan storage:link 2>/dev/null || true
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo ""
echo "======================================================"
echo "  DONE! All 3 features installed successfully."
echo ""
echo "  New menu items added:"
echo "  - Billing → Daily Collections"
echo "  - Billing → Collection Report"
echo "  - Inventory → Returned Equipment"
echo "  - HRM → Selfie Attendance"
echo "  - HRM → Selfie Report"
echo "======================================================"
