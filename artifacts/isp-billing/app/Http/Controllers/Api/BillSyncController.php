<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillSyncController extends Controller
{
    public function getStatus(Client $client)
    {
        // Refresh client and bills from database
        $client->refresh();
        $bills = $client->bills()
            ->with(['payments' => function($q) {
                $q->with(['paymentMethod', 'collectedBy'])->latest();
            }])
            ->latest()
            ->take(6)
            ->get();

        $lastPayment = $client->payments()->latest()->first();
        $lastBill = $bills->first();

        // Calculate last update timestamp
        $lastUpdate = max(
            $lastBill ? $lastBill->updated_at->timestamp : 0,
            $lastPayment ? $lastPayment->updated_at->timestamp : 0,
            $client->updated_at->timestamp
        );

        // Render the billing HTML
        $html = $this->renderBillsHtml($bills);

        return response()->json([
            'success' => true,
            'html' => $html,
            'lastUpdate' => $lastUpdate,
            'totalDue' => $client->total_due ?? 0,
            'billCount' => $bills->count()
        ]);
    }

    private function renderBillsHtml($bills)
    {
        if ($bills->isEmpty()) {
            return '<div class="alert alert-info">No bills generated yet.</div>';
        }

        $html = '';
        foreach ($bills as $bill) {
            $lastPayment = $bill->payments->first();
            $paidPercent = $bill->total_amount > 0 ? min(100, ($bill->paid_amount / $bill->total_amount) * 100) : 0;
            $progressClass = $bill->status == 'paid' ? 'paid' : ($bill->status == 'partial' ? 'partial' : 'unpaid');

            $html .= '<div class="bill-container">';
            $html .= '<div class="bill-row">';
            
            // Left card - Bill info
            $html .= '<div class="bill-cube-box generate">';
            $html .= '<div class="cube-header-bar">' . ($bill->status == 'paid' ? 'Bill' : 'Due') . ' - ' . e($bill->bill_month) . '</div>';
            $html .= '<div class="cube-body">';
            $html .= '<div class="cube-amount">৳' . number_format($bill->total_amount) . '</div>';
            $html .= '<div class="cube-detail">Fee: ৳' . number_format($bill->package_fee ?? 0);
            if ($bill->discount > 0) {
                $html .= ' | Disc: ৳' . number_format($bill->discount);
            }
            $html .= '</div>';
            $html .= '<div class="cube-detail">';
            if ($bill->previous_due > 0) {
                $html .= 'Prev Due: ৳' . number_format($bill->previous_due) . ' | ';
            }
            $html .= 'Total: ৳' . number_format($bill->total_amount) . '</div>';
            $html .= '<div class="cube-time">' . $bill->created_at->timezone('Asia/Dhaka')->format('d-m-y h:i A') . '</div>';
            $html .= '</div></div>';

            // Right card - Payment info
            if ($bill->status == 'paid' && $lastPayment) {
                $html .= '<div class="bill-cube-box payment">';
                $html .= '<div class="cube-header-bar">Paid - ' . e($lastPayment->paymentMethod->name ?? 'Cash') . '</div>';
                $html .= '<div class="cube-body">';
                $html .= '<div class="cube-amount">৳' . number_format($lastPayment->amount) . '</div>';
                if ($lastPayment->collectedBy) {
                    $html .= '<div class="cube-detail">By: ' . e($lastPayment->collectedBy->name) . '</div>';
                }
                if ($lastPayment->notes) {
                    $html .= '<div class="cube-detail">' . e(Str::limit($lastPayment->notes, 30)) . '</div>';
                }
                $html .= '<div class="cube-time">' . $lastPayment->created_at->timezone('Asia/Dhaka')->format('d-m-y h:i A') . '</div>';
                $html .= '</div></div>';
            } elseif ($bill->status == 'partial' && $lastPayment) {
                $html .= '<div class="bill-cube-box partial-pay">';
                $html .= '<div class="cube-header-bar">Partial - ' . e($lastPayment->paymentMethod->name ?? 'Cash') . '</div>';
                $html .= '<div class="cube-body">';
                $html .= '<div class="cube-amount">৳' . number_format($bill->paid_amount) . '</div>';
                if ($lastPayment->collectedBy) {
                    $html .= '<div class="cube-detail">By: ' . e($lastPayment->collectedBy->name) . '</div>';
                }
                $html .= '<div class="cube-detail">Remaining: ৳' . number_format($bill->due_amount) . '</div>';
                $html .= '<div class="cube-time">' . $lastPayment->created_at->timezone('Asia/Dhaka')->format('d-m-y h:i A') . '</div>';
                $html .= '</div></div>';
            } else {
                $html .= '<div class="bill-cube-box unpaid">';
                $html .= '<div class="cube-header-bar">Unpaid</div>';
                $html .= '<div class="cube-body">';
                $html .= '<div class="cube-amount">৳' . number_format($bill->due_amount) . '</div>';
                $html .= '<div class="cube-detail">Total Bill: ৳' . number_format($bill->total_amount) . '</div>';
                $html .= '<div class="cube-detail">';
                if ($bill->paid_amount > 0) {
                    $html .= 'Already Paid: ৳' . number_format($bill->paid_amount);
                } else {
                    $html .= 'No payment yet';
                }
                $html .= '</div>';
                $html .= '<div class="cube-time">Due Amount</div>';
                $html .= '</div></div>';
            }

            $html .= '</div>'; // end bill-row

            // Progress bar
            $html .= '<div class="bill-progress-bar">';
            $html .= '<div class="progress-fill ' . $progressClass . '" style="width: ' . $paidPercent . '%"></div>';
            $html .= '</div>';
            $html .= '<div class="bill-progress-text">';
            $html .= '<span class="paid-text">Paid: ৳' . number_format($bill->paid_amount) . '</span>';
            $html .= '<span class="due-text">Due: ৳' . number_format($bill->due_amount) . '</span>';
            $html .= '</div>';

            $html .= '</div>'; // end bill-container
        }

        return $html;
    }
}
