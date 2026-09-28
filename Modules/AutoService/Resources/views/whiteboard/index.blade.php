@extends('autoservice::layouts.master')
@section('content')
@include('autoservice::layouts.nav')
<section class="content-header"><h1>Workshop Whiteboard</h1></section>
<section class="content">
<div class="row">
 <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-car"></i></span><div class="info-box-content"><span class="info-box-text">Active Bays</span><span class="info-box-number">{{ $activeBays }}</span></div></div></div>
 <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-cogs"></i></span><div class="info-box-content"><span class="info-box-text">Waiting Parts</span><span class="info-box-number">{{ $partsWaiting }}</span></div></div></div>
 <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">Revenue Today</span><span class="info-box-number">{{ number_format($todayRevenue,2) }}</span></div></div></div>
</div>
<div class="row">
@foreach($jobsByStatus as $status => $jobs)
 <div class="col-md-3">
  <div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">{{ ucwords(str_replace('_',' ',$status)) }} ({{ $jobs->count() }})</h3></div>
  <div class="box-body" style="min-height:180px">
   @forelse($jobs as $job)
    <div class="well well-sm"><strong>{{ $job->job_no ?? ('JOB-'.$job->id) }}</strong><br>{{ $job->complaint ?? $job->status }}<br><small>Expected: {{ $job->expected_delivery_date ?? '-' }}</small></div>
   @empty <span class="text-muted">No jobs</span> @endforelse
  </div></div>
 </div>
@endforeach
</div>
</section>
@endsection
