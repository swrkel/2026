@php
$nav=[
 ['restaurant-new.dashboard','Dashboard','fa-dashboard','restaurant_new.dashboard.view'],['restaurant-new.manager.index','Control','fa-line-chart','restaurant_new.manager.view'],
 ['restaurant-new.waiter.index','Waiter','fa-cutlery','restaurant_new.waiter.use'],['restaurant-new.cashier.index','Cashier','fa-calculator','restaurant_new.cashier.use'],
 ['restaurant-new.kitchen.index','Kitchen','fa-fire','restaurant_new.kitchen.use'],['restaurant-new.reservations.index','Reservations','fa-calendar','restaurant_new.reservations.view'],
 ['restaurant-new.delivery.index','Delivery','fa-motorcycle','restaurant_new.delivery.use'],['restaurant-new.orders.index','Orders','fa-list-alt','restaurant_new.orders.view'],
 ['restaurant-new.stock.index','Stock','fa-cubes','restaurant_new.stock.view'],['restaurant-new.reports.index','Reports','fa-bar-chart','restaurant_new.reports.view'],
 ['restaurant-new.settings.index','Settings','fa-cogs','restaurant_new.settings.manage']];
@endphp
<nav class="rest-tabs">@foreach($nav as [$route,$label,$icon,$permission])@can($permission)@if(Route::has($route))<a href="{{ route($route) }}" class="{{ request()->routeIs($route) || request()->routeIs(str_replace('.index','.*',$route)) ? 'active':'' }}"><i class="fa {{ $icon }}"></i>{{ $label }}</a>@endif@endcan@endforeach</nav>
