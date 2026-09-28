@extends('layouts.app')
@section('title', 'My Health Disaster Recovery Reports')
@section('content')
<section class="content-header"><h1>My Health <small>Disaster Recovery Reports</small></h1></section>
<section class="content">
@include('myhealthmembers::disaster_recovery._nav')
@foreach(['backup_history'=>'Backup History','restore_history'=>'Restore History','system_health'=>'System Health','recovery_tests'=>'Recovery Tests'] as $key => $title)
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">{{ $title }}</h3><button onclick="window.print()" class="btn btn-default btn-xs pull-right"><i class="fa fa-print"></i> Print</button></div><div class="box-body table-responsive"><table class="table table-bordered table-striped">
<thead><tr><th>ID</th><th>Reference</th><th>Status</th><th>Date</th><th>Details</th></tr></thead><tbody>
@forelse(($reports[$key] ?? []) as $row)
<tr><td>{{ $row->id }}</td><td>{{ $row->backup_no ?? $row->restore_no ?? $row->test_no ?? $row->check_name ?? '-' }}</td><td>{{ ucfirst($row->status ?? '-') }}</td><td>{{ optional($row->created_at ?? $row->checked_at ?? $row->tested_at)->format('Y-m-d H:i') }}</td><td>{{ $row->message ?? $row->notes ?? $row->result_summary ?? '-' }}</td></tr>
@empty<tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>@endforelse
</tbody></table></div></div>
@endforeach
</section>
@endsection
