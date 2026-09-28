@extends('autoservice::layouts.master')
@section('title','Auto Service Billing & Delivery')
@section('autoservice_content')
<div class="row">
    <div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $summary['ready'] ?? 0 }}</h3><p>Ready for Billing</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-green"><div class="inner"><h3>{{ $summary['invoiced'] ?? 0 }}</h3><p>Invoiced Jobs</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-yellow"><div class="inner"><h3>{{ number_format((float)($summary['unpaid'] ?? 0), 2) }}</h3><p>Outstanding</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-purple"><div class="inner"><h3>{{ number_format((float)($summary['paid_today'] ?? 0), 2) }}</h3><p>Paid Today</p></div></div></div>
</div>
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Billing, Payment & Vehicle Release Board</h3></div>
    <div class="box-body">
        <form method="GET" class="form-inline" style="margin-bottom:15px">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search Job No">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                @foreach(['qc_passed','ready_for_delivery','completed','invoiced','partial','invoiced_paid'] as $st)
                    <option value="{{ $st }}" @selected(request('status')==$st)>{{ ucwords(str_replace('_',' ',$st)) }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">Search</button>
        </form>
        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr>
                <th>Job No</th><th>Customer</th><th>Vehicle</th><th>Status</th><th>Total</th><th>Paid</th><th>Balance</th><th style="min-width:360px">Actions</th>
            </tr></thead>
            <tbody>
            @forelse($jobs as $job)
                @php
                    $invoice = \Modules\AutoService\Entities\AutoServiceInvoice::where('job_id',$job->id)->latest()->first();
                    $balance = $invoice ? (float)$invoice->balance_amount : (float)($job->balance_amount ?? 0);
                @endphp
                <tr>
                    <td>{{ $job->job_no ?? $job->id }}</td>
                    <td>{{ $job->contact_id }}</td>
                    <td>{{ $job->vehicle_id }}</td>
                    <td><span class="label label-info">{{ ucwords(str_replace('_',' ', $job->status)) }}</span></td>
                    <td>{{ number_format((float)($invoice->total_amount ?? $job->total_amount ?? 0), 2) }}</td>
                    <td>{{ number_format((float)($invoice->paid_amount ?? $job->paid_amount ?? 0), 2) }}</td>
                    <td>{{ number_format($balance, 2) }}</td>
                    <td>
                        @if(!$invoice)
                            <form method="POST" action="{{ route('autoservice.billing_delivery.generate_invoice',$job->id) }}" style="display:inline">@csrf<button class="btn btn-xs btn-primary">Generate Invoice</button></form>
                        @else
                            <a class="btn btn-xs btn-default" href="{{ route('autoservice.invoices.print',$invoice->id) }}" target="_blank">Print</a>
                            @if($balance > 0)
                                <form method="POST" action="{{ route('autoservice.billing_delivery.mark_paid',$invoice->id) }}" style="display:inline" class="form-inline">@csrf
                                    <input type="hidden" name="amount" value="{{ $balance }}"><input type="hidden" name="method" value="cash">
                                    <button class="btn btn-xs btn-success">Mark Paid</button>
                                </form>
                            @endif
                            @if($job->status != 'delivered')
                                <form method="POST" action="{{ route('autoservice.billing_delivery.release',$job->id) }}" style="display:inline" onsubmit="return confirm('Release this vehicle to customer?')">@csrf
                                    @if($balance > 0)<input type="hidden" name="allow_credit_release" value="1">@endif
                                    <input type="hidden" name="delivery_note" value="Released from Billing & Delivery board">
                                    <button class="btn btn-xs btn-warning">Release Vehicle</button>
                                </form>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No jobs found for billing/delivery.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        {{ $jobs->appends(request()->query())->links() }}
    </div>
</div>
@endsection
