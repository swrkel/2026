<?php

namespace Modules\Vat\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use App\Business;
use App\Account;
use App\AccountType;
// Separation step 3 (document 5-18): the shared `contacts` table is now
// reached through a VAT-owned model, so this file no longer depends on the
// core App\Contact class when the Contact module is retired for Customers.
// NOTE: SharedContact maps to `contacts`; the existing VatContact entity
// maps to `vat_contacts` and is a different data set.
use Modules\Vat\Entities\SharedContact as Contact;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Vat\Entities\VatExpenseCategory;
use Yajra\DataTables\Facades\DataTables;

class VatExpenseCategoryController extends Controller
{
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;

    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $expense_categories = VatExpenseCategory::where('business_id', $business_id)
                ->select(['id', 'name', 'expense_code'])
                ->orderBy('name', 'asc');

            return DataTables::of($expense_categories)
                ->addColumn('action', function ($row) {
                    $edit_url = action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@edit', [$row->id]);
                    $delete_url = action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@destroy', [$row->id]);

                    return '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                            . e(__('messages.actions')) . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                            <li><a href="#" data-href="' . e($edit_url) . '" class="btn-modal" data-container=".expense_category_modal"><i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit')) . '</a></li>
                            <li><a href="#" data-href="' . e($delete_url) . '" class="delete_expense_category"><i class="glyphicon glyphicon-trash"></i> ' . e(__('messages.delete')) . '</a></li>
                        </ul>
                    </div>';
                })
                ->editColumn('expense_code', function ($row) {
                    return $row->expense_code ?: '-';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('vat::expense_category.index');
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $quick_add = request()->quick_add ? 1 : 0;
        $expense_accounts = $this->getExpenseAccountDropdown($business_id);
        $payees = $this->getPayeeDropdown($business_id);
        $default_expense_account_id = $this->getDefaultExpenseAccountId($business_id, $expense_accounts);
        $next_category_code = $this->getNextCategoryCode($business_id);

        if (request()->ajax()) {
            return view('vat::expense_category.create')->with(compact('quick_add', 'expense_accounts', 'payees', 'default_expense_account_id', 'next_category_code'));
        }

        return view('vat::expense_category.create_page')->with(compact('quick_add', 'expense_accounts', 'payees', 'default_expense_account_id', 'next_category_code'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:191',
                'expense_code' => 'nullable|string|max:191',
                'expense_account_id' => 'nullable|integer',
                'vat_input_claimed' => 'nullable|boolean',
                'payee_id' => 'nullable|integer',
            ]);

            $business_id = $request->session()->get('user.business_id');

            $data = [
                'business_id' => $business_id,
                'name' => $request->input('name'),
                'expense_code' => $request->input('expense_code') ?: $this->getNextCategoryCode($business_id),
            ];

            if (Schema::hasColumn('vat_expense_categories', 'expense_account_id')) {
                $data['expense_account_id'] = $request->input('expense_account_id') ?: $this->getDefaultExpenseAccountId($business_id, $this->getExpenseAccountDropdown($business_id));
            }

            if (Schema::hasColumn('vat_expense_categories', 'vat_input_claimed')) {
                $data['vat_input_claimed'] = $request->boolean('vat_input_claimed') ? 1 : 0;
            }

            if (Schema::hasColumn('vat_expense_categories', 'payee_id')) {
                $data['payee_id'] = $request->input('payee_id') ?: 0;
            }

            $expense_category = VatExpenseCategory::create($data);

            $output = [
                'success' => true,
                'expense_category_id' => $expense_category->id,
                'msg' => __('expense.added_success'),
            ];

            if ($request->ajax()) {
                return response()->json($output);
            }

            return redirect()->back()->with('status', $output);
        } catch (\Exception $e) {
            Log::emergency('VAT expense category store failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];

            if ($request->ajax()) {
                return response()->json($output);
            }

            return redirect()->back()->with('status', $output);
        }
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $expense_category = VatExpenseCategory::where('business_id', $business_id)->findOrFail($id);
        $expense_accounts = $this->getExpenseAccountDropdown($business_id);
        $payees = $this->getPayeeDropdown($business_id);
        $default_expense_account_id = $this->getDefaultExpenseAccountId($business_id, $expense_accounts);

        if (request()->ajax()) {
            return view('vat::expense_category.edit')->with(compact('expense_category', 'expense_accounts', 'payees', 'default_expense_account_id'));
        }

        return view('vat::expense_category.edit_page')->with(compact('expense_category', 'expense_accounts', 'payees', 'default_expense_account_id'));
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:191',
                'expense_code' => 'nullable|string|max:191',
                'expense_account_id' => 'nullable|integer',
                'vat_input_claimed' => 'nullable|boolean',
                'payee_id' => 'nullable|integer',
            ]);

            $business_id = $request->session()->get('user.business_id');
            $expense_category = VatExpenseCategory::where('business_id', $business_id)->findOrFail($id);
            $expense_category->name = $request->input('name');
            $expense_category->expense_code = $request->input('expense_code');

            if (Schema::hasColumn('vat_expense_categories', 'expense_account_id')) {
                $expense_category->expense_account_id = $request->input('expense_account_id') ?: $this->getDefaultExpenseAccountId($business_id, $this->getExpenseAccountDropdown($business_id));
            }

            if (Schema::hasColumn('vat_expense_categories', 'vat_input_claimed')) {
                $expense_category->vat_input_claimed = $request->boolean('vat_input_claimed') ? 1 : 0;
            }

            if (Schema::hasColumn('vat_expense_categories', 'payee_id')) {
                $expense_category->payee_id = $request->input('payee_id') ?: 0;
            }

            $expense_category->save();

            return [
                'success' => true,
                'msg' => __('expense.updated_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('VAT expense category update failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    public function destroy($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $expense_category = VatExpenseCategory::where('business_id', $business_id)->findOrFail($id);
            $expense_category->delete();

            return [
                'success' => true,
                'msg' => __('expense.deleted_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('VAT expense category delete failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    private function getPayeeDropdown($business_id)
    {
        try {
            $query = Contact::where('business_id', $business_id);
            $query->where(function ($q) {
                if (Schema::hasColumn('contacts', 'is_payee')) {
                    $q->where('is_payee', 1);
                }
                $q->orWhereIn('type', ['supplier', 'both', 'contact', 'payee']);
            });

            return ['' => __('messages.please_select')] + $query->orderBy('name')->pluck('name', 'id')->toArray();
        } catch (\Exception $e) {
            Log::warning('VAT expense category payee dropdown failed: ' . $e->getMessage());
            return ['' => __('messages.please_select')];
        }
    }

    private function getExpenseAccountDropdown($business_id)
    {
        try {
            $expense_account_type_id = AccountType::getAccountTypeIdByName('Expenses', $business_id, true);
            $query = Account::where('business_id', $business_id);
            if (!empty($expense_account_type_id)) {
                $query->where('account_type_id', $expense_account_type_id);
            }

            $accounts = $query->orderBy('name')->pluck('name', 'id')->toArray();
            if (!empty($accounts)) {
                return $accounts;
            }

            return Account::where('business_id', $business_id)->orderBy('name')->pluck('name', 'id')->toArray();
        } catch (\Exception $e) {
            Log::warning('VAT expense category account dropdown failed: ' . $e->getMessage());
            return [];
        }
    }

    private function getDefaultExpenseAccountId($business_id, array $expense_accounts = [])
    {
        if (!empty($expense_accounts)) {
            foreach ($expense_accounts as $id => $name) {
                if (strtolower(trim($name)) === 'expenses' || strtolower(trim($name)) === 'expense') {
                    return $id;
                }
            }
            return array_key_first($expense_accounts);
        }

        return null;
    }

    private function getNextCategoryCode($business_id)
    {
        $business = Business::find($business_id);
        $prefixes = $this->normalizeRefSetting($business->ref_no_prefixes ?? []);
        $starting_numbers = $this->normalizeRefSetting($business->ref_no_starting_number ?? []);

        $prefix = $prefixes['vat_expense_category'] ?? $prefixes['expense_category'] ?? $prefixes['expense'] ?? 'EC-';
        $starting_no_string = (string) ($starting_numbers['vat_expense_category'] ?? $starting_numbers['expense_category'] ?? '1');
        $starting_no = (int) ($starting_no_string ?: 1);
        $pad_length = max(strlen($starting_no_string), 1);

        $latest_code = VatExpenseCategory::where('business_id', $business_id)
            ->whereNotNull('expense_code')
            ->orderBy('id', 'desc')
            ->value('expense_code');

        $next_no = $starting_no;
        if (!empty($latest_code) && preg_match('/(\d+)$/', $latest_code, $matches)) {
            $next_no = max(((int) $matches[1]) + 1, $starting_no);
        }

        return $prefix . str_pad((string) $next_no, $pad_length, '0', STR_PAD_LEFT);
    }

    private function normalizeRefSetting($setting)
    {
        if (is_array($setting)) {
            return $setting;
        }
        if (is_object($setting)) {
            return (array) $setting;
        }
        if (is_string($setting) && trim($setting) !== '') {
            $decoded = json_decode($setting, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

}
