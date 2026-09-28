{{--
    Other Income — 8045.

    Services: products without Manage Stock. A service has nothing to draw down,
    which is what separates it from Other Sales.

    The amount autoloads and is read-only. A permitted user can unlock it with
    the Edit button, and any change is recorded against the line - who changed a
    price matters more than that it changed.
--}}

<div class="sw-section" id="sw_sec_other_income">

    <div class="sw-section-head collapsed" data-toggle="collapse" data-target="#sw_body_other_income">
        <i class="fa fa-chevron-down sw-caret"></i>
        <span>@lang('sw::lang.other_income')</span>
        <span class="sw-section-total" id="sw_oi_head_total">0.00</span>
    </div>

    <div class="collapse" id="sw_body_other_income">
        <div class="sw-section-body">

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_oi_service', __('sw::lang.service') . ':') !!}
                        <select class="form-control sw-select2" id="sw_oi_service" style="width:100%">
                            <option value="">@lang('sw::lang.please_select')</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_oi_details', __('sw::lang.details') . ':') !!}
                        <input type="text" class="form-control" id="sw_oi_details">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_oi_qty', __('sw::lang.qty') . ':') !!}
                        <input type="number" step="0.001" class="form-control text-right"
                               id="sw_oi_qty" value="1.000">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_oi_amount', __('sw::lang.amount') . ':') !!}
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control text-right"
                                   id="sw_oi_amount" readonly>
                            {{-- Shown only to a permitted user. The button is
                                 hidden by the server, not merely disabled by the
                                 browser. --}}
                            @if (auth()->user()->can('superadmin') || auth()->user()->can('sw.other_income.edit_price'))
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" id="sw_oi_edit_price"
                                            title="@lang('sw::lang.edit_price')">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-success btn-block" id="sw_oi_add">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="margin-top:10px">
                <table class="table table-bordered table-condensed sw-lines-table" id="sw_oi_table">
                    <thead>
                        <tr>
                            <th>@lang('sw::lang.code')</th>
                            <th>@lang('sw::lang.service')</th>
                            <th>@lang('sw::lang.details')</th>
                            <th class="text-right">@lang('sw::lang.qty')</th>
                            <th class="text-right">@lang('sw::lang.amount')</th>
                            <th class="text-right">@lang('sale.total')</th>
                            <th class="text-center">@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tbody id="sw_oi_rows">
                        <tr class="sw-oi-empty">
                            <td colspan="7" class="text-center text-muted" style="padding:16px">
                                @lang('sw::lang.no_other_income_yet')
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray">
                            <td colspan="5" class="text-right"><strong>@lang('sw::lang.other_income_total')</strong></td>
                            <td class="text-right"><strong id="sw_oi_total">0.00</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div id="sw_oi_hidden_inputs" style="display:none"></div>

        </div>
    </div>
</div>

<input type="hidden" name="total_other_income" id="sw_oi_total_input" value="0">
