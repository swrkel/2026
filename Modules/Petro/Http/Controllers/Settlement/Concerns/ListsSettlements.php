<?php

namespace Modules\Petro\Http\Controllers\Settlement\Concerns;

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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use Modules\HR\Entities\WorkShift;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Petro\Entities\CustomerBillVatPrefix;
use Modules\Petro\Entities\DailyCard;
use Modules\Petro\Entities\DailyCollection;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\DayEnd;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\MeterSale;
use Modules\Petro\Entities\OtherIncome;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PetroShift;
use Modules\Petro\Entities\PetroWhatsAppTemplate;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumperDayEntry;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorAssignment;
use Modules\Petro\Entities\PumpOperatorCommission;
use Modules\Petro\Entities\PumpOperatorPayment;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Entities\SettlementEditHistory;
use Modules\Petro\Entities\SettlementExcessPayment;
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\SettlementShortagePayment;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\Petro\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Listing, viewing and printing settlements.
 *
 * MA-002: split out of Petro's SettlementController, which was 11,795 lines.
 *
 * The grouping was worked out FOR THIS CONTROLLER, not copied from PetroPD's.
 * The four settlement modules have genuinely diverged - 17 of the 19
 * controllers they share differ in logic - so Petro has methods PetroPD does
 * not (mechanical meter comparison, auto shift numbering, real-time payment
 * sync) and vice versa. Copying a grouping across would have produced tidy
 * files with the wrong things in them.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged: routes still point at SettlementController,
 *   action() targets still resolve, and $this-> calls between these 91 methods
 *   still work. Separate controller classes would mean rewriting routes and
 *   every action() reference - a behavioural change dressed up as tidying.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten.
 *
 * Methods here: index, show, print, getUserActivityReport, getPrintPumpOperatorOtherSales, getDirectSettlementOtherSaleItems, getPumpOperatorOtherSaleTotalForSettlement, checkSlipNo
 */
