<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\ExpensesNew\Entities\Setting;
use Modules\ExpensesNew\Entities\Category;
use Modules\ExpensesNew\Entities\Expense;
use Modules\ExpensesNew\Entities\ExpenseAccount;
use Modules\ExpensesNew\Entities\ExpenseAttachment;
use Modules\ExpensesNew\Entities\Payee;
use Modules\ExpensesNew\Http\Requests\ExpenseRequest;
use Modules\ExpensesNew\Services\AccountingModuleService;
use Modules\ExpensesNew\Services\ExpenseAttachmentService;
use Modules\ExpensesNew\Services\ExpensePostingService;
use Modules\ExpensesNew\Services\FinanceAccountBookPostingService;
use Modules\ExpensesNew\Services\NumberingService;
use Modules\ExpensesNew\Services\OptionService;
use Modules\ExpensesNew\Services\PayeeSyncService;
use Modules\ExpensesNew\Utils\BusinessScope;

class ExpenseController extends Controller
{
    public function index(OptionService $options)
    {
        $businessId = BusinessScope::businessId();

        $initialExpenses = $this->expenseListQuery($businessId)
            ->select($this->expenseListColumns())
            ->orderByDesc('expenses.expense_date')
            ->orderByDesc('expenses.id')
            ->limit(200)
            ->get();

        return view('expensesnew::expenses.index', [
            'categories' => $options->categories($businessId),
            'payees' => $options->payees($businessId),
            'locations' => $options->locations($businessId),
            // Server-rendered fallback guarantees a newly saved expense is
            // visible even when the host layout does not load DataTables.
            'initialExpenses' => $initialExpenses,
            'currencyPrecision' => $this->currencyPrecision(),
        ]);
    }

    public function create(OptionService $options, AccountingModuleService $accountingModules)
    {
        $businessId = BusinessScope::businessId();

        return view(
            'expensesnew::expenses.form',
            $this->formData(new Expense(), $options, $accountingModules, $businessId)
        );
    }

    public function store(
        ExpenseRequest $request,
        ExpensePostingService $posting,
        ExpenseAttachmentService $attachments,
        NumberingService $numbers,
        AccountingModuleService $accountingModules
    ) {
        $businessId = BusinessScope::businessId();
        $validated = $this->prepareExpenseFormData($request->validated());
        $validated = $this->applyCategoryDefaults(
            $validated,
            $businessId,
            $accountingModules
        );

        $data = $validated + [
            'business_id' => $businessId,
            'expense_no' => $numbers->nextExpenseNo($businessId),
            'created_by' => auth()->id(),
        ];

        $expense = $posting->create($data);
        $attachments->storeMany($expense, $request->file('attachments', []));

        return redirect()
            ->route('expensesnew.expenses.index')
            ->with('status', 'Expense saved successfully');
    }

    public function edit($id, OptionService $options, AccountingModuleService $accountingModules)
    {
        $businessId = BusinessScope::businessId();
        $expense = Expense::with(['payments', 'attachments'])
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return view(
            'expensesnew::expenses.form',
            $this->formData($expense, $options, $accountingModules, $businessId)
        );
    }

    public function update(
        ExpenseRequest $request,
        $id,
        ExpensePostingService $posting,
        ExpenseAttachmentService $attachments,
        AccountingModuleService $accountingModules
    ) {
        $businessId = BusinessScope::businessId();
        $expense = Expense::where('business_id', $businessId)->findOrFail($id);
        $validated = $this->prepareExpenseFormData($request->validated(), $expense);
        $validated = $this->applyCategoryDefaults(
            $validated,
            $businessId,
            $accountingModules,
            $expense
        );

        $posting->update($expense, $validated + ['updated_by' => auth()->id()]);
        $attachments->storeMany($expense, $request->file('attachments', []));

        return redirect()
            ->route('expensesnew.expenses.index')
            ->with('status', 'Expense updated successfully');
    }

    public function destroy($id, FinanceAccountBookPostingService $accountBooks)
    {
        $businessId = BusinessScope::businessId();
        $expense = Expense::where('business_id', $businessId)->findOrFail($id);

        DB::transaction(function () use ($expense, $accountBooks): void {
            $accountBooks->remove($expense);
            $expense->delete();
        });

        return response()->json(['success' => true]);
    }

