@extends('beautysaloons::layout')
@section('beauty_content')
<div class="container-fluid bs-notifications">
    <h3>{{ __('beautysaloons::notifications.delivery_report') }}</h3>
    <div class="row mb-3">
        @foreach($summary as $status => $count)
            <div class="col-md-3"><div class="card card-body text-center"><strong>{{ ucfirst($status) }}</strong><h4>{{ $count }}</h4></div></div>
        @endforeach
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Date</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Response</th><th>Error</th></tr></thead>
            <tbody>
                @foreach($logs as $log)
                    <tr><td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td><td>{{ strtoupper($log->channel) }}</td><td>{{ $log->recipient }}</td><td>{{ $log->status }}</td><td>{{ $log->gateway_response }}</td><td>{{ $log->error_message }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
@endsection
