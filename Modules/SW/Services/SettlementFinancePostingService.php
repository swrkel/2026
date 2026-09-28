<?php

namespace Modules\SW\Services;

use App\ContactLedger;
use App\Transaction;
use App\Utils\TransactionUtil;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Posts a FINAL SW settlement into the application's normal accounting ledger.
 *
 * IS2211 root cause:
 * SW Settlement used to save only sw_* rows.  Finance, Finance Reports and the
 * account books read the normal transactions / transaction_sell_lines /
 * transaction_payments / account_transactions tables, so a successfully saved
 * SW settlement was invisible to every downstream accounting report.
 *
 * This bridge deliberately writes STANDARD accounting rows.  Finance therefore
 * does not need a special "read SW tables" exception and every existing report
 * sees the settlement through the same source as POS / Petro settlements.
 *
 * The posting is idempotent.  A settlement transaction is found by its SW
 * marker/settlement number and each collection is found by its source marker.
 * Re-entering this method cannot double-post the same settlement.
 */
class SettlementFinancePostingService
{
    public function __construct(protected TransactionUtil $transactionUtil)
    {
    }

    /**
     * Post one settled SW settlement to the core Finance ledger.
     *
     * This method is called inside SettlementSaveService's database transaction,
     * so either the SW settlement and all accounting rows commit together or
     * none of them do.
     */
    public function post(int $settlementId): void
    {
        if ($settlementId <= 0) {
            return;
        }

        // IMPORTANT (IS2214): metadata must be checked on the CURRENT tenant
        // connection. Laravel's Schema facade can retain metadata from a prior
        // connection while tenancy is switching, which previously made this
        // method return quietly even though the tenant tables existed. That left
        // a successfully saved SW settlement with no Finance/Customer posting.
        $requiredTables = [
            'sw_settlements', 'transactions', 'transaction_sell_lines',
            'transaction_payments', 'account_transactions', 'accounts',
            'products', 'variations', 'transaction_sell_lines_purchase_lines',
        ];
        foreach ($requiredTables as $table) {
            if (! $this->tableExists($table)) {
                throw new \RuntimeException(
                    'SW settlement Finance posting cannot continue because table "' . $table .
                    '" is unavailable in the current tenant database.'
                );
            }
        }

        $settlement = DB::table('sw_settlements')->where('id', $settlementId)->first();
        if (! $settlement) {
            return;
        }

        $businessId = (int) ($settlement->business_id ?? 0);
        $locationId = (int) ($settlement->location_id ?? 0);
        if ($businessId <= 0 || $locationId <= 0) {
            throw new \RuntimeException('SW settlement has no valid business/location for Finance posting.');
        }

        $shiftNumbers = $this->shiftNumbers($settlementId);
        $mainLines = $this->mainSaleLines($settlementId);
        $mainSalesTotal = round((float) $mainLines->sum('amount'), 4);

        $mainTransactionId = null;
        if ($mainSalesTotal > 0.00005) {
            $mainTransactionId = $this->ensureSaleTransaction(
                $settlement,
                $mainSalesTotal,
                'settlement',
                null,
                $this->settlementMarker($settlementId)
            );

            $this->ensureSellLines($mainTransactionId, $mainLines, $locationId);

            // IS2253: Petro General -> Tank Management derives Fuel Tank
            // balances and both Tank Transaction reports from tank_sell_lines.
            // SW previously created only the normal transaction_sell_lines, so
            // Finance/Product stock moved while the related fuel tank stayed
            // unchanged and no tank transaction appeared. Post the meter
            // quantity to the pump's configured fuel tank using the SAME core
            // settlement transaction. The helper is idempotent on
            // transaction_id + tank_id.
            $this->ensureTankSellLines(
                $mainTransactionId,
                $settlementId,
                $businessId,
                $locationId
            );

            $this->postProductAccounting($mainTransactionId, $businessId, $locationId);
        }

        // Credit sales are genuine customer receivables.  Keep them separate
        // from the cash/card/cheque sale transaction exactly as the established
        // settlement accounting flow does.
        $this->postCreditSales($settlement, $businessId, $locationId);

        // Payment account books in the current Finance module recognise
        // established settlement transaction types (settlement/card_payment,
        // settlement/cash_payment, etc.). Post receipts through those standard
        // transaction shapes instead of attaching them to the sale transaction.
        $this->postCollections(
            $settlement,
            $businessId,
            $locationId,
            $shiftNumbers
        );
        $this->assertCollectionsPosted($settlement, $businessId);

        if ($mainTransactionId) {
            // Post the non-cash classifications that make up Total Paid too.
            // Without these allocations the settlement sale reaches Sales
            // Income, but its expense/shortage/drawing/loan side is absent from
            // Finance and the financial statements are incomplete.
            $this->postSettlementAllocations(
                $settlement,
                $mainTransactionId,
                $businessId,
                $locationId,
                $shiftNumbers
            );
        }
    }

