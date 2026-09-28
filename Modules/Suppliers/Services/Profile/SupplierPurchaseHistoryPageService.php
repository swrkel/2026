<?php

namespace Modules\Suppliers\Services\Profile;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Utils\SupplierContextUtil;

class SupplierPurchaseHistoryPageService
{
    public function paginate(Supplier $supplier, array $filters = []): LengthAwarePaginator
    {
        if (! Schema::hasTable('transactions')) {
            return DB::table(DB::raw('(SELECT 1 AS id) as empty_rows'))
                ->whereRaw('1 = 0')
                ->paginate(25);
        }

        $perPage = (int) ($filters['per_page'] ?? 25);
        $perPage = in_array($perPage, [10, 25, 50, 100, 250], true) ? $perPage : 25;
        $search = trim((string) ($filters['search'] ?? ''));

        $query = DB::table('transactions as t')
            ->where('t.business_id', SupplierContextUtil::businessId())
            ->where('t.contact_id', (int) $supplier->id)
            ->where('t.type', 'purchase');

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }

        if (Schema::hasTable('business_locations')) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id');
        }

        $paymentSubquery = null;
        if (Schema::hasTable('transaction_payments')) {
            $paymentSubquery = DB::table('transaction_payments')
                ->select('transaction_id', DB::raw('SUM(amount) as paid_total'))
                ->whereNull('parent_id');

            if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
                $paymentSubquery->whereNull('deleted_at');
            }

            $paymentSubquery->groupBy('transaction_id');
            $query->leftJoinSub($paymentSubquery, 'tp_sum', 'tp_sum.transaction_id', '=', 't.id');
        }

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('t.invoice_no', 'like', '%' . $search . '%')
                    ->orWhere('t.ref_no', 'like', '%' . $search . '%')
                    ->orWhere('t.status', 'like', '%' . $search . '%')
                    ->orWhere('t.payment_status', 'like', '%' . $search . '%');
            });
        }

        $select = [
            't.id',
            't.type',
            't.transaction_date',
            't.invoice_no',
            't.ref_no',
            't.status',
            't.payment_status',
            't.final_total',
            't.created_at',
        ];

        $select[] = Schema::hasTable('business_locations')
            ? 'bl.name as location_name'
            : DB::raw("'' as location_name");

        $select[] = $paymentSubquery
            ? DB::raw('COALESCE(tp_sum.paid_total, 0) as paid_total')
            : DB::raw('0 as paid_total');

        return $query
            ->select($select)
            ->orderByDesc('t.transaction_date')
            ->orderByDesc('t.id')
            ->paginate($perPage)
            ->appends(array_filter([
                'search' => $search,
                'per_page' => $perPage,
            ], static fn ($value): bool => $value !== '' && $value !== null));
    }
}
