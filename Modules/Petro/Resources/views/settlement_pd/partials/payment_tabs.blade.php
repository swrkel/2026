
<style id="s383-petro-white-button-text-fix">
/* S383: Settlement/Add Payment buttons - default text must be white in all Petro modules. */
.settlement_tabs .nav-tabs > li > a,
.settlement_tabs .nav-tabs > li > a span,
.payment_tabs .nav-tabs > li > a,
.payment_tabs .nav-tabs > li > a span,
.payment_tabs .btn,
.payment_tabs .btn *,
.settlement_tabs .btn,
.settlement_tabs .btn *,
#settlement_save_btn,
#settlement_save_btn *,
#payment_review_btn,
#payment_review_btn *,
.btn_meter_sale_cancel,
.btn_meter_sale_cancel *,
.btn_update_meter_sale,
.btn_update_meter_sale * {
    color: #ffffff !important;
}

/* Only disabled/default grey buttons may keep their normal contrast. */
.settlement_tabs .btn-default,
.settlement_tabs .btn-default *,
.payment_tabs .btn-default,
.payment_tabs .btn-default * {
    color: #333333 !important;
}

/* Keep selected tab readable only where the tab itself intentionally becomes white. */
.settlement_tabs .nav-tabs > li.active > a,
.settlement_tabs .nav-tabs > li.active > a span,
.payment_tabs .nav-tabs > li.active > a,
.payment_tabs .nav-tabs > li.active > a span,
.settlement_tabs .nav-tabs > li > a.active,
.settlement_tabs .nav-tabs > li > a.active span,
.payment_tabs .nav-tabs > li > a.active,
.payment_tabs .nav-tabs > li > a.active span {
    color: #000000 !important;
}
</style>
<style>
    .notice_card{
        color: {{$font_color}} !important;
        font-family:  {!! $font_family !!} !important;
        background-color: {{$background_color}} !important;
        font-size:  {{$font_size}}px !important;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="settlement_tabs">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#cash_tab" class="tabs cash_tab" data-toggle="tab">
                        <i class="fa fa-money"></i> 
                        @if(empty($package_details['rename_cash_tab']))
                            <strong>@lang('petro::lang.cash')</strong>
                        @else
                            <strong>@lang('petro::lang.sale_amount')</strong>
                        @endif
                    </a>
                </li>
                
                <li>
                    <a href="#cash_deposit_tab" class="tabs cash_deposit_tab" style="" data-toggle="tab">
                        <i class="fa fa-credit-card"></i> <strong>
                            @lang('petro::lang.cash_deposit') </strong>
                    </a>
                </li>

                <li>
                    <a href="#pos_sales_tab" class="tabs pos_sales_tab" style="" data-toggle="tab">
                        <i class="fa fa-desktop"></i> <strong>
                            @lang('petro::lang.pos_sales') </strong>
                    </a>
                </li>
           
                <li>
                    <a href="#cards_tab" class="tabs cards_tab" style="" data-toggle="tab">
                        <i class="fa fa-credit-card"></i> <strong>
                            @lang('petro::lang.cards') </strong>
                    </a>
                </li>

                <li>
                    <a href="#cheques_tab" class="tabs cheques_tab" style="" data-toggle="tab">
                        <i class="fa fa-pencil"></i> <strong>
                            @lang('petro::lang.cheques') </strong>
                    </a>
                </li>

                <li>
                    <a href="#expense_tab" class="tabs expense_tab" style="" data-toggle="tab">
                        <i class="fa fa-bell-o"></i> <strong>
                            @lang('petro::lang.expneses') </strong>
                    </a>
                </li>

                <li>
                    <a href="#shortage_tab" class="tabs shortage_tab" style="" data-toggle="tab">
                        <i class="fa fa-thermometer-O"></i> <strong>
                            @lang('petro::lang.shortage') </strong>
                    </a>
                </li>
            
                <li>
                    <a href="#excess_tab" class="tabs excess_tab" style="" data-toggle="tab">
                        <i class="fa fa-thermometer-full"></i> <strong>
                            @lang('petro::lang.excess') </strong>
                    </a>
                </li>

                <li>
                    <a href="#credit_sales_tab" class="tabs credit_sales_tab" style="" data-toggle="tab">
                        <i class="fa fa-credit-card-alt"></i> <strong>
                            @lang('petro::lang.credit_sales') </strong>
                    </a>
                </li>
            
                <li>
                    <a href="#loan_payments_tab" class="tabs loan_payments_tab" style="" data-toggle="tab">
                        <i class="fa fa-credit-card-alt"></i> <strong>
                            @lang('petro::lang.loan_payments') </strong>
                    </a>
                </li>
                
                <li>
                    <a href="#drawing_payments_tab" class="tabs drawing_payments_tab" style="" data-toggle="tab">
                        <i class="fa fa-credit-card-alt"></i> <strong>
                            @lang('petro::lang.drawing_payments') </strong>
                    </a>
                </li>
                <li>
                    <a href="#settlement_customer_loans" class="tabs settlement_customer_loans_tab" style="" data-toggle="tab">
                        <i class="fa fa-credit-card-alt"></i> <strong>
                            @lang('petro::lang.customer_loans') </strong>
                    </a>
                </li>
                
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="cash_tab">
                    
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_cash']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.cash')
                    </div>
                    
                </div>
                
                <div class="tab-pane" id="cash_deposit_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_cash_deposit']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.cash_deposit')
                    </div>
                    
                </div>

                <div class="tab-pane" id="pos_sales_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_pos_sales']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.pos_sales')
                    </div>
                    
                </div>

                <div class="tab-pane" id="cards_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_cards']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.cards')
                    </div>
                   
                </div>

                <div class="tab-pane" id="cheques_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_cheques']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.cheques')
                    </div>
                   
                </div>

                <div class="tab-pane" id="expense_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_expenses']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.expense')
                    </div>
                   
                </div>

                <div class="tab-pane" id="shortage_tab">
                     @php $class = ""; @endphp
                    @if(!empty($package_details['ns_shortage']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.shortage')
                    </div>
                   
                </div>

                <div class="tab-pane" id="excess_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_excess']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.excess')
                    </div>
                   
                </div>

                <div class="tab-pane" id="credit_sales_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_credit_sales']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.credit_sales')
                    </div>
                   
                </div>
                
                <div class="tab-pane" id="loan_payments_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_loan_payments']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.loan_payments')
                    </div>
                    
                   
                </div>
                
                <div class="tab-pane" id="drawing_payments_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_drawing_payments']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.owners_drawings')
                    </div>
                    
                   
                </div>
                
                <div class="tab-pane" id="settlement_customer_loans">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_customer_loans']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petro::settlement.partials.payment_tabs.customer_loans')
                    </div>
                    
                   
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
      $('ul.nav li').click(function() {
        $('ul.nav li').removeClass('active'); // remove the active class from all li elements
        $(this).addClass('active'); // add the active class to the clicked li element
      });
    });
