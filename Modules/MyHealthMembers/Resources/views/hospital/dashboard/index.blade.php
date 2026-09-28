@extends('layouts.app')
@section('title', 'My Health Hospital Dashboard')

@section('content')
<section class="content-header">
    <h1>My Health Hospital Information System</h1>
</section>
<section class="content">
    <div class="row">
        @foreach($summary as $label => $value)
            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="small-box bg-aqua">
                    <div class="inner"><h3>{{ $value }}</h3><p>{{ ucwords(str_replace('_', ' ', $label)) }}</p></div>
                    <div class="icon"><i class="fa fa-heartbeat"></i></div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Today Queue</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Token</th><th>Queue</th><th>Member</th><th>Doctor</th><th>Room</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($queue as $row)
                    <tr>
                        <td>{{ $row->token_no }}</td><td>{{ $row->queue_no }}</td><td>{{ optional($row->member)->name }}</td>
                        <td>{{ optional($row->doctor)->name }}</td><td>{{ optional($row->room)->room_name }}</td><td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No appointments found for today.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
