<?php

namespace Modules\PumperDashboard\Http\Controllers;

use App\Category;
use App\Contact;
use App\CustomerReference;
use App\Product;
use App\Store;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\PumperDashboard\Entities\FuelTank;
use Modules\PumperDashboard\Entities\MeterSale;
use Modules\PumperDashboard\Entities\OtherSale;
use Modules\PumperDashboard\Entities\Pump;
use Modules\PumperDashboard\Entities\PumpOperatorCommission;
use Modules\PumperDashboard\Entities\PumpOperatorMeterSale;
use Modules\PumperDashboard\Entities\Settlement;

class SettlementSupportController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    public function getProductPrice(Request $request)
    {
        $product = Product::leftJoin('variations', 'products.id', 'variations.product_id')
            ->where('products.id', $request->product_id)
            ->select('sell_price_inc_tax')
            ->first();

        return ['price' => $product->sell_price_inc_tax ?? 0.00];
    }

    public function getCustomerDetails($customer_id)
    {
        $business_id = request()->session()->get('business.id');

        $query = Contact::leftJoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->leftJoin('contact_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
            ->where('contacts.business_id', $business_id)
            ->where('contacts.id', $customer_id)
            ->select([
                'contacts.vat_number',
                'contacts.manual_bill_settlement',
                'contacts.contact_id',
                'contacts.name',
                'contacts.created_at',
                'total_rp',
                'cg.name as customer_group',
                'city',
                'state',
                'country',
                'landmark',
                'mobile',
                'contacts.id',
                'is_default',
                'contacts.sub_customers',
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'advance_payment', -1*final_total, 0)) as advance_payment"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                'email',
                'tax_number',
                'contacts.pay_term_number',
                'contacts.pay_term_type',
                'contacts.credit_limit',
                'contacts.custom_field1',
                'contacts.custom_field2',
                'contacts.custom_field3',
                'contacts.custom_field4',
                'contacts.type',
            ])
            ->groupBy('contacts.id')
            ->first();

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

        $credit_limit = empty($query->credit_limit) ? 'No Limit' : $query->credit_limit;

        $customer_references = CustomerReference::where('contact_id', $customer_id)
            ->where('business_id', $business_id)
            ->select('reference')
            ->get();

        return [
            'total_outstanding' => $this->transactionUtil->num_f($total_outstanding, false),
            'credit_limit' => strval($credit_limit),
            'customer_references' => $customer_references,
            'vat_number' => $query->vat_number ?? null,
            'manual_bill_settlement' => $query->manual_bill_settlement ?? null,
            'customer_name' => $query->name ?? null,
        ];
    }

    public function getProductsByStoreId(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $fuel_category_id = Category::where('business_id', $business_id)
            ->where('name', 'Fuel')
            ->value('id');

        if ($request->store_id) {
            return $this->transactionUtil->getProductsByStoreId(
                $business_id,
                $request->location_id,
                $request->store_id,
                $request->tab ?? 0,
                $fuel_category_id,
                'pumperdashboard_settlements'
            );
        }

        return $this->transactionUtil->createDropdownHtml([], 'No Item Found');
    }

    public function getBalanceStockById(Request $request, $id)
    {
        try {
            $product = Product::join('variations', 'products.id', '=', 'variations.product_id')
                ->leftJoin('variation_location_details', function ($join) use ($id, $request) {
                    $join->on('variations.id', '=', 'variation_location_details.variation_id')
                        ->where('variation_location_details.product_id', '=', $id)
                        ->where('variation_location_details.location_id', '=', $request->location_id);
                })
                ->leftJoin('variation_store_details', function ($join) use ($request) {
                    $join->on('variations.id', '=', 'variation_store_details.variation_id')
                        ->where('variation_store_details.store_id', '=', $request->store_id);
                })
                ->where('products.id', $id)
                ->select(
                    DB::raw('COALESCE(variation_store_details.qty_available, 0) as qty_available'),
                    DB::raw('COALESCE(sell_price_inc_tax, 0) as sell_price_inc_tax'),
                    'products.name',
                    'products.sku'
                )
                ->first();

            return [
                'success' => true,
                'balance_stock' => $product->qty_available ?? 0,
                'price' => $product->sell_price_inc_tax ?? 0,
                'product_name' => $product->name ?? '',
                'code' => $product->sku ?? '',
                'msg' => __('pumperdashboard::lang.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    public function deleteOtherSale($id)
    {
        try {
            $other_sale = OtherSale::where('id', $id)->firstOrFail();

            Settlement::where('id', $other_sale->settlement_no)->update([
                'is_edit' => request()->is_edit,
            ]);

            $amount = $other_sale->sub_total - $other_sale->discount_amount;
            $other_sale->delete();

            return [
                'success' => true,
                'amount' => $amount,
                'msg' => __('pumperdashboard::lang.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    public function deleteMeterSale($id)
    {
        try {
            $meter_sale = MeterSale::where('id', $id)->firstOrFail();

            Settlement::where('id', $meter_sale->settlement_no)->update([
                'is_edit' => request()->is_edit,
            ]);

            $pump = Pump::where('id', $meter_sale->pump_id)->firstOrFail();
            FuelTank::where('id', $pump->fuel_tank_id)->increment('current_balance', $meter_sale->qty);

            $amount = $meter_sale->discount_amount;
            $starting_meter = $meter_sale->starting_meter;
            $meter_sale->delete();

            $pump->last_meter_reading = $starting_meter;
            $previous_meter_sale = MeterSale::where('pump_id', $pump->id)->orderBy('id', 'desc')->first();
            if (!empty($previous_meter_sale)) {
                $pump->starting_meter = $previous_meter_sale->starting_meter;
            }
            $pump->save();

            PumpOperatorCommission::where('meter_sale_id', $id)->delete();

            return [
                'success' => true,
                'amount' => $amount,
                'pump_name' => $pump->pump_name,
                'pump_id' => $pump->id,
                'msg' => __('pumperdashboard::lang.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    public function getMeterSaleForm($id)
    {
        $output = [
            'success' => false,
            'msg' => __('messages.something_went_wrong'),
        ];

        try {
            $meterSaleRecord = MeterSale::find($id);

            if ($meterSaleRecord) {
                $active_settlement = Settlement::where('id', $meterSaleRecord->settlement_no)->first();
                $discount_types = [
                    'fixed' => 'Fixed',
                    'percentage' => 'Percentage',
                ];
                $already_pumps = MeterSale::where('settlement_no', $active_settlement->id)->pluck('pump_id')->toArray();
                $business_id = $meterSaleRecord->business_id;

                if (request()->action_type == 'cancel') {
                    $meter_sale = [];
                } else {
                    $already_pumps = array_values(array_diff($already_pumps, [$meterSaleRecord->pump_id]));
                    $meter_sale = $meterSaleRecord->toArray();
                    $meter_sale['qty'] = $meterSaleRecord->closing_meter - $meterSaleRecord->starting_meter - $meterSaleRecord->testing_qty;
                }
            } else {
                $meter_sale = PumpOperatorMeterSale::where('id', $id)->first();
                if ($meter_sale) {
                    $active_settlement = Settlement::where('id', $meter_sale->settlement_no)->first();
                    $discount_types = [
                        'fixed' => 'Fixed',
                        'percentage' => 'Percentage',
                    ];
                    $already_pumps = MeterSale::where('settlement_no', $active_settlement->id)->pluck('pump_id')->toArray();
                    $business_id = $meter_sale->business_id;
                    if (request()->action_type != 'cancel') {
                        $already_pumps = array_values(array_diff($already_pumps, [$meter_sale->pump_id]));
                        $meter_sale = $meter_sale->toArray();
                    } else {
                        $meter_sale = [];
                    }
                } else {
                    $meter_sale = [];
                }
            }

            if (isset($active_settlement) && isset($business_id)) {
                $pump_nos = Pump::where('business_id', $business_id)
                    ->whereNotIn('id', $already_pumps)
                    ->pluck('pump_name', 'id');

                $html = view('pumperdashboard::settlement_pd.partials.meter_sale_form', compact('meter_sale', 'pump_nos', 'discount_types'))->render();

                $output = [
                    'success' => true,
                    'html' => $html,
                    'msg' => __('pumperdashboard::lang.success'),
                ];
            }
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = ['success' => false];
        }

        return $output;
    }
}