</script>
<style id="s385-force-settlement-button-text-white">
/* S385: focused fix requested by user.
   Settlement/Add Payment button text must be WHITE on Add Settlement and Add Payment pages.
   No functional logic changed. */
.settlement_tabs .btn,
.settlement_tabs .btn:link,
.settlement_tabs .btn:visited,
.settlement_tabs .btn:hover,
.settlement_tabs .btn:focus,
.settlement_tabs .btn:active,
.settlement_tabs .btn.active,
.settlement_tabs .btn.selected,
.settlement_tabs .btn.is-active,
.settlement_tabs .btn *,
.payment_tabs .btn,
.payment_tabs .btn:link,
.payment_tabs .btn:visited,
.payment_tabs .btn:hover,
.payment_tabs .btn:focus,
.payment_tabs .btn:active,
.payment_tabs .btn.active,
.payment_tabs .btn.selected,
.payment_tabs .btn.is-active,
.payment_tabs .btn *,
#settlement_form .btn,
#settlement_form .btn:link,
#settlement_form .btn:visited,
#settlement_form .btn:hover,
#settlement_form .btn:focus,
#settlement_form .btn:active,
#settlement_form .btn.active,
#settlement_form .btn.selected,
#settlement_form .btn.is-active,
#settlement_form .btn *,
#settlement_save_btn,
#settlement_save_btn:link,
#settlement_save_btn:visited,
#settlement_save_btn:hover,
#settlement_save_btn:focus,
#settlement_save_btn:active,
#settlement_save_btn.active,
#settlement_save_btn *,
#payment_review_btn,
#payment_review_btn:link,
#payment_review_btn:visited,
#payment_review_btn:hover,
#payment_review_btn:focus,
#payment_review_btn:active,
#payment_review_btn.active,
#payment_review_btn *,
.btn_meter_sale_cancel,
.btn_meter_sale_cancel *,
.btn_update_meter_sale,
.btn_update_meter_sale *,
.btn-modal.btn,
.btn-modal.btn *,
button.btn,
button.btn *,
a.btn,
a.btn *,
input.btn {
    color: #ffffff !important;
}
</style>
