@extends('layouts.app')
@section('title', __('stocktransfernew::lang.execution_logs'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.execution_logs')</h1></section>
<section class="content stn-page"><div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Schedule</th><th>Transfer</th><th>Status</th><th>Message</th></tr></thead><tbody>
@foreach($logs as $log)<tr><td>{{ $log->run_date }}</td><td>{{ $log->schedule_id }}</td><td>{{ $log->transfer_id }}</td><td>{{ $log->run_status }}</td><td>{{ $log->message }}</td></tr>@endforeach
</tbody></table>{{ $logs->links() }}</div></div></section>
@endsection
