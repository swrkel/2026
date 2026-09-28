<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\CustomerReference;
use Modules\Customers\Entities\CustomerReferenceFuelType;

/**
 * Task 8046 - all customer reference read/write logic.
 *
 * Controllers stay thin and this class owns the query building, the tenant
 * scoping and the QR payload refresh rules, matching how the rest of the
 * module separates controllers from services.
 */
class CustomerReferenceService
{
    protected CustomerReferenceQrService $qrService;

    protected CustomerReferenceFuelTypeService $fuelTypeService;

    public function __construct(
        CustomerReferenceQrService $qrService,
        CustomerReferenceFuelTypeService $fuelTypeService
    ) {
        $this->qrService = $qrService;
        $this->fuelTypeService = $fuelTypeService;
    }

    public function isInstalled(): bool
    {
        return Schema::hasTable('customer_qr_references');
    }

    /**
     * Base query for the list page, joined to the customer and the adding user.
     *
     * Selected as a query rather than an Eloquent eager load because the list
     * is served through DataTables server-side processing, which needs a single
     * flat query it can paginate and sort.
     */
    public function listQuery(int $businessId)
    {
        $query = DB::table('customer_qr_references')
            ->leftJoin('contacts', 'customer_qr_references.customer_id', '=', 'contacts.id')
            ->leftJoin('users', 'customer_qr_references.created_by', '=', 'users.id')
            ->where('customer_qr_references.business_id', $businessId)
            ->whereNull('customer_qr_references.deleted_at');

        $customerName = $this->customerNameExpression();
        $addedBy = $this->userNameExpression();

        return $query->select([
            'customer_qr_references.id',
            'customer_qr_references.reference_datetime',
            'customer_qr_references.customer_id',
            'customer_qr_references.is_vehicle',
            'customer_qr_references.reference_no',
            'customer_qr_references.fuel_type_id',
            'customer_qr_references.fuel_type_name',
            'customer_qr_references.qr_payload',
            'customer_qr_references.qr_token',
            'customer_qr_references.is_active',
            'customer_qr_references.created_by',
            DB::raw("{$customerName} as customer_name"),
            DB::raw("{$addedBy} as added_by_name"),
        ]);
    }

    /**
     * Apply the seven filters the spec asks for on the list page.
     *
     * Each filter is applied only when it carries a value, so an untouched
     * filter bar returns everything rather than nothing.
     */
    public function applyFilters($query, array $filters)
    {
        // Date range. Sent by the standard ERP date range picker as
        // "DD/MM/YYYY - DD/MM/YYYY"; also accepts explicit start/end values.
        [$start, $end] = $this->resolveDateRange($filters);
        if ($start !== null) {
            $query->where('customer_qr_references.reference_datetime', '>=', $start->startOfDay()->format('Y-m-d H:i:s'));
        }
        if ($end !== null) {
            $query->where('customer_qr_references.reference_datetime', '<=', $end->endOfDay()->format('Y-m-d H:i:s'));
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_qr_references.customer_id', (int) $filters['customer_id']);
        }

        // Status is tri-state: unset shows both, 0 and 1 filter. A plain
        // !empty() check would make "Inactive" (0) behave like "All".
        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('customer_qr_references.is_active', (int) $filters['status']);
        }

        if (isset($filters['is_vehicle']) && $filters['is_vehicle'] !== '' && $filters['is_vehicle'] !== null) {
            $query->where('customer_qr_references.is_vehicle', (int) $filters['is_vehicle']);
        }

        if (! empty($filters['reference_no'])) {
            $query->where('customer_qr_references.reference_no', 'like', '%' . trim($filters['reference_no']) . '%');
        }

        if (! empty($filters['fuel_type'])) {
            if (CustomerReferenceFuelType::isNotKnown($filters['fuel_type'])) {
                // "Not Known" is stored as a NULL id, so it cannot be matched
                // by equality against the posted sentinel string.
                $query->whereNull('customer_qr_references.fuel_type_id');
            } else {
                $query->where('customer_qr_references.fuel_type_id', (int) $filters['fuel_type']);
            }
        }

