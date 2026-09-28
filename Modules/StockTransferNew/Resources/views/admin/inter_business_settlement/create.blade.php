@extends('layouts.app')
@section('title', __('stocktransfernew::inter_business_settlement.create'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-inter-business-settlement.css') }}">
<section class="content-header stn-ibs-header"><h1>{{ __('stocktransfernew::inter_business_settlement.create') }}</h1><p>Select completed inter-business transfers to settle.</p></section>
<section class="content stn-ibs-page">
    <div class="box box-solid stn-ibs-toolbar">
        <form method="GET" action="{{ route('stock-transfer-new.inter-business-settlement.create') }}" class="stn-ibs-filter-form">
            <input type="number" name="from_business_id" value="{{ $filters['from_business_id'] ?? '' }}" placeholder="From business ID" class="form-control">
            <input type="number" name="to_business_id" value="{{ $filters['to_business_id'] ?? '' }}" placeholder="To business ID" class="form-control">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
            <button class="btn btn-primary">Filter</button>
        </form>
    </div>
    <form method="POST" action="{{ route('stock-transfer-new.inter-business-settlement.store') }}">
        @csrf
        <div class="box box-solid"><div class="box-body row">
            <div class="col-md-3"><label>From Business ID</label><input type="number" name="from_business_id" value="{{ $filters['from_business_id'] ?? '' }}" class="form-control" required></div>
            <div class="col-md-3"><label>To Business ID</label><input type="number" name="to_business_id" value="{{ $filters['to_business_id'] ?? '' }}" class="form-control" required></div>
            <div class="col-md-3"><label>Settlement Date</label><input type="date" name="settlement_date" value="{{ date('Y-m-d') }}" class="form-control" required></div>
            <div class="col-md-3"><label>Remarks</label><input type="text" name="remarks" class="form-control"></div>
        </div></div>
        <div class="box box-solid"><div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-ibs-table">
                <thead><tr><th><input type="checkbox" data-stn-check-all></th><th>Date</th><th>Transfer No</th><th>From</th><th>To</th><th>Value</th><th>Variance</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($eligibleTransfers as $row)
                    <tr><td><input type="checkbox" name="transfer_ids[]" value="{{ $row->id }}"></td><td>{{ $row->transaction_date }}</td><td>{{ $row->transfer_no }}</td><td>{{ $row->from_business_name }}</td><td>{{ $row->to_business_name }}</td><td class="text-right">{{ number_format($row->transfer_value, 4) }}</td><td class="text-right">{{ number_format($row->variance_value, 4) }}</td><td>{{ $row->status }}</td></tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No eligible completed inter-business transfers found.</td></tr>
                @endforelse
                </tbody>
            </table>
            <button class="btn btn-success">Create Settlement</button>
        </div></div>
    </form>
</section>
<script src="{{ asset('modules/stocktransfernew/js/stocktransfernew-inter-business-settlement.js') }}"></script>
@endsection
