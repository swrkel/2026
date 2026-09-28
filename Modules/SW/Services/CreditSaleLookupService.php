<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the Credit Sales form needs — 8047.
 *
 * Customers, their vehicle numbers, products, and the credit sales already
 * recorded against the shifts being settled.
 */
class CreditSaleLookupService
{
    /** Customers for the dropdown. */
    public function customers(int $businessId): array
    {
        return DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->when(Schema::hasColumn('contacts', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'supplier_business_name', 'contact_id as code'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'text' => trim(($c->code ? $c->code . '  ·  ' : '') . $c->name),
            ])
            ->all();
    }

    /**
     * A customer's vehicle numbers.
     *
     * From Customer References, as 8047 requires - `reference` is the vehicle
     * number, scoped to the contact.
     */
    public function vehicles(int $businessId, int $contactId): array
    {
        if (! Schema::hasTable('customer_references') || $contactId <= 0) {
            return [];
        }

        return DB::table('customer_references')
            ->where('business_id', $businessId)
            ->where('contact_id', $contactId)
            ->whereNotNull('reference')
            ->where('reference', '!=', '')
            ->orderBy('reference')
            ->pluck('reference')
            ->unique()
            ->values()
            ->all();
    }

    /** Products that may be sold on credit. */
    public function products(int $businessId): array
    {
        return DB::table('products')
            ->join('variations', 'variations.product_id', '=', 'products.id')
            ->where('products.business_id', $businessId)
            ->when(Schema::hasColumn('products', 'deleted_at'),
                fn ($q) => $q->whereNull('products.deleted_at'))
            ->orderBy('products.name')
            ->limit(500)
            ->get([
                'products.id as product_id',
                'products.name',
                'products.sku',
                'variations.id as variation_id',
                'variations.sell_price_inc_tax as unit_price',
            ])
            ->all();
    }

