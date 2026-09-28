<?php

namespace Modules\Poultry\Services;

use Illuminate\Support\Facades\DB;
use Modules\Poultry\Entities\Shared\VariationLocationDetail;
use Modules\Poultry\Support\BusinessContext;

/**
 * THE SEAM.
 *
 * This class and LedgerGateway are the only two files in Modules/Poultry that
 * know the core application exists. Everything else in the module talks to
 * these, so if core is ever refactored, two files change instead of fifty.
 *
 * WHY A GATEWAY RATHER THAN DIRECT WRITES
 *   Stock quantity in this ERP is not a column you may set. variation_location_details
 *   holds the position, but the arithmetic - and its interaction with purchase
 *   lines, sell lines, opening stock and stock adjustments - lives in the core
 *   product util. Writing qty_available by hand is precisely how stock drifts
 *   out of agreement with the transaction history, and the drift is invisible
 *   until someone reconciles.
 *
 *   So we resolve the core util from the container by string. If it is absent -
 *   a tenant without the Product module, or a pilot install - we degrade to
 *   recording against poultry tables only, rather than crashing.
 *
 * CONFIG
 *   poultry.post_to_stock = false disables all shared stock writes. The module
 *   then keeps its own consumption and production history and nothing else.
 */
class StockGateway
{
    /** Fully qualified name of the core stock util, resolved at runtime only. */
    protected const CORE_PRODUCT_UTIL = 'App\Utils\ProductUtil';

    /** True when shared stock posting is switched on and core is present. */
    public function isAvailable()
    {
        return (bool) config('poultry.post_to_stock', true) && class_exists(self::CORE_PRODUCT_UTIL);
    }

    protected function util()
    {
        return app(self::CORE_PRODUCT_UTIL);
    }

    /**
     * Current available quantity for a variation at a location. Safe to call
     * whether or not core is present - reads never need the util.
     */
    public function availableQty($variationId, $locationId)
    {
        return VariationLocationDetail::availableQty($variationId, $locationId);
    }

    /**
     * Issue stock out - feed, vaccines, medication consumed by a batch.
     *
     * Returns the transactions.id written, or null when posting is disabled.
     * Callers store that id so a later correction can reverse this movement
     * rather than editing stock in place.
     *
     * @throws \RuntimeException when stock is insufficient and overdraw is off
     */
    public function issue(array $payload)
    {
        $this->validatePayload($payload);

        if (! $this->isAvailable()) {
            return null;
        }

        $available = $this->availableQty($payload['variation_id'], $payload['location_id']);

        if ($available < $payload['qty'] && ! ($payload['allow_overdraw'] ?? false)) {
            throw new \RuntimeException(
                'Insufficient stock: '.$available.' available, '.$payload['qty'].' requested.'
            );
        }

        return DB::transaction(function () use ($payload) {
            $transactionId = $this->writeTransaction($payload, config('poultry.transaction_types.feed_issue'), -1);

            $this->util()->decreaseProductQuantity(
                $payload['product_id'],
                $payload['variation_id'],
                $payload['location_id'],
                $payload['qty']
            );

            return $transactionId;
        });
    }

    /**
     * Bring stock in - eggs collected, birds harvested, chicks hatched.
     * Returns the transactions.id written, or null when posting is disabled.
     */
    public function produce(array $payload)
    {
        $this->validatePayload($payload);

        if (! $this->isAvailable()) {
            return null;
        }

        return DB::transaction(function () use ($payload) {
            $type = $payload['transaction_type'] ?? config('poultry.transaction_types.production');

            $transactionId = $this->writeTransaction($payload, $type, 1);

            $this->util()->updateProductQuantity(
                $payload['location_id'],
                $payload['product_id'],
                $payload['variation_id'],
                $payload['qty'],
                0
            );

            return $transactionId;
        });
    }

    /**
     * Reverse a movement previously posted by this gateway. Used when a daily
     * record or collection is corrected - we never edit the original.
     */
    public function reverse($transactionId, array $payload)
    {
        if (! $this->isAvailable() || empty($transactionId)) {
            return null;
        }

        $original = DB::table(config('poultry.shared_tables.transactions', 'transactions'))
            ->where('id', $transactionId)
            ->first();

        if (! $original) {
            return null;
        }

        // An issue is reversed by producing back, and vice versa.
        return $payload['direction'] === 'issue'
            ? $this->produce($payload)
            : $this->issue(array_merge($payload, ['allow_overdraw' => true]));
    }

    /**
     * Write the header row into the shared transactions table.
     *
     * transactions.type is a plain indexed string in this schema, so the
     * poultry_* types need no core migration. Verified against Finance,
     * FinanceReports, ManagementReport, StockReports and app/Utils: all filter
     * transaction type by explicit whitelist, never by negative match, so
     * these rows cannot leak into existing report totals.
     */
    protected function writeTransaction(array $payload, $type, $sign)
    {
        $now = now();

        return DB::table(config('poultry.shared_tables.transactions', 'transactions'))->insertGetId([
            'business_id'     => $payload['business_id'] ?? BusinessContext::id(),
            'location_id'     => $payload['location_id'],
            'type'            => $type,
            'status'          => 'final',
            'payment_status'  => 'paid',
            'transaction_date' => $payload['date'] ?? $now->toDateTimeString(),
            'total_before_tax' => abs($payload['total_cost'] ?? 0),
            'final_total'     => abs($payload['total_cost'] ?? 0) * $sign,
            'additional_notes' => $payload['notes'] ?? null,
            'created_by'      => BusinessContext::userId(),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }

    protected function validatePayload(array $payload)
    {
        foreach (['product_id', 'variation_id', 'location_id', 'qty'] as $key) {
            if (! array_key_exists($key, $payload) || $payload[$key] === null) {
                throw new \InvalidArgumentException('StockGateway payload missing: '.$key);
            }
        }

        if ($payload['qty'] <= 0) {
            throw new \InvalidArgumentException('StockGateway quantity must be greater than zero.');
        }
    }
}
