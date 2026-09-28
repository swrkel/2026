<?php

namespace Modules\Membership\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\Membership\Entities\PrefixStartingNumber;

class PrefixStartingNumberService
{
    private function hasColumn(string $column): bool
    {
        try {
            return Schema::hasColumn('membership_settings', $column);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listForBusiness(int $businessId)
    {
        return PrefixStartingNumber::where('business_id', $businessId)
            ->with('createdBy:id,username,first_name,last_name')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getForBusiness(int $id, int $businessId): PrefixStartingNumber
    {
        return PrefixStartingNumber::where('id', $id)
            ->where('business_id', $businessId)
            ->with('createdBy:id,username,first_name,last_name')
            ->firstOrFail();
    }

    public function existsRegion(int $businessId, string $region, ?int $ignoreId = null): bool
    {
        $query = PrefixStartingNumber::where('business_id', $businessId);

        if ($this->hasColumn('region')) {
            $query->where('region', $region);
        } else {
            return false;
        }

        if (! empty($ignoreId)) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function create(int $businessId, array $data): PrefixStartingNumber
    {
        $payload = [
            'business_id'     => $businessId,
            'prefix'          => $data['prefix'] ?? null,
            'starting_number' => (int) $data['starting_number'],
        ];

        if ($this->hasColumn('region')) {
            $payload['region'] = $data['region'];
        }
        if ($this->hasColumn('next_sequence')) {
            $payload['next_sequence'] = (int) $data['starting_number'];
        }
        if ($this->hasColumn('created_by')) {
            $payload['created_by'] = Auth::id();
        }

        return PrefixStartingNumber::create($payload);
    }

    public function update(PrefixStartingNumber $setting, array $data): PrefixStartingNumber
    {
        $payload = [
            'prefix'          => $data['prefix'] ?? null,
            'starting_number' => (int) $data['starting_number'],
        ];

        if ($this->hasColumn('region')) {
            $payload['region'] = $data['region'];
        }

        $setting->update($payload);

        return $setting->fresh();
    }

    public function delete(PrefixStartingNumber $setting): void
    {
        $setting->delete();
    }
}
