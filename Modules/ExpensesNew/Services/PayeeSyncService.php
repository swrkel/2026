<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\ExpensesNew\Entities\Payee;

class PayeeSyncService
{
    public const CHEQUE_MODULE_NOT_ENABLED = 'Cheque Module Not Enabled';

    /**
     * Standalone safe sync from cheque payee tables when they exist.
     * This keeps a local expnew_payees copy, so Expenses-New does not depend on
     * Chequer or Cheque Writing at runtime.
     */
    public function syncFromChequePayees(int $businessId): int
    {
        $candidateTables = ['cheq_payees', 'cheque_payees', 'cheque_write_payees', 'cw_payees'];
        $schema = DB::getSchemaBuilder();
        $sourceTable = null;

        foreach ($candidateTables as $table) {
            if ($schema->hasTable($table)) {
                $sourceTable = $table;
                break;
            }
        }

        if (! $sourceTable) {
            return 0;
        }

        $query = DB::table($sourceTable);
        if ($schema->hasColumn($sourceTable, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        $nameColumn = $schema->hasColumn($sourceTable, 'payee_name') ? 'payee_name' : 'name';
        if (! $schema->hasColumn($sourceTable, $nameColumn)) {
            return 0;
        }

        $rows = $query->whereNotNull($nameColumn)->pluck($nameColumn);
        $count = 0;

        foreach ($rows as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            Payee::updateOrCreate(
                ['business_id' => $businessId, 'name' => $name],
                ['is_active' => 1]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Copy supplier names from the common Contacts/Suppliers master into the
     * standalone expnew_payees table. Only the local copy is used afterwards.
     */
    public function syncFromSuppliers(int $businessId): int
    {
        $rows = $this->supplierRows($businessId);
        $count = 0;

        foreach ($rows as $row) {
            $name = $this->supplierName($row);
            if ($name === '') {
                continue;
            }

            $address = $this->supplierAddress($row);

            Payee::updateOrCreate(
                ['business_id' => $businessId, 'name' => $name],
                [
                    'mobile' => $row->mobile ?? null,
                    'email' => $row->email ?? null,
                    'address' => $address !== '' ? $address : null,
                    'is_active' => 1,
                ]
            );
            $count++;
        }

        return $count;
    }

    public function supplierNames(int $businessId): Collection
    {
        return $this->supplierRows($businessId)
            ->map(fn (object $row): string => $this->supplierName($row))
            ->filter(static fn (string $name): bool => $name !== '')
            ->unique()
            ->values();
    }

    public function ensureChequeModuleFallback(int $businessId): Payee
    {
        return Payee::updateOrCreate(
            ['business_id' => $businessId, 'name' => self::CHEQUE_MODULE_NOT_ENABLED],
            ['is_active' => 1]
        );
    }

    protected function supplierRows(int $businessId): Collection
    {
        $schema = DB::getSchemaBuilder();
        if (! $schema->hasTable('contacts') || ! $schema->hasColumn('contacts', 'business_id')) {
            return collect();
        }

        $query = DB::table('contacts')->where('business_id', $businessId);

        if ($schema->hasColumn('contacts', 'type')) {
            $query->whereIn('type', ['supplier', 'both']);
        } elseif ($schema->hasColumn('contacts', 'supplier_business_name')) {
            $query->whereNotNull('supplier_business_name')
                ->where('supplier_business_name', '<>', '');
        } else {
            return collect();
        }

        if ($schema->hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($schema->hasColumn('contacts', 'contact_status')) {
            $query->where(function ($builder): void {
                $builder->where('contact_status', 'active')->orWhereNull('contact_status');
            });
        }

        $candidateColumns = [
            'supplier_business_name', 'name', 'prefix', 'first_name', 'middle_name', 'last_name',
            'mobile', 'email', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'zip_code',
        ];
        $columns = array_values(array_filter(
            $candidateColumns,
            static fn (string $column): bool => $schema->hasColumn('contacts', $column)
        ));

        return $columns === [] ? collect() : $query->get($columns);
    }

    protected function supplierName(object $row): string
    {
        $businessName = trim((string) ($row->supplier_business_name ?? ''));
        if ($businessName !== '') {
            return $businessName;
        }

        $name = trim((string) ($row->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim(implode(' ', array_filter([
            $row->prefix ?? null,
            $row->first_name ?? null,
            $row->middle_name ?? null,
            $row->last_name ?? null,
        ], static fn ($value): bool => trim((string) $value) !== '')));
    }

    protected function supplierAddress(object $row): string
    {
        return implode(', ', array_filter([
            $row->address_line_1 ?? null,
            $row->address_line_2 ?? null,
            $row->city ?? null,
            $row->state ?? null,
            $row->country ?? null,
            $row->zip_code ?? null,
        ], static fn ($value): bool => trim((string) $value) !== ''));
    }
}
