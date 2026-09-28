@extends('layouts.app')
@section('title', __('mpcs::lang.StockTaking_form'))

@section('content')


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang( 'mpcs::lang.StockTaking_form', ['contacts' => __('mpcs::lang.mange_F22StockTaking_form')
            ])</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('mpcs::lang.StockTaking_form')</a></li>
                    <li><span>@lang( 'mpcs::lang.StockTaking_form', ['contacts' => __('mpcs::lang.mange_F22StockTaking_form')
            ])</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- Main content -->
<section class="content main-content-inner">
    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#f22_form_tab" class="f22_form_tab" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.stock_taking_form')</strong>
                        </a>
                    </li>

                    <li>
                        <a href="#f22_last_verified_stock_tab" class="f22_last_verified_stock_tab" style=""
                            data-toggle="tab">
                            <i class="fa fa-check"></i> <strong>
                                @lang('mpcs::lang.last_verified_stock') </strong>
                        </a>
                    </li>

                    <li>
                        <a href="#list_f22_stock_taking_tab" class="list_f22_stock_taking_tab" style=""
                            data-toggle="tab">
                            <i class="fa fa-sign-in"></i> <strong>
                                @lang('mpcs::lang.list_stock_taking') </strong>
                        </a>
                    </li>


                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="f22_form_tab">
                        @include('mpcs::forms.F22.partials.form')
                    </div>

                    <div class="tab-pane" id="f22_last_verified_stock_tab">
                        @include('mpcs::forms.F22.partials.f22_last_verified_stock')
                    </div>

                    <div class="tab-pane" id="list_f22_stock_taking_tab">
                        @include('mpcs::forms.F22.partials.list_f22_stock_taking')
                    </div>
                </div>
            </div>
        </div>
    </div>


</section>
<!-- /.content -->

@endsection
@section('javascript')
<script type="text/javascript">
    $('#f22_product_id').select2();
  $('#form_date_range').daterangepicker({
        ranges: ranges,
        autoUpdateInput: false,
        locale: {
            format: moment_date_format,
            cancelLabel: LANG.clear,
            applyLabel: LANG.apply,
            customRangeLabel: LANG.custom_range,
        },
    });
    $('#form_date_range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(
            picker.startDate.format(moment_date_format) +
                ' - ' +
                picker.endDate.format(moment_date_format)
        );
    });

    $('#form_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    
    $('#form_f22_date_range').daterangepicker();
    if ($('#form_f22_date_range').length == 1) {
        $('#form_f22_date_range').daterangepicker(dateRangeSettings, function(start, end) {
            $('#form_f22_date_range').val(
                start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
            );
        });
        $('#form_f22_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#product_sr_date_filter').val('');
        });
        $('#form_f22_date_range')
            .data('daterangepicker')
            .setStartDate(moment().startOf('month'));
        $('#form_f22_date_range')
            .data('daterangepicker')
            .setEndDate(moment().endOf('month'));
    }

  

