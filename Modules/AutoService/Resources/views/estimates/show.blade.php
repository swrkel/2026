@extends('autoservice::layouts.master')
@section('title','Estimate '.$estimate->estimate_no)
@section('autoservice_content')
<div class="box autoservice-card">
    <div class="box-header with-border">
        <h3 class="box-title">Estimate Details</h3>
        <div class="pull-right" style="display:flex;gap:8px;">
            @if(!in_array($estimate->status, ['approved','converted']))
                <form method="post" action="{{ route('autoservice.estimates.approve',$estimate->id) }}">@csrf<button class="btn btn-success btn-sm">Approve</button></form>
            @endif
            @if($estimate->status !== 'converted')
                <form method="post" action="{{ route('autoservice.estimates.convert_to_job',$estimate->id) }}">@csrf<button class="btn btn-primary btn-sm">Convert to Job Card</button></form>
            @elseif($estimate->job_id)
                <a class="btn btn-info btn-sm" href="{{ route('autoservice.jobs.show',$estimate->job_id) }}">Open Job Card</a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3"><b>Date:</b><br>{{ $estimate->estimate_date }}</div>
            <div class="col-md-3"><b>Status:</b><br><span class="label label-info">{{ ucfirst(str_replace('_',' ',$estimate->status)) }}</span></div>
            <div class="col-md-3"><b>Total:</b><br>{{ number_format($estimate->total_amount,2) }}</div>
            <div class="col-md-3"><b>Valid Until:</b><br>{{ $estimate->valid_until }}</div>
        </div>
        <hr>
        <p><b>Complaint:</b> {{ $estimate->customer_complaint }}</p>
        <p><b>Advisor Notes:</b> {{ $estimate->advisor_notes }}</p>
        <table class="table table-bordered table-striped">
            <thead><tr><th>Description</th><th width="90">Qty</th><th width="120">Unit</th><th width="130">Total</th></tr></thead>
            <tbody>@foreach($estimate->lines as $line)<tr><td>{{ $line->description }}</td><td>{{ number_format($line->quantity,2) }}</td><td>{{ number_format($line->unit_price,2) }}</td><td>{{ number_format($line->line_total,2) }}</td></tr>@endforeach</tbody>
            <tfoot><tr><th colspan="3" class="text-right">Subtotal</th><th>{{ number_format($estimate->subtotal,2) }}</th></tr><tr><th colspan="3" class="text-right">Discount</th><th>{{ number_format($estimate->discount_amount,2) }}</th></tr><tr><th colspan="3" class="text-right">Tax</th><th>{{ number_format($estimate->tax_amount,2) }}</th></tr><tr><th colspan="3" class="text-right">Grand Total</th><th>{{ number_format($estimate->total_amount,2) }}</th></tr></tfoot>
        </table>
    </div>
</div>
@endsection
