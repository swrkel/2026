<?php

namespace Modules\HotelManagement\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

trait HotelTenantContext
{
    /**
     * Hotel Management contains tenant-owned operational data only.
     * Never allow a controller query to continue on the central connection.
     */
    protected function assertHotelTenantContext(): void
    {
        if (function_exists('tenancy') && !tenancy()->initialized) {
            abort(409, 'Hotel Management tenant context is not initialized.');
        }
    }
    protected function businessId(): ?int
    {
        return session('business.id') ?? session('business_id') ?? (Auth::check() ? (Auth::user()->business_id ?? null) : null);
    }

    protected function locationId(): ?int
    {
        return session('business_location_id') ?? session('location_id') ?? request()->get('business_location_id');
    }

    protected function tableBase(string $table): string
    {
        $table = trim($table);
        if (stripos($table, ' as ') !== false) { return trim(preg_split('/\s+as\s+/i', $table)[0]); }
        $parts = preg_split('/\s+/', $table);
        return trim($parts[0]);
    }

    protected function tableAlias(string $table): string
    {
        $table = trim($table);
        if (stripos($table, ' as ') !== false) { return trim(preg_split('/\s+as\s+/i', $table)[1]); }
        $parts = preg_split('/\s+/', $table);
        return count($parts) > 1 ? trim(end($parts)) : $this->tableBase($table);
    }

    protected function tableExists(string $table): bool
    {
        $this->assertHotelTenantContext();
        try { return Schema::hasTable($this->tableBase($table)); } catch (\Throwable $e) { return false; }
    }

    protected function scopedQuery(string $table)
    {
        $this->assertHotelTenantContext();
        $q = DB::table($table);
        $base = $this->tableBase($table);
        $alias = $this->tableAlias($table);
        if (Schema::hasColumn($base, 'business_id') && $this->businessId()) { $q->where($alias.'.business_id', $this->businessId()); }
        if (Schema::hasColumn($base, 'business_location_id') && $this->locationId()) { $q->where($alias.'.business_location_id', $this->locationId()); }
        if (Schema::hasColumn($base, 'deleted_at')) { $q->whereNull($alias.'.deleted_at'); }
        return $q;
    }

    protected function safeRows(string $table, int $limit = 50)
    {
        if (!$this->tableExists($table)) { return collect(); }
        return $this->scopedQuery($table)->latest('id')->limit($limit)->get();
    }

    protected function activeOptions(string $table, string $label, string $key = 'id')
    {
        if (!$this->tableExists($table)) { return collect(); }
        $q = $this->scopedQuery($table)->select($key, $label);
        if (Schema::hasColumn($table, 'status')) { $q->where('status', '!=', 'inactive'); }
        return $q->orderBy($label)->get();
    }

    protected function withScope(array $data): array
    {
        if ($this->businessId() && !array_key_exists('business_id', $data)) { $data['business_id'] = $this->businessId(); }
        if ($this->locationId() && !array_key_exists('business_location_id', $data)) { $data['business_location_id'] = $this->locationId(); }
        return $data;
    }

    protected function updateScopedRow(string $table, int $id, array $data): bool
    {
        if (!$this->tableExists($table)) { return false; }
        $data['updated_at'] = now();
        $q = $this->scopedQuery($table)->where('id', $id);
        return (bool) $q->update($data);
    }

    protected function deleteScopedRow(string $table, int $id): bool
    {
        if (!$this->tableExists($table)) { return false; }
        $q = $this->scopedQuery($table)->where('id', $id);
        if (Schema::hasColumn($table, 'deleted_at')) { return (bool) $q->update(['deleted_at' => now(), 'updated_at' => now()]); }
        return (bool) $q->delete();
    }


    protected function nextCode(string $table, string $column, string $prefix): string
    {
        $number = 1;
        try {
            if ($this->tableExists($table)) {
                $latest = $this->scopedQuery($table)->where($column, 'like', $prefix.'-%')->orderBy('id', 'desc')->value($column);
                if ($latest && preg_match('/(\d+)$/', (string) $latest, $m)) { $number = ((int) $m[1]) + 1; }
            }
        } catch (\Throwable $e) { }
        return $prefix.'-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    protected function audit(string $action, string $modelType, ?int $modelId = null, array $payload = []): void
    {
        if (!$this->tableExists('hm_audit_logs')) { return; }
        try {
            DB::table('hm_audit_logs')->insert([
                'business_id' => $this->businessId(),
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'payload' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) { }
    }
}
