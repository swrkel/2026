@extends('autoservice::layouts.master')
@section('title','Service Reminders')
@section('autoservice_content')
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered"><tr><th>Due Date</th><th>Send On</th><th>Mobile</th><th>Message</th><th>Status</th><th>Action</th></tr>@foreach($reminders as $r)<tr><td>{{ $r->due_date }}</td><td>{{ $r->send_on }}</td><td>{{ $r->mobile }}</td><td>{{ $r->message }}</td><td>{{ $r->status }}</td><td>@if($r->status!='sent')<form method="post" action="{{ route('autoservice.reminders.mark_sent',$r->id) }}">@csrf<button class="btn btn-xs btn-success">Mark Sent</button></form>@endif</td></tr>@endforeach</table>{{ $reminders->links() }}</div></div>
@endsection
