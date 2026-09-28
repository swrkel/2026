<?php

namespace Modules\Vat\Services;

use Carbon\Carbon;

/**
 * Separation step 1 (see document 5-18, "Suggested sequence").
 *
 * Number and date formatting, owned by the VAT module.
 *
 *
 * WHY THIS IS THE FIRST STEP
 *
 * The inventory found 534 calls from this module into core utility classes.
 * 307 of them - 57% - are these four methods. They carry no business logic, so
 * this is the largest block of coupling and the least risky to remove.
 *
 *
 * FIDELITY IS THE WHOLE POINT
 *
 * These parse and render MONEY. num_uf() alone is called 240 times, on invoice
 * totals, tax amounts and outstanding balances. If the separator convention
 * differs from core by even one character, "1.500" parses as 1.5 instead of
 * 1500 and every VAT document is silently wrong - no exception, no log entry.
 *
 * So this is a DELIBERATE TRANSCRIPTION of App\Utils\Util, not a reimplementation:
 *
 *   - num_uf()      -> Util::num_uf(), including reading the separators from
 *                      session('currency') when no currency object is passed.
 *   - num_f()       -> Util::num_f(), including its precision selection
 *                      (currency vs quantity vs forced) and symbol placement.
 *   - format_date() -> Util::format_date(), including the Carbon-instance
 *                      shortcut that avoids a timezone shift.
 *   - uf_date()     -> Util::uf_date(), including the early return for values
 *                      already in Y-m-d, and the Carbon::parse() fallback.
 *
 * The private helpers below (getNumberFormatSettings, formatNumberWithPrecision,
 * getCurrencyPrecision, getQuantityPrecision) mirror core's equivalents line for
 * line, including the second str_replace(',', '') in the normaliser, which
 * matters when the thousand separator is NOT a comma but stray commas are still
 * present in the input.
 *
 * If core's formatting changes, this must be revisited - that is the standing
 * cost of separation, and it is recorded here so the next person sees it.
 */
class VatFormatter
{
    /**
     * Unformat a display number into a plain float.
     *
     * Transcribed from Util::num_uf().
     */
    public function num_uf($input_number, $currency_details = null): float
    {
        $thousand_separator = '';
        $decimal_separator = '';

        if (! empty($currency_details)) {
            $thousand_separator = $currency_details->thousand_separator;
            $decimal_separator = $currency_details->decimal_separator;
        } else {
            $thousand_separator = session()->has('currency') ? session('currency')['thousand_separator'] : '';
            $decimal_separator = session()->has('currency') ? session('currency')['decimal_separator'] : '';
        }

        $num = str_replace($thousand_separator, '', $input_number);
        $num = str_replace($decimal_separator, '.', $num);

        return (float) $num;
    }

    /**
     * Format a number for display.
     *
     * Transcribed from Util::num_f().
     */
    public function num_f($input_number, $add_symbol = false, $business_details = null, $is_quantity = false, $force_precision = null)
    {
        $currency = session('currency', []);
        $format = $this->getNumberFormatSettings($business_details);

        if (! is_null($force_precision)) {
            $precision = (int) $force_precision;
        } elseif ($is_quantity) {
            $precision = $this->getQuantityPrecision($business_details);
        } else {
            $precision = $this->getCurrencyPrecision($business_details);
        }

        $formatted = $this->formatNumberWithPrecision(
            $input_number,
            $precision,
            $format['decimal_separator'],
            $format['thousand_separator']
        );

        if ($add_symbol) {
            $currency_symbol_placement = ! empty($business_details) && ! empty($business_details->currency_symbol_placement)
                ? $business_details->currency_symbol_placement
                : session('business.currency_symbol_placement', $currency['currency_symbol_placement'] ?? 'before');

            $symbol = ! empty($business_details) && ! empty($business_details->currency_symbol)
                ? $business_details->currency_symbol
                : ($currency['symbol'] ?? session('currency.symbol', ''));

            if ($currency_symbol_placement == 'after') {
                $formatted = trim($formatted . ' ' . $symbol);
            } else {
                $formatted = trim($symbol . ' ' . $formatted);
            }
        }

        return $formatted;
    }

