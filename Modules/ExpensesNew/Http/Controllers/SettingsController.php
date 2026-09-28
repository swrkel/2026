<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\ExpensesNew\Entities\CategoryCode;
use Modules\ExpensesNew\Entities\ExpensePrefix;
use Modules\ExpensesNew\Entities\Setting;
use Modules\ExpensesNew\Services\CategoryCodeUsageService;
use Modules\ExpensesNew\Services\ExpensePrefixUsageService;
use Modules\ExpensesNew\Utils\BusinessScope;

class SettingsController extends Controller
{
    public function index(CategoryCodeUsageService $usageService): View
    {
        $businessId = BusinessScope::businessId();

        $settings = Setting::query()
            ->where('business_id', $businessId)
            ->pluck('value', 'key');

        // Render the rows on the first page response. This removes the previous
        // dependency on a generic AJAX table initialiser and guarantees that a
        // newly saved category code is visible immediately after redirect.
        $categoryCodes = CategoryCode::query()
            ->where('business_id', $businessId)
            ->orderBy('code')
            ->get();

        $usageService->attachUsage($categoryCodes, $businessId);

        /*
         * MA-002: the actual next code, worked out by the same service the Add
         * Category form uses.
         *
         * The preview on this page was JavaScript-only, so before anything was
         * typed it could only show a dash - and with the settings already saved
         * it still showed a dash until you touched a field. Seeding it from the
         * server means the box shows the real next code the moment the page
         * opens, and the two can never disagree because they come from the same
         * calculation.
         */
        $nextCategoryCode = app(\Modules\ExpensesNew\Services\CategoryCodeGenerator::class)
            ->next(BusinessScope::businessId());

        // IS1991 (#1): the Prefix List, with usage counted live so a prefix
        // unlocks by itself once its last expense is deleted.
        $prefixes = $this->prefixList($businessId);
        $activePrefix = (string) ($settings['category_code_prefix'] ?? '');

        return view('expensesnew::settings.index', compact(
            'settings',
            'categoryCodes',
            'nextCategoryCode',
            'prefixes',
            'activePrefix'
        ));
    }

