@extends('layouts.app')
@section('title', 'Insurance Claim Details')
@section('content')
<section class="content-header"><h1>Insurance Claim - {{ $claim->claim_no }}</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<p><strong>Member:</strong> {{ optional($claim->member)->myhealth_code }} - {{ optional($claim->member)->name }}</p>
<p><strong>Policy:</strong> {{ optional($claim->policy)->policy_no }} / {{ optional(optional($claim->policy)->company)->company_name }}</p>
<p><strong>Date:</strong> {{ $claim->claim_date }} <strong>Status:</strong> {{ ucfirst($claim->status) }}</p>
<p><strong>Claim:</strong> {{ number_format($claim->claim_amount, 4) }} <strong>Approved:</strong> {{ number_format($claim->approved_amount, 4) }} <strong>Settled:</strong> {{ number_format($claim->settled_amount, 4) }}</p>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Type</th><th>Description</th><th>Service Date</th><th>Amount</th><th>Approved</th></tr></thead><tbody>
@foreach($claim->items as $item)<tr><td>{{ $item->item_type }}</td><td>{{ $item->description }}</td><td>{{ $item->service_date }}</td><td>{{ number_format($item->amount, 4) }}</td><td>{{ number_format($item->approved_amount, 4) }}</td></tr>@endforeach
</tbody></table></div>
<a href="{{ route('myhealth.insurance.claims.index') }}" class="btn btn-default">Back</a>
</div></div></section>
@endsection