    /**
     * Meter Sales + Other Sales + Other Income.
     * Credit Sales are posted separately to Accounts Receivable.
     */
    protected function mainSaleLines(int $settlementId): Collection
    {
        $rows = collect();

        if ($this->tableExists('sw_settlement_lines')) {
            foreach (DB::table('sw_settlement_lines')->where('settlement_id', $settlementId)->orderBy('id')->get() as $line) {
                $qty = (float) ($line->quantity ?? 0);
                $amount = (float) ($line->amount ?? 0);
                if ($qty <= 0 || $amount < 0) {
                    continue;
                }

                $productId = (int) ($line->product_id ?? 0);
                if ($productId <= 0) {
                    $productId = $this->productForPump((int) ($line->pump_id ?? 0));
                }

                $rows->push((object) [
                    'source' => 'meter',
                    'source_id' => (int) $line->id,
                    'product_id' => $productId,
                    'variation_id' => null,
                    'quantity' => $qty,
                    'rate' => (float) ($line->rate ?? 0),
                    'discount_type' => (string) ($line->discount_type ?? 'fixed'),
                    // SW fixed discount is a TOTAL line discount.
                    'discount_value' => (float) ($line->discount_value ?? 0),
                    'amount' => $amount,
                ]);
            }
        }

        if ($this->tableExists('sw_other_sales')) {
            foreach (DB::table('sw_other_sales')->where('settlement_id', $settlementId)->orderBy('id')->get() as $line) {
                $qty = (float) ($line->quantity ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $rows->push((object) [
                    'source' => 'other-sale',
                    'source_id' => (int) $line->id,
                    'product_id' => (int) ($line->product_id ?? 0),
                    'variation_id' => (int) ($line->variation_id ?? 0),
                    'quantity' => $qty,
                    'rate' => (float) ($line->rate ?? 0),
                    'discount_type' => (string) ($line->discount_type ?? 'fixed'),
                    'discount_value' => (float) ($line->discount_value ?? 0),
                    'amount' => (float) ($line->amount ?? 0),
                ]);
            }
        }

        if ($this->tableExists('sw_other_income')) {
            foreach (DB::table('sw_other_income')->where('settlement_id', $settlementId)->orderBy('id')->get() as $line) {
                $qty = (float) ($line->quantity ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $rows->push((object) [
                    'source' => 'other-income',
                    'source_id' => (int) $line->id,
                    'product_id' => (int) ($line->product_id ?? 0),
                    'variation_id' => null,
                    'quantity' => $qty,
                    'rate' => (float) ($line->rate ?? 0),
                    'discount_type' => 'fixed',
                    'discount_value' => 0.0,
                    'amount' => (float) ($line->amount ?? 0),
                ]);
            }
        }

        return $rows;
    }

    /**
     * One normal core transaction for the sale side.
     */
    protected function ensureSaleTransaction(
        object $settlement,
        float $amount,
        string $subType,
        ?int $contactId,
        string $marker,
        array $extra = []
    ): int {
        $businessId = (int) $settlement->business_id;
        $settlementNo = (string) $settlement->settlement_no;
        $columns = $this->columns('transactions');

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('sub_type', $subType)
            ->where('invoice_no', $settlementNo);

        if (isset($columns['transaction_note'])) {
            $query->where('transaction_note', 'like', '%' . $marker . '%');
        } elseif ($contactId) {
            $query->where('contact_id', $contactId)
                ->whereBetween('final_total', [$amount - 0.00005, $amount + 0.00005]);
        }

        $existing = $query->orderBy('id')->first();
        if ($existing) {
            $update = $this->compatibleRow('transactions', array_merge([
                'location_id' => (int) $settlement->location_id,
                'transaction_date' => $settlement->transaction_date,
                'status' => 'final',
                'total_before_tax' => $amount,
                'final_total' => $amount,
                'updated_at' => now(),
            ], $extra));

            if ($update) {
                DB::table('transactions')->where('id', $existing->id)->update($update);
            }

            return (int) $existing->id;
        }

        $row = array_merge([
            'business_id' => $businessId,
            'location_id' => (int) $settlement->location_id,
            'type' => 'sell',
            'sub_type' => $subType,
            'status' => 'final',
            'payment_status' => $subType === 'credit_sale' ? 'due' : 'paid',
            'contact_id' => $contactId,
            'pump_operator_id' => (int) ($settlement->pump_operator_id ?? 0) ?: null,
            'transaction_date' => $settlement->transaction_date,
            'total_before_tax' => $amount,
            'final_total' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'invoice_no' => $settlementNo,
            'ref_no' => $settlementNo,
            'is_settlement' => 1,
            'is_credit_sale' => $subType === 'credit_sale' ? 1 : 0,
            'created_by' => (int) ($settlement->created_by ?? auth()->id()),
            'transaction_note' => 'SW Settlement ' . $settlementNo . ' ' . $marker,
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra);

        return (int) DB::table('transactions')->insertGetId($this->compatibleRow('transactions', $row));
    }

    /**
     * Create standard transaction_sell_lines.  Finance Trading Profit reads this
     * table directly; Finance Sales Income/COGS/Finished Goods helpers also use
     * these rows.
     */
    protected function ensureSellLines(int $transactionId, Collection $lines, int $locationId): void
    {
        if ($transactionId <= 0 || $lines->isEmpty()) {
            return;
        }

        $columns = $this->columns('transaction_sell_lines');

        foreach ($lines as $line) {
            $productId = (int) ($line->product_id ?? 0);
            $qty = (float) ($line->quantity ?? 0);
            if ($qty <= 0) {
                continue;
            }
            if ($productId <= 0) {
                throw new \RuntimeException(
                    'SW settlement sale line ' . ($line->source ?? 'line') . '#' . (int) ($line->source_id ?? 0) .
                    ' has no product mapping, so Finance Sales/COGS/Finished Goods cannot be posted safely.'
                );
            }

            $variationId = $this->validVariation(
                $productId,
                (int) ($line->variation_id ?? 0),
                $locationId
            );
            if ($variationId <= 0) {
                throw new \RuntimeException(
                    'SW settlement product #' . $productId . ' has no valid variation for location #' . $locationId .
                    ', so Finance posting cannot continue.'
                );
            }

            $marker = '[SW-LINE:' . ($line->source ?? 'line') . ':' . (int) ($line->source_id ?? 0) . ']';

            $exists = DB::table('transaction_sell_lines')->where('transaction_id', $transactionId);
            if (isset($columns['sell_line_note'])) {
                $exists->where('sell_line_note', 'like', '%' . $marker . '%');
            } else {
                $exists->where('product_id', $productId)
                    ->where('variation_id', $variationId)
                    ->whereBetween('quantity', [$qty - 0.00005, $qty + 0.00005])
                    ->whereBetween('unit_price_inc_tax', [((float) $line->rate) - 0.00005, ((float) $line->rate) + 0.00005]);
            }

            $existingSelect = ['id'];
            if (isset($columns['sell_line_note'])) {
                $existingSelect[] = 'sell_line_note';
            }
            $existingLine = $exists->first($existingSelect);
            if ($existingLine) {
                // IS2249 stock repair: pre-fix SW sell lines already exist but
                // did not decrement variation_location_details. The stock marker
                // lets a retry repair exactly once without double-decrementing.
                if (isset($columns['sell_line_note'])
                    && ! str_contains((string) ($existingLine->sell_line_note ?? ''), '[SW-STOCK-DECREMENTED]')) {
                    $this->decreaseAvailableStock($productId, $variationId, $locationId, $qty);
                    $stockMarkerUpdate = [
                        'sell_line_note' => trim((string) ($existingLine->sell_line_note ?? '') . ' [SW-STOCK-DECREMENTED]'),
                    ];
                    if (isset($columns['updated_at'])) {
                        $stockMarkerUpdate['updated_at'] = now();
                    }
                    DB::table('transaction_sell_lines')
                        ->where('id', (int) $existingLine->id)
                        ->update($stockMarkerUpdate);
                }
                continue;
            }
            $rawDiscountType = strtolower(trim((string) ($line->discount_type ?? 'fixed')));
            $discountType = $rawDiscountType === 'percentage' ? 'percentage' : 'fixed';
            $rawDiscount = max(0.0, (float) ($line->discount_value ?? 0));

            // transaction_sell_lines stores a fixed discount PER UNIT.
            // Meter/Other Sale SW rows store a fixed discount for the WHOLE
            // line, whereas credit-sale rows already carry unit_discount. Keep
            // those two meanings separate or a credit sale discount is divided
            // by quantity a second time.
            if ($rawDiscountType === 'fixed_per_unit') {
                $lineDiscount = $rawDiscount;
            } elseif ($discountType === 'fixed' && $qty > 0) {
                $lineDiscount = $rawDiscount / $qty;
            } else {
                $lineDiscount = $rawDiscount;
            }

            $row = [
                'transaction_id' => $transactionId,
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $qty,
                'quantity_returned' => 0,
                'unit_price_before_discount' => (float) ($line->rate ?? 0),
                'unit_price' => (float) ($line->rate ?? 0),
                'unit_price_inc_tax' => (float) ($line->rate ?? 0),
                'line_discount_type' => $discountType,
                'line_discount_amount' => $lineDiscount,
                'item_tax' => 0,
                'tax_id' => null,
                'sell_line_note' => 'SW settlement posting ' . $marker,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $sellLineId = (int) DB::table('transaction_sell_lines')->insertGetId(
                $this->compatibleRow('transaction_sell_lines', $row)
            );

            // Standard POS sales reduce variation_location_details immediately.
            // SW previously created only the core sell line/purchase mapping, so
            // Product New -> Stock Center kept showing the old Available qty.
            $this->decreaseAvailableStock($productId, $variationId, $locationId, $qty);

            if ($sellLineId > 0 && isset($columns['sell_line_note'])) {
                $stockMarkerUpdate = [
                    'sell_line_note' => trim($row['sell_line_note'] . ' [SW-STOCK-DECREMENTED]'),
                ];
                if (isset($columns['updated_at'])) {
                    $stockMarkerUpdate['updated_at'] = now();
                }
                DB::table('transaction_sell_lines')->where('id', $sellLineId)->update($stockMarkerUpdate);
            }
        }
    }


    /**
     * Mirror SW meter sales into Petro's tank ledger.
     *
     * Petro General does not calculate a tank balance from
     * transaction_sell_lines. Its Fuel Tanks, Tank Transaction Details and
     * Tank Transaction Summary screens all use tank_sell_lines. Each SW meter
     * line therefore needs one tank movement against the fuel tank configured
     * on its pump.
     *
     * Several pumps may draw from the same tank. Aggregate those pump lines
     * before writing so each settlement transaction has one row per tank.
     * Re-running a settlement posting updates the existing row instead of
     * inserting another one, preventing a retry from reducing the tank twice.
     */
    protected function ensureTankSellLines(
        int $transactionId,
        int $settlementId,
        int $businessId,
        int $locationId
    ): void {
        if ($transactionId <= 0 || $settlementId <= 0 || $businessId <= 0 || $locationId <= 0) {
            return;
        }

        // Petro may be disabled for a tenant. In that case there is no tank
        // ledger to maintain and SW must continue to work normally.
        if (! $this->tableExists('tank_sell_lines')
            || ! $this->tableExists('pumps')
            || ! $this->tableExists('fuel_tanks')
            || ! $this->tableExists('sw_settlement_lines')
            || ! $this->hasColumn('pumps', 'fuel_tank_id')) {
            return;
        }

        $tankSellColumns = $this->columns('tank_sell_lines');
        foreach (['business_id', 'transaction_id', 'tank_id', 'product_id', 'quantity'] as $requiredColumn) {
            if (! isset($tankSellColumns[$requiredColumn])) {
                throw new \RuntimeException(
                    'SW tank posting cannot continue because tank_sell_lines.' .
                    $requiredColumn . ' is unavailable.'
                );
            }
        }

        $meterLines = DB::table('sw_settlement_lines as sl')
            ->leftJoin('pumps as p', 'p.id', '=', 'sl.pump_id')
            ->leftJoin('fuel_tanks as ft', 'ft.id', '=', 'p.fuel_tank_id')
            ->where('sl.settlement_id', $settlementId)
            ->where('sl.quantity', '>', 0)
            ->orderBy('sl.id')
            ->get([
                'sl.id',
                'sl.pump_id',
                'sl.product_id as settlement_product_id',
                'sl.quantity',
                'p.business_id as pump_business_id',
                'p.location_id as pump_location_id',
                'p.fuel_tank_id',
                'ft.business_id as tank_business_id',
                'ft.location_id as tank_location_id',
                'ft.product_id as tank_product_id',
            ]);

        if ($meterLines->isEmpty()) {
            return;
        }

        $aggregated = [];

        foreach ($meterLines as $line) {
            $lineId = (int) ($line->id ?? 0);
            $pumpId = (int) ($line->pump_id ?? 0);
            $tankId = (int) ($line->fuel_tank_id ?? 0);
            $quantity = (float) ($line->quantity ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            if ($pumpId <= 0 || $tankId <= 0 || empty($line->tank_business_id)) {
                throw new \RuntimeException(
                    'SW settlement meter line #' . $lineId .
                    ' has no related Fuel Tank. Configure the pump Fuel Tank before saving the settlement.'
                );
            }

            if ((int) $line->pump_business_id !== $businessId
                || (int) $line->tank_business_id !== $businessId) {
                throw new \RuntimeException(
                    'SW settlement meter line #' . $lineId .
                    ' points to a pump/fuel tank outside the current business.'
                );
            }

            if ((int) $line->pump_location_id !== $locationId
                || (int) $line->tank_location_id !== $locationId) {
                throw new \RuntimeException(
                    'SW settlement meter line #' . $lineId .
                    ' points to a pump/fuel tank outside the settlement location.'
                );
            }

            $settlementProductId = (int) ($line->settlement_product_id ?? 0);
            $tankProductId = (int) ($line->tank_product_id ?? 0);
            $productId = $tankProductId > 0 ? $tankProductId : $settlementProductId;

            if ($productId <= 0) {
                throw new \RuntimeException(
                    'Fuel Tank #' . $tankId . ' has no product mapping for SW settlement posting.'
                );
            }

            // A pump should dispense the same product as its configured tank.
            // Do not silently move a quantity under a different product because
            // that would make product stock and tank stock disagree.
            if ($settlementProductId > 0
                && $tankProductId > 0
                && $settlementProductId !== $tankProductId) {
                throw new \RuntimeException(
                    'SW settlement meter line #' . $lineId .
                    ' product does not match the product configured for Fuel Tank #' . $tankId . '.'
                );
            }

            $key = $tankId . ':' . $productId;
            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'tank_id' => $tankId,
                    'product_id' => $productId,
                    'quantity' => 0.0,
                ];
            }
            $aggregated[$key]['quantity'] += $quantity;
        }

        foreach ($aggregated as $tankLine) {
            $tankId = (int) $tankLine['tank_id'];
            $productId = (int) $tankLine['product_id'];
            $quantity = round((float) $tankLine['quantity'], 4);

            if ($quantity <= 0) {
                continue;
            }

            // One SW core transaction is dedicated to one settlement. This
            // makes transaction_id + tank_id the safest idempotency key and
            // also allows the quantity to be corrected on a legitimate retry.
            $existing = DB::table('tank_sell_lines')
                ->where('business_id', $businessId)
                ->where('transaction_id', $transactionId)
                ->where('tank_id', $tankId)
                ->orderBy('id')
                ->first();

            $row = [
                'business_id' => $businessId,
                'transaction_id' => $transactionId,
                'tank_id' => $tankId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('tank_sell_lines')
                    ->where('id', (int) $existing->id)
                    ->update($this->compatibleRow('tank_sell_lines', $row));
                continue;
            }

            $row['created_at'] = now();
            DB::table('tank_sell_lines')->insert(
                $this->compatibleRow('tank_sell_lines', $row)
            );
        }
    }

    /**
     * Decrease the standard location stock cache for a newly-posted SW sale.
     *
     * Product New -> Stock Center reads variation_location_details.qty_available,
     * while the old SW bridge only created transaction_sell_lines and FIFO maps.
     * This update is executed inside the same settlement DB transaction and only
     * for stock-enabled products, so a failure rolls the settlement back instead
     * of leaving Finance and stock out of sync.
     */
    protected function decreaseAvailableStock(
        int $productId,
        int $variationId,
        int $locationId,
        float $quantity
    ): void {
        if ($productId <= 0 || $variationId <= 0 || $locationId <= 0 || $quantity <= 0) {
            return;
        }

        if (! $this->tableExists('products')
            || ! $this->hasColumn('products', 'enable_stock')
            || (int) (DB::table('products')->where('id', $productId)->value('enable_stock') ?? 0) !== 1) {
            return;
        }

        if (! $this->tableExists('variation_location_details')
            || ! $this->hasColumn('variation_location_details', 'variation_id')
            || ! $this->hasColumn('variation_location_details', 'location_id')
            || ! $this->hasColumn('variation_location_details', 'qty_available')) {
            throw new \RuntimeException(
                'SW settlement cannot reduce stock for product #' . $productId .
                ' because the standard location stock table/columns are unavailable.'
            );
        }

        $stockQuery = DB::table('variation_location_details')
            ->where('variation_id', $variationId)
            ->where('location_id', $locationId);

        if ($this->hasColumn('variation_location_details', 'product_id')) {
            $stockQuery->where('product_id', $productId);
        }

        $stockRow = $stockQuery->lockForUpdate()->first(['id', 'qty_available']);
        if (! $stockRow) {
            throw new \RuntimeException(
                'SW settlement cannot reduce stock for product #' . $productId .
                ', variation #' . $variationId . ', location #' . $locationId .
                ' because no location-stock row exists.'
            );
        }

        $update = [
            'qty_available' => (float) ($stockRow->qty_available ?? 0) - $quantity,
        ];
        if ($this->hasColumn('variation_location_details', 'updated_at')) {
            $update['updated_at'] = now();
        }

        DB::table('variation_location_details')
            ->where('id', (int) $stockRow->id)
            ->update($update);
    }

    /**
     * Reuse the application's established category-account rules for Sales
     * Income, COGS and Finished Goods, then build purchase/sell mappings used by
     * Finance's Trading Profit report.
     */
    protected function postProductAccounting(int $transactionId, int $businessId, int $locationId): void
    {
        /** @var Transaction|null $transaction */
        $transaction = Transaction::with('sell_lines')->find($transactionId);
        if (! $transaction || $transaction->sell_lines->isEmpty()) {
            return;
        }

        $base = [
            'amount' => abs((float) $transaction->final_total),
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'note' => 'SW Settlement ' . $transaction->invoice_no,
            'business_id' => $businessId,
        ];

        // TransactionUtil's category / sub-category account resolution reads
        // session('business.id'), while AccountTransaction creation can read
        // session('user.business_id'). In a tenant + multi-business application
        // that value can be absent/stale while the SW controller correctly uses
        // the authenticated user's business. Temporarily align it with the
        // already-validated settlement business so Sales Income, COGS and
        // Finished Goods are posted to the correct business accounts.
        $hadSession = request()->hasSession();
        $oldBusinessId = $hadSession ? request()->session()->get('business.id') : null;
        $oldUserBusinessId = $hadSession ? request()->session()->get('user.business_id') : null;
        if ($hadSession) {
            request()->session()->put('business.id', $businessId);
            request()->session()->put('user.business_id', $businessId);
        }

        try {
            /*
             | IS2217: post the three product-accounting legs directly.
             |
             | The previous bridge delegated these writes to the application's
             | TransactionUtil. That helper is shared by several legacy modules
             | and resolves business/account context through request/session
             | state. In the SW tenant flow that made the settlement capable of
             | saving while Sales Income / COGS / Finished Goods remained absent
             | (or were written with incomplete context) from Finance.
             |
             | Resolve the exact same category/product account mappings here and
             | write standard account_transactions rows with the SW settlement's
             | authoritative business, transaction date, transaction_id and
             | sell_line_id. Finance account books and every financial statement
             | already read those standard rows, so no report-only workaround is
             | required.
             */
            $this->postProductLedgerRows($transaction, $businessId, $locationId);
            $this->deduplicateProductAccountingRows($transaction);
            $this->mapMissingPurchaseLines($transaction, $businessId, $locationId);
        } finally {
            if ($hadSession) {
                if ($oldBusinessId === null) {
                    request()->session()->forget('business.id');
                } else {
                    request()->session()->put('business.id', $oldBusinessId);
                }
                if ($oldUserBusinessId === null) {
                    request()->session()->forget('user.business_id');
                } else {
                    request()->session()->put('user.business_id', $oldUserBusinessId);
                }
            }
        }

        $this->assertProductAccountingPosted($transaction, $businessId);
    }

    /**
     * Write Sales Income, COGS and Finished Goods directly to the normal ledger.
     * These are the rows consumed by Finance account books, Trial Balance,
     * Income Statement, Balance Sheet and Profit & Loss.
     */
    protected function postProductLedgerRows(Transaction $transaction, int $businessId, int $locationId): void
    {
        foreach ($transaction->sell_lines as $line) {
            $lineId = (int) ($line->id ?? 0);
            $productId = (int) ($line->product_id ?? 0);
            $variationId = (int) ($line->variation_id ?? 0);
            $qty = (float) ($line->quantity ?? 0);
            if ($lineId <= 0 || $productId <= 0 || $qty <= 0) {
                continue;
            }

            $product = DB::table('products')->where('id', $productId)->first();
            if (! $product) {
                throw new \RuntimeException('SW settlement product #' . $productId . ' no longer exists.');
            }

            $categoryId = (int) ($product->category_id ?? 0);
            $subCategoryId = (int) ($product->sub_category_id ?? 0);

            $salesIncomeId = $this->mappedCategoryAccountId(
                $businessId,
                $subCategoryId,
                $categoryId,
                'sales_income_account_id',
                'sale_income'
            );
            if ($salesIncomeId <= 0) {
                $salesIncomeId = $this->accountIdByNames(
                    $businessId,
                    ['Sales Income', 'Sale Income'],
                    ['%Sales%Income%', '%Sale%Income%']
                );
            }
            if ($salesIncomeId <= 0) {
                throw new \RuntimeException(
                    'SW settlement cannot post Sales Income for product #' . $productId .
                    '. Configure a Sales Income account for business #' . $businessId . '.'
                );
            }

            $unitPrice = (float) ($line->unit_price_inc_tax ?? $line->unit_price ?? 0);
            $gross = $unitPrice * $qty;
            $discount = 0.0;
            $discountAmount = max(0.0, (float) ($line->line_discount_amount ?? 0));
            if (($line->line_discount_type ?? null) === 'percentage') {
                $discount = $gross * $discountAmount / 100;
            } else {
                $discount = $discountAmount * $qty;
            }
            $saleAmount = round(max(0.0, $gross - $discount), 6);

            $saleMarker = '[SW-PRODUCT:' . $transaction->id . ':' . $lineId . ':SALE]';
            if ($saleAmount > 0.0000005) {
                $this->ensureLedgerRow([
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'account_id' => $salesIncomeId,
                    'type' => 'credit',
                    'sub_type' => 'ledger_show',
                    'amount' => $saleAmount,
                    'operation_date' => $transaction->transaction_date,
                    'created_by' => $transaction->created_by,
                    'transaction_id' => $transaction->id,
                    'sell_line_id' => $lineId,
                    'note' => 'SW Settlement ' . $transaction->invoice_no . ' | Sales Income ' . $saleMarker,
                    'txnType' => 'sw_settlement_sale_income',
                ], $saleMarker);
            }

            $stockEnabled = (int) ($product->enable_stock ?? 0) === 1;
            if (! $stockEnabled) {
                continue;
            }

            $variation = DB::table('variations')->where('id', $variationId)->first();
            $unitCost = 0.0;
            if ($this->hasColumn('transaction_sell_lines', 'last_purchased_price')) {
                $unitCost = (float) ($line->last_purchased_price ?? 0);
            }
            if ($unitCost <= 0 && $variation) {
                $unitCost = (float) ($variation->dpp_inc_tax ?? 0);
            }
            if ($unitCost <= 0 && $variation) {
                $unitCost = (float) ($variation->default_purchase_price ?? 0);
            }

            // A zero-cost stock item legitimately has no COGS/FG movement.
            if ($unitCost <= 0.0000001) {
                continue;
            }

            $costAmount = round(abs($qty * $unitCost), 6);
            $stockAccountId = (int) ($product->stock_type ?? 0);
            if ($stockAccountId <= 0 || ! $this->accountBelongsToBusiness($stockAccountId, $businessId)) {
                throw new \RuntimeException(
                    'SW stock product #' . $productId .
                    ' has no valid Finished Goods / stock account for business #' . $businessId . '.'
                );
            }

            $cogsId = $this->mappedCategoryAccountId(
                $businessId,
                $subCategoryId,
                $categoryId,
                'cogs_account_id',
                'cogs'
            );
            if ($cogsId <= 0) {
                $cogsId = $this->accountIdByNames(
                    $businessId,
                    ['Cost of Goods Sold', 'COGS'],
                    ['%Cost%Goods%Sold%', '%COGS%']
                );
            }
            if ($cogsId <= 0) {
                throw new \RuntimeException(
                    'SW settlement cannot post COGS for product #' . $productId .
                    '. Configure a COGS account for business #' . $businessId . '.'
                );
            }

            $fgMarker = '[SW-PRODUCT:' . $transaction->id . ':' . $lineId . ':FG]';
            $this->ensureLedgerRow([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'account_id' => $stockAccountId,
                'type' => 'credit',
                'amount' => $costAmount,
                'operation_date' => $transaction->transaction_date,
                'created_by' => $transaction->created_by,
                'transaction_id' => $transaction->id,
                'sell_line_id' => $lineId,
                'note' => 'SW Settlement ' . $transaction->invoice_no . ' | Finished Goods ' . $fgMarker,
                'txnType' => 'sw_settlement_finished_goods',
            ], $fgMarker);

            $cogsMarker = '[SW-PRODUCT:' . $transaction->id . ':' . $lineId . ':COGS]';
            $this->ensureLedgerRow([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'account_id' => $cogsId,
                'type' => 'debit',
                'sub_type' => 'ledger_show',
                'amount' => $costAmount,
                'operation_date' => $transaction->transaction_date,
                'created_by' => $transaction->created_by,
                'transaction_id' => $transaction->id,
                'sell_line_id' => $lineId,
                'note' => 'SW Settlement ' . $transaction->invoice_no . ' | COGS ' . $cogsMarker,
                'txnType' => 'sw_settlement_cogs',
            ], $cogsMarker);
        }
    }

    /** Resolve category/sub-category accounting mappings without session state. */
    protected function mappedCategoryAccountId(
        int $businessId,
        int $subCategoryId,
        int $categoryId,
        string $column,
        string $kind
    ): int {
        if (! $this->tableExists('categories') || ! $this->hasColumn('categories', $column)) {
            return 0;
        }

        foreach (array_values(array_unique(array_filter([$subCategoryId, $categoryId]))) as $id) {
            $category = DB::table('categories')
                ->where('id', $id)
                ->where('business_id', $businessId)
                ->first();
            if (! $category) {
                continue;
            }

            $mapped = (int) ($category->{$column} ?? 0);
            if ($mapped > 0 && $this->accountBelongsToBusiness($mapped, $businessId)) {
                return $mapped;
            }

            $name = trim((string) ($category->name ?? ''));
            if ($name === '') {
                continue;
            }

            $exact = $kind === 'cogs'
                ? ['COGS - ' . $name, 'Cost of Goods Sold - ' . $name]
                : [
                    'Sales Income - ' . $name,
                    'Sales Income – ' . $name,
                    'Sale Income - ' . $name,
                    'Sale Income – ' . $name,
                ];
            $found = $this->accountIdByNames($businessId, $exact);
            if ($found > 0) {
                return $found;
            }
        }

        return 0;
    }

    /**
     * Core TransactionUtil create helpers insert product account rows directly.
     * Collapse exact semantic duplicates per sell line so a deliberate retry of
     * the SW posting bridge cannot duplicate Sales Income, COGS or Finished Goods.
     * Keep the newest row because it represents the current final settlement data.
     */
    protected function deduplicateProductAccountingRows(Transaction $transaction): void
    {
        foreach ($transaction->sell_lines as $line) {
            $rows = DB::table('account_transactions')
                ->where('transaction_id', $transaction->id)
                ->where('sell_line_id', $line->id)
                ->orderByDesc('id')
                ->get(['id', 'account_id', 'type', 'sub_type']);

            $seen = [];
            $delete = [];
            foreach ($rows as $row) {
                $key = (int) $row->account_id . '|' . (string) $row->type . '|' . (string) ($row->sub_type ?? '');
                if (isset($seen[$key])) {
                    $delete[] = (int) $row->id;
                } else {
                    $seen[$key] = true;
                }
            }

            if ($delete) {
                DB::table('account_transactions')->whereIn('id', $delete)->delete();
            }
        }
    }

    /**
     * Purchase/sell mappings are what the Finance Trading Profit report uses for
     * actual cost.  Map only un-mapped lines so a retry cannot increment purchase
     * line quantity_sold twice.
     */
    protected function mapMissingPurchaseLines(Transaction $transaction, int $businessId, int $locationId): void
    {
        if (! $this->tableExists('transaction_sell_lines_purchase_lines')) {
            return;
        }

        $sellLineIds = $transaction->sell_lines->pluck('id')->filter()->values();
        if ($sellLineIds->isEmpty()) {
            return;
        }

        $mappedIds = DB::table('transaction_sell_lines_purchase_lines')
            ->whereIn('sell_line_id', $sellLineIds->all())
            ->pluck('sell_line_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $missing = $transaction->sell_lines->filter(
            fn ($line) => ! in_array((int) $line->id, $mappedIds, true)
        )->values();

        if ($missing->isEmpty()) {
            return;
        }

        $business = [
            'id' => $businessId,
            'accounting_method' => session('business.accounting_method') ?: 'fifo',
            'location_id' => $locationId,
            'pos_settings' => $this->businessPosSettings($businessId),
        ];

        try {
            $this->transactionUtil->mapPurchaseSell($business, $missing, 'purchase');
        } catch (\Throwable $e) {
            // IS2214: Trading Profit depends on this map. Never report a saved
            // settlement as successful while its cost mapping is missing.
            throw new \RuntimeException(
                'SW settlement could not create the purchase/sale mapping required by Finance Trading Profit: ' .
                $e->getMessage(),
                0,
                $e
            );
        }
    }

    protected function businessPosSettings(int $businessId): array
    {
        if (! $this->tableExists('business') || ! $this->hasColumn('business', 'pos_settings')) {
            return [];
        }

        $raw = DB::table('business')->where('id', $businessId)->value('pos_settings');
        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Credit sales: create the customer receivable/customer-ledger side only.
     *
     * IS2240: an SW Credit Sale is a settlement allocation of a sale already
     * posted through Meter Sales / Other Sales / Other Income. It must therefore
     * NOT create a second set of transaction_sell_lines, Sales Income, COGS or
     * Finished Goods movements. The transaction row is retained for established
     * customer-due compatibility; only Accounts Receivable + Customer Ledger are
     * posted from this method.
     */
    protected function postCreditSales(object $settlement, int $businessId, int $locationId): void
    {
        if (! $this->tableExists('sw_settlement_credit_sales')) {
            return;
        }

        $receivableId = $this->accountIdByNames($businessId, [
            'Accounts Receivable',
            'Account Receivable',
        ], ['%Receivable%']);

        foreach (DB::table('sw_settlement_credit_sales')
            ->where('settlement_id', $settlement->id)
            ->orderBy('id')
            ->get() as $credit) {

            $amount = round((float) ($credit->amount ?? 0), 4);
            $contactId = (int) ($credit->contact_id ?? 0);
            if ($amount <= 0 || $contactId <= 0) {
                continue;
            }
            if ($receivableId <= 0) {
                throw new \RuntimeException(
                    'SW credit sale #' . (int) $credit->id .
                    ' cannot be posted because Accounts Receivable is not configured for business #' . $businessId . '.'
                );
            }

            $marker = '[SW-CREDIT:' . (int) $credit->id . ']';
            $extra = [
                'payment_status' => 'due',
                'ref_no' => $credit->order_no ?? $credit->reference ?? null,
                'customer_ref' => $credit->vehicle_no ?? null,
                'order_no' => $credit->order_no ?? $credit->reference ?? null,
                'order_date' => $credit->order_date ?? null,
                'credit_sale_id' => (int) $credit->id,
                'petro_settlement_id' => (int) $settlement->id,
            ];

            $transactionId = $this->ensureSaleTransaction(
                $settlement,
                $amount,
                'credit_sale',
                $contactId,
                $marker,
                $extra
            );

            /*
             | Do NOT call ensureSellLines()/postProductAccounting() here.
             | The same physical sale is already represented by the main SW sale
             | transaction. Creating product lines here is what made Credit Sales
             | appear again in Sales Income, COGS and Finished Goods account books.
            */

            // Customer Register -> Ledger reads the customer ledger source.
            // Creating only Accounts Receivable is not enough. Mirror the
            // established Settlement SW behaviour and create one debit ledger
            // row for the credit sale, idempotently.
            $this->ensureCustomerCreditLedger(
                $transactionId,
                $contactId,
                $amount,
                $settlement,
                $marker
            );

            if ($receivableId > 0) {
                $this->ensureLedgerRow([
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'account_id' => $receivableId,
                    'contact_id' => $contactId,
                    'type' => 'debit',
                    'amount' => $amount,
                    'operation_date' => $settlement->transaction_date,
                    'created_by' => (int) ($settlement->created_by ?? auth()->id()),
                    'transaction_id' => $transactionId,
                    'note' => 'Settlement No: ' . $settlement->settlement_no . ' | Credit Sale ' . $marker,
                    'txnType' => 'sw_settlement_credit_sale',
                ], $marker);
            }

            /*
             | No Sales Income fallback is required here. Revenue was already
             | posted by the main SW sale. The credit amount is only the portion
             | of that sale transferred to Accounts Receivable.
            */
        }
    }

    protected function creditSaleLines(object $credit): Collection
    {
        $rows = collect();

        $productId = (int) ($credit->product_id ?? 0);
        $qty = (float) ($credit->quantity ?? 0);
        if ($productId > 0 && $qty > 0) {
            $rows->push((object) [
                'source' => 'credit',
                'source_id' => (int) $credit->id,
                'product_id' => $productId,
                'variation_id' => (int) ($credit->variation_id ?? 0),
                'quantity' => $qty,
                'rate' => (float) ($credit->unit_price ?? $credit->rate ?? 0),
                // unit_discount is ALREADY a per-unit fixed discount.
                'discount_type' => 'fixed_per_unit',
                'discount_value' => (float) ($credit->unit_discount ?? 0),
                'amount' => (float) ($credit->amount ?? 0),
            ]);

            return $rows;
        }

        $dailyId = (int) ($credit->daily_credit_sale_id ?? 0);
        if ($dailyId <= 0 || ! $this->tableExists('sw_daily_credit_sale_lines')) {
            return $rows;
        }

        $lineColumns = $this->columns('sw_daily_credit_sale_lines');
        if (! isset($lineColumns['sw_daily_credit_sale_id'], $lineColumns['product_id'], $lineColumns['quantity'])) {
            return $rows;
        }

        foreach (DB::table('sw_daily_credit_sale_lines')
            ->where('sw_daily_credit_sale_id', $dailyId)
            ->orderBy('id')
            ->get() as $line) {

            $lineQty = (float) ($line->quantity ?? 0);
            if ((int) ($line->product_id ?? 0) <= 0 || $lineQty <= 0) {
                continue;
            }

            $rows->push((object) [
                'source' => 'daily-credit',
                'source_id' => (int) $line->id,
                'product_id' => (int) $line->product_id,
                'variation_id' => (int) ($line->variation_id ?? 0),
                'quantity' => $lineQty,
                'rate' => (float) ($line->unit_price ?? 0),
                'discount_type' => 'fixed_per_unit',
                'discount_value' => (float) ($line->unit_discount ?? 0),
                'amount' => (float) ($line->amount ?? 0),
            ]);
        }

        return $rows;
    }

    /**
     * Collections that represent money/clearing accounts for the main sale.
     * Expense, shortage, excess, loans and drawings are separate accounting
     * operations and may already have been posted by their source pages; they are
     * intentionally not duplicated here.
     */
    protected function postCollections(
        object $settlement,
        int $businessId,
        int $locationId,
        string $shiftNumbers
    ): void {
        if (! $this->tableExists('sw_collections')) {
            return;
        }

        $receiptTypes = ['cash', 'cash_deposit', 'card', 'cheque'];

        foreach (DB::table('sw_collections')
            ->where('settlement_id', $settlement->id)
            ->whereIn('payment_method', $receiptTypes)
            ->orderBy('id')
            ->get() as $collection) {

            $amount = round((float) ($collection->amount ?? 0), 4);
            if ($amount <= 0) {
                continue;
            }

            $method = strtolower(trim((string) $collection->payment_method));
            $accountId = $this->collectionAccountId($collection, $businessId, $method);
            if ($accountId <= 0) {
                throw new \RuntimeException(
                    'SW settlement cannot post ' . $method . ' collection #' . (int) $collection->id .
                    ' because no valid Finance account is configured for the current business.'
                );
            }

            $marker = '[SW-COLLECTION:' . (int) $collection->id . ']';
            $transactionSubType = match ($method) {
                'cash' => 'cash_payment',
                'card' => 'card_payment',
                'cheque' => 'cheque_payment',
                'cash_deposit' => 'cash_deposit',
                default => 'payment',
            };

            $paymentTransactionId = $this->ensureSettlementTransaction(
                $settlement,
                $amount,
                $transactionSubType,
                $marker,
                ! empty($collection->contact_id) ? (int) $collection->contact_id : null,
                $collection->reference ?? null
            );

            $paymentId = $this->ensureTransactionPayment(
                $settlement,
                $paymentTransactionId,
                $businessId,
                $accountId,
                $collection,
                $method,
                $marker,
                $shiftNumbers
            );

            $label = match ($method) {
                'cash' => 'Cash Payment',
                'card' => 'Card Payment',
                'cheque' => 'Cheque Payment',
                'cash_deposit' => 'Cash Deposit',
                default => ucfirst(str_replace('_', ' ', $method)),
            };

            $row = [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'account_id' => $accountId,
                'contact_id' => ! empty($collection->contact_id) ? (int) $collection->contact_id : null,
                'type' => 'debit',
                'amount' => $amount,
                'operation_date' => $settlement->transaction_date,
                'created_by' => (int) ($settlement->created_by ?? auth()->id()),
                'transaction_id' => $paymentTransactionId,
                'transaction_payment_id' => $paymentId ?: null,
                'note' => 'Settlement No: ' . $settlement->settlement_no . ' | ' . $label . ' ' . $marker
                    . (! empty($collection->note) ? ' | ' . $collection->note : ''),
                'cheque_number' => $method === 'cheque' ? ($collection->reference ?? null) : null,
                'sw_shift_no' => $shiftNumbers ?: null,
                'txnType' => 'sw_settlement_payment',
            ];

            // "deposit" is a long-established account_transactions enum value
            // and is used by Finance's bank/cash-deposit presentation logic.
            // Other payment labels are carried by transactions.sub_type so the
            // row remains compatible even on tenants with an older enum list.
            if ($method === 'cash_deposit') {
                $row['sub_type'] = 'deposit';
            }

            $this->ensureLedgerRow($row, $marker);
        }
    }

    /** Ensure every money collection is visible to the normal Finance account book source. */
    protected function assertCollectionsPosted(object $settlement, int $businessId): void
    {
        if (! $this->tableExists('sw_collections')) {
            return;
        }

        foreach (DB::table('sw_collections')
            ->where('settlement_id', $settlement->id)
            ->whereIn('payment_method', ['cash', 'cash_deposit', 'card', 'cheque'])
            ->where('amount', '>', 0)
            ->get() as $collection) {

            $marker = '[SW-COLLECTION:' . (int) $collection->id . ']';
            $posted = DB::table('account_transactions as at')
                ->join('accounts as a', 'a.id', '=', 'at.account_id')
                ->leftJoin('transactions as t', 't.id', '=', 'at.transaction_id')
                ->where('a.business_id', $businessId)
                ->where('at.type', 'debit')
                ->where('at.note', 'like', '%' . $marker . '%')
                ->whereNull('at.deleted_at')
                ->where(function ($q) use ($businessId) {
                    $q->where('at.business_id', $businessId)
                        ->orWhereNull('at.business_id')
                        ->orWhere('at.business_id', 0);
                })
                ->exists();

            if (! $posted) {
                throw new \RuntimeException(
                    'SW settlement payment #' . (int) $collection->id .
                    ' was not written to the Finance account-book ledger. The settlement has been rolled back.'
                );
            }
        }
    }

    /**
     * Non-receipt classifications from the settlement Payments table.
     *
     * These rows form part of Total Paid on the SW settlement: cash retained
     * by the operator may have been used for an expense, shortage, drawing or
     * loan instead of being handed over. Post the corresponding Finance
     * allocation here rather than inventing a second cash movement.
     */
    protected function postSettlementAllocations(
        object $settlement,
        int $transactionId,
        int $businessId,
        int $locationId,
        string $shiftNumbers
    ): void {
        if (! $this->tableExists('sw_collections')) {
            return;
        }

        $methods = [
            'expense', 'shortage', 'excess', 'loan_payment',
            'owners_drawing', 'loan_to_customer',
        ];

        foreach (DB::table('sw_collections')
            ->where('settlement_id', $settlement->id)
            ->whereIn('payment_method', $methods)
            ->orderBy('id')
            ->get() as $collection) {

            $rawAmount = round((float) ($collection->amount ?? 0), 4);
            $method = strtolower(trim((string) $collection->payment_method));

            // IS2249: Excess can be stored as a negative settlement amount so it
            // reduces Total Paid and clears a negative balance. Finance ledger
            // rows keep the established positive amount + credit direction.
            if (($method === 'excess' && abs($rawAmount) < 0.00005)
                || ($method !== 'excess' && $rawAmount <= 0)) {
                continue;
            }

            $amount = $method === 'excess' ? abs($rawAmount) : $rawAmount;
            $accountId = 0;
            $type = 'debit';
            $label = ucfirst(str_replace('_', ' ', $method));

            if ($method === 'expense') {
                $accountId = $this->selectedOrNamedAccount(
                    $collection, $businessId,
                    ['Expenses', 'Expense Account'], ['%Expense%']
                );
                $label = 'Expense';
            } elseif ($method === 'shortage') {
                $accountId = $this->accountIdByNames(
                    $businessId,
                    ['Accounts Receivable', 'Account Receivable', 'Shortage Receivable Account'],
                    ['%Receivable%', '%Shortage%']
                );
                $label = 'Shortage';
            } elseif ($method === 'excess') {
                $accountId = $this->accountIdByNames(
                    $businessId, ['Accounts Payable', 'Account Payable'], ['%Payable%']
                );
                $type = 'credit';
                $label = 'Excess';
            } elseif ($method === 'loan_payment') {
                /*
                 | IS2269: Loan Payment is again an account/bank allocation, not
                 | a customer receipt. The UI now requires the related Bank/loan
                 | account and stores it in sw_collections.account_id.
                 |
                 | Use that exact selected account. The fallback keeps older
                 | saved drafts/settlements postable when they pre-date IS2269.
                */
                $selectedLoanAccount = (int) ($collection->account_id ?? 0);
                if ($selectedLoanAccount > 0
                    && $this->accountBelongsToBusiness($selectedLoanAccount, $businessId)) {
                    $accountId = $selectedLoanAccount;
                } else {
                    $accountId = $this->accountIdFromGroup(
                        $businessId,
                        ['Loans Given', 'Loan', 'Loans']
                    );

                    if ($accountId <= 0) {
                        $accountId = $this->accountIdByNames(
                            $businessId,
                            ['Loans Given', 'Loan Account', 'Loan'],
                            ['%Loan%']
                        );
                    }
                }

                // Paying a loan from settlement funds is an allocation/outflow;
                // keep the normal allocation debit direction used by the legacy
                // settlement Loan Payments tab.
                $type = 'debit';
                $label = 'Loan Payment';
            } elseif ($method === 'owners_drawing') {
                $accountId = $this->selectedOrNamedAccount(
                    $collection, $businessId,
                    ['Owner Drawings', 'Owner Drawing', 'Drawings Account'],
                    ['%Drawing%', '%Owner%']
                );
                $label = 'Owner Drawing';
            } elseif ($method === 'loan_to_customer') {
                $accountId = $this->accountIdByNames(
                    $businessId, ['Accounts Receivable', 'Account Receivable'], ['%Receivable%']
                );
                $label = 'Loan to Customer';
            }

            if ($accountId <= 0) {
                throw new \RuntimeException(
                    'SW settlement allocation ' . $method . ' #' . (int) $collection->id .
                    ' cannot be posted because its Finance account is not configured for business #' . $businessId . '.'
                );
            }

            $marker = '[SW-ALLOCATION:' . (int) $collection->id . ']';
            $this->ensureLedgerRow([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'account_id' => $accountId,
                'contact_id' => ! empty($collection->contact_id) ? (int) $collection->contact_id : null,
                'type' => $type,
                'amount' => $amount,
                'operation_date' => $settlement->transaction_date,
                'created_by' => (int) ($settlement->created_by ?? auth()->id()),
                'transaction_id' => $transactionId,
                'note' => 'Settlement No: ' . $settlement->settlement_no . ' | ' . $label . ' ' . $marker
                    . (! empty($collection->note) ? ' | ' . $collection->note : ''),
                'sw_shift_no' => $shiftNumbers ?: null,
                'txnType' => 'sw_settlement_allocation',
            ], $marker);
        }
    }

    /**
     * Create the established core settlement transaction shape used by Finance
     * account books (cash_payment, card_payment, cheque_payment, cash_deposit).
     */
    protected function ensureSettlementTransaction(
        object $settlement,
        float $amount,
        string $subType,
        string $marker,
        ?int $contactId = null,
        ?string $reference = null
    ): int {
        $businessId = (int) $settlement->business_id;
        $settlementNo = (string) $settlement->settlement_no;
        $columns = $this->columns('transactions');

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'settlement')
            ->where('sub_type', $subType)
            ->where('invoice_no', $settlementNo);

        if (isset($columns['transaction_note'])) {
            $query->where('transaction_note', 'like', '%' . $marker . '%');
        } elseif ($reference && isset($columns['ref_no'])) {
            $query->where('ref_no', $reference);
        } else {
            $query->whereBetween('final_total', [$amount - 0.00005, $amount + 0.00005]);
        }

        $existing = $query->orderBy('id')->first();
        if ($existing) {
            $update = $this->compatibleRow('transactions', [
                'location_id' => (int) $settlement->location_id,
                'transaction_date' => $settlement->transaction_date,
                'status' => 'final',
                'payment_status' => 'paid',
                'total_before_tax' => $amount,
                'final_total' => $amount,
                'updated_at' => now(),
            ]);
            if ($update) {
                DB::table('transactions')->where('id', $existing->id)->update($update);
            }
            return (int) $existing->id;
        }

        $row = [
            'business_id' => $businessId,
            'location_id' => (int) $settlement->location_id,
            'type' => 'settlement',
            'sub_type' => $subType,
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => $contactId,
            'pump_operator_id' => (int) ($settlement->pump_operator_id ?? 0) ?: null,
            'transaction_date' => $settlement->transaction_date,
            'total_before_tax' => $amount,
            'final_total' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'invoice_no' => $settlementNo,
            'ref_no' => $reference ?: $settlementNo,
            'is_settlement' => 1,
            'is_credit_sale' => 0,
            'created_by' => (int) ($settlement->created_by ?? auth()->id()),
            'transaction_note' => 'SW Settlement ' . $settlementNo . ' ' . $marker,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('transactions')->insertGetId($this->compatibleRow('transactions', $row));
    }

    /** Create the customer-ledger side of an SW credit sale exactly once. */
    protected function ensureCustomerCreditLedger(
        int $transactionId,
        int $contactId,
        float $amount,
        object $settlement,
        string $marker
    ): void {
        if ($transactionId <= 0 || $contactId <= 0 || $amount <= 0) {
            return;
        }
        if (! $this->tableExists('contact_ledgers')) {
            throw new \RuntimeException(
                'SW credit sale cannot be posted because contact_ledgers is unavailable in the current tenant database.'
            );
        }

        $columns = $this->columns('contact_ledgers');
        $query = DB::table('contact_ledgers')
            ->where('contact_id', $contactId)
            ->where('transaction_id', $transactionId)
            ->where('type', 'debit');
        if (isset($columns['deleted_at'])) {
            $query->whereNull('deleted_at');
        }
        if ($query->exists()) {
            return;
        }

        $ledger = [
            'business_id' => (int) $settlement->business_id,
            'contact_id' => $contactId,
            'amount' => $amount,
            'type' => 'debit',
            'sub_type' => 'sell',
            'operation_date' => $settlement->transaction_date,
            'created_by' => (int) ($settlement->created_by ?? auth()->id()),
            'transaction_id' => $transactionId,
            'note' => 'Settlement No ' . $settlement->settlement_no . ' | SW Credit Sale ' . $marker,
        ];

        try {
            ContactLedger::createContactLedger($ledger, 'Customer Settlement');
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'SW credit sale was created but its Customer Ledger entry could not be created: ' . $e->getMessage(),
                0,
                $e
            );
        }

        // IS2217: do not allow a settlement to report success if the customer
        // ledger write was swallowed by model/schema differences. The Customer
        // Register ledger reads this standard table directly.
        $verify = DB::table('contact_ledgers')
            ->where('contact_id', $contactId)
            ->where('transaction_id', $transactionId)
            ->where('type', 'debit');
        if ($this->hasColumn('contact_ledgers', 'business_id')) {
            $verify->where('business_id', (int) $settlement->business_id);
        }
        if ($this->hasColumn('contact_ledgers', 'deleted_at')) {
            $verify->whereNull('deleted_at');
        }
        if (! $verify->exists()) {
            throw new \RuntimeException(
                'SW credit sale customer-ledger verification failed. The settlement has been rolled back.'
            );
        }
    }

    /**
     * Make silent partial Finance postings impossible. Sales Income must exist
     * for every classified sell line; stock-enabled lines with a non-zero cost
     * must also produce COGS and Finished Goods movements.
     */
    protected function assertProductAccountingPosted(Transaction $transaction, int $businessId): void
    {
        foreach ($transaction->sell_lines as $line) {
            $lineId = (int) $line->id;
            if ($lineId <= 0) {
                continue;
            }

            $product = $this->tableExists('products')
                ? DB::table('products')->where('id', $line->product_id)->first()
                : null;
            $variation = $this->tableExists('variations')
                ? DB::table('variations')->where('id', $line->variation_id)->first()
                : null;

            $stockAccountId = (int) ($product->stock_type ?? 0);
            $creditAccountIds = DB::table('account_transactions')
                ->where('transaction_id', $transaction->id)
                ->where('sell_line_id', $lineId)
                ->where('type', 'credit')
                ->pluck('account_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            // Sales Income must be a credit distinct from the stock account.
            // This distinguishes a real income posting from a lone Finished
            // Goods credit, which was one of the silent partial-posting cases.
            $hasSalesIncome = $creditAccountIds->contains(
                fn ($accountId) => $accountId > 0 && ($stockAccountId <= 0 || $accountId !== $stockAccountId)
            );
            if (! $hasSalesIncome) {
                throw new \RuntimeException(
                    'SW settlement Finance posting did not create Sales Income for sell line #' . $lineId .
                    '. Check Sales Income account mappings for business #' . $businessId . '.'
                );
            }

            $stockEnabled = (int) ($product->enable_stock ?? 0) === 1;
            $cost = (float) ($variation->dpp_inc_tax ?? $variation->default_purchase_price ?? 0);
            if (! $stockEnabled || $cost <= 0.0000001) {
                continue;
            }

            if ($stockAccountId <= 0) {
                throw new \RuntimeException(
                    'SW stock product #' . (int) $line->product_id .
                    ' has no Finished Goods / stock account mapping for business #' . $businessId . '.'
                );
            }

            $hasFinishedGoods = $creditAccountIds->contains($stockAccountId);
            if (! $hasFinishedGoods) {
                throw new \RuntimeException(
                    'SW settlement Finance posting did not create Finished Goods for stock sell line #' . $lineId . '.'
                );
            }

            $hasCogs = DB::table('account_transactions')
                ->where('transaction_id', $transaction->id)
                ->where('sell_line_id', $lineId)
                ->where('type', 'debit')
                ->exists();
            if (! $hasCogs) {
                throw new \RuntimeException(
                    'SW settlement Finance posting did not create COGS for stock sell line #' . $lineId .
                    '. Check COGS account mappings for business #' . $businessId . '.'
                );
            }
        }
    }

    protected function selectedOrNamedAccount(
        object $collection,
        int $businessId,
        array $exactNames,
        array $likes = []
    ): int {
        $selected = (int) ($collection->account_id ?? 0);
        if ($selected > 0 && $this->accountBelongsToBusiness($selected, $businessId)) {
            return $selected;
        }

        return $this->accountIdByNames($businessId, $exactNames, $likes);
    }

    protected function ensureTransactionPayment(
        object $settlement,
        int $transactionId,
        int $businessId,
        int $accountId,
        object $collection,
        string $method,
        string $marker,
        string $shiftNumbers
    ): ?int {
        if (! $this->tableExists('transaction_payments')) {
            return null;
        }

        $columns = $this->columns('transaction_payments');
        $query = DB::table('transaction_payments')
            ->where('transaction_id', $transactionId)
            ->where('business_id', $businessId);

        if (isset($columns['note'])) {
            $query->where('note', 'like', '%' . $marker . '%');
        } else {
            $query->where('account_id', $accountId)
                ->whereBetween('amount', [((float) $collection->amount) - 0.00005, ((float) $collection->amount) + 0.00005]);
            if (! empty($collection->reference) && isset($columns['payment_ref_no'])) {
                $query->where('payment_ref_no', (string) $collection->reference);
            }
        }

        $existing = $query->orderBy('id')->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $standardMethod = $method;
        $row = [
            'transaction_id' => $transactionId,
            'business_id' => $businessId,
            'amount' => (float) $collection->amount,
            'method' => $standardMethod,
            'paid_on' => $settlement->transaction_date,
            'created_by' => (int) ($settlement->created_by ?? auth()->id()),
            'payment_ref_no' => $collection->reference ?? null,
            'paid_in_type' => 'settlement',
            'account_id' => $accountId,
            'payment_for' => ! empty($collection->contact_id) ? (int) $collection->contact_id : null,
            'cheque_number' => $method === 'cheque' ? ($collection->reference ?? null) : null,
            'note' => 'SW Settlement ' . $settlement->settlement_no . ' ' . $marker
                . (! empty($collection->note) ? ' | ' . $collection->note : ''),
            'sw_shift_no' => $shiftNumbers ?: null,
            'shift_number' => $shiftNumbers ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('transaction_payments')->insertGetId(
            $this->compatibleRow('transaction_payments', $row)
        );
    }

    protected function collectionAccountId(object $collection, int $businessId, string $method): int
    {
        $selected = (int) ($collection->account_id ?? 0);
        if ($selected > 0 && $this->accountBelongsToBusiness($selected, $businessId)) {
            return $selected;
        }

        return match ($method) {
            'cash' => $this->accountIdByNames($businessId, ['Cash', 'Cash Account'], ['%Cash%']),
            'cheque' => $this->accountIdByNames($businessId, ['Cheques in Hand', 'Cheque in Hand'], ['%Cheque%Hand%']),
            'card' => $this->accountIdByNames($businessId, ['Cards (Credit Debit) Account', 'Card Account', 'Cards'], ['%Card%']),
            'cash_deposit' => $this->accountIdFromGroup($businessId, ['Bank Account', 'Bank'])
                ?: $this->accountIdByNames($businessId, ['Bank Account'], ['%Bank%']),
            default => 0,
        };
    }

    protected function accountBelongsToBusiness(int $accountId, int $businessId): bool
    {
        if ($accountId <= 0 || ! $this->tableExists('accounts')) {
            return false;
        }

        $query = DB::table('accounts')->where('id', $accountId)->where('business_id', $businessId);
        if ($this->hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if ($this->hasColumn('accounts', 'is_closed')) {
            $query->where('is_closed', 0);
        }

        return $query->exists();
    }

    protected function accountIdByNames(int $businessId, array $exactNames, array $likes = []): int
    {
        if (! $this->tableExists('accounts')) {
            return 0;
        }

        $base = DB::table('accounts')->where('business_id', $businessId);
        if ($this->hasColumn('accounts', 'deleted_at')) {
            $base->whereNull('deleted_at');
        }
        if ($this->hasColumn('accounts', 'is_closed')) {
            $base->where('is_closed', 0);
        }

        foreach ($exactNames as $name) {
            $id = (clone $base)->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($name))])->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        foreach ($likes as $like) {
            $id = (clone $base)->where('name', 'like', $like)->orderBy('id')->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        return 0;
    }

    protected function accountIdFromGroup(int $businessId, array $groupNames): int
    {
        if (! $this->tableExists('accounts') || ! $this->tableExists('account_groups') || ! $this->hasColumn('accounts', 'asset_type')) {
            return 0;
        }

        $query = DB::table('accounts as a')
            ->join('account_groups as g', 'g.id', '=', 'a.asset_type')
            ->where('a.business_id', $businessId);

        if ($this->hasColumn('account_groups', 'business_id')) {
            $query->where('g.business_id', $businessId);
        }
        if ($this->hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('a.deleted_at');
        }
        if ($this->hasColumn('accounts', 'is_closed')) {
            $query->where('a.is_closed', 0);
        }

        foreach ($groupNames as $group) {
            $id = (clone $query)
                ->where(function ($q) use ($group) {
                    $q->whereRaw('LOWER(TRIM(g.name)) = ?', [strtolower(trim($group))])
                        ->orWhere('g.name', 'like', '%' . $group . '%');
                })
                ->orderBy('a.id')
                ->value('a.id');
            if ($id) {
                return (int) $id;
            }
        }

        return 0;
    }

    protected function ensureLedgerRow(array $row, string $marker): void
    {
        if (! $this->tableExists('account_transactions')) {
            return;
        }

        $columns = $this->columns('account_transactions');
        $query = DB::table('account_transactions')
            ->where('account_id', (int) ($row['account_id'] ?? 0))
            ->where('type', (string) ($row['type'] ?? ''))
            ->where('transaction_id', (int) ($row['transaction_id'] ?? 0));

        if (isset($columns['note'])) {
            $query->where('note', 'like', '%' . $marker . '%');
        } else {
            $query->whereBetween('amount', [((float) ($row['amount'] ?? 0)) - 0.00005, ((float) ($row['amount'] ?? 0)) + 0.00005]);
            if (! empty($row['transaction_payment_id'])) {
                $query->where('transaction_payment_id', (int) $row['transaction_payment_id']);
            }
        }

        if ($query->exists()) {
            return;
        }

        $row['created_at'] = $row['created_at'] ?? now();
        $row['updated_at'] = $row['updated_at'] ?? now();
        DB::table('account_transactions')->insert($this->compatibleRow('account_transactions', $row));
    }

    protected function productForPump(int $pumpId): int
    {
        if ($pumpId <= 0 || ! $this->tableExists('pumps')) {
            return 0;
        }

        if ($this->hasColumn('pumps', 'product_id')) {
            $id = DB::table('pumps')->where('id', $pumpId)->value('product_id');
            if ($id) {
                return (int) $id;
            }
        }

        if ($this->hasColumn('pumps', 'fuel_tank_id') && $this->tableExists('fuel_tanks')) {
            $tankId = (int) (DB::table('pumps')->where('id', $pumpId)->value('fuel_tank_id') ?? 0);
            if ($tankId > 0 && $this->hasColumn('fuel_tanks', 'product_id')) {
                return (int) (DB::table('fuel_tanks')->where('id', $tankId)->value('product_id') ?? 0);
            }
        }

        return 0;
    }

    protected function validVariation(int $productId, int $candidate, int $locationId): int
    {
        if ($productId <= 0 || ! $this->tableExists('variations')) {
            return 0;
        }

        if ($candidate > 0 && DB::table('variations')->where('id', $candidate)->where('product_id', $productId)->exists()) {
            return $candidate;
        }

        // Prefer a variation available at this location where that table exists.
        if ($locationId > 0 && $this->tableExists('variation_location_details')) {
            $id = DB::table('variations as v')
                ->join('variation_location_details as vld', 'vld.variation_id', '=', 'v.id')
                ->where('v.product_id', $productId)
                ->where('vld.location_id', $locationId)
                ->orderBy('v.id')
                ->value('v.id');
            if ($id) {
                return (int) $id;
            }
        }

        return (int) (DB::table('variations')->where('product_id', $productId)->orderBy('id')->value('id') ?? 0);
    }

    protected function shiftNumbers(int $settlementId): string
    {
        if (! $this->tableExists('sw_settlement_shifts') || ! $this->tableExists('sw_shifts')) {
            return '';
        }

        return DB::table('sw_settlement_shifts as ss')
            ->join('sw_shifts as s', 's.id', '=', 'ss.sw_shift_id')
            ->where('ss.settlement_id', $settlementId)
            ->orderBy('s.id')
            ->pluck('s.sw_shift_no')
            ->filter()
            ->implode(', ');
    }

    protected function settlementMarker(int $settlementId): string
    {
        return '[SW-SETTLEMENT:' . $settlementId . ']';
    }

    /**
     * Live-connection table check for multi-tenant runtime. Avoid Schema facade
     * metadata because it can be attached to a previously active tenant.
     */
    protected function tableExists(string $table): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            $row = DB::selectOne(
                'SELECT 1 AS present FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$table]
            );
            return $row !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT 1 FROM `' . $table . '` LIMIT 0');
                return true;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return isset($this->columns($table)[$column]);
    }

    /** @return array<string,bool> */
    protected function columns(string $table): array
    {
        static $cache = [];

        $db = null;
        try {
            $db = (string) (DB::selectOne('SELECT DATABASE() AS db_name')->db_name ?? '');
        } catch (\Throwable $e) {
            $db = '';
        }
        $key = $db . ':' . $table;

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return $cache[$key] = [];
        }

        try {
            $rows = DB::select('SHOW COLUMNS FROM `' . $table . '`');
            $columns = [];
            foreach ($rows as $row) {
                $name = $row->Field ?? $row->field ?? null;
                if ($name) {
                    $columns[(string) $name] = true;
                }
            }
            return $cache[$key] = $columns;
        } catch (\Throwable $e) {
            return $cache[$key] = [];
        }
    }

    protected function compatibleRow(string $table, array $row): array
    {
        $columns = $this->columns($table);
        if (! $columns) {
            return $row;
        }

        return array_intersect_key($row, $columns);
    }
}
