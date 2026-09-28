@php
$payment_methods = array(
    'Free' => 'Free',
    'Cash' => 'Cash',
    'Card' => 'Card',
    'Online Transfer' => 'Online Transfer',
    'Cheque' => 'Cheque'
);
@endphp

<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\\Modules\\Superadmin\\Http\\Controllers\\RefillBusinessController@store'), 'method' => 'post', 'id' => 'refill_business_create_form', 'class' => 'refill-business-form', 'data-form-type' => 'refill-business', 'data-form-mode' => 'create' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('superadmin::lang.refill_business')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-4">
                    {!! Form::label('date', __('lang_v1.date') . ':*') !!}
                    {!! Form::date('date', date('Y-m-d'), ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.date')]) !!}
                </div>

                <div class="form-group col-md-4">
                    {!! Form::label('business_id', __('superadmin::lang.business') . ':') !!}
                    <select class="form-control sms-refill-select" name="business_id" id="add_business_id" required style="width:100%">
                        <option value="">@lang('lang_v1.please_select')</option>
                        @foreach($business as $one)
                            <option value="{{ $one->id }}" data-string="{{ $one->type }}">
                                {{ $one->name }} @if($one->type == 'client') (SMS API Client) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-4">
                    {!! Form::label('package_id', __('superadmin::lang.package') . ':') !!}
                    <select class="form-control sms-refill-select" id="package_id" name="package_id" required style="width:100%">
                        <option value="">{{ __('lang_v1.please_select') }}</option>
                        @foreach($packages as $one)
                            <option value="{{ $one->id }}" data-amount="{{ $one->amount }}" data-sms="{{ $one->no_of_sms }}">{{ $one->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="clearfix"></div>

                <div class="form-group col-md-4">
                    {!! Form::label('amount', __('superadmin::lang.amount') . ':*') !!}
                    {!! Form::text('amount', null, ['class' => 'form-control', 'disabled', 'placeholder' => __('superadmin::lang.amount')]) !!}
                </div>

                <div class="form-group col-md-4">
                    {!! Form::label('no_of_sms', __('superadmin::lang.no_of_sms') . ':*') !!}
                    {!! Form::text('no_of_sms', null, ['class' => 'form-control', 'disabled', 'placeholder' => __('superadmin::lang.no_of_sms')]) !!}
                </div>

                <div class="form-group col-md-4">
                    {!! Form::label('type', __('superadmin::lang.business_type') . ':*') !!}
                    {!! Form::text('type', null, ['class' => 'form-control', 'required', 'readonly', 'placeholder' => __('superadmin::lang.business_type'), 'id' => 'add_business_type']) !!}
                </div>

                <div class="clearfix"></div>

                <div class="form-group col-md-4">
                    {!! Form::label('expiry_date', __('superadmin::lang.expiry_date') . ':*') !!}
                    {!! Form::date('expiry_date', date('Y-m-d'), ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.date')]) !!}
                </div>

                <div class="form-group col-md-4">
                    {!! Form::label('payment_method', __('superadmin::lang.payment_method') . ':') !!}
                    <select class="form-control sms-refill-select" name="payment_method" id="payment_method" required style="width:100%">
                        <option value="">{{ __('lang_v1.please_select') }}</option>
                        @foreach($payment_methods as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-4">
                    {!! Form::label('note', __('superadmin::lang.note') . ':') !!}
                    {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2]) !!}
                </div>
            </div>

            <div class="row cheque_fields hide">
                <div class="form-group col-md-4">
                    {!! Form::label('bank_name', __('superadmin::lang.bank_name') . ':*') !!}
                    {!! Form::text('bank_name', null, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.bank_name')]) !!}
                </div>

                <div class="form-group col-md-4">
                    {!! Form::label('cheque_date', __('superadmin::lang.cheque_date') . ':*') !!}
                    {!! Form::date('cheque_date', null, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.cheque_date')]) !!}
                </div>

                <div class="form-group col-md-4">
                    {!! Form::label('cheque_no', __('superadmin::lang.cheque_no') . ':*') !!}
                    {!! Form::text('cheque_no', null, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.cheque_no')]) !!}
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

<script>
(function() {
    var $form = $('#refill_business_create_form');

    $form.find('.sms-refill-select').each(function() {
        var $select = $(this);
        if ($.fn.select2 && !$select.hasClass('select2-hidden-accessible')) {
            $select.select2({
                width: '100%',
                dropdownParent: $form.closest('.modal')
            });
        }
    });


    $form.find('#payment_method').off('change.sms_refill').on('change.sms_refill', function() {
        var isCheque = $(this).val() === 'Cheque';
        $form.find('.cheque_fields').toggleClass('hide', !isCheque);
        $form.find('.cheque_fields input').prop('required', isCheque);

        if (!isCheque) {
            $form.find('.cheque_fields input').val('');
        }
    });

    $form.find('#package_id').off('change.sms_refill').on('change.sms_refill', function() {
        var selectedOption = $(this).find('option:selected');
        var amount = selectedOption.data('amount') || 0;
        var sms = selectedOption.data('sms') || 0;

        __write_number($form.find("input[name='amount']"), amount);
        __write_number($form.find("input[name='no_of_sms']"), sms);
    });

    $form.find('#add_business_id').off('change.sms_refill').on('change.sms_refill', function() {
        var selectedOption = $(this).find('option:selected');
        $form.find('#add_business_type').val(selectedOption.data('string') || '');
    });

    $form.find('#payment_method').trigger('change.sms_refill');
})();
</script>
