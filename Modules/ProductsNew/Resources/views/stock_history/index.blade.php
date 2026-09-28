@extends('productsnew::layouts.app')
@section('productsnew_page_title', 'Product Stock History')
@section('productsnew_page_subtitle', 'Complete product movement ledger by date, location and store')

@section('productsnew_content')
<div class="pn-card pn-stock-history-filter no-print">
    <div class="pn-card-header">
        <div>
            <strong><i class="fa fa-filter"></i> Product Stock History Filters</strong>
            <span class="pn-muted">Use the standard date range, quick search, location and store filters.</span>
        </div>
    </div>
    <div class="pn-card-body">
        <form method="get" id="pn-stock-history-filter-form">
            <input type="hidden" name="from_date" id="pn_stock_history_from_date" value="{{ $filters['from_date'] }}">
            <input type="hidden" name="to_date" id="pn_stock_history_to_date" value="{{ $filters['to_date'] }}">

            <div class="pn-stock-history-toolbar">
                <div class="pn-filter-field pn-filter-date"><label for="pn_stock_history_date_range">Date Range</label><input type="text" id="pn_stock_history_date_range" class="form-control" readonly value="{{ date('d/m/Y', strtotime($filters['from_date'])) }} ~ {{ date('d/m/Y', strtotime($filters['to_date'])) }}" placeholder="Select a date range"></div>
                <div class="pn-filter-field pn-filter-location"><label for="pn_stock_history_location">Location</label><select class="form-control select2 pn-searchable-select" id="pn_stock_history_location" name="location_id" data-placeholder="All Locations" style="width:100%"><option value="">All Locations</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected((int)$filters['location_id']===(int)$location->id)>{{ $location->name }}</option>@endforeach</select></div>
                <div class="pn-filter-field pn-filter-store"><label for="pn_stock_history_store">Store</label><select class="form-control select2 pn-searchable-select" id="pn_stock_history_store" name="store_id" data-placeholder="All Stores" style="width:100%"><option value="">All Stores</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected((int)$filters['store_id']===(int)$store->id)>{{ $store->name }}</option>@endforeach</select></div>
                <div class="pn-filter-field pn-filter-product"><label for="pn_stock_history_product">Product</label><select class="form-control select2 pn-searchable-select" id="pn_stock_history_product" name="product_id" data-placeholder="All Products" style="width:100%"><option value="">All Products</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((int)$filters['product_id']===(int)$product->id)>{{ $product->name }}{{ $product->sku ? ' — '.$product->sku : '' }}</option>@endforeach</select></div>
                <div class="pn-filter-field pn-filter-movement"><label for="pn_stock_history_movement">Movement</label><select class="form-control select2 pn-searchable-select" id="pn_stock_history_movement" name="movement_type" data-placeholder="All Movements" style="width:100%"><option value="">All Movements</option>@foreach($movementTypes as $key => $label)<option value="{{ $key }}" @selected($filters['movement_type']===$key)>{{ $label }}</option>@endforeach</select></div>
                <div class="pn-filter-field pn-filter-search"><label for="pn_stock_history_search">Search</label><div class="input-group pn-search-input-group"><span class="input-group-addon"><i class="fa fa-search"></i></span><input type="text" class="form-control" id="pn_stock_history_search" name="search" value="{{ $filters['search'] }}" placeholder="Product, SKU, barcode or reference"></div></div>
            </div>
            <div class="pn-history-filter-actions-row"><div class="pn-history-filter-actions"><button type="submit" class="pn-btn pn-btn-primary" title="Apply Filters"><i class="fa fa-filter"></i><span>Apply</span></button><a class="pn-btn pn-btn-light" href="{{ route('products-new.stock-history.index') }}" title="Reset Filters"><i class="fa fa-refresh"></i><span>Reset</span></a><button type="button" class="pn-btn pn-btn-light" onclick="window.print()" title="Print"><i class="fa fa-print"></i><span>Print</span></button></div></div>
        </form>
    </div>
</div>

