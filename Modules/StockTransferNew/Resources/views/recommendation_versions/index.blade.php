@extends('layouts.app')
@section('title', __('stocktransfernew::messages.replenishment_versions'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stn_039.css') }}">
<section class="content-header stn39-header">
    <h1>{{ __('stocktransfernew::messages.replenishment_versions') }}</h1>
    <p>{{ __('stocktransfernew::messages.replenishment_versions_help') }}</p>
</section>
<section class="content stn39-page">
    @if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="stn39-card">
        <form method="GET" class="stn39-toolbar">
            <input type="text" name="product_id" value="{{ request('product_id') }}" placeholder="Product ID">
            <select name="status">
                <option value="">All Status</option>
                @foreach(['draft','approved','converted','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="risk_level">
                <option value="">All Risk</option>
                @foreach(['low','medium','high','critical'] as $risk)
                    <option value="{{ $risk }}" @selected(request('risk_level')===$risk)>{{ ucfirst($risk) }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">Search</button>
        </form>
    </div>

    <div class="stn39-grid">
        <div class="stn39-card">
            <h3>Create Recommendation Version</h3>
            <form method="POST" action="{{ route('stocktransfernew.replenishment_versions.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4"><label>Location ID</label><input class="form-control" name="location_id" required></div>
                    <div class="col-md-4"><label>Store ID</label><input class="form-control" name="store_id" required></div>
                    <div class="col-md-4"><label>Product ID</label><input class="form-control" name="product_id" required></div>
                </div>
                <div class="row mt-10">
                    <div class="col-md-4"><label>Recommended Qty</label><input class="form-control" name="recommended_qty" required></div>
                    <div class="col-md-4"><label>Confidence %</label><input class="form-control" name="confidence_score" value="0"></div>
                    <div class="col-md-4"><label>Risk</label><select class="form-control" name="risk_level"><option>low</option><option selected>medium</option><option>high</option><option>critical</option></select></div>
                </div>
                <label class="mt-10">Version Reason</label>
                <textarea class="form-control" name="version_reason" rows="2"></textarea>
                <button class="btn btn-success mt-10">Create Version</button>
            </form>
        </div>
    </div>

    <div class="stn39-card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped stn39-table">
                <thead>
                <tr>
                    <th>ID</th><th>Version</th><th>Product</th><th>Location</th><th>Store</th><th>Recommended</th><th>Approved</th><th>Risk</th><th>Status</th><th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($versions as $row)
                    <tr>
                        <td>{{ $row->id }}</td><td>V{{ $row->version_no }}</td><td>{{ $row->product_id }}</td><td>{{ $row->location_id }}</td><td>{{ $row->store_id }}</td>
                        <td>{{ number_format((float)$row->recommended_qty, 4) }}</td><td>{{ number_format((float)$row->approved_qty, 4) }}</td>
                        <td><span class="stn39-badge risk-{{ $row->risk_level }}">{{ ucfirst($row->risk_level) }}</span></td>
                        <td><span class="stn39-badge status-{{ $row->status }}">{{ ucfirst($row->status) }}</span></td>
                        <td class="stn39-actions">
                            @if(!in_array($row->status, ['converted','cancelled']))
                                <form method="POST" action="{{ route('stocktransfernew.replenishment_versions.approve', $row->id) }}">@csrf<input name="approved_qty" value="{{ $row->approved_qty ?: $row->recommended_qty }}"><button class="btn btn-xs btn-success">Approve</button></form>
                                @if($row->status === 'approved')<form method="POST" action="{{ route('stocktransfernew.replenishment_versions.convert', $row->id) }}">@csrf<button class="btn btn-xs btn-primary">Convert</button></form>@endif
                                <form method="POST" action="{{ route('stocktransfernew.replenishment_versions.cancel', $row->id) }}">@csrf<button class="btn btn-xs btn-danger">Cancel</button></form>
                            @else
                                <small>Locked</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">No recommendation versions found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $versions->links() }}
    </div>
</section>
<script src="{{ asset('modules/stocktransfernew/js/stn_039.js') }}"></script>
@endsection
