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
 * Other sales, other income and customer payments.
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
 * Methods here: saveOtherSale, deleteOtherSale, saveOtherIncome, deleteOtherIncome, saveCustomerPayment, deleteCustomerPayment
 */
trait HandlesOtherEntries
{
    public function saveOtherSale(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return [

                    'success' => false,

                    'msg' => __('petro::lang.date_greater_than_day_end'),

                ];

            }

            $data = [

                'business_id' => $business_id,

                'settlement_no' => $settlement_exist->id,

                'store_id' => $request->store_id,

                'product_id' => $request->product_id,

                'price' => abs((float) $request->price),

                // IS1546: Other Sale must not depend on meter-sale-only variables.
                // The previous code referenced $positive_meter_amounts['qty'], which is not
                // defined in this method and caused the generic "Something went wrong" message.
                'qty' => abs((float) $request->qty),

                'balance_stock' => $request->balance_stock,

                'discount' => abs((float) $request->discount),

                'discount_type' => $request->discount_type ?: 'fixed',

                'discount_amount' => abs((float) $request->discount_amount),

                'sub_total' => abs((float) $request->sub_total),

            ];

            $other_sale = OtherSale::create($data);

            Settlement::where('id', $settlement_exist->id)->update([

                'is_edit' => request()->is_edit,

            ]);

            $product = \App\Product::find($request->product_id);
            $business = \App\Business::find($business_id);
            $currency_precision = ! empty($business->currency_precision) ? $business->currency_precision : 2;
            $with_discount = (float) $request->sub_total - (float) $request->discount_amount;

            $row_html = '<tr data-source="manual">' .
                '<td>' . e(optional($product)->sku) . '</td>' .
                '<td>' . e(optional($product)->name) . '</td>' .
                '<td>' . number_format((float) $request->balance_stock, 4, '.', ',') . '</td>' .
                '<td>' . number_format((float) $request->price, $currency_precision) . '</td>' .
                '<td>' . number_format((float) $request->qty, 4, '.', ',') . '</td>' .
                '<td>' . e($request->discount_type) . '</td>' .
                '<td>' . number_format((float) $request->discount, $currency_precision) . '</td>' .
                '<td>' . number_format((float) $request->sub_total, $currency_precision) . '</td>' .
                '<td>' . number_format($with_discount, $currency_precision) . '</td>' .
                '<td><button class="btn btn-xs btn-danger delete_other_sale" data-href="/petro/settlement/delete-other-sale/' . $other_sale->id . '"><i class="fa fa-times"></i></button></td>' .
                '</tr>';

