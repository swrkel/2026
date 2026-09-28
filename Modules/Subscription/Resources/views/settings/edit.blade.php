<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <style>
            .select2 {
                width: 100% !important;
            }
        </style>

        {!! Form::open([
            'url' => action('\Modules\Subscription\Http\Controllers\SubscriptionSettingController@update', $subscription->id),
            'method' => 'put',
            'id' => 'edit_subscription_form',
            'enctype' => 'multipart/form-data',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('subscription::lang.subscription_settings')</h4>
        </div>

        <div class="modal-body">
            <div class="row">

                {{-- Date --}}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('subscription::lang.date')) !!}
                        {!! Form::date('transaction_date', date('Y-m-d', strtotime($subscription->transaction_date)), [
                            'class' => 'form-control transaction_date',
                            'required',
                            'readonly',
                        ]) !!}
                    </div>
                </div>

                {{-- Subscription Code (readonly) --}}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('subscription_code', __('subscription::lang.subscription_code')) !!}
                        {!! Form::text('subscription_code', $subscription->subscription_code ?? '', [
                            'class' => 'form-control',
                            'readonly',
                        ]) !!}
                    </div>
                </div>

                {{-- Subscription Product Dropdown --}}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product', __('subscription::lang.subscription_product')) !!}
                        {!! Form::select('product', $products, (string) $subscription->product, [
                            'class' => 'form-control select2',
                            'id' => 'product',
                            'placeholder' => __('subscription::lang.please_select'),
                            'required',
                        ]) !!}
                    </div>
                </div>


                {{-- Base Amount (readonly) --}}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('base_amount', __('subscription::lang.base_amount')) !!}
                        {!! Form::text('base_amount', $subscription->base_amount, [
                            'class' => 'form-control',
                            'id' => 'base_amount',
                            'readonly',
                        ]) !!}
                    </div>
                </div>
            </div>
            <div class="row">

                {{-- Subscription Cycle --}}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('subscription_cycle', __('subscription::lang.subscription_cycle')) !!}
                        {!! Form::select('subscription_cycle', $subscription_cycles, $subscription->subscription_cycle, [
                            'class' => 'form-control select2',
                            'required',
                            'placeholder' => __('subscription::lang.please_select'),
                        ]) !!}
                    </div>
                </div>

            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="save_leads_btn">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
