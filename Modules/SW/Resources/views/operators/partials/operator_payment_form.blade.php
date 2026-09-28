{{--
    Recover Shortage / Pay Excess.

    The one place in this module that posts to the ledger the moment it is
    saved. Everything else recorded during a shift waits for settlement.

    The amount defaults to the full outstanding balance but can be reduced - a
    partial recovery leaves the remainder owing rather than clearing the debt.
--}}

@php
    $isShortage = $type === 'shortage';
    $outstanding = (float) ($isShortage ? $operator->short_amount : $operator->excess_amount);
@endphp

<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => route('sw.operators.payment.store'),
            'method' => 'post',
            'id' => 'sw_operator_payment_form',
        ]) !!}

        {!! Form::hidden('pump_operator_id', $operator->id) !!}
        {!! Form::hidden('type', $type) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                {{ $isShortage ? __('sw::lang.recover_shortage') : __('sw::lang.pay_excess') }}
                &mdash; {{ $operator->name }}
            </h4>
        </div>

        <div class="modal-body">

            <div class="alert {{ $isShortage ? 'alert-warning' : 'alert-info' }}" style="font-size:13px">
                <strong>{{ __('sw::lang.outstanding') }}:</strong>
                {{ number_format($outstanding, 2) }}
                <div class="text-muted" style="margin-top:4px">
                    {{ $isShortage
                        ? __('sw::lang.recover_shortage_help')
                        : __('sw::lang.pay_excess_help') }}
                </div>
            </div>

            <div class="row">

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('amount', __('sw::lang.amount') . ':*') !!}
                        {!! Form::number('amount', number_format($outstanding, 2, '.', ''), [
                            'class' => 'form-control',
                            'step' => '0.01',
                            'min' => '0.01',
                            'max' => number_format($outstanding, 2, '.', ''),
                            'required',
                        ]) !!}
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.partial_allowed')
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('sw::lang.date') . ':*') !!}
                        {!! Form::date('transaction_date', date('Y-m-d'), [
                            'class' => 'form-control', 'required',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('payment_method', __('sw::lang.payment_method') . ':*') !!}
                        {!! Form::select('payment_method', $payment_methods, null, [
                            'class' => 'form-control select2',
                            'required',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('account_id', __('sw::lang.account') . ':*') !!}
                        {!! Form::select('account_id', $accounts, null, [
                            'class' => 'form-control select2',
                            'required',
                            'placeholder' => __('messages.please_select'),
                            'style' => 'width:100%',
                        ]) !!}
                        <span class="help-block" style="margin-bottom:0">
                            {{ $isShortage
                                ? __('sw::lang.account_receives')
                                : __('sw::lang.account_pays') }}
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('reference_no', __('sw::lang.reference') . ':') !!}
                        {!! Form::text('reference_no', null, ['class' => 'form-control', 'maxlength' => 191]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('sw_shift_no', __('sw::lang.shift_no') . ':') !!}
                        {!! Form::select('sw_shift_no', $shift_numbers, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('sw::lang.not_linked_to_a_shift'),
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('note', __('sw::lang.note') . ':') !!}
                        {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2]) !!}
                    </div>
                </div>

            </div>

            <div class="text-muted" style="font-size:12px;border-top:1px solid #eee;padding-top:10px">
                <i class="fa fa-info-circle"></i>
                {{ $isShortage ? __('sw::lang.posting_shortage') : __('sw::lang.posting_excess') }}
            </div>

        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>
