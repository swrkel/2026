<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Transaction;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\DefaultAccountType;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Business;

use Modules\Superadmin\Entities\AccountNumber;
use Yajra\DataTables\Facades\DataTables;

use App\Category;

class AccountNumbersController extends Controller
{
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil =  $moduleUtil;
        $this->productUtil =  $productUtil;
        $this->transactionUtil =  $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (request()->ajax()) {
            $business_id = session()->get('user.business_id');
            
            $drivers = AccountNumber::leftjoin('default_account_types as type', 'account_numbers.account_type', 'type.id')
                ->where('account_numbers.business_id',$business_id)
                ->select([
                    'account_numbers.*',
                    'type.name as type',
                ]);

            
            return DataTables::of($drivers)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                        
                            $html .= '<li><a href="#" data-href="' . action('\Modules\Superadmin\Http\Controllers\AccountNumbersController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        
                            $html .= '<li><a href="#" data-href="' . action('\Modules\Superadmin\Http\Controllers\AccountNumbersController@destroy', [$row->id]) . '" class="delete_account_transaction"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        
                        $html .= '<li class="divider"></li>';
                        
                        return $html;
                    }
                )
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $business_id = session()->get('user.business_id');
        $account_types = DefaultAccountType::where('business_id', $business_id)
            ->whereNull('parent_account_type_id')
            ->with(['sub_types'])
            ->get();

        $asset_type_ids = json_encode(DefaultAccountType::getAccountTypeIdOfType('Assets', $business_id));

        return view('superadmin::superadmin_settings.account_numbers.create')->with(compact(
            'account_types', 'asset_type_ids'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_type' => ['required', 'integer'],
                'prefix' => ['nullable', 'string', 'max:50'],
                'account_number' => ['required', 'integer', 'min:1'],
            ]);

            $data = [
                'account_type' => (int) $validated['account_type'],
                'prefix' => trim((string) ($validated['prefix'] ?? '')),
                'account_number' => (int) $validated['account_number'],
            ];

            $updatedAccounts = 0;

            DB::transaction(function () use ($data, &$updatedAccounts) {
                $businesses = Business::get();

                foreach ($businesses as $business) {
                    $businessId = (int) $business->id;
                    $payload = $data;
                    $payload['business_id'] = $businessId;

                    AccountNumber::updateOrInsert(
                        ['account_type' => $data['account_type'], 'business_id' => $businessId],
                        $payload
                    );

                    // Finance is optional. When installed, immediately apply
                    // the changed Super Admin prefix/start values to existing
                    // Accounts. The service also checks overlapping ranges and
                    // performs safe temporary renumbering inside a transaction.
                    if (class_exists(\Modules\Finance\Services\FinanceAccountNumberService::class)) {
                        $result = app(\Modules\Finance\Services\FinanceAccountNumberService::class)
                            ->renumberExistingAccountsForBusiness($businessId);
                        $updatedAccounts += (int) ($result['updated_accounts'] ?? 0);
                    }
                }
            });

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success') . ' ' . $updatedAccounts . ' existing Account(s) renumbered.',
                'updated_accounts' => $updatedAccounts,
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            $output = [
                'success' => false,
                'msg' => $e->validator->errors()->first(),
            ];
        } catch (\Throwable $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => $e->getMessage() ?: __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        //   
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $acc_no = AccountNumber::find($id);
        
        $business_id = session()->get('user.business_id');
        $account_types = DefaultAccountType::where('business_id', $business_id)
            ->whereNull('parent_account_type_id')
            ->with(['sub_types'])
            ->get();

        $asset_type_ids = json_encode(DefaultAccountType::getAccountTypeIdOfType('Assets', $business_id));

        return view('superadmin::superadmin_settings.account_numbers.edit')->with(compact(
            'acc_no','account_types', 'asset_type_ids'
        ));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        // 
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $account = AccountNumber::find($id);
            
            AccountNumber::where('account_type', $account->account_type)->delete();


            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
}
