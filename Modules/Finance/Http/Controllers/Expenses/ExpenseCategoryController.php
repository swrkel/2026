<?php

namespace Modules\Finance\Http\Controllers\Expenses;

use App\ExpenseCategory;
use App\ExpenseCategoryCode;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Essentials\Entities\EssentialsEmployee;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountType;
use Modules\Finance\Entities\Contact;
use Yajra\DataTables\Facades\DataTables;

/**
 * Finance expense categories.
 *
 * MA-002: NO LONGER A BRIDGE TO CORE.
 *
 * It previously declared
 *     extends App\Http\Controllers\ExpenseCategoryController
 * inheriting all 588 lines. Finance routed only index(), but index's
 * DataTable builds its View, Edit and Delete buttons with
 *     action('ExpenseCategoryController@show' / '@edit' / '@destroy')
 * which resolve to CORE - and the create/edit views post to core's @store and
 * @update as well. Moving index alone would have left a Finance page driving
 * core's controller, so the whole cycle had to move together.
 *
 * WHAT MOVED
 *   All fourteen methods, extracted programmatically from the core file
 *   rather than retyped, so the logic cannot drift. The action() targets in
 *   both the controller and the views now point at THIS class, and Finance
 *   registers the matching routes.
 *
 *   Account, AccountType and Contact resolve to Finance's own entities.
 *   Before pointing a write path at them I compared each against its core
 *   counterpart on the things that change behaviour - LogsActivity, boot
 *   hooks, SoftDeletes, global scopes and fillable/guarded. That check is
 *   what caught the ContactLedger and Journal defects earlier in this
 *   project, so it is not skipped.
 *
 * WHAT DELIBERATELY DID NOT MOVE
 *   ExpenseCategory and ExpenseCategoryCode stay as App\ classes. Finance has
 *   no equivalents, and inventing duplicates is precisely the drift this
 *   project has already been bitten by.
 *
 *   Util, ModuleUtil and ProductUtil are still injected, as they are
 *   throughout Finance. Extracting those is the 22,000-line problem flagged
 *   separately, not something to smuggle in here.
 *
 * A PRE-EXISTING FAULT CARRIED ACROSS UNCHANGED
 *   view($id) returns view('expense_category.view'), and that Blade file does
 *   not exist - not in core, and so not here either. The method would fail if
 *   it were ever reached. It is NOT routed, in core or in Finance, so nothing
 *   calls it. I have carried it over exactly as it was rather than quietly
 *   deleting or "fixing" it, because that is a decision for you: either the
 *   view is missing or the method is dead code.
 *
 * Finance bridge controllers remaining: 5 -> 4.
 */