    /**
     * Format a stored date for display.
     *
     * Transcribed from Util::format_date().
     */
    public function format_date($date, $show_time = false, $business_details = null)
    {
        $format = ! empty($business_details) ? $business_details->date_format : session('business.date_format');

        if (empty($format)) {
            $format = 'Y-m-d';
        }

        if (! empty($show_time)) {
            $time_format = ! empty($business_details) ? $business_details->time_format : session('business.time_format');
            $format .= ($time_format == 12) ? ' h:i A' : ' H:i';
        }

        if (empty($date)) {
            return null;
        }

        // Already a Carbon instance: format directly, or the timestamp round-trip
        // below shifts the value by the timezone offset.
        if ($date instanceof Carbon) {
            return $date->format($format);
        }

        return Carbon::createFromTimestamp(strtotime($date))->format($format);
    }

    /**
     * Parse a displayed date back into MySQL format.
     *
     * Transcribed from Util::uf_date().
     */
    public function uf_date($date, $time = false)
    {
        // Already stored-format: return untouched. Without this an ISO value
        // would be re-parsed against the business format and rejected.
        if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', (string) $date)) {
            return $date;
        }

        $date_format = session('business.date_format');
        $mysql_format = 'Y-m-d';

        if ($time) {
            $date_format .= (session('business.time_format') == 12) ? ' h:i A' : ' H:i';
            $mysql_format = 'Y-m-d H:i:s';

            if (strpos((string) $date, ':') === false) {
                $date .= ' 00:00';
            }
        }

        try {
            return ! empty($date_format) ? Carbon::createFromFormat($date_format, $date)->format($mysql_format) : null;
        } catch (\Exception $e) {
            try {
                return ! empty($date) ? Carbon::parse($date)->format($mysql_format) : null;
            } catch (\Exception $parseException) {
                return null;
            }
        }
    }

    /* ------------------------------------------------------------------ *
     * Private helpers - mirrors of core's equivalents
     * ------------------------------------------------------------------ */

    public function getCurrencyPrecision($business_details = null): int
    {
        if (! empty($business_details) && $business_details->currency_precision !== null && $business_details->currency_precision !== '') {
            return (int) $business_details->currency_precision;
        }

        $session_precision = session('business.currency_precision');

        if ($session_precision !== null && $session_precision !== '') {
            return (int) $session_precision;
        }

        return (int) config('constants.currency_precision', 2);
    }

    public function getQuantityPrecision($business_details = null): int
    {
        if (! empty($business_details) && $business_details->quantity_precision !== null && $business_details->quantity_precision !== '') {
            return (int) $business_details->quantity_precision;
        }

        $session_precision = session('business.quantity_precision');

        if ($session_precision !== null && $session_precision !== '') {
            return (int) $session_precision;
        }

        return (int) config('constants.quantity_precision', 2);
    }

    private function getNumberFormatSettings($business_details = null): array
    {
        $currency = session('currency', []);

        return [
            'thousand_separator' => ! empty($business_details) && isset($business_details->thousand_separator)
                ? $business_details->thousand_separator
                : ($currency['thousand_separator'] ?? ','),
            'decimal_separator' => ! empty($business_details) && isset($business_details->decimal_separator)
                ? $business_details->decimal_separator
                : ($currency['decimal_separator'] ?? '.'),
        ];
    }

    private function formatNumberWithPrecision($input_number, $precision, $decimal_separator = '.', $thousand_separator = ',')
    {
        if ($input_number === null || $input_number === '') {
            $input_number = 0;
        }

        $normalized = (string) $input_number;
        $normalized = str_replace($thousand_separator, '', $normalized);
        // Second pass on the comma is intentional and is in core: it strips
        // stray commas when the thousand separator is something else.
        $normalized = str_replace(',', '', $normalized);
        $normalized = str_replace($decimal_separator, '.', $normalized);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);

        if ($normalized === '' || $normalized === '-' || $normalized === '.') {
            $normalized = 0;
        }

        return number_format((float) $normalized, (int) $precision, $decimal_separator, $thousand_separator);
    }
}
