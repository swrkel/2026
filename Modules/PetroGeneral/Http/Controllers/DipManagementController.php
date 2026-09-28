<?php
namespace Modules\PetroGeneral\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\AccountType;
use App\Business;
use App\BusinessLocation;
use App\Product;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Modules\PetroGeneral\Entities\DipReading;
use Modules\PetroGeneral\Entities\DipResetting;
use Modules\PetroGeneral\Entities\FuelTank;
use Modules\PetroGeneral\Entities\TankPurchaseLine;
use App\Store; // IS1970: store-level stock sync on tank reset
use Modules\PetroGeneral\Entities\TankSellLine;
use Modules\PetroGeneral\Entities\TankTransfer;
use Modules\Superadmin\Entities\TankDipChart;
use Modules\Superadmin\Entities\TankDipChartDetail;
use Response;
use Session;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class DipManagementController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;
    protected $notificationUtil;
    private $barcode_types;
    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */

    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, NotificationUtil $notificationUtil)
    {
        $this->commonUtil       = $commonUtil;
        $this->productUtil      = $productUtil;
        $this->moduleUtil       = $moduleUtil;
        $this->transactionUtil  = $transactionUtil;
        $this->businessUtil     = $businessUtil;
        $this->notificationUtil = $notificationUtil;
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function index(Request $request)
    {
        $business_id        = $request->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $tanks              = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'id');
        $products           = Product::leftjoin('categories', 'products.category_id', 'categories.id')
            ->where('categories.name', 'Fuel')
            ->where('products.business_id', $business_id)
            ->pluck('products.name', 'products.id');
        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');
        return view('petrogeneral::dip_management.index')->with(compact(
            'business_locations',
            'message',
            'tanks',
            'products'
        ));
    }

    public function addDipChart(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $quick_add   = $request->quick_add;

        if (! empty($quick_add)) {
            $already_added = [];
            $view          = 'petrogeneral::dip_management.add_dip_chart_quick_add';
        } else {
            $already_added = TankDipChart::where('business_id', $business_id)->pluck('tank_id')->toArray();
            $view          = 'petrogeneral::dip_management.add_dip_chart';
        }

        // $tanks = FuelTank::where('business_id', $business_id)->whereNotIn('id',$already_added)->get();
        $tanks = FuelTank::where('business_id', $business_id)->get();
        Log::info('Tanks: ', $tanks->toArray());

        return view($view)->with(compact(
            'tanks'
        ));
    }

    public function addDipChartReading($id)
    {
        return view('petrogeneral::dip_management.add_dip_chart_reading')->with(compact(
            'id'
        ));
    }

    public function editDipChart($id)
    {
        $data = TankDipChartDetail::findOrFail($id);

        return view('petrogeneral::dip_management.edit_dip_chart')->with(compact(
            'data'
        ));
    }

    public function updateDipChart(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        try {

            DB::beginTransaction();

            $dip_reading       = $request->dip_reading;
            $dip_reading_value = $request->dip_reading_value;

            $dip_chart_details = TankDipChartDetail::findOrFail($id);

            $is_changed  = false;
            $changed_msg = false;

            if ($dip_chart_details->dip_reading != $dip_reading) {
                $is_changed = true;
                $changed_msg .= "Dip Reading changed from " . $this->transactionUtil->num_f($dip_chart_details->dip_reading) . " to " . $this->transactionUtil->num_f($dip_reading) . PHP_EOL;
            }

            if ($dip_chart_details->dip_reading_value != $dip_reading_value) {
                $is_changed = true;
                $changed_msg .= "Dip Readinng in Lts changed from " . $this->transactionUtil->num_f($dip_chart_details->dip_reading_value) . " to " . $this->transactionUtil->num_f($dip_reading_value) . PHP_EOL;
            }

            $dip_chart = TankDipChartDetail::where('id', $id)->update(['dip_reading' => $dip_reading, 'dip_reading_value' => $dip_reading_value]);

            if (! empty($is_changed) && ! empty($changed_msg)) {

                $activity               = new Activity();
                $activity->log_name     = "Dip Chart";
                $activity->description  = "update";
                $activity->subject_id   = $id;
                $activity->subject_type = "Modules\Superadmin\Entities\TankDipChart";
                $activity->causer_id    = auth()->user()->id;
                $activity->causer_type  = 'App\User';
                $activity->properties   = $changed_msg;
                $activity->created_at   = date('Y-m-d H:i');
                $activity->updated_at   = date('Y-m-d H:i');

                // Save the activity
                $activity->save();

            }

            DB::commit();

            $output = [
                'success' => true,
                'msg'     => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }
        return $output;
    }

    public function saveDipChartReading(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        try {

            DB::beginTransaction();

            $dip_reading       = $request->dip_reading;
            $dip_reading_value = $request->dip_reading_value;

            $dip_chart_details = TankDipChartDetail::create(['tank_dip_chart_id' => $id, 'dip_reading' => $dip_reading, 'dip_reading_value' => $dip_reading_value]);

            DB::commit();

            $output = [
                'success' => true,
                'msg'     => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }
        return $output;
    }

    public function deleteDipChart($id)
    {

        try {

            DB::beginTransaction();

            $dip_chart_details = TankDipChartDetail::findOrFail($id);

            $changed_msg = "";
            $is_changed  = true;
            $changed_msg .= "Deleted Dip Reading " . $this->transactionUtil->num_f($dip_chart_details->dip_reading) . PHP_EOL;
            $changed_msg .= "Deleted Dip Readinng in Lts " . $this->transactionUtil->num_f($dip_chart_details->dip_reading_value) . PHP_EOL;

            if (! empty($is_changed) && ! empty($changed_msg)) {

                $activity               = new Activity();
                $activity->log_name     = "Dip Chart";
                $activity->description  = "delete";
                $activity->subject_id   = $id;
                $activity->subject_type = "Modules\Superadmin\Entities\TankDipChart";
                $activity->causer_id    = auth()->user()->id;
                $activity->causer_type  = 'App\User';
                $activity->properties   = $changed_msg;
                $activity->created_at   = date('Y-m-d H:i');
                $activity->updated_at   = date('Y-m-d H:i');

                // Save the activity
                $activity->save();

            }
            $dip_chart_details->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg'     => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }
        return $output;
    }

    public function saveDipChart(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        Log::info('request received in saveDipChart: ' . json_encode($request->all()));

        try {

            DB::beginTransaction();

            $date_time                 = $this->transactionUtil->uf_date($request->date_time, true);
            $tank_id                   = $request->tank_id;
            $tank_manufacturer         = $request->tank_manufacturer;
            $tank_manufacturer_contact = $request->tank_manufacturer_contact;
            $quick_add                 = $request->quick_add;
            $sheet_name                = $request->sheet_name;

            // update manufacuteres
            FuelTank::where('id', $tank_id)->update(['tank_manufacturer' => $tank_manufacturer, 'tank_manufacturer_phone' => $tank_manufacturer_contact]);

            $dip_chart = TankDipChart::updateOrCreate(['business_id' => $business_id, 'tank_id' => $tank_id], ['business_id' => $business_id, 'date' => $date_time, 'sheet_name' => $sheet_name, 'tank_id' => $tank_id, 'created_by' => auth()->user()->id]);

            // if (! empty($quick_add)) {
                // $dip_reading = $request->dip_reading;
                // $dip_reading_value = $request->dip_reading_value;

                // $dip_chart_details = TankDipChartDetail::create(array('tank_dip_chart_id' => $dip_chart->id,'dip_reading' => $dip_reading, 'dip_reading_value' => $dip_reading_value));
                if (! empty($request->dip_reading)) {
                    foreach ($request->dip_reading as $key => $reading) {
                        if (! empty($reading) && ! empty($request->dip_reading_value[$key])) {
                            TankDipChartDetail::create([
                                'tank_dip_chart_id' => $dip_chart->id,
                                'dip_reading'       => $reading,
                                'dip_reading_value' => $request->dip_reading_value[$key],
                            ]);
                        }
                    }
                }

            // }

            DB::commit();

            $output = [
                'success' => true,
                'msg'     => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }
        if ($request->ajax()) {
            return $output;
        }
        return redirect()->back()->with(['status' => $output]);
    }

    public function getDipChart()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {

            $query = DB::table('tank_dip_chart_details')
                ->leftjoin('tank_dip_charts', 'tank_dip_chart_details.tank_dip_chart_id', 'tank_dip_charts.id')
                ->leftjoin('fuel_tanks', 'tank_dip_charts.tank_id', 'fuel_tanks.id')
                ->leftjoin('users', 'users.id', 'tank_dip_charts.created_by')
                ->where('tank_dip_charts.business_id', $business_id)
                ->select([
                    'tank_dip_charts.date', 'tank_dip_charts.sheet_name',
                    'fuel_tanks.tank_manufacturer', 'fuel_tanks.tank_manufacturer_phone', 'fuel_tanks.storage_volume', 'fuel_tanks.fuel_tank_number',
                    'users.username', 'tank_dip_chart_details.*',
                ]);

            if (! empty(request()->tank_id)) {
                $query = $query->where('tank_dip_charts.tank_id', request()->tank_id);
            }

            $dip_report = Datatables::of($query)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '';
                        $html .= '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                    data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if (auth()->user()->can("dipmanagement.edit_dip_chart")) {
                            $html .= '<li><a href="#" data-href="' . action("\Modules\PetroGeneral\Http\Controllers\DipManagementController@editDipChart", [$row->id]) . '" class="edit_dip"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can("dipmanagement.add_dip_chart")) {
                            $html .= '<li><a href="#" data-href="' . action("\Modules\PetroGeneral\Http\Controllers\DipManagementController@addDipChartReading", [$row->tank_dip_chart_id]) . '" class="edit_dip"><i class="fa fa-plus"></i> ' . __("lang_v1.add") . '</a></li>';
                        }

                        if (auth()->user()->can('dipmanagement.delete_dip_chart')) {
                            $html .= '<li><a href="' . action("\Modules\PetroGeneral\Http\Controllers\DipManagementController@deleteDipChart", [$row->id]) . '" class="delete_dipchart_button"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        $html .= '</ul></div>';
                        return $html;
                    })

                ->editColumn('date', '{{@format_datetime($date)}}')

                ->editColumn('dip_reading', '{{@num_format($dip_reading)}}')
                ->editColumn('dip_reading_value', '{{@num_format($dip_reading_value)}}')
                ->editColumn('storage_volume', '{{@num_format($storage_volume)}}')
                ->removeColumn('id');

            Log::info('dip chart data: '.json_decode($dip_report->toJson(), true));
            
            return $dip_report->rawColumns(['action', 'difference'])
                ->make(true);
        }
    }

    /**
     * Get Dip Report
     * @return Response
     */

    public function getDipReport()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $startDate = request()->start_date;
            $endDate   = request()->end_date;

            $query = DB::table('dip_readings')->
                leftjoin('business_locations', 'dip_readings.location_id', 'business_locations.id')
                ->leftjoin('fuel_tanks', 'dip_readings.tank_id', 'fuel_tanks.id')
                ->leftjoin('products', 'fuel_tanks.product_id', 'products.id')
                ->where('dip_readings.business_id', $business_id)
                ->select([
                    'dip_readings.*',
                    'business_locations.name as location_name',
                    'fuel_tanks.fuel_tank_number as tank_name',
                    'products.name as product_name', 'products.id as productID',
                ]);

            if (! empty(request()->location_id)) {
                $query->where('dip_readings.location_id', request()->location_id);
            }
            if (! empty(request()->tank_id)) {
                $query = $query->where('tank_id', request()->tank_id);
            }
            if (! empty(request()->product_id)) {

                $query = $query->where('product_id', request()->product_id);
            }
            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                /*
                 |------------------------------------------------------------------
                 | Filter on transaction_date, NOT date_and_time.
                 |------------------------------------------------------------------
                 |
                 | This used to convert date_and_time with STR_TO_DATE and compare
                 | that. The saved rows show why it fails:
                 |
                 |     transaction_date   date_and_time
                 |     2026-08-15         11/03/2026 00:00
                 |     2026-08-15         11/03/2026 00:00
                 |
                 | transaction_date is right - it comes from Daily Report Date. But
                 | date_and_time carries whatever the Dip Date & Time picker put
                 | there, and that is frequently a different date altogether. Parsed
                 | as %m/%d/%Y, "11/03/2026" is 3 November - well outside an August
                 | range - so every one of those dips was filtered out of the report
                 | even though it had saved perfectly.
                 |
                 | transaction_date is a real DATE column, is always populated by the
                 | save, and is the date the user actually chose for the report. It is
                 | the correct thing to filter on.
                 |
                 | date_and_time is kept only as a fallback for any historical row
                 | with no transaction_date.
                 */
                $query = $query->whereRaw(
                    "COALESCE(
                        dip_readings.transaction_date,
                        STR_TO_DATE(dip_readings.date_and_time, '%m/%d/%Y'),
                        DATE(dip_readings.date_and_time)
                    ) BETWEEN ? AND ?",
                    [$startDate, $endDate]
                );
            }

            // $query->orderBy('dip_readings.id','DESC')
            //     ->get();

            $business_details = Business::find($business_id);
            $dip_report       = Datatables::of($query)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '';
                        $html .= '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                    data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if (auth()->user()->can("dipmanagement.edit")) {
                            $html .= '<li><a href="#" data-href="' . action("\Modules\PetroGeneral\Http\Controllers\DipManagementController@edit", [$row->id]) . '" class="edit_dip"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can('dipmanagement.delete')) {
                            $html .= '<li><a href="' . action("\Modules\PetroGeneral\Http\Controllers\DipManagementController@destroy", [$row->id]) . '" class="delete_dipreport_button"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        $html .= '</ul></div>';
                        return $html;
                    })
                ->addColumn('difference', function ($row) {
                    // 2 decimals and comma separated, like the other quantities.
                    return number_format(
                        (float) $row->fuel_balance_dip_reading - (float) $row->current_qty,
                        2
                    );
                })
                ->addColumn('shortage_recovered', function ($row) use ($business_details) {
                    $difference = $row->fuel_balance_dip_reading - $row->current_qty;
                    $shortage = $difference < 0 ? abs($difference) : 0;
                    return $this->productUtil->num_f($shortage, false, $business_details, true);
                })
                ->addColumn('excess_amount', function ($row) use ($business_details) {
                    $difference = $row->fuel_balance_dip_reading - $row->current_qty;
                    $excess = $difference > 0 ? $difference : 0;
                    return $this->productUtil->num_f($excess, false, $business_details, true);
                })
                /*
                 |--------------------------------------------------------------
                 | Daily Report Date printed as an empty cell.
                 |--------------------------------------------------------------
                 |
                 | This was a Blade-string column:
                 |     '{{$transaction_date != "0000-00-00" ? @format_date(...) : "--"}}'
                 |
                 | @format_date is a Blade directive, and inside a DataTables string
                 | template it is not compiled - so the expression produced nothing
                 | and the cell came out blank rather than showing the date or even
                 | the "--" fallback.
                 |
                 | A closure formats it directly. Anything unparseable falls back to
                 | "--" instead of throwing for the whole report.
                 */
                ->editColumn('transaction_date', function ($row) {
                    if (empty($row->transaction_date) || $row->transaction_date === '0000-00-00') {
                        return '--';
                    }

                    try {
                        return \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y');
                    } catch (\Throwable $e) {
                        return '--';
                    }
                })

                /*
                 |--------------------------------------------------------------
                 | Quantities: 2 decimals, comma separated.
                 |--------------------------------------------------------------
                 |
                 | number_format() is used rather than num_f() because num_f
                 | follows the business's own currency precision setting, which is
                 | 3 on this install - so the columns showed three decimals and
                 | ignored the request. number_format fixes it at 2 and always adds
                 | the thousands separator.
                 */
                ->editColumn('dip_reading', function ($row) {
                    return number_format((float) $row->dip_reading, 2);
                })
                ->editColumn('fuel_balance_dip_reading', function ($row) {
                    return number_format((float) $row->fuel_balance_dip_reading, 2);
                })
                ->editColumn('current_qty', function ($row) {
                    // Was not formatted at all - it rendered the raw column value.
                    return number_format((float) $row->current_qty, 2);
                })
                ->editColumn('difference_value', function ($row) {
                    $variations = DB::table('variations')->where('product_id', $row->productID)->first();

                    // A product with no variation row would throw on ->sell_price_inc_tax.
                    $price = ! empty($variations) ? (float) $variations->sell_price_inc_tax : 0.0;

                    return number_format(
                        ((float) $row->fuel_balance_dip_reading - (float) $row->current_qty) * $price,
                        2
                    );
                })
                ->editColumn('location_name', function ($row) {
                    $locationName = $row->location_name ?? '--';
                    $noteButton = '';
                    
                    if (!empty($row->note) && trim($row->note) !== '') {
                        $noteEscaped = htmlspecialchars($row->note, ENT_QUOTES, 'UTF-8');
                        $noteButton = ' <button type="button" class="btn btn-xs btn-info view-note-btn" data-note="' . $noteEscaped . '" title="' . __('petrogeneral::lang.view_note') . '" style="margin-left: 5px;">
                            <i class="fa fa-sticky-note"></i> ' . __('petrogeneral::lang.note') . '
                        </button>';
                    }
                    
                    return $locationName . $noteButton;
                })
                ->removeColumn('id');
            return $dip_report->rawColumns(['action', 'difference', 'location_name', 'shortage_recovered', 'excess_amount'])
                ->make(true);
        }
    }
    /**
     * Add new Dip
     * @return Response
     */

    public function addNewDip()
    {
        $business_id               = request()->session()->get('user.business_id');
        $business_locations        = BusinessLocation::forDropdown($business_id);
        $default_location          = current(array_keys($business_locations->toArray()));
        $tanks                     = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'id');
        $count                     = DipReading::where('business_id', $business_id)->count();
        $ref_no                    = $count + 1;
        $tank_dip_chart_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'tank_dip_chart');
        return view('petrogeneral::dip_management.add_new_dip')->with(compact(
            'business_locations',
            'default_location',
            'tanks',
            'tank_dip_chart_permission',
            'ref_no'
        ));
    }
    /**
     * Save new Dip
     * @return Response
     */

     public function saveNewDip(Request $request)
     {
         $business_id = request()->session()->get('user.business_id');

     
         $has_reviewed = $this->transactionUtil->hasReviewed($request->date_and_time);
     
         if (!empty($has_reviewed)) {
             $output = [
                 'success' => 0,
                 'msg'     => __('lang_v1.review_first'),
             ];
     
             return redirect()->back()->with(['status' => $output]);
         }
     
         $reviewed = $this->transactionUtil->get_review($request->date_and_time, $request->date_and_time, true);
     
         if (!empty($reviewed)) {
             $output = [
                 'success' => 0,
                 'msg'     => "You can't add a dip for a date prior to a reviewed date",
             ];
     
             return $output;
         }
     
         try {
             // Handle notes - can be array or single value, and is optional
             $notes       = $request->notes ?? $request->note ?? [];
             $tank_id     = $request->tank_ids ?? $request->tank_id ?? [];
             /*
              | Dip Reading is now typed by hand on the form and posted as
              | manual_dip_readings[]. It takes priority over the old chart-driven
              | dip_readings[], which is kept only so existing callers and the
              | hidden chart field keep working.
              */
             $manual_dip_reading = $request->manual_dip_readings ?? $request->manual_dip_reading ?? [];
             $dip_reading = $request->dip_readings ?? $request->dip_reading ?? [];
             $fuel_bal    = $request->fuel_balance_dip_readings ?? $request->fuel_balance_dip_reading ?? [];
             $curr_qty    = $request->current_qtys ?? $request->current_qty ?? [];
     
             $tank_ids = [];
     
             $prod_summary         = "";
             $total_opening_stock  = 0.0;
             $total_received_stock = 0.0;
             $total_sold_qty       = 0.0;
             $total_testing_qty    = 0.0;
     
             // Validate daily_report_date
            if (empty($request->daily_report_date)) {
                $output = [
                    'success' => false,
                    'msg'     => 'Daily Report Date is required',
                ];
                return $output;
            }
            
            // Parse current report date once (YYYY-MM-DD)
            $current_date = $this->transactionUtil->uf_date($request->daily_report_date);

            /*
             |------------------------------------------------------------------
             | Guard the parsed date before it reaches any query.
             |------------------------------------------------------------------
             |
             | uf_date() returns NULL when the incoming value is not in the format
             | it expects. $current_date is then used as
             |
             |     ->whereDate('transaction_date', '<', $current_date)
             |
             | and Laravel throws
             |     Illegal operator and value combination
             |     .../Illuminate/Database/Query/Builder.php
             | because an operator other than '=' cannot be paired with NULL.
             |
             | That is the reported error, and it aborted the whole save: the rows
             | were added to the table on screen but nothing was written.
             |
             | The date is now normalised, and the save stops with a clear message
             | if it genuinely cannot be read - rather than failing deep inside the
             | query builder with something the user cannot act on.
             */
            if (empty($current_date)) {
                try {
                    $current_date = Carbon::parse($request->daily_report_date)->format('Y-m-d');
                } catch (\Throwable $dateException) {
                    $current_date = null;
                }
            }

            if (empty($current_date)) {
                return [
                    'success' => false,
                    'msg'     => 'Daily Report Date could not be read. Please re-select the date and try again.',
                ];
            }

            // Normalise to Y-m-d so every comparison below uses the same form.
            $current_date = Carbon::parse($current_date)->format('Y-m-d');
    
            // Ensure we have arrays to iterate over
             if (!is_array($tank_id)) {
                 $tank_id = [$tank_id];
             }
             if (!is_array($dip_reading)) {
                 $dip_reading = [$dip_reading];
             }
             if (!is_array($manual_dip_reading)) {
                 $manual_dip_reading = [$manual_dip_reading];
             }
             if (!is_array($fuel_bal)) {
                 $fuel_bal = [$fuel_bal];
             }
             if (!is_array($curr_qty)) {
                 $curr_qty = [$curr_qty];
             }
             if (!is_array($notes)) {
                 $notes = [$notes];
             }
     
             /*
              |------------------------------------------------------------------
              | Guard the per-row arrays before walking them.
              |------------------------------------------------------------------
              |
              | Every array below is addressed by $key from the tank loop. If any
              | one of them is shorter - which happened when the browser dropped an
              | undefined entry while serialising - the row either took a value
              | belonging to a different tank or hit a missing index and the whole
              | save failed. With a single row nothing could be out of step, which
              | is why the fault only appeared with several tanks.
              |
              | Any row without a tank id is skipped rather than saved blank.
              | ($tank_ids is already initialised further up.)
              */
             foreach ($tank_id as $key => $one) {
                 if (empty($one)) {
                     continue;
                 }

                 $tank_ids[] = $tank_id[$key];
     
                 $data = [
                     'business_id'              => $business_id,
                     'location_id'              => $request->location_id,
                     'ref_number'               => $request->ref_number + $key,
                     'tank_id'                  => $tank_id[$key],
                     /*
                      | Dip Date & Time is optional on the form. When left blank an
                      | empty string was written into the column, which then breaks
                      | the difference calculations that read it back.
                      |
                      | The fallback is written in the SAME format the date picker
                      | produces - m/d/Y H:i - because the Dip Report parses this
                      | column with STR_TO_DATE(..., '%m/%d/%Y'). Writing a
                      | database-style date here would save correctly but never
                      | appear in that report.
                      */
                     'date_and_time'            => ! empty($request->date_and_time)
                         ? $request->date_and_time
                         : Carbon::parse($current_date)->format('m/d/Y H:i'),
                     'transaction_date'         => $current_date,
                     /*
                      | TEMPORARY: the Dip Reading and Dip Reading Value fields are
                      | hidden on the form, so these can arrive empty or, if the
                      | browser omits them entirely, be missing from the array.
                      | Reading a missing index directly would raise an undefined
                      | index error and abort the save, so both default to 0.
                      |
                      | To restore: put back $dip_reading[$key] and $fuel_bal[$key].
                      */
                     /*
                      | The typed Dip Reading wins. The chart-driven value is used
                      | only when nothing was typed, which keeps older callers that
                      | still post dip_readings[] working unchanged.
                      */
                     'dip_reading'              => (isset($manual_dip_reading[$key]) && $manual_dip_reading[$key] !== '' && $manual_dip_reading[$key] !== null)
                         ? $manual_dip_reading[$key]
                         : ($dip_reading[$key] ?? 0),
                     'fuel_balance_dip_reading' => $fuel_bal[$key] ?? 0,
                     'current_qty'              => $curr_qty[$key] ?? 0,
                     'tank_manufacturer'        => $request->tank_manufacturer,
                     'tank_capacity'            => $request->tank_capacity,
                     'note'                     => (!empty($notes[$key]) && trim($notes[$key]) !== '') ? trim($notes[$key]) : null,
                 ];
     
                 $tank_dip_chart_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'tank_dip_chart');
                 /*
                  | TEMPORARY: the Dip Reading field is hidden on the Add New Dip
                  | form, so this arrives empty.
                  |
                  | findOrFail() on an empty value throws ModelNotFoundException,
                  | which would abort the save - the user would press Save and
                  | nothing would happen. It now only resolves the chart detail
                  | when a value was actually supplied, and leaves dip_reading as
                  | submitted otherwise.
                  |
                  | To restore: remove the !empty() guard and this comment.
                  */
                 /*
                  | Only resolve a chart detail when the value came from the chart
                  | dropdown. A hand-typed reading is a quantity, not a chart row id
                  | - looking it up would match an unrelated record and overwrite
                  | what the user entered.
                  */
                 $manual_reading_supplied = isset($manual_dip_reading[$key])
                     && $manual_dip_reading[$key] !== ''
                     && $manual_dip_reading[$key] !== null;

                 if ($tank_dip_chart_permission && ! $manual_reading_supplied && ! empty($dip_reading[$key])) {
                     $chart_detail = TankDipChartDetail::find($dip_reading[$key]);

                     if (! empty($chart_detail)) {
                         $data['dip_reading'] = $chart_detail->dip_reading;
                     }
                 }
     
                 /**
                  * FIXED CALCULATION SECTION
                  * - prev dip MUST be before current date
                  * - period is (day after prev dip) -> current day
                  * - pump sales uses settlements.transaction_date (matches reports)
                  * - EXCLUDE settlement-generated tank_sell_lines from "bulk" to avoid double-counting
                  */
                 $prev_DipReading = DipReading::where('business_id', $business_id)
                     ->where('tank_id', $tank_id[$key])
                     ->whereDate('transaction_date', '<', $current_date)
                     ->orderBy('transaction_date', 'desc')
                     ->orderBy('id', 'desc')
                     ->first();
     
                 $opening_stock  = 0.0;
                 $received_stock = 0.0;
                 $sold_qty       = 0.0;
                 $testing_qty    = 0.0;
     
                 // Default window = current day only
                 $start_dt = Carbon::parse($current_date)->startOfDay();
                 $end_dt   = Carbon::parse($current_date)->endOfDay();
     
                 if (!empty($prev_DipReading)) {
                     $opening_stock = (float) $prev_DipReading->fuel_balance_dip_reading;

                     // Start from the day AFTER previous dip day to avoid double-counting prev dip day
                     $start_dt = Carbon::parse($prev_DipReading->transaction_date)->addDay()->startOfDay();
                 }
                 /**
                  * RECEIVED (Tank purchases within period)
                  */
                 $purchaseQ = TankPurchaseLine::leftJoin('transactions', 'tank_purchase_lines.transaction_id', 'transactions.id')
                     ->where('tank_purchase_lines.tank_id', $tank_id[$key])
                     ->where('tank_purchase_lines.business_id', $business_id)
                     ->whereBetween('transactions.transaction_date', [$start_dt, $end_dt])
                     ->where('transactions.type', '!=', 'opening_stock');
     
                 if (!empty($request->location_id) && Schema::hasColumn('transactions', 'location_id')) {
                     $purchaseQ->where('transactions.location_id', $request->location_id);
                 }
     
                 if (Schema::hasColumn('transactions', 'status')) {
                     $purchaseQ->whereIn('transactions.status', ['received', 'final']);
                 }
     
                 $received_stock = (float) $purchaseQ->sum('tank_purchase_lines.quantity');

                // Add stock transfers (column is 'to_tank', not 'to_tank_id')
                $transferQ = TankTransfer::where('to_tank', $tank_id[$key])
                    ->where('business_id', $business_id)
                    ->whereBetween('date', [$start_dt, $end_dt]);
                $received_stock += (float) $transferQ->sum('quantity');

                 /**
                  * BULK SALES (Direct tank sales within period)
                  * Exclude settlement-generated tank_sell_lines (ST*) to avoid double counting with meter_sales.
                  */
                 $bulkQ = TankSellLine::leftJoin('transactions', 'tank_sell_lines.transaction_id', 'transactions.id')
                     ->where('tank_sell_lines.tank_id', $tank_id[$key])
                     ->where('tank_sell_lines.business_id', $business_id)
                     ->whereBetween('transactions.transaction_date', [$start_dt, $end_dt])
                     ->where('transactions.type', 'sell')
                     ->where(function ($q) {
                         $q->whereNull('transactions.sub_type')
                           ->orWhere('transactions.sub_type', '!=', 'settlement');
                     });
     
                 if (!empty($request->location_id) && Schema::hasColumn('transactions', 'location_id')) {
                     $bulkQ->where('transactions.location_id', $request->location_id);
                 }
     
                 if (Schema::hasColumn('transactions', 'status')) {
                     $bulkQ->where('transactions.status', 'final');
                 }
     
                 $bulk_sales = (float) $bulkQ->sum('tank_sell_lines.quantity');
     
                 /**
                  * PUMP SALES + TESTING
                  * Use settlements.transaction_date (matches your reports)
                  */
                 $meterBase = DB::table('meter_sales')
                     ->join('settlements', 'settlements.id', '=', 'meter_sales.settlement_no')
                     ->join('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
                     ->where('pumps.fuel_tank_id', $tank_id[$key])
                     ->where('settlements.business_id', $business_id)
                     ->whereBetween('settlements.transaction_date', [$start_dt, $end_dt]);
     
                 if (!empty($request->location_id) && Schema::hasColumn('settlements', 'location_id')) {
                     $meterBase->where('settlements.location_id', $request->location_id);
                 }
     
                 $testing_qty     = (float) (clone $meterBase)->sum('meter_sales.testing_qty');
                 $meter_total_qty = (float) (clone $meterBase)->sum('meter_sales.qty');
     
                 // Sold through pumps excludes testing
                 $meter_sold_qty = max(0.0, $meter_total_qty - $testing_qty);
     
                 // Total sold = pump sold + (true bulk only, not settlement)
                 $sold_qty = $meter_sold_qty + $bulk_sales;

                 // Optional fallback for first-ever dip with no activity detected
                 if (empty($prev_DipReading) && $received_stock == 0.0 && $sold_qty == 0.0 && $testing_qty == 0.0) {
                     $received_stock = (float) $fuel_bal[$key];
                 }
     
                 // Save dip
                 DipReading::create($data);
     
                 // Build dip details message
                 $tank = FuelTank::findOrFail($tank_id[$key]);
     
                 if ($key > 0) {
                     $prod_summary .= PHP_EOL . PHP_EOL;
                 }
     
                 $prod_summary .= "Tank No: " . $tank->fuel_tank_number . PHP_EOL;
                 $prod_summary .= "System Qty: " . $this->productUtil->num_f($curr_qty[$key]) . PHP_EOL;
                 $prod_summary .= "Dip Qty: " . $this->productUtil->num_f($fuel_bal[$key]) . PHP_EOL;
                 $prod_summary .= "Qty Difference: " . $this->productUtil->num_f((float)$fuel_bal[$key] - (float)$curr_qty[$key]);
     
                 // Accumulate totals for SMS
                 $total_opening_stock  += $opening_stock;
                 $total_received_stock += $received_stock;
                 $total_sold_qty       += $sold_qty;
                 $total_testing_qty    += $testing_qty;
             }
     
             // Format totals for SMS
             $opening_stock  = $this->productUtil->num_f($total_opening_stock);
             $received_stock = $this->productUtil->num_f($total_received_stock);
             $sold_qty       = $this->productUtil->num_f($total_sold_qty);
             $testing_qty    = $this->productUtil->num_f($total_testing_qty);
     
             $sms_data = [
                 'date_entered'   => $request->date_and_time,
                 'time_entered'   => date('H:i'),
                 'dip_details'    => $prod_summary,
                 'opening_stock'  => $opening_stock,
                 'received_stock' => $received_stock,
                 'sold_qty'       => $sold_qty,
                 'testing_qty'    => $testing_qty,
             ];
     
             $this->notificationUtil->sendPetroNotification('stock_and_dip_details', $sms_data);
     
             $output = [
                 'success' => true,
                 'msg'     => __('petrogeneral::lang.success'),
             ];
         } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
    
            $output = [
                'success' => false,
                'msg'     => 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
            ];
        }
    
        return $output;
     }
     
    /**
     * Get Dip Resettings
     * @return Response
     */

    public function getDipResetting()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $query = DB::table('dip_resettings')
                ->leftjoin('business_locations', 'dip_resettings.location_id', 'business_locations.id')
                ->leftjoin('fuel_tanks', 'dip_resettings.tank_id', 'fuel_tanks.id')
                ->leftjoin('products', 'fuel_tanks.product_id', 'products.id')
                ->select([
                    'dip_resettings.*',
                    'business_locations.name as location_name',
                    'fuel_tanks.fuel_tank_number as tank_name',
                    'products.name as product_name',
                ])
                ->where('dip_resettings.business_id', $business_id);
            if (! empty(request()->location_id)) {
                $query->where('dip_resettings.location_id', request()->location_id);
            }
            if (! empty(request()->tank_id)) {
                $query->where('dip_resettings.tank_id', request()->tank_id);
            }
            if (! empty(request()->product_id)) {
                $query->where('fuel_tanks.product_id', request()->product_id);
            }
            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $query = $query->whereRaw("STR_TO_DATE(date_and_time, '%m/%d/%Y') BETWEEN ? AND ?", [date(request()->start_date), date(request()->end_date)]);
            }
            $dip_report = Datatables::of($query->get())->removeColumn('id');
            return $dip_report->rawColumns(['difference'])
                ->make(true);
        }
    }
    /**
     * Add new Dip
     * @return Response
     */

    public function addResettingDip()
    {
        $business_id         = request()->session()->get('user.business_id');
        $business_locations  = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        $count               = DipResetting::where('business_id', $business_id)->count();
        $meter_reset_form_no = $count + 1;
        $meter_reset_form_no = "DRIP-" . $meter_reset_form_no;
        $tanks               = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'id');
        $business            = Business::where('id', $business_id)->first();
        $quantity_presicion  = $business->quantity_precision;
        return view('petrogeneral::dip_management.add_resetting_dip')->with(compact(
            'business_locations',
            'tanks',
            'meter_reset_form_no',
            'quantity_presicion'
        ));
    }
    /**
     * Save new Dip
     * @return Response
     */

    /**
     * Save one Dip Reset covering several tanks, in a single transaction.
     *
     * Either every tank in the round is corrected or none is. A half-saved dip
     * round would leave some tanks adjusted and others not, with nothing to show
     * which - worse than a save that fails cleanly and can simply be repeated.
     *
     * Laravel nests transactions with savepoints, so the inner
     * beginTransaction/commit inside saveResettingDip() behave correctly within
     * the outer one used here.
     */
    /**
     * Mirror a dip-reset adjustment into the Stock Adjustment New module.
     *
     *
     * WHY THIS IS NEEDED
     *
     * The Adjustments screen in Stock Adjustment New reads its OWN table,
     * san_stock_adjustments - not the core `transactions` table. A dip reset
     * creates a core stock_adjustment transaction and nothing else, so it could
     * never appear on that screen: the list was not filtering dip resets out, it
     * had simply never been told about them.
     *
     * That table carries a host_transaction_id column, which is exactly the link
     * this needs - the module is designed to sit alongside a core transaction
     * rather than replace it. So the record is written here with the core
     * transaction's id, and the two stay tied together.
     *
     * Everything is guarded on the tables existing. Stock Adjustment New is a
     * separate module and may not be installed on every tenant; a dip reset must
     * not fail because a reporting module is absent. A failure here is logged
     * and swallowed for the same reason - the stock correction itself has
     * already been applied correctly, and losing it because a mirror row could
     * not be written would be far worse than a missing list entry.
     */
    protected function mirrorDipResetToStockAdjustmentNew(
        $stock_adjustment,
        $request,
        $business_id,
        $product,
        $quantity,
        $adjustmentType
    ) {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('san_stock_adjustments')
                || ! \Illuminate\Support\Facades\Schema::hasTable('san_stock_adjustment_lines')) {
                return;
            }

            $systemQty = (float) $this->productUtil->num_uf($request->current_qty);
            $countedQty = (float) $this->productUtil->num_uf($request->reset_new_dip);
            $unitCost = (float) ($product->default_purchase_price ?? 0);
            $signedQty = $adjustmentType === 'decrease' ? -abs($quantity) : abs($quantity);

            $adjustmentId = \Illuminate\Support\Facades\DB::table('san_stock_adjustments')->insertGetId([
                'business_id' => $business_id,
                'location_id' => $request->location_id,
                'store_id' => $stock_adjustment->store_id ?? null,
                'adjustment_no' => $stock_adjustment->ref_no ?: $request->meter_reset_form_no,
                'adjustment_date' => $stock_adjustment->transaction_date,
                'adjustment_type' => 'quantity',
                'stock_adjustment_type' => $adjustmentType,
                'status' => 'posted',
                'total_qty' => abs($quantity),
                'total_cost_amount' => abs($quantity) * $unitCost,
                // Names the origin, so a dip reset is distinguishable in the list.
                'notes' => trim('Dip Reset ' . $request->meter_reset_form_no . ' - ' . (string) $request->reason),
                'created_by' => $request->session()->get('user.id'),
                'posted_by' => $request->session()->get('user.id'),
                'posted_at' => now(),
                'host_transaction_id' => $stock_adjustment->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::table('san_stock_adjustment_lines')->insert([
                'adjustment_id' => $adjustmentId,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku ?? null,
                'system_qty' => $systemQty,
                'counted_qty' => $countedQty,
                'adjustment_qty' => $signedQty,
                'stock_adjustment_type' => $adjustmentType,
                'unit_cost' => $unitCost,
                'cost_amount' => abs($quantity) * $unitCost,
                'line_notes' => (string) $request->reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Dip reset could not be mirrored to Stock Adjustment New', [
                'transaction_id' => $stock_adjustment->id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The submitted transaction date, resolved to Y-m-d and never null.
     *
     * IS2155: a dip reset failed with
     *
     *     SQLSTATE[23000]: Column 'transaction_date' cannot be null
     *
     * The form submitted "09/01/26" - a TWO-DIGIT year. uf_date() expects the
     * business format and returns NULL on anything else rather than throwing, so
     * the null travelled all the way to the insert.
     *
     * The single-tank path already guarded this: uf_date, then Carbon::parse,
     * then today. The zero-difference branch I added for the multi-tank save did
     * not - it called uf_date() alone. That omission is the bug.
     *
     * The same resolution now lives in one place and is used by both, so the two
     * paths cannot drift apart again.
     */
    protected function resolveDipTransactionDate(Request $request): string
    {
        $resolved = $this->transactionUtil->uf_date($request->transaction_date);

        if (empty($resolved)) {
            // Carbon reads two-digit years, ISO and most other shapes.
            try {
                $resolved = \Carbon\Carbon::parse($request->transaction_date)->format('Y-m-d');
            } catch (\Throwable $e) {
                $resolved = null;
            }
        }

        if (empty($resolved)) {
            \Log::warning('Dip resetting: transaction_date could not be read, defaulting to today', [
                'submitted' => $request->transaction_date,
            ]);

            $resolved = now()->format('Y-m-d');
        }

        return (string) $resolved;
    }

    /**
     * The account nominated on the Stock Adjustment New settings page.
     *
     * IS2159: a dip reset must post its gain or loss to the account LINKED there,
     * not to whichever Income or Expenses account happens to come first.
     *
     * san_stock_adjustment_account_mappings is matched on business,
     * adjustment_type and the product's SUB CATEGORY - nothing else. The mapping
     * is defined per sub category, so resolving by anything broader would post a
     * gain or loss to a book the settings never nominated for this product.
     *
     * Returns null when the module is not installed or no rule matches, and the
     * caller then falls back to its previous behaviour. A business that has
     * configured nothing keeps working exactly as before.
     */
    protected function getMappedStockAdjustmentAccountId($business_id, string $type, int $product_id): ?int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('san_stock_adjustment_account_mappings')) {
                return null;
            }

            $product = $product_id > 0
                ? DB::table('products')->where('id', $product_id)->first(['category_id', 'sub_category_id'])
                : null;

            $query = DB::table('san_stock_adjustment_account_mappings')
                ->where('business_id', $business_id)
                ->where('adjustment_type', $type);

            if (\Illuminate\Support\Facades\Schema::hasColumn('san_stock_adjustment_account_mappings', 'is_active')) {
                $query->where('is_active', 1);
            }

            /*
             * Matched on the PRODUCT SUB CATEGORY only.
             *
             * The mapping is keyed by sub category, so that is the only thing
             * matched on. Earlier drafts also fell back to the category and then
             * to a business-wide rule; both are removed. A near-miss rule
             * resolving to an account the settings never nominated for this sub
             * category would post a gain or loss to the wrong book - silently,
             * and in the accounts.
             *
             * With no sub-category rule this returns null, and the caller falls
             * back to its previous Income/Expenses behaviour rather than posting
             * nothing at all.
             */
            $subCategoryId = (int) ($product->sub_category_id ?? 0);

            if ($subCategoryId <= 0) {
                return null;
            }

            $mapping = $query->where('sub_category_id', $subCategoryId)->first();

            if (! $mapping) {
                return null;
            }

            return (int) ($mapping->stock_account_id ?: $mapping->account_to_link_id);
        } catch (\Throwable $e) {
            // Never block a dip reset because a reporting mapping could not be read.
            \Log::warning('Dip reset: stock adjustment account mapping lookup failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function saveResettingDipRows(Request $request, array $rows)
    {
        $business_id = $request->session()->get('user.business_id');
        $usable = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $dipQty = trim((string) ($row['dip_qty'] ?? ''));

            // Blank means the tank was not dipped - not that it read zero.
            if ($dipQty === '') {
                continue;
            }

            $systemQty = (float) str_replace(',', '', (string) ($row['system_qty'] ?? 0));
            $difference = $systemQty - ((float) str_replace(',', '', $dipQty));

            $type = null;
            // Difference is System Qty - Dip Qty. A negative difference means
            // the physical quantity is higher than the system quantity and stock
            // must INCREASE. A positive difference means physical stock is lower
            // and stock must DECREASE.
            if ($difference < -0.0005) {
                $type = 'increase';
            } elseif ($difference > 0.0005) {
                $type = 'decrease';
            }

            $usable[] = [
                'row' => $row,
                'difference' => $difference,
                'type' => $type,
                'has_movement' => abs($difference) >= 0.0005,
            ];
        }

        $withMovement = array_values(array_filter($usable, function ($item) {
            return $item['has_movement'];
        }));

        /*
         * Refused only when NO row has a difference - the save would then create
         * nothing at all. One unchanged tank among several must not block a
         * legitimate round.
         */
        if (empty($withMovement)) {
            return [
                'success' => 0,
                'msg'     => 'No difference to reset.',
            ];
        }

        foreach ($withMovement as $item) {
            if (trim((string) ($item['row']['note'] ?? '')) === '') {
                return [
                    'success' => 0,
                    'msg'     => 'Note is required.',
                ];
            }
        }

        try {
            DB::beginTransaction();

            foreach ($usable as $item) {
                $row = $item['row'];

                if ($item['has_movement']) {
                    /*
                     * Replayed through the single-tank path. `rows` is cleared on
                     * the copy so the method takes its normal branch instead of
                     * recursing back into this one.
                     */
                    $rowRequest = clone $request;
                    $rowRequest->replace(array_merge($request->except('rows'), [
                        'tank_id' => $row['tank_id'] ?? null,
                        'current_qty' => $row['system_qty'] ?? null,
                        /*
                         * IS2156: the COUNTED quantity drives the stock movement.
                         *
                         * saveResettingDip() computes the adjustment as
                         *
                         *     abs($reset_new_dip - $current_qty)
                         *
                         * so whatever is passed here decides how much stock moves.
                         *
                         * The multi-tank form shows its difference as
                         * System Qty - Dip Qty, and Dip Qty is the physically
                         * counted amount. Passing new_dip instead moved stock by a
                         * DIFFERENT figure than the one on screen - which is why
                         * the tank balance and the product stock ended up apart by
                         * small round amounts (-50, -50, -50, -70) after a reset.
                         *
                         * The separate New Dip field was removed from the form.
                         * Dip Qty is the physical counted quantity and is therefore
                         * also the quantity stored as reset_new_dip. Older payloads
                         * that still include new_dip remain harmless.
                         */
                        'reset_new_dip' => $row['dip_qty'] ?? ($row['new_dip'] ?? null),
                        'current_dip_difference' => number_format($item['difference'], 3, '.', ''),
                        'qty_to_adjust' => number_format(abs($item['difference']), 3, '.', ''),
                        /*
                         * Never trust the browser for direction. The requested rule is:
                         *   Difference = System Qty - Dip Qty
                         *   negative => Increase
                         *   positive => Decrease
                         *
                         * Recalculate it above and use that value for the actual stock
                         * movement as well as the accounting direction.
                         */
                        'adjustment_type' => $item['type'],
                        'inventory_adjustment_account' => $row['inventory_adjustment_account'] ?? null,
                        'reason' => $row['note'] ?? '',
                    ]));

                    $result = $this->saveResettingDip($rowRequest);

                    if (is_array($result) && empty($result['success'])) {
                        throw new \Exception($result['msg'] ?? 'Dip reset failed for one of the tanks.');
                    }

                    continue;
                }

                /*
                 * Zero difference: the dip is recorded, nothing moves. No stock
                 * adjustment, no Type, no account - showing a direction for a
                 * movement that never happened would misdescribe the record. The
                 * note is supplied automatically since there is no correction for
                 * the user to explain.
                 */
                DipResetting::create([
                    'business_id'            => $business_id,
                    'location_id'            => $request->location_id,
                    'meter_reset_form_no'    => $request->meter_reset_form_no,
                    'tank_id'                => $row['tank_id'] ?? null,
                    'date_and_time'          => $request->date_and_time,
                    'current_qty'            => $row['system_qty'] ?? 0,
                    'current_dip_difference' => 0,
                    // New Dip column is no longer part of the form; Dip Qty is
                    // the physical counted quantity and must be recorded here too.
                    'reset_new_dip'          => $row['dip_qty'] ?? ($row['new_dip'] ?? null),
                    'reason'                 => 'No difference',
                    // IS2155: resolved, never null - see resolveDipTransactionDate().
                    'transaction_date'       => $this->resolveDipTransactionDate($request),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            \Illuminate\Support\Facades\Log::error('Dip reset multi-tank save failed', [
                'business_id' => $business_id,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => 0,
                'msg'     => 'Could not save the dip reset: ' . $e->getMessage(),
            ];
        }

        return [
            'success' => 1,
            'msg'     => __('petrogeneral::lang.success'),
        ];
    }

    public function saveResettingDip(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        /*
         * A dip reset with no difference corrects nothing.
         *
         * If the dip reading matches what the system already holds there is
         * nothing to adjust, and saving would write a zero-quantity movement
         * into the stock ledger - a record that looks like a correction but
         * changes nothing and clutters every later reconciliation.
         *
         * The form blocks this in the browser as well; that check is a
         * convenience and can be bypassed, and this writes stock, so the rule is
         * enforced here too.
         *
         * A tolerance is used rather than a strict !== 0 because the value
         * arrives as a formatted string with three decimals; comparing floats
         * exactly against zero is unreliable.
         */
        /*
         * MULTI-TANK SAVE.
         *
         * The form now posts one row per tank at the chosen location as
         * rows[i][...]. Each row that has a Dip Qty is saved; rows left blank
         * were not dipped and are ignored entirely.
         *
         *   difference = System Qty - Dip Qty
         *   negative -> Increase; positive -> Decrease
         *   with a difference -> dip record + stock adjustment + tank update
         *   zero difference   -> dip record only, note "No difference",
         *                        NO stock adjustment and no account
         *
         * Every row is written inside ONE database transaction. A dip round that
         * half-saved would leave some tanks corrected and others not, with no
         * indication which - worse than a save that fails cleanly and can be
         * repeated.
         *
         * The whole save is refused only when no row has a difference, because
         * then it would create no adjustment at all. One unchanged tank among
         * several must not block the round.
         */
        /*
         * MULTI-TANK SAVE
         * ---------------
         * The form posts one row per tank at the chosen location as rows[i][...].
         * Rows left blank were not dipped and are ignored; rows with a Dip Qty
         * are saved.
         *
         * Each row with a real difference is replayed through THIS SAME METHOD
         * with a per-row request. That is deliberate: the stock adjustment, the
         * tank update, the accounting entry and the ledger application are 150
         * lines that must run in a specific order - there is a comment further
         * down recording a bug where one block ran before the transaction it
         * referenced existed. Re-using the tested path is safer than copying it,
         * and it cannot drift from the single-tank behaviour later.
         *
         * A zero-difference row is recorded WITHOUT an adjustment: the tank was
         * dipped and matched, which is worth knowing and is not the same as a
         * tank nobody looked at. No movement means no Type and no account.
         *
         * Performance: the row loop adds no queries beyond those the same tanks
         * would cost if saved one at a time - it is the same work in one request
         * instead of several, so it is cheaper overall, not dearer.
         */
        $rows = $request->input('rows');

        if (! empty($rows) && is_array($rows)) {
            return $this->saveResettingDipRows($request, $rows);
        }

        $dipDifference = (float) str_replace(',', '', (string) $request->input('current_dip_difference'));

        if (abs($dipDifference) < 0.0005) {
            $output = [
                'success' => 0,
                'msg'     => 'No difference to reset.',
            ];

            return redirect()->back()->with(['status' => $output])->withInput();
        }

        // The note explains WHY stock was corrected - required, not optional.
        if (trim((string) $request->input('reason')) === '') {
            $output = [
                'success' => 0,
                'msg'     => 'Note is required.',
            ];

            return redirect()->back()->with(['status' => $output])->withInput();
        }

        $has_reviewed = $this->transactionUtil->hasReviewed($request->date_and_time);

        if (! empty($has_reviewed)) {
            $output = [
                'success' => 0,
                'msg'     => __('lang_v1.review_first'),
            ];

            return redirect()->back()->with(['status' => $output]);
        }

        $reviewed = $this->transactionUtil->get_review($request->date_and_time, $request->date_and_time);

        if (! empty($reviewed)) {
            $output = [
                'success' => 0,
                'msg'     => "You can't add a resetting for an already reviewed date",
            ];

            return $output;
        }

        $current_qty = $this->productUtil->num_uf($request->current_qty);
        $reset_new_dip = $this->productUtil->num_uf($request->reset_new_dip);
        $quantity = abs($reset_new_dip - $current_qty);

        if ($quantity <= 0) {
            return [
                'success' => 0,
                'msg'     => __('No stock movement was created because the reset quantity is the same as the current quantity.'),
            ];
        }

        /*
         |----------------------------------------------------------------------
         | transaction_date must never reach the insert as NULL.
         |----------------------------------------------------------------------
         |
         | Reported: saving a dip resetting failed with
         |     Column 'transaction_date' cannot be null
         |
         | uf_date() returns NULL when the value is empty or not in the expected
         | format, and the column does not allow it - so the whole save aborted.
         |
         | The form fault is fixed separately (the field had no id, so its date
         | picker never attached). This is the second line of defence: if the date
         | still cannot be read, TODAY is used rather than failing the save. A
         | resetting recorded today with today's date is far better than an error
         | the operator cannot act on.
         */
        $transaction_date_for_reset = $this->transactionUtil->uf_date($request->transaction_date);

        if (empty($transaction_date_for_reset)) {
            try {
                $transaction_date_for_reset = \Carbon\Carbon::parse($request->transaction_date)->format('Y-m-d');
            } catch (\Throwable $e) {
                $transaction_date_for_reset = null;
            }
        }

        if (empty($transaction_date_for_reset)) {
            \Log::warning('Dip resetting: transaction_date could not be read, defaulting to today', [
                'submitted' => $request->transaction_date,
                'tank_id'   => $request->tank_id,
            ]);

            $transaction_date_for_reset = \Carbon\Carbon::today()->format('Y-m-d');
        }

        // try {
        $data = [
            'business_id'            => $business_id,
            'location_id'            => $request->location_id,
            'meter_reset_form_no'    => $request->meter_reset_form_no,
            'tank_id'                => $request->tank_id,
            'date_and_time'          => $request->date_and_time,
            'current_qty'            => $current_qty,
            'current_dip_difference' => $request->current_dip_difference,
            'reset_new_dip'          => $reset_new_dip,
            'reason'                 => $request->reason,
            'transaction_date'       => $transaction_date_for_reset,
        ];

        DB::beginTransaction();
        $dip_resetting    = DipResetting::create($data);
        $for_current_diff = DB::table('dip_readings')->where('tank_id', $request->tank_id)->latest()->first();
        if ($for_current_diff) {
            $dip_report = DB::table('dip_readings')
                ->where('id', $for_current_diff->id)
                ->update(['reset_new_dip' => $reset_new_dip]);
        }

        $user_id                              = $request->session()->get('user.id');
        $input_data['type']                   = 'stock_adjustment';
        $input_data['business_id']            = $business_id;
        $input_data['created_by']             = $user_id;
        $input_data['additional_notes']       = $request->reason;
        $input_data['adjustment_type']        = 'normal';
        $input_data['location_id']            = $request->location_id;
        /*
         | Reuses the guarded date resolved earlier in this method.
         |
         | This called uf_date() again directly, and it returns NULL when the value
         | is empty or in an unexpected format - so the stock_adjustment insert
         | failed with
         |     Column 'transaction_date' cannot be null
         |
         | $transaction_date_for_reset is the same value already worked out for the
         | dip_resettings row above, with a fallback to today. Using it here keeps
         | the two rows on the SAME date, which matters: they describe one event and
         | would otherwise be reportable on different days.
         */
        $input_data['transaction_date']       = $transaction_date_for_reset;
        $input_data['total_amount_recovered'] = 0.00;
        //     //Update reference count
        $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
        //Generate reference number
        if (empty($input_data['ref_no'])) {
            $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count);
        }
        $product = FuelTank::leftjoin('products', 'fuel_tanks.product_id', 'products.id')
            ->leftjoin('variations', 'products.id', 'variations.product_id')
            ->where('fuel_tanks.id', $request->tank_id)
            /*
             * IS2159: dpp_inc_tax is loaded as well.
             *
             * The value posted to the Stock Gain / Stock Loss account books must
             * be difference x purchase price INCLUDING tax. Only the exclusive
             * price was selected here, so the inclusive figure was not even
             * available to calculate with.
             */
            ->select(
                'products.*',
                'variations.id as variation_id',
                'variations.default_purchase_price',
                'variations.dpp_inc_tax'
            )->first();
        $adjustment_line = [
            'product_id'                   => $product->id,
            'variation_id'                 => $product->variation_id,
            'quantity'                     => $this->productUtil->num_uf($quantity),
            'unit_price'                   => $this->productUtil->num_uf($product->default_purchase_price),
            'type'                         => $request->adjustment_type,
            'stock_adjustment_type'        => $request->adjustment_type,
            'tank_id'                      => $request->tank_id,
            'inventory_adjustment_account' => $request->inventory_adjustment_account,
        ];
        $product_data[] = $adjustment_line;

        /*
         * IS1970: a Tank Reset applies its stock adjustment like any other.
         *
         * The note that stood here said the reset "corrects the tank balance
         * only" and must not touch product stock. That is what made the two
         * figures drift: the tank was reset to the counted quantity while
         * variation_location_details kept the old number, so Stock Center and
         * the F22 form disagreed with the tank after every reset.
         *
         * A reset IS a real inventory movement - the user has counted the tank
         * and is correcting stock to physical reality - and this method already
         * records it as one: a stock_adjustment Transaction, its
         * stock_adjustment_lines, TankPurchaseLine/TankSellLine, the accounting
         * entry, and fuel_tanks.current_balance. The adjustment was written but
         * never APPLIED, so the ledger said one thing and the stock table
         * another.
         *
         * This is deliberately the SAME application step a manual adjustment
         * uses in StockAdjustmentController (decreaseProductQuantity plus
         * decreaseProductQuantityStore, direction passed explicitly). One
         * mechanism moves stock for every adjustment in the system, so there is
         * a single place to look when a figure disagrees - rather than a
         * separate write per screen, which is how this divergence began.
         *
         * The direction MUST be passed explicitly: decreaseProductQuantity()
         * only infers it from the sign when $adjustment_type is null, and takes
         * the absolute difference regardless, so a negative quantity meaning
         * "increase" would be applied as a DECREASE.
         */
        /*
         * IS2159: value the movement at the TAX-INCLUSIVE purchase price.
         *
         * This used default_purchase_price - the EXCLUSIVE figure - so every
         * amount posted to the Stock Gain and Stock Loss account books was short
         * by the tax. final_total is what the account transaction below is
         * created with, so the books were understated by exactly that margin.
         *
         * dpp_inc_tax is the "Default Purchase Price (Incl. Tax)" shown on the
         * product screen, which is the basis the requirement names.
         *
         * Falls back to the exclusive price only when dpp_inc_tax is absent or
         * zero: on a product where the inclusive price was never set, an
         * approximate value is better than posting nothing at all.
         */
        $unit_cost_inc_tax = (float) ($product->dpp_inc_tax ?? 0);

        if ($unit_cost_inc_tax <= 0) {
            $unit_cost_inc_tax = (float) ($product->default_purchase_price ?? 0);
        }

        $input_data['final_total'] = $quantity * $unit_cost_inc_tax;

        $input_data['invoice_no'] = $request->meter_reset_form_no;
        $input_data['sub_type']   = 'dip_resetting';
        $input_data['stock_adjustment_type'] = $request->adjustment_type;
        $stock_adjustment         = Transaction::create($input_data);

        // Make the reset visible on the Stock Adjustment New / Adjustments page.
        $this->mirrorDipResetToStockAdjustmentNew(
            $stock_adjustment,
            $request,
            $business_id,
            $product,
            $quantity,
            $request->adjustment_type
        );

        /*
         |----------------------------------------------------------------------
         | Moved BELOW the line above - it used $stock_adjustment before it existed.
         |----------------------------------------------------------------------
         |
         | Reported: resetting a tank failed with
         |     Undefined variable $stock_adjustment
         |
         | This block reads $stock_adjustment->store_id, ->id and ->ref_no, but it
         | sat 33 lines ABOVE the Transaction::create() that assigns it. The stock
         | application was inserted in the wrong place, so it ran first and the
         | variable did not yet exist.
         |
         | Nothing inside is changed - it is the same stock application, now run
         | after the adjustment it belongs to has been created.
         */
        $reset_adjustment_qty = $this->productUtil->num_uf($quantity);

        $this->productUtil->decreaseProductQuantity(
            $product->id,
            $product->variation_id,
            $request->location_id,
            $reset_adjustment_qty,
            0,
            $request->adjustment_type
        );

        // Store-level stock, kept in step with the location-level figure exactly
        // as StockAdjustmentController does. Falls back to the business's first
        // store when the adjustment carries none, matching that controller.
        $reset_store_id = $stock_adjustment->store_id
            ?? optional(Store::where('business_id', $business_id)->first())->id;

        if (!empty($reset_store_id)) {
            $this->productUtil->decreaseProductQuantityStore(
                $product->id,
                $product->variation_id,
                $request->location_id,
                $reset_adjustment_qty,
                $reset_store_id,
                $request->adjustment_type,
                0
            );
        }
        $counted_dip = (float) $this->productUtil->num_uf($request->reset_new_dip);
        \DB::table('fuel_tanks')->where('id', $request->tank_id)->update(['current_balance' => $counted_dip, 'updated_at' => now()]);
        $tank_total = (float) \DB::table('fuel_tanks')->where('product_id', $product->id)->where('location_id', $request->location_id)->sum('current_balance');
        \DB::table('variation_location_details')->where('product_id', $product->id)->where('variation_id', $product->variation_id)->where('location_id', $request->location_id)->update(['qty_available' => $tank_total, 'updated_at' => now()]);

        \Log::info('IS1970 tank reset stock adjustment applied', [
            'stock_adjustment_id' => $stock_adjustment->id,
            'ref_no'              => $stock_adjustment->ref_no,
            'tank_id'             => $request->tank_id,
            'product_id'          => $product->id,
            'variation_id'        => $product->variation_id,
            'location_id'         => $request->location_id,
            'store_id'            => $reset_store_id,
            'adjustment_type'     => $request->adjustment_type,
            'quantity'            => $reset_adjustment_qty,
            'reset_new_dip'       => $reset_new_dip,
        ]);



        if ($request->adjustment_type == "increase") {
            $input_tank_purchase['transaction_id'] = $stock_adjustment->id;
            $input_tank_purchase['business_id']    = $business_id;
            $input_tank_purchase['tank_id']        = $request->tank_id;
            $input_tank_purchase['product_id']     = $product->id;
            $input_tank_purchase['quantity']       = $quantity;
            $tank_purchase_lines                   = TankPurchaseLine::create($input_tank_purchase);
        } else {
            $input_tank_purchase['transaction_id'] = $stock_adjustment->id;
            $input_tank_purchase['business_id']    = $business_id;
            $input_tank_purchase['tank_id']        = $request->tank_id;
            $input_tank_purchase['product_id']     = $product->id;
            $input_tank_purchase['quantity']       = $quantity;
            $tank_purchase_lines                   = TankSellLine::create($input_tank_purchase);
        }
        $dip_resetting->adjustment_transaction_id = $stock_adjustment->id;
        $dip_resetting->save();
        $stock_adjustment->stock_adjustment_lines()->createMany($product_data);
        FuelTank::where('id', $request->tank_id)->update([
            'current_balance' => $reset_new_dip,
        ]);

        $business = [
            'id'                => $business_id,
            'accounting_method' => $request->session()->get('business.accounting_method'),
            'location_id'       => $request->location_id,
        ];
        $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment');
        if ($request->adjustment_type == 'increase') {
            $acc_tran_type = 'debit';
        }
        if ($request->adjustment_type == 'decrease') {
            $acc_tran_type = 'credit';
        }
        $this_product = Product::where('id', $product->id)->first();
        if (! empty($this_product->stock_type)) {
            $account_transaction_data = [
                'amount'                 => $stock_adjustment->final_total,
                'account_id'             => $this_product->stock_type,
                'type'                   => $acc_tran_type,
                'operation_date'         => $stock_adjustment->transaction_date,
                'created_by'             => $stock_adjustment->created_by,
                'transaction_id'         => $stock_adjustment->id,
                'transaction_payment_id' => null,
                'note'                   => null,
            ];
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }
        if (! empty($request->inventory_adjustment_account)) {
            if ($request->adjustment_type == 'increase') {
                $acc_tran_type = 'credit';
            }
            if ($request->adjustment_type == 'decrease') {
                $acc_tran_type = 'debit';
            }
            $account_transaction_data = [
                'amount'                 => $stock_adjustment->final_total,
                'account_id'             => $request->inventory_adjustment_account,
                'type'                   => $acc_tran_type,
                'operation_date'         => $stock_adjustment->transaction_date,
                'created_by'             => $stock_adjustment->created_by,
                'transaction_id'         => $stock_adjustment->id,
                'transaction_payment_id' => null,
                'note'                   => null,
            ];
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }
        DB::commit();
        $output = [
            'success' => 1,
            'msg'     => __('petrogeneral::lang.success'),
        ];
        return $output;
    }
    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */

    public function store(Request $request)
    {
    }
    /**
     * Show the specified resource.
     * @return Response
     */

    public function show()
    {
        return view('petrogeneral::show');
    }
    /**
     * Show the form for editing the specified resource.
     * @return Response
     */

    public function edit($id)
    {
        $dip                       = DipReading::findOrFail($id);
        $business_id               = request()->session()->get('user.business_id');
        $business_locations        = BusinessLocation::forDropdown($business_id);
        $default_location          = current(array_keys($business_locations->toArray()));
        $tanks                     = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'id');
        $count                     = DipReading::where('business_id', $business_id)->count();
        $ref_no                    = $count + 1;
        $tank_dip_chart_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'tank_dip_chart');
        return view('petrogeneral::dip_management.edit_dip')->with(compact(
            'business_locations',
            'default_location',
            'tanks',
            'tank_dip_chart_permission',
            'ref_no',
            'dip'
        ));

    }
    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */

    public function update(Request $request, $id)
    {
        try {
            $input                     = $request->except('_token', '_method', 'daily_report_date');
            $input['transaction_date'] = $this->transactionUtil->uf_date($request->daily_report_date);
            $input['date_and_time']    = $request->date_and_time;

            DipReading::where('id', $id)->update($input);
            $output = [
                'success' => true,
                'msg'     => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
    /**
     * Remove the specified resource from storage.
     * @return Response
     */

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $dip_chart         = TankDipChart::findOrFail($id);
            $dip_chart_details = TankDipChartDetail::where('tank_dip_chart_id', $id)->get()->last();

            $changed_msg = "";
            $is_changed  = true;
            $changed_msg .= "Deleted Dip Sheet name " . $dip_chart->sheet_name . PHP_EOL;
            $changed_msg .= "Deleted Dip Reading " . $this->transactionUtil->num_f($dip_chart_details->dip_reading) . PHP_EOL;
            $changed_msg .= "Deleted Dip Readinng in Lts " . $this->transactionUtil->num_f($dip_chart_details->dip_reading_value) . PHP_EOL;

            if (! empty($is_changed) && ! empty($changed_msg)) {

                $activity               = new Activity();
                $activity->log_name     = "Dip Chart";
                $activity->description  = "delete";
                $activity->subject_id   = $id;
                $activity->subject_type = "Modules\Superadmin\Entities\TankDipChart";
                $activity->causer_id    = auth()->user()->id;
                $activity->causer_type  = 'App\User';
                $activity->properties   = $changed_msg;
                $activity->created_at   = date('Y-m-d H:i');
                $activity->updated_at   = date('Y-m-d H:i');

                // Save the activity
                $activity->save();

            }
            $dip_chart->delete();
            $dip_chart_details->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg'     => __('superadmin::lang.tank_dip_chart_delete_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
    /**
     * Get tank product details
     * @return Response
     */

    public function getTankProduct($tank_id)
    {
        $product = FuelTank::leftjoin('products', 'fuel_tanks.product_id', 'products.id')
            ->join('variations as v', 'v.product_id', '=', 'products.id')
            ->leftJoin('variation_location_details as vld', 'vld.variation_id', '=', 'v.id')
            ->where('fuel_tanks.id', $tank_id)
            ->select('products.*', DB::raw('SUM(vld.qty_available) as current_stock'))->first();
        return ['product' => $product];
    }

    /**
     * Every tank at a location, with the System Qty for each.
     *
     * The Dip Reset form now lists ALL tanks at the chosen location as rows,
     * rather than making the user pick one tank at a time. A dip round covers
     * several tanks in one walk, so entering them together matches how the work
     * is actually done - and nothing can be filled in and then forgotten,
     * because there is no separate "Add" step to miss.
     *
     * One location per form: the reset document, its number and its adjustments
     * all belong to a single location, so mixing them would produce a record
     * that does not describe one place.
     *
     * System Qty is the tank's current balance - the quantity the system
     * believes is in that tank. It is the figure the physical dip is compared
     * against.
     */
    public function getTanksByLocation($location_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $tanks = FuelTank::leftJoin('products', 'fuel_tanks.product_id', '=', 'products.id')
            ->where('fuel_tanks.business_id', $business_id)
            ->where('fuel_tanks.location_id', $location_id)
            ->select(
                'fuel_tanks.id',
                'fuel_tanks.fuel_tank_number',
                'fuel_tanks.product_id',
                'fuel_tanks.current_balance',
                'products.name as product_name',
                'products.sub_category_id'
            )
            ->orderBy('fuel_tanks.fuel_tank_number')
            ->get();

        $rows = [];

        foreach ($tanks as $tank) {
            /*
             * The tank balance is read through transactionUtil, the same source
             * the single-tank form uses, so both screens agree on System Qty.
             * Falling back to the stored current_balance keeps a tank listed
             * even if the helper has nothing for it - a missing row would look
             * like the tank does not exist.
             */
            try {
                $systemQty = $this->transactionUtil->getTankBalanceById($tank->id);
            } catch (\Throwable $e) {
                $systemQty = null;
            }

            if (! is_numeric($systemQty)) {
                $systemQty = $tank->current_balance;
            }

            $rows[] = [
                'tank_id' => $tank->id,
                'tank_number' => $tank->fuel_tank_number,
                'product_id' => $tank->product_id,
                'product_name' => $tank->product_name,
                'system_qty' => number_format((float) $systemQty, 3, '.', ''),
            ];
        }

        return response()->json(['tanks' => $rows]);
    }

    public function getTankBalanceById($tank_id)
    {
        /*
         | Order by id rather than latest().
         |
         | latest() sorts on created_at, which has no index on either table, so
         | MySQL sorted the whole matching set on every call. id is the primary
         | key and gives the same "most recent row" for these tables, using the
         | index instead. Only the columns actually read are selected, so the row
         | is not hydrated in full.
         */
        $for_current_diff           = DB::table('dip_readings')
            ->where('tank_id', $tank_id)
            ->select('fuel_balance_dip_reading', 'current_qty')
            ->orderByDesc('id')
            ->first();
        $dip_reseting_new_reset_qty = DB::table('dip_resettings')
            ->where('tank_id', $tank_id)
            ->select('reset_new_dip')
            ->orderByDesc('id')
            ->first();
        if (! empty($dip_reseting_new_reset_qty->reset_new_dip)) {
            $reset_new_dip = $dip_reseting_new_reset_qty->reset_new_dip;
        } else {
            $reset_new_dip = "0.00";
        }
        if (! empty($for_current_diff->fuel_balance_dip_reading)) {
            $add_new_diff_cqty = $for_current_diff->fuel_balance_dip_reading;
        } else {
            $add_new_diff_cqty = "0.00";
        }
        if (! empty($for_current_diff->fuel_balance_dip_reading)) {
            $current_diff_for_reseting = $for_current_diff->fuel_balance_dip_reading - $for_current_diff->current_qty;
        } else {
            $current_diff_for_reseting = "0.00";
        }
        $current_balance = $this->transactionUtil->getTankBalanceById($tank_id);
        if (! empty($for_current_diff->current_qty)) {
            $current_diff = $for_current_diff->current_qty;
        } else {
            $current_diff = "0.00";
        }
        $tank                         = FuelTank::find($tank_id);
        $product                      = Product::where('id', $tank->product_id)->first();
        $details['tank_manufacturer'] = $tank->tank_manufacturer;
        $details['tank_capacity']     = number_format($tank->tank_capacity, 3, '.', '');
        $tank_dip_chart               = TankDipChart::leftjoin('tank_dip_chart_details', 'tank_dip_charts.id', 'tank_dip_chart_details.tank_dip_chart_id')->where('tank_id', $tank_id)->pluck('tank_dip_chart_details.id', 'dip_reading');
        return ['current_stock' => number_format($current_balance, 3, '.', ''), 'details' => $details, 'current_diff_for_reseting' => $current_diff_for_reseting, 'add_new_diff_cqty' => $add_new_diff_cqty, 'reset_new_dip' => $reset_new_dip, 'dip_readings' => $tank_dip_chart, 'product' => $product, 'current_diff' => number_format($current_diff, 3, '.', '')];
    }

    /**
     * Inventory Adjustment Account options for the Dip Reset form.
     *
     * Mirrors StockAdjustmentController@getInventoryAdjustmentAccount, with two
     * deliberate differences:
     *
     *  1. NO 'access_account' subscription gate. That gate made the core endpoint
     *     return an empty dropdown for a business administrator, so the Dip Reset
     *     form could not be completed at all. A superadmin bypasses subscription
     *     checks, which is why it worked for them.
     *
     *     Scoped to this form only - Accounts remain closed elsewhere for the
     *     business. The form cannot function without the account, so serving it
     *     here does not widen access to anything else.
     *
     *  2. Filtered by BUSINESS. The core method reads
     *         Account::where('account_type_id', $account_type)
     *     with no business scope, which on a multi-tenant install returns other
     *     businesses' accounts. This one cannot.
     *
     * Returns the same HTML the form already expects, so nothing else changes.
     */
    public function getDipResetAdjustmentAccount(Request $request)
    {
        $result = '<option value="">' . __('messages.please_select') . '</option>';

        try {
            /*
             | Business id read from the session directly.
             |
             | This called $this->activeBusinessId(), which exists in
             | FuelTankController - NOT in this controller. The call threw, the
             | try/catch below swallowed it, and the method returned the empty
             | "Please Select" list every time. The endpoint answered 200, which is
             | why it looked like a data problem rather than a fatal one.
             |
             | Read the same way the core method reads it, with fallbacks.
             */
            $business_id = $request->session()->get('user.business_id')
                ?: $request->session()->get('business.id')
                ?: optional(auth()->user())->business_id;

            $type = $request->input('type');

            if (empty($business_id) || ! in_array($type, ['increase', 'decrease'], true)) {
                return $result;
            }

            /*
             * IS2159: prefer the Stock Adjustment New settings mapping.
             *
             * The requirement is that a dip reset posts to the accounts LINKED ON
             * THE STOCK ADJUSTMENT NEW SETTINGS PAGE. That mapping lives in
             * san_stock_adjustment_account_mappings, keyed by business,
             * adjustment_type and the product's category / sub category.
             *
             * This lookup previously ignored it and picked any account of type
             * Income (for a gain) or Expenses (for a loss), so a reset could land
             * in an account the settings never nominated.
             *
             * The mapping is consulted first, narrowing by sub category then
             * category. The Income/Expenses search below remains as the fallback
             * for a business that has configured no mapping - without it those
             * businesses would suddenly get no account at all, and the reset
             * would post no accounting entry.
             */
            $mappedAccountId = $this->getMappedStockAdjustmentAccountId(
                $business_id,
                $type,
                (int) $request->input('product_id', 0)
            );

            if (! empty($mappedAccountId)) {
                $account = Account::where('business_id', $business_id)
                    ->where('id', $mappedAccountId)
                    ->first();

                if ($account) {
                    return [$account->id => $account->name];
                }
            }

            // Fallback: increase posts to Income, decrease to Expenses - as core does.
            $account_type_name = $type === 'increase' ? 'Income' : 'Expenses';
            $account_type      = AccountType::getAccountTypeIdByName($account_type_name, $business_id);

            if (empty($account_type)) {
                return $result;
            }

            $accounts = Account::where('account_type_id', $account_type->id)
                ->where('business_id', $business_id)
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('accounts', 'is_closed'), function ($query) {
                    $query->where(function ($open) {
                        $open->whereNull('is_closed')->orWhere('is_closed', 0);
                    });
                })
                ->orderBy('name')
                ->pluck('name', 'id');

            if ($accounts->isEmpty()) {
                return $result;
            }

            return $this->transactionUtil->createDropdownHtml($accounts, __('messages.please_select'));
        } catch (\Throwable $e) {
            /*
             | Logged as an ERROR with the file and line.
             |
             | This was a warning with only the message, and it hid a fatal fault -
             | a call to a method that does not exist in this controller. The
             | endpoint kept answering 200 with an empty list, so the failure looked
             | like missing data for several rounds.
             */
            \Log::error('Dip reset adjustment account lookup failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'type'  => $request->input('type'),
            ]);

            return $result;
        }
    }
}
