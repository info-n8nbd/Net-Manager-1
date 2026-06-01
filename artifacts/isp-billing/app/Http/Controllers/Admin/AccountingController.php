<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditDebitNote;
use App\Models\MonthClose;
use App\Services\CreditDebitNoteService;
use App\Services\MonthCloseService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    protected MonthCloseService $monthCloseService;
    protected CreditDebitNoteService $noteService;

    public function __construct(MonthCloseService $monthCloseService, CreditDebitNoteService $noteService)
    {
        $this->monthCloseService = $monthCloseService;
        $this->noteService = $noteService;
    }

    public function monthClose(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $status = $this->monthCloseService->getMonthStatus($month, $year);
        $allMonths = MonthClose::orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->limit(24)
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'status' => $status,
                'months' => $allMonths,
            ]);
        }

        return view('admin.accounting.month-close', compact('status', 'allMonths', 'month', 'year'));
    }

    public function closeMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $monthClose = $this->monthCloseService->closeMonth(
                $request->month,
                $request->year,
                auth()->id(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Month closed successfully',
                'data' => $monthClose,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function lockMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        try {
            $monthClose = $this->monthCloseService->lockMonth(
                $request->month,
                $request->year,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Month locked successfully',
                'data' => $monthClose,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function reopenMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $monthClose = $this->monthCloseService->reopenMonth(
                $request->month,
                $request->year,
                auth()->id(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Month reopened successfully',
                'data' => $monthClose,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function creditDebitNotes(Request $request)
    {
        $query = CreditDebitNote::with(['client', 'createdBy', 'approvedBy']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $notes = $query->orderBy('created_at', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $pendingCount = CreditDebitNote::where('status', 'pending')->count();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'notes' => $notes,
                'pending_count' => $pendingCount,
            ]);
        }

        return view('admin.accounting.credit-debit-notes', compact('notes', 'pendingCount'));
    }

    public function createNote(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'billing_month' => 'required|integer|min:1|max:12',
            'billing_year' => 'required|integer|min:2020|max:2100',
        ]);

        try {
            $note = $this->noteService->createNote(
                $request->client_id,
                $request->type,
                $request->amount,
                $request->reason,
                $request->description,
                $request->billing_month,
                $request->billing_year,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => ucfirst($request->type) . ' note created successfully',
                'data' => $note,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function approveNote(Request $request, $id)
    {
        try {
            $note = $this->noteService->approveNote($id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Note approved and applied to client balance',
                'data' => $note,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function rejectNote(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $note = $this->noteService->rejectNote($id, auth()->id(), $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Note rejected',
                'data' => $note,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
