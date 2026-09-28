<?php

namespace Modules\PetroDirectNew\Support;

class SchemaDefinition
{
    public static function tables(): array
    {
        return [
            'pdirectnew_settings', 'pdirectnew_number_sequences', 'pdirectnew_operators',
            'pdirectnew_tanks', 'pdirectnew_pumps', 'pdirectnew_shifts',
            'pdirectnew_assignments', 'pdirectnew_meter_readings', 'pdirectnew_meter_resets',
            'pdirectnew_settlements', 'pdirectnew_settlement_meter_sales',
            'pdirectnew_settlement_payments', 'pdirectnew_settlement_other_sales',
            'pdirectnew_settlement_other_sale_lines', 'pdirectnew_settlement_other_income',
            'pdirectnew_settlement_customer_payments', 'pdirectnew_daily_collections',
            'pdirectnew_collection_lines', 'pdirectnew_pumper_day_entries', 'pdirectnew_unload_stocks', 'pdirectnew_unload_stock_lines', 'pdirectnew_tank_transfers',
            'pdirectnew_dip_charts', 'pdirectnew_dip_chart_lines', 'pdirectnew_dip_readings',
            'pdirectnew_adjustments', 'pdirectnew_print_logs', 'pdirectnew_audit_logs',
            'pdirectnew_saved_report_filters',
        ];
    }

    public static function requiredColumns(): array
    {
        return [
            'pdirectnew_operators' => [
                'source_operator_id', 'address', 'landline', 'dob', 'email', 'username',
                'passcode_hash', 'opening_balance', 'commission_type', 'commission_value',
                'short_amount', 'excess_amount', 'transaction_date', 'is_default',
                'can_fullscreen', 'hide_in_direct_settlement_if_pending_shifts',
                'source_updated_at',
            ],
        ];
    }
}
