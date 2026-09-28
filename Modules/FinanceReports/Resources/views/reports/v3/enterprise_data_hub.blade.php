@extends('layouts.app')
@section('title', 'Enterprise Data Hub - New')
@section('content')
<section class="content-header"><h1>Enterprise Data Hub - New <small>Finance Reports Enterprise v3.0</small></h1></section>
<section class="content">
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Read-only module adapters</h3></div><div class="box-body">
<p>{{ $summary['principle'] }}</p><div class="row"><div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-plug"></i></span><div class="info-box-content"><span class="info-box-text">Available / Active</span><span class="info-box-number">{{ $summary['active'] }}</span></div></div></div><div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span><div class="info-box-content"><span class="info-box-text">Future Ready</span><span class="info-box-number">{{ $summary['future_ready'] }}</span></div></div></div></div><table class="table table-bordered table-striped"><thead><tr><th>Module</th><th>Status</th><th>Reporting Source</th><th>Mode</th></tr></thead><tbody>@foreach($summary['modules'] as $module)<tr><td>{{ $module['name'] }}</td><td>{{ $module['status'] }}</td><td>{{ $module['source'] }}</td><td>{{ $module['mode'] }}</td></tr>@endforeach</tbody></table>
</div></div>
</section>
@endsection
