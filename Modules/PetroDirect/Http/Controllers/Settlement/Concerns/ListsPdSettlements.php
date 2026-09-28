<?php

namespace Modules\PetroDirect\Http\Controllers\Settlement\Concerns;

use Modules\PetroDirect\Support\PetroDirectDebug;
use Modules\PetroDirect\Support\SchemaCapabilityCache;
use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\Http\Controllers\ContactController;
use App\NotificationTemplate;
use App\Product;
use App\Store;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use Modules\HR\Entities\WorkShift;
use Modules\PetroDirect\Entities\CustomerPayment;
use Modules\PetroDirect\Entities\CustomerBillVatPrefix;
use Modules\PetroDirect\Entities\DailyCard;
use Modules\PetroDirect\Entities\DailyCollection;
use Modules\PetroDirect\Entities\DailyVoucher;
use Modules\PetroDirect\Entities\DayEnd;
use Modules\PetroDirect\Entities\FuelTank;
use Modules\PetroDirect\Entities\MeterSale;
use Modules\PetroDirect\Entities\OtherIncome;
use Modules\PetroDirect\Entities\OtherSale;
use Modules\PetroDirect\Entities\PetroShift;
use Modules\PetroDirect\Entities\PetroWhatsAppTemplate;
use Modules\PetroDirect\Entities\Pump;
use Modules\PetroDirect\Entities\PumperDayEntry;
use Modules\PetroDirect\Entities\PumpOperator;
use Modules\PetroDirect\Entities\PumpOperatorAssignment;
use Modules\PetroDirect\Entities\PumpOperatorCommission;
use Modules\PetroDirect\Entities\PumpOperatorPayment;
use Modules\PetroDirect\Entities\PumpOperatorOtherSale;
use Modules\PetroDirect\Entities\Settlement;
use Modules\PetroDirect\Entities\SettlementCardPayment;
use Modules\PetroDirect\Entities\SettlementCashDeposit;
use Modules\PetroDirect\Entities\SettlementCashPayment;
use Modules\PetroDirect\Entities\SettlementChequePayment;
use Modules\PetroDirect\Entities\SettlementCreditSalePayment;
use Modules\PetroDirect\Entities\SettlementEditHistory;
use Modules\PetroDirect\Entities\SettlementExcessPayment;
use Modules\PetroDirect\Entities\PumpOperatorMeterSale;
use Modules\PetroDirect\Entities\SettlementExpensePayment;
use Modules\PetroDirect\Entities\SettlementShortagePayment;
use Modules\PetroDirect\Entities\SettlementLoanPayment;
use Modules\PetroDirect\Entities\SettlementDrawingPayment;
use Modules\PetroDirect\Entities\SettlementCustomerLoan;
use Modules\PetroDirect\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\PetroDirect\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Listing, viewing and printing settlements.
 *
 * MA-002: split out of PetroDirect's SettlementController, which was 10,590
 * lines in a single file.
 *
 * The grouping follows the one used for the PD settlement controllers, since
 * these files share most of their method names - but it was rebuilt against
 * THIS file, because the modules have genuinely diverged in content.
 *
 * Traits, not separate controllers: routes, action() targets and the $this->
 * calls between these methods all resolve exactly as before. Method bodies
 * are byte-identical to the original.
 *
 * Methods here: index, show, getUserActivityReport, extractLastInteger, getDirectSettlementShiftPrefix, getNextDirectSettlementShiftLabel, getAvailableDirectSettlementPumps, print, checkSlipNo
 */
