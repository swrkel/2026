<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;

class PurchaseEntryShowService
{
    public function __construct(protected PurchaseDateNumberUtil $numbers)
    {
    }

    /** @return array<string, mixed> */
    public function pageData(int $id): array
    {
        $purchase = $this->purchase($id);
        $lines = $this->lines($id);
        $payments = $this->payments($id);
        $paidTotal = (float) $payments->sum(fn ($payment): float => (float) ($payment->amount ?? 0));
        $finalTotal = (float) ($purchase->final_total ?? 0);

        return [
            'purchase' => $purchase,
            'lines' => $lines,
            'payments' => $payments,
            'paid_total' => round($paidTotal, 6),
            'due_total' => round(max(0, $finalTotal - $paidTotal), 6),
            'line_tax_total' => round((float) $lines->sum(fn ($line): float => (float) ($line->line_tax ?? 0)), 6),
            'currency_precision' => max(0, min(6, (int) (session('business.currency_precision') ?? 2))),
            'quantity_precision' => max(0, min(6, (int) (session('business.quantity_precision') ?? 3))),
            'currency_symbol' => (string) (session('currency.symbol') ?: session('business.currency_symbol') ?: ''),
        ];
    }

    public function find(int $id): object
    {
        return $this->purchase($id);
    }

    protected function purchase(int $id): object
    {
        abort_unless(Schema::hasTable('transactions'), 404, 'Purchase transaction table is not available.');

        $query = DB::table('transactions as t')
            ->where('t.business_id', $this->numbers->businessId())
            ->where('t.type', 'purchase')
            ->where('t.id', $id);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }

        $select = ['t.*'];
        if (Schema::hasTable('contacts')) {
            $query->leftJoin('contacts as c', 'c.id', '=', 't.contact_id');
            $select[] = $this->columnOrEmpty('contacts', 'name', 'c', 'contact_name');
            $select[] = $this->columnOrEmpty('contacts', 'supplier_business_name', 'c', 'supplier_business_name');
            $select[] = $this->columnOrEmpty('contacts', 'contact_id', 'c', 'supplier_code');
            $select[] = $this->columnOrEmpty('contacts', 'mobile', 'c', 'supplier_mobile');
            $select[] = $this->columnOrEmpty('contacts', 'email', 'c', 'supplier_email');
            $select[] = $this->columnOrEmpty('contacts', 'tax_number', 'c', 'supplier_tax_number');
        } else {
            foreach (['contact_name', 'supplier_business_name', 'supplier_code', 'supplier_mobile', 'supplier_email', 'supplier_tax_number'] as $alias) {
                $select[] = DB::raw("'' as {$alias}");
            }
        }

