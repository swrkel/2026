@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-card disnew-page"><div class="pos-card-header"><h4>Returns & Credit Notes Summary</h4></div><div class="pos-card-body">
<form method="GET" class="mb-3"><div class="row"><div class="col-md-3"><input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="form-control"></div><div class="col-md-3"><input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="form-control"></div><div class="col-md-2"><button class="btn btn-primary">Filter</button></div></div></form>
<div class="row"><div class="col-md-3"><div class="pos-kpi">Returns<br><strong>{{ $summary['return_count'] }}</strong></div></div><div class="col-md-3"><div class="pos-kpi">Return Total<br><strong>{{ number_format($summary['return_total'], 4) }}</strong></div></div><div class="col-md-3"><div class="pos-kpi">Credit Notes<br><strong>{{ $summary['credit_note_count'] }}</strong></div></div><div class="col-md-3"><div class="pos-kpi">Credit Total<br><strong>{{ number_format($summary['credit_note_total'], 4) }}</strong></div></div></div></div></div>
@endsection
