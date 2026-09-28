@extends('layouts.app')
@section('title', $title ?? 'Communication Hub')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Communication Hub' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@php($title='Message Queue')
<div class="box box-primary"><div class="box-header with-border"><form method="POST" action="{{ route('communicationhub.queue.process') }}">@csrf<button class="btn btn-success btn-sm"><i class="fa fa-play"></i> Process Queue</button></form></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Priority</th><th>Attempts</th><th>Cost</th><th>Created</th><th>Action</th></tr></thead><tbody>@foreach($messages as $message)<tr><td>{{ $message->id }}</td><td>{{ strtoupper($message->channel) }}</td><td>{{ $message->recipient }}</td><td>{{ $message->status }}</td><td>{{ $message->priority }}</td><td>{{ $message->attempts }}</td><td>{{ $message->cost }}</td><td>{{ $message->created_at }}</td><td><form method="POST" action="{{ route('communicationhub.queue.retry',$message) }}">@csrf<button class="btn btn-xs btn-warning">Retry</button></form></td></tr>@endforeach</tbody></table>{{ $messages->links() }}</div></div>
</section>
@endsection
