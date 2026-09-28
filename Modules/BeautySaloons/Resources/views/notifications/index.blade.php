@extends('beautysaloons::layout')
@section('beauty_content')
<div class="container-fluid bs-notifications">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>{{ __('beautysaloons::notifications.logs') }}</h3>
        <div>
            <a href="{{ route('beautysaloons.notifications.templates') }}" class="btn btn-primary">Templates</a>
            <a href="{{ route('beautysaloons.notifications.settings') }}" class="btn btn-secondary">Settings</a>
            <a href="{{ route('beautysaloons.notifications.reports.delivery') }}" class="btn btn-success">Delivery Report</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Date</th><th>Channel</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Retry</th><th>Sent At</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ strtoupper($log->channel) }}</td>
                        <td>{{ $log->recipient }}</td>
                        <td>{{ $log->subject }}</td>
                        <td><span class="badge badge-info">{{ ucfirst($log->status) }}</span></td>
                        <td>{{ $log->retry_count }}</td>
                        <td>{{ optional($log->sent_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No notifications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
@endsection
