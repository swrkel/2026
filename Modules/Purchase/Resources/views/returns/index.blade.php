@extends('layouts.app')
@section('title', __('purchase::lang.purchase_returns'))

@section('content')
<style>{!! file_get_contents(module_path('Purchase', 'Resources/assets/css/purchase-workspace.css')) !!}</style>

<section class="content purchase-workspace">
    <div class="purchase-workspace-header">
        <div>
            <h1><i class="fa fa-undo"></i> List Purchase Return Entries</h1>
            <div class="purchase-breadcrumb">Purchase (New) / List Purchase Return Entries</div>
        </div>
        <div class="purchase-workspace-actions">
            @if(app(\Modules\Purchase\Utils\PurchaseAccessUtil::class)->canCreateReturns())
                <a href="{{ route('purchase.returns.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add Purchase Return</a>
            @endif
        </div>
    </div>

    @if(session('status'))
        @php($status = session('status'))
        <div class="alert {{ !empty($status['success']) ? 'alert-success' : 'alert-danger' }}">{{ $status['msg'] ?? '' }}</div>
    @endif

    <div class="purchase-panel">
        <div class="purchase-panel-heading">
            <span><i class="fa fa-filter"></i> Filters</span>
            <a href="{{ route('purchase.returns.index') }}" class="btn btn-default btn-xs"><i class="fa fa-refresh"></i> Reset</a>
        </div>
        <div class="purchase-panel-body">
            <form method="get" action="{{ route('purchase.returns.index') }}">
                <div class="purchase-filter-grid purchase-filter-grid-4">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Return no., purchase no. or supplier">
                    </div>
                    <div class="form-group">
                        <label>Business Location</label>
                        <select name="location_id" class="form-control select2" style="width:100%">
                            <option value="">All</option>
                            @foreach($locations as $location)<option value="{{ $location->id }}" @selected((string)$filters['location_id'] === (string)$location->id)>{{ $location->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control select2" style="width:100%">
                            <option value="">All</option>
                            @foreach($suppliers as $supplier)
                                @php($supplierName = trim((string)($supplier->supplier_business_name ?: $supplier->name)))
                                <option value="{{ $supplier->id }}" @selected((string)$filters['supplier_id'] === (string)$supplier->id)>{{ $supplierName }}{{ $supplier->contact_id ? ' - '.$supplier->contact_id : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="purchase-summary-strip" style="grid-template-columns:repeat(2,minmax(0,1fr));max-width:560px;">
        <div class="purchase-summary-tile"><span>Return Records</span><strong>{{ number_format($summary['record_count']) }}</strong></div>
        <div class="purchase-summary-tile"><span>Return Total</span><strong>{{ number_format($summary['total_amount'], 2) }}</strong></div>
    </div>

    <div class="purchase-panel">
        <div class="purchase-panel-heading">
            <span><i class="fa fa-list"></i> Purchase Return Entries</span>
            <span>{{ number_format($rows->total()) }} record(s)</span>
        </div>
        <div class="purchase-panel-body">
            @include('purchase::returns.partials.table')
            <div class="text-right">{{ $rows->links() }}</div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/purchase-return-list.js')) !!}</script>
@endsection
