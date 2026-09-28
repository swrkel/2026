<?php

return [
    'module' => [
        'key' => 'petro_direct_new',
        'label' => 'Petro Direct-New',
    ],
    'items' => [
        ['key' => 'petro_direct_new_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'route_paths' => ['petro-direct-new', 'petro-direct-new/dashboard']],
        ['key' => 'petro_direct_new_direct_settlement', 'label' => 'Direct Settlement', 'type' => 'page', 'route_paths' => ['petro-direct-new/settlements/create']],
        ['key' => 'petro_direct_new_list_settlements', 'label' => 'List Direct Settlements', 'type' => 'page', 'route_paths' => ['petro-direct-new/settlements']],
        ['key' => 'petro_direct_new_pumper_management', 'label' => 'Pumper Management', 'type' => 'page', 'route_paths' => ['petro-direct-new/pumper-management']],
        ['key' => 'petro_direct_new_reports', 'label' => 'Reports', 'type' => 'page', 'route_paths' => ['petro-direct-new/reports']],
        ['key' => 'petro_direct_new_tab_operators', 'label' => 'Pump Operators Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="operators"]']],
        ['key' => 'petro_direct_new_tab_payments', 'label' => 'Pumper Excess / Shortage Payments Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="payments"]']],
        ['key' => 'petro_direct_new_tab_pumper_day_entries', 'label' => 'Pumper Day Entries Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="pumper_day_entries"]']],
        ['key' => 'petro_direct_new_tab_shift_summary', 'label' => 'Shift Summary Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="shift_summary"]']],
        ['key' => 'petro_direct_new_tab_payment_summary', 'label' => 'Payment Summary Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="payment_summary"]']],
        ['key' => 'petro_direct_new_tab_meters_with_payments', 'label' => 'Meters with Payments Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="meters_with_payments"]']],
        ['key' => 'petro_direct_new_tab_daily_pump_status', 'label' => 'Daily Pump Status Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="daily_pump_status"]']],
        ['key' => 'petro_direct_new_tab_close_shift', 'label' => 'Close Shift Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="close_shift"]']],
        ['key' => 'petro_direct_new_tab_current_meter', 'label' => 'Current Meter Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="current_meter"]']],
        ['key' => 'petro_direct_new_tab_unload_stock', 'label' => 'Unload Stock Tab', 'type' => 'tab', 'selectors' => ['[data-pdirectnew-tab="unload_stock"]']],
    ],
];
