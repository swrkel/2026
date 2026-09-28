<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('simpleaudit::simpleaudit.module_name'))</title>
    @yield('head')
</head>
<body class="sau-standalone-body">
    <div class="sau-shell">
        <aside class="sau-sidebar">
            <div class="sau-brand">
                <div class="sau-brand-mark">SA</div>
                <div><strong>{{ __('simpleaudit::simpleaudit.module_name') }}</strong><small>{{ __('simpleaudit::simpleaudit.module_tagline') }}</small></div>
            </div>
            <nav>
                <a href="{{ route('simpleaudit.purchase-audit') }}" class="active">
                    <span class="sau-nav-icon">⌕</span>
                    {{ __('simpleaudit::simpleaudit.purchase_audit') }}
                </a>
            </nav>
        </aside>
        <main class="sau-main">@yield('content')</main>
    </div>
    @yield('scripts')
</body>
</html>