trait ListsSettlements
{
    public function index()
    {

        $business_id = request()

            ->session()

            ->get('user.business_id');

        if (

            ! $this->moduleUtil->hasThePermissionInSubscription(

                $business_id,

                'enable_petro_module'

            )

        ) {

            abort(403, 'Unauthorized Access');

        }

        if (request()->ajax()) {

            $business_id = request()

                ->session()

                ->get('user.business_id');

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

                    ->leftJoin('pump_operator_assignments', function ($join) {

                        $join->on(

                            'settlements.id',

                            '=',

                            'pump_operator_assignments.settlement_id'

                            // ->orOn(function ($query) {

                            //     $query->on('settlements.pump_operator_id', '=', 'pump_operator_assignments.pump_operator_id')

                            //         ->whereNull('pump_operator_assignments.settlement_id');

                            // })

                        );

                    })
                    ->where('settlements.business_id', $business_id)
                    ->where('settlements.settlement_no', 'LIKE', 'ST%')
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
                            $query->orWhereExists(function ($sub) use ($detail_table) {
                                $sub->select(DB::raw(1))
                                    ->from($detail_table)
                                    ->where(function ($detail_query) use ($detail_table) {
                                        $detail_query->whereColumn($detail_table . '.settlement_no', 'settlements.id')
                                            ->orWhereColumn($detail_table . '.settlement_no', 'settlements.settlement_no');
                                    });
                            });
                        }

                        $query->orWhereExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('settlement_credit_sale_payments')
                                ->where(function ($credit_query) {
                                    $credit_query->whereColumn('settlement_credit_sale_payments.settlement_no', 'settlements.settlement_no')
                                        ->orWhereColumn('settlement_credit_sale_payments.settlement_no', 'settlements.id');
                                });
                        });
                    })

                    ->select([

                        'pump_operators.name as pump_operator_name',

                        'business_locations.name as location_name',

                        'settlements.*',

                        'pump_operator_assignments.shift_number',

                    ])

                    ->with(['meter_sales', 'other_sales', 'other_incomes', 'customer_payments']);

                $this->excludePetroPdModuleSettlements($query, $business_id);
                $this->excludeSettlementsWithPetroPdSettledShifts($query, $business_id);
                \App\Utils\PetroPdIsolationUtil::excludeSettlements($query);

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
                    ->where('settlement_no', 'NOT LIKE', 'SET-SW%');

                $this->excludePetroPdModuleSettlements($first, $business_id, 'settlement_no');
                $this->excludeSettlementsWithPetroPdSettledShifts($first, $business_id);
                \App\Utils\PetroPdIsolationUtil::excludeSettlements($first, 'settlements.id', 'settlements.pump_operator_id');

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

                // Direct Settlement actions use the Petro permission set. Package
                // flags remain loaded for backward compatibility, but must not hide
                // Edit / Edit No Change from an authorised user.
                $can_edit_direct_settlement = $this->canEditDirectSettlements();

                \Modules\Petro\Support\PetroDebug::info('Settlement list query prepared');

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

                                        '<a class="btn  btn-danger btn-sm" href="'.

                                        action(

                                            "\Modules\SettlementSW\Http\Controllers\SettlementSWController@index"

                                        ).

                                        '">'.

                                        __('petro::lang.finish_settlement').

                                        '</a>';

                                } else {

                                    $html .=

                                        '<a class="btn  btn-danger btn-sm" href="'.

                                        action(

                                            "\Modules\Petro\Http\Controllers\SettlementController@create"

                                        ) . '?view_settlement_id=' . $row->id .

                                        '">'.

                                        __('petro::lang.finish_settlement').

                                        '</a>';

                                }

                            } elseif ($row->is_edit == 1) {

                                $html .=

                                    '<a class="btn  btn-warning btn-sm" href="'.

                                    action(

                                        "\Modules\Petro\Http\Controllers\SettlementController@edit",

                                        [$row->id]

                                    ).

                                    '">'.

                                    __('petro::lang.finish_editting').

                                    '</a>';

                            } else {

                                $html .=

                                    '<div class="btn-group direct-settlement-action-group">







                                <button type="button" class="btn btn-info dropdown-toggle btn-xs direct-settlement-action-toggle"







                                    aria-haspopup="true" aria-expanded="false">'.

                                    __('messages.actions').

                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown



                                    </span>



                                </button>







                                <ul class="dropdown-menu dropdown-menu-left direct-settlement-action-menu" role="menu">';

                                $view_url = action(
                                    "\Modules\Petro\Http\Controllers\SettlementController@show",
                                    [$row->id]
                                );

                                $html .=
                                    '<li><a href="' . $view_url . '" data-href="' . $view_url .
                                    '" class="btn-modal view_settlement_button" data-container=".settlement_modal"><i class="fa fa-eye" aria-hidden="true"></i> ' .
                                    __('messages.view') .
                                    '</a></li>';

                                if ($can_edit_direct_settlement) {

                                    $html .=

                                        '<li><a href="'.

                                        action(

                                            "\Modules\Petro\Http\Controllers\SettlementController@edit",

                                            [$row->id]

                                        ).

                                        '" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> '.

                                        __('messages.edit').

                                        '</a></li>';

                                }

                                if ($can_edit_direct_settlement) {

                                    $html .=

                                        '<li><a href="'.

                                        action(

                                            "\Modules\Petro\Http\Controllers\SettlementController@edit",

                                            [$row->id]

                                        ).

                                        '?no_change=1" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> '.

                                        __('petro::lang.edit_no_change').

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

                                        ->can('petro.settlement.delete')

                                ) {

                                    // commented By M Usman for hiding Delete Action

                                    $html .=

                                        '<li><a href="'.

                                        action(

                                            "\Modules\Petro\Http\Controllers\SettlementController@destroy",

                                            [$row->id]

                                        ).

                                        '" class="delete_settlement_button"><i class="fa fa-trash"></i> '.

                                        __('messages.delete').

                                        '</a></li>';

                                }

                                $html .=

                                    '<li><a data-href="'.

                                    action(

                                        "\Modules\Petro\Http\Controllers\SettlementController@print",

                                        [$row->id]

                                    ).

                                    '" class="print_settlement_button"><i class="fa fa-print"></i> '.

                                    __('petro::lang.print').

                                    '</a></li>';

                                $mechanical_meter_url = action(
                                    "\Modules\Petro\Http\Controllers\SettlementController@mechanicalMeter",
                                    [$row->id]
                                );

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

                                "\Modules\Petro\Http\Controllers\SettlementController@show",

                                [$row->id]

                            );

                        },

                    ])

                    ->removeColumn('id');

                return $settlements

                    ->rawColumns(['action', 'status', 'total_amount'])

                    ->make(true);

            }

        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $pump_operators = $this->getDirectSettlementPumpOperatorDropdown($business_id);

        $settlement_nos = Settlement::where('business_id', $business_id)
            ->where('settlement_no', 'LIKE', $this->getDirectSettlementPrefix($business_id) . '%')
            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlement_no', 'NOT LIKE', 'PDST%');

        $this->excludePetroPdModuleSettlements($settlement_nos, $business_id, 'settlement_no');
        $this->excludeSettlementsWithPetroPdSettledShifts($settlement_nos, $business_id);
        \App\Utils\PetroPdIsolationUtil::excludeSettlements($settlement_nos, 'settlements.id', 'settlements.pump_operator_id');

        $settlement_nos = $settlement_nos
            ->pluck(
                'settlement_no',
                'id'
            );

        $message = $this->transactionUtil->getGeneralMessage(

            'general_message_pump_management_checkbox'

        );

        return view('petro::settlement.index')->with(

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

        $business_id = request()

            ->session()

            ->get('business.id');

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

            $settlement = Settlement::where('settlements.id', $id)

            ->where('settlements.business_id', $business_id)

            ->leftjoin(

                'pump_operators',

                'settlements.pump_operator_id',

                'pump_operators.id'

            )

            ->leftJoin('pump_operator_assignments', function ($join) {

                $join->on(

                    'settlements.id',

                    '=',

                    'pump_operator_assignments.settlement_id'

                    // ->orOn(function ($query) {

                    //     $query->on('settlements.pump_operator_id', '=', 'pump_operator_assignments.pump_operator_id')

                    //         ->whereNull('pump_operator_assignments.settlement_id');

                    // })

                );

            })

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

                'pump_operators.name as pump_operator_name',

                'pump_operator_assignments.shift_id'

            )

            ->first();

        if (empty($settlement)) {
            abort(404);
        }

        // Reload cash payments with fallback for mixed settlement_no storage (id vs settlement_no string)
        $cash_payments = \Modules\Petro\Entities\SettlementCashPayment::where('business_id', $business_id)
            ->where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)
                    ->orWhere('settlement_no', $settlement->settlement_no);
            })
            ->get();

        $settlement->setRelation('cash_payments', $cash_payments);

        // Manually load cash deposits if relationship didn't load them (fallback for Settlement SW)
        if ($settlement && $settlement->cash_deposits->isEmpty()) {
            $cash_deposits = \Modules\Petro\Entities\SettlementCashDeposit::where('settlement_no', $settlement->settlement_no)
                ->orWhere('settlement_no', $settlement->id)
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
            $credit_sales = SettlementCreditSalePayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
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

            ->where('customer_payments.settlement_no', $id)

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

        return view('petro::settlement.show')->with(

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

    public function print($id)
    {

        $business_id = request()

            ->session()

            ->get('business.id');

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

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

        // Reload cash payments with fallback for mixed settlement_no storage (id vs settlement_no string)
        // CRITICAL: This reload is essential because show() method has this but print() was missing it
        // When settlement_no is stored as integer instead of string, the eager-loaded relationship fails
        if ($settlement) {
            $cash_payments = \Modules\Petro\Entities\SettlementCashPayment::where('business_id', $business_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no);
                })
                ->get();

            $settlement->setRelation('cash_payments', $cash_payments);
        }

        // Manually load cash deposits if relationship didn't load them (fallback for Settlement SW)
        if ($settlement && $settlement->cash_deposits->isEmpty()) {
            $cash_deposits = \Modules\Petro\Entities\SettlementCashDeposit::where('settlement_no', $settlement->settlement_no)
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

        return view('petro::settlement.print')->with(

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

    public function getUserActivityReport(Request $request)
    {

        $business_id = request()

            ->session()

            ->get('user.business_id');

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

                                "Modules\Petro\Entities\Settlement"

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

        return view('petro::report.user_activity')->with(

            compact('users', 'type', 'subject')

        );

    }

    private function getPrintPumpOperatorOtherSales($settlement, int $business_id, array $shift_ids = [])
    {
        if (empty($settlement)) {
            return collect();
        }

        $shift_ids = array_values(array_unique(array_filter(array_map('intval', $shift_ids))));

        $work_shift_ids = array_values(array_unique(array_filter(array_map('intval', $this->extractShiftIdsFromSettlement($settlement)))));

        $linked_shift_ids = MeterSale::where('business_id', $business_id)
            ->where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)
                    ->orWhere('settlement_no', $settlement->settlement_no);
            })
            ->whereNotNull('shift_id')
            ->pluck('shift_id')
            ->map(fn ($shift_id) => (int) $shift_id)
            ->toArray();

        $assignment_shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('settlement_id', $settlement->id)
            ->whereNotNull('shift_id')
            ->pluck('shift_id')
            ->map(fn ($shift_id) => (int) $shift_id)
            ->toArray();

        $shift_ids = array_values(array_unique(array_filter(array_merge(
            $shift_ids,
            $work_shift_ids,
            $linked_shift_ids,
            $assignment_shift_ids
        ))));

        $query = PumpOperatorOtherSale::where('business_id', $business_id);

        if (! empty($shift_ids)) {
            return $query->whereIn('shift_id', $shift_ids)->get();
        }

        if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operator_other_sales', 'settlement_no')) {
            return $query->where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)
                    ->orWhere('settlement_no', $settlement->settlement_no);
            })->get();
        }

        return collect();
    }

    private function getDirectSettlementOtherSaleItems(int $businessId, ?int $fuelCategoryId = null): array
    {
        $items = [];

        try {
            $items = $this->transactionUtil->getProductDropDownArray(
                $businessId,
                $fuelCategoryId,
                'petro_settlements'
            );
        } catch (\Throwable $exception) {
            \Modules\Petro\Support\PetroDebug::info(
                'Petro module-filtered Other Sale dropdown failed; using safe fallback.',
                [
                    'business_id' => $businessId,
                    'message' => $exception->getMessage(),
                ]
            );
        }

        $items = collect($items)->filter(function ($label, $id) {
            return (int) $id > 0 && trim((string) $label) !== '';
        })->all();

        $query = Product::query()
            ->where('business_id', $businessId);

        if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('products', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('products', 'is_inactive')) {
            $query->where(function ($activeQuery) {
                $activeQuery->whereNull('is_inactive')
                    ->orWhere('is_inactive', 0);
            });
        }

        if (! empty($fuelCategoryId)
            && \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('products', 'category_id')) {
            $query->where(function ($categoryQuery) use ($fuelCategoryId) {
                $categoryQuery->whereNull('category_id')
                    ->orWhere('category_id', '!=', $fuelCategoryId);
            });
        }

        $fallbackItems = $query
            ->orderBy('name')
            ->pluck('name', 'id')
            ->filter(function ($label, $id) {
                return (int) $id > 0 && trim((string) $label) !== '';
            })
            ->all();

        $merged = $items + $fallbackItems;
        natcasesort($merged);

        return $merged;
    }

    /**
     * Normalize the displayed Direct Settlement date to one Y-m-d value.
     */

    private function getPumpOperatorOtherSaleTotalForSettlement($settlement)
    {
        $shiftIds = $this->extractShiftIdsFromSettlement($settlement);

        if (empty($shiftIds) && ! empty($settlement->id)) {
            $shiftIds = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->unique()
                ->values()
                ->toArray();
        }

        if (empty($shiftIds)) {
            return 0;
        }

        return PumpOperatorOtherSale::leftJoin('pump_operator_assignments', function ($join) {
            $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')
                ->whereRaw(
                    'pump_operator_assignments.id = (
                        SELECT MAX(poa.id)
                        FROM pump_operator_assignments poa
                        WHERE poa.shift_id = pump_operator_other_sales.shift_id
                    )'
                );
        })
            ->whereIn('pump_operator_other_sales.shift_id', $shiftIds)
            ->sum('pump_operator_other_sales.sub_total');
    }

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

        // Define your duplicate allowance logic here

        // For example, allow duplicates only if user has a special role

        $allow_duplicates = false;

        // Return JSON response

        return response()->json([

            'exists' => $exists,

            'allow_duplicates' => $allow_duplicates,

        ]);

    }
}
