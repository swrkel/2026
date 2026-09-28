{{--
    Add / edit a card payment.

    Card Type is a Finance account from the "Card" group - choosing it decides
    where the money lands, which is why there is no separate account field.
--}}

<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => $row ? route('sw.daily-cards.update', $row->id) : route('sw.daily-cards.store'),
            'method' => $row ? 'put' : 'post',
            'id' => 'sw_daily_card_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">
                {{ $row ? __('sw::lang.edit_daily_card') : __('sw::lang.add_daily_card') }}
                @if ($row && $row->collection_form_no)
                    <small class="text-muted">
                        &mdash; @lang('sw::lang.collection_form_no') {{ $row->collection_form_no }}
                    </small>
                @endif
            </h4>
        </div>

        <div class="modal-body">
            <div class="row">

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('pump_operator_id', __('sw::lang.pump_operator') . ':*') !!}
                        {!! Form::select('pump_operator_id', $operators, $row->pump_operator_id ?? null, [
                            'class' => 'form-control select2', 'id' => 'sw_cdf_operator', 'required',
                            'placeholder' => __('messages.please_select'), 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('sw_shift_id', __('sw::lang.shift_no') . ':*') !!}
                        <select name="sw_shift_id" id="sw_cdf_shift" class="form-control" required>
                            @if ($row)
                                <option value="{{ $row->sw_shift_id }}" selected>{{ $row->sw_shift_no }}</option>
                            @else
                                <option value="">{{ __('sw::lang.choose_an_operator_first') }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('card_type_account_id', __('sw::lang.card_type') . ':*') !!}
                        {!! Form::select('card_type_account_id', $card_accounts,
                            $row->card_type_account_id ?? null, [
                            'class' => 'form-control select2', 'required',
                            'placeholder' => __('messages.please_select'), 'style' => 'width:100%',
                        ]) !!}
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.card_type_help')
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('amount', __('sw::lang.amount') . ':*') !!}
                        {!! Form::number('amount', $row->amount ?? null, [
                            'class' => 'form-control', 'step' => '0.01', 'min' => '0.01', 'required',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('card_number', __('sw::lang.card_number') . ':') !!}
                        {!! Form::text('card_number', $row->card_number ?? null, [
                            'class' => 'form-control', 'maxlength' => 100,
                        ]) !!}
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.card_number_help')
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('slip_no', __('sw::lang.slip_no') . ':') !!}
                        {!! Form::text('slip_no', $row->slip_no ?? null, [
                            'class' => 'form-control', 'maxlength' => 100,
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('contact_id', __('sw::lang.customer') . ':') !!}
                        <select name="contact_id" id="sw_cdf_customer" class="form-control select2"
                            style="width:100%">
                            <option value="">{{ __('sw::lang.not_linked_to_a_customer') }}</option>
                            @if ($row && $row->contact_id)
                                <option value="{{ $row->contact_id }}" selected>{{ $row->customer_name }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('card_date', __('sw::lang.date') . ':*') !!}
                        {!! Form::date('card_date',
                            $row && $row->card_date ? \Carbon\Carbon::parse($row->card_date)->format('Y-m-d') : date('Y-m-d'),
                            ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('note', __('sw::lang.note') . ':') !!}
                        {!! Form::textarea('note', $row->note ?? null, ['class' => 'form-control', 'rows' => 2]) !!}
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

<script>
$(function () {

    $('#sw_cdf_operator').on('change', function () {
        var $shift = $('#sw_cdf_shift');
        $shift.prop('disabled', true).empty().append('<option value="">{{ __('sw::lang.loading') }}</option>');

        if (!$(this).val()) {
            $shift.prop('disabled', false).empty()
                .append('<option value="">{{ __('sw::lang.choose_an_operator_first') }}</option>');
            return;
        }

        $.get('{{ route('sw.operators.shifts') }}', {
            pump_operator_id: $(this).val(),
            location_id: $('#sw_cd_location_id').val()
        }, function (rows) {
            $shift.empty();
            if (!rows.length) {
                $shift.append('<option value="">{{ __('sw::lang.no_open_shift') }}</option>');
            } else {
                $shift.append('<option value="">{{ __('messages.please_select') }}</option>');
                $.each(rows, function (i, r) { $shift.append($('<option>').val(r.id).text(r.label)); });
            }
            $shift.prop('disabled', false);
        });
    });

    // A card payment need not be against a named customer, so this is optional.
    $('#sw_cdf_customer').select2({
        ajax: {
            url: '{{ route('sw.customers.search') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data }; }
        },
        minimumInputLength: 1,
        allowClear: true,
        placeholder: '{{ __('sw::lang.not_linked_to_a_customer') }}'
    });

});
</script>
