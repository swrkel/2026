{{--
    ZIP 31 - Global Currency Fix V2
    Path: resources/views/layouts/partials/global_currency_inputs.blade.php

    Stronger fix than ZIP 29:
    - Reads the active business currency directly from the current tenant database.
    - Does not depend only on stale session('currency').
    - Updates both session('currency') and session('business') currency keys.
    - Outputs the normal hidden currency inputs used by the ERP JavaScript.
--}}
@php
    use Illuminate\Support\Facades\DB;

    $activeBusinessId = session()->get('user.business_id')
        ?? data_get(session()->get('business'), 'id')
        ?? session()->get('business.id');

    $sessionCurrency = session()->get('currency', []);
    $sessionBusiness = session()->get('business', []);

    $businessCurrency = null;
    $activeBusiness = null;

    try {
        if (!empty($activeBusinessId) && DB::getSchemaBuilder()->hasTable('business')) {
            $activeBusiness = DB::table('business')->where('id', $activeBusinessId)->first();

            if (!empty($activeBusiness) && !empty($activeBusiness->currency_id) && DB::getSchemaBuilder()->hasTable('currencies')) {
                $businessCurrency = DB::table('currencies')->where('id', $activeBusiness->currency_id)->first();
            }
        }
    } catch (\Throwable $e) {
        try {
            \Log::warning('ZIP31 currency DB lookup failed', [
                'business_id' => $activeBusinessId,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $ignore) {}
    }

    $currencySymbol = data_get($businessCurrency, 'symbol')
        ?? data_get($businessCurrency, 'currency_symbol')
        ?? data_get($activeBusiness, 'currency_symbol')
        ?? data_get($sessionCurrency, 'symbol')
        ?? data_get($sessionBusiness, 'currency_symbol')
        ?? '$';

    $currencyCode = data_get($businessCurrency, 'code')
        ?? data_get($businessCurrency, 'currency_code')
        ?? data_get($activeBusiness, 'currency_code')
        ?? data_get($sessionCurrency, 'code')
        ?? data_get($sessionBusiness, 'currency_code')
        ?? '';

    // If a non-USD tenant currency has an incorrect/stale '$' symbol in session/table,
    // show the selected currency code instead of misleading users with '$'.
    if ((empty($currencySymbol) || trim((string) $currencySymbol) === '$') && strtoupper((string) $currencyCode) !== 'USD') {
        $currencySymbol = $currencyCode;
    }

    $thousandSeparator = data_get($businessCurrency, 'thousand_separator')
        ?? data_get($activeBusiness, 'thousand_separator')
        ?? data_get($sessionCurrency, 'thousand_separator')
        ?? data_get($sessionBusiness, 'thousand_separator')
        ?? ',';

    $decimalSeparator = data_get($businessCurrency, 'decimal_separator')
        ?? data_get($activeBusiness, 'decimal_separator')
        ?? data_get($sessionCurrency, 'decimal_separator')
        ?? data_get($sessionBusiness, 'decimal_separator')
        ?? '.';

    $currencyPrecision = data_get($activeBusiness, 'currency_precision')
        ?? data_get($sessionBusiness, 'currency_precision')
        ?? config('constants.currency_precision', 2);

    $quantityPrecision = data_get($activeBusiness, 'quantity_precision')
        ?? data_get($sessionBusiness, 'quantity_precision')
        ?? config('constants.quantity_precision', 2);

    $symbolPlacement = data_get($activeBusiness, 'currency_symbol_placement')
        ?? data_get($sessionBusiness, 'currency_symbol_placement')
        ?? 'before';

    $currencyId = data_get($businessCurrency, 'id')
        ?? data_get($activeBusiness, 'currency_id')
        ?? data_get($sessionCurrency, 'id')
        ?? '';

    $freshCurrencySession = [
        'id' => $currencyId,
        'code' => $currencyCode,
        'symbol' => $currencySymbol,
        'thousand_separator' => $thousandSeparator,
        'decimal_separator' => $decimalSeparator,
    ];

    session()->put('currency', $freshCurrencySession);
    session()->put('business.currency_id', $currencyId);
    session()->put('business.currency_code', $currencyCode);
    session()->put('business.currency_symbol', $currencySymbol);
    session()->put('business.currency_precision', $currencyPrecision);
    session()->put('business.quantity_precision', $quantityPrecision);
    session()->put('business.currency_symbol_placement', $symbolPlacement);
    session()->put('business.thousand_separator', $thousandSeparator);
    session()->put('business.decimal_separator', $decimalSeparator);
@endphp

<!-- Add currency related field - ZIP 31 authoritative business currency -->
<input type="hidden" id="__code" value="{{ $currencyCode }}">
<input type="hidden" id="__symbol" value="{{ $currencySymbol }}">
<input type="hidden" id="__thousand" value="{{ $thousandSeparator }}">
<input type="hidden" id="__decimal" value="{{ $decimalSeparator }}">
<input type="hidden" id="__symbol_placement" value="{{ $symbolPlacement }}">
<input type="hidden" id="__precision" value="{{ $currencyPrecision }}">
<input type="hidden" id="__quantity_precision" value="{{ $quantityPrecision }}">
<input type="hidden" id="zip31_business_currency_symbol" value="{{ $currencySymbol }}">
<input type="hidden" id="zip31_business_currency_code" value="{{ $currencyCode }}">
<!-- End of currency related field - ZIP 31 -->

<script>
    window.ZIP201_GLOBAL_CURRENCY_SYMBOL_FIX = true;
    window.business_currency_symbol = @json($currencySymbol);
    window.business_currency_code = @json($currencyCode);
    window.business_currency_precision = @json((int) $currencyPrecision);
    window.business_currency_symbol_placement = @json($symbolPlacement);
</script>