    public function destroyAttachment($id, ExpenseAttachmentService $service)
    {
        $businessId = BusinessScope::businessId();
        $attachment = ExpenseAttachment::where('business_id', $businessId)->findOrFail($id);
        $service->delete($attachment);

        return back()->with('status', 'Attachment removed');
    }

    public function show($id)
    {
        $businessId = BusinessScope::businessId();
        $expense = Expense::with(['category', 'payee', 'account', 'payments', 'attachments'])
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return view('expensesnew::expenses.show', compact('expense'));
    }

    public function print($id)
    {
        $businessId = BusinessScope::businessId();
        $expense = Expense::with(['category', 'payee', 'account', 'payments'])
            ->where('business_id', $businessId)
            ->findOrFail($id);

        /*
         * MA-002 (S-621): the two Expense Voucher print options.
         *
         * Both default to TRUE when the setting has never been saved, matching
         * what the voucher did before this change.
         */
        $printSettings = Setting::query()
            ->where('business_id', BusinessScope::businessId())
            ->whereIn('key', ['print_show_expense_note', 'print_show_payment_note'])
            ->pluck('value', 'key');

        $showExpenseNote = ! $printSettings->has('print_show_expense_note')
            || (int) $printSettings['print_show_expense_note'] === 1;

        $showPaymentNote = ! $printSettings->has('print_show_payment_note')
            || (int) $printSettings['print_show_payment_note'] === 1;

        return view('expensesnew::expenses.print', compact('expense', 'showExpenseNote', 'showPaymentNote'));
    }