$('#f22_location_id option:eq(1)').attr('selected', true);
$(document).ready(function(){
     form_f22_list_table = $('#form_f22_list_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/mpcs/get-form-f22-list',
            data: function(d) {
                // d.location_id = $('#f22_location_id').val();
                // d.product_id = $('#f22_product_id').val();
            }
        },
        columns: [
            { data: 'created_at', name: 'created_at' },
            { data: 'locations_name', name: 'location' },
            { data: 'form_no', name: 'form_no' },
            { data: 'stock_adjustment_no', name: 'stock_adjustment_no' },
            { data: 'total_stock_lose_purchase', name: 'total_stock_lose_purchase' },
            { data: 'total_stock_lose_sale', name: 'total_stock_lose_sale' },
            { data: 'username', name: 'username' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        fnDrawCallback: function(oSettings) {

        },
    });

    $('#f22_product_id, #f22_location_id').change(function(){
        form_22_table.ajax.reload();
        if($('#f22_location_id').val() !== ''  && $('#f22_location_id').val() !== undefined){
            $('.f22_location_name').text($('#f22_location_id :selected').text());
            $('#f22_location_name').val($('#f22_location_id :selected').text());
        }else{
            $('.f22_location_name').text('All');
            $('#f22_location_name').val('All');
        }
    });


    var form22EditedValues = {};

    function parseFieldFromHtml(html, selector, attrName) {
        const $el = $('<div>' + (html || '') + '</div>').find(selector).first();
        if (!$el.length) {
            return '';
        }
        if (attrName) {
            return $el.attr(attrName) || '';
        }
        if ($el.is('input')) {
            return $el.val() || '';
        }
        return ($el.text() || '').trim();
    }

    function parseF22Number(value) {
        if (value === null || value === undefined || value === '') {
            return 0;
        }

        value = value.toString().replace(/,/g, '').trim();
        value = parseFloat(value);

        return isNaN(value) ? 0 : value;
    }
   
     //form_22_table 
      form_22_table = $('#form_22_table').DataTable({
         processing: true,
         serverSide: false,
         lengthChange: false,
         pageLength: {{!empty($settings->F22_no_of_product_per_page) ? $settings->F22_no_of_product_per_page : 25}},
         columnDefs: [ {
             "targets": 0,
             "orderable": false
         } ],
         ajax: {
             url: '/mpcs/get-form-f22',
             data: function(d) {
                 d.location_id = $('#f22_location_id').val();
                 d.product_id = $('#f22_product_id').val();
             }
         },
         "columnDefs": [
             { "width": "2%", "targets": 2 }
         ],
         columns: [
             { data: 'DT_Row_Index', name: 'DT_Row_Index' },
             { data: 'sku', name: 'sku' },
             { data: 'book_no', name: 'book_no' },
             { data: 'product', name: 'product' },
             { data: 'current_stock', name: 'current_stock' },
             { data: 'stock_count', name: 'stock_count' },
             { data: 'unit_purchase_price', name: 'unit_purchase_price' },
             { data: 'total_purchase_price', name: 'total_purchase_price' },
             { data: 'unit_sale_price', name: 'unit_sale_price' },
             { data: 'total_sale_price', name: 'total_sale_price' },
             { data: 'qty_difference', name: 'qty_difference' },
         ],
         fnDrawCallback: function(oSettings) {
             __currency_convert_recursively($('#form_22_table'));
             var api = this.api();
             var currency_precision = __currency_precision;
             api.rows({ page: 'current' }).every(function () {
                 var tr = $(this.node());
                 if (!tr.length) return;
                 var rowData = this.data();
                 var rowKeyMatch = (parseFieldFromHtml(rowData.product, 'input[type="hidden"][name^="f22["]', 'name') || '')
                     .match(/^f22\[(.+?)\]\[/);
                 var rowKey = rowKeyMatch ? rowKeyMatch[1] : null;
                 if (rowKey && form22EditedValues[rowKey] !== undefined) {
                     var edited = form22EditedValues[rowKey];
                     tr.find('input.stock_count').val(edited.stock_count);
                     tr.find('.total_purchase_price').text(__number_f(edited.total_purchase_price, false, false, currency_precision));
                     tr.find('.total_purchase_price').data('orig-value', edited.total_purchase_price);
                     tr.find('input.total_purchase_price_input').val(edited.total_purchase_price);
                     tr.find('.total_sale_price').text(__number_f(edited.total_sale_price, false, false, currency_precision));
                     tr.find('.total_sale_price').data('orig-value', edited.total_sale_price);
                     tr.find('input.total_sale_price_input').val(edited.total_sale_price);
                     tr.find('.qty_difference').val(edited.qty_difference);
                 }
             });
             updateF22Totals(api);
         }
     });

    function updateF22Totals(apiInstance) {
        const api = (apiInstance && typeof apiInstance === 'object') ? apiInstance : $('#form_22_table').DataTable();
        if (!api) return;
        var info = api.page.info();
        if (!info) return;
        var start = info.start;
        var end = info.end;

        let pagePurchase = 0;
        let pageSale = 0;
        let prevPurchase = 0;
        let prevSale = 0;

        api.rows({ search: 'applied', order: 'applied' }).every(function(rowIdx, tableLoop, containerLoop) {
            const rowData = this.data();
            const rowKeyMatch = (parseFieldFromHtml(rowData.product, 'input[type="hidden"][name^="f22["]', 'name') || '')
                .match(/^f22\[(.+?)\]\[/);
            const rowKey = rowKeyMatch ? rowKeyMatch[1] : null;
            
            const unitPurchasePrice = parseF22Number(parseFieldFromHtml(rowData.unit_purchase_price, '.unit_purchase_price', 'data-orig-value'));
            const unitSalePrice = parseF22Number(parseFieldFromHtml(rowData.unit_sale_price, '.unit_sale_price', 'data-orig-value'));

            const edited = rowKey ? (form22EditedValues[rowKey] || {}) : {};
            
            let stockCount;
            if (edited.stock_count !== undefined) {
                stockCount = parseF22Number(edited.stock_count);
            } else {
                stockCount = parseF22Number(parseFieldFromHtml(rowData.stock_count, 'input.stock_count'));
            }

            const purchase = edited.total_purchase_price !== undefined ? parseF22Number(edited.total_purchase_price) : (stockCount * unitPurchasePrice);
            const sale = edited.total_sale_price !== undefined ? parseF22Number(edited.total_sale_price) : (stockCount * unitSalePrice);

            if (containerLoop >= start && containerLoop < end) {
                pagePurchase += purchase;
                pageSale += sale;
            } else if (containerLoop < start) {
                prevPurchase += purchase;
                prevSale += sale;
            }
        });

        let pgrand = prevPurchase + pagePurchase;
        let sgrand = prevSale + pageSale;
        
        $('#purchase_price1').val(pagePurchase);
        $('#purchase_price3').val(pagePurchase);
        $('#sales_price1').val(pageSale);
        $('#sales_price3').val(pageSale);

        $('#footer_total_purchase_price').text(__number_f(pagePurchase, false, false, __currency_precision));
        $('#footer_total_sale_price').text(__number_f(pageSale, false, false, __currency_precision));
        $('#pre_total_purchase_price').text(__number_f(prevPurchase, false, false, __currency_precision));
        $('#pre_total_sale_price').text(__number_f(prevSale, false, false, __currency_precision));
        $('#grand_total_purchase_price').text(__number_f(pgrand, false, false, __currency_precision));
        $('#grand_total_sale_price').text(__number_f(sgrand, false, false, __currency_precision));
    }

    $(document).on('keyup', '.stock_count', function(){
       let tr = $(this).closest('tr');
       let rowData = form_22_table.row(tr).data();
       if (!rowData) return;
       let rowKeyMatch = (parseFieldFromHtml(rowData.product, 'input[type="hidden"][name^="f22["]', 'name') || '')
           .match(/^f22\[(.+?)\]\[/);
       let rowKey = rowKeyMatch ? rowKeyMatch[1] : null;
       if (!rowKey) return;

       let unit_purchase_price =  parseF22Number(tr.find('.unit_purchase_price').data('orig-value'));
       let unit_sale_price =  parseF22Number(tr.find('.unit_sale_price').data('orig-value'));
       let current_stock =  parseF22Number(tr.find('.current_stock').data('orig-value'));
       let stock = parseF22Number($(this).val());

       let total_purhcase_value = unit_purchase_price * stock;
       let total_sale_value = unit_sale_price * stock;
       let qty_difference = stock - current_stock;
       tr.find('.total_purchase_price').text(__number_f(total_purhcase_value, false, false, __currency_precision));
       tr.find('.total_purchase_price').data('orig-value',total_purhcase_value);
       tr.find('.total_purhcase_value').val(total_purhcase_value);
       tr.find('.total_sale_price').text(__number_f(total_sale_value, false, false, __currency_precision));
       tr.find('.total_sale_price').data('orig-value', total_sale_value);
       tr.find('.total_sale_value').val(total_sale_value);
       tr.find('.qty_difference').val(qty_difference);

       form22EditedValues[rowKey] = {
           stock_count: stock,
           total_purchase_price: total_purhcase_value,
           total_sale_price: total_sale_value,
           qty_difference: qty_difference
       };
       
       calculateTotals();
    });
    
    function calculateTotals() {
        updateF22Totals();
    }

     //form_22_table 
    var lf_ppage_totals = [];  
    var lf_spage_totals = [];
    var lf_pre_gppage_totals = [];
    var lf_pre_gspage_totals = [];
     form_22_last_verified_table = $('#form_22_last_verified_table').DataTable({
        processing: true,
        serverSide: false,
        pageLength: {{!empty($settings->F22_no_of_product_per_page) ? $settings->F22_no_of_product_per_page : 25}},
        ajax: {
            url: '/mpcs/get-last-verified-form-f22',
            data: function(d) {
            }
        },
        "columnDefs": [
            { "width": "2%", "targets": 2 }
        ],
        columns: [
            { data: 'DT_Row_Index', name: 'DT_Row_Index' },
            { data: 'sku', name: 'sku' },
            { data: 'book_no', name: 'book_no' },
            { data: 'product', name: 'product' },
            { data: 'current_stock', name: 'current_stock' },
            { data: 'stock_count', name: 'stock_count' },
            { data: 'unit_purchase_price', name: 'unit_purchase_price' },
            { data: 'total_purchase_price', name: 'total_purchase_price' },
            { data: 'unit_sale_price', name: 'unit_sale_price' },
            { data: 'total_sale_price', name: 'total_sale_price' },
            { data: 'qty_difference', name: 'qty_difference' },
        ],
        fnDrawCallback: function(oSettings) {
         
        },
        "initComplete": function(settings, json) {
            var table_info = form_22_last_verified_table.page.info(); //get table info
             for( i = 0; i < table_info.pages ; i++){
                lf_ppage_totals[i] = 0.00;
                lf_spage_totals[i] = 0.00;
                lf_pre_gppage_totals[i] = 0.00;
                lf_pre_gspage_totals[i] = 0.00;
                
            }
        }
    });
 
    $('#form_22_last_verified_table').on( 'page.dt', function () {
        lastFormCalculateTotals(1);
    });
    $('#form_22_last_verified_table').on( 'init.dt', function () {
        lastFormCalculateTotals();
    }).dataTable();

    function lastFormCalculateTotals() {
        var info = form_22_last_verified_table.page.info();
        if (!info) return;
        var start = info.start;
        var end = info.end;

        let pagePurchase = 0;
        let pageSale = 0;
        let prevPurchase = 0;
        let prevSale = 0;

        form_22_last_verified_table.rows({ search: 'applied', order: 'applied' }).every(function(rowIdx, tableLoop, containerLoop) {
            let rowNode = this.node();
            let purchase = parseFloat($(rowNode).find('.lf_total_purchase_price').attr('data-orig-value')) || parseFloat($(rowNode).find('.lf_total_purchase_price').text().replace(/,/g, '')) || 0;
            let sale = parseFloat($(rowNode).find('.lf_total_sale_price').attr('data-orig-value')) || parseFloat($(rowNode).find('.lf_total_sale_price').text().replace(/,/g, '')) || 0;

            if (containerLoop >= start && containerLoop < end) {
                pagePurchase += purchase;
                pageSale += sale;
            } else if (containerLoop < start) {
                prevPurchase += purchase;
                prevSale += sale;
            }
        });

        let lf_pgrand = prevPurchase + pagePurchase;
        let lf_sgrand = prevSale + pageSale;
        
        $('#lf_footer_total_purchase_price').text(__number_f(pagePurchase, false, false, __currency_precision));
        $('#lf_footer_total_sale_price').text(__number_f(pageSale, false, false, __currency_precision));
        $('#lf_pre_total_purchase_price').text(__number_f(prevPurchase, false, false, __currency_precision));
        $('#lf_pre_total_sale_price').text(__number_f(prevSale, false, false, __currency_precision));
        $('#lf_grand_total_purchase_price').text(__number_f(lf_pgrand , false, false, __currency_precision));
        $('#lf_grand_total_sale_price').text(__number_f(lf_sgrand , false, false, __currency_precision));
    }
  

    $('#f22_save_and_print').click(function(e){
        e.preventDefault();
        $(this).attr('disabled', 'disabled');
        $.ajax({
            method: 'post',
            url: '/mpcs/save-form-f22',
            // data: { data: $('#f22_form').serialize() },
            data: { data: form_22_table.$('input, select').serialize() + $('#f22_form').serialize() },
            success: function(result) {
                if(result.success == 0){
                    toastr.error(result.msg);

                    return false;
                }

                printPage(result);
                
            },
        });
    })
    $('#f22_print').click(function(e){
        e.preventDefault();
        $.ajax({
            method: 'post',
            url: '/mpcs/print-form-f22',
            data: { data: form_22_table.$('input, select').serialize() + $('#f22_form').serialize() },
            success: function(result) {
                if(result.success == 0){
                    toastr.error(result.msg);

                    return false;
                }
                onlyPrintPage(result);
                
            },
        });
    });
    $('#lf_f22_print').click(function(e){
        e.preventDefault();
        $.ajax({
            method: 'post',
            url: '/mpcs/print-form-f22',
            data: { data: form_22_last_verified_table.$('input, select').serialize() + $('#lf_f22_form').serialize() },
            success: function(result) {
                if(result.success == 0){
                    toastr.error(result.msg);

                    return false;
                }
                printPage(result);
                
            },
        });
    });
    $(document).on('click', '.reprint_form', function(e){
        e.preventDefault();
        href= $(this).data('href');
        console.log(href);
        
        $.ajax({
            method: 'get',
            url: href,
            data: { },
            success: function(result) {
                if(result.success == 0){
                    toastr.error(result.msg);

                    return false;
                }
                printPage(result);
                
            },
        });
    });


});

function printPage(content) {
		var w = window.open('', '_self');
		$(w.document.body).html(content);
		w.print();
		w.close();
        window.location.href = "{{URL::to('/')}}/mpcs/F22_stock_taking";
	}
function onlyPrintPage(content) {
		var w = window.open('', '_blank');
		$(w.document.body).html(`@include('layouts.partials.css')` + content);
		w.print();
		w.close();
        return false;
	}

</script>
@endsection
