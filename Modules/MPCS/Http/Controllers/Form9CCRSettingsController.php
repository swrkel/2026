<?php

namespace Modules\MPCS\Http\Controllers;

use App\BusinessLocation;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MPCS\Entities\Mpcs9cCreditFormSettings;
use Yajra\DataTables\Facades\DataTables;

class Form9CCRSettingsController extends Controller
{
    protected $businessUtil;

    public function __construct(BusinessUtil $businessUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->middleware('web');
    }

    private function resolveBusinessId(Request $request): int
    {
        $businessId = $request->session()->get('user.business_id')
            ?? optional($request->user())->business_id
            ?? $request->session()->get('business.id');

        abort_if(empty($businessId), 403, 'Business context is missing.');

        return (int) $businessId;
    }

    public function index(Request $request)
    {
        abort_unless($request->user(), 401);

        $businessId = $this->resolveBusinessId($request);

        if ($request->ajax()) {
            $user = $request->user();
            $settings = Mpcs9cCreditFormSettings::query()
                ->where('business_id', $businessId)
                ->orderByDesc('id');

            return DataTables::of($settings)
                ->editColumn('action', function ($row) use ($user) {
                    if ((int) ($user->is_superadmin_default ?? 0) !== 1) {
                        return '<button type="button" class="btn btn-primary btn-xs" disabled>'
                            . '<i class="fa fa-edit" aria-hidden="true"></i> '
                            . e(__('messages.edit'))
                            . '</button>';
                    }

                    return '<button type="button" data-href="'
                        . e(url('/mpcs/edit-form-9ccr-settings/' . $row->id))
                        . '" class="btn-modal btn btn-primary btn-xs" '
                        . 'data-container=".update_form_9_ccr_settings_modal">'
                        . '<i class="fa fa-edit" aria-hidden="true"></i> '
                        . e(__('messages.edit'))
                        . '</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $settings = Mpcs9cCreditFormSettings::where('business_id', $businessId)->first();
        $businessLocations = BusinessLocation::forDropdown($businessId);

        return view('mpcs::forms.form_9ccr', [
            'business_locations' => $businessLocations,
            'settings' => $settings,
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $this->resolveBusinessId($request);
        $latestSetting = Mpcs9cCreditFormSettings::where('business_id', $businessId)
            ->orderByDesc('id')
            ->first();

        $form_9a_no = optional($latestSetting)->ref_pre_form_number;

        return view('mpcs::forms.partials.create_9ccr_form_settings', compact('form_9a_no'));
    }

    public function store(Request $request)
    {
        $businessId = $this->resolveBusinessId($request);
        $validated = $request->validate([
            'datepicker' => ['required', 'date'],
            'form_starting_number' => ['required'],
            'ref_previous_form_number' => ['required'],
        ]);

        Mpcs9cCreditFormSettings::create([
            'business_id' => $businessId,
            'date_time' => $validated['datepicker'],
            'starting_number' => $validated['form_starting_number'],
            'ref_pre_form_number' => $validated['ref_previous_form_number'],
            'added_user' => optional($request->user())->username,
        ]);

        $output = [
            'success' => true,
            'msg' => __('mpcs::lang.form_9a_settings_add_success'),
        ];

        return $request->expectsJson()
            ? response()->json($output)
            : redirect()->back()->with('success', $output['msg']);
    }

    public function edit(Request $request, $id)
    {
        $businessId = $this->resolveBusinessId($request);
        $settings = Mpcs9cCreditFormSettings::where('business_id', $businessId)
            ->where('id', $id)
            ->firstOrFail();

        return view('mpcs::forms.partials.edit_9ccr_form_settings', compact('settings'));
    }

    public function update(Request $request, $id)
    {
        $businessId = $this->resolveBusinessId($request);
        $validated = $request->validate([
            'datepicker' => ['required', 'date'],
            'form_starting_number' => ['required'],
            'ref_previous_form_number' => ['required'],
        ]);

        $settings = Mpcs9cCreditFormSettings::where('business_id', $businessId)
            ->where('id', $id)
            ->firstOrFail();

        $settings->update([
            'date_time' => $validated['datepicker'],
            'starting_number' => $validated['form_starting_number'],
            'ref_pre_form_number' => $validated['ref_previous_form_number'],
            'added_user' => optional($request->user())->username,
        ]);

        $output = [
            'success' => true,
            'msg' => __('mpcs::lang.form_9a_settings_update_success'),
        ];

        return $request->expectsJson()
            ? response()->json($output)
            : redirect()->back()->with('success', $output['msg']);
    }

    public function get9CCRForm(Request $request)
    {
        $businessId = $this->resolveBusinessId($request);
        $selectedDate = $request->input('selected_date');

        $creditSales = DB::table('f14_page')
            ->where('business_id', $businessId)
            ->whereDate('date', $selectedDate)
            ->select(
                'bill_no',
                'product_name',
                'qty',
                'page',
                'total_amount_rs',
                'total_amount_cents',
                'goods_rs',
                'goods_cents',
                'loading_rs',
                'loading_cents',
                'empty_rs',
                'empty_cents',
                'transport_rs',
                'transport_cents',
                'others_rs',
                'others_cents'
            )
            ->get();

        $thisDocumentTotal = $creditSales->sum(function ($sale) {
            return $sale->total_amount_rs + ($sale->total_amount_cents / 100);
        });
        $previousDayTotal = 0;

        return response()->json([
            'credit_sales' => $creditSales,
            'this_document_total' => $thisDocumentTotal,
            'previous_day_total' => $previousDayTotal,
            'total' => $thisDocumentTotal + $previousDayTotal,
        ]);
    }
}
