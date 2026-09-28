@extends('layouts.app')
@section('title', __('sale.products'))

@section('content')

@php
    $business_id = request()->session()->get('user.business_id');
    $subscription = Modules\Superadmin\Entities\Subscription::current_subscription($business_id);
    $pacakge_details = array();
    
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
    }
@endphp

<style>
/* PLP-001 Professional Product List top section */
#product-list-page .product-filter-card{background:#fff;border:1px solid #e7edf5;border-radius:20px;box-shadow:0 12px 32px rgba(15,76,129,.08);margin-bottom:14px;overflow:hidden}
#product-list-page .product-filter-title{display:flex;align-items:center;gap:8px;padding:12px 18px;background:linear-gradient(135deg,#f8fbff,#eef6ff);border-bottom:1px solid #e7edf5;color:#334155;font-size:16px;font-weight:800}
#product-list-page .product-filter-body{padding:14px 16px 10px 16px}
#product-list-page .product-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:12px 16px;align-items:end}
#product-list-page .product-filter-item label{display:block;color:#475569;font-size:13px;font-weight:800;margin-bottom:6px}
#product-list-page .product-filter-item .form-control,#product-list-page .product-filter-item .select2-container .select2-selection--single{min-height:42px!important;border:1px solid #dbe7f3!important;border-radius:14px!important;box-shadow:0 6px 18px rgba(15,76,129,.05)!important}
#product-list-page .product-filter-checks{display:flex;flex-wrap:wrap;gap:10px 18px;align-items:center;padding-top:4px}
#product-list-page .product-filter-checks label{margin:0;font-size:14px;color:#475569}
#product-list-page .product-filter-reset{border:0;border-radius:12px;background:linear-gradient(135deg,#64748b,#334155);color:#fff;padding:10px 14px;font-weight:800;box-shadow:0 8px 18px rgba(51,65,85,.18)}
#product-list-page .product-tabs-card.nav-tabs-custom{border-radius:20px;border:1px solid #e7edf5;box-shadow:0 12px 32px rgba(15,76,129,.08);overflow:hidden}
#product-list-page .product-page-actions{width:100%!important;display:flex!important;justify-content:flex-end!important;align-items:center!important;gap:10px!important;flex-wrap:wrap!important;margin:8px 0 14px 0!important;text-align:right!important;clear:both!important}
#product-list-page .product-page-actions .btn{border:0!important;border-radius:12px!important;padding:10px 16px!important;font-weight:800!important;box-shadow:0 8px 18px rgba(15,76,129,.14)!important}
#product-list-page .product-bulk-action-bar{display:flex;flex-wrap:wrap;align-items:center;gap:8px;background:#fff;border:1px solid #e7edf5;border-radius:16px;padding:12px;margin:0 0 12px 0;box-shadow:0 8px 22px rgba(15,76,129,.06)}
#product-list-page .product-bulk-action-bar form{display:inline-flex;margin:0}
#product-list-page .product-bulk-action-bar .btn,#product-list-page .product-bulk-action-bar input[type="submit"]{border-radius:10px!important;font-size:13px!important;font-weight:800!important;padding:8px 12px!important}
#product-list-page .product-tabs-card,#product-list-page .tab-content,#product-list-page .tab-pane,#product-list-page .dataTables_wrapper{width:100%!important;max-width:100%!important}
#product-list-page .dataTables_scroll,#product-list-page .dataTables_scrollHead,#product-list-page .dataTables_scrollBody{width:100%!important;max-width:100%!important}
#product-list-page #product_table .btn-group>.dropdown-toggle.btn-xs{font-size:15px!important;line-height:1.5!important;padding:4px 9px!important;min-height:32px!important;border-radius:8px!important}
#product-list-page #product_table .btn-group>.dropdown-toggle.btn-xs .caret{margin-left:5px}

/* PLP-002 stable Product List column widths.
   Widths are calculated from the page's existing rendered columns and then
   adjusted by the requested percentages. This keeps the result stable even
   when permissions add/remove price columns or the browser size changes. */
#product-list-page #product_table,
#product-list-page #inactive_product_table{
    width:100%!important;
    table-layout:auto!important;
}
#product-list-page.plp-column-widths-ready #product_table th.plp-col-product,
#product-list-page.plp-column-widths-ready #product_table td.plp-col-product,
#product-list-page.plp-column-widths-ready #inactive_product_table th.plp-col-product,
#product-list-page.plp-column-widths-ready #inactive_product_table td.plp-col-product{
    width:var(--plp-product-width)!important;
    min-width:var(--plp-product-width)!important;
    max-width:var(--plp-product-width)!important;
    box-sizing:border-box!important;
    white-space:normal!important;
    overflow-wrap:anywhere;
}
#product-list-page.plp-column-widths-ready #product_table th.plp-col-purchase-price,
#product-list-page.plp-column-widths-ready #product_table td.plp-col-purchase-price,
#product-list-page.plp-column-widths-ready #product_table th.plp-col-selling-price,
#product-list-page.plp-column-widths-ready #product_table td.plp-col-selling-price,
#product-list-page.plp-column-widths-ready #inactive_product_table th.plp-col-purchase-price,
#product-list-page.plp-column-widths-ready #inactive_product_table td.plp-col-purchase-price,
#product-list-page.plp-column-widths-ready #inactive_product_table th.plp-col-selling-price,
#product-list-page.plp-column-widths-ready #inactive_product_table td.plp-col-selling-price{
    width:var(--plp-price-width)!important;
    min-width:var(--plp-price-width)!important;
    max-width:var(--plp-price-width)!important;
    box-sizing:border-box!important;
}
#product-list-page.plp-column-widths-ready #product_table th.plp-col-current-stock,
#product-list-page.plp-column-widths-ready #product_table td.plp-col-current-stock,
#product-list-page.plp-column-widths-ready #inactive_product_table th.plp-col-current-stock,
#product-list-page.plp-column-widths-ready #inactive_product_table td.plp-col-current-stock{
    width:var(--plp-current-stock-width)!important;
    min-width:var(--plp-current-stock-width)!important;
    max-width:var(--plp-current-stock-width)!important;
    box-sizing:border-box!important;
}
#product-list-page.plp-column-widths-ready #product_table th.plp-col-product-type,
#product-list-page.plp-column-widths-ready #product_table td.plp-col-product-type,
#product-list-page.plp-column-widths-ready #inactive_product_table th.plp-col-product-type,
#product-list-page.plp-column-widths-ready #inactive_product_table td.plp-col-product-type{
    width:var(--plp-product-type-width)!important;
    min-width:var(--plp-product-type-width)!important;
    max-width:var(--plp-product-type-width)!important;
    box-sizing:border-box!important;
    white-space:normal!important;
}
#product-list-page.plp-column-widths-ready #product_table th.plp-col-category,
#product-list-page.plp-column-widths-ready #product_table td.plp-col-category,
#product-list-page.plp-column-widths-ready #inactive_product_table th.plp-col-category,
#product-list-page.plp-column-widths-ready #inactive_product_table td.plp-col-category{
    width:var(--plp-category-width)!important;
    min-width:var(--plp-category-width)!important;
    max-width:var(--plp-category-width)!important;
    box-sizing:border-box!important;
    white-space:normal!important;
    overflow-wrap:anywhere;
}
#product-list-page th.plp-col-purchase-price,
#product-list-page th.plp-col-selling-price,
#product-list-page th.plp-col-current-stock{
    white-space:normal!important;
}
#product-list-page td.plp-col-purchase-price,
#product-list-page td.plp-col-selling-price,
#product-list-page td.plp-col-current-stock{
    white-space:nowrap!important;
    text-align:right!important;
}
#product-list-page .dataTables_scrollBody{
    overflow-x:auto!important;
}
@media(max-width:1199px){#product-list-page .product-filter-grid{grid-template-columns:repeat(3,minmax(180px,1fr))}}
@media(max-width:991px){#product-list-page .product-filter-grid{grid-template-columns:repeat(2,minmax(160px,1fr))}}
@media(max-width:575px){#product-list-page .product-filter-grid{grid-template-columns:1fr}#product-list-page .product-page-actions{justify-content:stretch!important}#product-list-page .product-page-actions .btn{width:100%}}
</style>

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('sale.products')
        <small>@lang('lang_v1.manage_products')</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content" id="product-list-page">
<div class="row">
    <div class="col-md-12">
        <div class="product-filter-card">
            <div class="product-filter-title">
                <i class="fa fa-filter"></i>
                <span>@lang('report.filters')</span>
            </div>
            <div class="product-filter-body">
                <div class="product-filter-grid">
                    <div class="product-filter-item">
                        {!! Form::label('product_id', __('lang_v1.products') . ':') !!}
                        {!! Form::select('product_id', $products, null, ['class' => 'form-control select2 product_id', 'style' => 'width:100%', 'id' => 'product_list_filter_product_id', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="product-filter-item">
                        {!! Form::label('category_id', __('product.category') . ':') !!}
                        {!! Form::select('category_id', $categories, null, ['class' => 'category_id form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_category_id', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="product-filter-item">
                        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                        {!! Form::select('sub_category_id', $sub_categories, null, ['class' => 'form-control select2 sub_category_id', 'style' => 'width:100%', 'id' => 'product_list_filter_sub_category_id', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="product-filter-item">
                        {!! Form::label('type', __('product.product_type') . ':') !!}
                        {!! Form::select('type', ['single' => __('lang_v1.single'), 'variable' => __('lang_v1.variable'), 'combo' => __('lang_v1.combo')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_type', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="product-filter-item">
                        {!! Form::label('brand_id', __('product.brand') . ':') !!}
                        {!! Form::select('brand_id', $brands, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_brand_id', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="product-filter-item">
                        {!! Form::label('unit_id', __('product.unit') . ':') !!}
                        {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_unit_id', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="product-filter-item">
                        {!! Form::label('tax_id', __('product.tax') . ':') !!}
                        {!! Form::select('tax_id', $taxes, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_tax_id', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="product-filter-item" id="location_filter">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'location_id', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="product-filter-item">
                        {!! Form::label('active_state', __('business.is_active') . ':') !!}
                        {!! Form::select('active_state', ['active' => __('business.is_active'), 'inactive' => __('lang_v1.inactive')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'active_state', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="product-filter-item">
                        {!! Form::label('semi_finished', __('unit.semi_finished') . ':') !!}
                        {!! Form::select('semi_finished', ['1' => __('messages.yes'), '0' => __('messages.no')], null, ['class' => 'form-control select2 semi_finished', 'style' => 'width:100%', 'id' => 'product_list_filter_semi_finished', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    @if(!empty($pos_module_data))
                        @foreach($pos_module_data as $key => $value)
                            @if(!empty($value['view_path']))
                                <div class="product-filter-item">
                                    @includeIf($value['view_path'], ['view_data' => $value['view_data']])
                                </div>
                            @endif
                        @endforeach
                    @endif
                    <div class="product-filter-item">
                        <div class="product-filter-checks">
                            <label>{!! Form::checkbox('not_for_selling', 1, false, ['class' => 'input-icheck', 'id' => 'not_for_selling']); !!} <strong>@lang('lang_v1.not_for_selling')</strong></label>
                            @if($is_woocommerce)
                                <label>{!! Form::checkbox('woocommerce_enabled', 1, false, ['class' => 'input-icheck', 'id' => 'woocommerce_enabled']); !!} {{ __('lang_v1.woocommerce_enabled') }}</label>
                            @endif
                        </div>
                    </div>
                    <div class="product-filter-item">
                        <button type="button" class="product-filter-reset" id="product_filter_reset"><i class="fa fa-refresh"></i> Reset Filters</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@can('product.view')
    <div class="row">
        <div class="col-md-12">
           <!-- Custom Tabs -->
            <div class="nav-tabs-custom product-tabs-card">
                <ul class="nav nav-tabs">
                    
                    @if((array_key_exists('products_all_products',$pacakge_details) && !empty($pacakge_details['products_all_products'])) || !array_key_exists('products_all_products',$pacakge_details) )
                        <li class="active">
                            <a href="#product_list_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cubes" aria-hidden="true"></i> @lang('lang_v1.all_products')</a>
                        </li>
                    @endif
                    
                    @if((array_key_exists('products_stock_report',$pacakge_details) && !empty($pacakge_details['products_stock_report'])) || !array_key_exists('products_stock_report',$pacakge_details) )
                        @can('stock_report.view')
                        <li>
                            <a href="#product_stock_report" data-toggle="tab" aria-expanded="true"><i class="fa fa-hourglass-half" aria-hidden="true"></i> @lang('report.stock_report')</a>
                        </li>
                        @endcan
                    @endif
                    <li>
                        <a href="#inactive_product_list_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-ban" aria-hidden="true"></i> Inactive Products</a>
                    </li>
                </ul>

                <div class="tab-content">
                    @if((array_key_exists('products_all_products',$pacakge_details) && !empty($pacakge_details['products_all_products'])) || !array_key_exists('products_all_products',$pacakge_details) )
                        <div class="tab-pane active" id="product_list_tab">
                            <div class="product-page-actions" style="width:100%;display:flex;justify-content:flex-end;align-items:center;gap:10px;flex-wrap:wrap;clear:both;">
                                @can('product.create')
                                    <a class="btn btn-primary all-p-btn" href="{{action([\App\Http\Controllers\ProductController::class, 'create'])}}">
                                        <i class="fa fa-plus"></i> @lang('messages.add')
                                    </a>
                                @endcan
                                @if($is_admin)
                                    <a class="btn btn-success all-p-btn" href="{{action([\App\Http\Controllers\ProductController::class, 'downloadExcel'])}}">
                                        <i class="fa fa-download"></i> @lang('lang_v1.download_excel')
                                    </a>
                                @endif
                            </div>
                            @include('product.partials.product_list')
                        </div>
                    @endif
                    
                    @if((array_key_exists('products_stock_report',$pacakge_details) && !empty($pacakge_details['products_stock_report'])) || !array_key_exists('products_stock_report',$pacakge_details) )
                        @can('stock_report.view')
                        <div class="tab-pane" id="product_stock_report">
                            @include('report.partials.stock_report_table')
                        </div>
                        @endcan
                    @endif
                    <div class="tab-pane" id="inactive_product_list_tab">
                        @include('product.partials.inactive_product_list')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
<input type="hidden" id="is_rack_enabled" value="{{$rack_enabled}}">

<div class="modal fade product_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade" id="view_product_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade" id="opening_stock_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@if($is_woocommerce)
    @include('product.partials.toggle_woocommerce_sync_modal')
@endif
@include('product.partials.edit_product_location_modal')

</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready( function(){
            var productListWidthsApplied = false;

            function productListHeaderWidth($header, columnClass) {
                var $column = $header.find('th.' + columnClass).first();
                return $column.length ? Math.round($column.outerWidth()) : 0;
            }

            function applyRequestedProductColumnWidths(api) {
                if (productListWidthsApplied || !api) {
                    return;
                }

                var $header = $(api.table().header());
                var productWidth = productListHeaderWidth($header, 'plp-col-product');
                var purchasePriceWidth = productListHeaderWidth($header, 'plp-col-purchase-price');
                var sellingPriceWidth = productListHeaderWidth($header, 'plp-col-selling-price');
                var currentStockWidth = productListHeaderWidth($header, 'plp-col-current-stock');
                var productTypeWidth = productListHeaderWidth($header, 'plp-col-product-type');
                var categoryWidth = productListHeaderWidth($header, 'plp-col-category');

                if (!productWidth || !currentStockWidth || !productTypeWidth || !categoryWidth) {
                    return;
                }

                var commonPriceWidth = purchasePriceWidth || sellingPriceWidth;
                var page = document.getElementById('product-list-page');

                page.style.setProperty('--plp-product-width', Math.round(productWidth * 1.30) + 'px');
                if (commonPriceWidth) {
                    page.style.setProperty('--plp-price-width', Math.round(commonPriceWidth * 0.50) + 'px');
                }
                page.style.setProperty('--plp-current-stock-width', Math.round(currentStockWidth * 0.70) + 'px');
                page.style.setProperty('--plp-product-type-width', Math.round(productTypeWidth * 0.60) + 'px');
                page.style.setProperty('--plp-category-width', Math.round(categoryWidth * 1.40) + 'px');
                page.classList.add('plp-column-widths-ready');
                productListWidthsApplied = true;

                window.setTimeout(function () {
                    api.columns.adjust();
                    if (typeof inactive_product_table !== 'undefined') {
                        inactive_product_table.columns.adjust();
                    }
                }, 0);
            }

            $(document).on('click', '#product_filter_reset', function(e) {
                e.preventDefault();
                $('#product_list_filter_type, #product_list_filter_category_id, #product_list_filter_sub_category_id, #product_list_filter_product_id, #product_list_filter_semi_finished, #product_list_filter_brand_id, #product_list_filter_unit_id, #product_list_filter_tax_id, #location_id, #active_state, #repair_model_id').val('').trigger('change');
                $('#not_for_selling, #woocommerce_enabled').prop('checked', false).trigger('ifChanged');
                if (typeof product_table !== 'undefined') { product_table.ajax.reload(); }
                if (typeof inactive_product_table !== 'undefined') { inactive_product_table.ajax.reload(); }
                if (typeof stock_report_table !== 'undefined' && $('#product_stock_report').hasClass('active')) { stock_report_table.ajax.reload(); }
            });
            product_table = $('#product_table').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                scrollCollapse: false,
                aaSorting: [[3, 'asc']],
                "ajax": {
                    "url": "/products",
                    "data": function ( d ) {
                        d.type = $('#product_list_filter_type').val();
                        d.category_id = $('#product_list_filter_category_id').val();
                        d.sub_category_id = $('#product_list_filter_sub_category_id').val();
                        d.product_id = $('#product_list_filter_product_id').val();
                        d.semi_finished = $('#product_list_filter_semi_finished').val();
                        d.brand_id = $('#product_list_filter_brand_id').val();
                        d.unit_id = $('#product_list_filter_unit_id').val();
                        d.tax_id = $('#product_list_filter_tax_id').val();
                        d.active_state = $('#active_state').val();
                        d.not_for_selling = $('#not_for_selling').is(':checked');
                        d.location_id = $('#location_id').val();
                        if ($('#repair_model_id').length == 1) {
                            d.repair_model_id = $('#repair_model_id').val();
                        }

                        if ($('#woocommerce_enabled').length == 1 && $('#woocommerce_enabled').is(':checked')) {
                            d.woocommerce_enabled = 1;
                        }

                        d = __datatable_ajax_callback(d);
                    }
                },
                columnDefs: [ {
                    "targets": [0, 1, 2],
                    "orderable": false,
                    "searchable": false
                } ],
                columns: [
                        { data: 'mass_delete'  },
                        { data: 'image', name: 'products.image'  },
                        { data: 'action', name: 'action'},
                        { data: 'product', name: 'products.name', className: 'plp-col-product' },
                        { data: 'product_locations', name: 'product_locations'  },
                        @can('view_purchase_price')
                            { data: 'purchase_price', name: 'max_purchase_price', searchable: false, className: 'plp-col-purchase-price' },
                        @endcan
                        @can('access_default_selling_price')
                            { data: 'selling_price', name: 'max_price', searchable: false, className: 'plp-col-selling-price' },
                        @endcan
                        { data: 'current_stock', searchable: false, className: 'plp-col-current-stock' },
                        { data: 'type', name: 'products.type', className: 'plp-col-product-type' },
                        { data: 'category', name: 'c1.name', className: 'plp-col-category' },
                        { data: 'brand', name: 'brands.name'},
                        { data: 'tax', name: 'tax_rates.name', searchable: false},
                        { data: 'sku', name: 'products.sku'},
                        { data: 'semi_finished', name: 'products.semi_finished'},
                        { data: 'product_custom_field1', name: 'products.product_custom_field1', visible: $('#cf_1').text().length > 0  },
                        { data: 'product_custom_field2', name: 'products.product_custom_field2' , visible: $('#cf_2').text().length > 0},
                        { data: 'product_custom_field3', name: 'products.product_custom_field3', visible: $('#cf_3').text().length > 0},
                        { data: 'product_custom_field4', name: 'products.product_custom_field4', visible: $('#cf_4').text().length > 0 },
                    ],
                    createdRow: function( row, data, dataIndex ) {
                        if($('input#is_rack_enabled').val() == 1){
                            var target_col = 0;
                            @can('product.delete')
                                target_col = 1;
                            @endcan
                            $( row ).find('td:eq('+target_col+') div').prepend('<i style="margin:auto;" class="fa fa-plus-circle text-success cursor-pointer no-print rack-details" title="' + LANG.details + '"></i>&nbsp;&nbsp;');
                        }
                        $( row ).find('td:eq(0)').attr('class', 'selectable_td');
                    },
                    fnDrawCallback: function(oSettings) {
                        __currency_convert_recursively($('#product_table'));
                    },
                    initComplete: function() {
                        applyRequestedProductColumnWidths(this.api());
                    },
            });
            inactive_product_table = $('#inactive_product_table').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                scrollCollapse: false,
                aaSorting: [[1, 'asc']],
                ajax: {
                    url: '/products',
                    data: function (d) {
                        d.type = $('#product_list_filter_type').val();
                        d.category_id = $('#product_list_filter_category_id').val();
                        d.sub_category_id = $('#product_list_filter_sub_category_id').val();
                        d.product_id = $('#product_list_filter_product_id').val();
                        d.semi_finished = $('#product_list_filter_semi_finished').val();
                        d.brand_id = $('#product_list_filter_brand_id').val();
                        d.unit_id = $('#product_list_filter_unit_id').val();
                        d.tax_id = $('#product_list_filter_tax_id').val();
                        d.not_for_selling = $('#not_for_selling').is(':checked');
                        d.location_id = $('#location_id').val();
                        d.active_state = 'inactive';
                        d.tab_mode = 'inactive';
                        if ($('#repair_model_id').length == 1) {
                            d.repair_model_id = $('#repair_model_id').val();
                        }

                        if ($('#woocommerce_enabled').length == 1 && $('#woocommerce_enabled').is(':checked')) {
                            d.woocommerce_enabled = 1;
                        }

                        d = __datatable_ajax_callback(d);
                    }
                },
                columnDefs: [ {
                    targets: [0],
                    orderable: false,
                    searchable: false
                } ],
                columns: [
                    { data: 'image', name: 'products.image'  },
                    { data: 'product', name: 'products.name', className: 'plp-col-product' },
                    { data: 'product_locations', name: 'product_locations'  },
                    @can('view_purchase_price')
                        { data: 'purchase_price', name: 'max_purchase_price', searchable: false, className: 'plp-col-purchase-price' },
                    @endcan
                    @can('access_default_selling_price')
                        { data: 'selling_price', name: 'max_price', searchable: false, className: 'plp-col-selling-price' },
                    @endcan
                    { data: 'current_stock', searchable: false, className: 'plp-col-current-stock' },
                    { data: 'type', name: 'products.type', className: 'plp-col-product-type' },
                    { data: 'category', name: 'c1.name', className: 'plp-col-category' },
                    { data: 'brand', name: 'brands.name'},
                    { data: 'tax', name: 'tax_rates.name', searchable: false},
                    { data: 'sku', name: 'products.sku'},
                    { data: 'semi_finished', name: 'products.semi_finished'},
                    { data: 'product_custom_field1', name: 'products.product_custom_field1', visible: $('#cf_1').text().length > 0  },
                    { data: 'product_custom_field2', name: 'products.product_custom_field2' , visible: $('#cf_2').text().length > 0},
                    { data: 'product_custom_field3', name: 'products.product_custom_field3', visible: $('#cf_3').text().length > 0},
                    { data: 'product_custom_field4', name: 'products.product_custom_field4', visible: $('#cf_4').text().length > 0 },
                ],
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#inactive_product_table'));
                },
            });
            // Array to track the ids of the details displayed rows
            var detailRows = [];

            $('#product_table tbody').on( 'click', 'tr i.rack-details', function () {
                var i = $(this);
                var tr = $(this).closest('tr');
                var row = product_table.row( tr );
                var idx = $.inArray( tr.attr('id'), detailRows );

                if ( row.child.isShown() ) {
                    i.addClass( 'fa-plus-circle text-success' );
                    i.removeClass( 'fa-minus-circle text-danger' );

                    row.child.hide();
         
                    // Remove from the 'open' array
                    detailRows.splice( idx, 1 );
                } else {
                    i.removeClass( 'fa-plus-circle text-success' );
                    i.addClass( 'fa-minus-circle text-danger' );

                    row.child( get_product_details( row.data() ) ).show();
         
                    // Add to the 'open' array
                    if ( idx === -1 ) {
                        detailRows.push( tr.attr('id') );
                    }
                }
            });

            $('#opening_stock_modal').on('hidden.bs.modal', function(e) {
                product_table.ajax.reload();
            });

            $('table#product_table tbody').on('click', 'a.delete-product', function(e){
                e.preventDefault();
                swal({
                  title: LANG.sure,
                  icon: "warning",
                  buttons: true,
                  dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            success: function(result){
                                if(result.success == true){
                                    toastr.success(result.msg);
                                    product_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', '#delete-selected', function(e){
                e.preventDefault();
                var selected_rows = getSelectedRows();
                
                if(selected_rows.length > 0){
                    $('input#selected_rows').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $('form#mass_delete_form').submit();
                        }
                    });
                } else{
                    $('input#selected_rows').val('');
                    swal('@lang("lang_v1.no_row_selected")');
                }    
            });

            $(document).on('click', '#deactivate-selected', function(e){
                e.preventDefault();
                var selected_rows = getSelectedRows();
                
                if(selected_rows.length > 0){
                    $('input#selected_products').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            var form = $('form#mass_deactivate_form')

                            var data = form.serialize();
                                $.ajax({
                                    method: form.attr('method'),
                                    url: form.attr('action'),
                                    dataType: 'json',
                                    data: data,
                                    success: function(result) {
                                        if (result.success == true) {
                                            toastr.success(result.msg);
                                            product_table.ajax.reload();
                                            inactive_product_table.ajax.reload();
                                            form
                                            .find('#selected_products')
                                            .val('');
                                        } else {
                                            toastr.error(result.msg);
                                        }
                                    },
                                });
                        }
                    });
                } else{
                    $('input#selected_products').val('');
                    swal('@lang("lang_v1.no_row_selected")');
                }    
            })

            $(document).on('click', '#edit-selected', function(e){
                e.preventDefault();
                var selected_rows = getSelectedRows();
                
                if(selected_rows.length > 0){
                    $('input#selected_products_for_edit').val(selected_rows);
                    $('form#bulk_edit_form').submit();
                } else{
                    $('input#selected_products').val('');
                    swal('@lang("lang_v1.no_row_selected")');
                }    
            })

            $('table#product_table tbody').on('click', 'a.activate-product', function(e){
                e.preventDefault();
                var href = $(this).attr('href');
                $.ajax({
                    method: "get",
                    url: href,
                    dataType: "json",
                    success: function(result){
                        if(result.success == true){
                            toastr.success(result.msg);
                            product_table.ajax.reload();
                            inactive_product_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('change', '#product_list_filter_product_id,#product_list_filter_semi_finished,#product_list_filter_type, #product_list_filter_category_id,#product_list_filter_sub_category_id, #product_list_filter_brand_id, #product_list_filter_unit_id, #product_list_filter_tax_id, #location_id, #active_state, #repair_model_id', 
                function() {
                    if ($("#product_list_tab").hasClass('active')) {
                        product_table.ajax.reload();
                    }

                    if ($("#product_stock_report").hasClass('active')) {
                        stock_report_table.ajax.reload();
                    }

                    if ($("#inactive_product_list_tab").hasClass('active')) {
                        inactive_product_table.ajax.reload();
                    }
                    
                    if($('#product_list_filter_product_id').val() !== '' && $('#product_list_filter_product_id').val() !== undefined){
                      $('.product').text($('#product_list_filter_product_id :selected').text());
                    }else{
                      $('.product').text('All');
                    }
                    if($('#product_list_filter_category_id').val() !== '' && $('#product_list_filter_category_id').val() !== undefined){
                      $('.category').text($('#product_list_filter_category_id :selected').text());
                    }else{
                      $('.category').text('All');
                    }
                    if($('#product_list_filter_sub_category_id').val() !== '' && $('#product_list_filter_sub_category_id').val() !== undefined){
                      $('.sub_category').text($('#product_list_filter_sub_category_id :selected').text());
                    }else{
                      $('.sub_category').text('All');
                    }
            });

            $(document).on('ifChanged', '#not_for_selling, #woocommerce_enabled', function(){
                if ($("#product_list_tab").hasClass('active')) {
                    product_table.ajax.reload();
                }

                if ($("#product_stock_report").hasClass('active')) {
                    stock_report_table.ajax.reload();
                }

                if ($("#inactive_product_list_tab").hasClass('active')) {
                    inactive_product_table.ajax.reload();
                }
            });

            $('#product_location').select2({dropdownParent: $('#product_location').closest('.modal')});

            @if($is_woocommerce)
                $(document).on('click', '.toggle_woocomerce_sync', function(e){
                    e.preventDefault();
                    var selected_rows = getSelectedRows();
                    if(selected_rows.length > 0){
                        $('#woocommerce_sync_modal').modal('show');
                        $("input#woocommerce_products_sync").val(selected_rows);
                    } else{
                        $('input#selected_products').val('');
                        swal('@lang("lang_v1.no_row_selected")');
                    }    
                });

                $(document).on('submit', 'form#toggle_woocommerce_sync_form', function(e){
                    e.preventDefault();
                    var url = $('form#toggle_woocommerce_sync_form').attr('action');
                    var method = $('form#toggle_woocommerce_sync_form').attr('method');
                    var data = $('form#toggle_woocommerce_sync_form').serialize();
                    var ladda = Ladda.create(document.querySelector('.ladda-button'));
                    ladda.start();
                    $.ajax({
                        method: method,
                        dataType: "json",
                        url: url,
                        data:data,
                        success: function(result){
                            ladda.stop();
                            if (result.success) {
                                $("input#woocommerce_products_sync").val('');
                                $('#woocommerce_sync_modal').modal('hide');
                                toastr.success(result.msg);
                                product_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                });
            @endif
        });
        
        $('.category_id, .sub_category_id').change(function(){
          var cat = $('#product_list_filter_category_id').val();
          var sub_cat = $('#product_list_filter_sub_category_id').val();
          $.ajax({
            method: 'POST',
            url: '/products/get_sub_categories',
            dataType: 'html',
            data: { cat_id: cat },
            success: function(result) {
                console.log(result);
              if (result) {
                $('#product_list_filter_sub_category_id').html(result);
              }
            },
          });
          $.ajax({
            method: 'POST',
            url: '/products/get_product_category_wise',
            dataType: 'html',
            data: { cat_id: cat , sub_cat_id: sub_cat },
            success: function(result) {
              if (result) {
                $('#product_list_filter_product_id').html(result);
              }
            },
          });
        });

        $(document).on('shown.bs.modal', 'div.view_product_modal, div.view_modal, #view_product_modal', 
            function(){
                var div = jQuery('#view_product_stock_details');
            if (div.length) {
                $.ajax({
                    url: "{{action([\App\Http\Controllers\ReportController::class, 'getStockReport'])}}"  + '?for=view_product&product_id=' + div.data('product_id'),
                    dataType: 'html',
                    success: function(result) {
                        jQuery('#view_product_stock_details').html(result);
                        __currency_convert_recursively(div);
                    },
                });
            }
            __currency_convert_recursively($(this));
        });
        var data_table_initailized = false;
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            window.setTimeout(function () {
                if ($.fn.dataTable) {
                    $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
                }
            }, 0);

            if ($(e.target).attr('href') == '#product_stock_report') {
                if (!data_table_initailized) {
                    var stock_report_cols = [
                        { data: 'sku', name: 'variations.sub_sku' },
                        { data: 'product', name: 'p.name' },
                        { data: 'variation', name: 'variation' },
                        { data: 'category_name', name: 'c.name' },
                        { data: 'location_name', name: 'l.name' },
                        { data: 'unit_price', name: 'variations.sell_price_inc_tax' },
                        { data: 'stock', name: 'stock', searchable: false },
                    ];
                    if ($('th.stock_price').length) {
                            stock_report_cols.push({ data: 'stock_price', name: 'stock_price', searchable: false });
                            stock_report_cols.push({ data: 'stock_value_by_sale_price', name: 'stock_value_by_sale_price', searchable: false, orderable: false });
                            stock_report_cols.push({ data: 'potential_profit', name: 'potential_profit', searchable: false, orderable: false });
                        }
                        
                    
                        // stock_report_cols.push({ data: 'total_purchased', name: 'total_purchased', searchable: false });
                        stock_report_cols.push({ data: 'total_sold', name: 'total_sold', searchable: false });
                        stock_report_cols.push({ data: 'total_transfered', name: 'total_transfered', searchable: false });
                        stock_report_cols.push({ data: 'total_adjusted', name: 'total_adjusted', searchable: false });
                    
                        if ($('th.current_stock_mfg').length) {
                            stock_report_cols.push({ data: 'total_mfg_stock', name: 'total_mfg_stock', searchable: false });
                        }
                    stock_report_table = $('#stock_report_table').DataTable({
                            processing: true,
                            serverSide: true,
                            scrollY: "75vh",
                            scrollX: true,
                            scrollCollapse: false,
                            fixedHeader: false,
                            ajax: {
                                url: '/reports/stock-report',
                                data: function(d) {
                                    d.type = $('#product_list_filter_type').val();
                                    d.product_id = $('#product_list_filter_product_id').val();
                                    d.location_id = $('#location_id').val();
                                    d.category_id = $('#product_list_filter_category_id').val();
                                    d.sub_category_id = $('#product_list_filter_sub_category_id').val();
                                    d.brand_id = $('#product_list_filter_brand_id').val();
                                    d.unit_id = $('#product_list_filter_unit_id').val();
                                    d.tax_id = $('#product_list_filter_tax_id').val();
                                    d.store_id = $('#store_id').val();
                                    d.active_state = $('#active_state').val();
                                    d.only_mfg_products = $('#only_mfg_products').length && $('#only_mfg_products').is(':checked') ? 1 : 0;
                                    
                                    
                                },
                            },
                            columns: stock_report_cols, 
                            fnDrawCallback: function(oSettings) {
                                __currency_convert_recursively($('#stock_report_table'));
                            },
                            "footerCallback": function ( row, data, start, end, display ) {
                                var footer_total_stock = 0;
                                var footer_total_sold = 0;
                                var footer_total_transfered = 0;
                                var total_adjusted = 0;
                                var total_stock_price = 0;
                                var footer_stock_value_by_sale_price = 0;
                                var total_potential_profit = 0;
                                var footer_total_mfg_stock = 0;
                                for (var r in data){
                                    footer_total_stock += $(data[r].stock).data('orig-value') ? 
                                    parseFloat($(data[r].stock).data('orig-value')) : 0;
                    
                                    footer_total_sold += $(data[r].total_sold).data('orig-value') ? 
                                    parseFloat($(data[r].total_sold).data('orig-value')) : 0;
                    
                                    footer_total_transfered += $(data[r].total_transfered).data('orig-value') ? 
                                    parseFloat($(data[r].total_transfered).data('orig-value')) : 0;
                    
                                    total_adjusted += $(data[r].total_adjusted).data('orig-value') ? 
                                    parseFloat($(data[r].total_adjusted).data('orig-value')) : 0;
                    
                                    total_stock_price += $(data[r].stock_price).data('orig-value') ? 
                                    parseFloat($(data[r].stock_price).data('orig-value')) : 0;
                    
                                    footer_stock_value_by_sale_price += $(data[r].stock_value_by_sale_price).data('orig-value') ? 
                                    parseFloat($(data[r].stock_value_by_sale_price).data('orig-value')) : 0;
                    
                                    total_potential_profit += $(data[r].potential_profit).data('orig-value') ? 
                                    parseFloat($(data[r].potential_profit).data('orig-value')) : 0;
                    
                                    footer_total_mfg_stock += $(data[r].total_mfg_stock).data('orig-value') ? 
                                    parseFloat($(data[r].total_mfg_stock).data('orig-value')) : 0;
                                }
                    
                                $('.footer_total_stock').html(__currency_trans_from_en(footer_total_stock, false));
                                $('.footer_total_stock_price').html(__currency_trans_from_en(total_stock_price));
                                $('.footer_total_sold').html(__currency_trans_from_en(footer_total_sold, false));
                                $('.footer_total_transfered').html(__currency_trans_from_en(footer_total_transfered, false));
                                $('.footer_total_adjusted').html(__currency_trans_from_en(total_adjusted, false));
                                $('.footer_stock_value_by_sale_price').html(__currency_trans_from_en(footer_stock_value_by_sale_price));
                                $('.footer_potential_profit').html(__currency_trans_from_en(total_potential_profit));
                                if ($('th.current_stock_mfg').length) {
                                    $('.footer_total_mfg_stock').html(__currency_trans_from_en(footer_total_mfg_stock, false));
                                }
                            },
                        });
                    data_table_initailized = true;
                } else {
                    stock_report_table.ajax.reload();
                }
            } else {
                product_table.ajax.reload();
            }
        });

        $(document).on('click', '.update_product_location', function(e){
            e.preventDefault();
            var selected_rows = getSelectedRows();
            
            if(selected_rows.length > 0){
                $('input#selected_products').val(selected_rows);
                var type = $(this).data('type');
                var modal = $('#edit_product_location_modal');
                if(type == 'add') {
                    modal.find('.remove_from_location_title').addClass('hide');
                    modal.find('.add_to_location_title').removeClass('hide');
                } else if(type == 'remove') {
                    modal.find('.add_to_location_title').addClass('hide');
                    modal.find('.remove_from_location_title').removeClass('hide');
                }

                modal.modal('show');
                modal.find('#product_location').select2({ dropdownParent: modal });
                modal.find('#product_location').val('').change();
                modal.find('#update_type').val(type);
                modal.find('#products_to_update_location').val(selected_rows);
            } else{
                $('input#selected_products').val('');
                swal('@lang("lang_v1.no_row_selected")');
            }    
        });

        function getSelectedRows() {
            var selected_rows = [];
            var i = 0;
            $('.row-select:checked').each(function() {
                selected_rows[i++] = $(this).val();
            });

            return selected_rows;
        }

    $(document).on('submit', 'form#edit_product_location_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();

        $.ajax({
            method: $(this).attr('method'),
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            beforeSend: function(xhr) {
                __disable_submit_button(form.find('button[type="submit"]'));
            },
            success: function(result) {
                if (result.success == true) {
                    $('div#edit_product_location_modal').modal('hide');
                    toastr.success(result.msg);
                    product_table.ajax.reload();
                    $('form#edit_product_location_form')
                    .find('button[type="submit"]')
                    .attr('disabled', false);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    
    $(document).on('submit', 'form#disable_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();
        console.log($(this).attr('action'));
        
        $.ajax({
            method: $(this).attr('method'),
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            beforeSend: function(xhr) {
                __disable_submit_button(form.find('button[type="submit"]'));
            },
            success: function(result) {
                if (result.success == true) {
                    toastr.success(result.msg);
                    $('form#disable_form')
                    .find('button[type="submit"]')
                    .attr('disabled', false);
                    
                    $('.modal').modal('hide');
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    </script>
@endsection
