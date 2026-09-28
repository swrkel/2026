<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\LeadsNew\Models\LeadsNewLead;

class LeadsNewBulkService
{
    public function assign(array $leadIds, int $userId, int $actorId): int
    {
        return DB::transaction(function () use ($leadIds, $userId, $actorId) {
            return LeadsNewLead::query()
                ->whereIn('id', $leadIds)
                ->update(['assigned_to' => $userId, 'updated_by' => $actorId, 'updated_at' => now()]);
        });
    }

    public function updateStatus(array $leadIds, string $status, int $actorId): int
    {
        return DB::transaction(function () use ($leadIds, $status, $actorId) {
            return LeadsNewLead::query()
                ->whereIn('id', $leadIds)
                ->update(['status' => $status, 'updated_by' => $actorId, 'updated_at' => now()]);
        });
    }
}
