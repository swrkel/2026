<?php

namespace Modules\Customers\Services;

use App\Business;

class CustomerConfigurationService
{
    protected $rootKey = 'customers_module';

    public function get(int $businessId, string $section): array
    {
        $business = Business::find($businessId);
        $settings = $this->decode($business->common_settings ?? null);
        return $settings[$this->rootKey][$section] ?? $this->defaults($section);
    }

    public function update(int $businessId, string $section, array $data): array
    {
        $business = Business::findOrFail($businessId);
        $settings = $this->decode($business->common_settings ?? null);
        if (!isset($settings[$this->rootKey]) || !is_array($settings[$this->rootKey])) {
            $settings[$this->rootKey] = [];
        }
        $clean = array_merge($this->defaults($section), $data);
        $settings[$this->rootKey][$section] = $clean;
        $business->common_settings = json_encode($settings);
        $business->save();
        return $clean;
    }

    public function defaults(string $section): array
    {
        $defaults = [
            'numbering' => [
                'prefix' => 'CUS',
                'auto_numbering' => 1,
                'manual_numbering_allowed' => 1,
                'next_number' => 1,
            ],
            'defaults' => [
                'customer_type' => 'credit_customer',
                'status' => 'active',
                'credit_terms_days' => 0,
                'default_credit_limit' => 0,
            ],
            'preferences' => [
                'grid_page_length' => 25,
                'show_credit_limit' => 1,
                'show_last_payment' => 1,
                'statement_format' => 'standard',
            ],
            'portal' => [
                'enable_dealer_login' => 1,
                'enable_orders' => 1,
                'enable_statements' => 1,
                'enable_notifications' => 1,
            ],
            'credit' => [
                'approval_required' => 1,
                'warning_percent' => 80,
                'block_percent' => 100,
                'review_days' => 30,
            ],
            'notifications' => [
                'email_enabled' => 0,
                'sms_enabled' => 0,
                'portal_enabled' => 1,
                'overdue_alerts' => 1,
            ],
        ];
        return $defaults[$section] ?? [];
    }

    protected function decode($value): array
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
