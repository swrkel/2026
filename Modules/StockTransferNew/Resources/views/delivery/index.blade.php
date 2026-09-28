@extends('stocktransfernew::layouts.app')
@section('title', __('stocktransfernew::delivery.title'))
@section('content')
<div class="stn-page">
    <div class="stn-toolbar">
        <h3>{{ __('stocktransfernew::delivery.title') }}</h3>
        <form method="GET" class="stn-filter-row">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('stocktransfernew::delivery.search') }}">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            <select name="status">
                <option value="">{{ __('stocktransfernew::delivery.all_status') }}</option>
                <option value="good">Good</option>
                <option value="damaged">Damaged</option>
                <option value="short">Short</option>
            </select>
            <button class="btn btn-primary">{{ __('stocktransfernew::delivery.filter') }}</button>
        </form>
    </div>
    <div class="table-responsive stn-card">
        <table class="table table-bordered table-sm stn-table">
            <thead><tr><th>#</th><th>Transfer</th><th>Delivered At</th><th>Receiver</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($records as $row)
                <tr>
                    <td>{{ $row->id }}</td><td>{{ $row->transfer_id }}</td><td>{{ optional($row->delivered_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $row->received_by }}</td><td>{{ ucfirst($row->condition_status) }}</td>
                    <td><a class="btn btn-sm btn-info" href="{{ route('stock-transfer-new.delivery.show', $row->id) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">{{ __('stocktransfernew::delivery.no_records') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $records->links() }}
    </div>
</div>
@endsection
