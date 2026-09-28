@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }}</h1></section><section class="content">
@include('myhealthmembers::reports._filters')
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Appointment No</th><th>Date</th><th>Time</th><th>Member</th><th>Doctor</th><th>Status</th><th>Fee</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->appointment_no }}</td><td>{{ $row->appointment_date }}</td><td>{{ $row->appointment_time }}</td><td>{{ optional($row->member)->name ?? $row->member_id }}</td><td>{{ optional($row->doctor)->doctor_name ?? optional($row->doctor)->name ?? $row->doctor_id }}</td><td>{{ $row->status }}</td><td class="text-right">{{ number_format($row->consultation_fee, 4) }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records found</td></tr>@endforelse
</tbody></table></div></div></section>
@endsection
