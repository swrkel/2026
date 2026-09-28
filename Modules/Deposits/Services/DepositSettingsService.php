<?php

namespace Modules\Deposits\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Deposits\Models\DepositSetting;

class DepositSettingsService
{
    public function businessId(): ?int
    {
        return session('business.id') ? (int) session('business.id') : null;
    }

    public function defaults(): array
    {
        return [
            'account_prefix' => 'DEP',
            'certificate_prefix' => 'CERT',
            'transaction_prefix' => 'DTR',
            'default_interest_frequency' => 'monthly',
            'default_interest_method' => 'simple',
            'allow_negative_balance' => '0',
            'require_nominee' => '0',
            'require_beneficiary' => '0',
        ];
    }

    public function all(): array
    {
        $settings = $this->defaults();
        if (! Schema::hasTable('deposit_settings')) {
            return $settings;
        }

        $rows = DepositSetting::where('business_id', $this->businessId())->pluck('value', 'key')->toArray();
        return array_merge($settings, $rows);
    }

    public function get(string $key, $default = null)
    {
        $settings = $this->all();
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function save(array $settings): void
    {
        if (! Schema::hasTable('deposit_settings')) {
            return;
        }

        $businessId = $this->businessId();
        foreach ($this->defaults() as $key => $default) {
            if (! array_key_exists($key, $settings)) {
                continue;
            }

            DepositSetting::updateOrCreate(
                ['business_id' => $businessId, 'key' => $key],
                ['value' => (string) $settings[$key], 'updated_by' => auth()->id(), 'created_by' => auth()->id()]
            );
        }
    }
}
