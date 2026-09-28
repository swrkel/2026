@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::lang.enterprise_kds'))

@section('content')
<section class="content-header restaurantnew-header">
    <h1>{{ __('restaurantnew::lang.enterprise_kds') }}</h1>
</section>
<section class="content restaurantnew-kds-pro" data-refresh-url="{{ route('restaurantnew.kds.enterprise.queue_json') }}">
    <div class="row restnew-command-row">
        <div class="col-md-3"><div class="restnew-card"><span>{{ __('restaurantnew::lang.received') }}</span><strong>{{ $queue->where('current_status','received')->count() }}</strong></div></div>
        <div class="col-md-3"><div class="restnew-card"><span>{{ __('restaurantnew::lang.preparing') }}</span><strong>{{ $queue->whereIn('current_status',['accepted','preparing','cooking'])->count() }}</strong></div></div>
        <div class="col-md-3"><div class="restnew-card"><span>{{ __('restaurantnew::lang.ready') }}</span><strong>{{ $queue->where('current_status','ready')->count() }}</strong></div></div>
        <div class="col-md-3"><div class="restnew-card"><span>{{ __('restaurantnew::lang.overdue') }}</span><strong class="js-kds-overdue">0</strong></div></div>
    </div>

    <div class="box box-primary restnew-box">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('restaurantnew::lang.live_kitchen_queue') }}</h3>
            <div class="box-tools"><a href="{{ route('restaurantnew.kds.enterprise.screens') }}" class="btn btn-sm btn-primary">{{ __('restaurantnew::lang.kds_screens') }}</a></div>
        </div>
        <div class="box-body">
            <div class="row js-kds-board">
                @foreach($queue as $item)
                    <div class="col-md-4 col-sm-6 kds-card-wrap" data-id="{{ $item->id }}">
                        <div class="kds-card priority-{{ $item->priority }} status-{{ $item->current_status }}">
                            <div class="kds-card-head">
                                <strong>{{ $item->order_no ?: '#'.$item->order_id }}</strong>
                                <span>{{ ucfirst(str_replace('_',' ', $item->order_type)) }}</span>
                            </div>
                            <h3>{{ $item->item_name }}</h3>
                            <p>{{ __('restaurantnew::lang.qty') }}: {{ number_format($item->quantity, 3) }}</p>
                            <p>{{ __('restaurantnew::lang.section') }}: {{ $item->kitchen_section ?: '-' }}</p>
                            <p class="kds-note">{{ $item->kitchen_note }}</p>
                            <div class="kds-timer" data-start="{{ optional($item->started_at ?: $item->received_at)->toIso8601String() }}" data-expected="{{ $item->expected_prep_minutes }}">--:--</div>
                            <div class="kds-actions">
                                <button class="btn btn-lg btn-info js-kds-status" data-status="accepted">{{ __('restaurantnew::lang.accept') }}</button>
                                <button class="btn btn-lg btn-warning js-kds-status" data-status="preparing">{{ __('restaurantnew::lang.start') }}</button>
                                <button class="btn btn-lg btn-success js-kds-status" data-status="ready">{{ __('restaurantnew::lang.ready') }}</button>
                                <button class="btn btn-lg btn-default js-kds-status" data-status="collected">{{ __('restaurantnew::lang.collected') }}</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('Modules/RestaurantNew/Resources/assets/js/enterprise-kds.js') }}"></script>
@endpush