    /**
     * Credit sales already recorded against these shifts.
     *
     * 8047: autoload what was entered on the Daily Credit Sales tab, for the
     * selected operator and shift numbers.
     *
     * Brought in as editable lines, not as a read-only total: 8047 gives the
     * user Edit and Delete over them.
     */
    public function fromDailyTab(int $businessId, array $shiftIds, int $operatorId): array
    {
        if (empty($shiftIds) || ! Schema::hasTable('sw_daily_credit_sales')) {
            return [];
        }

        $base = DB::table('sw_daily_credit_sales as d')
            ->leftJoin('contacts as c', 'c.id', '=', 'd.contact_id')
            ->whereIn('d.sw_shift_id', $shiftIds)
            ->when($operatorId > 0, fn ($q) => $q->where('d.pump_operator_id', $operatorId));

        /*
         | IS2232: carry the actual product lines into Settlement.
         |
         | The previous implementation returned one aggregate row per Daily
         | Credit Sale. That gave the settlement customer/order/amount but left
         | Product, Qty, Unit Price and Unit Discount blank. When the line table
         | exists, return one row per product line instead. The header-only path
         | remains as a compatibility fallback for older databases/data.
        */
        if (Schema::hasTable('sw_daily_credit_sale_lines')) {
            $lineColumns = Schema::getColumnListing('sw_daily_credit_sale_lines');
            $required = ['sw_daily_credit_sale_id', 'product_id', 'quantity', 'unit_price', 'unit_discount', 'amount_before_discount', 'amount'];

            if (empty(array_diff($required, $lineColumns))) {
                $query = clone $base;
                $query->join('sw_daily_credit_sale_lines as l', 'l.sw_daily_credit_sale_id', '=', 'd.id');

                $hasProducts = Schema::hasTable('products');
                if ($hasProducts) {
                    $query->leftJoin('products as p', 'p.id', '=', 'l.product_id');
                }

                $select = [
                    'd.id as daily_credit_sale_id',
                    'd.contact_id',
                    'd.order_no',
                    'd.order_date',
                    'd.vehicle_no',
                    'd.note',
                    'c.name as customer_name',
                    'l.product_id',
                    'l.quantity',
                    'l.unit_price',
                    'l.unit_discount',
                    'l.amount_before_discount',
                    'l.amount',
                ];

                // Older SW line tables may not have variation_id yet.
                // Do not make the whole Daily Credit Sales lookup fail for that
                // optional field; Settlement can still save the product line.
                $select[] = in_array('variation_id', $lineColumns, true)
                    ? 'l.variation_id'
                    : DB::raw('NULL as variation_id');

                if ($hasProducts) {
                    $select[] = 'p.name as product_base_name';
                    $select[] = 'p.sku as product_sku';
                }

                $lines = $query->orderBy('d.id')->orderBy('l.id')->get($select);

                if ($lines->isNotEmpty()) {
                    return $lines->map(function ($r) {
                        $name = (string) ($r->product_base_name ?? '');
                        $sku = (string) ($r->product_sku ?? '');
                        $productName = trim(($sku !== '' ? $sku . '  ·  ' : '') . $name);
                        $before = (float) ($r->amount_before_discount ?? 0);
                        $after = (float) ($r->amount ?? 0);

                        return [
                            'daily_credit_sale_id' => (int) $r->daily_credit_sale_id,
                            'contact_id' => (int) $r->contact_id,
                            'customer_name' => (string) ($r->customer_name ?? ''),
                            'order_no' => (string) ($r->order_no ?? ''),
                            'order_date' => $r->order_date,
                            'vehicle_no' => (string) ($r->vehicle_no ?? ''),
                            'product_id' => (int) ($r->product_id ?? 0),
                            'variation_id' => ! empty($r->variation_id) ? (int) $r->variation_id : null,
                            'product_name' => $productName,
                            'quantity' => (float) ($r->quantity ?? 0),
                            'unit_price' => (float) ($r->unit_price ?? 0),
                            'unit_discount' => (float) ($r->unit_discount ?? 0),
                            'amount_before_discount' => $before,
                            'credit_discount_amount' => round(max(0, $before - $after), 4),
                            'amount' => $after,
                            'note' => (string) ($r->note ?? ''),
                        ];
                    })->all();
                }
            }
        }

        return $base
            ->orderBy('d.id')
            ->get([
                'd.id as daily_credit_sale_id',
                'd.contact_id',
                'd.order_no',
                'd.order_date',
                'd.vehicle_no',
                'd.amount',
                'd.total_before_discount',
                'd.total_discount',
                'd.note',
                'c.name as customer_name',
            ])
            ->map(function ($r) {
                return [
                    'daily_credit_sale_id' => (int) $r->daily_credit_sale_id,
                    'contact_id' => (int) $r->contact_id,
                    'customer_name' => (string) ($r->customer_name ?? ''),
                    'order_no' => (string) ($r->order_no ?? ''),
                    'order_date' => $r->order_date,
                    'vehicle_no' => (string) ($r->vehicle_no ?? ''),
                    'product_id' => null,
                    'variation_id' => null,
                    'product_name' => '',
                    'quantity' => 0,
                    'unit_price' => 0,
                    'unit_discount' => 0,
                    'amount_before_discount' => (float) ($r->total_before_discount ?? $r->amount ?? 0),
                    'credit_discount_amount' => (float) ($r->total_discount ?? 0),
                    'amount' => (float) ($r->amount ?? 0),
                    'note' => (string) ($r->note ?? ''),
                ];
            })
            ->all();
    }

    /**
     * Add a vehicle number to a customer.
     *
     * 8047: "User to add a new vehicle Number if not in the dropdown list."
     * Written to Customer References so it is there next time, on this screen
     * and everywhere else that reads them.
     */
    public function addVehicle(int $businessId, int $contactId, string $vehicle): bool
    {
        $vehicle = trim($vehicle);

        if ($vehicle === '' || $contactId <= 0 || ! Schema::hasTable('customer_references')) {
            return false;
        }

        $exists = DB::table('customer_references')
            ->where('business_id', $businessId)
            ->where('contact_id', $contactId)
            ->where('reference', $vehicle)
            ->exists();

        if ($exists) {
            return true;
        }

        DB::table('customer_references')->insert([
            'business_id' => $businessId,
            'contact_id' => $contactId,
            'reference' => $vehicle,
            'date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }
}
