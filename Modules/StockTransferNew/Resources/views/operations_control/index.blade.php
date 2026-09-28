@extends('layouts.app')
@section('title', __('stocktransfernew::messages.operations_control_center'))
@section('content')
<section class="content-header stn-042-header"><h1>{{ __('stocktransfernew::messages.operations_control_center') }}</h1></section>
<section class="content stn-042-wrap">
    <div class="row stn-kpi-row">
        @foreach(($kpis ?? []) as $label => $value)
            <div class="col-md-3"><div class="stn-kpi-card"><span>{{ ucwords(str_replace('_', ' ', $label)) }}</span><strong>{{ number_format($value) }}</strong></div></div>
        @endforeach
    </div>
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">{{ __('stocktransfernew::messages.live_status_board') }}</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-042-live-board">
                <thead><tr><th>Transfer No</th><th>Status</th><th>Priority</th><th>Expected Delivery</th><th>Updated</th></tr></thead>
                <tbody>
                @foreach(($live_status['rows'] ?? []) as $row)
                    <tr><td>{{ $row->transfer_no }}</td><td>{{ $row->status }}</td><td>{{ $row->priority }}</td><td>{{ $row->expected_delivery_at }}</td><td>{{ $row->updated_at }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
