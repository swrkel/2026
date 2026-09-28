@extends('layouts.app')

@section('title', __('stocktransfernew::data_quality.title'))

@section('content')
<section class="content-header stn-page-header">
    <h1>{{ __('stocktransfernew::data_quality.title') }}</h1>
    <p>{{ __('stocktransfernew::data_quality.subtitle') }}</p>
</section>

<section class="content stocktransfernew-data-quality">
    <div class="stn-toolbar">
        <form method="GET" action="{{ route('stocktransfernew.admin.data-quality.index') }}" class="form-inline">
            <input type="text" name="business_id" value="{{ $filters['business_id'] ?? '' }}" class="form-control" placeholder="Business ID">
            <input type="text" name="location_id" value="{{ $filters['location_id'] ?? '' }}" class="form-control" placeholder="Location ID">
            <input type="text" name="store_id" value="{{ $filters['store_id'] ?? '' }}" class="form-control" placeholder="Store ID">
            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control">
            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control">
            <button class="btn btn-primary">{{ __('stocktransfernew::data_quality.filter') }}</button>
            <a href="{{ route('stocktransfernew.admin.data-quality.export', request()->query()) }}" class="btn btn-success">CSV</a>
        </form>
    </div>

    <div class="row stn-quality-cards">
        <div class="col-md-2 col-sm-6"><div class="stn-card"><span>Orphan Lines</span><strong>{{ $summary['orphan_lines'] }}</strong></div></div>
        <div class="col-md-2 col-sm-6"><div class="stn-card"><span>Missing Store Links</span><strong>{{ $summary['missing_store_links'] }}</strong></div></div>
        <div class="col-md-2 col-sm-6"><div class="stn-card"><span>Negative Movements</span><strong>{{ $summary['negative_movements'] }}</strong></div></div>
        <div class="col-md-2 col-sm-6"><div class="stn-card"><span>Stuck In Transit</span><strong>{{ $summary['stuck_in_transit'] }}</strong></div></div>
        <div class="col-md-2 col-sm-6"><div class="stn-card"><span>Over Received</span><strong>{{ $summary['unbalanced_received'] }}</strong></div></div>
    </div>

    <div class="box stn-box">
        <div class="box-header"><h3 class="box-title">Latest System Checks</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Check</th><th>Status</th><th>Message</th><th>Checked At</th></tr></thead>
                <tbody>
                @forelse($summary['latest_checks'] as $check)
                    <tr>
                        <td>{{ $check['check_key'] ?? '' }}</td>
                        <td>{{ $check['status'] ?? '' }}</td>
                        <td>{{ $check['message'] ?? '' }}</td>
                        <td>{{ $check['checked_at'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No saved checks yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-data-quality.css') }}">
@endpush
