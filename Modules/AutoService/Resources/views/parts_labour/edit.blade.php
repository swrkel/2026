@extends('autoservice::layouts.master')
@section('title','Parts & Labour - '.$job->job_no)
@section('autoservice_content')
<div class="box box-primary"><div class="box-body">
    <b>Job:</b> {{ $job->job_no }} &nbsp; | &nbsp;
    <b>Vehicle:</b> {{ optional($job->vehicle)->registration_no }} &nbsp; | &nbsp;
    <b>Status:</b> {{ ucwords(str_replace('_',' ', $job->status)) }} &nbsp; | &nbsp;
    <b>Total:</b> {{ number_format($job->total_amount, 2) }}
    <a href="{{ route('autoservice.parts_labour.index') }}" class="btn btn-default btn-sm pull-right">Back</a>
</div></div>

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<form method="post" action="{{ route('autoservice.parts_labour.parts', $job->id) }}">@csrf
<div class="box box-info">
    <div class="box-header with-border"><h3 class="box-title">Parts Reservation / Issue / Return</h3></div>
    <div class="box-body table-responsive">
        <input type="hidden" name="sync_job_lines" value="1">
        <table class="table table-bordered">
            <thead><tr><th style="width:130px">Type</th><th style="width:210px">Product</th><th>Description</th><th style="width:90px">Qty</th><th style="width:120px">Unit Cost</th><th style="width:145px">Date</th><th>Reference / Note</th></tr></thead>
            <tbody>
            @for($i=0;$i<10;$i++)
                @php $p=$job->partMovements[$i] ?? null; @endphp
                <tr>
                    <td><select class="form-control" name="parts[{{ $i }}][movement_type]"><option value="reserved" {{ optional($p)->movement_type=='reserved'?'selected':'' }}>Reserved</option><option value="issued" {{ optional($p)->movement_type=='issued'?'selected':'' }}>Issued</option><option value="returned" {{ optional($p)->movement_type=='returned'?'selected':'' }}>Returned</option><option value="warranty" {{ optional($p)->movement_type=='warranty'?'selected':'' }}>Warranty</option></select></td>
                    <td><select class="form-control" name="parts[{{ $i }}][product_id]"><option value="">Manual / Other</option>@foreach($products as $id=>$name)<option value="{{ $id }}" {{ optional($p)->product_id==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></td>
                    <td><input class="form-control" name="parts[{{ $i }}][description]" value="{{ optional($p)->description }}" placeholder="Part description"></td>
                    <td><input class="form-control text-right" name="parts[{{ $i }}][quantity]" value="{{ optional($p)->quantity }}"></td>
                    <td><input class="form-control text-right" name="parts[{{ $i }}][unit_cost]" value="{{ optional($p)->unit_cost }}"></td>
                    <td><input type="date" class="form-control" name="parts[{{ $i }}][movement_date]" value="{{ optional($p)->movement_date ?? date('Y-m-d') }}"></td>
                    <td><input class="form-control" name="parts[{{ $i }}][reference_no]" value="{{ optional($p)->reference_no }}" placeholder="Reference"></td>
                </tr>
            @endfor
            </tbody>
        </table>
    </div>
    <div class="box-footer"><button class="btn btn-primary">Save Parts & Refresh Totals</button></div>
</div>
</form>

<form method="post" action="{{ route('autoservice.parts_labour.labour', $job->id) }}">@csrf
<div class="box box-success">
    <div class="box-header with-border"><h3 class="box-title">Labour Billing</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>Description</th><th style="width:120px">Hours / Qty</th><th style="width:150px">Rate</th><th style="width:150px">Current Total</th></tr></thead>
            <tbody>
            @for($i=0;$i<8;$i++)
                @php $l=$labourLines[$i] ?? null; @endphp
                <tr>
                    <td><input class="form-control" name="labour[{{ $i }}][description]" value="{{ optional($l)->description }}" placeholder="Labour / service work"></td>
                    <td><input class="form-control text-right" name="labour[{{ $i }}][hours]" value="{{ optional($l)->quantity }}"></td>
                    <td><input class="form-control text-right" name="labour[{{ $i }}][hourly_rate]" value="{{ optional($l)->unit_price }}"></td>
                    <td class="text-right">{{ number_format(optional($l)->line_total ?? 0, 2) }}</td>
                </tr>
            @endfor
            </tbody>
        </table>
    </div>
    <div class="box-footer"><button class="btn btn-success">Save Labour & Refresh Totals</button></div>
</div>
</form>
@endsection
