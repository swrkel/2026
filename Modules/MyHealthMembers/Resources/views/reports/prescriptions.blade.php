@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }}</h1></section><section class="content">
@include('myhealthmembers::reports._filters')
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Member ID</th><th>Doctor/User</th><th>Prescription</th><th>Instructions</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->prescription_date }}</td><td>{{ $row->member_id }}</td><td>{{ $row->doctor_user_id }}</td><td>{{ $row->prescription_details }}</td><td>{{ $row->instructions }}</td></tr>@empty<tr><td colspan="5" class="text-center">No records found</td></tr>@endforelse
</tbody></table></div></div></section>
@endsection