<div class="pn-history-summary-header">
    <div class="pn-history-summary-title">
        <h3>Stock Movement Summary</h3>
        <p>
            <i class="fa fa-calendar"></i>
            {{ date('d F Y', strtotime($filters['from_date'])) }}
            <span class="pn-history-date-separator">to</span>
            {{ date('d F Y', strtotime($filters['to_date'])) }}
        </p>
    </div>
    <span class="pn-summary-note"><i class="fa fa-info-circle"></i> Quantities are shown to 3 decimal places</span>
</div>

@php
$cards = [
 ['Opening Stock Qty','opening_stock','fa-cubes','neutral'],
 ['Purchased Qty','purchased','fa-shopping-cart','in'],
 ['Sold Qty','sold','fa-line-chart','out'],
 ['Purchase Returned Qty','purchase_returned','fa-reply','out'],
 ['Sold Returned Qty','sale_returned','fa-undo','in'],
 ['Stock Adjustment Qty','adjustment','fa-sliders','adjust'],
 ['Transferred In','transferred_in','fa-arrow-down','in'],
 ['Transferred Out','transferred_out','fa-arrow-up','out'],
 ['Closing Stock Qty','closing_stock','fa-balance-scale','closing']
];
@endphp
<div class="pn-history-kpi-grid">
@foreach($cards as [$label,$key,$icon,$tone])
    <div class="pn-history-kpi pn-history-kpi-{{ $tone }}">
        <div class="pn-history-kpi-icon"><i class="fa {{ $icon }}"></i></div>
        <div class="pn-history-kpi-content"><span>{{ $label }}</span><strong>{{ number_format((float)$summary[$key],3) }}</strong></div>
    </div>
@endforeach
</div>

<div class="pn-history-summary-grid">
    <div class="pn-card">
        <div class="pn-card-header"><div><strong>Location-wise Stock Status</strong><span class="pn-muted">Movement and closing quantity for the selected date range</span></div></div>
        <div class="pn-card-body pn-table-wrap"><table class="table pn-table"><thead><tr><th>Location</th><th class="text-right">Opening</th><th class="text-right">Qty In</th><th class="text-right">Qty Out</th><th class="text-right">Net Change</th><th class="text-right">Closing</th></tr></thead><tbody>
        @forelse($locationSummary as $row)<tr><td><strong>{{ $row['name'] }}</strong></td><td class="text-right">{{ number_format($row['opening'],3) }}</td><td class="text-right pn-qty-in">{{ number_format($row['in'],3) }}</td><td class="text-right pn-qty-out">{{ number_format($row['out'],3) }}</td><td class="text-right">{{ number_format($row['net'],3) }}</td><td class="text-right"><strong>{{ number_format($row['closing'],3) }}</strong></td></tr>@empty<tr><td colspan="6" class="text-center pn-empty">No location movement found for the selected filters.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="pn-card">
        <div class="pn-card-header"><div><strong>Store-wise Stock Status</strong><span class="pn-muted">Separate movement view for stores</span></div></div>
        <div class="pn-card-body pn-table-wrap"><table class="table pn-table"><thead><tr><th>Store</th><th class="text-right">Opening</th><th class="text-right">Qty In</th><th class="text-right">Qty Out</th><th class="text-right">Net Change</th><th class="text-right">Closing</th></tr></thead><tbody>
        @forelse($storeSummary as $row)<tr><td><strong>{{ $row['name'] }}</strong></td><td class="text-right">{{ number_format($row['opening'],3) }}</td><td class="text-right pn-qty-in">{{ number_format($row['in'],3) }}</td><td class="text-right pn-qty-out">{{ number_format($row['out'],3) }}</td><td class="text-right">{{ number_format($row['net'],3) }}</td><td class="text-right"><strong>{{ number_format($row['closing'],3) }}</strong></td></tr>@empty<tr><td colspan="6" class="text-center pn-empty">No store movement found for the selected filters.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>

