@extends('autoservice::layouts.master')
@section('content')
<section class="content-header"><h1>Auto Service Notification Log</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Channel</th><th>Event</th><th>Recipient</th><th>Message</th><th>Status</th><th>Action</th></tr></thead><tbody>@foreach($logs as $log)<tr><td>{{ @format_datetime($log->created_at) }}</td><td>{{ strtoupper($log->channel) }}</td><td>{{ $log->event }}</td><td>{{ $log->recipient }}</td><td>{{ $log->message }}</td><td>{{ ucfirst($log->status) }}</td><td>@if($log->status!='sent')<form method="post" action="{{ route('autoservice.notifications.mark_sent',$log->id) }}">@csrf<button class="btn btn-xs btn-success">Mark Sent</button></form>@endif</td></tr>@endforeach</tbody></table>{{ $logs->links() }}</div></div></section>
@endsection
