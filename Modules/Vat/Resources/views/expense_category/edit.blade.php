<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@update', [$expense_category->id]), 'method' => 'PUT', 'id' => 'expense_category_add_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('expense.edit_expense_category')</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('name', __('expense.category_name') . ':*') !!}
                        {!! Form::text('name', $expense_category->name, ['class' => 'form-control', 'required', 'placeholder' => __('expense.category_name')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('expense_code', __('vat::lang.expense_code') . ':*') !!}
                        {!! Form::text('expense_code', $expense_category->expense_code, ['class' => 'form-control', 'required', 'readonly', 'placeholder' => __('vat::lang.expense_code')]) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('expense_account_id', __('account.expense_account') . ':') !!}
                        {!! Form::select('expense_account_id', $expense_accounts ?? [], $expense_category->expense_account_id ?? $default_expense_account_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('payee_id', __('expense.payee') . ':') !!}
                        {!! Form::select('payee_id', $payees ?? [], $expense_category->payee_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%;']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group" style="margin-top: 8px;">
                        <label>
                            {!! Form::checkbox('vat_input_claimed', 1, !empty($expense_category->vat_input_claimed), ['class' => 'input-icheck', 'id' => 'vat_input_claimed']) !!}
                            <strong>VAT Input Claimed</strong>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
<script>
    $('.expense_category_modal').find('select.select2').select2({dropdownParent: $('.expense_category_modal'), width: '100%'});
    $('.expense_category_modal').find('.input-icheck').iCheck({checkboxClass: 'icheckbox_square-blue'});
</script>
