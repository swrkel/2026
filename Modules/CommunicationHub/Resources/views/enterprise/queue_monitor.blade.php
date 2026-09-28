@extends('communicationhub::layout')

@section('communicationhub_title', 'Queue Monitor')
@section('communicationhub_content')
<div class="row">@foreach($queueStats as $status=>$count)<div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ number_format($count) }}</h3><p>{{ ucwords(str_replace('_',' ', $status)) }}</p></div><div class="icon"><i class="fa fa-tasks"></i></div></div></div>@endforeach</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Recent Queue Messages</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Priority</th><th>Retries</th><th>Cost</th></tr></thead><tbody>@foreach($messages as $message)<tr><td>{{ $message->created_at }}</td><td>{{ strtoupper($message->channel ?? '') }}</td><td>{{ $message->recipient ?? $message->to ?? '-' }}</td><td><span class="label label-default">{{ $message->status }}</span></td><td>{{ $message->priority ?? '-' }}</td><td>{{ $message->retry_count ?? 0 }}</td><td>{{ number_format((float)($message->actual_cost ?? $message->estimated_cost ?? 0), 4) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
