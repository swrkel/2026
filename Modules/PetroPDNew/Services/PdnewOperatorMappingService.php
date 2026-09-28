<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Entities\PdnewOperatorMapping;
use RuntimeException;

class PdnewOperatorMappingService
{
    public function syncFromPoneProfile(int $businessId, mixed $profile): PdnewOperatorMapping
    {
        $operator = (array) $profile;
        $sourceBusinessId = (int) ($operator['business_id'] ?? 0);

        if ($businessId <= 0 || ($sourceBusinessId > 0 && $sourceBusinessId !== $businessId)) {
            throw new RuntimeException('The Pumper Dashboard-New operator does not belong to the active business.');
        }

        return $this->syncFromSnapshot($businessId, [
            'shift' => [
                'operator_profile_id' => (int) ($operator['id'] ?? 0),
                'location_id' => (int) ($operator['location_id'] ?? 0) ?: null,
                'pd_operator_id' => (int) ($operator['pd_operator_id'] ?? 0),
            ],
            'operator' => $operator,
        ]);
    }

    public function syncFromSnapshot(int $businessId, array $snapshot): PdnewOperatorMapping
    {
        $shift = (array) ($snapshot['shift'] ?? []);
        $operator = (array) ($snapshot['operator'] ?? []);
        $profileId = (int) ($shift['operator_profile_id'] ?? $operator['id'] ?? 0);
        $sourceBusinessId = (int) ($operator['business_id'] ?? 0);

        if ($businessId <= 0 || ($sourceBusinessId > 0 && $sourceBusinessId !== $businessId)) {
            throw new RuntimeException('The Pumper Dashboard-New operator does not belong to the active business.');
        }
        if ($profileId <= 0) {
            throw new RuntimeException('The Pumper Dashboard-New source does not contain a valid operator profile.');
        }

        return DB::transaction(function () use ($businessId, $shift, $operator, $profileId): PdnewOperatorMapping {
            $now = now();
            $locationId = (int) ($shift['location_id'] ?? $operator['location_id'] ?? 0) ?: null;
            $sourceStatus = $this->normaliseStatus($operator);
            $pdOperatorId = (int) ($shift['pd_operator_id'] ?? $operator['pd_operator_id'] ?? 0);
            $userId = (int) ($operator['user_id'] ?? 0) ?: null;

            $mapping = PdnewOperatorMapping::query()
                ->where('business_id', $businessId)
                ->where('pone_operator_profile_id', $profileId)
                ->lockForUpdate()
                ->first();

            $existingSettings = $mapping ? (array) $mapping->settings : [];
            $localOverrides = (array) data_get($existingSettings, 'local_overrides', []);
            $settings = $this->settings($operator);
            $settings['local_overrides'] = $localOverrides;
            $settings['source_status'] = $sourceStatus;
            $settings['source_login_enabled'] = (bool) ($operator['login_enabled'] ?? false);
            $settings['source_operator_profile_id'] = $profileId;
            $settings['source_pd_operator_id'] = $pdOperatorId;

            if (! $mapping) {
                $mapping = new PdnewOperatorMapping();
                $mapping->business_id = $businessId;
                $mapping->pone_operator_profile_id = $profileId;
                $mapping->status = $sourceStatus;
                $mapping->created_at = $now;
            }

            $mapping->location_id = $locationId;
            $mapping->pone_pd_operator_id = $pdOperatorId;
            $mapping->user_id = $userId;
            $mapping->display_name = trim((string) ($localOverrides['display_name'] ?? ''))
                ?: $this->displayName($operator, $profileId);
            $mapping->settings = $settings;
            $mapping->last_synced_at = $now;
            $mapping->updated_at = $now;
            $mapping->save();

            return $mapping->fresh();
        }, 3);
    }

    public function updateLocalSettings(PdnewOperatorMapping $mapping, array $data): PdnewOperatorMapping
    {
        return DB::transaction(function () use ($mapping, $data): PdnewOperatorMapping {
            $mapping = PdnewOperatorMapping::query()
                ->where('business_id', $mapping->business_id)
                ->whereKey($mapping->id)
                ->lockForUpdate()
                ->firstOrFail();

            $settings = (array) $mapping->settings;
            if (array_key_exists('settings', $data)) {
                $settings = array_merge($settings, (array) ($data['settings'] ?? []));
            }

            $mapping->status = (string) ($data['status'] ?? $mapping->status);
            $mapping->settings = $settings;
            $mapping->save();

            return $mapping->fresh();
        }, 3);
    }

    private function displayName(array $operator, int $profileId): string
    {
        foreach (['display_name', 'name', 'operator_name'] as $key) {
            $value = trim((string) ($operator[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'Operator #' . $profileId;
    }

    private function normaliseStatus(array $operator): string
    {
        $value = strtolower(trim((string) ($operator['status'] ?? 'active')));

        return in_array($value, ['1', 'active', 'enabled'], true) ? 'active' : 'inactive';
    }

    private function settings(array $operator): array
    {
        if (is_array($operator['settings'] ?? null)) {
            return $operator['settings'];
        }

        $decoded = json_decode((string) ($operator['settings'] ?? ''), true);

        return is_array($decoded) ? $decoded : [];
    }
}
