@extends('layouts.app')
@section('title', __('report.product_report'))
<style>
    .dataTables_scrollBody thead {
        display: none;
    }
</style>
@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        @can('stock_report.view')
                            <li class="active">
                                <a href="#stock_report" class="stock_report" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('report.stock_report')</strong>
                                </a>
                            </li>
                        @endcan
                        @can('stock_report.view')
                            <li class="">
                                <a href="#stock_summary" class="stock_summary" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('report.stock_summary')</strong>
                                </a>
                            </li>
                        @endcan
                        @can('stock_adjustment_report.view')
                            <li class="">
                                <a href="#stock_adjustment_report" class="stock_adjustment_report" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('report.stock_adjustment_report')</strong>
                                </a>
                            </li>
                        @endcan
                        @can('item_report.view')
                            <li class="">
                                <a href="#items_report" class="items_report" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('report.items_report')</strong>
                                </a>
                            </li>
                        @endcan
                        @can('product_purchase_report.view')
                            <li class="">
                                <a href="#product_purchase_report" class="product_purchase_report" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('report.product_purchase_report')</strong>
                                </a>
                            </li>
                        @endcan
                        @can('product_sell_report.view')
                            <li class="">
                                <a href="#product_sell_report" class="product_sell_report" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('report.product_sell_report')</strong>
                                </a>
                            </li>
                        @endcan
                        @can('product_transaction_report.view')
                            <li class="">
                                <a href="#product_transaction_report" class="product_transaction_report" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i>
                                    <strong>@lang('report.product_transaction_report')</strong>
                                </a>
                            </li>
                        @endcan
                        <li class="">
                            <a href="#product_loss_excess_report" class="product_loss_excess_report" data-toggle="tab">
                                <i class="fa fa-file-text-o"></i>
                                <strong>@lang('report.product_loss_excess_report')</strong>
                            </a>
                        </li>
                        @can('product_transaction_report.view')
                            <li class="">
                                <a href="#prod_trans_report_store_wise" class="prod_trans_report_store_wise" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i>
                                    <strong>@lang('report.prod_trans_report_store_wise')</strong>
                                </a>
                            </li>
                        @endcan
                    </ul>
                    <div class="tab-content">
                        @can('stock_report.view')
                            <div class="tab-pane active" id="stock_report">
                                @include('report.stock_report_tab')
                            </div>
                        @endcan
                        @can('stock_report.view')
                            <div class="tab-pane" id="stock_summary">
                                @include('report.stock_summary_tab')
                            </div>
                        @endcan
                        @can('stock_adjustment_report.view')
                            <div class="tab-pane" id="stock_adjustment_report">
                                @include('report.stock_adjustment_report')
                            </div>
                        @endcan
                        @can('item_report.view')
                            <div class="tab-pane" id="items_report">
                                @include('report.items_report')
                            </div>
                        @endcan
                        @can('product_purchase_report.view')
                            <div class="tab-pane" id="product_purchase_report">
                                @include('report.product_purchase_report')
                            </div>
                        @endcan
                        @can('product_sell_report.view')
                            <div class="tab-pane" id="product_sell_report">
                                @include('report.product_sell_report')
                            </div>
                        @endcan
                        @can('product_transaction_report.view')
                            <div class="tab-pane" id="product_transaction_report">
                                @include('report.product_transaction_report')
                            </div>
                        @endcan
                        <div class="tab-pane" id="product_loss_excess_report">
                            @include('report.product_loss_excess_report')
                        </div>
                        @can('product_transaction_report.view')
                            <div class="tab-pane" id="prod_trans_report_store_wise">
                                @include('report.prod_trans_report_store_wise')
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('javascript')
    <script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script>
        $('#stock_summary_date_range').daterangepicker();
        $('#stock_report_filter_form #location_id').change(function() {
            let check_store_not = null;
            option_value = "{{ __('lang_v1.all') }}";
            if (!$(this).val()) {
                $('#store_id').empty();
                $('#store_id').append(`<option value= "">` + option_value + `</option>`);
                return;
            }
            $.ajax({
                method: 'get',
                url: '/stock-transfer/get_transfer_store_id/' + $('#location_id').val(),
                data: {
                    check_store_not: check_store_not
                },
                success: function(result) {

                    $('#store_id').empty();
                    $('#store_id').append(`<option value= "">` + option_value + `</option>`);
                    $.each(result, function(i, location) {
                        $('#store_id').append(`<option value= "` + location.id + `">` + location
                            .name + `</option>`);
                    });
                    $("#store_id").change();
                },
            });

            stock_report_table.ajax.reload();
            stock_expiry_report_table.ajax.reload();
        });

        $('#stock_summary_filter_form #summary_location_id').change(function() {
            let check_store_not = null;
            option_value = "{{ __('lang_v1.all') }}";
            if (!$(this).val()) {
                $('#summary_store_id').empty();
                $('#summary_store_id').append(`<option value= "">` + option_value + `</option>`);
                return;
            }
            $.ajax({
                method: 'get',
                url: '/stock-transfer/get_transfer_store_id/' + $('#summary_location_id').val(),
                data: {
                    check_store_not: check_store_not
                },
                success: function(result) {

                    $('#summary_store_id').empty();
                    $('#summary_store_id').append(`<option value= "">` + option_value + `</option>`);
                    $.each(result, function(i, location) {
                        $('#summary_store_id').append(`<option value= "` + location.id + `">` +
                            location.name + `</option>`);
                    });
                    $("#summary_store_id").change();
                },
            });

            stock_summary_table.ajax.reload();
        });

        $(document).ready(function() {
            summaryUpdate();
            stocksummaryUpdate();
            get_summary_value();
            if ($('#ir_location_id > option').length <= 2) {
                console.log($("#ir_location_id option:last").val());
                $("#ir_location_id").val($("#ir_location_id option:last").val()).trigger('change');
            }

            if ($('#sell_location_id > option').length <= 2) {
                console.log($("#sell_location_id option:last").val());
                $("#sell_location_id").val($("#sell_location_id option:last").val()).trigger('change');
            }
        })

        function summaryUpdate() {
            var product_id = $('#product_list_filter_product_id').val();
            var category_id = $('#stock_report_filter_form #category_id').val();
            var sub_category_id = $('#product_list_filter_sub_category_id').val();
            var location_id = $('#stock_report_filter_form #location_id').val();
            var brand_id = $('#stock_report_filter_form #brand').val();
            var unit_id = $('#stock_report_filter_form #unit').val();
            var store_id = $('#stock_report_filter_form #store_id').val();
            let start = null;
            let end = null;
            var drp = $('#stock_report_date_range').data('daterangepicker');

            var stock_start = '';
            var stock_end = '';

            if (drp) {
                stock_start = drp.startDate.format('YYYY-MM-DD');
                stock_end = drp.endDate.format('YYYY-MM-DD');
            }

            var data = {
                product_id: product_id,
                category_id: category_id,
                sub_category_id: sub_category_id,
                location_id: location_id,
                brand_id: brand_id,
                unit_id: unit_id,
                store_id: store_id,
                start_date: stock_start,
                end_date: stock_end
            };

            var loader = __fa_awesome();
            $('#stock_report').find('.opening_qty').html(loader);
            $('#stock_report').find('.opening_amount').html(loader);
            $('#stock_report').find('.purchase_qty').html(loader);
            $('#stock_report').find('.purchase_amount').html(loader);
            $('#stock_report').find('.sold_qty').html(loader);
            $('#stock_report').find('.sold_amount').html(loader);
            $('#stock_report').find('.balance_qty').html(loader);
            $('#stock_report').find('.balance_amount').html(loader);

            $.ajax({
                method: 'GET',
                url: '/reports/get-product-transaction-summary',
                dataType: 'json',
                data: data,
                success: function(data) {
                    $('#stock_report').find('.sold_qty').html(__number_f(data.sold_qty));
                    $('#stock_report').find('.purchase_qty').html(__number_f(data.purchase_qty));
                    $('#stock_report').find('.opening_qty').html(__number_f(data.opening_qty));
                    $('#stock_report').find('.balance_qty').html(__number_f(data.balance_qty));
                    $('#stock_report').find('.sold_amount').html(__currency_trans_from_en(data.sold_amount));
                    $('#stock_report').find('.purchase_amount').html(__currency_trans_from_en(data
                        .purchase_amount));
                    $('#stock_report').find('.opening_amount').html(__currency_trans_from_en(data
                        .opening_amount));
                    $('#stock_report').find('.balance_amount').html(__currency_trans_from_en(data
                        .balance_amount));
                },
            });
        }

        function stocksummaryUpdate() {
            var product_id = $('#summary_product_list_filter_product_id').val();
            var category_id = $('#stock_summary_filter_form #summary_product_list_filter_category_id').val();
            var sub_category_id = $('#summary_product_list_filter_sub_category_id').val();
            var location_id = $('#stock_summary_filter_form #summary_location_id').val();
            var brand_id = $('#stock_summary_filter_form #summary_product_list_filter_brand_id').val();
            var unit_id = $('#stock_summary_filter_form #summary_product_list_filter_unit_id').val();
            var store_id = $('#stock_summary_filter_form #summary_store_id').val();
            let start = null;
            let end = null;
            let stock_start = '';
            let stock_end = '';

            if ($('#stock_summary_date_range').length) {
                let drp = $('#stock_summary_date_range').data('daterangepicker');
                if (drp) {
                    stock_start = drp.startDate.format('YYYY-MM-DD');
                    stock_end = drp.endDate.format('YYYY-MM-DD');
                }
            }

            var data = {
                product_id: product_id,
                category_id: category_id,
                sub_category_id: sub_category_id,
                location_id: location_id,
                brand_id: brand_id,
                unit_id: unit_id,
                store_id: store_id,
                start_date: stock_start,
                end_date: stock_end
            };

            var loader = __fa_awesome();
            $('#stock_summary').find('.opening_qty').html(loader);
            $('#stock_summary').find('.opening_amount').html(loader);
            $('#stock_summary').find('.purchase_qty').html(loader);
            $('#stock_summary').find('.purchase_amount').html(loader);
            $('#stock_summary').find('.sold_qty').html(loader);
            $('#stock_summary').find('.sold_amount').html(loader);
            $('#stock_summary').find('.balance_qty').html(loader);
            $('#stock_summary').find('.balance_amount').html(loader);

            $.ajax({
                method: 'GET',
                url: '/reports/get-product-transaction-summary',
                dataType: 'json',
                data: data,
                success: function(data) {
                    $('#stock_summary').find('.sold_qty').html(__number_f(data.sold_qty));
                    $('#stock_summary').find('.purchase_qty').html(__number_f(data.purchase_qty));
                    $('#stock_summary').find('.opening_qty').html(__number_f(data.opening_qty));
                    $('#stock_summary').find('.balance_qty').html(__number_f(data.balance_qty));
                    $('#stock_summary').find('.sold_amount').html(__currency_trans_from_en(data.sold_amount));
                    $('#stock_summary').find('.purchase_amount').html(__currency_trans_from_en(data
                        .purchase_amount));
                    $('#stock_summary').find('.opening_amount').html(__currency_trans_from_en(data
                        .opening_amount));
                    $('#stock_summary').find('.balance_amount').html(__currency_trans_from_en(data
                        .balance_amount));
                },
            });
        }

        $('.category_id').change(function() {
            var cat = $(this).val();
            var allLabel = "{{ __('lang_v1.all') }}";

            // Determine the correct subcategory dropdown for this specific category element
            var $subCatSelect;
            var triggerId = $(this).attr('id');
            if (triggerId === 'product_list_filter_category_id') {
                $subCatSelect = $('#product_list_filter_sub_category_id');
            } else if (triggerId === 'summary_product_list_filter_category_id') {
                $subCatSelect = $('#summary_product_list_filter_sub_category_id');
            } else {
                $subCatSelect = $('#sub_category_id');
            }

            // Reset subcategory to All when category is cleared
            if (!cat) {
                $subCatSelect.html('<option value="">' + allLabel + '</option>');
                if ($.fn.select2 && $subCatSelect.data('select2')) {
                    $subCatSelect.trigger('change.select2');
                }
            } else {
                $.ajax({
                    method: 'POST',
                    url: '/products/get_sub_categories',
                    dataType: 'html',
                    data: { cat_id: cat },
                    success: function(result) {
                        if (result) { $subCatSelect.html(result); }
                    },
                });
            }

            // Also update the product dropdown
            var sub_cat = $subCatSelect.val();
            var $productSelect;
            if (triggerId === 'product_list_filter_category_id') {
                $productSelect = $('#product_list_filter_product_id');
            } else if (triggerId === 'summary_product_list_filter_category_id') {
                $productSelect = $('#summary_product_list_filter_product_id');
            } else {
                $productSelect = $('#product_list_filter_product_id');
            }

            $.ajax({
                method: 'POST',
                url: '/products/get_product_category_wise',
                dataType: 'html',
                data: { cat_id: cat, sub_cat_id: sub_cat },
                success: function(result) {
                    if (result) { $productSelect.html(result); }
                },
            });
        });

    </script>


    <script>
        $(document).ready(function() {

        function ensureDateRangePicker(selector) {
        console.log('ensureDateRangePicker called for:', selector);
    
    // Check if already initialized
    if ($(selector).data('daterangepicker')) {
        console.log('Date picker already initialized for:', selector);
        return;
    }
    
    console.log('Initializing date picker for:', selector);
    
    $(selector).daterangepicker({
        locale: {
            format: 'YYYY-MM-DD'
        },
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month'),
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end, label) {
        // Callback when a range is selected
        console.log('Date range selected via callback:', label, start.format('YYYY-MM-DD'), 'to', end.format('YYYY-MM-DD'));
        
        // Manually trigger the apply event
        $(selector).trigger('apply.daterangepicker', [{
            startDate: start,
            endDate: end,
            chosenLabel: label
        }]);
    });
    
    console.log('Date picker initialized for:', selector);
}

            summaryUpdateProductTransaction();

            // if (!$('#product_transaction_report').is(':visible') &&
            //     !$('#prod_trans_report_store_wise').is(':visible')) {
            //     return;
            // }
            function summaryUpdateProductTransaction() {
                console.log('summaryUpdateProductTransaction called');

                var isStoreWise = $('#prod_trans_report_store_wise').hasClass('active');

                var $rangeEl = isStoreWise ?
                    $('#product_transaction_date_range_sw') :
                    $('#product_transaction_date_range');

                var drp = $rangeEl.length ? $rangeEl.data('daterangepicker') : null;

                var data = {
                    start_date: drp && drp.startDate ? drp.startDate.format('YYYY-MM-DD') : '',
                    end_date: drp && drp.endDate ? drp.endDate.format('YYYY-MM-DD') : '',

                    location_id: isStoreWise ?
                        $('#product_transaction_location_id_sw').val() : $('#product_transaction_location_id')
                        .val(),
                    category_id: isStoreWise ?
                        $('#product_transaction_category_id_sw').val() : $('#product_transaction_category_id')
                        .val(),
                    sub_category_id: isStoreWise ?
                        $('#product_transaction_sub_category_id_sw').val() : $(
                            '#product_transaction_sub_category_id').val(),
                    brand_id: isStoreWise ?
                        $('#product_transaction_brand_sw').val() : $('#product_transaction_brand').val(),
                    unit_id: isStoreWise ?
                        $('#product_transaction_unit_sw').val() : $('#product_transaction_unit').val(),
                    store_id: isStoreWise ?
                        $('#product_transaction_store_id_sw').val() : $('#product_transaction_store_id').val()
                };

                var loader = __fa_awesome();
                var $activeTab = isStoreWise ?
                    $('#prod_trans_report_store_wise') :
                    $('#product_transaction_report');

                $activeTab.find(
                    '.opening_qty, .opening_amount, .purchase_qty, .purchase_amount, .sold_qty, .sold_amount, .balance_qty, .balance_amount'
                ).html(loader);

                $.ajax({
                    method: 'GET',
                    url: '/reports/get-product-transaction-summary',
                    dataType: 'json',
                    data: data,
                    success: function(data) {
                        console.log('summary data', data);

                        $activeTab.find('.opening_qty').html(__number_f(data.opening_qty ?? 0));
                        $activeTab.find('.opening_amount').html(__currency_trans_from_en(data
                            .opening_amount ?? 0));

                        $activeTab.find('.purchase_qty').html(__number_f(data.purchase_qty ?? 0));
                        $activeTab.find('.purchase_amount').html(__currency_trans_from_en(data
                            .purchase_amount ?? 0));

                        $activeTab.find('.sold_qty').html(__number_f(data.sold_qty ?? 0));
                        $activeTab.find('.sold_amount').html(__currency_trans_from_en(data
                            .sold_amount ?? 0));

                        $activeTab.find('.balance_qty').html(__number_f(data.balance_qty ?? 0));
                        $activeTab.find('.balance_amount').html(__currency_trans_from_en(data
                            .balance_amount ?? 0));
                    }
                });
            }

            function loadStores(locationId, $store) {
                let option_value = "{{ __('lang_v1.all') }}";
                $store.empty().append(`<option value="">${option_value}</option>`).prop('disabled', false);

                if (!locationId) {
                    $store.trigger('change');
                    return;
                }

                $.ajax({
                    method: 'get',
                    url: '/stock-transfer/get_transfer_store_id/' + locationId,
                    data: {
                        check_store_not: null
                    },
                    success: function(result) {
                        $.each(result, function(i, store) {
                            $store.append(`<option value="${store.id}">${store.name}</option>`);
                        });
                        $store.prop('disabled', false).trigger('change');
                    }
                });
            }

            ensureDateRangePicker('#product_transaction_date_range');

            const ptCols = [{
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'transaction_date',
                    name: 'transaction_date'
                },
                {
                    data: 'sku',
                    name: 'sku'
                },
                {
                    data: 'product',
                    name: 'product'
                },
                {
                    data: 'description',
                    name: 'description'
                },
                {
                    data: 'store_name',
                    name: 'store_name'
                },
                {
                    data: 'starting_qty',
                    name: 'starting_qty'
                },
                {
                    data: 'purchase_qty',
                    name: 'purchase_qty'
                },
                {
                    data: 'bonus_qty',
                    name: 'bonus_qty'
                },
                {
                    data: 'sold_qty',
                    name: 'sold_qty'
                },
                {
                    data: 'balance_qty',
                    name: 'balance_qty'
                },
                {
                    data: 'balance_qty_value',
                    name: 'balance_qty_value'
                },
                {
                    data: 'stock_value_date_wise',
                    name: 'stock_value_date_wise',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'product_added_date',
                    name: 'product_added_date'
                }
            ];

            if (!$.fn.DataTable.isDataTable('#product_transaction_report_table')) {
                window.product_transaction_report_table = $('#product_transaction_report_table').DataTable({
                    processing: true,
                    serverSide: true,
                    autoWidth: false,
                    ajax: {
                        url: '/reports/product-transaction-report',
                        data: function(d) {
                            d.location_id = $('#product_transaction_location_id').val();
                            d.category_id = $('#product_transaction_category_id').val();
                            d.sub_category_id = $('#product_transaction_sub_category_id').val();
                            d.brand_id = $('#product_transaction_brand').val();
                            d.unit_id = $('#product_transaction_unit').val();
                            d.store_id = $('#product_transaction_store_id').val();
                            d.product_id = $('#product_transaction_product').val();

                            var drp = $('#product_transaction_date_range').data('daterangepicker');
                            if (drp) {
                                d.start_date = drp.startDate.format('YYYY-MM-DD');
                                d.end_date = drp.endDate.format('YYYY-MM-DD');
                            }
                            console.log('Sending dates to server:', d.start_date, 'to', d.end_date);
                        },
                        dataSrc: function(json) {
                            console.log('Server response:', json);
                            console.log('First row data:', json.data ? json.data[0] : 'No data');
                            return json.data;
                        }
                    },
                    @include('layouts.partials.datatable_export_button')
                    columns: ptCols
                });
            }

            $('#product_transaction_location_id')
                .off('change.pt')
                .on('change.pt', function() {
                    loadStores($(this).val(), $('#product_transaction_store_id'));
                });

            $('#product_transaction_report_filter_form')
                .off('change.ptfilters')
                .on('change.ptfilters', 'select,input', function() {
                    if (
                        window.product_transaction_report_table &&
                        window.product_transaction_report_table.ajax
                    ) {
                        window.product_transaction_report_table.ajax.reload();
                    }

                    summaryUpdateProductTransaction();
                });
                ensureDateRangePicker('#product_transaction_date_range');
                $('#product_transaction_date_range').on('apply.daterangepicker', function(ev, picker) {
    console.log('Date range changed:', picker.startDate.format('YYYY-MM-DD'), 'to', picker.endDate.format('YYYY-MM-DD'));
    if (window.product_transaction_report_table && window.product_transaction_report_table.ajax) {
        window.product_transaction_report_table.ajax.reload();
    }
    // Also update the summary with the new dates
    summaryUpdateProductTransaction();
});

 ensureDateRangePicker('#product_transaction_date_range_sw');

                $('#product_transaction_date_range_sw').on('apply.daterangepicker', function(ev, picker) {
    console.log('Store-wise date range changed');
    if (window.prod_trans_report_store_wise_table && window.prod_trans_report_store_wise_table.ajax) {
        window.prod_trans_report_store_wise_table.ajax.reload();
    }
    summaryUpdateProductTransaction();
});

           

            const ptSwCols = [{
                    data: 'action',
                    name: 'action',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'transaction_date',
                    name: 'transactions.transaction_date'
                },
                {
                    data: 'sku',
                    name: 'variations.sub_sku'
                },
                {
                    data: 'product',
                    name: 'p.name'
                },
                {
                    data: 'description',
                    name: 'description',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'starting_qty',
                    name: 'starting_qty',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'purchase_qty',
                    name: 'purchase_qty',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'bonus_qty',
                    name: 'bonus_qty',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'sold_qty',
                    name: 'sold_qty',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'balance_qty',
                    name: 'balance_qty',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'balance_qty_value',
                    name: 'balance_qty_value',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'date',
                    name: 'p.created_at'
                }
            ];

            if (!$.fn.DataTable.isDataTable('#prod_trans_report_store_wise_table')) {
                window.prod_trans_report_store_wise_table = $('#prod_trans_report_store_wise_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '/reports/product-transaction-report-store-wise',
                        data: function(d) {
                            d.location_id = $('#product_transaction_location_id_sw').val();
                            d.category_id = $('#product_transaction_category_id_sw').val();
                            d.sub_category_id = $('#product_transaction_sub_category_id_sw').val();
                            d.brand_id = $('#product_transaction_brand_sw').val();
                            d.unit_id = $('#product_transaction_unit_sw').val();
                            d.store_id = $('#product_transaction_store_id_sw').val();
                            d.product_id = $('#product_transaction_product_sw').val();

                            var drp = $('#product_transaction_date_range_sw').data('daterangepicker');
                            if (drp) {
                                d.start_date = drp.startDate.format('YYYY-MM-DD');
                                d.end_date = drp.endDate.format('YYYY-MM-DD');
                            }
                        },
                    },
                    @include('layouts.partials.datatable_export_button')
                    columns: ptSwCols,
                    order: [
                        [1, 'desc']
                    ]
                });
            }

            $('#product_transaction_location_id_sw')
                .off('change.ptsw')
                .on('change.ptsw', function() {
                    loadStores($(this).val(), $('#product_transaction_store_id_sw'));
                });

            $('#product_transaction_report_filter_form_sw')
                .off('change.ptswfilters')
                .on('change.ptswfilters', 'select,input', function() {
                    if (window.prod_trans_report_store_wise_table) {
                        window.prod_trans_report_store_wise_table.ajax.reload();
                    }
                    summaryUpdateProductTransaction();
                });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function() {
                setTimeout(function() {
                    $.fn.dataTable.tables({
                            visible: true,
                            api: true
                        })
                        .columns.adjust();
                }, 50);
            });

            $('a[href="#product_transaction_report"], a[href="#prod_trans_report_store_wise"]')
                .one('shown.bs.tab', function() {

                    ensureDateRangePicker('#product_transaction_date_range');
                    ensureDateRangePicker('#product_transaction_date_range_sw');

                    $('#product_transaction_location_id').trigger('change');
                    $('#product_transaction_location_id_sw').trigger('change');

                    summaryUpdateProductTransaction();
                });


        });
    </script>

    <script>
        $(document).ready(function() {

            let purchase_date = $('#product_pr_date_filter').val().split(' - ');
            $('.purchase_period_from').text(purchase_date[0]);
            $('.purchase_period_to').text(purchase_date[1]);

            $('#purchase_category_id, #purchase_sub_category_id, #purhcase_supplier_id, #purhcase_location_id, #product_pr_date_filter')
                .change(function() {
                    get_purchase_report_summary();

                    let purchase_date = $('#product_pr_date_filter').val().split(' - ');
                    $('.purchase_period_from').text(purchase_date[0]);
                    $('.purchase_period_to').text(purchase_date[1]);
                    if ($('#purchase_category_id').val() !== '' && $('#purchase_category_id').val() !==
                        undefined) {
                        $('.purchase_category').text($('#purchase_category_id :selected').text());
                    } else {
                        $('.purchase_category').text('All');
                    }
                    if ($('#purchase_sub_category_id').val() !== '' && $('#purchase_sub_category_id').val() !==
                        undefined) {
                        $('.purchase_sub_category').text($('#purchase_sub_category_id :selected').text());
                    } else {
                        $('.purchase_sub_category').text('All');
                    }
                    if ($('#purhcase_supplier_id').val() !== '' && $('#purhcase_supplier_id').val() !==
                        undefined) {
                        $('.purchase_sub_category').text($('#purhcase_supplier_id :selected').text());
                    } else {
                        $('.purchase_sub_category').text('All');
                    }
                    if ($('#purhcase_location_id').val() !== '' && $('#purhcase_location_id').val() !==
                        undefined) {
                        $('.purchase_sub_category').text($('#purhcase_location_id :selected').text());
                    } else {
                        $('.purchase_sub_category').text('All');
                    }
                });
            get_purchase_report_summary();

            $('#purchase_category_id').change(function() {
                var cat = $('#purchase_category_id').val();
                var allLabel = "{{ __('lang_v1.all') }}";

                // When category is All, reset subcategory to All without AJAX
                if (!cat) {
                    $('#purchase_sub_category_id').html('<option value="">' + allLabel + '</option>');
                    return;
                }

                $.ajax({
                    method: 'POST',
                    url: '/products/get_sub_categories',
                    dataType: 'html',
                    data: { cat_id: cat },
                    success: function(result) {
                        if (result) { $('#purchase_sub_category_id').html(result); }
                    },
                });
            });

        });

        function get_purchase_report_summary() {
            var loader = __fa_awesome();
            $('#product_purchase_report').find('.purchase_total_qty_purcahse').html(loader);
            $('#product_purchase_report').find('.purchase_total_qty_purcahse_value').html(loader);
            $('#product_purchase_report').find('.purchase_total_qty_adjusted').html(loader);
            $('#product_purchase_report').find('.purchase_total_qty_adjusted_value').html(loader);
            $('#product_purchase_report').find('.purchase_total_sold_qty').html(loader);
            let purchase_date = $('#product_pr_date_filter').val().split(' - ');

            let start_date = purchase_date[0];;
            let end_date = purchase_date[1];
            let variation_id = $('#variation_id').val();
            let supplier_id = $('select#purhcase_supplier_id').val();
            let location_id = $('select#purhcase_location_id').val();
            let category_id = $('select#purchase_category_id').val();
            let sub_category_id = $('select#purchase_sub_category_id').val();

            $.ajax({
                method: 'get',
                url: '/reports/product-purchase-report-summary',
                data: {
                    start_date,
                    end_date,
                    variation_id,
                    supplier_id,
                    location_id,
                    category_id,
                    sub_category_id,
                },
                success: function(result) {
                    $('.purchase_total_qty_purcahse').html(result.purchase_qty);
                    $('.purchase_total_qty_purcahse_value').html(result.purchase_qty_value);
                    $('.purchase_total_qty_adjusted').html(result.adjusted_qty);
                    $('.purchase_total_qty_adjusted_value').html(result.adjusted_qty_value);
                    $('.purchase_total_sold_qty').html(result.total_sold_qty);
                },
            });
        }

        const printBusinessName = @json(request()->session()->get('business.name'));
        const printSystemFooter = @json(optional(\App\System::where('key','admin_reports_footer')->first())->value);

        function buildReportPrintHeader(locationText, dateText) {
            return '' +
                '<div style="width:100%;text-align:center;margin-bottom:8px;">' +
                '<h3 style="margin:0 0 6px 0;">' + (printBusinessName || '') + '</h3>' +
                '<div><strong>Business Location:</strong> ' + (locationText || 'All') + '</div>' +
                '<div><strong>Date:</strong> ' + (dateText || '') + '</div>' +
                '</div><hr style="margin:8px 0;">';
        }

        function buildReportPrintFooter() {
            if (!printSystemFooter) {
                return '';
            }
            return '<hr style="margin:8px 0;"><div style="text-align:center;">' + printSystemFooter + '</div>';
        }

        function printPSummary() {
            var w = window.open('', '_self');
            var locationText = $('#purhcase_location_id option:selected').text() || 'All';
            var dateText = $('#product_pr_date_filter').val() || '';
            var html = buildReportPrintHeader(locationText, dateText) +
                document.getElementById("purchase_summary_div").innerHTML +
                buildReportPrintFooter();
            $(w.document.body).html(html);
            w.print();
            w.close();
            window.location.href = "{{ URL::to('/') }}/reports/product";
        }

        function printPDiv() {
            $('.remove-print').removeClass('table-responsive');
            var w = window.open('', '_self');
            var locationText = $('#purhcase_location_id option:selected').text() || 'All';
            var dateText = $('#product_pr_date_filter').val() || '';
            var html = buildReportPrintHeader(locationText, dateText) +
                document.getElementById("purchase_summary_div").innerHTML +
                document.getElementById("table_div").innerHTML +
                buildReportPrintFooter();
            $(w.document.body).html(html);
            w.print();
            w.close();
            window.location.href = "{{ URL::to('/') }}/reports/product";
        }

        $(document).ready(function() {
            let drp = $('#product_sr_date_filter').data('daterangepicker');
            if (drp) {
                $('.sell_period_from').text(drp.startDate.format('YYYY-MM-DD'));
                $('.sell_period_to').text(drp.endDate.format('YYYY-MM-DD'));
            }

            $('#sell_category_id, #sell_sub_category_id, #sell_customer_id, #sell_location_id, #product_sr_date_filter')
                .change(function() {
                    //set value in summary section
                    get_sell_report_summary();

                    let drp = $('#product_sr_date_filter').data('daterangepicker');
                    if (drp) {
                        $('.sell_period_from').text(drp.startDate.format('YYYY-MM-DD'));
                        $('.sell_period_to').text(drp.endDate.format('YYYY-MM-DD'));
                    }
                    if ($('#sell_category_id').val() !== '' && $('#sell_category_id').val() !== undefined) {
                        $('.sell_category').text($('#sell_category_id :selected').text());
                    } else {
                        $('.sell_category').text('All');
                    }
                    if ($('#sell_sub_category_id').val() !== '' && $('#sell_sub_category_id').val() !==
                        undefined) {
                        $('.sell_sub_category').text($('#sell_sub_category_id :selected').text());
                    } else {
                        $('.sell_sub_category').text('All');
                    }
                    if ($('#sell_customer_id').val() !== '' && $('#sell_customer_id').val() !== undefined) {
                        $('.sell_customer').text($('#sell_customer_id :selected').text());
                    } else {
                        $('.sell_customer').text('All');
                    }
                    if ($('#sell_location_id').val() !== '' && $('#sell_location_id').val() !== undefined) {
                        $('.sell_location').text($('#sell_location_id :selected').text());
                    } else {
                        $('.sell_location').text('All');
                    }
                });
            get_sell_report_summary();
        });

        function get_sell_report_summary() {
            var loader = __fa_awesome();
            $('#product_sell_report').find('.sell_total_qty_purcahse').html(loader);
            $('#product_sell_report').find('.sell_total_qty_purcahse_value').html(loader);
            $('#product_sell_report').find('.sell_total_qty_adjusted').html(loader);
            $('#product_sell_report').find('.sell_total_qty_adjusted_value').html(loader);
            let drp = $('#product_sr_date_filter').data('daterangepicker');
            let start_date = drp ? drp.startDate.format('YYYY-MM-DD') : '';
            let end_date = drp ? drp.endDate.format('YYYY-MM-DD') : '';
            let variation_id = $('#variation_id').val();
            let customer_id = $('select#sell_customer_id').val();
            let location_id = $('select#sell_location_id').val();
            let category_id = $('select#sell_category_id').val();
            let sub_category_id = $('select#sell_sub_category_id').val();

            $.ajax({
                method: 'get',
                url: '/reports/product-sell-report-summary',
                data: {
                    start_date,
                    end_date,
                    variation_id,
                    customer_id,
                    location_id,
                    category_id,
                    sub_category_id,
                },
                success: function(result) {
                    $('.sell_total_qty_sell').html(result.sell_qty);
                    $('.sell_total_qty_sell_value').html(result.sell_qty_value);
                },
            });
        }

        $('#sell_category_id').change(function() {
            var cat = $('#sell_category_id').val();
            var allLabel = "{{ __('lang_v1.all') }}";

            // When category is All, reset subcategory to All without AJAX
            if (!cat) {
                $('#sell_sub_category_id').html('<option value="">' + allLabel + '</option>');
                return;
            }

            $.ajax({
                method: 'POST',
                url: '/products/get_sub_categories',
                dataType: 'html',
                data: { cat_id: cat },
                success: function(result) {
                    if (result) { $('#sell_sub_category_id').html(result); }
                },
            });
        })


        function printSSummary() {
            var w = window.open('', '_self');
            var locationText = $('#sell_location_id option:selected').text() || 'All';
            var dateText = $('#product_sr_date_filter').val() || '';
            var html = buildReportPrintHeader(locationText, dateText) +
                document.getElementById("sell_summary_div").innerHTML +
                buildReportPrintFooter();
            $(w.document.body).html(html);
            w.print();
            w.close();
            window.location.href = "{{ URL::to('/') }}/reports/product";
        }

        function printSDiv() {
            $('.remove-print').removeClass('table-responsive');
            var w = window.open('', '_self');
            var locationText = $('#sell_location_id option:selected').text() || 'All';
            var dateText = $('#product_sr_date_filter').val() || '';
            var html = buildReportPrintHeader(locationText, dateText) +
                document.getElementById("sell_summary_div").innerHTML +
                document.getElementById("table_div").innerHTML +
                buildReportPrintFooter();
            $(w.document.body).html(html);
            w.print();
            w.close();
            window.location.href = "{{ URL::to('/') }}/reports/product";
        }

        $(document).ready(function() {
            var product_loss_excess_report_cols = [{
                    data: 'transaction_date',
                    name: 'transaction_date'
                },
                {
                    data: 'location_name',
                    name: 'business_locations.name'
                },
                {
                    data: 'product',
                    name: 'p.name'
                },
                {
                    data: 'unit',
                    name: 'units.short_name'
                },
                {
                    data: 'weight_loss_excess_qty',
                    name: 'weight_loss_excess_qty'
                },
                {
                    data: 'weight_loss_excess',
                    name: 'weight_loss_excess'
                },
                {
                    data: 'customer_name',
                    name: 'contacts.name'
                },
                {
                    data: 'invoice_no',
                    name: 'invoice_no'
                },
                {
                    data: 'final_total',
                    name: 'final_total'
                }
            ];
            product_loss_excess_report_table = $('#product_loss_excess_report_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/reports/product-weight-loss-excess-report',
                    data: function(d) {
                        d.location_id = $('#product_loss_excess_location_id').val();
                        d.contact_id = $('#product_loss_excess_customer').val();
                        d.type = $('#product_loss_excess_type').val();
                        d.unit_id = $('#product_loss_excess_unit').val();
                        d.product_id = $('#product_loss_excess_product').val();

                        var drp = $('#product_loss_excess_date_range').data('daterangepicker');
                        if (drp) {
                            d.start_date = drp.startDate.format('YYYY-MM-DD');
                            d.end_date = drp.endDate.format('YYYY-MM-DD');
                        }
                    },
                },
                @include('layouts.partials.datatable_export_button')
                columns: product_loss_excess_report_cols,
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#product_loss_excess_report_table'));
                },
            });

            $('#product_loss_excess_product, #product_loss_excess_date_range, #product_loss_excess_report_filter_form #location_id, #product_loss_excess_report_filter_form #product_loss_excess_type, #product_loss_excess_report_filter_form #product_loss_excess_customer, #product_loss_excess_report_filter_form #product_loss_excess_brand, #product_loss_excess_report_filter_form #product_loss_excess_unit,#product_loss_excess_report_filter_form #view_stock_filter,#product_loss_excess_report_filter_form #store_id ')
                .change(function() {
                    product_loss_excess_report_table.ajax.reload();
                });

            $('#product_loss_excess_category_id, #product_loss_excess_sub_category_id').change(function() {
                var cat = $('#product_loss_excess_category_id').val();
                var sub_cat = $('#product_loss_excess_sub_category_id').val();
                $.ajax({
                    method: 'POST',
                    url: '/products/get_sub_categories',
                    dataType: 'html',
                    data: {
                        cat_id: cat
                    },
                    success: function(result) {
                        if (result) {
                            $('#product_loss_excess_sub_category_id').html(result);
                        }
                    },
                });
                $.ajax({
                    method: 'POST',
                    url: '/products/get_product_category_wise',
                    dataType: 'html',
                    data: {
                        cat_id: cat,
                        sub_cat_id: sub_cat
                    },
                    success: function(result) {
                        if (result) {
                            $('#product_loss_excess_product').html(result);
                        }
                    },
                });
            });
        })
    </script>
@endsection
