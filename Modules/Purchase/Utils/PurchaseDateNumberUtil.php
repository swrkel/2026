<?php

namespace Modules\Purchase\Utils;

use Carbon\Carbon;
use DateTimeInterface;

class PurchaseDateNumberUtil
{
    public function businessId(): int
    {
        return (int) (session('user.business_id') ?: session('business.id') ?: 0);
    }

    public function userId(): int
    {
        return (int) (session('user.id') ?: auth()->id() ?: 0);
    }

    public function number(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $decimal = (string) (session('currency.decimal_separator')
            ?: session('business.decimal_separator')
            ?: '.');
        $thousand = (string) (session('currency.thousand_separator')
            ?: session('business.thousand_separator')
            ?: ',');

        if ($thousand !== '') {
            $value = str_replace($thousand, '', $value);
        }

        if ($decimal !== '.') {
            $value = str_replace($decimal, '.', $value);
        }

        $value = preg_replace('/[^0-9.\-]/', '', $value) ?? '0';

        return is_numeric($value) ? (float) $value : 0.0;
    }

    public function dateTime(mixed $value): Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        $text = trim((string) $value);
        if ($text === '') {
            return now();
        }

        $formats = array_values(array_unique(array_filter([
            'Y-m-d\TH:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            $this->businessDateFormat() . ' H:i',
            $this->businessDateFormat(),
            'm/d/Y H:i',
            'm/d/Y',
            'd/m/Y H:i',
            'd/m/Y',
        ])));

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $text);
                if ($date !== false) {
                    return $date;
                }
            } catch (\Throwable) {
                // Try the next supported format.
            }
        }

        return Carbon::parse($text);
    }

    public function date(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return $this->dateTime($text)->toDateString();
    }

    public function businessDateFormat(): string
    {
        $format = (string) (session('business.date_format') ?: 'm/d/Y');

        return strtr($format, [
            'dd' => 'd',
            'mm' => 'm',
            'yyyy' => 'Y',
        ]);
    }
}
