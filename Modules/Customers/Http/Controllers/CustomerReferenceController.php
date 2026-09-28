<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Customers\Entities\CustomerReferenceFuelType;
use Modules\Customers\Http\Requests\StoreCustomerReferenceRequest;
use Modules\Customers\Http\Requests\UpdateCustomerReferenceRequest;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerReferenceService;
use Yajra\DataTables\Facades\DataTables;

/**
 * Task 8046 - List Customer Reference.
 *
 * Screens owned by this controller:
 *   - the List Customer Reference page and its server-side DataTable
 *   - the Add Customer Reference popup (multi-row entry, saved in one go)
 *   - row edit, delete and the Active/Inactive toggle
 *   - the QR action popup, and its Print / PDF / WhatsApp / Email actions
 *
 * Everything is Customers-module owned: its own controller, service, requests
 * and views, with no Contact-module controllers or views involved, matching the
 * separation the module has been moving toward since CUS_SEP_005.
 */
class CustomerReferenceController extends Controller
{
    protected CustomerReferenceService $service;

    protected CustomerPermissionService $permissionService;

    public function __construct(
        CustomerReferenceService $service,
        CustomerPermissionService $permissionService
    ) {
        $this->service = $service;
        $this->permissionService = $permissionService;
    }

    protected function businessId(Request $request): int
    {
        return (int) ($request->session()->get('business.id') ?: $request->session()->get('user.business_id'));
    }

    protected function userId(): ?int
    {
        return optional(auth()->user())->id;
    }

