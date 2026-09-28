<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('pumperdashboardnew::lang.operator_display') }}</title>
    <link rel="stylesheet" href="{{ route('pumper-dashboard-new.assets.show', ['type' => 'css', 'file' => 'operator-login-dashboard.css', 'v' => config('pumperdashboardnew.asset_version')]) }}">
</head>
<body class="pone-legacy-login-body">
<main class="pone-legacy-login-page" aria-labelledby="pone-login-title">
    <header class="pone-legacy-selected-business" title="{{ $business->name ?? 'Business' }}">
        {{ $business->name ?? 'Business' }}
    </header>

    <section class="pone-legacy-login-left">
        <div class="pone-legacy-welcome"><h1>Welcome</h1></div>
        <div class="pone-legacy-login-card">
            <h2 id="pone-login-title">Pump Operator Dashboard - New</h2>
            <p class="pone-legacy-lock-status"><span aria-hidden="true">&#128274;</span> Locked</p>

            @if ($errors->any())
                <div class="pone-legacy-alert pone-legacy-alert-danger" role="alert">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif
            @if (session('status'))
                @php($status = session('status'))
                <div class="pone-legacy-alert {{ is_array($status) && !empty($status['success']) ? 'pone-legacy-alert-success' : 'pone-legacy-alert-danger' }}" role="status">
                    {{ is_array($status) ? ($status['msg'] ?? '') : $status }}
                </div>
            @endif
            @if (isset($schemaReady) && ! $schemaReady)
                <div class="pone-legacy-alert pone-legacy-alert-warning" role="alert">The Pumper Dashboard-New database setup is incomplete for this tenant.</div>
            @endif

            <form method="post" action="{{ route('pumper-dashboard-new.login.store') }}" autocomplete="off" data-pone-operator-login-form>
                @csrf
                <input type="hidden" name="company_number" value="{{ $companyNumber }}">
                <label class="pone-legacy-passcode-wrap" for="pone-passcode">
                    <span class="pone-legacy-passcode-icon" aria-hidden="true">&#128274;</span>
                    <input id="pone-passcode" name="passcode" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="20" autocomplete="one-time-code" placeholder="Passcode" aria-label="Passcode" required autofocus data-pone-passcode>
                </label>
                <button type="submit" class="pone-legacy-enter-button" data-pone-login-submit>
                    <span data-pone-login-submit-label>Click to Enter</span><span class="pone-legacy-button-spinner" aria-hidden="true"></span>
                </button>
            </form>
        </div>
    </section>

    <section class="pone-legacy-login-right" aria-label="Passcode keypad">
        <div class="pone-legacy-login-clock" data-pone-clock data-timezone="Asia/Colombo">
            <div><strong>Today:</strong> <span data-pone-clock-date>{{ now()->format('m-d-Y') }}</span></div>
            <div><strong>Time:</strong> <span data-pone-clock-time>{{ now()->format('H:i:s') }}</span></div>
        </div>
        <div class="pone-legacy-keypad" data-pone-keypad>
            @foreach ([7,8,9,4,5,6,1,2,3] as $digit)
                <button type="button" class="pone-legacy-key" data-pone-key="{{ $digit }}" aria-label="{{ $digit }}">{{ $digit }}</button>
            @endforeach
            <button type="button" class="pone-legacy-key pone-legacy-key-delete" data-pone-key-action="backspace" aria-label="Delete last digit">&#9003;</button>
            <button type="button" class="pone-legacy-key" data-pone-key="0" aria-label="0">0</button>
            <button type="button" class="pone-legacy-key pone-legacy-key-enter" data-pone-key-action="submit" aria-label="Submit passcode">&#8629;</button>
        </div>
    </section>
</main>
<script src="{{ route('pumper-dashboard-new.assets.show', ['type' => 'js', 'file' => 'operator-login-dashboard.js', 'v' => config('pumperdashboardnew.asset_version')]) }}"></script>
</body>
</html>
