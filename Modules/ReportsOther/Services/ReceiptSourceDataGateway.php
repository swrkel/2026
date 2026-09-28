<?php

namespace Modules\ReportsOther\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ReportsOther\Models\Source;
use Modules\ReportsOther\Support\CurrentScope;
use RuntimeException;

class ReceiptSourceDataGateway
{
    public function __construct(private readonly CurrentScope $scope)
    {
    }

    public function snapshot(Source $source, CarbonInterface|string $date): array
    {
        if (!hash_equals((string) $this->scope->key(), (string) $source->scope_key)) {
            throw new RuntimeException('The selected source does not belong to the current business/location/store.');
        }

        $date = $date instanceof CarbonInterface ? $date->copy() : Carbon::parse($date);
        $source->loadMissing('mappings');
        if ($source->mappings->isEmpty()) {
            return $this->unavailable('The selected Source has no mapped Product Categories/Sub Categories.');
        }

        $cfg = config('reportsother.receipt_source');
        foreach (['transactions_table', 'transaction_lines_table', 'products_table'] as $key) {
            $table = (string) ($cfg[$key] ?? '');
            if (!$this->identifier($table) || !Schema::hasTable($table)) {
                return $this->unavailable('Receipt source table is not available: '.$table);
            }
        }

        $t = (string) $cfg['transactions_table'];
        $l = (string) $cfg['transaction_lines_table'];
        $p = (string) $cfg['products_table'];
        $categoryIds = $source->mappings->where('item_type', 'category')->pluck('item_id')->map(fn ($v) => (int) $v)->values()->all();
        $subCategoryIds = $source->mappings->where('item_type', 'sub_category')->pluck('item_id')->map(fn ($v) => (int) $v)->values()->all();

        $required = [
            [$t, 'transaction_id_column'],
            [$t, 'transaction_business_column'],
            [$t, 'transaction_date_column'],
            [$l, 'line_transaction_column'],
            [$l, 'line_product_column'],
            [$p, 'product_id_column'],
        ];
        foreach ($required as [$table, $columnKey]) {
            $column = (string) ($cfg[$columnKey] ?? '');
            if (!$this->identifier($column) || !Schema::hasColumn($table, $column)) {
                return $this->unavailable('Required receipt source column is not available: '.$table.'.'.$column);
            }
        }
        if ($categoryIds) {
            $column = (string) ($cfg['product_category_column'] ?? '');
            if (!$this->identifier($column) || !Schema::hasColumn($p, $column)) {
                return $this->unavailable('Required receipt source column is not available: '.$p.'.'.$column);
            }
        }
        if ($subCategoryIds) {
            $column = (string) ($cfg['product_sub_category_column'] ?? '');
            if (!$this->identifier($column) || !Schema::hasColumn($p, $column)) {
                return $this->unavailable('Required receipt source column is not available: '.$p.'.'.$column);
            }
        }

        $lineAmount = trim((string) ($cfg['line_amount_column'] ?? ''));
        $useLineAmount = $lineAmount !== '' && $this->identifier($lineAmount) && Schema::hasColumn($l, $lineAmount);
        if (!$useLineAmount) {
            foreach (['line_quantity_column', 'line_unit_price_column'] as $columnKey) {
                $column = (string) ($cfg[$columnKey] ?? '');
                if (!$this->identifier($column) || !Schema::hasColumn($l, $column)) {
                    return $this->unavailable('Receipt amounts cannot be calculated because '.$l.'.'.$column.' is not available.');
                }
            }
        }

        $txId = (string) $cfg['transaction_id_column'];
        $txBusiness = (string) $cfg['transaction_business_column'];
        $txDate = (string) $cfg['transaction_date_column'];
        $txLocation = (string) ($cfg['transaction_location_column'] ?? '');
        $txStore = (string) ($cfg['transaction_store_column'] ?? '');
        $txType = (string) ($cfg['transaction_type_column'] ?? '');
        $txStatus = (string) ($cfg['transaction_status_column'] ?? '');
        $txContact = (string) ($cfg['transaction_contact_column'] ?? '');
        $lineTx = (string) $cfg['line_transaction_column'];
        $lineProduct = (string) $cfg['line_product_column'];
        $productId = (string) $cfg['product_id_column'];
        $productCategory = (string) $cfg['product_category_column'];
        $productSubCategory = (string) $cfg['product_sub_category_column'];

        $select = ["t.$txId as transaction_id"];
        $select[] = $categoryIds ? "p.$productCategory as category_id" : DB::raw('NULL as category_id');
        $select[] = $subCategoryIds ? "p.$productSubCategory as sub_category_id" : DB::raw('NULL as sub_category_id');
        if ($useLineAmount) {
            $select[] = "l.$lineAmount as line_amount";
        } else {
            $qty = (string) $cfg['line_quantity_column'];
            $price = (string) $cfg['line_unit_price_column'];
            $select[] = "l.$qty as line_quantity";
            $select[] = "l.$price as line_unit_price";
        }
        $hasContact = $txContact !== '' && $this->identifier($txContact) && Schema::hasColumn($t, $txContact);
        if ($hasContact) {
            $select[] = "t.$txContact as contact_id";
        }

        $query = DB::table($l.' as l')
            ->join($t.' as t', "t.$txId", '=', "l.$lineTx")
            ->join($p.' as p', "p.$productId", '=', "l.$lineProduct")
            ->select($select)
            ->where("t.$txBusiness", $this->scope->businessId())
            ->whereDate("t.$txDate", $date->toDateString())
            ->where(function ($q) use ($categoryIds, $subCategoryIds, $productCategory, $productSubCategory) {
                if ($categoryIds) {
                    $q->whereIn("p.$productCategory", $categoryIds);
                }
                if ($subCategoryIds) {
                    if ($categoryIds) {
                        $q->orWhereIn("p.$productSubCategory", $subCategoryIds);
                    } else {
                        $q->whereIn("p.$productSubCategory", $subCategoryIds);
                    }
                }
            });

        if ($this->scope->locationId() && $txLocation !== '' && $this->identifier($txLocation) && Schema::hasColumn($t, $txLocation)) {
            $query->where("t.$txLocation", $this->scope->locationId());
        }
        if ($this->scope->storeId() && $txStore !== '' && $this->identifier($txStore) && Schema::hasColumn($t, $txStore)) {
            $query->where("t.$txStore", $this->scope->storeId());
        }
        if ($txType !== '' && $this->identifier($txType) && Schema::hasColumn($t, $txType) && !empty($cfg['transaction_types'])) {
            $query->whereIn("t.$txType", $cfg['transaction_types']);
        }
        if ($txStatus !== '' && $this->identifier($txStatus) && Schema::hasColumn($t, $txStatus) && !empty($cfg['transaction_statuses'])) {
            $query->whereIn("t.$txStatus", $cfg['transaction_statuses']);
        }

        $rows = $query->get();

        $detailMap = [];
        $sort = 0;
        foreach ($source->mappings->sortBy(fn ($m) => $m->item_type.'|'.$m->item_name_snapshot) as $mapping) {
            $key = $mapping->item_type.':'.(int) $mapping->item_id;
            $detailMap[$key] = [
                'item_type' => $mapping->item_type,
                'item_id' => (int) $mapping->item_id,
                'source_detail' => (string) $mapping->item_name_snapshot,
                'amount' => 0.0,
                'sort_order' => ++$sort,
            ];
        }

        $transactionIds = [];
        $contactIds = [];
        foreach ($rows as $row) {
            $subKey = 'sub_category:'.(int) ($row->sub_category_id ?? 0);
            $categoryKey = 'category:'.(int) ($row->category_id ?? 0);
            $key = isset($detailMap[$subKey]) ? $subKey : (isset($detailMap[$categoryKey]) ? $categoryKey : null);
            if (!$key) {
                continue;
            }

            $amount = $useLineAmount
                ? (float) ($row->line_amount ?? 0)
                : ((float) ($row->line_quantity ?? 0) * (float) ($row->line_unit_price ?? 0));
            $detailMap[$key]['amount'] += $amount;
            $transactionIds[(int) $row->transaction_id] = (int) $row->transaction_id;
            if ($hasContact && is_numeric($row->contact_id ?? null) && (int) $row->contact_id > 0) {
                $contactIds[(int) $row->contact_id] = (int) $row->contact_id;
            }
        }

        $details = array_values($detailMap);
        $total = array_reduce($details, fn ($carry, $detail) => $carry + (float) $detail['amount'], 0.0);

        return [
            'available' => true,
            'message' => null,
            'details' => $details,
            'total' => $total,
            'membership_no' => $this->membershipNumber(array_values($contactIds), $cfg),
            'cheques' => $this->cheques(array_values($transactionIds), $cfg),
            'matched_transactions' => count($transactionIds),
        ];
    }