    /**
     * IS1991 (#1): saved prefixes for a business, with creator name and usage.
     */
    protected function prefixList(int $businessId)
    {
        if (! Schema::hasTable('expnew_expense_prefixes')) {
            return collect();
        }

        $prefixes = ExpensePrefix::query()
            ->where('business_id', $businessId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        app(ExpensePrefixUsageService::class)->attachUsage($prefixes, $businessId);

        $this->attachCreatorNames($prefixes);

        return $prefixes;
    }

    /**
     * Resolve created_by into a display name.
     *
     * Read straight from `users` rather than through a model: the module owns
     * no user entity, and a missing or deleted user must leave the row showing
     * a dash rather than breaking the list.
     */
    protected function attachCreatorNames($prefixes): void
    {
        $ids = $prefixes->pluck('created_by')->filter()->unique()->values();

        if ($ids->isEmpty() || ! Schema::hasTable('users')) {
            $prefixes->each(fn ($prefix) => $prefix->setAttribute('created_user_name', null));

            return;
        }

        $nameParts = [];
        foreach (['surname', 'first_name', 'last_name'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $nameParts[] = "COALESCE({$column}, '')";
            }
        }

        $expression = $nameParts === []
            ? "COALESCE(username, '')"
            : 'TRIM(CONCAT_WS(\' \', ' . implode(', ', $nameParts) . '))';

        $names = DB::table('users')
            ->whereIn('id', $ids)
            ->selectRaw("id, {$expression} AS display_name")
            ->pluck('display_name', 'id');

        $prefixes->each(function ($prefix) use ($names): void {
            $name = trim((string) $names->get($prefix->created_by, ''));
            $prefix->setAttribute('created_user_name', $name !== '' ? $name : null);
        });
    }

    public function save(Request $request): RedirectResponse
    {
        $businessId = BusinessScope::businessId();

        $validated = $request->validate([
            /*
             * MA-002 (S-615 #3): default_payment_method and voucher_prefix have
             * been removed from the form.
             *
             * default_payment_method was 'required'. Leaving that rule in place
             * with the field gone would have failed validation on every save -
             * the page would simply refuse to save the two code settings, with
             * an error about a field no longer on screen.
             *
             * The rules are removed; the stored values are NOT deleted. If
             * either setting is read anywhere else it keeps what it has, and if
             * the fields are ever wanted back they return with their values
             * intact.
             */
            // MA-002 (S-612 #1): prefix and starting number for category codes.
            // The start is validated as digits only - a start of "10A" would
            // otherwise be stored and then silently ignored by the generator.
            'category_code_prefix' => ['nullable', 'string', 'max:20'],
            'category_code_start' => ['nullable', 'regex:/^[0-9]{1,12}$/'],
            // MA-002: the Date field from the supplied design. Free text, so the
            // format shown (dd/mm/yyyy HH:mm) is preserved exactly as typed
            // rather than being reinterpreted by a date cast.
            'category_code_date' => ['nullable', 'string', 'max:32'],
            // MA-002 (S-621): Expense Voucher print options.
            'print_show_expense_note' => ['nullable', 'in:0,1'],
            'print_show_payment_note' => ['nullable', 'in:0,1'],
            /*
             | 8031: when on, the Add and Edit forms prefill Paid Amount with the
             | Total Amount. The user can still change it - this only saves the
             | typing for the common case of an expense paid in full.
             */
            'paid_amount_autofill' => ['nullable', 'in:0,1'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['business_id' => $businessId, 'key' => $key],
                ['value' => $value]
            );
        }

        // IS1991 (#1): record the prefix so it appears in the Prefix List. The
        // setting above still holds the prefix in use, which is what generates
        // codes; this is the audit row behind it.
        $this->recordPrefix(
            $businessId,
            (string) ($validated['category_code_prefix'] ?? ''),
            $validated['category_code_start'] ?? null,
            $validated['category_code_date'] ?? null
        );

        return redirect()
            ->route('expensesnew.settings.index')
            ->with('status', 'Settings saved');
    }

    /**
     * IS1991 (#1): store or refresh one prefix on the list.
     *
     * An empty prefix records nothing - it means "no prefix", not a prefix
     * whose text happens to be blank, and a blank row could not be edited or
     * deleted meaningfully.
     */
    protected function recordPrefix(int $businessId, string $prefix, $startingNo, $codeDate): void
    {
        $prefix = trim($prefix);

        if ($prefix === '' || ! Schema::hasTable('expnew_expense_prefixes')) {
            return;
        }

        $existing = ExpensePrefix::query()
            ->where('business_id', $businessId)
            ->where('prefix', $prefix)
            ->first();

        if ($existing) {
            // created_by and created_at are left alone: the list is meant to
            // show who first created the prefix, not who last saved settings.
            $existing->fill([
                'starting_no' => $startingNo,
                'code_date' => $codeDate,
                'updated_by' => auth()->id(),
            ])->save();

            return;
        }

        ExpensePrefix::create([
            'business_id' => $businessId,
            'prefix' => $prefix,
            'starting_no' => $startingNo,
            'code_date' => $codeDate,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * IS1991 (#1): rename a prefix that has no expense transactions behind it.
     */
    public function updatePrefix(
        Request $request,
        int $id,
        ExpensePrefixUsageService $usageService
    ): RedirectResponse {
        $businessId = BusinessScope::businessId();

        $prefixRow = ExpensePrefix::query()
            ->where('business_id', $businessId)
            ->findOrFail($id);

        if ($usageService->hasTransactions($prefixRow)) {
            return redirect()
                ->route('expensesnew.settings.index')
                ->withErrors([
                    'This prefix is already used by expense transactions and cannot be edited. Create a new prefix instead.',
                ]);
        }

        $validated = $request->validate([
            'prefix' => [
                'required',
                'string',
                'max:20',
                Rule::unique('expnew_expense_prefixes', 'prefix')
                    ->ignore($prefixRow->id)
                    ->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'starting_no' => ['nullable', 'regex:/^[0-9]{1,12}$/'],
        ]);

        $wasActive = $this->isActivePrefix($businessId, $prefixRow->prefix);

        $prefixRow->fill([
            'prefix' => trim($validated['prefix']),
            'starting_no' => $validated['starting_no'] ?? null,
            'updated_by' => auth()->id(),
        ])->save();

        /*
         * Keep the generator in step. If the prefix being renamed is the one
         * currently generating codes, the setting has to follow it - otherwise
         * the list would show the new name while new categories carried the old
         * one.
         */
        if ($wasActive) {
            $this->putSetting($businessId, 'category_code_prefix', $prefixRow->prefix);

            if (! empty($validated['starting_no'])) {
                $this->putSetting($businessId, 'category_code_start', $validated['starting_no']);
            }
        }

        return redirect()
            ->route('expensesnew.settings.index')
            ->with('status', 'Prefix updated');
    }

    /**
     * IS1991 (#1): delete a prefix that has no expense transactions behind it.
     */
    public function destroyPrefix(int $id, ExpensePrefixUsageService $usageService): RedirectResponse
    {
        $businessId = BusinessScope::businessId();

        $prefixRow = ExpensePrefix::query()
            ->where('business_id', $businessId)
            ->findOrFail($id);

        if ($usageService->hasTransactions($prefixRow)) {
            return redirect()
                ->route('expensesnew.settings.index')
                ->withErrors([
                    'This prefix is already used by expense transactions and cannot be deleted. Create a new prefix instead.',
                ]);
        }

        $wasActive = $this->isActivePrefix($businessId, $prefixRow->prefix);

        $prefixRow->delete();

        // Deleting the prefix in use clears the setting too, so the next
        // category code is generated without it rather than from a prefix that
        // no longer exists on the list.
        if ($wasActive) {
            $this->putSetting($businessId, 'category_code_prefix', '');
        }

        return redirect()
            ->route('expensesnew.settings.index')
            ->with('status', 'Prefix deleted');
    }

    protected function isActivePrefix(int $businessId, ?string $prefix): bool
    {
        $current = Setting::query()
            ->where('business_id', $businessId)
            ->where('key', 'category_code_prefix')
            ->value('value');

        return trim((string) $current) !== '' && trim((string) $current) === trim((string) $prefix);
    }

    protected function putSetting(int $businessId, string $key, $value): void
    {
        Setting::updateOrCreate(
            ['business_id' => $businessId, 'key' => $key],
            ['value' => $value]
        );
    }

    public function categoryCodeData(CategoryCodeUsageService $usageService): JsonResponse
    {
        $businessId = BusinessScope::businessId();

        $rows = CategoryCode::query()
            ->where('business_id', $businessId)
            ->orderBy('code')
            ->get();

        $usageService->attachUsage($rows, $businessId);

        return response()->json([
            'data' => $rows->map(fn (CategoryCode $row) => [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'action' => $row->has_transactions
                    ? '<button type="button" class="btn btn-xs btn-danger" disabled aria-disabled="true" title="This code has existing expense transactions and cannot be deleted.">Delete</button>'
                    : '<button type="button" class="btn btn-xs btn-danger expnew-delete" data-url="'
                        .e(route('expensesnew.settings.category_codes.destroy', $row->id))
                        .'">Delete</button>',
            ])->values(),
        ]);
    }

    public function storeCategoryCode(Request $request): JsonResponse|RedirectResponse
    {
        $businessId = BusinessScope::businessId();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('expnew_category_codes', 'code')
                    ->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
        ]);

        CategoryCode::create($validated + [
            'business_id' => $businessId,
            'is_active' => 1,
            'created_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('expensesnew.settings.index')
            ->with('status', 'Expense category code saved');
    }

    public function destroyCategoryCode(
        Request $request,
        int $id,
        CategoryCodeUsageService $usageService
    ): JsonResponse|RedirectResponse {
        $categoryCode = CategoryCode::query()
            ->where('business_id', BusinessScope::businessId())
            ->findOrFail($id);

        if ($usageService->hasTransactions($categoryCode)) {
            $message = 'This expense category code cannot be deleted because it has existing transactions.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 409);
            }

            return redirect()
                ->route('expensesnew.settings.index')
                ->withErrors([$message]);
        }

        $categoryCode->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('expensesnew.settings.index')
            ->with('status', 'Expense category code deleted');
    }
}
