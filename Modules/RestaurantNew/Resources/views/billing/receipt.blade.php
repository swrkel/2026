<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $bill->bill_no }}</title>
    <link rel="stylesheet" href="{{ asset('modules/restaurantnew/css/restaurantnew_billing.css') }}">
</head>
<body class="restaurantnew-receipt" onload="window.print()">
    <div class="receipt-box">
        <h3>@lang('restaurantnew::lang.restaurant_receipt')</h3>
        <p><strong>@lang('restaurantnew::lang.bill_no'):</strong> {{ $bill->bill_no }}</p>
        <p><strong>@lang('restaurantnew::lang.date'):</strong> {{ optional($bill->bill_date)->format('Y-m-d H:i') }}</p>
        @include('restaurantnew::billing.partials.bill-lines')
        @include('restaurantnew::billing.partials.summary-card')
        <p class="text-center">@lang('restaurantnew::lang.thank_you')</p>
    </div>
</body>
</html>
