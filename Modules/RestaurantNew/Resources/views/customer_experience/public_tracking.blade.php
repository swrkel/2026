<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Tracking</title>
    <link rel="stylesheet" href="{{ asset('modules/restaurantnew/css/customer-experience.css') }}">
</head>
<body class="rn-public-body">
    <div class="rn-public-card">
        <h2>Your Order Status</h2>
        <div class="rn-status-pill">{{ ucwords(str_replace('_', ' ', $tracking->current_status)) }}</div>
        <p>Last update: {{ optional($tracking->last_status_at)->format('Y-m-d H:i') }}</p>
        @if(!empty($tracking->public_payload))
            <pre>{{ json_encode($tracking->public_payload, JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>
</body>
</html>
