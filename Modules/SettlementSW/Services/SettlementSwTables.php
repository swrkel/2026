<?php

namespace Modules\SettlementSW\Services;

/**
 * Central table-name resolver for Settlement SW legacy-compatible storage.
 *
 * Active Settlement SW code must not hard-code Petro table names. While the
 * current tenant databases still keep the historic physical table names, this
 * service keeps that compatibility inside the module and allows future
 * migration by changing Config/settlementsw.php only.
 */
class SettlementSwTables
{
    public static function dailyShifts(): string
    {
        return config('settlementsw.tables.daily_shifts', 'petro_daily_shifts');
    }

    public static function shifts(): string
    {
        return config('settlementsw.tables.shifts', 'petro_shifts');
    }

    public static function workShifts(): string
    {
        return config('settlementsw.tables.work_shifts', 'work_shifts');
    }

    public static function settlements(): string
    {
        return config('settlementsw.tables.settlements', 'settlements');
    }

    public static function products(): string
    {
        return config('settlementsw.tables.products', 'products');
    }

    public static function tempData(): string
    {
        return config('settlementsw.tables.temp_data', 'temp_data');
    }

    public static function dailyCollections(): string
    {
        return config('settlementsw.tables.daily_collections', 'daily_collections');
    }

    public static function dailyVoucherItems(): string
    {
        return config('settlementsw.tables.daily_voucher_items', 'daily_voucher_items');
    }

    public static function customerPayments(): string
    {
        return config('settlementsw.tables.customer_payments', 'customer_payments');
    }

    public static function pumpOperatorOtherSales(): string
    {
        return config('settlementsw.tables.pump_operator_other_sales', 'pump_operator_other_sales');
    }

    public static function pumpOperatorMeterSaleDetails(): string
    {
        return config('settlementsw.tables.pump_operator_meter_sale_details', 'pump_operator_meter_sale_details');
    }

    public static function meterSales(): string
    {
        return config('settlementsw.tables.meter_sales', 'meter_sales');
    }


    public static function contacts(): string
    {
        return config('settlementsw.tables.contacts', 'contacts');
    }

    public static function contactGroups(): string
    {
        return config('settlementsw.tables.contact_groups', 'contact_groups');
    }

    public static function customerReferences(): string
    {
        return config('settlementsw.tables.customer_references', 'customer_references');
    }

    public static function transactions(): string
    {
        return config('settlementsw.tables.transactions', 'transactions');
    }

    public static function transactionPayments(): string
    {
        return config('settlementsw.tables.transaction_payments', 'transaction_payments');
    }

    public static function accounts(): string
    {
        return config('settlementsw.tables.accounts', 'accounts');
    }

    public static function accountGroups(): string
    {
        return config('settlementsw.tables.account_groups', 'account_groups');
    }

    public static function accountTransactions(): string
    {
        return config('settlementsw.tables.account_transactions', 'account_transactions');
    }

    public static function business(): string
    {
        return config('settlementsw.tables.business', 'business');
    }

    public static function businessLocations(): string
    {
        return config('settlementsw.tables.business_locations', 'business_locations');
    }

    public static function contactColumn(string $column): string
    {
        return self::contacts() . '.' . $column;
    }

    public static function contactGroupColumn(string $column): string
    {
        return self::contactGroups() . '.' . $column;
    }

    public static function dailyShiftColumn(string $column): string
    {
        return self::dailyShifts() . '.' . $column;
    }

    public static function shiftColumn(string $column): string
    {
        return self::shifts() . '.' . $column;
    }
}
