@extends('stocktransfernew::layouts.app')
@section('content')
@php($exportType = 'exceptions')
<div class="stnew-page">
    <div class="stnew-page-header"><h1>{{ $title }}</h1></div>
    @include('stocktransfernew::reports.advanced._filters')
    <div class="stnew-card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped stnew-datatable">
                <thead><tr><th>Transfer No</th><th>Date</th><th>Status</th><th>Priority</th><th>From Location</th><th>To Location</th><th>Short</th><th>Excess</th><th>Age Days</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr><td>{{ data_get($row, 'transfer_no') }}</td><td>{{ data_get($row, 'transfer_date') }}</td><td>{{ data_get($row, 'status') }}</td><td>{{ data_get($row, 'priority') }}</td><td>{{ data_get($row, 'from_location_id') }}</td><td>{{ data_get($row, 'to_location_id') }}</td><td>{{ data_get($row, 'short_qty') }}</td><td>{{ data_get($row, 'excess_qty') }}</td><td>{{ data_get($row, 'age_days') }}</td></tr>
                    @empty
                        <tr><td colspan="9" class="text-center">{{ __('stocktransfernew::lang.no_records_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ method_exists($rows, 'links') ? $rows->appends(request()->query())->links() : '' }}
    </div>
</div>
@endsection
