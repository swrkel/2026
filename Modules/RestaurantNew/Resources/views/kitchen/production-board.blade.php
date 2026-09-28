@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.kitchen_production_board'))
@section('content')
<div class="rn-page rn-kitchen-board">
    <div class="rn-header">
        <h3>@lang('restaurantnew::lang.kitchen_production_board')</h3>
        <div class="rn-actions">
            <button class="btn btn-primary" id="rn-refresh-kitchen-board">@lang('restaurantnew::lang.refresh')</button>
        </div>
    </div>
    <div class="rn-board-columns" id="rn-kitchen-board" data-url="{{ route('restaurantnew.kitchen.production.data') }}">
        @foreach(['received' => 'Received Orders', 'preparing' => 'Preparing', 'ready' => 'Ready to Serve'] as $status => $title)
            <div class="rn-board-column" data-status="{{ $status }}">
                <div class="rn-board-title">{{ $title }}</div>
                <div class="rn-board-items">
                    @foreach($queues->where('status', $status) as $queue)
                        <div class="rn-kitchen-card rn-priority-{{ $queue->priority }}" data-id="{{ $queue->id }}">
                            <div class="rn-card-top"><strong>{{ $queue->queue_no }}</strong><span>{{ strtoupper($queue->priority) }}</span></div>
                            <div>@lang('restaurantnew::lang.order') #{{ $queue->order_id }}</div>
                            <div>@lang('restaurantnew::lang.received_at'): {{ optional($queue->received_at)->format('H:i') }}</div>
                            <div>@lang('restaurantnew::lang.estimate'): {{ $queue->estimated_minutes }} min</div>
                            <div class="rn-card-actions">
                                @if($status === 'received')<button data-action="preparing" class="btn btn-sm btn-warning">Start</button>@endif
                                @if($status === 'preparing')<button data-action="ready" class="btn btn-sm btn-success">Ready</button>@endif
                                @if($status === 'ready')<button data-action="served" class="btn btn-sm btn-info">Served</button>@endif
                                <button data-action="print" class="btn btn-sm btn-secondary">Print</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
@push('javascript')
<script src="{{ asset('Modules/RestaurantNew/Resources/assets/js/kitchen-production.js') }}"></script>
@endpush
