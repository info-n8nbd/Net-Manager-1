<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Client;
use App\Models\ClientLineShift;
use App\Models\ClientReconnection;
use App\Models\PackageChangeRequest;
use App\Models\ClientDocument;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientHistoryController extends Controller
{
    public function lineShifts(Client $client)
    {
        $shifts = $client->lineShifts()->with('processor')->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:15);
        return view('admin.clients.line-shifts', compact('client', 'shifts'));
    }

    public function storeLineShift(Request $request, Client $client)
    {
        $validated = $request->validate([
            'new_address' => 'required|string|max:500',
            'new_zone' => 'nullable|string',
            'shift_charge' => 'required|numeric|min:0',
            'shift_date' => 'required|date',
            'remarks' => 'nullable|string'
        ]);

        $validated['client_id'] = $client->id;
        $validated['old_address'] = $client->address;
        $validated['old_zone'] = $client->zone?->name;
        $validated['processed_by'] = auth()->id();

        ClientLineShift::create($validated);

        $client->address = $validated['new_address'];
        if ($request->zone_id) {
            $client->zone_id = $request->zone_id;
        }
        $client->save();

        return redirect()->back()->with('success', 'Line shift recorded successfully.');
    }

    public function reconnections(Client $client)
    {
        $reconnections = $client->reconnections()->with('processor')->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:15);
        return view('admin.clients.reconnections', compact('client', 'reconnections'));
    }

    public function storeReconnection(Request $request, Client $client)
    {
        $validated = $request->validate([
            'disconnect_date' => 'required|date',
            'reconnect_date' => 'required|date|after_or_equal:disconnect_date',
            'reconnection_fee' => 'required|numeric|min:0',
            'disconnect_reason' => 'nullable|string',
            'remarks' => 'nullable|string'
        ]);

        $validated['client_id'] = $client->id;
        $validated['processed_by'] = auth()->id();

        ClientReconnection::create($validated);

        $client->status = 'active';
        $client->expire_date = now()->addMonthNoOverflow();
        $client->save();

        return redirect()->back()->with('success', 'Reconnection recorded successfully.');
    }

    public function packageRequests()
    {
        $requests = PackageChangeRequest::with(['client', 'currentPackage', 'requestedPackage', 'approver'])
            ->latest()
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.clients.package-requests', compact('requests'));
    }

    public function approvePackageRequest(Request $request, PackageChangeRequest $changeRequest)
    {
        $changeRequest->status = $request->action === 'approve' ? 'approved' : 'rejected';
        $changeRequest->admin_remarks = $request->remarks;
        $changeRequest->approved_by = auth()->id();
        $changeRequest->effective_date = $request->action === 'approve' ? now() : null;
        $changeRequest->save();

        $message = 'Package change request processed.';

        if ($request->action === 'approve') {
            $client = $changeRequest->client;
            $currentPackage = $changeRequest->currentPackage;
            $newPackage = $changeRequest->requestedPackage;
            
            // Check if this is an upgrade (new package costs more)
            $upgradeAmount = $newPackage->price - $currentPackage->price;
            
            if ($upgradeAmount > 0) {
                // Generate upgrade bill for the difference
                $bill = Bill::create([
                    'client_id' => $client->id,
                    'billing_month' => now()->month,
                    'billing_year' => now()->year,
                    'bill_date' => now(),
                    'due_date' => now()->addDays(7),
                    'package_amount' => $upgradeAmount,
                    'previous_due' => 0,
                    'discount' => 0,
                    'advance_used' => 0,
                    'total_amount' => $upgradeAmount,
                    'paid_amount' => 0,
                    'due_amount' => $upgradeAmount,
                    'status' => 'unpaid',
                    'remarks' => 'Package Upgrade: ' . $currentPackage->name . ' → ' . $newPackage->name,
                    'generated_by' => auth()->id(),
                ]);
                
                // Update client due amount
                $client->due_amount = ($client->due_amount ?? 0) + $upgradeAmount;
                
                $message = 'Package upgraded! Upgrade bill of ৳' . number_format($upgradeAmount) . ' generated.';
            }
            
            $client->package_id = $changeRequest->requested_package_id;
            $client->monthly_fee = $newPackage->price;
            $client->save();
        }

        return redirect()->back()->with('success', $message);
    }

    public function documents(Client $client)
    {
        $documents = $client->documents()->latest()->get();
        return view('admin.clients.documents', compact('client', 'documents'));
    }

    public function uploadDocument(Request $request, Client $client)
    {
        $request->validate([
            'document_type' => 'required|string',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        $file = $request->file('document');
        $path = $file->store('client-documents/' . $client->id, 'public');

        ClientDocument::create([
            'client_id' => $client->id,
            'document_type' => $request->document_type,
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $path
        ]);

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }

    public function deleteDocument(ClientDocument $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return redirect()->back()->with('success', 'Document deleted.');
    }

    public function ledger(Client $client)
    {
        $billingService = new BillingService();
        $ledger = $billingService->getClientLedger($client);
        return view('admin.clients.ledger', compact('client', 'ledger'));
    }

    public function statusHistory(Client $client)
    {
        $logs = $client->statusLogs()->with('changedBy')->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.clients.status-history', compact('client', 'logs'));
    }

    public function paymentHistory(Client $client)
    {
        $bills = $client->bills()
            ->with(['payments.paymentMethod', 'payments.collectedBy'])
            ->orderBy('bill_date', 'desc')
            ->get();
        
        $totalPaid = $client->payments()->sum('amount');
        $totalBills = $client->bills()->sum('total_amount');
        
        return view('admin.clients.payment-history', compact('client', 'bills', 'totalPaid', 'totalBills'));
    }
}
