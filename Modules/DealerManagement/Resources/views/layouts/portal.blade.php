<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', __('dealermanagement::general.module_name'))</title>
<link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('modules/dealermanagement/css/dealer.css') }}">
<style>{!! file_exists(base_path('Modules/DealerManagement/Resources/css/dealer.css')) ? file_get_contents(base_path('Modules/DealerManagement/Resources/css/dealer.css')) : '' !!}</style>
</head><body data-currency-precision="{{ (int) session('business.currency_precision', config('constants.currency_precision', 2)) }}" data-quantity-precision="{{ (int) session('business.quantity_precision', config('constants.quantity_precision', 2)) }}">
@php($me = app(\Modules\DealerManagement\Services\DealerContext::class)->user())
<div class="dlr-shell">
<aside class="dlr-sidebar" id="dlrSidebar">
  <div class="dlr-logo">SYZYGY</div>
  <div class="dlr-module-select">Dealer Management <i class="fa fa-angle-down"></i></div>
  <nav class="dlr-nav">
    @php($links=[
      ['dealermanagement.portal.dashboard','fa-home','Dashboard'],['dealermanagement.portal.stock.index','fa-cubes','My Stock'],['dealermanagement.portal.stock.update.form','fa-check-square-o','Update Stock'],['dealermanagement.portal.stock.history','fa-history','Stock History'],['dealermanagement.portal.reorder.index','fa-bell-o','Re-order Levels'],['dealermanagement.portal.orders.index','fa-shopping-cart','Orders'],['dealermanagement.portal.deliveries.index','fa-truck','Deliveries'],['dealermanagement.portal.returns.index','fa-reply','Returns'],['dealermanagement.portal.notifications.index','fa-bell','Notifications'],['dealermanagement.portal.reports.index','fa-bar-chart','Reports'],['dealermanagement.portal.outlets.index','fa-map-marker','Outlets'],['dealermanagement.portal.users.index','fa-users','Users'],['dealermanagement.portal.roles.index','fa-key','Roles']
    ])
    @foreach($links as $l) @if(Route::has($l[0]))<a class="{{ request()->routeIs($l[0]) ? 'active':'' }}" href="{{ route($l[0]) }}"><i class="fa {{ $l[1] }}"></i><span>{{ $l[2] }}</span></a>@endif @endforeach
  </nav>
  <button type="button" class="dlr-collapse" onclick="document.body.classList.toggle('dlr-sidebar-collapsed')"><i class="fa fa-angle-double-left"></i> Collapse</button>
</aside>
<main class="dlr-main">
<header class="dlr-topbar">
  <div><div class="dlr-module-caption">DEALER MANAGEMENT</div><div class="dlr-page-caption">@yield('title','Dashboard')</div></div>
  <div class="dlr-top-actions"><a title="Multi-Distributor Dealer Hub" href="{{ route('dealermanagement.hub.login') }}"><i class="fa fa-globe"></i></a><div class="dlr-date-chip"><i class="fa fa-calendar"></i> <span id="dlr-top-clock"></span></div><a title="Notifications" href="{{ route('dealermanagement.portal.notifications.index') }}"><i class="fa fa-bell-o"></i></a><a title="Login Code" href="{{ route('dealermanagement.portal.profile.login-code') }}"><i class="fa fa-key"></i></a><a title="Password" href="{{ route('dealermanagement.portal.profile.password') }}"><i class="fa fa-lock"></i></a><form method="post" action="{{ route('dealermanagement.logout') }}">@csrf<button title="Logout"><i class="fa fa-sign-out"></i></button></form></div>
</header>
<div class="dlr-wrap">
@if(session('status'))<div class="dlr-alert success"><i class="fa fa-check-circle"></i> {{ session('status') }}</div>@endif
@if(session('error'))<div class="dlr-alert error"><i class="fa fa-exclamation-circle"></i> {{ session('error') }}</div>@endif
@if($errors->any())<div class="dlr-alert error"><i class="fa fa-exclamation-circle"></i><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
@yield('content')
</div>
</main></div>
<div class="dlr-modal-backdrop"><div class="dlr-modal"><button type="button" class="dlr-modal-close" onclick="this.closest('.dlr-modal-backdrop').classList.remove('show')">×</button><h3>Note</h3><div class="dlr-modal-text"></div></div></div>
<script src="{{ asset('modules/dealermanagement/js/dealer.js') }}"></script>
<script>setInterval(function(){var e=document.getElementById('dlr-top-clock');if(e)e.textContent=new Date().toLocaleString();},1000);document.dispatchEvent(new Event('dlr:ready'));</script>
@yield('scripts')</body></html>
