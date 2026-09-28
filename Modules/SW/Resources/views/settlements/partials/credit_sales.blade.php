{{--
    Credit Sales — 8047 + IS2245.

    When embedded in Payments this detailed entry panel is hidden until the user
    clicks the Credit Sales payment-type button. It keeps one authoritative
    credit-sales form and avoids the old generic Customer/Amount/Note duplicate.
--}}
@php($embeddedCreditSales = !empty($embedded_in_payments))

<div class="sw-section" id="sw_sec_credit_sales" @if($embeddedCreditSales) style="display:none;margin-top:14px" @endif>

    @unless($embeddedCreditSales)
        <div class="sw-section-head collapsed" data-toggle="collapse" data-target="#sw_body_credit_sales">
            <i class="fa fa-chevron-down sw-caret"></i>
            <span>@lang('sw::lang.credit_sales')</span>
            <span class="sw-section-total" id="sw_cs_head_total">0.00</span>
        </div>
    @endunless

    <div class="{{ $embeddedCreditSales ? '' : 'collapse' }}" id="sw_body_credit_sales">
        <div class="sw-section-body">

            {{-- IS2245: expose every requested credit-sale field after the
                 Payments -> Credit Sales button is selected. --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_cs_customer', __('sw::lang.customer') . ':') !!}
                        <div class="input-group">
                            <select class="form-control sw-select2" id="sw_cs_customer" style="width:100%">
                                <option value="">@lang('sw::lang.please_select')</option>
                            </select>
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="sw_cs_add_customer"
                                        title="@lang('sw::lang.add_customer')">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_cs_order_no', __('sw::lang.order_no') . ':') !!}
                        <input type="text" class="form-control" id="sw_cs_order_no">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_cs_order_date', __('sw::lang.order_date') . ':') !!}
                        <input type="date" class="form-control" id="sw_cs_order_date">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_cs_vehicle_select', __('sw::lang.vehicle_no') . ':') !!}
                        <select class="form-control sw-select2" id="sw_cs_vehicle_select" style="width:100%">
                            <option value="">@lang('sw::lang.choose_a_customer_first')</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_cs_product', __('sw::lang.select_product') . ':') !!}
                        <select class="form-control sw-select2" id="sw_cs_product" style="width:100%">
                            <option value="">@lang('sw::lang.please_select')</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_cs_price', __('sw::lang.unit_price') . ':') !!}
                        <input type="number" step="0.01" class="form-control text-right" id="sw_cs_price">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_cs_disc', __('sw::lang.unit_discount') . ':') !!}
                        <input type="number" step="0.01" class="form-control text-right"
                               id="sw_cs_disc" value="0.00">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_cs_qty', __('sw::lang.qty') . ':') !!}
                        <input type="number" step="0.001" class="form-control text-right"
                               id="sw_cs_qty" value="0.000">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_cs_before', __('sw::lang.before_discount') . ':') !!}
                        <input type="text" class="form-control text-right" id="sw_cs_before" readonly>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_cs_discount_amount', __('sw::lang.credit_discount_amount') . ':') !!}
                        <input type="text" class="form-control text-right" id="sw_cs_discount_amount" readonly>
                        <input type="hidden" id="sw_cs_after" value="0.00">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_cs_vehicle', __('sw::lang.enter_vehicle_no') . ':') !!}
                        <div class="input-group">
                            <input type="text" class="form-control" id="sw_cs_vehicle" autocomplete="off">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="sw_cs_save_vehicle"
                                        title="@lang('sw::lang.save_vehicle_to_customer')">
                                    <i class="fa fa-floppy-o"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="form-group">
                        {!! Form::label('sw_cs_note', __('sw::lang.payment_note') . ':') !!}
                        <input type="text" class="form-control" id="sw_cs_note">
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group" style="padding-top:25px">
                        <button type="button" class="btn btn-primary btn-block" id="sw_cs_add">
                            @lang('messages.add')
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive sw-full-table-wrap" style="margin-top:10px">
                <table class="table table-bordered table-condensed sw-lines-table sw-fit-table sw-credit-lines-table" id="sw_cs_table">
                    <thead>
                        <tr>
                            <th><span class="sw-credit-th">@lang('sw::lang.customer')</span></th>
                            <th><span class="sw-credit-th">{!! str_replace(' ', '<br>', e(__('sw::lang.order_no'))) !!}</span></th>
                            <th><span class="sw-credit-th">{!! str_replace(' ', '<br>', e(__('sw::lang.order_date'))) !!}</span></th>
                            <th><span class="sw-credit-th">{!! str_replace(' ', '<br>', e(__('sw::lang.vehicle_no'))) !!}</span></th>
                            <th><span class="sw-credit-th">@lang('sw::lang.product')</span></th>
                            <th class="text-right"><span class="sw-credit-th">@lang('sw::lang.qty')</span></th>
                            <th class="text-right"><span class="sw-credit-th">{!! str_replace(' ', '<br>', e(__('sw::lang.unit_price'))) !!}</span></th>
                            <th class="text-right"><span class="sw-credit-th">{!! str_replace(' ', '<br>', e(__('sw::lang.unit_discount'))) !!}</span></th>
                            <th class="text-right"><span class="sw-credit-th">Amount<br>Before Discount</span></th>
                            <th class="text-right"><span class="sw-credit-th">Credit Discount<br>Amount</span></th>
                            <th class="text-right"><span class="sw-credit-th">Credit Sale<br>Amount</span></th>
                            <th><span class="sw-credit-th">@lang('sw::lang.payment_note')</span></th>
                            <th class="text-center"><span class="sw-credit-th">@lang('messages.action')</span></th>
                        </tr>
                    </thead>
                    <tbody id="sw_cs_rows">
                        <tr class="sw-cs-empty">
                            <td colspan="13" class="text-center text-muted" style="padding:16px">
                                @lang('sw::lang.no_credit_sales_yet')
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray">
                            <td colspan="10" class="text-right"><strong>@lang('sw::lang.credit_sales_total')</strong></td>
                            <td class="text-right"><strong id="sw_cs_total">0.00</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div id="sw_cs_hidden_inputs" style="display:none"></div>

        </div>
    </div>
</div>

<input type="hidden" name="total_credit_sales" id="sw_cs_total_input" value="0">
