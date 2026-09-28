<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">
        {!! Form::open(['url' => action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseController@storePrefixSettings'), 'method' => 'post', 'id' => 'vat_expense_prefix_settings_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('vat::lang.prefix_and_starting_nos')</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('expense_prefix', __('vat::lang.prefix') . ':') !!}
                        {!! Form::text('expense_prefix', $expense_prefix, ['class' => 'form-control', 'placeholder' => __('vat::lang.prefix')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('expense_starting_no', __('vat::lang.starting_no') . ':*') !!}
                        {!! Form::text('expense_starting_no', $expense_starting_no, ['class' => 'form-control', 'required', 'placeholder' => __('vat::lang.starting_no')]) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
