

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang( 'petrogeneral::lang.daily_credit_sales', ['contacts' => __('petrogeneral::lang.mange_daily_voucher') ])</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petrogeneral::lang.daily_credit_sales')</a></li>
                    <li><span>@lang( 'petrogeneral::lang.daily_credit_sales', ['contacts' => __('petrogeneral::lang.mange_daily_voucher') ])</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('daily_voucher_location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('daily_voucher_location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('daily_voucher_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('daily_voucher_date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'daily_voucher_date_range', 'readonly']); !!}
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('dv_pump_operator', __('petrogeneral::lang.pump_operator').':') !!}
                        {!! Form::select('dv_pump_operator', $assigned_operators, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all')]); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('dv_customer_id', __('petrogeneral::lang.customer').':') !!}
                        {!! Form::select('dv_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width: 100%;','required','placeholder' => __('petrogeneral::lang.all')]); !!}
                    </div>
                </div>

                <div class="clearfix"></div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('dv_settlement_id',  __('petrogeneral::lang.settlement_no') . ':') !!}
                        {!! Form::select('dv_settlement_id', $daily_voucher_settlements, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>


                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('dv_status', __('petrogeneral::lang.status').':') !!}<br>
                        {!! Form::select('dv_status', array('pending' => 'Pending', 'completed' => 'Completed'), null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrogeneral::lang.all_your_credit_sales')])
    @slot('tool')
   <div class="box-tools pull-right">
           <button type="button" class="btn  btn-primary btn-modal"
               data-href="{{action('\Modules\PetroGeneral\Http\Controllers\DailyVoucherController@create', ['type' => 'daily_collection'])}}"
               data-container=".pump_modal">
               <i class="fa fa-plus"></i> @lang('messages.add')</button>

   </div>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="daily_credit_sale_table" style="width: 100%;">
            <thead>
            <tr>
                <th>@lang('petrogeneral::lang.action')</th>
                <th>@lang('petrogeneral::lang.date')</th>
                <th>@lang('petrogeneral::lang.location')</th>
                <th>@lang('petrogeneral::lang.time')</th>
                <th>@lang('petrogeneral::lang.pump_operator')</th>
                <th>@lang('petrogeneral::lang.shift_number')</th>
                <th>@lang('petrogeneral::lang.collection_form_no')</th>
                <th>@lang('petrogeneral::lang.payment_type')</th>
                <th>Customer</th>
                <th>@lang('petrogeneral::lang.slip_no')</th>
                <th>@lang('petrogeneral::lang.order_no')</th>
                <th>@lang('petrogeneral::lang.amount')</th>
                @if(empty($only_pumper))
                    <th>@lang('petrogeneral::lang.note')</th>
                    <th>@lang('petrogeneral::lang.edited_by')</th>
                @endif
            </tr>
            </thead>

            <tfoot>
            <tr class="bg-gray font-17 footer-total">
                <td colspan="11" class="text-right" style="color:brown">
                    <strong>@lang('sale.total'):</strong></td>
                <td style="color:brown"><span class="display_currency" id="footer_daily_credit_sale_amount"
                                              data-currency_symbol="true"></span>
                @if(empty($only_pumper))
                    <td></td>
                    <td></td>
                @endif
            </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
