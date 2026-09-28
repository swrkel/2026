<?php

namespace Modules\MPCS\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class F20CDSFormController extends Controller
{
    /**
     * F 20 Form - CDS entry page with the requested F20 CDS Settings tab.
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->ensureSettingsTable();
        $business_id = $this->businessId();

        // Solution 7995-19: fast AJAX meter refresh.
        // When date/location is changed, return only meter JSON instead of reloading the full page.
        if ((int) request()->get('ajax_meter_data') === 1) {
            $ajax_form_date = request()->get('form_date') ?: now()->format('Y-m-d');
            $ajax_location_id = request()->get('location_id') ?: null;
            $ajax_pumps = $this->pumpList($business_id, $ajax_location_id);
            $ajax_meter_data_by_location = $this->meterDataByLocation($business_id, $ajax_form_date);
            $ajax_location_key = !empty($ajax_location_id) ? (string) $ajax_location_id : 'all';
            $ajax_meter_data = $ajax_meter_data_by_location[$ajax_location_key] ?? ($ajax_meter_data_by_location['all'] ?? []);

            return response()->json([
                'success' => 1,
                'form_date' => $ajax_form_date,
                'location_id' => $ajax_location_id,
                'pumps' => $ajax_pumps->map(function ($pump) {
                    return [
                        'id' => $pump->id,
                        'pump_name' => $pump->pump_name ?: ('Pump ' . $pump->id),
                        'location_id' => $pump->location_id,
                    ];
                })->values(),
                'meter_data' => $ajax_meter_data,
                'tanks' => $this->tankList($business_id, $ajax_location_id)->map(function ($tank) {
                    return [
                        'id' => $tank->id,
                        'tank_name' => $tank->tank_name ?: ('Tank ' . $tank->id),
                        'location_id' => $tank->location_id,
                    ];
                })->values(),
                'tank_data' => $this->tankDataByLocation($business_id, $ajax_form_date)[$ajax_location_key] ?? [],
                'daily_sales_categories' => $this->productSubcategoryList($business_id),
                'daily_sales_amounts' => $this->productSubcategorySalesByDate($business_id, $ajax_form_date),
                'credit_sales' => $this->creditSalesByCustomerForDate($business_id, $ajax_form_date, $ajax_location_id),
                'currency_precision' => $this->currencyPrecision($business_id),
                'quantity_precision' => $this->quantityPrecision($business_id),
            ]);
        }

        $business = Business::find($business_id);
        $currency_precision = $this->currencyPrecision($business_id);
        $quantity_precision = $this->quantityPrecision($business_id);
        $business_locations = $this->uniqueBusinessLocations(BusinessLocation::forDropdown($business_id));
        $settings = $this->latestSettings($business_id);
        $settings_list = $this->settingsList($business_id);
        $form_no = $this->nextFormNumber($business_id);
        $today = now()->format('Y-m-d');
        $form_date = request()->get('form_date') ?: (!empty($settings->opening_date) ? $settings->opening_date : $today);
        // Always load the main F 20 Form - CDS tab on normal page load/refresh.
        // Users can still click the Settings/List tabs without a page reload.
        $active_tab = 'form';
        $default_location_id = request()->get('location_id') ?: (!empty($business_locations) && is_array($business_locations) ? array_key_first($business_locations) : null);
        $f20_cds_pumps = $this->pumpList($business_id, $default_location_id);
        $f20_cds_pumps_by_location = $this->pumpListByLocation($business_id);
        $f20_cds_meter_data_by_location = $this->meterDataByLocation($business_id, $form_date);
        $f20_cds_tanks = $this->tankList($business_id, $default_location_id);
        $f20_cds_tanks_by_location = $this->tankListByLocation($business_id);
        $f20_cds_tank_data_by_location = $this->tankDataByLocation($business_id, $form_date);
        $f20_cds_daily_sales_categories = $this->productSubcategoryList($business_id);
        $f20_cds_daily_sales_amounts = $this->productSubcategorySalesByDate($business_id, $form_date);
        $f20_cds_credit_sales = $this->creditSalesByCustomerForDate($business_id, $form_date, $default_location_id);
        $forms = $this->recentForms($business_id);

        return view('mpcs::forms.F20_CDS.index')->with(compact(
            'business_id',
            'business',
            'business_locations',
            'settings',
            'settings_list',
            'form_no',
            'today',
            'form_date',
            'active_tab',
            'default_location_id',
            'f20_cds_pumps',
            'f20_cds_pumps_by_location',
            'f20_cds_meter_data_by_location',
            'f20_cds_tanks',
            'f20_cds_tanks_by_location',
            'f20_cds_tank_data_by_location',
            'f20_cds_daily_sales_categories',
            'f20_cds_daily_sales_amounts',
            'f20_cds_credit_sales',
            'forms',
            'currency_precision',
            'quantity_precision'
        ));
    }

    /**
     * Save F20 CDS settings: Opening Date and Form Starting No.
     */
    public function storeSettings(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->ensureSettingsTable();
        $business_id = $this->businessId();

        $request->validate([
            'opening_date' => 'required|date',
            'form_starting_no' => 'required|integer|min:1',
        ]);

        try {
            DB::table('mpcs_f20_cds_settings')->where('business_id', $business_id)->update([
                'is_active' => 0,
                'updated_at' => now(),
            ]);

            DB::table('mpcs_f20_cds_settings')->insert([
                'business_id' => $business_id,
                'opening_date' => $request->input('opening_date'),
                'form_starting_no' => (int) $request->input('form_starting_no'),
                'is_active' => 1,
                'created_by' => optional(Auth::user())->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@index')
                ->with('status', ['success' => 1, 'msg' => 'F20 CDS Settings saved successfully.']);
        } catch (\Exception $e) {
            Log::error('F20 CDS settings save failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return redirect()->back()->withInput()->with('f20_cds_active_tab', 'settings')->with('status', [
                'success' => 0,
                'msg' => 'Unable to save F20 CDS Settings. ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Save F 20 Form - CDS draft/foundation data.
     */
    public function store(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = $this->businessId();
        $this->ensureSettingsTable();

        if (!Schema::hasTable('mpcs_f20_cds_headers') || !Schema::hasTable('mpcs_f20_cds_details')) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'F 20 CDS database tables are missing. Please run the SQL file included in the ZIP first.'
            ])->withInput();
        }

        $request->validate([
            'form_no' => 'required',
            'form_date' => 'required|date',
            'location_id' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $header_id = DB::table('mpcs_f20_cds_headers')->insertGetId([
                'business_id' => $business_id,
                'location_id' => $request->input('location_id'),
                'form_no' => $request->input('form_no'),
                'form_date' => $request->input('form_date'),
                'society_name' => $request->input('society_name'),
                'manager_name' => $request->input('manager_name'),
                'cash_balance' => $this->num($request->input('cash_balance')),
                'sales_total' => $this->num($request->input('sales_total')),
                'expenses_total' => $this->num($request->input('expenses_total')),
                'cash_deposit_total' => $this->num($request->input('cash_deposit_total')),
                'remarks' => $request->input('remarks'),
                'status' => 'draft',
                'created_by' => optional(Auth::user())->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = $request->input('rows', []);
            foreach ($rows as $section => $items) {
                if (!is_array($items)) {
                    continue;
                }

                // Support both structures used by MPCS forms:
                // 1) rows[section][line_no][description/qty/amount]
                // 2) rows[unique_section][description/qty/amount]
                if (array_key_exists('description', $items) || array_key_exists('qty', $items) || array_key_exists('amount', $items)) {
                    $this->insertDetailRow($header_id, $business_id, $section, 1, $items);
                    continue;
                }

                foreach ($items as $line_no => $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $this->insertDetailRow($header_id, $business_id, $section, $line_no, $item);
                }
            }

            DB::commit();

            return redirect()->action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@print', [$header_id])
                ->with('status', ['success' => 1, 'msg' => 'F 20 Form – CDS saved successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('F20 CDS save failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'Unable to save F 20 Form – CDS. ' . $e->getMessage()
            ])->withInput();
        }
    }


    /**
     * Solution 7995-23: Product sub categories for Daily Sales Status.
     * Shows all product sub categories one below the other in the first Details column.
     */
    private function productSubcategoryList($business_id)
    {
        $items = collect();

        try {
            if (Schema::hasTable('categories')) {
                $query = DB::table('categories')->where('business_id', $business_id);

                if (Schema::hasColumn('categories', 'parent_id')) {
                    $query->whereNotNull('parent_id')->where('parent_id', '>', 0);
                }

                if (Schema::hasColumn('categories', 'category_type')) {
                    $query->where(function ($q) {
                        $q->whereNull('category_type')
                          ->orWhere('category_type', 'product')
                          ->orWhere('category_type', 'products');
                    });
                }

                $nameColumn = Schema::hasColumn('categories', 'name') ? 'name' : 'id';
                $items = $query->select('id', DB::raw($nameColumn === 'id' ? "CONCAT('Category ', id) as name" : "$nameColumn as name"))
                    ->orderBy($nameColumn === 'id' ? 'id' : $nameColumn)
                    ->get();
            }

            // Fallback: if product sub categories are not configured in categories,
            // show product names so the Daily Sales Status section is still populated.
            if ($items->isEmpty() && Schema::hasTable('products')) {
                $items = DB::table('products')
                    ->where('business_id', $business_id)
                    ->select('id', DB::raw(Schema::hasColumn('products', 'name') ? 'name as name' : "CONCAT('Product ', id) as name"))
                    ->orderBy(Schema::hasColumn('products', 'name') ? 'name' : 'id')
                    ->get();
            }
        } catch (\Exception $e) {
            Log::warning('F20 CDS product subcategory list skipped', [
                'business_id' => $business_id,
                'message' => $e->getMessage(),
            ]);
        }

        return $items->map(function ($item) {
            return [
                'id' => (int) $item->id,
                'name' => (string) ($item->name ?: ('Item ' . $item->id)),
            ];
        })->values();
    }

    /**
     * Sum selected-date sales amount by product sub category.
     *
     * Solution 7995-24:
     * User confirmed that product sub-categories are mapped with Income Accounts.
     * Therefore Daily Sales Status must come from account_transactions first,
     * using the sales account mapped in the categories table. The old meter_sales
     * calculation remains only as a fallback for unmapped categories.
     */
    private function productSubcategorySalesByDate($business_id, $form_date = null)
    {
        $selected_date = !empty($form_date) ? date('Y-m-d', strtotime($form_date)) : now()->format('Y-m-d');
        $amounts = [];

        // 1) Primary source: account_transactions grouped by category sales account mapping.
        try {
            if (Schema::hasTable('categories') && Schema::hasTable('account_transactions')) {
                $categories = $this->rawProductSubcategories($business_id);

                foreach ($categories as $category) {
                    $category_id = (int) $category->id;
                    $account_ids = $this->categorySalesAccountIds($category);

                    if (empty($account_ids)) {
                        continue;
                    }

                    $query = DB::table('account_transactions')
                        ->where('business_id', $business_id)
                        ->whereIn('account_id', $account_ids);

                    if (Schema::hasColumn('account_transactions', 'deleted_at')) {
                        $query->whereNull('deleted_at');
                    }

                    if (Schema::hasColumn('account_transactions', 'operation_date')) {
                        $query->whereDate('operation_date', $selected_date);
                    } elseif (Schema::hasColumn('account_transactions', 'created_at')) {
                        $query->whereDate('created_at', $selected_date);
                    }

                    if (Schema::hasColumn('account_transactions', 'type')) {
                        $total = $query->select(DB::raw("SUM(CASE WHEN type = 'credit' THEN amount WHEN type = 'debit' THEN -amount ELSE amount END) as total_amount"))->value('total_amount');
                    } else {
                        $total = $query->sum('amount');
                    }

                    $total = (float) $total;
                    if (abs($total) > 0.00001) {
                        $amounts[$category_id] = $this->formatAmount(abs($total), $business_id);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('F20 CDS account transaction subcategory sales skipped', [
                'selected_date' => $selected_date,
                'message' => $e->getMessage(),
            ]);
        }

        // 2) Fallback/source supplement: meter_sales grouped by product sub-category.
        // This keeps fuel/meter-based categories populated where no account mapping exists.
        if (Schema::hasTable('meter_sales') && Schema::hasTable('products') && Schema::hasColumn('meter_sales', 'product_id')) {
            try {
                $amountExpr = '0';
                if (Schema::hasColumn('meter_sales', 'sub_total')) {
                    $amountExpr = 'COALESCE(ms.sub_total, 0)';
                } elseif (Schema::hasColumn('meter_sales', 'amount')) {
                    $amountExpr = 'COALESCE(ms.amount, 0)';
                } elseif (Schema::hasColumn('meter_sales', 'total_amount')) {
                    $amountExpr = 'COALESCE(ms.total_amount, 0)';
                }

                $groupExpr = 'p.id';
                if (Schema::hasColumn('products', 'sub_category_id')) {
                    $groupExpr = 'COALESCE(p.sub_category_id, p.category_id, p.id)';
                } elseif (Schema::hasColumn('products', 'category_id')) {
                    $groupExpr = 'COALESCE(p.category_id, p.id)';
                }

                $query = DB::table('meter_sales as ms')
                    ->join('products as p', 'p.id', '=', 'ms.product_id')
                    ->where('ms.business_id', $business_id)
                    ->whereDate('ms.created_at', $selected_date)
                    ->select(DB::raw($groupExpr . ' as group_id'), DB::raw('SUM(' . $amountExpr . ') as total_amount'))
                    ->groupBy(DB::raw($groupExpr));

                $rows = $query->get();
                foreach ($rows as $row) {
                    $group_id = (int) $row->group_id;
                    if (!isset($amounts[$group_id]) || (float) str_replace(',', '', $amounts[$group_id]) == 0) {
                        $amounts[$group_id] = $this->formatAmount($row->total_amount, $business_id);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('F20 CDS meter sales subcategory fallback skipped', [
                    'selected_date' => $selected_date,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $amounts;
    }

    /**
     * Raw product sub-category rows from categories table.
     */
    private function rawProductSubcategories($business_id)
    {
        if (!Schema::hasTable('categories')) {
            return collect();
        }

        $query = DB::table('categories')->where('business_id', $business_id);

        if (Schema::hasColumn('categories', 'parent_id')) {
            $query->whereNotNull('parent_id')->where('parent_id', '>', 0);
        }

        if (Schema::hasColumn('categories', 'category_type')) {
            $query->where(function ($q) {
                $q->whereNull('category_type')
                  ->orWhere('category_type', 'product')
                  ->orWhere('category_type', 'products');
            });
        }

        return $query->get();
    }

    /**
     * Extract all possible linked sales/income account ids from a category row.
     * Different ERP builds used slightly different column names, so this supports
     * all known variants safely.
     */
    private function categorySalesAccountIds($category)
    {
        $ids = [];
        $possible_columns = [
            'sales_accounts',
            'sales_account',
            'sales_account_id',
            'sales_income_account',
            'sales_income_account_id',
            'income_account',
            'income_account_id',
        ];

        foreach ($possible_columns as $column) {
            if (isset($category->{$column}) && $category->{$column} !== null && $category->{$column} !== '') {
                $ids = array_merge($ids, $this->extractIds($category->{$column}));
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /**
     * Supports normal integer, comma-separated values, and simple JSON arrays.
     */
    private function extractIds($value)
    {
        if (is_array($value)) {
            return $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        preg_match_all('/\d+/', $value, $matches);
        return $matches[0] ?? [];
    }


    /**
     * Solution 7995-25: Credit sales customer list and amount for F20 CDS.
     * Loads credit sale customer names and selected-date amounts into the
     * right side Daily Sales Status table. Manual rows remain editable, and
     * the final total includes both credit sales and manual entries.
     */
    private function creditSalesByCustomerForDate($business_id, $form_date = null, $location_id = null)
    {
        $selected_date = !empty($form_date) ? date('Y-m-d', strtotime($form_date)) : now()->format('Y-m-d');
        $rows = collect();

        if (!Schema::hasTable('settlement_credit_sale_payments')) {
            return $rows;
        }

        try {
            $table = 'settlement_credit_sale_payments';
            $query = DB::table($table . ' as scsp')
                ->where('scsp.business_id', $business_id);

            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('scsp.deleted_at');
            }

            if (!empty($location_id)) {
                foreach (['location_id', 'business_location_id'] as $locColumn) {
                    if (Schema::hasColumn($table, $locColumn)) {
                        $query->where('scsp.' . $locColumn, $location_id);
                        break;
                    }
                }
            }

            if (Schema::hasColumn($table, 'order_date')) {
                $query->whereDate('scsp.order_date', $selected_date);
            } elseif (Schema::hasColumn($table, 'transaction_date')) {
                $query->whereDate('scsp.transaction_date', $selected_date);
            } elseif (Schema::hasColumn($table, 'date')) {
                $query->whereDate('scsp.date', $selected_date);
            } elseif (Schema::hasColumn($table, 'created_at')) {
                $query->whereDate('scsp.created_at', $selected_date);
            }

            $customerNameExpr = "CONCAT('Credit Customer ', COALESCE(scsp.customer_id, ''))";
            if (Schema::hasTable('contacts') && Schema::hasColumn($table, 'customer_id')) {
                $query->leftJoin('contacts as c', 'c.id', '=', 'scsp.customer_id');
                if (Schema::hasColumn('contacts', 'name')) {
                    $customerNameExpr = "COALESCE(c.name, CONCAT('Credit Customer ', COALESCE(scsp.customer_id, '')))";
                } elseif (Schema::hasColumn('contacts', 'supplier_business_name')) {
                    $customerNameExpr = "COALESCE(c.supplier_business_name, CONCAT('Credit Customer ', COALESCE(scsp.customer_id, '')))";
                }
            } elseif (Schema::hasColumn($table, 'customer_name')) {
                $customerNameExpr = "COALESCE(scsp.customer_name, CONCAT('Credit Customer ', COALESCE(scsp.customer_id, '')))";
            }

            $amountParts = [];
            foreach (['amount', 'sub_total', 'final_total', 'total_amount'] as $amountColumn) {
                if (Schema::hasColumn($table, $amountColumn)) {
                    $amountParts[] = 'COALESCE(scsp.' . $amountColumn . ', 0)';
                    break;
                }
            }
            $amountExpr = !empty($amountParts) ? $amountParts[0] : '0';

            if (Schema::hasColumn($table, 'total_discount')) {
                $amountExpr = '(' . $amountExpr . ' - COALESCE(scsp.total_discount, 0))';
            }

            $rows = $query
                ->select(DB::raw($customerNameExpr . ' as customer_name'), DB::raw('SUM(' . $amountExpr . ') as amount'))
                ->groupBy(DB::raw($customerNameExpr))
                ->havingRaw('ABS(SUM(' . $amountExpr . ')) > 0.00001')
                ->orderBy('customer_name')
                ->get()
                ->map(function ($row) {
                    return [
                        'customer_name' => $row->customer_name ?: 'Credit Customer',
                        'amount' => $this->formatAmount(abs((float) $row->amount), $business_id),
                    ];
                });
        } catch (\Exception $e) {
            Log::warning('F20 CDS credit sales customer load skipped', [
                'selected_date' => $selected_date,
                'message' => $e->getMessage(),
            ]);
        }

        return $rows;
    }

    private function recentForms($business_id)
    {
        if (!Schema::hasTable('mpcs_f20_cds_headers')) {
            return collect();
        }

        return DB::table('mpcs_f20_cds_headers')
            ->where('business_id', $business_id)
            ->orderBy('form_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(25)
            ->get();
    }

    public function list()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = $this->businessId();
        $forms = collect();
        if (Schema::hasTable('mpcs_f20_cds_headers')) {
            $forms = DB::table('mpcs_f20_cds_headers')
                ->where('business_id', $business_id)
                ->orderBy('form_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(25);
        }

        return view('mpcs::forms.F20_CDS.list')->with(compact('forms'));
    }

    public function print($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!Schema::hasTable('mpcs_f20_cds_headers') || !Schema::hasTable('mpcs_f20_cds_details')) {
            return redirect()->action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@index');
        }

        $business_id = $this->businessId();
        $header = DB::table('mpcs_f20_cds_headers')
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->first();

        if (!$header) {
            abort(404);
        }

        // Keep the saved-print view on the exact same visual/data structure used by
        // the live F20 CDS page.  The old print view flattened the saved report into
        // generic Bootstrap tables, which is why Print no longer resembled Page View.
        $detail_rows = DB::table('mpcs_f20_cds_details')
            ->where('business_id', $business_id)
            ->where('header_id', $id)
            ->orderBy('id')
            ->get();

        $details = $detail_rows->groupBy('section');
        $business = Business::find($business_id);
        $location = null;
        if (!empty($header->location_id)) {
            $location = BusinessLocation::where('business_id', $business_id)
                ->where('id', $header->location_id)
                ->first();
        }

        $pumps = $this->pumpList($business_id, $header->location_id ?: null);
        $tanks = $this->tankList($business_id, $header->location_id ?: null);

        // Preserve historical saved rows even if a pump/tank was later renamed or
        // deactivated.  Missing master records are represented by their saved ID.
        $saved_pump_ids = $detail_rows->map(function ($row) {
            return preg_match('/^meter_.+_(\d+)$/', (string) $row->section, $m) ? (int) $m[1] : null;
        })->filter()->unique()->values();

        $known_pump_ids = $pumps->pluck('id')->map(function ($id) { return (int) $id; })->all();
        foreach ($saved_pump_ids as $pump_id) {
            if (!in_array((int) $pump_id, $known_pump_ids, true)) {
                $pumps->push((object) [
                    'id' => (int) $pump_id,
                    'pump_name' => 'Pump ' . $pump_id,
                    'location_id' => $header->location_id,
                ]);
            }
        }

        $saved_tank_ids = $detail_rows->map(function ($row) {
            return preg_match('/^stock_.+_(\d+)$/', (string) $row->section, $m) ? (int) $m[1] : null;
        })->filter()->unique()->values();

        $known_tank_ids = $tanks->pluck('id')->map(function ($id) { return (int) $id; })->all();
        foreach ($saved_tank_ids as $tank_id) {
            if (!in_array((int) $tank_id, $known_tank_ids, true)) {
                $tanks->push((object) [
                    'id' => (int) $tank_id,
                    'tank_name' => 'Tank ' . $tank_id,
                    'location_id' => $header->location_id,
                ]);
            }
        }

        $currency_precision = $this->currencyPrecision($business_id);
        $quantity_precision = $this->quantityPrecision($business_id);

        return view('mpcs::forms.F20_CDS.print')->with(compact(
            'header',
            'details',
            'detail_rows',
            'business',
            'location',
            'pumps',
            'tanks',
            'currency_precision',
            'quantity_precision'
        ));
    }


    private function insertDetailRow($header_id, $business_id, $section, $line_no, array $item)
    {
        $description = trim((string)($item['description'] ?? ''));
        $amount = $this->num($item['amount'] ?? 0);
        $qty = $this->num($item['qty'] ?? 0);

        if ($description === '' && $amount == 0 && $qty == 0) {
            return;
        }

        DB::table('mpcs_f20_cds_details')->insert([
            'header_id' => $header_id,
            'business_id' => $business_id,
            'section' => $section,
            'line_no' => (int)$line_no,
            'description' => $description,
            'qty' => $qty,
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }


    private function pumpList($business_id, $location_id = null)
    {
        if (!Schema::hasTable('pumps')) {
            return collect();
        }

        $query = DB::table('pumps')
            ->where(function ($q) use ($business_id) {
                $q->where('business_id', $business_id);

                if (Schema::hasTable('products')) {
                    $q->orWhereIn('product_id', function ($sub) use ($business_id) {
                        $sub->select('id')->from('products')->where('business_id', $business_id);
                    });
                }
            });

        if (!empty($location_id) && Schema::hasColumn('pumps', 'location_id')) {
            $query->where('location_id', $location_id);
        }

        return $query->select('id', 'pump_name', 'location_id')
            ->orderByRaw('CAST(pump_name AS UNSIGNED) ASC')
            ->orderBy('pump_name')
            ->get();
    }

    private function pumpListByLocation($business_id)
    {
        $pumps = $this->pumpList($business_id, null);

        return $pumps->groupBy(function ($pump) {
            return !empty($pump->location_id) ? (string) $pump->location_id : 'all';
        })->map(function ($items) {
            return $items->map(function ($pump) {
                return [
                    'id' => $pump->id,
                    'pump_name' => $pump->pump_name ?: ('Pump ' . $pump->id),
                    'location_id' => $pump->location_id,
                ];
            })->values();
        });
    }



    private function tankList($business_id, $location_id = null)
    {
        if (!Schema::hasTable('fuel_tanks')) {
            return collect();
        }

        $query = DB::table('fuel_tanks')->where('business_id', $business_id);

        if (!empty($location_id) && Schema::hasColumn('fuel_tanks', 'location_id')) {
            $query->where('location_id', $location_id);
        }

        $nameColumn = Schema::hasColumn('fuel_tanks', 'fuel_tank_number') ? 'fuel_tank_number' : 'id';
        $select = [
            'id',
            DB::raw($nameColumn === 'id' ? "CONCAT('Tank ', id) as tank_name" : "$nameColumn as tank_name"),
            DB::raw(Schema::hasColumn('fuel_tanks', 'location_id') ? 'location_id' : 'NULL as location_id'),
            DB::raw(Schema::hasColumn('fuel_tanks', 'current_balance') ? 'current_balance' : '0 as current_balance'),
        ];

        return $query->select($select)
            ->orderBy('id')
            ->get();
    }

    private function tankListByLocation($business_id)
    {
        $tanks = $this->tankList($business_id, null);

        return $tanks->groupBy(function ($tank) {
            return !empty($tank->location_id) ? (string) $tank->location_id : 'all';
        })->map(function ($items) {
            return $items->map(function ($tank) {
                return [
                    'id' => $tank->id,
                    'tank_name' => $tank->tank_name ?: ('Tank ' . $tank->id),
                    'location_id' => $tank->location_id,
                ];
            })->values();
        });
    }

    /**
     * Solution 7995-27: Tank stock data for the Balance Stock section.
     *
     * This now follows the Petro / Tank Balance ledger logic instead of reading
     * fuel_tanks.current_balance directly.  The calculation is date based:
     *
     * Previous Balance = all purchases/opening/transfer-in before selected day
     *                    minus all sales/transfer-out before selected day.
     * Received Qty     = purchases/opening/stock-increase + transfer-in on selected day.
     * Issued           = sales/stock-decrease/deleted purchase + transfer-out on selected day.
     * Testing Qty      = meter testing qty for pumps linked to the same tank on selected day.
     * Day's Balance    = Previous + Received - Issued.
     */
    private function tankDataByLocation($business_id, $form_date = null)
    {
        $tanks = $this->tankList($business_id, null);
        $tank_data = [];
        $selected_date = !empty($form_date) ? date('Y-m-d', strtotime($form_date)) : now()->format('Y-m-d');
        $start = $selected_date . ' 00:00:00';
        $end = $selected_date . ' 23:59:59';

        foreach ($tanks as $tank) {
            $location_key = !empty($tank->location_id) ? (string) $tank->location_id : 'all';
            if (!isset($tank_data[$location_key])) {
                $tank_data[$location_key] = [];
            }
            if (!isset($tank_data['all'])) {
                $tank_data['all'] = [];
            }

            $previous_balance = $this->tankLedgerBalanceBefore($business_id, $tank->id, $start);
            $received_selected = $this->tankLedgerReceivedBetween($business_id, $tank->id, $start, $end);
            $issued_selected = $this->tankLedgerIssuedBetween($business_id, $tank->id, $start, $end);
            $testing_selected = $this->tankTestingQtyBetween($business_id, $tank->id, $start, $end);

            $total = $previous_balance + $received_selected;
            $balance = $total - $issued_selected;

            $values = [
                'previous_balance' => $this->formatFuelQty($previous_balance),
                'received_qty' => $this->formatFuelQty($received_selected),
                'total' => $this->formatFuelQty($total),
                'issued' => $this->formatFuelQty($issued_selected),
                'balance_qty' => $this->formatFuelQty($balance),
                'testing_qty' => $this->formatFuelQty($testing_selected),
                'days_balance_qty' => $this->formatFuelQty($balance),
            ];

            $tank_data[$location_key][$tank->id] = $values;
            $tank_data['all'][$tank->id] = $values;
        }

        return $tank_data;
    }

    private function tankLedgerBalanceBefore($business_id, $tank_id, $before_datetime)
    {
        return $this->tankLedgerReceivedBefore($business_id, $tank_id, $before_datetime)
            - $this->tankLedgerIssuedBefore($business_id, $tank_id, $before_datetime);
    }

    private function tankLedgerReceivedBefore($business_id, $tank_id, $before_datetime)
    {
        return $this->tankPurchaseQty($business_id, $tank_id, null, $before_datetime)
            + $this->tankTransferInQty($business_id, $tank_id, null, $before_datetime);
    }

    private function tankLedgerIssuedBefore($business_id, $tank_id, $before_datetime)
    {
        return $this->tankSellQty($business_id, $tank_id, null, $before_datetime)
            + $this->tankDeletedPurchaseQty($business_id, $tank_id, null, $before_datetime)
            + $this->tankTransferOutQty($business_id, $tank_id, null, $before_datetime);
    }

    private function tankLedgerReceivedBetween($business_id, $tank_id, $start_datetime, $end_datetime)
    {
        return $this->tankPurchaseQty($business_id, $tank_id, $start_datetime, $end_datetime)
            + $this->tankTransferInQty($business_id, $tank_id, $start_datetime, $end_datetime);
    }

    private function tankLedgerIssuedBetween($business_id, $tank_id, $start_datetime, $end_datetime)
    {
        return $this->tankSellQty($business_id, $tank_id, $start_datetime, $end_datetime)
            + $this->tankDeletedPurchaseQty($business_id, $tank_id, $start_datetime, $end_datetime)
            + $this->tankTransferOutQty($business_id, $tank_id, $start_datetime, $end_datetime);
    }

    private function tankPurchaseQty($business_id, $tank_id, $start_datetime = null, $end_datetime = null)
    {
        if (!Schema::hasTable('tank_purchase_lines') || !Schema::hasTable('transactions')) {
            return 0;
        }

        try {
            $query = DB::table('tank_purchase_lines')
                ->join('transactions', 'transactions.id', '=', 'tank_purchase_lines.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('tank_purchase_lines.business_id', $business_id)
                ->where('tank_purchase_lines.tank_id', $tank_id)
                ->where(function ($q) {
                    $q->whereIn('transactions.type', ['purchase', 'opening_stock'])
                        ->orWhere(function ($inner) {
                            $inner->where('transactions.type', 'stock_adjustment')
                                ->where(function ($sa) {
                                    $sa->where('transactions.sub_type', 'dip_resetting')
                                        ->orWhereNull('transactions.sub_type');
                                })
                                ->where('transactions.stock_adjustment_type', 'increase');
                        });
                });

            if (Schema::hasColumn('tank_purchase_lines', 'new_deleted_at')) {
                $query->whereNull('tank_purchase_lines.new_deleted_at');
            }

            $this->applyTransactionDateFilter($query, $start_datetime, $end_datetime);

            return (float) $query->sum('tank_purchase_lines.quantity');
        } catch (\Throwable $e) {
            Log::warning('F20 CDS tank purchase qty skipped', [
                'tank_id' => $tank_id,
                'message' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    private function tankDeletedPurchaseQty($business_id, $tank_id, $start_datetime = null, $end_datetime = null)
    {
        if (!Schema::hasTable('tank_purchase_lines') || !Schema::hasTable('transactions')) {
            return 0;
        }

        try {
            $query = DB::table('tank_purchase_lines')
                ->join('transactions', 'transactions.id', '=', 'tank_purchase_lines.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('tank_purchase_lines.business_id', $business_id)
                ->where('tank_purchase_lines.tank_id', $tank_id)
                ->where('transactions.type', '_deleted_purchase');

            $this->applyTransactionDateFilter($query, $start_datetime, $end_datetime);

            return (float) $query->sum('tank_purchase_lines.quantity');
        } catch (\Throwable $e) {
            Log::warning('F20 CDS tank deleted purchase qty skipped', [
                'tank_id' => $tank_id,
                'message' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    private function tankSellQty($business_id, $tank_id, $start_datetime = null, $end_datetime = null)
    {
        if (!Schema::hasTable('tank_sell_lines') || !Schema::hasTable('transactions')) {
            return 0;
        }

        try {
            $query = DB::table('tank_sell_lines')
                ->join('transactions', 'transactions.id', '=', 'tank_sell_lines.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('tank_sell_lines.business_id', $business_id)
                ->where('tank_sell_lines.tank_id', $tank_id)
                ->where(function ($q) {
                    $q->where(function ($normal) {
                        $normal->whereNotIn('transactions.type', ['purchase', '_deleted_purchase', 'stock_adjustment']);
                    })->orWhere(function ($adjustment) {
                        $adjustment->where('transactions.type', 'stock_adjustment')
                            ->where(function ($sa) {
                                $sa->where('transactions.sub_type', 'dip_resetting')
                                    ->orWhereNull('transactions.sub_type');
                            })
                            ->where('transactions.stock_adjustment_type', 'decrease');
                    });
                });

            $this->applyTransactionDateFilter($query, $start_datetime, $end_datetime);

            return (float) $query->sum('tank_sell_lines.quantity');
        } catch (\Throwable $e) {
            Log::warning('F20 CDS tank sell qty skipped', [
                'tank_id' => $tank_id,
                'message' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    private function tankTransferInQty($business_id, $tank_id, $start_datetime = null, $end_datetime = null)
    {
        if (!Schema::hasTable('tank_transfers')) {
            return 0;
        }

        try {
            $query = DB::table('tank_transfers')
                ->where('business_id', $business_id)
                ->where('to_tank', $tank_id);

            $this->applyGenericDateFilter($query, 'date', 'created_at', $start_datetime, $end_datetime);

            return (float) $query->sum('quantity');
        } catch (\Throwable $e) {
            Log::warning('F20 CDS tank transfer-in qty skipped', [
                'tank_id' => $tank_id,
                'message' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    private function tankTransferOutQty($business_id, $tank_id, $start_datetime = null, $end_datetime = null)
    {
        if (!Schema::hasTable('tank_transfers')) {
            return 0;
        }

        try {
            $query = DB::table('tank_transfers')
                ->where('business_id', $business_id)
                ->where('from_tank', $tank_id);

            $this->applyGenericDateFilter($query, 'date', 'created_at', $start_datetime, $end_datetime);

            return (float) $query->sum('quantity');
        } catch (\Throwable $e) {
            Log::warning('F20 CDS tank transfer-out qty skipped', [
                'tank_id' => $tank_id,
                'message' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    private function tankTestingQtyBetween($business_id, $tank_id, $start_datetime, $end_datetime)
    {
        $testing = 0;

        try {
            if (Schema::hasTable('meter_sales') && Schema::hasTable('pumps') && Schema::hasColumn('meter_sales', 'testing_qty')) {
                $query = DB::table('meter_sales')
                    ->join('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
                    ->where('pumps.fuel_tank_id', $tank_id);

                if (Schema::hasColumn('meter_sales', 'business_id')) {
                    $query->where('meter_sales.business_id', $business_id);
                }

                $this->applyGenericDateFilter($query, 'date_time', 'created_at', $start_datetime, $end_datetime, 'meter_sales');
                $testing += (float) $query->sum('meter_sales.testing_qty');
            }
        } catch (\Throwable $e) {
            Log::warning('F20 CDS meter_sales tank testing lookup skipped', [
                'tank_id' => $tank_id,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            if (Schema::hasTable('pump_operator_meter_sales') && Schema::hasTable('pump_operator_meter_sale_details') && Schema::hasTable('pumps')) {
                $query = DB::table('pump_operator_meter_sales as poms')
                    ->join('pump_operator_meter_sale_details as pomsd', 'poms.id', '=', 'pomsd.sale_id')
                    ->join('pumps', 'pomsd.pump_id', '=', 'pumps.id')
                    ->where('pumps.fuel_tank_id', $tank_id);

                if (Schema::hasColumn('poms', 'business_id')) {
                    $query->where('poms.business_id', $business_id);
                }

                $qtyColumn = Schema::hasColumn('poms', 'testing_qty') ? 'poms.testing_qty' : (Schema::hasColumn('pomsd', 'testing_qty') ? 'pomsd.testing_qty' : null);
                if (!empty($qtyColumn)) {
                    $this->applyGenericDateFilter($query, 'date_time', 'created_at', $start_datetime, $end_datetime, 'poms');
                    $testing += (float) $query->sum(DB::raw($qtyColumn));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('F20 CDS operator tank testing lookup skipped', [
                'tank_id' => $tank_id,
                'message' => $e->getMessage(),
            ]);
        }

        return $testing;
    }

    private function applyTransactionDateFilter($query, $start_datetime = null, $end_datetime = null)
    {
        $dateColumn = Schema::hasColumn('transactions', 'transaction_date') ? 'transactions.transaction_date' : 'transactions.created_at';

        if (!empty($start_datetime) && !empty($end_datetime)) {
            $query->where($dateColumn, '>=', $start_datetime)
                ->where($dateColumn, '<=', $end_datetime);
        } elseif (!empty($end_datetime)) {
            $query->where($dateColumn, '<', $end_datetime);
        }
    }

    private function applyGenericDateFilter($query, $preferredColumn, $fallbackColumn, $start_datetime = null, $end_datetime = null, $tableAlias = null)
    {
        $table = $tableAlias ?: null;
        $preferred = $table ? $table . '.' . $preferredColumn : $preferredColumn;
        $fallback = $table ? $table . '.' . $fallbackColumn : $fallbackColumn;

        $dateColumn = null;
        if ($tableAlias) {
            $baseTable = $tableAlias === 'poms' ? 'pump_operator_meter_sales' : ($tableAlias === 'meter_sales' ? 'meter_sales' : $tableAlias);
            if (Schema::hasColumn($baseTable, $preferredColumn)) {
                $dateColumn = $preferred;
            } elseif (Schema::hasColumn($baseTable, $fallbackColumn)) {
                $dateColumn = $fallback;
            }
        } else {
            // Used only for simple, unaliased tables where the preferred/fallback columns are known.
            $dateColumn = $preferred;
        }

        if (empty($dateColumn)) {
            return;
        }

        if (!empty($start_datetime) && !empty($end_datetime)) {
            $query->where($dateColumn, '>=', $start_datetime)
                ->where($dateColumn, '<=', $end_datetime);
        } elseif (!empty($end_datetime)) {
            $query->where($dateColumn, '<', $end_datetime);
        }
    }

    /**
     * F22 meter readings per pump, grouped by location.
     *
     * Solution 7995-17:
     * - Shows meter data for the ACTUAL selected date, not one fixed/default day.
     * - A F22 meter row is treated as selected-date data when either:
     *     1) form_f22_headers.form_date = selected F20 date, OR
     *     2) DATE(form_f22_pump_meters.created_at) = selected F20 date
     *   This is needed because some existing F22 pump meter rows were saved with the
     *   correct created_at date while the header date can differ in old data.
     * - Last Meter = selected date F22 meter reading.
     * - Starting Meter = latest previous meter reading before the selected date.
     * - If there is no selected-date F22 meter row for a pump, all meter values stay blank.
     */
    /**
     * Meter readings per pump, grouped by location.
     *
     * Solution 7995-18:
     * - Primary source: meter_sales table for the selected date.
     * - Date matching supports both meter_sales.created_at and settlements.transaction_date.
     * - Fallback source: F22 pump meter readings.
     * - Last Meter is shown only when a reading exists for the selected date.
     * - Starting Meter is the latest previous closing/last meter before the selected date.
     */
    private function meterDataByLocation($business_id, $form_date = null)
    {
        $pumps = $this->pumpList($business_id, null);
        $meter_data = [];
        $pump_locations = [];

        foreach ($pumps as $pump) {
            $location_key = !empty($pump->location_id) ? (string) $pump->location_id : 'all';
            $pump_locations[$pump->id] = $location_key;

            if (!isset($meter_data[$location_key])) {
                $meter_data[$location_key] = [];
            }

            $blank = [
                'last_meter' => '0.000',
                'starting_meter' => '0.000',
                'total_sale' => '0.000',
                'balance' => '0.000',
                'pumps_checked' => '0.000',
                'cash_sale' => $this->formatAmount(0, $business_id),
            ];

            $meter_data[$location_key][$pump->id] = $blank;
            $meter_data['all'][$pump->id] = $blank;
        }

        $selected_date = !empty($form_date) ? date('Y-m-d', strtotime($form_date)) : now()->format('Y-m-d');

        // 1) Preferred source: Petro meter_sales. This is the table used by the
        // daily meter flow and it contains starting_meter / closing_meter / qty / amount.
        if (Schema::hasTable('meter_sales')) {
            try {
                $meterSalesQuery = DB::table('meter_sales as ms')
                    ->leftJoin('pumps as p', 'p.id', '=', 'ms.pump_id')
                    ->where('ms.business_id', $business_id)
                    ->whereNotNull('ms.pump_id');

                if (Schema::hasTable('settlements') && Schema::hasColumn('meter_sales', 'settlement_no')) {
                    $meterSalesQuery->leftJoin('settlements as st', function ($join) {
                        $join->on('st.id', '=', 'ms.settlement_no')
                            ->orOn('st.settlement_no', '=', 'ms.settlement_no');
                    });
                }

                $dateWhere = function ($q) use ($selected_date) {
                    if (Schema::hasColumn('meter_sales', 'created_at')) {
                        $q->whereDate('ms.created_at', $selected_date);
                    }
                    if (Schema::hasTable('settlements') && Schema::hasColumn('settlements', 'transaction_date')) {
                        $q->orWhereDate('st.transaction_date', $selected_date);
                    }
                    if (Schema::hasTable('settlements') && Schema::hasColumn('settlements', 'created_at')) {
                        $q->orWhereDate('st.created_at', $selected_date);
                    }
                };

                $select = [
                    'ms.id',
                    'ms.pump_id',
                    DB::raw(Schema::hasColumn('meter_sales', 'settlement_no') ? 'ms.settlement_no as settlement_no' : 'NULL as settlement_no'),
                    DB::raw(Schema::hasColumn('meter_sales', 'shift_id') ? 'ms.shift_id as shift_id' : 'NULL as shift_id'),
                    DB::raw(Schema::hasColumn('meter_sales', 'product_id') ? 'ms.product_id as product_id' : 'NULL as product_id'),
                    DB::raw('COALESCE(ms.closing_meter, 0) as closing_meter'),
                    DB::raw('COALESCE(ms.starting_meter, 0) as starting_meter'),
                    DB::raw('COALESCE(ms.qty, 0) as qty'),
                    DB::raw('COALESCE(ms.testing_qty, 0) as testing_qty'),
                    DB::raw('COALESCE(ms.sub_total, ms.discount_amount, 0) as cash_sale'),
                    DB::raw(Schema::hasColumn('pumps', 'location_id') ? 'p.location_id as location_id' : 'NULL as location_id'),
                    DB::raw(Schema::hasColumn('meter_sales', 'created_at') ? 'DATE(ms.created_at) as meter_sale_date' : 'NULL as meter_sale_date'),
                ];

                if (Schema::hasTable('settlements') && Schema::hasColumn('settlements', 'transaction_date')) {
                    $select[] = DB::raw('DATE(st.transaction_date) as settlement_transaction_date');
                } else {
                    $select[] = DB::raw('NULL as settlement_transaction_date');
                }

                $selectedRows = (clone $meterSalesQuery)
                    ->where($dateWhere)
                    ->select($select)
                    ->orderBy('ms.pump_id')
                    ->orderBy('ms.id', 'desc')
                    ->get()
                    ->groupBy('pump_id')
                    ->map(function ($rows) {
                        return $rows->first();
                    });

                if ($selectedRows->isNotEmpty()) {
                    foreach ($selectedRows as $pump_id => $row) {
                        $previousRow = (clone $meterSalesQuery)
                            ->where('ms.pump_id', $pump_id)
                            ->where(function ($q) use ($selected_date) {
                                if (Schema::hasColumn('meter_sales', 'created_at')) {
                                    $q->whereDate('ms.created_at', '<', $selected_date);
                                }
                                if (Schema::hasTable('settlements') && Schema::hasColumn('settlements', 'transaction_date')) {
                                    $q->orWhereDate('st.transaction_date', '<', $selected_date);
                                }
                                if (Schema::hasTable('settlements') && Schema::hasColumn('settlements', 'created_at')) {
                                    $q->orWhereDate('st.created_at', '<', $selected_date);
                                }
                            })
                            ->select($select)
                            ->orderBy('ms.id', 'desc')
                            ->first();

                        $last_meter = $this->num($row->closing_meter);
                        $starting_meter = $previousRow ? $this->num($previousRow->closing_meter) : $this->num($row->starting_meter);
                        $testing_qty = $this->num($row->testing_qty);
                        $total_sale = max(0, $last_meter - $starting_meter - $testing_qty);
                        if ($total_sale == 0 && $this->num($row->qty) > 0) {
                            $total_sale = $this->num($row->qty);
                        }

                        // Solution 7995-20:
                        // Row renamed to Testing and it must show the pump testing quantity.
                        // Cash Sale amount must be Total Sale Amount - Credit Sale Amount.
                        $total_sale_amount = $this->num($row->cash_sale);
                        $credit_sale_amount = $this->creditSaleAmountForMeterRow($business_id, $selected_date, $row);
                        $cash_sale_amount = max(0, $total_sale_amount - $credit_sale_amount);

                        $values = [
                            'last_meter' => $last_meter > 0 ? $this->formatNumber($last_meter, 3) : '0.000',
                            'starting_meter' => $starting_meter > 0 ? $this->formatNumber($starting_meter, 3) : '0.000',
                            'total_sale' => $total_sale > 0 ? $this->formatNumber($total_sale, 3) : '0.000',
                            'balance' => $total_sale > 0 ? $this->formatNumber($total_sale, 3) : '0.000',
                            'pumps_checked' => $testing_qty > 0 ? $this->formatNumber($testing_qty, 3) : '0.000',
                            'cash_sale' => $total_sale_amount > 0 ? $this->formatAmount($cash_sale_amount, $business_id) : $this->formatAmount(0, $business_id),
                        ];

                        if ($values['last_meter'] === '') {
                            continue;
                        }

                        $location_key = !empty($row->location_id) ? (string) $row->location_id : ($pump_locations[$pump_id] ?? 'all');
                        if (!isset($meter_data[$location_key])) {
                            $meter_data[$location_key] = [];
                        }

                        $meter_data[$location_key][$pump_id] = $values;
                        $meter_data['all'][$pump_id] = $values;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('F20 CDS meter_sales autoload skipped', [
                    'selected_date' => $selected_date,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // 2) Fallback source: F22 pump meters. Use this only for pumps still blank.
        if (Schema::hasTable('form_f22_pump_meters') && Schema::hasTable('form_f22_headers')) {
            try {
                $locationRaw = Schema::hasColumn('form_f22_headers', 'location_id')
                    ? 'f22h.location_id as location_id'
                    : (Schema::hasColumn('pumps', 'location_id') ? 'p.location_id as location_id' : 'NULL as location_id');

                $rows = DB::table('form_f22_pump_meters as f22m')
                    ->join('form_f22_headers as f22h', 'f22h.id', '=', 'f22m.header_id')
                    ->leftJoin('pumps as p', 'p.id', '=', 'f22m.pump_id')
                    ->where('f22h.business_id', $business_id)
                    ->whereNotNull('f22m.pump_id')
                    ->whereNotNull('f22m.meter_reading')
                    ->where('f22m.meter_reading', '>', 0)
                    ->select([
                        'f22m.id',
                        'f22m.header_id',
                        'f22m.pump_id',
                        'f22m.pump_name',
                        'f22m.meter_reading',
                        DB::raw('DATE(f22h.form_date) as header_form_date'),
                        DB::raw('DATE(f22m.created_at) as meter_created_date'),
                        DB::raw($locationRaw),
                    ])
                    ->get()
                    ->map(function ($row) {
                        $row->header_date_clean = (!empty($row->header_form_date) && $row->header_form_date !== '0000-00-00') ? $row->header_form_date : null;
                        $row->created_date_clean = (!empty($row->meter_created_date) && $row->meter_created_date !== '0000-00-00') ? $row->meter_created_date : null;
                        $row->sort_date = $row->header_date_clean ?: $row->created_date_clean;
                        return $row;
                    })
                    ->filter(function ($row) use ($selected_date) {
                        return (!empty($row->header_date_clean) && $row->header_date_clean <= $selected_date)
                            || (!empty($row->created_date_clean) && $row->created_date_clean <= $selected_date);
                    })
                    ->groupBy('pump_id');

                foreach ($rows as $pump_id => $pump_rows) {
                    $location_key_check = $pump_locations[$pump_id] ?? 'all';
                    $alreadyHasMeterSales = !empty($meter_data['all'][$pump_id]['last_meter'] ?? '');
                    if ($alreadyHasMeterSales) {
                        continue;
                    }

                    $selected_rows = $pump_rows->filter(function ($r) use ($selected_date) {
                        return $r->header_date_clean === $selected_date || $r->created_date_clean === $selected_date;
                    });

                    if ($selected_rows->isEmpty()) {
                        continue;
                    }

                    $last_row = $selected_rows->sortByDesc(function ($row) {
                        return ($row->sort_date ?: '') . '-' . str_pad((string)$row->header_id, 10, '0', STR_PAD_LEFT) . '-' . str_pad((string)$row->id, 10, '0', STR_PAD_LEFT);
                    })->first();

                    $previous_row = $pump_rows->filter(function ($r) use ($selected_date) {
                        return (!empty($r->header_date_clean) && $r->header_date_clean < $selected_date)
                            || (!empty($r->created_date_clean) && $r->created_date_clean < $selected_date);
                    })->sortByDesc(function ($row) {
                        $date = $row->sort_date ?: $row->created_date_clean ?: $row->header_date_clean ?: '';
                        return $date . '-' . str_pad((string)$row->header_id, 10, '0', STR_PAD_LEFT) . '-' . str_pad((string)$row->id, 10, '0', STR_PAD_LEFT);
                    })->first();

                    $last_meter = $this->num($last_row->meter_reading);
                    $starting_meter = $previous_row ? $this->num($previous_row->meter_reading) : null;
                    $total_sale = $starting_meter !== null ? max(0, $last_meter - $starting_meter) : null;

                    $values = [
                        'last_meter' => $this->formatNumber($last_meter, 3),
                        'starting_meter' => $starting_meter !== null ? $this->formatNumber($starting_meter, 3) : '0.000',
                        'total_sale' => $total_sale !== null ? $this->formatNumber($total_sale, 3) : '0.000',
                        'balance' => $total_sale !== null ? $this->formatNumber($total_sale, 3) : '0.000',
                        'pumps_checked' => '0.000',
                        'cash_sale' => $this->formatAmount(0, $business_id),
                    ];

                    $location_key = !empty($last_row->location_id) ? (string) $last_row->location_id : $location_key_check;
                    if (!isset($meter_data[$location_key])) {
                        $meter_data[$location_key] = [];
                    }

                    $meter_data[$location_key][$pump_id] = $values;
                    $meter_data['all'][$pump_id] = $values;
                }
            } catch (\Exception $e) {
                Log::warning('F20 CDS selected-date F22 meter autoload skipped', [
                    'selected_date' => $selected_date,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $meter_data;
    }

    private function applyMeterRow(&$meter_data, $row, $only_if_blank = false)
    {
        $pump_id = $row->pump_id ?? null;
        if (!$pump_id) {
            return;
        }

        $location_key = !empty($row->location_id) ? (string) $row->location_id : 'all';
        if (!isset($meter_data[$location_key])) {
            $meter_data[$location_key] = [];
        }

        $starting_meter = $this->num($row->starting_meter ?? 0);
        $last_meter = $this->num($row->last_meter ?? 0);
        $total_sale = $this->num($row->total_sale ?? 0);

        if ($total_sale == 0 && ($last_meter > 0 || $starting_meter > 0)) {
            $total_sale = max(0, $last_meter - $starting_meter);
        }

        $new_values = [
            'last_meter' => $last_meter || $starting_meter ? $this->formatNumber($last_meter, 3) : '0.000',
            'starting_meter' => $last_meter || $starting_meter ? $this->formatNumber($starting_meter, 3) : '0.000',
            'total_sale' => $total_sale ? $this->formatNumber($total_sale, 3) : '0.000',
            'balance' => ($last_meter || $starting_meter) ? $this->formatNumber(max(0, $last_meter - $starting_meter), 3) : '0.000',
            'pumps_checked' => $this->num($row->testing_qty ?? 0) ? $this->formatNumber($this->num($row->testing_qty ?? 0), 3) : '0.000',
            'cash_sale' => $this->num($row->cash_sale ?? 0) ? $this->formatAmount($this->num($row->cash_sale ?? 0), $this->businessId()) : $this->formatAmount(0, $this->businessId()),
        ];

        if ($only_if_blank && isset($meter_data[$location_key][$pump_id])) {
            foreach ($new_values as $key => $value) {
                if (($meter_data[$location_key][$pump_id][$key] ?? '') === '' && $value !== '') {
                    $meter_data[$location_key][$pump_id][$key] = $value;
                }
            }
            return;
        }

        $meter_data[$location_key][$pump_id] = $new_values;
    }


    /**
     * Solution 7995-20: Calculate Credit Sale amount for one meter row when possible.
     * The live systems differ by version, so this method only subtracts credit sales
     * when safe matching columns are available. Otherwise it returns 0 and keeps
     * the meter sales total as cash sale.
     */
    private function creditSaleAmountForMeterRow($business_id, $selected_date, $row)
    {
        if (!Schema::hasTable('settlement_credit_sale_payments')) {
            return 0;
        }

        try {
            $query = DB::table('settlement_credit_sale_payments')
                ->where('business_id', $business_id);

            if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_id') && !empty($row->pump_id)) {
                $query->where('pump_id', $row->pump_id);
            } elseif (Schema::hasColumn('settlement_credit_sale_payments', 'product_id') && !empty($row->product_id)) {
                $query->where('product_id', $row->product_id);
            } else {
                return 0;
            }

            if (Schema::hasColumn('settlement_credit_sale_payments', 'settlement_no') && !empty($row->settlement_no)) {
                $query->where(function ($q) use ($row) {
                    $q->where('settlement_no', $row->settlement_no);
                    if (is_numeric($row->settlement_no)) {
                        $q->orWhere('settlement_no', (int)$row->settlement_no);
                    }
                });
            } elseif (Schema::hasColumn('settlement_credit_sale_payments', 'order_date')) {
                $query->whereDate('order_date', $selected_date);
            } elseif (Schema::hasColumn('settlement_credit_sale_payments', 'created_at')) {
                $query->whereDate('created_at', $selected_date);
            } else {
                return 0;
            }

            $amountColumn = null;
            foreach (['amount', 'sub_total', 'final_total'] as $column) {
                if (Schema::hasColumn('settlement_credit_sale_payments', $column)) {
                    $amountColumn = $column;
                    break;
                }
            }

            return $amountColumn ? (float) $query->sum($amountColumn) : 0;
        } catch (\Exception $e) {
            Log::warning('F20 CDS credit sale amount skipped', [
                'selected_date' => $selected_date,
                'message' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    private function formatNumber($value, $decimals = 2)
    {
        if ($value === '' || $value === null) {
            $value = 0;
        }

        return number_format((float) $value, (int) $decimals, '.', ',');
    }

    private function formatAmount($value, $business_id = null)
    {
        return $this->formatNumber($value, $this->currencyPrecision($business_id));
    }

    private function formatFuelQty($value)
    {
        return $this->formatNumber($value, 3);
    }

    private function currencyPrecision($business_id = null)
    {
        $business_id = $business_id ?: $this->businessId();
        $default = 2;

        try {
            if (!$business_id || !Schema::hasTable('business')) {
                return $default;
            }

            foreach (['currency_precision', 'amount_precision', 'decimal_precision'] as $column) {
                if (Schema::hasColumn('business', $column)) {
                    $value = DB::table('business')->where('id', $business_id)->value($column);
                    if ($value !== null && $value !== '') {
                        return max(0, (int) $value);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('F20 CDS currency precision fallback used', ['message' => $e->getMessage()]);
        }

        return $default;
    }

    private function quantityPrecision($business_id = null)
    {
        $business_id = $business_id ?: $this->businessId();
        $default = 3;

        try {
            if (!$business_id || !Schema::hasTable('business')) {
                return $default;
            }

            foreach (['quantity_precision', 'qty_precision'] as $column) {
                if (Schema::hasColumn('business', $column)) {
                    $value = DB::table('business')->where('id', $business_id)->value($column);
                    if ($value !== null && $value !== '') {
                        return max(0, (int) $value);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('F20 CDS quantity precision fallback used', ['message' => $e->getMessage()]);
        }

        return $default;
    }


    private function uniqueBusinessLocations($locations)
    {
        if (empty($locations) || !is_array($locations)) {
            return $locations;
        }

        $clean = [];
        $seen = [];

        foreach ($locations as $id => $name) {
            $label = trim((string) $name);
            $key = mb_strtolower($label);

            if ($label === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $clean[$id] = $name;
        }

        return $clean;
    }

    private function businessId()
    {
        return request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: optional(Auth::user())->business_id;
    }

    private function latestSettings($business_id)
    {
        if (!Schema::hasTable('mpcs_f20_cds_settings')) {
            return null;
        }

        return DB::table('mpcs_f20_cds_settings')
            ->where('business_id', $business_id)
            ->orderBy('is_active', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    private function settingsList($business_id)
    {
        if (!Schema::hasTable('mpcs_f20_cds_settings')) {
            return collect();
        }

        return DB::table('mpcs_f20_cds_settings')
            ->where('business_id', $business_id)
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();
    }

    private function nextFormNumber($business_id)
    {
        $settings = $this->latestSettings($business_id);
        $starting_no = !empty($settings->form_starting_no) ? (int) $settings->form_starting_no : 1;

        if (Schema::hasTable('mpcs_f20_cds_headers')) {
            $last = DB::table('mpcs_f20_cds_headers')
                ->where('business_id', $business_id)
                ->orderBy('id', 'desc')
                ->value('form_no');

            if (!empty($last) && is_numeric($last)) {
                return max(((int)$last) + 1, $starting_no);
            }
        }

        return $starting_no;
    }

    private function ensureSettingsTable()
    {
        if (Schema::hasTable('mpcs_f20_cds_settings')) {
            return;
        }

        Schema::create('mpcs_f20_cds_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->date('opening_date')->nullable();
            $table->unsignedInteger('form_starting_no')->default(1);
            $table->tinyInteger('is_active')->default(1)->index();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    private function num($value)
    {
        return (float) str_replace(',', '', $value ?: 0);
    }
}
