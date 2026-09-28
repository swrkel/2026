<?php
namespace Modules\HRManager\Services;
use Illuminate\Support\Facades\DB;
class HrSafeDataService
{
    public function hasTable(string $table): bool { try { return DB::getSchemaBuilder()->hasTable($table); } catch (\Throwable $e) { return false; } }
    public function count(string $table, $businessId, array $where=[]): int {
        if (!$this->hasTable($table)) return 0;
        $q = DB::table($table)->where('business_id',$businessId);
        foreach ($where as $k=>$v) $q->where($k,$v);
        return $q->count();
    }
    public function rows(string $table, $businessId, int $limit=25) {
        if (!$this->hasTable($table)) return collect();
        return DB::table($table)->where('business_id',$businessId)->orderByDesc('id')->limit($limit)->get();
    }
    public function sum(string $table, $businessId, string $column): float {
        if (!$this->hasTable($table)) return 0;
        return (float) DB::table($table)->where('business_id',$businessId)->sum($column);
    }
}
