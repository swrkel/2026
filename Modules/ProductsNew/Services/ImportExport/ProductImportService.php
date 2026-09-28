<?php

namespace Modules\ProductsNew\Services\ImportExport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\ProductsNew\Entities\ProductsNewImportLine;
use Modules\ProductsNew\Entities\ProductsNewImportSession;
use Modules\ProductsNew\Services\ProductIdentityGuard;
use Modules\ProductsNew\Services\ProductQueryService;
use Modules\ProductsNew\Services\ProductWriteService;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProductImportService
{
    public function __construct(
        protected ProductWriteService $products,
        protected ProductQueryService $listProducts,
        protected ProductsNewTenantGuard $guard,
        protected ProductIdentityGuard $identityGuard
    ) {
    }

    public function createSession(?UploadedFile $file, array $context): ProductsNewImportSession
    {
        $businessId = $this->guard->businessId(
            isset($context['business_id']) && $context['business_id'] !== ''
                ? (int) $context['business_id']
                : null
        );

        $requestedLocation = $context['business_location_id'] ?? null;
        $locationId = $this->resolveLocationId($requestedLocation, false);

        if ($this->hasValue($requestedLocation) && $locationId === null) {
            throw ValidationException::withMessages([
                'business_location_id' => 'The selected default business location is not available to this business.',
            ]);
        }

        if ($locationId === null) {
            $locationId = $this->defaultLocationId();
        }

        return ProductsNewImportSession::create([
            'business_id' => $businessId,
            'business_location_id' => $locationId,
            'file_name' => $file ? $file->getClientOriginalName() : 'manual-import.csv',
            'status' => 'draft',
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'created_by' => $this->guard->userId(),
        ]);
    }

    public function parseCsv(ProductsNewImportSession $session, UploadedFile $file): array
    {
        $this->assertSessionAccess($session);

        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded CSV file could not be opened.',
            ]);
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => 'The uploaded CSV file is empty.',
            ]);
        }

        $headers = array_map(fn ($value) => $this->normaliseHeader($value), $headers);
        $this->validateHeaders($headers);

        // A new session is normally empty. Clearing here also makes a deliberately
        // retried parse deterministic instead of appending duplicate review rows.
        ProductsNewImportLine::where('import_session_id', $session->id)->delete();

        $valid = 0;
        $invalid = 0;
        $lineNo = 1;
        $total = 0;
        $seenSkus = [];
        $seenBarcodes = [];
        $seenSingleNames = [];

        while (($row = fgetcsv($handle)) !== false) {
            $lineNo++;

            if ($this->isBlankCsvRow($row)) {
                continue;
            }

            $row = array_slice(array_pad($row, count($headers), null), 0, count($headers));
            $payload = array_combine($headers, $row) ?: [];
            $payload = collect($payload)->map(function ($value) {
                return is_string($value) ? trim($value) : $value;
            })->toArray();

            $errors = $this->validateRow($payload, $seenSkus, $seenBarcodes, $seenSingleNames);
            $skuKey = $this->identityGuard->normaliseLookupKey($payload['sku'] ?? null);
            if ($skuKey !== null) {
                $seenSkus[$skuKey] = true;
            }
            $barcodeKey = $this->identityGuard->normaliseLookupKey($payload['barcode'] ?? null);
            if ($barcodeKey !== null) {
                $seenBarcodes[$barcodeKey] = true;
            }
            $name = trim((string) ($payload['product_name'] ?? ''));
            if ($name !== '') {
                $seenSingleNames[$this->identityGuard->normaliseNameKey($name)] = true;
            }

            $total++;
            $errors ? $invalid++ : $valid++;

            ProductsNewImportLine::create([
                'import_session_id' => $session->id,
                'line_no' => $lineNo,
                'sku' => $payload['sku'] ?? null,
                'barcode' => $payload['barcode'] ?? null,
                'product_name' => $payload['product_name'] ?? null,
                'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'validation_errors' => $errors
                    ? json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null,
                'status' => $errors ? 'invalid' : 'valid',
            ]);
        }

        fclose($handle);

        $session->update([
            'total_rows' => $total,
            'valid_rows' => $valid,
            'invalid_rows' => $invalid,
            'status' => 'validated',
        ]);

        return compact('valid', 'invalid');
    }

    /**
     * @param array<string,bool> $seenSkus
     * @param array<string,bool> $seenBarcodes
     * @param array<string,bool> $seenSingleNames
     */
    public function validateRow(
        array $row,
        array $seenSkus = [],
        array $seenBarcodes = [],
        array $seenSingleNames = []
    ): array
    {
        $errors = [];
        $name = trim((string) ($row['product_name'] ?? ''));
        $sku = trim((string) ($row['sku'] ?? ''));

        if ($name === '') {
            $errors[] = 'Product name is required';
        }
        if ($sku === '') {
            $errors[] = 'SKU is required';
        }
        if (isset($row['purchase_price']) && $row['purchase_price'] !== '' && ! $this->isNumeric($row['purchase_price'])) {
            $errors[] = 'Purchase price must be numeric';
        }
        if (isset($row['purchase_price_inc_tax']) && $row['purchase_price_inc_tax'] !== '' && ! $this->isNumeric($row['purchase_price_inc_tax'])) {
            $errors[] = 'Purchase price including tax must be numeric';
        }
        if (isset($row['selling_price']) && $row['selling_price'] !== '' && ! $this->isNumeric($row['selling_price'])) {
            $errors[] = 'Selling price must be numeric';
        }
        if (isset($row['selling_price_inc_tax']) && $row['selling_price_inc_tax'] !== '' && ! $this->isNumeric($row['selling_price_inc_tax'])) {
            $errors[] = 'Selling price including tax must be numeric';
        }
        if (isset($row['profit_percent']) && $row['profit_percent'] !== '' && ! $this->isNumeric($row['profit_percent'])) {
            $errors[] = 'Profit percent must be numeric';
        }
        if (isset($row['alert_quantity']) && $row['alert_quantity'] !== '' && ! $this->isNumeric($row['alert_quantity'])) {
            $errors[] = 'Alert quantity must be numeric';
        }

        $taxType = strtolower(trim((string) ($row['tax_type'] ?? 'exclusive')));
        if ($taxType !== '' && ! in_array($taxType, ['exclusive', 'inclusive'], true)) {
            $errors[] = 'Tax type must be exclusive or inclusive';
        }

        $status = strtolower(trim((string) ($row['status'] ?? 'active')));
        if ($status !== '' && ! in_array($status, ['active', 'inactive'], true)) {
            $errors[] = 'Status must be active or inactive';
        }

        $skuKey = $this->identityGuard->normaliseLookupKey($sku);
        if ($skuKey !== null) {
            if (isset($seenSkus[$skuKey])) {
                $errors[] = 'SKU is duplicated in this import file';
            } elseif ($this->identityGuard->findSkuConflict($sku) !== null) {
                $errors[] = 'SKU already exists for this business';
            }
        }

        $barcode = trim((string) ($row['barcode'] ?? ''));
        $barcodeKey = $this->identityGuard->normaliseLookupKey($barcode);
        if ($barcodeKey !== null) {
            if (isset($seenBarcodes[$barcodeKey])) {
                $errors[] = 'Barcode is duplicated in this import file';
            } elseif ($this->identityGuard->findBarcodeConflict($barcode) !== null) {
                $errors[] = 'Barcode already exists for this business';
            }
        }

        if ($name !== '') {
            $nameKey = $this->identityGuard->normaliseNameKey($name);
            if (isset($seenSingleNames[$nameKey])) {
                $errors[] = 'Single product name is duplicated in this import file';
            } elseif ($this->identityGuard->findSingleNameConflict($name) !== null) {
                $errors[] = 'This Single product already exists for this business; edit the existing product instead';
            }
        }

        if ($this->hasValue($row['category'] ?? null)
            && $this->resolveNamedId('categories', $row['category'], ['name'], true, false) === null) {
            $errors[] = 'Category was not found for this business';
        }

        if ($this->hasValue($row['brand'] ?? null)
            && $this->resolveNamedId('brands', $row['brand'], ['name']) === null) {
            $errors[] = 'Brand was not found for this business';
        }

        if ($this->hasValue($row['unit'] ?? null)
            && $this->resolveNamedId('units', $row['unit'], ['short_name', 'actual_name']) === null) {
            $errors[] = 'Unit was not found for this business';
        }

        if ($this->hasValue($row['business_location'] ?? null)
            && $this->resolveLocationId($row['business_location'], false) === null) {
            $errors[] = 'Business location was not found or is not available to this business';
        }

        return array_values(array_unique($errors));
    }

    /**
     * Actually write validated import rows into the shared product master.
     *
     * Older Products New builds only changed the review row from "valid" to
     * "imported" and never created a row in products. For that reason this
     * method intentionally also accepts historical lines already marked
     * "imported": clicking Commit again repairs those old phantom imports.
     *
     * @return array{created:int,already_present:int,total:int}
     */
    public function commit(ProductsNewImportSession $session): array
    {
        $this->assertSessionAccess($session, true);

        if ((int) $session->invalid_rows > 0) {
            throw ValidationException::withMessages([
                'import' => 'This import still contains invalid rows. Correct the CSV and validate it again before committing.',
            ]);
        }

        return DB::transaction(function () use ($session): array {
            $created = 0;
            $alreadyPresent = 0;

            $lines = ProductsNewImportLine::where('import_session_id', $session->id)
                ->whereIn('status', ['valid', 'imported'])
                ->orderBy('line_no')
                ->lockForUpdate()
                ->get();

            foreach ($lines as $line) {
                $payload = json_decode((string) $line->payload_json, true);
                $payload = is_array($payload) ? $payload : [];
                $sku = trim((string) ($payload['sku'] ?? $line->sku ?? ''));

                if ($sku === '') {
                    throw ValidationException::withMessages([
                        'import' => 'Import line ' . $line->line_no . ' has no SKU and cannot be committed.',
                    ]);
                }

                $existing = $this->findProductBySku($sku);

                // Historical rows from the broken importer were already marked
                // imported. If a matching product really exists now, the repair
                // is idempotent and must not create a duplicate.
                if ($existing !== null && $line->status === 'imported') {
                    $this->repairImportedProductVisibility(
                        (int) $existing->id,
                        $payload,
                        $session->business_location_id ? (int) $session->business_location_id : null
                    );

                    if ($this->listProducts->findForView((int) $existing->id, true) === null) {
                        throw ValidationException::withMessages([
                            'import' => 'Previously imported SKU "' . $sku . '" exists in the product master but is still not visible to List Products. No duplicate was created.',
                        ]);
                    }

                    $alreadyPresent++;
                    continue;
                }

                // A current, freshly validated row must never overwrite a real
                // product that appeared after validation.
                if ($existing !== null) {
                    throw ValidationException::withMessages([
                        'import' => 'Import line ' . $line->line_no . ': SKU "' . $sku . '" already exists for this business.',
                    ]);
                }

                $data = $this->toProductData($payload, (int) $line->line_no, $session->business_location_id ? (int) $session->business_location_id : null);
                $product = $this->products->create($data);

                // A raw products row is not enough: verify through the exact
                // Products New List Products query before declaring success.
                if ($this->listProducts->findForView((int) $product->id, true) === null) {
                    throw ValidationException::withMessages([
                        'import' => 'Import line ' . $line->line_no . ' created product ID ' . $product->id . ' but it is not visible to the current List Products query. The import was rolled back to avoid a phantom success.',
                    ]);
                }

                $line->update([
                    'status' => 'imported',
                    'validation_errors' => null,
                    // Keep the original CSV payload and append a harmless trace
                    // key without requiring a database schema change.
                    'payload_json' => json_encode(
                        array_merge($payload, ['_imported_product_id' => (int) $product->id]),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                ]);

                $created++;
            }

            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'valid_rows' => ProductsNewImportLine::where('import_session_id', $session->id)
                    ->whereIn('status', ['valid', 'imported'])
                    ->count(),
            ]);

            return [
                'created' => $created,
                'already_present' => $alreadyPresent,
                'total' => $created + $alreadyPresent,
            ];
        });
    }

    public function findSessionForCurrentBusiness(int $sessionId, bool $allowHistoricalNullBusiness = false): ProductsNewImportSession
    {
        $session = ProductsNewImportSession::query()->findOrFail($sessionId);
        $this->assertSessionAccess($session, $allowHistoricalNullBusiness);

        return $session;
    }

    public function assertSessionAccess(ProductsNewImportSession $session, bool $allowHistoricalNullBusiness = false): void
    {
        $businessId = $this->guard->businessId();
        $sessionBusinessId = (int) ($session->business_id ?? 0);

        if ($sessionBusinessId > 0 && $sessionBusinessId !== $businessId) {
            throw new AccessDeniedHttpException('This import session belongs to another business.');
        }

        if ($sessionBusinessId === 0) {
            // Old sessions created by the previous importer had business_id NULL.
            // Permit repair only to the same user who created that session. This
            // keeps multi-business data separated while still repairing the bug.
            $createdBy = (int) ($session->created_by ?? 0);
            $currentUser = (int) ($this->guard->userId() ?? 0);

            if (! $allowHistoricalNullBusiness || $createdBy <= 0 || $createdBy !== $currentUser) {
                throw new AccessDeniedHttpException('This legacy import session has no business ownership and cannot be opened from this business.');
            }

            $session->update(['business_id' => $businessId]);
        }
    }

    protected function toProductData(array $row, int $lineNo, ?int $defaultLocationId = null): array
    {
        $categoryId = $this->resolveNamedId('categories', $row['category'] ?? null, ['name'], true, true);
        $brandId = $this->resolveNamedId('brands', $row['brand'] ?? null, ['name'], false, true);
        $unitId = $this->resolveNamedId('units', $row['unit'] ?? null, ['short_name', 'actual_name'], false, true);
        $locationValue = $row['business_location'] ?? null;
        $locationId = $this->hasValue($locationValue)
            ? $this->resolveLocationId($locationValue, true)
            : ($defaultLocationId ?: $this->defaultLocationId());

        $taxType = strtolower(trim((string) ($row['tax_type'] ?? 'exclusive')));
        if ($taxType === '') {
            $taxType = 'exclusive';
        }

        $status = strtolower(trim((string) ($row['status'] ?? 'active')));
        if ($status === '') {
            $status = 'active';
        }

        $purchasePrice = $this->numericValue($row['purchase_price'] ?? null);
        $purchasePriceIncTax = $this->numericValue($row['purchase_price_inc_tax'] ?? null);
        if ($purchasePriceIncTax === null) {
            // Backward compatible with existing CSV templates: when only one
            // purchase cost is supplied, treat it as the inclusive stock value.
            $purchasePriceIncTax = $purchasePrice;
        }

        $data = [
            'name' => trim((string) ($row['product_name'] ?? '')),
            'sku' => trim((string) ($row['sku'] ?? '')),
            'barcode' => $this->nullableString($row['barcode'] ?? null),
            'type' => 'single',
            'unit_id' => $unitId,
            'brand_id' => $brandId,
            'category_id' => $categoryId,
            'sub_category_id' => null,
            'tax_type' => $taxType,
            'alert_quantity' => $this->numericValue($row['alert_quantity'] ?? null),
            'enable_stock' => 1,
            'vat_claimed' => 0,
            'not_for_selling' => $status === 'inactive' ? 1 : 0,
            'is_inactive' => $status === 'inactive' ? 1 : 0,
            'products_new_status' => $status,
            'single_dpp' => $purchasePrice,
            'single_dpp_inc_tax' => $purchasePriceIncTax,
            'profit_percent' => $this->numericValue($row['profit_percent'] ?? null),
            'single_dsp' => $this->numericValue($row['selling_price'] ?? null),
            'single_dsp_inc_tax' => $this->numericValue($row['selling_price_inc_tax'] ?? null),
            'product_description' => $this->nullableString($row['product_description'] ?? null),
            'product_locations_present' => 1,
            'product_locations' => $locationId ? [$locationId] : [],
        ];

        if ($data['name'] === '' || $data['sku'] === '') {
            throw ValidationException::withMessages([
                'import' => 'Import line ' . $lineNo . ' is missing the product name or SKU.',
            ]);
        }

        return $data;
    }

    protected function validateHeaders(array $headers): void
    {
        $required = ['product_name', 'sku'];
        $missing = array_values(array_diff($required, $headers));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'The CSV is missing required column(s): ' . implode(', ', $missing) . '.',
            ]);
        }
    }

    protected function normaliseHeader($header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?: '';

        return trim($header, '_');
    }

    protected function resolveNamedId(
        string $table,
        $value,
        array $nameColumns,
        bool $topLevelCategoryOnly = false,
        bool $throwIfMissing = false
    ): ?int {
        if (! $this->hasValue($value)) {
            return null;
        }

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            if ($throwIfMissing) {
                throw ValidationException::withMessages([
                    'import' => 'The required master table "' . $table . '" is missing.',
                ]);
            }
            return null;
        }

        $query = DB::table($table);
        if (Schema::hasColumn($table, 'business_id')) {
            $this->guard->applyBusiness($query, $table . '.business_id');
        }
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }
        if ($topLevelCategoryOnly && Schema::hasColumn($table, 'parent_id')) {
            $query->whereRaw('COALESCE(' . $table . '.parent_id, 0) = 0');
        }

        $text = trim((string) $value);
        if (ctype_digit($text)) {
            $byId = (clone $query)->where($table . '.id', (int) $text)->value($table . '.id');
            if ($byId !== null) {
                return (int) $byId;
            }
        }

        $availableColumns = array_values(array_filter(
            $nameColumns,
            fn (string $column): bool => Schema::hasColumn($table, $column)
        ));

        if ($availableColumns !== []) {
            $named = (clone $query)->where(function ($where) use ($table, $availableColumns, $text): void {
                foreach ($availableColumns as $index => $column) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $where->{$method}('LOWER(TRIM(' . $table . '.`' . $column . '`)) = ?', [mb_strtolower($text)]);
                }
            })->value($table . '.id');

            if ($named !== null) {
                return (int) $named;
            }
        }

        if ($throwIfMissing) {
            throw ValidationException::withMessages([
                'import' => 'Import master value "' . $text . '" was not found in ' . $table . ' for this business.',
            ]);
        }

        return null;
    }

    protected function resolveLocationId($value, bool $throwIfMissing): ?int
    {
        if (! $this->hasValue($value)) {
            return null;
        }

        if (! Schema::hasTable('business_locations') || ! Schema::hasColumn('business_locations', 'id')) {
            if ($throwIfMissing) {
                throw ValidationException::withMessages([
                    'import' => 'The business locations table is missing.',
                ]);
            }
            return null;
        }

        $query = DB::table('business_locations');
        if (Schema::hasColumn('business_locations', 'business_id')) {
            $this->guard->applyBusiness($query, 'business_locations.business_id');
        }
        if (Schema::hasColumn('business_locations', 'deleted_at')) {
            $query->whereNull('business_locations.deleted_at');
        }

        $text = trim((string) $value);
        if (ctype_digit($text)) {
            $id = (clone $query)->where('id', (int) $text)->value('id');
            if ($id !== null) {
                return (int) $id;
            }
        }

        if (Schema::hasColumn('business_locations', 'name')) {
            $id = (clone $query)
                ->whereRaw('LOWER(TRIM(`name`)) = ?', [mb_strtolower($text)])
                ->value('id');
            if ($id !== null) {
                return (int) $id;
            }
        }

        if ($throwIfMissing) {
            throw ValidationException::withMessages([
                'import' => 'Business location "' . $text . '" was not found for this business.',
            ]);
        }

        return null;
    }

    protected function repairImportedProductVisibility(int $productId, array $row, ?int $defaultLocationId = null): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $status = strtolower(trim((string) ($row['status'] ?? 'active')));
        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $update = [];
        if (Schema::hasColumn('products', 'not_for_selling')) {
            $update['not_for_selling'] = $status === 'inactive' ? 1 : 0;
        }
        if (Schema::hasColumn('products', 'is_inactive')) {
            $update['is_inactive'] = $status === 'inactive' ? 1 : 0;
        }
        if (Schema::hasColumn('products', 'products_new_status')) {
            $update['products_new_status'] = $status;
        }
        if ($update !== []) {
            DB::table('products')->where('id', $productId)->update($update);
        }

        if (! Schema::hasTable('product_locations')
            || ! Schema::hasColumn('product_locations', 'product_id')
            || ! Schema::hasColumn('product_locations', 'location_id')) {
            return;
        }

        $hasLocation = DB::table('product_locations')->where('product_id', $productId)->exists();
        if ($hasLocation) {
            return;
        }

        $locationValue = $row['business_location'] ?? null;
        $locationId = $this->hasValue($locationValue)
            ? $this->resolveLocationId($locationValue, false)
            : ($defaultLocationId ?: $this->defaultLocationId());

        if ($locationId) {
            DB::table('product_locations')->insert([
                'product_id' => $productId,
                'location_id' => $locationId,
            ]);
        }
    }

    protected function defaultLocationId(): ?int
    {
        if (! Schema::hasTable('business_locations') || ! Schema::hasColumn('business_locations', 'id')) {
            return null;
        }

        $query = DB::table('business_locations');
        if (Schema::hasColumn('business_locations', 'business_id')) {
            $this->guard->applyBusiness($query, 'business_locations.business_id');
        }
        if (Schema::hasColumn('business_locations', 'deleted_at')) {
            $query->whereNull('business_locations.deleted_at');
        }

        $allowed = $this->guard->allowedLocationIds();
        if ($allowed === []) {
            $query->whereRaw('1 = 0');
        } elseif ($allowed !== ['all']) {
            $query->whereIn('business_locations.id', array_map('intval', $allowed));
        }

        $id = $query->orderBy('business_locations.id')->value('business_locations.id');

        return $id !== null ? (int) $id : null;
    }

    protected function productSkuExists(string $sku): bool
    {
        return $this->findProductBySku($sku) !== null;
    }

    protected function findProductBySku(string $sku): ?object
    {
        return $this->identityGuard->findSkuConflict($sku);
    }

    protected function hasValue($value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    protected function nullableString($value): ?string
    {
        return $this->hasValue($value) ? trim((string) $value) : null;
    }

    protected function numericValue($value): ?float
    {
        if (! $this->hasValue($value)) {
            return null;
        }

        $normalised = str_replace(',', '', trim((string) $value));

        return is_numeric($normalised) ? (float) $normalised : null;
    }

    protected function isNumeric($value): bool
    {
        return $this->numericValue($value) !== null;
    }

    protected function normaliseLookup($value): ?string
    {
        if (! $this->hasValue($value)) {
            return null;
        }

        return mb_strtolower(trim((string) $value));
    }

    protected function isBlankCsvRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->hasValue($value)) {
                return false;
            }
        }

        return true;
    }
}
