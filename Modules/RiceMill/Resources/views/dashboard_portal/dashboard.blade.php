@extends('layouts.app')

@section('title', 'Rice Mill Dashboard')

@section('content')
<style>
.rcm-portal{padding:24px;background:#f4f7fb;min-height:calc(100vh - 50px)}.rcm-portal-head{background:linear-gradient(135deg,#173d2d,#2b7554);color:#fff;border-radius:20px;padding:26px 30px;display:flex;align-items:center;justify-content:space-between;gap:20px;box-shadow:0 12px 28px rgba(15,23,42,.14);margin-bottom:24px}.rcm-portal-brand{display:flex;align-items:center;gap:16px}.rcm-portal-icon{width:58px;height:58px;border-radius:16px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:28px}.rcm-portal-head h1{margin:0 0 4px;font-weight:800;font-size:27px}.rcm-portal-head p{margin:0;color:#dff3e8}.rcm-portal-user{text-align:right}.rcm-portal-user strong{display:block;font-size:15px}.rcm-logout{display:inline-block;margin-top:9px;padding:8px 13px;border:1px solid rgba(255,255,255,.45);border-radius:9px;background:transparent;color:#fff}.rcm-section-title{font-size:14px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;color:#64748b;margin:24px 0 12px}.rcm-portal-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.rcm-portal-card{background:#fff;border:1px solid #e5e7eb;border-radius:17px;padding:22px;text-decoration:none!important;color:#1f2937!important;box-shadow:0 5px 16px rgba(15,23,42,.06);transition:.18s ease;min-height:150px;display:flex;flex-direction:column}.rcm-portal-card:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(15,23,42,.1);border-color:#cbd5e1}.rcm-card-icon{width:48px;height:48px;border-radius:13px;display:flex;align-items:center;justify-content:center;background:#ecfdf3;color:#188a50;font-size:22px;margin-bottom:18px}.rcm-portal-card strong{font-size:17px;margin-bottom:5px}.rcm-portal-card small{color:#64748b;line-height:1.45}.rcm-empty{background:#fff;border-radius:16px;padding:32px;text-align:center;border:1px solid #e5e7eb;color:#64748b}@media(max-width:1100px){.rcm-portal-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:640px){.rcm-portal{padding:14px}.rcm-portal-head{align-items:flex-start;flex-direction:column}.rcm-portal-user{text-align:left}.rcm-portal-grid{grid-template-columns:1fr}}
</style>
<div class="rcm-portal">
    <div class="rcm-portal-head">
        <div class="rcm-portal-brand">
            <div class="rcm-portal-icon"><i class="fa fa-industry"></i></div>
            <div>
                <h1>Rice Mill Dashboard</h1>
                <p>{{ $business?->name ?? 'Rice Mill' }}</p>
            </div>
        </div>
        <div class="rcm-portal-user">
            <strong>{{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->username ?? 'User') }}</strong>
            <span>Permission-controlled operational access</span>
            <form method="POST" action="{{ route('rice-mill-dashboard.logout') }}">
                @csrf
                <button type="submit" class="rcm-logout"><i class="fa fa-sign-out"></i> Logout</button>
            </form>
        </div>
    </div>

    @php $main = collect($actions)->whereNull('group'); $stock = collect($actions)->where('group', 'Stock Status'); @endphp

    @if($main->isNotEmpty())
        <div class="rcm-section-title">Operations</div>
        <div class="rcm-portal-grid">
            @foreach($main as $action)
                <a class="rcm-portal-card" href="{{ route($action['route']) }}">
                    <span class="rcm-card-icon"><i class="{{ $action['icon'] }}"></i></span>
                    <strong>{{ $action['label'] }}</strong>
                    <small>{{ $action['description'] }}</small>
                </a>
            @endforeach
        </div>
    @endif

    @if($stock->isNotEmpty())
        <div class="rcm-section-title">Stock Status</div>
        <div class="rcm-portal-grid">
            @foreach($stock as $action)
                <a class="rcm-portal-card" href="{{ route($action['route']) }}">
                    <span class="rcm-card-icon"><i class="{{ $action['icon'] }}"></i></span>
                    <strong>{{ $action['label'] }}</strong>
                    <small>{{ $action['description'] }}</small>
                </a>
            @endforeach
        </div>
    @endif

    @if(empty($actions))
        <div class="rcm-empty"><i class="fa fa-lock fa-2x"></i><h3>No Dashboard Functions Assigned</h3><p>Ask the administrator to assign the required Rice Mill permissions in User Management New.</p></div>
    @endif
</div>
@endsection
