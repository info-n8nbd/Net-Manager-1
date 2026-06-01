<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class ClientTimelineService
{
    public function getClientTimeline(int $clientId, int $limit = 50): array
    {
        $timeline = [];

        $bills = DB::table('bills')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        foreach ($bills as $bill) {
            $timeline[] = [
                'type' => 'bill',
                'icon' => 'fa-file-invoice',
                'color' => 'info',
                'title' => "Bill Generated - {$bill->bill_no}",
                'description' => "Amount: ৳{$bill->total_amount}",
                'date' => $bill->created_at,
                'metadata' => ['bill_id' => $bill->id, 'amount' => $bill->total_amount],
            ];
        }

        $payments = DB::table('payments')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        foreach ($payments as $payment) {
            $timeline[] = [
                'type' => 'payment',
                'icon' => 'fa-money-bill-wave',
                'color' => 'success',
                'title' => "Payment Received",
                'description' => "Amount: ৳{$payment->amount}" . ($payment->transaction_id ? " (TrxID: {$payment->transaction_id})" : ''),
                'date' => $payment->created_at,
                'metadata' => ['payment_id' => $payment->id, 'amount' => $payment->amount],
            ];
        }

        $tickets = DB::table('tickets')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        foreach ($tickets as $ticket) {
            $timeline[] = [
                'type' => 'ticket',
                'icon' => 'fa-headset',
                'color' => $ticket->status === 'closed' ? 'secondary' : 'warning',
                'title' => "Support Ticket - {$ticket->ticket_no}",
                'description' => $ticket->subject,
                'date' => $ticket->created_at,
                'metadata' => ['ticket_id' => $ticket->id, 'status' => $ticket->status],
            ];
        }

        $statusLogs = DB::table('client_status_logs')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($statusLogs as $log) {
            $timeline[] = [
                'type' => 'status_change',
                'icon' => 'fa-exchange-alt',
                'color' => 'primary',
                'title' => "Status Changed",
                'description' => "From {$log->old_status} to {$log->new_status}" . ($log->remarks ? " - {$log->remarks}" : ''),
                'date' => $log->created_at,
                'metadata' => ['old_status' => $log->old_status, 'new_status' => $log->new_status],
            ];
        }

        $lineShifts = DB::table('client_line_shifts')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($lineShifts as $shift) {
            $timeline[] = [
                'type' => 'line_shift',
                'icon' => 'fa-map-marker-alt',
                'color' => 'purple',
                'title' => "Line Shifted",
                'description' => "From {$shift->old_address} to {$shift->new_address}",
                'date' => $shift->created_at,
                'metadata' => ['shift_id' => $shift->id],
            ];
        }

        $reconnections = DB::table('client_reconnections')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($reconnections as $recon) {
            $timeline[] = [
                'type' => 'reconnection',
                'icon' => 'fa-plug',
                'color' => 'success',
                'title' => "Connection Restored",
                'description' => "Reconnection fee: ৳{$recon->reconnection_fee}",
                'date' => $recon->created_at,
                'metadata' => ['reconnection_id' => $recon->id],
            ];
        }

        $customEntries = DB::table('client_timeline')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        foreach ($customEntries as $entry) {
            $iconMap = [
                'note' => 'fa-sticky-note',
                'package_change' => 'fa-box',
                'line_shift' => 'fa-map-marker-alt',
                'reconnection' => 'fa-plug',
            ];
            $colorMap = [
                'note' => 'secondary',
                'package_change' => 'info',
                'line_shift' => 'purple',
                'reconnection' => 'success',
            ];
            $timeline[] = [
                'type' => $entry->event_type,
                'icon' => $iconMap[$entry->event_type] ?? 'fa-circle',
                'color' => $colorMap[$entry->event_type] ?? 'secondary',
                'title' => $entry->title,
                'description' => $entry->description,
                'date' => $entry->created_at,
                'metadata' => json_decode($entry->metadata ?? '{}', true),
            ];
        }

        usort($timeline, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
        return array_slice($timeline, 0, $limit);
    }

    public function addTimelineEntry(int $clientId, string $type, string $title, ?string $description = null, ?array $metadata = null, ?int $createdBy = null): void
    {
        DB::table('client_timeline')->insert([
            'client_id' => $clientId,
            'event_type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_by' => $createdBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
