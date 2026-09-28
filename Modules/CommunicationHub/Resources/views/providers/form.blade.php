@extends('layouts.app')
@section('title', $provider->exists ? 'Edit Communication Provider' : 'Add Communication Provider')
@section('content')
<section class="content-header">
    <h1>{{ $provider->exists ? 'Edit Communication Provider' : 'Add Communication Provider' }}</h1>
</section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ $provider->exists ? route('communicationhub.providers.update',$provider) : route('communicationhub.providers.store') }}">
@csrf
@if($provider->exists) @method('PUT') @endif
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Provider Details</h3></div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-4"><label>Name *</label><input class="form-control" name="name" value="{{ old('name',$provider->name) }}" required></div>
            <div class="col-md-2"><label>Channel *</label><select class="form-control" name="channel" required>
                @foreach(['sms'=>'SMS','email'=>'Email','whatsapp'=>'WhatsApp','push'=>'Push'] as $value=>$label)
                    <option value="{{ $value }}" {{ old('channel',$provider->channel) === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select></div>
            <div class="col-md-4"><label>Driver *</label><select class="form-control" name="driver" required>
                @foreach($driverOptions ?? [] as $channel => $drivers)
                    <optgroup label="{{ strtoupper($channel) }}">
                        @foreach($drivers as $key => $label)
                            <option value="{{ $key }}" {{ old('driver',$provider->driver) === $key ? 'selected' : '' }}>{{ $label }} ({{ $key }})</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select></div>
            <div class="col-md-2"><label>Priority</label><input type="number" min="1" class="form-control" name="priority" value="{{ old('priority',$provider->priority ?: 1) }}"></div>
        </div>
        <div class="row" style="margin-top:12px;">
            <div class="col-md-2"><label>Country</label><input class="form-control" name="country_code" value="{{ old('country_code',$provider->country_code) }}" placeholder="LK / Any"></div>
            <div class="col-md-2"><label>Cost Per Message</label><input type="number" step="0.0001" min="0" class="form-control" name="cost_per_message" value="{{ old('cost_per_message',$provider->cost_per_message ?: 0) }}"></div>
            <div class="col-md-2"><label>Daily Limit</label><input type="number" min="0" class="form-control" name="daily_limit" value="{{ old('daily_limit',$provider->daily_limit) }}"></div>
            <div class="col-md-2"><label>Monthly Limit</label><input type="number" min="0" class="form-control" name="monthly_limit" value="{{ old('monthly_limit',$provider->monthly_limit) }}"></div>
            <div class="col-md-2"><label>Status</label><select class="form-control" name="is_active"><option value="1" {{ old('is_active',$provider->is_active) ? 'selected' : '' }}>Active</option><option value="0" {{ ! old('is_active',$provider->is_active) ? 'selected' : '' }}>Inactive</option></select></div>
            <div class="col-md-2"><label>Health</label><select class="form-control" name="health_status"><option value="unknown">Unknown</option><option value="healthy" {{ old('health_status',$provider->health_status) === 'healthy' ? 'selected' : '' }}>Healthy</option><option value="failed" {{ old('health_status',$provider->health_status) === 'failed' ? 'selected' : '' }}>Failed</option></select></div>
        </div>
        <div class="row" style="margin-top:12px;">
            <div class="col-md-12">
                <label>Provider Configuration JSON</label>
                <textarea class="form-control" name="provider_config_json" rows="8" placeholder='{"api_key":"...","sender_id":"...","cost_per_message":1.00}'>{{ old('provider_config_json', json_encode($provider->provider_config ?: new \stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                <p class="help-block">Standalone configuration only. Do not store credentials in old SMS or Wallet modules.</p>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <button class="btn btn-primary"><i class="fa fa-save"></i> Save Provider</button>
        <a href="{{ route('communicationhub.providers.index') }}" class="btn btn-default">Cancel</a>
    </div>
</div>
</form>
</section>
@endsection
