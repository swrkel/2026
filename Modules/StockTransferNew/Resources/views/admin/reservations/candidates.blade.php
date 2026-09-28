@extends('layouts.app')
@section('title', __('stocktransfernew::reservations.candidate_title'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-reservations.css') }}">
<section class="content-header stn-page-header">
    <h1>{{ __('stocktransfernew::reservations.candidate_title') }}</h1>
    <div class="stn-toolbar"><a href="{{ route('stock-transfer-new.admin.reservations.index') }}" class="btn btn-default">Back</a></div>
</section>
<section class="content stn-reservation-page">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="box box-primary stn-box"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped stn-table" id="stn-reservation-candidates-table">
            <thead><tr><th>Transfer No</th><th>Date</th><th>Status</th><th>SKU</th><th>Product</th><th>Batch</th><th class="text-right">Requested Qty</th><th class="text-right">Value</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td>{{ $row->transfer_no }}</td><td>{{ $row->transaction_date }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->sku }}</td><td>{{ $row->product_name }}</td><td>{{ $row->batch_no }}</td><td class="text-right">{{ number_format($row->requested_qty, 4) }}</td><td class="text-right">{{ number_format($row->requested_value, 4) }}</td>
                    <td>
                        <form method="POST" action="{{ route('stock-transfer-new.admin.reservations.reserve', $row->transfer_line_id) }}" class="form-inline">
                            @csrf
                            <input type="number" name="expiry_hours" class="form-control input-sm stn-expiry-input" placeholder="Hours">
                            <button class="btn btn-xs btn-primary">{{ __('stocktransfernew::reservations.reserve') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center">{{ __('messages.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</section>
<script src="{{ asset('modules/stocktransfernew/js/stocktransfernew-reservations.js') }}"></script>
@endsection
