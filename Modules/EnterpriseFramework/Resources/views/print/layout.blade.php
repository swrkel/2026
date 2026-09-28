<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Enterprise Report' }}</title>
</head>
<body>
    <header>
        <h2>{{ $company ?? config('app.name') }}</h2>
        <p>{{ $title ?? 'Enterprise Report' }}</p>
        <p>{{ $branch ?? 'Consolidated' }} | Generated: {{ now()->format(config('enterpriseframework.datetime_format', 'Y-m-d H:i:s')) }}</p>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
