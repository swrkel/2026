<?php
namespace Modules\ProductsNew\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\ProductsNew\Entities\ProductsNewOpeningStockSession;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class OpeningStockImportService
{
    private const HEADERS = ['product_id', 'sku', 'variation_id', 'variation_sku', 'quantity', 'unit_cost', 'notes'];

    public function __construct(
        protected ProductsNewTenantGuard $guard,
        protected OpeningStockService $openingStock,
        protected ProductStatusService $status
    ) {}

    public function templateCsv(): string
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADERS);
        fputcsv($stream, ['', 'EXAMPLE-SKU', '', '', '10.000', '125.5000', 'Optional note']);
        rewind($stream);
        return stream_get_contents($stream);
    }

    public function import(ProductsNewOpeningStockSession $session, UploadedFile $file): array
    {
        $this->assertSessionBusiness($session);
        if (!$session->location_id) {
            throw ValidationException::withMessages([
                'opening_stock_file' => 'Select a business location when creating the opening stock session before importing.',
            ]);
        }

        $hash = hash_file('sha256', $file->getRealPath());
        if (Schema::hasColumn('products_new_opening_stock_sessions', 'import_hash') && $session->import_hash === $hash) {
            throw ValidationException::withMessages([
                'opening_stock_file' => 'This exact file has already been imported into this opening stock session.',
            ]);
        }

        $rows = $this->parseAndValidate($file);
        if (!$rows) {
            throw ValidationException::withMessages(['opening_stock_file' => 'The CSV file contains no opening stock rows.']);
        }

        $totalQty = 0.0;
        DB::transaction(function () use ($session, $rows, $hash, &$totalQty) {
            foreach ($rows as $row) {
                $this->openingStock->postLine($session, [
                    'product_id' => $row['product_id'],
                    'variation_id' => $row['variation_id'],
                    'qty' => $row['quantity'],
                    'unit_cost' => $row['unit_cost'],
                    'notes' => trim(($row['notes'] ?: 'Imported opening stock') . ' [CSV line ' . $row['line'] . ']'),
                ]);
                $totalQty += $row['quantity'];
            }

            $updates = ['status' => 'posted', 'updated_at' => now()];
            if (Schema::hasColumn('products_new_opening_stock_sessions', 'import_hash')) $updates['import_hash'] = $hash;
            if (Schema::hasColumn('products_new_opening_stock_sessions', 'imported_lines')) $updates['imported_lines'] = count($rows);
            if (Schema::hasColumn('products_new_opening_stock_sessions', 'imported_at')) $updates['imported_at'] = now();
            DB::table('products_new_opening_stock_sessions')->where('id', $session->id)->update($updates);
        });

        return ['lines' => count($rows), 'qty' => $totalQty];
    }

    private function parseAndValidate(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            throw ValidationException::withMessages(['opening_stock_file' => 'The uploaded CSV file could not be read.']);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw ValidationException::withMessages(['opening_stock_file' => 'The CSV header row is missing.']);
        }
        $header = array_map(fn ($v) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $v))), $header);
        $missing = array_diff(self::HEADERS, $header);
        if ($missing) {
            fclose($handle);
            throw ValidationException::withMessages([
                'opening_stock_file' => 'Missing CSV columns: ' . implode(', ', $missing) . '. Download and use the provided template.',
            ]);
        }

        $index = array_flip($header);
        $rows = [];
        $errors = [];
        $line = 1;
        while (($csv = fgetcsv($handle)) !== false) {
            $line++;
            if (!array_filter($csv, fn ($v) => trim((string) $v) !== '')) continue;
            $value = fn ($key) => trim((string) ($csv[$index[$key]] ?? ''));

            $productId = $this->resolveProduct($value('product_id'), $value('sku'));
            $variationId = $this->resolveVariation($productId, $value('variation_id'), $value('variation_sku'));
            $qty = filter_var($value('quantity'), FILTER_VALIDATE_FLOAT);
            $costRaw = $value('unit_cost');
            $cost = $costRaw === '' ? 0.0 : filter_var($costRaw, FILTER_VALIDATE_FLOAT);

            if (!$productId) $errors[] = "Line {$line}: product was not found by product_id or SKU.";
            if ($qty === false || $qty <= 0) $errors[] = "Line {$line}: quantity must be greater than zero.";
            if ($cost === false || $cost < 0) $errors[] = "Line {$line}: unit_cost must be zero or greater.";
            if (($value('variation_id') !== '' || $value('variation_sku') !== '') && !$variationId) {
                $errors[] = "Line {$line}: variation was not found for the selected product.";
            }

            if ($productId && $qty !== false && $qty > 0 && $cost !== false && $cost >= 0) {
                $rows[] = [
                    'line' => $line,
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'quantity' => (float) $qty,
                    'unit_cost' => (float) $cost,
                    'notes' => mb_substr($value('notes'), 0, 500),
                ];
            }
        }
        fclose($handle);

        if ($errors) {
            throw ValidationException::withMessages([
                'opening_stock_file' => array_slice($errors, 0, 25),
            ]);
        }
        return $rows;
    }

    private function resolveProduct(string $id, string $sku): ?int
    {
        $query = DB::table('products')
            ->where('business_id', $this->guard->businessId());

        $this->status->applyActiveOnly($query, 'products');

        if ($id !== '') {
            return (int) (clone $query)->where('id', (int) $id)->value('id') ?: null;
        }

        if ($sku !== '') {
            return (int) (clone $query)->where('sku', $sku)->value('id') ?: null;
        }

        return null;
    }

    private function resolveVariation(int $productId, string $id, string $sku): ?int
    {
        if ($id === '' && $sku === '') return null;
        $q = DB::table('variations')->where('product_id', $productId);
        if ($id !== '') return (int) $q->where('id', (int) $id)->value('id') ?: null;
        if ($sku !== '' && Schema::hasColumn('variations', 'sub_sku')) return (int) $q->where('sub_sku', $sku)->value('id') ?: null;
        return null;
    }

    private function assertSessionBusiness(ProductsNewOpeningStockSession $session): void
    {
        if ((int) $session->business_id !== (int) $this->guard->businessId()) abort(404);
    }
}
