@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.supplier_stock_report'))
@section('suppliers_content')
<section class="content-header supplier-stock-report-heading">
    <h1>
        <span>@lang('suppliers::lang.supplier_stock_report')</span>
        @if($supplier)
            <span class="supplier-tab-supplier-name">
                {{ $supplier->name }} @if($supplier->contact_id) ({{ $supplier->contact_id }}) @endif
            </span>
        @endif
    </h1>
</section>
<section class="content main-content-inner">
    <div class="box box-primary"><div class="box-body">
        @include('suppliers::partials.tabs', [
            'active' => 'stock_report',
            'supplierId' => optional($supplier)->id,
        ])

        @if($supplier && $stockDataUrl)
            <div id="supplier_stock_report_table_toolbar" class="supplier-dt-toolbar-host" aria-label="Supplier stock report table controls"></div>
            <div class="table-responsive supplier-full-table-shell supplier-stock-table-shell">
                <table
                    class="table table-bordered table-striped supplier-full-width-table"
                    id="supplier_stock_report_table"
                    style="width:100%"
                    data-source-url="{{ $stockDataUrl }}"
                >
                    <thead><tr>
                        <th>@lang('suppliers::lang.product')</th>
                        <th>@lang('suppliers::lang.qty')</th>
                        <th>@lang('suppliers::lang.purchase_price')</th>
                        <th>@lang('suppliers::lang.date')</th>
                        <th>@lang('suppliers::lang.reference_no')</th>
                    </tr></thead>
                </table>
            </div>
        @else
            <div class="alert alert-info">Please select a supplier from the Supplier List to view the stock report.</div>
        @endif
    </div></div>
</section>
@endsection
