@php
$restNewActive=request()->is('restaurant-new')||request()->is('restaurant-new/*');$restNewItems=[];
try{$defs=[
 ['restaurant-new.dashboard','Dashboard','fa fa-dashboard','restaurant_new.dashboard.view'],['restaurant-new.manager.index','Control Centre','fa fa-line-chart','restaurant_new.manager.view'],
 ['restaurant-new.waiter.index','Waiter Screen','fa fa-cutlery','restaurant_new.waiter.use'],['restaurant-new.cashier.index','Cashier Screen','fa fa-calculator','restaurant_new.cashier.use'],
 ['restaurant-new.kitchen.index','Kitchen Display','fa fa-fire','restaurant_new.kitchen.use'],['restaurant-new.takeaway.index','Takeaway','fa fa-shopping-bag','restaurant_new.takeaway.use'],
 ['restaurant-new.collection.index','Collection Centre','fa fa-bell','restaurant_new.collection.use'],['restaurant-new.reservations.index','Reservations','fa fa-calendar','restaurant_new.reservations.view'],
 ['restaurant-new.delivery.index','Delivery Operations','fa fa-motorcycle','restaurant_new.delivery.use'],['restaurant-new.orders.index','Orders','fa fa-list-alt','restaurant_new.orders.view'],
 ['restaurant-new.shifts.index','Shifts','fa fa-clock-o','restaurant_new.shifts.view'],['restaurant-new.menu.index','Menu Setup','fa fa-book','restaurant_new.menu.view'],
 ['restaurant-new.modifiers.index','Menu Modifiers','fa fa-list-ul','restaurant_new.menu.manage'],['restaurant-new.discounts.index','Discount Rules','fa fa-percent','restaurant_new.discounts.manage'],
 ['restaurant-new.setup.index','Configuration','fa fa-wrench','restaurant_new.setup.manage'],['restaurant-new.recipes.index','Recipes','fa fa-flask','restaurant_new.recipes.manage'],
 ['restaurant-new.stock.index','Ingredient Stock','fa fa-cubes','restaurant_new.stock.view'],['restaurant-new.procurement.suppliers','Suppliers','fa fa-address-book','restaurant_new.procurement.view'],
 ['restaurant-new.procurement.receipts','Goods Receipts','fa fa-truck','restaurant_new.procurement.view'],['restaurant-new.transfers.index','Stock Transfers','fa fa-exchange','restaurant_new.stock.transfer'],
 ['restaurant-new.stocktakes.index','Stocktakes','fa fa-check-square-o','restaurant_new.stock.stocktake'],['restaurant-new.wastage.index','Wastage','fa fa-trash','restaurant_new.stock.wastage'],
 ['restaurant-new.reports.index','Reports','fa fa-bar-chart','restaurant_new.reports.view'],['restaurant-new.settings.index','Settings','fa fa-cogs','restaurant_new.settings.manage']];
 foreach($defs as [$route,$label,$icon,$permission])if(Route::has($route)&&auth()->user()?->can($permission))$restNewItems[]=compact('route','label','icon','permission');
}catch(\Throwable $e){$restNewItems=[];}
@endphp
@if($restNewItems)<li class="nav-item {{ $restNewActive?'active active-sub':'' }}" data-sidebar-module="restaurant_new"><a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#restaurant-new-menu" aria-expanded="{{ $restNewActive?'true':'false' }}"><i class="fa fa-cutlery"></i><span>Restaurant-New</span></a><div id="restaurant-new-menu" class="collapse {{ $restNewActive?'show':'' }}" data-parent="#accordionSidebar"><div class="bg-white py-2 collapse-inner rounded"><h6 class="collapse-header">Restaurant-New:</h6>@foreach($restNewItems as $item)<a class="collapse-item {{ request()->routeIs($item['route'])?'active':'' }}" href="{{ route($item['route']) }}"><i class="{{ $item['icon'] }} mr-1"></i>{{ $item['label'] }}</a>@endforeach</div></div></li>@endif
