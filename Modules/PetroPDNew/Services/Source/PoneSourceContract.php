<?php

namespace Modules\PetroPDNew\Services\Source;

/**
 * Versioned, read-only field contract for Pumper Dashboard-New.
 *
 * Keeping the source contract outside the reader makes schema validation,
 * hashing and future version upgrades explicit and independently testable.
 */
final class PoneSourceContract
{
    private const VERSION = 'pumper_dashboard_new.v1';

    private const SOURCE_COLUMNS = [
        'pone_shifts' => [
            'id', 'uuid', 'business_id', 'location_id', 'operator_profile_id',
            'pd_operator_id', 'user_id', 'shift_number', 'status', 'opened_at',
            'closed_at', 'meter_sales_total', 'other_sales_total', 'payments_total',
            'expected_total', 'declared_total', 'shortage_amount', 'excess_amount',
            'reconciliation_status', 'collection_form_no', 'notes', 'created_by',
            'closed_by',
        ],
        'pone_pd_operators' => [
            'id', 'business_id', 'location_id', 'pd_operator_id', 'user_id',
            'display_name', 'login_enabled', 'status',
        ],
        'pone_pump_assignments' => [
            'id', 'shift_id', 'business_id', 'location_id', 'operator_profile_id',
            'pd_operator_id', 'pump_id', 'product_id', 'opening_meter',
            'current_meter', 'closing_meter', 'testing_quantity', 'sold_quantity',
            'unit_price', 'amount', 'status', 'assigned_at', 'accepted_at',
            'closed_at', 'confirmed_at',
        ],
        'pone_meter_readings' => [
            'id', 'shift_id', 'assignment_id', 'business_id', 'location_id',
            'operator_profile_id', 'pump_id', 'reading_type', 'meter_value',
            'testing_quantity', 'source', 'recorded_at', 'recorded_by', 'note',
        ],
        'pone_payments' => [
            'id', 'uuid', 'shift_id', 'business_id', 'location_id',
            'operator_profile_id', 'pd_operator_id', 'payment_number',
            'payment_type', 'gross_amount', 'discount_amount', 'amount',
            'customer_id', 'reference_no', 'card_type', 'card_last_four',
            'slip_no', 'bank_name', 'cheque_no', 'cheque_date',
            'transaction_at', 'note', 'status', 'created_by', 'voided_by',
            'voided_at',
        ],
        'pone_payment_cash_denominations' => [
            'id', 'payment_id', 'denomination', 'quantity', 'amount',
        ],
        'pone_payment_card_lines' => [
            'id', 'payment_id', 'card_type', 'last_four', 'slip_no',
            'account_id', 'reference_no', 'amount',
        ],
        'pone_credit_sales' => [
            'id', 'payment_id', 'business_id', 'customer_id', 'order_number',
            'bill_number', 'vehicle_number', 'customer_reference', 'order_date',
            'due_date', 'customer_confirmed', 'order_confirmed',
            'vehicle_confirmed', 'customer_confirmed_at', 'order_confirmed_at',
            'vehicle_confirmed_at', 'confirmation_rounds', 'locked_at',
            'confirmed_by', 'note',
        ],
        'pone_credit_sale_lines' => [
            'id', 'credit_sale_id', 'product_id', 'quantity', 'unit_price',
            'discount_amount', 'amount',
        ],
        'pone_other_sales' => [
            'id', 'uuid', 'shift_id', 'business_id', 'location_id',
            'operator_profile_id', 'pd_operator_id', 'store_id', 'sale_number',
            'sale_at', 'gross_amount', 'discount_amount', 'net_amount', 'status',
            'note', 'created_by',
        ],
        'pone_other_sale_lines' => [
            'id', 'other_sale_id', 'product_id', 'quantity', 'unit_price',
            'discount_amount', 'amount', 'balance_stock_snapshot',
        ],
        'pone_unload_stocks' => [
            'id', 'uuid', 'shift_id', 'business_id', 'location_id',
            'operator_profile_id', 'pd_operator_id', 'store_id',
            'receipt_number', 'bill_number', 'supplier_reference',
            'unloaded_at', 'total_quantity', 'total_amount', 'status', 'note',
            'created_by',
        ],
        'pone_unload_stock_lines' => [
            'id', 'unload_stock_id', 'product_id', 'tank_id', 'quantity',
            'unit_cost', 'amount', 'dip_reading', 'current_stock',
        ],
        'pone_day_entries' => [
            'id', 'shift_id', 'business_id', 'location_id',
            'operator_profile_id', 'pd_operator_id', 'assignment_id', 'pump_id',
            'entry_type', 'reference_no', 'quantity', 'amount',
            'starting_meter', 'closing_meter', 'testing_quantity', 'entry_at',
            'note', 'status', 'created_by',
        ],
        'pone_daily_collections' => [
            'id', 'uuid', 'shift_id', 'business_id', 'location_id',
            'operator_profile_id', 'pd_operator_id', 'collection_number',
            'collection_at', 'expected_amount', 'cash_amount', 'card_amount',
            'cheque_amount', 'credit_amount', 'other_amount', 'declared_amount',
            'difference_amount', 'status', 'note', 'created_by', 'confirmed_by',
            'voided_by', 'voided_at',
        ],
        'pone_shortage_recoveries' => [
            'id', 'shift_id', 'business_id', 'location_id',
            'operator_profile_id', 'pd_operator_id', 'recovery_number',
            'recovery_date', 'amount', 'payment_method', 'reference_no', 'note',
            'status', 'created_by', 'voided_by', 'voided_at',
        ],
        'pone_excess_commissions' => [
            'id', 'shift_id', 'business_id', 'location_id',
            'operator_profile_id', 'pd_operator_id', 'commission_number',
            'commission_date', 'base_excess_amount', 'commission_type',
            'commission_rate', 'commission_amount', 'note', 'status',
            'created_by', 'voided_by', 'voided_at',
        ],
        'pone_operator_ledger_entries' => [
            'id', 'entry_key', 'business_id', 'location_id',
            'operator_profile_id', 'pd_operator_id', 'shift_id', 'source_type',
            'source_id', 'entry_at', 'reference_no', 'description', 'debit',
            'credit', 'status', 'created_by',
        ],
    ];

