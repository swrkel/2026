<br>
<div class="row">
    <div class="col-md-12">
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('expense_number', __( 'petropd::lang.expense_number' )) !!}
                {!! Form::text('expense_number', null, ['class' => 'form-control check_pumper expense_number', 'required', 'readonly',
                'placeholder' => __(
                'petropd::lang.expense_number' ) ]); !!}
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                {!! Form::label('category', __('petropd::lang.category').':') !!}
                {!! Form::select('category', $expense_categories, null, ['class' => 'form-control check_pumper select2', 'style' => 'width: 100%;',
                'placeholder' => __('petropd::lang.please_select')]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('reference_no', __( 'petropd::lang.reference_no' )) !!}
                {!! Form::text('reference_no', null, ['class' => 'form-control check_pumper reference_no', 'required',
                'placeholder' => __(
                'petropd::lang.reference_no' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('expense_amount', __( 'petropd::lang.amount' )) !!}
                {!! Form::text('expense_amount', null, ['class' => 'form-control check_pumper expense_amount', 'required',
                'placeholder' => __(
                'petropd::lang.amount' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('expense_reason', __( 'petropd::lang.reason' )) !!}
                {!! Form::text('expense_reason', null, ['class' => 'form-control check_pumper expense_reason', 'required',
                'placeholder' => __(
                'petropd::lang.reason' ) ]); !!}
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                {!! Form::label('expense_account', __('petropd::lang.expense_account').':') !!}
                {!! Form::select('expense_account', $expense_accounts, null, ['class' => 'form-control check_pumper select2', 'style' => 'width: 100%;',
                'placeholder' => __('petropd::lang.please_select')]); !!}
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
                    <th>@lang('petropd::lang.code' )</th>
                    <th>@lang('petropd::lang.products' )</th>
                    <th>@lang('petropd::lang.pump' )</th>
                    <th>@lang('petropd::lang.starting_meter')</th>
                    <th>@lang('petropd::lang.closing_meter')</th>
                    <th>@lang('petropd::lang.price')</th>
                    <th>@lang('petropd::lang.qty' )</th>
                    <th>@lang('petropd::lang.discount' )</th>
                    <th>@lang('petropd::lang.testing_qty' )</th>
                    <th>@lang('petropd::lang.sub_total' )</th>
                </tr>
            </thead>
            <tbody>

            </tbody>

            <tfoot>
                <tr>
                    <td colspan="7" style="text-align: right; font-weight: bold;">@lang('petropd::lang.meter_sale_total') :</td>
                    <td colspan="3" style="text-align: left; font-weight: bold;">0.00</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>