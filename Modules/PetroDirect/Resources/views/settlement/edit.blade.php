@extends('layouts.app')
@section('title', __('petrodirect::lang.settlement'))

@section('content')
@php
$business_id = session()->get('user.business_id');
$business_details = App\Business::find($business_id);
$currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
$meeter_precision = 3;
@endphp


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang( 'petrodirect::lang.settlement', ['contacts' => __('petrodirect::lang.mange_settlement') ])</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petrodirect::lang.settlement')</a></li>
                    <li><span>@lang( 'petrodirect::lang.settlement', ['contacts' => __('petrodirect::lang.mange_settlement') ])</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('settlement_no', __('petrodirect::lang.settlement_no') . ':') !!}
                    {!! Form::text('settlement_no', !empty($active_settlement) ? $active_settlement->settlement_no :
                    $settlement_no, ['class' => 'form-control', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, !empty($active_settlement) ?
                    $active_settlement->location_id : (!empty($default_location) ? $default_location : null), ['class'
                    => 'form-control select2', 'id' => 'location_id',
                    'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('pump_operator', __('petrodirect::lang.pump_operator').':') !!}
                    {!! Form::select('pump_operator_id', $pump_operators, !empty($active_settlement) ?
                    $active_settlement->pump_operator_id : null, ['class' => 'form-control select2', 'id' =>
                    'pump_operator_id',
                    'placeholder' => __('petrodirect::lang.all')]); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_date', __( 'petrodirect::lang.transaction_date' ) . ':*') !!}
                    {!! Form::text('transaction_date', null, ['class' =>
                    'form-control transaction_date', 'required',
                    'placeholder' => __(
                    'petrodirect::lang.transaction_date' ) ]); !!}
                </div>
            </div>
   
            
            <input type="hidden" id="is_edit" value="1">
            <input type="hidden" id="no_change" value="{{request()->no_change}}">
            <input type="hidden" id="active_settlement_id" value="{{ $active_settlement->id ?? 0 }}">

        </div>
        <div class="col-md-12">
        <div class="col-md-3">
                <div class="form-group">
                    
                {!! Form::label('shift_number', __('petrodirect::lang.shift_number') . ':') !!}
                {!! Form::text('shift_number', $shift_number[0]['shift_number'] ?? '', ['class' => 'form-control', 'readonly']) !!}

                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('work_shift', __('petrodirect::lang.work_shift').':') !!}
                    {!! Form::select('work_shift[]', $wrok_shifts, !empty($active_settlement) ?
                    $active_settlement->work_shift : null, ['class' => 'form-control select2', 'id' => 'work_shift',
                    'multiple']); !!}
                </div>
            </div>
           

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('note', __('petrodirect::lang.note') . ':') !!}
                    {!! Form::text('note', !empty($active_settlement) ? $active_settlement->note : null, ['class' =>
                    'form-control note',
                    'placeholder' => __(
                    'petrodirect::lang.note' ) ]); !!}
                </div>
            </div>
        </div>
                 

            @endcomponent
       
    </div>

    {{-- IS2344: Fast local tab switcher for Edit / Edit No Change.
         Do NOT use a MutationObserver here. On tenants where the global Manage
         Page scanner re-applies permission markers, an observer that removes
         those markers can enter an endless add/remove loop and make the page
         appear to keep loading. The Edit route is already authorised; this
         switcher only owns these five top-level PetroDirect settlement panes. --}}
    <script id="petrodirect-direct-settlement-edit-tab-fix">
        window.petroDirectOpenDirectSettlementTab = function (selector, control, clickEvent) {
            if (clickEvent) {
                clickEvent.preventDefault();
            }

            var root = control && control.closest
                ? control.closest('.direct-settlement-main-tabs')
                : document.querySelector('.direct-settlement-main-tabs');
            if (!root || !selector || selector.charAt(0) !== '#') return false;

            var nav = root.querySelector(':scope > .nav-tabs');
            var content = root.querySelector(':scope > .tab-content');
            var pane = content ? content.querySelector(':scope > ' + selector) : null;
            if (!nav || !content || !pane) return false;

            Array.prototype.forEach.call(nav.children, function (item) {
                item.classList.remove('active', 'show');
                var tabControl = item.querySelector('[data-petrodirect-main-tab]');
                if (!tabControl) return;

                /* Anchors do not honour the HTML disabled attribute. Clear only
                   stale auto-generated markers on these already-authorised
                   PetroDirect page controls; nested payment permissions are not
                   touched. */
                item.classList.remove('business-manage-disabled-tab', 'disabled');
                item.removeAttribute('disabled');
                item.removeAttribute('data-auto-permission-blocked');
                item.removeAttribute('aria-hidden');

                tabControl.classList.remove('active', 'business-manage-disabled-tab', 'disabled');
                tabControl.removeAttribute('disabled');
                tabControl.removeAttribute('data-auto-permission-blocked');
                tabControl.removeAttribute('aria-hidden');
                tabControl.setAttribute('aria-selected', 'false');
            });

            Array.prototype.forEach.call(content.children, function (candidate) {
                if (!candidate.classList.contains('direct-settlement-main-pane')) return;
                candidate.classList.remove('active', 'in', 'show');
                candidate.style.setProperty('display', 'none', 'important');
                candidate.style.setProperty('visibility', 'hidden', 'important');
                candidate.style.setProperty('pointer-events', 'none', 'important');
                candidate.setAttribute('aria-hidden', 'true');
            });

            /* Clear a stale marker only when its pane is actually opened. This
               avoids the previous continuous observer-vs-scanner mutation loop. */
            pane.classList.remove('business-manage-disabled-tab', 'disabled');
            pane.removeAttribute('disabled');
            pane.removeAttribute('data-auto-permission-blocked');

            if (control && control.parentNode) {
                control.parentNode.classList.add('active', 'show');
            }
            if (control) {
                control.classList.add('active');
                control.setAttribute('aria-selected', 'true');
            }

            pane.classList.add('active', 'in', 'show');
            pane.style.setProperty('display', 'block', 'important');
            pane.style.setProperty('visibility', 'visible', 'important');
            pane.style.setProperty('pointer-events', 'auto', 'important');
            pane.setAttribute('aria-hidden', 'false');

            var belowBox = document.getElementById('below_box');
            if (belowBox) belowBox.classList.remove('hide');

            if (window.jQuery) {
                /* Preserve existing PetroDirect per-tab loaders/calculations. */
                window.jQuery(document).trigger(
                    'petrodirect:direct-settlement-tab-shown',
                    [selector]
                );

                window.setTimeout(function () {
                    try {
                        if (window.jQuery.fn && window.jQuery.fn.dataTable) {
                            var $pane = window.jQuery(pane);
                            $pane.find('table.dataTable').each(function () {
                                try {
                                    window.jQuery(this).DataTable().columns.adjust();
                                } catch (ignore) {}
                            });
                        }
                    } catch (ignore) {}

                    if (selector === '#payment_tab'
                        && typeof window.calculate_payment_tab_total === 'function') {
                        window.calculate_payment_tab_total();
                    }
                }, 0);
            }

            /* Do not stop propagation. Existing PetroDirect delegated handlers
               (for example Other Sale loaders) are still allowed to run. */
            return false;
        };
    </script>

    @component('components.widget', ['class' => 'box-primary below_box', 'id' => 'below_box'])
    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs direct-settlement-main-tabs">
                <ul class="nav nav-tabs no-erp-global-tabs no-exf-tabs" role="tablist">
                    <li class="active">
                        <a href="#meter_sale_tab" role="tab" data-petrodirect-main-tab="1" class="direct-main-tab-control meter_sale_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#meter_sale_tab', this, event);">
                            <i class="fa fa-tachometer"></i> <strong>@lang('petrodirect::lang.meter_sale')</strong>
                        </a>
                    </li>

                    <li>
                        <a href="#other_sale_tab" role="tab" data-petrodirect-main-tab="1" class="direct-main-tab-control other_sale_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#other_sale_tab', this, event);">
                            <i class="fa fa-balance-scale"></i> <strong>@lang('petrodirect::lang.other_sale')</strong>
                        </a>
                    </li>

                    <li>
                        <a href="#other_income_tab" role="tab" data-petrodirect-main-tab="1" class="direct-main-tab-control other_income_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#other_income_tab', this, event);">
                            <i class="fa fa-thermometer"></i> <strong>@lang('petrodirect::lang.other_income')</strong>
                        </a>
                    </li>

                    <li>
                        <a href="#customer_payment_tab" role="tab" data-petrodirect-main-tab="1" class="direct-main-tab-control customer_payment_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#customer_payment_tab', this, event);">
                            <i class="fa fa-money"></i> <strong>@lang('petrodirect::lang.customer_payment')</strong>
                        </a>
                    </li>

                    <li>
                        <a href="#payment_tab" role="tab" data-petrodirect-main-tab="1" class="direct-main-tab-control payment_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#payment_tab', this, event);">
                            <i class="fa fa-book"></i> <strong>@lang('petrodirect::lang.payment')</strong>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane direct-settlement-main-pane active in show" id="meter_sale_tab">
                        @include('petrodirect::settlement.partials.meter_sale', ['edit' => 1])
                    </div>

                    <div class="tab-pane direct-settlement-main-pane" id="other_sale_tab">
                        @include('petrodirect::settlement.partials.other_sale')
                    </div>

                    <div class="tab-pane direct-settlement-main-pane" id="other_income_tab">
                        @include('petrodirect::settlement.partials.other_income')
                    </div>

                    <div class="tab-pane direct-settlement-main-pane" id="customer_payment_tab">
                        @include('petrodirect::settlement.partials.customer_payment')
                    </div>

                    <div class="tab-pane direct-settlement-main-pane" id="payment_tab">
                        @include('petrodirect::settlement.partials.payment')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endcomponent

    <style id="petrodirect-direct-settlement-edit-tab-visibility">
        .direct-settlement-main-tabs > .nav-tabs > li > .direct-main-tab-control {
            border: 1px solid transparent;
            border-radius: 0;
            color: #fff !important;
            cursor: pointer;
            display: block;
            font: inherit;
            line-height: 1.42857143;
            margin-right: 2px;
            padding: 15px 22px;
            position: relative;
            white-space: nowrap;
        }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(1) > .direct-main-tab-control { background: #2f80ed; }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(2) > .direct-main-tab-control { background: #9b0f8f; }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(3) > .direct-main-tab-control { background: #3486a8; }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(4) > .direct-main-tab-control { background: #247a2e; }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(5) > .direct-main-tab-control { background: #f4a51c; }
        .direct-settlement-main-tabs > .nav-tabs > li.active > .direct-main-tab-control {
            background: #fff !important;
            border-color: #ddd #ddd #fff;
            color: #222 !important;
        }
        /* IS2344: top-level controls on an already-authorised Edit page must
           stay clickable even if a tenant's global scanner adds a stale marker.
           This is CSS-only and does not mutate the DOM continuously. */
        .direct-settlement-main-tabs > .nav-tabs > li,
        .direct-settlement-main-tabs > .nav-tabs > li.business-manage-disabled-tab {
            display: block !important;
            pointer-events: auto !important;
            opacity: 1 !important;
            cursor: pointer !important;
        }
        .direct-settlement-main-tabs > .nav-tabs > li > .direct-main-tab-control,
        .direct-settlement-main-tabs > .nav-tabs > li > .direct-main-tab-control.business-manage-disabled-tab {
            display: block !important;
            pointer-events: auto !important;
            opacity: 1 !important;
            cursor: pointer !important;
        }
        .direct-settlement-main-tabs > .tab-content > .direct-settlement-main-pane {
            display: none !important;
        }
        .direct-settlement-main-tabs > .tab-content > .direct-settlement-main-pane.active,
        .direct-settlement-main-tabs > .tab-content > .direct-settlement-main-pane.active.business-manage-disabled-tab {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
    </style>

    {{-- IS2344: no MutationObserver here by design. The previous observer
         could loop against the tenant/global permission scanner and freeze the page. --}}

    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade add_payment" role="dialog" aria-labelledby="gridSystemModalLabel" style="overflow-y: auto;">
    </div>
    <div class="modal fade preview_settlement" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    @include('petrodirect::settlement.partials.mechanical_meter_entry')
    <div id="settlement_print"></div>
    
</section>
<!-- /.content -->

<div class="modal fade edit_disabled" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog">
      <div class="modal-content">

        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">@lang('petrodirect::lang.edit_disabled')</h4>
        </div>

        <!-- Modal Body -->
        <div class="modal-body" style="padding: 50px">
          <p class="text-bold">@lang('petrodirect::lang.edit_disabled_exp')</p>
          <p class="text-center">{!! $can_edit_details[1] !!}</p>
        </div>

      </div>
    </div>
  </div>


    @include('petrodirect::partials.global_tab_standard')
@endsection
@section('javascript')
@include('petrodirect::settlement.partials.payment_tab_controller')
<script src="{{ route('petrodirect.assets.js', ['file' => 'app.js']) }}?v={{ @filemtime(base_path('Modules/PetroDirect/Resources/assets/js/app.js')) ?: 1 }}"></script>
<script src="{{ route('petrodirect.assets.js', ['file' => 'payment.js']) }}?v=20260731"></script>
<script src="{{ route('petrodirect.assets.js', ['file' => 'petro_payment.js']) }}?v=20260731"></script>
<script>
    $(document).on("click", ".credit_sale_add_updated", function () {
  console.log('789');

  var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;
  if (credit_total_amount <= 0) {
    toastr.error("Please enter amount");
    return false;
  }
  var credit_sale_customer_id = $("#credit_sale_customer_id").val();
  var customer_name = $("#credit_sale_customer_id :selected").text();
  var credit_sale_product_id = $("#credit_sale_product_id").val();
  var credit_sale_product_name = $("#credit_sale_product_id :selected").text();
  if (
    $("#customer_reference_one_time").val() !== "" &&
    $("#customer_reference_one_time").val() !== null &&
    $("#customer_reference_one_time").val() !== undefined
  ) {
    var customer_reference = $("#customer_reference_one_time").val();
  } else {
    var customer_reference = $("#customer_reference").val();
  }
  var settlement_no = $("#settlement_no").val();
  var order_date = $("#order_date").val();
  var order_number = $("#order_number").val();

  var credit_sale_price = __read_number($("#unit_price"));
  var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;
  var credit_sale_qty = __read_number($("#credit_sale_qty")) ?? 0;
  var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;
  var credit_sub_total = credit_total_amount - credit_total_discount;

  var outstanding = $(".current_outstanding").text();
  var credit_limit = $(".credit_limit").text();
  var credit_note = $("#credit_note").val();
  var is_edit = $("#is_edit").val() ?? 0;

  $.ajax({
    method: "post",
    url: "/petrodirect/settlement/payment/save-credit-sale-payment",
    data: {
      active_settlement_id: $('#active_settlement_id').val(),
      settlement_no: settlement_no,
      scsp_id: $("#scsp_id").val(),
      customer_id: credit_sale_customer_id,
      product_id: credit_sale_product_id,
      order_number: order_number,
      order_date: order_date,

      price: credit_sale_price,
      unit_discount: credit_unit_discount,
      qty: credit_sale_qty,
      amount: credit_total_amount,
      sub_total: credit_sub_total,
      total_discount: credit_total_discount,
      outstanding: outstanding,
      credit_limit: credit_limit,
      customer_reference: customer_reference,
      note: credit_note,
      is_edit: is_edit,
    },
    success: function (result) {
      if (!result.success) {
        toastr.error(result.msg);
      } else {
        settlement_credit_sale_payment_id =
          result.settlement_credit_sale_payment_id;
        add_payment_updated(credit_total_amount - credit_total_discount);
        $("#credit_sale_table tbody").prepend(
          `
                    <tr> 
                        <td>` +
            customer_name +
            `</td>
                        <td>` +
            outstanding +
            `</td>
                        <td>` +
            credit_limit +
            `</td>
                        <td>` +
            order_number +
            `</td>
                        <td>` +
            order_date +
            `</td>
                        <td>` +
            customer_reference +
            `</td>
                        <td>` +
            credit_sale_product_name +
            `</td>
                        <td>` +
            __number_f(credit_sale_price, false, false, __currency_precision) +
            `</td>
                        <td>` +
            __number_f(credit_sale_qty, false, false, __currency_precision) +
            `</td>
                        <td class="credit_sale_amount">` +
            __number_f(
              credit_total_amount,
              false,
              false,
              __currency_precision
            ) +
            `</td>
                        
                        <td class="credit_tbl_discount_amount">` +
            __number_f(
              credit_total_discount,
              false,
              false,
              __currency_precision
            ) +
            `</td>
                        <td class="credit_tbl_total_amount">` +
            __number_f(credit_sub_total, false, false, __currency_precision) +
            `</td>
                        
                        
                        <td>` +
            credit_note +
            `</td>
                        <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petrodirect/settlement/payment/delete-credit-sale-payment/` +
            settlement_credit_sale_payment_id +
            `"><i class="fa fa-times"></i></button>
                        </td>
                    </tr>
                `
        );
        $("#customer_reference_one_time").val("").trigger("change");
        $(".credit_sale_fields").val("");
        $(".cash_fields").val("");
        $("#credit_sale_product_id").trigger("change");
        $("#order_number").val(order_number);
        calculateTotal(
          "#credit_sale_table",
          ".credit_sale_amount",
          ".credit_sale_total"
        );
        calculateTotal(
          "#credit_sale_table",
          ".credit_tbl_discount_amount",
          ".credit_tb_discount_total"
        );
        calculateTotal(
          "#credit_sale_table",
          ".credit_tbl_total_amount",
          ".credit_tbl_amount_total"
        );
      }
    },
  });
});

function add_payment_updated(add_amount) {
    add_amount = parseFloat(add_amount);
    total_balance = parseFloat($("#total_balance").val());
    total_paid = parseFloat($("#total_paid").val());
    total_balance = total_balance + add_amount;
    console.log('total_balance',total_balance);
    total_paid = total_paid + add_amount;
    $("#total_balance").val(__number_f(total_balance, false, false, __currency_precision));
    $("#total_paid").val(total_paid);
    $(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));
    $(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));
  /* if (total_balance === 0) {
        $("#settlement_save_btn").removeClass("hide");
    } else {
        $("#settlement_save_btn").addClass("hide");
    }*/
    show_hide_excess_shortage_tab();
    calculateDenoms(add_amount);
}
</script>
<script>
    
    @if($can_edit_details[0] == 0)
        $('.edit_disabled').modal({
            backdrop: 'static',
            keyboard: false
        });
    @endif

    @if(!empty($active_settlement))
    $('.transaction_date').datepicker("setDate", "{{\Carbon::parse($active_settlement->transaction_date)->format('m/d/Y')}}");
    @else
    $('.transaction_date').datepicker("setDate", new Date());
    @endif
    $('#customer_payment_cheque_date').datepicker("setDate", new Date());
    $('#location_id').select2();
    $('#shif_time_in').datetimepicker({
        format: 'LT'
    });
    $('#shif_time_out').datetimepicker({
        format: 'LT'
    });
    $('#item').select2();
    $('#store_id').select2();


    $(document)
        .off('click.petrodirectEditPayment', '#add_payment')
        .on('click.petrodirectEditPayment', '#add_payment', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            if ($button.prop('disabled') || $button.data('loading')) {
                return false;
            }

            var baseUrl = $button.data('href') || '';
            if (!baseUrl) {
                toastr.error('Payment to Finalize URL is not available. Please refresh the page.');
                return false;
            }

            var paymentUrl;
            try {
                paymentUrl = new URL(baseUrl, window.location.origin);
            } catch (error) {
                toastr.error('Invalid Payment to Finalize URL. Please refresh the page.');
                return false;
            }

            var settlementNo = $('#settlement_no').val()
                || $button.data('settlement-no')
                || $('.settlement_no').first().text().trim();
            var activeSettlementId = $('#active_settlement_id').val()
                || $button.data('active-settlement-id')
                || 0;
            var pumpOperatorId = $('#pump_operator_id').val() || '';
            var noChange = $('#no_change').val() || '';

            paymentUrl.searchParams.set('type', 'settlement');
            paymentUrl.searchParams.set('settlement_no', settlementNo || '');
            paymentUrl.searchParams.set('active_settlement_id', activeSettlementId || '0');
            paymentUrl.searchParams.set('operator_id', pumpOperatorId);
            paymentUrl.searchParams.set('pump_operator_id', pumpOperatorId);
            paymentUrl.searchParams.set('location_id', $('#location_id').val() || '');
            paymentUrl.searchParams.set('transaction_date', $('.transaction_date').val() || '');
            paymentUrl.searchParams.set('no_change', noChange);

            // Petro Direct uses its own synthetic DST shift label. The payment
            // controller expects the Direct Settlement placeholder value 0, not
            // the human-readable DST label shown in the edit form.
            paymentUrl.searchParams.set('shift_ids', '0');
            paymentUrl.searchParams.set('direct_shift_label', $('#shift_number').val() || '');

            var url = paymentUrl.pathname + paymentUrl.search;
            var originalText = $button.html();

            $button.data('loading', true)
                .prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Loading...');

            $('.add_payment').empty().load(url, function(response, status, xhr) {
                $button.data('loading', false)
                    .prop('disabled', false)
                    .html(originalText);

                if (status === 'error' || xhr.status >= 400) {
                    var errorMessage = 'Unable to open Payment to Finalize.';
                    try {
                        var json = JSON.parse(xhr.responseText);
                        errorMessage = json.msg || json.message || errorMessage;
                    } catch (ignore) {
                        if (xhr.responseText && xhr.responseText.indexOf('<!DOCTYPE') === -1) {
                            errorMessage = xhr.responseText;
                        }
                    }
                    toastr.error(errorMessage);
                    return;
                }

                $('.add_payment').modal({
                    backdrop: 'static',
                    keyboard: false
                });

                var hasShortage = Boolean($('.add_payment #shortage_amount').val())
                    || $('.add_payment #shortage_table tbody tr').length > 0;
                var hasExcess = Boolean($('.add_payment #excess_amount').val())
                    || $('.add_payment #excess_table tbody tr').length > 0;

                $('.add_payment #excess_amount').prop('disabled', hasShortage);
                $('.add_payment .excess_amount_err').toggleClass('hidden', !hasShortage);
                $('.add_payment #shortage_amount').prop('disabled', hasExcess);
                $('.add_payment .shortage_amount_err').toggleClass('hidden', !hasExcess);

                $('.add_payment')
                    .off('input.petrodirectShortage', '#shortage_amount')
                    .on('input.petrodirectShortage', '#shortage_amount', function() {
                        var lockExcess = Boolean($(this).val())
                            || $('.add_payment #shortage_table tbody tr').length > 0;
                        $('.add_payment #excess_amount').prop('disabled', lockExcess);
                        $('.add_payment .excess_amount_err').toggleClass('hidden', !lockExcess);
                    })
                    .off('input.petrodirectExcess', '#excess_amount')
                    .on('input.petrodirectExcess', '#excess_amount', function() {
                        var lockShortage = Boolean($(this).val())
                            || $('.add_payment #excess_table tbody tr').length > 0;
                        $('.add_payment #shortage_amount').prop('disabled', lockShortage);
                        $('.add_payment .shortage_amount_err').toggleClass('hidden', !lockShortage);
                    });

                if (typeof show_hide_excess_shortage_tab === 'function') {
                    show_hide_excess_shortage_tab();
                }
            });

            return false;
        });

    $(document).on("click", ".cash_add_updated", function () {
        console.log('123');
        if ($("#cash_amount").val() == "") {
            toastr.error("Please enter amount");
            return false;
        }
        var cash_customer_id = $("#cash_customer_id").val();
        var cash_amount = $("#cash_amount").val();
        var settlement_no = $("#settlement_no").val();
        var customer_name = $("#cash_customer_id :selected").text();
        var cash_note = $("#cash_note").val();
        var is_edit = $("#is_edit").val() ?? 0;

        $.ajax({
            method: "post",
            url: "/petrodirect/settlement/payment/save-cash-payment",
            data: {
            active_settlement_id: $('#active_settlement_id').val(),
            customer_id: cash_customer_id,
            amount: cash_amount,
            settlement_no: settlement_no,
            note: cash_note,
            is_edit: is_edit,
            },
            success: function (result) {
            if (!result.success) {
                toastr.error(result.msg);
            } else {
                if ($("#calculate_cash").is(":checked")) {
                $(".denoms_totals").hide();
                $(".cash_to_disable").hide();
                $("#cash_amount").prop("readonly", true);
                } else {
                $(".denoms_totals").show();
                $(".cash_to_disable").show();
                $("#cash_amount").prop("readonly", false);
                }

                console.log("here is cash add data ==>", result);
                settlement_cash_payment_id = result.settlement_cash_payment_id;
                add_payment(cash_amount);
                $("#cash_table tbody").append(
                `
                            <tr> 
                                <td>` +
                    customer_name +
                    `</td>
                                <td class="cash_amount">` +
                    __number_f(cash_amount, false, false, __currency_precision) +
                    `</td>
                                <td>` +
                    cash_note +
                    `</td>
                                <td><button type="button" class="btn btn-xs btn-danger delete_cash_payment" data-href="/petrodirect/settlement/payment/delete-cash-payment/` +
                    settlement_cash_payment_id +
                    `"><i class="fa fa-times"></i></button>
                                </td>
                            </tr>
                        `
                );
                $(".cash_fields").val("");
                calculateTotal("#cash_table", ".cash_amount", ".cash_total");
            }
            },
        });
    });
    $(document).on("click", ".excess_add_btn", function () {
        console.log('function called')
        var excess_amount_input = $("#excess_amount").val();
        var excess_note = $("#excess_note").val();
        if (excess_amount_input == "") {
            toastr.error("Please enter amount");
            return false;
        } else {
            if (excess_amount_input > 0) {
                toastr.error("Please enter the amount with a negative symbol");
                return false;
            }
        }
        var settlement_no = $("#settlement_no").val();
        var excess_amount = $("#excess_amount").val();
        var is_edit = $("#is_edit").val() ?? 0;
        
        $.ajax({
            method: "post",
            url: "/petrodirect/settlement/payment/save-excess-payment",
            data: {
                active_settlement_id: $('#active_settlement_id').val(),
                settlement_no: settlement_no,
                amount: excess_amount,
                note: excess_note,
                is_edit: is_edit
            },
            success: function (result) {
                console.log(result.success)
                if (!result.success) {
                    toastr.error(result.msg);
                } else {
                    
                    settlement_excess_payment_id = result.settlement_excess_payment_id;
                    $("#excess_table tbody").append(
                        `
                        <tr> 
                            <td></td>
                            <td class="excess_amount">` +
                            __number_f(excess_amount, false, false, __currency_precision) +
                            `</td>
                            <td>` +
                            excess_note +
                            `</td>
                            <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/petrodirect/settlement/payment/delete-excess-payment/` +
                            settlement_excess_payment_id +
                            `"><i class="fa fa-times"></i></button>
                            </td>
                        </tr>
                    `
                    );
                    console.log('working');
                    $(".excess_fields").val("");
                    $(".cash_fields").val("");
                    
                    $("#excess_number").val(result.excess_number);
                    calculateTotal("#excess_table", ".excess_amount", ".excess_total");
                    add_payment(excess_amount);
                }
                console.log('result',result)
            },
        });
    });
    $(document).on("click", ".credit_sale_add", function () {
            if ($("#credit_sale_amount").val() == "") {
                toastr.error("Please enter amount");
                return false;
            }
            var credit_sale_customer_id = $("#credit_sale_customer_id").val();
            var customer_name = $("#credit_sale_customer_id :selected").text();
            var credit_sale_product_id = $("#credit_sale_product_id").val();
            var credit_sale_product_name = $("#credit_sale_product_id :selected").text();
            if ($("#customer_reference_one_time").val() !== "" && $("#customer_reference_one_time").val() !== null && $("#customer_reference_one_time").val() !== undefined) {
                var customer_reference = $("#customer_reference_one_time").val();
            } else {
                var customer_reference = $("#customer_reference").val();
            }
            var settlement_no = $("#settlement_no").val();
            var order_date = $("#order_date").val();
            var order_number = $("#order_number").val();
            
            var credit_sale_price = __read_number($("#unit_price"));
            var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;
            var credit_sale_qty = __read_number($("#credit_sale_qty")) ?? 0;
            var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;
            var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;
            var credit_sub_total = __read_number($("#credit_sale_amount")) ?? 0;
            
            var outstanding = $(".current_outstanding").text();
            var credit_limit = $(".credit_limit").text();
            var credit_note = $("#credit_note").val();
            var is_edit = $("#is_edit").val() ?? 0;
            
            $.ajax({
                method: "post",
                url: "/petrodirect/settlement/payment/save-credit-sale-payment",
                data: {
                    active_settlement_id: $('#active_settlement_id').val(),
                    settlement_no: settlement_no,
                    scsp_id: $("#scsp_id").val(),
                    customer_id: credit_sale_customer_id,
                    product_id: credit_sale_product_id,
                    order_number: order_number,
                    order_date: order_date,
                    
                    price: credit_sale_price,
                    unit_discount: credit_unit_discount,
                    qty: credit_sale_qty,
                    amount: credit_total_amount,
                    sub_total: credit_sub_total,
                    total_discount: credit_total_discount,
                    outstanding: outstanding,
                    credit_limit: credit_limit,
                    customer_reference: customer_reference,
                    note: credit_note,
                    is_edit: is_edit
                },
                success: function (result) {
                    if (!result.success) {
                        toastr.error(result.msg);
                    } else {
                        settlement_credit_sale_payment_id = result.settlement_credit_sale_payment_id;
                        add_payment(credit_total_amount-credit_total_discount);
                        $("#credit_sale_table tbody").prepend(
                            `
                            <tr> 
                                <td>` +
                                customer_name +
                                `</td>
                                <td>` +
                                outstanding +
                                `</td>
                                <td>` +
                                credit_limit +
                                `</td>
                                <td>` +
                                order_number +
                                `</td>
                                <td>` +
                                order_date +
                                `</td>
                                <td>` +
                                customer_reference +
                                `</td>
                                <td>` +
                                credit_sale_product_name +
                                `</td>
                                <td>` +
                                __number_f(credit_sale_price, false, false, __currency_precision) +
                                `</td>
                                <td>` +
                                __number_f(credit_sale_qty, false, false, __currency_precision) +
                                `</td>
                                <td class="credit_sale_amount">` +
                                __number_f(credit_total_amount, false, false, __currency_precision) +
                                `</td>
                                
                                <td class="credit_tbl_discount_amount">` +
                                __number_f(credit_total_discount, false, false, __currency_precision) +
                                `</td>
                                <td class="credit_tbl_total_amount">` +
                                __number_f(credit_sub_total, false, false, __currency_precision) +
                                `</td>
                                
                                
                                <td>` +
                            credit_note +
                                `</td>
                                <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petrodirect/settlement/payment/delete-credit-sale-payment/` +
                                settlement_credit_sale_payment_id +
                                `"><i class="fa fa-times"></i></button>
                                </td>
                            </tr>
                        `
                        );
                        $("#customer_reference_one_time").val("").trigger("change");
                        $(".credit_sale_fields").val("");
                        $(".cash_fields").val("");
                        $("#credit_sale_product_id").trigger('change');
                        $("#order_number").val(order_number);
                        calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");
                        calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");
                        calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");
                        
                    }
                },
            });
    });

    $('#note, #work_shift, #transaction_date, #pump_operator_id, #location_id').change(function() {
        $.ajax({
            method: 'put',
            url: "{{route('petrodirect.settlement.update', $active_settlement->id)}}",
            data: {
                active_settlement_id: $('#active_settlement_id').val(),
                note: $('#note').val(),
                work_shift: $('#work_shift').val(),
                transaction_date: $('#transaction_date').val(),
                pump_operator_id: $('#pump_operator_id').val(),
                location_id: $('#location_id').val()
            },
            success: function(result) {
                if (result.success == 1) {
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    })


    $('#card_customer_id').select2();
    $('#work_shift').select2();
    $('#customer_payment_customer_id').select2();
    $('#settlement_print').css('visibility', 'hidden');
</script>


<script>
    $(document).on('click', '#save_edit_price_other_income_btn', function() {
        var edit_price = $('#other_income_edit_price').val();

        $('#other_income_price').val(edit_price);
        $('#other_income_edit_price').val('0');
        $('#edit_price_other_income').modal('hide');
    });

    $('#other_sale_qty').change(function() {
        if (parseFloat($(this).val()) > parseFloat($('#balance_stock').val())) {
            toastr.error('Out of Stock');
            $(this).val('').focus();
        }
    })

</script>
@endsection
