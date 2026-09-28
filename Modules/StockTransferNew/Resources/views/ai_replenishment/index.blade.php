@extends('layouts.app')

@section('title', __('stocktransfernew::messages.ai_replenishment_review'))

@section('content')
<section class="content-header stn38-header">
    <h1>{{ __('stocktransfernew::messages.ai_replenishment_review') }}</h1>
</section>

<section class="content stn38-wrap">
    <div class="stn38-toolbar">
        <form method="GET" class="stn38-filter-form">
            <select name="status" class="form-control">
                <option value="">{{ __('stocktransfernew::messages.all_statuses') }}</option>
                @foreach(['pending','approved','rejected','returned'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="risk_level" class="form-control">
                <option value="">{{ __('stocktransfernew::messages.all_risks') }}</option>
                @foreach(['critical','high','medium','low'] as $risk)
                    <option value="{{ $risk }}" @selected(request('risk_level') === $risk)>{{ ucfirst($risk) }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">{{ __('messages.filter') }}</button>
        </form>
    </div>

    <div class="box stn38-card">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn38-table">
                <thead>
                    <tr>
                        <th>{{ __('stocktransfernew::messages.product') }}</th>
                        <th>{{ __('stocktransfernew::messages.from_store') }}</th>
                        <th>{{ __('stocktransfernew::messages.to_store') }}</th>
                        <th>{{ __('stocktransfernew::messages.suggested_qty') }}</th>
                        <th>{{ __('stocktransfernew::messages.confidence') }}</th>
                        <th>{{ __('stocktransfernew::messages.risk') }}</th>
                        <th>{{ __('stocktransfernew::messages.reason') }}</th>
                        <th>{{ __('stocktransfernew::messages.status') }}</th>
                        <th>{{ __('stocktransfernew::messages.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <td>{{ $review->product_name }}<br><small>{{ $review->product_sku }}</small></td>
                            <td>{{ $review->source_store_id }}</td>
                            <td>{{ $review->store_id }}</td>
                            <td>{{ number_format((float) $review->suggested_qty, 4) }}</td>
                            <td>{{ number_format((float) $review->confidence_score, 2) }}%</td>
                            <td><span class="stn38-risk stn38-risk-{{ $review->risk_level }}">{{ ucfirst($review->risk_level) }}</span></td>
                            <td>{{ $review->reason }}</td>
                            <td>{{ ucfirst($review->status) }}</td>
                            <td>
                                @if(in_array($review->status, ['pending','returned']))
                                    <form method="POST" action="{{ route('stocktransfernew.ai-replenishment.approve', $review->id) }}" class="stn38-action-form">
                                        @csrf
                                        <input type="number" step="0.0001" min="0.0001" name="approved_qty" value="{{ $review->suggested_qty }}" class="form-control input-sm">
                                        <input type="text" name="remarks" class="form-control input-sm" placeholder="Remarks">
                                        <button class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('stocktransfernew.ai-replenishment.reject', $review->id) }}" class="stn38-inline-form">
                                        @csrf
                                        <button class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                    <form method="POST" action="{{ route('stocktransfernew.ai-replenishment.return', $review->id) }}" class="stn38-inline-form">
                                        @csrf
                                        <button class="btn btn-warning btn-sm">Return</button>
                                    </form>
                                @else
                                    <span class="text-muted">Locked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">{{ __('stocktransfernew::messages.no_records_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $reviews->links() }}
        </div>
    </div>
</section>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stn_038.css') }}">
@endpush

@push('javascript')
<script src="{{ asset('modules/stocktransfernew/js/stn_038.js') }}"></script>
@endpush