    /**
     * Server-side DataTables source. Querying the standalone tables directly
     * avoids relation/serialization failures and keeps large tenant histories
     * responsive.
     */
    public function data(Request $request)
    {
        $businessId = BusinessScope::businessId();
        $query = $this->expenseListQuery($businessId);

        if ($request->filled('location_id')) {
            $query->where('expenses.location_id', $request->integer('location_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('expenses.category_id', $request->integer('category_id'));
        }
        if ($request->filled('payee_id')) {
            $query->where('expenses.payee_id', $request->integer('payee_id'));
        }
        if ($request->filled('payment_status')) {
            $query->where('expenses.payment_status', $request->string('payment_status')->toString());
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expenses.expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expenses.expense_date', '<=', $request->date_to);
        }

        $search = trim((string) data_get($request->input('search', []), 'value', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $like = '%' . $search . '%';
                $builder->where('expenses.expense_no', 'like', $like)
                    ->orWhere('categories.name', 'like', $like)
                    ->orWhere('payees.name', 'like', $like)
                    ->orWhere('accounts.name', 'like', $like)
                    ->orWhere('expenses.reference_no', 'like', $like)
                    ->orWhere('expenses.payment_status', 'like', $like);
            });
        }

        $recordsTotal = DB::table('expnew_expenses')
            ->where('business_id', $businessId)
            ->count();
        $recordsFiltered = (clone $query)->count('expenses.id');

        // Must match the visible DataTables column order (Action is first).
        $orderColumns = [
            'expenses.id',
            'expenses.expense_date',
            'expenses.expense_no',
            'categories.name',
            'payees.name',
            'accounts.name',
            'expenses.total_amount',
            'expenses.paid_amount',
            'expenses.due_amount',
            'expenses.payment_status',
        ];
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $orderColumns[$orderIndex] ?? 'expenses.expense_date';

        $length = (int) $request->input('length', 25);
        $length = $length === -1 ? 5000 : max(10, min($length, 500));
        $start = max(0, (int) $request->input('start', 0));

        $rows = $query
            ->select($this->expenseListColumns())
            ->orderBy($orderColumn, $orderDirection)
            ->orderByDesc('expenses.id')
            ->offset($start)
            ->limit($length)
            ->get();

        $currencyPrecision = $this->currencyPrecision();

        $data = $rows->map(static function (object $row) use ($currencyPrecision): array {
            $expenseForAction = (object) ['id' => $row->id];

            return [
                'id' => $row->id,
                'date' => $row->expense_date,
                'expense_no' => $row->expense_no,
                'category' => $row->category_name ?: '—',
                'payee' => $row->payee_name ?: '—',
                'account' => $row->account_name ?: '—',
                'total_amount' => number_format((float) $row->total_amount, $currencyPrecision),
                'paid_amount' => number_format((float) $row->paid_amount, $currencyPrecision),
                'due_amount' => number_format((float) $row->due_amount, $currencyPrecision),
                'payment_status' => ucfirst((string) ($row->payment_status ?: 'due')),
                'action' => view('expensesnew::expenses.partials.actions', ['e' => $expenseForAction])->render(),
            ];
        })->values();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    protected function formData(
        Expense $expense,
        OptionService $options,
        AccountingModuleService $accountingModules,
        int $businessId
    ): array {
        $payees = $options->payees($businessId);
        $fallbackPayeeId = $payees->search(PayeeSyncService::CHEQUE_MODULE_NOT_ENABLED);

        $locations = $options->locations($businessId);
        $existingPaymentAccount = null;
        if ($expense->id && (int) ($expense->bank_account_id ?? 0) > 0 && DB::getSchemaBuilder()->hasTable('accounts')) {
            $account = DB::table('accounts')
                ->where('business_id', $businessId)
                ->where('id', (int) $expense->bank_account_id)
                ->first(['id', 'name']);
            if ($account) {
                $existingPaymentAccount = [
                    'id' => (int) $account->id,
                    'name' => (string) $account->name,
                    'location_id' => $expense->location_id ? (int) $expense->location_id : null,
                ];
            }
        }

        return [
            'expense' => $expense,
            'categories' => $options->expenseFormCategories($businessId),
            'payees' => $payees,
            'defaultPayeeId' => $fallbackPayeeId === false ? null : (int) $fallbackPayeeId,
            'locations' => $locations,
            'accounts' => $options->expenseAccounts($businessId),
            'categoryAccountMap' => $options->categoryExpenseAccountMap($businessId),
            'categoryDefaultPayeeMap' => $options->categoryDefaultPayeeMap($businessId),
            'accountingModules' => $accountingModules->options(),
            'paymentMethodsByLocation' => $accountingModules->paymentMethodsByLocation($businessId),
            'paymentAccountingModuleMapByLocation' => $accountingModules->paymentAccountingModuleMapByLocation($businessId),
            'paymentAccountOptionsByLocation' => $accountingModules->paymentAccountOptionsByLocation($businessId),
            'existingPaymentAccount' => $existingPaymentAccount,
        ];
    }

    protected function applyCategoryDefaults(
        array $data,
        int $businessId,
        AccountingModuleService $accountingModules,
        ?Expense $existingExpense = null
    ): array {
        $category = Category::where('business_id', $businessId)
            ->find($data['category_id'] ?? null);

        if (! $category) {
            throw ValidationException::withMessages([
                'category_id' => 'The selected expense category is not available for this business.',
            ]);
        }

        if (! $category->expense_account_id) {
            throw ValidationException::withMessages([
                'expense_account_id' => 'The selected expense category does not have a linked expense account.',
            ]);
        }

        $accountExists = ExpenseAccount::where('business_id', $businessId)
            ->whereKey($category->expense_account_id)
            ->exists();

        if (! $accountExists) {
            throw ValidationException::withMessages([
                'expense_account_id' => 'The expense account linked to the selected category is unavailable.',
            ]);
        }

        $data['expense_account_id'] = (int) $category->expense_account_id;

        $locationId = (int) ($data['location_id'] ?? 0);
        if ($locationId <= 0) {
            throw ValidationException::withMessages([
                'location_id' => 'Please select a business location.',
            ]);
        }

        $paymentMethod = isset($data['payment_method']) ? trim((string) $data['payment_method']) : '';

        // IS2341: the current Payment Options configuration is authoritative on
        // both Add and Edit. Disabled historical methods cannot be submitted.
        if (! $accountingModules->isPaymentMethodAllowed($businessId, $locationId, $paymentMethod)) {
            throw ValidationException::withMessages([
                'payment_method' => 'The selected payment method is not active for Expenses at the selected business location.',
            ]);
        }

        $accountingModule = $accountingModules->accountingModuleForPaymentMethod(
            $businessId,
            $locationId,
            $paymentMethod
        );
        if (! $accountingModule) {
            throw ValidationException::withMessages([
                'payment_method' => 'The selected payment method does not have a linked accounting module.',
            ]);
        }
        $data['accounting_module'] = $accountingModule;

        $selectedFinanceAccount = (int) ($data['bank_account_id'] ?? 0);
        if (! $accountingModules->isLocationAccountAllowed(
            $businessId,
            $locationId,
            $paymentMethod,
            $selectedFinanceAccount
        )) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Select an accounting account linked to the chosen payment method for this business location.',
            ]);
        }
        $data['bank_account_id'] = $selectedFinanceAccount;

