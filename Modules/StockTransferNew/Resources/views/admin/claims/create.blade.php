@extends('stocktransfernew::layouts.app')
@section('title', __('stocktransfernew::claims.create'))
@section('content')
<div class="stn-claims-page">
    <div class="stn-page-header"><div><h3>{{ __('stocktransfernew::claims.create') }}</h3><p>Select a completed transfer with shortage, damage or excess variance.</p></div><a href="{{ route('stock-transfer-new.claims.index') }}" class="btn btn-default">Back</a></div>
    <form method="get" class="stn-toolbar">
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"><button class="btn btn-secondary">Find Eligible Transfers</button>
    </form>
    <div class="table-responsive stn-card">
        <table class="table table-bordered table-sm stn-table">
            <thead><tr><th>Transfer No</th><th>Date</th><th>Status</th><th class="text-right">Shortage</th><th class="text-right">Excess</th><th class="text-right">Variance Value</th><th>Claim</th></tr></thead>
            <tbody>
            @forelse($eligibleTransfers as $transfer)
                <tr>
                    <td>{{ $transfer->transfer_no }}</td><td>{{ $transfer->transaction_date }}</td><td>{{ ucfirst($transfer->status) }}</td><td class="text-right">{{ number_format($transfer->shortage_qty, 4) }}</td><td class="text-right">{{ number_format($transfer->excess_qty, 4) }}</td><td class="text-right">{{ number_format($transfer->variance_value, 2) }}</td>
                    <td>
                        <form method="post" action="{{ route('stock-transfer-new.claims.store') }}" class="stn-inline-form">@csrf
                            <input type="hidden" name="transfer_id" value="{{ $transfer->id }}"><input type="hidden" name="claim_type" value="variance"><input type="date" name="claim_date" value="{{ date('Y-m-d') }}"><input type="text" name="responsible_party" placeholder="Responsible party"><button class="btn btn-primary btn-xs">Create</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No eligible transfer variances found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
