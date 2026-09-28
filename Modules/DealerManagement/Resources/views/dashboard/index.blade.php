@extends('dealermanagement::layouts.portal')
@section('title','Dealer Dashboard')
@section('content')
<div class="dlr-section-title" id="dlr-greeting">Welcome, {{ $user->name }}</div>
<div class="dlr-section-subtitle">{{ $dealer->name ?? 'Dealer' }} · View stock, orders, deliveries and alerts from one place.</div>
<div class="dlr-grid" style="grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px">
@foreach([
 ['Products in Stock',$stockCount,'fa-cubes'],['Re-order Alerts',$reorders,'fa-bell-o'],['Open Orders',$pendingOrders,'fa-shopping-cart'],['Unread Notifications',$notifications,'fa-envelope-o']
] as $k)
<div class="dlr-card dlr-kpi-card"><div class="dlr-kpi">{{ number_format($k[1]) }}</div><div class="dlr-muted">{{ $k[0] }}</div><div class="dlr-kpi-icon"><i class="fa {{ $k[2] }}"></i></div></div>
@endforeach
</div>
<div class="dlr-card"><div class="dlr-section-title" style="font-size:18px">Quick Operations</div><div class="dlr-section-subtitle">Choose an action below.</div><div class="dlr-grid">@foreach($buttons as $i=>$button)<a class="dlr-tile" href="{{ route($button['route']) }}"><i class="fa {{ $button['icon'] }}"></i><strong>{{ $button['title'] }}</strong></a>@endforeach</div></div>
@endsection
@section('scripts')<script>(function(){var d=new Date(),h=d.getHours(),g=h<12?'Good Morning':(h<18?'Good Afternoon':'Good Evening');var e=document.getElementById('dlr-greeting');if(e)e.textContent=g+', {{ addslashes($user->name) }}';})();</script>@endsection
