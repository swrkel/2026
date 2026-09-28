@extends('layouts.app')
@section('title', 'Pumper Management')
@section('content')
<div class="pdirectnew-module pdirectnew-pumper-page container-fluid py-3" data-index-url="{{ route('petro-direct-new.pumper-management.index') }}">
    @include('petrodirectnew::partials.styles')
    <div class="pdn-page-head">
        <div>
            <h1>Pumper Management</h1>
            <p>Direct fuel operations, operator control, meters, payments and shift monitoring.</p>
        </div>
        <div class="pdn-page-actions">
            <a class="btn btn-primary" href="{{ route('petro-direct-new.settlements.create') }}"><i class="fa fa-plus-circle"></i> Direct Settlement</a>
            <a class="btn btn-info" href="{{ route('petro-direct-new.reports.index') }}"><i class="fa fa-bar-chart"></i> Reports</a>
        </div>
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><strong>Please correct the following:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="pdn-card pdn-toolbar" method="get" id="pdirectnew-location-filter">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="pdn-filter-location">
            <label>Business Location</label>
            <select class="form-control" name="location_id">
                <option value="">All Permitted Locations</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" @selected($locationId == $loc->id)>{{ $loc->name ?? ('Location '.$loc->id) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button>
        <button type="button" class="btn btn-default" id="pdirectnew-clear-filter"><i class="fa fa-times"></i> Clear</button>
    </form>

    @php
        $tabs = [
            'operators' => ['Pump Operators','fa-users','pdn-tab-white'],
            'payments' => ['Pumper Excess / Shortage Payments','fa-minus','pdn-tab-magenta'],
            'pumper_day_entries' => ['Pumper Day Entries','fa-calculator','pdn-tab-cyan'],
            'shift_summary' => ['Shift Summary','fa-clock-o','pdn-tab-green'],
            'payment_summary' => ['Payment Summary','fa-money','pdn-tab-orange'],
            'meters_with_payments' => ['Meters with Payments','fa-money','pdn-tab-teal'],
            'daily_pump_status' => ['Daily Pump Status','fa-table','pdn-tab-purple'],
            'close_shift' => ['Close Shift','fa-ban','pdn-tab-lime'],
            'current_meter' => ['Current Meter','fa-thermometer-half','pdn-tab-blue'],
            'unload_stock' => ['Unload Stock','fa-arrow-down','pdn-tab-magenta'],
        ];
    @endphp

    <div class="pdn-card pdn-management-shell">
        <div class="pdn-tabs pdn-legacy-tabs">
            @foreach($tabs as $key => [$label,$icon,$class])
                <a data-pdirectnew-tab="{{ $key }}"
                   data-tab-url="{{ route('petro-direct-new.pumper-management.tab', ['tab' => $key]) }}"
                   class="{{ $class }} {{ $tab === $key ? 'active' : '' }}"
                   href="{{ route('petro-direct-new.pumper-management.index',['tab'=>$key,'location_id'=>$locationId]) }}">
                    <i class="fa {{ $icon }}"></i> {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="pdn-tab-body" id="pdirectnew-tab-body" data-current-tab="{{ $tab }}">
            @includeIf('petrodirectnew::pumper_management.tabs.'.$tab)
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
@include('petrodirectnew::partials.pumper-management-script')
</script>
@endsection
