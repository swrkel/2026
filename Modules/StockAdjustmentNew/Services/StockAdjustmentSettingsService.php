<?php

namespace Modules\StockAdjustmentNew\Services;

use Illuminate\Support\Facades\Schema;
use Modules\StockAdjustmentNew\Entities\StockAdjustmentSetting;

class StockAdjustmentSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'number_prefix' => (string) config('stockadjustmentnew.defaults.number_prefix', 'SAN-'),
            'number_padding' => (int) config('stockadjustmentnew.defaults.number_padding', 5),
            'default_adjustment_type' => (string) config('stockadjustmentnew.defaults.default_adjustment_type', 'quantity'),
            'default_page_size' => (int) config('stockadjustmentnew.defaults.default_page_size', 25),
            'quantity_decimals' => (int) config('stockadjustmentnew.defaults.decimal_qty', 4),
            'amount_decimals' => (int) config('stockadjustmentnew.defaults.decimal_amount', 4),
            'require_reason' => false,
            'require_location' => true,
            'require_store' => false,
            'require_approval' => (bool) config('stockadjustmentnew.defaults.require_approval', true),
            'auto_submit' => false,
            'auto_post_after_approval' => false,
            'require_batch_when_available' => true,
            'hide_zero_stock_products' => false,
            'allow_negative_stock' => (bool) config('stockadjustmentnew.defaults.allow_negative_stock', false),
            'allow_zero_unit_cost' => true,
            'allow_backdated_adjustments' => true,
            'max_backdate_days' => null,
            'allow_future_dated_adjustments' => true,
            'batch_selection_method' => 'fefo',
        ];
    }

    /**
     * Return the effective settings for one business. Missing rows and missing
     * tables safely fall back to module defaults so the rest of the module can
     * continue operating during tenant-by-tenant deployment.
     *
     * @return array<string, mixed>
     */
    public function values(?int $businessId): array
    {
        $defaults = $this->defaults();

        if (! $businessId || ! Schema::hasTable('san_stock_adjustment_settings')) {
            return $defaults;
        }

        $row = StockAdjustmentSetting::query()
            ->where('business_id', $businessId)
            ->first();

        if (! $row) {
            return $defaults;
        }

        foreach (array_keys($defaults) as $key) {
            if ($row->getAttribute($key) !== null) {
                $defaults[$key] = $row->getAttribute($key);
            }
        }

        $defaults['require_location'] = true;

        if (! in_array((string) ($defaults['default_adjustment_type'] ?? ''), ['quantity', 'value', 'damage', 'expiry'], true)) {
            $defaults['default_adjustment_type'] = 'quantity';
        }

        return $defaults;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(int $businessId, array $data, ?int $userId): StockAdjustmentSetting
    {
        $payload = $this->normalise($data);
        $payload['updated_by'] = $userId;

        $setting = StockAdjustmentSetting::query()->firstOrNew([
            'business_id' => $businessId,
        ]);

        if (! $setting->exists) {
            $payload['created_by'] = $userId;
        }

        $setting->fill($payload)->save();

        return $setting->refresh();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalise(array $data): array
    {
        $booleanKeys = [
            'require_reason',
            'require_location',
            'require_store',
            'require_approval',
            'auto_submit',
            'auto_post_after_approval',
            'require_batch_when_available',
            'hide_zero_stock_products',
            'allow_negative_stock',
            'allow_zero_unit_cost',
            'allow_backdated_adjustments',
            'allow_future_dated_adjustments',
        ];

        foreach ($booleanKeys as $key) {
            $data[$key] = (bool) ($data[$key] ?? false);
        }

        $data['require_location'] = true;
        $data['default_adjustment_type'] = in_array((string) ($data['default_adjustment_type'] ?? ''), ['quantity', 'value', 'damage', 'expiry'], true)
            ? (string) $data['default_adjustment_type']
            : 'quantity';
        $data['number_prefix'] = trim((string) ($data['number_prefix'] ?? 'SAN-'));
        $data['number_padding'] = max(3, min(10, (int) ($data['number_padding'] ?? 5)));
        $data['default_page_size'] = max(10, min(100, (int) ($data['default_page_size'] ?? 25)));
        $data['quantity_decimals'] = max(0, min(8, (int) ($data['quantity_decimals'] ?? 4)));
        $data['amount_decimals'] = max(0, min(8, (int) ($data['amount_decimals'] ?? 4)));
        $data['max_backdate_days'] = ($data['max_backdate_days'] ?? '') === ''
            ? null
            : max(0, (int) $data['max_backdate_days']);

        return $data;
    }
}