    private const REQUIRED_COLUMNS = [
            'pone_shifts' => [
                'id', 'business_id', 'location_id', 'operator_profile_id',
                'pd_operator_id', 'shift_number', 'status', 'closed_at',
                'meter_sales_total', 'other_sales_total', 'payments_total',
                'expected_total', 'declared_total', 'shortage_amount', 'excess_amount',
                'reconciliation_status',
            ],
            'pone_pd_operators' => [
                'id', 'business_id', 'pd_operator_id', 'display_name', 'status',
            ],
            'pone_pump_assignments' => [
                'id', 'shift_id', 'business_id', 'pump_id', 'opening_meter',
                'closing_meter', 'testing_quantity', 'sold_quantity',
                'unit_price', 'amount', 'status',
            ],
            'pone_meter_readings' => [
                'id', 'shift_id', 'assignment_id', 'business_id',
                'meter_value', 'recorded_at',
            ],
            'pone_payments' => [
                'id', 'shift_id', 'business_id', 'payment_number',
                'payment_type', 'amount', 'transaction_at', 'status',
            ],
            'pone_other_sales' => [
                'id', 'shift_id', 'business_id', 'sale_number', 'net_amount',
                'status',
            ],
            'pone_unload_stocks' => [
                'id', 'shift_id', 'business_id', 'receipt_number',
                'total_quantity', 'total_amount', 'status',
            ],
            'pone_day_entries' => [
                'id', 'shift_id', 'business_id', 'entry_type', 'status',
            ],
            'pone_daily_collections' => [
                'id', 'shift_id', 'business_id', 'collection_number',
                'declared_amount', 'status',
            ],
            'pone_shift_settlement_references' => [
                'id', 'shift_id', 'business_id', 'settlement_no',
                'settlement_date',
            ],
        ];

    public function version(): string
    {
        return self::VERSION;
    }

    public function sourceColumns(string $table): array
    {
        return self::SOURCE_COLUMNS[$table] ?? [];
    }

    public function requiredColumns(): array
    {
        return self::REQUIRED_COLUMNS;
    }
}
