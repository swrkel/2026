@extends('layouts.app')
@section('title', __('lang_v1.product_stock_history'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.product_stock_history')</h1>
</section>

<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-md-12">
    @component('components.widget', ['title' => $product->name])
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('product_id',  __('sale.product') . ':') !!}
                {!! Form::select('product_id', [$product->id=>$product->name . ' - ' . $product->sku], $product->id, ['class' => 'form-control', 'style' => 'width:100%']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, request()->input('location_id', null), ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </div>
        @if($product->type == 'variable')
            <div class="col-md-3">
                <div class="form-group">
                    <label for="variation_id">@lang('product.variations'):</label>
                    <select class="select2 form-control" name="variation_id" id="variation_id">
                        @foreach($product->variations as $variation)
                            <option value="{{$variation->id}}"
                            @if(request()->input('variation_id', null) == $variation->id)
                                selected
                            @endif
                            >{{$variation->product_variation->name}} - {{$variation->name}} ({{$variation->sub_sku}})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <input type="hidden" id="variation_id" name="variation_id" value="{{$product->variations->first()->id}}">
        @endif
    @endcomponent
    <div class="row">
        {{-- <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('search_box', __('lang_v1.search_box') . ':') !!}
                {!! Form::text('search_box', null, [
                    'placeholder' => __('lang_v1.search_box'),
                    'class' => 'form-control',
                    'id' => 'filter_search_box'
                ]) !!}
            </div>
        </div> --}}

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sales_form_no', __('lang_v1.sales_form_number') . ':') !!}
                {!! Form::text('sales_form_no', null, [
                    'class' => 'form-control',
                    'placeholder' => __('lang_v1.sales_form_number'),
                    'id' => 'filter_sales_form'
                ]) !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('purchase_order_no', __('lang_v1.purchase_order_number') . ':') !!}
                {!! Form::text('purchase_order_no', null, [
                    'class' => 'form-control',
                    'placeholder' => __('lang_v1.purchase_order_number'),
                    'id' => 'filter_po_number'
                ]) !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('bill_no', __('lang_v1.bill_number') . ':') !!}
                {!! Form::text('bill_no', null, [
                    'class' => 'form-control',
                    'placeholder' => __('lang_v1.bill_number'),
                    'id' => 'filter_bill_number'
                ]) !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('customer_id', __('lang_v1.customer') . ':') !!}
                {!! Form::select('customer_id', $customers, null, [
                    'class' => 'form-control select2',
                    'id' => 'filter_customer',
                    'placeholder' => __('lang_v1.select_customer')
                ]) !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('supplier_id', __('lang_v1.supplier') . ':') !!}
                {!! Form::select('supplier_id', $business, null, [
                    'class' => 'form-control select2',
                    'id' => 'filter_supplier',
                    'placeholder' => __('lang_v1.select_supplier')
                ]) !!}
            </div>
        </div>
       <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('date_range', __('lang_v1.date_range') . ':') !!}
                {!! Form::text('date_range', null, [
                    'class' => 'form-control',
                    'id' => 'filter_date_range',
                    'placeholder' => __('lang_v1.select_date_range'),
                    'readonly'
                ]) !!}
            </div>
        </div>
    </div>
    @component('components.widget')
        <div id="product_stock_history" style="display: none;"></div>
    @endcomponent
    </div>
</div>

</section>
<!-- /.content -->
@endsection

@section('javascript')
   <script type="text/javascript">
        $(document).ready( function(){

            let startDate = '';
            let endDate   = '';

            // Date range picker
            $(document).on('focus', '#filter_date_range', function () {
                if ($(this).data('daterangepicker')) return;

                $(this).daterangepicker(
                    dateRangeSettings,
                    function (start, end) {
                        startDate = start.format('YYYY-MM-DD');
                        endDate   = end.format('YYYY-MM-DD');

                        $('#filter_date_range').val(
                            start.format(moment_date_format) + ' ~ ' +
                            end.format(moment_date_format)
                        );

                        $("#report_date_range").text(
                            "Date Range: " + $('#filter_date_range').val()
                        );

                        load_stock_history($('#variation_id').val(), $('#location_id').val());
                    }
                );
            });

            $('#filter_date_range').on('apply.daterangepicker', function(ev, picker) {
                if (picker.chosenLabel === 'Custom Date Range') {
                    $('.custom_date_typing_modal').modal('show');
                }
            });


             $(document).on('click', '#custom_date_apply_button', function () {
                let startDateInput =
                    $('#custom_date_from_year1').val() +
                    $('#custom_date_from_year2').val() +
                    $('#custom_date_from_year3').val() +
                    $('#custom_date_from_year4').val() +
                    "-" +
                    $('#custom_date_from_month1').val() +
                    $('#custom_date_from_month2').val() +
                    "-" +
                    $('#custom_date_from_date1').val() +
                    $('#custom_date_from_date2').val();

                let endDateInput =
                    $('#custom_date_to_year1').val() +
                    $('#custom_date_to_year2').val() +
                    $('#custom_date_to_year3').val() +
                    $('#custom_date_to_year4').val() +
                    "-" +
                    $('#custom_date_to_month1').val() +
                    $('#custom_date_to_month2').val() +
                    "-" +
                    $('#custom_date_to_date1').val() +
                    $('#custom_date_to_date2').val();

                if (startDateInput.length !== 10 || endDateInput.length !== 10) {
                    alert("Please select both start and end dates.");
                    return;
                }

                let start = moment(startDateInput, 'YYYY-MM-DD');
                let end   = moment(endDateInput, 'YYYY-MM-DD');

                if (!start.isValid() || !end.isValid() || start.isAfter(end)) {
                    alert("Invalid date range.");
                    return;
                }

                startDate = start.format('YYYY-MM-DD');
                endDate   = end.format('YYYY-MM-DD');

                const $dateRange = $('#filter_date_range');
                $dateRange.val(
                    start.format(moment_date_format) + ' ~ ' +
                    end.format(moment_date_format)
                );

                if ($dateRange.data('daterangepicker')) {
                    $dateRange.data('daterangepicker').setStartDate(start);
                    $dateRange.data('daterangepicker').setEndDate(end);
                }

                $("#report_date_range").text(
                    "Date Range: " + $dateRange.val()
                );

                $('.custom_date_typing_modal').modal('hide');

                load_stock_history($('#variation_id').val(), $('#location_id').val());
            });

            $(document).on('change keyup', '#variation_id, #location_id, #filter_search_box, #filter_sales_form, #filter_po_number, #filter_bill_number, #filter_customer, #filter_supplier, #filter_date_range', function(){
                load_stock_history($('#variation_id').val(), $('#location_id').val());
            });


            load_stock_history($('#variation_id').val(), $('#location_id').val());

            $('#product_id').select2({
                ajax: {
                    url: '/products/list-no-variation',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term, // search term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data,
                        };
                    },
                },
                minimumInputLength: 1,
                escapeMarkup: function(m) {
                    return m;
                },
            }).on('select2:select', function (e) {
                var data = e.params.data;
                window.location.href = "{{url('/')}}/products/stock-history/" + data.id
            });

             // AJAX load function
            function load_stock_history(variation_id, location_id) {
                let data = {
                    search_box: $('#filter_search_box').val(),
                    sales_form_no: $('#filter_sales_form').val(),
                    purchase_order_no: $('#filter_po_number').val(),
                    bill_no: $('#filter_bill_number').val(),
                    customer_id: $('#filter_customer').val(),
                    supplier_id: $('#filter_supplier').val(),
                    date_range: $('#filter_date_range').val(),
                };

                console.log(data);
                

                $.ajax({
                    url: '/products/stock-history/' + variation_id + "?location_id=" + location_id,
                    type: 'GET',
                    data: data,
                    dataType: 'html',
                    success: function(result) {
                        $('#product_stock_history').html(result).fadeIn();
                        __currency_convert_recursively($('#product_stock_history'));

                        $('#stock_history_table').DataTable({
                            searching: true,
                            ordering: true,
                            order: [],
                            destroy: true
                        });
                    }
                });
            }

            $(document).on('change', '#variation_id, #location_id', function(){
                load_stock_history($('#variation_id').val(), $('#location_id').val());
            });
        });

   </script>
@endsection