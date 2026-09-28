@extends('layouts.app')
@section('title', __('mpcs::lang.F22StockTaking_form'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1> @lang('mpcs::lang.F22StockTaking_form')
        <small>@lang( 'mpcs::lang.F22StockTaking_form', ['contacts' => __('mpcs::lang.mange_F22StockTaking_form')
            ])</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="row">
                <div class="col-md-3">
                    {{-- {!! Form::label('manager_name', __('mpcs::lang.manager_name'), ['']) !!}
                    {!! Form::text('manager_name', null, ['class' => 'form-control']) !!} --}}
                </div>
                <div class="col-md-3 pull-right">
                    <button type="submit" name="submit_type" id="f22_save_and_print" value="save_and_print"
                        class="btn btn-primary pull-right"
                        style="margin-left: 20px">@lang('mpcs::lang.update_and_print')</button>
                </div>
            </div>
            <div class="col-md-12">
                {{-- <div class="row">
                    <div class="col-md-4"></div>
                    <div class="col-md-5">
                        <div class="text-center">
                            <h5 style="font-weight: bold;">{{request()->session()->get('business.name')}} <br>
                                <span class="f22_location_name">@lang('petro::lang.all')</span></h5>
                                <input type="hidden" name="f22_location_name" id="f22_location_name" value="All">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center pull-left">
                            <h5 style="font-weight: bold;" class="text-red">@lang('mpcs::lang.f22_form')
                                @lang('mpcs::lang.form_no') : {{$F22_from_no}}</h5>
                        </div>
                    </div>
                </div> --}}
                {!! Form::close() !!}
                <div class="row" style="margin-top: 20px;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="form_22_table">
                            <thead>
                                <tr>
                                    <th>@lang('mpcs::lang.code')</th>
                                    <th>@lang('mpcs::lang.book_no')</th>
                                    <th>@lang('mpcs::lang.product')</th>
                                    <th>@lang('mpcs::lang.current_stock')</th>
                                    <th>@lang('mpcs::lang.stock_count')</th>
                                    <th>@lang('mpcs::lang.unit_purchase_price')</th>
                                    <th>@lang('mpcs::lang.total_purchase_price')</th>
                                    <th>@lang('mpcs::lang.unit_sale_price')</th>
                                    <th>@lang('mpcs::lang.total_sale_price')</th>
                                    <th>@lang('mpcs::lang.qty_difference')</th>

                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray">
                                    <td class="text-red text-bold" colspan="6">@lang('mpcs::lang.total_this_page')</td>
                                    <td class="text-red text-bold text-right" id="footer_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="footer_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr class="bg-gray">
                                    <td class="text-red text-bold" colspan="6">@lang('mpcs::lang.total_previous_page')
                                    </td>
                                    <td class="text-red text-bold text-right" id="pre_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="pre_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr class="bg-gray">
                                    <td class="text-red text-bold" colspan="6">@lang('mpcs::lang.grand_total')</td>
                                    <td class="text-red text-bold text-right" id="grand_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="grand_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td colspan="11"> @lang('mpcs::lang.confirm_f22')</td>
                                </tr>
                                <tr>
                                    <td colspan="6"><h5 style="font-weight: bold; margin-bottom: 0px; ">
                                        @lang('mpcs::lang.checked_by'): ____________</h5></td>
                                        <td colspan="4"><h5 style="font-weight: bold; margin-bottom: 0px; ">
                                            @lang('mpcs::lang.received_by'): ____________</h5> <br></td>
                                </tr>
                                <tr>
                                    <td colspan="6"> <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                        @lang('mpcs::lang.signature_of_manager'): ____________</h5></td>
                                        <td colspan="4">
                                            <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                                @lang('mpcs::lang.handed_over_by'): ____________</h5>
                                        </td>
                                </tr>
                                <tr>
                                    <td colspan="11"> <h5 style="font-weight: bold; margin-top: 10px; ">@lang('mpcs::lang.user'):
                                        {{auth()->user()->username }}</h5></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <input type="hidden" name="purchase_price1" id="purchase_price1" value="">
                <input type="hidden" name="sales_price1" id="sales_price1" value="">
                <input type="hidden" name="purchase_price2" id="purchase_price2" value="">
                <input type="hidden" name="sales_price2" id="sales_price2" value="">
                <input type="hidden" name="purchase_price3" id="purchase_price3" value="">
                <input type="hidden" name="sales_price3" id="sales_price3" value="">
            </div>

            @endcomponent
            
        </div>
    </div>


</section>
<!-- /.content -->

@endsection
@section('javascript')
<script type="text/javascript">
  

$(document).ready(function(){
   
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
        columnDefs: [ {
            "targets": 0,
            "orderable": false
        } ],
        ajax: {
            url: '/mpcs/edit-form-f22/{{$id}}',
            data: function(d) {
                d.location_id = $('#f22_location_id').val();
                d.product_id = $('#f22_product_id').val();
            }
        },
        "columnDefs": [
            { "width": "2%", "targets": 2 }
        ],
        columns: [
            { data: 'sku', name: 'sku' },
            { data: 'book_no', name: 'book_no' },
            { data: 'product', name: 'product' },
            { data: 'current_stock', name: 'current_stock', className: 'text-right' },
            { data: 'stock_count', name: 'stock_count', className: 'text-right' },
            { data: 'unit_purchase_price', name: 'unit_purchase_price', className: 'text-right' },
            { data: 'total_purchase_price', name: 'total_purchase_price', className: 'text-right' },
            { data: 'unit_sale_price', name: 'unit_sale_price', className: 'text-right' },
            { data: 'total_sale_price', name: 'total_sale_price', className: 'text-right' },
            { data: 'qty_difference', name: 'qty_difference', className: 'text-right' }
        ],
        fnDrawCallback: function(oSettings) {
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
            calculateTotals(api);
        },
        "initComplete": function(settings, json) {
            var table_info = form_22_table.page.info(); //get table info
             for( i = 0; i < table_info.pages ; i++){
                ppage_totals[i] = 0.00;
                spage_totals[i] = 0.00;
                pre_gppage_totals[i] = 0.00;
                pre_gspage_totals[i] = 0.00;
                
            }
        }
    });

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
   
 
    function calculateTotals(apiInstance) {
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

    $('#form_22_table').on( 'page.dt', function () {
        calculateTotals(1);
    });
    $('#form_22_table').on( 'init.dt', function () {
        $('.stock_count').each(function(){
            if(parseF22Number($(this).val()) > 0) {
                $(this).trigger('keyup');
            }
        });
    });

   

    $('#f22_save_and_print').click(function(e){
        e.preventDefault();
        $.ajax({
            method: 'put',
            url: '/mpcs/update-form-f22/{{$id}}',
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

});

function printPage(content) {
    var w = window.open('', '_self');
    $(w.document.body).html(content);
    w.print();
    w.close();
    window.location.href = "{{URL::to('/')}}/mpcs/F22_stock_taking";
}
</script>
@endsection
