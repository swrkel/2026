@extends('layouts.app')

@section('title', __('stocktransfernew::archive.title'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-archive.css') }}">
<div class="stn-archive-page">
    <div class="stn-page-header">
        <div>
            <h3>{{ __('stocktransfernew::archive.title') }}</h3>
            <p>{{ __('stocktransfernew::archive.subtitle') }}</p>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="stn-kpi-grid">
        <div class="stn-kpi"><span>{{ __('stocktransfernew::archive.archive_runs') }}</span><strong>{{ $summary['preview_runs'] ?? 0 }}</strong></div>
        <div class="stn-kpi"><span>Archived</span><strong>{{ $summary['archived_runs'] ?? 0 }}</strong></div>
        <div class="stn-kpi"><span>{{ __('stocktransfernew::archive.eligible_completed') }}</span><strong>{{ $summary['eligible_completed'] ?? 0 }}</strong></div>
        <div class="stn-kpi"><span>{{ __('stocktransfernew::archive.restore_requests') }}</span><strong>{{ $summary['restore_requests'] ?? 0 }}</strong></div>
    </div>

    <div class="stn-card">
        <form method="POST" action="{{ route('stocktransfernew.archive.preview') }}" class="stn-form-row">
            @csrf
            <label>{{ __('stocktransfernew::archive.archive_until') }}</label>
            <input type="date" name="archive_until" required value="{{ now()->subYear()->endOfYear()->toDateString() }}">
            <input type="number" name="location_id" placeholder="Location ID (optional)">
            <input type="number" name="store_id" placeholder="Store ID (optional)">
            <input type="text" name="remarks" placeholder="Remarks">
            <button type="submit" class="btn btn-primary">{{ __('stocktransfernew::archive.create_preview') }}</button>
        </form>
    </div>

    <div class="stn-card">
        <h4>Archive Run Register</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Until</th><th>Status</th><th>Transfers</th><th>Lines</th><th>Qty</th><th>Value</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->id }}</td>
                            <td>{{ optional($run->archive_until)->format('Y-m-d') }}</td>
                            <td><span class="stn-badge stn-{{ $run->status }}">{{ ucfirst($run->status) }}</span></td>
                            <td>{{ number_format($run->total_completed_transfers) }}</td>
                            <td>{{ number_format($run->total_lines) }}</td>
                            <td>{{ number_format($run->total_qty, 3) }}</td>
                            <td>{{ number_format($run->total_value, 2) }}</td>
                            <td><a class="btn btn-sm btn-info" href="{{ route('stocktransfernew.archive.show', $run->id) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No archive previews found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $runs->links() }}
    </div>
</div>
@endsection
