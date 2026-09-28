<br>
<div class="row">
    <div class="col-md-12">
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('expense_number', __( 'settlementsw::lang.expense_number' )) !!}
                {!! Form::text('expense_number', null, ['class' => 'form-control check_pumper expense_number', 'required', 'readonly',
                'placeholder' => __(
                'settlementsw::lang.expense_number' ) ]); !!}
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                {!! Form::label('category', __('settlementsw::lang.category').':') !!}
                {!! Form::select('category', $expense_categories, null, ['class' => 'form-control check_pumper select2', 'style' => 'width: 100%;',
                'placeholder' => __('settlementsw::lang.please_select')]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('reference_no', __( 'settlementsw::lang.reference_no' )) !!}
                {!! Form::text('reference_no', null, ['class' => 'form-control check_pumper reference_no', 'required',
                'placeholder' => __(
                'settlementsw::lang.reference_no' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('expense_amount', __( 'settlementsw::lang.amount' )) !!}
                {!! Form::text('expense_amount', null, ['class' => 'form-control check_pumper expense_amount', 'required',
                'placeholder' => __(
                'settlementsw::lang.amount' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('expense_reason', __( 'settlementsw::lang.reason' )) !!}
                {!! Form::text('expense_reason', null, ['class' => 'form-control check_pumper expense_reason', 'required',
                'placeholder' => __(
                'settlementsw::lang.reason' ) ]); !!}
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                {!! Form::label('expense_account', __('settlementsw::lang.expense_account').':') !!}
                {!! Form::select('expense_account', $expense_accounts, null, ['class' => 'form-control check_pumper select2', 'style' => 'width: 100%;',
                'placeholder' => __('settlementsw::lang.please_select')]); !!}
            </div>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary" style="margin-top: 23px;">@lang('messages.add')</button>
        </div>
    </div>
</div>
<br>
<br>
<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="meter_sale_table">
            <thead>
                <tr>
                    <th>@lang('settlementsw::lang.code' )</th>
                    <th>@lang('settlementsw::lang.products' )</th>
                    <th>@lang('settlementsw::lang.pump' )</th>
                    <th>@lang('settlementsw::lang.starting_meter')</th>
                    <th>@lang('settlementsw::lang.closing_meter')</th>
                    <th>@lang('settlementsw::lang.price')</th>
                    <th>@lang('settlementsw::lang.qty' )</th>
                    <th>@lang('settlementsw::lang.discount' )</th>
                    <th>@lang('settlementsw::lang.testing_qty' )</th>
                    <th>@lang('settlementsw::lang.sub_total' )</th>
                </tr>
            </thead>
            <tbody>

            </tbody>

            <tfoot>
                <tr>
                    <td colspan="7" style="text-align: right; font-weight: bold;">@lang('settlementsw::lang.meter_sale_total') :</td>
                    <td colspan="3" style="text-align: left; font-weight: bold;">0.00</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>