<?php

namespace App\Utils\TransactionUtil;

use App\AccountTransaction;
use App\Account;
use App\AccountType;
use Modules\Fleet\Entities\Fleet;
use App\Business;
use App\BusinessLocation;
use App\Utils\Util;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\Currency;
use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Exceptions\PurchaseSellMismatch;
use App\Http\Controllers\Ecom\ContactController;
use App\InvoiceScheme;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\StockAdjustmentLine;
use App\TransactionSellLinesPurchaseLines;
use App\Variation;
use App\VariationLocationDetails;
use App\VariationStoreDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\PaymentMethod;
use App\System;;
use Illuminate\Support\Facades\Auth;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\TankSellLine;
use Modules\Petro\Entities\TankPurchaseLine;
use Modules\Property\Entities\Property;
use Modules\Property\Entities\PropertyBlock;
use Modules\Property\Entities\PropertySellLine;
use Modules\Property\Entities\PropertyAccountSetting;
use Modules\Petro\Entities\DipReading;
use Modules\Petro\Entities\PumpOperatorCommission;
use App\Variation_store_detail;
use App\ExpenseCategory;
use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\TankTransfer;
use Modules\Vat\Entities\VatCustomerStatement;
use Modules\Vat\Entities\VatCustomerStatementDetail;
use Modules\Shipping\Entities\ShippingAgentCommission;
use Modules\Shipping\Entities\ShippingPartnerCommission;
use Modules\SMS\Entities\SmsListInterest;
use Modules\Superadmin\Entities\RefillBusiness;
use App\SmsLog;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatSetting;
use Modules\Superadmin\Entities\SmsApiClient;
use Modules\Superadmin\Entities\SmsReminderSetting;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use App\ProductVariation;
use App\Unit;
use App\Brands;
use Modules\Petro\Entities\DailyVoucherItem;
use Modules\Petro\Entities\DailyVoucher;
use App\Http\Controllers\SellController;
use Illuminate\Http\Request;

/**
 * Stock levels, tank balances and purchase-to-sell mapping.
 *
 * MA-002: split out of App\Utils\TransactionUtil, which was 11,666 lines in
 * a single file with 180 methods.
 *
 * THIS IS CORE, NOT A MODULE - it is used across the whole system, so the
 * split is deliberately the safest kind available: a trait. The class keeps
 * its name, its namespace and every one of its methods, so all 485 call sites
 * that reach into TransactionUtil resolve exactly as before. Nothing outside
 * this directory needed to change.
 *
 * Method bodies are byte-identical to the original.
 *
 * Methods here: corectStockAccounts, adjustQuantity, deleteProductStockTransactionReverse, mapPurchaseSell, getStockAdjustedQuantityForPurchaseLine, adjustMappingPurchaseSell, mapDecrementPurchaseQuantity, mapPurchaseQuantityForDeleteStockAdjustment, adjustMappingPurchaseSellAfterEditingPurchase, getNonFuelProductBalanceOnDate, getOpeningClosingStock, getrangeOpeningClosingStock, getOpeningClosingStockFinal, getOpeningClosingStockNew, getLotNumbersFromVariation, updateQuantitySoldFromSellLine, isLotUsed, updateManageStockAccount, manageStockAccount, resolveSessionBusinessIdForStock, getTankBalanceById, getTankBalanceByDate, getFuelStockByProductId, getTankBalanceByDateInclude, getTankCurrentDifference, getTankProductBalanceByProductId, getTankProductBalanceByProductIdDate, getStockForSubCateogryByTransactionType
 */
trait ReportsStock
{
public function corectStockAccounts()
    {

        // first select all sell transactions
        // then link the transaction sell lines
        // search by amount (previously exclusive of VAT)
        // if transaction found, update the amount with the VAT inclusive amount
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);
        $sells = Transaction::where('type', 'sell')->where('transaction_updated', 0)->orderBy('id', 'DESC')->limit(1000)->get();
        foreach ($sells as $sale) {

            $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->leftjoin('product_variations', 'products.id', 'product_variations.product_id')
                ->leftjoin('variations', 'product_variations.id', 'variations.product_variation_id')
                ->where('transaction_id', $sale->id)
                ->select('transaction_sell_lines.*', 'products.stock_type', 'variations.dpp_inc_tax', 'variations.default_purchase_price', 'products.category_id', 'products.sub_category_id')
                ->get();
            foreach ($sell_lines as $sl) {

                if (!empty($sl->sub_category_id)) {
                    $account = Category::where('business_id', $sale->business_id)->where('id', $sl->sub_category_id)->select('cogs_account_id')->first()->cogs_account_id ?? null;
                    if (empty($account_id)) {
                        $account = Category::where('business_id', $sale->business_id)->where('id', $sl->category_id)->select('cogs_account_id')->first()->cogs_account_id ?? null;
                    }
                } else {
                    $account = Category::where('business_id', $sale->business_id)->where('id', $sl->category_id)->select('cogs_account_id')->first()->cogs_account_id ?? null;
                }

                $account = Account::where(DB::raw("REPLACE(`name`, '  ', ' ')"), 'Cost of Goods Sold')->where('business_id', $sale->business_id)->first()->id ?? 0;


                $initial_amount = abs($sl->quantity * $sl->default_purchase_price);
                $new_amount = abs($sl->quantity * $sl->dpp_inc_tax);

                $cogs = AccountTransaction::where('account_id', $account)->where('type', 'debit')->where('transaction_id', $sale->id)->where('amount', $initial_amount)->first();
                $fga = AccountTransaction::where('account_id', $sl->stock_type)->where('type', 'credit')->where('transaction_id', $sale->id)->where('amount', $initial_amount)->first();


                if (!empty($cogs) && $new_amount > 0) {
                    $cogs->amount = $new_amount;
                    $cogs->save;
                }

                if (!empty($fga) && $new_amount > 0) {
                    $fga->amount = $new_amount;
                    $fga->save;
                }
            }

            $sale->transaction_updated = 1;
            $sale->save();
        }

