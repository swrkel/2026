@extends('layouts.app')
@section('title', __('stocktransfernew::lang.logistics_rate_cards'))

@section('content')
<section class="content-header stn-page-header">
    <h1>{{ __('stocktransfernew::lang.logistics_rate_cards') }}</h1>
</section>
<section class="content stn-rate-card-page">
    <div class="box box-primary stn-pos-box">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('stocktransfernew::lang.add_rate_card') }}</h3>
        </div>
        <form method="POST" action="{{ route('stock-transfer-new.logistics-rate-cards.store') }}">
            @csrf
            <div class="box-body row">
                <div class="col-md-3"><label>{{ __('stocktransfernew::lang.transporter') }}</label><input name="transporter_name" class="form-control" required></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.route_code') }}</label><input name="route_code" class="form-control"></div>
                <div class="col-md-3"><label>{{ __('stocktransfernew::lang.route_name') }}</label><input name="route_name" class="form-control"></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.vehicle_type') }}</label><input name="vehicle_type" class="form-control"></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.rate_basis') }}</label><select name="rate_basis" class="form-control"><option value="route">Route</option><option value="km">KM</option><option value="weight">Weight</option><option value="volume">Volume</option><option value="mixed">Mixed</option></select></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.base_rate') }}</label><input name="base_rate" class="form-control input_number" value="0"></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.rate_per_km') }}</label><input name="rate_per_km" class="form-control input_number" value="0"></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.rate_per_kg') }}</label><input name="rate_per_kg" class="form-control input_number" value="0"></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.rate_per_cbm') }}</label><input name="rate_per_cbm" class="form-control input_number" value="0"></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.minimum_charge') }}</label><input name="minimum_charge" class="form-control input_number" value="0"></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.effective_from') }}</label><input type="date" name="effective_from" class="form-control" required></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.effective_to') }}</label><input type="date" name="effective_to" class="form-control"></div>
                <div class="col-md-2"><label>{{ __('stocktransfernew::lang.status') }}</label><select name="status" class="form-control"><option value="active">Active</option><option value="draft">Draft</option><option value="inactive">Inactive</option></select></div>
            </div>
            <div class="box-footer text-right"><button class="btn btn-primary">{{ __('messages.save') }}</button></div>
        </form>
    </div>

    <div class="box stn-pos-box">
        <div class="box-header with-border"><h3 class="box-title">{{ __('stocktransfernew::lang.active_rate_cards') }}</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-rate-card-table">
                <thead><tr><th>Transporter</th><th>Route</th><th>Vehicle</th><th>Basis</th><th>Base</th><th>KM</th><th>KG</th><th>CBM</th><th>Minimum</th><th>Effective</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($cards as $card)
                        <tr>
                            <td>{{ $card->transporter_name }}</td><td>{{ $card->route_code }} {{ $card->route_name }}</td><td>{{ $card->vehicle_type }}</td><td>{{ $card->rate_basis }}</td>
                            <td>{{ number_format($card->base_rate, 4) }}</td><td>{{ number_format($card->rate_per_km, 4) }}</td><td>{{ number_format($card->rate_per_kg, 4) }}</td><td>{{ number_format($card->rate_per_cbm, 4) }}</td><td>{{ number_format($card->minimum_charge, 4) }}</td>
                            <td>{{ optional($card->effective_from)->format('Y-m-d') }} - {{ optional($card->effective_to)->format('Y-m-d') }}</td><td>{{ ucfirst($card->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted">{{ __('messages.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="box stn-pos-box">
        <div class="box-header with-border"><h3 class="box-title">{{ __('stocktransfernew::lang.freight_rate_variance') }}</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-variance-table">
                <thead><tr><th>Transfer</th><th>Invoice</th><th>Expected</th><th>Actual</th><th>Variance</th><th>%</th><th>Status</th><th>Note</th></tr></thead>
                <tbody>
                    @forelse($variances as $row)
                        <tr><td>{{ $row->transfer_id }}</td><td>{{ $row->freight_invoice_id }}</td><td>{{ number_format($row->expected_amount, 4) }}</td><td>{{ number_format($row->actual_amount, 4) }}</td><td>{{ number_format($row->variance_amount, 4) }}</td><td>{{ number_format($row->variance_percent, 4) }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->review_note }}</td></tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">{{ __('messages.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/stocktransfernew/js/logistics_rate_cards.js') }}"></script>
@endsection
