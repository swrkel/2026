@extends('layouts.app')
@section('title', $title ?? 'My Health Nursing')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'My Health Nursing' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

@php($title = 'Vital Signs')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Vital Signs Register</h3><div class="box-tools"><a href="{{ route('myhealth.nursing.vitals.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add</a></div></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Member</th><th>Temp</th><th>Pulse</th><th>BP</th><th>SpO2</th><th>BMI</th><th>Status</th></tr></thead><tbody>
@forelse($vitals as $v)<tr><td>{{ $v->recorded_at ?? $v->created_at }}</td><td>{{ optional($v->member)->name ?? $v->member_id }}</td><td>{{ $v->temperature }}</td><td>{{ $v->pulse }}</td><td>{{ $v->systolic_bp }}/{{ $v->diastolic_bp }}</td><td>{{ $v->spo2 }}</td><td>{{ $v->bmi }}</td><td><span class="label label-{{ $v->status == 'critical' ? 'danger' : ($v->status == 'warning' ? 'warning' : 'success') }}">{{ ucfirst($v->status) }}</span></td></tr>@empty
<tr><td colspan="8" class="text-center text-muted">No vital signs found.</td></tr>@endforelse
</tbody></table>{{ $vitals->links() }}</div></div>
</section>
@endsection
