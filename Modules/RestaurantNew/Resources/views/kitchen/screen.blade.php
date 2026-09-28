@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::restaurantnew.kitchen_screen'))
@section('content')
<div class="rn-page rn-kitchen-screen">
    <div class="rn-header"><h3>{{ __('restaurantnew::restaurantnew.kitchen_received_orders') }}</h3><a class="btn btn-primary" href="{{ route('restaurantnew.sales.create') }}">{{ __('restaurantnew::restaurantnew.create_sale') }}</a></div>
    <div class="rn-toolbar"><a class="btn btn-default" href="{{ route('restaurantnew.kitchen.screen') }}">All</a><a class="btn btn-info" href="?status=received">Received</a><a class="btn btn-warning" href="?status=preparing">Preparing</a><a class="btn btn-success" href="?status=ready">Ready</a></div>
    <div class="rn-kitchen-grid">
        @forelse($orders as $queue)
        <div class="rn-kot-card rn-status-{{ $queue->status }}">
            <div class="rn-kot-head"><strong>{{ $queue->queue_no }}</strong><span>{{ strtoupper($queue->status) }}</span></div>
            <p>Received: {{ optional($queue->received_at)->format('Y-m-d H:i') }}</p>
            <ul>@foreach(optional($queue->order)->lines ?? [] as $line)<li><b>{{ number_format($line->quantity,3) }}</b> × {{ $line->menu_item_name }} @if($line->note)<small>({{ $line->note }})</small>@endif</li>@endforeach</ul>
            <div class="rn-actions">
                <a target="_blank" class="btn btn-sm btn-dark" href="{{ route('restaurantnew.kitchen.kot.print', $queue->id) }}">Print KOT/Bill</a>
                @foreach(['preparing'=>'Preparing','ready'=>'Ready','served'=>'Served'] as $key=>$label)
                <form method="POST" action="{{ route('restaurantnew.kitchen.status', $queue->id) }}">@csrf<input type="hidden" name="status" value="{{ $key }}"><button class="btn btn-sm btn-primary">{{ $label }}</button></form>
                @endforeach
            </div>
        </div>
        @empty <div class="rn-card">No received orders.</div> @endforelse
    </div>
    {{ $orders->links() }}
</div>
@endsection
