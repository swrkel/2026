@extends('stocktransfernew::layouts.app')

@section('title', __('stocktransfernew::transporter_scorecard.title'))

@section('content')
<section class="content-header stn-page-header">
    <h1>{{ __('stocktransfernew::transporter_scorecard.title') }}</h1>
</section>

<section class="content stn-transporter-scorecard">
    <div class="row stn-kpi-row">
        <div class="col-md-2"><div class="stn-kpi"><span>{{ __('stocktransfernew::transporter_scorecard.transporters') }}</span><strong>{{ $summary['transporters'] ?? 0 }}</strong></div></div>
        <div class="col-md-2"><div class="stn-kpi"><span>{{ __('stocktransfernew::transporter_scorecard.transfers') }}</span><strong>{{ $summary['transfers'] ?? 0 }}</strong></div></div>
        <div class="col-md-2"><div class="stn-kpi"><span>{{ __('stocktransfernew::transporter_scorecard.delayed') }}</span><strong>{{ $summary['delayed'] ?? 0 }}</strong></div></div>
        <div class="col-md-2"><div class="stn-kpi"><span>{{ __('stocktransfernew::transporter_scorecard.claims') }}</span><strong>{{ $summary['claims'] ?? 0 }}</strong></div></div>
        <div class="col-md-4"><div class="stn-kpi"><span>{{ __('stocktransfernew::transporter_scorecard.freight_variance') }}</span><strong>{{ number_format($summary['freight_variance'] ?? 0, 4) }}</strong></div></div>
    </div>

    <div class="box stn-box">
        <div class="box-header with-border stn-toolbar">
            <form method="get" class="form-inline">
                <input type="text" name="transporter_name" value="{{ request('transporter_name') }}" class="form-control" placeholder="{{ __('stocktransfernew::transporter_scorecard.search_transporter') }}">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                <button class="btn btn-primary">{{ __('stocktransfernew::transporter_scorecard.search') }}</button>
                <a href="{{ route('stock-transfer-new.transporter-scorecard.export-csv', request()->all()) }}" class="btn btn-success">CSV</a>
            </form>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-table" id="stn-transporter-scorecard-table">
                <thead>
                    <tr>
                        <th>{{ __('stocktransfernew::transporter_scorecard.transporter') }}</th>
                        <th>{{ __('stocktransfernew::transporter_scorecard.transfers') }}</th>
                        <th>{{ __('stocktransfernew::transporter_scorecard.on_time') }}</th>
                        <th>{{ __('stocktransfernew::transporter_scorecard.delayed') }}</th>
                        <th>{{ __('stocktransfernew::transporter_scorecard.claims') }}</th>
                        <th>{{ __('stocktransfernew::transporter_scorecard.freight_variance') }}</th>
                        <th>{{ __('stocktransfernew::transporter_scorecard.score') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['transporter_name'] }}</td>
                            <td>{{ $row['transfer_count'] }}</td>
                            <td>{{ $row['on_time_count'] }}</td>
                            <td>{{ $row['delayed_count'] }}</td>
                            <td>{{ $row['damage_claim_count'] }}</td>
                            <td>{{ number_format($row['freight_variance'], 4) }}</td>
                            <td><span class="stn-score stn-score-{{ $row['score'] >= 80 ? 'good' : ($row['score'] >= 50 ? 'warn' : 'bad') }}">{{ $row['score'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