<div class="pn-card pn-history-ledger">
    <div class="pn-card-header"><div><strong>Detailed Product Movement Ledger</strong><span class="pn-muted">Opening stock is shown first, then stock-affecting transactions are listed in date and time order</span></div><span class="pn-badge">{{ number_format($paginator->total()) }} records</span></div>
    <div class="pn-card-body pn-table-wrap"><table class="table pn-table pn-history-table">
        <colgroup><col class="pn-history-col-date"><col class="pn-history-col-product"><col class="pn-history-col-sku"><col class="pn-history-col-store"><col class="pn-history-col-movement"><col class="pn-history-col-reference"><col class="pn-history-col-opening"><col class="pn-history-col-qty"><col class="pn-history-col-qty"><col class="pn-history-col-balance"><col class="pn-history-col-notes"></colgroup>
        <thead><tr><th>Date & Time</th><th>Product / Variation</th><th>SKU</th><th>Store</th><th>Movement</th><th>Reference</th><th class="text-right">Opening</th><th class="text-right">Qty In</th><th class="text-right">Qty Out</th><th class="text-right">Balance</th><th>Notes</th></tr></thead><tbody>
        @forelse($paginator as $row)<tr><td class="pn-nowrap">{{ $row['movement_at']->format('d M Y') }}<small>{{ $row['movement_at']->format('h:i:s A') }}</small></td><td><strong>{{ $row['product_name'] }}</strong><small>{{ $row['variation_name'] }}</small></td><td>{{ $row['sku'] ?: '—' }}</td><td>{{ $row['store_name'] }}</td><td><span class="pn-movement-badge pn-movement-{{ $row['movement_type'] }}">{{ $row['movement_label'] }}</span></td><td>{{ $row['reference_no'] }}</td><td class="text-right pn-qty-opening">{{ $row['opening_qty'] ? number_format($row['opening_qty'],3) : '—' }}</td><td class="text-right pn-qty-in">{{ $row['qty_in'] ? number_format($row['qty_in'],3) : '—' }}</td><td class="text-right pn-qty-out">{{ $row['qty_out'] ? number_format($row['qty_out'],3) : '—' }}</td><td class="text-right"><strong>{{ number_format($row['balance_after'],3) }}</strong></td><td>{{ $row['notes'] ?: '—' }}</td></tr>
        @empty<tr><td colspan="11" class="text-center pn-empty"><strong>No stock movements found.</strong><br><small>Try a wider date range or reset Product, Location, Store and Movement filters.</small></td></tr>@endforelse
        </tbody></table></div><div class="pn-card-footer">{{ $paginator->links() }}</div>
</div>
@endsection

@push('javascript')
<script>
(function($){
    'use strict';
    $(function(){
        var $form = $('#pn-stock-history-filter-form');
        var $range = $('#pn_stock_history_date_range');
        var start = moment($('#pn_stock_history_from_date').val(), 'YYYY-MM-DD');
        var end = moment($('#pn_stock_history_to_date').val(), 'YYYY-MM-DD');

        if ($.fn.daterangepicker) {
            $range.daterangepicker(window.dateRangeSettings || {
                startDate: start,
                endDate: end,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, function(s, e){
                $('#pn_stock_history_from_date').val(s.format('YYYY-MM-DD'));
                $('#pn_stock_history_to_date').val(e.format('YYYY-MM-DD'));
                $range.val(s.format(window.moment_date_format || 'DD/MM/YYYY') + ' ~ ' + e.format(window.moment_date_format || 'DD/MM/YYYY'));
            });
            $range.data('daterangepicker').setStartDate(start);
            $range.data('daterangepicker').setEndDate(end);
        }

        $('.pn-searchable-select').each(function(){
            var $el = $(this);
            if ($.fn.select2) {
                if ($el.hasClass('select2-hidden-accessible')) { $el.select2('destroy'); }
                $el.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $el.data('placeholder') || 'Select',
                    minimumResultsForSearch: 0
                });
            }
        });

        $('#pn_stock_history_location, #pn_stock_history_store, #pn_stock_history_product, #pn_stock_history_movement').on('change', function(){
            // Keep manual Apply button to avoid repeated heavy report requests.
        });
    });
})(jQuery);
</script>
@endpush
