@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }}</h1></section><section class="content">
@include('myhealthmembers::reports._filters')
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Member</th><th>Allergies</th><th>Chronic Conditions</th><th>Current Medications</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ optional($row->updated_at)->format('Y-m-d') }}</td><td>{{ optional($row->member)->name ?? $row->member_id }}</td><td>{{ $row->allergies }}</td><td>{{ $row->chronic_conditions }}</td><td>{{ $row->current_medications }}</td></tr>@empty<tr><td colspan="5" class="text-center">No records found</td></tr>@endforelse
</tbody></table></div></div></section>
@endsection
