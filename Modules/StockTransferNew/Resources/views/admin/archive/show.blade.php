@extends('layouts.app')

@section('title', __('stocktransfernew::archive.title'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-archive.css') }}">
<div class="stn-archive-page">
    <div class="stn-page-header">
        <div>
            <h3>Archive Run #{{ $run->id }}</h3>
            <p>Checksum: {{ $run->checksum }}</p>
        </div>
        <a href="{{ route('stocktransfernew.archive.index') }}" class="btn btn-secondary">Back</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="stn-kpi-grid">
        <div class="stn-kpi"><span>Status</span><strong>{{ ucfirst($run->status) }}</strong></div>
        <div class="stn-kpi"><span>Transfers</span><strong>{{ number_format($run->total_completed_transfers) }}</strong></div>
        <div class="stn-kpi"><span>Qty</span><strong>{{ number_format($run->total_qty, 3) }}</strong></div>
        <div class="stn-kpi"><span>Value</span><strong>{{ number_format($run->total_value, 2) }}</strong></div>
    </div>

    @if($run->status === 'preview')
        <form method="POST" action="{{ route('stocktransfernew.archive.execute', $run->id) }}" onsubmit="return confirm('Confirm archive execution? This records the archive status but does not delete live data.');">
            @csrf
            <button class="btn btn-danger">{{ __('stocktransfernew::archive.execute_archive') }}</button>
        </form>
    @endif

    <div class="stn-card">
        <h4>Preview Lines</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead><tr><th>Transfer No</th><th>Date</th><th>Status</th><th>Lines</th><th>Qty</th><th>Value</th><th>Decision</th></tr></thead>
                <tbody>
                @foreach($run->lines as $line)
                    <tr>
                        <td>{{ $line->transfer_no }}</td>
                        <td>{{ optional($line->transfer_date)->format('Y-m-d') }}</td>
                        <td>{{ $line->status }}</td>
                        <td>{{ $line->line_count }}</td>
                        <td>{{ number_format($line->total_qty, 3) }}</td>
                        <td>{{ number_format($line->total_value, 2) }}</td>
                        <td>{{ $line->archive_decision }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="stn-card">
        <h4>{{ __('stocktransfernew::archive.restore_request') }}</h4>
        <form method="POST" action="{{ route('stocktransfernew.archive.restore_request') }}" class="stn-form-row">
            @csrf
            <input type="hidden" name="archive_run_id" value="{{ $run->id }}">
            <input type="number" name="transfer_id" placeholder="Transfer ID">
            <input type="text" name="transfer_no" placeholder="Transfer No">
            <input type="text" name="reason" required placeholder="Reason for restore review">
            <button class="btn btn-warning">Submit Request</button>
        </form>
    </div>
</div>
@endsection
