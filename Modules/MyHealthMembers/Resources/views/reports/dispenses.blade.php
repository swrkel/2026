@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }}</h1></section><section class="content">
@include('myhealthmembers::reports._filters')
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Dispense No</th><th>Date</th><th>Member</th><th>Status</th><th>Total</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->dispense_no }}</td><td>{{ $row->dispense_date }}</td><td>{{ optional($row->member)->name ?? $row->member_id }}</td><td>{{ $row->status }}</td><td class="text-right">{{ number_format($row->total_amount, 4) }}</td></tr>@empty<tr><td colspan="5" class="text-center">No records found</td></tr>@endforelse
</tbody></table></div></div></section>
@endsection
