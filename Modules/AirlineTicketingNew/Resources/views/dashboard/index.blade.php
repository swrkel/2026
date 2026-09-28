@extends('airlineticketingnew::layouts.app')

@section('atn-title', __('airlineticketingnew::messages.dashboard'))

@section('atn-content')
<div class="atn-command-center">
    <div class="atn-toolbar">
        <div>
            <h3>{{ __('airlineticketingnew::messages.command_center') }}</h3>
            <p>{{ __('airlineticketingnew::messages.command_center_help') }}</p>
        </div>
        <div class="atn-toolbar-actions">
            <a href="#" class="btn btn-primary disabled">
                <i class="fa fa-plus"></i> {{ __('airlineticketingnew::messages.new_booking') }}
            </a>
        </div>
    </div>

    <div class="row atn-summary-row">
        @foreach ([
            ['key' => 'today_bookings', 'icon' => 'fa-calendar-check-o'],
            ['key' => 'today_tickets', 'icon' => 'fa-ticket'],
            ['key' => 'pending_ticketing', 'icon' => 'fa-clock-o'],
            ['key' => 'pending_refunds', 'icon' => 'fa-undo'],
        ] as $card)
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="atn-summary-card">
                    <div class="atn-summary-icon"><i class="fa {{ $card['icon'] }}"></i></div>
                    <div>
                        <div class="atn-summary-label">{{ __('airlineticketingnew::messages.' . $card['key']) }}</div>
                        <div class="atn-summary-value">{{ number_format($summary[$card['key']] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="atn-panel">
        <div class="atn-panel-header">
            <h4>{{ __('airlineticketingnew::messages.foundation_status') }}</h4>
        </div>
        <div class="atn-panel-body">
            <div class="alert alert-info">
                {{ __('airlineticketingnew::messages.foundation_ready') }}
            </div>
        </div>
    </div>
</div>
@endsection
