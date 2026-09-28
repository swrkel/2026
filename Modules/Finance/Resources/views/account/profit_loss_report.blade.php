@extends('layouts.app')

@section('title', __('lang_v1.profit_loss_report'))

@section('css')
<style>
    /* IS1767: keep the Trading Profit tabs below the filter grid at full width. */
    #trading_profit_report,
    #trading_profit_report .trading-profit-tabs-card,
    #trading_profit_report .trading-profit-tabs-scroll,
    #trading_profit_report .tab-content {
        clear: both !important;
        float: none !important;
        position: relative !important;
        left: auto !important;
        right: auto !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    #trading_profit_report .trading-profit-tabs-card {
        display: block !important;
        overflow: visible !important;
        margin-top: 12px !important;
    }

    #trading_profit_report .trading-profit-tabs-scroll {
        display: block !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        padding: 0 2px !important;
    }

    #trading_profit_report #trading_profit_tabs {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        align-items: flex-end !important;
        align-content: flex-start !important;
        clear: both !important;
        float: none !important;
        position: static !important;
        inset: auto !important;
        transform: none !important;
        width: 100% !important;
        min-width: 100% !important;
        max-width: none !important;
        height: auto !important;
        min-height: 0 !important;
        overflow: visible !important;
        margin: 0 0 10px !important;
    }

    #trading_profit_report #trading_profit_tabs > li {
        display: inline-flex !important;
        flex: 0 0 auto !important;
        float: none !important;
        position: static !important;
        width: auto !important;
        height: auto !important;
        min-width: 0 !important;
        max-width: none !important;
    }

    #trading_profit_report #trading_profit_tabs > li > a {
        display: inline-flex !important;
        position: relative !important;
        float: none !important;
        width: auto !important;
        min-width: max-content !important;
        max-width: none !important;
        height: auto !important;
        text-indent: 0 !important;
        writing-mode: horizontal-tb !important;
        white-space: nowrap !important;
        overflow: visible !important;
        visibility: visible !important;
        opacity: 1 !important;
        transform: none !important;
    }

    #trading_profit_report .tab-content {
        display: block !important;
        overflow: visible !important;
        min-height: 220px;
    }

    #trading_profit_report .tab-pane,
    #trading_profit_report .table-responsive,
    #trading_profit_report .dataTables_wrapper {
        width: 100% !important;
        max-width: 100% !important;
    }
</style>
@endsection

