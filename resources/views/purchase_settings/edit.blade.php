<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('purchase.edit_purchase_return_account')</h4>
        </div>
        <div class="modal-body">
            {!! Form::open(['url' => action('PurchaseSettingsController@update', [$purchase_return_account->id]), 'method' => 'put', 'id' => 'purchase_return_account_edit_form' ]) !!}
                <div class="form-group">
                    {!! Form::label('account_id', __('purchase.purchase_return_account') . ':') !!}
                    {!! Form::select('account_id', $accounts, $purchase_return_account->account_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                </div>
                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>