    /**
     * The List Customer Reference page.
     */
    public function index(Request $request)
    {
        $this->permissionService->authorize('view');

        $businessId = $this->businessId($request);

        // The page must open even when the table has not been installed on this
        // tenant yet, showing an install notice rather than a 500. The module
        // already behaves this way for the workflow and master-data pages.
        $installed = $this->service->isInstalled();

        $customers = $installed ? $this->service->customerOptions($businessId) : [];
        $fuelTypes = $this->service->fuelTypeOptions($businessId);
        $addedByUsers = $installed ? $this->service->addedByOptions($businessId) : [];
        $fuelTypeConfigured = $this->service->fuelTypeService()->isConfigured($businessId);
        $qrCapabilities = $this->service->qrService()->capabilities();

        /*
         * The runtime script's configuration is assembled here and passed to
         * the view as one pre-encoded JSON string.
         *
         * It is built in PHP rather than inline in the blade with a json directive
         * because Blade parses a directive argument by bracket counting, and
         * an array literal that itself contains array access terminates that
         * parser early - which silently produces a truncated, syntactically
         * broken compiled view.
         *
         * JSON_HEX_TAG and friends stop any route or token value from breaking
         * out of the surrounding <script> element.
         */
        $jsConfigJson = json_encode([
            'installed' => $installed,
            'qrServerSide' => (bool) ($qrCapabilities['server_side'] ?? false),
            'notKnownValue' => CustomerReferenceFuelType::NOT_KNOWN_VALUE,
            'notKnownLabel' => CustomerReferenceFuelType::NOT_KNOWN_LABEL,
            'routes' => [
                'data' => route('customers.customer_references.data'),
                'store' => route('customers.customer_references.store'),
                'editBase' => url('/customers/customer-references'),
                'csrf' => csrf_token(),
            ],
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

        return view('customers::customer_references.index', compact(
            'installed',
            'customers',
            'fuelTypes',
            'addedByUsers',
            'fuelTypeConfigured',
            'qrCapabilities',
            'jsConfigJson'
        ));
    }

    /**
     * Serve the page's runtime JavaScript from the module.
     *
     * Same approach as the Bulk Payment runtime: the file is static, so it is
     * served with a long cache lifetime and busted by the ?v= query string in
     * the view. This keeps the script out of the blade template, where it could
     * not be cached at all.
     */
    public function runtime()
    {
        $this->permissionService->authorize('view');

        $path = module_path('Customers', 'Resources/assets/js/customer-reference.js');
        $javascript = is_file($path)
            ? file_get_contents($path)
            : "console.error('Customer Reference runtime file is missing.');";

        return response($javascript, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400, immutable',
        ]);
    }

    /**
     * Server-side DataTable feed for the list page.
     */
    public function data(Request $request)
    {
        $this->permissionService->authorize('view');

        $businessId = $this->businessId($request);

        if (! $this->service->isInstalled()) {
            return DataTables::of(collect())->make(true);
        }

        $query = $this->service->applyFilters(
            $this->service->listQuery($businessId),
            $request->only([
                'date_range', 'start_date', 'end_date', 'customer_id', 'status',
                'is_vehicle', 'reference_no', 'fuel_type', 'added_by',
            ])
        );

        $canEdit = $this->permissionService->allows('edit');
        $canDelete = $this->permissionService->allows('delete');

        return DataTables::of($query)
            ->addColumn('action', function ($row) use ($canEdit, $canDelete) {
                return view('customers::customer_references.partials.row_actions', [
                    'row' => $row,
                    'canEdit' => $canEdit,
                    'canDelete' => $canDelete,
                ])->render();
            })
            ->editColumn('reference_datetime', function ($row) {
                return empty($row->reference_datetime)
                    ? ''
                    : \Carbon\Carbon::parse($row->reference_datetime)->format('d/m/Y H:i');
            })
            ->addColumn('status_label', function ($row) {
                return $row->is_active
                    ? '<span class="label label-success">Active</span>'
                    : '<span class="label label-default">Inactive</span>';
            })
            ->addColumn('is_vehicle_label', function ($row) {
                return $row->is_vehicle ? 'Yes' : 'No';
            })
            ->addColumn('fuel_type_label', function ($row) {
                // Fuel type is meaningless for a non-vehicle reference, so the
                // cell is left blank rather than showing "Not Known", which
                // would read as though a fuel type had been chosen.
                if (! $row->is_vehicle) {
                    return '';
                }

                return e($row->fuel_type_name ?: CustomerReferenceFuelType::NOT_KNOWN_LABEL);
            })
            ->editColumn('customer_name', function ($row) {
                return e($row->customer_name);
            })
            ->editColumn('reference_no', function ($row) {
                return e($row->reference_no);
            })
            ->editColumn('added_by_name', function ($row) {
                return e($row->added_by_name);
            })
            ->filterColumn('customer_name', function ($query, $keyword) {
                /*
                 * customer_name is a computed alias, so DataTables cannot
                 * search it as a column. The expression is taken from the
                 * service rather than rewritten here, because it is built
                 * dynamically from whichever name columns the tenant's
                 * `contacts` table actually has.
                 */
                $query->whereRaw($this->service->customerNameSql() . ' LIKE ?', ['%' . $keyword . '%']);
            })
            ->rawColumns(['action', 'status_label'])
            ->make(true);
    }

    /**
     * Save the rows stacked up in the Add Customer Reference popup.
     *
     * The popup posts a `references` array so several can be added in one
     * transaction, which is what the spec's "add multiple references in once
     * time" requires.
     */
    public function store(StoreCustomerReferenceRequest $request): JsonResponse
    {
        $this->permissionService->authorize('create');

        $businessId = $this->businessId($request);

        if (! $this->service->isInstalled()) {
            return response()->json([
                'success' => false,
                'msg' => 'The customer_qr_references table is not installed in this database yet.',
            ], 422);
        }

        try {
            $created = $this->service->createMany(
                $businessId,
                $request->referenceRows(),
                $this->userId()
            );

            return response()->json([
                'success' => true,
                'count' => count($created),
                'msg' => count($created) . ' customer reference(s) saved successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Customer Reference save failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'Unable to save the customer references. Please try again.',
            ], 500);
        }
    }

    /**
     * Row data for the edit popup.
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        $this->permissionService->authorize('edit');

        $businessId = $this->businessId($request);
        $reference = $this->service->findOrFail($businessId, $id);

        return response()->json([
            'success' => true,
            'reference' => [
                'id' => $reference->id,
                'customer_id' => $reference->customer_id,
                'reference_datetime' => optional($reference->reference_datetime)->format('Y-m-d\TH:i'),
                'is_vehicle' => $reference->is_vehicle ? 1 : 0,
                'reference_no' => $reference->reference_no,
                // The select is keyed by string, and "Not Known" is the
                // sentinel rather than an id, so both cases are normalised here.
                'fuel_type' => $reference->fuel_type_id
                    ? (string) $reference->fuel_type_id
                    : CustomerReferenceFuelType::NOT_KNOWN_VALUE,
            ],
        ]);
    }

    public function update(UpdateCustomerReferenceRequest $request, int $id): JsonResponse
    {
        $this->permissionService->authorize('edit');

        $businessId = $this->businessId($request);

        try {
            $this->service->update($businessId, $id, $request->referenceRow(), $this->userId());

            return response()->json([
                'success' => true,
                'msg' => 'Customer reference updated successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Customer Reference update failed', [
                'business_id' => $businessId,
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'Unable to update this customer reference.',
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->permissionService->authorize('delete');

        try {
            $this->service->delete($this->businessId($request), $id);

            return response()->json([
                'success' => true,
                'msg' => 'Customer reference deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Customer Reference delete failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'Unable to delete this customer reference.',
            ], 500);
        }
    }

    /**
     * Active / Inactive toggle from the Action column.
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $this->permissionService->authorize('edit');

        try {
            $isActive = $this->service->toggleStatus($this->businessId($request), $id, $this->userId());

            return response()->json([
                'success' => true,
                'is_active' => $isActive,
                'msg' => $isActive ? 'Customer reference activated.' : 'Customer reference deactivated.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Customer Reference status toggle failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'Unable to change the status of this customer reference.',
            ], 500);
        }
    }

    /**
     * Read-only View popup.
     */
    public function show(Request $request, int $id)
    {
        $this->permissionService->authorize('view');

        $data = $this->service->viewModel($this->businessId($request), $id);

        return view('customers::customer_references.partials.view_modal', $data);
    }
}
