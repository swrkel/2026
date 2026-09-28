<?php

namespace Modules\StockTransferNew\Services\Uat;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SignoffRegisterService
{
    public function latest(): array
    {
        if (!Schema::hasTable('stn_uat_signoffs')) {
            return [];
        }

        return DB::table('stn_uat_signoffs')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function store(array $data): void
    {
        if (!Schema::hasTable('stn_uat_signoffs')) {
            return;
        }

        DB::table('stn_uat_signoffs')->insert([
            'area' => $data['area'] ?? 'General',
            'signed_by' => $data['signed_by'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'remarks' => $data['remarks'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
