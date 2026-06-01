<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Income;
use App\Models\Expense;
use App\Models\IncomeHead;
use App\Models\ExpenseHead;
use App\Models\Vendor;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\FundTransfer;
use App\Models\ClientServiceCharge;
use App\Models\StaffDue;
use App\Models\User;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\SalaryPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function cash(Request $request)
    {
        $dateFrom = $request->date_from;
        $dateTo   = $request->date_to;

        // ── Account rows ──────────────────────────────────────────────
        $accounts = Account::where('is_active', true)->orderBy('name')->get();

        $accountRows = $accounts->map(function ($account) use ($dateFrom, $dateTo) {
            $incomeQ = Income::where('account_id', $account->id);
            $expenseQ = Expense::where('account_id', $account->id)
                ->where(function ($q) { $q->whereNull('approval_status')->orWhere('approval_status', 'approved'); });
            $ftInQ   = FundTransfer::where('to_account_id', $account->id);
            $ftOutQ  = FundTransfer::where('from_account_id', $account->id);

            if ($dateFrom) {
                $incomeQ->whereDate('income_date', '>=', $dateFrom);
                $expenseQ->whereDate('expense_date', '>=', $dateFrom);
                $ftInQ->whereDate('transfer_date', '>=', $dateFrom);
                $ftOutQ->whereDate('transfer_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $incomeQ->whereDate('income_date', '<=', $dateTo);
                $expenseQ->whereDate('expense_date', '<=', $dateTo);
                $ftInQ->whereDate('transfer_date', '<=', $dateTo);
                $ftOutQ->whereDate('transfer_date', '<=', $dateTo);
            }

            $received = $incomeQ->sum('amount') + $ftInQ->sum('amount');
            $paid     = $expenseQ->sum('amount') + $ftOutQ->sum('amount');
            $opening  = (float) $account->opening_balance;
            $balance  = $opening + $received - $paid;

            return [
                'id'           => $account->id,
                'name'         => $account->name,
                'type'         => $account->type,
                'sub_info'     => null,
                'opening'      => $opening,
                'received'     => $received,
                'paid'         => $paid,
                'balance'      => $balance,
                'row_type'     => 'account',
            ];
        });

        // ── Staff / Technician Cash rows (from StaffDue) ─────────────
        $staffDues = StaffDue::with(['user'])
            ->where(function ($q) {
                $q->where('current_due', '!=', 0)->orWhere('total_collected', '>', 0);
            })
            ->get();

        $staffRows = $staffDues->map(function ($due) {
            $user     = $due->user;
            $employee = $user ? Employee::withoutGlobalScopes()->where('user_id', $user->id)->first() : null;
            $empId    = $employee->employee_id ?? null;
            $dept     = null;
            if ($employee && $employee->department_id) {
                $dept = \App\Models\Department::find($employee->department_id)?->name;
            }

            return [
                'id'           => $user?->id,
                'name'         => 'Cash-' . ($user?->name ?? 'Unknown'),
                'type'         => 'staff_cash',
                'sub_info'     => [
                    'name'   => $user?->name,
                    'emp_id' => $empId,
                    'dept'   => $dept,
                ],
                'opening'      => 0.0,
                'received'     => (float) $due->total_collected,
                'paid'         => (float) $due->total_submitted,
                'balance'      => (float) $due->current_due,
                'row_type'     => 'staff',
            ];
        });

        $rows = $accountRows->concat($staffRows)->values();

        $grandOpening  = $rows->sum('opening');
        $grandReceived = $rows->sum('received');
        $grandPaid     = $rows->sum('paid');
        $grandBalance  = $rows->sum('balance');

        return view('admin.accounts.cash', compact(
            'rows', 'grandOpening', 'grandReceived', 'grandPaid', 'grandBalance',
            'dateFrom', 'dateTo'
        ));
    }

    public function accountLedger(Account $account, Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');

        // Incomes
        $incomes = Income::with('incomeHead')
            ->where('account_id', $account->id)
            ->whereBetween('income_date', [$dateFrom, $dateTo])
            ->orderBy('income_date')
            ->get()
            ->map(fn($i) => [
                'date'        => $i->income_date,
                'description' => ($i->incomeHead?->name ?? 'Income') . ($i->description ? ': ' . $i->description : ''),
                'debit'       => 0,
                'credit'      => $i->amount,
                'type'        => 'income',
            ]);

        // Expenses
        $expenses = Expense::with('expenseHead')
            ->where('account_id', $account->id)
            ->where(function ($q) { $q->whereNull('approval_status')->orWhere('approval_status', 'approved'); })
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->orderBy('expense_date')
            ->get()
            ->map(fn($e) => [
                'date'        => $e->expense_date,
                'description' => ($e->expenseHead?->name ?? 'Expense') . ($e->description ? ': ' . $e->description : ''),
                'debit'       => $e->amount,
                'credit'      => 0,
                'type'        => 'expense',
            ]);

        // Fund Transfers In
        $ftIn = FundTransfer::where('to_account_id', $account->id)
            ->whereBetween('transfer_date', [$dateFrom, $dateTo])
            ->orderBy('transfer_date')
            ->get()
            ->map(fn($f) => [
                'date'        => $f->transfer_date,
                'description' => 'Fund Transfer In' . ($f->description ? ': ' . $f->description : ''),
                'debit'       => 0,
                'credit'      => $f->amount,
                'type'        => 'transfer_in',
            ]);

        // Fund Transfers Out
        $ftOut = FundTransfer::where('from_account_id', $account->id)
            ->whereBetween('transfer_date', [$dateFrom, $dateTo])
            ->orderBy('transfer_date')
            ->get()
            ->map(fn($f) => [
                'date'        => $f->transfer_date,
                'description' => 'Fund Transfer Out' . ($f->description ? ': ' . $f->description : ''),
                'debit'       => $f->amount,
                'credit'      => 0,
                'type'        => 'transfer_out',
            ]);

        // Merge + sort
        $transactions = $incomes->concat($expenses)->concat($ftIn)->concat($ftOut)
            ->sortBy('date')->values();

        // Running balance
        $runningBalance = (float) $account->opening_balance;
        $transactions = $transactions->map(function ($t) use (&$runningBalance) {
            $runningBalance += $t['credit'] - $t['debit'];
            $t['balance'] = $runningBalance;
            return $t;
        });

        $totalDebit  = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');
        $closingBal  = (float) $account->opening_balance + $totalCredit - $totalDebit;

        return view('admin.accounts.account-ledger', compact(
            'account', 'transactions', 'totalDebit', 'totalCredit', 'closingBal',
            'dateFrom', 'dateTo'
        ));
    }

    public function staffCashLedger(User $user, Request $request)
    {
        $staffDue = StaffDue::where('user_id', $user->id)->first();
        $employee = Employee::withoutGlobalScopes()->where('user_id', $user->id)->first();

        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');

        // Payments collected by this staff
        $paymentsCollected = Payment::with(['client', 'paymentMethod'])
            ->where('collected_by', $user->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date')
            ->get()
            ->map(fn($p) => [
                'date'        => $p->payment_date,
                'description' => 'Bill Payment — ' . ($p->client?->name ?? 'Unknown') . ' (' . ($p->paymentMethod?->name ?? 'Cash') . ')',
                'debit'       => 0,
                'credit'      => $p->amount,
                'type'        => 'collection',
            ]);

        // Submissions (from collection_transfers)
        $submissions = DB::table('collection_transfers')
            ->where('from_user_id', $user->id)
            ->where('status', 'verified')
            ->whereBetween('transfer_date', [$dateFrom, $dateTo])
            ->orderBy('transfer_date')
            ->get()
            ->map(fn($s) => [
                'date'        => $s->transfer_date,
                'description' => 'Submitted to Admin' . ($s->remarks ? ': ' . $s->remarks : ''),
                'debit'       => $s->amount,
                'credit'      => 0,
                'type'        => 'submission',
            ]);

        $transactions = collect($paymentsCollected)->concat($submissions)
            ->sortBy('date')->values();

        $runningBalance = 0.0;
        $transactions = $transactions->map(function ($t) use (&$runningBalance) {
            $runningBalance += $t['credit'] - $t['debit'];
            $t['balance'] = $runningBalance;
            return $t;
        });

        $totalCollected  = $transactions->sum('credit');
        $totalSubmitted  = $transactions->sum('debit');
        $currentDue      = $staffDue?->current_due ?? 0;

        return view('admin.accounts.staff-cash-ledger', compact(
            'user', 'employee', 'staffDue', 'transactions',
            'totalCollected', 'totalSubmitted', 'currentDue',
            'dateFrom', 'dateTo'
        ));
    }

    public function income(Request $request)
    {
        $query = Income::with(['incomeHead', 'account']);
        if ($request->date_from) {
            $query->whereDate('income_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('income_date', '<=', $request->date_to);
        }
        $incomes = $query->orderBy('income_date', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $incomeHeads = IncomeHead::where('is_active', true)->get();
        $accounts = Account::where('is_active', true)->get();
        return view('admin.accounts.income', compact('incomes', 'incomeHeads', 'accounts'));
    }

    public function storeIncome(Request $request)
    {
        $request->validate([
            'income_head_id' => 'required|exists:income_heads,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'income_date' => 'required|date',
        ]);

        DB::transaction(function () use ($request) {
            Income::create([
                'income_head_id' => $request->income_head_id,
                'account_id' => $request->account_id,
                'amount' => $request->amount,
                'income_date' => $request->income_date,
                'description' => $request->description,
                'reference' => $request->reference,
                'created_by' => auth()->id(),
            ]);

            $account = Account::lockForUpdate()->find($request->account_id);
            $account->current_balance += $request->amount;
            $account->save();
        });

        return back()->with('success', 'Income recorded successfully.');
    }

    public function updateIncome(Request $request, Income $income)
    {
        $request->validate([
            'income_head_id' => 'required|exists:income_heads,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'income_date' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $income) {
            $oldAmount = $income->amount;
            $oldAccountId = $income->account_id;
            
            if ($oldAccountId != $request->account_id) {
                $oldAccount = Account::lockForUpdate()->find($oldAccountId);
                $oldAccount->current_balance -= $oldAmount;
                $oldAccount->save();
                
                $newAccount = Account::lockForUpdate()->find($request->account_id);
                $newAccount->current_balance += $request->amount;
                $newAccount->save();
            } else {
                $account = Account::lockForUpdate()->find($request->account_id);
                $account->current_balance = $account->current_balance - $oldAmount + $request->amount;
                $account->save();
            }

            $income->update([
                'income_head_id' => $request->income_head_id,
                'account_id' => $request->account_id,
                'amount' => $request->amount,
                'income_date' => $request->income_date,
                'description' => $request->description,
                'reference' => $request->reference,
            ]);
        });

        return back()->with('success', 'Income updated successfully.');
    }

    public function destroyIncome(Income $income)
    {
        DB::transaction(function () use ($income) {
            $account = Account::lockForUpdate()->find($income->account_id);
            $account->current_balance -= $income->amount;
            $account->save();
            
            $income->delete();
        });
        return back()->with('success', 'Income deleted successfully.');
    }

    public function expense(Request $request)
    {
        $query = Expense::with(['expenseHead', 'account']);
        if ($request->date_from) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }
        $expenses = $query->orderBy('expense_date', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $expenseHeads = ExpenseHead::where('is_active', true)->get();
        $accounts = Account::where('is_active', true)->get();
        return view('admin.accounts.expense', compact('expenses', 'expenseHeads', 'accounts'));
    }

    public function installationCharges(Request $request)
    {
        $query = ClientServiceCharge::with(['client', 'paidByUser'])
            ->where('charge_type', 'Installation Charge')
            ->whereNull('paid_by') // base (unpaid/due) records — one per client
            ->whereHas('client', fn($q) => $q->whereNull('reseller_id'));

        if ($request->date_from) {
            $query->whereDate('charge_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('charge_date', '<=', $request->date_to);
        }
        if ($request->search) {
            $query->whereHas('client', function ($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('client_id', 'ilike', '%' . $request->search . '%');
            });
        }
        // Client IDs that have at least one payment record
        $partiallyPaidClientIds = ClientServiceCharge::where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')
            ->where('amount', '>', 0)
            ->pluck('client_id')
            ->toArray();

        if ($request->status === 'paid') {
            $query->where('amount', '>', 0)->whereIn('client_id', function($sub){
                $sub->select('id')->from('clients')->where('signup_fee_paid', true);
            });
        } elseif ($request->status === 'partial') {
            // Partial: has payment but signup_fee_paid is still false (not fully paid)
            $query->where('amount', '>', 0)
                ->whereIn('client_id', function($sub){
                    $sub->select('id')->from('clients')->where('signup_fee_paid', false);
                })
                ->whereIn('client_id', $partiallyPaidClientIds ?: [0]);
        } elseif ($request->status === 'due') {
            // Due: no payment at all
            $query->where('amount', '>', 0)
                ->whereIn('client_id', function($sub){
                    $sub->select('id')->from('clients')->where('signup_fee_paid', false);
                })
                ->whereNotIn('client_id', $partiallyPaidClientIds ?: [0]);
        } elseif ($request->status === 'free') {
            $query->where(function($q){ $q->where('amount', '<=', 0)->orWhereNull('amount'); });
        }

        $charges = $query->orderBy('charge_date', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25);

        // totals
        $totalAmount  = ClientServiceCharge::where('charge_type','Installation Charge')->whereNull('paid_by')->where('amount','>',0)->whereHas('client', fn($q) => $q->whereNull('reseller_id'))->sum('amount');
        $totalPaid    = ClientServiceCharge::where('charge_type','Installation Charge')->whereNotNull('paid_by')->where('amount','>',0)->whereHas('client', fn($q) => $q->whereNull('reseller_id'))->sum('amount');
        $totalDue     = max(0, $totalAmount - $totalPaid);
        $total        = $totalAmount;

        $paymentMethods = auth()->user()->allowedPaymentMethods();
        $paymentReasons = \App\Models\DropdownOption::getOptions('payment_reasons');

        return view('admin.accounts.installation-charges', compact('charges', 'total', 'totalPaid', 'totalDue', 'paymentMethods', 'paymentReasons'));
    }

    public function updateInstallationCharge(Request $request, ClientServiceCharge $charge)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0',
            'charge_date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $oldAmount = (float) $charge->amount;
        $newAmount = (float) $request->amount;

        $charge->update([
            'amount'      => $newAmount,
            'charge_date' => $request->charge_date,
            'description' => $request->description,
        ]);

        // Sync client fields when charge amount changes
        if ($charge->client_id && $newAmount !== $oldAmount) {
            $client = $charge->client;
            if ($client) {
                $isFree = $newAmount <= 0;

                $client->update([
                    'installation_charge' => $newAmount,
                    'signup_fee'          => $newAmount,
                    'is_free'             => $isFree,
                ]);

                // Recalculate signup_fee_paid
                $totalPaid = (float) ClientServiceCharge::where('client_id', $client->id)
                    ->where('charge_type', 'Installation Charge')
                    ->whereNotNull('paid_by')
                    ->where('amount', '>', 0)
                    ->sum('amount');

                $client->update(['signup_fee_paid' => !!$newAmount && $totalPaid >= $newAmount]);
            }
        }

        return redirect()
            ->route('admin.sales.installation-charges', request()->query())
            ->with('success', 'Installation charge updated successfully.');
    }

    public function destroyInstallationCharge(ClientServiceCharge $charge)
    {
        $client = $charge->client;

        // Delete base record and all payment records for this client's installation charge
        if ($client) {
            ClientServiceCharge::where('client_id', $client->id)
                ->where('charge_type', 'Installation Charge')
                ->delete();

            $client->update([
                'installation_charge' => 0,
                'signup_fee'          => 0,
                'signup_fee_paid'     => false,
            ]);
        } else {
            $charge->delete();
        }

        return redirect()
            ->route('admin.sales.installation-charges', request()->query())
            ->with('success', 'Installation charge record deleted successfully.');
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'expense_head_id' => 'required|exists:expense_heads,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
        ]);

        DB::transaction(function () use ($request) {
            Expense::create([
                'expense_head_id' => $request->expense_head_id,
                'account_id' => $request->account_id,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'description' => $request->description,
                'reference' => $request->reference,
                'created_by' => auth()->id(),
            ]);

            $account = Account::lockForUpdate()->find($request->account_id);
            $account->current_balance -= $request->amount;
            $account->save();
        });

        try {
            $expenseHead = ExpenseHead::find($request->expense_head_id);
            $accountName = Account::find($request->account_id)->name ?? '';
            (new \App\Services\TelegramService())->sendExpenseNotification(
                $expenseHead->name ?? 'Unknown',
                (float) $request->amount,
                auth()->user()->name ?? 'Unknown',
                $request->description,
                $accountName,
                $request->expense_date ?? '',
                $request->reference ?? ''
            );
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'Expense recorded successfully.');
    }

    public function updateExpense(Request $request, Expense $expense)
    {
        $request->validate([
            'expense_head_id' => 'required|exists:expense_heads,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $expense) {
            $oldAmount = $expense->amount;
            $oldAccountId = $expense->account_id;
            
            if ($oldAccountId != $request->account_id) {
                $oldAccount = Account::lockForUpdate()->find($oldAccountId);
                $oldAccount->current_balance += $oldAmount;
                $oldAccount->save();
                
                $newAccount = Account::lockForUpdate()->find($request->account_id);
                $newAccount->current_balance -= $request->amount;
                $newAccount->save();
            } else {
                $account = Account::lockForUpdate()->find($request->account_id);
                $account->current_balance = $account->current_balance + $oldAmount - $request->amount;
                $account->save();
            }

            $expense->update([
                'expense_head_id' => $request->expense_head_id,
                'account_id' => $request->account_id,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'description' => $request->description,
                'reference' => $request->reference,
            ]);
        });

        return back()->with('success', 'Expense updated successfully.');
    }

    public function destroyExpense(Expense $expense)
    {
        DB::transaction(function () use ($expense) {
            $account = Account::lockForUpdate()->find($expense->account_id);
            $account->current_balance += $expense->amount;
            $account->save();
            
            $expense->delete();
        });
        return back()->with('success', 'Expense deleted successfully.');
    }

    public function banks()
    {
        $accounts = Account::where('type', 'bank')->get();
        return view('admin.accounts.banks', compact('accounts'));
    }
    
    public function expenseApproval(Request $request)
    {
        $query = Expense::with(['expenseHead', 'createdBy']);
        if ($request->status) {
            $query->where('approval_status', $request->status);
        } else {
            $query->where('approval_status', 'pending');
        }
        
        $expenses = $query->orderBy('expense_date', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $pendingCount = Expense::where('approval_status', 'pending')->count();
        $approvedToday = Expense::where('approval_status', 'approved')->whereDate('updated_at', today())->count();
        $rejectedToday = Expense::where('approval_status', 'rejected')->whereDate('updated_at', today())->count();
        $pendingAmount = Expense::where('approval_status', 'pending')->sum('amount');
        
        return view('admin.accounts.expense-approval', compact('expenses', 'pendingCount', 'approvedToday', 'rejectedToday', 'pendingAmount'));
    }
    
    public function approveExpense(Request $request, Expense $expense)
    {
        if ($expense->approval_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Expense already processed']);
        }
        
        DB::transaction(function () use ($expense) {
            $expense->update([
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            if ($expense->account_id) {
                $account = Account::lockForUpdate()->find($expense->account_id);
                $account->current_balance -= $expense->amount;
                $account->save();
            }
        });
        
        return response()->json(['success' => true, 'message' => 'Expense approved successfully']);
    }
    
    public function rejectExpense(Request $request, Expense $expense)
    {
        if ($expense->approval_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Expense already processed']);
        }
        
        $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);
        
        $expense->update([
            'approval_status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'description' => ($expense->description ? $expense->description . ' | ' : '') . 'Rejection reason: ' . $request->reason,
        ]);
        
        return response()->json(['success' => true, 'message' => 'Expense rejected successfully']);
    }
    
    public function fundTransfer()
    {
        $transfers = FundTransfer::with(['fromAccount', 'toAccount', 'createdBy'])->orderBy('transfer_date', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $accounts = Account::where('is_active', true)->get();
        return view('admin.accounts.fund-transfer', compact('transfers', 'accounts'));
    }
    
    public function storeFundTransfer(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
        ]);
        
        try {
            DB::transaction(function () use ($request) {
                $fromAccount = Account::lockForUpdate()->find($request->from_account_id);
                if ($fromAccount->current_balance < $request->amount) {
                    throw new \Exception('Insufficient balance in source account.');
                }
                
                FundTransfer::create([
                    'from_account_id' => $request->from_account_id,
                    'to_account_id' => $request->to_account_id,
                    'amount' => $request->amount,
                    'transfer_date' => $request->transfer_date,
                    'description' => $request->description,
                    'created_by' => auth()->id(),
                ]);
                
                $fromAccount->current_balance -= $request->amount;
                $fromAccount->save();
                
                $toAccount = Account::lockForUpdate()->find($request->to_account_id);
                $toAccount->current_balance += $request->amount;
                $toAccount->save();
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
        
        return back()->with('success', 'Fund transfer completed successfully.');
    }
    
    public function vendorLedger(Request $request)
    {
        $vendors = Vendor::withSum('payments', 'debit')->withSum('purchases', 'total')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.accounts.vendor-ledger', compact('vendors'));
    }
    
    public function loan(Request $request)
    {
        $query = Loan::with(['account', 'creator']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->where('status', '!=', 'completed')->whereNotNull('due_date')->where('due_date', '<', now());
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('party_name', 'ilike', "%{$search}%")
                  ->orWhere('party_phone', 'ilike', "%{$search}%")
                  ->orWhere('reference_no', 'ilike', "%{$search}%");
            });
        }

        $loans = $query->orderBy('loan_date', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20)->withQueryString();
        $accounts = Account::where('is_active', true)->get();
        $employees = \App\Models\Employee::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $totalGiven = Loan::where('type', 'given')->sum('amount');
        $totalReceived = Loan::where('type', 'received')->sum('amount');
        $outstandingGiven = Loan::where('type', 'given')->where('status', '!=', 'completed')->sum('remaining_amount');
        $outstandingReceived = Loan::where('type', 'received')->where('status', '!=', 'completed')->sum('remaining_amount');
        $overdueCount = Loan::where('status', '!=', 'completed')->whereNotNull('due_date')->where('due_date', '<', now())->count();

        $summary = compact('totalGiven', 'totalReceived', 'outstandingGiven', 'outstandingReceived', 'overdueCount');

        return view('admin.accounts.loan', compact('loans', 'accounts', 'summary', 'employees'));
    }

    public function storeLoan(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:received,given',
            'loan_date' => 'required|date',
            'party_name' => 'required|string|max:255',
            'party_phone' => 'nullable|string|max:20',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $account = Account::lockForUpdate()->find($request->account_id);
                if ($request->type === 'given' && $account->current_balance < $request->amount) {
                    throw new \Exception('Insufficient balance to give loan.');
                }
                $refNo = 'LOAN-' . date('Ymd') . '-' . str_pad(Loan::count() + 1, 4, '0', STR_PAD_LEFT);

                Loan::create([
                    'reference_no' => $refNo,
                    'account_id' => $request->account_id,
                    'amount' => $request->amount,
                    'remaining_amount' => $request->amount,
                    'type' => $request->type,
                    'loan_date' => $request->loan_date,
                    'due_date' => $request->due_date,
                    'party_name' => $request->party_name,
                    'party_phone' => $request->party_phone,
                    'description' => $request->description,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                    'company_id' => auth()->user()->company_id,
                ]);

                if ($request->type === 'received') {
                    $account->current_balance += $request->amount;
                } else {
                    $account->current_balance -= $request->amount;
                }
                $account->save();
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Loan recorded successfully.');
    }

    public function showLoan(Loan $loan)
    {
        $loan->load(['account', 'creator', 'payments' => function ($q) {
            $q->with(['account', 'creator'])->orderBy('payment_date', 'desc');
        }]);
        $accounts = Account::where('is_active', true)->get();
        $paidPercent = $loan->amount > 0 ? round(($loan->paid_amount / $loan->amount) * 100, 1) : 0;
        $isOverdue = $loan->due_date && $loan->due_date->isPast() && $loan->status !== 'completed';

        return view('admin.accounts.loan-detail', compact('loan', 'accounts', 'paidPercent', 'isOverdue'));
    }

    public function addLoanPayment(Request $request, Loan $loan)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $loan->remaining_amount,
            'payment_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($request, $loan) {
                $account = Account::lockForUpdate()->find($request->account_id);

                LoanPayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $request->amount,
                    'payment_date' => $request->payment_date,
                    'account_id' => $request->account_id,
                    'remarks' => $request->remarks,
                    'created_by' => auth()->id(),
                ]);

                if ($loan->type === 'received') {
                    $account->current_balance -= $request->amount;
                } else {
                    $account->current_balance += $request->amount;
                }
                $account->save();

                $loan->updatePaymentStatus();
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment of ৳' . number_format($request->amount, 2) . ' recorded successfully.');
    }

    public function editLoan(Loan $loan)
    {
        $accounts = Account::where('is_active', true)->get();
        return view('admin.accounts.loan-edit', compact('loan', 'accounts'));
    }

    public function updateLoan(Request $request, Loan $loan)
    {
        $rules = [
            'party_name' => 'required|string|max:255',
            'party_phone' => 'nullable|string|max:20',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
        ];

        if ($loan->paid_amount <= 0) {
            $rules['amount'] = 'required|numeric|min:0.01';
            $rules['account_id'] = 'required|exists:accounts,id';
            $rules['type'] = 'required|in:received,given';
            $rules['loan_date'] = 'required|date';
        }

        $request->validate($rules);

        try {
            DB::transaction(function () use ($request, $loan) {
                $data = [
                    'party_name' => $request->party_name,
                    'party_phone' => $request->party_phone,
                    'due_date' => $request->due_date,
                    'description' => $request->description,
                ];

                if ($loan->paid_amount <= 0) {
                    $oldAccount = Account::lockForUpdate()->find($loan->account_id);
                    if ($loan->type === 'received') {
                        $oldAccount->current_balance -= $loan->amount;
                    } else {
                        $oldAccount->current_balance += $loan->amount;
                    }
                    $oldAccount->save();

                    $newAccount = Account::lockForUpdate()->find($request->account_id);
                    if ($request->type === 'given' && $newAccount->current_balance < $request->amount) {
                        throw new \Exception('Insufficient balance to give loan.');
                    }
                    if ($request->type === 'received') {
                        $newAccount->current_balance += $request->amount;
                    } else {
                        $newAccount->current_balance -= $request->amount;
                    }
                    $newAccount->save();

                    $data['amount'] = $request->amount;
                    $data['remaining_amount'] = $request->amount;
                    $data['account_id'] = $request->account_id;
                    $data['type'] = $request->type;
                    $data['loan_date'] = $request->loan_date;
                }

                $loan->update($data);
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.accounts.loan.show', $loan)->with('success', 'Loan updated successfully.');
    }

    public function deleteLoan(Loan $loan)
    {
        if ($loan->paid_amount > 0) {
            return back()->with('error', 'Cannot delete a loan that has payments. Remove all payments first.');
        }

        try {
            DB::transaction(function () use ($loan) {
                $account = Account::lockForUpdate()->find($loan->account_id);
                if ($loan->type === 'received') {
                    $account->current_balance -= $loan->amount;
                } else {
                    $account->current_balance += $loan->amount;
                }
                $account->save();
                $loan->delete();
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.accounts.loan')->with('success', 'Loan deleted successfully.');
    }

    public function deleteLoanPayment(Loan $loan, LoanPayment $payment)
    {
        if ($payment->loan_id !== $loan->id) {
            return back()->with('error', 'Payment does not belong to this loan.');
        }

        try {
            DB::transaction(function () use ($loan, $payment) {
                $account = Account::lockForUpdate()->find($payment->account_id);
                if ($loan->type === 'received') {
                    $account->current_balance += $payment->amount;
                } else {
                    $account->current_balance -= $payment->amount;
                }
                $account->save();

                $payment->delete();
                $loan->updatePaymentStatus();
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment removed successfully.');
    }

    public function loanStatement(Loan $loan)
    {
        $loan->load(['account', 'creator', 'payments' => function ($q) {
            $q->with(['account', 'creator'])->orderBy('payment_date', 'asc');
        }]);

        $runningBalance = $loan->amount;
        $paymentsWithBalance = $loan->payments->map(function ($payment) use (&$runningBalance) {
            $runningBalance -= $payment->amount;
            $payment->running_balance = $runningBalance;
            return $payment;
        });

        $company = auth()->user()->company ?? null;

        return view('admin.accounts.loan-statement', compact('loan', 'paymentsWithBalance', 'company'));
    }
}
