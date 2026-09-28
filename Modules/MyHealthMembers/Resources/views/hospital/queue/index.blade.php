@extends('layouts.app')
@section('title', 'My Health Waiting Queue')
@section('content')
<section class="content-header"><h1>Waiting Queue</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th style="font-size:22px">Token</th><th>Queue</th><th>Patient</th><th>Doctor</th><th>Room</th><th>Status</th></tr></thead><tbody>@forelse($queue as $row)<tr><td style="font-size:24px;font-weight:bold">{{ $row->token_no }}</td><td>{{ $row->queue_no }}</td><td>{{ optional($row->member)->name }}</td><td>{{ optional($row->doctor)->name }}</td><td>{{ optional($row->room)->room_name }}</td><td>{{ ucfirst(str_replace('_',' ',$row->status)) }}</td></tr>@empty<tr><td colspan="6" class="text-center">No waiting patients.</td></tr>@endforelse</tbody></table></div></div></section>
@endsection
