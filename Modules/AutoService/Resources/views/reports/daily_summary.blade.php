@extends('autoservice::layouts.master')
@section('title','Daily Workshop Summary')
@section('autoservice_content')
<form class="form-inline" method="get">
    <div class="form-group"><label>Date</label> <input type="date" name="date" value="{{ $date }}" class="form-control"></div>
    <button class="btn btn-primary">Filter</button>
</form>
<br>
<div class="row">
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-wrench"></i></span><div class="info-box-content"><span class="info-box-text">Jobs</span><span class="info-box-number">{{ $jobs_count }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Completed</span><span class="info-box-number">{{ $completed_count }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-file-text"></i></span><div class="info-box-content"><span class="info-box-text">Invoice Total</span><span class="info-box-number">{{ number_format($invoice_total, 2) }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">Balance</span><span class="info-box-number">{{ number_format($balance_total, 2) }}</span></div></div></div>
</div>
<div class="box"><div class="box-header with-border"><h3 class="box-title">Jobs by Status</h3></div><div class="box-body"><table class="table table-bordered"><tr><th>Status</th><th class="text-right">Count</th></tr>@foreach($status_rows as $row)<tr><td>{{ ucwords(str_replace('_',' ', $row->status ?: 'Pending')) }}</td><td class="text-right">{{ $row->total }}</td></tr>@endforeach</table></div></div>
@endsection