        echo "success " . $sells->count();
    }

    public function adjustQuantity($location_id, $product_id, $variation_id, $increment_qty, $store_id = null)
    {
        if ($increment_qty != 0) {
            $enable_stock = Product::find($product_id)->enable_stock;
            if ($enable_stock == 1) {
                // Adjust only the selected store's quantity if provided
                if (!empty($store_id)) {
                    $store_details = VariationStoreDetail::where('variation_id', $variation_id)
                        ->where('product_id', $product_id)
                        ->where('store_id', $store_id)
                        ->first();
                    if (!empty($store_details)) {
                        $store_details->increment('qty_available', $increment_qty);
                    }
                }


                //Adjust Quantity in variations location table
                VariationLocationDetails::where('variation_id', $variation_id)
                    ->where('product_id', $product_id)
                    ->where('location_id', $location_id)
                    ->increment('qty_available', $increment_qty);
            }
        }
    }
    /**
     * Add line for payment
     *
     * @param object/int $transaction
     * @param array $payments
     *
     * @return boolean
     */

    public function deleteProductStockTransactionReverse($transaction, $stock_id)
    {
        $contact_id = $transaction->contact_id;
        $account_transaction_data = [
            'amount' => $transaction->final_total,
            'contact_id' => $contact_id,
            'account_id' => $stock_id,
            'type' => 'credit',
            'operation_date' => \Carbon::now(),
            'created_by' => Auth::user()->id,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => null
        ];
        $new_account_transaction = AccountTransaction::createAccountTransaction($account_transaction_data);
        $new_account_transaction->deleted_by = Auth::user()->id;
        $new_account_transaction->save();
    }
    /**
     * Purchase currency details
     *
     * @param int $business_id
     *
     * @return object
     */

    public function mapPurchaseSell($business, $transaction_lines, $mapping_type = 'purchase', $check_expiry = true, $purchase_line_id = null)
    {
        if (empty($transaction_lines)) {
            return false;
        }
        $allow_overselling = !empty($business['pos_settings']['allow_overselling']) ? true : false;
        // When requests are queued offline and later synced, permit overselling
        // to avoid stock mapping failures at sync time.
        if (request()->has('offline_mode') && (int)request()->input('offline_mode') === 1) {
            $allow_overselling = true;
        }
        //Set flag to check for expired items during SELLING only.
        $stop_selling_expired = false;
        if ($check_expiry) {
            // Some legacy flows (Petro settlement/finalize, command jobs, older tenants) may
            // have a partial business array in session without the expiry keys.  Do not use
            // direct array indexes here, otherwise final settlement can fail with
            // "Undefined array key enable_product_expiry" before saving the transaction.
            $session_business = request()->session()->get('business', []);
            $enable_product_expiry = 0;
            $on_product_expiry = null;

            if (is_array($session_business)) {
                $enable_product_expiry = $session_business['enable_product_expiry'] ?? 0;
                $on_product_expiry = $session_business['on_product_expiry'] ?? null;
            } elseif (is_object($session_business)) {
                $enable_product_expiry = $session_business->enable_product_expiry ?? 0;
                $on_product_expiry = $session_business->on_product_expiry ?? null;
            }

            if ((int) $enable_product_expiry === 1 && $on_product_expiry === 'stop_selling') {
                if ($mapping_type == 'purchase') {
                    $stop_selling_expired = true;
                }
            }
        }
        $qty_selling = null;
        foreach ($transaction_lines as $line) {
            //Check if stock is not enabled then no need to assign purchase & sell
            $product = Product::find($line->product_id);
            if ($product->enable_stock != 1) {
                continue;
            }
            $qty_sum_query = $this->get_pl_quantity_sum_string('PL');
            //Get purchase lines, only for products with enable stock.
            $query = Transaction::join('purchase_lines AS PL', 'transactions.id', '=', 'PL.transaction_id')
                ->where('transactions.business_id', $business['id'])
                ->where('transactions.location_id', $business['location_id'])
                ->whereIn('transactions.type', [
                    'purchase',
                    'purchase_transfer',
                    'opening_stock',
                    'production_purchase'
                ])
                ->where('transactions.status', 'received')
                ->whereRaw("( $qty_sum_query ) < PL.quantity")
                ->where('PL.product_id', $line->product_id)
                ->where('PL.variation_id', $line->variation_id);
            //If product expiry is enabled then check for on expiry conditions
            if ($stop_selling_expired && empty($purchase_line_id)) {
                $stop_before = request()->session()->get('business')['stop_selling_before'];
                $expiry_date = \Carbon::today()->addDays($stop_before)->toDateString();
                $query->whereRaw('PL.exp_date IS NULL OR PL.exp_date > ?', [$expiry_date]);
            }
            //If lot number present consider only lot number purchase line
            if (!empty($line->lot_no_line_id)) {
                $query->where('PL.id', $line->lot_no_line_id);
            }
            //If purchase_line_id is given consider only that purchase line
            if (!empty($purchase_line_id)) {
                $query->where('PL.id', $purchase_line_id);
            }
            //Sort according to LIFO or FIFO
            if ($business['accounting_method'] == 'lifo') {
                $query = $query->orderBy('transaction_date', 'desc');
            } else {
                $query = $query->orderBy('transaction_date', 'asc');
            }
            $rows = $query->select(
                'PL.id as purchase_lines_id',
                DB::raw("(PL.quantity - ( $qty_sum_query )) AS quantity_available"),
                'PL.quantity_sold as quantity_sold',
                'PL.quantity_adjusted as quantity_adjusted',
                'PL.quantity_returned as quantity_returned',
                'PL.mfg_quantity_used as mfg_quantity_used',
                'transactions.invoice_no'
            )->get();
            $purchase_sell_map = [];
            //Iterate over the rows, assign the purchase line to sell lines.
            $qty_selling = $line->quantity;
            foreach ($rows as $k => $row) {
                $qty_allocated = 0;
                //removed stock adjustement quantity from $qty_sum_query  // Util.php method get_pl_quantity_sum_string()
                //adding stock adjustment qunatity for purchase line based on adjustemnt type
                $total_adjusted_quantity = $this->getStockAdjustedQuantityForPurchaseLine($row->purchase_lines_id);
                if (!empty($total_adjusted_quantity)) {
                    // add / subtract adjusted quanity from quantity available based on adjustment type
                    $row->quantity_available = $row->quantity_available + $total_adjusted_quantity;
                }
                //Check if qty_available is more or equal
                if ($qty_selling <= $row->quantity_available) {
                    $qty_allocated = $qty_selling;
                    $qty_selling = 0;
                } else {
                    $qty_selling = $qty_selling - $row->quantity_available;
                    $qty_allocated = $row->quantity_available;
                }
                //Check for sell mapping or stock adjsutment mapping
                if ($mapping_type == 'stock_adjustment') {
                    //Mapping of stock adjustment
                    $purchase_adjustment_map[] =
                        [
                            'stock_adjustment_line_id' => $line->id,
                            'purchase_line_id' => $row->purchase_lines_id,
                            'quantity' => $qty_allocated,
                            'created_at' => \Carbon::now(),
                            'updated_at' => \Carbon::now()
                        ];
                    //Update purchase line
                    $stock_adjustment_line = StockAdjustmentLine::where('transaction_id', $line->transaction_id)
                        ->where('variation_id', $line->variation_id)
                        ->first();

                    if (!empty($stock_adjustment_line) && $stock_adjustment_line->stock_adjustment_type == 'increase') {
                        PurchaseLine::where('id', $row->purchase_lines_id)
                            ->decrement('quantity_adjusted', $qty_allocated);
                    } else {
                        PurchaseLine::where('id', $row->purchase_lines_id)
                            ->increment('quantity_adjusted', $qty_allocated);
                    }
                } elseif ($mapping_type == 'purchase') {
                    //Mapping of purchase
                    $purchase_sell_map[] = [
                        'sell_line_id' => $line->id,
                        'purchase_line_id' => $row->purchase_lines_id,
                        'quantity' => $qty_allocated,
                        'created_at' => \Carbon::now(),
                        'updated_at' => \Carbon::now()
                    ];
                    //Update purchase line
                    PurchaseLine::where('id', $row->purchase_lines_id)
                        ->update(['quantity_sold' => $row->quantity_sold + $qty_allocated]);
                } elseif ($mapping_type == 'production_purchase') {
                    //Mapping of purchase
                    $purchase_sell_map[] = [
                        'sell_line_id' => $line->id,
                        'purchase_line_id' => $row->purchase_lines_id,
                        'quantity' => $qty_allocated,
                        'created_at' => \Carbon::now(),
                        'updated_at' => \Carbon::now()
                    ];
                    //Update purchase line
                    PurchaseLine::where('id', $row->purchase_lines_id)
                        ->update(['mfg_quantity_used' => $row->mfg_quantity_used + $qty_allocated]);
                }
                if ($qty_selling == 0) {
                    break;
                }
            }
            if ($qty_selling < 0 || is_null($qty_selling)) {
                //If overselling not allowed through exception else create mapping with blank purchase_line_id
                if (!$allow_overselling) {
                    $variation = Variation::find($line->variation_id);
                    $mismatch_name = $product->name;
                    if (!empty($variation->sub_sku)) {
                        $mismatch_name .= ' ' . 'SKU: ' . $variation->sub_sku;
                    }
                    if (!empty($qty_selling)) {
                        $mismatch_name .= ' ' . 'Quantity: ' . abs($qty_selling);
                    }
                    if ($mapping_type == 'purchase') {
                        $mismatch_error = trans(
                            "messages.purchase_sell_mismatch_exception",
                            ['product' => $mismatch_name]
                        );
                        if ($stop_selling_expired) {
                            $mismatch_error .= __('lang_v1.available_stock_expired');
                        }
                    } elseif ($mapping_type == 'stock_adjustment') {
                        $mismatch_error = trans(
                            "messages.purchase_stock_adjustment_mismatch_exception",
                            ['product' => $mismatch_name]
                        );
                    } else {
                        $mismatch_error = trans(
                            "lang_v1.quantity_mismatch_exception",
                            ['product' => $mismatch_name]
                        );
                    }
                    $business_name = optional(Business::find($business['id']))->name;
                    $location_name = optional(BusinessLocation::find($business['location_id']))->name;
                    \Log::emergency($mismatch_error . ' Business: ' . $business_name . ' Location: ' . $location_name);
                    throw new PurchaseSellMismatch($mismatch_error);
                } else {
                    //Mapping with no purchase line
                    $purchase_sell_map[] = [
                        'sell_line_id' => $line->id,
                        'purchase_line_id' => 0,
                        'quantity' => $qty_selling,
                        'created_at' => \Carbon::now(),
                        'updated_at' => \Carbon::now()
                    ];
                }
            }
            //Insert the mapping
            if (!empty($purchase_adjustment_map)) {
                TransactionSellLinesPurchaseLines::insert($purchase_adjustment_map);
            }
            if (!empty($purchase_sell_map)) {
                TransactionSellLinesPurchaseLines::insert($purchase_sell_map);
            }
        }
    }

    public function getStockAdjustedQuantityForPurchaseLine($purchase_line_id)
    {
        $quantity = 0;
        $stock_adjusted_query = TransactionSellLinesPurchaseLines::join('stock_adjustment_lines', 'transaction_sell_lines_purchase_lines.stock_adjustment_line_id', '=', 'stock_adjustment_lines.id')
            ->where('purchase_line_id', $purchase_line_id)
            ->whereNotNull('stock_adjustment_line_id')
            ->select('stock_adjustment_lines.stock_adjustment_type', 'stock_adjustment_lines.quantity')
            ->groupBy('stock_adjustment_lines.id')
            ->get();
        foreach ($stock_adjusted_query as $stock_adjusted) {
            if ($stock_adjusted->stock_adjustment_type == 'increase') {
                $quantity -= $stock_adjusted->quantity; // Increase should reduce 'used' amount
            }
            if ($stock_adjusted->stock_adjustment_type == 'decrease') {
                $quantity += $stock_adjusted->quantity; // Decrease should increase 'used' amount
            }
        }
        return $quantity;
    }
    /**
     * F => D (Delete all mapping lines, decrease the qty sold.)
     * D => F (Call the mapPurchaseSell function)
     * F => F (Check for quantity of existing product, call mapPurchase for new products.)
     *
     * @param  string $status_before
     * @param  object $transaction
     * @param  array $business
     * @param  array $deleted_line_ids = [] //deleted sell lines ids.
     *
     * @return void
     */

    public function adjustMappingPurchaseSell(
        $status_before,
        $transaction,
        $business,
        $deleted_line_ids = []
    ) {
        if ($status_before == 'final' && $transaction->status == 'draft') {
            //Get sell lines used for the transaction.
            $sell_purchases = Transaction::join('transaction_sell_lines AS SL', 'transactions.id', '=', 'SL.transaction_id')
                ->join('transaction_sell_lines_purchase_lines as TSP', 'SL.id', '=', 'TSP.sell_line_id')
                ->where('transactions.id', $transaction->id)
                ->select('TSP.purchase_line_id', 'TSP.quantity', 'TSP.id')
                ->get()
                ->toArray();
            //Included the deleted sell lines
            if (!empty($deleted_line_ids)) {
                $deleted_sell_purchases = TransactionSellLinesPurchaseLines::whereIn('sell_line_id', $deleted_line_ids)
                    ->select('purchase_line_id', 'quantity', 'id')
                    ->get()
                    ->toArray();
                $sell_purchases = $sell_purchases + $deleted_sell_purchases;
            }
            //TODO: Optimize the query to take our of loop.
            $sell_purchase_ids = [];
            if (!empty($sell_purchases)) {
                //Decrease the quantity sold of products
                foreach ($sell_purchases as $row) {
                    PurchaseLine::where('id', $row['purchase_line_id'])
                        ->decrement('quantity_sold', $row['quantity']);
                    $sell_purchase_ids[] = $row['id'];
                }
                //Delete the lines.
                TransactionSellLinesPurchaseLines::whereIn('id', $sell_purchase_ids)
                    ->delete();
            }
        } elseif ($status_before == 'draft' && $transaction->status == 'final') {
            $this->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');
        } elseif ($status_before == 'final' && $transaction->status == 'final') {
            //Handle deleted line
            if (!empty($deleted_line_ids)) {
                $deleted_sell_purchases = TransactionSellLinesPurchaseLines::whereIn('sell_line_id', $deleted_line_ids)
                    ->select('sell_line_id', 'quantity')
                    ->get();
                if (!empty($deleted_sell_purchases)) {
                    foreach ($deleted_sell_purchases as $value) {
                        $this->mapDecrementPurchaseQuantity($value->sell_line_id, $value->quantity);
                    }
                }
            }
            //Check for update quantity, new added rows, deleted rows.
            $sell_purchases = Transaction::join('transaction_sell_lines AS SL', 'transactions.id', '=', 'SL.transaction_id')
                ->leftjoin('transaction_sell_lines_purchase_lines as TSP', 'SL.id', '=', 'TSP.sell_line_id')
                ->where('transactions.id', $transaction->id)
                ->select(
                    'TSP.purchase_line_id',
                    'TSP.quantity AS tsp_quantity',
                    'TSP.id as tsp_id',
                    'SL.*'
                )
                ->get();
            $deleted_sell_lines = [];
            $new_sell_lines = [];
            $processed_sell_lines = [];
            foreach ($sell_purchases as $line) {
                if (empty($line->purchase_line_id)) {
                    $new_sell_lines[] = $line;
                } else {
                    //Skip if already processed.
                    if (in_array($line->purchase_line_id, $processed_sell_lines)) {
                        continue;
                    }
                    $processed_sell_lines[] = $line->purchase_line_id;
                    $total_sold_entry = TransactionSellLinesPurchaseLines::where('sell_line_id', $line->id)
                        ->select(DB::raw('SUM(quantity) AS quantity'))
                        ->first();
                    if ($total_sold_entry->quantity != $line->quantity) {
                        if ($line->quantity > $total_sold_entry->quantity) {
                            //If quantity is increased add it to new sell lines by decreasing tsp_quantity
                            $line_temp = $line;
                            $line_temp->quantity = $line_temp->quantity - $total_sold_entry->quantity;
                            $new_sell_lines[] = $line_temp;
                        } elseif ($line->quantity < $total_sold_entry->quantity) {
                            $decrement_qty = $total_sold_entry->quantity - $line->quantity;
                            $this->mapDecrementPurchaseQuantity($line->id, $decrement_qty);
                        }
                    }
                }
            }
            //Add mapping for new sell lines and for incremented quantity
            if (!empty($new_sell_lines)) {
                $this->mapPurchaseSell($business, $new_sell_lines);
            }
        }
    }
    /**
     * Decrease the purchase quantity from
     * transaction_sell_lines_purchase_lines and purchase_lines.quantity_sold
     *
     * @param  int $sell_line_id
     * @param  int $decrement_qty
     *
     * @return void
     */

    private function mapDecrementPurchaseQuantity($sell_line_id, $decrement_qty)
    {
        $sell_purchase_line = TransactionSellLinesPurchaseLines::where('sell_line_id', $sell_line_id)
            ->orderBy('id', 'desc')
            ->get();
        foreach ($sell_purchase_line as $row) {
            if ($row->quantity > $decrement_qty) {
                PurchaseLine::where('id', $row->purchase_line_id)
                    ->decrement('quantity_sold', $decrement_qty);
                $row->quantity = $row->quantity - $decrement_qty;
                $row->save();
                $decrement_qty = 0;
            } else {
                PurchaseLine::where('id', $row->purchase_line_id)
                    ->decrement('quantity_sold', $decrement_qty);
                $row->delete();
            }
            $decrement_qty = $decrement_qty - $row->quantity;
            if ($decrement_qty <= 0) {
                break;
            }
        }
    }
    /**
     * Decrement quantity adjusted in product line according to
     * transaction_sell_lines_purchase_lines
     * Used in delete of stock adjustment
     *
     * @param  array $line_ids
     *
     * @return boolean
     */

    public function mapPurchaseQuantityForDeleteStockAdjustment($line_ids)
    {
        if (empty($line_ids)) {
            return true;
        }
        $map_line = TransactionSellLinesPurchaseLines::whereIn('stock_adjustment_line_id', $line_ids)
            ->orderBy('id', 'desc')
            ->get();
        foreach ($map_line as $row) {
            PurchaseLine::where('id', $row->purchase_line_id)
                ->decrement('quantity_adjusted', $row->quantity);
        }
        //Delete the tslp line.
        TransactionSellLinesPurchaseLines::whereIn('stock_adjustment_line_id', $line_ids)
            ->delete();
        return true;
    }
    /**
     * Adjust the existing mapping between purchase & sell on edit of
     * purchase
     *
     * @param  string $before_status
     * @param  object $transaction
     * @param  object $delete_purchase_lines
     *
     * @return void
     */

    public function adjustMappingPurchaseSellAfterEditingPurchase($before_status, $transaction, $delete_purchase_lines)
    {
        if ($before_status == 'received' && $transaction->status == 'received') {
            //Check if there is some irregularities between purchase & sell and make appropiate adjustment.
            //Get all purchase line having irregularities.
            $purchase_lines = Transaction::join(
                'purchase_lines AS PL',
                'transactions.id',
                '=',
                'PL.transaction_id'
            )
                ->join(
                    'transaction_sell_lines_purchase_lines AS TSPL',
                    'PL.id',
                    '=',
                    'TSPL.purchase_line_id'
                )
                ->groupBy('TSPL.purchase_line_id')
                ->where('transactions.id', $transaction->id)
                ->havingRaw('SUM(TSPL.quantity) > MAX(PL.quantity)')
                ->select([
                    'TSPL.purchase_line_id AS id',
                    DB::raw('SUM(TSPL.quantity) AS tspl_quantity'),
                    DB::raw('MAX(PL.quantity) AS pl_quantity')
                ])
                ->get()
                ->toArray();
        } elseif ($before_status == 'received' && $transaction->status != 'received') {
            //Delete sell for those & add new sell or throw error.
            $purchase_lines = Transaction::join(
                'purchase_lines AS PL',
                'transactions.id',
                '=',
                'PL.transaction_id'
            )
                ->join(
                    'transaction_sell_lines_purchase_lines AS TSPL',
                    'PL.id',
                    '=',
                    'TSPL.purchase_line_id'
                )
                ->groupBy('TSPL.purchase_line_id')
                ->where('transactions.id', $transaction->id)
                ->select([
                    'TSPL.purchase_line_id AS id',
                    DB::raw('MAX(PL.quantity) AS pl_quantity')
                ])
                ->get()
                ->toArray();
        } else {
            return true;
        }
        //Get detail of purchase lines deleted
        if (!empty($delete_purchase_lines)) {
            $purchase_lines = $delete_purchase_lines->toArray() + $purchase_lines;
        }
        //All sell lines & Stock adjustment lines.
        $sell_lines = [];
        $stock_adjustment_lines = [];
        foreach ($purchase_lines as $purchase_line) {
            $tspl_quantity = isset($purchase_line['tspl_quantity']) ? $purchase_line['tspl_quantity'] : 0;
            $pl_quantity = isset($purchase_line['pl_quantity']) ? $purchase_line['pl_quantity'] : $purchase_line['quantity'];
            $extra_sold = abs($tspl_quantity - $pl_quantity);
            //Decrease the quantity from transaction_sell_lines_purchase_lines or delete it if zero
            $tspl = TransactionSellLinesPurchaseLines::where('purchase_line_id', $purchase_line['id'])
                ->leftjoin(
                    'transaction_sell_lines AS SL',
                    'transaction_sell_lines_purchase_lines.sell_line_id',
                    '=',
                    'SL.id'
                )
                ->leftjoin(
                    'stock_adjustment_lines AS SAL',
                    'transaction_sell_lines_purchase_lines.stock_adjustment_line_id',
                    '=',
                    'SAL.id'
                )
                ->orderBy('transaction_sell_lines_purchase_lines.id', 'desc')
                ->select([
                    'SL.product_id AS sell_product_id',
                    'SL.variation_id AS sell_variation_id',
                    'SL.id AS sell_line_id',
                    'SAL.product_id AS adjust_product_id',
                    'SAL.variation_id AS adjust_variation_id',
                    'SAL.id AS adjust_line_id',
                    'transaction_sell_lines_purchase_lines.quantity',
                    'transaction_sell_lines_purchase_lines.purchase_line_id',
                    'transaction_sell_lines_purchase_lines.id as tslpl_id'
                ])
                ->get();
            foreach ($tspl as $row) {
                if ($row->quantity <= $extra_sold) {
                    if (!empty($row->sell_line_id)) {
                        $sell_lines[] = (object) [
                            'id' => $row->sell_line_id,
                            'quantity' => $row->quantity,
                            'product_id' => $row->sell_product_id,
                            'variation_id' => $row->sell_variation_id,
                        ];
                        PurchaseLine::where('id', $row->purchase_line_id)
                            ->decrement('quantity_sold', $row->quantity);
                    } else {
                        $stock_adjustment_lines[] =
                            (object) [
                                'id' => $row->adjust_line_id,
                                'quantity' => $row->quantity,
                                'product_id' => $row->adjust_product_id,
                                'variation_id' => $row->adjust_variation_id,
                            ];
                        PurchaseLine::where('id', $row->purchase_line_id)
                            ->decrement('quantity_adjusted', $row->quantity);
                    }
                    $extra_sold = $extra_sold - $row->quantity;
                    TransactionSellLinesPurchaseLines::where('id', $row->tslpl_id)->delete();
                } else {
                    if (!empty($row->sell_line_id)) {
                        $sell_lines[] = (object) [
                            'id' => $row->sell_line_id,
                            'quantity' => $extra_sold,
                            'product_id' => $row->sell_product_id,
                            'variation_id' => $row->sell_variation_id,
                        ];
                        PurchaseLine::where('id', $row->purchase_line_id)
                            ->decrement('quantity_sold', $extra_sold);
                    } else {
                        $stock_adjustment_lines[] =
                            (object) [
                                'id' => $row->adjust_line_id,
                                'quantity' => $extra_sold,
                                'product_id' => $row->adjust_product_id,
                                'variation_id' => $row->adjust_variation_id,
                            ];
                        PurchaseLine::where('id', $row->purchase_line_id)
                            ->decrement('quantity_adjusted', $extra_sold);
                    }
                    TransactionSellLinesPurchaseLines::where('id', $row->tslpl_id)->update(['quantity' => $row->quantity - $extra_sold]);
                    $extra_sold = 0;
                }
                if ($extra_sold == 0) {
                    break;
                }
            }
        }
        $business = Business::find($transaction->business_id)->toArray();
        $business['location_id'] = $transaction->location_id;
        //Allocate the sold lines to purchases.
        if (!empty($sell_lines)) {
            $sell_lines = (object) $sell_lines;
            $this->mapPurchaseSell($business, $sell_lines, 'purchase');
        }
        //Allocate the stock adjustment lines to purchases.
        if (!empty($stock_adjustment_lines)) {
            $stock_adjustment_lines = (object) $stock_adjustment_lines;
            $this->mapPurchaseSell($business, $stock_adjustment_lines, 'stock_adjustment');
        }
    }
    /**
     * Check if transaction can be edited based on business     transaction_edit_days
     *
     * @param  int/object $transaction
     * @param  int $edit_duration
     *
     * @return boolean
     */

    public function getNonFuelProductBalanceOnDate($one, $date, $business_id)
    {

        $product_id = $one->product_id;
        $variation_id = $one->id;
        $sum = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
            ->where('transactions.business_id', $business_id)

            ->where(function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', 1)
                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->whereIn('transactions.type', ['purchase', 'purchase_return'])
            ->where('purchase_lines.product_id', $product_id)
            ->where('transactions.transaction_date', '<=', $date)
            ->where('purchase_lines.variation_id', $variation_id)
            ->where('transactions.status', 'received')
            ->whereNull('purchase_lines.deleted_at')
            ->groupBy('purchase_lines.variation_id')
            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity, SUM(purchase_lines.quantity_returned) AS sum_quantity_returned')
            ->withoutTrashed()
            ->first();

        $sumPurchaseDeleted = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
            ->where('transactions.business_id', $business_id)

            ->where(function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', 1)
                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->whereIn('transactions.type', ['_deleted_purchase'])
            ->where('purchase_lines.product_id', $product_id)
            ->where('purchase_lines.variation_id', $variation_id)
            ->where('transactions.status', 'received')
            ->where('transactions.transaction_date', '<=', $date)
            ->whereNull('purchase_lines.deleted_at')
            ->groupBy('purchase_lines.variation_id')
            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity, SUM(purchase_lines.quantity_returned) AS sum_quantity_returned')
            ->withoutTrashed()
            ->first();

        $sumTransfer = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')

            ->where('transactions.business_id', $business_id)

            ->where(function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', 1)
                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->whereIn('transactions.type', ['purchase_transfer'])
            ->where('purchase_lines.product_id', $product_id)
            ->where('purchase_lines.variation_id', $variation_id)
            ->where('transactions.status', 'received')
            ->where('transactions.transaction_date', '<=', $date)
            ->whereNull('purchase_lines.deleted_at')
            ->groupBy('purchase_lines.variation_id')
            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity')
            ->withoutTrashed()
            ->first();

        $sumProd = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')

            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', 1)
                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->whereIn('transactions.type', ['production_purchase'])
            ->where('purchase_lines.product_id', $product_id)
            ->where('purchase_lines.variation_id', $variation_id)
            ->where('transactions.transaction_date', '<=', $date)
            ->where('transactions.status', 'received')
            ->whereNull('purchase_lines.deleted_at')
            ->groupBy('purchase_lines.variation_id')
            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity')
            ->withoutTrashed()
            ->first();


        $sumOpening = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')

            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', 1)
                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->whereIn('transactions.type', ['opening_stock'])
            ->where('purchase_lines.product_id', $product_id)
            ->where('purchase_lines.variation_id', $variation_id)
            ->where('transactions.transaction_date', '<=', $date)
            ->where('transactions.status', 'received')
            ->whereNull('purchase_lines.deleted_at')
            ->groupBy('purchase_lines.variation_id')
            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity')
            ->withoutTrashed()
            ->first();

        $sumAdjust = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', 'stock_adjustment_lines.transaction_id')

            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', 1)
                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->whereIn('transactions.type', ['stock_adjustment'])
            ->where('stock_adjustment_lines.product_id', $product_id)
            ->where('stock_adjustment_lines.variation_id', $variation_id)
            ->where('transactions.transaction_date', '<=', $date)
            ->where('transactions.status', 'received')
            ->select(
                DB::raw("SUM(CASE WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'increase' THEN stock_adjustment_lines.quantity ELSE 0 END) as increased"),
                DB::raw("SUM(CASE WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'decrease' THEN stock_adjustment_lines.quantity ELSE 0 END) as decreased")
            )
            ->withoutTrashed()
            ->get()
            ->first();

        $incr = !empty($sumAdjust) ? $sumAdjust->increased : 0;
        $decr = !empty($sumAdjust) ? $sumAdjust->decreased : 0;

        $sumOp = !empty($sumOpening) ? $sumOpening->sum_quantity : 0;
        $sumPt = !empty($sumTransfer) ? $sumTransfer->sum_quantity : 0;
        $sumPr = !empty($sumProd) ? $sumProd->sum_quantity : 0;

        $sumQuantity = !empty($sum) ? $sum->sum_quantity : 0;
        $sumQuantityDeleted = !empty($sumPurchaseDeleted) ? $sumPurchaseDeleted->sum_quantity : 0;
        $sumQuantityReturnedDeleted = !empty($sumPurchaseDeleted) ? $sumPurchaseDeleted->sum_quantity_returned : 0;

        $sumQuantityReturned = !empty($sum) ? $sum->sum_quantity_returned : 0;


        $sumSell = Transaction::leftJoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')

            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', 1)
                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->whereIn('transactions.type', ['sell', 'sell_return'])
            ->where('transaction_sell_lines.product_id', $product_id)
            ->where('transaction_sell_lines.variation_id', $variation_id)
            ->where('transactions.status', 'final')
            ->where('transactions.transaction_date', '<=', $date)
            ->whereNull('transaction_sell_lines.deleted_at')
            ->groupBy('transaction_sell_lines.variation_id')
            ->selectRaw('SUM(transaction_sell_lines.quantity) AS sum_quantity, SUM(transaction_sell_lines.quantity_returned) AS sum_quantity_returned')
            ->withoutTrashed()
            ->first();

        $sumSellTr = Transaction::leftJoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')

            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', 1)
                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->whereIn('transactions.type', ['sell_transfer'])
            ->where('transaction_sell_lines.product_id', $product_id)
            ->where('transaction_sell_lines.variation_id', $variation_id)
            ->where('transactions.status', 'final')
            ->where('transactions.transaction_date', '<=', $date)
            ->whereNull('transaction_sell_lines.deleted_at')
            ->groupBy('transaction_sell_lines.variation_id')
            ->selectRaw('SUM(transaction_sell_lines.quantity) AS sum_quantity')
            ->withoutTrashed()
            ->first();

        $sumSellPr = Transaction::leftJoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')

            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', 1)
                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {
                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->whereIn('transactions.type', ['production_sell'])
            ->where('transaction_sell_lines.product_id', $product_id)
            ->where('transaction_sell_lines.variation_id', $variation_id)
            ->where('transactions.status', 'final')
            ->where('transactions.transaction_date', '<=', $date)
            ->whereNull('transaction_sell_lines.deleted_at')
            ->groupBy('transaction_sell_lines.variation_id')
            ->selectRaw('SUM(transaction_sell_lines.quantity) AS sum_quantity')
            ->withoutTrashed()
            ->first();

        $sumQuantitySell = !empty($sumSell) ? $sumSell->sum_quantity : 0;
        $sumQuantityTr = !empty($sumSellTr) ? $sumSellTr->sum_quantity : 0;
        $sumQuantityPr = !empty($sumSellPr) ? $sumSellPr->sum_quantity : 0;

        $sumQuantitySell_2 = !empty($sumSell) ? $sumSell->sum_quantity_sell : 0;
        $sumQuantityReturnedSell = !empty($sumSell) ? $sumSell->sum_quantity_returned : 0;

        $bal = $sumOp + $sumQuantity - $sumQuantityDeleted - $sumQuantityReturned + $sumQuantityReturnedDeleted - $sumQuantitySell + $sumQuantityReturnedSell + $incr - $decr + $sumPt - $sumQuantityTr - $sumQuantityPr + $sumPr;
        return $bal;
    }

    public function getOpeningClosingStock($business_id, $date, $location_id, $is_opening = false, $by_sale_price = false, $filters = [])
    {
        $query = PurchaseLine::join(
            'transactions as purchase',
            'purchase_lines.transaction_id',
            '=',
            'purchase.id'
        )
            ->where('purchase.business_id', $business_id);
        $price_query_part = 'v.dpp_inc_tax'; /*"(purchase_lines.purchase_price +
COALESCE(purchase_lines.item_tax, 0))";*/
        if ($by_sale_price) {
            $price_query_part = 'v.sell_price_inc_tax';
        }
        $query->leftjoin('variations as v', 'v.id', '=', 'purchase_lines.variation_id')
            ->leftjoin('products as p', 'p.id', '=', 'purchase_lines.product_id')
            ->leftjoin('variation_store_details as vsd', 'v.id', '=', 'vsd.variation_id');

        if (!empty($filters['category_id'])) {
            $query->where('p.category_id', $filters['category_id']);
        }
        if (!empty($filters['sub_category_id'])) {
            $query->where('p.sub_category_id', $filters['sub_category_id']);
        }
        if (!empty($filters['brand_id'])) {
            $query->where('p.brand_id', $filters['brand_id']);
        }
        if (!empty($filters['unit_id'])) {
            $query->where('p.unit_id', $filters['unit_id']);
        }
        if (!empty($filters['tax_id'])) {
            $query->where('p.tax', $filters['tax_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('p.type', $filters['type']);
        }
        if (isset($filters['active_state']) && $filters['active_state'] == 'active') {
            $query->where('p.is_inactive', 0);
        }
        if (isset($filters['active_state']) && $filters['active_state'] == 'inactive') {
            $query->where('p.is_inactive', 1);
        }
        if (isset($filters['not_for_selling']) && $filters['not_for_selling'] == 1) {
            $query->where('p.not_for_selling', 1);
        }
        if (!empty($filters['repair_model_id'])) {
            $query->where('p.repair_model_id', request()->get('repair_model_id'));
        }
        if (!empty($filters['store_id'])) {
            $query->where('vsd.store_id', $filters['store_id']);
        }
        if (isset($filters['only_mfg_products']) && $filters['only_mfg_products'] == 1) {
            $query->join('mfg_recipes as mr', 'mr.variation_id', '=', 'v.id');
        }

        //If opening
        if ($is_opening) {
            $next_day = \Carbon::createFromFormat('Y-m-d', $date)->addDay()->format('Y-m-d');
            $query->where(function ($query) use ($date, $next_day) {
                $query->whereRaw("date(transaction_date) <= '$date'")
                    ->orWhereRaw("date(transaction_date) = '$next_day' AND purchase.type='opening_stock' ");
            });
        } else {
            $query->whereRaw("date(transaction_date) <= '$date'");
        }


        $query->select(
            DB::raw("SUM((purchase_lines.quantity - purchase_lines.quantity_returned - purchase_lines.quantity_adjusted -
                            (SELECT COALESCE(SUM(tspl.quantity - tspl.qty_returned), 0) FROM
                            transaction_sell_lines_purchase_lines AS tspl
                            JOIN transaction_sell_lines as tsl ON
                            tspl.sell_line_id=tsl.id
                            JOIN transactions as sale ON
                            tsl.transaction_id=sale.id
                            WHERE tspl.purchase_line_id = purchase_lines.id AND
                            date(sale.transaction_date) <= '$date') ) * $price_query_part
                        ) as stock")
        );
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('purchase.location_id', $permitted_locations);
        }
        if (!empty($location_id)) {
            $query->where('purchase.location_id', $location_id);
        }


        $details = $query->first();

        return $details->stock;
    }

    public function getrangeOpeningClosingStock($business_id, $start_date, $end_date, $location_id)
    {
        $query = PurchaseLine::join(
            'transactions as purchase',
            'purchase_lines.transaction_id',
            '=',
            'purchase.id'
        )
            ->where('purchase.business_id', $business_id);
        $price_query_part = 'v.dpp_inc_tax'/*"(purchase_lines.purchase_price +
COALESCE(purchase_lines.item_tax, 0))"*/;

        $query->leftjoin('variations as v', 'v.id', '=', 'purchase_lines.variation_id')
            ->leftjoin('products as p', 'p.id', '=', 'purchase_lines.product_id')
            ->leftjoin('variation_store_details as vsd', 'v.id', '=', 'vsd.variation_id');


        //If opening

        $query->where(function ($query) use ($start_date, $end_date) {
            $query->whereRaw("date(transaction_date) <= '$end_date' AND date(transaction_date) >= '$start_date' AND purchase.type='opening_stock' ");
        });

        $query->select(
            DB::raw("SUM((purchase_lines.quantity - purchase_lines.quantity_returned - purchase_lines.quantity_adjusted -
                            (SELECT COALESCE(SUM(tspl.quantity - tspl.qty_returned), 0) FROM
                            transaction_sell_lines_purchase_lines AS tspl
                            JOIN transaction_sell_lines as tsl ON
                            tspl.sell_line_id=tsl.id
                            JOIN transactions as sale ON
                            tsl.transaction_id=sale.id
                            WHERE tspl.purchase_line_id = purchase_lines.id AND date(sale.transaction_date) >= '$start_date' AND
                            date(sale.transaction_date) <= '$end_date' AND sale.type='opening_stock') ) * $price_query_part
                        ) as stock")
        );
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('purchase.location_id', $permitted_locations);
        }
        if (!empty($location_id)) {
            $query->where('purchase.location_id', $location_id);
        }


        $details = $query->first();

        return $details->stock;
    }
    /**
     * Calculates opening stock on the given date
     *
     * @param int $business_id
     * @param string $date
     * @param string $end_date
     * @param int $location_id
     * @param boolean $is_opening = false
     *
     * @return float
     */

    public function getOpeningClosingStockFinal($business_id, $date, $end_date, $location_id)
    {
        $query = PurchaseLine::join(
            'transactions as purchase',
            'purchase_lines.transaction_id',
            '=',
            'purchase.id'
        )
            ->where('purchase.business_id', $business_id);

        $query->leftjoin('variations as v', 'v.id', '=', 'purchase_lines.variation_id')
            ->leftjoin('products as p', 'p.id', '=', 'purchase_lines.product_id');
        if (!empty($filters['category_id'])) {
            $query->where('p.category_id', $filters['category_id']);
        }
        if (!empty($filters['sub_category_id'])) {
            $query->where('p.sub_category_id', $filters['sub_category_id']);
        }
        if (!empty($filters['brand_id'])) {
            $query->where('p.brand_id', $filters['brand_id']);
        }
        if (!empty($filters['unit_id'])) {
            $query->where('p.unit_id', $filters['unit_id']);
        }

        //calculate first day of stock
        $next_day = \Carbon::createFromFormat('Y-m-d', $date)->addDay()->format('Y-m-d');
        $opening_date_query = PurchaseLine::join('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')
            ->select(DB::raw("min(date(transactions.transaction_date)) as stock_date"))
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'opening_stock')
            ->whereBetween('transactions.transaction_date', [$next_day . ' 00:00:00', $end_date . ' 23:59:59']);

        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('purchase.location_id', $permitted_locations);
            $opening_date_query->whereIn('transactions.location_id', $permitted_locations);
        }
        if (!empty($location_id)) {
            $query->where('purchase.location_id', $location_id);
            $opening_date_query->where('transactions.location_id', $location_id);
        }
        $stock_date = $opening_date_query->first()->stock_date;
        $stock_date_found = false;
        if ($stock_date) {
            $stock_date_format = \Carbon::createFromFormat('Y-m-d', $stock_date);
            $start_date_format = \Carbon::createFromFormat('Y-m-d', $next_day);
            $end_date_format = \Carbon::createFromFormat('Y-m-d', $end_date);
            if ($stock_date_format->gte($start_date_format) && $stock_date_format->lte($end_date_format)) {
                $stock_date_found = true;
            }
        }
        if ($stock_date_found) {
            $query->select(
                DB::raw("SUM((purchase_lines.quantity - purchase_lines.quantity_returned + purchase_lines.quantity_adjusted) * purchase_lines.purchase_price_inc_tax 
                            ) as stock")
            )
                ->where('purchase.type', 'opening_stock')
                ->whereRaw("date(transaction_date) <= '$stock_date'");

            $details = $query->first();


            // and then you can get query log

            return $details->stock;
        } else {
            $query->where(function ($query) use ($date, $next_day) {
                $query->whereRaw("date(transaction_date) <= '$date'")
                    ->orWhereRaw("date(transaction_date) = '$next_day' AND purchase.type='opening_stock' ");
            });
        }
        $query->select(
            DB::raw("SUM((purchase_lines.quantity - purchase_lines.quantity_returned + purchase_lines.quantity_adjusted -
                            (SELECT COALESCE(SUM(tspl.quantity - tspl.qty_returned), 0) FROM
                            transaction_sell_lines_purchase_lines AS tspl
                            JOIN transaction_sell_lines as tsl ON
                            tspl.sell_line_id=tsl.id
                            JOIN transactions as sale ON
                            tsl.transaction_id=sale.id
                            WHERE tspl.purchase_line_id = purchase_lines.id AND
                            date(sale.transaction_date) <= '$date'))  * purchase_lines.purchase_price_inc_tax
                        ) as stock")
        );


        // Your Eloquent query executed by using get()



        $details = $query->first();
        return $details->stock;
    }
    /**
     * Calculates total stock on the given date
     *
     * @param int $business_id
     * @param string $date
     * @param int $location_id
     * @param boolean $is_opening = false
     *
     * @return float
     */

    public function getOpeningClosingStockNew($business_id, $date, $end_date, $location_id, $is_opening = false)
    {
        $query = PurchaseLine::join(
            'transactions as purchase',
            'purchase_lines.transaction_id',
            '=',
            'purchase.id'
        )->where('purchase.business_id', $business_id);
        //If opening
        if ($is_opening) {
            $query->where(function ($query) use ($date, $end_date) {
                $query->whereRaw("date(transaction_date) <= '$end_date' AND purchase.type='opening_stock'");
            });
        } else {
            $query->whereRaw("date(transaction_date) <= '$date'");
        }
        $query->select(
            DB::raw("SUM((purchase_lines.quantity - purchase_lines.quantity_returned - purchase_lines.quantity_adjusted -
                            (SELECT COALESCE(SUM(tspl.quantity - tspl.qty_returned), 0) FROM
                            transaction_sell_lines_purchase_lines AS tspl
                            JOIN transaction_sell_lines as tsl ON
                            tspl.sell_line_id=tsl.id
                            JOIN transactions as sale ON
                            tsl.transaction_id=sale.id
                            WHERE tspl.purchase_line_id = purchase_lines.id AND
                            date(sale.transaction_date) <= '$date') ) * (purchase_lines.purchase_price +
                            COALESCE(purchase_lines.item_tax, 0))
                        ) as stock")
        );
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('purchase.location_id', $permitted_locations);
        }
        if (!empty($location_id)) {
            $query->where('purchase.location_id', $location_id);
        }
        $details = $query->first();
        return $details->stock;
    }
    /**
     * Gives the total sell commission for a commission agent within the date range passed
     *
     * @param int $business_id
     * @param string $start_date
     * @param string $end_date
     * @param int $location_id
     * @param int $commission_agent
     *
     * @return array
     */

    public function getLotNumbersFromVariation($variation_id, $business_id, $location_id, $exclude_empty_lot = false)
    {
        $query = PurchaseLine::join(
            'transactions as T',
            'purchase_lines.transaction_id',
            '=',
            'T.id'
        )
            ->where('T.business_id', $business_id)
            ->where('T.location_id', $location_id)
            ->where('purchase_lines.variation_id', $variation_id);
        //If expiry is disabled
        if (request()->session()->get('business.enable_product_expiry') == 0) {
            $query->whereNotNull('purchase_lines.lot_number');
        }
        if ($exclude_empty_lot) {
            $query->whereRaw('(purchase_lines.quantity_sold + purchase_lines.quantity_adjusted + purchase_lines.quantity_returned) < purchase_lines.quantity');
        } else {
            $query->whereRaw('(purchase_lines.quantity_sold + purchase_lines.quantity_adjusted + purchase_lines.quantity_returned) <= purchase_lines.quantity');
        }
        $purchase_lines = $query->select('purchase_lines.id as purchase_line_id', 'lot_number', 'purchase_lines.exp_date as exp_date', DB::raw('(purchase_lines.quantity - (purchase_lines.quantity_sold + purchase_lines.quantity_adjusted + purchase_lines.quantity_returned)) AS qty_available'))->get();
        return $purchase_lines;
    }
    /**
     * Checks if credit limit of a customer is exceeded
     *
     * @param  array $input
     * @param  int $exclude_transaction_id (For update sell)
     *
     * @return mixed
     * if exceeded returns credit_limit else false
     */

    public function updateQuantitySoldFromSellLine($sell_line, $new_quantity, $old_quantity)
    {
        $qty_difference = $this->num_uf($new_quantity) - $this->num_uf($old_quantity);
        if ($qty_difference != 0) {
            $qty_left_to_update = $qty_difference;
            $sell_line_purchase_lines = TransactionSellLinesPurchaseLines::where('sell_line_id', $sell_line->id)->get();
            //Return from each purchase line
            foreach ($sell_line_purchase_lines as $tslpl) {
                //If differnce is +ve decrease quantity sold
                if ($qty_difference > 0) {
                    if ($tslpl->qty_returned < $tslpl->quantity) {
                        //Quantity that can be returned from sell line purchase line
                        $tspl_qty_left_to_return = $tslpl->quantity - $tslpl->qty_returned;
                        $purchase_line = PurchaseLine::find($tslpl->purchase_line_id);
                        if ($qty_left_to_update <= $tspl_qty_left_to_return) {
                            $purchase_line->quantity_sold -= $qty_left_to_update;
                            $purchase_line->save();
                            $tslpl->qty_returned += $qty_left_to_update;
                            $tslpl->save();
                            break;
                        } else {
                            $purchase_line->quantity_sold -= $tspl_qty_left_to_return;
                            $purchase_line->save();
                            $tslpl->qty_returned += $tspl_qty_left_to_return;
                            $tslpl->save();
                            $qty_left_to_update -= $tspl_qty_left_to_return;
                        }
                    }
                } //If differnce is -ve increase quantity sold
                elseif ($qty_difference < 0) {
                    $purchase_line = PurchaseLine::find($tslpl->purchase_line_id);
                    $tspl_qty_to_return = $tslpl->qty_returned + $qty_left_to_update;
                    if ($tspl_qty_to_return >= 0) {
                        $purchase_line->quantity_sold -= $qty_left_to_update;
                        $purchase_line->save();
                        $tslpl->qty_returned += $qty_left_to_update;
                        $tslpl->save();
                        break;
                    } else {
                        $purchase_line->quantity_sold += $tslpl->quantity;
                        $purchase_line->save();
                        $tslpl->qty_returned = 0;
                        $tslpl->save();
                        $qty_left_to_update += $tslpl->quantity;
                    }
                }
            }
        }
    }
    /**
     * Check if return exist for a particular purchase or sell
     * @param id $transacion_id
     *
     * @return boolean
     */

    public function isLotUsed($transaction)
    {
        foreach ($transaction->purchase_lines as $purchase_line) {
            $exists = TransactionSellLine::where('lot_no_line_id', $purchase_line->id)->exists();
            if ($exists) {
                return true;
            }
        }
        return false;
    }
    /**
     * Creates recurring invoice from existing sale
     * @param obj $transaction, bool $is_draft
     *
     * @return obj $recurring_invoice
     */

    public function updateManageStockAccount($transaction)
    {
        $product_details = $this->getTransactionProductDetail($transaction->id, $transaction->type);
        if ($transaction->type == 'sell') {
            $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->leftjoin('variations', 'transaction_sell_lines.variation_id', 'variations.id')
                ->where('transaction_id', $transaction->id)
                ->select('transaction_sell_lines.*', 'products.category_id', 'products.enable_stock', 'products.stock_type', 'products.sub_category_id', 'variations.default_purchase_price', 'variations.dpp_inc_tax')
                ->get();

            $stock_account_ids = $sell_lines->pluck('stock_type')->filter()->unique()->values()->all();

            if (!empty($stock_account_ids)) {
                AccountTransaction::where('transaction_id', $transaction->id)
                    ->whereIn('account_id', $stock_account_ids)
                    ->whereNotNull('sell_line_id')
                    ->delete();
            }

            // For sell transactions, Finished Goods / stock accounts must be valued at purchase price (cost)
            // when updating after edits, matching the create logic in manageStockAccount
            foreach ($sell_lines as $sale) {
                if ($sale->quantity >= 0 && $sale->enable_stock) {
                    $quantity  = floatval($sale->quantity);
                    $unit_cost = 0.0;

                    // Prefer last_purchased_price (line-specific cost) then fall back to variation cost fields
                    if (!empty($sale->last_purchased_price) && $sale->last_purchased_price > 0) {
                        $unit_cost = floatval($sale->last_purchased_price);
                    } elseif (!empty($sale->dpp_inc_tax) && $sale->dpp_inc_tax > 0) {
                        $unit_cost = floatval($sale->dpp_inc_tax);
                    } elseif (!empty($sale->default_purchase_price) && $sale->default_purchase_price > 0) {
                        $unit_cost = floatval($sale->default_purchase_price);
                    }

                    if ($unit_cost > 0 && !empty($sale->stock_type)) {
                        // Use purchase price only - quantity * purchase_price (cost basis)
                        $line_amount = abs($quantity * $unit_cost);

                        $account_transaction_data = [
                            'type'           => 'credit',
                            'sub_type'       => null,
                            'amount'         => $line_amount,
                            'account_id'     => $sale->stock_type,
                            'transaction_id' => $transaction->id,
                            'sell_line_id'   => $sale->id,
                            'operation_date' => $transaction->transaction_date,
                        ];
                        AccountTransaction::createAccountTransaction($account_transaction_data);
                    }
                }
            }
        } else {
            foreach ($product_details as $product_detail) {
                if ($product_detail->enable_stock) {
                    $amount = $product_detail->amount;
                    if (!empty($product_detail->stock_type)) {
                        $account_transaction = AccountTransaction::where('transaction_id', $transaction->id)->where('account_id', $product_detail->stock_type)->first();
                        if (!empty($account_transaction)) {
                            $account_transaction->amount = $amount;
                            $account_transaction->operation_date = $transaction->transaction_date;
                            $account_transaction->save();
                        } else {
                            $account_transaction_data = [
                                'type' => 'debit',
                                'sub_type' => null,
                                'amount' => $amount,
                                'account_id' => $product_detail->stock_type,
                                'transaction_id' => $transaction->id,
                                'operation_date' => $transaction->transaction_date,
                            ];
                            AccountTransaction::createAccountTransaction($account_transaction_data);
                        }
                    }
                }
            }
        }
        return true;
    }

    public function manageStockAccount($transaction, $account_transaction_data, $trans_type, $amount, $sub_type = null, $status = null)
    {
        $product_details = $this->getTransactionProductDetail($transaction->id, $transaction->type);

        // For sell transactions, Finished Goods / stock accounts must be valued at purchase price (cost),
        // so the Finished Goods Account book shows quantity * purchase price and edits work correctly.
        if ($transaction->type == 'sell') {
            $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->leftjoin('variations', 'transaction_sell_lines.variation_id', 'variations.id')
                ->where('transaction_id', $transaction->id)
                ->select(
                    'transaction_sell_lines.*',
                    'products.category_id',
                    'products.enable_stock',
                    'products.stock_type',
                    'products.sub_category_id',
                    'variations.default_purchase_price',
                    'variations.dpp_inc_tax'
                )
                ->get();

            foreach ($sell_lines as $sale) {
                if ($sale->quantity >= 0 && $sale->enable_stock) { // exclude POS page return and non-stock
                    $account_transaction_data['type']     = $trans_type;
                    $account_transaction_data['sub_type'] = $sub_type;

                    $quantity  = floatval($sale->quantity);
                    $unit_cost = 0.0;

                    // Prefer last_purchased_price (line-specific cost) then fall back to variation cost fields
                    if (!empty($sale->last_purchased_price) && $sale->last_purchased_price > 0) {
                        $unit_cost = floatval($sale->last_purchased_price);
                    } elseif (!empty($sale->dpp_inc_tax) && $sale->dpp_inc_tax > 0) {
                        $unit_cost = floatval($sale->dpp_inc_tax);
                    } elseif (!empty($sale->default_purchase_price) && $sale->default_purchase_price > 0) {
                        $unit_cost = floatval($sale->default_purchase_price);
                    }

                    if ($unit_cost > 0) {
                        $line_amount                           = abs($quantity * $unit_cost);
                        $account_transaction_data['amount']    = $line_amount;
                        $account_transaction_data['operation_date'] = $transaction->transaction_date;

                        if (!empty($sale->stock_type)) {
                            $account_transaction_data['account_id']  = $sale->stock_type;
                            $account_transaction_data['sell_line_id'] = $sale->id;
                            // Finished Goods Account entries should not be tied to individual payments
                            // They are transaction-level entries, so remove transaction_payment_id
                            $fga_data = $account_transaction_data;
                            unset($fga_data['transaction_payment_id']);
                            AccountTransaction::createAccountTransaction($fga_data);
                        }
                    }
                }
            }
        } else {
            $account_transaction_data['type'] = $trans_type;
            $account_transaction_data['amount'] = $amount;
            foreach ($product_details as $product_detail) {
                if ($product_detail->enable_stock && $status == "received") {
                    $account_transaction_data['type'] = $trans_type;
                    $account_transaction_data['sub_type'] = $sub_type;
                    $account_transaction_data['amount'] = $product_detail->amount;
                    $account_transaction_data['operation_date'] = $transaction->transaction_date;
                    if (!empty($product_detail->stock_type)) {
                        $account_transaction_data['account_id'] = $product_detail->stock_type;
                        $account_transaction_data['transaction_id'] = $transaction->id;
                        $account_transaction = AccountTransaction::createAccountTransaction($account_transaction_data);
                    }
                }
            }
        }
        return true;
    }

    private function resolveSessionBusinessIdForStock()
    {
        return request()->session()->get('user.business_id')
            ?? request()->session()->get('business.id')
            ?? request()->session()->get('business_id');
    }

    public function getTankBalanceById($tank_id)
    {
        $business_id = $this->resolveSessionBusinessIdForStock();
        DB::enableQueryLog();
        $purchase_query = FuelTank::leftjoin('tank_purchase_lines', 'fuel_tanks.id', 'tank_purchase_lines.tank_id')
            ->leftJoin('transactions', 'tank_purchase_lines.transaction_id', '=', 'transactions.id')
            ->where('fuel_tanks.id', $tank_id)
            ->where('fuel_tanks.business_id', $business_id)
            ->where('transactions.type', '!=', '_deleted_purchase')
            ->whereNull('tank_purchase_lines.new_deleted_at')
            ->select([
                DB::raw('SUM(tank_purchase_lines.quantity) as pruchase_qty')
            ])->first();


        $transfers_in = FuelTank::leftjoin('tank_transfers', 'fuel_tanks.id', 'tank_transfers.to_tank')
            ->where('fuel_tanks.id', $tank_id)
            ->where('fuel_tanks.business_id', $business_id)
            ->select([
                DB::raw('SUM(tank_transfers.quantity) as qty')
            ])->first();

        $transfers_out = FuelTank::leftjoin('tank_transfers', 'fuel_tanks.id', 'tank_transfers.from_tank')
            ->where('fuel_tanks.id', $tank_id)
            ->where('fuel_tanks.business_id', $business_id)
            ->select([
                DB::raw('SUM(tank_transfers.quantity) as qty')
            ])->first();

        $sell_query = TankSellLine::where('tank_id', $tank_id)
            ->select([
                DB::raw('SUM(quantity) as sell_qty')
            ])->first();

        $purchase_qty = !empty($purchase_query->pruchase_qty) ? $purchase_query->pruchase_qty : 0;
        $transfer_in_qty = $transfers_in->qty ?? 0;
        $transfer_out_qty = $transfers_out->qty ?? 0;


        $sell_qty = !empty($sell_query->sell_qty) ? $sell_query->sell_qty : 0;
        $stock_adjustment = Transaction::leftjoin('stock_adjustment_lines', 'transactions.id', 'stock_adjustment_lines.transaction_id')
            ->where('stock_adjustment_lines.tank_id', $tank_id)
            ->where(function ($query) {
                $query->whereNull('transactions.sub_type')
                    ->orWhere('transactions.sub_type', '!=', 'dip_resetting');
            })
            ->select(
                DB::raw("SUM(CASE WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'increase' THEN stock_adjustment_lines.quantity WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'decrease' THEN -1 * stock_adjustment_lines.quantity ELSE 0 END) as stock_adjusted")
            )->first();
        $stock_adjusted = !empty($stock_adjustment->stock_adjusted) ? $stock_adjustment->stock_adjusted : 0;
        $calculated_balance = ($purchase_qty - abs($sell_qty) + $transfer_in_qty - $transfer_out_qty + $stock_adjusted);

        // IS1516: if the legacy transaction calculation cannot find rows for an existing fuel tank,
        // fall back to the tank's maintained current_balance instead of showing 0.00.
        if ((float) $calculated_balance == 0.0) {
            $tank_current_balance = FuelTank::where('id', $tank_id)
                ->where('business_id', $business_id)
                ->value('current_balance');
            if (!is_null($tank_current_balance) && (float) $tank_current_balance != 0.0) {
                return (float) $tank_current_balance;
            }
        }

        return $calculated_balance;
    }

    public function getTankBalanceByDate($tank_id, $date)
    {
        $business_id = $this->resolveSessionBusinessIdForStock();

        $purchase_query = FuelTank::leftjoin('tank_purchase_lines', 'fuel_tanks.id', 'tank_purchase_lines.tank_id')
            ->leftjoin('transactions', 'transactions.id', 'tank_purchase_lines.transaction_id')
            ->where('fuel_tanks.id', $tank_id)
            ->where('fuel_tanks.business_id', $business_id)
            ->where('transactions.created_at', '<', $date)
            ->where('transactions.type', '!=', '_deleted_purchase')
            ->whereNull('tank_purchase_lines.new_deleted_at')
            ->select([
                DB::raw('SUM(tank_purchase_lines.quantity) as pruchase_qty')
            ])->first();


        $transfers_in = FuelTank::leftjoin('tank_transfers', 'fuel_tanks.id', 'tank_transfers.to_tank')
            ->where('fuel_tanks.id', $tank_id)
            ->where('tank_transfers.created_at', '<', $date)
            ->where('fuel_tanks.business_id', $business_id)
            ->select([
                DB::raw('SUM(tank_transfers.quantity) as qty')
            ])->first();

        $transfers_out = FuelTank::leftjoin('tank_transfers', 'fuel_tanks.id', 'tank_transfers.from_tank')
            ->where('fuel_tanks.id', $tank_id)
            ->where('tank_transfers.created_at', '<', $date)
            ->where('fuel_tanks.business_id', $business_id)
            ->select([
                DB::raw('SUM(tank_transfers.quantity) as qty')
            ])->first();


        $sell_query = TankSellLine::where('tank_id', $tank_id)
            ->leftjoin('transactions', 'transactions.id', 'tank_sell_lines.transaction_id')
            ->where('transactions.created_at', '<', $date)
            ->select([
                DB::raw('SUM(quantity) as sell_qty')
            ])->first();

        $purchase_qty = !empty($purchase_query->pruchase_qty) ? $purchase_query->pruchase_qty : 0;
        $transfer_in_qty = $transfers_in->qty ?? 0;
        $transfer_out_qty = $transfers_out->qty ?? 0;


        $sell_qty = !empty($sell_query->sell_qty) ? $sell_query->sell_qty : 0;
        $sell_qty = !empty($sell_query->sell_qty) ? $sell_query->sell_qty : 0;
        $stock_adjustment = Transaction::leftjoin('stock_adjustment_lines', 'transactions.id', 'stock_adjustment_lines.transaction_id')
            ->where('stock_adjustment_lines.tank_id', $tank_id)
            ->where("transactions.created_at", "<", $date)
            ->where(function ($query) {
                $query->whereNull('transactions.sub_type')
                    ->orWhere('transactions.sub_type', '!=', 'dip_resetting');
            })
            ->select(
                DB::raw("SUM(CASE WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'increase' THEN stock_adjustment_lines.quantity WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'decrease' THEN -1 * stock_adjustment_lines.quantity ELSE 0 END) as stock_adjusted")
            )->first();
        $stock_adjusted = !empty($stock_adjustment->stock_adjusted) ? $stock_adjustment->stock_adjusted : 0;
        return ($purchase_qty - abs($sell_qty) + $transfer_in_qty - $transfer_out_qty + $stock_adjusted);
    }

    /**
     * MA-002 PERF: request-scoped memo cache.
     *
     * This is called once per row from the F17 form's DataTable, for every
     * row whose category is Fuel. Each call fetches the tanks for the product
     * and then runs getTankBalanceById() per tank - and that method issues
     * roughly a dozen joins and sums. A form with several fuel lines for the
     * same product and location repeats the identical work on every row.
     *
     * Both call sites are display-only - F17FormController::create() and
     * ::edit() - so the figure cannot change part-way through rendering.
     * Verified before adding this.
     *
     * The cache is keyed by business + product + location and lives for the
     * request only, so nothing is held between requests and a page reload
     * always recomputes.
     */
    private static array $ma002FuelStockCache = [];

    public function getFuelStockByProductId($product_id, $location_id)
    {
        $business_id = $this->resolveSessionBusinessIdForStock();

        $key = $business_id . '#' . $product_id . '#' . $location_id;

        if (array_key_exists($key, self::$ma002FuelStockCache)) {
            return self::$ma002FuelStockCache[$key];
        }

        $tanks = FuelTank::where('product_id', $product_id)
            ->where('location_id', $location_id)
            ->where('business_id', $business_id)
            ->get();

        $total_stock = 0;
        foreach ($tanks as $tank) {
            $total_stock += $this->getTankBalanceById($tank->id);
        }

        self::$ma002FuelStockCache[$key] = $total_stock;

        return $total_stock;
    }

    public function getTankBalanceByDateInclude($tank_id, $date)
    {
        $business_id = $this->resolveSessionBusinessIdForStock();

        $purchase_query = FuelTank::leftjoin('tank_purchase_lines', 'fuel_tanks.id', 'tank_purchase_lines.tank_id')
            ->leftjoin('transactions', 'transactions.id', 'tank_purchase_lines.transaction_id')
            ->where('fuel_tanks.id', $tank_id)
            ->where('fuel_tanks.business_id', $business_id)
            ->whereDate('transactions.transaction_date', '<=', $date)
            ->whereNull('tank_purchase_lines.new_deleted_at')
            ->select([
                DB::raw('SUM(tank_purchase_lines.quantity) as pruchase_qty')
            ])->first();


        $transfers_in = FuelTank::leftjoin('tank_transfers', 'fuel_tanks.id', 'tank_transfers.to_tank')
            ->where('fuel_tanks.id', $tank_id)
            ->where('tank_transfers.created_at', '<=', $date)
            ->where('fuel_tanks.business_id', $business_id)
            ->select([
                DB::raw('SUM(tank_transfers.quantity) as qty')
            ])->first();

        $transfers_out = FuelTank::leftjoin('tank_transfers', 'fuel_tanks.id', 'tank_transfers.from_tank')
            ->where('fuel_tanks.id', $tank_id)
            ->where('tank_transfers.created_at', '<=', $date)
            ->where('fuel_tanks.business_id', $business_id)
            ->select([
                DB::raw('SUM(tank_transfers.quantity) as qty')
            ])->first();


        $sell_query = TankSellLine::where('tank_id', $tank_id)
            ->leftjoin('transactions', 'transactions.id', 'tank_sell_lines.transaction_id')
            ->whereDate('transactions.transaction_date', '<=', $date)
            ->select([
                DB::raw('SUM(quantity) as sell_qty')
            ])->first();

        $purchase_qty = !empty($purchase_query->pruchase_qty) ? $purchase_query->pruchase_qty : 0;
        $transfer_in_qty = $transfers_in->qty ?? 0;
        $transfer_out_qty = $transfers_out->qty ?? 0;


        $sell_qty = !empty($sell_query->sell_qty) ? $sell_query->sell_qty : 0;
        $stock_adjustment = Transaction::leftjoin('stock_adjustment_lines', 'transactions.id', 'stock_adjustment_lines.transaction_id')
            ->where('stock_adjustment_lines.tank_id', $tank_id)
            ->whereDate('transactions.transaction_date', '<=', $date)
            ->where(function ($query) {
                $query->whereNull('transactions.sub_type')
                    ->orWhere('transactions.sub_type', '!=', 'dip_resetting');
            })
            ->select(
                DB::raw("SUM(CASE WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'increase' THEN stock_adjustment_lines.quantity WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'decrease' THEN -1 * stock_adjustment_lines.quantity ELSE 0 END) as stock_adjusted")
            )->first();
        $stock_adjusted = !empty($stock_adjustment->stock_adjusted) ? $stock_adjustment->stock_adjusted : 0;
        return ($purchase_qty - abs($sell_qty) + $transfer_in_qty - $transfer_out_qty + $stock_adjusted);
    }

    /**
     * @ModifiedBy Afes Oktavianus
     * @DateBy 05-06-2021
     * @Task 3343
     */

    public function getTankCurrentDifference($tank_id)
    {
        $business_id = $this->resolveSessionBusinessIdForStock();
        $fuel_balance_dip_reading = DipReading::query()->where('dip_readings.business_id', $business_id)
            ->where('dip_readings.tank_id', $tank_id)
            ->sum('fuel_balance_dip_reading');
        $current_qty = DipReading::query()->where('dip_readings.business_id', $business_id)
            ->where('dip_readings.tank_id', $tank_id)
            ->sum('current_qty');
        $current_diff = ($fuel_balance_dip_reading - $current_qty);
        return abs($current_diff);
    }

    /**
     * IS1970: ONE SOURCE OF TRUTH FOR PRODUCT STOCK.
     *
     * This returned a FUEL TANK balance - the sum of getTankBalanceById() over
     * every tank holding the product, with a current_balance fallback. Every
     * other category, and Stock Center itself, reads
     *     variation_location_details.qty_available
     * so fuel was the only thing in the application answered from a second
     * source. The two drifted apart, as two sources for one figure always
     * will: Auto Diesel read 1,200.000 in Stock Center against 1,011.00 on the
     * F22 form.
     *
     * The decision is that variation_location_details is the master. It is now
     * trustworthy for fuel, which it previously was not:
     *
     *   - fuel sales decrement it (IS1958 #1 - meter sales used to skip the
     *     decrement entirely, so it only ever grew)
     *   - each F22 stock take resets it to the physical count (IS1960), so it
     *     is re-anchored to reality rather than drifting indefinitely
     *
     * The change is made HERE rather than at the ~18 call sites that use this
     * helper (ReportController, F17, the Petro and Product ProductControllers,
     * ProductUtil). One edit converts them all consistently, and one edit
     * reverts them if this proves wrong - far safer than eighteen separate
     * changes that could drift out of step with each other.
     *
     * The method name is kept so no caller has to change. It no longer reads
     * tanks; genuine tank figures still come from getTankBalanceById() and
     * getTankBalanceByDate(), which the tank screens use directly and which are
     * untouched.
     *
     * NOTE: getTankProductBalanceByProductIdDate() below is deliberately NOT
     * converted. It answers "what was the balance ON a date", and
     * variation_location_details holds only the CURRENT quantity with no
     * history, so it cannot answer that question. Historical reporting must
     * keep deriving from transactions.
     *
     * @param  int       $product_id
     * @param  int|null  $location_id  Null sums every location.
     * @return float
     */
    public function getTankProductBalanceByProductId($product_id, $location_id = null)
    {
        $business_id = $this->resolveSessionBusinessIdForStock();

        /*
         * vld carries its own indexed product_id, so the product filter needs no
         * join. products is joined only to keep the business scope - the same
         * scope the tank query it replaces applied.
         */
        $query = DB::table('variation_location_details as vld')
            ->join('products as p', 'p.id', '=', 'vld.product_id')
            ->where('vld.product_id', $product_id)
            ->where('p.business_id', $business_id);

        if (!is_null($location_id)) {
            $query->where('vld.location_id', $location_id);
        }

        return (float) $query->sum(DB::raw('COALESCE(vld.qty_available, 0)'));
    }

    public function getTankProductBalanceByProductIdDate($product_id, $date, $location_id = null)
    {
        DB::enableQueryLog();
        $business_id = $this->resolveSessionBusinessIdForStock();

        $tankQuery = FuelTank::where('business_id', $business_id)
            ->where('product_id', $product_id);

        if (!is_null($location_id)) {
            $tankQuery->where('location_id', $location_id);
        }

        $fuel_tanks = $tankQuery->get();

        $balance = 0.0;
        foreach ($fuel_tanks as $tank) {
            $balance += $this->getTankBalanceByDateInclude($tank->id, $date);
        }

        $return_query = Transaction::join('purchase_lines as pl', 'transactions.id', '=', 'pl.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase_return')
            ->where('transactions.status', 'final')
            ->where('pl.product_id', $product_id)
            ->whereDate('transactions.transaction_date', '<=', $date);

        if (!is_null($location_id)) {
            $return_query->where('transactions.location_id', $location_id);
        }

        $returned_qty = (float) $return_query->sum(DB::raw('COALESCE(pl.quantity_returned, pl.quantity, 0)'));

        $adjusted_balance = $balance - $returned_qty;

        return $adjusted_balance > 0 ? $adjusted_balance : 0.0;
    }

    // Modified by Muneeb Ahmad for Store Dropdown - Task#3454

    public function getStockForSubCateogryByTransactionType($type, $sub_cat_id, $start_date, $end_date, $get_previous = false, $get_qty = true, $module = 'dailysummary_stocksummary_qty')
    {
        $business_id = session()->get('user.business_id');
        $query = Transaction::where('transactions.business_id', $business_id)->where('transactions.type', $type);
        if ($type == 'sell') {
            $query->leftjoin(
                'transaction_sell_lines',
                'transactions.id',
                'transaction_sell_lines.transaction_id'
            )
                ->leftjoin(
                    'products',
                    'transaction_sell_lines.product_id',
                    'products.id'
                );
        }
        if ($type == 'purchase' || $type == 'opening_stock') {
            $query->leftjoin(
                'purchase_lines',
                'transactions.id',
                'purchase_lines.transaction_id'
            )
                ->leftjoin(
                    'products',
                    'purchase_lines.product_id',
                    'products.id'
                );
        }
        if ($type == 'stock_adjustment') {
            $query->leftjoin(
                'stock_adjustment_lines',
                'transactions.id',
                'stock_adjustment_lines.transaction_id'
            )
                ->leftjoin(
                    'products',
                    'stock_adjustment_lines.product_id',
                    'products.id'
                );
        }
        $query->leftjoin(
            'variations',
            'products.id',
            'variations.product_id'
        );
        if (!empty($start_date) && !empty($end_date) && !$get_previous && $type != 'opening_stock') {
            $query->whereDate('transactions.transaction_date', '>=', $start_date);
            $query->whereDate('transactions.transaction_date', '<=', $end_date);
        }
        if ($get_previous && $type != 'opening_stock') {
            $query->whereDate('transactions.transaction_date', '<', $start_date);
        }
        if ($type == 'opening_stock') {
            $query->whereDate('transactions.transaction_date', '<', $end_date);
        }
        $query->where('products.sub_category_id', $sub_cat_id)->groupBy('products.sub_category_id');

        $query->where(function ($q) use ($module) {
            $q->whereNull('products.disabled_in')->orwhereRaw("NOT FIND_IN_SET(?, products.disabled_in)", [$module]);
        });


        if (!$get_qty) {
            if ($type == 'sell') {
                $query->where(function ($q) {
                    $q->where('transactions.sub_type', '!=', 'credit_sale')->orWhereNull('transactions.sub_type');
                });
                $amount = $query->select(
                    DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)*variations.dpp_inc_tax) as amount')
                )->first();
            }
            return $amount ? $amount->amount : 0;
        }
        if ($get_qty) {
            if ($type == 'sell') {
                $query->where(function ($q) {
                    $q->where('transactions.sub_type', '!=', 'credit_sale')->orWhereNull('transactions.sub_type');
                });
                $qty = $query->select(
                    DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as qty')
                )->first();
            }
            if ($type == 'purchase' || $type == 'opening_stock') {
                $qty = $query->select(
                    DB::raw("SUM(purchase_lines.quantity) as qty")
                )->first();
            }
            if ($type == 'stock_adjustment') {
                $qty = $query->select(
                    DB::raw("SUM(stock_adjustment_lines.quantity) as qty")
                )->first();
            }
            return $qty ? $qty->qty : 0;
        }
    }
    // Added by Muneeb Ahmad for Store Dropdown
}
