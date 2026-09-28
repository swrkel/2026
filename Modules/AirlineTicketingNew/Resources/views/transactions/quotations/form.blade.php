@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::transactions.new_quotation'))
@section('atn-content')
@include('airlineticketingnew::transactions.navigation')
<form method="POST" action="{{ route('airline-ticketing-new.quotations.store') }}" id="atn_quotation_form">
@csrf
<div class="atn-panel">
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::transactions.quotation_details') }}</h4></div>
<div class="atn-panel-body"><div class="row">
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.quotation_date') }}</label><input type="date" class="form-control" name="quotation_date" value="{{ old('quotation_date',date('Y-m-d')) }}" required></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.valid_until') }}</label><input type="date" class="form-control" name="valid_until" value="{{ old('valid_until') }}"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.customer_type') }}</label><select class="form-control" name="customer_type"><option value="individual">Individual</option><option value="corporate">Corporate</option><option value="walk_in">Walk-in</option></select></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.currency') }}</label><input class="form-control" name="currency_code" value="{{ old('currency_code','LKR') }}" maxlength="3" required></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.exchange_rate') }}</label><input class="form-control input_number" name="exchange_rate" value="{{ old('exchange_rate','1') }}" required></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.passenger') }}</label><select class="form-control atn-ajax-passenger" name="passenger_id"></select></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.corporate_customer') }}</label><select class="form-control atn-ajax-corporate" name="corporate_customer_id"></select></div></div>
<div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.remarks') }}</label><textarea class="form-control" name="remarks">{{ old('remarks') }}</textarea></div></div>
</div></div>
</div>

<div class="atn-panel">
<div class="atn-panel-header atn-flex-between"><h4>{{ __('airlineticketingnew::transactions.flight_segments') }}</h4><button type="button" class="btn btn-primary btn-sm" id="atn_add_segment"><i class="fa fa-plus"></i> {{ __('airlineticketingnew::transactions.add_segment') }}</button></div>
<div class="table-responsive"><table class="table table-bordered atn-table" id="atn_segments_table">
<thead><tr><th>#</th><th>{{ __('airlineticketingnew::transactions.airline') }}</th><th>{{ __('airlineticketingnew::transactions.flight_number') }}</th><th>{{ __('airlineticketingnew::transactions.origin') }}</th><th>{{ __('airlineticketingnew::transactions.destination') }}</th><th>{{ __('airlineticketingnew::transactions.departure') }}</th><th>{{ __('airlineticketingnew::transactions.arrival') }}</th><th>{{ __('airlineticketingnew::transactions.class') }}</th><th>{{ __('airlineticketingnew::transactions.base_fare') }}</th><th>{{ __('airlineticketingnew::transactions.tax') }}</th><th>{{ __('airlineticketingnew::transactions.fee') }}</th><th>{{ __('airlineticketingnew::transactions.discount') }}</th><th></th></tr></thead>
<tbody></tbody>
</table></div>
<div class="atn-panel-footer text-right"><button class="btn btn-primary"><i class="fa fa-save"></i> {{ __('airlineticketingnew::transactions.save_quotation') }}</button></div>
</div>
</form>

<template id="atn_segment_template">
<tr>
<td class="atn-segment-number"></td>
<td><select class="form-control" data-name="airline_id">@foreach($airlines as $airline)<option value="{{ $airline->id }}">{{ $airline->name }}</option>@endforeach</select></td>
<td><input class="form-control" data-name="flight_number"></td>
<td><select class="form-control" data-name="origin_airport_id">@foreach($airports as $airport)<option value="{{ $airport->id }}">{{ $airport->iata_code }} - {{ $airport->city }}</option>@endforeach</select></td>
<td><select class="form-control" data-name="destination_airport_id">@foreach($airports as $airport)<option value="{{ $airport->id }}">{{ $airport->iata_code }} - {{ $airport->city }}</option>@endforeach</select></td>
<td><input type="datetime-local" class="form-control" data-name="departure_at"></td>
<td><input type="datetime-local" class="form-control" data-name="arrival_at"></td>
<td><select class="form-control" data-name="travel_class_id">@foreach($travelClasses as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></td>
<td><input class="form-control input_number" data-name="base_fare" value="0"></td>
<td><input class="form-control input_number" data-name="tax_amount" value="0"></td>
<td><input class="form-control input_number" data-name="service_fee" value="0"></td>
<td><input class="form-control input_number" data-name="discount_amount" value="0"></td>
<td><button type="button" class="btn btn-danger btn-xs atn-remove-segment"><i class="fa fa-trash"></i></button></td>
</tr>
</template>
@endsection
@push('atn-js')
<script>
window.ATN_QUOTATION = {
    passengerLookup: @json(route('airline-ticketing-new.lookups.passengers')),
    corporateLookup: @json(route('airline-ticketing-new.lookups.corporate-customers'))
};
</script>
<script src="{{ asset('modules/airline-ticketing-new/js/atn-transactions.js') }}"></script>
@endpush
