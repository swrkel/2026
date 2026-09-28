<?php
namespace Modules\Audit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FilterOptionService
{
    public function businesses(): array
    {
        try {
            if (!Schema::hasTable('business')) return [];
            return DB::table('business')->orderBy('name')->pluck('name', 'id')->toArray();
        } catch (\Throwable $e) { return []; }
    }

    public function locations(?int $businessId = null): array
    {
        try {
            $table = Schema::hasTable('business_locations') ? 'business_locations' : (Schema::hasTable('locations') ? 'locations' : null);
            if (!$table) return [];
            $q = DB::table($table);
            if ($businessId && Schema::hasColumn($table, 'business_id')) $q->where('business_id', $businessId);
            return $q->orderBy('name')->pluck('name', 'id')->toArray();
        } catch (\Throwable $e) { return []; }
    }
}
