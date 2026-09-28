@extends('layouts.app')
@section('title', $settlement->settlement_no)
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-inter-business-settlement.css') }}">
<section class="content-header stn-ibs-header"><h1>{{ $settlement->settlement_no }}</h1><p>{{ $settlement->from_business_name }} → {{ $settlement->to_business_name }}</p></section>
<section class="content stn-ibs-page">
    <div class="row stn-ibs-cards">
        <div class="col-md-4"><div class="stn-card"><span>Transfers</span><strong>{{ number_format($totals['transfers']) }}</strong></div></div>
        <div class="col-md-4"><div class="stn-card"><span>Transfer Value</span><strong>{{ number_format($totals['transfer_value'], 4) }}</strong></div></div>
        <div class="col-md-4"><div class="stn-card warning"><span>Variance Value</span><strong>{{ number_format($totals['variance_value'], 4) }}</strong></div></div>
    </div>
    <div class="box box-solid"><div class="box-body">
        <strong>Status:</strong> <span class="label label-default">{{ ucfirst($settlement->status) }}</span>
        @if($settlement->status === 'draft')
            <form method="POST" action="{{ route('stock-transfer-new.inter-business-settlement.approve', $settlement->id) }}" class="stn-inline-form">@csrf<input type="text" name="remarks" placeholder="Approval remarks" class="form-control"><button class="btn btn-success">Approve</button></form>
            <form method="POST" action="{{ route('stock-transfer-new.inter-business-settlement.cancel', $settlement->id) }}" class="stn-inline-form">@csrf<input type="text" name="remarks" placeholder="Cancel reason" class="form-control" required><button class="btn btn-danger">Cancel</button></form>
        @endif
    </div></div>
    <div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped stn-ibs-table"><thead><tr><th>Transfer No</th><th>Transfer Value</th><th>Variance Value</th></tr></thead><tbody>@foreach($lines as $line)<tr><td>{{ $line->transfer_no }}</td><td class="text-right">{{ number_format($line->transfer_value, 4) }}</td><td class="text-right">{{ number_format($line->variance_value, 4) }}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection
