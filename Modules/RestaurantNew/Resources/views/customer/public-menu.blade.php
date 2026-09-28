<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $menu->title }}</title>
    <link rel="stylesheet" href="{{ asset('modules/restaurantnew/css/customer-experience.css') }}">
</head>
<body class="rn-public-menu">
    <header class="rn-public-header">
        <h1>{{ $menu->title }}</h1>
        <p>{{ __('restaurantnew::messages.scan_order_message') }}</p>
    </header>
    <main class="rn-public-content">
        <div class="rn-public-card">
            <h2>{{ __('restaurantnew::messages.menu_items') }}</h2>
            <p>{{ __('restaurantnew::messages.public_menu_placeholder') }}</p>
        </div>
        @if($menu->allow_self_order)
            <button class="rn-public-primary">{{ __('restaurantnew::messages.start_order') }}</button>
        @endif
    </main>
</body>
</html>
