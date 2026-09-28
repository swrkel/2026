@extends('layouts.app')

@section('title', __('myhealthmembers::lang.myhealth_settings'))

@section('content')
<section class="content-header">
    <h1>{{ __('myhealthmembers::lang.myhealth_settings') }}</h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    {!! Form::open(['route' => 'myhealth.settings.update', 'method' => 'post']) !!}
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('myhealthmembers::lang.settings') }}</h3>
        </div>
        <div class="box-body">
            <div class="row">
                @php
                    $defaults = [
                        'general' => ['allow_member_self_registration' => '1', 'require_member_otp_access' => '1', 'qr_expiry_minutes' => '30'],
                        'pharmacy' => ['enable_fifo_dispense' => '1', 'low_stock_alert_days' => '30'],
                        'insurance' => ['enable_claim_approval' => '1'],
                        'telemedicine' => ['default_session_minutes' => '20', 'enable_waiting_room' => '1'],
                        'billing' => ['invoice_prefix' => 'MHINV', 'receipt_prefix' => 'MHREC'],
                    ];
                @endphp

                @foreach($defaults as $group => $items)
                    <div class="col-md-6">
                        <div class="box box-solid">
                            <div class="box-header with-border">
                                <h4 class="box-title text-capitalize">{{ str_replace('_', ' ', $group) }}</h4>
                            </div>
                            <div class="box-body">
                                @foreach($items as $key => $defaultValue)
                                    @php($currentValue = data_get($settings, $group . '.' . $key . '.setting_value', $defaultValue))
                                    <div class="form-group">
                                        <label>{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                        <input type="text" name="settings[{{ $group }}][{{ $key }}]" value="{{ $currentValue }}" class="form-control">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ __('messages.save') }}
            </button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection
