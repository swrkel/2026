{{--
    Add / edit a cheque.

    No account field: which account a cheque lands in is decided at settlement,
    not while the shift is running.
--}}

<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => $row ? route('sw.daily-cheques.update', $row->id) : route('sw.daily-cheques.store'),
            'method' => $row ? 'put' : 'post',
            'id' => 'sw_daily_cheque_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">
                {{ $row ? __('sw::lang.edit_cheque') : __('sw::lang.add_cheque') }}
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
                            'class' => 'form-control select2', 'id' => 'sw_chf_operator', 'required',
                            'placeholder' => __('messages.please_select'), 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('sw_shift_id', __('sw::lang.shift_no') . ':*') !!}
                        <select name="sw_shift_id" id="sw_chf_shift" class="form-control" required>
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
                        {!! Form::label('cheque_no', __('sw::lang.cheque_no') . ':*') !!}
                        {!! Form::text('cheque_no', $row->cheque_no ?? null, [
                            'class' => 'form-control', 'maxlength' => 60, 'required',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('bank', __('sw::lang.bank') . ':') !!}
                        {!! Form::text('bank', $row->bank ?? null, [
                            'class' => 'form-control', 'maxlength' => 191, 'list' => 'sw_chf_banks',
                        ]) !!}
                        {{-- A suggestion list, not a fixed one: the bank is free
                             text, but offering what has been typed before keeps
                             the same bank spelled the same way. --}}
                        <datalist id="sw_chf_banks"></datalist>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('cheque_date', __('sw::lang.cheque_date') . ':*') !!}
                        {!! Form::date('cheque_date',
                            $row && $row->cheque_date ? \Carbon\Carbon::parse($row->cheque_date)->format('Y-m-d') : date('Y-m-d'),
                            ['class' => 'form-control', 'id' => 'sw_chf_date', 'required']) !!}
                        <span class="help-block sw-postdated" id="sw_chf_postdated"
                            style="display:none;margin-bottom:0">
                            @lang('sw::lang.post_dated_warning')
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

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('contact_id', __('sw::lang.customer') . ':') !!}
                        <select name="contact_id" id="sw_chf_customer" class="form-control select2"
                            style="width:100%">
                            <option value="">{{ __('sw::lang.not_linked_to_a_customer') }}</option>
                            @if ($row && $row->contact_id)
                                <option value="{{ $row->contact_id }}" selected>{{ $row->customer_name }}</option>
                            @endif
                        </select>
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

    $('#sw_chf_operator').on('change', function () {
        var $shift = $('#sw_chf_shift');
        $shift.prop('disabled', true).empty().append('<option value="">{{ __('sw::lang.loading') }}</option>');

        if (!$(this).val()) {
            $shift.prop('disabled', false).empty()
                .append('<option value="">{{ __('sw::lang.choose_an_operator_first') }}</option>');
            return;
        }

        $.get('{{ route('sw.operators.shifts') }}', {
            pump_operator_id: $(this).val(),
            location_id: $('#sw_ch_location_id').val() || ''
        })
        .done(function (rows) {
            $shift.empty();
            rows = rows || [];

            if (!rows.length) {
                $shift.append('<option value="">{{ __('sw::lang.no_open_shift') }}</option>');
            } else {
                $.each(rows, function (i, r) {
                    $shift.append($('<option>').val(r.id).text(r.label));
                });

                // Daily Cheque belongs to the operator's active shift.  The
                // endpoint is already ordered newest first, so select the first
                // related open shift immediately instead of making the user
                // choose it again.
                $shift.val(String(rows[0].id)).trigger('change');
            }
        })
        .fail(function () {
            $shift.empty().append(
                $('<option>', { value: '', text: 'Unable to load shift. Please select the operator again.' })
            );
        })
        .always(function () {
            $shift.prop('disabled', false);
        });
    });

    // A post-dated cheque cannot be banked yet - say so while it is being
    // entered, rather than leaving it to be noticed at settlement.
    function swChfCheckDate() {
        var val = $('#sw_chf_date').val();
        if (!val) { $('#sw_chf_postdated').hide(); return; }

        var chosen = new Date(val + 'T00:00:00');
        var today = new Date();
        today.setHours(0, 0, 0, 0);

        $('#sw_chf_postdated').toggle(chosen > today);
    }

    $('#sw_chf_date').on('change input', swChfCheckDate);
    swChfCheckDate();

    // Banks already used, so the same bank is spelled the same way.
    $.get('{{ route('sw.daily-cheques.banks') }}', function (banks) {
        var $list = $('#sw_chf_banks');
        $.each(banks || [], function (i, b) {
            $list.append($('<option>').attr('value', b));
        });
    });

    $('#sw_chf_customer').select2({
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
