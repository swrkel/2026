<?php

namespace Modules\Suppliers\Services\Financial;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supplier-owned payment detail source.
 *
 * Root Supplier Pay Due rows may have transaction_id = NULL. Older records may
 * also have payment_for missing while their allocation children still point to
 * the real purchase transaction. Resolve the supplier from every available
 * source so Action > View remains usable for both legacy and current payments.
 */
class SupplierPaymentDetailService
{
    public function find(int $businessId, int $paymentId): ?array
    {
        if ($businessId <= 0 || $paymentId <= 0 || ! Schema::hasTable('transaction_payments')) {
            return null;
        }

        $paymentQuery = DB::table('transaction_payments as tp')
            ->where('tp.id', $paymentId);

        if (Schema::hasColumn('transaction_payments', 'business_id')) {
            $paymentQuery->where('tp.business_id', $businessId);
        }
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $paymentQuery->whereNull('tp.deleted_at');
        }

        $payment = $paymentQuery->first();
        if (! $payment) {
            return null;
        }

        $transaction = $this->linkedTransaction($businessId, (int) ($payment->transaction_id ?? 0));
        $allocations = $this->allocations($businessId, $paymentId);

        $supplierId = (int) ($payment->payment_for ?? 0);
        if ($supplierId <= 0 && $transaction) {
            $supplierId = (int) ($transaction->contact_id ?? 0);
        }
        if ($supplierId <= 0 && $allocations->isNotEmpty()) {
            $supplierId = (int) ($allocations->pluck('contact_id')->filter()->first() ?? 0);
        }

        $supplier = $this->supplier($businessId, $supplierId);
        if (! $supplier) {
            return null;
        }

        // The root Supplier payment is the user's actual payment record and its
        // paid_on is authoritative. Allocation-child dates are only a fallback
        // for legacy rows where the root date is genuinely missing.
        $effectivePaidOn = $payment->paid_on ?? null;
        if (empty($effectivePaidOn)) {
            $effectivePaidOn = $allocations->pluck('paid_on')->filter()->sort()->first();
        }

        $account = $this->account($businessId, (int) ($payment->account_id ?? 0));
        $accountMovements = $this->accountMovements(
            $businessId,
            collect([$paymentId])->merge($allocations->pluck('payment_id'))->filter()->unique()->values()->all()
        );

        // S763: Action > View follows the standard compact payment receipt.
        // Resolve the business/location independently because Supplier Pay Due
        // root payments intentionally have transaction_id = NULL.
        $business = $this->business($businessId);
        $locationId = (int) ($transaction->location_id ?? 0);
        if ($locationId <= 0) {
            $locationId = (int) ($allocations->pluck('location_id')->filter()->first() ?? 0);
        }
        $location = $this->location($businessId, $locationId);

