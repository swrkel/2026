<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Services\Ledger\SupplierLedgerDetailService;
use Modules\Suppliers\Utils\SupplierContextUtil;

class SupplierLedgerTabController extends Controller
{
    protected SupplierLedgerDetailService $ledgerService;

    public function __construct(SupplierLedgerDetailService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Render only the light ledger shell. Heavy transaction rows are requested
     * asynchronously after the tab is visible, so opening the tab is immediate.
     */
    public function index(Request $request, $supplierId = null)
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $supplier = $supplierId
            ? Supplier::query()
                ->select(['id', 'name', 'contact_id', 'business_id', 'type', 'updated_at'])
                ->where('business_id', $businessId)
                ->whereIn('type', ['supplier', 'both'])
                ->findOrFail((int) $supplierId)
            : null;

        return view('suppliers::ledger.index', [
            'supplier' => $supplier,
            'ledgerDataUrl' => $supplier
                ? route('suppliers.ledger.data', ['supplier' => $supplier->id])
                : null,
        ]);
    }

    /**
     * DataTables endpoint. The expensive canonical ledger build is cached using
     * a data-version fingerprint, so it invalidates automatically after a new or
     * updated purchase, payment, ledger adjustment, or opening balance.
     */
    public function data(Request $request, $supplierId): JsonResponse
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $supplier = Supplier::query()
            ->select(['id', 'opening_balance', 'updated_at'])
            ->where('business_id', $businessId)
            ->whereIn('type', ['supplier', 'both'])
            ->findOrFail((int) $supplierId);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $locationId = $request->filled('location_id')
            ? (int) $request->input('location_id')
            : null;

        $cacheKey = $this->ledgerCacheKey(
            $businessId,
            (int) $supplier->id,
            $startDate,
            $endDate,
            $locationId,
            $supplier
        );

        $ledger = Cache::remember($cacheKey, now()->addMinutes(5), function () use (
            $supplier,
            $startDate,
            $endDate,
            $locationId,
            $businessId
        ): array {
            return $this->ledgerService->getLedgerDetails(
                (int) $supplier->id,
                $startDate,
                $endDate,
                $locationId,
                $businessId
            );
        });

        $rows = collect($ledger['rows'] ?? [])->values();
        $recordsTotal = $rows->count();
        $search = trim((string) data_get($request->input('search', []), 'value', ''));
        $filteredRows = $search === '' ? $rows : $this->filterRows($rows, $search);
        $recordsFiltered = $filteredRows->count();

        $order = (array) $request->input('order', []);
        $orderColumn = (int) data_get($order, '0.column', 0);
        $orderDirection = strtolower((string) data_get($order, '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $columnMap = [
            0 => 'system_entered_date',
            1 => 'date',
            2 => 'description',
            3 => 'type',
            4 => 'payment_status',
            5 => 'reference',
            6 => 'location',
            7 => 'debit',
            8 => 'credit',
            9 => 'balance',
            10 => 'payment_method',
        ];
        $sortKey = $columnMap[$orderColumn] ?? 'date';

        $filteredRows = $orderDirection === 'desc'
            ? $filteredRows->sortByDesc($sortKey, SORT_NATURAL)
            : $filteredRows->sortBy($sortKey, SORT_NATURAL);

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);
        $length = $length < 0 ? 1000 : min(max($length, 10), 250);
        $pageRows = $filteredRows->values()->slice($start, $length)->values();

        return response()->json([
            'draw' => max(0, (int) $request->input('draw', 0)),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $pageRows,
            'summary' => [
                'opening_balance' => (float) ($ledger['opening_balance'] ?? 0),
                'debit_total' => (float) ($ledger['debit_total'] ?? 0),
                'credit_total' => (float) ($ledger['credit_total'] ?? 0),
                'balance' => (float) ($ledger['balance'] ?? 0),
                'record_count' => (int) ($ledger['record_count'] ?? $recordsTotal),
            ],
        ]);
    }

    private function filterRows(Collection $rows, string $search): Collection
    {
        $needle = function_exists('mb_strtolower')
            ? mb_strtolower($search)
            : strtolower($search);

        return $rows->filter(static function (array $row) use ($needle): bool {
            $haystack = implode(' ', [
                $row['system_entered_date'] ?? '',
                $row['date'] ?? '',
                $row['description'] ?? '',
                $row['type'] ?? '',
                $row['payment_status'] ?? '',
                $row['reference'] ?? '',
                $row['location'] ?? '',
                $row['debit'] ?? '',
                $row['credit'] ?? '',
                $row['balance'] ?? '',
                $row['payment_method'] ?? '',
            ]);

            $haystack = function_exists('mb_strtolower')
                ? mb_strtolower($haystack)
                : strtolower($haystack);

            return str_contains($haystack, $needle);
        })->values();
    }

    private function ledgerCacheKey(
        int $businessId,
        int $supplierId,
        ?string $startDate,
        ?string $endDate,
        ?int $locationId,
        Supplier $supplier
    ): string {
        $versions = [
            'schema_version' => 'S758-20260917-payment-date-1',
            'database' => DB::connection()->getDatabaseName(),
            'business_id' => $businessId,
            'supplier_id' => $supplierId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location_id' => $locationId,
            'supplier_updated_at' => optional($supplier->updated_at)->format('Y-m-d H:i:s.u'),
            'opening_balance' => (string) ($supplier->opening_balance ?? '0'),
        ];

        if (Schema::hasTable('transactions')) {
            $versions['transactions'] = $this->tableVersion(
                'transactions',
                static fn ($query) => $query
                    ->where('business_id', $businessId)
                    ->where('contact_id', $supplierId)
            );
        }

        if (Schema::hasTable('transaction_payments')) {
            $versions['payments'] = $this->tableVersion(
                'transaction_payments',
                static fn ($query) => $query
                    ->where('business_id', $businessId)
                    ->where('payment_for', $supplierId)
            );
        }

        if (Schema::hasTable('contact_ledgers')) {
            $versions['contact_ledgers'] = $this->tableVersion(
                'contact_ledgers',
                static fn ($query) => $query->where('contact_id', $supplierId)
            );
        }

        return 'suppliers:ledger:' . hash('sha256', json_encode($versions));
    }

    private function tableVersion(string $table, callable $scope): array
    {
        $query = $scope(DB::table($table));
        $selects = ['COUNT(*) as row_count', 'COALESCE(MAX(id), 0) as max_id'];

        if (Schema::hasColumn($table, 'updated_at')) {
            $selects[] = "COALESCE(MAX(updated_at), '1970-01-01 00:00:00') as max_updated_at";
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $selects[] = "COALESCE(MAX(deleted_at), '1970-01-01 00:00:00') as max_deleted_at";
        }

        $version = $query->selectRaw(implode(', ', $selects))->first();

        return (array) $version;
    }
}
