{{--
    Meter Sales — 8043.

    An entry form, a + button, and a table of what has been added. Not a grid of
    every pump: a shift covers the pumps that were worked, which is rarely all
    of them, and an operator should not have to skip past rows that do not apply.

    Rows live in the browser until the settlement is saved. Nothing is written
    until then, so an abandoned settlement leaves nothing behind.
--}}

<div class="sw-section" id="sw_sec_meter_sales">

    <div class="sw-section-head" data-toggle="collapse" data-target="#sw_body_meter_sales">
        <i class="fa fa-chevron-down sw-caret"></i>
        <span>@lang('sw::lang.meter_sales')</span>
        <span class="sw-section-total" id="sw_meter_head_total">0.00</span>
    </div>

    <div class="collapse in" id="sw_body_meter_sales">
        <div class="sw-section-body">

            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_ms_pump', __('sw::lang.pump_no') . ':') !!}
                        <select class="form-control sw-select2" id="sw_ms_pump" style="width:100%">
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_ms_start', __('sw::lang.pump_starting_meter') . ':') !!}
                        {{-- Autoloaded from the pump's previous closing, and read
                             only: an operator typing over it would break the
                             chain between one shift and the next. --}}
                        <input type="number" step="0.001" class="form-control text-right"
                               id="sw_ms_start" readonly>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_ms_close', __('sw::lang.pump_closing_meter') . ':') !!}
                        <input type="number" step="0.001" class="form-control text-right"
                               id="sw_ms_close" placeholder="0.000">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_ms_qty', __('sw::lang.sold_qty') . ':') !!}
                        <input type="number" step="0.001" class="form-control text-right"
                               id="sw_ms_qty" readonly value="0.000">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_ms_price', __('sw::lang.unit_price') . ':') !!}
                        <input type="number" step="0.01" class="form-control text-right"
                               id="sw_ms_price" readonly>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_ms_testing', __('sw::lang.testing_qty') . ':') !!}
                        <input type="number" step="0.001" class="form-control text-right"
                               id="sw_ms_testing" value="0.000">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_ms_disc_type', __('sw::lang.discount_type') . ':') !!}
                        <select class="form-control" id="sw_ms_disc_type" style="width:100%">
                            <option value="">@lang('messages.please_select')</option>
                            <option value="fixed">@lang('sw::lang.fixed')</option>
                            <option value="percentage">@lang('sw::lang.percentage')</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_ms_disc', __('sw::lang.discount') . ':') !!}
                        <input type="number" step="0.001" class="form-control text-right"
                               id="sw_ms_disc" value="0.000">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-success btn-block" id="sw_ms_add">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive sw-full-table-wrap" style="margin-top:10px">
                <table class="table table-bordered table-condensed sw-lines-table sw-fit-table sw-meter-lines-table" id="sw_ms_table">
                    <thead>
                        <tr>
                            <th>@lang('sw::lang.code')</th>
                            <th>@lang('sw::lang.product')</th>
                            <th>@lang('sw::lang.pump')</th>
                            <th class="text-right"><span>@lang('sw::lang.starting_meter')</span></th>
                            <th class="text-right"><span>@lang('sw::lang.reconfirm_meter')</span></th>
                            <th class="text-right">@lang('sw::lang.price')</th>
                            <th class="text-right"><span>@lang('sw::lang.sold_qty')</span></th>
                            <th><span>@lang('sw::lang.discount_type')</span></th>
                            <th class="text-right"><span>@lang('sw::lang.discount_value')</span></th>
                            <th class="text-right"><span>@lang('sw::lang.testing_qty')</span></th>
                            <th class="text-right"><span>@lang('sw::lang.total_qty')</span></th>
                            <th class="text-right"><span>@lang('sw::lang.before_discount')</span></th>
                            <th class="text-right"><span>@lang('sw::lang.amount_after_discount')</span></th>
                            <th class="text-center">@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tbody id="sw_ms_rows">
                        <tr class="sw-ms-empty">
                            <td colspan="14" class="text-center text-muted" style="padding:16px">
                                @lang('sw::lang.no_meter_lines_yet')
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray sw-meter-total-row">
                            <td colspan="9" id="sw_ms_product_totals" class="sw-meter-product-totals"></td>
                            <td colspan="3" class="text-right sw-meter-total-label">
                                <strong>@lang('sw::lang.meter_sales_total')</strong>
                            </td>
                            <td colspan="2" class="text-right sw-meter-total-amount">
                                <strong id="sw_ms_total">0.00</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Keep posted fields outside <table>; inputs directly under a
                 <tr> are invalid HTML and may be dropped by the browser. --}}
            <div id="sw_ms_hidden_inputs" style="display:none"></div>

        </div>
    </div>
</div>