        if (empty($data['payee_id']) && $category->default_payee_id) {
            $data['payee_id'] = (int) $category->default_payee_id;
        }

        if (! empty($data['payee_id'])) {
            $payeeExists = Payee::where('business_id', $businessId)
                ->whereKey($data['payee_id'])
                ->exists();

            if (! $payeeExists) {
                throw ValidationException::withMessages([
                    'payee_id' => 'The selected payee is not available for this business.',
                ]);
            }
        }

        return $data;
    }

    /**
     * The Add/Edit form now has one visible amount field. New expenses keep the
     * established database/Finance contract by deriving total_amount from
     * paid_amount; edits preserve an existing historical total_amount.
     * Also remove method-specific values that are hidden on the form so an edit
     * cannot retain an old cheque/card reference after changing payment method.
     */
    private function prepareExpenseFormData(array $data, ?Expense $existingExpense = null): array
    {
        $amount = round((float) ($data['paid_amount'] ?? 0), 4);
        $data['paid_amount'] = $amount;
        $data['total_amount'] = $existingExpense
            ? round((float) $existingExpense->total_amount, 4)
            : $amount;

        $method = (string) ($data['payment_method'] ?? '');

        if ($method === 'cash') {
            $data['reference_no'] = null;
            $data['cheque_no'] = null;
            $data['card_no'] = null;
        } elseif ($method === 'card') {
            $data['cheque_no'] = null;
        } elseif ($method === 'cheque') {
            $data['card_no'] = null;
        } elseif ($method === 'bank_transfer') {
            $data['cheque_no'] = null;
            $data['card_no'] = null;
        }

        return $data;
    }

    /**
     * Use the application's Business Settings currency precision for List
     * Expenses. Values are stored at scale 4, so display precision is bounded
     * to that storage precision.
     */
    private function currencyPrecision(): int
    {
        $precision = (int) session('business.currency_precision', 2);

        return max(0, min($precision, 4));
    }

    private function expenseListQuery(int $businessId)
    {
        return DB::table('expnew_expenses as expenses')
            ->leftJoin('expnew_categories as categories', function ($join): void {
                $join->on('categories.id', '=', 'expenses.category_id')
                    ->on('categories.business_id', '=', 'expenses.business_id');
            })
            ->leftJoin('expnew_payees as payees', function ($join): void {
                $join->on('payees.id', '=', 'expenses.payee_id')
                    ->on('payees.business_id', '=', 'expenses.business_id');
            })
            ->leftJoin('expnew_expense_accounts as accounts', function ($join): void {
                $join->on('accounts.id', '=', 'expenses.expense_account_id')
                    ->on('accounts.business_id', '=', 'expenses.business_id');
            })
            ->where('expenses.business_id', $businessId);
    }

    /** @return array<int, string> */
    private function expenseListColumns(): array
    {
        return [
            'expenses.id',
            'expenses.expense_date',
            'expenses.expense_no',
            'expenses.location_id',
            'expenses.category_id',
            'expenses.payee_id',
            'expenses.total_amount',
            'expenses.paid_amount',
            'expenses.due_amount',
            'expenses.payment_status',
            'categories.name as category_name',
            'payees.name as payee_name',
            'accounts.name as account_name',
        ];
    }
}
