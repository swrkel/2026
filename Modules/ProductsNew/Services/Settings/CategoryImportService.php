<?php

namespace Modules\ProductsNew\Services\Settings;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class CategoryImportService
{
    public function __construct(protected ProductSettingWriteService $writer)
    {
    }

    public function headers(): array
    {
        return [
            'category_name',
            'category_code',
            'category_code_is_hsn',
            'type',
            'parent_category',
            'add_related_account_at',
            'cogs_account',
            'sales_income_account',
            'weight_loss_excess_applicable',
            'vat_exempted',
            'vat_based_on',
            'apply_vat_on',
        ];
    }

    public function sampleRows(): array
    {
        return [
            [
                'Beverages',
                'BEV',
                'No',
                'Category',
                '',
                'Category Level',
                'Cost of Goods Sold',
                'Sales',
                'No',
                'No',
                'Sale Price',
                'Category / Subcategory Settings',
            ],
            [
                'Soft Drinks',
                'SOFT',
                'No',
                'Subcategory',
                'Beverages',
                'Subcategory Level',
                'Cost of Goods Sold',
                'Sales',
                'No',
                'No',
                'Sale Price',
                'Category / Subcategory Settings',
            ],
        ];
    }

    public function csvTemplate(): string
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            return implode(',', $this->headers()) . "\n";
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $this->headers());
        foreach ($this->sampleRows() as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $csv;
    }

    /**
     * @return array{created:int,skipped:int,total:int}
     */
    public function import(UploadedFile $file): array
    {
        if (!Schema::hasTable('categories')) {
            throw ValidationException::withMessages([
                'file' => 'The categories table is not available in the active tenant database.',
            ]);
        }

        $rows = $this->readRows($file);
        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'The import file is empty. Keep the heading row and add at least one category row.',
            ]);
        }

        $headers = array_map(fn ($value) => $this->normaliseHeader($value), array_shift($rows));
        $this->validateHeaders($headers);

        $records = [];
        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;
            $row = array_slice(array_pad($row, count($headers), ''), 0, count($headers));
            $record = array_combine($headers, $row) ?: [];
            $record = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $record);

            if ($this->isBlankRecord($record)) {
                continue;
            }

            $record['_row'] = $excelRow;
            $records[] = $record;
        }

        if ($records === []) {
            throw ValidationException::withMessages([
                'file' => 'No category data rows were found below the heading row.',
            ]);
        }

        // Import parent categories before subcategories, even when a user has
        // placed a subcategory above its parent in the spreadsheet.
        usort($records, function (array $a, array $b): int {
            return $this->isSubcategory($a) <=> $this->isSubcategory($b);
        });

        return DB::transaction(function () use ($records): array {
            $created = 0;
            $skipped = 0;

            foreach ($records as $record) {
                $row = (int) $record['_row'];
                $name = trim((string) ($record['category_name'] ?? ''));
                $type = $this->normaliseType($record['type'] ?? '');

                if ($name === '') {
                    $this->rowError($row, 'category_name is required.');
                }
                if (mb_strlen($name) > 191) {
                    $this->rowError($row, 'category_name may not be longer than 191 characters.');
                }
                $categoryCode = trim((string) ($record['category_code'] ?? ''));
                if (mb_strlen($categoryCode) > 50) {
                    $this->rowError($row, 'category_code may not be longer than 50 characters.');
                }
                if ($type === null) {
                    $this->rowError($row, 'type must be Category or Subcategory.');
                }

                $parentId = null;
                if ($type === 'subcategory') {
                    $parentValue = trim((string) ($record['parent_category'] ?? ''));
                    if ($parentValue === '') {
                        $this->rowError($row, 'parent_category is required for a Subcategory.');
                    }
                    $parentId = $this->resolveParentCategory($parentValue);
                    if ($parentId === null) {
                        $this->rowError($row, 'parent_category "' . $parentValue . '" was not found for this business. Import the parent category in the same file or create it first.');
                    }
                }

                if ($this->categoryAlreadyExists($name, $parentId)) {
                    $skipped++;
                    continue;
                }

                $data = [
                    'name' => $name,
                    'short_code' => $this->blankToNull($categoryCode),
                    'category_code_is_hsn' => $this->yesNo($record['category_code_is_hsn'] ?? '', false, $row, 'category_code_is_hsn'),
                    'add_as_sub_category' => $type === 'subcategory',
                    'parent_id' => $parentId,
                    'add_related_account' => $this->relatedAccountLevel($record['add_related_account_at'] ?? '', $row),
                    'cogs_account_id' => $this->resolveAccount($record['cogs_account'] ?? '', $row, 'cogs_account'),
                    'sales_income_account_id' => $this->resolveAccount($record['sales_income_account'] ?? '', $row, 'sales_income_account'),
                    'weight_excess_loss_applicable' => $this->yesNo($record['weight_loss_excess_applicable'] ?? '', false, $row, 'weight_loss_excess_applicable'),
                    'vat_exempted' => $this->yesNo($record['vat_exempted'] ?? '', false, $row, 'vat_exempted') ? 'Yes' : 'No',
                    'vat_based_on' => $this->vatBasedOn($record['vat_based_on'] ?? '', $row),
                    'apply_vat_on' => $this->applyVatOn($record['apply_vat_on'] ?? '', $row),
                ];

                try {
                    $this->writer->createCategory($data);
                } catch (ValidationException $exception) {
                    $message = collect($exception->errors())->flatten()->first() ?: $exception->getMessage();
                    $this->rowError($row, (string) $message);
                } catch (\Throwable $exception) {
                    report($exception);
                    $this->rowError($row, 'could not be imported. Please check the row values and related accounts.');
                }

                $created++;
            }

            return [
                'created' => $created,
                'skipped' => $skipped,
                'total' => count($records),
            ];
        });
    }

    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsv($file->getRealPath());
        }

        if ($extension === 'xlsx') {
            return $this->readXlsx($file->getRealPath());
        }

        throw ValidationException::withMessages([
            'file' => 'Use a .csv or .xlsx file. Old .xls files are not supported; save them as .xlsx first.',
        ]);
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded CSV file could not be opened.']);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }
        rewind($handle);

        $delimiter = $this->detectDelimiter($firstLine);
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function detectDelimiter(string $line): string
    {
        $counts = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
        ];
        arsort($counts);
        $delimiter = (string) array_key_first($counts);

        return ($counts[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }

    private function readXlsx(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages([
                'file' => 'XLSX import needs the PHP Zip extension on the server. You can use the CSV template immediately instead.',
            ]);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'The XLSX file could not be opened.']);
        }

        $sharedStrings = $this->xlsxSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw ValidationException::withMessages(['file' => 'The first worksheet could not be found in the XLSX file.']);
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false) {
            throw ValidationException::withMessages(['file' => 'The XLSX worksheet is not valid XML.']);
        }

        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        $rowNodes = $xml->xpath('//x:sheetData/x:row') ?: [];

        $mainNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

        foreach ($rowNodes as $rowNode) {
            $values = [];
            $maxIndex = -1;
            $cells = $rowNode->children($mainNamespace)->c;
            foreach ($cells as $cell) {
                $reference = (string) $cell['r'];
                $column = preg_replace('/\d+/', '', $reference) ?: 'A';
                $index = $this->columnIndex($column);
                $maxIndex = max($maxIndex, $index);
                $type = (string) $cell['t'];
                $value = '';

                if ($type === 'inlineStr') {
                    $parts = $cell->xpath('.//*[local-name()="t"]') ?: [];
                    $value = implode('', array_map(fn ($node) => (string) $node, $parts));
                } else {
                    $children = $cell->children($mainNamespace);
                    $raw = isset($children->v) ? (string) $children->v : '';
                    if ($type === 's') {
                        $value = $sharedStrings[(int) $raw] ?? '';
                    } elseif ($type === 'b') {
                        $value = $raw === '1' ? 'Yes' : 'No';
                    } else {
                        $value = $raw;
                    }
                }

                $values[$index] = $value;
            }

            if ($maxIndex < 0) {
                $rows[] = [];
                continue;
            }

            $row = array_fill(0, $maxIndex + 1, '');
            foreach ($values as $index => $value) {
                $row[$index] = $value;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $xmlString = $zip->getFromName('xl/sharedStrings.xml');
        if ($xmlString === false) {
            return [];
        }

        $xml = @simplexml_load_string($xmlString);
        if ($xml === false) {
            return [];
        }
        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $values = [];
        foreach (($xml->xpath('//x:si') ?: []) as $item) {
            $parts = $item->xpath('.//*[local-name()="t"]') ?: [];
            $values[] = implode('', array_map(fn ($node) => (string) $node, $parts));
        }

        return $values;
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split(strtoupper($letters)) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }
        return max(0, $index - 1);
    }

    private function normaliseHeader($value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $value));
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim((string) $value, '_');

        $aliases = [
            'name' => 'category_name',
            'category' => 'category_name',
            'short_code' => 'category_code',
            'level' => 'type',
            'category_type' => 'type',
            'parent' => 'parent_category',
            'parent_category_name' => 'parent_category',
            'add_related_account' => 'add_related_account_at',
            'cogs' => 'cogs_account',
            'sales_income' => 'sales_income_account',
            'sales_account' => 'sales_income_account',
            'weight_excess_loss_applicable' => 'weight_loss_excess_applicable',
        ];

        return $aliases[$value] ?? $value;
    }

    private function validateHeaders(array $headers): void
    {
        $missing = array_values(array_diff(['category_name', 'type'], $headers));
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'Missing required column heading(s): ' . implode(', ', $missing) . '.',
            ]);
        }

        if (count($headers) !== count(array_unique($headers))) {
            throw ValidationException::withMessages([
                'file' => 'The import file contains duplicate column headings.',
            ]);
        }
    }

    private function isBlankRecord(array $record): bool
    {
        foreach ($record as $key => $value) {
            if ($key !== '_row' && trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function normaliseType($value): ?string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['-', '_'], ' ', $value);
        if (in_array($value, ['category', 'parent', 'main category'], true)) {
            return 'category';
        }
        if (in_array($value, ['subcategory', 'sub category', 'child'], true)) {
            return 'subcategory';
        }
        return null;
    }

    private function isSubcategory(array $record): int
    {
        return $this->normaliseType($record['type'] ?? '') === 'subcategory' ? 1 : 0;
    }

    private function resolveParentCategory(string $value): ?int
    {
        $query = DB::table('categories')->where(function ($where) use ($value): void {
            $where->whereRaw('LOWER(name) = ?', [mb_strtolower($value)]);
            if (Schema::hasColumn('categories', 'short_code')) {
                $where->orWhereRaw('LOWER(short_code) = ?', [mb_strtolower($value)]);
            }
        });

        $this->scopeCategories($query);
        if (Schema::hasColumn('categories', 'parent_id')) {
            $query->whereRaw('COALESCE(parent_id, 0) = 0');
        }

        $row = $query->orderBy('id')->first();
        return $row ? (int) $row->id : null;
    }

    private function categoryAlreadyExists(string $name, ?int $parentId): bool
    {
        $query = DB::table('categories')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        $this->scopeCategories($query);

        if (Schema::hasColumn('categories', 'parent_id')) {
            if ($parentId === null) {
                $query->whereRaw('COALESCE(parent_id, 0) = 0');
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        return $query->exists();
    }

    private function scopeCategories($query): void
    {
        if (Schema::hasColumn('categories', 'business_id')) {
            $query->where('business_id', $this->writer->businessId());
        }
        if (Schema::hasColumn('categories', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
    }

    private function resolveAccount($value, int $row, string $column): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (!Schema::hasTable('accounts')) {
            $this->rowError($row, $column . ' was supplied but the accounts table is not available.');
        }

        $query = DB::table('accounts');
        if (is_numeric($value)) {
            $query->where('id', (int) $value);
        } else {
            if (!Schema::hasColumn('accounts', 'name')) {
                $this->rowError($row, $column . ' must use an account ID because the account name column is unavailable.');
            }
            $query->whereRaw('LOWER(name) = ?', [mb_strtolower($value)]);
        }
        if (Schema::hasColumn('accounts', 'business_id')) {
            $query->where('business_id', $this->writer->businessId());
        }
        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where(function ($where): void {
                $where->whereNull('is_closed')->orWhere('is_closed', 0);
            });
        }

        $account = $query->orderBy('id')->first();
        if (!$account) {
            $this->rowError($row, $column . ' "' . $value . '" was not found for this business.');
        }

        return (int) $account->id;
    }

    private function yesNo($value, bool $default, int $row, string $column): bool
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return $default;
        }
        if (in_array($value, ['yes', 'y', '1', 'true'], true)) {
            return true;
        }
        if (in_array($value, ['no', 'n', '0', 'false'], true)) {
            return false;
        }

        $this->rowError($row, $column . ' must be Yes or No.');
    }

    private function relatedAccountLevel($value, int $row): ?string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['-', '_'], ' ', $value);
        if ($value === '') {
            return null;
        }
        if (in_array($value, ['category', 'category level'], true)) {
            return 'category_level';
        }
        if (in_array($value, ['subcategory', 'sub category', 'subcategory level', 'sub category level'], true)) {
            return 'sub_category_level';
        }

        $this->rowError($row, 'add_related_account_at must be Category Level, Subcategory Level, or blank.');
    }

    private function vatBasedOn($value, int $row): string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['-', '_'], ' ', $value);
        if ($value === '' || in_array($value, ['sale', 'sale price', 'selling price'], true)) {
            return 'sale_price';
        }
        if (in_array($value, ['purchase', 'purchase price', 'cost price'], true)) {
            return 'purchase_price';
        }

        $this->rowError($row, 'vat_based_on must be Sale Price or Purchase Price.');
    }

    private function applyVatOn($value, int $row): string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['-', '_', '/'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        if ($value === '' || in_array($value, ['category subcategory settings', 'category settings', 'subcategory settings'], true)) {
            return 'on_product_sub_category_settings';
        }
        if (in_array($value, ['product tax settings', 'product tax settings section', 'tax settings'], true)) {
            return 'on_product_tax_settings_section';
        }

        $this->rowError($row, 'apply_vat_on must be Category / Subcategory Settings or Product Tax Settings Section.');
    }

    private function blankToNull($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function rowError(int $row, string $message): never
    {
        throw ValidationException::withMessages([
            'file' => 'Excel row ' . $row . ': ' . $message,
        ]);
    }
}
