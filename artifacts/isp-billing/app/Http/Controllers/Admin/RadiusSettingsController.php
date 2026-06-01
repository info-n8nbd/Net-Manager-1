<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Package;
use App\Services\RadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RadiusSettingsController extends Controller
{
    public function index()
    {
        $settings = DB::table('radius_settings')->first();
        if (!$settings) {
            DB::table('radius_settings')->insert([
                'enabled' => false,
                'server_host' => '127.0.0.1',
                'auth_port' => 1812,
                'acct_port' => 1813,
                'db_host' => '127.0.0.1',
                'db_port' => 3306,
                'db_name' => 'radius',
                'db_user' => 'radius',
                'default_auth_type' => 'PAP',
                'auto_sync_clients' => true,
                'auto_sync_packages' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $settings = DB::table('radius_settings')->first();
        }
        return view('admin.network.radius-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'server_host' => 'required|string|max:255',
            'auth_port' => 'required|integer|min:1|max:65535',
            'acct_port' => 'required|integer|min:1|max:65535',
            'secret' => 'nullable|string|max:255',
            'nas_identifier' => 'nullable|string|max:255',
            'db_host' => 'required|string|max:255',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_name' => 'required|string|max:255',
            'db_user' => 'required|string|max:255',
            'db_password' => 'nullable|string|max:255',
            'auto_sync_clients' => 'nullable|boolean',
            'auto_sync_packages' => 'nullable|boolean',
            'default_auth_type' => 'required|in:PAP,CHAP,MS-CHAPv2',
        ]);

        $validated['enabled'] = $request->has('enabled') ? 1 : 0;
        $validated['auto_sync_clients'] = $request->has('auto_sync_clients') ? 1 : 0;
        $validated['auto_sync_packages'] = $request->has('auto_sync_packages') ? 1 : 0;
        $validated['updated_at'] = now();

        if (empty($validated['db_password'])) {
            unset($validated['db_password']);
        }
        if (empty($validated['secret'])) {
            unset($validated['secret']);
        }

        $settings = DB::table('radius_settings')->first();
        if ($settings) {
            DB::table('radius_settings')->where('id', $settings->id)->update($validated);
        } else {
            $validated['created_at'] = now();
            DB::table('radius_settings')->insert($validated);
        }

        return redirect()->route('admin.network.radius-settings')
            ->with('success', 'RADIUS settings updated successfully.');
    }

    public function testConnection()
    {
        try {
            $radiusService = new RadiusService();
            $result = $radiusService->testConnection();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('RADIUS test connection error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function syncAllClients()
    {
        try {
            $radiusService = new RadiusService();
            if (!$radiusService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'RADIUS is not enabled',
                    'data' => [],
                ]);
            }

            $clients = Client::where('status', 'active')
                ->whereNotNull('pppoe_username')
                ->get();

            $synced = 0;
            $errors = 0;
            foreach ($clients as $client) {
                $password = $client->pppoe_password ?? $client->username;
                $result = $radiusService->createUser($client, $password);
                if ($result['success']) {
                    $synced++;
                } else {
                    $errors++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$synced} clients to RADIUS. {$errors} errors.",
                'data' => ['synced' => $synced, 'errors' => $errors],
            ]);
        } catch (\Exception $e) {
            Log::error('RADIUS sync clients error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function syncAllPackages()
    {
        try {
            $radiusService = new RadiusService();
            if (!$radiusService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'RADIUS is not enabled',
                    'data' => [],
                ]);
            }

            $packages = Package::where('is_active', true)->get();
            $synced = 0;
            $errors = 0;

            foreach ($packages as $package) {
                $result = $radiusService->syncPackageToGroup($package);
                if ($result['success']) {
                    $synced++;
                } else {
                    $errors++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$synced} packages to RADIUS groups. {$errors} errors.",
                'data' => ['synced' => $synced, 'errors' => $errors],
            ]);
        } catch (\Exception $e) {
            Log::error('RADIUS sync packages error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }
}
