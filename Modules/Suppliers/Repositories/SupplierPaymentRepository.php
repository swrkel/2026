<?php

namespace Modules\Suppliers\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\SupplierTransactionPayment;

class SupplierPaymentRepository
{
    /**
     * Return one row per REAL supplier payment.
     *
     * Supplier Pay Due creates one parent transaction_payments row and one or
     * more allocation children. The children are accounting allocations, not
     * separate payments, so they must never be shown/summed again in the
     * Supplier Statement or Supplier balance summary.
     */
    public function paymentQuery(int $businessId, int $supplierId, array $filters = [])
    {
        $query = SupplierTransactionPayment::query()
            ->where('business_id', $businessId)
            ->where('payment_for', $supplierId)
            ->with(['transaction.location:id,name']);

        if (Schema::hasColumn('transaction_payments', 'parent_id')) {
            $query->whereNull('parent_id');
        }

        if (!empty($filters['location_id'])) {
            $locationId = (int) $filters['location_id'];
            $query->where(function ($paymentQuery) use ($locationId) {
                // A Supplier Pay Due parent is a business-level payment and has
                // no transaction_id. Keep it visible under a location filter;
                // direct Purchase payments remain location-specific.
                $paymentQuery->whereNull('transaction_id')
                    ->orWhereHas('transaction', function ($transactionQuery) use ($locationId) {
                        $transactionQuery->where('location_id', $locationId);
                    });
            });
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('paid_on', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('paid_on', '<=', $filters['end_date']);
        }

        return $query->orderBy('paid_on')->orderBy('id');
    }

    /**
     * Supplier Payments page source.
     *
     * Important payment model:
     * - SLP (Supplier Pay Due) is the parent row: transaction_id = NULL.
     * - its allocation children have parent_id = SLP payment id and must not be
     *   displayed as additional payments;
     * - APEP/LPEP are direct Purchase payments and have transaction_id set.
     *
     * Therefore the page starts from transaction_payments, keeps only root
     * payments, and resolves the supplier from payment_for first with the
     * linked purchase contact as a safe legacy fallback.
     */
    public function supplierPaymentsQuery(int $businessId, ?int $supplierId = null)
    {
        $query = SupplierTransactionPayment::query()
            ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id');

        // S758: for historical Supplier Pay Due records, the allocation children
        // can contain the user's selected Paid on date while an older shared
        // payment flow stamped the root row with the current date.  Resolve one
        // effective date from the children so existing records display correctly
        // without rewriting accounting history.
        $hasParentId = Schema::hasColumn('transaction_payments', 'parent_id');
        if ($hasParentId) {
            $allocationDates = DB::table('transaction_payments as supplier_payment_child')
                ->where('supplier_payment_child.business_id', $businessId)
                ->whereNotNull('supplier_payment_child.parent_id');

            if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
                $allocationDates->whereNull('supplier_payment_child.deleted_at');
            }

            $allocationDates
                ->groupBy('supplier_payment_child.parent_id')
                ->select([
                    'supplier_payment_child.parent_id',
                    DB::raw('MIN(supplier_payment_child.paid_on) as effective_paid_on'),
                ]);

            $query->leftJoinSub($allocationDates, 'supplier_payment_dates', function ($join) {
                $join->on('supplier_payment_dates.parent_id', '=', 'transaction_payments.id');
            });
        }

        $query->join('contacts as c', 'c.id', '=', DB::raw('COALESCE(transaction_payments.payment_for, t.contact_id)'))
            ->where('transaction_payments.business_id', $businessId)
            ->where('c.business_id', $businessId)
            ->whereIn('c.type', ['supplier', 'both'])
            ->whereNull('transaction_payments.deleted_at')
            ->when($supplierId, fn ($q) => $q->where('c.id', $supplierId));

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->where(function ($transactionQuery) {
                // Parent SLP rows intentionally have no linked transaction.
                // For direct payments, ignore payments whose transaction was
                // soft-deleted.
                $transactionQuery->whereNull('t.id')
                    ->orWhereNull('t.deleted_at');
            });
        }

        if ($hasParentId) {
            $query->whereNull('transaction_payments.parent_id');
        }

        $effectivePaidOn = $hasParentId
            ? 'COALESCE(transaction_payments.paid_on, supplier_payment_dates.effective_paid_on)'
            : 'transaction_payments.paid_on';

        return $query->select(
            'transaction_payments.*',
            'c.name as supplier_name',
            'c.contact_id as supplier_code',
            't.ref_no',
            't.invoice_no',
            't.location_id',
            DB::raw($effectivePaidOn . ' as effective_paid_on')
        );
    }
}