        if (Schema::hasTable('business_locations')) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id');
            $select[] = $this->columnOrEmpty('business_locations', 'name', 'bl', 'location_name');
        } else {
            $select[] = DB::raw("'' as location_name");
        }

        if (Schema::hasTable('stores') && Schema::hasColumn('transactions', 'store_id')) {
            $query->leftJoin('stores as st', 'st.id', '=', 't.store_id');
            $select[] = $this->columnOrEmpty('stores', 'name', 'st', 'store_name');
        } else {
            $select[] = DB::raw("'' as store_name");
        }

        $purchase = $query->first($select);
        abort_if(! $purchase, 404, 'Purchase entry not found.');

        $purchase->supplier_name = trim((string) ($purchase->supplier_business_name ?: $purchase->contact_name ?: ''));

        return $purchase;
    }

    protected function lines(int $transactionId): Collection
    {
        if (! Schema::hasTable('purchase_lines')) {
            return collect();
        }

        $query = DB::table('purchase_lines as pl')->where('pl.transaction_id', $transactionId);
        if (Schema::hasColumn('purchase_lines', 'deleted_at')) {
            $query->whereNull('pl.deleted_at');
        }

        $select = ['pl.*'];
        if (Schema::hasTable('products')) {
            $query->leftJoin('products as p', 'p.id', '=', 'pl.product_id');
            $select[] = $this->columnOrEmpty('products', 'name', 'p', 'product_name');
            $select[] = $this->columnOrEmpty('products', 'sku', 'p', 'product_sku');
            $select[] = Schema::hasColumn('products', 'enable_stock')
                ? 'p.enable_stock as enable_stock'
                : DB::raw('1 as enable_stock');
            if (Schema::hasTable('units') && Schema::hasColumn('products', 'unit_id')) {
                $query->leftJoin('units as bu', 'bu.id', '=', 'p.unit_id');
                $select[] = $this->columnOrEmpty('units', 'short_name', 'bu', 'base_unit_name');
                $select[] = $this->columnOrEmpty('units', 'actual_name', 'bu', 'base_unit_actual_name');
            } else {
                $select[] = DB::raw("'' as base_unit_name");
                $select[] = DB::raw("'' as base_unit_actual_name");
            }
        } else {
            $select[] = DB::raw("'' as product_name");
            $select[] = DB::raw("'' as product_sku");
            $select[] = DB::raw('1 as enable_stock');
            $select[] = DB::raw("'' as base_unit_name");
            $select[] = DB::raw("'' as base_unit_actual_name");
        }

        if (Schema::hasTable('variations')) {
            $query->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id');
            $select[] = $this->columnOrEmpty('variations', 'name', 'v', 'variation_name');
            $select[] = $this->columnOrEmpty('variations', 'sub_sku', 'v', 'variation_sku');
            $select[] = Schema::hasColumn('variations', 'profit_percent')
                ? 'v.profit_percent as profit_percent'
                : DB::raw('0 as profit_percent');
            $select[] = Schema::hasColumn('variations', 'sell_price_inc_tax')
                ? 'v.sell_price_inc_tax as selling_price'
                : DB::raw('0 as selling_price');
        } else {
            $select[] = DB::raw("'' as variation_name");
            $select[] = DB::raw("'' as variation_sku");
            $select[] = DB::raw('0 as profit_percent');
            $select[] = DB::raw('0 as selling_price');
        }

        if (Schema::hasTable('units') && Schema::hasColumn('purchase_lines', 'sub_unit_id')) {
            $query->leftJoin('units as su', 'su.id', '=', 'pl.sub_unit_id');
            $select[] = $this->columnOrEmpty('units', 'short_name', 'su', 'sub_unit_name');
            $select[] = Schema::hasColumn('units', 'base_unit_multiplier')
                ? 'su.base_unit_multiplier as sub_unit_multiplier'
                : DB::raw('1 as sub_unit_multiplier');
        } else {
            $select[] = DB::raw("'' as sub_unit_name");
            $select[] = DB::raw('1 as sub_unit_multiplier');
        }

        if (Schema::hasTable('tax_rates') && Schema::hasColumn('purchase_lines', 'tax_id')) {
            $query->leftJoin('tax_rates as tr', 'tr.id', '=', 'pl.tax_id');
            $select[] = $this->columnOrEmpty('tax_rates', 'name', 'tr', 'tax_name');
            $select[] = Schema::hasColumn('tax_rates', 'amount')
                ? 'tr.amount as tax_rate'
                : DB::raw('0 as tax_rate');
        } else {
            $select[] = DB::raw("'' as tax_name");
            $select[] = DB::raw('0 as tax_rate');
        }

        return $query->orderBy('pl.id')->get($select)->map(function ($line): object {
            $multiplier = max(0.000001, (float) ($line->sub_unit_multiplier ?: 1));
            $line->display_quantity = (float) ($line->quantity ?? 0) / $multiplier;
            $line->display_bonus_quantity = (float) ($line->bonus_qty ?? 0) / $multiplier;
            $line->display_unit = trim((string) ($line->sub_unit_name ?: $line->base_unit_name ?: $line->base_unit_actual_name ?: 'Unit'));
            $line->sku = trim((string) ($line->variation_sku ?: $line->product_sku ?: ''));
            $line->line_subtotal = (float) ($line->quantity ?? 0) * (float) ($line->purchase_price ?? 0);
            $line->line_tax = (float) ($line->quantity ?? 0) * (float) ($line->item_tax ?? 0);
            $line->line_total = (float) ($line->quantity ?? 0) * (float) ($line->purchase_price_inc_tax ?? 0);

            return $line;
        });
    }

    protected function payments(int $transactionId): Collection
    {
        if (! Schema::hasTable('transaction_payments')) {
            return collect();
        }

        $query = DB::table('transaction_payments as tp')->where('tp.transaction_id', $transactionId);
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('tp.deleted_at');
        }

        $select = ['tp.*'];
        if (Schema::hasTable('accounts') && Schema::hasColumn('transaction_payments', 'account_id')) {
            $query->leftJoin('accounts as a', 'a.id', '=', 'tp.account_id');
            $select[] = $this->columnOrEmpty('accounts', 'name', 'a', 'account_name');
        } else {
            $select[] = DB::raw("'' as account_name");
        }

        return $query->orderBy('tp.paid_on')->orderBy('tp.id')->get($select);
    }

    protected function columnOrEmpty(string $table, string $column, string $alias, string $outputAlias): mixed
    {
        return Schema::hasColumn($table, $column)
            ? "{$alias}.{$column} as {$outputAlias}"
            : DB::raw("'' as {$outputAlias}");
    }
}
