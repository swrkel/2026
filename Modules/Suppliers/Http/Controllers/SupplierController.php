<?php

namespace Modules\Suppliers\Http\Controllers;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Http\Requests\SupplierStoreRequest;
use Modules\Suppliers\Http\Requests\SupplierUpdateRequest;
use Modules\Suppliers\Services\Forms\SupplierFormDataService;
use Modules\Suppliers\Services\SupplierBalanceService;
use Modules\Suppliers\Services\SupplierNumberService;
use Modules\Suppliers\Services\SupplierQueryService;
use Modules\Suppliers\Services\Financial\SupplierOpeningBalanceSyncService;

class SupplierController extends Controller
{
    public function __construct(
        private SupplierQueryService $supplierQueryService,
        private SupplierNumberService $supplierNumberService,
        private SupplierFormDataService $supplierFormDataService,
        private SupplierBalanceService $supplierBalanceService,
        private SupplierOpeningBalanceSyncService $openingBalanceSyncService
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'location_id', 'date_range', 'per_page']);

        // The Supplier Records page is a supplier master list, not a daily
        // transaction report. Do not apply a created-date filter unless the user
        // explicitly enters or selects a date range. This keeps the list aligned
        // with the Add Purchase supplier selector, which exposes every supplier
        // for the current business.
        if (!$request->filled('date_range')) {
            unset($filters['date_range']);
        }

        $perPage = (int) ($filters['per_page'] ?? 25);
        $perPage = in_array($perPage, [10, 25, 50, 100, 250], true) ? $perPage : 25;
        $filters['per_page'] = $perPage;

        $query = $this->supplierQueryService->listQuery($filters);

        // Calculate Supplier Total, Total Due, and Total Overpaid from every
        // supplier matching the active filters. This intentionally runs before
        // pagination so the summary is never limited to the current page.
        $summarySupplierIds = (clone $query)
            ->reorder()
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $supplierBalanceSummary = $this->supplierBalanceService->summary($summarySupplierIds);

        $suppliers = $query->paginate($perPage);

        // S529: Calculate payable balances only for the suppliers on the current
        // page. This keeps the list fast while matching the established supplier
        // ledger/Contact list Total Due rules.
        $supplierIds = $suppliers->getCollection()->pluck('id')->map(static fn ($id) => (int) $id)->all();
        $balances = $this->supplierBalanceService->balances($supplierIds);

        $suppliers->setCollection($suppliers->getCollection()->map(function (Supplier $supplier) use ($balances) {
            $balance = $balances[(int) $supplier->id] ?? [];

            $supplier->setAttribute('total_due', (float) ($balance['total_due'] ?? 0));
            $supplier->setAttribute('total_purchase_return', (float) ($balance['total_purchase_return'] ?? 0));
            $supplier->setAttribute('purchase_return_paid', (float) ($balance['purchase_return_paid'] ?? 0));
            $supplier->setAttribute('return_due', (float) ($balance['return_due'] ?? 0));

            return $supplier;
        }));

        return view('suppliers::suppliers.index', compact('suppliers', 'filters', 'supplierBalanceSummary'));
    }

    public function create()
    {
        $supplierNumber = $this->supplierNumberService->nextSupplierNumber();
        $formData = $this->supplierFormDataService->defaults();

        return view('suppliers::suppliers.create', array_merge($formData, compact('supplierNumber')));
    }

    public function store(SupplierStoreRequest $request)
    {
        $data = $request->validated();
        $data['business_id'] = \Modules\Suppliers\Utils\SupplierContextUtil::businessId();
        $data['type'] = 'supplier';
        $data['contact_id'] = $data['contact_id'] ?? $this->supplierNumberService->nextSupplierNumber();
        $data['created_by'] = \Modules\Suppliers\Utils\SupplierContextUtil::userId();

        // Transaction Date is exposed on Add Supplier for installations that
        // keep this optional field on contacts. Because this is central code
        // serving multiple tenant schemas, never send an unknown column to SQL.
        $transactionDate = $data['transaction_date'] ?? null;
        unset($data['transaction_date']);
        if ($transactionDate !== null
            && \Illuminate\Support\Facades\Schema::hasColumn('contacts', 'transaction_date')) {
            $data['transaction_date'] = $transactionDate;
        }

        // Keep standalone Suppliers module aligned with the core contacts supplier list.
        // Some supplier lists filter out inactive/deleted contacts, so ensure new
        // suppliers are created as active contacts and visible immediately.
        $data['is_default'] = $data['is_default'] ?? 0;
        if (\Illuminate\Support\Facades\Schema::hasColumn('contacts', 'status')) {
            $data['status'] = $data['status'] ?? 'active';
        }

        $supplier = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $transactionDate) {
            $supplier = Supplier::create($data);
            $this->openingBalanceSyncService->sync($supplier, $transactionDate);

            return $supplier;
        });

        return redirect()
            ->route('suppliers.records.index')
            ->with('status', __('suppliers::lang.supplier_created_successfully'));
    }

    /**
     * Normalize legacy standalone opening balances before handing the request
     * to the ERP's established Supplier Pay Due endpoint.  The Pay Due link is
     * loaded by AJAX; the same-origin redirect is followed automatically and
     * the shared payment modal is returned unchanged.
     */
    public function preparePayDue(Supplier $supplier)
    {
        $this->ensureSupplierAccess($supplier);
        $this->openingBalanceSyncService->sync($supplier);

        return redirect()->to(url('/payments/pay-contact-due/' . (int) $supplier->id) . '?type=purchase');
    }

    public function show(Supplier $supplier)
    {
        $this->ensureSupplierAccess($supplier);

        return view('suppliers::suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        $this->ensureSupplierAccess($supplier);
        $formData = $this->supplierFormDataService->defaults($supplier);

        return view('suppliers::suppliers.edit', array_merge($formData, compact('supplier')));
    }

    public function update(SupplierUpdateRequest $request, Supplier $supplier)
    {
        $this->ensureSupplierAccess($supplier);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $supplier) {
            $supplier->update($request->validated());
            $supplier->refresh();
            $this->openingBalanceSyncService->sync($supplier);
        });

        return redirect()
            ->route('suppliers.records.index')
            ->with('status', __('suppliers::lang.supplier_updated_successfully'));
    }

    public function destroy(Supplier $supplier)
    {
        $this->ensureSupplierAccess($supplier);
        $this->requireSupplierPermission('supplier.delete');
        $supplier->delete();

        return redirect()
            ->route('suppliers.records.index')
            ->with('status', __('suppliers::lang.supplier_deleted_successfully'));
    }

    public function toggleActive(Supplier $supplier)
    {
        $this->ensureSupplierAccess($supplier);
        $this->requireSupplierPermission('supplier.update');

        $wasActive = (bool) ($supplier->active ?? true);
        $supplier->active = $wasActive ? 0 : 1;
        $supplier->save();

        return redirect()
            ->back()
            ->with('status', $wasActive
                ? __('lang_v1.contact_deactivate_success')
                : __('lang_v1.contact_activate_success'));
    }
}
