@extends('layouts.app')
@section('title', 'Clinical Timeline')
@section('content')
<section class="content-header"><h1>My Health <small>Clinical Timeline</small></h1></section>
<section class="content">
    <form method="GET" class="form-inline" style="margin-bottom:15px;">
        <div class="form-group"><label>Member Code</label><input type="text" name="member_code" value="{{ $memberCode }}" class="form-control" required></div>
        <button class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
    </form>
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Longitudinal Care Timeline</h3></div>
        <div class="box-body">
            <ul class="timeline">
                @forelse($timeline as $row)
                    <li>
                        <i class="fa fa-heartbeat bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fa fa-clock-o"></i> {{ $row->date }}</span>
                            <h3 class="timeline-header">{{ $row->title }}</h3>
                            <div class="timeline-body">{{ $row->details }}</div>
                        </div>
                    </li>
                @empty
                    <li><i class="fa fa-info bg-gray"></i><div class="timeline-item"><h3 class="timeline-header">No timeline records found.</h3></div></li>
                @endforelse
            </ul>
        </div>
    </div>
</section>
@endsection