@section('content')
<div class="page-title-area no-print">
    <div class="row align-items-center">
        <div class="col-sm-8">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Profit &amp; Loss Report on Trading</h4>
                <ul class="breadcrumbs pull-left" style="margin-top:15px">
                    <li><a href="#">Finance Module</a></li>
                    <li><span>Trading Profit</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content" id="trading_profit_report">
    @component('components.filters', ['title' => __('finance::report.filters')])
        {{-- The row/clearfix is required: Bootstrap col-md-* elements are floated.
             Without this container the tab card is squeezed into the narrow space
             beside the filters and appears as vertical coloured strips. --}}
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="trading_profit_location">@lang('finance::lang_v1.business_location'):</label>
                    <select id="trading_profit_location" class="form-control select2" style="width:100%">
                        <option value="all">@lang('finance::report.all')</option>
                        @foreach($business_locations as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="trading_profit_from">@lang('finance::report.from'):</label>
                    <input type="date" id="trading_profit_from" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="trading_profit_to">@lang('finance::report.to'):</label>
                    <input type="date" id="trading_profit_to" class="form-control" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" id="apply_trading_profit_filters" class="btn btn-primary btn-block">
                        <i class="fa fa-search"></i> @lang('finance::report.apply_filters')
                    </button>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    @endcomponent

    <div class="nav-tabs-custom trading-profit-tabs-card">
        <div class="trading-profit-tabs-scroll">
        <ul class="nav nav-tabs categorised_reports" id="trading_profit_tabs">
            <li class="active"><a href="#profit_by_products" data-toggle="tab">@lang('lang_v1.profit_by_products')</a></li>
            <li><a href="#profit_by_categories" data-toggle="tab">@lang('lang_v1.profit_by_categories')</a></li>
            <li><a href="#profit_by_sub_categories" data-toggle="tab">@lang('lang_v1.profit_by_sub_categories')</a></li>
            <li><a href="#profit_by_brands" data-toggle="tab">@lang('lang_v1.profit_by_brands')</a></li>
            <li><a href="#profit_by_locations" data-toggle="tab">@lang('lang_v1.profit_by_locations')</a></li>
            <li><a href="#profit_by_invoice" data-toggle="tab">@lang('lang_v1.profit_by_invoice')</a></li>
            <li><a href="#profit_by_date" data-toggle="tab">@lang('lang_v1.profit_by_date')</a></li>
            <li><a href="#profit_by_customer" data-toggle="tab">@lang('lang_v1.profit_by_customer')</a></li>
            <li><a href="#profit_by_day" data-toggle="tab">@lang('lang_v1.profit_by_day')</a></li>
        </ul>
        </div>
        <div class="tab-content">
            @php
                $tradingTabs = [
                    'profit_by_products' => ['profit_by_products_table', __('sale.product'), 'product', 'product'],
                    'profit_by_categories' => ['profit_by_categories_table', __('product.category'), 'category', 'category'],
                    'profit_by_sub_categories' => ['profit_by_sub_categories_table', __('product.sub_category'), 'sub-category', 'category'],
                    'profit_by_brands' => ['profit_by_brands_table', __('product.brand'), 'brand', 'brand'],
                    'profit_by_locations' => ['profit_by_locations_table', __('sale.location'), 'location', 'location'],
                    'profit_by_invoice' => ['profit_by_invoice_table', __('sale.invoice_no'), 'invoice', 'invoice_no'],
                    'profit_by_date' => ['profit_by_date_table', __('lang_v1.date'), 'date', 'transaction_date'],
                    'profit_by_customer' => ['profit_by_customer_table', __('product.customer'), 'customer', 'customer'],
                    'profit_by_day' => ['profit_by_day_table', __('lang_v1.days'), 'day', 'transaction_date'],
                ];
            @endphp
            @foreach($tradingTabs as $paneId => $tab)
                <div class="tab-pane {{ $loop->first ? 'active' : '' }}" id="{{ $paneId }}">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="{{ $tab[0] }}" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ $tab[1] }}</th>
                                    <th>@lang('lang_v1.total_sales')</th>
                                    <th>@lang('lang_v1.gross_profit')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr class="bg-gray font-17">
                                    <th>@lang('sale.total'):</th>
                                    <th><span class="display_currency footer_total_sales" data-currency_symbol="true">0</span></th>
                                    <th><span class="display_currency footer_total" data-currency_symbol="true">0</span></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(function () {
    var $report = $('#trading_profit_report');
    var $tabs = $report.find('#trading_profit_tabs');
    var endpointTemplate = @json(route('finance.trading-profit.data', ['by' => '__GROUP__']));
    var tables = {};
    var configs = {
        '#profit_by_products': {table: '#profit_by_products_table', endpoint: 'product', field: 'product'},
        '#profit_by_categories': {table: '#profit_by_categories_table', endpoint: 'category', field: 'category'},
        '#profit_by_sub_categories': {table: '#profit_by_sub_categories_table', endpoint: 'sub-category', field: 'category'},
        '#profit_by_brands': {table: '#profit_by_brands_table', endpoint: 'brand', field: 'brand'},
        '#profit_by_locations': {table: '#profit_by_locations_table', endpoint: 'location', field: 'location'},
        '#profit_by_invoice': {table: '#profit_by_invoice_table', endpoint: 'invoice', field: 'invoice_no', invoice: true},
        '#profit_by_date': {table: '#profit_by_date_table', endpoint: 'date', field: 'transaction_date'},
        '#profit_by_customer': {table: '#profit_by_customer_table', endpoint: 'customer', field: 'customer'},
        '#profit_by_day': {table: '#profit_by_day_table', endpoint: 'day', field: 'transaction_date'}
    };

    function initialise(pane) {
        if (tables[pane]) {
            tables[pane].ajax.reload(null, false);
            return;
        }

        var cfg = configs[pane];
        var $table = cfg ? $report.find(cfg.table) : $();
        if (!cfg || !$table.length || !$.fn.DataTable) {
            return;
        }

        var columns = [
            {data: cfg.field, name: cfg.field, defaultContent: ''},
            {data: cfg.invoice ? 'final_total' : 'total_sales', name: cfg.invoice ? 'final_total' : 'total_sales', searchable: false},
            {data: 'gross_profit', name: 'gross_profit', searchable: false}
        ];

        tables[pane] = $table.DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            searchDelay: 350,
            pageLength: 25,
            order: [[0, 'asc']],
            ajax: {
                url: endpointTemplate.replace('__GROUP__', cfg.endpoint),
                dataSrc: function (json) {
                    if (json && json.error) {
                        toastr.error(json.error);
                    }
                    return json && Array.isArray(json.data) ? json.data : [];
                },
                data: function (d) {
                    d.start_date = $report.find('#trading_profit_from').val();
                    d.end_date = $report.find('#trading_profit_to').val();
                    d.location_id = $report.find('#trading_profit_location').val();
                },
                error: function (xhr) {
                    var message = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message))
                        ? (xhr.responseJSON.error || xhr.responseJSON.message)
                        : @json(__('messages.something_went_wrong'));
                    toastr.error(message);
                }
            },
            columns: columns,
            drawCallback: function () {
                var api = this.api();
                var sales = 0;
                var profit = 0;
                api.rows({page: 'current'}).data().each(function (row) {
                    sales += parseFloat(cfg.invoice ? row.final_total : $('<div>').html(row.total_sales || 0).text()) || 0;
                    profit += parseFloat($('<div>').html(row.gross_profit || 0).text()) || 0;
                });
                $table.find('.footer_total_sales').attr('data-orig-value', sales).text(sales);
                $table.find('.footer_total').attr('data-orig-value', profit).text(profit);
                if (typeof window.__currency_convert_recursively === 'function') {
                    __currency_convert_recursively($table);
                }
                api.columns.adjust();
            }
        });
    }

    initialise('#profit_by_products');

    $tabs.on('click.tradingProfit', 'a[data-toggle="tab"]', function (event) {
        event.preventDefault();
        $(this).tab('show');
    });

    $tabs.on('shown.bs.tab.tradingProfit', 'a[data-toggle="tab"]', function (event) {
        var pane = $(event.target).attr('href');
        initialise(pane);
        if (tables[pane]) {
            setTimeout(function () {
                tables[pane].columns.adjust().draw(false);
            }, 0);
        }
    });

    $report.find('#apply_trading_profit_filters').on('click.tradingProfit', function () {
        Object.keys(tables).forEach(function (pane) {
            tables[pane].ajax.reload(null, false);
        });
    });

    $report.find('#trading_profit_location').on('change.tradingProfit', function () {
        Object.keys(tables).forEach(function (pane) {
            tables[pane].ajax.reload(null, false);
        });
    });
});
</script>
@endsection