        return [
            'payment' => $payment,
            'supplier' => $supplier,
            'transaction' => $transaction,
            'allocations' => $allocations,
            'account' => $account,
            'account_movements' => $accountMovements,
            'business' => $business,
            'location' => $location,
            'effective_paid_on' => $effectivePaidOn,
            'effective_paid_on_display' => $this->displayDate($effectivePaidOn),
        ];
    }

    private function linkedTransaction(int $businessId, int $transactionId): ?object
    {
        if ($transactionId <= 0 || ! Schema::hasTable('transactions')) {
            return null;
        }

        $query = DB::table('transactions as t')
            ->where('t.id', $transactionId);

        if (Schema::hasColumn('transactions', 'business_id')) {
            $query->where('t.business_id', $businessId);
        }
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }
        if (Schema::hasTable('business_locations') && Schema::hasColumn('transactions', 'location_id')) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
                ->addSelect('bl.name as location_name');
        }

        $query->addSelect('t.*');

        return $query->first();
    }

    private function allocations(int $businessId, int $paymentId)
    {
        if (! Schema::hasColumn('transaction_payments', 'parent_id')) {
            return collect();
        }

        $query = DB::table('transaction_payments as child')
            ->where('child.parent_id', $paymentId);

        if (Schema::hasColumn('transaction_payments', 'business_id')) {
            $query->where('child.business_id', $businessId);
        }
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('child.deleted_at');
        }

        if (Schema::hasTable('transactions')) {
            $query->leftJoin('transactions as t', 't.id', '=', 'child.transaction_id');
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $query->where(function ($inner) {
                    $inner->whereNull('t.id')->orWhereNull('t.deleted_at');
                });
            }
        }

        if (Schema::hasTable('business_locations') && Schema::hasTable('transactions')) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id');
        }

        $selects = [
            'child.id as payment_id',
            'child.transaction_id',
            'child.amount',
            'child.paid_on',
            'child.method',
        ];

        foreach (['payment_ref_no', 'note', 'account_id', 'created_at', 'updated_at'] as $column) {
            $selects[] = Schema::hasColumn('transaction_payments', $column)
                ? 'child.' . $column
                : DB::raw('NULL as ' . $column);
        }

        if (Schema::hasTable('transactions')) {
            foreach ([
                'contact_id', 'type', 'transaction_date', 'ref_no', 'invoice_no',
                'final_total', 'payment_status', 'status', 'location_id',
            ] as $column) {
                $alias = match ($column) {
                    'type' => 'transaction_type',
                    'status' => 'transaction_status',
                    default => $column,
                };
                $selects[] = Schema::hasColumn('transactions', $column)
                    ? 't.' . $column . ' as ' . $alias
                    : DB::raw('NULL as ' . $alias);
            }
        } else {
            foreach (['contact_id', 'transaction_type', 'transaction_date', 'ref_no', 'invoice_no', 'final_total', 'payment_status', 'transaction_status', 'location_id'] as $alias) {
                $selects[] = DB::raw('NULL as ' . $alias);
            }
        }

        $selects[] = (Schema::hasTable('business_locations') && Schema::hasTable('transactions'))
            ? 'bl.name as location_name'
            : DB::raw('NULL as location_name');

        return $query->select($selects)->orderBy('child.id')->get();
    }

    private function supplier(int $businessId, int $supplierId): ?object
    {
        if ($supplierId <= 0 || ! Schema::hasTable('contacts')) {
            return null;
        }

        $query = DB::table('contacts')->where('id', $supplierId);
        if (Schema::hasColumn('contacts', 'business_id')) {
            $query->where('business_id', $businessId);
        }
        if (Schema::hasColumn('contacts', 'type')) {
            $query->whereIn('type', ['supplier', 'both']);
        }
        if (Schema::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
    }

    private function account(int $businessId, int $accountId): ?object
    {
        if ($accountId <= 0 || ! Schema::hasTable('accounts')) {
            return null;
        }

        $query = DB::table('accounts')->where('id', $accountId);
        if (Schema::hasColumn('accounts', 'business_id')) {
            $query->where('business_id', $businessId);
        }
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
    }


    private function business(int $businessId): ?object
    {
        if ($businessId <= 0 || ! Schema::hasTable('business')) {
            return null;
        }

        return DB::table('business')->where('id', $businessId)->first();
    }

    private function location(int $businessId, int $locationId): ?object
    {
        if (! Schema::hasTable('business_locations')) {
            return null;
        }

        $query = DB::table('business_locations');
        if (Schema::hasColumn('business_locations', 'business_id')) {
            $query->where('business_id', $businessId);
        }
        if (Schema::hasColumn('business_locations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($locationId > 0) {
            $location = (clone $query)->where('id', $locationId)->first();
            if ($location) {
                return $location;
            }
        }

        if (Schema::hasColumn('business_locations', 'is_active')) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('id')->first();
    }

    private function accountMovements(int $businessId, array $paymentIds)
    {
        if (empty($paymentIds)
            || ! Schema::hasTable('account_transactions')
            || ! Schema::hasColumn('account_transactions', 'transaction_payment_id')) {
            return collect();
        }

        $query = DB::table('account_transactions as at')
            ->whereIn('at.transaction_payment_id', array_map('intval', $paymentIds));

        if (Schema::hasColumn('account_transactions', 'business_id')) {
            $query->where('at.business_id', $businessId);
        }
        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            $query->whereNull('at.deleted_at');
        }

        $hasAccountJoin = Schema::hasTable('accounts')
            && Schema::hasColumn('account_transactions', 'account_id');

        if ($hasAccountJoin) {
            $query->leftJoin('accounts as a', 'a.id', '=', 'at.account_id');
        }

        $selects = ['at.*'];
        $selects[] = $hasAccountJoin
            ? 'a.name as account_name'
            : DB::raw('NULL as account_name');

        return $query->select($selects)
            ->orderBy(Schema::hasColumn('account_transactions', 'operation_date') ? 'at.operation_date' : 'at.id')
            ->orderBy('at.id')
            ->get();
    }

    private function displayDate($value): string
    {
        if (! $value) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
