<?php
namespace Modules\PetroGeneral\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\AccountType;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\ExpenseCategory;
use App\Http\Controllers\ContactController;
use Modules\PetroGeneral\Entities\PumpOperatorMeterSale;
use App\Product;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\PetroGeneral\Entities\CustomerPayment;
use Modules\PetroGeneral\Entities\DailyCard;
use Modules\PetroGeneral\Entities\DailyChequePayment;
use Modules\PetroGeneral\Entities\DailyCollection;
use Modules\PetroGeneral\Entities\DailyVoucher;
use Modules\PetroGeneral\Entities\DayEnd;
use Modules\PetroGeneral\Entities\MeterSale;
use Modules\PetroGeneral\Entities\OtherIncome;
use Modules\PetroGeneral\Entities\OtherSale;
use Modules\PetroGeneral\Entities\PetroShift;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\PumpOperatorAssignment;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorOtherSale;
use Modules\PetroGeneral\Entities\PumpOperatorPayment;
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\SettlementCardPayment;
use Modules\PetroGeneral\Entities\SettlementCashDeposit;
use Modules\PetroGeneral\Entities\SettlementCashPayment;
use Modules\PetroGeneral\Entities\SettlementChequePayment;
use Modules\PetroGeneral\Entities\SettlementCreditSalePayment;
use Modules\PetroGeneral\Entities\SettlementCustomerLoan;
use Modules\PetroGeneral\Entities\SettlementDrawingPayment;
use Modules\PetroGeneral\Entities\SettlementExcessPayment;
use Modules\PetroGeneral\Entities\SettlementExpensePayment;
use Modules\PetroGeneral\Entities\SettlementLoanPayment;
use Modules\PetroGeneral\Entities\SettlementPosPayment;
use Modules\PetroGeneral\Entities\SettlementShortagePayment;
use Modules\PetroGeneral\Entities\IssueCustomerBillSetting;
use Modules\Superadmin\Entities\Subscription;
use PhpParser\Node\Expr\AssignOp\Concat;

