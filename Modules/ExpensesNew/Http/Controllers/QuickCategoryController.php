<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ExpensesNew - inline "quick add expense category" for other modules.
 *
 * MA-002: this is step one of migrating the 26 screens across Fleet, Property,
 * SettlementSW, AutoRepairServices, EVCharging and the four Petro modules that
 * currently call
 *     action('ExpenseCategoryController@create', ['quick_add' => true])
 * and so depend on CORE's expense category controller.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS DOES NOT USE ExpensesNew\Entities\Category
 *
 * That model writes to `expnew_categories`. The 26 calling screens build their
 * dropdowns from `expense_categories` via App\ExpenseCategory. In your
 * database:
 *
 *     expense_categories    12 rows
 *     expnew_categories      0 rows      and nothing syncs the two
 *
 * So a quick-add built on the module's own model would save successfully and
 * the caller's dropdown would never show the new row. No error, no clue why -
 * worse than the coupling it replaced.
 *
 * This controller therefore reads and writes `expense_categories` DIRECTLY,
 * through the query builder. That is deliberate and it is not a workaround
 * for its own sake:
 *
 *   - it works today, against the table the callers actually read;
 *   - it takes no dependency on App\ExpenseCategory, so the calling modules
 *     stop pointing at a core controller, which is the whole point;
 *   - it does not pre-empt the decision about which table owns expense
 *     categories. If you point ExpensesNew\Entities\Category at
 *     `expense_categories`, this keeps working unchanged. If you migrate the
 *     other way, ONE constant below changes.
 *
 * ---------------------------------------------------------------------------
 * CONTRACT - matched to core's, because 26 existing screens rely on it
 *
 *   GET   returns BARE MODAL MARKUP - no layout. Core's
 *         expense_category/create.blade.php opens with <div class="modal-dialog">
 *         which is why those screens can drop it into a .btn-modal. The
 *         module's own categories/form.blade.php extends a full page layout
 *         and would render a whole page inside a modal box.
 *
 *   POST  returns JSON  { success, expense_category_id, msg }  - the same keys
 *         core returns, so the existing JS on those 26 screens works without
 *         being touched.
 */
class QuickCategoryController extends Controller
{
    /**
     * The table expense categories actually live in today.
     *
     * If ownership moves to `expnew_categories`, this is the single line that
     * changes - and the migration of the 12 existing rows becomes the only
     * other work.
     */
    private const TABLE = 'expense_categories';

    private function businessId(): int
    {
        return (int) request()->session()->get('user.business_id');
    }

    /**
     * Bare modal markup for the inline add form.
     */
    public function create()
    {
        $business_id = $this->businessId();

        $expense_accounts = DB::table('accounts')
            ->where('business_id', $business_id)
            ->where('is_closed', 0)
            ->orderBy('name')
            ->pluck('name', 'id');

        $parents = DB::table(self::TABLE)
            ->where('business_id', $business_id)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('is_sub_category')->orWhere('is_sub_category', 0);
            })
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('expensesnew::quick_category.create', compact('expense_accounts', 'parents'));
    }

    /**
     * Store and return the JSON shape core returns.
     */
    public function store(Request $request)
    {
        $business_id = $this->businessId();

        try {
            $validated = $request->validate([
                'name'            => ['required', 'string', 'max:191'],
                'code'            => ['nullable', 'string', 'max:191'],
                'expense_account' => ['nullable', 'integer'],
                'parent_id'       => ['nullable', 'integer'],
            ]);

            $isSub = $request->boolean('is_sub_category') && ! empty($validated['parent_id']);

            /*
             * Core generates a code when none is given, so a fresh tenant does
             * not get a blank one. Reproduced rather than invented, so
             * categories added here look the same as categories added through
             * the existing screens.
             */
            $code = $validated['code'] ?? null;
            if (empty($code)) {
                $count = DB::table(self::TABLE)->where('business_id', $business_id)->count();
                $code = 'EXP-' . ($count + 1);
            }

            $now = now();
            $id = DB::table(self::TABLE)->insertGetId([
                'name'            => $validated['name'],
                'business_id'     => $business_id,
                'code'            => $code,
                'expense_account' => $validated['expense_account'] ?? null,
                'is_sub_category' => $isSub ? 1 : 0,
                'parent_id'       => $isSub ? $validated['parent_id'] : null,
                'payee_id'        => 0,
                'is_employee'     => 0,
                'vat_claimed'     => 0,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            return [
                'success'             => true,
                'expense_category_id' => $id,
                'msg'                 => __('expense.added_success'),
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'success' => false,
                'msg'     => $e->validator->errors()->first(),
            ];
        } catch (\Exception $e) {
            Log::emergency('ExpensesNew quick category: File:' . $e->getFile()
                . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }
    }

    /**
     * Refreshed <option> list, so a caller can repopulate its dropdown after
     * an add without reloading the page. Core's screens do this via a separate
     * dropdown endpoint; providing it here keeps the whole cycle in this
     * module.
     */
    public function options()
    {
        $rows = DB::table(self::TABLE)
            ->where('business_id', $this->businessId())
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name', 'id');

        $html = '<option value="">' . __('messages.please_select') . '</option>';
        foreach ($rows as $id => $name) {
            $html .= '<option value="' . e($id) . '">' . e($name) . '</option>';
        }

        return $html;
    }
}
