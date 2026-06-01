<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\BackupService;
use App\Services\SuperAdminGuardService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    private function requireSuperAdmin(): void
    {
        if (!SuperAdminGuardService::isSuperAdmin(auth()->user())) {
            abort(403, 'Only Super Admins can manage backups.');
        }
    }

    public function index()
    {
        $this->requireSuperAdmin();
        $backups = Backup::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.backups.index', compact('backups'));
    }

    public function createDatabase(Request $request)
    {
        $this->requireSuperAdmin();
        $name = $request->input('name');
        $backup = $this->backupService->createDatabaseBackup($name);

        if ($backup->status === 'completed') {
            return back()->with('success', 'Database backup created successfully.');
        }

        return back()->with('error', 'Database backup failed: ' . ($backup->error_message ?? 'Unknown error'));
    }

    public function createConfig(Request $request)
    {
        $this->requireSuperAdmin();
        $name = $request->input('name');
        $backup = $this->backupService->createConfigBackup($name);

        if ($backup->status === 'completed') {
            return back()->with('success', 'Configuration backup created successfully.');
        }

        return back()->with('error', 'Configuration backup failed: ' . ($backup->error_message ?? 'Unknown error'));
    }

    public function download(Backup $backup)
    {
        $this->requireSuperAdmin();
        $filePath = $this->backupService->downloadBackup($backup);

        if (!$filePath) {
            return back()->with('error', 'Backup file not found.');
        }

        return response()->download($filePath, basename($filePath));
    }

    public function restore(Request $request, Backup $backup)
    {
        $this->requireSuperAdmin();
        $request->validate([
            'confirmation' => 'required|string|in:RESTORE',
        ]);

        if ($backup->type !== 'database') {
            return back()->with('error', 'Only database backups can be restored.');
        }

        $success = $this->backupService->restoreDatabase($backup);

        if ($success) {
            return back()->with('success', 'Database restored successfully from backup.');
        }

        return back()->with('error', 'Failed to restore database from backup.');
    }

    public function destroy(Backup $backup)
    {
        $this->requireSuperAdmin();
        $this->backupService->deleteBackup($backup);

        return back()->with('success', 'Backup deleted successfully.');
    }

    public function cleanup(Request $request)
    {
        $this->requireSuperAdmin();
        $request->validate(['keep_days' => 'nullable|integer|min:1|max:365']);
        $keepDays = $request->input('keep_days', 30);
        $count = $this->backupService->cleanOldBackups($keepDays);

        return back()->with('success', "{$count} old backup(s) cleaned up successfully.");
    }
}
