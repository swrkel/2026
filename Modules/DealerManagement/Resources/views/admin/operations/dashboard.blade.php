@extends('layouts.app')
@section('title','Dealer Management Dashboard')
@section('content')
@include('dealermanagement::partials.system-standard')
<section class="content-header"><h1>Dealer Management Dashboard</h1><div class="dlr-page-intro">Monitor dealer stock, re-order requirements, orders and alerts from one workspace.</div></section>
<section class="content">
@if(($stats['dealers'] ?? 0) === 0)<div class="alert alert-info"><i class="fa fa-info-circle"></i> No dealers have been created yet. All Dealer Management pages remain available and will populate automatically as dealer activity is recorded.</div>@endif
<div class="row">
@foreach([['Dealers',$stats['dealers'],'users'],['Outlets',$stats['outlets'],'map-marker'],['Active Dealer Users',$stats['users'],'user'],['Low / Re-order Stock',$stats['low_stock'],'warning'],['Pending Dealer Orders',$stats['pending_orders'],'shopping-cart'],['Unread Alerts',$stats['unread_notifications'],'bell']] as $s)
<div class="col-lg-4 col-md-4 col-sm-6"><div class="small-box"><div class="inner"><h3>{{ number_format($s[1]) }}</h3><p>{{ $s[0] }}</p></div><div class="icon"><i class="fa fa-{{ $s[2] }}"></i></div></div></div>
@endforeach
</div>
<div class="box"><div class="box-header"><h3 class="box-title"><i class="fa fa-bolt"></i> Quick Operations</h3></div><div class="box-body"><div class="row">
@foreach([
 ['dealermanagement.admin.dealers.index','fa-handshake-o','Dealers'],['dealermanagement.admin.outlets.index','fa-map-marker','Dealer Outlets'],['dealermanagement.admin.users.index','fa-users','Dealer Users'],['dealermanagement.admin.stock.index','fa-cubes','Stock Balances'],['dealermanagement.admin.hub-sales.index','fa-line-chart','Hub Dealer Sales'],['dealermanagement.admin.stock.updates','fa-check-square-o','Stock Updates'],['dealermanagement.admin.reorder.index','fa-bell-o','Re-order Planning'],['dealermanagement.admin.orders.index','fa-shopping-cart','Dealer Orders'],['dealermanagement.admin.deliveries.index','fa-truck','Deliveries'],['dealermanagement.admin.returns.index','fa-reply','Returns'],['dealermanagement.admin.notifications.index','fa-bell','Notifications'],['dealermanagement.admin.reports.index','fa-bar-chart','Reports'],['dealermanagement.admin.sync.index','fa-refresh','Distribution Sync']
] as $q)
<div class="col-lg-3 col-md-4 col-sm-6" style="margin-bottom:12px"><a class="dlr-quick-card" href="{{ route($q[0]) }}"><i class="fa {{ $q[1] }}"></i><span>{{ $q[2] }}</span></a></div>
@endforeach
</div></div></div>
</section>
@endsection
