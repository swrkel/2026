@extends('layouts.app')
@section('title', 'Claim Settlements')
@section('content')
<section class="content-header"><h1>Claim Settlements <a href="{{ route('myhealth.billing.claims.create') }}" class="btn btn-primary pull-right">Add Settlement</a></h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<table class="table table-bordered table-striped"><thead><tr><th>Settlement No</th><th>Date</th><th>Claim</th><th>Member</th><th class="text-right">Approved</th><th class="text-right">Settled</th><th>Status</th></tr></thead><tbody>
@forelse($settlements as $settlement)<tr><td>{{ $settlement->settlement_no }}</td><td>{{ $settlement->settlement_date }}</td><td>{{ optional($settlement->claim)->claim_no }}</td><td>{{ optional(optional($settlement->claim)->member)->name }}</td><td class="text-right">{{ number_format($settlement->approved_amount, 4) }}</td><td class="text-right">{{ number_format($settlement->settled_amount, 4) }}</td><td>{{ $settlement->status }}</td></tr>@empty<tr><td colspan="7">No records found.</td></tr>@endforelse
</tbody></table>{{ $settlements->links() }}
</section>
@endsection
