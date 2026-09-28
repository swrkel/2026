<?php

namespace Modules\PetroGeneral\Utils;

use App\Business;

/**
 * Petro General formatting helper.
 * Keeps number/date formatting inside PetroGeneral instead of depending on Petro utilities.
 */
class PetroGeneralFormatUtil
{
    public static function businessId(): ?int
    {
        return session('business.id') ?: session()->get('user.business_id');
    }

    public static function business()
    {
        $businessId = self::businessId();
        return $businessId ? Business::find($businessId) : null;
    }

    public static function currencySymbol(): string
    {
        $business = self::business();
        return optional(optional($business)->currency)->symbol ?: optional(optional($business)->currency)->code ?: '';
    }

    public static function currencyPrecision(): int
    {
        $business = self::business();
        return (int) ($business->currency_precision ?? config('constants.currency_precision', 2));
    }

    public static function quantityPrecision(): int
    {
        $business = self::business();
        return (int) ($business->quantity_precision ?? config('constants.quantity_precision', 2));
    }

    public static function money($value, bool $withSymbol = true): string
    {
        $amount = number_format((float) $value, self::currencyPrecision(), '.', ',');
        return trim(($withSymbol ? self::currencySymbol().' ' : '').$amount);
    }

    public static function quantity($value, ?int $precision = null): string
    {
        return number_format((float) $value, $precision ?? self::quantityPrecision(), '.', ',');
    }

    public static function fuelQuantity($value): string
    {
        return number_format((float) $value, 3, '.', ',');
    }

    public static function date($date): string
    {
        if (empty($date)) {
            return '';
        }
        return date('Y-m-d', strtotime($date));
    }

    public static function dateTime($date): string
    {
        if (empty($date)) {
            return '';
        }
        return date('Y-m-d H:i', strtotime($date));
    }
}
