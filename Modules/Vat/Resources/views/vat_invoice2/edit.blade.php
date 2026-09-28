@extends('layouts.app')
@section('title', __('vat::lang.vat_invoice'))

@php
    $business_id = request()->session()->get('business.id');
    $tax_rate = \App\TaxRate::where('business_id',$business_id)->first();
    $tax = !empty($tax_rate) ? $tax_rate->amount : 0;
@endphp

@section('content')
@include('vat::vat_invoice2.partials.nav')
<style>
    table>tbody>tr>td {
        vertical-align: middle;
    }


    /* S733: hide Unit Cost and Qty completely for Service invoices. */
    #issue_customer_bill_add_table.vat2-service-mode .vat2-unit-cost-column,
    #issue_customer_bill_add_table.vat2-service-mode .vat2-qty-column {
        display: none !important;
    }

    .select2-results__option {
        padding: 8px;
    }

    .select2-container .select2-selection--single {
        height: 34px !important;
    }
    #edit_final_grand_total_words { width:100% !important; min-width:100% !important; }
    .select2-results__options { max-height:300px !important; overflow-y:auto !important; }
</style>

<div class="col-md-12">
    {!! Form::open(['method' =>
    'put', 'id' => 'issue_bill_customer_form' ])
    !!}
    <div class="row">
        <input type="hidden" id="tax_rate" value="{{$tax}}">
    
           
    <div class="col-md-3">
        <div class="form-group">
          {!! Form::label('location_id', __( 'vat::lang.location' )) !!}
          {!! Form::select('location_id', $business_locations, $invoice->location_id, ['class' => 'form-control select2', 'style' => 'width:100%;', 'required']);
          !!}
        </div>
      </div>
    
    <div class="col-md-3">
        <div class="form-group">
          {!! Form::label('prefix', __( 'vat::lang.prefix' )) !!}
          {!! Form::select('prefix_id', $prefixes, $invoice->prefix, ['class' => 'form-control select2', 'style' => 'width:100%;', 'id' => 'prefix_id', 'required', 'placeholder' =>
          __( 'vat::lang.please_select')]);
          !!}
        </div>
      </div>
      
    <div class="col-md-3">
        <div class="form-group">
          {!! Form::label('customer_bill_no', __( 'vat::lang.bill_no' )) !!}
          {!! Form::text('customer_bill_no', $invoice->customer_bill_no, ['class' => 'form-control', 'required','readonly', 'placeholder' =>
          __( 'vat::lang.bill_no')]);
          !!}
        </div>
      </div>
    
    <div class="col-md-3">
        <div class="form-group">
          {!! Form::label('customer_id', __( 'vat::lang.customer' )) !!}
          {!! Form::select('customer_id', $customers, $invoice->customer_id, ['class' => 'form-control select2', 'style' => 'width:100%;', 'required', 'placeholder' =>
          __( 'vat::lang.please_select')]);
          !!}
        </div>
      </div>
    </div>
    
    <div class="row">
        <div class="form-group col-sm-3">
            {!! Form::label('vat_number', __( 'airline::lang.customer_vat_no' ) . '') !!}
            <div class="input-group">
                <div class="input-group-btn">
                    <button type="button" class="btn btn-default bg-white btn-flat" title="{{__('airline::lang.customer_vat_no')}}">
                        <i class="fa fa-user"></i>
                    </button>
                </div>
                {!! Form::text('vat_number', null, ['class' => 'form-control', 'id' => 'customer_vat_number', 'readonly','placeholder' => __('airline::lang.customer_vat_no')]); !!}
                <input type="hidden" id="vat_btn_input">
                <span class="input-group-btn vat-btn-group hide">
                    <button type="button" class="btn btn-default bg-white btn-flat btn-vat-modal vat-btn-group-action" data-href="" data-container=".contact_modal_noreload">
                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                    </button>
                </span>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
              {!! Form::label('sub_customer', __( 'vat::lang.sub_customer' )) !!}
              {!! Form::select('sub_customer', [], null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' =>
              __( 'vat::lang.please_select')]);
              !!}
            </div>
          </div>
          
         <div class="col-md-3">
            <div class="form-group">
              {!! Form::label('invoice_to', __( 'vat::lang.invoice_to' )) !!}
              {!! Form::select('invoice_to', ['customer' => __('vat::lang.customer'),'sub_customer' => __('vat::lang.sub_customer')], $invoice->invoice_to, ['class' => 'form-control select2', 'style' => 'width:100%;']);
              !!}
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="form-group">
              {!! Form::label('voucher_order_creditlimit', __( 'vat::lang.credit_limit' )) !!}
              {!! Form::text('voucher_order_creditlimit', $invoice->credit_limit, ['class' => 'form-control', 'required','readonly', 'placeholder' =>
              __( 'vat::lang.credit_limit')]);
              !!}
            </div>
        </div>
    </div>
    
    
    <div class="row">
        
        <div class="col-md-3">
            <div class="form-group">
              {!! Form::label('voucher_order_outstanding', __( 'vat::lang.outstanding' )) !!}
              {!! Form::text('voucher_order_outstanding', @num_format($invoice->outstanding_amount), ['class' => 'form-control', 'required','readonly', 'placeholder' =>
              __( 'vat::lang.outstanding')]);
              !!}
            </div>
          </div>
        
     <div class="form-group col-sm-3">
            {!! Form::label('reference_id', __( 'vat::lang.reference' )) !!}
            <div class="input-group">
                <div class="input-group-btn">
                    <button type="button" class="btn btn-default bg-white btn-flat" style="" title="{{__('airline::lang.customer')}}">
                        <i class="fa fa-user"></i>
                    </button>
                </div>
                {!! Form::select('reference_id', $customer_ref, $invoice->reference_id, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' =>
                      __( 'vat::lang.please_select')]);
                      !!}
                <span class="input-group-btn">
                    <button type="button" style="" class="btn btn-default bg-white btn-flat btn-modal  reference-btn" data-href="{{action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@referenceQuickAdd')}}" data-container=".contact_modal">
                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                    </button>
                </span>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
              {!! Form::label('sale_type', __( 'vat::lang.sale_type' )) !!}
              {!! Form::select('sale_type', array('Product' => __('vat::lang.product'),'Service' => __('vat::lang.service')), $invoice->sale_type, ['class' => 'form-control select2', 'style' => 'width:100%;', 'required', 'placeholder' =>
              __( 'vat::lang.please_select')]);
              !!}
            </div>
         </div>
      
     
    </div>
      
       <div class="col-md-2">
            <div class="form-group">
              {!! Form::label('voucher_order_amount', __( 'vat::lang.invoice_amount' )) !!}
              {!! Form::text('voucher_order_amount', @num_format($invoice->total_amount), ['class' => 'form-control','readonly', 'required', 'placeholder' =>
              __( 'vat::lang.invoice_amount')]);
              !!}
            </div>
          </div>
        <div class="col-md-2">
            <div class="form-group">
              {!! Form::label('voucher_order_date', __( 'vat::lang.transaction_date' )) !!}
              {!! Form::text('voucher_order_date', @format_date($invoice->date), ['class' => 'form-control','readonly', 'placeholder' =>
              __( 'vat::lang.transaction_date')]);
              !!}
            </div>
      </div>
      
      <div class="col-md-2">
            <div class="form-group">
              {!! Form::label('supplied_on', __( 'vat::lang.supplied_on' )) !!}
              {!! Form::date('supplied_on', $invoice->supplied_on, ['class' => 'form-control', 'placeholder' =>
              __( 'vat::lang.supplied_on')]);
              !!}
            </div>
      </div>

      <div class="col-md-3">
          <div class="form-group">
              {!! Form::label('place_of_supply', __('vat::lang.place_of_supply')) !!}
              {!! Form::text('place_of_supply', $invoice->place_of_supply, [
                  'class' => 'form-control',
                  'placeholder' => __('vat::lang.place_of_supply')
              ]) !!}
          </div>
      </div>
       <div class="col-md-2">
        <div class="form-group">
            {!! Form::label('print_format', __('vat::lang.print_format')) !!}

            @php
                // Show all available formats when editing
                $print_options = [
                    'vat_print_2026' => __('vat::lang.vat_print_2026'),
                    'vat_print_163' => __('vat::lang.vat_print_163'),
                    'old' => __('vat::lang.old_vat_print'),
                ];
            @endphp

            {!! Form::select(
                'print_format',
                $print_options,
                in_array(($invoice->print_format ?? null), ['old', 'vat_print_2026', 'vat_print_163'], true) ? $invoice->print_format : 'vat_print_2026',
                ['class' => 'form-control']
            ) !!}
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            {!! Form::label('product_category_id', __('vat::lang.product_category')) !!}
            {!! Form::select('product_category_id', $product_categories, null, [
                'id' => 'product_category_id',
                'class' => 'form-control select2 vat2-product-category-select',
                'style' => 'width:100%;',
                'placeholder' => __('vat::lang.please_select'),
            ]) !!}
        </div>
    </div>
  

    <div class="clearfix"></div>

    <table class="table table-responsive" id="issue_customer_bill_add_table">
      <thead>
        <tr>
          <th width="20%">@lang('vat::lang.product')</th>
          <th class="vat2-unit-cost-column">@lang('vat::lang.unit_price_before_vat')</th>
          <th class="vat2-qty-column">@lang('vat::lang.qty')</th>
          <th>@lang('vat::lang.discount')</th>
           <th>@lang('vat::lang.unit_vat') {{$tax}} %</th>
          <th>@lang('vat::lang.vat')</th>
          <th>@lang('vat::lang.sub_total')</th>
          <th>@lang('vat::lang.action')</th>
        </tr>
      </thead>
      <tbody>
          @foreach($invoice_details as $key => $detail)
            <tr>
              <td>
                {!! Form::select('issue_customer_bill[product_id][]', $products, $detail->product_id, ['class' => 'form-control select2 product_id', 'style' => 'width:100%;', 'required', 'placeholder' =>
                __( 'vat::lang.please_select')]) !!}
              </td>
              <td class="vat2-unit-cost-column">
                {!! Form::hidden('issue_customer_bill[unit_price][]', $detail->unit_price, ['class' => 'form-control unit_price', 'placeholder' => __('vat::lang.unit_price'), 'readonly']) !!}
                {!! Form::hidden('issue_customer_bill[unit_price_unformatted][]', $detail->unit_price, ['class' => 'unit_price_unformatted']) !!}
                
                {!! Form::text('issue_customer_bill[unit_price_excl][]', $detail->unit_price_before_tax, ['class' => 'form-control unit_price_excl', 'readonly']) !!}
                {!! Form::hidden('issue_customer_bill[unit_price_excl_unformatted][]', $detail->unit_price_before_tax, ['class' => 'unit_price_excl_unformatted']) !!}
                
              </td>
              <td class="vat2-qty-column">
                {!! Form::text('issue_customer_bill[qty][]', $detail->qty, ['class' => 'form-control qty', 'placeholder' => __('vat::lang.qty')]) !!}
                {!! Form::hidden('issue_customer_bill[qty_unformatted][]', $detail->qty, ['class' => 'qty_unformatted']) !!}
              </td>
              
              <td>
                {!! Form::text('issue_customer_bill[discount][]',$detail->discount, ['class' => 'form-control discount', 'placeholder' => __('vat::lang.discount')]) !!}
              </td>
              
              <td>
                {!! Form::text('issue_customer_bill[unit_vat_rate][]', $detail->unit_vat_rate, ['class' => 'form-control unit_vat_rate text-right', 'placeholder' => __('vat::lang.unit_vat'), 'readonly']) !!}
                {!! Form::hidden('issue_customer_bill[unit_vat_rate_unformatted][]', $detail->unit_vat_rate, ['class' => 'unit_vat_rate_unformatted']) !!}
              </td>
              
              <td>
                {!! Form::text('issue_customer_bill[tax][]', $detail->tax, ['class' => 'form-control tax','readonly', 'placeholder' => __('vat::lang.tax')]) !!}
                {!! Form::hidden('issue_customer_bill[tax_unformatted][]', $detail->tax, ['class' => 'tax_unformatted']) !!}
              </td>
              <td>
                {!! Form::text('issue_customer_bill[sub_total][]', $detail->sub_total, ['class' => 'form-control sub_total', 'placeholder' => __('vat::lang.sub_total')]) !!}
                {!! Form::hidden('issue_customer_bill[sub_total_unformatted][]', $detail->sub_total, ['class' => 'sub_total_unformatted']) !!}
              </td>
              <td>
                  @if($key == 0)
                    <button type="button" class="btn btn-xs btn-primary add_row" style="margin-top: 6px;">+</button>
                  @else
                    <button type="button" class="btn btn-xs btn-danger remove_row" style="margin-top: 6px;">-</button>
                  @endif
                
              </td>
            </tr>
          @endforeach
        

      </tbody>
      <tfoot>
          <tr>
              <th class="vat2-summary-spacer" colspan="4"></th>
              <th class="vat2-summary-label" colspan="2">
                  @lang('vat::lang.total_invoice_amount_with_vat')
              </th>
              <th>
                  {!! Form::text('grand_total', 0, ['class' => 'form-control','readonly', 'id' => 'grand_total', 'placeholder' => __('vat::lang.total')]) !!}
              </th>
              <th></th>
          </tr>
          
          <tr>
              <th class="vat2-summary-spacer" colspan="4"></th>
              <th class="vat2-summary-label" colspan="2">
                  @lang('vat::lang.tax_base_value')
              </th>
              <th>
                  {!! Form::text('grand_total', 0, ['class' => 'form-control','readonly', 'id' => 'grand_total_with_vat', 'placeholder' => __('vat::lang.total')]) !!}
              </th>
              <th></th>
          </tr>
          
           <tr>
              <th class="vat2-summary-spacer" colspan="4"></th>
              <th class="vat2-summary-label" colspan="2">
                  @lang('vat::lang.vat') ({{$tax}}%)
                  
              </th>
              <th>
                  {!! Form::text('vat_total', 0, ['class' => 'form-control','readonly', 'id' => 'vat_total', 'placeholder' => __('vat::lang.total')]) !!}
              </th>
              <th></th>
          </tr>
          
          
          <tr>
              <th class="vat2-summary-spacer" colspan="4"></th>
              <th class="vat2-summary-label" colspan="2">
                  @lang('vat::lang.price_adjustment')
              </th>
              <th>
                  {!! Form::text('price_adjustment', @num_format($invoice->price_adjustment), ['class' => 'form-control', 'id' => 'price_adjustment', 'placeholder' => __('vat::lang.price_adjustment')]) !!}
              </th>
              <th></th>
          </tr>
          
          
          <tr>
              <th class="vat2-summary-spacer" colspan="4"></th>
              <th class="vat2-summary-label" colspan="2">
                  @lang('vat::lang.total_invoice_amount_with_vat')
              </th>
              <th>
                  {!! Form::text('final_grand_total', 0, ['class' => 'form-control','readonly', 'id' => 'final_grand_total_with_vat', 'placeholder' => __('vat::lang.total')]) !!}
              </th>
              <th></th>
          </tr>
          <tr class="vat2-amount-words-row">
              <th class="vat2-amount-words-spacer" colspan="2"></th>
              <th class="vat2-amount-words-label" colspan="2">
                  @lang('vat::lang.amount_in_words')
              </th>
              <th class="vat2-amount-words-value" colspan="3">
                  {!! Form::textarea('edit_final_grand_total_words', null, [
                      'class' => 'form-control vat2-amount-words-input',
                      'id' => 'edit_final_grand_total_words',
                      'rows' => 2,
                      'style' => 'resize:none; overflow:hidden; width:100%; min-height:58px;',
                      'placeholder' => __('vat::lang.amount_in_words'),
                  ]) !!}
              </th>
              <th></th>
          </tr>
      </tfoot>
    </table>
    </div>
    
    <div class="box-body payment_row" data-row_id="0">
      <div id="payment_rows_div">
                  @foreach($payment as $index => $one)
                          @include('sale_pos.partials.payment_row_form', ['row_index' => $index, 'payment' => $one])
                      @endforeach 
      </div>
	  </div>
    <div class="col-12" style="padding:10px;">
        <div class="form-group">
            {!! Form::label('additional_information', __('sale.additional_information_if') . ':') !!}
            {!! Form::textarea('additional_information', $invoice->additional_information, [
                'class' => 'form-control',
                'rows' => 3,
                'id' => 'additional_information'
            ]) !!}
        </div>
    </div>
     <hr>
	
	<div class="pull-right">
      <button type="submit" class="btn btn-primary" id="save_issue_bill_customer_btn" formaction="{{action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@update',[$invoice->id])}}">@lang( 'messages.save' )</button>
      <button type="submit" class="btn btn-danger" id="save_issue_bill_customer_btn"  formaction="{{action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@update',[$invoice->id])}}?is_print=true">@lang( 'messages.save_and_print' )</button>
    </div>
  

    {!! Form::close() !!}
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade contact_modal_noreload" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    </div>
</div>
@endsection
@section('javascript')
<script src="{{url('Modules/Vat/Resources/assets/js/app-new.js')}}"></script>
<script>
  $('#voucher_order_date').datepicker("setDate" , '{{@format_date($invoice->date)}}');
  $('.select2').select2();
  
  $(document).ready(function(){
      $('#customer_id').trigger('change');
  });
  
  $(document).on('click','#update_vat_number',function(e) {
            e.preventDefault();
            
            if($("#update_fields_type").val() == 'nic_number'){
                var data = {'nic_number' : $("#add_nic_number").val()};
            } else if($("#update_fields_type").val() == 'mobile'){
                var data = {'mobile' : $("#add_mobile").val()};
            }else{
                if($("#is_single_field").val() == 'yes'){
                    var data = {'vat_number' : $("#main_add_vat_number").val()};  
                }else{
                    var data = {'vat_number' : $("#add_vat_number").val()};  
                }
                
            }
            
            
            $.ajax({
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: data,
                url: $('#contact_vat_number_form').attr('action'),
                success: function(result) {
                    if (result.success == true) {
                        $('div.contact_modal_noreload').modal('hide');
                        toastr.success(result.msg);
                        
                        if($("#update_fields_type").val() == 'nic_number'){
                            $("#passport_number_text").val(result.contact.nic_number);
                        } else if($("#update_fields_type").val() == 'mobile'){
                            $("#passenger_mobile_text").val(result.contact.mobile);
                        }else{
                            $("#customer_vat_number").val(result.contact.vat_number);
                        }
                        
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
  
  $(document).on('click', '.btn-vat-modal', function(e) {

          e.preventDefault();
          
          var url = '/contacts/update-vatnumber/' + $("#vat_btn_input").val();
          
          
          console.log(url);
    
          var container = $(this).data('container');
          
          $(container).empty();
    
          $.ajax({
    
              url: url,
    
              dataType: 'html',
    
              success: function(result) {
                  // var contact = $('#default_contact_id').val();
                  $(container).html(result).modal('show');
                  // $(container).find('input#contact_id').val(contact);
              },
    
          });
    
      });
  

  $('#customer_id').change(function(){
    let customer_id = $('#customer_id :selected').val();
    
    if(customer_id){
        $(".reference-btn").show();
        $(".vat-btn-group").removeClass('hide');
        $("#vat_btn_input").val(customer_id);
    }else{
        $(".reference-btn").hide();
        $(".vat-btn-group").addClass('hide');
        $("#customer_vat_number").val("");
    }
    
        
        $.ajax({
            method: "get",
            url: "/petro/settlement/payment/get-customer-details/" + customer_id,
            data: {},
            success: function (result) {
                $("#sub_customer").empty().append(`<option selected="selected" value="">Please Select</option>`);;
                $.each(result.sub_customers, function(id, name) {
                    $("#sub_customer").append($('<option></option>').attr('value', id).text(name));
                });
                
                
                $("#voucher_order_outstanding").val(result.total_outstanding);
                $("#voucher_order_creditlimit").val(result.credit_limit);
                
                $("#customer_vat_number").val(result.vat_number);
                
                
                $('#voucher_order_amount').trigger('change');
                
                $("#sub_customer").val("{{$invoice->sub_customer}}").trigger('change');
            },
        });
        
        $.ajax({
            method: "get",
            url: "/petro/issue-customer-bill/get-customer-reference/" + customer_id,
            data: {},
            success: function (result) {
                $("#reference_id").empty().append(result);
                $("#reference_id").val("{{$invoice->reference_id}}").trigger("change");
            },
        });
        
  })
  
  
  let vatInvoice2UnitVatDecimals = 2;
  let vatInvoice2UnitVatRoundingOffRequired = false;
  let vatInvoice2SubTotalDecimals = 2;
  let vatInvoice2SubTotalRoundingOffRequired = false;

  function isEnabledPrefixOption(value) {
      return value === true || value === 1 || value === '1';
  }

  function applyPrefixVatConfig(result) {
      var unitVatDecimals = parseInt(result.unit_vat_no_of_decimals, 10);
      var subTotalDecimals = parseInt(result.sub_total_no_of_decimals, 10);
      vatInvoice2UnitVatDecimals = (!isNaN(unitVatDecimals) && unitVatDecimals >= 0) ? Math.min(unitVatDecimals, 10) : 2;
      vatInvoice2SubTotalDecimals = (!isNaN(subTotalDecimals) && subTotalDecimals >= 0) ? Math.min(subTotalDecimals, 10) : 2;
      vatInvoice2UnitVatRoundingOffRequired = isEnabledPrefixOption(result.unit_vat_rounding_off_required);
      vatInvoice2SubTotalRoundingOffRequired = isEnabledPrefixOption(result.sub_total_rounding_off_required);
  }

  const vatInvoice2CalculationDecimals = 10;

  function fullPrecisionNumber(value) {
      var numericValue = parseFloat(value || 0);
      if (isNaN(numericValue)) {
          numericValue = 0;
      }

      return Number(numericValue.toFixed(vatInvoice2CalculationDecimals));
  }

            // VAT Invoice 2 prices are tax-inclusive. Always derive the exact
            // before-tax value with: inclusive price / (1 + tax rate / 100).
            function vatInvoice2BeforeTaxFromInclusive(inclusivePrice, taxRate) {
                var inclusive = fullPrecisionNumber(inclusivePrice);
                var rate = fullPrecisionNumber(taxRate);
                var divisor = fullPrecisionNumber(1 + (rate / 100));

                if (divisor <= 0) {
                    return inclusive;
                }

                return fullPrecisionNumber(inclusive / divisor);
            }


  function writeFullPrecisionField(field, value) {
      var numericValue = fullPrecisionNumber(value);
      field.val(numericValue.toFixed(vatInvoice2CalculationDecimals).replace(/\.?0+$/, ''));
      return numericValue;
  }

  function readFullPrecisionField(field, fallbackField) {
      var value = field.length ? parseFloat(field.val()) : NaN;

      if (isNaN(value) && fallbackField && fallbackField.length) {
          value = __read_number(fallbackField);
      }

      return fullPrecisionNumber(value);
  }

  function truncateByDecimals(value, decimals) {
      var factor = Math.pow(10, decimals);
      return value < 0 ? Math.ceil(value * factor) / factor : Math.floor(value * factor) / factor;
  }

  function applyConfiguredDecimals(value, decimals, roundingRequired) {
      var numericValue = parseFloat(value || 0);
      if (isNaN(numericValue)) { numericValue = 0; }
      return roundingRequired
          ? Number(numericValue.toFixed(decimals))
          : truncateByDecimals(numericValue, decimals);
  }

  function roundByPrefixUnitVatDecimals(value) {
      return applyConfiguredDecimals(value, vatInvoice2UnitVatDecimals, vatInvoice2UnitVatRoundingOffRequired);
  }

  function roundByPrefixSubTotalDecimals(value) {
      return applyConfiguredDecimals(value, vatInvoice2SubTotalDecimals, vatInvoice2SubTotalRoundingOffRequired);
  }

  function formatByPrefixUnitVatDecimals(value) {
      return __number_f(roundByPrefixUnitVatDecimals(value), false, false, vatInvoice2UnitVatDecimals);
  }

  // Prefix settings control only visible formatting. All calculations use the
  // paired full-precision *_unformatted values.
  function writePrefixUnitVatNumber(field, value) {
      var normalised = roundByPrefixUnitVatDecimals(value);
      __write_number(field, normalised, false, vatInvoice2UnitVatDecimals);
      return normalised;
  }

  function writePrefixSubTotalNumber(field, value) {
      var normalised = roundByPrefixSubTotalDecimals(value);
      __write_number(field, normalised, false, vatInvoice2SubTotalDecimals);
      return normalised;
  }

  function recalculateAllRows() {
    $('#issue_customer_bill_add_table tbody tr').each(function() {
      calculate($(this).find('.qty'), true);
    });
  }

  let is_prefix_initial_load = true;
  $(document).on('change', '#prefix_id', function(){
    let prefix_id = $(this).val();
    if(prefix_id){
        $.ajax({
          method: 'get',
          url: '/vat-module/get-prefix2/'+prefix_id,
          data: {  },
          success: function(result) {
                if(!is_prefix_initial_load){
                    $("#customer_bill_no").val(result.bill_no);
                }
                applyPrefixVatConfig(result);
                if (is_prefix_initial_load) {
                    calculateGrandTotals();
                } else {
                    recalculateAllRows();
                }
                is_prefix_initial_load = false;
          },
        });
    }else{
        if(!is_prefix_initial_load){
            $("#customer_bill_no").val("");
        }
        vatInvoice2UnitVatDecimals = 2;
        vatInvoice2UnitVatRoundingOffRequired = false;
        vatInvoice2SubTotalDecimals = 2;
        vatInvoice2SubTotalRoundingOffRequired = false;
        if (is_prefix_initial_load) {
            calculateGrandTotals();
        } else {
            recalculateAllRows();
        }
        is_prefix_initial_load = false;
    }
  });

  $(document).ready(function () {
    $('#prefix_id').trigger('change');
  });
  
  
  $(document).ready(function () {
        /* S733: keep Edit consistent with Add when Sale Type is Service. */
        function applyVatInvoice2SaleTypeLayout() {
            var isService = String($('#sale_type').val() || '').trim().toLowerCase() === 'service';
            var $table = $('#issue_customer_bill_add_table');

            $table.toggleClass('vat2-service-mode', isService);
            $table.find('tfoot .vat2-summary-spacer').attr('colspan', isService ? 2 : 4);
            $table.find('tfoot .vat2-amount-words-spacer').attr('colspan', isService ? 1 : 2);
            $table.find('tfoot .vat2-amount-words-label').attr('colspan', isService ? 1 : 2);
        }

        $(document).on('change.vatInvoice2SaleType', '#sale_type', applyVatInvoice2SaleTypeLayout);
        applyVatInvoice2SaleTypeLayout();

        var vatInvoice2ProductCatalog = {!! json_encode($products->map(function ($name, $id) { return ['id' => (string) $id, 'name' => $name]; })->values()) !!};
        var vatInvoice2ProductCategoryLinks = {!! json_encode($product_category_links) !!};

        function productMatchesVatInvoice2Category(productId, categoryId) {
            if (!categoryId) {
                return true;
            }
            var links = vatInvoice2ProductCategoryLinks[String(productId)] || [];
            return links.map(String).indexOf(String(categoryId)) !== -1;
        }

        function refillVatInvoice2ProductSelect($select, categoryId, preserveSelection) {
            var currentValue = preserveSelection ? String($select.val() || '') : '';
            var currentAllowed = currentValue && productMatchesVatInvoice2Category(currentValue, categoryId);

            $select.empty().append($('<option>', { value: '', text: {!! json_encode(__('vat::lang.please_select')) !!} }));
            $.each(vatInvoice2ProductCatalog, function(index, product) {
                var productId = String(product.id);
                if (productMatchesVatInvoice2Category(productId, categoryId)) {
                    $select.append($('<option>', { value: productId, text: product.name }));
                }
            });

            $select.val(currentAllowed ? currentValue : '');
            $select.trigger('change.select2');
            if (preserveSelection && currentValue && !currentAllowed) {
                $select.trigger('change');
            }
        }

        function applyVatInvoice2ProductCategoryFilter(scope, preserveSelection) {
            var categoryId = String($('#product_category_id').val() || '');
            var $scope = scope ? $(scope) : $('#issue_customer_bill_add_table');
            $scope.find('select.product_id').addBack('select.product_id').each(function() {
                refillVatInvoice2ProductSelect($(this), categoryId, preserveSelection !== false);
            });
        }

        $(document).on('change.vatInvoice2Category', '#product_category_id', function() {
            applyVatInvoice2ProductCategoryFilter($('#issue_customer_bill_add_table'), true);
        });

        $(document).on('select2:open', function() {
            window.setTimeout(function() {
                $('.select2-container--open .select2-search__field').last().trigger('focus');
            }, 0);
        });

        var new_row = `
            <tr>
              <td>
                {!! Form::select('issue_customer_bill[product_id][]', $products, null, ['class' => 'form-control select2 product_id', 'style' => 'width:100%;', 'required', 'placeholder' =>
                __( 'vat::lang.please_select')]) !!}
              </td>
              <td class="vat2-unit-cost-column">
                {!! Form::hidden('issue_customer_bill[unit_price][]', 0, ['class' => 'form-control unit_price', 'placeholder' => __('vat::lang.unit_price'), 'readonly']) !!}
                {!! Form::hidden('issue_customer_bill[unit_price_unformatted][]', 0, ['class' => 'unit_price_unformatted']) !!}
                
                {!! Form::text('issue_customer_bill[unit_price_excl][]', 0, ['class' => 'form-control unit_price_excl', 'readonly']) !!}
                {!! Form::hidden('issue_customer_bill[unit_price_excl_unformatted][]', 0, ['class' => 'unit_price_excl_unformatted']) !!}
              </td>
              <td class="vat2-qty-column">
                {!! Form::text('issue_customer_bill[qty][]', 0, ['class' => 'form-control qty', 'placeholder' => __('vat::lang.qty')]) !!}
                {!! Form::hidden('issue_customer_bill[qty_unformatted][]', 0, ['class' => 'qty_unformatted']) !!}
              </td>
              <td>
                {!! Form::text('issue_customer_bill[discount][]', 0, ['class' => 'form-control discount', 'placeholder' => __('vat::lang.discount')]) !!}
              </td>
              
               <td>
                {!! Form::text('issue_customer_bill[unit_vat_rate][]', 0, ['class' => 'form-control unit_vat_rate text-right', 'placeholder' => __('vat::lang.unit_vat'), 'readonly']) !!}
                {!! Form::hidden('issue_customer_bill[unit_vat_rate_unformatted][]', 0, ['class' => 'unit_vat_rate_unformatted']) !!}
              </td>
              
              <td>
                {!! Form::text('issue_customer_bill[tax][]', 0, ['class' => 'form-control tax','readonly', 'placeholder' => __('vat::lang.tax')]) !!}
                {!! Form::hidden('issue_customer_bill[tax_unformatted][]', 0, ['class' => 'tax_unformatted']) !!}
              </td>
              <td>
                {!! Form::text('issue_customer_bill[sub_total][]', 0, ['class' => 'form-control sub_total', 'placeholder' => __('vat::lang.sub_total')]) !!}
                {!! Form::hidden('issue_customer_bill[sub_total_unformatted][]', 0, ['class' => 'sub_total_unformatted']) !!}
              </td>
              <td>
                <button type="button" class="btn btn-xs btn-danger remove_row" style="margin-top: 6px;">-</button>
              </td>
            </tr>
        `;
        $('.add_row').on('click', function () {
            var $newRow = $(new_row);
            $('#issue_customer_bill_add_table tbody').prepend($newRow);
            $('.select2').select2();
            applyVatInvoice2ProductCategoryFilter($newRow, false);
            applyVatInvoice2SaleTypeLayout();
        });
        
        
        $(document).on('click', '.remove_row', function () {
            $(this).closest('tr').remove();
            calculateGrandTotals();
        });
        
        $(document).on('change', '.unit_price, .qty, .discount', function() {
            calculate($(this), !$(this).hasClass('qty'));
        });

        // Handle Sub Total field - update Qty live as user types and lock it.
        $(document).on('input', '.sub_total', function() {
            updateQtyFromSubTotal($(this), false);
        });

        $(document).on('change', '.sub_total', function() {
            updateQtyFromSubTotal($(this), true);
        });

            function updateQtyFromSubTotal(subTotalField, triggerCalculation) {
                var row = subTotalField.closest('tr');
                var inclusiveUnitPriceField = row.find('.unit_price_unformatted');
                var beforeTaxField = row.find('.unit_price_excl');
                var beforeTaxUnformattedField = row.find('.unit_price_excl_unformatted');
                var qtyField = row.find('.qty');
                var qtyUnformattedField = row.find('.qty_unformatted');
                var discountField = row.find('.discount');
                var unitVatRateField = row.find('.unit_vat_rate');
                var unitVatRateUnformattedField = row.find('.unit_vat_rate_unformatted');
                var taxField = row.find('.tax');
                var taxUnformattedField = row.find('.tax_unformatted');
                var subTotalUnformattedField = row.find('.sub_total_unformatted');

                var taxRate = fullPrecisionNumber(__read_number($('#tax_rate')) || 0);
                var taxDivisor = fullPrecisionNumber(1 + (taxRate / 100));
                var enteredValue = (subTotalField.val() || '').toString().trim();
                var rawSubTotal = fullPrecisionNumber(__read_number(subTotalField));
                var inclusiveUnitPrice = readFullPrecisionField(inclusiveUnitPriceField, row.find('.unit_price'));
                var discount = fullPrecisionNumber(__read_number(discountField));
                var netInclusiveUnitPrice = fullPrecisionNumber(Math.max(inclusiveUnitPrice - discount, 0));

                writeFullPrecisionField(subTotalUnformattedField, rawSubTotal);

                if (triggerCalculation) {
                    writePrefixSubTotalNumber(subTotalField, rawSubTotal);
                }

                if (rawSubTotal > 0 && netInclusiveUnitPrice > 0) {
                    var rawQty = fullPrecisionNumber(rawSubTotal / netInclusiveUnitPrice);
                    var rawBeforeTaxAmount = taxDivisor > 0
                        ? fullPrecisionNumber(rawSubTotal / taxDivisor)
                        : rawSubTotal;
                    var rawVatAmount = fullPrecisionNumber(rawSubTotal - rawBeforeTaxAmount);
                    var rawUnitVat = rawQty !== 0
                        ? fullPrecisionNumber(rawVatAmount / rawQty)
                        : 0;
                    var unitPriceBeforeTax = rawQty !== 0
                        ? fullPrecisionNumber(rawBeforeTaxAmount / rawQty)
                        : vatInvoice2BeforeTaxFromInclusive(netInclusiveUnitPrice, taxRate);

                    writeFullPrecisionField(beforeTaxUnformattedField, unitPriceBeforeTax);
                    writePrefixSubTotalNumber(beforeTaxField, unitPriceBeforeTax);

                    writeFullPrecisionField(qtyUnformattedField, rawQty);
                    writePrefixUnitVatNumber(qtyField, rawQty);
                    qtyField.prop('readonly', true).data('locked', true);

                    writeFullPrecisionField(unitVatRateUnformattedField, rawUnitVat);
                    unitVatRateField.val(formatByPrefixUnitVatDecimals(rawUnitVat));

                    writeFullPrecisionField(taxUnformattedField, rawVatAmount);
                    writePrefixSubTotalNumber(taxField, rawVatAmount);

                    if (triggerCalculation) {
                        calculateGrandTotals();
                    }
                } else if (rawSubTotal === 0 || !enteredValue) {
                    qtyField.prop('readonly', false).data('locked', false);

                    writeFullPrecisionField(beforeTaxUnformattedField, 0);
                    writePrefixSubTotalNumber(beforeTaxField, 0);
                    writeFullPrecisionField(qtyUnformattedField, 0);
                    writePrefixUnitVatNumber(qtyField, 0);
                    writeFullPrecisionField(unitVatRateUnformattedField, 0);
                    writePrefixUnitVatNumber(unitVatRateField, 0);
                    writeFullPrecisionField(taxUnformattedField, 0);
                    writePrefixSubTotalNumber(taxField, 0);
                    writeFullPrecisionField(subTotalUnformattedField, 0);

                    if (triggerCalculation) {
                        calculateGrandTotals();
                    }
                }
            }

            function calculate($this, preserveRawQty) {
                var row = $($this).closest('tr');
                var inclusiveUnitPriceField = row.find('.unit_price_unformatted');
                var beforeTaxField = row.find('.unit_price_excl');
                var beforeTaxUnformattedField = row.find('.unit_price_excl_unformatted');
                var qtyField = row.find('.qty');
                var qtyUnformattedField = row.find('.qty_unformatted');
                var discountField = row.find('.discount');
                var unitVatRateField = row.find('.unit_vat_rate');
                var unitVatRateUnformattedField = row.find('.unit_vat_rate_unformatted');
                var taxField = row.find('.tax');
                var taxUnformattedField = row.find('.tax_unformatted');
                var subTotalField = row.find('.sub_total');
                var subTotalUnformattedField = row.find('.sub_total_unformatted');

                var inclusiveUnitPrice = readFullPrecisionField(inclusiveUnitPriceField, row.find('.unit_price'));
                var qty = preserveRawQty
                    ? readFullPrecisionField(qtyUnformattedField, qtyField)
                    : fullPrecisionNumber(__read_number(qtyField));
                var discount = fullPrecisionNumber(__read_number(discountField));
                var taxRate = fullPrecisionNumber(__read_number($('#tax_rate')) || 0);
                var taxDivisor = fullPrecisionNumber(1 + (taxRate / 100));
                var netInclusiveUnitPrice = fullPrecisionNumber(Math.max(inclusiveUnitPrice - discount, 0));

                writeFullPrecisionField(qtyUnformattedField, qty);
                writePrefixUnitVatNumber(qtyField, qty);

                var rawSubTotal = fullPrecisionNumber(netInclusiveUnitPrice * qty);
                var rawBeforeTaxAmount = taxDivisor > 0
                    ? fullPrecisionNumber(rawSubTotal / taxDivisor)
                    : rawSubTotal;
                var rawVat = fullPrecisionNumber(rawSubTotal - rawBeforeTaxAmount);
                var rawUnitVat = qty !== 0 ? fullPrecisionNumber(rawVat / qty) : 0;
                var unitPriceBeforeTax = qty !== 0
                    ? fullPrecisionNumber(rawBeforeTaxAmount / qty)
                    : vatInvoice2BeforeTaxFromInclusive(netInclusiveUnitPrice, taxRate);

                writeFullPrecisionField(beforeTaxUnformattedField, unitPriceBeforeTax);
                writePrefixSubTotalNumber(beforeTaxField, unitPriceBeforeTax);

                writeFullPrecisionField(unitVatRateUnformattedField, rawUnitVat);
                unitVatRateField.val(formatByPrefixUnitVatDecimals(rawUnitVat));

                writeFullPrecisionField(taxUnformattedField, rawVat);
                writePrefixSubTotalNumber(taxField, rawVat);

                writeFullPrecisionField(subTotalUnformattedField, rawSubTotal);
                writePrefixSubTotalNumber(subTotalField, rawSubTotal);

                calculateGrandTotals();
            }

            $(document).on('change', '.product_id', function() {
            var $productSelect = $(this);
            var productId = $productSelect.val();
            var row = $productSelect.closest('tr');
            var unitPriceField = row.find('.unit_price');
            var unitPriceUnformattedField = row.find('.unit_price_unformatted');
            var unitPriceExclField = row.find('.unit_price_excl');
            var unitPriceExclUnformattedField = row.find('.unit_price_excl_unformatted');
            var qtyField = row.find('.qty');
            var qtyUnformattedField = row.find('.qty_unformatted');

            if (!productId) {
                __write_number(unitPriceField, 0);
                writeFullPrecisionField(unitPriceUnformattedField, 0);
                __write_number(unitPriceExclField, 0);
                writeFullPrecisionField(unitPriceExclUnformattedField, 0);
                calculate(qtyField, true);
                return;
            }

            $.ajax({
                url: '/vat-module/vat-invoice2/product-details/' + encodeURIComponent(productId),
                type: 'GET',
                cache: false,
                success: function(data) {
                    if (String($productSelect.val() || '') !== String(productId)) {
                        return;
                    }

                    var unitPrice = fullPrecisionNumber(data.unit_price || 0);
                        var taxRate = fullPrecisionNumber(__read_number($('#tax_rate')) || data.tax_rate || 0);
                        var unitPriceExcl = vatInvoice2BeforeTaxFromInclusive(unitPrice, taxRate);

                    __write_number(unitPriceField, unitPrice);
                    writeFullPrecisionField(unitPriceUnformattedField, unitPrice);
                    writePrefixSubTotalNumber(unitPriceExclField, unitPriceExcl);
                    writeFullPrecisionField(unitPriceExclUnformattedField, unitPriceExcl);

                    var currentQty = readFullPrecisionField(qtyUnformattedField, qtyField);
                    if (!currentQty || currentQty <= 0) {
                        currentQty = 1;
                        writeFullPrecisionField(qtyUnformattedField, currentQty);
                        writePrefixUnitVatNumber(qtyField, currentQty);
                    }

                    calculate(qtyField, true);
                },
                error: function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Unable to load the selected product details.');
                    }
                }
            });
        });

        $(document).on('change', '#price_adjustment', function () {
            calculateGrandTotals();
        });

        // Simple number to words function (supports integers only)
        function numberToWordsProfessional(amount) {
            amount = Number(amount);
            if (isNaN(amount)) return '';

            let number = Math.floor(amount);
            let fraction = Math.round((amount - number) * 100);

            // Handle rounding overflow
            if (fraction === 100) {
                number += 1;
                fraction = 0;
            }

            const a = [
                '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen',
                'Eighteen', 'Nineteen'
            ];
            const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            function inWords(n) {
                if (n === 0) return 'Zero';
                if (n < 20) return a[n];
                if (n < 100)
                    return b[Math.floor(n / 10)] + (n % 10 ? ' ' + a[n % 10] : '');
                if (n < 1000)
                    return a[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + inWords(n % 100) : '');
                if (n < 1000000)
                    return inWords(Math.floor(n / 1000)) + ' Thousand' + (n % 1000 ? ' ' + inWords(n % 1000) : '');
                if (n < 1000000000)
                    return inWords(Math.floor(n / 1000000)) + ' Million' + (n % 1000000 ? ' ' + inWords(n % 1000000) : '');
                return 'Number Too Large';
            }

            let words = 'Rupees ' + inWords(number);

            if (fraction > 0) {
                words += ' and Cents ' + inWords(fraction);
            }

            words += ' Only';

            return words;
        }
        
        function calculateGrandTotals() {
            var vatTotal = 0;
            var grandTotal = 0;
            var priceAdjustment = fullPrecisionNumber(__read_number($('#price_adjustment')));

            $('#issue_customer_bill_add_table tbody tr').each(function() {
                var row = $(this);
                vatTotal = fullPrecisionNumber(
                    vatTotal + readFullPrecisionField(row.find('.tax_unformatted'), row.find('.tax'))
                );
                grandTotal = fullPrecisionNumber(
                    grandTotal + readFullPrecisionField(row.find('.sub_total_unformatted'), row.find('.sub_total'))
                );
            });

            // Tax Base Value must use normal nearest-cent rounding because
            // the field is displayed to 2 decimals. VAT is then exactly:
            // Total Invoice Amount (with VAT) - rounded Tax Base Value.
            function roundVatInvoice2Money2(value) {
                var n = parseFloat(value || 0);
                if (isNaN(n)) { n = 0; }
                return Math.round((n + Number.EPSILON) * 100) / 100;
            }

            var taxRate = fullPrecisionNumber(__read_number($('#tax_rate')) || 0);
            var taxBaseValue = taxRate > 0
                ? roundVatInvoice2Money2(grandTotal / (1 + (taxRate / 100)))
                : roundVatInvoice2Money2(grandTotal);
            var vatAmount = roundVatInvoice2Money2(grandTotal - taxBaseValue);
            var finalGrandTotal = fullPrecisionNumber(grandTotal + priceAdjustment);

            __write_number($('#vat_total'), vatAmount, false, 2);
            writePrefixSubTotalNumber($('#grand_total'), grandTotal);
            __write_number($('#grand_total_with_vat'), taxBaseValue, false, 2);
            writePrefixSubTotalNumber($('#final_grand_total_with_vat'), finalGrandTotal);
            writePrefixSubTotalNumber($('#voucher_order_amount'), grandTotal);
            writePrefixSubTotalNumber($('#amount_0'), finalGrandTotal);
            var $wordsField = $('#edit_final_grand_total_words');
            $wordsField.val(numberToWordsProfessional(finalGrandTotal));
            $wordsField.css('height', 'auto');
            if ($wordsField[0]) {
                $wordsField.css('height', Math.max(58, $wordsField[0].scrollHeight) + 'px');
            }
            $('#amount_0').attr('readonly', true);
            $('#voucher_order_amount').trigger('change');
        }

        
    });
    
    $(document).ready(function(){
      // Keep stored invoice line prices intact on initial edit load.
      $(".payment_types_dropdown").trigger('change');
  })
  
</script>
@endsection
