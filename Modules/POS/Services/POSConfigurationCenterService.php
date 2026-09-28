<?php

namespace Modules\POS\Services;

class POSConfigurationCenterService
{
    public function sections(int $businessId): array
    {
        return [
            'general' => ['icon' => 'fa fa-cogs', 'title' => __('pos::messages.general_settings'), 'description' => __('pos::messages.general_settings_desc')],
            'registers' => ['icon' => 'fa fa-desktop', 'title' => __('pos::messages.register_settings'), 'description' => __('pos::messages.register_settings_desc')],
            'payments' => ['icon' => 'fa fa-credit-card', 'title' => __('pos::messages.payment_settings'), 'description' => __('pos::messages.payment_settings_desc')],
            'receipts' => ['icon' => 'fa fa-print', 'title' => __('pos::messages.receipt_settings'), 'description' => __('pos::messages.receipt_settings_desc')],
            'devices' => ['icon' => 'fa fa-plug', 'title' => __('pos::messages.device_settings'), 'description' => __('pos::messages.device_settings_desc')],
            'plugins' => ['icon' => 'fa fa-puzzle-piece', 'title' => __('pos::messages.plugin_manager'), 'description' => __('pos::messages.plugin_manager_desc')],
            'capabilities' => ['icon' => 'fa fa-sliders', 'title' => __('pos::messages.capability_manager'), 'description' => __('pos::messages.capability_manager_desc')],
            'finance' => ['icon' => 'fa fa-calculator', 'title' => __('pos::messages.finance_mapping'), 'description' => __('pos::messages.finance_mapping_desc')],
            'security' => ['icon' => 'fa fa-shield', 'title' => __('pos::messages.security_settings'), 'description' => __('pos::messages.security_settings_desc')],
            'performance' => ['icon' => 'fa fa-tachometer', 'title' => __('pos::messages.performance_settings'), 'description' => __('pos::messages.performance_settings_desc')],
        ];
    }

    public function section(int $businessId, string $section): array
    {
        $sections = $this->sections($businessId);
        abort_unless(isset($sections[$section]), 404);

        return [
            'meta' => $sections[$section],
            'fields' => $this->fields($section),
        ];
    }

    protected function fields(string $section): array
    {
        $common = [
            ['name' => 'note', 'type' => 'textarea', 'label' => __('pos::messages.note')],
        ];

        $fields = [
            'general' => [
                ['name' => 'default_location', 'type' => 'select', 'label' => __('pos::messages.default_location')],
                ['name' => 'theme', 'type' => 'select', 'label' => __('pos::messages.pos_theme')],
                ['name' => 'number_format', 'type' => 'text', 'label' => __('pos::messages.number_format')],
            ],
            'payments' => [
                ['name' => 'allow_multiple_payments', 'type' => 'checkbox', 'label' => __('pos::messages.allow_multiple_payments')],
                ['name' => 'allow_customer_credit', 'type' => 'checkbox', 'label' => __('pos::messages.allow_customer_credit')],
                ['name' => 'allow_overpayment', 'type' => 'checkbox', 'label' => __('pos::messages.allow_overpayment')],
            ],
            'plugins' => [
                ['name' => 'retail', 'type' => 'checkbox', 'label' => __('pos::messages.retail_plugin')],
                ['name' => 'beauty_saloons', 'type' => 'checkbox', 'label' => __('pos::messages.beauty_saloons_plugin')],
                ['name' => 'hotel', 'type' => 'checkbox', 'label' => __('pos::messages.hotel_plugin')],
                ['name' => 'restaurant', 'type' => 'checkbox', 'label' => __('pos::messages.restaurant_plugin')],
            ],
        ];

        return array_merge($fields[$section] ?? [], $common);
    }
}