        if (! empty($filters['added_by'])) {
            $query->where('customer_qr_references.created_by', (int) $filters['added_by']);
        }

        return $query;
    }

    /**
     * Create one reference and generate its QR payload.
     */
    public function create(int $businessId, array $data, ?int $userId = null): CustomerReference
    {
        $customerName = $this->customerName($businessId, (int) $data['customer_id']);
        $isVehicle = (bool) ($data['is_vehicle'] ?? false);

        // Fuel type only applies to vehicles. Clearing it for non-vehicles here
        // rather than trusting the form means a stale hidden field cannot
        // attach a fuel type to a non-vehicle reference.
        [$fuelTypeId, $fuelTypeName] = $isVehicle
            ? $this->resolveFuelType($businessId, $data['fuel_type'] ?? null)
            : [null, null];

        $referenceNo = trim((string) $data['reference_no']);

        $reference = new CustomerReference();
        $reference->business_id = $businessId;
        $reference->customer_id = (int) $data['customer_id'];
        $reference->reference_datetime = $this->resolveDateTime($data['reference_datetime'] ?? null);
        $reference->is_vehicle = $isVehicle;
        $reference->reference_no = $referenceNo;
        $reference->fuel_type_id = $fuelTypeId;
        $reference->fuel_type_name = $fuelTypeName;
        $reference->qr_payload = $this->qrService->buildPayload($customerName, $isVehicle, $referenceNo, $fuelTypeName);
        $reference->qr_token = $this->qrService->generateToken();
        $reference->is_active = true;
        $reference->created_by = $userId;
        $reference->save();

        return $reference;
    }

    /**
     * Create several references in one transaction.
     *
     * The popup lets the user stack up rows and press Save once. All-or-nothing
     * is the right behaviour: a partial save would leave the user unsure which
     * of their rows made it, with no way to tell from the list.
     *
     * @param  array<int, array>  $rows
     * @return array<int, CustomerReference>
     */
    public function createMany(int $businessId, array $rows, ?int $userId = null): array
    {
        return DB::transaction(function () use ($businessId, $rows, $userId) {
            $created = [];
            foreach ($rows as $row) {
                $created[] = $this->create($businessId, $row, $userId);
            }

            return $created;
        });
    }

    /**
     * Update a reference and refresh its QR payload to match.
     */
    public function update(int $businessId, int $id, array $data, ?int $userId = null): CustomerReference
    {
        $reference = $this->findOrFail($businessId, $id);

        $customerId = (int) ($data['customer_id'] ?? $reference->customer_id);
        $customerName = $this->customerName($businessId, $customerId);
        $isVehicle = (bool) ($data['is_vehicle'] ?? false);

        [$fuelTypeId, $fuelTypeName] = $isVehicle
            ? $this->resolveFuelType($businessId, $data['fuel_type'] ?? null)
            : [null, null];

        $referenceNo = trim((string) ($data['reference_no'] ?? $reference->reference_no));

        $reference->customer_id = $customerId;
        $reference->reference_datetime = $this->resolveDateTime($data['reference_datetime'] ?? null);
        $reference->is_vehicle = $isVehicle;
        $reference->reference_no = $referenceNo;
        $reference->fuel_type_id = $fuelTypeId;
        $reference->fuel_type_name = $fuelTypeName;

        // The QR content is regenerated on every edit. Any other choice leaves
        // the code disagreeing with the row it is attached to.
        $reference->qr_payload = $this->qrService->buildPayload($customerName, $isVehicle, $referenceNo, $fuelTypeName);

        if (empty($reference->qr_token)) {
            $reference->qr_token = $this->qrService->generateToken();
        }

        $reference->updated_by = $userId;
        $reference->save();

        return $reference;
    }

    public function delete(int $businessId, int $id): void
    {
        $this->findOrFail($businessId, $id)->delete();
    }

    /**
     * Flip Active/Inactive and return the new state.
     */
    public function toggleStatus(int $businessId, int $id, ?int $userId = null): bool
    {
        $reference = $this->findOrFail($businessId, $id);
        $reference->is_active = ! $reference->is_active;
        $reference->updated_by = $userId;
        $reference->save();

        return (bool) $reference->is_active;
    }

    /**
     * Fetch scoped to the business so one tenant cannot reach another's rows
     * by guessing an id.
     */
    public function findOrFail(int $businessId, int $id): CustomerReference
    {
        return CustomerReference::where('business_id', $businessId)->findOrFail($id);
    }

    /**
     * A reference plus the display values the QR/view screens need.
     */
    public function viewModel(int $businessId, int $id): array
    {
        $reference = $this->findOrFail($businessId, $id);
        $customerName = $this->customerName($businessId, (int) $reference->customer_id);

        // Regenerate defensively: rows created before a data fix may have an
        // empty payload, and an empty QR is worse than a rebuilt one.
        $payload = $reference->qr_payload
            ?: $this->qrService->buildPayloadForReference($reference, $customerName);

        return [
            'reference' => $reference,
            'customer_name' => $customerName,
            'qr_payload' => $payload,
            'qr_svg' => $this->qrService->renderSvg($payload),
            'qr_server_side' => $this->qrService->serverSideAvailable(),
            'added_by_name' => $this->userName((int) $reference->created_by),
            'fuel_type_label' => $reference->fuelTypeLabel(),
        ];
    }

    /**
     * Customers for the dropdowns.
     *
     * @return array<int, string>
     */
    public function customerOptions(int $businessId): array
    {
        $nameExpression = $this->customerNameExpression();

        $query = DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->whereIn('type', ['customer', 'both'])
            ->select('id', DB::raw("{$nameExpression} as display_name"));

        return $query->orderBy('display_name')->pluck('display_name', 'id')->toArray();
    }

    /**
     * Users who have actually added a reference, for the "Added By" filter.
     *
     * Deliberately not every user in the business: a filter listing hundreds of
     * names that can only ever match a handful is harder to use than one that
     * lists only the names present in the data.
     *
     * @return array<int, string>
     */
    public function addedByOptions(int $businessId): array
    {
        if (! $this->isInstalled()) {
            return [];
        }

        $nameExpression = $this->userNameExpression();

        return DB::table('customer_qr_references')
            ->leftJoin('users', 'customer_qr_references.created_by', '=', 'users.id')
            ->where('customer_qr_references.business_id', $businessId)
            ->whereNull('customer_qr_references.deleted_at')
            ->whereNotNull('customer_qr_references.created_by')
            ->select('customer_qr_references.created_by as id', DB::raw("{$nameExpression} as display_name"))
            ->distinct()
            ->orderBy('display_name')
            ->pluck('display_name', 'id')
            ->toArray();
    }

    public function fuelTypeOptions(int $businessId): array
    {
        return $this->fuelTypeService->options($businessId);
    }

    public function qrService(): CustomerReferenceQrService
    {
        return $this->qrService;
    }

    public function fuelTypeService(): CustomerReferenceFuelTypeService
    {
        return $this->fuelTypeService;
    }

    /**
     * Resolve a posted fuel type into [id, name].
     *
     * An id that does not belong to this tenant's Fuel category is downgraded
     * to "Not Known" rather than rejected, so a category deleted between page
     * load and submit does not lose the user's whole entry.
     *
     * @return array{0: ?int, 1: string}
     */
    protected function resolveFuelType(int $businessId, $posted): array
    {
        if (CustomerReferenceFuelType::isNotKnown($posted)) {
            return [null, CustomerReferenceFuelType::NOT_KNOWN_LABEL];
        }

        if (! $this->fuelTypeService->isValidFuelTypeId($businessId, $posted)) {
            return [null, CustomerReferenceFuelType::NOT_KNOWN_LABEL];
        }

        $id = (int) $posted;

        return [$id, (string) $this->fuelTypeService->nameFor($businessId, $id)];
    }

    /**
     * Parse the popup's date/time, defaulting to now.
     *
     * Accepts both the ERP's DD/MM/YYYY display format and the ISO format the
     * browser's datetime-local control posts.
     */
    protected function resolveDateTime($value): string
    {
        if (empty($value)) {
            return now()->format('Y-m-d H:i:s');
        }

        foreach (['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d H:i:s');
                }
            } catch (\Throwable $e) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return now()->format('Y-m-d H:i:s');
        }
    }

    /**
     * Turn the filter bar's date range into two Carbon instances.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function resolveDateRange(array $filters): array
    {
        $start = $filters['start_date'] ?? null;
        $end = $filters['end_date'] ?? null;

        if (empty($start) && ! empty($filters['date_range'])) {
            $parts = explode('-', (string) $filters['date_range']);
            if (count($parts) === 2) {
                $start = trim($parts[0]);
                $end = trim($parts[1]);
            }
        }

        return [$this->parseDate($start), $this->parseDate($end)];
    }

    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, trim((string) $value));
                if ($parsed !== false) {
                    return $parsed;
                }
            } catch (\Throwable $e) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function customerName(int $businessId, int $customerId): string
    {
        $nameExpression = $this->customerNameExpression();

        $row = DB::table('contacts')
            ->where('business_id', $businessId)
            ->where('id', $customerId)
            ->select(DB::raw("{$nameExpression} as display_name"))
            ->first();

        return $row ? (string) $row->display_name : '';
    }

    protected function userName(?int $userId): string
    {
        if (empty($userId)) {
            return '';
        }

        $nameExpression = $this->userNameExpression();

        $row = DB::table('users')->where('id', $userId)->select(DB::raw("{$nameExpression} as display_name"))->first();

        return $row ? (string) $row->display_name : '';
    }

    /**
     * SQL expression producing a customer's display name.
     *
     * Built dynamically because `contacts` differs across tenant schema
     * versions - some have supplier_business_name, some do not. Referencing a
     * missing column directly would fail the whole query at runtime.
     */
    /**
     * Public accessor so the DataTable's search override can reuse exactly the
     * same expression the SELECT uses. Building the expression twice, once
     * here and once in the controller, is how the two quietly drift apart.
     */
    public function customerNameSql(): string
    {
        return $this->customerNameExpression();
    }

    protected function customerNameExpression(): string
    {
        $parts = [];

        if (Schema::hasColumn('contacts', 'supplier_business_name')) {
            $parts[] = "NULLIF(TRIM(COALESCE(contacts.supplier_business_name, '')), '')";
        }

        $nameParts = [];
        foreach (['prefix', 'first_name', 'middle_name', 'last_name'] as $column) {
            if (Schema::hasColumn('contacts', $column)) {
                $nameParts[] = "COALESCE(contacts.{$column}, '')";
            }
        }

        if (! empty($nameParts)) {
            $parts[] = "NULLIF(TRIM(CONCAT_WS(' ', " . implode(', ', $nameParts) . ")), '')";
        }

        if (Schema::hasColumn('contacts', 'name')) {
            $parts[] = "NULLIF(TRIM(COALESCE(contacts.name, '')), '')";
        }

        if (empty($parts)) {
            return "CONCAT('Customer #', contacts.id)";
        }

        return 'COALESCE(' . implode(', ', $parts) . ", CONCAT('Customer #', contacts.id))";
    }

    protected function userNameExpression(): string
    {
        $nameParts = [];
        foreach (['first_name', 'last_name'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $nameParts[] = "COALESCE(users.{$column}, '')";
            }
        }

        $parts = [];
        if (! empty($nameParts)) {
            $parts[] = "NULLIF(TRIM(CONCAT_WS(' ', " . implode(', ', $nameParts) . ")), '')";
        }

        if (Schema::hasColumn('users', 'username')) {
            $parts[] = "NULLIF(TRIM(COALESCE(users.username, '')), '')";
        }

        if (empty($parts)) {
            return "''";
        }

        return 'COALESCE(' . implode(', ', $parts) . ", '')";
    }
}
