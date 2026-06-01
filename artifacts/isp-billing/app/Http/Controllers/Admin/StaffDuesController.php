<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffDue;
use App\Models\StaffSubmission;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffDuesController extends Controller
{
    public function index()
    {
        $staffDues = StaffDue::with('user')
            ->where('current_due', '>', 0)
            ->orderByDesc('current_due')
            ->get();
        
        $allStaffDues = StaffDue::with('user')
            ->orderByDesc('total_collected')
            ->get();
        
        $recentSubmissions = StaffSubmission::with(['staff', 'receiver'])
            ->latest()
            ->limit(10)
            ->get();

        $pendingSubmissions = StaffSubmission::with('staff')
            ->where('status', 'pending')
            ->latest()
            ->get();
        
        $totalPendingDues = StaffDue::sum('current_due');
        $totalCollected = StaffDue::sum('total_collected');
        $totalSubmitted = StaffDue::sum('total_submitted');
        
        return view('admin.staff-dues.index', compact(
            'staffDues', 'allStaffDues', 'recentSubmissions',
            'pendingSubmissions',
            'totalPendingDues', 'totalCollected', 'totalSubmitted'
        ));
    }

    public function show(User $user)
    {
        $staffDue = StaffDue::where('user_id', $user->id)->first();
        
        $unsubmittedPayments = Payment::with('client')
            ->where('collected_by', $user->id)
            ->where('submitted_to_admin', false)
            ->orderByDesc('created_at')
            ->get();
        
        $submissions = StaffSubmission::where('staff_user_id', $user->id)
            ->with('receiver')
            ->latest()
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        
        return view('admin.staff-dues.show', compact('user', 'staffDue', 'unsubmittedPayments', 'submissions'));
    }

    public function receivePayment(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'remarks' => 'nullable|string',
        ]);
        
        $staffDue = StaffDue::where('user_id', $user->id)->first();
        
        if (!$staffDue) {
            return back()->with('error', 'No collection record found for this staff.');
        }

        $submissionAmount = $request->amount;
        
        DB::beginTransaction();
        try {
            $submission = StaffSubmission::create([
                'staff_user_id' => $user->id,
                'amount' => $submissionAmount,
                'submission_date' => now()->toDateString(),
                'payment_method' => $request->payment_method,
                'remarks' => $request->remarks,
                'status' => 'received',
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);
            
            $markedAmount = 0;
            $unsubmittedPayments = Payment::where('collected_by', $user->id)
                ->where('submitted_to_admin', false)
                ->orderBy('created_at')
                ->get();
            
            foreach ($unsubmittedPayments as $payment) {
                if ($markedAmount + $payment->amount <= $submissionAmount) {
                    $payment->submitted_to_admin = true;
                    $payment->submitted_at = now();
                    $payment->submission_id = $submission->id;
                    $payment->save();
                    $markedAmount += $payment->amount;
                }
                if ($markedAmount >= $submissionAmount) break;
            }
            
            // Update staff dues based on the ACTUAL amount received (the full $submissionAmount)
            // even if it exceeds the sum of unsubmitted payments (to handle corrections/overpayments)
            $staffDue->current_due = max(0, $staffDue->current_due - $submissionAmount);
            $staffDue->total_submitted += $submissionAmount;
            $staffDue->last_submission_date = now();
            $staffDue->save();
            
            DB::commit();
            
            $dateTime = now()->format('d-m-y h:i:s A') . ' (BDT GMT+6)';
            $message = "৳" . number_format($submissionAmount, 2) . " received from {$user->name} on {$dateTime}. Dues updated.";
            
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing submission: ' . $e->getMessage());
        }
    }

    public function clearAllDues(Request $request, User $user)
    {
        $staffDue = StaffDue::where('user_id', $user->id)->first();
        
        $unsubmittedPayments = Payment::where('collected_by', $user->id)
            ->where('submitted_to_admin', false)
            ->get();
        
        $actualDue = $unsubmittedPayments->sum('amount');
        
        // Use staff due current_due if no unsubmitted payments but there is a recorded due
        $amountToClear = $actualDue > 0 ? $actualDue : ($staffDue ? $staffDue->current_due : 0);
        
        if ($amountToClear <= 0) {
            return back()->with('error', 'No dues to clear.');
        }
        
        DB::beginTransaction();
        try {
            $submission = StaffSubmission::create([
                'staff_user_id' => $user->id,
                'amount' => $amountToClear,
                'submission_date' => now()->toDateString(),
                'payment_method' => $request->input('payment_method', 'cash'),
                'remarks' => 'Full dues received by admin',
                'status' => 'received',
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);
            
            // Mark any unsubmitted payments as submitted
            foreach ($unsubmittedPayments as $payment) {
                $payment->submitted_to_admin = true;
                $payment->submitted_at = now();
                $payment->submission_id = $submission->id;
                $payment->save();
            }
            
            // Update staff due record
            if ($staffDue) {
                $staffDue->total_submitted += $amountToClear;
                $staffDue->current_due = 0;
                $staffDue->last_submission_date = now();
                $staffDue->save();
            }
            
            DB::commit();
            
            $dateTime = now()->format('d-m-y h:i:s A') . ' (BDT GMT+6)';
            return back()->with('success', "All dues (৳" . number_format($amountToClear, 2) . ") cleared for {$user->name} on {$dateTime}.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error clearing dues: ' . $e->getMessage());
        }
    }

    public function approveSubmission(Request $request, \App\Models\StaffSubmission $submission)
    {
        if ($submission->status !== 'pending') {
            return back()->with('error', 'This submission is already ' . $submission->status . '.');
        }

        DB::beginTransaction();
        try {
            $submission->status = 'received';
            $submission->received_by = auth()->id();
            $submission->received_at = now();
            $submission->payment_method = $request->input('payment_method', 'cash');
            $submission->save();

            // NOW decrement current_due and increment total_submitted
            $staffDue = StaffDue::where('user_id', $submission->staff_user_id)->first();
            if ($staffDue) {
                $staffDue->current_due = max(0, $staffDue->current_due - $submission->amount);
                $staffDue->total_submitted += $submission->amount;
                $staffDue->last_submission_date = now();
                $staffDue->save();
            }

            DB::commit();
            $dateTime = now()->format('d-m-y h:i:s A') . ' (BDT)';
            return back()->with('success', '৳' . number_format($submission->amount, 0) . ' approved from ' . ($submission->staff->name ?? 'Staff') . ' on ' . $dateTime);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error approving: ' . $e->getMessage());
        }
    }


    // ── My Collections (for employee/admin users who collect cash) ────────────

    public function myCollections(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $startDate = $request->start_date ?? today()->startOfMonth()->format('Y-m-d');
        $endDate   = $request->end_date   ?? today()->format('Y-m-d');

        $payments = \App\Models\Payment::with(['client', 'paymentMethod'])
            ->where('collected_by', $user->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->get();

        $staffDue = StaffDue::where('user_id', $user->id)->first();

        $pendingAmount = \App\Models\StaffSubmission::where('staff_user_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');

        $submissions = \App\Models\StaffSubmission::where('staff_user_id', $user->id)
            ->orderBy('submission_date', 'desc')
            ->limit(10)
            ->get();

        $unsubmittedTotal = \App\Models\Payment::where('collected_by', $user->id)
            ->where(function($q) { $q->whereNull('submitted_to_admin')->orWhere('submitted_to_admin', false); })
            ->where('status', 'approved')
            ->sum('amount');

        return view('admin.my-collections.index', compact(
            'payments', 'staffDue', 'pendingAmount', 'submissions',
            'unsubmittedTotal', 'startDate', 'endDate'
        ));
    }

    public function mySubmitToAdmin(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'amount'  => 'required|numeric|min:1',
            'remarks' => 'nullable|string|max:500',
        ]);

        $user     = auth()->user();
        $staffDue = StaffDue::where('user_id', $user->id)->first();

        if (!$staffDue || $staffDue->current_due <= 0) {
            return back()->with('error', 'You have no pending collection to submit.');
        }

        $existingPending = \App\Models\StaffSubmission::where('staff_user_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');

        $available = $staffDue->current_due - $existingPending;
        if ($request->amount > $available + 0.01) {
            return back()->with('error',
                'Available to submit: ৳' . number_format($available, 0) .
                (($existingPending > 0) ? ' (৳' . number_format($existingPending, 0) . ' already pending approval)' : '')
            );
        }

        \App\Models\StaffSubmission::create([
            'staff_user_id'   => $user->id,
            'amount'          => $request->amount,
            'remarks'         => $request->remarks,
            'submission_date' => now(),
            'status'          => 'pending',
        ]);

        return back()->with('success',
            '৳' . number_format($request->amount, 0) . ' submitted to admin. Waiting for approval.'
        );
    }

}
