@extends('autoservice::layouts.master')
@section('title','Job '.$job->job_no)
@section('autoservice_content')
<div class="box autoservice-card">
    <div class="box-header with-border">
        <h3 class="box-title">Job Card {{ $job->job_no }}</h3>
        <div class="pull-right" style="display:flex;gap:8px;">
            @if(!in_array($job->status,['in_progress','completed','delivered']))<form method="post" action="{{ route('autoservice.jobs.start',$job->id) }}">@csrf<button class="btn btn-primary btn-sm">Start Job</button></form>@endif
            @if(!in_array($job->status,['completed','delivered']))<form method="post" action="{{ route('autoservice.jobs.complete',$job->id) }}">@csrf<button class="btn btn-success btn-sm">Complete</button></form>@endif
            <form method="post" action="{{ route('autoservice.jobs.generate_invoice',$job->id) }}">@csrf<button class="btn btn-warning btn-sm">Generate Invoice</button></form>
            <a class="btn btn-default btn-sm" target="_blank" href="{{ route('autoservice.jobs.print',$job->id) }}">Print</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3"><b>Status</b><br><span class="label label-info">{{ ucfirst(str_replace('_',' ',$job->status)) }}</span></div>
            <div class="col-md-3"><b>Total</b><br>{{ number_format($job->total_amount,2) }}</div>
            <div class="col-md-3"><b>Paid</b><br>{{ number_format($job->paid_amount,2) }}</div>
            <div class="col-md-3"><b>Balance</b><br>{{ number_format($job->balance_amount,2) }}</div>
        </div>
        <hr>
        <p><b>Complaint:</b> {{ $job->customer_complaint }}</p>
        <p><b>Advisor Notes:</b> {{ $job->advisor_notes }}</p>
        <form method="post" action="{{ route('autoservice.jobs.hold',$job->id) }}" class="row" style="margin-bottom:15px;">@csrf<div class="col-md-9"><input class="form-control" name="hold_reason" placeholder="Reason for putting job on hold"></div><div class="col-md-3"><button class="btn btn-danger btn-block">Put On Hold</button></div></form>
    </div>
</div>
<div class="box autoservice-card"><div class="box-header"><h3 class="box-title">Job Lines</h3></div><div class="box-body"><table class="table table-bordered table-striped"><thead><tr><th>Description</th><th width="90">Qty</th><th width="120">Unit</th><th width="130">Total</th></tr></thead><tbody>@foreach($job->lines as $l)<tr><td>{{ $l->description }}</td><td>{{ number_format($l->quantity,2) }}</td><td>{{ number_format($l->unit_price,2) }}</td><td>{{ number_format($l->line_total,2) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