    private function membershipNumber(array $contactIds, array $cfg): ?string
    {
        if (count($contactIds) !== 1) {
            return null;
        }
        $table = trim((string) ($cfg['contacts_table'] ?? ''));
        $idColumn = trim((string) ($cfg['contact_id_column'] ?? 'id'));
        $membershipColumn = trim((string) ($cfg['contact_membership_column'] ?? ''));
        if ($table === '' || $membershipColumn === '' || !$this->identifier($table)
            || !$this->identifier($idColumn) || !$this->identifier($membershipColumn)
            || !Schema::hasTable($table) || !Schema::hasColumn($table, $idColumn)
            || !Schema::hasColumn($table, $membershipColumn)) {
            return null;
        }

        $value = DB::table($table)->where($idColumn, $contactIds[0])->value($membershipColumn);
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    private function cheques(array $transactionIds, array $cfg): array
    {
        if (!$transactionIds) {
            return [];
        }

        $table = trim((string) ($cfg['payments_table'] ?? ''));
        if ($table === '' || !$this->identifier($table) || !Schema::hasTable($table)) {
            return [];
        }

        $txColumn = (string) ($cfg['payment_transaction_column'] ?? 'transaction_id');
        $methodColumn = (string) ($cfg['payment_method_column'] ?? 'method');
        $numberColumn = (string) ($cfg['payment_cheque_number_column'] ?? 'cheque_number');
        foreach ([$txColumn, $methodColumn, $numberColumn] as $column) {
            if (!$this->identifier($column) || !Schema::hasColumn($table, $column)) {
                return [];
            }
        }

        $idColumn = (string) ($cfg['payment_id_column'] ?? 'id');
        $bankColumn = (string) ($cfg['payment_bank_column'] ?? '');
        $bankFallback = (string) ($cfg['payment_bank_fallback_column'] ?? '');
        $dateColumn = (string) ($cfg['payment_cheque_date_column'] ?? '');
        $dateFallback = (string) ($cfg['payment_cheque_date_fallback_column'] ?? '');

        $select = ["$txColumn as transaction_id", "$numberColumn as cheque_number"];
        $hasId = $this->identifier($idColumn) && Schema::hasColumn($table, $idColumn);
        if ($hasId) {
            $select[] = "$idColumn as external_payment_id";
        }
        $actualBankColumn = $this->firstExistingColumn($table, [$bankColumn, $bankFallback]);
        $actualDateColumn = $this->firstExistingColumn($table, [$dateColumn, $dateFallback]);
        if ($actualBankColumn) {
            $select[] = "$actualBankColumn as bank_name";
        }
        if ($actualDateColumn) {
            $select[] = "$actualDateColumn as cheque_date";
        }

        $query = DB::table($table)->select($select)->whereIn($txColumn, $transactionIds);
        $methods = $cfg['cheque_methods'] ?? [];
        if ($methods) {
            $query->whereIn($methodColumn, $methods);
        }

        return $query->get()->map(function ($row) {
            $date = null;
            if (!empty($row->cheque_date)) {
                try {
                    $date = Carbon::parse($row->cheque_date)->toDateString();
                } catch (\Throwable) {
                    $date = substr((string) $row->cheque_date, 0, 10);
                }
            }
            return [
                'external_payment_id' => isset($row->external_payment_id) ? (string) $row->external_payment_id : null,
                'cheque_number' => trim((string) ($row->cheque_number ?? '')) ?: null,
                'bank_name' => trim((string) ($row->bank_name ?? '')) ?: null,
                'cheque_date' => $date,
            ];
        })->filter(fn ($row) => $row['cheque_number'] || $row['bank_name'] || $row['cheque_date'])
            ->unique(fn ($row) => implode('|', [$row['external_payment_id'], $row['cheque_number'], $row['bank_name'], $row['cheque_date']]))
            ->values()->all();
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            $column = trim((string) $column);
            if ($column !== '' && $this->identifier($column) && Schema::hasColumn($table, $column)) {
                return $column;
            }
        }
        return null;
    }

    private function unavailable(string $message): array
    {
        return [
            'available' => false,
            'message' => $message,
            'details' => [],
            'total' => 0.0,
            'membership_no' => null,
            'cheques' => [],
            'matched_transactions' => 0,
        ];
    }

    private function identifier(string $value): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $value);
    }
}