class AddPaymentController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;

    protected $businessUtil;

    private $barcode_types;

    /**
     * Constructor

     *

     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {

        $this->commonUtil = $commonUtil;

        $this->productUtil = $productUtil;

        $this->moduleUtil = $moduleUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

    }

    private function pumpOperatorPaymentsHasCardMetaColumns(): bool
    {
        return Schema::hasColumn('pump_operator_payments', 'customer_id')
            && Schema::hasColumn('pump_operator_payments', 'slip_no')
            && Schema::hasColumn('pump_operator_payments', 'card_type')
            && Schema::hasColumn('pump_operator_payments', 'card_number');
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function addCardPaymentManualUnlinkedFilter($query, bool $include_linked_card_payment): void
    {
        if ($include_linked_card_payment) {
            $query->whereNull('linked_card_payment.id');
        }

        $query->whereNull('parent_card_payment.id')
            ->whereNull('matched_card_payment.id')
            ->whereNull('daily_cards.id');
    }

    private function addCardPaymentShiftFilter($query, array $shift_ids, bool $include_linked_card_payment): void
    {
        if (count($shift_ids) > 1) {
            if ($include_linked_card_payment) {
                $query->whereIn('linked_card_payment.shift_id', $shift_ids)
                    ->orWhereIn('parent_card_payment.shift_id', $shift_ids);
            } else {
                $query->whereIn('parent_card_payment.shift_id', $shift_ids);
            }

            $query->orWhereIn('matched_card_payment.shift_id', $shift_ids);
            return;
        }

        if ($include_linked_card_payment) {
            $query->where('linked_card_payment.shift_id', $shift_ids[0])
                ->orWhere('parent_card_payment.shift_id', $shift_ids[0]);
        } else {
            $query->where('parent_card_payment.shift_id', $shift_ids[0]);
        }

        $query->orWhere('matched_card_payment.shift_id', $shift_ids[0]);
    }

    private function addCardPaymentDailyCardShiftFilter($query, array $shift_ids, bool $include_linked_card_payment): void
    {
        if ($include_linked_card_payment) {
            $query->whereNull('linked_card_payment.shift_id');
        }

        $query->whereNull('parent_card_payment.shift_id')
            ->whereNull('matched_card_payment.shift_id')
            ->whereNotNull('daily_cards.shift_id');

        if (count($shift_ids) > 1) {
            $query->whereIn('daily_cards.shift_id', $shift_ids);
        } else {
            $query->where('daily_cards.shift_id', $shift_ids[0]);
        }
    }

    private function getSettlementWorkShiftIds(?Settlement $settlement): array
    {
        if (empty($settlement) || empty($settlement->work_shift)) {
            return [];
        }

        $work_shifts = $settlement->work_shift;

        if (is_string($work_shifts)) {
            $decoded = json_decode($work_shifts, true);
            $work_shifts = is_array($decoded) ? $decoded : explode(',', $work_shifts);
        }

        if (! is_array($work_shifts)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $work_shifts))));
    }

    private function findLinkedDailyCardForPumpPayment(PumpOperatorPayment $pump_payment, int $business_id): ?DailyCard
    {
        $query = DailyCard::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_payment->pump_operator_id)
            ->where('collection_no', $pump_payment->collection_form_no)
            ->where('amount', $pump_payment->payment_amount);

        if (! empty($pump_payment->slip_no)) {
            $query->whereRaw('REPLACE(COALESCE(slip_no, ""), " ", "") = ?', [
                preg_replace('/\s+/', '', (string) $pump_payment->slip_no),
            ]);
        }

        return $query->orderByDesc('id')->first();
    }

    private function findMatchingPumpCardPayment(?Settlement $settlement, Request $request, int $business_id): ?PumpOperatorPayment
    {
        if (empty($settlement)) {
            return null;
        }

        $work_shift_ids = $this->getSettlementWorkShiftIds($settlement);
        $has_card_meta_columns = $this->pumpOperatorPaymentsHasCardMetaColumns();

        $base_query = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->where('payment_type', 'card')
            ->where(function ($q) {
                $q->whereNull('is_used')->orWhere('is_used', 0);
            });

        if (! empty($work_shift_ids)) {
            $base_query->whereIn('shift_id', $work_shift_ids);
        }

        if ($request->filled('pump_payment_id')) {
            $exact_payment = (clone $base_query)
                ->where('id', $request->input('pump_payment_id'))
                ->first();

            if (! empty($exact_payment)) {
                return $exact_payment;
            }
        }

        if ($request->filled('collection_form_no')) {
            $collection_payment = (clone $base_query)
                ->where('collection_form_no', $request->input('collection_form_no'))
                ->first();

            if (! empty($collection_payment)) {
                return $collection_payment;
            }
        }

        $amount = (float) $request->input('amount', 0);
        $normalized_slip_no = preg_replace('/\s+/', '', (string) $request->input('slip_no', ''));
        $customer_id = $request->input('customer_id');
        $card_type = $request->input('card_type');
        $card_number = trim((string) $request->input('card_number', ''));

        if ($has_card_meta_columns && ($amount > 0 || ! empty($normalized_slip_no) || ! empty($customer_id) || ! empty($card_type) || ! empty($card_number))) {
            $meta_query = clone $base_query;

            if ($amount > 0) {
                $meta_query->where('payment_amount', $amount);
            }

            if (! empty($normalized_slip_no)) {
                $meta_query->whereRaw('REPLACE(COALESCE(slip_no, ""), " ", "") = ?', [$normalized_slip_no]);
            }

            if (! empty($customer_id)) {
                $meta_query->where('customer_id', $customer_id);
            }

            if (! empty($card_type)) {
                $meta_query->where('card_type', $card_type);
            }

            if (! empty($card_number)) {
                $meta_query->where('card_number', $card_number);
            }

            $matched_payment = $meta_query->orderByDesc('id')->first();
            if (! empty($matched_payment)) {
                return $matched_payment;
            }
        }

        if ($amount > 0) {
            return (clone $base_query)
                ->where('payment_amount', $amount)
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    /**
     * Display a listing of the resource.

     *
     * @return Response
     */
    public function index()
    {

        $business_id = request()->session()->get('user.business_id');

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
            abort(403, 'Unauthorized Access');
        }

        return view('petrogeneral::index');

    }

    /**
     * Show the form for creating a new resource.

     *
     * @return Response
     */
    public function addDailyCards($settlement_no, $pump_operator_id, $business_id, $shift_id)
    {
        // Handle both single shift_id and comma-separated string or array
        $shift_ids = [];
        if (is_string($shift_id) && strpos($shift_id, ',') !== false) {
            $shift_ids = array_filter(array_map('trim', explode(',', $shift_id)));
        } elseif (is_array($shift_id)) {
            $shift_ids = $shift_id;
        } else {
            $shift_ids = [$shift_id];
        }
        $shift_ids = array_values(array_filter(array_map('intval', $shift_ids)));

        // settlement_no here is the settlement ID; resolve the string settlement_no for daily vouchers/credit sales
        $settlement = Settlement::find($settlement_no);
        if (empty($settlement) && !empty($settlement_no)) {
            $settlement = Settlement::where('settlement_no', $settlement_no)->first();
        }
        $settlement_no_str = !empty($settlement) ? $settlement->settlement_no : $settlement_no;

        // Get unique collection numbers for card payments in these shifts
        $query = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('payment_type', 'card');

        if (count($shift_ids) > 1) {
            $query->whereIn('shift_id', $shift_ids);
        } else {
            $query->where('shift_id', $shift_ids[0]);
        }

        $collection_numbers = $query->pluck('collection_form_no')
            ->unique()
            ->filter()
            ->toArray();

        if (!empty($collection_numbers)) {
            // Fetch ALL DailyCards for these collection numbers at once
            $daily_cards_query = DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->whereIn('collection_no', $collection_numbers);

            $daily_cards = $daily_cards_query->get();

            foreach ($daily_cards as $daily_card) {
                $data = [
                    'business_id'   => $business_id,
                    'settlement_no' => $settlement_no,
                    'amount'        => $daily_card->amount,
                    'card_type'     => $daily_card->card_type,
                    'card_number'   => $daily_card->card_number,
                    'daily_card_id' => $daily_card->id,
                    'customer_id'   => $daily_card->customer_id,
                    'note'          => $daily_card->note,
                    'slip_no'       => $daily_card->slip_no,
                ];

                $pump_payment_query = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('payment_type', 'card')
                    ->whereRaw('pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci', [$daily_card->collection_no])
                    ->whereRaw('ABS(payment_amount - ?) < 0.02', [(float) $daily_card->amount])
                    ->where(function($q) {
                        $q->whereNull('is_used')->orWhere('is_used', 0);
                    });

                if (count($shift_ids) > 1) {
                    $pump_payment_query->whereIn('shift_id', $shift_ids);
                } else {
                    $pump_payment_query->where('shift_id', $shift_ids[0]);
                }

                $pump_payment = $pump_payment_query->first();
                if (empty($pump_payment)) {
                    continue;
                }

                $data['pump_payment_id'] = $pump_payment->id;

                $settlement_card_payment = SettlementCardPayment::where('daily_card_id', $daily_card->id)
                    ->where('settlement_no', $settlement_no)
                    ->first();

                if (empty($settlement_card_payment)) {
                    $stale_settlement_card_payment = SettlementCardPayment::where('daily_card_id', $daily_card->id)->first();

                    if (! empty($stale_settlement_card_payment)) {
                        // Keep one source row per daily card; if an older settlement link exists, move it to the current settlement.
                        $settlement_card_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                            ->editCardPayment($business_id, $stale_settlement_card_payment->id, $data);
                    } else {
                        $settlement_card_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                            ->upsertOne($business_id, (string) $settlement_no, 'settlement_card_payments', $data);
                    }
                } else {
                    $settlement_card_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                        ->editCardPayment($business_id, $settlement_card_payment->id, $data);
                }

                // Update daily card status
                $daily_card->used_status   = 1;
                $daily_card->settlement_no = $settlement_no;
                $daily_card->save();

                if ($settlement_card_payment) {
                    $pump_payment->is_used = 1;
                    $pump_payment->parent_id = $settlement_card_payment->id;
                    $pump_payment->settlement_no = $settlement_no;
                    $pump_payment->save();
                }
            }
        }

        // Update the credit_nos
        $credit_query = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('payment_type', 'credit');

        if (count($shift_ids) > 1) {
            $credit_query->whereIn('shift_id', $shift_ids);
        } else {
            $credit_query->where('shift_id', $shift_ids[0]);
        }

        $credit_by_shifts = $credit_query->get();

        if ($credit_by_shifts->isNotEmpty()) {
            foreach ($credit_by_shifts as $credit_by_shift) {
                $items_query = SettlementCreditSalePayment::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('collection_form_no', $credit_by_shift->collection_form_no)
                    ->whereRaw('ABS(amount - ?) < 0.02', [(float) $credit_by_shift->payment_amount]);

                if ($this->tableHasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                    $items_query->where(function ($query) use ($credit_by_shift, $settlement_no, $settlement_no_str) {
                        $query->where('pump_payment_id', $credit_by_shift->id)
                            ->orWhere(function ($legacyQuery) use ($settlement_no, $settlement_no_str) {
                                $legacyQuery->whereNull('pump_payment_id')
                                    ->where(function ($ownerQuery) use ($settlement_no, $settlement_no_str) {
                                        $ownerQuery->whereNull('settlement_no')
                                            ->orWhere('settlement_no', '')
                                            ->orWhere('settlement_no', (string) $settlement_no)
                                            ->orWhere('settlement_no', (string) $settlement_no_str);
                                    });
                            });
                    });
                } else {
                    $items_query->where(function ($query) use ($settlement_no, $settlement_no_str) {
                        $query->whereNull('settlement_no')
                            ->orWhere('settlement_no', '')
                            ->orWhere('settlement_no', (string) $settlement_no)
                            ->orWhere('settlement_no', (string) $settlement_no_str);
                    });
                }

                $items = $items_query->get();

                foreach ($items as $item) {
                    $creditSaleUpdate = ['settlement_no' => $settlement_no_str];

                    $voucher = null;

                    if (! empty($item->collection_form_no)) {
                        $voucher = DailyVoucher::where('business_id', $business_id)
                            ->where('daily_vouchers_no', $item->collection_form_no)
                            ->where('operator_id', $item->pump_operator_id)
                            ->where('customer_id', $item->customer_id)
                            ->first();
                    }

                    if (! $voucher) {
                        $base_voucher_query = DailyVoucher::where('business_id', $business_id)
                            ->where('voucher_order_number', $item->order_number)
                            ->where('voucher_order_date', $item->order_date)
                            ->where('operator_id', $item->pump_operator_id)
                            ->where('customer_id', $item->customer_id);

                        $voucher = (clone $base_voucher_query)
                            ->where('total_amount', $item->amount)
                            ->orderBy('id', 'desc')
                            ->first();

                        if (! $voucher) {
                            $voucher = $base_voucher_query->orderBy('id', 'desc')->first();
                        }
                    }

                    if ($voucher) {
                        $voucher->settlement_no = $settlement_no_str;
                        $voucher->save();
                        $creditSaleUpdate['daily_voucher_id'] = $voucher->id;
                    }

                    app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                        ->editCreditSale($business_id, $item->id, $creditSaleUpdate);
                }
            }
        }

    }

    public function addDailyCheques($settlement_no, $pump_operator_id, $business_id, array $shift_ids = [])
    {

        $has_settlement_cheque_pump_payment_column = $this->tableHasColumn('settlement_cheque_payments', 'pump_payment_id');
        $settlement = Settlement::where('business_id', $business_id)
            ->where('id', $settlement_no)
            ->first();
        $settlement_keys = array_filter([
            (string) $settlement_no,
            ! empty($settlement) ? (string) $settlement->settlement_no : null,
        ]);

        $pending = DailyChequePayment::join('pump_operator_payments', 'pump_operator_payments.id', 'daily_cheque_payments.linked_payment_id')
            ->where('daily_cheque_payments.business_id', $business_id)
            ->where('pump_operator_payments.business_id', $business_id)
            ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
            ->where('pump_operator_payments.payment_type', 'cheque')
            ->select('daily_cheque_payments.*')
            ->when(! empty($shift_ids), function ($query) use ($shift_ids) {
                $query->whereIn('daily_cheque_payments.shift_id', $shift_ids);
            })
            ->get();

        foreach ($pending as $one) {
            if (! empty($one->settlement_no) && ! in_array((string) $one->settlement_no, $settlement_keys, true)) {
                $finalized_owner = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where(function ($q) use ($one) {
                        $q->where('id', $one->settlement_no)
                            ->orWhere('settlement_no', $one->settlement_no);
                    })
                    ->exists();

                if ($finalized_owner) {
                    continue;
                }
            }

            $duplicate_query = SettlementChequePayment::where('business_id', $business_id);

            $duplicate_query->where(function ($q) use ($one, $has_settlement_cheque_pump_payment_column) {
                if ($has_settlement_cheque_pump_payment_column && ! empty($one->linked_payment_id)) {
                    $q->where('pump_payment_id', $one->linked_payment_id)
                        ->orWhere(function ($field_query) use ($one) {
                            $field_query->where('customer_id', $one->customer_id)
                                ->where('amount', $one->amount)
                                ->where('bank_name', $one->bank_name)
                                ->where('cheque_number', $one->cheque_number)
                                ->where('cheque_date', $one->cheque_date);
                        });
                } else {
                    $q->where('customer_id', $one->customer_id)
                        ->where('amount', $one->amount)
                        ->where('bank_name', $one->bank_name)
                        ->where('cheque_number', $one->cheque_number)
                        ->where('cheque_date', $one->cheque_date);
                }
            });

            $settlement_cheque_payment = $duplicate_query->first();

            if (! empty($settlement_cheque_payment)) {
                if (! in_array((string) $settlement_cheque_payment->settlement_no, $settlement_keys, true)) {
                    $finalized_owner = Settlement::where('business_id', $business_id)
                        ->where('status', 0)
                        ->where(function ($q) use ($settlement_cheque_payment) {
                            $q->where('id', $settlement_cheque_payment->settlement_no)
                                ->orWhere('settlement_no', $settlement_cheque_payment->settlement_no);
                        })
                        ->exists();

                    if ($finalized_owner) {
                        continue;
                    }
                }

                $settlement_cheque_payment->settlement_no = $settlement_no;

                if (
                    $has_settlement_cheque_pump_payment_column
                    && empty($settlement_cheque_payment->pump_payment_id)
                    && ! empty($one->linked_payment_id)
                ) {
                    $settlement_cheque_payment->pump_payment_id = $one->linked_payment_id;
                }

                $settlement_cheque_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                    ->editChequePayment($business_id, $settlement_cheque_payment->id, $settlement_cheque_payment->getAttributes());

                $one->settlement_no = $settlement_no;
                $one->save();

                $pmt = PumpOperatorPayment::find($one->linked_payment_id);
                if (! empty($pmt)) {
                    $pmt->is_used = 1;
                    $pmt->parent_id = $settlement_cheque_payment->id;
                    $pmt->settlement_no = $settlement_no;
                    $pmt->save();
                }

                continue;
            }

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement_no,

                'amount'        => $one->amount,

                'bank_name'     => $one->bank_name,

                'cheque_number' => $one->cheque_number,

                'cheque_date'   => $one->cheque_date,

                'customer_id'   => $one->customer_id,

            ];

            if ($has_settlement_cheque_pump_payment_column) {
                $data['pump_payment_id'] = $one->linked_payment_id;
            }

            $settlement_cheque_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement_no, 'settlement_cheque_payments', $data);

            $pmt = PumpOperatorPayment::findOrFail($one->linked_payment_id);

            $pmt->is_used = 1;

            $pmt->parent_id = $settlement_cheque_payment->id;

            $pmt->settlement_no = $settlement_no;

            $pmt->save();

            $one->settlement_no = $settlement_no;

            $one->save();

        }

        return true;

    }

    public function addDailyShortageExcess($settlement_no, $pump_operator_id, $business_id, $shift_ids)
    {
        // Handle both string and array input for shift_ids
        if (is_string($shift_ids)) {
            $shift_Ids = explode(',', $shift_ids);
        } elseif (is_array($shift_ids)) {
            $shift_Ids = $shift_ids;
        } else {
            // If it's neither string nor array, log and return
            \Log::error('Invalid shift_ids type provided', [
                'shift_ids' => $shift_ids,
                'type'      => gettype($shift_ids),
            ]);

            return;
        }

        // Ensure all shift IDs are integers
        $shift_Ids = array_map('intval', $shift_Ids);

        // Remove any empty values
        $shift_Ids = array_filter($shift_Ids);

        // If no valid shift IDs, return early
        if (empty($shift_Ids)) {
            \Log::warning('No valid shift IDs provided for daily shortage/excess', [
                'pump_operator_id' => $pump_operator_id,
                'business_id'      => $business_id,
            ]);

            return;
        }

        $daily_shortage_excess = PumpOperatorPayment::where('business_id', $business_id)
            ->whereIn('shift_id', $shift_Ids)
            ->where('pump_operator_id', $pump_operator_id)
            ->where(function ($query) {
                $query->whereNull('is_used')->orWhere('is_used', 0);
            })
            ->whereIn('payment_type', ['shortage', 'excess'])
            ->get();

        $settlement    = Settlement::findOrFail($settlement_no);
        $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);

        if (! empty($daily_shortage_excess)) {
            foreach ($daily_shortage_excess as $payment) {
                $shortage_excess = PumpOperatorPayment::findOrFail($payment->id);

                if ($payment->payment_type == 'shortage') {
                    $data = [
                        'business_id'      => $business_id,
                        'settlement_no'    => $settlement->id,
                        'amount'           => $payment->payment_amount,
                        'current_shortage' => $pump_operator->short_amount,
                    ];
                    $parent_payment = SettlementShortagePayment::create($data);
                }

                if ($payment->payment_type == 'excess') {
                    $data = [
                        'business_id'    => $business_id,
                        'settlement_no'  => $settlement->id,
                        'amount'         => $payment->payment_amount,
                        'current_excess' => $pump_operator->excess_amount,
                    ];

                    // Skip if amount is greater than 0 (this condition seems odd, you might want to review it)
                    if (request()->amount > 0) {
                        continue;
                    }

                    $parent_payment = SettlementExcessPayment::create($data);
                }

                // Update the pump operator payment record
                $shortage_excess->is_used       = 1;
                $shortage_excess->parent_id     = $parent_payment->id;
                $shortage_excess->settlement_no = $settlement_no;
                $shortage_excess->save();
            }
        }
    }

    public function createSettlementIfNotExist(Request $request)
    {

        $business_id = $request->session()->get('business.id');
        $pump_operator_id = $request->operator_id ?: $request->pump_operator_id;
        $active_settlement_id = (int) $request->input('active_settlement_id', 0);
        $settlement_no = trim((string) ($request->settlement_no ?? ''));

        // ZIP057_ACTIVE_SETTLEMENT_EARLY_RETURN

        if ($active_settlement_id > 0) {
            $active_settlement = Settlement::where('id', $active_settlement_id)
                ->where('business_id', $business_id)
                ->first();

            if (! empty($active_settlement)) {
                return $active_settlement;
            }
        }
        $transaction_date = \Carbon::parse($request->transaction_date)->format('Y-m-d');

        // Identify if Petro PD request
        $isPetroPdRequest = false;
        $source = (string) $request->input('source', '');
        if (in_array($source, ['petro_pd', 'petropd'], true)) {
            $isPetroPdRequest = true;
        } else {
            $refererPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);
            if (is_string($refererPath) && \Illuminate\Support\Str::startsWith($refererPath, '/petropd')) {
                $isPetroPdRequest = true;
            }
        }

        // Identify Petro PD prefixes
        $petro_pd_prefixes = ['PDST'];
        $business = \App\Business::find($business_id);
        $ref_no_prefixes = $business->ref_no_prefixes ?? [];
        if (is_string($ref_no_prefixes)) {
            $ref_no_prefixes = json_decode($ref_no_prefixes, true) ?? [];
        }
        $pdPrefix = $ref_no_prefixes['settlement_pd'] ?? null;
        $petroPrefix = $ref_no_prefixes['settlement'] ?? 'ST';
        if (! empty($pdPrefix) && $pdPrefix !== $petroPrefix) {
            $petro_pd_prefixes[] = $pdPrefix;
        }
        $petro_pd_prefixes = array_values(array_unique(array_filter($petro_pd_prefixes)));

        // Validate prefix
        $is_valid_pd_settlement_no = false;
        if (! empty($settlement_no)) {
            foreach ($petro_pd_prefixes as $pref) {
                if (\Illuminate\Support\Str::startsWith($settlement_no, $pref)) {
                    $is_valid_pd_settlement_no = true;
                    break;
                }
            }
        }

        if ($isPetroPdRequest && ! $is_valid_pd_settlement_no) {
            $settlement_no = '';
            $request->merge(['settlement_no' => '']);
        }

        if ($active_settlement_id > 0) {
            $active_settlement = Settlement::where('id', $active_settlement_id)
                ->where('business_id', $business_id)
                ->first();

            if (! empty($active_settlement)) {
                $active_is_pd = false;
                foreach ($petro_pd_prefixes as $pref) {
                    if (\Illuminate\Support\Str::startsWith($active_settlement->settlement_no, $pref)) {
                        $active_is_pd = true;
                        break;
                    }
                }
                if ($isPetroPdRequest === $active_is_pd) {
                    $request->merge(['settlement_no' => $active_settlement->settlement_no]);
                    return $active_settlement;
                }
            }
        }

        if (empty($settlement_no)) {
            $existing_draft_query = Settlement::where('business_id', $business_id)
                ->when(!empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                    $query->where('pump_operator_id', $pump_operator_id);
                })
                ->when(!empty($request->location_id), function ($query) use ($request) {
                    $query->where('location_id', $request->location_id);
                })
                ->where('status', 1)
                ->whereDate('transaction_date', $transaction_date);

            if ($isPetroPdRequest) {
                $existing_draft_query->where(function ($q) use ($petro_pd_prefixes) {
                    foreach ($petro_pd_prefixes as $pref) {
                        $q->orWhere('settlement_no', 'LIKE', $pref . '%');
                    }
                });
            } else {
                $existing_draft_query->where('settlement_no', 'NOT LIKE', 'SET-SW%');
                foreach ($petro_pd_prefixes as $pref) {
                    $existing_draft_query->where('settlement_no', 'NOT LIKE', $pref . '%');
                }
            }

            $existing_draft = $existing_draft_query->orderByDesc('id')->first();

            if (!empty($existing_draft) && !empty($existing_draft->settlement_no)) {
                $settlement_no = $existing_draft->settlement_no;
            } else {
                $prefix = $isPetroPdRequest
                    ? ($petro_pd_prefixes[0] ?? 'PDST')
                    : (!empty($ref_no_prefixes['settlement']) ? $ref_no_prefixes['settlement'] : 'SET-');

                $count_query = Settlement::where('business_id', $business_id)
                    ->where('settlement_no', 'LIKE', $prefix . '%');

                if ($isPetroPdRequest) {
                    $count_query->where(function ($q) use ($petro_pd_prefixes) {
                        foreach ($petro_pd_prefixes as $pref) {
                            $q->orWhere('settlement_no', 'LIKE', $pref . '%');
                        }
                    });
                } else {
                    $count_query->where('settlement_no', 'NOT LIKE', 'SET-SW%');
                    foreach ($petro_pd_prefixes as $pref) {
                        $count_query->where('settlement_no', 'NOT LIKE', $pref . '%');
                    }
                }

                $count = $count_query->orderBy('id', 'DESC')->first();

                $count = !empty($count) ? (int) $this->extractLastInteger($count->settlement_no) : 0;
                $settlement_no = $prefix . (1 + $count);
            }

            /*
             * IS1959: never hand out a settlement number that already exists.
             *
             * The number above is derived from the highest existing one and then
             * used without checking. Two saves running close together read the
             * same highest value and both write the same code, which is how ST1
             * came to exist twice (settlements id 1 and id 2).
             *
             * A duplicate is not cosmetic. Every screen that joins on
             * settlement_no then matches the wrong record: the Tank Transaction
             * Details testing lookup resolved ST1 to the first row it found and
             * reported 0.000 litres for a tank that had 4.000 recorded, because
             * the meter sale belonged to the other ST1.
             *
             * This walks forward to the first genuinely free number. The check is
             * scoped to the same business so separate businesses keep their own
             * sequences.
             */
            if (!empty($settlement_no)) {
                $guardAttempts = 0;

                while (Settlement::where('business_id', $business_id)
                        ->where('settlement_no', $settlement_no)
                        ->exists()) {

                    $count++;
                    $settlement_no = $prefix . (1 + $count);

                    // Defensive stop - never spin forever on unexpected data.
                    if (++$guardAttempts > 1000) {
                        \Log::error('IS1959: could not allocate a free settlement number', [
                            'business_id' => $business_id,
                            'prefix' => $prefix,
                            'last_tried' => $settlement_no,
                        ]);
                        break;
                    }
                }
            }

            $request->merge(['settlement_no' => $settlement_no]);
        }

        $settlement_data = [

            'settlement_no'    => $settlement_no,

            'business_id'      => $business_id,

            'transaction_date' => $transaction_date,

            'location_id'      => $request->location_id ?? '',

            'pump_operator_id' => $pump_operator_id,

            'work_shift'       => ! empty($request->work_shift) ? $request->work_shift : [],

            'note'             => $request->note ?? '',

            'status'           => 1,

        ];

        $latest_date = DayEnd::where('business_id', $business_id)->get()->last()->day_end_date ?? null;

        if (! empty($latest_date) && strtotime($latest_date) >= strtotime($settlement_data['transaction_date'])) {

            return 406;

        }

        $settlement_exist = Settlement::where('settlement_no', $settlement_no)->where('business_id', $business_id)->first();

        if (empty($settlement_exist)) {

            $settlement_exist = Settlement::create($settlement_data);

            // Only link daily collections for the specific shift(s) being settled
            $shift_ids = [];
            $shift_id_string = $request->shift_ids ?? $request->shift_id ?? null;
            if (!empty($shift_id_string)) {
                $shift_ids = is_array($shift_id_string)
                    ? $shift_id_string
                    : array_filter(array_map('trim', explode(',', $shift_id_string)));
            } elseif (!empty($request->work_shift)) {
                $work_shifts = is_array($request->work_shift)
                    ? $request->work_shift
                    : json_decode($request->work_shift, true);
                $shift_ids = is_array($work_shifts)
                    ? $work_shifts
                    : array_filter(array_map('trim', explode(',', $request->work_shift)));
            }
            $shift_ids = array_filter(array_map('intval', (array) $shift_ids));

            if (!empty($shift_ids)) {
            DailyCollection::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->whereNull('settlement_id')
                    ->whereIn('shift_id', $shift_ids)
                ->update(['settlement_id' => $settlement_exist->id]);
            } else {
                \Log::warning('AddPaymentController createSettlementIfNotExist: Missing shift_ids, skipping daily collection linking', [
                    'settlement_id' => $settlement_exist->id,
                    'settlement_no' => $settlement_exist->settlement_no,
                    'pump_operator_id' => $pump_operator_id,
                ]);
            }

            if (!empty($shift_ids)) {
                $daily_card_query = DailyCard::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->whereNull('settlement_no');

                if (Schema::hasColumn('daily_cards', 'shift_id')) {
                    $daily_card_query->whereIn('shift_id', $shift_ids);
                }

                $daily_card_query->update(['settlement_no' => $settlement_exist->settlement_no]);

                $daily_voucher_query = DailyVoucher::where('business_id', $business_id)
                    ->where('operator_id', $pump_operator_id)
                    ->whereNull('settlement_no');

                if (Schema::hasColumn('daily_vouchers', 'shift_id')) {
                    $daily_voucher_query->whereIn('shift_id', $shift_ids);
                }

                $daily_voucher_query->update(['settlement_no' => $settlement_exist->settlement_no]);
            } else {
                \Log::warning('AddPaymentController createSettlementIfNotExist: Missing shift_ids, skipping daily card/voucher linking', [
                    'settlement_id' => $settlement_exist->id,
                    'settlement_no' => $settlement_exist->settlement_no,
                    'pump_operator_id' => $pump_operator_id,
                ]);
            }

        }

        return $settlement_exist;

    }

    private function getClosedShiftIdsForSettlement(array $shift_ids, ?int $pump_operator_id = null): array
    {
        if (empty($shift_ids)) {
            return [];
        }

        $assignment_query = PumpOperatorAssignment::whereIn('shift_id', $shift_ids)
            ->where(function ($query) {
                $query->where('status', 'close')
                    ->orWhere('is_manually_closed', 1);
            });

        if (! empty($pump_operator_id)) {
            $assignment_query->where('pump_operator_id', $pump_operator_id);
        }

        $closed_by_assignment = $assignment_query->pluck('shift_id')->toArray();
        $closed_by_shift = PetroShift::whereIn('id', $shift_ids)
            ->where('status', 2)
            ->pluck('id')
            ->toArray();

        return array_values(array_unique(array_map('intval', array_merge($closed_by_assignment, $closed_by_shift))));
    }

    // public function create(Request $request)
    // {
    //     // Validate critical inputs first
    //     if (empty($request->settlement_no)) {
    //         return response()->json([
    //             'success' => false,
    //             'msg'     => 'Settlement number is required',
    //         ]);
    //     }

    //     if (empty($request->shift_ids)) {
    //         return response()->json([
    //             'success' => false,
    //             'msg'     => 'Shift ID is required',
    //         ]);
    //     }

    //     if (empty($request->operator_id)) {
    //         return response()->json([
    //             'success' => false,
    //             'msg'     => 'Pump operator ID is required',
    //         ]);
    //     }

    //     $business_id  = request()->session()->get('business.id');
    //     $business     = Business::where('id', $business_id)->first();
    //     $pos_settings = json_decode($business->pos_settings, true);
    //     $cash_denoms  = ! empty($pos_settings['cash_denominations']) ? explode(',', $pos_settings['cash_denominations']) : [];

    //     // Use consistent settlement identifier
    //     $request_settlement_no = $request->settlement_no;
    //     $provider              = $request->provider;
    //     $pump_operator_id      = $request->operator_id;
    //     $is_settlement_page    = isset($request->settlement_page) ? 1 : 0;

    //     // Find or create settlement
    //     $settlement = Settlement::where('settlement_no', $request_settlement_no)
    //         ->where('business_id', $business_id)
    //         ->first();

    //     if (! $settlement && ! $request->provider) {
    //         $settlement = $this->createSettlementIfNotExist($request);
    //     }

    //     if (! $settlement) {
    //         return response()->json([
    //             'success' => false,
    //             'msg'     => 'Settlement not found',
    //         ]);
    //     }

    //     // Use settlement ID for all internal references
    //     $settlement_id = $settlement->id;

    //     // Keep original settlement_no for display
    //     $display_settlement_no = $settlement->settlement_no;

    //     // Parse shift IDs properly
    //     $shift_ids = ! empty($request->shift_ids) ? explode(",", $request->shift_ids) : [];
    //     $shift_ids = array_map('intval', $shift_ids); // Convert to integers

    //     // Use work_shift from settlement if available, otherwise use request
    //     if (! empty($settlement->work_shift) && is_array($settlement->work_shift)) {
    //         $shift_ids_for_settlement = $settlement->work_shift;
    //     } else {
    //         $shift_ids_for_settlement = $shift_ids;
    //     }

    //     // For display purposes
    //     $show_shift_no = implode(", ", $shift_ids_for_settlement);

    //     // Debug logging
    //     Log::info('Settlement Data', [
    //         'request_settlement_no'    => $request_settlement_no,
    //         'settlement_id'            => $settlement_id,
    //         'display_settlement_no'    => $display_settlement_no,
    //         'shift_ids'                => $shift_ids,
    //         'shift_ids_for_settlement' => $shift_ids_for_settlement,
    //         'pump_operator_id'         => $pump_operator_id,
    //     ]);

    //     // Get payments with proper shift filtering
    //     $payments = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')
    //         ->whereIn('shift_id', $shift_ids_for_settlement)
    //         ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
    //         ->select(
    //             DB::raw('SUM(IF(payment_type="cash", payment_amount, 0)) as cash'),
    //             DB::raw('SUM(IF(payment_type="card", payment_amount, 0)) as card'),
    //             DB::raw('SUM(IF(payment_type="cheque", payment_amount, 0)) as cheque'),
    //             DB::raw('SUM(IF(payment_type="credit", payment_amount, 0)) as credit'),
    //             DB::raw('SUM(IF(payment_type="other", payment_amount, 0)) as other'),
    //             DB::raw('SUM(IF(payment_type="shortage" OR payment_type="excess", payment_amount, 0)) as shortage_excess'),
    //             DB::raw('SUM(payment_amount) as total')
    //         )->first();

    //     // Prefill the added card numbers in daily collection
    //     $this->addDailyCards($settlement_id, $pump_operator_id, $business_id, $shift_ids_for_settlement);
    //     $this->addDailyCheques($settlement_id, $pump_operator_id, $business_id);
    //     $this->addDailyShortageExcess($settlement_id, $pump_operator_id, $business_id, $shift_ids_for_settlement);

    //     $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();

    //     $business_locations = BusinessLocation::forDropdown($business_id);
    //     $default_location   = current(array_keys($business_locations->toArray()));
    //     $payment_types      = $this->productUtil->payment_types($default_location, false, false, false, false, "is_sale_enabled");

    //     $expense_no         = $this->getExpenseNumber($settlement_id);
    //     $expense_categories = ExpenseCategory::where('business_id', $business_id)
    //         ->pluck('name', 'id');

    //     $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();
    //     $expense_accounts        = [];

    //     if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account')) {
    //         if (! empty($expense_account_type_id)) {
    //             $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');
    //         }
    //     }

    //     $subscription    = Subscription::current_subscription($business_id);
    //     $package_details = $subscription->package_details;
    //     $only_walkin     = $package_details['only_walkin'] ?? 0;

    //     if (! empty($only_walkin)) {
    //         $credit_customers = Contact::customersDropdown($business_id, false, true, 'customer');
    //     } else {
    //         $credit_customers = Contact::where('name', '!=', 'Walk-In Customer')->where('active', 1)->where('type', 'customer')->where('business_id', $business_id)->pluck('name', 'id');
    //     }

    //     $customers = Contact::customersDropdown($business_id, false, true, 'customer');
    //     $walkin    = Contact::where('name', 'Walk-In Customer')->where('business_id', $business_id)->pluck('name', 'id');
    //     $products  = Product::where('business_id', $business_id)->forModule('petro_settlements')->pluck('name', 'id');

    //     $card_types = [];
    //     $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();

    //     if (! empty($card_group)) {
    //         $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
    //     }

    //     $customer_payments_tab = CustomerPayment::leftjoin('contacts', 'customer_payments.customer_id', 'contacts.id')
    //         ->where('customer_payments.settlement_no', $settlement_id)
    //         ->select('customer_payments.*', 'contacts.name as customer_name')
    //         ->get();

    //     // Fetch settlement cash payments with customer names
    //     $settlement_cash_payments2 = SettlementCashPayment::leftJoin('contacts', 'settlement_cash_payments.customer_id', '=', 'contacts.id')
    //         ->where('settlement_cash_payments.settlement_no', $settlement_id)
    //         ->select('settlement_cash_payments.*', 'contacts.name as customer_name')
    //         ->get();

    //     // Pump operator cash payments - Filter by settlement's work_shift
    //     $settlement_cash_payments1 = PumpOperatorPayment::whereIn('shift_id', $shift_ids_for_settlement)
    //         ->where('pump_operator_id', $pump_operator_id)
    //         ->where('payment_type', 'cash')
    //         ->where(function ($q) {
    //             $q->whereNull('is_used')->orWhere('is_used', 0);
    //         })
    //         ->select('payment_amount as amount', 'shift_id')
    //         ->get();

    //     // Convert into SettlementCashPayment models with proper settlement reference
    //     $newdatarecords = $settlement_cash_payments1->map(function ($payment) use ($settlement_id) {
    //         $record                      = new SettlementCashPayment();
    //         $record->id                  = null;
    //         $record->settlement_no       = $settlement_id; // Use actual settlement ID
    //         $record->business_id         = null;
    //         $record->customer_id         = null;
    //         $record->amount              = $payment->amount;
    //         $record->customer_payment_id = null;
    //         $record->note                = 'Auto-generated from pump operator payments';
    //         $record->slip                = '';
    //         $record->created_at          = now();
    //         $record->updated_at          = now();
    //         $record->customer_name       = 'Walk-in Customer';
    //         $record->shift_id            = $payment->shift_id; // Store shift info for traceability
    //         return $record;
    //     });

    //     // Combine both sets
    //     if ($settlement_cash_payments2->isNotEmpty()) {
    //         $settlement_cash_payments = $settlement_cash_payments2
    //             ->concat($newdatarecords)
    //             ->values();
    //     } else {
    //         $settlement_cash_payments = $newdatarecords;
    //     }

    //     $cash_total = $settlement_cash_payments->sum('amount');

    //     $settlement_customer_loans = SettlementCustomerLoan::leftjoin('contacts', 'settlement_customer_loans.customer_id', 'contacts.id')
    //         ->where('settlement_customer_loans.settlement_no', $settlement_id)
    //         ->select('settlement_customer_loans.*', 'contacts.name as customer_name')
    //         ->get();

    //     $settlement_loan_payments = SettlementLoanPayment::leftjoin('accounts', 'accounts.id', 'settlement_loan_payments.loan_account')
    //         ->where('settlement_loan_payments.settlement_no', $settlement_id)
    //         ->select('settlement_loan_payments.*', 'accounts.name as loan_account_name')
    //         ->get();

    //     $settlement_drawings_payments = SettlementDrawingPayment::leftjoin('accounts', 'accounts.id', 'settlement_drawing_payments.loan_account')
    //         ->where('settlement_drawing_payments.settlement_no', $settlement_id)
    //         ->select('settlement_drawing_payments.*', 'accounts.name as loan_account_name')
    //         ->get();

    //     // Actual settlement card payments
    //     $settlement_card_payments1 = SettlementCardPayment::query()
    //         ->leftJoin('contacts', 'settlement_card_payments.customer_id', '=', 'contacts.id')
    //         ->leftJoin('accounts', 'settlement_card_payments.card_type', '=', 'accounts.id')
    //         ->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
    //         ->where('settlement_card_payments.settlement_no', $settlement_id)
    //         ->select(
    //             'settlement_card_payments.*',
    //             'contacts.name as customer_name',
    //             'accounts.name as card_type_name',
    //             'daily_cards.pump_operator_id'
    //         )
    //         ->get();

    //     // Fetch walking payments from pump operator payments
    //     $walking_payments = SettlementCardPayment::query()
    //         ->leftJoin('contacts', 'settlement_card_payments.customer_id', '=', 'contacts.id')
    //         ->leftJoin('accounts', 'settlement_card_payments.card_type', '=', 'accounts.id')
    //         ->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
    //         ->join('pump_operator_payments as pop', function ($join) use ($shift_ids_for_settlement, $pump_operator_id) {
    //             $join->on('settlement_card_payments.business_id', '=', 'pop.business_id')
    //                 ->on('settlement_card_payments.amount', '=', 'pop.payment_amount')
    //                 ->whereIn('pop.shift_id', $shift_ids_for_settlement)
    //                 ->where('pop.pump_operator_id', $pump_operator_id)
    //                 ->where('pop.payment_type', 'card');
    //         })
    //         ->where('settlement_card_payments.settlement_no', $settlement_id)
    //         ->select(
    //             'settlement_card_payments.*',
    //             'contacts.name as customer_name',
    //             'accounts.name as card_type_name',
    //             'daily_cards.pump_operator_id',
    //             'pop.shift_id',
    //             'pop.payment_amount'
    //         )
    //         ->groupBy('settlement_card_payments.id')
    //         ->get();

    //     // Combine both collections - show ALL card payments (direct + pump operator)
    //     $settlement_card_payments = $settlement_card_payments1
    //         ->merge($walking_payments)
    //         ->unique('id')
    //         ->values();

    //     $settlement_cash_deposits = SettlementCashDeposit::leftjoin('accounts', 'settlement_cash_deposits.bank_id', 'accounts.id')
    //         ->where('settlement_cash_deposits.settlement_no', $settlement_id)
    //         ->select('settlement_cash_deposits.*', 'accounts.name as bank_name')
    //         ->get();

    //     $settlement_cheque_payments = SettlementChequePayment::leftjoin('contacts', 'settlement_cheque_payments.customer_id', 'contacts.id')
    //         ->where('settlement_cheque_payments.settlement_no', $settlement_id)
    //         ->select('settlement_cheque_payments.*', 'contacts.name as customer_name')
    //         ->get();

    //     $settlement_credit_sale_payments2 = DB::table('settlement_credit_sale_payments')
    //         ->whereExists(function ($query) use ($shift_ids_for_settlement, $pump_operator_id) {
    //             $query->select(DB::raw(1))
    //                 ->from('pump_operator_payments')
    //                 ->whereRaw('settlement_credit_sale_payments.pump_operator_id = pump_operator_payments.pump_operator_id')
    //                 ->whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement)
    //                 ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
    //                 ->where('pump_operator_payments.payment_type', 'credit')
    //                 ->whereRaw('settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_general_ci = pump_operator_payments.collection_form_no COLLATE utf8mb4_general_ci');
    //         })
    //         ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
    //         ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
    //         ->select(
    //             'settlement_credit_sale_payments.*',
    //             'contacts.name as customer_name',
    //             'products.name as product_name'
    //         )
    //         ->get();

    //     $settlement_credit_sale_payments1 = SettlementCreditSalePayment::query()
    //         ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
    //         ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
    //         ->leftJoin('daily_vouchers', 'settlement_credit_sale_payments.daily_voucher_id', '=', 'daily_vouchers.id')
    //         ->where('settlement_credit_sale_payments.settlement_no', $settlement_id)
    //         ->where('settlement_credit_sale_payments.pump_operator_id', $pump_operator_id)
    //         ->select(
    //             'settlement_credit_sale_payments.*',
    //             'contacts.name as customer_name',
    //             'products.name as product_name',
    //             'daily_vouchers.operator_id'
    //         )
    //         ->get();

    //     $settlement_credit_sale_payments = collect()
    //         ->merge($settlement_credit_sale_payments1 ?? collect())
    //         ->merge($settlement_credit_sale_payments2 ?? collect())
    //         ->unique(fn($item) => $item->id ?? null)
    //         ->values();

    //     $settlement_expense_payments = SettlementExpensePayment::leftjoin('accounts', 'settlement_expense_payments.account_id', 'accounts.id')
    //         ->leftjoin('expense_categories', 'settlement_expense_payments.category_id', 'expense_categories.id')
    //         ->where('settlement_expense_payments.settlement_no', $settlement_id)
    //         ->select('settlement_expense_payments.*', 'accounts.name as account_name', 'expense_categories.name as category_name')
    //         ->get();

    //     $settlement_shortage_payments = SettlementShortagePayment::where('settlement_shortage_payments.settlement_no', $settlement_id)
    //         ->select('settlement_shortage_payments.*')
    //         ->get();

    //     $settlement_excess_payments = SettlementExcessPayment::where('settlement_excess_payments.settlement_no', $settlement_id)
    //         ->select('settlement_excess_payments.*')
    //         ->get();

    //     $total_daily_collection = floatval(DailyCollection::where([
    //         'pump_operator_id' => $pump_operator_id,
    //         'shift_id'         => $request->shift_ids,
    //     ])->where('business_id', $business_id)->whereNull('settlement_id')->sum('current_amount'));

    //     $total_excess     = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'excess');
    //     $total_shortage   = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'shortage');
    //     $operator_bal     = $this->transactionUtil->getPumpOperatorBalance($pump_operator_id);
    //     $total_commission = $this->calculateCommission($pump_operator_id, $settlement_id);

    //     $business_details   = Business::find($business_id);
    //     $currency_precision = $business_details->currency_precision;

    //     $total_meter_sale = MeterSale::where('settlement_no', $settlement_id)
    //         ->whereIn('shift_id', $shift_ids_for_settlement)
    //         ->sum('discount_amount');

    //     $other_sales      = OtherSale::where('settlement_no', $settlement_id)->get();
    //     $total_other_sale = $other_sales->sum('sub_total') - $other_sales->sum('discount_amount');

    //     if (str_contains($display_settlement_no, 'SET-SW')) {
    //         $total_other_sale = $other_sales->sum('sub_total');
    //     }

    //     if (! empty($shift_ids_for_settlement)) {
    //         $pump_operator_total_other_sale = PumpOperatorOtherSale::join('products', 'products.id', '=', 'pump_operator_other_sales.product_id')
    //             ->leftJoin('variations', 'products.id', 'variations.product_id')
    //             ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
    //             ->whereIn('pump_operator_other_sales.shift_id', $shift_ids_for_settlement)
    //             ->join('pump_operator_assignments', function ($join) {
    //                 $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')
    //                     ->where('pump_operator_assignments.status', 'close')
    //                     ->whereRaw('pump_operator_assignments.id = (
    //                         SELECT MAX(poa.id)
    //                         FROM pump_operator_assignments poa
    //                         WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"
    //                     )');
    //             })
    //             ->sum("sub_total");

    //         $total_other_sale = $total_other_sale + $pump_operator_total_other_sale;
    //     }

    //     $total_other_income     = OtherIncome::where('settlement_no', $settlement_id)->sum('sub_total');
    //     $total_customer_payment = CustomerPayment::where('settlement_no', $settlement_id)->sum('sub_total');

    //     $total_amount = number_format(($total_meter_sale + $total_other_sale + $total_other_income + $total_customer_payment), $currency_precision, '.', '');

    //     $total_settlement_cash_payment         = SettlementCashPayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_loan_payment         = SettlementLoanPayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_drawings_payment     = SettlementDrawingPayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_cash_deposit         = SettlementCashDeposit::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_card_payment         = $settlement_card_payments->sum('amount');
    //     $total_settlement_customer_loan        = SettlementCustomerLoan::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_cheque_payment       = SettlementChequePayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_credit_sale_payment  = $settlement_credit_sale_payments->sum('amount');
    //     $total_settlement_credit_sale_discount = $settlement_credit_sale_payments->sum('total_discount');
    //     $total_settlement_expense_payment      = SettlementExpensePayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_shortage_payment     = SettlementShortagePayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_excess_payment       = SettlementExcessPayment::where('settlement_no', $settlement_id)->sum('amount');

    //     $total_paid = number_format((
    //         $total_settlement_customer_loan +
    //         $total_settlement_loan_payment +
    //         $total_settlement_cash_deposit +
    //         $total_daily_collection +
    //         $total_settlement_cash_payment +
    //         $total_settlement_card_payment +
    //         $total_settlement_cheque_payment +
    //         $total_settlement_credit_sale_payment - $total_settlement_credit_sale_discount +
    //         $total_settlement_expense_payment +
    //         $total_settlement_shortage_payment +
    //         $total_settlement_excess_payment +
    //         $total_settlement_drawings_payment
    //     ), $currency_precision, '.', '');

    //     $total_balance = number_format($total_amount - $total_paid, $currency_precision, '.', '');
    //     $total_balance = abs($total_balance);

    //     $loans_given_group_id  = AccountGroup::getGroupByName('Loans Given');
    //     $drawings_group_id     = AccountGroup::getGroupByName('Owners Drawings');
    //     $bank_account_group_id = AccountGroup::getGroupByName('Bank Account');

    //     $bank_accounts = Account::where('business_id', $business_id)->where('asset_type', $bank_account_group_id->id)->pluck('name', 'id');

    //     if (! empty($loans_given_group_id)) {
    //         $loans_given = Account::where('business_id', $business_id)->where('asset_type', $loans_given_group_id->id)->pluck('name', 'id');
    //     } else {
    //         $loans_given = [];
    //     }

    //     if (! empty($drawings_group_id)) {
    //         $drawings_acc = Account::where('business_id', $business_id)->where('asset_type', $drawings_group_id->id)->pluck('name', 'id');
    //     } else {
    //         $drawings_acc = [];
    //     }

    //     $message          = $package_details['notsubscribed_message_content'] ?? "You have not subscribed to this module!";
    //     $font_family      = $package_details['ns_font_family'] ?? '';
    //     $font_color       = $package_details['ns_font_color'] ?? '';
    //     $font_size        = $package_details['ns_font_size'] ?? '';
    //     $background_color = $package_details['ns_background_color'] ?? '';

    //     // Use display settlement number for the view
    //     $settlement->settlement_no = $display_settlement_no;

    //     if ($request->type == 'settlement_pd') {
    //         return view('petrogeneral::settlement_pd.partials.add_payment')->with(compact(
    //             'operator_bal',
    //             'business',
    //             'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',
    //             'bank_accounts',
    //             'is_settlement_page',
    //             'total_settlement_cash_deposit',
    //             'settlement',
    //             'cash_denoms',
    //             'pump_operator',
    //             'customer_payments_tab',
    //             'settlement_cash_payments',
    //             'settlement_customer_loans',
    //             'cash_total',
    //             'settlement_loan_payments',
    //             'settlement_drawings_payments',
    //             'settlement_card_payments',
    //             'settlement_cheque_payments',
    //             'settlement_credit_sale_payments',
    //             'settlement_expense_payments',
    //             'settlement_shortage_payments',
    //             'settlement_excess_payments',
    //             'settlement_cash_deposits',
    //             'payment_types',
    //             'expense_accounts',
    //             'expense_categories',
    //             'expense_no',
    //             'customers',
    //             'products',
    //             'card_types',
    //             'total_daily_collection',
    //             'total_commission',
    //             'total_amount',
    //             'total_paid',
    //             'total_balance',
    //             'total_excess',
    //             'total_shortage',
    //             'loans_given',
    //             'drawings_acc',
    //             'only_walkin',
    //             'walkin',
    //             'provider',
    //             'credit_customers',
    //             'show_shift_no'
    //         ));
    //     }

    //     return view('petrogeneral::settlement.partials.add_payment')->with(compact(
    //         'operator_bal',
    //         'business',
    //         'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',
    //         'bank_accounts',
    //         'is_settlement_page',
    //         'total_settlement_cash_deposit',
    //         'settlement',
    //         'cash_denoms',
    //         'pump_operator',
    //         'customer_payments_tab',
    //         'settlement_cash_payments',
    //         'cash_total',
    //         'settlement_customer_loans',
    //         'settlement_loan_payments',
    //         'settlement_drawings_payments',
    //         'settlement_card_payments',
    //         'settlement_cheque_payments',
    //         'settlement_credit_sale_payments',
    //         'settlement_expense_payments',
    //         'settlement_shortage_payments',
    //         'settlement_excess_payments',
    //         'settlement_cash_deposits',
    //         'payment_types',
    //         'expense_accounts',
    //         'expense_categories',
    //         'expense_no',
    //         'customers',
    //         'products',
    //         'card_types',
    //         'total_daily_collection',
    //         'total_commission',
    //         'total_amount',
    //         'total_paid',
    //         'total_balance',
    //         'total_excess',
    //         'total_shortage',
    //         'loans_given',
    //         'drawings_acc',
    //         'only_walkin',
    //         'walkin',
    //         'provider',
    //         'credit_customers',
    //         'show_shift_no'
    //     ));
    // }

    public function create(Request $request)
    {

        $business_id = request()->session()->get('business.id');

        $business = Business::where('id', $business_id)->first();

        $pos_settings = json_decode($business->pos_settings, true);

        $cash_denoms = ! empty($pos_settings['cash_denominations']) ? explode(',', $pos_settings['cash_denominations']) : [];

        $settlement_no = $request->settlement_no;

        $provider = $request->provider;

        $no_change = $request->boolean('no_change') || ($request->type === 'settlement_pd');

        /*
        |--------------------------------------------------------------------------
        | PetroPD Settlement Number Safety
        |--------------------------------------------------------------------------
        | The same Petro AddPaymentController is used by both Direct Petro Settlement
        | and PetroPD Settlement. Direct settlement numbers use ST, while PetroPD
        | settlement numbers must use PDST.
        |
        | If a PetroPD payment-finalize request reaches this controller with an old
        | ST value, do not reuse that Direct Settlement. Clear it so the PDST draft
        | is created/loaded correctly. This prevents the Payment to Finalize modal
        | from showing ST instead of PDST.
        */
        $isPetroPdPaymentRequest = ($request->type === 'settlement_pd')
            || in_array((string) $request->input('source'), ['petro_pd', 'petropd'], true);

        $petroPdPrefixesForPayment = ['PDST'];
        $refNoPrefixesForPayment = request()->session()->get('business.ref_no_prefixes');
        if (is_string($refNoPrefixesForPayment)) {
            $decodedPrefixesForPayment = json_decode($refNoPrefixesForPayment, true);
            $refNoPrefixesForPayment = is_array($decodedPrefixesForPayment) ? $decodedPrefixesForPayment : [];
        }
        if (! empty($refNoPrefixesForPayment['settlement_pd'])) {
            $petroPdPrefixesForPayment[] = $refNoPrefixesForPayment['settlement_pd'];
        }
        $petroPdPrefixesForPayment = array_values(array_unique(array_filter($petroPdPrefixesForPayment)));

        $isPetroPdSettlementNumber = function ($value) use ($petroPdPrefixesForPayment) {
            $value = (string) $value;
            foreach ($petroPdPrefixesForPayment as $prefix) {
                if (Str::startsWith($value, $prefix)) {
                    return true;
                }
            }
            return false;
        };

        if ($isPetroPdPaymentRequest && ! empty($settlement_no) && ! $isPetroPdSettlementNumber($settlement_no)) {
            $settlement_no = '';
            $request->merge(['settlement_no' => '']);
        }

        $pump_operator_id = $request->operator_id ?: $request->pump_operator_id;

        $is_settlement_page = isset($request->settlement_page) ? 1 : 0;

        $active_settlement_id = (int) $request->input('active_settlement_id', 0);
        $settlement = null;

        if ($active_settlement_id > 0) {
            $settlement = Settlement::where('id', $active_settlement_id)
                ->where('business_id', $business_id)
                ->first();

            if (! empty($settlement)) {
                if ($isPetroPdPaymentRequest && ! $isPetroPdSettlementNumber($settlement->settlement_no)) {
                    // A Direct ST settlement must never be reused inside PetroPD Payment to Finalize.
                    $settlement = null;
                    $settlement_no = '';
                    $request->merge(['settlement_no' => '']);
                } else {
                    /*
                     * ZIP 057:
                     * Always reuse the active unsaved settlement after page refresh.
                     * This keeps previously entered payment tab details visible until
                     * the settlement is finally saved/finalized.
                     */
                    $settlement_no = $settlement->settlement_no;
                    $request->merge([
                        'settlement_no' => $settlement_no,
                        'active_settlement_id' => $settlement->id,
                    ]);
                }
            }
        }

        if (empty($settlement)) {
            $settlement = Settlement::where('settlement_no', $settlement_no)->where('business_id', $business_id)->first();
        }

        // CRITICAL: If settlement exists but pump_operator_id doesn't match, create a new settlement
        // This prevents loading old settlements for different pump operators
        if ($active_settlement_id === 0 && $settlement && !empty($pump_operator_id) && $settlement->pump_operator_id != $pump_operator_id) {
            \Log::warning('AddPaymentController@create - Settlement pump_operator_id mismatch, creating new settlement', [
                'existing_settlement_id' => $settlement->id,
                'existing_settlement_no' => $settlement->settlement_no,
                'existing_pump_operator_id' => $settlement->pump_operator_id,
                'requested_pump_operator_id' => $pump_operator_id,
            ]);
            $settlement = null; // Force creation of new settlement
        }

        // Resolve requested target shift IDs
        $check_shift_ids = [];
        $check_shift_str = $request->shift_ids ?? $request->query('shift_ids') ?? $request->input('shift_ids') ?? null;
        if (! empty($check_shift_str)) {
            if (is_array($check_shift_str)) {
                $check_shift_ids = $check_shift_str;
            } else {
                $check_shift_ids = array_filter(array_map('trim', explode(',', $check_shift_str)));
            }
            $check_shift_ids = array_values(array_filter(array_map('intval', $check_shift_ids)));
        }

        if ($request->type === 'settlement_pd' && ! empty($check_shift_ids)) {
            $mismatch = false;
            if ($settlement) {
                $settlement_shifts = $this->getSettlementWorkShiftIds($settlement);
                sort($settlement_shifts);
                $temp_check = $check_shift_ids;
                sort($temp_check);
                if ($settlement_shifts !== $temp_check) {
                    $mismatch = true;
                }
            } else {
                $mismatch = true;
            }

            if ($mismatch) {
                // Try to find if a settlement for these exact shift IDs already exists
                $existing_settlement = null;
                if (! empty($pump_operator_id)) {
                    $possible_settlements_query = Settlement::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id);

                    if ($isPetroPdPaymentRequest) {
                        $possible_settlements_query->where(function ($prefixQuery) use ($petroPdPrefixesForPayment) {
                            foreach ($petroPdPrefixesForPayment as $prefix) {
                                $prefixQuery->orWhere('settlement_no', 'LIKE', $prefix . '%');
                            }
                        });
                    }

                    $possible_settlements = $possible_settlements_query->get();
                    foreach ($possible_settlements as $ps) {
                        $ps_shifts = $this->getSettlementWorkShiftIds($ps);
                        sort($ps_shifts);
                        $temp_check = $check_shift_ids;
                        sort($temp_check);
                        if ($ps_shifts === $temp_check) {
                            $existing_settlement = $ps;
                            break;
                        }
                    }
                }

                if ($existing_settlement) {
                    $settlement = $existing_settlement;
                    $settlement_no = $settlement->settlement_no;
                    $request->merge(['settlement_no' => $settlement_no]);
                } else {
                    // Generate a new unique settlement number
                    $ref_no_prefixes = request()->session()->get('business.ref_no_prefixes');
                    if (is_string($ref_no_prefixes)) {
                        $decoded = json_decode($ref_no_prefixes, true);
                        $ref_no_prefixes = is_array($decoded) ? $decoded : [];
                    }
                    $prefix = ! empty($ref_no_prefixes['settlement_pd']) ? $ref_no_prefixes['settlement_pd'] : 'PDST';

                    $last_settlement = Settlement::where('business_id', $business_id)
                        ->where('settlement_no', 'LIKE', $prefix . '%')
                        ->orderBy('id', 'DESC')
                        ->first();
                    $count = 0;
                    if (! empty($last_settlement)) {
                        preg_match('/(\d+)$/', $last_settlement->settlement_no, $matches);
                        $count = ! empty($matches[1]) ? (int) $matches[1] : 0;
                    }
                    $new_settlement_no = $prefix . ($count + 1);
                    while (Settlement::where('business_id', $business_id)->where('settlement_no', $new_settlement_no)->exists()) {
                        $count++;
                        $new_settlement_no = $prefix . ($count + 1);
                    }

                    $settlement_no = $new_settlement_no;
                    $request->merge([
                        'settlement_no' => $settlement_no,
                        'work_shift' => $check_shift_ids
                    ]);
                    $settlement = null;
                }
            }
        }

        if (! isset($settlement) && ! $request->provider) {

            $settlement = $this->createSettlementIfNotExist($request);

        }

        // Check if settlement creation failed (returns 406 or null)
        if (! $settlement || (is_numeric($settlement) && $settlement == 406)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'msg' => __('petrogeneral::lang.settlement_date_before_day_end'),
                ], 406);
            }
            return redirect()->back()->with('error', __('petrogeneral::lang.settlement_date_before_day_end'));
        }

        // For settlement_pd type, get pump_operator_id from settlement if not provided in request
        if ($request->type == 'settlement_pd') {
            // If pump_operator_id is not in request, try to get it from settlement
            if (empty($pump_operator_id) && isset($settlement) && !empty($settlement->pump_operator_id)) {
                $pump_operator_id = $settlement->pump_operator_id;
            }

            // Also try to get from request parameter 'pump_operator_id' (alternative name)
            if (empty($pump_operator_id) && $request->has('pump_operator_id')) {
                $pump_operator_id = $request->pump_operator_id;
            }
        }

        // 758 add
        $shift_ids       = [];
        // Try multiple ways to get shift_ids from request
        $shift_id_string = $request->shift_ids ?? $request->query('shift_ids') ?? $request->input('shift_ids') ?? null;

        if (! empty($shift_id_string)) {
            if (is_array($shift_id_string)) {
                $shift_ids = $shift_id_string;
            } else {
                $shift_ids = array_filter(array_map('trim', explode(',', $shift_id_string)));
            }
            // Convert to integers and filter out invalid values
            $shift_ids = array_filter(array_map('intval', $shift_ids));
            $shift_ids = array_values($shift_ids); // Re-index array
        }

        $requested_shift_ids = $shift_ids;
        $is_direct_shift_placeholder = false;
        $raw_shift_ids_for_direct_check = $request->shift_ids ?? $request->query('shift_ids') ?? $request->input('shift_ids') ?? null;
        if ($raw_shift_ids_for_direct_check !== null && $raw_shift_ids_for_direct_check !== '') {
            $raw_shift_values = is_array($raw_shift_ids_for_direct_check)
                ? $raw_shift_ids_for_direct_check
                : array_map('trim', explode(',', $raw_shift_ids_for_direct_check));

            $is_direct_shift_placeholder = in_array('0', array_map('strval', $raw_shift_values), true);
        }

        if ($request->type === 'settlement_pd' && ! empty($requested_shift_ids)) {
            $shift_ids = $this->getClosedShiftIdsForSettlement($requested_shift_ids, ! empty($pump_operator_id) ? (int) $pump_operator_id : null);
            if (empty($shift_ids)) {
                $shift_ids = [0];
            }
            $shift_id_string = implode(',', $shift_ids);
        }

        // For settlement_pd, if shift_ids not provided, try to get from settlement's work_shift
        if ($request->type === 'settlement_pd' && empty($shift_ids) && !empty($settlement)) {
            $work_shifts = $settlement->work_shift ?? null;
            if (!empty($work_shifts)) {
                if (is_array($work_shifts)) {
                    $shift_ids = array_filter(array_map('intval', $work_shifts));
                    $shift_ids = array_values($shift_ids);
                } else {
                    $shift_ids = [intval($work_shifts)];
                }
            }
        }

        // Debug logging for settlement_pd (moved after settlement_id is defined)

        $payments = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

            ->select(

            DB::raw('SUM(IF(payment_type="cash", payment_amount, 0)) as cash'),

            DB::raw('SUM(IF(payment_type="card", payment_amount, 0)) as card'),

            DB::raw('SUM(IF(payment_type="cheque", payment_amount, 0)) as cheque'),

            DB::raw('SUM(IF(payment_type="credit", payment_amount, 0)) as credit'),

            DB::raw('SUM(IF(payment_type="other", payment_amount, 0)) as other'),

            DB::raw('SUM(IF(payment_type="shortage" OR payment_type="excess", payment_amount, 0)) as shortage_excess'),

            DB::raw('SUM(payment_amount) as total')

        );

        if (! empty($pump_operator_id)) {
        $payments->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
        }
        // 793 add
        if (! empty($shift_ids)) {
            $payments->whereIn('pump_operator_payments.shift_id', $shift_ids);
        } elseif (! empty($shift_id_string)) {
            // fallback if shift ids is a single id string
            $payments->where('pump_operator_payments.shift_id', $shift_id_string);
        }

        $payments = $payments->first();

        // prefill the added card nummbers in daily collection
        // $shift_id =$request->shift_ids;
        // comment ine 804 and use
        $shift_id = ! empty($shift_ids) ? implode(',', $shift_ids) : $shift_id_string;

        // Use settlement ID for internal operations (methods expect settlement ID, not settlement_no string)
        $settlement_id = $settlement->id;


        // Only call these methods if pump_operator_id is available
        if (!empty($pump_operator_id)) {
            // Only call addDailyCards if shift_id is not empty
            if (!empty($shift_id)) {
                $this->addDailyCards($settlement_id, $pump_operator_id, $business_id, $shift_id);
            }

            $this->addDailyCheques($settlement_id, $pump_operator_id, $business_id, $shift_ids);

            // Only call addDailyShortageExcess if shift_ids is not empty
            if (!empty($request->shift_ids) || !empty($shift_ids)) {
                $this->addDailyShortageExcess($settlement_id, $pump_operator_id, $business_id, $request->shift_ids ?? $shift_ids);
            }

        $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();
        } else {
            $pump_operator = null;
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        $payment_types = $this->productUtil->payment_types($default_location, false, false, false, false, 'is_sale_enabled');

        $expense_no = $this->getExpenseNumber($settlement_id);

        $expense_categories = ExpenseCategory::where('business_id', $business_id)

            ->pluck('name', 'id');

        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();

        $expense_accounts = [];

        if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account')) {

            if (! empty($expense_account_type_id)) {

                $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');

            }

        }

        $subscription = Subscription::current_subscription($business_id);

        $package_details = $subscription->package_details;

        $only_walkin = $package_details['only_walkin'] ?? 0;

        if (! empty($only_walkin)) {

            $credit_customers = Contact::customersDropdown($business_id, false, true, 'customer');

        } else {

            $credit_customers = Contact::where('name', '!=', 'Walk-In Customer')->where('active', 1)->where('type', 'customer')->where('business_id', $business_id)->pluck('name', 'id');

        }

        $customers = Contact::customersDropdown($business_id, false, true, 'customer');

        $walkin = Contact::where('name', 'Walk-In Customer')->where('business_id', $business_id)->pluck('name', 'id');

        $products = Product::where('business_id', $business_id)->forModule('petro_settlements')->pluck('name', 'id');

        $card_types = [];

        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();

        if (! empty($card_group)) {

            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');

        }

        // dd($settlement->id)
        $customer_payments_tab = CustomerPayment::leftjoin('contacts', 'customer_payments.customer_id', 'contacts.id')

            ->where('customer_payments.settlement_no', $settlement_id)

            ->select('customer_payments.*', 'contacts.name as customer_name')

            ->get();
        // comment 904
        //  $shift_id = $request->shift_ids;
        $settlement_no = $settlement_id;
        // dd($settlement->id);

        // CRITICAL: For settlement_pd, ALWAYS use shift_ids from REQUEST, NOT from settlement's work_shift
        // The settlement's work_shift may contain old shifts that shouldn't be shown
        // We MUST filter by the shift(s) the user actually selected in the UI
        $work_shifts = $settlement->work_shift ?? null;
        $shift_ids_for_settlement = [];

        // PRIORITY 1: Use shift_ids from request (this is what the user selected)
        if (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($shift_ids)) {
            $shift_ids_for_settlement = $shift_ids;
        }
        // PRIORITY 2: Parse shift_id_string from request
        elseif (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($shift_id_string)) {
            $parsed = array_filter(array_map('trim', explode(',', $shift_id_string)));
            if (!empty($parsed)) {
                $shift_ids_for_settlement = array_map('intval', $parsed);
                $shift_ids_for_settlement = array_filter($shift_ids_for_settlement);
            }
        }
        // PRIORITY 3: Fallback to work_shift ONLY if request doesn't have shift_ids
        // CRITICAL: If work_shift contains multiple shifts, use ONLY the LAST one (most recent)
        // This prevents showing payments from old shifts that shouldn't be included
        elseif (!empty($work_shifts) && is_array($work_shifts)) {
            $all_work_shifts = array_filter(array_map('intval', $work_shifts));
            // If multiple shifts, use only the LAST one (assuming they're added in chronological order)
            if (count($all_work_shifts) > 1) {
                $shift_ids_for_settlement = [end($all_work_shifts)]; // Use only the last/most recent shift
                \Log::warning('AddPaymentController@create - Multiple shifts in work_shift, using only most recent', [
                    'all_work_shifts' => $all_work_shifts,
                    'using_shift_id' => $shift_ids_for_settlement[0],
                ]);
            } else {
                $shift_ids_for_settlement = $all_work_shifts;
            }
        }
        // PRIORITY 4: Use shift_ids if available (non-settlement_pd)
        elseif (!empty($shift_ids)) {
            $shift_ids_for_settlement = $shift_ids;
        }

        // Ensure shift_ids_for_settlement contains integers for proper filtering
        if (!empty($shift_ids_for_settlement)) {
            $shift_ids_for_settlement = array_map('intval', $shift_ids_for_settlement);
            $shift_ids_for_settlement = array_filter($shift_ids_for_settlement); // Remove any zeros or invalid values
            $shift_ids_for_settlement = array_values($shift_ids_for_settlement); // Re-index
        }

        $direct_shift_label = null;
        if (! empty($work_shifts)) {
            $direct_shift_values = is_array($work_shifts)
                ? $work_shifts
                : (json_decode($work_shifts, true) ?: [$work_shifts]);

            foreach ((array) $direct_shift_values as $direct_shift_value) {
                if (is_scalar($direct_shift_value) && preg_match('/^[A-Z]*DST\s*\d+$/i', trim((string) $direct_shift_value))) {
                    $direct_shift_label = trim((string) $direct_shift_value);
                    break;
                }
            }
        }

        $is_direct_shift_settlement = $is_direct_shift_placeholder || ! empty($direct_shift_label);

        if (! $is_direct_shift_settlement && $request->type === 'settlement_pd' && ! empty($shift_ids_for_settlement)) {
            $closed_shift_ids_for_settlement = $this->getClosedShiftIdsForSettlement(
                $shift_ids_for_settlement,
                ! empty($pump_operator_id) ? (int) $pump_operator_id : null
            );
            $shift_ids_for_settlement = ! empty($closed_shift_ids_for_settlement)
                ? $closed_shift_ids_for_settlement
                : [0];
        }

        // CRITICAL: For settlement_pd, if we still don't have shift_ids, try multiple fallbacks
        // This ensures we can show payments even if shift_ids aren't passed in the request
        if ($request->type === 'settlement_pd' && ! $is_direct_shift_settlement && empty($shift_ids_for_settlement) && !empty($settlement)) {
            // FALLBACK 1: Try to get shift_id from the most recent meter sale for this settlement
            $recent_meter_sale = \Modules\PetroGeneral\Entities\MeterSale::where('settlement_no', $settlement->id)
                ->whereNotNull('shift_id')
                ->orderBy('id', 'desc')
                ->first();

            if ($recent_meter_sale && !empty($recent_meter_sale->shift_id)) {
                $shift_ids_for_settlement = [$recent_meter_sale->shift_id];
                \Log::info('AddPaymentController@create - Using shift_id from most recent meter sale', [
                    'shift_id' => $recent_meter_sale->shift_id,
                    'meter_sale_id' => $recent_meter_sale->id,
                ]);
            }
            // FALLBACK 2: Try to get shift_id from pump_operator_assignments linked to this settlement
            elseif (!empty($pump_operator_id)) {
                $assignment_shift_ids = \Modules\PetroGeneral\Entities\PumpOperatorAssignment::where('settlement_id', $settlement->id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->unique()
                    ->values()
                    ->toArray();

                if (!empty($assignment_shift_ids)) {
                    $shift_ids_for_settlement = $assignment_shift_ids;
                    \Log::info('AddPaymentController@create - Using shift_ids from pump_operator_assignments', [
                        'shift_ids' => $shift_ids_for_settlement,
                        'settlement_id' => $settlement->id,
                    ]);
                }
            }

            // FALLBACK 3: If still empty, try to get from most recent pump_operator_payment for this operator
            if (empty($shift_ids_for_settlement) && !empty($pump_operator_id)) {
                $recent_payment = \Modules\PetroGeneral\Entities\PumpOperatorPayment::where('pump_operator_id', $pump_operator_id)
                    ->whereNotNull('shift_id')
                    ->where(function($q) {
                        $q->whereNull('settlement_no')->orWhere('settlement_no', '');
                    })
                    ->orderBy('id', 'desc')
                    ->first();

                if ($recent_payment && !empty($recent_payment->shift_id)) {
                    $shift_ids_for_settlement = [$recent_payment->shift_id];
                    \Log::info('AddPaymentController@create - Using shift_id from most recent unlinked pump_operator_payment', [
                        'shift_id' => $recent_payment->shift_id,
                        'payment_id' => $recent_payment->id,
                    ]);
                }
            }

            // Last resort: Log warning but don't block - let the payment loading logic handle empty shift_ids
            if (empty($shift_ids_for_settlement)) {
                \Log::warning('AddPaymentController@create - No shift_ids available after all fallbacks, payments may not show correctly', [
                    'settlement_id' => $settlement->id,
                    'settlement_no' => $settlement->settlement_no,
                    'pump_operator_id' => $pump_operator_id,
                    'request_shift_ids' => $request->shift_ids ?? 'not_provided',
                ]);
            }
        }

        // CRITICAL LOGGING: Log all shift filtering information
        \Log::debug('AddPaymentController@create - Shift Filtering Debug', [
            'request_type' => $request->type ?? 'unknown',
            'request_shift_ids' => $request->shift_ids ?? 'not_provided',
            'shift_id_string' => $shift_id_string ?? 'not_provided',
            'shift_ids_from_request' => $shift_ids ?? [],
            'settlement_id' => $settlement->id ?? 'not_set',
            'settlement_no' => $settlement->settlement_no ?? 'not_set',
            'settlement_work_shift' => $settlement->work_shift ?? 'not_set',
            'shift_ids_for_settlement_FINAL' => $shift_ids_for_settlement ?? [],
            'pump_operator_id' => $pump_operator_id ?? 'not_set',
            'WARNING' => empty($shift_ids_for_settlement) ? 'NO SHIFT FILTERING - ALL PAYMENTS WILL BE SHOWN!' : 'Shift filtering active',
        ]);

        // Fetch settlement cash payments with customer names
        // CRITICAL: For settlement_pd, filter by shift_ids OR show payments created during this settlement session
        // ALSO: Include pump_operator_payments that aren't yet linked to a settlement but match the current shift and pump operator
        $settlement_cash_payments2_query = SettlementCashPayment::leftJoin('contacts', 'settlement_cash_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('pump_operator_payments', function($join) {
                $join->on('pump_operator_payments.id', '=', 'settlement_cash_payments.customer_payment_id')
                     ->orOn('pump_operator_payments.id', '=', 'settlement_cash_payments.pump_payment_id');
            })
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_cash_payments.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_cash_payments.settlement_no', $settlement->settlement_no);
                }
            })
            ->where(function ($q) {
                $q->whereNull('pump_operator_payments.id')
                    ->orWhere('pump_operator_payments.payment_type', 'cash');
            });

        // CRITICAL: For settlement_pd, ONLY show cash payments from the current shift(s)
        // Use shift_ids_for_settlement which prioritizes request shift_ids over work_shift
        if (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0) {
            $settlement_cash_payments2_query->where(function ($query) use ($shift_ids_for_settlement) {
                $query->whereNull('pump_operator_payments.id');

                if (count($shift_ids_for_settlement) > 1) {
                    $query->orWhereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement);
                } else {
                    $query->orWhere('pump_operator_payments.shift_id', $shift_ids_for_settlement[0]);
                }
            });
        } elseif ($request->type === 'settlement_pd' && empty($shift_ids_for_settlement) && $settlement) {
            // If no shift_ids available, show payments created AFTER the settlement was created
            // This ensures newly added payments are visible
            $settlement_cash_payments2_query->where('settlement_cash_payments.created_at', '>=', $settlement->created_at);
            \Log::warning('AddPaymentController@create - No shift_ids_for_settlement, showing payments created after settlement creation', [
                'settlement_id' => $settlement_id,
                'settlement_created_at' => $settlement->created_at,
                'request_shift_ids' => $request->shift_ids ?? 'not_provided',
            ]);
        }

        // Get the linked cash payments first
        $settlement_cash_payments2 = $settlement_cash_payments2_query
            ->select('settlement_cash_payments.*', 'contacts.name as customer_name', 'pump_operator_payments.shift_id as payment_shift_id')
            ->get();

        // CRITICAL: Also include pump_operator_payments that aren't yet linked to SettlementCashPayment
        // but match the current shift and pump operator (entries from pumper dashboard)
        // IMPORTANT: If shift_ids_for_settlement is empty, still try to show unlinked payments from this operator
        // This ensures payments from pumper dashboard are visible even if shift filtering fails
        if (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($pump_operator_id)) {
            // Collect PumpOperatorPayment IDs already linked via SettlementCashPayment to avoid duplicates
            $already_linked_pop_ids = $settlement_cash_payments2
                ->flatMap(function($item) {
                    $ids = [];
                    if (!empty($item->customer_payment_id) && is_numeric($item->customer_payment_id)) {
                        $ids[] = (int) $item->customer_payment_id;
                    }
                    if (!empty($item->pump_payment_id) && is_numeric($item->pump_payment_id)) {
                        $ids[] = (int) $item->pump_payment_id;
                    }
                    return $ids;
                })
                ->unique()
                ->values()
                ->toArray();

            $unlinked_cash_query = \Modules\PetroGeneral\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('payment_type', 'cash')
                ->where(function($q) {
                    $q->whereNull('is_used')->orWhere('is_used', 0);
                })
                ->where(function($q) {
                    $q->whereNull('settlement_no')->orWhere('settlement_no', '');
                });

            // Exclude PumpOperatorPayment records already linked to SettlementCashPayment
            if (!empty($already_linked_pop_ids)) {
                $unlinked_cash_query->whereNotIn('id', $already_linked_pop_ids);
            }

            // Filter by shift_ids if available, otherwise show recent unlinked payments
            if (!empty($shift_ids_for_settlement)) {
                $unlinked_cash_query->whereIn('shift_id', $shift_ids_for_settlement);
            } else {
                // If no shift_ids, show payments created after settlement was created (recent entries)
                if ($settlement && $settlement->created_at) {
                    $unlinked_cash_query->where('created_at', '>=', $settlement->created_at);
                }
                \Log::info('AddPaymentController@create - Loading unlinked cash payments without shift filter (using created_at)', [
                    'settlement_id' => $settlement_id,
                    'pump_operator_id' => $pump_operator_id,
                ]);
            }

            $unlinked_cash_payments = $unlinked_cash_query->get();

            // Convert to SettlementCashPayment-like format and merge with linked payments
            foreach ($unlinked_cash_payments as $pop) {
                $contact = \App\Contact::find($pop->customer_id);
                $settlement_cash_payments2->push((object)[
                    'id' => 'pop_' . $pop->id, // Prefix to identify as unlinked
                    'settlement_no' => null,
                    'amount' => $pop->payment_amount,
                    'customer_id' => $pop->customer_id,
                    'customer_name' => $contact ? $contact->name : 'Walk-In Customer',
                    'note' => $pop->note,
                    'pump_payment_id' => $pop->id,
                    'customer_payment_id' => $pop->id,
                    'is_unlinked' => true, // Flag to identify unlinked entries
                    'payment_shift_id' => $pop->shift_id,
                ]);
            }

            \Log::info('AddPaymentController@create - Unlinked cash payments added', [
                'unlinked_count' => $unlinked_cash_payments->count(),
                'total_cash_payments' => $settlement_cash_payments2->count(),
            ]);
        }

        \Log::debug('AddPaymentController@create - Cash Payments Loaded', [
            'settlement_id' => $settlement_id,
            'request_type' => $request->type ?? 'unknown',
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'cash_payments_count' => $settlement_cash_payments2->count(),
            'cash_payments_total' => $settlement_cash_payments2->sum('amount'),
            'cash_payments_details' => $settlement_cash_payments2->map(function($cp) {
                return [
                    'id' => $cp->id,
                    'amount' => $cp->amount,
                    'shift_id' => $cp->payment_shift_id ?? 'no_shift',
                ];
            })->toArray(),
        ]);

        $settlement_cash_payments1 = collect();
        if (! empty($shift_ids_for_settlement)) {
            // Get pump payment IDs that are already linked to SettlementCashPayment to avoid duplicates
            // Check both by parent_id (if SettlementCashPayment has parent_id) and by settlement_no + is_used
            $linked_pump_payment_ids = PumpOperatorPayment::where('pump_operator_payments.pump_operator_id', $pump_operator_id)
                ->where('pump_operator_payments.payment_type', 'cash')
                ->where('pump_operator_payments.is_used', 1)
                ->where('pump_operator_payments.settlement_no', $settlement_id)
                ->pluck('pump_operator_payments.id')
                ->toArray();

            // Also exclude PumpOperatorPayment IDs already present in settlement_cash_payments2
            // (both from customer_payment_id and pump_payment_id to avoid any overlap)
            $already_in_cash2 = $settlement_cash_payments2
                ->flatMap(function($item) {
                    $ids = [];
                    if (!empty($item->customer_payment_id) && is_numeric($item->customer_payment_id)) {
                        $ids[] = (int) $item->customer_payment_id;
                    }
                    if (!empty($item->pump_payment_id) && is_numeric($item->pump_payment_id)) {
                        $ids[] = (int) $item->pump_payment_id;
                    }
                    return $ids;
                })
                ->unique()
                ->toArray();

            $linked_pump_payment_ids = array_unique(array_merge($linked_pump_payment_ids, $already_in_cash2));

            $settlement_cash_payments1 = PumpOperatorPayment::whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement)
                ->where('pump_operator_payments.payment_type', 'cash')
                ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
                ->where(function ($q) {
                    $q->whereNull('pump_operator_payments.is_used')
                        ->orWhere('pump_operator_payments.is_used', 0);
                })
                ->whereNotIn('pump_operator_payments.id', $linked_pump_payment_ids) // Exclude already linked payments
                ->select(
                    'pump_operator_payments.id as pump_payment_id',
                    'pump_operator_payments.payment_amount as amount'
                )
                ->get();
        }

        // Convert into fake SettlementCashPayment models
        $newdatarecords = $settlement_cash_payments1->map(function ($data1) {
            $record                      = new SettlementCashPayment;
            $record->id                  = null;
            $record->settlement_no       = null;
            $record->business_id         = null;
            $record->customer_id         = null;
            $record->amount              = $data1->amount;
            $record->customer_payment_id = null;
            $record->note                = '';
            $record->slip                = '';
            $record->created_at          = now();
            $record->updated_at          = now();
            $record->customer_name       = 'Walking Customer';
            $record->pump_payment_id     = $data1->pump_payment_id;

            return $record;
        });

        // Combine both sets and remove duplicates based on row identity (id or pump_payment_id)
        // Prefer id so multiple saved rows with same amount/customer are all preserved (fix: duplicate amounts disappearing after Back/refresh)
        if ($settlement_cash_payments2->isNotEmpty()) {
            $settlement_cash_payments = $settlement_cash_payments2
                ->concat($newdatarecords)
                ->unique(function ($item) {
                    if (!empty($item->id)) {
                        return 'id_' . $item->id;
                    }
                    if (isset($item->pump_payment_id) && $item->pump_payment_id) {
                        return 'pump_' . $item->pump_payment_id;
                    }
                    return 'amount_' . $item->amount . '_customer_' . ($item->customer_id ?? 'null');
                })
                ->values(); // reset keys
        } else {
            $settlement_cash_payments = $newdatarecords;
        }

        $cash_total = $settlement_cash_payments->sum('amount');

        // 5️⃣ Debug output
        // dd($settlement_cash_payments);

        // SETTLEMENT POS PAYMENTS - Similar logic to cash payments
        $settlement_pos_payments2_query = SettlementPosPayment::leftJoin('contacts', 'settlement_pos_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('pump_operator_payments', 'pump_operator_payments.id', '=', 'settlement_pos_payments.customer_payment_id')
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_pos_payments.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_pos_payments.settlement_no', $settlement->settlement_no);
                }
            });

        // Filter by shift_ids for settlement_pd
        if ($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0) {
            if (count($shift_ids_for_settlement) > 1) {
                $settlement_pos_payments2_query->whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement);
            } else {
                $settlement_pos_payments2_query->where('pump_operator_payments.shift_id', $shift_ids_for_settlement[0]);
            }
        } elseif ($request->type === 'settlement_pd' && empty($shift_ids_for_settlement) && $settlement) {
            $settlement_pos_payments2_query->where('settlement_pos_payments.created_at', '>=', $settlement->created_at);
        }

        $settlement_pos_payments2 = $settlement_pos_payments2_query
            ->select('settlement_pos_payments.*', 'contacts.name as customer_name', 'pump_operator_payments.shift_id as payment_shift_id')
            ->get();

        // Include unlinked POS payments from pump operator
        $newposrecords = collect();
        if ($request->type === 'settlement_pd' && !empty($pump_operator_id)) {
            $already_linked_pos_ids = $settlement_pos_payments2
                ->pluck('customer_payment_id')
                ->filter()
                ->map(function($id) { return is_numeric($id) ? (int) $id : $id; })
                ->unique()
                ->values()
                ->toArray();

            $unlinked_pos_query = \Modules\PetroGeneral\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('payment_type', 'pos')
                ->where(function($q) {
                    $q->whereNull('is_used')->orWhere('is_used', 0);
                })
                ->where(function($q) {
                    $q->whereNull('settlement_no')->orWhere('settlement_no', '');
                });

            if (!empty($already_linked_pos_ids)) {
                $unlinked_pos_query->whereNotIn('id', $already_linked_pos_ids);
            }

            if (!empty($shift_ids_for_settlement)) {
                $unlinked_pos_query->whereIn('shift_id', $shift_ids_for_settlement);
            } else {
                if ($settlement && $settlement->created_at) {
                    $unlinked_pos_query->where('created_at', '>=', $settlement->created_at);
                }
            }

            $unlinked_pos_payments = $unlinked_pos_query->get();

            $newposrecords = $unlinked_pos_payments->map(function ($data1) use ($settlement_id) {
                $record = new SettlementPosPayment();
                $record->id = null;
                $record->settlement_no = $settlement_id;
                $record->business_id = null;
                $record->customer_id = null;
                $record->amount = $data1->payment_amount;
                $record->customer_payment_id = null;
                $record->note = 'Auto-generated from pump operator POS payments';
                $record->created_at = now();
                $record->updated_at = now();
                $record->customer_name = 'Walking Customer';
                $record->pump_payment_id = $data1->id;
                return $record;
            });
        }

        // Combine POS payments
        if ($settlement_pos_payments2->isNotEmpty()) {
            $settlement_pos_sales = $settlement_pos_payments2
                ->concat($newposrecords)
                ->unique(function ($item) {
                    if (!empty($item->id)) {
                        return 'id_' . $item->id;
                    }
                    if (isset($item->pump_payment_id) && $item->pump_payment_id) {
                        return 'pump_' . $item->pump_payment_id;
                    }
                    return 'amount_' . $item->amount . '_customer_' . ($item->customer_id ?? 'null');
                })
                ->values();
        } else {
            $settlement_pos_sales = $newposrecords;
        }

        $pos_total = $settlement_pos_sales->sum('amount');

        // CRITICAL: Filter customer loans - only show loans from THIS settlement
        // Customer loans don't have shift_id, but they're linked to settlement_no
        // For settlement_pd, ONLY show loans that belong to THIS settlement (not old ones)
        $settlement_customer_loans = SettlementCustomerLoan::leftjoin('contacts', 'settlement_customer_loans.customer_id', 'contacts.id')
            ->where(function($q) use ($settlement_id, $settlement) {
                // Match by settlement ID (integer) or settlement_no (string)
                $q->where('settlement_customer_loans.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_customer_loans.settlement_no', $settlement->settlement_no);
                }
            })
            // CRITICAL: For settlement_pd, only show loans created AFTER the settlement was created
            // This prevents showing loans from old settlements that might have the same ID
            ->when($request->type === 'settlement_pd' && $settlement && $settlement->created_at, function($q) use ($settlement) {
                $q->where('settlement_customer_loans.created_at', '>=', $settlement->created_at);
            })
            // Also ensure we're only matching by the exact settlement_id (not old settlements)
            ->when($request->type === 'settlement_pd' && is_numeric($settlement_id), function($q) use ($settlement_id) {
                $q->where('settlement_customer_loans.settlement_no', $settlement_id);
            })
            ->select('settlement_customer_loans.*', 'contacts.name as customer_name')
            ->get();

        \Log::debug('AddPaymentController@create - Customer Loans Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'customer_loans_count' => $settlement_customer_loans->count(),
            'customer_loans_total' => $settlement_customer_loans->sum('amount'),
        ]);

        // CRITICAL: Filter loan payments by shift_ids - only show loans from current shift(s)
        $settlement_loan_payments = SettlementLoanPayment::leftjoin('accounts', 'accounts.id', 'settlement_loan_payments.loan_account')
            ->where('settlement_loan_payments.settlement_no', $settlement_id)
            ->when(!empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0 && $request->type === 'settlement_pd', function($q) use ($shift_ids_for_settlement) {
                // For settlement_pd, filter by shift if possible
                // Note: Loan payments might not have direct shift_id linkage
            })
            ->select('settlement_loan_payments.*', 'accounts.name as loan_account_name')
            ->get();

        \Log::debug('AddPaymentController@create - Loan Payments Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'loan_payments_count' => $settlement_loan_payments->count(),
            'loan_payments_total' => $settlement_loan_payments->sum('amount'),
        ]);

        // CRITICAL: Filter drawings payments by shift_ids - only show drawings from current shift(s)
        $settlement_drawings_payments = SettlementDrawingPayment::leftjoin('accounts', 'accounts.id', 'settlement_drawing_payments.loan_account')
            ->where('settlement_drawing_payments.settlement_no', $settlement_id)
            ->when(!empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0 && $request->type === 'settlement_pd', function($q) use ($shift_ids_for_settlement) {
                // For settlement_pd, filter by shift if possible
            })
            ->select('settlement_drawing_payments.*', 'accounts.name as loan_account_name')
            ->get();

        \Log::debug('AddPaymentController@create - Drawings Payments Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'drawings_payments_count' => $settlement_drawings_payments->count(),
            'drawings_payments_total' => $settlement_drawings_payments->sum('amount'),
        ]);

        // 1️⃣ Actual settlement card payments
        // Fetch card payments that are already linked to this settlement
        // CRITICAL: daily_cards table doesn't have shift_id, so we must filter via pump_operator_payments
        $has_card_meta_columns = $this->pumpOperatorPaymentsHasCardMetaColumns();
        $has_settlement_card_pump_payment_column = $this->tableHasColumn('settlement_card_payments', 'pump_payment_id');

        $settlement_card_payments1 = SettlementCardPayment::query()
            ->leftJoin('contacts', 'settlement_card_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('accounts', 'settlement_card_payments.card_type', '=', 'accounts.id')
            ->when($has_settlement_card_pump_payment_column, function ($query) {
                $query->leftJoin('pump_operator_payments as linked_card_payment', function ($join) {
                    $join->on('linked_card_payment.id', '=', 'settlement_card_payments.pump_payment_id')
                        ->where('linked_card_payment.payment_type', 'card');
                });
            })
            ->leftJoin('pump_operator_payments as parent_card_payment', function ($join) {
                $join->on('parent_card_payment.parent_id', '=', 'settlement_card_payments.id')
                    ->where('parent_card_payment.payment_type', 'card');
            })
            ->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
            ->leftJoin('pump_operator_payments as matched_card_payment', function ($join) {
                $join->on('matched_card_payment.pump_operator_id', '=', 'daily_cards.pump_operator_id')
                    ->whereRaw('matched_card_payment.collection_form_no COLLATE utf8mb4_unicode_ci = daily_cards.collection_no COLLATE utf8mb4_unicode_ci')
                    ->where('matched_card_payment.payment_type', 'card')
                    ->whereColumn('matched_card_payment.payment_amount', 'daily_cards.amount');
            })
            ->where(function ($q) use ($settlement_id, $settlement) {
                $q->where('settlement_card_payments.settlement_no', $settlement_id)
                    ->orWhere('settlement_card_payments.settlement_no', $settlement->settlement_no ?? null);
            })
            // CRITICAL FIX: For settlement_pd, STRICTLY filter by shift_ids_for_settlement
            // This prevents showing card payments from previously selected but unsaved shifts (e.g., user selected Shift 5, went to Payment to Finalize, clicked Back, then selected Shift 3)
            // We must ONLY show card payments that match the CURRENT shift selection
            ->when($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0, function ($q) use ($shift_ids_for_settlement, $has_settlement_card_pump_payment_column) {
                $q->where(function($subQ) use ($shift_ids_for_settlement, $has_settlement_card_pump_payment_column) {
                    $subQ->where(function ($manualQ) use ($has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentManualUnlinkedFilter($manualQ, $has_settlement_card_pump_payment_column);
                    })
                    // OPTION 1: Card payment linked via pump_operator_payments.shift_id
                    ->orWhere(function($popQ) use ($shift_ids_for_settlement, $has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentShiftFilter($popQ, $shift_ids_for_settlement, $has_settlement_card_pump_payment_column);
                    })
                    ->orWhere(function($dcQ) use ($shift_ids_for_settlement, $has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentDailyCardShiftFilter($dcQ, $shift_ids_for_settlement, $has_settlement_card_pump_payment_column);
                    });
                });
            })
            // For non-settlement_pd or if shift_ids_for_settlement is empty, use shift_ids from request
            ->when($request->type !== 'settlement' && ($request->type !== 'settlement_pd' || empty($shift_ids_for_settlement)) && !empty($shift_ids) && count($shift_ids) > 0, function ($q) use ($shift_ids, $has_settlement_card_pump_payment_column) {
                $q->where(function($subQ) use ($shift_ids, $has_settlement_card_pump_payment_column) {
                    $subQ->where(function ($manualQ) use ($has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentManualUnlinkedFilter($manualQ, $has_settlement_card_pump_payment_column);
                    });

                    $subQ->orWhere(function ($popQ) use ($shift_ids, $has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentShiftFilter($popQ, $shift_ids, $has_settlement_card_pump_payment_column);
                    })->orWhere(function ($dcQ) use ($shift_ids, $has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentDailyCardShiftFilter($dcQ, $shift_ids, $has_settlement_card_pump_payment_column);
                    });
                });
            })
            ->select(
                'settlement_card_payments.*',
                'contacts.name as customer_name',
                'accounts.name as card_type_name',
                DB::raw(($has_settlement_card_pump_payment_column
                    ? 'COALESCE(linked_card_payment.pump_operator_id, parent_card_payment.pump_operator_id, matched_card_payment.pump_operator_id, daily_cards.pump_operator_id)'
                    : 'COALESCE(parent_card_payment.pump_operator_id, matched_card_payment.pump_operator_id, daily_cards.pump_operator_id)')
                    . ' as pump_operator_id'),
                'daily_cards.id as daily_card_id',
                DB::raw(($has_settlement_card_pump_payment_column
                    ? 'COALESCE(linked_card_payment.shift_id, parent_card_payment.shift_id, matched_card_payment.shift_id, daily_cards.shift_id)'
                    : 'COALESCE(parent_card_payment.shift_id, matched_card_payment.shift_id, daily_cards.shift_id)')
                    . ' as payment_shift_id'),
                DB::raw(($has_settlement_card_pump_payment_column
                    ? 'COALESCE(linked_card_payment.id, parent_card_payment.id, matched_card_payment.id)'
                    : 'COALESCE(parent_card_payment.id, matched_card_payment.id)')
                    . ' as pump_payment_id')
            )
            ->get();

        // 2️⃣ Fetch walking payments from pump operator payments - only if not already saved as SettlementCardPayment
        $existing_daily_card_ids = $settlement_card_payments1->pluck('daily_card_id')->filter()->toArray();
        $existing_pump_payment_ids = $settlement_card_payments1->pluck('pump_payment_id')->filter()->toArray();

        $walking_payments_query = PumpOperatorPayment::query()
            ->leftJoin('daily_cards', function ($join) {
                $join->on('daily_cards.pump_operator_id', '=', 'pump_operator_payments.pump_operator_id')
                    ->on('daily_cards.amount', '=', 'pump_operator_payments.payment_amount')
                    ->whereRaw('daily_cards.collection_no COLLATE utf8mb4_unicode_ci = pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci');
            })
            ->where('pump_operator_payments.payment_type', 'card')
            ->when(!empty($pump_operator_id), function ($q) use ($pump_operator_id) {
                $q->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
            })
            ->when($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0, function ($q) use ($shift_ids_for_settlement) {
                if (count($shift_ids_for_settlement) > 1) {
                    $q->whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement);
                } else {
                    $q->where('pump_operator_payments.shift_id', $shift_ids_for_settlement[0]);
                }
            })
            ->when(($request->type !== 'settlement_pd' || empty($shift_ids_for_settlement)) && !empty($shift_ids) && count($shift_ids) > 0, function ($q) use ($shift_ids) {
                if (count($shift_ids) > 1) {
                    $q->whereIn('pump_operator_payments.shift_id', $shift_ids);
                } else {
                    $q->where('pump_operator_payments.shift_id', $shift_ids[0]);
                }
            })
            ->when($request->type === 'settlement_pd' && empty($shift_ids_for_settlement) && empty($shift_ids), function ($q) {
                $q->whereRaw('1 = 0');
            })
            ->where(function ($q) {
                $q->whereNull('pump_operator_payments.is_used')
                    ->orWhere('pump_operator_payments.is_used', 0);
            })
            ->whereNotExists(function ($query) use ($settlement_id, $settlement, $has_settlement_card_pump_payment_column) {
                $query->select(DB::raw(1))
                    ->from('settlement_card_payments')
                    ->where(function ($linkedQuery) {
                        $linkedQuery->whereColumn('settlement_card_payments.id', 'pump_operator_payments.parent_id')
                            ->orWhereColumn('settlement_card_payments.daily_card_id', 'daily_cards.id');
                    })
                    ->when($has_settlement_card_pump_payment_column, function ($innerQuery) {
                        $innerQuery->orWhere(function ($linkedByPumpPaymentQuery) {
                            $linkedByPumpPaymentQuery->whereColumn('settlement_card_payments.pump_payment_id', 'pump_operator_payments.id');
                        });
                    })
                    ->where(function ($q) use ($settlement_id, $settlement) {
                        $q->where('settlement_card_payments.settlement_no', $settlement_id)
                            ->orWhere('settlement_card_payments.settlement_no', $settlement->settlement_no ?? null);
                    });
            })
            ->when(!empty($existing_pump_payment_ids), function ($q) use ($existing_pump_payment_ids) {
                $q->whereNotIn('pump_operator_payments.id', $existing_pump_payment_ids);
            })
            ->when(!empty($existing_daily_card_ids), function ($q) use ($existing_daily_card_ids) {
                $q->where(function ($dailyCardQuery) use ($existing_daily_card_ids) {
                    $dailyCardQuery->whereNull('daily_cards.id')
                        ->orWhereNotIn('daily_cards.id', $existing_daily_card_ids);
                });
            });

        if ($has_card_meta_columns) {
            $walking_payments_query
                ->leftJoin('contacts as payment_customer', 'payment_customer.id', '=', 'pump_operator_payments.customer_id')
                ->leftJoin('accounts as payment_card_account', 'payment_card_account.id', '=', 'pump_operator_payments.card_type');
        }

        $walking_payments_query
            ->leftJoin('contacts as legacy_customer', 'legacy_customer.id', '=', 'daily_cards.customer_id')
            ->leftJoin('accounts as legacy_card_account', 'legacy_card_account.id', '=', 'daily_cards.card_type');

        $customer_id_select = $has_card_meta_columns
            ? 'COALESCE(pump_operator_payments.customer_id, daily_cards.customer_id)'
            : 'daily_cards.customer_id';
        $card_type_select = $has_card_meta_columns
            ? 'COALESCE(NULLIF(pump_operator_payments.card_type, ""), CAST(daily_cards.card_type AS CHAR))'
            : 'CAST(daily_cards.card_type AS CHAR)';
        $card_number_select = $has_card_meta_columns
            ? 'COALESCE(NULLIF(pump_operator_payments.card_number, ""), daily_cards.card_number)'
            : 'daily_cards.card_number';
        $slip_no_select = $has_card_meta_columns
            ? 'COALESCE(NULLIF(pump_operator_payments.slip_no, ""), daily_cards.slip_no, "")'
            : 'COALESCE(daily_cards.slip_no, "")';
        $customer_name_select = $has_card_meta_columns
            ? 'COALESCE(payment_customer.name, legacy_customer.name)'
            : 'legacy_customer.name';
        $card_type_name_select = $has_card_meta_columns
            ? 'COALESCE(payment_card_account.name, legacy_card_account.name)'
            : 'legacy_card_account.name';

        $walking_payments = $walking_payments_query
            ->select(
                DB::raw('NULL AS id'),
                DB::raw((int) $settlement_id . ' AS settlement_no'),
                'pump_operator_payments.business_id',
                DB::raw($customer_id_select . ' AS customer_id'),
                'daily_cards.id as daily_card_id',
                DB::raw($card_type_select . ' AS card_type'),
                DB::raw($card_number_select . ' AS card_number'),
                DB::raw('CAST(COALESCE(pump_operator_payments.payment_amount, 0) AS DECIMAL(10,2)) AS amount'),
                DB::raw('NULL AS customer_payment_id'),
                DB::raw('"" AS note'),
                DB::raw($slip_no_select . ' AS slip_no'),
                DB::raw($customer_name_select . ' AS customer_name'),
                DB::raw($card_type_name_select . ' AS card_type_name'),
                'pump_operator_payments.id as pump_payment_id',
                'pump_operator_payments.shift_id as payment_shift_id',
                'daily_cards.shift_id as daily_card_shift_id'
            )
            ->get();


        // Combine both collections - show ALL card payments (direct + pump operator)
        // Prefer id so multiple saved rows with same amount are all preserved (fix: duplicate amounts disappearing after Back/refresh)
        $settlement_card_payments = collect()
            ->merge($settlement_card_payments1) // existing saved
            ->merge($walking_payments)          // unsaved walking cards
            ->unique(function ($item) use ($request) {
                if ($request->type === 'settlement_pd') {
                    if (!empty($item->pump_payment_id)) {
                        return 'pump_' . $item->pump_payment_id;
                    }
                    if (!empty($item->daily_card_id)) {
                        return 'daily_' . $item->daily_card_id;
                    }
                }
                if (!empty($item->id)) {
                    return 'id_' . $item->id;
                }
                if (!empty($item->pump_payment_id)) {
                    return 'pump_' . $item->pump_payment_id;
                }

                return ($item->daily_card_id ?? '') . '-' . ($item->amount ?? '') . '-' . ($item->slip_no ?? '');
            })
            ->values();

        \Log::debug('AddPaymentController@create - Card Payments Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'settlement_card_payments1_count' => $settlement_card_payments1->count(),
            'walking_payments_count' => $walking_payments->count(),
            'total_card_payments_count' => $settlement_card_payments->count(),
            'walking_payments_shift_ids' => $walking_payments->pluck('payment_shift_id')->unique()->toArray(),
        ]);

        // CRITICAL: Filter cash deposits - only show deposits from THIS settlement
        // Cash deposits don't have shift_id, but they're linked to settlement_no
        // For settlement_pd, ONLY show deposits that belong to THIS settlement (not old ones)
        $settlement_cash_deposits = SettlementCashDeposit::leftjoin('accounts', 'settlement_cash_deposits.bank_id', 'accounts.id')
            ->where(function($q) use ($settlement_id, $settlement) {
                // Match by settlement ID (integer) or settlement_no (string)
                $q->where('settlement_cash_deposits.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_cash_deposits.settlement_no', $settlement->settlement_no);
                }
            })
            // CRITICAL: For settlement_pd, only show deposits created AFTER the settlement was created
            // This prevents showing deposits from old settlements that might have the same ID
            ->when($request->type === 'settlement_pd' && $settlement && $settlement->created_at, function($q) use ($settlement) {
                $q->where('settlement_cash_deposits.created_at', '>=', $settlement->created_at);
            })
            // Also ensure we're only matching by the exact settlement_id (not old settlements)
            ->when($request->type === 'settlement_pd' && is_numeric($settlement_id), function($q) use ($settlement_id) {
                $q->where('settlement_cash_deposits.settlement_no', $settlement_id);
            })
            ->select('settlement_cash_deposits.*', 'accounts.name as bank_name')
            ->get();

        \Log::debug('AddPaymentController@create - Cash Deposits Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'cash_deposits_count' => $settlement_cash_deposits->count(),
            'cash_deposits_total' => $settlement_cash_deposits->sum('amount'),
        ]);

        $has_settlement_cheque_pump_payment_column = $this->tableHasColumn('settlement_cheque_payments', 'pump_payment_id');

        $settlement_cheque_payments_query = SettlementChequePayment::leftjoin('contacts', 'settlement_cheque_payments.customer_id', 'contacts.id')
            ->when($has_settlement_cheque_pump_payment_column, function ($query) {
                $query->leftJoin('pump_operator_payments as linked_cheque_payment', function ($join) {
                    $join->on('linked_cheque_payment.id', '=', 'settlement_cheque_payments.pump_payment_id')
                        ->where('linked_cheque_payment.payment_type', 'cheque');
                });
            })
            ->leftJoin('pump_operator_payments as parent_cheque_payment', function ($join) {
                $join->on('parent_cheque_payment.parent_id', '=', 'settlement_cheque_payments.id')
                    ->where('parent_cheque_payment.payment_type', 'cheque');
            })
            ->where(function ($q) use ($settlement_id, $settlement) {
                $q->where('settlement_cheque_payments.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_cheque_payments.settlement_no', $settlement->settlement_no);
                }
            });

        if (! empty($shift_ids_for_settlement)) {
            $settlement_cheque_payments_query->where(function ($q) use ($shift_ids_for_settlement, $has_settlement_cheque_pump_payment_column) {
                if ($has_settlement_cheque_pump_payment_column) {
                    $q->whereIn('linked_cheque_payment.shift_id', $shift_ids_for_settlement)
                        ->orWhere(function ($no_link_query) {
                            $no_link_query->whereNull('linked_cheque_payment.id')
                                ->whereNull('parent_cheque_payment.id');
                        });
                }
                $q->orWhereIn('parent_cheque_payment.shift_id', $shift_ids_for_settlement);

                if (! $has_settlement_cheque_pump_payment_column) {
                    $q->orWhereNull('parent_cheque_payment.id');
                }
            });
        }

        $settlement_cheque_payments = $settlement_cheque_payments_query
            ->select(
                'settlement_cheque_payments.*',
                'contacts.name as customer_name',
                DB::raw(($has_settlement_cheque_pump_payment_column
                    ? 'COALESCE(settlement_cheque_payments.pump_payment_id, linked_cheque_payment.id, parent_cheque_payment.id)'
                    : 'parent_cheque_payment.id') . ' as pump_payment_id')
            )
            ->get();

        // Fetch credit sales that are already linked to this settlement
        // CRITICAL: For direct settlement, credit sales are saved with settlement_no string
        // We MUST find them by settlement_no, regardless of pump_operator_payments join
        // IMPORTANT: For Direct Settlement with settlement_no set, fetch WITHOUT grouping to maintain consistency
        // Grouping/aggregation causes totals to change when navigating back
        $settlement_credit_sale_payments1 = SettlementCreditSalePayment::query()
            ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
            ->leftJoin('daily_vouchers', 'settlement_credit_sale_payments.daily_voucher_id', '=', 'daily_vouchers.id')
            ->leftJoin('pump_operator_payments', function($join) {
                $join->on('pump_operator_payments.pump_operator_id', '=', 'settlement_credit_sale_payments.pump_operator_id')
                     ->whereRaw('pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci = settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_unicode_ci')
                     ->where('pump_operator_payments.payment_type', 'credit');
            })
            ->where(function ($q) use ($settlement_id, $settlement, $pump_operator_id) {
                // CRITICAL: Credit sales with settlement_no set belong to this settlement and should be included
                // regardless of pump_operator_id or pump_operator_payments join. Only filter by pump_operator_id for NULL settlement_no.

                // Primary match: settlement_no string (credit sales are saved with settlement_no string)
                // This MUST be the first condition to ensure credit sales with settlement_no are always found
                if ($settlement && !empty($settlement->settlement_no)) {
                    $q->where('settlement_credit_sale_payments.settlement_no', $settlement->settlement_no);
                }

                // Also match by integer ID and string ID formats (for backward compatibility)
                // Use orWhere so any of these can match
                if ($settlement && !empty($settlement->settlement_no)) {
                    // If we already have settlement_no match, add these as alternatives
                    $q->orWhere('settlement_credit_sale_payments.settlement_no', $settlement_id)
                        ->orWhere('settlement_credit_sale_payments.settlement_no', (string) $settlement_id);
                } else {
                    // If no settlement_no, try matching by ID
                    $q->where('settlement_credit_sale_payments.settlement_no', $settlement_id)
                        ->orWhere('settlement_credit_sale_payments.settlement_no', (string) $settlement_id);
                }

                // For credit sales with NULL settlement_no, only include if pump_operator_id matches
                // BUT only if we haven't already matched by settlement_no
                if (!empty($pump_operator_id)) {
                    $q->orWhere(function($subQ) use ($pump_operator_id) {
                        $subQ->whereNull('settlement_credit_sale_payments.settlement_no')
                            ->where('settlement_credit_sale_payments.pump_operator_id', $pump_operator_id);
                    });
                } else {
                    // If no pump_operator_id, include all NULL settlement_no credit sales
                    $q->orWhereNull('settlement_credit_sale_payments.settlement_no');
                }
            })
            // CRITICAL: For settlement_pd, filter by shift_ids_for_settlement via pump_operator_payments.shift_id
            // BUT only for credit sales WITHOUT settlement_no (NULL settlement_no)
            // Credit sales WITH settlement_no belong to this settlement regardless of shift
            ->when($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0, function ($q) use ($shift_ids_for_settlement, $settlement) {
                $q->where(function($subQ) use ($shift_ids_for_settlement, $settlement) {
                    // Include credit sales with settlement_no set (they belong to this settlement)
                    if ($settlement && !empty($settlement->settlement_no)) {
                        $subQ->where('settlement_credit_sale_payments.settlement_no', $settlement->settlement_no);
                    }
                    // For credit sales without settlement_no, filter by shift
                    $subQ->orWhere(function($shiftQ) use ($shift_ids_for_settlement) {
                        $shiftQ->whereNull('settlement_credit_sale_payments.settlement_no');
                        // Filter by pump_operator_payments.shift_id (which DOES exist)
                        if (count($shift_ids_for_settlement) > 1) {
                            $shiftQ->whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement);
                        } else {
                            $shiftQ->where('pump_operator_payments.shift_id', $shift_ids_for_settlement[0]);
                        }
                    });
                });
            })
            // For non-settlement_pd or if shift_ids_for_settlement is empty, use shift_ids from request
            // BUT only for credit sales WITHOUT settlement_no
            ->when(($request->type !== 'settlement_pd' || empty($shift_ids_for_settlement)) && !empty($shift_ids) && count($shift_ids) > 0, function ($q) use ($shift_ids, $settlement) {
                $q->where(function($subQ) use ($shift_ids, $settlement) {
                    // Include credit sales with settlement_no set (they belong to this settlement)
                    if ($settlement && !empty($settlement->settlement_no)) {
                        $subQ->where('settlement_credit_sale_payments.settlement_no', $settlement->settlement_no);
                    }
                    // For credit sales without settlement_no, filter by shift
                    $subQ->orWhere(function($shiftQ) use ($shift_ids) {
                        $shiftQ->whereNull('settlement_credit_sale_payments.settlement_no');
                        // Include records where daily_vouchers.shift_id matches, or where daily_vouchers is null
                        $shiftQ->whereNull('daily_vouchers.shift_id')
                            ->orWhere(function($dvQ) use ($shift_ids) {
                                if (count($shift_ids) > 1) {
                                    $dvQ->whereIn('daily_vouchers.shift_id', $shift_ids);
                                } else {
                                    $dvQ->where('daily_vouchers.shift_id', $shift_ids[0]);
                                }
                        });
                    });
                });
            })
            ->when($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0, function ($q) use ($shift_ids_for_settlement) {
                $q->whereExists(function ($shiftQuery) use ($shift_ids_for_settlement) {
                    $shiftQuery->select(DB::raw(1))
                        ->from('pump_operator_payments as scsp_shift_payments')
                        ->whereColumn('scsp_shift_payments.business_id', 'settlement_credit_sale_payments.business_id')
                        ->whereColumn('scsp_shift_payments.pump_operator_id', 'settlement_credit_sale_payments.pump_operator_id')
                        ->where('scsp_shift_payments.payment_type', 'credit')
                        ->whereIn('scsp_shift_payments.shift_id', $shift_ids_for_settlement)
                        ->where(function ($matchQuery) {
                            $matchQuery->whereColumn('scsp_shift_payments.id', 'settlement_credit_sale_payments.pump_payment_id')
                                ->orWhere(function ($legacyQuery) {
                                    $legacyQuery->whereNull('settlement_credit_sale_payments.pump_payment_id')
                                        ->whereRaw('scsp_shift_payments.collection_form_no COLLATE utf8mb4_unicode_ci = settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_unicode_ci');
                                });
                        });
                });
            });

        // CRITICAL: For Direct Settlement (type === 'settlement'), do NOT use grouping/aggregation
        // This prevents totals from changing when navigating back
        // For Settlement PD, use grouping to consolidate multiple line items into one order
        if ($request->type === 'settlement') {
            // Direct Settlement: Fetch individual records without grouping
            $settlement_credit_sale_payments1 = $settlement_credit_sale_payments1
                ->select(
                    'settlement_credit_sale_payments.id',
                    'settlement_credit_sale_payments.settlement_no',
                    'settlement_credit_sale_payments.customer_id',
                    'settlement_credit_sale_payments.daily_voucher_id',
                    'settlement_credit_sale_payments.collection_form_no',
                    'settlement_credit_sale_payments.amount',
                    'settlement_credit_sale_payments.qty',
                    'settlement_credit_sale_payments.order_date',
                    'settlement_credit_sale_payments.order_number',
                    'settlement_credit_sale_payments.customer_reference',
                    'settlement_credit_sale_payments.price',
                    'settlement_credit_sale_payments.total_discount',
                    'settlement_credit_sale_payments.sub_total',
                    'settlement_credit_sale_payments.outstanding',
                    'settlement_credit_sale_payments.credit_limit',
                    'settlement_credit_sale_payments.note',
                    'settlement_credit_sale_payments.pump_operator_id',
                    'settlement_credit_sale_payments.product_id',
                    'products.name as product_name',
                    'contacts.name as customer_name',
                    'daily_vouchers.operator_id as operator_id'
                )
                ->orderBy('settlement_credit_sale_payments.id', 'asc')
                ->get();
        } else {
            // Settlement PD: consolidate only rows from the same credit sale/customer/order.
            // Grouping by collection_form_no alone hides separate customers when old pumper
            // dashboard rows reused the same form number.
            $settlement_credit_sale_payments1 = $settlement_credit_sale_payments1
                ->groupBy(DB::raw("CASE WHEN settlement_credit_sale_payments.collection_form_no IS NULL OR settlement_credit_sale_payments.collection_form_no = '' THEN CONCAT('id:', settlement_credit_sale_payments.id) ELSE CONCAT('cf:', settlement_credit_sale_payments.collection_form_no, '|customer:', COALESCE(settlement_credit_sale_payments.customer_id, ''), '|order:', COALESCE(settlement_credit_sale_payments.order_number, '')) END"))
                ->select(
                    DB::raw('MAX(settlement_credit_sale_payments.id) as id'),
                    DB::raw('MAX(settlement_credit_sale_payments.settlement_no) as settlement_no'),
                    DB::raw('MAX(settlement_credit_sale_payments.customer_id) as customer_id'),
                    DB::raw('MAX(settlement_credit_sale_payments.daily_voucher_id) as daily_voucher_id'),
                    DB::raw('MAX(settlement_credit_sale_payments.collection_form_no) as collection_form_no'),
                    DB::raw('SUM(settlement_credit_sale_payments.amount) as amount'),
                    DB::raw('SUM(settlement_credit_sale_payments.qty) as qty'),
                    DB::raw('MAX(settlement_credit_sale_payments.order_date) as order_date'),
                    DB::raw('MAX(settlement_credit_sale_payments.order_number) as order_number'),
                    DB::raw('MAX(settlement_credit_sale_payments.customer_reference) as customer_reference'),
                    DB::raw('MAX(settlement_credit_sale_payments.price) as price'),
                    DB::raw('SUM(settlement_credit_sale_payments.total_discount) as total_discount'),
                    DB::raw('SUM(settlement_credit_sale_payments.sub_total) as sub_total'),
                    DB::raw('MAX(settlement_credit_sale_payments.outstanding) as outstanding'),
                    DB::raw('MAX(settlement_credit_sale_payments.credit_limit) as credit_limit'),
                    DB::raw('MAX(settlement_credit_sale_payments.note) as note'),
                    DB::raw('MAX(settlement_credit_sale_payments.pump_operator_id) as pump_operator_id'),
                    DB::raw('GROUP_CONCAT(DISTINCT products.name ORDER BY products.name SEPARATOR ", ") as product_name'),
                    DB::raw('MAX(contacts.name) as customer_name'),
                    DB::raw('MAX(daily_vouchers.operator_id) as operator_id')
                )
                ->orderBy('settlement_credit_sale_payments.id', 'asc')
                ->get();
        }

        // Get IDs of already saved credit sales to exclude from settlement_credit_sale_payments2
        $existing_credit_sale_ids = $settlement_credit_sale_payments1->pluck('id')->filter()->toArray();

        // Fetch credit sales from pump_operator_payments that haven't been linked to a settlement yet
        // IMPORTANT: For settlement_pd, we MUST filter by shift_ids to only show credit sales from the current shift(s)
        // CRITICAL: Use shift_ids_for_settlement (which prioritizes request shift_ids) NOT work_shift
        $settlement_credit_sale_payments2 = collect();
        if (!empty($pump_operator_id)) {
            // Use shift_ids_for_settlement which already prioritizes request shift_ids over work_shift
            // CRITICAL: For settlement_pd, if shift_ids_for_settlement is empty, don't show any credit sales
            // This prevents showing credit sales from all shifts when shift filtering fails
            $filter_shift_ids = $shift_ids_for_settlement;

            // CRITICAL: For settlement_pd, try to load credit sales even if shift_ids are empty
            // Use shift_ids if available, otherwise use created_at filter to show recent entries
            if ($request->type === 'settlement_pd' && empty($filter_shift_ids)) {
                \Log::info('AddPaymentController@create - No shift_ids_for_settlement for credit sales - will use created_at filter', [
                    'settlement_id' => $settlement_id,
                    'pump_operator_id' => $pump_operator_id,
                    'request_shift_ids' => $request->shift_ids ?? 'not_provided',
                ]);
            }

            $credit_sales_query2 = DB::table('settlement_credit_sale_payments')
                ->whereExists(function ($query) use ($filter_shift_ids, $pump_operator_id, $request, $settlement) {
                    $query->select(DB::raw(1))
                        ->from('pump_operator_payments')
                        ->whereRaw('settlement_credit_sale_payments.pump_operator_id = pump_operator_payments.pump_operator_id')
                        ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
                        ->where('pump_operator_payments.payment_type', 'credit')
                        ->whereRaw('settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_general_ci = pump_operator_payments.collection_form_no COLLATE utf8mb4_general_ci')
                        // CRITICAL: For settlement_pd, filter by shift_ids if available
                        ->when($request->type === 'settlement_pd' && !empty($filter_shift_ids) && count($filter_shift_ids) > 0, function ($q) use ($filter_shift_ids) {
                            if (count($filter_shift_ids) > 1) {
                                $q->whereIn('pump_operator_payments.shift_id', $filter_shift_ids);
                            } else {
                                $q->where('pump_operator_payments.shift_id', $filter_shift_ids[0]);
                            }
                        })
                        // If no shift_ids, filter by created_at to show recent entries
                        ->when($request->type === 'settlement_pd' && empty($filter_shift_ids) && $settlement && $settlement->created_at, function ($q) use ($settlement) {
                            $q->where('pump_operator_payments.created_at', '>=', $settlement->created_at);
                        });
                })
                ->whereNull('settlement_credit_sale_payments.settlement_no')
                ->when(!empty($existing_credit_sale_ids), function ($q) use ($existing_credit_sale_ids) {
                    $q->whereNotIn('settlement_credit_sale_payments.id', $existing_credit_sale_ids);
                })
                ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
                ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id');

            // CRITICAL: For Direct Settlement, do NOT use grouping/aggregation
            // This prevents totals from changing when navigating back
            if ($request->type === 'settlement') {
                // Direct Settlement: Fetch individual records without grouping
                $settlement_credit_sale_payments2 = $credit_sales_query2
                    ->select(
                        'settlement_credit_sale_payments.id',
                        'settlement_credit_sale_payments.customer_id',
                        'settlement_credit_sale_payments.daily_voucher_id',
                        'settlement_credit_sale_payments.collection_form_no',
                        'settlement_credit_sale_payments.amount',
                        'settlement_credit_sale_payments.qty',
                        'settlement_credit_sale_payments.order_date',
                        'settlement_credit_sale_payments.order_number',
                        'settlement_credit_sale_payments.customer_reference',
                        'settlement_credit_sale_payments.price',
                        'settlement_credit_sale_payments.total_discount',
                        'settlement_credit_sale_payments.sub_total',
                        'settlement_credit_sale_payments.outstanding',
                        'settlement_credit_sale_payments.credit_limit',
                        'settlement_credit_sale_payments.note',
                        'settlement_credit_sale_payments.product_id',
                        'products.name as product_name',
                        'contacts.name as customer_name'
                    )
                    ->orderBy('settlement_credit_sale_payments.id', 'asc')
                    ->get();
            } else {
                // Settlement PD: consolidate only rows from the same credit sale/customer/order.
                $settlement_credit_sale_payments2 = $credit_sales_query2
                    ->groupBy(DB::raw("CASE WHEN settlement_credit_sale_payments.collection_form_no IS NULL OR settlement_credit_sale_payments.collection_form_no = '' THEN CONCAT('id:', settlement_credit_sale_payments.id) ELSE CONCAT('cf:', settlement_credit_sale_payments.collection_form_no, '|customer:', COALESCE(settlement_credit_sale_payments.customer_id, ''), '|order:', COALESCE(settlement_credit_sale_payments.order_number, '')) END"))
                    ->select(
                        DB::raw('MAX(settlement_credit_sale_payments.id) as id'),
                        DB::raw('MAX(settlement_credit_sale_payments.customer_id) as customer_id'),
                        DB::raw('MAX(settlement_credit_sale_payments.daily_voucher_id) as daily_voucher_id'),
                        DB::raw('MAX(settlement_credit_sale_payments.collection_form_no) as collection_form_no'),
                        DB::raw('SUM(settlement_credit_sale_payments.amount) as amount'),
                        DB::raw('SUM(settlement_credit_sale_payments.qty) as qty'),
                        DB::raw('MAX(settlement_credit_sale_payments.order_date) as order_date'),
                        DB::raw('MAX(settlement_credit_sale_payments.order_number) as order_number'),
                        DB::raw('MAX(settlement_credit_sale_payments.customer_reference) as customer_reference'),
                        DB::raw('MAX(settlement_credit_sale_payments.price) as price'),
                        DB::raw('SUM(settlement_credit_sale_payments.total_discount) as total_discount'),
                        DB::raw('SUM(settlement_credit_sale_payments.sub_total) as sub_total'),
                        DB::raw('MAX(settlement_credit_sale_payments.outstanding) as outstanding'),
                        DB::raw('MAX(settlement_credit_sale_payments.credit_limit) as credit_limit'),
                        DB::raw('MAX(settlement_credit_sale_payments.note) as note'),
                        DB::raw('GROUP_CONCAT(DISTINCT products.name ORDER BY products.name SEPARATOR ", ") as product_name'),
                        DB::raw('MAX(contacts.name) as customer_name')
                    )
                    ->orderBy('settlement_credit_sale_payments.id', 'asc')
                    ->get();
            }
        }

        // Combine both collections with better deduplication
        // CRITICAL: For direct settlement, use ID-based deduplication to preserve individual credit sale records
        // This ensures that when user goes back and returns, the same credit sales are shown with same values
        $settlement_credit_sale_payments = collect()
            ->merge($settlement_credit_sale_payments1 ?? collect())
            ->merge($settlement_credit_sale_payments2 ?? collect())
            ->unique(function ($item) use ($request) {
                if ($request->type === 'settlement') {
                    return implode('|', [
                        'direct',
                        $item->customer_id ?? '',
                        $item->product_id ?? '',
                        $item->order_number ?? '',
                        $item->order_date ?? '',
                        (float) ($item->qty ?? 0),
                        (float) ($item->amount ?? 0),
                        (float) ($item->total_discount ?? 0),
                    ]);
                }

                // For direct settlement, prefer ID-based identity to preserve individual records
                // This prevents aggregation that changes values when page reloads
                if (!empty($item->id)) {
                    return 'id-' . $item->id;
                }
                // Fallback to other identifiers for settlement_pd
                if (!empty($item->daily_voucher_id)) {
                    return 'dv-' . $item->daily_voucher_id;
                }
                if (!empty($item->collection_form_no)) {
                    return 'cf-' . $item->collection_form_no;
                }
                return 'ord-' . ($item->order_number ?? '') . '-' . ($item->customer_id ?? '') . '-' . ($item->order_date ?? '');
            })
            ->values();

        \Log::debug('AddPaymentController@create - Credit Sales Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'pump_operator_id' => $pump_operator_id ?? 'not_set',
            'settlement_credit_sale_payments1_count' => $settlement_credit_sale_payments1->count(),
            'settlement_credit_sale_payments2_count' => $settlement_credit_sale_payments2->count(),
            'total_credit_sale_payments_count' => $settlement_credit_sale_payments->count(),
            'credit_sale_collection_form_nos' => $settlement_credit_sale_payments->pluck('collection_form_no')->unique()->toArray(),
        ]);

        $pumper_credit_sale_payments = collect();
        if (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($pump_operator_id)) {
            $has_pump_operator_payment_slip_no = $this->tableHasColumn('pump_operator_payments', 'slip_no');
            $pumper_credit_sale_selects = [
                'pump_operator_payments.id',
                'pump_operator_payments.date_and_time',
                'pump_operator_payments.shift_id',
                'pump_operator_payments.collection_form_no',
                'pump_operator_payments.payment_type',
                'pump_operator_payments.payment_amount',
            ];
            $pumper_credit_sale_selects[] = $has_pump_operator_payment_slip_no
                ? 'pump_operator_payments.slip_no'
                : DB::raw('NULL as slip_no');
            $pumper_credit_sale_selects = array_merge($pumper_credit_sale_selects, [
                'business_locations.name as location_name',
                'pump_operators.name as pump_operator_name',
                'contacts.name as customer_name',
                DB::raw("'pump_operator_payment' as source_type"),
                DB::raw('MAX(credit_sales.id) as scsp_id'),
                DB::raw('MAX(credit_sales.daily_voucher_id) as daily_voucher_id'),
                DB::raw('MAX(credit_daily_vouchers.settlement_no) as daily_voucher_settlement_no'),
                DB::raw('MAX(credit_sales.order_number) as order_number'),
            ]);

            $pumper_credit_sale_payments = PumpOperatorPayment::leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('business_locations', 'pump_operators.location_id', '=', 'business_locations.id')
                ->leftJoin('settlement_credit_sale_payments as credit_sales', function ($join) {
                    $join->on('credit_sales.pump_payment_id', '=', 'pump_operator_payments.id');
                })
                ->leftJoin('daily_vouchers as credit_daily_vouchers', 'credit_daily_vouchers.id', '=', 'credit_sales.daily_voucher_id')
                ->leftJoin('contacts', 'credit_sales.customer_id', '=', 'contacts.id')
                ->where('pump_operator_payments.business_id', $business_id)
                ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
                ->where('pump_operator_payments.payment_type', 'credit')
                ->when(!empty($shift_ids_for_settlement), function ($q) use ($shift_ids_for_settlement) {
                    $q->whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement);
                })
                ->when(empty($shift_ids_for_settlement) && !empty($settlement->created_at), function ($q) use ($settlement) {
                    $q->where('pump_operator_payments.created_at', '>=', $settlement->created_at);
                })
                ->select($pumper_credit_sale_selects)
                ->groupBy('pump_operator_payments.id')
                ->orderBy('pump_operator_payments.date_and_time')
                ->orderBy('pump_operator_payments.id')
                ->get();
        }

        if (in_array($request->type, ['settlement', 'settlement_pd']) && $pumper_credit_sale_payments->isEmpty() && $settlement_credit_sale_payments->isNotEmpty()) {
            $credit_sale_location_name = '-';
            if (!empty($pump_operator) && !empty($pump_operator->location_id)) {
                $credit_sale_location_name = BusinessLocation::where('id', $pump_operator->location_id)->value('name') ?: '-';
            }

            $pumper_credit_sale_payments = $settlement_credit_sale_payments->map(function ($credit_sale_payment) use ($credit_sale_location_name, $pump_operator, $settlement) {
                $amount = (float) ($credit_sale_payment->amount ?? 0);
                $discount = (float) ($credit_sale_payment->total_discount ?? 0);

                return (object) [
                    'source_type' => 'settlement_credit_sale',
                    'id' => null,
                    'date_and_time' => $credit_sale_payment->order_date ?? ($settlement->transaction_date ?? null),
                    'shift_id' => null,
                    'collection_form_no' => $credit_sale_payment->collection_form_no,
                    'payment_type' => 'credit',
                    'payment_amount' => $amount - $discount,
                    'slip_no' => $credit_sale_payment->slip_no ?? '',
                    'location_name' => $credit_sale_location_name,
                    'pump_operator_name' => !empty($pump_operator) ? $pump_operator->name : '',
                    'customer_name' => $credit_sale_payment->customer_name ?? '',
                    'scsp_id' => $credit_sale_payment->id,
                    'daily_voucher_id' => $credit_sale_payment->daily_voucher_id ?? null,
                    'daily_voucher_settlement_no' => null,
                    'order_number' => $credit_sale_payment->order_number ?? '',
                ];
            })->values();
        }

        // CRITICAL: Filter expense payments by shift_ids - only show expenses from current shift(s)
        $settlement_expense_payments = SettlementExpensePayment::leftjoin('accounts', 'settlement_expense_payments.account_id', 'accounts.id')
            ->leftjoin('expense_categories', 'settlement_expense_payments.category_id', 'expense_categories.id')
            ->where('settlement_expense_payments.settlement_no', $settlement_id)
            ->when(!empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0 && $request->type === 'settlement_pd', function($q) use ($shift_ids_for_settlement) {
                // For settlement_pd, filter by shift if possible
            })
            ->select('settlement_expense_payments.*', 'accounts.name as account_name', 'expense_categories.name as category_name')
            ->get();

        \Log::debug('AddPaymentController@create - Expense Payments Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'expense_payments_count' => $settlement_expense_payments->count(),
            'expense_payments_total' => $settlement_expense_payments->sum('amount'),
        ]);

        // Shortage/excess rows are written with the settlement id, but older/manual rows can
        // carry the settlement number. Load both keys so rows survive a modal/page refresh.
        $settlement_shortage_payments = SettlementShortagePayment::where('settlement_shortage_payments.business_id', $business_id)
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_shortage_payments.settlement_no', $settlement_id)
                    ->orWhere('settlement_shortage_payments.settlement_no', (string) $settlement_id);

                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_shortage_payments.settlement_no', $settlement->settlement_no);
                }
            })
            ->select('settlement_shortage_payments.*')
            ->get();

        $settlement_excess_payments = SettlementExcessPayment::where('settlement_excess_payments.business_id', $business_id)
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_excess_payments.settlement_no', $settlement_id)
                    ->orWhere('settlement_excess_payments.settlement_no', (string) $settlement_id);

                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_excess_payments.settlement_no', $settlement->settlement_no);
                }
            })
            ->select('settlement_excess_payments.*')
            ->get();

        \Log::debug('AddPaymentController@create - Shortage/Excess Payments Loaded', [
            'settlement_id' => $settlement->id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'shortage_payments_count' => $settlement_shortage_payments->count(),
            'shortage_payments_total' => $settlement_shortage_payments->sum('amount'),
            'excess_payments_count' => $settlement_excess_payments->count(),
            'excess_payments_total' => $settlement_excess_payments->sum('amount'),
        ]);

       $daily_collections = DailyCollection::select('*')
        ->where('settlement_id', $settlement_no)
        ->where('business_id', $business_id)
        ->get();


        $daily_collections_mapped = $daily_collections->map(function ($c) {
            return (object)[
                'id' => null,
                'settlement_no' => $c->settlement_id,
                'business_id' => $c->business_id,
                'customer_id' => null,
                'amount' => $c->current_amount,
                'customer_payment_id' => null,
                'note' => '',
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
                'customer_name' => 'Walking Customer',
                'shift_id' => $c->shift_id,
                'shift_number' => $c->shift_number,
            ];
        });

        Log::info('Fetched in add payment: ' . $daily_collections->count() . ' daily collections for settlement id: ' . $settlement_no);

        // Avoid double-counting cash: do NOT append daily_collections here.
        // Cash coming from pumper/daily collections is already represented via
        // SettlementCashPayment and PumpOperatorPayment mapping above.
        $settlement_cash_payments = $settlement_cash_payments->values();

        // DAILY CARDS
        // Card slips that are already linked to this settlement. Limit to current shift(s) when available.
        $daily_cards = DailyCard::leftJoin('contacts', 'daily_cards.customer_id', '=', 'contacts.id')
            ->leftJoin('accounts', 'daily_cards.card_type', '=', 'accounts.id')
            ->select(
                'daily_cards.*',
                'contacts.name as customer_name',
                'accounts.name as card_type_name'
            )
            ->where('daily_cards.settlement_no', $request->settlement_no)
            ->where('daily_cards.business_id', $business_id)
            ->when($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement), function ($q) use ($shift_ids_for_settlement) {
                if (count($shift_ids_for_settlement) > 1) {
                    $q->whereIn('daily_cards.shift_id', $shift_ids_for_settlement);
                } else {
                    $q->where('daily_cards.shift_id', $shift_ids_for_settlement[0]);
                }
            })
            ->get();

        $daily_cards_mapped = $daily_cards->map(function ($c) {
            return (object)[
                'id' => null,
                'settlement_no' => $c->settlement_no,
                'business_id' => $c->business_id,
                'customer_id' => $c->customer_id,
                'daily_card_id' => $c->id,

                'card_type' => $c->card_type,
                'card_type_name' => $c->card_type_name,
                'card_number' => $c->card_number,

                'amount' => $c->amount,
                'customer_payment_id' => $c->customer_payment_id,
                'note' => $c->note,
                'slip_no' => $c->slip_no,

                'customer_name' => $c->customer_name,

                'pump_operator_id' => $c->pump_operator_id,
                'shift_id' => $c->shift_id,
            ];
        });

        Log::info('Fetched in add payment: ' . $daily_cards->count() . ' daily cards for settlement no: ' . $request->settlement_no);

        // For settlement_pd type, skip daily_cards to prevent overcounting
        // Only include records explicitly linked to the current PD settlement (settlement_card_payments1 + walking_payments)
        // Prefer id so multiple saved rows with same amount are all preserved (fix: duplicate amounts disappearing after Back/refresh)
        if ($request->type !== 'settlement_pd') {
        $settlement_card_payments = $settlement_card_payments
            ->concat($daily_cards_mapped)
            ->unique(function ($item) {
                if (!empty($item->id)) {
                    return 'id_' . $item->id;
                }
                if (!empty($item->pump_payment_id)) {
                    return 'pump_' . $item->pump_payment_id;
                }
                return ($item->daily_card_id ?? 'x') . '-' . ($item->card_number ?? 'y') . '-' . $item->amount . '-' . ($item->slip_no ?? '');
            })
            ->values();
        } else {
            $settlement_card_payments = $settlement_card_payments
                ->unique(function ($item) {
                    if (!empty($item->pump_payment_id)) {
                        return 'pump_' . $item->pump_payment_id;
                    }
                    if (!empty($item->daily_card_id)) {
                        return 'daily_' . $item->daily_card_id;
                    }
                    if (!empty($item->id)) {
                        return 'id_' . $item->id;
                    }
                    return ($item->daily_card_id ?? 'x') . '-' . ($item->card_number ?? 'y') . '-' . $item->amount . '-' . ($item->slip_no ?? '');
                })
                ->values();
        }

        // DAILY VOUCHERS
        $daily_vouchers = DailyVoucher::leftJoin('contacts', 'daily_vouchers.customer_id', '=', 'contacts.id')
            ->leftJoin('settlement_credit_sale_payments', 'daily_vouchers.id', '=', 'settlement_credit_sale_payments.daily_voucher_id')
            ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
            ->select(
                'daily_vouchers.*',
                'contacts.name as customer_name',
                'products.name as product_name',
                'settlement_credit_sale_payments.qty as qty',
                'settlement_credit_sale_payments.amount as credit_amount'
            )
            ->where('daily_vouchers.settlement_no', $request->settlement_no)
            ->where('daily_vouchers.business_id', $business_id)
            ->get();

        $daily_voucher_mapped = $daily_vouchers->map(function ($v) {
            return (object)[
                'id' => null,
                'settlement_no' => $v->settlement_no,
                'business_id' => $v->business_id,
                'customer_id' => $v->customer_id,
                'daily_voucher_id' => $v->id,
                'product_id' => null,
                'order_number' => $v->voucher_order_number,
                'order_date' => $v->voucher_order_date,
                'customer_reference' => $v->customer_references,
                'price' => null,
                'discount' => null,
                'total_discount' => null,
                'sub_total' => null,
                'qty' => $v->qty,
                'amount' => $v->credit_amount ?? $v->total_amount,
                'outstanding' => $v->current_outstanding,
                'credit_limit' => null,
                'note' => null,

                'customer_name' => $v->customer_name,
                'product_name' => $v->product_name,
                'vehicle_no' => $v->vehicle_no,

                'operator_id' => $v->operator_id,
                'shift_id' => $v->shift_id
            ];
        });


        Log::info('Fetched in add payment: ' . $daily_vouchers->count() . ' daily vouchers for settlement no: ' . $request->settlement_no);

        // For settlement_pd type, skip daily_vouchers to prevent overcounting
        // Only include records explicitly linked to the current PD settlement (settlement_credit_sale_payments1 + settlement_credit_sale_payments2)
        if ($request->type !== 'settlement_pd') {
            $existing_credit_daily_voucher_ids = $settlement_credit_sale_payments
                ->pluck('daily_voucher_id')
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->all();

            if (! empty($existing_credit_daily_voucher_ids)) {
                $daily_voucher_mapped = $daily_voucher_mapped
                    ->reject(fn ($item) => ! empty($item->daily_voucher_id)
                        && in_array((string) $item->daily_voucher_id, $existing_credit_daily_voucher_ids, true))
                    ->values();
            }

        $settlement_credit_sale_payments = $settlement_credit_sale_payments
            ->concat($daily_voucher_mapped)
            ->unique(function ($item) use ($request) {
                if ($request->type === 'settlement') {
                    return implode('|', [
                        'direct',
                        $item->customer_id ?? '',
                        $item->order_number ?? '',
                        $item->order_date ?? '',
                        $item->product_id ?? ($item->product_name ?? ''),
                        (float) ($item->qty ?? 0),
                        (float) ($item->amount ?? 0),
                        (float) ($item->total_discount ?? 0),
                    ]);
                }

                if (!empty($item->id)) {
                    return 'id_' . $item->id;
                }
                if (!empty($item->daily_voucher_id)) {
                    return 'voucher_' . $item->daily_voucher_id;
                }
                if (!empty($item->order_number)) {
                    return 'order_' . $item->order_number;
                }
                return 'uniq_' . ($item->customer_id ?? '0') . '_' . ($item->amount ?? '0');
            })
            ->values();
        } else {
            $settlement_credit_sale_payments = $settlement_credit_sale_payments->values();
        }

        /**
         * @ChangedBy Afes

         *
         * @Date 25-05-2021

         * @Date 02-06-2021

         *
         * @Task 12700

         * @Task 127004
         */

        // $total_daily_collection = floatval(DailyCollection::where('pump_operator_id', $pump_operator_id)->where('business_id', $business_id)->whereNull('settlement_id')->sum('current_amount'));

        // $total_daily_collection = floatval(DailyCollection::where(['pump_operator_id' => $pump_operator_id, 'shift_id'=> $request->shift_ids])->where('business_id', $business_id)->whereNull('settlement_id')->sum('current_amount'));

        // -------------------------
        // total_daily_collection: use whereIn for shift(s) 1287
        // -------------------------
        $dailyQuery = DailyCollection::where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->whereNull('settlement_id');

        if (! empty($shift_ids)) {
            $dailyQuery->whereIn('shift_id', $shift_ids);
        } elseif (! empty($shift_id_string)) {
            $dailyQuery->where('shift_id', $shift_id_string);
        }

        $total_daily_collection = floatval($dailyQuery->sum('current_amount'));

        /**
         * @ModifiedBy Afes Oktavianus

         *
         * @Date 02-06-2021

         * @Date 03-06-2021

         *
         * @Task 127004
         */
        // Only calculate these if pump_operator_id is available
        $total_excess = 0;
        $total_shortage = 0;
        $operator_bal = 0;
        $total_commission = 0;

        if (!empty($pump_operator_id)) {
        $total_excess = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'excess');
        $total_shortage = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'shortage');
        $operator_bal = $this->transactionUtil->getPumpOperatorBalance($pump_operator_id);
        $total_commission = $this->calculateCommission($pump_operator_id, $settlement->id);
        }

        $business_details = Business::find($business_id);

        $currency_precision = $business_details->currency_precision;

        // Settlement PD: keep the modal meter total aligned with the create page summary.
        // The summary excludes pumper-dashboard payment rows and scopes the rows to this operator.
        if ($request->type !== 'settlement_pd') {
            $linked_meter_sale_rows = MeterSale::where('business_id', $business_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', (string) $settlement->id);
                    if (!empty($settlement->settlement_no)) {
                        $q->orWhere('settlement_no', (string) $settlement->settlement_no);
                    }
                })
                ->when(!empty($shift_ids) && is_array($shift_ids), function ($q) use ($shift_ids) {
                    return $q->whereIn('shift_id', $shift_ids);
                })
                ->get();

            $total_meter_sale_linked = (float) $linked_meter_sale_rows
                ->unique(function ($item) {
                    return implode('|', [
                        $item->settlement_no,
                        $item->shift_id,
                        $item->pump_id,
                        $item->starting_meter,
                        $item->closing_meter,
                        $item->qty,
                        $item->price,
                        $item->discount,
                        $item->discount_type,
                    ]);
                })
                ->sum('discount_amount');

            $total_meter_sale_shift = 0.0;
            if (!empty($shift_ids) && is_array($shift_ids)) {
                $shift_meter_sale_rows = MeterSale::where('business_id', $business_id)
                    ->whereIn('shift_id', $shift_ids)
                    ->get();

                $total_meter_sale_shift = (float) $shift_meter_sale_rows
                    ->unique(function ($item) {
                        return implode('|', [
                            $item->settlement_no,
                            $item->shift_id,
                            $item->pump_id,
                            $item->starting_meter,
                            $item->closing_meter,
                            $item->qty,
                            $item->price,
                            $item->discount,
                            $item->discount_type,
                        ]);
                    })
                    ->sum('discount_amount');
            }
        } else {
            $total_meter_sale_linked = (float) PumpOperatorMeterSale::where('business_id', $business_id)
                ->when(Schema::hasColumn('pump_operator_meter_sales', 'source'), function ($q) {
                    return $q->where('source', 'closing');
                })
                ->when(!empty($pump_operator_id), function ($q) use ($pump_operator_id) {
                    return $q->where('pump_operator_id', $pump_operator_id);
                })
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', (string) $settlement->id);
                    if (!empty($settlement->settlement_no)) {
                        $q->orWhere('settlement_no', (string) $settlement->settlement_no);
                    }
                })
                ->when(!empty($shift_ids) && is_array($shift_ids), function ($q) use ($shift_ids) {
                    return $q->whereIn('shift_id', $shift_ids);
                })
                ->sum('balance');

            $total_meter_sale_shift = 0.0;
            if (!empty($shift_ids) && is_array($shift_ids)) {
                $total_meter_sale_shift = (float) PumpOperatorMeterSale::where('business_id', $business_id)
                    ->when(Schema::hasColumn('pump_operator_meter_sales', 'source'), function ($q) {
                        return $q->where('source', 'closing');
                    })
                    ->whereIn('shift_id', $shift_ids)
                    ->when(!empty($pump_operator_id), function ($q) use ($pump_operator_id) {
                        return $q->where('pump_operator_id', $pump_operator_id);
                    })
                    ->sum('balance');
            }
        }
        $total_meter_sale = ($total_meter_sale_linked == 0.0 && $total_meter_sale_shift > 0.0)
            ? $total_meter_sale_shift
            : $total_meter_sale_linked;

        $other_sales = OtherSale::where('settlement_no', $settlement->id)->get();

        $total_other_sale = $other_sales->sum('sub_total') - $other_sales->sum('discount_amount');

        if (str_contains($settlement->settlement_no, 'SET-SW')) {

            $total_other_sale = $other_sales->sum('sub_total');

        }

        $show_shift_no = [];

        if ($request->shift_ids) {

            $raw_shift_ids = $request->shift_ids;
            $shift_ids = is_array($raw_shift_ids)
                ? array_filter(array_map('intval', $raw_shift_ids))
                : explode(',', $raw_shift_ids);

            $show_shift_no = $shift_ids;


            $pump_operator_total_other_sale = PumpOperatorOtherSale::join('products', 'products.id', '=', 'pump_operator_other_sales.product_id')

                ->leftJoin('variations', 'products.id', 'variations.product_id')

                ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')

                ->whereIn('pump_operator_other_sales.shift_id', $shift_ids);

            $pump_operator_total_other_sale = $pump_operator_total_other_sale->join('pump_operator_assignments', function ($join) {

                $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')

                    ->where('pump_operator_assignments.status', 'close')

                    ->whereRaw('pump_operator_assignments.id = (

                        SELECT MAX(poa.id)

                        FROM pump_operator_assignments poa

                        WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"

                    )');

            });

            $pump_operator_total_other_sale = $pump_operator_total_other_sale->sum('sub_total');

            $total_other_sale = $total_other_sale + $pump_operator_total_other_sale;

        }

        $total_other_income = OtherIncome::where('settlement_no', $settlement->id)->sum('sub_total');

        // Customer payments are actual collections (payments), not sales.
        // Direct Settlement must NOT include customer payments in Total Amount.
        $total_customer_payment = CustomerPayment::where('settlement_no', $settlement->id)->sum('sub_total');

        // NOTE: Total Amount calculation moved below, after credit sales are calculated
        // This ensures credit sales are included in Total Amount for direct settlement

        // dd( $total_amount);
        // CRITICAL: Use the filtered collections for totals, not direct queries
        // This ensures we only count payments from the current shift(s)
        $total_settlement_cash_payment = $settlement_cash_payments->sum('amount');

        $total_settlement_loan_payment = $settlement_loan_payments->sum('amount');

        $total_settlement_drawings_payment = $settlement_drawings_payments->sum('amount');

        $total_settlement_cash_deposit = $settlement_cash_deposits->sum('amount');

        $total_settlement_card_payment = $settlement_card_payments->sum('amount');

        $total_settlement_customer_loan = $settlement_customer_loans->sum('amount');

        $total_settlement_cheque_payment = $settlement_cheque_payments->sum('amount');

        $total_settlement_credit_sale_payment = $settlement_credit_sale_payments->sum('amount');

        $total_settlement_credit_sale_discount = $settlement_credit_sale_payments->sum('total_discount');

        $total_settlement_credit_sale_net = $total_settlement_credit_sale_payment - $total_settlement_credit_sale_discount;

        // CRITICAL: Calculate Total Amount ONCE using consistent data sources
        // Direct Settlement rules:
        // - Credit Sales are PAYMENTS (collections), NOT sales -> they go to Total Paid, NOT Total Amount
        // - Total Amount = Meter Sales + Other Sales + Other Income + Operator Balance (Current Short/Excess)
        // - Total Paid = All payments including credit sales
        // - Customer payments are also payments -> contribute to Total Paid, not Total Amount
        // IMPORTANT: Credit sales should NEVER be included in Total Amount for Direct Settlement
        // CRITICAL: Operator balance (Current Short/Excess) must be included in Total Amount
        // - If operator has a shortage (positive balance), they owe this amount -> add to Total Amount
        // - If operator has excess (negative balance), they have overpaid -> add to Total Amount (will be negative, reducing total)

        if ($request->type === 'settlement') {
            // Direct Settlement: Credit sales are payments, not sales - DO NOT include in Total Amount
            // BUT include operator balance (Current Short/Excess)
            $total_amount = number_format(($total_meter_sale + $total_other_sale + $total_other_income + $operator_bal), $currency_precision, '.', '');
        } elseif ($request->type === 'settlement_pd') {
            // Settlement PD Payment Due is the sales due only. Credit sales are counted in Total Paid.
            $total_amount = number_format(($total_meter_sale + $total_other_sale), $currency_precision, '.', '');
        } else {
            // Default for other providers.
            $total_amount = number_format(($total_meter_sale + $total_other_sale + $total_other_income + $total_customer_payment + $operator_bal), $currency_precision, '.', '');
        }

        // REMOVED: Direct query fallback that caused Total Paid inconsistencies
        // The filtered $settlement_credit_sale_payments already contains all relevant credit sales
        // Using the same filtered data for both Total Amount and Total Paid ensures consistency

        $total_settlement_expense_payment = $settlement_expense_payments->sum('amount');

        $total_settlement_shortage_payment = $settlement_shortage_payments->sum('amount');

        $total_settlement_excess_payment = $settlement_excess_payments->sum('amount');

        /**
         * Excess payments are stored as negative values (validated in saveExcessPayment).
         * They must be added as-is to reduce total_paid, offsetting the overpayment.
         */
        $total_settlement_excess_payment_for_total_paid = $total_settlement_excess_payment;

        if (env('PETRO_SETTLEMENT_PD_DEBUG', false) && $request->type === 'settlement_pd') {
            Log::debug('SettlementPD AddPayment: totals (pre-balance)', [
                'business_id' => $business_id,
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'shift_ids' => $shift_ids,
                'meter_sales_linked_total' => $total_meter_sale_linked,
                'meter_sales_shift_total' => $total_meter_sale_shift,
                'meter_sales_final_total' => $total_meter_sale,
                'total_amount' => $total_amount,
                'total_settlement_cash_payment' => $total_settlement_cash_payment,
                'total_settlement_card_payment' => $total_settlement_card_payment,
                'total_settlement_cheque_payment' => $total_settlement_cheque_payment,
                'total_settlement_credit_sale_payment' => $total_settlement_credit_sale_payment,
                'total_settlement_credit_sale_discount' => $total_settlement_credit_sale_discount,
                'total_settlement_expense_payment' => $total_settlement_expense_payment,
                'total_settlement_shortage_payment' => $total_settlement_shortage_payment,
                'total_settlement_excess_payment' => $total_settlement_excess_payment,
                'total_settlement_excess_payment_for_total_paid' => $total_settlement_excess_payment_for_total_paid,
            ]);
        }

        // CRITICAL: Do NOT include $total_daily_collection in total_paid calculation
        // Cash from DailyCollection is already represented via SettlementCashPayment records
        // Including both would cause double-counting of cash payments
        // The comment on line 1548-1550 confirms: "Avoid double-counting cash: do NOT append daily_collections here"
        // Shortage payments = missing money = increases what's owed (ADD to total_paid)
        // CRITICAL: For Direct Settlement (type === 'settlement'), credit sales are SALES (in Total Amount)
        // AND also count as "paid" (accounts receivable) -> must be included in total_paid
        // This ensures balance calculation is correct: Balance = Total Amount - Total Paid
        $is_direct_settlement = ($request->type === 'settlement');
        // For direct settlement, include credit sales in total_paid (they're accounts receivable)
        // For settlement_pd, credit sales are already payments, so include them
        $credit_sales_component_in_paid = $total_settlement_credit_sale_net;
        $customer_payment_component_in_paid = ($is_direct_settlement || $request->type === 'settlement_pd')
            ? $total_customer_payment
            : 0;

        $total_paid = number_format(($total_settlement_customer_loan +

            $total_settlement_loan_payment +

            $total_settlement_cash_deposit +

            // REMOVED: $total_daily_collection + (causes double-counting with SettlementCashPayment)

            $total_settlement_cash_payment +

            $total_settlement_card_payment +

            $total_settlement_cheque_payment +

            $credit_sales_component_in_paid +

            $total_settlement_expense_payment +

            $total_settlement_shortage_payment +

            $total_settlement_excess_payment_for_total_paid +

            $total_settlement_drawings_payment +

            $customer_payment_component_in_paid

        ), $currency_precision, '.', '');

        $total_balance = number_format($total_amount - $total_paid, $currency_precision, '.', '');

        // For direct settlement, balance can be negative (excess) or positive (shortage)
        // Don't use abs() as it removes the sign which is needed for proper balance calculation
        // The balance sign indicates whether there's an excess (negative) or shortage (positive)
        if ($request->type !== 'settlement') {
            // For non-direct settlement, keep existing behavior (abs for display)
            $total_balance = abs($total_balance);
        }
        //  dd(  $total_paid);
        // dd($total_balance);

        // Always log for direct settlement to debug credit sales calculation
        if ($request->type === 'settlement') {
            // Debug: Check what credit sales were found
            $found_credit_sale_ids = $settlement_credit_sale_payments->pluck('id')->toArray();
            $found_credit_sale_settlement_nos = $settlement_credit_sale_payments->pluck('settlement_no')->unique()->toArray();

            Log::info('DirectSettlement AddPayment: totals calculation', [
                'business_id' => $business_id,
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'shift_ids' => $shift_ids,
                'credit_sales_count' => $settlement_credit_sale_payments->count(),
                'credit_sales_ids_found' => $found_credit_sale_ids,
                'credit_sales_settlement_nos_found' => $found_credit_sale_settlement_nos,
                'credit_sales_payment_total' => (float) $total_settlement_credit_sale_payment,
                'credit_sales_discount_total' => (float) $total_settlement_credit_sale_discount,
                'credit_sales_net' => (float) $total_settlement_credit_sale_net,
                'credit_sales_component_in_paid' => (float) $credit_sales_component_in_paid,
                'meter_sales_total' => (float) $total_meter_sale,
                'other_sale_total' => (float) $total_other_sale,
                'other_income_total' => (float) $total_other_income,
                'customer_payment_total' => (float) $total_customer_payment,
                'cash_payment_total' => (float) $total_settlement_cash_payment,
                'card_payment_total' => (float) $total_settlement_card_payment,
                'cheque_payment_total' => (float) $total_settlement_cheque_payment,
                'total_amount' => (float) $total_amount,
                'total_paid' => (float) $total_paid,
                'total_balance' => (float) $total_balance,
            ]);

            if ($total_settlement_credit_sale_net > 0 && (float) $total_amount < (float) $total_settlement_credit_sale_net) {
                Log::warning('DirectSettlement AddPayment: total_amount < credit_sales_net (unexpected)', [
                    'settlement_id' => $settlement->id,
                    'total_amount' => (float) $total_amount,
                    'credit_sales_net' => (float) $total_settlement_credit_sale_net,
                ]);
            }
        }

        if (env('PETRO_SETTLEMENT_PD_DEBUG', false) && $request->type === 'settlement_pd') {
            Log::debug('SettlementPD AddPayment: finalize preconditions', [
                'business_id' => $business_id,
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'shift_ids' => $shift_ids,
                'total_amount' => (float) $total_amount,
                'total_paid' => (float) $total_paid,
                'total_balance' => (float) $total_balance,
                'finalize_button_should_show' => ((float) $total_balance === 0.0),
            ]);
        }

        $loans_given_group_id = AccountGroup::getGroupByName('Loans Given');

        $drawings_group_id = AccountGroup::getGroupByName('Owners Drawings');

        $bank_account_group_id = AccountGroup::getGroupByName('Bank Account');

        $bank_accounts = Account::where('business_id', $business_id)->where('asset_type', $bank_account_group_id->id)->pluck('name', 'id');

        if (! empty($loans_given_group_id)) {

            $loans_given = Account::where('business_id', $business_id)->where('asset_type', $loans_given_group_id->id)->pluck('name', 'id');

        } else {

            $loans_given = [];

        }

        if (! empty($drawings_group_id)) {

            $drawings_acc = Account::where('business_id', $business_id)->where('asset_type', $drawings_group_id->id)->pluck('name', 'id');

        } else {

            $drawings_acc = [];

        }

        $message = $package_details['notsubscribed_message_content'] ?? null;

        $font_family = $package_details['ns_font_family'] ?? null;

        $font_color = $package_details['ns_font_color'] ?? null;

        $font_size = $package_details['ns_font_size'] ?? null;

        $background_color = $package_details['ns_background_color'] ?? null;

        // dd($settlement_card_payments, $settlement_cash_payments);

        $message = ! empty($message) ? $message : 'You have not subscribed to this module!';

        // Convert shift_ids to shift_numbers for display
        $show_shift_no = '';
        if (!empty($shift_ids) && count($shift_ids) > 0) {
            // Get shift_numbers from PumpOperatorAssignment using shift_ids
            $shift_numbers = \Modules\PetroGeneral\Entities\PumpOperatorAssignment::whereIn('shift_id', $shift_ids)
                ->where('pump_operator_id', $pump_operator_id)
                ->distinct()
                ->pluck('shift_number')
                ->toArray();

            if (!empty($shift_numbers)) {
                $show_shift_no = implode(', ', $shift_numbers);
            } else {
                // Fallback: show shift_ids if shift_numbers not found
                $show_shift_no = is_array($shift_ids) ? implode(', ', $shift_ids) : $shift_ids;
            }
        } else {
            // If no shift_ids provided, try to get from settlement's work_shift
            if (!empty($settlement) && !empty($settlement->work_shift)) {
                $work_shifts = is_array($settlement->work_shift) ? $settlement->work_shift : [$settlement->work_shift];
                $shift_numbers = \Modules\PetroGeneral\Entities\PumpOperatorAssignment::whereIn('shift_id', $work_shifts)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->distinct()
                    ->pluck('shift_number')
                    ->toArray();
                if (!empty($shift_numbers)) {
                    $show_shift_no = implode(', ', $shift_numbers);
                }
            }
        }

        // Get last credit sale details for prefilling
        $lastCreditSaleDetails = $this->getLastCreditSaleDetails($business_id);

        // REMOVED: This was incorrectly recalculating total_amount and adding credit sales back
        // Credit sales should NEVER be in Total Amount for Direct Settlement
        // The correct calculation is already done above at line 2510

        if ($request->type == 'settlement_pd') {
            $settlement_pd_add_payment_view = $request->input('source') === 'petropd'
                ? 'petropd::pd_settlement.partials.add_payment'
                : 'petrogeneral::settlement_pd.partials.add_payment';

            return view($settlement_pd_add_payment_view)->with(compact(

                'operator_bal',

                'business',

                'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',

                'bank_accounts',

                'is_settlement_page',

                'total_settlement_cash_deposit',

                'settlement',

                'cash_denoms',

                'pump_operator',

                'customer_payments_tab',

                'settlement_cash_payments',

                'settlement_pos_sales',

                'settlement_customer_loans',

                'cash_total',

                'settlement_loan_payments',

                'settlement_drawings_payments',

                'settlement_card_payments',

                'settlement_cheque_payments',

                'settlement_credit_sale_payments',

                'settlement_expense_payments',

                'settlement_shortage_payments',

                'settlement_excess_payments',

                'settlement_cash_deposits',

                'payment_types',

                'expense_accounts',

                'expense_categories',

                'expense_no',

                'customers',

                'products',

                'card_types',

                'total_daily_collection',

                'total_commission',

                'total_amount',

                'total_paid',

                'total_balance',

                'total_excess',

                'total_shortage',

                'loans_given',

                'drawings_acc',

                'only_walkin',

                'walkin',

                'provider',

                'credit_customers',

                'show_shift_no',

                'lastCreditSaleDetails',

                'pumper_credit_sale_payments',

                'no_change'

            ));
        } elseif ($request->type == 'settlement') {

            return view('petrogeneral::settlement.partials.add_payment')->with(compact(

                'operator_bal',

                'business',

                'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',

                'bank_accounts',

                'is_settlement_page',

                'total_settlement_cash_deposit',

                'settlement',

                'cash_denoms',

                'pump_operator',

                'customer_payments_tab',

                'settlement_cash_payments',

                'settlement_pos_sales',

                'cash_total',

                'settlement_customer_loans',

                'settlement_loan_payments',

                'settlement_drawings_payments',

                'settlement_card_payments',

                'settlement_cheque_payments',

                'settlement_credit_sale_payments',

                'settlement_expense_payments',

                'settlement_shortage_payments',

                'settlement_excess_payments',

                'settlement_cash_deposits',

                'payment_types',

                'expense_accounts',

                'expense_categories',

                'expense_no',

                'customers',

                'products',

                'card_types',

                'total_daily_collection',

                'total_commission',

                'total_amount',

                'total_paid',

                'total_balance',

                'total_excess',

                'total_shortage',

                'loans_given',

                'drawings_acc',

                'only_walkin',

                'walkin',

                'provider',

                'credit_customers',

                'show_shift_no',

                'lastCreditSaleDetails',

                'pumper_credit_sale_payments'

            ));
        }
        return view('settlementsw::swsettlement.partials.add_payment')->with(compact(

            'operator_bal',

            'business',

            'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',

            'bank_accounts',

            'is_settlement_page',

            'total_settlement_cash_deposit',

            'settlement',

            'cash_denoms',

            'pump_operator',

            'customer_payments_tab',

            'settlement_cash_payments',

            'cash_total',

            'settlement_customer_loans',

            'settlement_loan_payments',

            'settlement_drawings_payments',

            'settlement_card_payments',

            'settlement_cheque_payments',

            'settlement_credit_sale_payments',

            'settlement_expense_payments',

            'settlement_shortage_payments',

            'settlement_excess_payments',

            'settlement_cash_deposits',

            'payment_types',

            'expense_accounts',

            'expense_categories',

            'expense_no',

            'customers',

            'products',

            'card_types',

            'total_daily_collection',

            'total_commission',

            'total_amount',

            'total_paid',

            'total_balance',

            'total_excess',

            'total_shortage',

            'loans_given',

            'drawings_acc',

            'only_walkin',

            'walkin',

            'provider',

            'credit_customers',

            'show_shift_no',

            'lastCreditSaleDetails'

        ));

    }

    /**
     * Get the last credit sale details for prefilling
     *
     * @param int $business_id
     * @return array
     */
    public function getLastCreditSaleDetails($business_id)
    {
        // Check package-level setting first (from Super Admin checkbox)
        $subscription = Subscription::current_subscription($business_id);
        $package_details = $subscription->package_details ?? [];

        // Explicitly check if the package-level setting is enabled (value should be 1 when checked)
        $packageEnabled = !empty($package_details['prefill_credit_sale_details']) &&
                          $package_details['prefill_credit_sale_details'] == 1;

        // Package-level setting must be enabled (Super Admin checkbox controls this)
        if (!$packageEnabled) {
            return [];
        }

        // Also check business-level setting (from issue_customer_bill_settings)
        // This can override the package-level setting if it exists
        $issueBillSetting = IssueCustomerBillSetting::where('business_id', $business_id)->first();

        if ($issueBillSetting) {
            // Business-level setting exists - check if it's explicitly disabled
            // Handle both boolean true/false and integer 1/0 (depending on DB driver)
            $businessEnabled = $issueBillSetting->prefill_credit_sale_details === true ||
                              $issueBillSetting->prefill_credit_sale_details === 1 ||
                              $issueBillSetting->prefill_credit_sale_details === '1';

            // If business-level setting is disabled, don't prefill
            if (!$businessEnabled) {
                return [];
            }
        }
        // If no business-level setting exists, proceed with package-level setting

        // Get the last credit sale payment for this business
        $lastCreditSale = SettlementCreditSalePayment::where('business_id', $business_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastCreditSale) {
            return [];
        }

        return [
            'customer_id' => $lastCreditSale->customer_id,
            'product_id' => $lastCreditSale->product_id,
            'price' => $lastCreditSale->price,
            'unit_discount' => $lastCreditSale->unit_discount ?? 0,
            'qty' => $lastCreditSale->qty,
            'customer_reference' => $lastCreditSale->customer_reference,
        ];
    }

    public function getExpenseNumber($settlement_id)
    {

        $settlement_int = preg_match_all('!\d+!', $settlement_id, $matches);

        $ref_no_prefixes = request()->session()->get('business.ref_no_prefixes');

        $expense_prefix = ! empty($ref_no_prefixes['expense']) ? $ref_no_prefixes['expense'] : '';

        $expense_count = SettlementExpensePayment::where('settlement_no', $settlement_id)->count();

        $expense_no = $expense_prefix . '-' . $settlement_int . '-' . ($expense_count + 1);

        return $expense_no;

    }

    /**
     * Store a newly created resource in storage.

     *
     * @param  pump_operator_id

     * @param  settlement_no

     * @return Response
     */
    public function calculateCommission($pump_operator_id, $settlement_id)
    {

        $all_sales = OtherSale::where('settlement_no', $settlement_id)->get();

        $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();

        $pump_operator_commission_type = isset($pump_operator->commission_type) ? $pump_operator->commission_type : '';

        $pump_operator_commission_value = isset($pump_operator->commission_ap) ? $pump_operator->commission_ap : 0;

        if ($pump_operator_commission_type == 'fixed') {

            $total_sales_counts = $all_sales->count();

            return $total_sales_counts * $pump_operator_commission_value;

        } elseif ($pump_operator_commission_type == 'percentage') {

            $total_sales_commission = 0;

            return $total_sales_commission;

        } else {

            return 0.00;

        }

    }

    /**
     * Store a newly created resource in storage.

     *
     * @return Response
     */
    public function store(Request $request)
    {}

    /**
     * Show the specified resource.

     *
     * @return Response
     */
    public function show()
    {

        // return view('petrogeneral::show');

    }

    /**
     * Show the form for editing the specified resource.

     *
     * @return Response
     */
    public function edit()
    {

        // return view('petrogeneral::edit');

    }

    /**
     * Update the specified resource in storage.

     *
     * @return Response
     */
    public function update(Request $request)
    {}

    /**
     * Remove the specified resource from storage.

     *
     * @return Response
     */
    public function destroy()
    {}

    /**
     * add cash payment data to db

     *
     * @return Response
     */
    public function saveCashPayment(Request $request)
    {
        Log::info('Request Data for saveCashPayment in AddPaymentController:', ['request' => $request->all()]);

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('business.id');

            // Retrieve Settlement
            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // If editing existing settlement, mark so ledger will be rebuilt on save
            if ($request->has('is_edit') && $request->is_edit) {
                Settlement::where('id', $settlement->id)->update(['is_edit' => 1]);
            }

            // Check for duplicate settlement cash payment to prevent accidental double-submission
            // Match by settlement, customer, amount, and recent timestamp (within last 5 seconds)
            $duplicate_check = SettlementCashPayment::where('settlement_no', $settlement->id)
                ->where('customer_id', $request->customer_id)
                ->where('amount', $request->amount)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($duplicate_check) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => __('petrogeneral::lang.duplicate_payment_detected'),
                ];
            }

            // DAY1-ORPHAN: pump_payment_id linked further down — orphan-path insert here.
            $settlement_cash_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_cash_payments', [
                    'amount'      => $request->amount,
                    'customer_id' => $request->customer_id,
                    'note'        => $request->note,
                ]);

            // Link matching PumpOperatorPayment if exists
            $work_shifts = $settlement->work_shift;
            if (is_string($work_shifts)) {
                $work_shifts = json_decode($work_shifts, true) ?? [];
            }
            // fallback if json_decode failed or it was not json
            if (!is_array($work_shifts)) {
                $work_shifts = explode(',', $settlement->work_shift ?? '');
            }
            $work_shifts = array_filter(array_map('trim', $work_shifts));

            if ($request->has('pump_payment_id')) {
                $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('id', $request->pump_payment_id)
                    ->first();
            } else {
                if (!empty($work_shifts)) {
                    $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->whereIn('shift_id', $work_shifts)
                        ->where('payment_type', 'cash')
                        ->where('payment_amount', $request->amount)
                        ->where(function($q) {
                            $q->whereNull('is_used')->orWhere('is_used', 0);
                        })
                        ->first();
                } else {
                    $pump_payment = null;
                }
            }

                if ($pump_payment) {
                    $pump_payment->is_used = 1;
                    $pump_payment->parent_id = $settlement_cash_payment->id;
                    $pump_payment->settlement_no = $settlement->id;
                    $pump_payment->save();

                    // Also update the fake ID ref if provided
                    $settlement_cash_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                        ->editCashPayment($business_id, $settlement_cash_payment->id, [
                            'pump_payment_id' => $pump_payment->id,
                        ]);
                }


            // Update Settlement edit flag if needed
            if ($request->has('is_edit')) {
                $settlement->update(['is_edit' => $request->is_edit]);
            }

            // CRITICAL: Do NOT create Transaction, TransactionPayment, AccountTransaction, or ContactLedger here
            // These should only be created when the settlement is saved (finalized).
            // Creating them here causes duplicates and data inconsistency (missing transaction links).

            // CRITICAL: Do NOT create Transaction, TransactionPayment, or AccountTransaction here
            // These should only be created when the settlement is saved (in SettlementPDController@store)
            // Creating them here causes duplicates when the settlement is saved
            // The SettlementCashPayment record is sufficient for tracking the payment until settlement is saved

            DB::commit();

            $output = [
                'success'                    => true,
                'settlement_cash_payment_id' => $settlement_cash_payment->id,
                // include linked pump payment if any so UI can show edit button immediately
                'pump_payment_id'            => isset($pump_payment) ? $pump_payment->id : null,
                'msg'                        => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function saveCustomerLoan(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement->id,

                'amount'        => $request->amount,

                'customer_id'   => $request->customer_id,

                'note'          => $request->note,

            ];

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $settlement_cash_payment = SettlementCustomerLoan::create($data);

            $output = [

                'success'                     => true,

                'settlement_customer_loan_id' => $settlement_cash_payment->id,

                'msg'                         => __('petrogeneral::lang.success'),

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

    public function saveLoanPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement->id,

                'amount'        => $request->amount,

                'loan_account'  => $request->loan_account,

                'note'          => $request->note,

            ];

            $settlement_loan_payment = SettlementLoanPayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                    => true,

                'settlement_loan_payment_id' => $settlement_loan_payment->id,

                'msg'                        => __('petrogeneral::lang.success'),

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

    public function saveDrawingPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement->id,

                'amount'        => $request->amount,

                'loan_account'  => $request->loan_account,

                'note'          => $request->note,

            ];

            $settlement_loan_payment = SettlementDrawingPayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                    => true,

                'settlement_loan_payment_id' => $settlement_loan_payment->id,

                'msg'                        => __('petrogeneral::lang.success'),

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

    public function saveCashDeposit(Request $request)
    {

        try {
            $business_id = $request->session()->get('business.id');
            
            if (empty($request->settlement_no)) {
                 return response()->json([
                    'success' => 0,
                    'msg' => 'Settlement number is missing.'
                ]);
            }

            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->first();

            if (empty($settlement)) {
                 // Try by ID fallback
                 $settlement = Settlement::where('id', $request->settlement_no)
                    ->where('business_id', $business_id)
                    ->first();
            }

            if (empty($settlement)) {
                \Log::error('saveCashDeposit: Settlement not found', [
                    'settlement_no' => $request->settlement_no,
                    'business_id' => $business_id
                ]);
                return response()->json([
                    'success' => 0,
                    'msg' => 'Settlement not found. Please ensure you have created the settlement and try again.'
                ]);
            }

            $data = [
                'business_id'    => $business_id,
                'settlement_no'  => $settlement->id,
                'bank_id'        => $request->bank_id,
                'amount'         => $request->cash_deposit_amount,
                'account_no'     => $request->account,
                'time_deposited' => $request->time,
            ];

            $settlement_cash_payment = SettlementCashDeposit::create($data);

            // Update is_edit status
            $settlement->update(['is_edit' => $request->is_edit ?? 0]);

            $output = [
                'success' => 1,
                'settlement_cash_payment_id' => $settlement_cash_payment->id,
                'msg' => __('petrogeneral::lang.success')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return response()->json($output);
    }


    /**
     * delete cash payment data to db

     *
     * @return Response
     */
    public function deleteCashPayment($id, Request $request)
    {

        try {

            $pumpPaymentId = $request->input('pump_payment_id');
            if (! empty($pumpPaymentId)) {
                $pumpPayment = PumpOperatorPayment::findOrFail($pumpPaymentId);
                $pumpPayment->is_used = 1;
                $pumpPayment->save();

                $output = [
                    'success' => true,
                    'amount'  => $pumpPayment->payment_amount,
                    'msg'     => __('petrogeneral::lang.success'),
                ];

                return $output;
            }

            $payment = SettlementCashPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            // Delete corresponding ContactLedger entry if it exists (linked via settlement_no in note)
            $settlement_record = Settlement::find($payment->settlement_no);
            if ($settlement_record) {
                ContactLedger::where('transaction_id', null)
                    ->where('amount', $amount)
                    ->where('note', 'like', '%' . $settlement_record->settlement_no . '%')
                    ->where('sub_type', 'cash_payment')
                    ->forceDelete();
            }

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

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

    public function deleteCustomerLoan($id)
    {

        try {

            $payment = SettlementCustomerLoan::where('id', $id)->first();

            // Check if payment exists before trying to access its properties
            if (!$payment) {
                return [
                    'success' => false,
                    'msg'     => __('petrogeneral::lang.customer_loan_not_found'),
                ];
            }

            // Update settlement if settlement_no exists and is numeric
            if (!empty($payment->settlement_no)) {
                if (is_numeric($payment->settlement_no)) {
            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                } else {
                    // If settlement_no is a string (like "ST7"), find by settlement_no
                    Settlement::where('settlement_no', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                }
            }

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

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

    public function deleteLoanPayment($id)
    {

        try {

            $payment = SettlementLoanPayment::where('id', $id)->first();

            // Check if payment exists before trying to access its properties
            if (!$payment) {
                return [
                    'success' => false,
                    'msg'     => __('petrogeneral::lang.loan_payment_not_found'),
                ];
            }

            // Update settlement if settlement_no exists and is numeric
            if (!empty($payment->settlement_no)) {
                if (is_numeric($payment->settlement_no)) {
            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                } else {
                    // If settlement_no is a string (like "ST7"), find by settlement_no
                    Settlement::where('settlement_no', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                }
            }

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

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

    public function deleteDrawingPayment($id)
    {

        try {

            $payment = SettlementDrawingPayment::where('id', $id)->first();

            // Check if payment exists before trying to access its properties
            if (!$payment) {
                return [
                    'success' => false,
                    'msg'     => __('petrogeneral::lang.drawing_payment_not_found'),
                ];
            }

            // Update settlement if settlement_no exists and is numeric
            if (!empty($payment->settlement_no)) {
                if (is_numeric($payment->settlement_no)) {
            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                } else {
                    // If settlement_no is a string (like "ST7"), find by settlement_no
                    Settlement::where('settlement_no', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                }
            }

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

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

    public function deleteCashDeposit($id)
    {

        try {

            $payment = SettlementCashDeposit::where('id', $id)->first();

            // Check if payment exists before trying to access its properties
            if (!$payment) {
                return [
                    'success' => false,
                    'msg'     => __('petrogeneral::lang.cash_deposit_not_found'),
                ];
            }

            // Update settlement if settlement_no exists and is numeric
            if (!empty($payment->settlement_no)) {
                if (is_numeric($payment->settlement_no)) {
            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                } else {
                    // If settlement_no is a string (like "ST7"), find by settlement_no
                    Settlement::where('settlement_no', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                }
            }

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

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
     * add card payment data to db

     *
     * @return Response
     */
    public function saveCardPayment(Request $request)
    {
        Log::info('Request Data for saveCardPayment in AddPaymentController:', ['request' => $request->all()]);

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('business.id');

            // Check for duplicate slip number
            // $slip_no = trim(str_replace(' ', '', $request->slip_no));
            $slip_no = preg_replace('/\s+/', '', $request->slip_no);
            $today   = \Carbon::now()->format('Y-m-d');

            $existingRecord = SettlementCardPayment::where('slip_no', $slip_no)
                ->whereDate('created_at', $today)
                ->exists();

            if (! empty($slip_no) && $existingRecord) {
                throw new \Exception(__('messages.duplicate_slip'));
            }

            // Retrieve Settlement
            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // Check for duplicate settlement card payment to prevent accidental double-submission
            // Match by settlement, customer, amount, and recent timestamp (within last 5 seconds)
            $duplicate_check = SettlementCardPayment::where('settlement_no', $settlement->id)
                ->where('customer_id', $request->customer_id)
                ->where('amount', $request->amount)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($duplicate_check) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => __('petrogeneral::lang.duplicate_payment_detected'),
                ];
            }

            $pump_payment = $this->findMatchingPumpCardPayment($settlement, $request, $business_id);
            $linked_daily_card = ! empty($pump_payment)
                ? $this->findLinkedDailyCardForPumpPayment($pump_payment, $business_id)
                : null;

            $settlement_card_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_card_payments', [
                    'amount'      => $request->amount,
                    'card_type'   => $request->card_type,
                    'card_number' => $request->card_number,
                    'customer_id' => $request->customer_id,
                    'note'        => $request->note,
                    'slip_no'     => $slip_no,
                    'pump_payment_id' => optional($pump_payment)->id,
                    'daily_card_id' => $linked_daily_card->id ?? null,
                ]);

            if (! empty($pump_payment)) {
                $pump_payment->is_used = 1;
                $pump_payment->parent_id = $settlement_card_payment->id;
                $pump_payment->settlement_no = $settlement->id;
                $pump_payment->save();
            }

            // Update Settlement edit flag if needed
            if ($request->has('is_edit')) {
                $settlement->update(['is_edit' => $request->is_edit]);
            }

            // CRITICAL: Do NOT create Transaction, TransactionPayment, AccountTransaction, or ContactLedger here
            // These should only be created when the settlement is saved (finalized).
            // Creating them here causes duplicates and data inconsistency (missing transaction links).

            // CRITICAL: Do NOT create Transaction, TransactionPayment, or AccountTransaction here
            // These should only be created when the settlement is saved (in SettlementPDController@store)
            // Creating them here causes duplicates when the settlement is saved
            // The SettlementCardPayment record is sufficient for tracking the payment until settlement is saved

            DB::commit();

            $output = [
                'success'                    => true,
                'settlement_card_payment_id' => $settlement_card_payment->id,
                'pump_payment_id'            => isset($pump_payment) ? $pump_payment->id : null,
                'msg'                        => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => $e->getMessage() === __('messages.duplicate_slip')
                    ? __('messages.duplicate_slip')
                    : __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * delete card payment data to db

     *
     * @return Response
     */
    public function deleteCardPayment($id, Request $request)
    {

        try {

            $pumpPaymentId = $request->input('pump_payment_id');
            if (! empty($pumpPaymentId)) {
                $pumpPayment = PumpOperatorPayment::findOrFail($pumpPaymentId);
                $pumpPayment->is_used = 1;
                $pumpPayment->save();

                $output = [
                    'success' => true,
                    'amount'  => $pumpPayment->payment_amount,
                    'msg'     => __('petrogeneral::lang.success'),
                ];

                return $output;
            }

            $payment = SettlementCardPayment::where('id', $id)->first();

            if (empty($payment)) {
                throw new \Exception('Card payment not found');
            }

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;
            $business_id = $request->session()->get('business.id');

            app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                ->deletePaymentLine($business_id, 'settlement_card_payments', $payment->id);

            $output = [

                'success' => true,

                'amount'  => $amount,

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
     * add cheque payment data to db

     *
     * @return Response
     */
    public function saveChequePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            // Check for duplicate settlement cheque payment to prevent accidental double-submission
            // Match by settlement, customer, amount, and recent timestamp (within last 5 seconds)
            $duplicate_check = SettlementChequePayment::where('settlement_no', $settlement->id)
                ->where('customer_id', $request->customer_id)
                ->where('amount', $request->amount)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($duplicate_check) {
                return [
                    'success' => false,
                    'msg' => __('petrogeneral::lang.duplicate_payment_detected'),
                ];
            }

            $data = [

                'business_id'              => $business_id,

                'settlement_no'            => $settlement->id,

                'amount'                   => $request->amount,

                'bank_name'                => $request->bank_name,

                'cheque_number'            => $request->cheque_number,

                'cheque_date'              => \Carbon::parse($request->cheque_date)->format('Y-m-d'),

                'customer_id'              => $request->customer_id,

                'note'                     => $request->note,

                'post_dated_cheque'        => $request->post_dated_cheque,

                'update_post_dated_cheque' => $request->update_post_dated_cheque,

            ];

            // DAY1-ORPHAN: pump_payment_id is linked below — orphan-path insert here.
            $settlement_cheque_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_cheque_payments', $data);

            // try linking a pump payment if present
            $pump_payment = null;
            $work_shifts = $settlement->work_shift;
            if (is_string($work_shifts)) {
                $work_shifts = json_decode($work_shifts, true) ?? [];
            }
            if (!is_array($work_shifts)) {
                $work_shifts = explode(',', $settlement->work_shift ?? '');
            }
            $work_shifts = array_filter(array_map('trim', $work_shifts));

            if ($request->has('pump_payment_id')) {
                $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('id', $request->pump_payment_id)
                    ->first();
            } elseif (!empty($work_shifts)) {
                $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_id', $work_shifts)
                    ->where('payment_type', 'cheque')
                    ->where('payment_amount', $request->amount)
                    ->where(function($q) {
                        $q->whereNull('is_used')->orWhere('is_used', 0);
                    })
                    ->first();
            }
            if ($pump_payment) {
                $pump_payment->is_used = 1;
                $pump_payment->parent_id = $settlement_cheque_payment->id;
                $pump_payment->settlement_no = $settlement->id;
                $pump_payment->save();
                $settlement_cheque_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                    ->editChequePayment($business_id, $settlement_cheque_payment->id, [
                        'pump_payment_id' => $pump_payment->id,
                    ]);
            }

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                      => true,

                'settlement_cheque_payment_id' => $settlement_cheque_payment->id,

                'pump_payment_id'              => isset($pump_payment) ? $pump_payment->id : null,

                'msg'                          => __('petrogeneral::lang.success'),

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
     * delete cheque payment data to db

     *
     * @return Response
     */
    public function deleteChequePayment($id)
    {

        try {

            $payment = SettlementChequePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

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

    // public function check_order_number(Request $request)

    //     {
    // dd('hello');

    //         try {

    //         } catch (\Exception $e) {

    //             $output = [

    //                 'success' => false,

    //                 'msg' => __('messages.something_went_wrong')

    //             ];

    //         }

    //         return $output;

    //     }

    /**
     * add credit_sale payment data to db

     *
     * @return Response
     */
    public function saveCreditSalePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $business = Business::find($business_id);

            $orderNumberRules = ['nullable'];

            // Only enforce uniqueness if duplicate orders are NOT allowed
            if (! $business->duplicate_orders_allowed) {
                $orderNumberRules[] = Rule::unique('settlement_credit_sale_payments', 'order_number')
                    ->where(function ($query) use ($request) {
                        return $query->where('customer_id', $request->customer_id);
                    });
            }

            $validator = Validator::make($request->all(), [
                'order_number' => $orderNumberRules,
            ]);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'msg'     => $validator->errors()->first(),
                ];
            }

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            // Business rule: Order No must not be reused for a different customer
            // within the same Pump Operator + Shift + Date context.
            $order_number = trim((string) $request->order_number);
            if (
                $order_number !== ''
                && $order_number !== '0'
                && $order_number !== '-'
                && stripos($order_number, 'No Order') !== 0
            ) {
                $pump_operator_id = (int) (!empty($request->pump_operator_id) ? $request->pump_operator_id : $settlement->pump_operator_id);

                $order_no_used_by_other_customer = SettlementCreditSalePayment::query()
                    ->join('settlements', 'settlements.settlement_no', '=', 'settlement_credit_sale_payments.settlement_no')
                    ->where('settlement_credit_sale_payments.business_id', $business_id)
                    ->where('settlement_credit_sale_payments.pump_operator_id', $pump_operator_id)
                    ->where('settlement_credit_sale_payments.order_number', $order_number)
                    ->whereDate('settlements.transaction_date', $settlement->transaction_date)
                    ->where('settlements.work_shift', $settlement->work_shift)
                    ->where('settlement_credit_sale_payments.customer_id', '!=', $request->customer_id)
                    ->exists();

                if ($order_no_used_by_other_customer) {
                    return [
                        'success' => false,
                        'msg' => 'Order No ' . $order_number . ' is already used for another customer in this shift. Please use the same customer or change the Order No.',
                    ];
                }
            }

            $price = $this->productUtil->num_uf($request->price);

            $unit_discount = $this->productUtil->num_uf($request->unit_discount);

            $qty = $this->productUtil->num_uf($request->qty);

            $amount = $this->productUtil->num_uf($request->amount);

            $total_discount = $this->productUtil->num_uf($request->total_discount) ?? 0;

            // Always compute net amount server-side to prevent client-side mismatches.
            $sub_total = $amount - $total_discount;

            // observe if it has been saved before and remove it

            $order_date = \Carbon::parse($request->order_date)->format('Y-m-d');

            $existing_payment = null;
            if ($request->filled('scsp_id')) {
                $existing_payment = SettlementCreditSalePayment::where('id', $request->input('scsp_id'))
                    ->where('business_id', $business_id)
                    ->first();
            }

            if (! $existing_payment) {
                // DAY1-LEGACY-LOOKUP: retained for old edit forms that do not submit scsp_id.
                $existing_payment = SettlementCreditSalePayment::where('order_number', $request->order_number)

                    ->where('customer_id', $request->customer_id)

                    ->where('business_id', $business_id)

                    ->where('settlement_no', $settlement->settlement_no)

                    ->where('product_id', $request->product_id)

                    ->where('pump_operator_id', (int) $request->pump_operator_id)

                    ->where('order_date', $order_date)

                    ->where('qty', $qty)

                    ->where('amount', $amount)

                    ->where('total_discount', $total_discount)

                    ->first();
            }

            $pump_payment = null;
            if ($request->filled('pump_payment_id')) {
                $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('id', $request->input('pump_payment_id'))
                    ->where('payment_type', 'credit')
                    ->first();
            } elseif (!empty($request->collection_form_no)) {
                $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('pump_operator_id', (int) $request->pump_operator_id)
                    ->where('collection_form_no', $request->collection_form_no)
                    ->where('payment_type', 'credit')
                    ->orderByDesc('id')
                    ->first();
            }

            if (! $existing_payment) {

                $data = [

                    'business_id'        => $business_id,

                    'settlement_no'      => $settlement->settlement_no, // Use string settlement_no for credit_sale_payments relationship

                    'customer_id'        => $request->customer_id,

                    'product_id'         => $request->product_id,

                    'order_number'       => !empty($request->order_number) ? trim($request->order_number) : '0',

                    'order_date'         => $order_date,

                    'pump_operator_id'   => (int) $request->pump_operator_id,

                    'price'              => $price,

                    'discount'           => $unit_discount,

                    'qty'                => $qty,

                    'amount'             => $amount,

                    'sub_total'          => $sub_total,

                    'total_discount'     => $total_discount,

                    'outstanding'        => $this->productUtil->num_uf($request->outstanding),

                    'credit_limit'       => $request->credit_limit,

                    'customer_reference' => $request->customer_reference,

                    'note'               => $request->note,

                    // DAY1-ORPHAN: manual settlement credit sales may not have a source pump_operator_payments row.
                    'pump_payment_id'     => optional($pump_payment)->id,

                ];

                // dd($data);

                $settlement_credit_sale_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, (string) $settlement->settlement_no, 'settlement_credit_sale_payments', $data);

            } else {
                $editData = [
                    'price' => $price,
                    'discount' => $unit_discount,
                    'qty' => $qty,
                    'amount' => $amount,
                    'sub_total' => $sub_total,
                    'total_discount' => $total_discount,
                    'outstanding' => $this->productUtil->num_uf($request->outstanding),
                    'credit_limit' => $request->credit_limit,
                    'customer_reference' => $request->customer_reference,
                    'note' => $request->note,
                ];
                if (empty($existing_payment->pump_payment_id) && !empty($pump_payment)) {
                    $editData['pump_payment_id'] = $pump_payment->id;
                }
                $settlement_credit_sale_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                    ->editCreditSale($business_id, $existing_payment->id, $editData);
                $existing_payment = $settlement_credit_sale_payment;

                // Update the linked pump_operator_payment for this specific credit sale.
                //
                // IS1293 fix (2026-05-13): the previous code keyed updates on the COMPOSITE
                // (pump_operator_id, collection_form_no, payment_type) which is NOT unique
                // when multiple credit sales are added one-by-one in the same dashboard
                // session — they all share the same collection_form_no. A bulk update on
                // that composite overwrote sibling rows' payment_amount to the value being
                // edited on a single row. The Payment Summary view then showed all rows
                // with the same amount.
                //
                // After Step 2 every scsp row carries pump_payment_id pointing to its
                // source pump_operator_payments.id. Use that FK for a single-row update.
                if (! empty($existing_payment->pump_payment_id)) {
                    PumpOperatorPayment::where('id', $existing_payment->pump_payment_id)
                        ->where('payment_type', 'credit')
                        ->update(['payment_amount' => $amount]);
                } else {
                    // Legacy row (pump_payment_id NULL — pre-Step 2). Intentionally do NOT
                    // bulk-update the source pump_operator_payments by composite — that was
                    // the IS1293 bug we just fixed. The Payment Summary view will display a
                    // potentially-stale payment_amount on the source row until week 1's
                    // historical backfill populates pump_payment_id. Logged so we can audit.
                    \Log::info('Petro edit credit sale skipped pump_operator_payments update — legacy row missing pump_payment_id', [
                        'scsp_id' => $existing_payment->id,
                        'collection_form_no' => $existing_payment->collection_form_no,
                        'pump_operator_id' => $existing_payment->pump_operator_id,
                        'new_amount' => $amount,
                    ]);
                }

                // DailyVoucher: prefer direct linkage by daily_voucher_id (per-row FK).
                // Fall back to composite only if daily_voucher_id is missing — and even
                // then, the daily_voucher table is typically one-per-credit-sale so the
                // composite usually still uniquely identifies the row.
                if (! empty($existing_payment->daily_voucher_id)) {
                    DailyVoucher::where('id', $existing_payment->daily_voucher_id)
                        ->where('business_id', $business_id)
                        ->update(['total_amount' => $sub_total]);
                }

                // DailyCollection: this table aggregates per-collection-form across all
                // line items in the form. Recompute its current_amount as the SUM of all
                // scsp.sub_total sharing this collection_form_no, rather than overwriting
                // it to the single edited row's sub_total (which dropped sibling amounts).
                if (! empty($existing_payment->collection_form_no) && ! empty($existing_payment->pump_operator_id)) {
                    $formTotal = SettlementCreditSalePayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $existing_payment->pump_operator_id)
                        ->where('collection_form_no', $existing_payment->collection_form_no)
                        ->sum('sub_total');

                    DailyCollection::where('business_id', $business_id)
                        ->where('pump_operator_id', $existing_payment->pump_operator_id)
                        ->where('collection_form_no', $existing_payment->collection_form_no)
                        ->where('type', 'daily_voucher')
                        ->update(['current_amount' => $formTotal]);
                }

                // Update accounting entries when credit sale is edited (Account Receivable + Customer Ledger)
                // So edited amounts show correctly in List Account / Account Receivable and Contact Ledger
                if (!empty($existing_payment->transaction_id)) {
                    Transaction::where('id', $existing_payment->transaction_id)->update([
                        'final_total' => $sub_total,
                        'total_before_tax' => $sub_total,
                        'discount_amount' => $total_discount,
                    ]);
                    ContactLedger::where('transaction_id', $existing_payment->transaction_id)->update([
                        'amount' => $sub_total,
                    ]);
                    $ar_account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                    if (!empty($ar_account_id)) {
                        AccountTransaction::where('transaction_id', $existing_payment->transaction_id)
                            ->where('account_id', $ar_account_id)
                            ->where('type', 'debit')
                            ->update(['amount' => $sub_total]);
                    }
                }
            }

            $output = [

                'success'                           => true,

                'settlement_credit_sale_payment_id' => $settlement_credit_sale_payment->id,

                'msg'                               => __('petrogeneral::lang.success'),

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
     * delete credit_sale payment data to db

     *
     * @return Response
     */
    public function deleteCreditSalePayment($id)
    {

        try {

            $payment = SettlementCreditSalePayment::where('id', $id)->first();

            if (empty($payment)) {
                return [
                    'success' => false,
                    'msg'     => __('petrogeneral::lang.credit_sale_not_found') ?: 'Credit sale not found',
                ];
            }

            // Resolve settlement: credit sales store settlement_no as string (e.g. "SET-656") or integer id
            $settlement_no = $payment->settlement_no;
            $settlement = null;
            if (is_numeric($settlement_no) && (string) (int) $settlement_no === (string) $settlement_no) {
                $settlement = Settlement::find($settlement_no);
            } else {
                $settlement = Settlement::where('settlement_no', $settlement_no)->first();
            }
            if ($settlement) {
                $settlement->update(['is_edit' => request()->is_edit]);
            }

            $amount = $payment->amount;

            $discount = $payment->total_discount ?? 0;

            // Delete related accounting entries when credit sale is removed (Account Receivable + Customer Ledger)
            // Entries are linked via transaction_id; ref_no/sub_type do not match creation
            if (!empty($payment->transaction_id)) {
                AccountTransaction::where('transaction_id', $payment->transaction_id)->forceDelete();
                ContactLedger::where('transaction_id', $payment->transaction_id)->forceDelete();
                TransactionPayment::where('transaction_id', $payment->transaction_id)->forceDelete();
                Transaction::where('id', $payment->transaction_id)->forceDelete();
            }

            // Delete Daily Voucher entries if they exist
            if (!empty($payment->daily_voucher_id)) {
                DailyVoucher::where('id', $payment->daily_voucher_id)->delete();
            }

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'net_amount' => $amount - $discount,

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
     * get price of product

     *
     * @return Response
     */
    public function getProductPrice(Request $request)
    {

        $product_id = $request->product_id;

        $product = Product::leftjoin('variations', 'products.id', 'variations.product_id')

            ->where('products.id', $product_id)

            ->select('sell_price_inc_tax')

            ->first();

        if (! empty($product)) {

            $price = $product->sell_price_inc_tax;

        } else {

            $price = 0.00;

        }

        return ['price' => $price];

    }

    /**
     * get price of product

     *
     * @return Response
     */
    public function getCustomerDetails($customer_id, ContactController $contactController)
    {

        $business_id = request()->session()->get('business.id');

        $query = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')

            ->leftjoin('contact_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')

            ->where('contacts.business_id', $business_id)

            ->where('contacts.id', $customer_id)

        // ->onlyCustomers()

            ->select([

                'contacts.vat_number', 'contacts.manual_bill_settlement', 'contacts.contact_id', 'contacts.name', 'contacts.created_at', 'total_rp', 'cg.name as customer_group', 'city', 'state', 'country', 'landmark', 'mobile', 'contacts.id', 'is_default', 'contacts.sub_customers',

                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),

                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),

                DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),

                DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),

                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),

                DB::raw("SUM(IF(t.type = 'advance_payment', -1*final_total, 0)) as advance_payment"),

                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),

                'email', 'tax_number', 'contacts.pay_term_number', 'contacts.pay_term_type', 'contacts.credit_limit', 'contacts.custom_field1', 'contacts.custom_field2', 'contacts.custom_field3', 'contacts.custom_field4', 'contacts.type',

            ])

            ->groupBy('contacts.id')->first();

        $due = 0;

        $return_due = 0;

        $opening_balance = 0;

        if (! empty($query)) {

            $due = $query->total_invoice - $query->invoice_received + $query->advance_payment;

            $return_due = $query->total_sell_return - $query->sell_return_paid;

            $opening_balance = $query->opening_balance - $query->opening_balance_paid;

        }

        $total_outstanding = $due - $return_due + $opening_balance;

        if (empty($total_outstanding)) {

            $total_outstanding = 0.00;

        }

        if (empty($query->credit_limit)) {

            $credit_limit = 'No Limit';

        } else {

            $credit_limit = $query->credit_limit;

        }

        $business_details = Business::find($business_id);

        $customer_references = CustomerReference::where('contact_id', $customer_id)->where('business_id', $business_id)->select('reference')->get();

        $sub_ids = json_decode($query->sub_customers) ?? [];

        $sub_customers = Contact::whereIn('id', $sub_ids)->pluck('name', 'id');

        $payment_data = DB::table('customer_payments')

            ->leftJoin('settlements', 'customer_payments.settlement_no', '=', 'settlements.id')

            ->leftJoin('transactions', 'transactions.invoice_no', '=', 'settlements.settlement_no')

            ->where('customer_payments.customer_id', $customer_id)

            ->select([

                'settlements.transaction_date',

                'settlements.settlement_no',

                'transactions.order_no',

            ])

            ->orderBy('settlements.transaction_date', 'desc') // Optional: order by date

            ->get();

        // return ['total_outstanding' =>  strval($this->productUtil->num_f($total_outstanding, false, $business_details, true)), 'credit_limit' => strval($credit_limit), 'customer_references' => $customer_references];

        return ['total_outstanding' => $this->productUtil->num_f(strval($contactController->get_cus_due_bal($customer_id, false))), 'credit_limit' => strval($credit_limit), 'customer_references' => $customer_references, 'sub_customers' => $sub_customers, 'vat_number' => $query->vat_number, 'manual_bill_settlement' => $query->manual_bill_settlement, 'customer_name' => $query->name, 'payment_data' => $payment_data];

    }

    /**
     * add expense payment data to db

     *
     * @return Response
     */
    public function saveExpensePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'    => $business_id,

                'settlement_no'  => $settlement->id,

                'expense_number' => $request->expense_number,

                'category_id'    => $request->category_id,

                'reference_no'   => $request->reference_no,

                'account_id'     => $request->account_id,

                'reason'         => $request->reason,

                'amount'         => $request->amount,

            ];

            // Update reference count

            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');

            // Generate reference number

            if (empty($request->reference_no)) {

                $data['reference_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);

            }

            $settlement_expense_payment = SettlementExpensePayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $expense_number = $this->getExpenseNumber($request->settlement_no);

            $output = [

                'success'                       => true,

                'expense_number'                => $expense_number,

                'reference_no'                  => $settlement_expense_payment->reference_no,

                'settlement_expense_payment_id' => $settlement_expense_payment->id,

                'msg'                           => __('petrogeneral::lang.success'),

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
     * delete expense payment data to db

     *
     * @return Response
     */
    public function deleteExpensePayment($id)
    {

        try {

            $payment = SettlementExpensePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

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

    private function resolvePaymentSettlement(Request $request, $business_id)
    {
        $settlement_no = $request->settlement_no ?? $request->settlement_id;
        $settlement_id = $request->payment_settlement_id ?? $request->settlement_record_id ?? $request->settlement_id;

        if (empty($settlement_no) && empty($settlement_id)) {
            return null;
        }

        return Settlement::where('business_id', $business_id)
            ->where(function ($query) use ($settlement_no, $settlement_id) {
                if (! empty($settlement_no)) {
                    $query->where('settlement_no', $settlement_no);

                    if (is_numeric($settlement_no)) {
                        $query->orWhere('id', (int) $settlement_no);
                    }
                }

                if (! empty($settlement_id) && is_numeric($settlement_id)) {
                    $query->orWhere('id', (int) $settlement_id);
                }
            })
            ->first();
    }

    /**
     * add shortage payment data to db

     *
     * @return Response
     */
    public function saveShortagePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = $this->resolvePaymentSettlement($request, $business_id);

            if (empty($settlement)) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ], 404);
            }

            $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);

            $raw_amount = trim($request->amount ?? '');
            $clean_amount = str_replace(',', '', $raw_amount);
            $amount = is_numeric($clean_amount) ? (float) $clean_amount : null;

            if ($amount === null || $amount <= 0) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('Please enter a positive amount for Shortage'),
                ], 422);
            }

            $data = [

                'business_id'      => $business_id,

                'settlement_no'    => $settlement->id,

                'amount'           => $amount,

                'current_shortage' => $pump_operator->short_amount,

                'note'             => $request->note,

            ];

            $settlement_shortage_payment = SettlementShortagePayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            Log::info('AddPaymentController@saveShortagePayment - Shortage payment saved', [
                'request_settlement_no'         => $request->settlement_no,
                'request_payment_settlement_id' => $request->payment_settlement_id,
                'settlement_id'                 => $settlement->id,
                'settlement_no'                 => $settlement->settlement_no,
                'payment_id'                    => $settlement_shortage_payment->id,
                'payment_settlement_no'         => $settlement_shortage_payment->settlement_no,
                'amount'                        => $settlement_shortage_payment->amount,
            ]);

            $output = [

                'success'                        => true,

                'settlement_shortage_payment_id' => $settlement_shortage_payment->id,

                'msg'                            => __('petrogeneral::lang.success'),

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
     * delete shortage payment data to db

     *
     * @return Response
     */
    public function deleteShortagePayment($id)
    {

        try {

            $payment = SettlementShortagePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = (float) ($payment->amount ?? 0);

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petrogeneral::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return response()->json($output);

    }

    /**
     * add excess payment data to db

     *
     * @return Response
     */
    public function saveExcessPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = $this->resolvePaymentSettlement($request, $business_id);

            if (empty($settlement)) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ], 404);
            }

            $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);

            $raw_amount = trim($request->amount ?? '');
            $clean_amount = str_replace(',', '', $raw_amount);
            $amount = is_numeric($clean_amount) ? (float) $clean_amount : null;

            if ($amount === null || $amount >= 0) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('Please enter a negative amount for Excess'),
                ], 422);
            }

            $data = [

                'business_id'    => $business_id,

                'settlement_no'  => $settlement->id,

                'amount'         => $amount,

                'current_excess' => $pump_operator->excess_amount,

                'note'           => $request->note,

            ];

            $settlement_excess_payment = SettlementExcessPayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            Log::info('AddPaymentController@saveExcessPayment - Excess payment saved', [
                'request_settlement_no'         => $request->settlement_no,
                'request_payment_settlement_id' => $request->payment_settlement_id,
                'settlement_id'                 => $settlement->id,
                'settlement_no'                 => $settlement->settlement_no,
                'payment_id'                    => $settlement_excess_payment->id,
                'payment_settlement_no'         => $settlement_excess_payment->settlement_no,
                'amount'                        => $settlement_excess_payment->amount,
            ]);

            $output = [

                'success'                      => true,

                'settlement_excess_payment_id' => $settlement_excess_payment->id,

                'msg'                          => __('petrogeneral::lang.success'),

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
     * delete excess payment data to db

     *
     * @return Response
     */
    public function deleteExcessPayment($id)
    {

        try {

            $payment = SettlementExcessPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = (float) ($payment->amount ?? 0);

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petrogeneral::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return response()->json($output);

    }

    /**
     * preview payment details

     *
     * @return Response
     */
    public function preview($id)
    {

        $business_id = request()->session()->get('business.id');

        $settlement = Settlement::where('settlements.id', $id)->where('settlements.business_id', $business_id)

            ->leftjoin('pump_operators', 'settlements.pump_operator_id', 'pump_operators.id')

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

            ->select('settlements.*', 'pump_operators.name as pump_operator_name')

            ->first();

        // Manually load cash deposits if relationship didn't load them (fallback for Settlement SW)
        if ($settlement && $settlement->cash_deposits->isEmpty()) {
            $cash_deposits = SettlementCashDeposit::where('settlement_no', $settlement->settlement_no)
                ->orWhere('settlement_no', $settlement->id)
                ->get();
            $settlement->setRelation('cash_deposits', $cash_deposits);
        }

        // Get shift_ids for this settlement to filter payments
        $shift_ids = [];
        if (!empty($settlement->work_shift)) {
            if (is_array($settlement->work_shift)) {
                $shift_ids = $settlement->work_shift;
            } elseif (is_string($settlement->work_shift)) {
                $shift_ids = array_filter(array_map('intval', explode(',', $settlement->work_shift)));
            }
        }

        // If no shift_ids from work_shift, get from pump_operator_assignments
        if (empty($shift_ids) && !empty($settlement->pump_operator_id)) {
            $assignments = \Modules\PetroGeneral\Entities\PumpOperatorAssignment::where('pump_operator_id', $settlement->pump_operator_id)
                ->where('settlement_id', $settlement->id)
                ->pluck('shift_id')
                ->toArray();
            if (!empty($assignments)) {
                $shift_ids = $assignments;
            }
        }

        // Filter card_payments by shift_id through DailyCard join
        if ($settlement) {
            $filtered_card_payments = SettlementCardPayment::where(function ($query) use ($settlement) {
                    $query->where('settlement_card_payments.settlement_no', $settlement->id)
                        ->orWhere('settlement_card_payments.settlement_no', $settlement->settlement_no);
                })
                ->where('settlement_card_payments.business_id', $business_id)
                ->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
                ->when(!empty($shift_ids), function ($query) use ($shift_ids) {
                    $query->where(function ($shift_query) use ($shift_ids) {
                        $shift_query->whereIn('daily_cards.shift_id', $shift_ids)
                            ->orWhereNull('settlement_card_payments.daily_card_id');
                    });
                })
                ->select('settlement_card_payments.*')
                ->get();
            $settlement->setRelation('card_payments', $filtered_card_payments);
        }

        // Reload cash_payments - use ID (integer) to match cash_payments relationship
        if ($settlement) {
            $cash_payments_query = SettlementCashPayment::where('settlement_cash_payments.settlement_no', $settlement->id)
                ->where('settlement_cash_payments.business_id', $business_id);

            // Filter by shift_id if available (through PumpOperatorPayment join)
            if (!empty($shift_ids)) {
                $cash_payments_query->where(function ($q) use ($shift_ids) {
                    $q->whereExists(function ($existsQ) use ($shift_ids) {
                        $existsQ->select(\DB::raw(1))
                            ->from('pump_operator_payments')
                            ->where(function ($subQ) {
                                $subQ->whereColumn('pump_operator_payments.id', 'settlement_cash_payments.customer_payment_id')
                                     ->orWhereColumn('pump_operator_payments.id', 'settlement_cash_payments.pump_payment_id');
                            })
                            ->whereIn('pump_operator_payments.shift_id', $shift_ids);
                    })
                    ->orWhere(function ($orQ) {
                        $orQ->whereNull('settlement_cash_payments.customer_payment_id')
                            ->whereNull('settlement_cash_payments.pump_payment_id');
                    });
                });
            }

            $filtered_cash_payments = $cash_payments_query->get();
            $settlement->setRelation('cash_payments', $filtered_cash_payments);
        }

        $daily_collections = DailyCollection::where('daily_collections.business_id', $business_id)

            ->where('daily_collections.pump_operator_id', $settlement->pump_operator_id)

            ->whereNull('settlement_id')

            ->when(!empty($shift_ids), function($query) use ($shift_ids) {
                $query->whereIn('shift_id', $shift_ids);
            })

            ->select([

                'daily_collections.*',

            ])->orderBy('daily_collections.id')->get();

        // To avoid duplicate cash lines in the Payment to Finalize view,
        // only synthesize DailyCollection rows as temporary cash_payments
        // when there are no existing cash_payments linked to this settlement.
        if ($settlement->cash_payments->isEmpty()) {
            foreach ($daily_collections as $dc_row) {

            $customers = Contact::customersDropdown($business_id, false, true, 'customer');

            $settlementCashPayment = new SettlementCashPayment;

            $settlementCashPayment->business_id = $business_id;

            $settlementCashPayment->settlement_no = $settlement->id;

                $settlementCashPayment->amount = floatval($dc_row->current_amount);

            $settlementCashPayment->customer_id = array_key_first($customers->toArray());

            $settlement->cash_payments[] = $settlementCashPayment;
            }
        }

        $business = Business::where('id', $settlement->business_id)->first();

        $pump_operator = PumpOperator::where('id', $settlement->pump_operator_id)->first();

        // this for only to show in print page customer payments which entered in customer payments tab

        $customer_payments_tab = CustomerPayment::leftjoin('contacts', 'customer_payments.customer_id', 'contacts.id')

            ->where('customer_payments.settlement_no', $id)

            ->where('customer_payments.business_id', $business_id)

            ->select('customer_payments.*', 'contacts.name as customer_name')

            ->get();

        return view('petrogeneral::settlement.partials.payment_preview')->with(compact('settlement', 'business', 'pump_operator', 'customer_payments_tab'));

    }

    /**
     * preview payment details

     *
     * @return Response
     */
    public function productPreview($id)
    {

        $business_id = request()->session()->get('business.id');

        $settlement = Settlement::where('settlements.id', $id)

            ->leftjoin('settlement_credit_sale_payments', 'settlements.id', 'settlement_credit_sale_payments.settlement_no')

            ->leftjoin('products', 'products.id', 'settlement_credit_sale_payments.product_id')

            ->select('settlements.*', 'products.*', 'settlement_credit_sale_payments.*')

            ->get();

        return view('petrogeneral::settlement.partials.product_preview')->with(compact('settlement'));

    }

    /**
     * Save POS payment
     *
     * @param Request $request
     * @return Response
     */
    public function savePosPayment(Request $request)
    {
        Log::info('Request Data for savePosPayment in AddPaymentController:', ['request' => $request->all()]);

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('business.id');

            // Retrieve Settlement
            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // If editing existing settlement, mark so ledger will be rebuilt on save
            if ($request->has('is_edit') && $request->is_edit) {
                Settlement::where('id', $settlement->id)->update(['is_edit' => 1]);
            }

            // Create Settlement POS Payment record
            $settlement_pos_payment = SettlementPosPayment::create([
                'business_id'   => $business_id,
                'settlement_no' => $settlement->id,
                'amount'        => $request->amount,
                'customer_id'   => $request->customer_id,
                'note'          => $request->note,
            ]);

            // Link matching PumpOperatorPayment if exists
            $work_shifts = $settlement->work_shift;
            if (is_string($work_shifts)) {
                $work_shifts = json_decode($work_shifts, true) ?? [];
            }
            // fallback if json_decode failed or it was not json
            if (!is_array($work_shifts)) {
                $work_shifts = explode(',', $settlement->work_shift ?? '');
            }
            $work_shifts = array_filter(array_map('trim', $work_shifts));

            if ($request->has('pump_payment_id')) {
                $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('id', $request->pump_payment_id)
                    ->first();
            } else {
                if (!empty($work_shifts)) {
                    $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->whereIn('shift_id', $work_shifts)
                        ->where('payment_type', 'pos')
                        ->where('payment_amount', $request->amount)
                        ->where(function($q) {
                            $q->whereNull('is_used')->orWhere('is_used', 0);
                        })
                        ->first();
                } else {
                    $pump_payment = null;
                }
            }

            if ($pump_payment) {
                $pump_payment->is_used = 1;
                $pump_payment->parent_id = $settlement_pos_payment->id;
                $pump_payment->settlement_no = $settlement->id;
                $pump_payment->save();

                // Also update the fake ID ref if provided
                $settlement_pos_payment->pump_payment_id = $pump_payment->id;
                $settlement_pos_payment->save();
            }

            // Update Settlement edit flag if needed
            if ($request->has('is_edit')) {
                $settlement->update(['is_edit' => $request->is_edit]);
            }

            DB::commit();

            $output = [
                'success'                   => true,
                'settlement_pos_payment_id' => $settlement_pos_payment->id,
                'pump_payment_id'           => isset($pump_payment) ? $pump_payment->id : null,
                'msg'                       => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Delete POS payment
     *
     * @param int $id
     * @param Request $request
     * @return Response
     */
    public function deletePosPayment($id, Request $request)
    {
        try {
            $pumpPaymentId = $request->input('pump_payment_id');
            if (!empty($pumpPaymentId)) {
                $pumpPayment = PumpOperatorPayment::findOrFail($pumpPaymentId);
                $pumpPayment->is_used = 1;
                $pumpPayment->save();

                $output = [
                    'success' => true,
                    'amount'  => $pumpPayment->payment_amount,
                    'msg'     => __('petrogeneral::lang.success'),
                ];

                return $output;
            }

            $payment = SettlementPosPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            // Delete corresponding ContactLedger entry if it exists (linked via settlement_no in note)
            $settlement_record = Settlement::find($payment->settlement_no);
            if ($settlement_record) {
                ContactLedger::where('transaction_id', null)
                    ->where('amount', $amount)
                    ->where('note', 'like', '%' . $settlement_record->settlement_no . '%')
                    ->where('sub_type', 'pos_payment')
                    ->forceDelete();
            }

            $payment->delete();

            $output = [
                'success' => true,
                'amount'  => $amount,
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
}