class ExpenseCategoryController extends Controller
{
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil =  $moduleUtil;
        $this->productUtil =  $productUtil;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }


        $business_id = request()->session()->get('user.business_id');

        $expense_cat = ExpenseCategoryCode::where('business_id',$business_id)->first();


        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $expense_category = ExpenseCategory::leftjoin('accounts', 'expense_account', 'accounts.id')
                ->leftjoin('contacts', 'contacts.id', '=', 'expense_categories.payee_id')
                ->where('expense_categories.business_id', $business_id)
                //->where('expense_categories.payee_id', 'NULL')
                ->select(['expense_categories.name', 'code', 'accounts.name as account_name', 'contacts.name as payee_name', 'expense_categories.id']);
                
            return Datatables::of($expense_category)
                ->addColumn(
                    'action',
                    '<button data-href="{{action(\'\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseCategoryController@show\', [$id])}}" class="btn btn-xs btn-success btn-modal" data-container=".expense_category_modal"><i class="glyphicon glyphicon-eye-open"></i> @lang("messages.view")</button>
                        &nbsp;
                    <button data-href="{{action(\'\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseCategoryController@edit\', [$id])}}" class="btn btn-xs btn-primary btn-modal" data-container=".expense_category_modal"><i class="glyphicon glyphicon-edit"></i>  @lang("messages.edit")</button>
                        &nbsp;
                    <button data-href="{{action(\'\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseCategoryController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_expense_category"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>'
                )
                ->removeColumn('id')
                ->rawColumns([4])
                ->make(false);
        }

        return view('finance::expense_category.index')->with(compact(['expense_cat']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $expense_accounts = collect();
        $expense_account_id = null;
        $account_access = true;
        $expense_categories = collect();
        $employees = collect();
        $payees = ['' => 'No Payee'];
        $quick_add = request()->quick_add ? 1 : 0;
        
        $expense_cat = ExpenseCategoryCode::where('business_id',$business_id)->first();
        $expense = ExpenseCategory::where('business_id',$business_id)->get()->last();

        if(empty($expense_cat)) {
            // S348: Even when the category-code setup has not been created yet,
            // the Add Category popup must still load the Expense Account and Payee
            // dropdowns.  The previous code only populated these lists inside the
            // expense-code branch, leaving both fields empty on new tenants.
            $expcode = "EXP-1";
        } else {
            if (!empty($expense)) {
                $code = explode('-', $expense->code);

                $expcode = (!empty($expense_cat->prefix) ? $expense_cat->prefix : '') . "-" . ((!empty($code[1]) && $code[1] >= $expense_cat->starting_no) ? $code[1] + 1 : $expense_cat->starting_no);
            } else {
                $expcode = (!empty($expense_cat->prefix) ? $expense_cat->prefix : '') . "-" . $expense_cat->starting_no;
            }
        }

        list($expense_accounts, $expense_account_id, $account_access) = $this->getExpenseAccountOptions($business_id);
        $expense_categories = ExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
        $employees = EssentialsEmployee::pluck('name', 'id');
        $payees = $this->getPayeeOptions($business_id);




        return view('finance::expense_category.create')->with(compact('expcode','expense_accounts', 'account_access', 'expense_account_id', 'quick_add', 'expense_categories', 'employees', 'payees'));
    }
    public function view($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $expense_accounts = collect();
        $expense_account_id = null;
        $account_access = true;
        $expense_categories = collect();
        $employees = collect();
        $payees = ['' => 'No Payee'];
        $quick_add = request()->quick_add ? 1 : 0;
        
        $expense_cat = ExpenseCategoryCode::where('business_id',$business_id)->first();
        $expense = ExpenseCategory::where('business_id',$business_id)->get()->last();
        if(!empty($expense)){
            $code = explode('-',$expense->code);
            
            $expcode = (!empty($expense_cat->prefix) ? $expense_cat->prefix : '')."-".((!empty($code[1]) && $code[1] >= $expense_cat->starting_no) ? $code[1]+1 : $expense_cat->starting_no);
        }else{
            $expcode = (!empty($expense_cat->prefix) ? $expense_cat->prefix : '')."-".$expense_cat->starting_no;
        }
        
        
        list($expense_accounts, $expense_account_id, $account_access) = $this->getExpenseAccountOptions($business_id);

        $expense_categories = ExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
        $employees = EssentialsEmployee::pluck('name', 'id');
        $quick_add = request()->quick_add ? 1 : 0;

        $payees = $this->getPayeeOptions($business_id);

        return view('finance::expense_category.view')->with(compact('expcode','expense_accounts', 'account_access', 'expense_account_id', 'quick_add', 'expense_categories', 'employees', 'payees'));
    }

    /**
     * Build the Expense Account dropdown safely.
     * Uses the Expenses account type when available and falls back to accounts
     * whose name contains Expense so the category modal never opens empty.
     */
    private function getExpenseAccountOptions($business_id)
    {
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $expense_account_id = null;

        /*
         * Keep this lookup independent from a tenant's chart-of-accounts naming.
         * Older tenants use account type names, some use account groups (for example
         * Direct Expense/CPC), and others only have an expense word in the account
         * name. The previous status=1 fallback could also remove every account when
         * status was a string column, leaving the modal with an empty dropdown.
         */
        $base_query = DB::table('accounts')
            ->where('accounts.business_id', $business_id);

        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $base_query->whereNull('accounts.deleted_at');
        }

        if (Schema::hasColumn('accounts', 'is_closed')) {
            $base_query->where(function ($query) {
                $query->where('accounts.is_closed', 0)
                    ->orWhereNull('accounts.is_closed');
            });
        }

        if (Schema::hasColumn('accounts', 'is_main_account')) {
            $base_query->where(function ($query) {
                $query->where('accounts.is_main_account', 0)
                    ->orWhereNull('accounts.is_main_account');
            });
        }

        $preferred_query = clone $base_query;
        $has_account_types = Schema::hasTable('account_types')
            && Schema::hasColumn('accounts', 'account_type_id');
        $has_account_groups = Schema::hasTable('account_groups')
            && Schema::hasColumn('accounts', 'asset_type');

        if ($has_account_types) {
            $preferred_query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
        }

        if ($has_account_groups) {
            $preferred_query->leftJoin('account_groups', 'accounts.asset_type', '=', 'account_groups.id');
        }

        $preferred_query->where(function ($query) use ($has_account_types, $has_account_groups) {
            $query->where('accounts.name', 'like', '%expense%');

            if ($has_account_types) {
                $query->orWhere('account_types.name', 'like', '%expense%');
            }

            if ($has_account_groups) {
                $query->orWhere('account_groups.name', 'like', '%expense%')
                    ->orWhereIn('account_groups.name', ['CPC', 'Direct Expense', 'Indirect Expense']);
            }
        });

        $expense_accounts = $preferred_query
            ->select('accounts.id', 'accounts.name')
            ->distinct()
            ->orderBy('accounts.name')
            ->pluck('accounts.name', 'accounts.id');

        // A category must still be creatable on tenants whose accounts have not yet
        // been classified. Show all usable posting accounts instead of a blank field.
        if ($expense_accounts->isEmpty()) {
            $expense_accounts = $base_query
                ->select('accounts.id', 'accounts.name')
                ->distinct()
                ->orderBy('accounts.name')
                ->pluck('accounts.name', 'accounts.id');
        }

        if ($expense_accounts->isNotEmpty()) {
            $expense_account_id = $expense_accounts->keys()->first();
        }

        return [$expense_accounts, $expense_account_id, $account_access];
    }

    /**
     * Payees for Expense Categories must include manually-created cheque payees
     * and supplier contacts. This keeps Cheque Writing -> Manage Payee and
     * Expense Categories -> Payee synchronized.
     */
    private function getPayeeOptions($business_id)
    {
        /*
         * Cheque Writing stores managed payees in contacts. Some historical rows
         * have active=0/null or no contact type, so filtering by active/type alone
         * made the Expense Category modal appear empty even though payees existed.
         */
        $query = DB::table('contacts')
            ->where('business_id', $business_id)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->where('name', '!=', 'Walk-In Customer');

        if (Schema::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $has_is_payee = Schema::hasColumn('contacts', 'is_payee');
        $has_type = Schema::hasColumn('contacts', 'type');

        if ($has_is_payee || $has_type) {
            $query->where(function ($query) use ($has_is_payee, $has_type) {
                if ($has_is_payee) {
                    $query->where('is_payee', 1);
                }

                if ($has_type) {
                    $method = $has_is_payee ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('type', ['supplier', 'customer', 'both', 'contact', 'payee']);
                }
            });
        }

        $payees = $query
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Last-resort compatibility for old tenants whose managed payee rows were
        // saved without is_payee/type values. They remain valid contact IDs and can
        // therefore be stored safely in expense_categories.payee_id.
        if (empty($payees)) {
            $fallback = DB::table('contacts')
                ->where('business_id', $business_id)
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->where('name', '!=', 'Walk-In Customer');

            if (Schema::hasColumn('contacts', 'deleted_at')) {
                $fallback->whereNull('deleted_at');
            }

            $payees = $fallback->orderBy('name')->pluck('name', 'id')->toArray();
        }

        return ['' => 'No Payee'] + $payees;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }


        try {
//            $validator = Validator::make($request->all(), [
//                'name' => 'required|string',
//                'code' => 'required|string',
//                'expense_account' => 'required|string',
//                'payee_id' => 'required|string',
//                'parent_id' => 'nullable|string',
//            ]);
//
//            if ($validator->fails()) {
//                return [
//                    'success' => false,
//                    'msg' => __("messages.something_went_wrong")
//                ];
//            }

            $input = $request->only(['name', 'code', 'expense_account', 'payee_id', 'is_sub_category','vat_claimed', 'parent_id', 'is_employee', 'employee_id']);

            $business_id = request()->session()->get('user.business_id');

            $code = explode('-', (string) $input['code']);

            if (!$request->payee_id) {
                $input['payee_id'] = '0';
            } else {
                // Optional cheque-payee tables are displayed in the dropdown as
                // payee_<table>_<id>.  expense_categories.payee_id is numeric, so
                // do not insert the string key into an integer column.
                $input['payee_id'] = is_numeric($request->payee_id) ? $request->payee_id : '0';
            }

            $input['business_id'] = $request->session()->get('user.business_id');

            // S348: Allow saving even if Expense Category Code setup is not present.
            // Prefix validation was blocking new categories on fresh tenants and made
            // the popup look broken.
            if (count($code) <= 1 && empty($input['code'])) {
                $input['code'] = 'EXP-' . (ExpenseCategory::where('business_id', $business_id)->count() + 1);
            }

            $expense_category = ExpenseCategory::create($input);
            $output = [
                'success' => true,
                'expense_category_id' => $expense_category->id,
                'msg' => __("expense.added_success")
            ];


        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ExpenseCategory  $expenseCategory
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        } 

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $expense_category = ExpenseCategory::where('business_id', $business_id)->find($id);
            list($expense_accounts, $expense_account_id, $account_access) = $this->getExpenseAccountOptions($business_id);

            $expense_categories = ExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
            $employees = EssentialsEmployee::pluck('name', 'id');
            $payees = $this->getPayeeOptions($business_id);
            return view('finance::expense_category.show')
                ->with(compact('expense_category', 'expense_accounts', 'expense_categories', 'employees', 'payees'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $expense_category = ExpenseCategory::where('business_id', $business_id)->find($id);
            list($expense_accounts, $expense_account_id, $account_access) = $this->getExpenseAccountOptions($business_id);

            $expense_categories = ExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
            $employees = EssentialsEmployee::pluck('name', 'id');
            $payees = $this->getPayeeOptions($business_id);
            return view('finance::expense_category.edit')
                ->with(compact('expense_category', 'expense_accounts', 'expense_categories', 'employees', 'payees'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'code', 'payee_id', 'expense_account', 'is_sub_category', 'parent_id','vat_claimed']);
                $business_id = $request->session()->get('user.business_id');

                $expense_category = ExpenseCategory::where('business_id', $business_id)->findOrFail($id);
                $expense_category->name = $input['name'];
                $expense_category->code = $input['code'];
                $expense_category->is_sub_category = !empty($input['is_sub_category']) ? 1 : 0;
                $expense_category->vat_claimed = !empty($input['vat_claimed']) ? 1 : 0;
                $expense_category->parent_id = $input['parent_id'];
                $expense_category->expense_account = $input['expense_account'];
                if (empty($request->payee_id)) {
                    $input['payee_id'] = '0';
                } else {
                    // Optional cheque-payee tables are displayed in the dropdown as
                    // payee_<table>_<id>.  expense_categories.payee_id is numeric, so
                    // do not insert the string key into an integer column during edit.
                    $input['payee_id'] = is_numeric($request->payee_id) ? $request->payee_id : '0';
                }
                $expense_category->payee_id = $input['payee_id'];
                $expense_category->save();
                
                

                $output = [
                    'success' => true,
                    'msg' => __("expense.updated_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $expense_category = ExpenseCategory::where('business_id', $business_id)->findOrFail($id);
                $expense_category->delete();

                $output = [
                    'success' => true,
                    'msg' => __("expense.deleted_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    public function getAccountIdByCategory($id = null)
    {
        $business_id = request()->session()->get('user.business_id');

        /*
         * LA-1150: tolerate an empty category id.
         *
         * The route is reached from more than twenty places across the app
         * (public/js/petrodirect_payment.js, payment.js, several module blades
         * and so on), and most build the URL as
         *     "/get-expense-account-category-id/" + $(this).val()
         * with no check on the value. When the dropdown reads "Please Select"
         * that produced a trailing-slash URL matching no route, and the user
         * got the red toast:
         *     "The route get-expense-account-category-id could not be found"
         *
         * The route parameter is now optional (routes/web.php and
         * routes/tenant.php) and an empty id returns the same empty shape the
         * callers already handle for an unknown category, instead of a 404.
         * Fixing it here covers every caller at once rather than patching each
         * script separately.
         */
        if ($id === null || $id === '' || !is_numeric($id)) {
            return ['expense_account_id' => null, 'name' => null, 'payee_name' => null];
        }

        $expense_category = ExpenseCategory::leftjoin('accounts', 'expense_account', 'accounts.id')
            ->leftjoin('contacts', 'contacts.id', '=', 'expense_categories.payee_id')
            ->where('expense_categories.id', $id)
            ->where('expense_categories.business_id', $business_id)
            ->select('expense_account', 'accounts.name', 'contacts.name as payee_name')
            ->first();

        return ['expense_account_id' => !empty($expense_category) ? $expense_category->expense_account : null, 'name' => !empty($expense_category) ? $expense_category->name : null, 'payee_name' => !empty($expense_category) ? $expense_category->payee_name : null];
    }

    public function getExpenseCategoryDropDown()
    {
        $business_id = request()->session()->get('user.business_id');
        $expense_category = ExpenseCategory::where('business_id', $business_id)->select('name', 'id')->get();

        $html = '<option value="">' . __("lang_v1.please_select") . '</option>';
        foreach ($expense_category as $category) {
            $html .= '<option value="' . $category->id . '">' . $category->name . '</option>';
        }

        return $html;
    }
    public function checkDuplicate(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $name = $request->name;
        $is_sub_category = $request->is_sub_category;
        $parent_name = $request->parent_name;

        if ($is_sub_category == 0) {
            $expense_category = ExpenseCategory::where('business_id', $business_id)->where('name', $name)->select('id')->first();
            if (!empty($expense_category)) {
                $output = [
                    'success' => '0',
                    'msg' => __('expense.duplicate_name_msg')
                ];
                return $output;
            }
        } else {
            $expense_category = ExpenseCategory::where('business_id', $business_id)->where('is_sub_category', 1)->where('name', $name)->select('id')->first();
            if (!empty($expense_category)) {
                $output = [
                    'success' => '0',
                    'msg' => __('expense.duplicate_name_msg')
                ];
                return $output;
            }
        }


        return null;
    }
}
