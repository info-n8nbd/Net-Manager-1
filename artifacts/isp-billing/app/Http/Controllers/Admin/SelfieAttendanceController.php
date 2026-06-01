<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSelfieAttendance;
use App\Models\Employee;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SelfieAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date   = $request->date ?? today()->format('Y-m-d');
        $userId = $request->user_id;
        $user   = auth()->user();
        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'superadmin']);
        $isCompanyAdmin = ($user->user_type ?? '') === 'admin';

        // Permission check: Super Admin, Company Admin, or has hrm.selfie_attendance menu access
        if (!$isSuperAdmin && !$isCompanyAdmin) {
            $hasPermission = \App\Services\MenuPermissionService::canAccessMenu($user, "hrm", "selfie_attendance");
            if (!$hasPermission) {
                return redirect()->route('admin.dashboard')->with('error', 'You do not have permission to access Selfie Attendance.');
            }
        }

        $isAdminView = $isSuperAdmin || $isCompanyAdmin;

        $query = EmployeeSelfieAttendance::with(['user', 'user.roles', 'employee'])
            ->whereDate('attendance_date', $date);
        // Super Admin sees all companies; TenantAware global scope handles company filtering

        if (!$isAdminView) {
            $query->where('user_id', $user->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->orderBy('check_in_time', 'asc')->get();

        $todaySummary = [
            'total_checkins'  => $attendances->where('type', 'check_in')->count(),
            'total_checkouts' => $attendances->where('type', 'check_out')->count(),
            'unique_users'    => $attendances->unique('user_id')->count(),
        ];

        $hasCheckedInToday = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', 'check_in')->exists();

        $hasCheckedOutToday = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', 'check_out')->exists();

        $employees = $isAdminView
            ? ($isSuperAdmin
                ? Employee::where("status", "active")->with("user")->orderBy("name")->get()
                : Employee::where("company_id", $user->company_id)->where("status", "active")->with("user")->orderBy("name")->get())
            : collect();

        $settings = EmployeeSelfieAttendance::getSettings();

        return view('admin.hrm.selfie-attendance', compact(
            'attendances', 'date', 'todaySummary', 'hasCheckedInToday',
            'hasCheckedOutToday', 'employees', 'isSuperAdmin', 'isAdminView', 'settings'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'selfie'           => 'required|string',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'type'             => 'required|in:check_in,check_out',
            'location_address' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'superadmin']);
        $isCompanyAdmin = ($user->user_type ?? '') === 'admin';
        if (!$isSuperAdmin && !$isCompanyAdmin) {
            $hasPermission = \App\Services\MenuPermissionService::canAccessMenu($user, "hrm", "selfie_attendance");
            if (!$hasPermission) {
                return response()->json(['error' => 'Permission denied.'], 403);
            }
        }

        $alreadyExists = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())
            ->where('type', $request->type)
            ->exists();

        if ($alreadyExists) {
            $label = $request->type === 'check_in' ? 'Check In' : 'Check Out';
            return response()->json(['error' => "You have already done {$label} today."], 422);
        }

        $imageData = $request->selfie;
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $m)) {
            $ext       = $m[1];
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        } else {
            $ext = 'jpg';
        }

        $decoded = base64_decode($imageData);
        if (!$decoded) {
            return response()->json(['error' => 'Invalid selfie image data.'], 422);
        }

        $fileName = 'selfie_' . $user->id . '_' . now()->format('Ymd_His') . '.' . $ext;
        $path     = 'attendances/selfies/' . now()->format('Y/m');
        $fullDir  = storage_path('app/public/' . $path);
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0775, true);
            @chown($fullDir, 'www-data');
        }
        file_put_contents($fullDir . '/' . $fileName, $decoded);

        $employee = Employee::where('user_id', $user->id)->first();

        $settings = EmployeeSelfieAttendance::getSettings();
        $isValid  = null;
        $distance = null;
        if ($settings['office_lat'] && $request->latitude) {
            $distance = EmployeeSelfieAttendance::distanceInMeters(
                (float)$settings['office_lat'], (float)$settings['office_lng'],
                (float)$request->latitude, (float)$request->longitude
            );
            $isValid = $distance <= (float)$settings['office_radius'];
        }

        $attendance = EmployeeSelfieAttendance::create([
            'user_id'          => $user->id,
            'employee_id'      => $employee?->id,
            'attendance_date'  => today(),
            'check_in_time'    => now()->format('H:i:s'),
            'selfie_path'      => $path . '/' . $fileName,
            'latitude'         => $request->latitude,
            'longitude'        => $request->longitude,
            'location_address' => $request->location_address ? strip_tags($request->location_address) : null,
            'ip_address'       => $request->ip(),
            'device_info'      => substr($request->userAgent(), 0, 255),
            'type'             => $request->type,
            'remarks'          => $request->remarks,
            'company_id'       => $user->company_id ?? 1,
        ]);

        $typeLabel = $request->type === 'check_in' ? 'Check In' : 'Check Out';
        ActivityLog::log('create', 'selfie_attendance', "{$typeLabel} recorded for {$user->name} at " . now()->format('h:i A'));

        $msg = "{$typeLabel} recorded at " . now()->format('h:i:s A');
        if ($isValid === false) {
            $dist = round($distance);
            $msg .= " ⚠️ Outside office radius ({$dist}m away)";
        } elseif ($isValid === true) {
            $msg .= " ✅ Location valid";
        }

        return response()->json([
            'success'    => true,
            'message'    => $msg,
            'is_valid'   => $isValid,
            'distance'   => $distance ? round($distance) : null,
            'attendance' => $attendance,
        ]);
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['Super Admin', 'superadmin'])) {
            return redirect()->back()->with('error', 'Only Super Admin can delete attendance records.');
        }

        $attendance = EmployeeSelfieAttendance::findOrFail($id);
        if ($attendance->selfie_path) {
            Storage::disk('public')->delete($attendance->selfie_path);
        }
        $attendance->delete();
        ActivityLog::log('delete', 'selfie_attendance', "Attendance record deleted by {$user->name}");

        return redirect()->back()->with('success', 'Attendance record deleted successfully.');
    }

    public function report(Request $request)
    {
        $month  = $request->month ?? date('m');
        $year   = $request->year  ?? date('Y');
        $userId = $request->user_id;
        $user   = auth()->user();
        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'superadmin']);
        $isCompanyAdmin = ($user->user_type ?? '') === 'admin';
        $isAdminView    = $isSuperAdmin || $isCompanyAdmin;

        $query = EmployeeSelfieAttendance::with(['user', 'employee'])
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year);

        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->orderBy('check_in_time', 'asc')->get();
        $settings    = EmployeeSelfieAttendance::getSettings();

        $grouped     = $attendances->groupBy(fn($a) => $a->attendance_date->format('Y-m-d'));
        $userGrouped = $attendances->groupBy('user_id');

        $employeeSummary = [];
        foreach ($userGrouped as $uid => $records) {
            $checkIns  = $records->where('type', 'check_in');
            $checkOuts = $records->where('type', 'check_out');
            $lateCount = $checkIns->filter(fn($r) => $r->isLateCheckIn())->count();
            $earlyCount= $checkOuts->filter(fn($r) => $r->isEarlyLeave())->count();

            $totalMins = 0;
            foreach ($checkIns as $ci) {
                $co = $checkOuts->firstWhere('attendance_date', $ci->attendance_date);
                if ($co) {
                    $start = Carbon::parse($ci->attendance_date->format('Y-m-d') . ' ' . $ci->check_in_time);
                    $end   = Carbon::parse($co->attendance_date->format('Y-m-d') . ' ' . $co->check_in_time);
                    if ($end->gt($start)) $totalMins += $start->diffInMinutes($end);
                }
            }

            $employeeSummary[$uid] = [
                'name'         => $records->first()->user->name ?? 'N/A',
                'days_present' => $checkIns->count(),
                'late_count'   => $lateCount,
                'early_leaves' => $earlyCount,
                'total_hours'  => floor($totalMins / 60) . 'h ' . ($totalMins % 60) . 'm',
            ];
        }

        $workingHours = [];
        $allDates     = $attendances->pluck('attendance_date')->unique();
        foreach ($allDates as $date) {
            $dayKey   = $date->format('Y-m-d');
            $dayRecs  = $attendances->filter(fn($a) => $a->attendance_date->format('Y-m-d') === $dayKey);
            $dayUsers = $dayRecs->groupBy('user_id');
            foreach ($dayUsers as $uid => $urecs) {
                $ci = $urecs->firstWhere('type', 'check_in');
                $co = $urecs->firstWhere('type', 'check_out');
                $wh = null;
                if ($ci && $co) {
                    $start = Carbon::parse($dayKey . ' ' . $ci->check_in_time);
                    $end   = Carbon::parse($dayKey . ' ' . $co->check_in_time);
                    if ($end->gt($start)) {
                        $mins = $start->diffInMinutes($end);
                        $wh   = floor($mins / 60) . 'h ' . ($mins % 60) . 'm';
                    }
                }
                $workingHours[$dayKey][$uid] = $wh;
            }
        }

        $employees = $isAdminView
            ? Employee::where('status', 'active')->with('user')->orderBy('name')->get()
            : collect();

        $monthlySummary = $isSuperAdmin
            ? EmployeeSelfieAttendance::with('user')
                ->whereMonth('attendance_date', $month)
                ->whereYear('attendance_date', $year)
                ->where('type', 'check_in')
                ->select('user_id', DB::raw('COUNT(*) as total_days'))
                ->groupBy('user_id')
                ->get()
            : collect();

        if ($request->export === 'csv') {
            return $this->exportCsv($attendances, $workingHours, $month, $year);
        }

        return view('admin.hrm.selfie-attendance-report', compact(
            'attendances', 'grouped', 'month', 'year', 'employees',
            'monthlySummary', 'isSuperAdmin', 'employeeSummary',
            'workingHours', 'settings'
        ));
    }

    private function exportCsv($attendances, $workingHours, $month, $year)
    {
        $monthName = date('F', mktime(0, 0, 0, $month));
        $filename  = "attendance_{$monthName}_{$year}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($attendances, $workingHours) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Employee', 'Type', 'Time', 'Location', 'Working Hours', 'Late', 'Status']);

            $byDate = $attendances->groupBy(fn($a) => $a->attendance_date->format('Y-m-d'));
            foreach ($byDate as $date => $dayRecs) {
                $dayByUser = $dayRecs->groupBy('user_id');
                foreach ($dayByUser as $uid => $urecs) {
                    $ci   = $urecs->firstWhere('type', 'check_in');
                    $co   = $urecs->firstWhere('type', 'check_out');
                    $wh   = $workingHours[$date][$uid] ?? 'N/A';
                    $name = $ci ? ($ci->user->name ?? 'N/A') : ($co->user->name ?? 'N/A');
                    $late = $ci && $ci->isLateCheckIn() ? 'YES' : 'No';
                    $locValid = $ci ? ($ci->isValidLocation() === true ? 'Valid' : ($ci->isValidLocation() === false ? 'Invalid' : 'N/A')) : 'N/A';
                    fputcsv($handle, [
                        $date,
                        $name,
                        'Check In',
                        $ci ? substr($ci->check_in_time, 0, 8) : '-',
                        $ci ? ($ci->location_address ?? ($ci->latitude . ',' . $ci->longitude)) : '-',
                        $wh,
                        $late,
                        $locValid,
                    ]);
                    if ($co) {
                        fputcsv($handle, [
                            $date, $name, 'Check Out',
                            substr($co->check_in_time, 0, 8),
                            $co->location_address ?? ($co->latitude . ',' . $co->longitude),
                            '', '', '',
                        ]);
                    }
                }
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function map(Request $request)
    {
        $date   = $request->date ?? today()->format('Y-m-d');
        $user   = auth()->user();
        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'superadmin']);

        $query = EmployeeSelfieAttendance::with(['user', 'employee'])
            ->whereDate('attendance_date', $date)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        }

        $attendances = $query->orderBy('check_in_time', 'asc')->get();
        $settings    = EmployeeSelfieAttendance::getSettings();

        return view('admin.hrm.selfie-attendance-map', compact(
            'attendances', 'date', 'isSuperAdmin', 'settings'
        ));
    }

    public function settings()
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['Super Admin', 'superadmin'])) {
            return redirect()->route('admin.hrm.selfie-attendance')->with('error', 'Access denied.');
        }
        $settings = EmployeeSelfieAttendance::getSettings();
        return view('admin.hrm.selfie-attendance-settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['Super Admin', 'superadmin'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $request->validate([
            'office_lat'       => 'nullable|numeric',
            'office_lng'       => 'nullable|numeric',
            'office_radius'    => 'required|integer|min:10|max:10000',
            'late_time'        => 'required',
            'early_leave_time' => 'required',
            'office_name'      => 'nullable|string|max:100',
        ]);

        EmployeeSelfieAttendance::saveSettings([
            'office_lat'       => $request->office_lat,
            'office_lng'       => $request->office_lng,
            'office_radius'    => (int)$request->office_radius,
            'late_time'        => $request->late_time,
            'early_leave_time' => $request->early_leave_time,
            'office_name'      => $request->office_name ?? 'Main Office',
        ]);

        return redirect()->back()->with('success', 'Attendance settings saved successfully.');
    }
    // ─── Personal My Attendance (for the logged-in admin user) ──────────────
    public function myPersonalAttendance()
    {
        $user = auth()->user();
        $todayCheckin  = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', 'check_in')->first();
        $todayCheckout = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', 'check_out')->first();
        $history = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->orderBy('attendance_date', 'desc')->orderBy('check_in_time', 'asc')
            ->get()->groupBy(fn($r) => $r->attendance_date->format('Y-m-d'));
        return view('admin.hrm.my-attendance', compact('todayCheckin', 'todayCheckout', 'history'));
    }

    public function storePersonalAttendance(Request $request)
    {
        $request->validate([
            'selfie'           => 'required|string',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'type'             => 'required|in:check_in,check_out',
            'location_address' => 'nullable|string|max:500',
        ]);
        $user = auth()->user();
        $alreadyExists = EmployeeSelfieAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())->where('type', $request->type)->exists();
        if ($alreadyExists) {
            $label = $request->type === 'check_in' ? 'Check In' : 'Check Out';
            return response()->json(['error' => "You have already done {$label} today."], 422);
        }
        $imageData = $request->selfie;
        if (preg_match('/^data:image\\/(\\w+);base64,/', $imageData, $m)) {
            $ext = $m[1]; $imageData = substr($imageData, strpos($imageData, ',') + 1);
        } else { $ext = 'jpg'; }
        $decoded = base64_decode($imageData);
        if (!$decoded) return response()->json(['error' => 'Invalid image data.'], 422);
        $fileName = 'selfie_' . $user->id . '_' . now()->format('Ymd_His') . '.' . $ext;
        $path     = 'attendances/selfies/' . now()->format('Y/m');
        $fullDir  = storage_path('app/public/' . $path);
        if (!is_dir($fullDir)) { mkdir($fullDir, 0775, true); @chown($fullDir, 'www-data'); }
        file_put_contents($fullDir . '/' . $fileName, $decoded);
        $employee = \App\Models\Employee::where('user_id', $user->id)->first();
        EmployeeSelfieAttendance::create([
            'user_id'          => $user->id,
            'employee_id'      => $employee?->id,
            'attendance_date'  => today(),
            'check_in_time'    => now()->format('H:i:s'),
            'selfie_path'      => $path . '/' . $fileName,
            'latitude'         => $request->latitude,
            'longitude'        => $request->longitude,
            'location_address' => $request->location_address,
            'ip_address'       => $request->ip(),
            'device_info'      => substr($request->userAgent() ?? '', 0, 255),
            'type'             => $request->type,
            'company_id'       => $user->company_id ?? 1,
        ]);
        $typeLabel = $request->type === 'check_in' ? 'Check In' : 'Check Out';
        return response()->json(['success' => true, 'message' => "{$typeLabel} recorded at " . now()->format('h:i:s A')]);
    }

}
