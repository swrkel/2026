@extends('layouts.app')
@section('title', 'Vaccination Certificate')
@section('content')
<section class="content-header"><h1>Vaccination Certificate</h1></section>
<section class="content"><div class="box box-success"><div class="box-body">
<h3 class="text-center">My Health Vaccination Certificate</h3><hr>
<table class="table table-bordered">
<tr><th>Certificate No</th><td>{{ $record->certificate_no }}</td></tr>
<tr><th>Vaccination No</th><td>{{ $record->vaccination_no }}</td></tr>
<tr><th>Member ID</th><td>{{ $record->member_id }}</td></tr>
<tr><th>Vaccine ID</th><td>{{ $record->vaccine_id }}</td></tr>
<tr><th>Dose No</th><td>{{ $record->dose_no }}</td></tr>
<tr><th>Date Given</th><td>{{ optional($record->date_given)->format('Y-m-d') }}</td></tr>
<tr><th>Next Due</th><td>{{ optional($record->next_due_date)->format('Y-m-d') }}</td></tr>
</table>
<p class="text-muted">Keep this certificate safe. QR verification can be enabled in the next release.</p>
<button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i> Print / Save as PDF</button>
</div></div></section>
@endsection
