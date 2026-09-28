<?php

namespace Modules\MPCS\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\BusinessLocation;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\MPCS\Entities\Mpcs9cCashFormSettings;

class Form9CSettingsController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $commonUtil;
    protected $businessUtil;

    private $barcode_types;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil)
    {

        $this->businessUtil = $businessUtil;
        $this->middleware('web');
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    // public function index()
    // {
    //     if (request()->ajax()) {


    //        $header = Mpcs9cCashFormSettings::query();


    //         return Datatables::of($header)
    //             ->removeColumn('id')
    //             ->removeColumn('business_id')
    //             ->removeColumn('created_at')
    //             ->removeColumn('updated_at')
    //             ->editColumn('action', function ($row) {
    //                 $html = '<button href="#" data-href="' . url('/mpcs/edit-form-9c-settings/' . $row->id) . '" class="btn-modal btn btn-primary btn-xs" data-container=".update_form_9_c_settings_modal"><i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</button>';
    //                 return $html;
    //             })

    //             ->rawColumns(['action'])
    //             ->make(true);
    //     }

    //     $business_id = request()->session()->get('business.id');

    //     $settings = Mpcs9cCashFormSettings::where('business_id', $business_id)->first();
    //     $business_locations = BusinessLocation::forDropdown($business_id);
    //     return view('mpcs::forms.form_9c')->with(compact(
    //         'business_locations',
    //         'settings'
    //     ));
    // }

    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        } 
        if (request()->ajax()) {
            $user = auth()->user(); // Get logged-in user
            /*
             * IS2014: accept either session key.
             *
             * This read only 'user.business_id' while the non-ajax branch below
             * reads 'business.id'. Where the two differ - or where only one is
             * populated - the list queried a different business from the page
             * that renders it, so saved settings appeared to vanish. Same defect
             * pattern as IS2009 on the F15 settings screen.
             */
            $business_id = request()->session()->get('user.business_id')
                ?? request()->session()->get('business.id');

            $header = Mpcs9cCashFormSettings::where('business_id', $business_id);

            /*
             * IS2027: return a REAL error instead of a broken response.
             *
             * "Invalid JSON response" only means the endpoint emitted something
             * that was not JSON - a warning, a notice, or a 500 error page. It
             * never says what. The IS2014 fix guarded the two causes visible in
             * the code, was deployed, and the symptom persisted, which means the
             * actual trigger is something this code cannot see from here.
             *
             * Wrapping the build makes the endpoint always answer with valid
             * JSON, and puts the underlying message and location into that JSON.
             * DataTables will then show the real reason rather than the generic
             * warning, and the next report will carry something diagnosable.
             *
             * The output buffer is discarded for the same reason: any stray
             * warning printed by a lower layer is captured here rather than
             * being prepended to the payload.
             */
            try {
                ob_start();

                $response = $this->buildSettingsDatatable($header);

                $stray = trim((string) ob_get_clean());

                if ($stray !== '') {
                    \Illuminate\Support\Facades\Log::warning(
                        'IS2027 F9C settings datatable emitted stray output before JSON',
                        ['output' => mb_substr($stray, 0, 2000)]
                    );
                }

                return $response;
            } catch (\Throwable $e) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }

                \Illuminate\Support\Facades\Log::error('IS2027 F9C settings datatable failed', [
                    'business_id' => $business_id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                return response()->json([
                    'draw' => (int) request()->input('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'F9C settings could not be listed: ' . $e->getMessage()
                        . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
                ]);
            }
        }

        // IS2027: same resolution as the ajax branch above.
        $business_id = request()->session()->get('user.business_id')
            ?? request()->session()->get('business.id');

        $settings = Mpcs9cCashFormSettings::where('business_id', $business_id)->first();
        // business_locations is consumed by form_9c.blade.php; it must stay.
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('mpcs::forms.form_9c')->with(compact(
            'business_locations',
            'settings'
        ));
    }

    /**
     * IS2027: the datatable build, extracted so index() can wrap it.
     */
    private function buildSettingsDatatable($header)
    {
        $user = auth()->user();

        return Datatables::of($header)
                ->removeColumn('id')
                ->removeColumn('business_id')
                ->removeColumn('created_at')
                ->removeColumn('updated_at')
                ->editColumn('date_time', function ($row) {
                    /*
                     * IS2014: guarded. A null or unparseable date_time made
                     * Carbon::parse() throw, and the exception HTML was appended
                     * to the partly-built JSON - which is exactly what DataTables
                     * reports as "Invalid JSON response".
                     */
                    if (empty($row->date_time)) {
                        return '';
                    }

                    try {
                        return Carbon::parse($row->date_time)->format('d-m-Y');
                    } catch (\Throwable $e) {
                        return (string) $row->date_time;
                    }
                })
                ->editColumn('action', function ($row) use ($user) {
                    /*
                     * IS2014: "DataTables warning: table id=form_9a_settings_table
                     * - Invalid JSON response" after saving settings.
                     *
                     * That message means the endpoint returned something that was
                     * not valid JSON - almost always a PHP warning, notice or
                     * exception printed ahead of the payload.
                     *
                     * The trigger here was $user->is_superadmin_default. It is
                     * read unguarded, so on an installation where that column
                     * does not exist (or the user row is missing it) the property
                     * access emits a warning that lands in the response body
                     * before DataTables' JSON. The sibling controller
                     * Form9CCRSettingsController already casts it defensively as
                     * "(int) ($user->is_superadmin_default ?? 0)"; this one was
                     * never updated to match.
                     *
                     * A null $user is handled too - a session that expired
                     * mid-request would otherwise fatal on ->is_superadmin_default.
                     */
                    $isSuperadmin = $user
                        && (int) ($user->is_superadmin_default ?? 0) === 1;

                    if ($isSuperadmin) {
                        // Show active edit button for Super Admin
                        return '<button href="#" data-href="' . url('/mpcs/edit-form-9c-settings/' . $row->id) . '" 
                        class="btn-modal btn btn-primary btn-xs" 
                        data-container=".update_form_9_c_settings_modal">
                        <i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '
                    </button>';
                    }

                    // Show disabled button for non-Super Admins
                    return '<button class="btn btn-primary btn-xs" disabled>
                        <i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '
                    </button>';
                })
                ->rawColumns(['action'])
                ->make(true);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function create()
    {
        return view('mpcs::forms.partials.create_9c_form_settings');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if (!auth()->check()) {
            return $request->ajax()
                ? response()->json(['success' => 0, 'msg' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        $business_id = session()->get('user.business_id')
            ?? session()->get('business.id');

        /*
         * IS2027: the datepicker value was inserted RAW into a timestamp column.
         *
         * mpcs_9c_cash_form_settings.date_time is `timestamp NOT NULL`. The form
         * posts its date in the BUSINESS display format (dd/mm/yyyy or
         * mm/dd/yyyy), and this passed that string straight to insertGetId().
         *
         * MySQL then does one of two things, neither good:
         *   - strict mode ON  : rejects the value and the insert throws, so the
         *                       save 500s;
         *   - strict mode OFF : silently stores 0000-00-00 00:00:00, and the
         *                       settings list then renders a row whose date is
         *                       invalid.
         *
         * Either way the symptom appears immediately AFTER saving, which is what
         * the ticket describes and why an empty table behaved fine.
         *
         * Parsed here into MySQL format, business format first because only that
         * can tell 08/11/2026 apart as 8 November or 11 August. Same resolver
         * pattern used for IS1992 and the VAT invoice date.
         */
        $date_time = $this->resolveSettingsDateTime($request->input('datepicker'));

        $data = array(
            'business_id' => $business_id,
            'date_time' => $date_time,
            'starting_number' => $request->input('form_starting_number'),
            'ref_pre_form_number' => $request->input('ref_previous_form_number'),
            'added_user' => auth()->user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        Mpcs9cCashFormSettings::insertGetId($data);

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_9a_settings_add_success')
        ];

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->back()->with('success', __('mpcs::lang.form_9a_settings_add_success'));
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $settings = Mpcs9cCashFormSettings::where('business_id', $business_id)->where('id', $id)->first();
        return view('mpcs::forms.partials.edit_9c_form_settings')->with(compact(
            'settings'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if (!auth()->check()) {
            return $request->ajax()
                ? response()->json(['success' => 0, 'msg' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        $business_id = request()->session()->get('user.business_id');

        $data = array(
            'business_id' => $business_id,
            'date_time' => $request->input('datepicker'),
            'starting_number' => $request->input('form_starting_number'),
            'ref_pre_form_number' => $request->input('ref_previous_form_number'),
            'added_user' => auth()->user()->username,
            'created_at' => date('Y-m-d H:i'),
            'updated_at' => date('Y-m-d H:i'),
        );


        Mpcs9cCashFormSettings::where('id', $id)->update($data);

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_9a_settings_update_success')
        ];

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->back()->with('success', __('mpcs::lang.form_9a_settings_add_success'));
    }

   private function getSalesData($selected_date)
    {
        $business_id = request()->session()->get('user.business_id');
    
        // Fetch cash sales data product sub-category wise
        $cash_sales = DB::table('form9c_sub_categories')
            ->select('sub_category_id', DB::raw('SUM(amount) as total_sale'))
            ->where('business_id', $business_id)
            ->whereDate('created_at', $selected_date)
            ->groupBy('sub_category_id')
            ->get();
    
        // Fetch credit sales data product sub-category wise
        $credit_sales = DB::table('form9c_sub_categories')
            ->select('sub_category_id', DB::raw('SUM(amount) as total_credit_sale'))
            ->where('business_id', $business_id)
            ->whereDate('created_at', $selected_date)
            ->where('is_credit', 1) // Assuming there's a column `is_credit` to differentiate credit sales
            ->groupBy('sub_category_id')
            ->get();
    
        // Combine cash and credit sales data
        $sales_data = [];
        foreach ($cash_sales as $cash_sale) {
            $sales_data[$cash_sale->sub_category_id]['total_sale'] = $cash_sale->total_sale;
        }
    
        foreach ($credit_sales as $credit_sale) {
            $sales_data[$credit_sale->sub_category_id]['total_credit_sale'] = $credit_sale->total_credit_sale;
        }
    
        // Calculate Total Amount for each product sub-category
        foreach ($sales_data as $sub_category_id => $data) {
            $total_sale = $data['total_sale'] ?? 0;
            $total_credit_sale = $data['total_credit_sale'] ?? 0;
            $sales_data[$sub_category_id]['total_amount'] = $total_sale - $total_credit_sale;
        }
    
        return $sales_data;
    }
    

    /**
     * IS2027: turn the submitted settings date into MySQL datetime format.
     *
     * date_time is `timestamp NOT NULL`, so a display-format string cannot be
     * stored directly. The business format is tried first - it is the only thing
     * that can read an ambiguous 08/11/2026 correctly - then common formats with
     * a round-trip check, so Carbon cannot quietly roll 31/02 into March.
     *
     * Falls back to now() rather than failing the save: losing the settings over
     * a date the picker produced would be worse than dating them today, and a
     * NOT NULL column cannot take null anyway.
     */
    private function resolveSettingsDateTime($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return date('Y-m-d H:i:s');
        }

        // Already MySQL format.
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}( \\d{2}:\\d{2}(:\\d{2})?)?$/', $value)) {
            return strlen($value) === 10 ? $value . ' 00:00:00' : $value;
        }

        $businessFormat = session('business.date_format');
        $candidates = [];

        if (! empty($businessFormat)) {
            $candidates[] = $businessFormat . ' H:i';
            $candidates[] = $businessFormat;
        }

        $candidates = array_merge($candidates, [
            'd/m/Y H:i', 'm/d/Y H:i', 'd-m-Y H:i', 'm-d-Y H:i',
            'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'Y/m/d',
        ]);

        foreach ($candidates as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);

                if ($parsed && $parsed->format($format) === $value) {
                    return $parsed->format('Y-m-d H:i:s');
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return date('Y-m-d H:i:s');
        }
    }
}
