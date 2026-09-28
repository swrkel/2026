<?php

namespace Modules\Purchase\Services\Settings;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsDataService
{
    public function general(int $businessId): array
    {
        return [
            'business' => $this->pick($this->firstForBusiness('businesses', $businessId), ['id','name','currency_id','start_date','tax_number_1','tax_label_1','tax_number_2','tax_label_2','fy_start_month','accounting_method','default_sales_tax','sell_price_tax','time_zone']),
            'settings' => $this->firstForBusiness('purchase_settings', $businessId),
            'locations' => array_map(fn ($row) => $this->pick($row, ['id','name','location_id','landmark','city','state','country','zip_code','mobile','email','invoice_scheme_id','invoice_layout_id','is_active']), $this->rowsForBusiness('business_locations', $businessId, 100)),
        ];
    }

    public function numbering(int $businessId): array
    {
        $settings = $this->firstForBusiness('purchase_settings', $businessId);
        $business = $this->firstForBusiness('businesses', $businessId);

        $prefixes = [];
        foreach ([$settings, $business] as $source) {
            foreach ($source as $key => $value) {
                $lower = strtolower((string) $key);
                if (str_contains($lower, 'prefix') || str_contains($lower, 'number') || str_contains($lower, 'invoice')) {
                    $prefixes[$key] = $value;
                }
            }
        }

        return [
            'settings' => $settings,
            'numbering' => $prefixes,
            'invoice_schemes' => array_map(fn ($row) => $this->pick($row, ['id','name','scheme_type','prefix','start_number','invoice_count','total_digits','is_default']), $this->rowsForBusiness('invoice_schemes', $businessId, 100)),
        ];
    }

    public function approval(int $businessId): array
    {
        $rows = [];
        foreach (['purchase_approvals', 'purchase_approval_settings', 'purchase_approval'] as $table) {
            if (Schema::hasTable($table)) {
                $rows = $this->rowsForBusiness($table, $businessId, 200);
                if ($rows) {
                    break;
                }
            }
        }

        return [
            'settings' => $this->firstForBusiness('purchase_settings', $businessId),
            'approvals' => $rows,
        ];
    }

    public function tax(int $businessId): array
    {
        $taxes = $this->rowsForBusiness('tax_rates', $businessId, 200, function ($query, string $table): void {
            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
        });

        return [
            'settings' => $this->firstForBusiness('purchase_settings', $businessId),
            'taxes' => array_map(fn ($row) => $this->pick($row, ['id','name','amount','is_tax_group','for_tax_group','tax_type','is_active']), $taxes),
        ];
    }

    public function supplier(int $businessId): array
    {
        $suppliers = [];
        if (Schema::hasTable('contacts')) {
            $query = DB::table('contacts');
            if (Schema::hasColumn('contacts', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (Schema::hasColumn('contacts', 'type')) {
                $query->whereIn('type', ['supplier', 'both']);
            }
            if (Schema::hasColumn('contacts', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            $suppliers = $query->orderBy($this->safeOrderColumn('contacts'))->limit(200)->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'settings' => $this->firstForBusiness('purchase_settings', $businessId),
            'suppliers' => array_map(fn ($row) => $this->pick($row, ['id','contact_id','name','supplier_business_name','mobile','email','pay_term_number','pay_term_type','credit_limit','is_default']), $suppliers),
        ];
    }

    private function firstForBusiness(string $table, int $businessId): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table);
        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        } elseif ($table === 'businesses' && Schema::hasColumn($table, 'id')) {
            $query->where('id', $businessId);
        } else {
            // Never leak another business' settings in a multi-business tenant.
            return [];
        }

        $row = $query->first();
        return $row ? (array) $row : [];
    }

    private function rowsForBusiness(string $table, int $businessId, int $limit = 100, ?callable $extra = null): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table);
        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        } else {
            // Settings pages must remain strictly business-scoped.
            return [];
        }
        if ($extra) {
            $extra($query, $table);
        }

        return $query->orderBy($this->safeOrderColumn($table))->limit($limit)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function pick(array $row, array $keys): array
    {
        return array_intersect_key($row, array_flip($keys));
    }

    private function safeOrderColumn(string $table): string
    {
        foreach (['id', 'name', 'created_at'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return Schema::getColumnListing($table)[0] ?? 'id';
    }
}
