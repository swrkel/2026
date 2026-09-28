<?php

namespace Modules\ReportsOther\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ReportsOther\Support\CurrentScope;

class OrganizationGateway
{
    public function __construct(private readonly CurrentScope $scope)
    {
    }

    public function identity(): array
    {
        $cfg = config('reportsother.organisation');
        $businessName = 'Business #'.$this->scope->businessId();
        $locationName = $this->scope->locationId() ? 'Location #'.$this->scope->locationId() : 'All Locations';
        $locationAddress = '';

        $businessTable = (string) ($cfg['business_table'] ?? 'business');
        $businessIdColumn = (string) ($cfg['business_id_column'] ?? 'id');
        $businessNameColumn = (string) ($cfg['business_name_column'] ?? 'name');

        if ($this->safeTable($businessTable) && $this->safeColumn($businessIdColumn) && $this->safeColumn($businessNameColumn)
            && Schema::hasTable($businessTable) && Schema::hasColumn($businessTable, $businessIdColumn) && Schema::hasColumn($businessTable, $businessNameColumn)) {
            $value = DB::table($businessTable)
                ->where($businessIdColumn, $this->scope->businessId())
                ->value($businessNameColumn);
            if (is_string($value) && trim($value) !== '') {
                $businessName = trim($value);
            }
        }

        if ($this->scope->locationId()) {
            $locationTable = (string) ($cfg['location_table'] ?? 'business_locations');
            $locationIdColumn = (string) ($cfg['location_id_column'] ?? 'id');
            $locationBusinessColumn = (string) ($cfg['location_business_column'] ?? 'business_id');
            $locationNameColumn = (string) ($cfg['location_name_column'] ?? 'name');

            if ($this->safeTable($locationTable) && Schema::hasTable($locationTable)
                && $this->safeColumn($locationIdColumn) && Schema::hasColumn($locationTable, $locationIdColumn)) {
                $columns = [$locationIdColumn];
                if ($this->safeColumn($locationNameColumn) && Schema::hasColumn($locationTable, $locationNameColumn)) {
                    $columns[] = $locationNameColumn;
                }
                foreach (($cfg['location_address_columns'] ?? []) as $column) {
                    if ($this->safeColumn($column) && Schema::hasColumn($locationTable, $column)) {
                        $columns[] = $column;
                    }
                }

                $query = DB::table($locationTable)->select(array_values(array_unique($columns)))
                    ->where($locationIdColumn, $this->scope->locationId());
                if ($this->safeColumn($locationBusinessColumn) && Schema::hasColumn($locationTable, $locationBusinessColumn)) {
                    $query->where($locationBusinessColumn, $this->scope->businessId());
                }

                $row = $query->first();
                if ($row) {
                    if (isset($row->{$locationNameColumn}) && trim((string) $row->{$locationNameColumn}) !== '') {
                        $locationName = trim((string) $row->{$locationNameColumn});
                    }
                    $parts = [];
                    foreach (($cfg['location_address_columns'] ?? []) as $column) {
                        if (isset($row->{$column}) && trim((string) $row->{$column}) !== '') {
                            $parts[] = trim((string) $row->{$column});
                        }
                    }
                    $locationAddress = implode(', ', array_values(array_unique($parts)));
                }
            }
        }

        return [
            'business_name' => $businessName,
            'location_name' => $locationName,
            'location_address' => $locationAddress,
        ];
    }

    private function safeTable(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
    }

    private function safeColumn(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
    }
}
