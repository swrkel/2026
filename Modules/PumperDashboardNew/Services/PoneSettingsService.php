<?php

namespace Modules\PumperDashboardNew\Services;

use Modules\PumperDashboardNew\Entities\PoneModuleSetting;

class PoneSettingsService
{
    public function get(int $businessId, ?int $locationId = null): array
    {
        $defaults = config('pumperdashboardnew.defaults', []);
        $integration = config('pumperdashboardnew.integration', []);
        $row = PoneModuleSetting::query()->where('scope_key', $this->scopeKey($businessId, $locationId))->first();
        if (! $row && $locationId !== null) {
            $row = PoneModuleSetting::query()->where('scope_key', $this->scopeKey($businessId, null))->first();
        }
        $database = $row ? $row->toArray() : [];
        $custom = is_array($database['settings'] ?? null) ? $database['settings'] : [];
        return array_merge($defaults, [
            'sync_during_operation' => (bool) ($database['sync_during_operation'] ?? $integration['sync_during_operation'] ?? true),
            'require_clean_sync_before_close' => (bool) ($database['require_clean_sync_before_close'] ?? $integration['require_clean_sync_before_close'] ?? true),
        ], $database, $custom, [
            'integration_enabled' => true,
            'integration_mode' => 'petro_pd_new',
        ]);
    }

    public function update(int $businessId, ?int $locationId, array $data, ?int $userId): PoneModuleSetting
    {
        $scopeKey = $this->scopeKey($businessId, $locationId);
        $allowed = [
            'integration_enabled', 'integration_mode', 'sync_during_operation',
            'require_clean_sync_before_close', 'allow_operator_open_shift',
            'allow_close_with_open_pumps', 'allow_close_with_pending_sync',
            'cash_denomination_enabled', 'multi_card_enabled', 'cheque_enabled',
            'credit_sale_enabled', 'require_credit_dual_confirmation',
            'require_collection_before_close', 'allow_payment_edit',
            'payment_edit_lock_minutes', 'auto_logout_minutes', 'receipt_paper_size',
            'default_store_id', 'amount_decimals', 'quantity_decimals', 'meter_decimals',
            'shift_prefix', 'payment_prefix', 'other_sale_prefix', 'unload_prefix',
            'settlement_prefix', 'collection_prefix', 'recovery_prefix', 'commission_prefix',
        ];
        $payload = array_intersect_key($data, array_flip($allowed));
        $payload['integration_mode'] = 'petro_pd_new';
        $payload['integration_enabled'] = true;
        $payload += [
            'scope_key' => $scopeKey,
            'business_id' => $businessId,
            'location_id' => $locationId,
            'updated_by' => $userId,
        ];
        return PoneModuleSetting::query()->updateOrCreate(['scope_key' => $scopeKey], $payload);
    }

    public function scopeKey(int $businessId, ?int $locationId): string
    {
        return $businessId . ':' . ($locationId ?: 'all');
    }
}
