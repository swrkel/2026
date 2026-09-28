<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Services\SupplierRecordService;
use Modules\Suppliers\Services\Financial\SupplierPaymentDetailService;
use Modules\Suppliers\Utils\SupplierContextUtil;
use Yajra\DataTables\Facades\DataTables;

class SupplierPaymentTabController extends Controller
{
    protected $supplierService;
    protected SupplierPaymentDetailService $paymentDetailService;

    public function __construct(
        SupplierRecordService $supplierService,
        SupplierPaymentDetailService $paymentDetailService
    ) {
        $this->supplierService = $supplierService;
        $this->paymentDetailService = $paymentDetailService;
    }

    public function index(Request $request)
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $selectedSupplierId = max(0, (int) $request->input('supplier_id', 0));
        $suppliers = $this->supplierService->selectedDropdown($businessId, $selectedSupplierId);

        if ($selectedSupplierId > 0 && ! $suppliers->has($selectedSupplierId)) {
            $selectedSupplierId = 0;
            $suppliers = $this->supplierService->selectedDropdown($businessId, null);
        }

        return view('suppliers::payments.index', [
            'suppliers' => $suppliers,
            'selectedSupplierId' => $selectedSupplierId,
            'supplierLookupUrl' => route('suppliers.lookup.suppliers'),
            'paymentsDataUrl' => route('suppliers.payments.data'),
        ]);
    }

    public function data(Request $request)
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $supplierId = $request->input('supplier_id');
        $query = $this->supplierService->paymentsQuery($businessId, $supplierId);
        $paidOnOrderColumn = Schema::hasColumn('transaction_payments', 'parent_id')
            ? 'COALESCE(transaction_payments.paid_on, supplier_payment_dates.effective_paid_on) $1'
            : 'transaction_payments.paid_on $1';

        return DataTables::of($query)
            ->editColumn('paid_on', static function ($row): string {
                $paidOn = $row->effective_paid_on ?: $row->paid_on;

                return $paidOn ? date('Y-m-d', strtotime((string) $paidOn)) : '-';
            })
            ->addColumn('reference_no', static fn ($row): string => (string) (
                $row->payment_ref_no ?: $row->invoice_no ?: $row->ref_no ?: '-'
            ))
            ->editColumn('method', static function ($row): string {
                $method = trim((string) ($row->method ?? ''));

                return $method === '' ? '-' : ucwords(str_replace('_', ' ', $method));
            })
            ->filterColumn('supplier_name', static function ($query, $keyword): void {
                $query->where('c.name', 'like', $keyword . '%');
            })
            ->filterColumn('reference_no', static function ($query, $keyword): void {
                $query->where(function ($referenceQuery) use ($keyword): void {
                    $referenceQuery->where('transaction_payments.payment_ref_no', 'like', $keyword . '%')
                        ->orWhere('t.invoice_no', 'like', $keyword . '%')
                        ->orWhere('t.ref_no', 'like', $keyword . '%');
                });
            })
            ->orderColumn('supplier_name', 'c.name $1')
            ->orderColumn('paid_on', $paidOnOrderColumn)
            ->orderColumn('reference_no', 'COALESCE(transaction_payments.payment_ref_no, t.invoice_no, t.ref_no) $1')
            ->addColumn('action', static function ($row): string {
                $paymentId = (int) $row->id;
                $viewUrl = route('suppliers.payments.show', ['payment' => $paymentId]);
                // S766: Supplier Pay Due root payments intentionally have no
                // transaction_id, so the host /payments/{id}/edit endpoint
                // cannot resolve a parent transaction and crashes. Keep the
                // host editor for direct Purchase payments, but use the
                // Suppliers-owned editor for business-level Pay Due roots.
                $editUrl = empty($row->transaction_id)
                    ? route('suppliers.payments.edit', ['payment' => $paymentId])
                    : url('/payments/' . $paymentId . '/edit');
                $deleteUrl = url('/payments/' . $paymentId);

                // S768: direct Purchase payments still use the ERP's standard
                // payment editor, but that modal does not carry its Location
                // into the shared Payment Method -> Account loader and its
                // datepicker can leave Paid on blank on the Supplier page.
                // Carry the canonical context with the Action link so the
                // Suppliers JS can stabilise the host modal without touching
                // core/app files.
                $editPaidOn = '';
                $effectivePaidOn = $row->effective_paid_on ?: $row->paid_on;
                if (! empty($effectivePaidOn)) {
                    try {
                        $editPaidOn = \Carbon\Carbon::parse((string) $effectivePaidOn)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $editPaidOn = '';
                    }
                }

                $editLocationId = (int) ($row->location_id ?? 0);
                $editMethod = (string) ($row->method ?? '');
                $editAccountId = (int) ($row->account_id ?? 0);

                return view('suppliers::payments.partials.actions', compact(
                    'paymentId',
                    'viewUrl',
                    'editUrl',
                    'deleteUrl',
                    'editPaidOn',
                    'editLocationId',
                    'editMethod',
                    'editAccountId'
                ))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    public function edit(int $payment)
    {
        abort_unless(
            SupplierContextUtil::can('supplier.edit') || SupplierContextUtil::can('suppliers.edit'),
            403,
            'Unauthorized action.'
        );

        $businessId = SupplierContextUtil::businessId();
        $details = $this->paymentDetailService->find($businessId, $payment);
        abort_if($details === null, 404, 'Supplier payment not found.');

        $paymentRow = $details['payment'];
        // Direct Purchase payments continue through the ERP's standard payment
        // editor. This module-owned form is only for Supplier Pay Due roots.
        abort_unless(empty($paymentRow->transaction_id), 404, 'Supplier payment editor is not required for this payment.');

        $accounts = DB::table('accounts')
            ->where('business_id', $businessId)
            ->when(Schema::hasColumn('accounts', 'is_closed'), static fn ($q) => $q->where('is_closed', 0))
            ->orderBy('name')
            ->pluck('name', 'id');

        $locationId = (int) data_get($details, 'location.id', 0);
        $locationName = trim((string) data_get($details, 'location.name', ''));
        $paymentMethods = $this->paymentMethodsForLocation(
            $businessId,
            $locationId,
            (string) ($paymentRow->method ?? '')
        );

        return view('suppliers::payments.edit', array_merge($details, [
            'accounts' => $accounts,
            'locationId' => $locationId,
            'locationName' => $locationName,
            'paymentMethods' => $paymentMethods,
            'paidOnDate' => $this->resolvePaidOnDateForEdit($details),
            'updateUrl' => route('suppliers.payments.update', ['payment' => $payment]),
        ]));
    }

    public function update(Request $request, int $payment)
    {
        abort_unless(
            SupplierContextUtil::can('supplier.edit') || SupplierContextUtil::can('suppliers.edit'),
            403,
            'Unauthorized action.'
        );

        $businessId = SupplierContextUtil::businessId();
        $details = $this->paymentDetailService->find($businessId, $payment);
        abort_if($details === null, 404, 'Supplier payment not found.');

        $paymentRow = $details['payment'];
        abort_unless(empty($paymentRow->transaction_id), 422, 'This payment must be edited from its source transaction.');

        $validated = $request->validate([
            'paid_on' => ['required', 'date_format:Y-m-d'],
            'method' => ['required', 'string', 'max:60'],
            'account_id' => ['required', 'integer'],
            'location_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $accountId = (int) $validated['account_id'];
        $accountExists = DB::table('accounts')
            ->where('business_id', $businessId)
            ->where('id', $accountId)
            ->when(Schema::hasColumn('accounts', 'is_closed'), static fn ($q) => $q->where('is_closed', 0))
            ->exists();

        abort_unless($accountExists, 422, 'Please select a valid payment account.');

        $locationId = (int) ($validated['location_id'] ?? 0);
        if ($locationId > 0 && Schema::hasTable('business_locations')) {
            $locationExists = DB::table('business_locations')
                ->where('id', $locationId)
                ->when(
                    Schema::hasColumn('business_locations', 'business_id'),
                    static fn ($q) => $q->where('business_id', $businessId)
                )
                ->when(
                    Schema::hasColumn('business_locations', 'deleted_at'),
                    static fn ($q) => $q->whereNull('deleted_at')
                )
                ->exists();

            abort_unless($locationExists, 422, 'Please select a valid business location.');
        }

        $method = trim((string) $validated['method']);
        abort_if($method === '', 422, 'Please select a payment method.');

        $originalPaidOn = (string) ($paymentRow->paid_on ?? '');
        $timePart = '00:00:00';
        if ($originalPaidOn !== '') {
            try {
                $timePart = \Carbon\Carbon::parse($originalPaidOn)->format('H:i:s');
            } catch (\Throwable $e) {
                $timePart = '00:00:00';
            }
        }
        $paidOn = $validated['paid_on'] . ' ' . $timePart;
        $note = trim((string) ($validated['note'] ?? ''));

        DB::transaction(function () use ($payment, $businessId, $accountId, $paidOn, $note, $method): void {
            $familyIds = [$payment];
            if (Schema::hasColumn('transaction_payments', 'parent_id')) {
                $familyIds = array_values(array_unique(array_merge(
                    $familyIds,
                    DB::table('transaction_payments')
                        ->where('parent_id', $payment)
                        ->pluck('id')
                        ->map(static fn ($id): int => (int) $id)
                        ->all()
                )));
            }

            $paymentUpdate = [
                'paid_on' => $paidOn,
                'method' => $method,
                'account_id' => $accountId,
                'note' => $note !== '' ? $note : null,
            ];
            if (Schema::hasColumn('transaction_payments', 'updated_at')) {
                $paymentUpdate['updated_at'] = now();
            }

            DB::table('transaction_payments')
                ->whereIn('id', $familyIds)
                ->where(function ($q) use ($businessId): void {
                    $q->where('business_id', $businessId)->orWhereNull('business_id');
                })
                ->update($paymentUpdate);

            if (! Schema::hasTable('account_transactions')
                || ! Schema::hasColumn('account_transactions', 'transaction_payment_id')) {
                return;
            }

            // The selected payment account is the CREDIT leg for Supplier Pay
            // Due. Accounts Payable is the DEBIT leg and must not be replaced.
            $accountsPayableId = (int) DB::table('accounts')
                ->where('business_id', $businessId)
                ->whereRaw('LOWER(TRIM(name)) IN (?, ?)', ['account payable', 'accounts payable'])
                ->value('id');

            $ledgerQuery = DB::table('account_transactions')
                ->whereIn('transaction_payment_id', $familyIds);
            if (Schema::hasColumn('account_transactions', 'business_id')) {
                $ledgerQuery->where('business_id', $businessId);
            }
            if (Schema::hasColumn('account_transactions', 'deleted_at')) {
                $ledgerQuery->whereNull('deleted_at');
            }

            $ledgerRows = $ledgerQuery->get(['id', 'account_id', 'type']);
            foreach ($ledgerRows as $ledgerRow) {
                $update = ['operation_date' => $paidOn];
                $type = strtolower((string) ($ledgerRow->type ?? ''));
                $currentAccountId = (int) ($ledgerRow->account_id ?? 0);

                if ($type === 'credit' && ($accountsPayableId <= 0 || $currentAccountId !== $accountsPayableId)) {
                    $update['account_id'] = $accountId;
                }
                if (Schema::hasColumn('account_transactions', 'updated_at')) {
                    $update['updated_at'] = now();
                }

                DB::table('account_transactions')->where('id', (int) $ledgerRow->id)->update($update);
            }
        });

        return response()->json([
            'success' => true,
            'msg' => 'Supplier payment updated successfully.',
        ]);
    }

    /**
     * S768: return the enabled purchase-payment methods for the payment's
     * business location. The host Util remains the canonical source when it is
     * available; the database fallback keeps Suppliers functional on installs
     * where the shared Util is not loaded during a module-only request.
     */
    private function paymentMethodsForLocation(
        int $businessId,
        int $locationId,
        string $currentMethod = ''
    ): array {
        $methods = [];

        try {
            if (class_exists(\App\Utils\Util::class)) {
                $util = app(\App\Utils\Util::class);
                $methods = (array) $util->payment_types(
                    $locationId > 0 ? $locationId : null,
                    false,
                    false,
                    false,
                    false,
                    true,
                    'is_purchase_enabled'
                );
            }
        } catch (\Throwable $e) {
            $methods = [];
        }

        foreach (['location_id', 'credit_sale', 'credit_purchase', 'credit_expense', 'pd_cheque', 'post_dated_cheque'] as $unsupported) {
            unset($methods[$unsupported]);
        }

        if ($methods === [] && Schema::hasTable('transaction_payments')) {
            $query = DB::table('transaction_payments')
                ->whereNotNull('method')
                ->where('method', '<>', '');

            if (Schema::hasColumn('transaction_payments', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            foreach ($query->distinct()->orderBy('method')->pluck('method') as $method) {
                $key = trim((string) $method);
                if ($key !== '') {
                    $methods[$key] = ucwords(str_replace('_', ' ', $key));
                }
            }
        }

        $currentMethod = trim($currentMethod);
        if ($currentMethod !== '' && ! array_key_exists($currentMethod, $methods)) {
            $methods[$currentMethod] = ucwords(str_replace('_', ' ', $currentMethod));
        }

        return $methods;
    }

    /**
     * S768: produce a browser-native YYYY-MM-DD value even for historical
     * records where the selected date survives on an allocation/accounting row
     * instead of the root transaction_payments row.
     */
    private function resolvePaidOnDateForEdit(array $details): string
    {
        $candidates = [
            data_get($details, 'payment.paid_on'),
            $details['effective_paid_on'] ?? null,
        ];

        foreach (($details['allocations'] ?? collect()) as $allocation) {
            $candidates[] = $allocation->paid_on ?? null;
        }

        foreach (($details['account_movements'] ?? collect()) as $movement) {
            $candidates[] = $movement->operation_date ?? null;
        }

        $candidates[] = data_get($details, 'payment.created_at');

        foreach ($candidates as $candidate) {
            if (empty($candidate)) {
                continue;
            }

            try {
                return \Carbon\Carbon::parse((string) $candidate)->format('Y-m-d');
            } catch (\Throwable $e) {
                continue;
            }
        }

        return '';
    }

    public function show(int $payment)
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $details = $this->paymentDetailService->find(
            SupplierContextUtil::businessId(),
            $payment
        );

        abort_if($details === null, 404, 'Supplier payment not found.');

        return view('suppliers::payments.show', $details);
    }

}
