<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('restaurantnew::messages.order_status') }}</title>
    <link rel="stylesheet" href="{{ asset('modules/restaurantnew/css/customer-experience.css') }}">
</head>
<body class="rn-public-menu">
    <main class="rn-public-content">
        <div class="rn-public-card">
            <h1>{{ __('restaurantnew::messages.order_status') }}</h1>
            <p>{{ __('restaurantnew::messages.order_no') }}: {{ $order->order_no ?? $link->restaurant_order_id }}</p>
            <p>{{ __('restaurantnew::messages.status') }}: <strong>{{ $order->status ?? 'received' }}</strong></p>
        </div>
    </main>
</body>
</html>
