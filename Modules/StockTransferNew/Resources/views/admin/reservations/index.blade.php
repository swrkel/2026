@extends('layouts.app')
@section('title', __('stocktransfernew::reservations.title'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-reservations.css') }}">
<section class="content-header stn-page-header">
    <h1>{{ __('stocktransfernew::reservations.title') }}</h1>
    <div class="stn-toolbar">
        <a href="{{ route('stock-transfer-new.admin.reservations.candidates') }}" class="btn btn-primary">{{ __('stocktransfernew::reservations.candidate_title') }}</a>
        <form method="POST" action="{{ route('stock-transfer-new.admin.reservations.expire') }}" class="d-inline">
            @csrf
            <button class="btn btn-warning">{{ __('stocktransfernew::reservations.expire_overdue') }}</button>
        </form>
        <a href="{{ route('stock-transfer-new.admin.reservations.export', request()->all()) }}" class="btn btn-success">{{ __('stocktransfernew::reservations.export_csv') }}</a>
    </div>
</section>

<section class="content stn-reservation-page">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="row stn-kpi-row">
        @foreach(['total_reservations','active_reservations','expired_reservations','reserved_qty','pending_qty','reserved_value'] as $key)
            <div class="col-md-2 col-sm-4 col-xs-6"><div class="stn-kpi"><span>{{ __('stocktransfernew::reservations.' . $key) }}</span><strong>{{ number_format($summary[$key] ?? 0, 4) }}</strong></div></div>
        @endforeach
    </div>

    <div class="box box-primary stn-box">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-table" id="stn-reservations-table">
                <thead><tr>
                    <th>{{ __('stocktransfernew::reservations.reservation_no') }}</th><th>{{ __('stocktransfernew::reservations.transfer_no') }}</th><th>{{ __('stocktransfernew::reservations.date') }}</th><th>{{ __('stocktransfernew::reservations.status') }}</th><th>{{ __('stocktransfernew::reservations.sku') }}</th><th>{{ __('stocktransfernew::reservations.product') }}</th><th>{{ __('stocktransfernew::reservations.reserved_qty') }}</th><th>{{ __('stocktransfernew::reservations.pending_qty') }}</th><th>{{ __('stocktransfernew::reservations.reserved_value') }}</th><th>{{ __('stocktransfernew::reservations.expires_at') }}</th><th>{{ __('messages.action') }}</th>
                </tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->reservation_no }}</td><td>{{ $row->transfer_no }}</td><td>{{ $row->reservation_date }}</td><td><span class="label label-info">{{ ucfirst($row->reservation_status) }}</span></td><td>{{ $row->sku }}</td><td>{{ $row->product_name }}</td><td class="text-right">{{ number_format($row->reserved_qty, 4) }}</td><td class="text-right">{{ number_format($row->pending_qty, 4) }}</td><td class="text-right">{{ number_format($row->reserved_value, 4) }}</td><td>{{ $row->expires_at }}</td>
                        <td>
                            @if(in_array($row->reservation_status, ['active','expired']))
                                <form method="POST" action="{{ route('stock-transfer-new.admin.reservations.release', $row->id) }}" class="stn-release-form">
                                    @csrf
                                    <input type="text" name="release_reason" class="form-control input-sm" placeholder="{{ __('stocktransfernew::reservations.release_reason') }}" required>
                                    <button class="btn btn-xs btn-danger">{{ __('stocktransfernew::reservations.release') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center">{{ __('messages.no_data') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
<script src="{{ asset('modules/stocktransfernew/js/stocktransfernew-reservations.js') }}"></script>
@endsection
