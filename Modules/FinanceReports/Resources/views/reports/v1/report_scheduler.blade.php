@extends('layouts.app')
@section('title', 'Report Scheduler - New')
@section('content')
<section class="content-header"><h1>Report Scheduler - New</h1></section>
<section class="content">
@include('financereports::layouts.toolbar', ['title' => 'Report Scheduler - New'])
<div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Report</th><th>Frequency</th><th>Time</th><th>Format</th><th>Status</th></tr></thead><tbody>
@foreach($schedules as $item)
<tr><td>{{ $item['report'] }}</td><td>{{ $item['frequency'] }}</td><td>{{ $item['time'] }}</td><td>{{ $item['format'] }}</td><td>{{ $item['status'] }}</td></tr>
@endforeach
</tbody></table>
<p class="text-muted">This is the read-only scheduling framework. Actual automatic sending can be connected later to your ERP notification/email/SMS system.</p>
</div></div>
</section>
@endsection
