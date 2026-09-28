@extends('layouts.app')
@section('title', 'Operation Theatre Reports')
@section('content')
<section class="content-header"><h1>Operation Theatre Reports</h1></section>
<section class="content"><div class="row">
@foreach(['Scheduled'=>$scheduled,'Completed'=>$completed,'Emergency'=>$emergency,'Operative Records'=>$records] as $label=>$value)
<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-bar-chart"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $value }}</span></div></div></div>
@endforeach
</div><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Latest Surgery Register</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Surgery No</th><th>Member</th><th>Procedure</th><th>Priority</th><th>Status</th><th>Scheduled</th></tr></thead><tbody>@foreach($schedules as $row)<tr><td>{{ $row->surgery_no }}</td><td>{{ $row->member_id }}</td><td>{{ $row->procedure_name }}</td><td>{{ ucfirst($row->priority) }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ optional($row->scheduled_start_at)->format('Y-m-d H:i') }}</td></tr>@endforeach</tbody></table></div></div></section>
@endsection
