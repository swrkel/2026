<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\ExpensesNew\Entities\Category;
use Modules\ExpensesNew\Entities\Expense;
use Modules\ExpensesNew\Services\CategoryCodeGenerator;
use Modules\ExpensesNew\Services\EmployeeOptionService;
use Modules\ExpensesNew\Services\OptionService;
use Modules\ExpensesNew\Services\PayeeSyncService;
use Modules\ExpensesNew\Utils\BusinessScope;

class CategoryController extends Controller
{
    public function index()
    {
        $businessId = BusinessScope::businessId();

        // Render the saved rows with the page instead of depending on an AJAX
        // initializer. This guarantees that a newly saved category is visible
        // after the redirect even when the host layout does not render a stack.
        $categories = Category::query()
            ->with([
                'expenseAccount:id,business_id,name',
                'defaultPayee:id,business_id,name',
            ])
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return view('expensesnew::categories.index', compact('categories'));
    }

    /**
     * MA-002 (S-612 #2): the three flags, only for columns that exist.
     */
    protected function categoryFlags(Request $request): array
    {
        $flags = [];

        foreach (['vat_input_claimed', 'is_sub_category', 'is_employee'] as $flag) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('expnew_categories', $flag)) {
                $flags[$flag] = $request->boolean($flag);
            }
        }

        /*
         * IS1991 (#2): the selection each flag asks for.
         *
         * Cleared when its checkbox is off, deliberately. Otherwise unticking
         * "Sub Category" would hide the dropdown while the old parent stayed on
         * the record - invisible on the form and still acting on every report
         * that reads it.
         *
         * Column-guarded like the flags above, so the form keeps working before
         * 2026_08_11_000002 has run.
         */
        foreach (['is_sub_category' => 'parent_id', 'is_employee' => 'employee_id'] as $flag => $column) {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('expnew_categories', $column)) {
                continue;
            }

            $value = $request->input($column);

            $flags[$column] = ($request->boolean($flag) && $value !== null && $value !== '')
                ? (int) $value
                : null;
        }

        return $flags;
    }

    /**
     * MA-002 (S-621 #2): what a category needs, for the Add Expenses form.
     *
     * Returns the three flags plus the options each one requires, so the form
     * can show a field AND populate it in a single request rather than one
     * call per dropdown.
     *
     * Every list is scoped to the business, and every table is checked before
     * it is queried - an installation without the HR module returns an empty
     * employee list rather than erroring.
     */
    public function requirements($id)
    {
        $businessId = BusinessScope::businessId();

        $category = Category::where('business_id', $businessId)
            ->where('id', (int) $id)
            ->first();

        if (empty($category)) {
            return response()->json([
                'success' => false,
                'msg' => 'Category not found.',
            ], 404);
        }

        $flag = function (string $column) use ($category): bool {
            return \Illuminate\Support\Facades\Schema::hasColumn('expnew_categories', $column)
                && ! empty($category->{$column});
        };

        $needsVat = $flag('vat_input_claimed');
        $needsSub = $flag('is_sub_category');
        $needsEmployee = $flag('is_employee');

        $vatCategories = [];
        if ($needsVat && \Illuminate\Support\Facades\Schema::hasTable('tax_rates')) {
            $vatCategories = \DB::table('tax_rates')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        /*
         * Sub categories are the other categories on this business. The
         * category being edited is excluded so it cannot be its own parent.
         */
        $subCategories = [];
        if ($needsSub) {
            $subCategories = Category::where('business_id', $businessId)
                ->where('id', '<>', $category->id)
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        /*
         * IS1991 (#2): resolved through EmployeeOptionService.
         *
         * This used to name the `employees` table directly, while the category
         * form had no employee list at all. Two places asking the same question
         * differently is how they end up disagreeing, so the table lookup now
         * lives in one service that both call.
         */
        $employees = $needsEmployee
            ? app(EmployeeOptionService::class)->options($businessId)
            : [];

        return response()->json([
            'success' => true,
            'vat_input_claimed' => $needsVat,
            'is_sub_category' => $needsSub,
            'is_employee' => $needsEmployee,
            'vat_categories' => $vatCategories,
            'sub_categories' => $subCategories,
            'employees' => $employees,
        ]);
    }

    /**
     * IS1991 (#2): the options behind the two checkbox-driven dropdowns.
     *
     * Sent with the page rather than fetched when a box is ticked. The lists
     * are small, and a dropdown that is already filled cannot show up empty
     * because a request failed - which is exactly what was reported.
     *
     * $excludeId keeps a category out of its own parent list on Edit.
     */
    protected function flagOptions(int $businessId, ?int $excludeId = null): array
    {
        $parents = Category::query()
            ->where('business_id', $businessId)
            ->when($excludeId, fn ($query) => $query->where('id', '<>', $excludeId))
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name', 'id');

        return [
            'parentCategories' => $parents,
            'employees' => app(EmployeeOptionService::class)->options($businessId),
        ];
    }

    public function create(OptionService $options, CategoryCodeGenerator $codes)
    {
        $businessId = BusinessScope::businessId();
        $payees = $options->categoryDefaultPayees($businessId);
        $defaultPayeeId = $payees->search(PayeeSyncService::CHEQUE_MODULE_NOT_ENABLED);

        return view('expensesnew::categories.form', $this->flagOptions($businessId) + [
            'category' => new Category(),
            /*
             * MA-002 (S-612 #2): the next code, from the prefix and starting
             * number on the Settings page.
             *
             * Prefilled rather than locked - a category that genuinely needs a
             * hand-written code can still have one, and the generator skips
             * non-numeric codes when working out the next number, so one
             * exception does not break the sequence.
             */
            'suggestedCode' => $codes->next($businessId),
            'accounts' => $options->expenseAccounts($businessId),
            'payees' => $payees,
            'defaultPayeeId' => $defaultPayeeId === false ? null : (int) $defaultPayeeId,
        ]);
    }

    public function store(Request $request)
    {
        $businessId = BusinessScope::businessId();
        $data = $this->validatedData($request, $businessId);

        Category::create($data + [
            'business_id' => $businessId,
            'is_active' => $request->boolean('is_active'),
        /*
         * MA-002 (S-612 #2): the three flags are merged in only for columns
         * that exist. The migration adding them may not have run yet, and
         * passing a key with no column throws "Unknown column" on save - so
         * this keeps the form usable before the migration and picks the values
         * up automatically once it has run.
         */
        ] + $this->categoryFlags($request) + [
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('expensesnew.expenses.index')->with('status', 'Category saved');
    }

    public function edit($id, OptionService $options, CategoryCodeGenerator $codes)
    {
        $businessId = BusinessScope::businessId();
        $category = Category::where('business_id', $businessId)->findOrFail($id);

        return view('expensesnew::categories.form', $this->flagOptions($businessId, (int) $category->id) + [
            'category' => $category,
            /*
             * LA-1147: the form reads $suggestedCode and edit() never supplied
             * it. Line 148 tolerated that with `?? ''`, but the next line did
             * not, so Edit hit "Undefined variable $suggestedCode".
             *
             * Passing it properly rather than only guarding the view: an older
             * category saved before codes existed now prefills with the next
             * code, exactly as Add does, instead of showing an empty box with a
             * "no code configured" warning.
             */
            'suggestedCode' => $codes->next($businessId),
            'accounts' => $options->expenseAccounts($businessId),
            'payees' => $options->categoryDefaultPayees($businessId),
            'defaultPayeeId' => null,
        ]);
    }

    public function update(Request $request, $id)
    {
        $businessId = BusinessScope::businessId();
        $category = Category::where('business_id', $businessId)->findOrFail($id);

        // MA-002 (S-612 #2): same column-aware merge as store().
        $category->update($this->validatedData($request, $businessId) + [
            'is_active' => $request->boolean('is_active'),
        ] + $this->categoryFlags($request) + [
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('expensesnew.expenses.index')->with('status', 'Category updated');
    }

    public function show($id)
    {
        $businessId = BusinessScope::businessId();
        $category = Category::with(['expenseAccount', 'defaultPayee'])
            ->where('business_id', $businessId)
            ->findOrFail($id);
        $expenseCount = Expense::where('business_id', $businessId)
            ->where('category_id', $category->id)
            ->count();

        return view('expensesnew::categories.show', compact('category', 'expenseCount'));
    }

    /**
     * MA-002 (S-620 #8): enable or disable a category.
     *
     * Disabling is the safe alternative to deleting - a category used by
     * expenses cannot be removed, but it can be taken out of use so it stops
     * appearing on new entries while its history stays intact.
     */
    public function toggleActive($id)
    {
        $businessId = BusinessScope::businessId();

        $category = Category::where('business_id', $businessId)->findOrFail($id);

        $category->is_active = $category->is_active ? 0 : 1;
        $category->updated_by = auth()->id();
        $category->save();

        return response()->json([
            'success' => true,
            'is_active' => (int) $category->is_active,
            'message' => $category->is_active
                ? 'Category enabled.'
                : 'Category disabled.',
        ]);
    }

    public function destroy($id)
    {
        $businessId = BusinessScope::businessId();
        $category = Category::where('business_id', $businessId)->findOrFail($id);

        /*
         * MA-002 (S-620 #8): a category used by ANY expense cannot be deleted.
         *
         * "Any" includes deleted expenses, as agreed - expnew_expenses has no
         * deleted_at column, so every row that exists counts, which is exactly
         * the rule wanted.
         *
         * The count is included in the message so the user knows the scale of
         * what is blocking them rather than just being refused.
         */
        $usedBy = Expense::where('business_id', $businessId)
            ->where('category_id', $category->id)
            ->count();

        if ($usedBy > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This category cannot be deleted - it is used by '
                    . $usedBy . ' expense' . ($usedBy === 1 ? '' : 's')
                    . '. You can disable it instead, which stops it appearing on new entries.',
            ], 422);
        }

        $category->delete();

        return response()->json(['success' => true]);
    }

    public function options(OptionService $options)
    {
        return response()->json($options->categories(BusinessScope::businessId()));
    }

    /**
     * Compatibility data source retained for existing integrations. The main
     * Categories page is server rendered so it cannot appear empty merely
     * because DataTables JavaScript failed to initialise.
     */
    public function data()
    {
        $rows = Category::query()
            ->with([
                'expenseAccount:id,business_id,name',
                'defaultPayee:id,business_id,name',
            ])
            ->where('business_id', BusinessScope::businessId())
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $rows->map(static fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'expense_account' => optional($category->expenseAccount)->name ?: '—',
                'default_payee' => optional($category->defaultPayee)->name ?: '—',
                'active' => $category->is_active ? 'Yes' : 'No',
                'action' => view('expensesnew::categories.partials.actions', ['c' => $category])->render(),
            ]),
        ]);
    }

    protected function validatedData(Request $request, int $businessId): array
    {
        $validated = $this->validateCategory($request, $businessId);

        /*
         * IS1991 (#2): the flags and their two selections are validated here but
         * NOT returned.
         *
         * store() and update() merge with `+`, which keeps the left-hand key, so
         * anything returned from here beats categoryFlags(). That quietly
         * defeated the Schema::hasColumn guard the flags rely on - a site that
         * had not yet run the migration would have had the raw keys written
         * straight through and hit "Unknown column" on save. Removing them here
         * leaves categoryFlags() as the single place that decides what is
         * written, which is what its comment always said it was.
         */
        foreach (['vat_input_claimed', 'is_sub_category', 'is_employee', 'parent_id', 'employee_id'] as $key) {
            unset($validated[$key]);
        }

        return $validated;
    }

    protected function validateCategory(Request $request, int $businessId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:50'],
            // MA-002 (S-612 #2): the three flags. Booleans, so anything other
            // than a truthy value is rejected rather than stored as a string.
            'vat_input_claimed' => ['nullable', 'boolean'],
            'is_sub_category' => ['nullable', 'boolean'],
            'is_employee' => ['nullable', 'boolean'],
            /*
             * IS1991 (#2): the two selections.
             *
             * parent_id is checked against this business's own categories.
             * employee_id is only checked for shape - the employee table
             * belongs to the HR module and its name varies by installation
             * (see EmployeeOptionService), so an exists rule naming one table
             * would reject valid input, or error outright, on any site that
             * uses a different one.
             */
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('expnew_categories', 'id')
                    ->where(static fn ($query) => $query->where('business_id', $businessId)),
            ],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'expense_account_id' => [
                'required',
                'integer',
                Rule::exists('expnew_expense_accounts', 'id')
                    ->where(static fn ($query) => $query->where('business_id', $businessId)),
            ],
            'default_payee_id' => [
                'nullable',
                'integer',
                Rule::exists('expnew_payees', 'id')
                    ->where(static fn ($query) => $query->where('business_id', $businessId)),
            ],
        ]);
    }
}
