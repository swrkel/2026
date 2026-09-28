<div class="vat-category-form-standard">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('name', __('expense.category_name') . ':*') !!}
                {!! Form::text('name', $expense_category->name ?? null, ['class' => 'form-control', 'required', 'placeholder' => __('expense.category_name')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('expense_code', __('vat::lang.expense_code') . ':') !!}
                {!! Form::text('expense_code', $expense_category->expense_code ?? $expense_category->code ?? null, ['class' => 'form-control', 'placeholder' => __('vat::lang.expense_code')]) !!}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('expense_account_id', __('expense.expense_account') . ':') !!}
                {!! Form::select('expense_account_id', $expense_accounts, $expense_category->expense_account_id ?? $expense_category->expense_account ?? $expense_category->account_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('payee_id', __('expense.payee') . ':') !!}
                {!! Form::select('payee_id', $payees, $expense_category->payee_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%;']) !!}
                <small class="help-block">Payees added in Cheque Writing &rarr; Manage Payee will be shown here.</small>
            </div>
        </div>
    </div>
</div>
