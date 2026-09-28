{{--
    Other Sales — 8044.

    Store, product, balance stock, price, quantity, discount, then +.

    Balance stock is shown but not enforced: a forecourt shop sells what is on
    the shelf, and a stock figure that has drifted should not stop the sale
    being recorded. It is there to be noticed, not to refuse.
--}}

<div class="sw-section" id="sw_sec_other_sales">

    <div class="sw-section-head collapsed" data-toggle="collapse" data-target="#sw_body_other_sales">
        <i class="fa fa-chevron-down sw-caret"></i>
        <span>@lang('sw::lang.other_sales')</span>
        <span class="sw-section-total" id="sw_os_head_total">0.00</span>
    </div>

    <div class="collapse" id="sw_body_other_sales">
        <div class="sw-section-body">

            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_os_store', __('sw::lang.store') . ':') !!}
                        <select class="form-control sw-select2" id="sw_os_store" style="width:100%">
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_os_product', __('sw::lang.select_product') . ':') !!}
                        <select class="form-control sw-select2" id="sw_os_product" style="width:100%">
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_os_stock', __('sw::lang.balance_stock') . ':') !!}
                        <input type="text" class="form-control text-right" id="sw_os_stock" readonly>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_os_price', __('sw::lang.price') . ':') !!}
                        <input type="number" step="0.01" class="form-control text-right"
                               id="sw_os_price" readonly>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_os_qty', __('sw::lang.qty') . ':') !!}
                        <input type="number" step="0.001" class="form-control text-right"
                               id="sw_os_qty" value="0.000">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_os_disc_type', __('sw::lang.discount_type') . ':') !!}
                        <select class="form-control" id="sw_os_disc_type" style="width:100%">
                            <option value="">@lang('messages.please_select')</option>
                            <option value="fixed">@lang('sw::lang.fixed')</option>
                            <option value="percentage">@lang('sw::lang.percentage')</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_os_disc', __('sw::lang.discount') . ':') !!}
                        <input type="number" step="0.001" class="form-control text-right"
                               id="sw_os_disc" value="0.000">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-success btn-block" id="sw_os_add">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="margin-top:10px">
                <table class="table table-bordered table-condensed sw-lines-table" id="sw_os_table">
                    <thead>
                        <tr>
                            <th>@lang('sw::lang.code')</th>
                            <th>@lang('sw::lang.product')</th>
                            <th>@lang('sw::lang.store')</th>
                            <th class="text-right">@lang('sw::lang.qty')</th>
                            <th class="text-right">@lang('sw::lang.price')</th>
                            <th>@lang('sw::lang.discount_type')</th>
                            <th class="text-right">@lang('sw::lang.discount_value')</th>
                            <th class="text-right">@lang('sw::lang.before_discount')</th>
                            <th class="text-right">@lang('sw::lang.amount_after_discount')</th>
                            <th class="text-center">@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tbody id="sw_os_rows">
                        <tr class="sw-os-empty">
                            <td colspan="10" class="text-center text-muted" style="padding:16px">
                                @lang('sw::lang.no_other_sales_yet')
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray">
                            <td colspan="8" class="text-right"><strong>@lang('sw::lang.other_sales_total')</strong></td>
                            <td class="text-right"><strong id="sw_os_total">0.00</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div id="sw_os_hidden_inputs" style="display:none"></div>

        </div>
    </div>
</div>

<input type="hidden" name="total_other_sales" id="sw_os_total_input" value="0">