trait ListsPdSettlements
{
    public function index()
    {

        $business_id = request()

            ->session()

            ->get('user.business_id');

        if (! $this->hasPetroDirectAccess('petrodirect.settlements.view')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {

            $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);

            if (request()->ajax()) {

                $query = Settlement::leftJoin(

                    'business_locations',

                    'settlements.location_id',

                    '=',

                    'business_locations.id'

                )

                    ->leftJoin(

                        'pump_operators',

                        'settlements.pump_operator_id',

                        '=',

                        'pump_operators.id'

                    )

                    ->where('settlements.business_id', $business_id)
                    ->where(function ($numberQuery) use ($business_id) { $this->scopeDirectSettlementNumberSeries($numberQuery, (int) $business_id, 'settlements.settlement_no'); })
                    ->where('settlements.settlement_no', 'NOT LIKE', 'SET-SW%')
                    ->where('settlements.settlement_no', 'NOT LIKE', 'PDST%')
                    ->where(function ($query) {
                        $query->where('settlements.status', 0)
                            ->orWhere('settlements.total_amount', '>', 0);

                        foreach ([
                            'meter_sales',
                            'other_sales',
                            'other_incomes',
                            'customer_payments',
                            'settlement_cash_payments',
                            'settlement_card_payments',
                            'settlement_cheque_payments',
                            'settlement_cash_deposits',
                            'settlement_expense_payments',
                            'settlement_shortage_payments',
                            'settlement_excess_payments',
                            'settlement_loan_payments',
                            'settlement_drawing_payments',
                            'settlement_customer_loans',
                        ] as $detail_table) {
                            if (
                                ! SchemaCapabilityCache::hasTable($detail_table)
                                || ! SchemaCapabilityCache::hasColumn($detail_table, 'settlement_no')
                            ) {
                                continue;
                            }

                            $query->orWhereExists(function ($sub) use ($detail_table) {
                                $sub->select(DB::raw(1))
                                    ->from($detail_table)
                                    ->where(function ($detail_query) use ($detail_table) {
                                        $detail_query->whereColumn($detail_table . '.settlement_no', 'settlements.id')
                                            ->orWhereColumn($detail_table . '.settlement_no', 'settlements.settlement_no');
                                    });
                            });
                        }

                        if (
                            SchemaCapabilityCache::hasTable('settlement_credit_sale_payments')
                            && SchemaCapabilityCache::hasColumn('settlement_credit_sale_payments', 'settlement_no')
                        ) {
                            $query->orWhereExists(function ($sub) {
                                $sub->select(DB::raw(1))
                                    ->from('settlement_credit_sale_payments')
                                    ->where(function ($credit_query) {
                                        $credit_query->whereColumn('settlement_credit_sale_payments.settlement_no', 'settlements.settlement_no')
                                            ->orWhereColumn('settlement_credit_sale_payments.settlement_no', 'settlements.id');
                                    });
                            });
                        }
                    })

                    ->select([

                        'pump_operators.name as pump_operator_name',

                        'business_locations.name as location_name',

                        'settlements.*',

                    ])

                    ->with(['meter_sales', 'other_sales', 'other_incomes', 'customer_payments']);

                $this->excludePetroPdModuleSettlements($query, $business_id);
                $this->excludeSettlementsWithPetroPdSettledShifts($query, $business_id);
                \Modules\PetroDirect\Support\PetroDirectIsolation::excludeSettlements($query);

                if (! empty(request()->location_id)) {

                    $query->where(

                        'settlements.location_id',

                        request()->location_id

                    );

                }

                if (! empty(request()->pump_operator)) {

                    $query->where(

                        'settlements.pump_operator_id',

                        request()->pump_operator

                    );

                }

                if (! empty(request()->settlement_no)) {

                    $query->where('settlements.id', request()->settlement_no);

                }

                if (

                    ! empty(request()->start_date) &&

                    ! empty(request()->end_date)

                ) {

                    $query->whereDate(

                        'settlements.transaction_date',

                        '>=',

                        request()->start_date

                    );

                    $query->whereDate(

                        'settlements.transaction_date',

                        '<=',

                        request()->end_date

                    );

                }

                $query->groupBy('settlements.id');

                $query->orderBy('settlements.id', 'desc');

                $first = null;

                $first = Settlement::where('business_id', $business_id)
                    ->where(function ($numberQuery) use ($business_id) { $this->scopeDirectSettlementNumberSeries($numberQuery, (int) $business_id); })
                    ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                    ->where('settlement_no', 'NOT LIKE', 'PDST%');

                $this->excludePetroPdModuleSettlements($first, $business_id, 'settlement_no');
                $this->excludeSettlementsWithPetroPdSettledShifts($first, $business_id);
                \Modules\PetroDirect\Support\PetroDirectIsolation::excludeSettlements($first);

                $first = $first
                    ->where('status', 0)

                    ->orderBy('id', 'desc')

                    ->first();

                $delete_settlement = $this->moduleUtil->hasThePermissionInSubscription(

                    $business_id,

                    'delete_settlement'

                );

                $edit_settlement = $this->moduleUtil->hasThePermissionInSubscription(

                    $business_id,

                    'edit_settlement'

                );

                $edit_settlement_no_change = $this->moduleUtil->hasThePermissionInSubscription(

                    $business_id,

                    'edit_settlement_no_change'

                );

                // PetroDirect-owned records use PetroDirect permissions. Keep the
                // legacy flags only as a compatibility fallback for older roles.
                $can_edit_direct_settlement = $this->canEditDirectSettlements();


                $pumpOtherSaleTotals = [];

                $settlements = Datatables::of($query)

                    ->addColumn(

                        'action',

                        function ($row) use ($first, $delete_settlement, $edit_settlement, $edit_settlement_no_change, $can_edit_direct_settlement) {

                            $html = '';

                            if ($row->status == 1) {

                                if (

                                    Str::startsWith(

                                        $row->settlement_no,

                                        'SET-SW'

                                    )

                                ) {

                                    $html .=

                                        '<a class="btn btn-danger btn-sm" href="'.

                                        route('petrodirect.settlement.create', ['view_settlement_id' => $row->id]).

                                        '">'.

                                        __('petrodirect::lang.finish_settlement').

                                        '</a>';

                                } else {

                                    $html .=

                                        '<a class="btn  btn-danger btn-sm" href="'.

                                        action(

                                            "\Modules\PetroDirect\Http\Controllers\SettlementController@create"

                                        ) . '?view_settlement_id=' . $row->id .

                                        '">'.

                                        __('petrodirect::lang.finish_settlement').

                                        '</a>';

                                }

                            } elseif ($row->is_edit == 1) {

                                $html .=

                                    '<a class="btn  btn-warning btn-sm" href="'.

                                    route('petrodirect.settlement.edit', ['settlement' => $row->id]).

                                    '">'.

                                    __('petrodirect::lang.finish_editting').

                                    '</a>';

                            } else {

                                $html .=

                                    '<div class="btn-group">







                                <button type="button" class="btn btn-info dropdown-toggle btn-xs"







                                    data-toggle="dropdown" aria-expanded="false">'.

                                    __('messages.actions').

                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown



                                    </span>



                                </button>







                                <ul class="dropdown-menu dropdown-menu-left direct-settlement-action-menu" role="menu">';

                                $view_url = action(
                                    "\Modules\PetroDirect\Http\Controllers\SettlementController@show",
                                    [$row->id]
                                );

                                $html .=
                                    '<li><a href="' . $view_url . '" data-href="' . $view_url .
                                    '" class="btn-modal view_settlement_button" data-container=".settlement_modal"><i class="fa fa-eye" aria-hidden="true"></i> ' .
                                    __('messages.view') .
                                    '</a></li>';

                                // Keep Print permanently visible in the Direct Settlement Action menu.
                                // A real href is required because some global dropdown styles hide
                                // anchors that only contain data-href.
                                $print_url = route('petrodirect.settlement.print', ['id' => $row->id]);
                                $html .=
                                    '<li class="direct-settlement-print-item"><a href="' . $print_url .
                                    '" data-href="' . $print_url .
                                    '" class="print_settlement_button" role="menuitem"><i class="fa fa-print" aria-hidden="true"></i> ' .
                                    __('petrodirect::lang.print') .
                                    '</a></li>';

                                if ($can_edit_direct_settlement) {

                                    $html .=

                                        '<li><a href="'.

                                        route('petrodirect.settlement.edit', ['settlement' => $row->id]).

                                        '" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> '.

                                        __('messages.edit').

                                        '</a></li>';

                                }

                                if ($can_edit_direct_settlement) {

                                    $html .=

                                        '<li><a href="'.

                                        route('petrodirect.settlement.edit', [
                                            'settlement' => $row->id,
                                            'no_change' => 1,
                                        ]).

                                        '" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> '.

                                        __('petrodirect::lang.edit_no_change').

                                        '</a></li>';

                                }

                                if (

                                    $this->moduleUtil->hasThePermissionInSubscription(

                                        request()

                                            ->session()

                                            ->get('user.business_id'),

                                        'individual_sale'

                                    )

                                ) {

                                    $settlement = DB::table('transactions')

                                        ->where(

                                            'invoice_no',

                                            $row->settlement_no

                                        )

                                        ->where('type', 'sell')

                                        ->first();

                                    if (! empty($settlement)) {

                                        if (

                                            strtotime(

                                                $this->transactionUtil->__getVatEffectiveDate(

                                                    request()

                                                        ->session()

                                                        ->get(

                                                            'user.business_id'

                                                        )

                                                )

                                            ) <=

                                            strtotime($row->transaction_date)

                                        ) {

                                            $html .=

                                                '<li><a href="#" data-href="'.

                                                action(

                                                    "\Modules\Vat\Http\Controllers\VatController@updateSingleVats",

                                                    [

                                                        'transaction_id' => $settlement->id,

                                                    ]

                                                ).

                                                '" class="regenerate-vat"><i class="fa fa-pencil"></i> '.

                                                __(

                                                    'superadmin::lang.regenerate_vat'

                                                ).

                                                '</a></li>';

                                        }

                                    }

                                }

                                if (

                                    ! empty($first) &&

                                    $first->id == $row->id &&

                                    $delete_settlement &&

                                    auth()

                                        ->user()

                                        ->can('settlement.delete')

                                ) {

                                    // commented By M Usman for hiding Delete Action

                                    $html .=

                                        '<li><a href="'.

                                        action(

                                            "\Modules\PetroDirect\Http\Controllers\SettlementController@destroy",

                                            [$row->id]

                                        ).

                                        '" class="delete_settlement_button"><i class="fa fa-trash"></i> '.

                                        __('messages.delete').

                                        '</a></li>';

                                }

                                $mechanical_meter_url = route('petrodirect.settlement.mechanical-meter', [$row->id]);

                                $html .=
                                    '<li><a href="'.
                                    $mechanical_meter_url.
                                    '" data-href="'.
                                    $mechanical_meter_url.
                                    '" class="mechanical-meter-settlement-button"><i class="fa fa-tachometer"></i> Mechanical Meter</a></li>';

                                $html .= '</ul></div>';

                            }

                            return $html;

                        }

                    )

                    ->editColumn('status', function ($row) {

                        if ($row->status == 0) {

                            return '<span class="label label-success">Completed</span>';

                        } else {

                            return '<span class="label label-danger">Pending</span>';

                        }

                    })

                    // ->addColumn('pump_nos', function($row){

                    //     $pump_nos = '';

                    //     if(!empty($row->meter_sales())){

                    //         $_pump_nos = $row->meter_sales->pluck('pump_id')->toArray() ?? [];

                    //         $_pumps = Pump::whereIn('id',$_pump_nos)->pluck('pump_no')->toArray();

                    //         $pump_nos = implode(', ',$_pumps);

                    //     }

                    //     return $pump_nos;

                    // })

                    ->addColumn('shift_number', function ($row) use ($business_id) {
                        // Direct settlements store their own DST sequence in work_shift.
                        // Always expose a concrete shift_number key to DataTables so the
                        // List Direct Settlement page does not raise an unknown-parameter
                        // warning. The assignment lookup is only a legacy-data fallback.
                        $direct_shift_number = $this->normalizeDirectSettlementShiftLabel(
                            $row->work_shift,
                            $business_id
                        );

                        if (! empty($direct_shift_number)) {
                            return $direct_shift_number;
                        }

                        return PumpOperatorAssignment::where('business_id', $business_id)
                            ->where('settlement_id', $row->id)
                            ->when(! empty($row->pump_operator_id), function ($query) use ($row) {
                                $query->where('pump_operator_id', $row->pump_operator_id);
                            })
                            ->whereNotNull('shift_number')
                            ->pluck('shift_number')
                            ->filter()
                            ->unique()
                            ->implode(', ');
                    })

                    ->addColumn('pump_nos', function ($row) {

                        $pump_nos = '';

                        if (

                            ! empty($row->meter_sales) &&

                            $row->meter_sales->count() > 0

                        ) {

                            $_pump_nos = $row->meter_sales

                                ->pluck('pump_id')

                                ->toArray();

                            $_pumps = Pump::whereIn('id', $_pump_nos)

                                ->pluck('pump_no')

                                ->toArray();

                            $pump_nos = implode(', ', $_pumps);

                        }

                        return $pump_nos;

                    })

                    // ->editColumn('shift', function ($row) {

                    //     if (!empty($row->work_shift)) {

                    //         $shifts = WorkShift::whereIn('id', $row->work_shift)->pluck('shift_name')->toArray();

                    //         return implode(',', $shifts);

                    //     } else {

                    //         return '';

                    //     }

                    // })

                    ->editColumn('shift', function ($row) {

                        if (! empty($row->work_shift) && is_array($row->work_shift)) {
                            $directShiftLabel = $this->normalizeDirectSettlementShiftLabel(
                                $row->work_shift,
                                (int) request()->session()->get('user.business_id')
                            );

                            if (! empty($directShiftLabel)) {
                                return $directShiftLabel;
                            }

                            $shiftIds = array_filter($row->work_shift, function ($shift) {
                                return is_numeric($shift) && (int) $shift > 0;
                            });

                            if (! empty($shiftIds)) {
                                $shifts = WorkShift::whereIn('id', $shiftIds)

                                    ->pluck('shift_name')

                                    ->toArray();

                                return implode(',', $shifts);
                            }
                        }

                        return '';

                    })

                    ->addColumn('created_by', function ($row) {

                        $transaction = Transaction::where(

                            'invoice_no',

                            $row->settlement_no

                        )

                            ->leftJoin(

                                'users',

                                'users.id',

                                'transactions.created_by'

                            )

                            ->select('users.username')

                            ->first();

                        if (! empty($transaction)) {

                            return $transaction->username;

                        }

                    })

                    ->editColumn('transaction_date', function ($row) {
                        $selectedDate = $row->transaction_date ?: $row->created_at;

                        return ! empty($selectedDate)
                            ? $this->transactionUtil->format_date($selectedDate)
                            : '';
                    })

                    ->editColumn('note', function ($row) {
                        $note = trim((string) ($row->note ?? ''));

                        if ($note === '') {
                            return '<span class="text-muted direct-settlement-no-note">&mdash;</span>';
                        }

                        $escapedNote = htmlspecialchars($note, ENT_QUOTES, 'UTF-8');

                        return '<button type="button" class="btn btn-xs btn-info direct-settlement-note-btn"'
                            . ' data-note="' . $escapedNote . '"'
                            . ' title="' . $escapedNote . '"'
                            . ' aria-label="' . htmlspecialchars(__('petrodirect::lang.view_note'), ENT_QUOTES, 'UTF-8') . '">'
                            . '<i class="fa fa-sticky-note"></i> ' . htmlspecialchars(__('petrodirect::lang.note'), ENT_QUOTES, 'UTF-8')
                            . '</button>'
                            // Keep the actual note text available to DataTables export/assistive text.
                            . '<span class="direct-settlement-note-export-text">' . $escapedNote . '</span>';
                    })

                    // ->editColumn('total_amount', '{{@num_format($total_amount)}}')

                    ->editColumn('total_amount', function ($row) use (&$pumpOtherSaleTotals) {

                        $meter_total = $row->meter_sales->sum('discount_amount');
                        $other_sales_total = $row->other_sales->sum('sub_total');
                        $other_income_total = $row->other_incomes->sum('sub_total');
                        $customer_payment_total = $row->customer_payments->sum('sub_total');

                        $other_sales_discount = $row->other_sales->sum(function ($sale) {
                            return $sale->discount_amount ?? 0;
                        });

                        if (! array_key_exists($row->id, $pumpOtherSaleTotals)) {
                            $pumpOtherSaleTotals[$row->id] = $this->getPumpOperatorOtherSaleTotalForSettlement($row);
                        }
                        $pump_other_sale_total = $pumpOtherSaleTotals[$row->id];

                        $calculated_total = $meter_total +
                            $other_sales_total +
                            $other_income_total +
                            $customer_payment_total +
                            $pump_other_sale_total;

                        if ($calculated_total <= 0 && ! empty($row->total_amount)) {
                            $calculated_total = (float) $row->total_amount;
                        }

                        $adjusted_total = max($calculated_total - $other_sales_discount, 0);

                        return '<span class="total_amount">'.
                            number_format($adjusted_total, 2, '.', ',').
                            '</span>';
                    })

                    ->setRowAttr([

                        'data-href' => function ($row) {

                            return action(

                                "\Modules\PetroDirect\Http\Controllers\SettlementController@show",

                                [$row->id]

                            );

                        },

                    ])

                    ->removeColumn('id');

                return $settlements

                    ->rawColumns(['action', 'status', 'note', 'total_amount'])

                    ->make(true);

            }

        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $pending_pump_operator_ids = $this->getDirectSettlementHiddenPendingPumpOperatorIds($business_id);

        $pump_operators = $this->getDirectSettlementPumpOperators($business_id)
            ->when(! empty($pending_pump_operator_ids), function ($query) use ($pending_pump_operator_ids) {
                $query->whereNotIn('id', $pending_pump_operator_ids);
            })
            ->orderBy('name')
            ->pluck('name', 'id');

        $settlement_nos = Settlement::where('business_id', $business_id)
            ->where(function ($numberQuery) use ($business_id) { $this->scopeDirectSettlementNumberSeries($numberQuery, (int) $business_id); })
            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlement_no', 'NOT LIKE', 'PDST%');

        $this->excludePetroPdModuleSettlements($settlement_nos, $business_id, 'settlement_no');
        $this->excludeSettlementsWithPetroPdSettledShifts($settlement_nos, $business_id);
        \Modules\PetroDirect\Support\PetroDirectIsolation::excludeSettlements($settlement_nos);

        $settlement_nos = $settlement_nos
            ->pluck(
                'settlement_no',
                'id'
            );

        $message = $this->transactionUtil->getGeneralMessage(

            'general_message_pump_management_checkbox'

        );

        return view('petrodirect::settlement.index')->with(

            compact(

                'business_locations',

                'pump_operators',

                'settlement_nos',

                'message'

            )

        );

    }

    public function show($id)
    {

        $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);

        if ($business_id <= 0) {
            abort(403, 'Unable to resolve the active business.');
        }

        abort_unless(
            $this->isHistoricalDirectSettlementRecord((int) $id, $business_id),
            404
        );

        $settlement = Settlement::where('settlements.id', $id)

            ->where('settlements.business_id', $business_id)
            ->where(function ($numberQuery) use ($business_id) { $this->scopeDirectSettlementNumberSeries($numberQuery, (int) $business_id, 'settlements.settlement_no'); })
            ->where('settlements.settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlements.settlement_no', 'NOT LIKE', 'PDST%')

            ->leftjoin(

                'pump_operators',

                'settlements.pump_operator_id',

                'pump_operators.id'

            )

            ->with([

                'meter_sales',

                'other_sales',

                'other_incomes',

                'customer_payments',

                'cash_payments',

                'cash_deposits',

                'card_payments',

                'cheque_payments',

                'credit_sale_payments',

                'expense_payments',

                'excess_payments',

                'shortage_payments',

                'loan_payments',

                'drawings_payments',

                'customer_loans',

            ])

            ->select(

                'settlements.*',

                'pump_operators.name as pump_operator_name'

            )

            ->first();

        if (empty($settlement)) {
            abort(404);
        }

        // Saved Direct view uses the same authoritative Meter Sale set as
        // Preview/finalisation; never re-expand from the shared legacy table.
        app(\Modules\PetroDirect\Services\DirectSettlementMeterSaleScopeService::class)
            ->apply($settlement, (int) $business_id);

        // Reload cash payments with fallback for mixed settlement_no storage (id vs settlement_no string)
        $cash_payments = \Modules\PetroDirect\Entities\SettlementCashPayment::where('business_id', $business_id)
            ->where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)
                    ->orWhere('settlement_no', $settlement->settlement_no);
            })
            ->get();

        $settlement->setRelation('cash_payments', $cash_payments);

        // Manually load cash deposits if relationship didn't load them (fallback for Settlement SW)
        if ($settlement && $settlement->cash_deposits->isEmpty()) {
            $cash_deposits = \Modules\PetroDirect\Entities\SettlementCashDeposit::where('business_id', $business_id)
                ->where(function ($query) use ($settlement) {
                    $query->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('cash_deposits', $cash_deposits);
        }

        // Fix: Fetch Cash Deposits from Accounting Module (AccountTransaction)
        // because they are not stored in settlement_cash_deposits table
        if ($settlement) {
            // Match deposits by settlement_no in the note field (e.g., "Settlement No: SET-SW1")
            // OR by date as fallback (deposits created on same date as settlement)
            $settlement_identifier = $settlement->settlement_no;
            
            $account_transactions = \App\AccountTransaction::where('business_id', $business_id)
                ->where('sub_type', 'deposit')
                ->where(function ($query) use ($settlement_identifier, $settlement) {
                    // PRIMARY: Match by settlement_no in note field
                    $query->where(function ($q) use ($settlement_identifier, $settlement) {
                        $q->where('note', 'like', '%' . $settlement_identifier . '%')
                          ->orWhere('note', 'like', '%Settlement No: ' . $settlement->id . '%')
                          ->orWhere('note', 'like', '%settlement #' . $settlement_identifier . '%');
                    })
                    // FALLBACK: Match by date if note doesn't contain settlement number
                    ->orWhereDate('operation_date', $settlement->transaction_date);
                })
                ->get();
            
            // Transform AccountTransactions to match SettlementCashDeposit structure if needed,
            // or just merge them into the collection since we mostly need 'amount'.
            if ($account_transactions->isNotEmpty()) {
                // We create new instances or just merge. Since the view uses ->sum('amount'), 
                // merging the AccountTransaction objects directly is safe as they have an 'amount' field.
                $current_deposits = $settlement->cash_deposits;
                $merged_deposits = $current_deposits->merge($account_transactions);
                $settlement->setRelation('cash_deposits', $merged_deposits);
            }
        }

        // Reload credit_sale_payments with fallback logic (similar to SettlementPDController)
        // This handles cases where credit sales are saved with settlement ID instead of settlement_no string
        if ($settlement) {
            $other_sales = OtherSale::where('business_id', $business_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no);
                })
                ->get();
            $settlement->setRelation('other_sales', $other_sales);

            // Always reload to ensure we have all records (handles both string and integer settlement_no)
            // For direct settlement, do NOT filter by pump_operator_id so all credit sales linked to this
            // settlement are included (matches behavior already fixed for Settlement PD).
            $credit_sales = SettlementCreditSalePayment::where('business_id', $business_id)
                ->petroDirectOwned()
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->with('product')
                ->get();
            $settlement->setRelation('credit_sale_payments', $credit_sales);
        }

        // Reload excess_payments with fallback logic (handles both string and integer settlement_no)
        if ($settlement) {
            $excess_payments = SettlementExcessPayment::where('business_id', $business_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('excess_payments', $excess_payments);
        }

        // Reload shortage_payments with fallback logic (handles both string and integer settlement_no)
        if ($settlement) {
            $shortage_payments = SettlementShortagePayment::where('business_id', $business_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('shortage_payments', $shortage_payments);
        }

        $business = Business::where(

            'id',

            ! empty($settlement) ? $settlement->business_id : 0

        )->first();

        $pump_operator = PumpOperator::where(

            'id',

            ! empty($settlement) ? $settlement->pump_operator_id : 0

        )->first();

        // this for only to show in print page customer payments which entered in customer payments tab

        $customer_payments_tab = CustomerPayment::leftjoin(

            'contacts',

            'customer_payments.customer_id',

            'contacts.id'

        )

            ->where(function ($query) use ($settlement) {
                $query->where('customer_payments.settlement_no', $settlement->id)
                    ->orWhere('customer_payments.settlement_no', $settlement->settlement_no);
            })

            ->where('customer_payments.business_id', $business_id)

            ->select('customer_payments.*', 'contacts.name as customer_name')

            ->get();

        $total_daily_collection = floatval(

            DailyCollection::where(

                'pump_operator_id',

                $settlement->pump_operator_id

            )

                ->where('business_id', $business->id)

                ->where('settlement_id', $settlement->id)

                ->where('type', 'daily_collection')

                ->sum('current_amount')

        );

        return view('petrodirect::settlement.show')->with(

            compact(

                'settlement',

                'business',

                'pump_operator',

                'customer_payments_tab',

                'total_daily_collection'

            )

        );

    }

    /**
     * Show the form for editing the specified resource.







     * @return Response
     */

    public function getUserActivityReport(Request $request)
    {

        $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);

        if (request()->ajax()) {

            $with = [];

            $shipping_statuses = $this->transactionUtil->shipping_statuses();

            $business_users = User::where('business_id', $business_id)

                ->pluck('id')

                ->toArray();

            $activity = Activity::whereIn(

                'causer_id',

                $business_users

            )->whereIn('subject_type', $this->transactionUtil->petro_classes);

            if (! empty(request()->user) && request()->user != 'All') {

                $user = request()->user;

                $activity->where('causer_id', $user);

            }

            if (! empty(request()->type) && request()->type != 'All') {

                $type = request()->type;

                $activity->where('description', $type);

            }

            if (! empty(request()->subject) && request()->subject != 'All') {

                $subject = request()->subject;

                $activity->where('log_name', $subject);

            }

            if (! empty(request()->startDate) && ! empty(request()->endDate)) {

                $activity->whereDate('created_at', '>=', request()->startDate);

                $activity->whereDate('created_at', '<=', request()->endDate);

            }

            $datatable = Datatables::of($activity)

                ->editColumn(

                    'created_at',

                    '{{ @format_datetime($created_at) }}'

                )

                ->removeColumn('id')

                ->editColumn('causer_id', function ($row) {

                    $causer_id = $row->causer_id;

                    $username = User::where('id', $causer_id)

                        ->select('username')

                        ->first()->username;

                    return $username;

                })

                ->addColumn('ref_no', function ($row) {

                    $attributes = json_decode($row->properties, true);

                    $new = $attributes['attributes'] ?? [];

                    $html = '';

                    if ($row->subject_type == "App\TransactionPayment") {

                        if (! empty($new['payment_ref_no'])) {

                            $html .= $new['payment_ref_no'];

                        }

                    } else {

                        if (! empty($new['invoice_no'])) {

                            $html .= $new['invoice_no'];

                        } else {

                            if (

                                $row->subject_type ==

                                "Modules\PetroDirect\Entities\Settlement"

                            ) {

                                $html .= $new['settlement_no'];

                            }

                        }

                    }

                    return $html;

                })

                ->addColumn('description_details', function ($row) {

                    $attributes = json_decode($row->properties, true);

                    $new = $attributes['attributes'] ?? [];

                    $old = $attributes['old'] ?? [];

                    $html = '';

                    if ($row->description == 'updated') {

                        foreach ($new as $key => $newValue) {

                            if (

                                $key != 'created_at' &&

                                $key != 'updated_at' &&

                                $key != 'id'

                            ) {

                                $oldValue = $old[$key] ?? null;

                                if (

                                    $key == 'payment_method' &&

                                    $oldValue == 'Cash'

                                ) {

                                    $oldValue = 'Cash ';

                                }

                                if ($newValue !== $oldValue) {

                                    $originalKey = str_replace(

                                        '_',

                                        ' ',

                                        ucfirst($key)

                                    );

                                    $html .= "Original $originalKey $oldValue changed to $newValue <br>";

                                }

                            }

                        }

                    } elseif ($row->description == 'deleted') {

                        if ($row->subject_type == "App\TransactionPayment") {

                            $contact = Contact::find($new['payment_for']);

                            if (! empty($contact)) {

                                $html .=

                                    'Contact Name: '.$contact->name.'<br>';

                            }

                            if (! empty($new['amount'])) {

                                $html .=

                                    'Amount: '.

                                    $this->productUtil->num_f($new['amount']).

                                    '<br>';

                            }

                            if (! empty($new['payment_ref_no'])) {

                                $html .=

                                    'Ref No: '.

                                    $new['payment_ref_no'].

                                    '<br>';

                            }

                        } else {

                            return '';

                        }

                    } elseif (

                        $row->description == 'update' &&

                        $row->log_name == 'Settlement'

                    ) {

                        $jsonProperties = $row->properties;

                        $decodedProperties = json_decode($jsonProperties);

                        $text = $decodedProperties[0];

                        $html .= $text;

                        // $html .= $row->properties;

                    } elseif (

                        ($row->description == 'update' ||

                            $row->description == 'delete') &&

                        ($row->log_name == 'Day End Settlement' ||

                            $row->log_name == 'Dip Chart' ||

                            $row->log_name == 'Dip Report')

                    ) {

                        $jsonProperties = $row->properties;

                        $decodedProperties = json_decode($jsonProperties);

                        $text = $decodedProperties[0];

                        $html .= nl2br($text);

                        // $html .= $row->properties;

                    } else {

                        $html = '';

                    }

                    return nl2br($html);

                });

            $rawColumns = ['description_details'];

            return $datatable

                ->rawColumns($rawColumns)

                ->make(true);

        }

        $users = User::where('business_id', $business_id)->pluck(

            'username',

            'id'

        );

        $type = Activity::distinct()->pluck('description');

        $subject = Activity::distinct()->pluck('log_name');

        return view('petrodirect::report.user_activity')->with(

            compact('users', 'type', 'subject')

        );

    }

    public function extractLastInteger($text)
    {
        if (is_array($text)) {
            $text = implode(' ', array_filter(array_map(function ($value) {
                return is_scalar($value) ? (string) $value : '';
            }, $text)));
        }

        $text = (string) $text;

        if (preg_match_all('/\d+/', $text, $matches) && ! empty($matches[0])) {

            return intval(end($matches[0]));

        } else {

            return 0;

        }

    }

    private function getDirectSettlementShiftPrefix(?int $business_id = null): string
    {
        // One permanent numbering authority for Petro Direct.  The Direct Shift
        // and Direct Settlement number must always be the same DSTn pair.
        return 'DST';
    }

    private function getNextDirectSettlementShiftLabel(int $business_id, ?string $currentLabel = null, ?int $currentOperatorId = null, ?int $selectedOperatorId = null): string
    {
        $currentLabel = $this->normalizeDirectSettlementShiftLabel($currentLabel, $business_id);

        // Historical records keep their stored shift label unchanged. New work
        // always previews the same canonical number as Direct Settlement No.
        if (! empty($currentLabel)) {
            return $currentLabel;
        }

        return $this->getNextDirectSettlementNo($business_id);
    }

    /**
     * MA-002 (IS-1944 #1): $excludeSettlementNo is OPTIONAL and defaults to null.
     *
     * It must be passed EXPLICITLY. An earlier draft read it from request()
     * inside this method, which was wrong: EditsPdSettlements calls this too,
     * and on an edit the pumps already on the settlement MUST still be listed -
     * they are part of it. Reading the request would have hidden them the
     * moment an edit request happened to carry a settlement_no.
     *
     * Only the create-time pump lookup passes it.
     */
    private function getAvailableDirectSettlementPumps(int $business_id, ?int $location_id = null, $excludeSettlementNo = null)
    {
        /*
         * LA-1091 v4 root correction.
         * Direct Settlement is a manual entry screen. A pump already assigned/open in
         * another operational flow must not disappear from this dropdown after the user
         * selects it. Rebuilding the list without that pump caused Select2 to display
         * "Please Select" while its meter/price details stayed on screen.
         */
        $labelColumn = SchemaCapabilityCache::hasColumn('pumps', 'pump_name')
            ? 'pump_name'
            : (SchemaCapabilityCache::hasColumn('pumps', 'pump_no') ? 'pump_no' : 'id');

        $buildPumpQuery = function (bool $filterBusiness, bool $filterLocation) use ($business_id, $location_id) {
            $query = Pump::query();

            if ($filterBusiness && ! empty($business_id) && SchemaCapabilityCache::hasColumn('pumps', 'business_id')) {
                $query->where('business_id', $business_id);
            }

            if ($filterLocation && ! empty($location_id) && SchemaCapabilityCache::hasColumn('pumps', 'location_id')) {
                $query->where('location_id', $location_id);
            }

            if (SchemaCapabilityCache::hasColumn('pumps', 'is_other_sales_pump')) {
                $query->where(function ($q) {
                    $q->where('is_other_sales_pump', 0)->orWhereNull('is_other_sales_pump');
                });
            }

            if (SchemaCapabilityCache::hasColumn('pumps', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            /*
             * MA-002 (IS-1944 #1): hide pumps already added to THIS settlement.
             *
             * Adding a meter sale writes a meter_sales row immediately, tagged
             * with the settlement it belongs to - the settlement itself is
             * saved later. So a pump could be picked again and added twice.
             *
             * Those pumps are excluded until the settlement is saved. Once it
             * is, a new settlement gets a new settlement_no and every pump is
             * offered again, which is the behaviour asked for.
             *
             * Guarded on the table and columns existing, so an installation
             * without meter_sales is unaffected.
             */
            if (! empty($excludeSettlementNo)
                && SchemaCapabilityCache::hasColumn('meter_sales', 'pump_id')
                && SchemaCapabilityCache::hasColumn('meter_sales', 'settlement_no')) {

                $alreadyAdded = \DB::table('meter_sales')
                    ->where('settlement_no', $excludeSettlementNo)
                    ->when(
                        SchemaCapabilityCache::hasColumn('meter_sales', 'business_id') && ! empty($business_id),
                        fn ($q) => $q->where('business_id', $business_id)
                    )
                    ->when(
                        SchemaCapabilityCache::hasColumn('meter_sales', 'deleted_at'),
                        fn ($q) => $q->whereNull('deleted_at')
                    )
                    ->pluck('pump_id')
                    ->filter()
                    ->all();

                if (! empty($alreadyAdded)) {
                    $query->whereNotIn('id', $alreadyAdded);
                }
            }
            \Modules\PetroDirect\Support\PetroDirectIsolation::excludePumps($query, 'pumps.is_petro_pd_only');

            return $query;
        };

        $pumps = $buildPumpQuery(true, true)->orderBy($labelColumn)->pluck($labelColumn, 'id');

        // Older tenant data can have missing location/business links. Use safe fallbacks
        // rather than returning an empty list that resets the user's selection.
        if ($pumps->isEmpty()) {
            $pumps = $buildPumpQuery(true, false)->orderBy($labelColumn)->pluck($labelColumn, 'id');
        }
        if ($pumps->isEmpty()) {
            $pumps = $buildPumpQuery(false, false)->orderBy($labelColumn)->pluck($labelColumn, 'id');
        }

        return $pumps->map(function ($label, $id) {
            return $label ?: ('Pump ' . $id);
        });
    }

    public function print($id)
    {

        /*
         * MA-002 (Issue 1): "Unable to load the settlement print." was produced
         * by the abort_unless() gate below returning 404, not by the print view
         * itself. On tenant domains 'business.id' can be absent from the
         * session, which made $business_id null and forced
         * isHistoricalDirectSettlementRecord() to return false for every row.
         * Resolve the active business the same way index()/show() do.
         */
        $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);

        abort_if($business_id <= 0, 403, __('messages.unauthorized_action'));

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        abort_unless(
            $this->isHistoricalDirectSettlementRecord((int) $id, (int) $business_id),
            404
        );

        $settlement = Settlement::where('settlements.id', $id)

            ->where('settlements.business_id', $business_id)

            ->leftjoin(

                'pump_operators',

                'settlements.pump_operator_id',

                'pump_operators.id'

            )

            ->with([

                'meter_sales',

                'other_sales',

                'other_incomes',

                'customer_payments',

                'cash_payments',

                'cash_deposits',

                'card_payments',

                'cheque_payments',

                'credit_sale_payments',

                'expense_payments',

                'excess_payments',

                'shortage_payments',

                'loan_payments',

                'drawings_payments',

                'customer_loans',

            ])

            ->select(

                'settlements.*',

                'pump_operators.name as pump_operator_name'

            )

            ->first();

        if ($settlement) {
            // Printed/reprinted Direct Settlement must exactly match the Meter
            // Sale rows that were confirmed and finalised.
            app(\Modules\PetroDirect\Services\DirectSettlementMeterSaleScopeService::class)
                ->apply($settlement, (int) $business_id);
        }

        // Reload cash payments with fallback for mixed settlement_no storage (id vs settlement_no string)
        // CRITICAL: This reload is essential because show() method has this but print() was missing it
        // When settlement_no is stored as integer instead of string, the eager-loaded relationship fails
        if ($settlement) {
            $cash_payments = \Modules\PetroDirect\Entities\SettlementCashPayment::where('business_id', $business_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no);
                })
                ->get();

            $settlement->setRelation('cash_payments', $cash_payments);
        }

        // Manually load cash deposits if relationship didn't load them (fallback for Settlement SW)
        if ($settlement && $settlement->cash_deposits->isEmpty()) {
            $cash_deposits = \Modules\PetroDirect\Entities\SettlementCashDeposit::where('settlement_no', $settlement->settlement_no)
                ->orWhere('settlement_no', $settlement->id)
                ->get();
            $settlement->setRelation('cash_deposits', $cash_deposits);
        }

        // Fix: Fetch Cash Deposits from Accounting Module (AccountTransaction)
        // because they are not stored in settlement_cash_deposits table
        if ($settlement) {
            $account_transactions = \App\AccountTransaction::where('business_id', $business_id)
                ->where('sub_type', 'deposit')
                ->whereDate('operation_date', $settlement->transaction_date)
                ->get();
            
            // Transform AccountTransactions to match SettlementCashDeposit structure if needed,
            // or just merge them into the collection since we mostly need 'amount'.
            if ($account_transactions->isNotEmpty()) {
                // We create new instances or just merge. Since the view uses ->sum('amount'), 
                // merging the AccountTransaction objects directly is safe as they have an 'amount' field.
                $current_deposits = $settlement->cash_deposits;
                $merged_deposits = $current_deposits->merge($account_transactions);
                $settlement->setRelation('cash_deposits', $merged_deposits);
            }
        }

        // Reload credit_sale_payments with fallback logic (similar to SettlementPDController)
        // This handles cases where credit sales are saved with settlement ID instead of settlement_no string
        if ($settlement) {
            // Always reload to ensure we have all records (handles both string and integer settlement_no)
            // For direct settlement, do NOT filter by pump_operator_id so all credit sales linked to this
            // settlement are included (matches behavior already fixed for Settlement PD).
            $credit_sales = SettlementCreditSalePayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->where('business_id', $business_id)
                ->petroDirectOwned()
                ->with('product')
                ->get();
            $settlement->setRelation('credit_sale_payments', $credit_sales);
        }

        // Reload excess_payments with fallback logic (handles both string and integer settlement_no)
        if ($settlement) {
            $excess_payments = SettlementExcessPayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('excess_payments', $excess_payments);
        }

        // Reload shortage_payments with fallback logic (handles both string and integer settlement_no)
        if ($settlement) {
            $shortage_payments = SettlementShortagePayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('shortage_payments', $shortage_payments);
        }

        // Reload cheque_payments with fallback logic (handles both string and integer settlement_no)
        if ($settlement) {
            $cheque_payments = SettlementChequePayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('cheque_payments', $cheque_payments);
        }

        // Reload expense_payments with fallback logic (handles both string and integer settlement_no)
        if ($settlement) {
            $expense_payments = SettlementExpensePayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('expense_payments', $expense_payments);
        }

        // Reload loan_payments with fallback logic (handles both string and integer settlement_no)
        if ($settlement) {
            $loan_payments = SettlementLoanPayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('loan_payments', $loan_payments);
        }

        // Reload drawings_payments with fallback logic (handles both string and integer settlement_no)
        if ($settlement) {
            $drawings_payments = SettlementDrawingPayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('drawings_payments', $drawings_payments);
        }

        // Reload customer_loans with fallback logic (handles both string and integer settlement_no)
        if ($settlement) {
            $customer_loans = SettlementCustomerLoan::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->get();
            $settlement->setRelation('customer_loans', $customer_loans);
        }

        $shift_ids = [];
        $print_pump_operator_other_sales = collect();
        if ($settlement) {
            $shift_ids = array_values(array_unique(array_filter(array_map('intval', $this->extractShiftIdsFromSettlement($settlement)))));
            $print_pump_operator_other_sales = $this->getPrintPumpOperatorOtherSales($settlement, (int) $business_id, $shift_ids);
        }

        $business = Business::where('id', $settlement->business_id)->first();

        $pump_operator = PumpOperator::where(

            'id',

            $settlement->pump_operator_id

        )->first();

        // this for only to show in print page customer payments which entered in customer payments tab

        $customer_payments_tab = CustomerPayment::leftjoin(

            'contacts',

            'customer_payments.customer_id',

            'contacts.id'

        )

            ->where(

                'customer_payments.settlement_no',

                $settlement->settlement_no

            )

            ->where('customer_payments.business_id', $business_id)

            ->select('customer_payments.*', 'contacts.name as customer_name')

            ->get();

        $total_daily_collection = floatval(

            DailyCollection::where(

                'pump_operator_id',

                $settlement->pump_operator_id

            )

                ->where('business_id', $business->id)

                ->where('settlement_id', $settlement->id)

                ->where('type', 'daily_collection')

                ->sum('current_amount')

        );

        return view('petrodirect::settlement.print')->with(

            compact(

                'settlement',

                'business',

                'pump_operator',

                'customer_payments_tab',

                'total_daily_collection',

                'shift_ids',

                'print_pump_operator_other_sales'

            )

        );

    }

    // Added by Muneeb Ahmad for Store Dropdown

    /**
     * Return only stores available to the logged-in user for the selected location.
     * Falls back to every permitted store when the location has no store records.
     */

    public function checkSlipNo(Request $request)
    {

        $slip_no = trim(str_replace(' ', '', $request->input('slip_no')));

        $business_id = auth()->user()->business_id;
        $today = \Carbon\Carbon::now()->format('Y-m-d');

        // Check if the slip number already exists for this business in AccountTransaction
        $exists_account = AccountTransaction::where('business_id', $business_id)
            ->where('slip_no', $slip_no)
            ->exists();

        // Check if the slip number already exists in SettlementCardPayment for today
        $exists_settlement = SettlementCardPayment::where('slip_no', $slip_no)
            ->whereDate('created_at', $today)
            ->exists();

        // Slip number exists if found in either table
        $exists = $exists_account || $exists_settlement;

        // Checked means "Do not allow", so the response value is its inverse.
        $preventDuplicateSlipNumbers = ModuleUtil::hasConfiguredPermissionInSubscription(
            $business_id,
            'duplicate_slip_numbers'
        );
        $allow_duplicates = ! $preventDuplicateSlipNumbers;

        // Return JSON response

        return response()->json([

            'exists' => $exists,

            'allow_duplicates' => $allow_duplicates,

        ]);

    }
}
