<?php

namespace Modules\Membership\Utils;

class PointSettingFormatter
{
    public static function dateTime($dateTime): string
    {
        return !empty($dateTime) ? $dateTime->format('Y-m-d H:i') : '';
    }

    public static function businessTypeName($row): string
    {
        return !empty($row->businessType) ? $row->businessType->business_type : '-';
    }

    public static function expiryPeriod($row): string
    {
        $period = [];

        if (!empty($row->expiry_period_years)) {
            $period[] = $row->expiry_period_years . ' ' . __('membership::lang.years');
        }

        if (!empty($row->expiry_period_months)) {
            $period[] = $row->expiry_period_months . ' ' . __('membership::lang.months');
        }

        return !empty($period) ? implode(', ', $period) : '-';
    }

    public static function addedBy($row): string
    {
        if (empty($row->createdBy)) {
            return '-';
        }

        $name = trim(($row->createdBy->first_name ?? '') . ' ' . ($row->createdBy->last_name ?? ''));
        return $name !== '' ? $name : ($row->createdBy->username ?? '-');
    }
}
