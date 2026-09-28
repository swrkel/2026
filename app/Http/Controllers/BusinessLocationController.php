<?php

namespace App\Http\Controllers;

use App\Account;
use App\Currency;
use App\Utils\Util;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\BusinessLocation;
use App\Services\BranchAccountingService;
use App\Utils\ModuleUtil;
use App\SellingPriceGroup;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;
use Modules\Property\Entities\PaymentOption;
use Modules\Property\Entities\InstallmentCycle;
use Modules\Property\Entities\PurchaseLandAccount;

class BusinessLocationController extends Controller
{
    protected $moduleUtil;
    protected $commonUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $branchAccountingService;

    /**
     * Constructor
     *
     * @param ModuleUtil $moduleUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, Util $commonUtil, BusinessUtil $businessUtil, BranchAccountingService $branchAccountingService)
    {
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->branchAccountingService = $branchAccountingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            /*
             * Keep the list request lightweight. Location dependency checks can
             * touch many module tables, so they are performed only when the
             * Action menu is opened for a specific row. The destroy action also
             * repeats the check and remains the authoritative safety guard.
             */
            $has_multiple_locations = BusinessLocation::where('business_id', $business_id)
                ->limit(2)
                ->pluck('id')
                ->count() > 1;

            $locations = BusinessLocation::where('business_locations.business_id', $business_id)
                ->leftjoin(
                    'currencies as cr',
                    'business_locations.currency_id',
                    '=',
                    'cr.id'
                )
                ->leftjoin(
                    'invoice_schemes as ic',
                    'business_locations.invoice_scheme_id',
                    '=',
                    'ic.id'
                )
                ->leftjoin(
                    'invoice_layouts as il',
                    'business_locations.invoice_layout_id',
                    '=',
                    'il.id'
                )
                ->leftjoin(
                    'selling_price_groups as spg',
                    'business_locations.selling_price_group_id',
                    '=',
                    'spg.id'
                )
                ->select([
                    'business_locations.name',
                    'location_id',
                    'landmark',
                    'business_locations.id',
                    'spg.name as price_group',
                    'cr.symbol as currency',
                    'ic.name as invoice_scheme',
                    'il.name as invoice_layout',
                    'business_locations.is_active',
                ]);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $locations->whereIn('business_locations.id', $permitted_locations);
            }

            return Datatables::of($locations)
                ->editColumn('landmark', function ($location) {
                    $landmark = trim((string) $location->landmark);

                    if ($landmark === '') {
                        return '<span class="text-muted">&mdash;</span>';
                    }

                    return '<button type="button" class="btn btn-xs btn-info view-location-landmark" data-landmark="'
                        . e($landmark)
                        . '"><i class="fa fa-map-marker"></i><span class="landmark-button-label">Click to<br>View</span></button>';
                })
                ->addColumn('action', function ($location) use ($has_multiple_locations) {
                    $location_id = (int) $location->id;

                    $edit_url = action('BusinessLocationController@edit', [$location_id]);
                    $settings_url = route('location.settings', [$location_id]);
                    $status_url = action('BusinessLocationController@activateDeactivateLocation', [$location_id]);
                    $delete_check_url = url('business-location/' . $location_id . '/delete-eligibility');
                    $delete_url = action('BusinessLocationController@destroy', [$location_id]);

                    $is_active = (bool) $location->is_active;
                    $status_label = $is_active
                        ? __('lang_v1.deactivate_location')
                        : __('lang_v1.activate_location');
                    $status_class = $is_active
                        ? 'location-deactivate-menu-item'
                        : 'location-activate-menu-item';

                    $html = '<div class="btn-group business-location-action-menu">'
                        . '<button type="button" class="btn btn-primary btn-xs dropdown-toggle business-location-action-parent" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                        . '<i class="fa fa-cog"></i> ' . e(__('messages.action')) . ' <span class="caret"></span>'
                        . '</button>'
                        . '<ul class="dropdown-menu dropdown-menu-right" role="menu">'
                        . '<li><button type="button" data-href="' . e($edit_url) . '" class="business-location-menu-item location-edit-menu-item btn-modal" data-container=".location_edit_modal">'
                        . '<i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit')) . '</button></li>'
                        . '<li><a href="' . e($settings_url) . '" class="business-location-menu-item location-settings-menu-item">'
                        . '<i class="fa fa-wrench"></i> ' . e(__('messages.settings')) . '</a></li>'
                        . '<li><button type="button" data-href="' . e($status_url) . '" class="business-location-menu-item activate-deactivate-location ' . $status_class . '">'
                        . '<i class="fa fa-power-off"></i> ' . e($status_label) . '</button></li>';

                    if ($has_multiple_locations) {
                        $html .= '<li role="separator" class="divider location-delete-divider"></li>'
                            . '<li class="location-delete-slot" data-check-url="' . e($delete_check_url) . '" data-delete-url="' . e($delete_url) . '"></li>';
                    }

                    $html .= '</ul></div>';

                    return $html;
                })
                ->removeColumn('id')
                ->removeColumn('is_active')
                ->rawColumns(['landmark', 'action'])
                ->escapeColumns([])
                ->make(false);
        }

        return view('business_location.index');
    }

    /**
     * Tables containing only removable location setup records.
     *
     * These rows are not transaction/form usage and can be cleaned safely when
     * an otherwise-unused business location is deleted.
     */
    private function getSafeLocationCleanupTables(): array
    {
        return [
            'product_locations',
        ];
    }

    /**
     * Return all supplied business-location IDs referenced by operational data.
     *
     * @param array<int, int|string> $location_ids
     * @return array<int, int>
     */
    private function getUsedBusinessLocationIds(array $location_ids): array
    {
        $location_ids = array_values(array_unique(array_filter(array_map('intval', $location_ids))));

        if (empty($location_ids)) {
            return [];
        }

        $location_columns = [
            'location_id',
            'business_location_id',
            'from_location_id',
            'to_location_id',
            'source_location_id',
            'destination_location_id',
            'source_business_location_id',
            'destination_business_location_id',
            'delivery_location_id',
            'asset_location_id',
            'vault_location_id',
        ];

        $ignored_tables = array_merge(
            ['business_locations'],
            $this->getSafeLocationCleanupTables()
        );

        $table_prefix = DB::getTablePrefix();
        if ($table_prefix !== '') {
            foreach ($ignored_tables as $ignored_table) {
                $ignored_tables[] = $table_prefix . $ignored_table;
            }
        }

        $ignored_tables = array_values(array_unique($ignored_tables));
        $used_location_ids = [];

        try {
            if (DB::getDriverName() === 'mysql') {
                $location_references = DB::table('information_schema.columns as c')
                    ->join('information_schema.tables as t', function ($join) {
                        $join->on('t.TABLE_SCHEMA', '=', 'c.TABLE_SCHEMA')
                            ->on('t.TABLE_NAME', '=', 'c.TABLE_NAME');
                    })
                    ->selectRaw('c.TABLE_NAME as table_name, c.COLUMN_NAME as column_name, c.DATA_TYPE as data_type')
                    ->where('c.TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('t.TABLE_TYPE', 'BASE TABLE')
                    ->whereIn('c.COLUMN_NAME', $location_columns)
                    ->whereNotIn('c.TABLE_NAME', $ignored_tables)
                    ->get();

                $numeric_data_types = [
                    'tinyint',
                    'smallint',
                    'mediumint',
                    'int',
                    'integer',
                    'bigint',
                    'decimal',
                    'numeric',
                ];

                $exists_clauses = [];

                foreach ($location_references as $reference) {
                    $table = (string) $reference->table_name;
                    $column = (string) $reference->column_name;
                    $data_type = strtolower((string) $reference->data_type);

                    /*
                     * Business-location references are numeric IDs. Skipping
                     * character columns avoids treating fields that store a
                     * branch code (for example accounts.location_id = "BL001")
                     * as a reference to business_locations.id.
                     */
                    if (!in_array($data_type, $numeric_data_types, true)) {
                        continue;
                    }

                    // Names originate from information_schema, but validate them
                    // before placing them into a quoted SQL identifier.
                    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)
                        || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
                        continue;
                    }

                    $exists_clauses[] = 'EXISTS (SELECT 1 FROM `'
                        . $table
                        . '` WHERE `'
                        . $column
                        . '` = bl.id LIMIT 1)';
                }

                if (!empty($exists_clauses)) {
                    /*
                     * Use one EXISTS query instead of issuing one query per
                     * module table. This keeps the Business Locations list fast
                     * even when many standalone modules are installed.
                     */
                    $business_locations_table = DB::getTablePrefix() . 'business_locations';
                    $placeholders = implode(',', array_fill(0, count($location_ids), '?'));

                    $used_rows = DB::select(
                        'SELECT bl.id FROM `'
                        . $business_locations_table
                        . '` AS bl WHERE bl.id IN ('
                        . $placeholders
                        . ') AND ('
                        . implode(' OR ', $exists_clauses)
                        . ')',
                        $location_ids
                    );

                    foreach ($used_rows as $used_row) {
                        $used_location_ids[] = (int) $used_row->id;
                    }
                }
            } else {
                // Fallback for test/non-MySQL environments.
                $fallback_tables = [
                    'transactions',
                    'variation_location_details',
                    'cash_registers',
                    'stores',
                ];

                foreach ($fallback_tables as $table) {
                    if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'location_id')) {
                        continue;
                    }

                    $referenced_ids = DB::table($table)
                        ->whereIn('location_id', $location_ids)
                        ->distinct()
                        ->pluck('location_id');

                    foreach ($referenced_ids as $referenced_id) {
                        $used_location_ids[] = (int) $referenced_id;
                    }
                }
            }
        } catch (\Throwable $e) {
            /*
             * Fail closed. If dependency inspection cannot be completed, hide
             * Delete for every location instead of risking data loss.
             */
            Log::warning(
                'Business location dependency inspection failed. File:' . $e->getFile()
                . ' Line:' . $e->getLine()
                . ' Message:' . $e->getMessage()
            );

            return $location_ids;
        }

        return array_values(array_unique(array_intersect($location_ids, $used_location_ids)));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for location quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('locations', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('locations', $business_id);
        }

        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');

        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');

        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $payment_types = $this->commonUtil->payment_types();

        $branch_id = $this->businessUtil->getIdWithIncrement($business_id, 'business_location');

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        $currency = Currency::select(['id',DB::raw("CONCAT(country,' ',currency,' ',symbol)  AS name")])->pluck('name','id');
        $countries = DB::table('countries')->orderBy('country')->pluck('country', 'country')->toArray();
       

        return view('business_location.create')
            ->with(compact(
                'invoice_layouts',
                'invoice_schemes',
                'price_groups',
                'payment_types',
                'accounts',
                'branch_id',
                'currency',
                'countries'
            ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not, then check for location quota
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            } elseif (!$this->moduleUtil->isQuotaAvailable('locations', $business_id)) {
                return $this->moduleUtil->quotaExpiredResponse('locations', $business_id);
            }

            // Validate mobile number for uniqueness
            $request->validate([
                'mobile' => 'nullable|unique:business_locations,mobile',
            ], [
                'mobile.unique' => 'Mobile Number is already there. Please select a different Mobile Number',
            ]);
            
            $input = $request->only([
                'name', 'landmark', 'city', 'state', 'country', 'district', 'zip_code', 'invoice_scheme_id',
                'invoice_layout_id', 'mobile', 'alternate_number', 'email', 'website', 'location_id', 'selling_price_group_id','address_1','address_2','address_3','currency_id' 
            ]);

            $input['business_id'] = $business_id;

            $dpa = $request->input('default_payment_accounts');
            $input['default_payment_accounts'] = !empty($dpa) ? json_encode($dpa) : '{}';

            //Update reference count
            $ref_count = $this->moduleUtil->setAndGetReferenceCount('business_location');

            if (empty($input['location_id'])) {
                $input['location_id'] = $this->moduleUtil->generateReferenceNumber('business_location', $ref_count);
            }

            $location = BusinessLocation::create($input);

            /*
            |--------------------------------------------------------------------------
            | Auto Create Branch Accounting Structure
            |--------------------------------------------------------------------------
            */

$this->branchAccountingService->createBranchAccounts(
    $business_id,
    $location->location_id,
    $location->name
);

            //Create a new permission related to the created location
            Permission::create(['name' => 'location.' . $location->id]);
            
            // create advance default account
            $this-> checkCreateAdvances($business_id);
            $this-> checkCreateLand($business_id);
            $this->checkCreateCycles($business_id,'Daily');
            $this->checkCreateCycles($business_id,'Weekly');
            $this->checkCreateCycles($business_id,'Bi-Montly');
            $this->checkCreateCycles($business_id,'Monthly');
            $this->checkCreateCycles($business_id,'Quarterly');
            $this->checkCreateCycles($business_id,'Bi-Annually');
            $this->checkCreateCycles($business_id,'Annually');
            Account::ensureIncomeFreeProductsOrSamplesAccountsByBusiness($business_id, (int) $request->session()->get('user.id'));
            
            
            $output = [
                'success' => true,
                'msg' => __("business.business_location_added_success"),
                'redirect_url' => action('BusinessLocationController@index'),
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        // The location form is normally submitted through AJAX from the list page.
        // Keep the JSON contract for AJAX calls, but redirect normal form posts back
        // to the Business Locations list instead of rendering the JSON response.
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json($output);
        }

        if ($output['success']) {
            return redirect($output['redirect_url'])->with('status', $output);
        }

        return redirect()->back()->withInput()->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $location = BusinessLocation::where('business_id', $business_id)
            ->find($id);
        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');
        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');

        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $payment_types = $this->commonUtil->payment_types();

        //Accounts
        $accounts = [];
        //only current assets type accounts 
        $accounts = Account::leftjoin('account_types', 'accounts.account_type_id', 'account_types.id')->where('accounts.business_id', $business_id)->notClosed()->where(function ($query) {
            $query->where('account_types.name', 'Current Assets');
        })->pluck('accounts.name', 'accounts.id');

        $currency = Currency::select(['id',DB::raw("CONCAT(country,' ',currency,' ',symbol)  AS name")])->pluck('name','id');
        $countries = DB::table('countries')->orderBy('country')->pluck('country', 'country')->toArray();
       
        return view('business_location.edit')
            ->with(compact(
                'location',
                'invoice_layouts',
                'invoice_schemes',
                'price_groups',
                'payment_types',
                'accounts',
                'currency',
                'countries'
            ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Validate mobile number for uniqueness (excluding current location)
            $request->validate([
                'mobile' => 'nullable|unique:business_locations,mobile,' . $id,
            ], [
                'mobile.unique' => 'Mobile Number is already there. Please select a different Mobile Number',
            ]);
            
            $input = $request->only([
                'landmark', 'city', 'state', 'country', 'district', 'invoice_scheme_id',
                'invoice_layout_id', 'mobile', 'alternate_number', 'email', 'website', 'selling_price_group_id','address_1','address_2','address_3','currency_id' 
            ]);

            $business_id = $request->session()->get('user.business_id');

            // $input['default_payment_accounts'] = !empty($input['default_payment_accounts']) ? json_encode($input['default_payment_accounts']) : null;

            BusinessLocation::where('business_id', $business_id)
                ->where('id', $id)
                ->update($input);
                
            
            $this-> checkCreateAdvances($business_id);
            $this-> checkCreateLand($business_id);
            $this->checkCreateCycles($business_id,'Daily');
            $this->checkCreateCycles($business_id,'Weekly');
            $this->checkCreateCycles($business_id,'Bi-Montly');
            $this->checkCreateCycles($business_id,'Monthly');
            $this->checkCreateCycles($business_id,'Quarterly');
            $this->checkCreateCycles($business_id,'Bi-Annually');
            $this->checkCreateCycles($business_id,'Annually');
            Account::ensureIncomeFreeProductsOrSamplesAccountsByBusiness($business_id, (int) $request->session()->get('user.id'));

            $output = [
                'success' => true,
                'msg' => __('business.business_location_updated_success')
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    
    public function checkCreateAdvances($business_id){
        // insert default if the business was already created without
        $payment_option = PaymentOption::where('business_id', $business_id)
            ->where('payment_option','Advance Amount')
            ->where('is_default',1)
            ->count();
        $linked_id = $this->transactionUtil->account_exist_return_id('Advance Account – Sale of Land Blocks');
        if(!$payment_option > 0){
             PaymentOption::create([
                'business_id' => $business_id,
                'payment_option' => 'Advance Amount',
                'date' => date('Y-m-d'),
                'location_id' => $business_id,
                'created_by' => request()->session()->get('user.id'),
                'is_default' => 1,
                'credit_account' => $linked_id ?? 0,
                'credit_account_type' => 0,
                'credit_sub_account_type' => 0,
            ]); 
         } 
             
        return true;
    }
    
    
    public function checkCreateLand($business_id){
        // insert default if the business was already created without
        $payment_option = PurchaseLandAccount::where('business_id', $business_id)
            ->where('payment_option','Advance Amount')
            ->where('is_default',1)
            ->count();
        $linked_id = $this->transactionUtil->account_exist_return_id('Advance Account – Sale of Land Blocks');
        if(!$payment_option > 0){
             PurchaseLandAccount::create([
                'business_id' => $business_id,
                'payment_option' => 'Advance Amount',
                'date' => date('Y-m-d'),
                'location_id' => $business_id,
                'created_by' => request()->session()->get('user.id'),
                'is_default' => 1,
                'credit_account' => $linked_id ?? 0,
                'credit_account_type' => 0,
                'credit_sub_account_type' => 0,
            ]); 
         } 
             
        return true;
    }
    
    
    public function checkCreateCycles($business_id,$cycle){
        // insert default if the business was already created without
        $payment_option = InstallmentCycle::where('business_id', $business_id)
            ->where('name',$cycle)
            ->where('is_default',1)
            ->count();
        
        if(!$payment_option > 0){
             InstallmentCycle::create([
                'business_id' => $business_id,
                'name' => $cycle,
                'date' => date('Y-m-d'),
                'cycle_date' => date('Y-m-d'),
                'created_by' => request()->session()->get('user.id'),
                'is_default' => 1
            ]); 
         } 
             
        return true;
    }
    
    /**
     * Check whether Delete may be shown for one location.
     *
     * This is deliberately separate from the list DataTable request so loading
     * the Business Locations page never scans operational module tables.
     */
    public function checkDeleteEligibility($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $location = BusinessLocation::where('business_id', $business_id)
            ->select('id')
            ->findOrFail($id);

        $has_another_location = BusinessLocation::where('business_id', $business_id)
            ->where('id', '!=', $location->id)
            ->exists();

        if (!$has_another_location) {
            return response()->json([
                'success' => true,
                'can_delete' => false,
            ]);
        }

        $is_used = in_array(
            (int) $location->id,
            $this->getUsedBusinessLocationIds([(int) $location->id]),
            true
        );

        return response()->json([
            'success' => true,
            'can_delete' => !$is_used,
        ]);
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $location = BusinessLocation::where('business_id', $business_id)
                ->findOrFail($id);

            $has_another_location = BusinessLocation::where('business_id', $business_id)
                ->where('id', '!=', $location->id)
                ->exists();

            if (!$has_another_location) {
                return response()->json([
                    'success' => false,
                    'msg' => 'The last business location cannot be deleted.',
                ]);
            }

            /*
             * Re-check usage at deletion time. The Action menu is only a user
             * interface convenience; this server-side guard is authoritative.
             */
            if (in_array(
                (int) $location->id,
                $this->getUsedBusinessLocationIds([(int) $location->id]),
                true
            )) {
                return response()->json([
                    'success' => false,
                    'msg' => 'This location is already used in a form, transaction, stock, store, user, or module record. Please deactivate it instead of deleting it.',
                ]);
            }

            DB::transaction(function () use ($location) {
                foreach ($this->getSafeLocationCleanupTables() as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'location_id')) {
                        DB::table($table)->where('location_id', $location->id)->delete();
                    }
                }

                $permission = Permission::where('name', 'location.' . $location->id)->first();
                if (!empty($permission)) {
                    $permission->delete();
                }

                $location->delete();
            });

            $output = [
                'success' => true,
                'msg' => 'Business location deleted successfully.',
            ];
        } catch (\Exception $e) {
            Log::emergency(
                'File:' . $e->getFile()
                . ' Line:' . $e->getLine()
                . ' Message:' . $e->getMessage()
            );

            $output = [
                'success' => false,
                'msg' => 'The business location could not be deleted. It may still be linked to existing records.',
            ];
        }

        return response()->json($output);
    }

    /**
     * Checks if the given location id already exist for the current business.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkLocationId(Request $request)
    {
        $location_id = $request->input('location_id');

        $valid = 'true';
        if (!empty($location_id)) {
            $business_id = $request->session()->get('user.business_id');
            $hidden_id = $request->input('hidden_id');

            $query = BusinessLocation::where('business_id', $business_id)
                ->where('location_id', $location_id);
            if (!empty($hidden_id)) {
                $query->where('id', '!=', $hidden_id);
            }
            $count = $query->count();
            if ($count > 0) {
                $valid = 'false';
            }
        }
        echo $valid;
        exit;
    }

    /**
     * Function to activate or deactivate a location.
     * @param int $location_id
     *
     * @return json
     */
    public function activateDeactivateLocation($location_id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $business_location = BusinessLocation::where('business_id', $business_id)
                ->findOrFail($location_id);

            $business_location->is_active = !$business_location->is_active;
            $business_location->save();

            $msg = $business_location->is_active ? __('lang_v1.business_location_activated_successfully') : __('lang_v1.business_location_deactivated_successfully');

            $output = [
                'success' => true,
                'msg' => $msg
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
     * Get Location Currency
     **/
    public function getCurrency(Request $request)
    {
        $currency = BusinessLocation::with('currency')->find($request->id)->currency;
        return response()->json([
            'currency' => $currency->symbol,
        ]);
    }
}

