@extends('airlineticketingnew::layouts.app')

@section('atn-title', __('airlineticketingnew::messages.settings'))

@section('atn-content')
<div class="atn-panel">
    <div class="atn-panel-header">
        <h4>{{ __('airlineticketingnew::messages.general_settings') }}</h4>
    </div>

    <form method="POST" action="{{ route('airline-ticketing-new.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="atn-panel-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('airlineticketingnew::messages.default_currency_code') }}</label>
                        <input class="form-control" maxlength="3" name="default_currency_code"
                               value="{{ old('default_currency_code', $settings['default_currency_code'] ?? '') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('airlineticketingnew::messages.default_service_fee') }}</label>
                        <input class="form-control input_number" name="default_service_fee"
                               value="{{ old('default_service_fee', $settings['default_service_fee'] ?? '0') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('airlineticketingnew::messages.booking_prefix') }}</label>
                        <input class="form-control" required name="booking_prefix"
                               value="{{ old('booking_prefix', $settings['booking_prefix'] ?? 'ATB') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('airlineticketingnew::messages.ticket_prefix') }}</label>
                        <input class="form-control" required name="ticket_prefix"
                               value="{{ old('ticket_prefix', $settings['ticket_prefix'] ?? 'ATT') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="atn-panel-footer text-right">
            <button class="btn btn-primary" type="submit">
                <i class="fa fa-save"></i> {{ __('airlineticketingnew::messages.save') }}
            </button>
        </div>
    </form>
</div>
@endsection