            $output = [

                'success' => true,

                'other_sale_id' => $other_sale->id,

                'settlement_id' => $settlement_exist->id,

                'settlement_no' => $settlement_exist->settlement_no,

                'row_html' => $row_html,

                'amount' => $with_discount,

                'msg' => __('petro::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return response()->json($output);

    }

    public function deleteOtherSale($id)
    {

        try {

            $other_sale = OtherSale::where('id', $id)->first();

            Settlement::where('id', $other_sale->settlement_no)->update([

                'is_edit' => request()->is_edit,

            ]);

            $amount = $other_sale->sub_total - $other_sale->discount_amount;

            $other_sale->delete();

            $output = [

                'success' => true,

                'amount' => $amount,

                'msg' => __('petro::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * save other income data in db







     * @param product_id







     * @return Response
     */

    public function saveOtherIncome(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return [

                    'success' => false,

                    'msg' => __('petro::lang.date_greater_than_day_end'),

                ];

            }

            $sub_total = $this->productUtil->num_uf($request->qty) * $this->productUtil->num_uf($request->price);

            $data = [

                'business_id' => $business_id,

                'settlement_no' => $settlement_exist->id,

                'product_id' => $request->product_id,

                'qty' => $request->qty,

                'price' => $request->price,

                'reason' => $request->other_income_reason,

                'sub_total' => $sub_total,

            ];

            $other_income = OtherIncome::create($data);

            Settlement::where('id', $settlement_exist->id)->update([

                'is_edit' => request()->is_edit,

            ]);

            $output = [

                'success' => true,

                'other_income_id' => $other_income->id,

                'sub_total' => $other_income->sub_total,

                'msg' => __('petro::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteOtherIncome($id)
    {

        try {

            $other_income = OtherIncome::where('id', $id)->first();

            Settlement::where('id', $other_income->settlement_no)->update([

                'is_edit' => request()->is_edit,

            ]);

            $sub_total = $other_income->sub_total;

            $other_income->delete();

            $output = [

                'success' => true,

                'sub_total' => $sub_total,

                'msg' => __('petro::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * save customer payment data in db







     * @param product_id







     * @return Response
     */

    public function saveCustomerPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return [

                    'success' => false,

                    'msg' => __('petro::lang.date_greater_than_day_end'),

                ];

            }

            $data = [

                'business_id' => $business_id,

                'settlement_no' => $settlement_exist->id,

                'customer_id' => $request->customer_id,

                'payment_method' => $request->payment_method,

                'cheque_date' => ! empty($request->cheque_date)

                    ? \Carbon::parse($request->cheque_date)->format('Y-m-d')

                    : null,

                'cheque_number' => $request->cheque_number,

                'bank_name' => $request->bank_name,

                'amount' => $request->amount,

                'sub_total' => $request->sub_total,

                'post_dated_cheque' => $request->post_dated_cheque,

            ];

            DB::beginTransaction();

            // Check for duplicate customer payment to prevent accidental double-submission
            // Match by settlement, customer, amount, and recent timestamp (within last 5 seconds)
            $duplicate_check = CustomerPayment::where('settlement_no', $settlement_exist->id)
                ->where('customer_id', $request->customer_id)
                ->where('amount', $request->amount)
                ->where('payment_method', $request->payment_method)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($duplicate_check) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => __('petro::lang.duplicate_payment_detected'),
                ];
            }

            $customer_payment = CustomerPayment::create($data);

            Settlement::where('id', $settlement_exist->id)->update([

                'is_edit' => request()->is_edit,

            ]);

            // Direct-settlement customer-payment flow — identity is customer_payment_id.
            if ($request->payment_method == 'cash') {
                $cash_data = [
                    'amount' => $request->amount,
                    'customer_id' => $request->customer_id,
                    'customer_payment_id' => $customer_payment->id,
                ];
                $settlement_cash_payment = app(\Modules\Petro\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, (string) $settlement_exist->id, 'settlement_cash_payments', $cash_data, 'customer_payment_id');
            }

            if ($request->payment_method == 'card') {
                $card_data = [
                    'amount' => $request->amount,
                    'card_type' => $request->card_type,
                    'card_number' => $request->card_number,
                    'customer_id' => $request->customer_id,
                    'customer_payment_id' => $customer_payment->id,
                ];
                $settlement_card_payment = app(\Modules\Petro\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, (string) $settlement_exist->id, 'settlement_card_payments', $card_data, 'customer_payment_id');
            }

            if ($request->payment_method == 'cheque') {
                $cheque_data = [
                    'amount' => $request->amount,
                    'bank_name' => $request->bank_name,
                    'cheque_number' => $request->cheque_number,
                    'cheque_date' => ! empty($request->cheque_date)
                        ? \Carbon::parse($request->cheque_date)->format('Y-m-d')
                        : null,
                    'customer_id' => $request->customer_id,
                    'customer_payment_id' => $customer_payment->id,
                ];
                $settlement_cheque_payment = app(\Modules\Petro\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, (string) $settlement_exist->id, 'settlement_cheque_payments', $cheque_data, 'customer_payment_id');
            }

            DB::commit();

            $output = [

                'success' => true,

                'customer_payment_id' => $customer_payment->id,

                'msg' => __('petro::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteCustomerPayment($id)
    {

        try {

            $customer_payment = CustomerPayment::where('id', $id)->first();

            Settlement::where('id', $customer_payment->settlement_no)->update([

                'is_edit' => request()->is_edit,

            ]);

            $amount = $customer_payment->amount;

            $customer_payment->delete();

            SettlementCashPayment::where('customer_payment_id', $id)->delete();

            SettlementCardPayment::where('customer_payment_id', $id)->delete();

            SettlementChequePayment::where(

                'customer_payment_id',

                $id

            )->delete();

            $output = [

                'success' => true,

                'amount' => $amount,

                'msg' => __('petro::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }
}
