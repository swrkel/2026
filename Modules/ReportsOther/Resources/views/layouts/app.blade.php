<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('reportsother::messages.module_name'))</title>
    <link rel="stylesheet" href="{{ route('reports-other.asset', ['type' => 'css', 'file' => 'reports-other.css', 'v' => '20260920-v8']) }}">
</head>
<body>
<header class="reo-topbar">
    <div>
        <div class="reo-eyebrow">{{ __('reportsother::messages.module_name') }}</div>
        <h1>@yield('page-title', __('reportsother::messages.module_name'))</h1>
    </div>
    <a class="reo-btn reo-btn-light" href="{{ config('reportsother.dashboard_url') }}">Dashboard</a>
</header>
<main class="reo-shell">
    @if(session('status'))
        <div class="reo-alert reo-alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="reo-alert reo-alert-danger">
            <strong>Please correct the following:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>

<div class="reo-modal" data-reo-share-modal hidden>
    <div class="reo-modal-backdrop" data-reo-modal-close></div>
    <div class="reo-modal-card" role="dialog" aria-modal="true" aria-labelledby="reo-share-title">
        <div class="reo-modal-head">
            <div>
                <div class="reo-eyebrow reo-modal-eyebrow">Reports - Other</div>
                <h3 id="reo-share-title" data-reo-share-title>Share Report</h3>
            </div>
            <button type="button" class="reo-modal-close" data-reo-modal-close aria-label="Close">×</button>
        </div>
        <form data-reo-share-form>
            <input type="hidden" name="channel" data-reo-share-channel>
            <label class="reo-label" data-reo-recipient-label for="reo-share-recipient">Recipient</label>
            <input class="reo-input" id="reo-share-recipient" name="recipient" type="text" data-reo-share-recipient autocomplete="off">
            <div class="reo-help" data-reo-recipient-help></div>
            <label class="reo-label reo-share-message-label" for="reo-share-message">Message</label>
            <textarea class="reo-input reo-textarea" id="reo-share-message" name="message" rows="3" data-reo-share-message></textarea>
            <div class="reo-modal-status" data-reo-share-status></div>
            <div class="reo-actions">
                <button class="reo-btn reo-btn-primary" type="submit" data-reo-share-submit>Send</button>
                <button class="reo-btn reo-btn-light" type="button" data-reo-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="reo-modal" data-reo-audit-modal hidden>
    <div class="reo-modal-backdrop" data-reo-audit-close></div>
    <div class="reo-modal-card reo-modal-wide" role="dialog" aria-modal="true" aria-labelledby="reo-audit-title">
        <div class="reo-modal-head">
            <h3 id="reo-audit-title">Edited Details</h3>
            <button type="button" class="reo-modal-close" data-reo-audit-close aria-label="Close">×</button>
        </div>
        <div data-reo-audit-content></div>
    </div>
</div>

<script src="{{ route('reports-other.asset', ['type' => 'js', 'file' => 'reports-other.js', 'v' => '20260920-v8']) }}"></script>
</body>
</html>
