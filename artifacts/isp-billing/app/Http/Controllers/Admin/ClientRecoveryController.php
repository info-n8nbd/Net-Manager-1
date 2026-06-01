<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Services\ClientRecoveryService;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientRecoveryController extends Controller
{
    protected ClientRecoveryService $clientRecoveryService;

    public function __construct(ClientRecoveryService $clientRecoveryService)
    {
        $this->clientRecoveryService = $clientRecoveryService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'zone_id']);
        $clients = $this->clientRecoveryService->getDeletedClients($filters);
        $zones = \App\Models\Zone::orderBy('name')->get();

        return view('admin.client-recovery.index', compact('clients', 'zones'));
    }

    public function recover(Request $request, $clientId)
    {
        $client = Client::withoutGlobalScope('exclude_deleted')->findOrFail($clientId);

        if (!in_array($client->status, ['deleted', 'inactive'])) {
            return back()->with('error', 'This client is not in deleted or inactive status.');
        }

        $this->clientRecoveryService->recoverClient($client, $request->input('notes'));

        $mikrotikMsg = ($client->username && $client->connection_type === 'pppoe') ? ' MikroTik PPPoE secret has been re-created.' : '';

        $redirectRoute = url()->previous() && str_contains(url()->previous(), 'inactive')
            ? 'admin.clients.inactive'
            : 'admin.client-recovery.index';

        return redirect()->route($redirectRoute)->with('success', "Client '{$client->name}' has been restored successfully with all history.{$mikrotikMsg}");
    }

    public function history($clientId)
    {
        $client = Client::withoutGlobalScope('exclude_deleted')->with(['zone'])->findOrFail($clientId);
        $history = $this->clientRecoveryService->getRecoveryHistory($client->id);

        return view('admin.client-recovery.history', compact('client', 'history'));
    }

    public function permanentDelete($clientId)
    {
        $client = Client::withoutGlobalScope('exclude_deleted')->findOrFail($clientId);

        if ($client->status !== 'deleted') {
            return back()->with('error', 'Only deleted clients can be permanently removed.');
        }

        DB::beginTransaction();
        try {
            if ($client->username) {
                $deviceId = $client->mikrotik_device_id;
                $device = $deviceId ? MikrotikDevice::find($deviceId) : MikrotikDevice::where('is_active', true)->first();

                if ($device) {
                    try {
                        $mk = new MikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                        $mk->removePPPoEUser($client->username);
                        Log::info("Permanent delete: MikroTik user '{$client->username}' removed from device {$device->name}");
                    } catch (\Throwable $e) {
                        Log::warning("Permanent delete: Failed to remove MikroTik user '{$client->username}': " . $e->getMessage());
                    }
                }
            }

            $cid = $client->id;

            DB::table('ticket_messages')->whereIn('ticket_id', function($q) use ($cid) {
                $q->select('id')->from('tickets')->where('client_id', $cid);
            })->delete();
            DB::table('ticket_assignments')->whereIn('ticket_id', function($q) use ($cid) {
                $q->select('id')->from('tickets')->where('client_id', $cid);
            })->delete();
            DB::table('ticket_feedback')->whereIn('ticket_id', function($q) use ($cid) {
                $q->select('id')->from('tickets')->where('client_id', $cid);
            })->delete();
            DB::table('ticket_sla_timers')->whereIn('ticket_id', function($q) use ($cid) {
                $q->select('id')->from('tickets')->where('client_id', $cid);
            })->delete();
            DB::table('ticket_escalations')->whereIn('ticket_id', function($q) use ($cid) {
                $q->select('id')->from('tickets')->where('client_id', $cid);
            })->delete();
            DB::table('ticket_resolution_scores')->whereIn('ticket_id', function($q) use ($cid) {
                $q->select('id')->from('tickets')->where('client_id', $cid);
            })->delete();
            DB::table('tickets')->where('client_id', $cid)->delete();

            DB::table('bill_items')->whereIn('bill_id', function($q) use ($cid) {
                $q->select('id')->from('bills')->where('client_id', $cid);
            })->delete();
            DB::table('bill_adjustments')->whereIn('bill_id', function($q) use ($cid) {
                $q->select('id')->from('bills')->where('client_id', $cid);
            })->delete();
            DB::table('bill_corrections')->where('client_id', $cid)->delete();
            DB::table('bill_splits')->where('client_id', $cid)->delete();
            DB::table('bills')->where('client_id', $cid)->delete();

            DB::table('payment_corrections')->where('client_id', $cid)->delete();
            DB::table('payment_gateway_transactions')->where('client_id', $cid)->delete();
            DB::table('payment_links')->where('client_id', $cid)->delete();
            DB::table('payment_fraud_alerts')->whereIn('payment_id', function($q) use ($cid) {
                $q->select('id')->from('payments')->where('client_id', $cid);
            })->delete();
            DB::table('payment_audit_trail')->whereIn('payment_id', function($q) use ($cid) {
                $q->select('id')->from('payments')->where('client_id', $cid);
            })->delete();
            DB::table('payments')->where('client_id', $cid)->delete();
            DB::table('advance_payment_rewards')->where('client_id', $cid)->delete();
            DB::table('advance_payments')->where('client_id', $cid)->delete();

            DB::table('client_status_logs')->where('client_id', $cid)->delete();
            DB::table('client_expiry_logs')->where('client_id', $cid)->delete();
            DB::table('client_activity_logs')->where('client_id', $cid)->delete();
            DB::table('client_timeline')->where('client_id', $cid)->delete();
            DB::table('client_documents')->where('client_id', $cid)->delete();
            DB::table('client_packages')->where('client_id', $cid)->delete();
            DB::table('client_agreements')->where('client_id', $cid)->delete();
            DB::table('client_grace_periods')->where('client_id', $cid)->delete();
            DB::table('client_promo_usage')->where('client_id', $cid)->delete();
            DB::table('client_lifetime_values')->where('client_id', $cid)->delete();
            DB::table('client_line_shifts')->where('client_id', $cid)->delete();
            DB::table('client_reconnections')->where('client_id', $cid)->delete();
            DB::table('client_reconnect_logs')->where('client_id', $cid)->delete();
            DB::table('client_additional_payments')->where('client_id', $cid)->delete();
            DB::table('client_recovery_logs')->where('client_id', $cid)->delete();

            DB::table('sms_logs')->where('client_id', $cid)->delete();
            DB::table('whatsapp_logs')->where('client_id', $cid)->delete();
            DB::table('voice_call_logs')->where('client_id', $cid)->delete();
            DB::table('due_reminders')->where('client_id', $cid)->delete();

            DB::table('mikrotik_action_logs')->where('client_id', $cid)->delete();
            DB::table('mikrotik_bandwidth_usage')->where('client_id', $cid)->delete();
            DB::table('mikrotik_realtime_sessions')->where('client_id', $cid)->delete();
            DB::table('mikrotik_sessions')->where('client_id', $cid)->delete();
            DB::table('mikrotik_users')->where('client_id', $cid)->delete();

            DB::table('ip_allocations')->where('client_id', $cid)->delete();
            DB::table('mac_bindings')->where('client_id', $cid)->delete();
            DB::table('port_forwards')->where('client_id', $cid)->delete();
            DB::table('onu_devices')->where('client_id', $cid)->delete();
            DB::table('vpn_accounts')->where('client_id', $cid)->delete();

            DB::table('technician_jobs')->where('client_id', $cid)->delete();
            DB::table('technician_action_logs')->where('client_id', $cid)->delete();
            DB::table('admin_overrides')->where('client_id', $cid)->delete();
            DB::table('card_transactions')->where('client_id', $cid)->delete();
            DB::table('churn_predictions')->where('client_id', $cid)->delete();
            DB::table('credit_debit_notes')->where('client_id', $cid)->delete();
            DB::table('device_inventory')->where('deployed_to_client', $cid)->delete();
            DB::table('invoices')->where('client_id', $cid)->delete();
            DB::table('ledger_entries')->where('client_id', $cid)->delete();
            DB::table('internet_quality_scores')->where('client_id', $cid)->delete();
            DB::table('uptime_logs')->where('client_id', $cid)->delete();
            DB::table('slow_speed_alerts')->where('client_id', $cid)->delete();
            DB::table('speed_test_logs')->where('client_id', $cid)->delete();
            DB::table('speed_boost_requests')->where('client_id', $cid)->delete();
            DB::table('service_ratings')->where('client_id', $cid)->delete();
            DB::table('package_change_requests')->where('client_id', $cid)->delete();
            DB::table('minute_sales')->where('client_id', $cid)->delete();
            DB::table('product_serials')->where('client_id', $cid)->delete();
            DB::table('refunds')->where('client_id', $cid)->delete();
            DB::table('reseller_clients')->where('client_id', $cid)->delete();
            DB::table('sub_reseller_commissions')->where('client_id', $cid)->delete();
            DB::table('scratch_cards')->where('used_by_client_id', $cid)->update(['used_by_client_id' => null, 'status' => 'active']);

            DB::table('family_group_members')->where('client_id', $cid)->delete();
            DB::table('new_connection_applications')->where('created_client_id', $cid)->update(['created_client_id' => null]);
            DB::table('migration_router_snapshots')->where('billing_client_id', $cid)->update(['billing_client_id' => null]);

            $clientName = $client->name;
            $clientCode = $client->client_id;
            $client->delete();

            DB::commit();

            return redirect()->route('admin.client-recovery.index')
                ->with('success', "Client '{$clientName}' (ID: {$clientCode}) has been permanently deleted from database and MikroTik.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Permanent delete failed for client {$clientId}: " . $e->getMessage());
            return back()->with('error', 'Permanent delete failed: ' . $e->getMessage());
        }
    }
}
