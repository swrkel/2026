@php
    use App\Account;
    $cash_account_id = Account::getAccountByAccountName('Cash')->id;
    $is_petro_or_settlement_enabled = $settlement_access == 1 || $petro_access == 1;
@endphp

<div class="modal-dialog modal-md" role="document" style="width: 70%;">
    <div class="modal-content">

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('account.edit_journal')</h4>
        </div>

        <div class="modal-body">
            {!! Form::open(['url' => action('JournalController@update', $journal->id), 'method' => 'put' ]) !!}
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        {{-- Journal ID --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('journal_id', __('account.journal_no')) !!}
                                {!! Form::text('journal_id', $journal->journal_id, [
                                    'class' => 'form-control journal_id',
                                    'required', 'readonly'
                                ]) !!}
                            </div>
                        </div>

                        {{-- Date --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('date', __('account.date')) !!}
                                {!! Form::text('date', null, [
                                    'class' => 'form-control journal_date',
                                    'required'
                                ]) !!}
                            </div>
                        </div>

                        {{-- Location --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('select_location', __('account.select_location')) !!}
                                {!! Form::select('location_id', $locations, $journal->location_id, [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'id' => 'location_id',
                                    'required',
                                    'placeholder' => 'Please select'
                                ]) !!}
                            </div>
                        </div>

                        {{-- Opening Balance --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('is_opening_balance', __('account.opening_balance')) !!}
                                {!! Form::select('is_opening_balance', ['yes' => 'Yes', 'no' => 'No'], $journal->is_opening_balance, [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'id' => 'is_opening_balance',
                                    'required',
                                    'disabled'
                                ]) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Note -->
                        <div class="{{ $settlement_access == 1 ? 'col-md-4' : 'col-md-6' }}">
                            <div class="form-group">
                                {!! Form::label('note', __('account.note')) !!}
                                {!! Form::textarea('note', $journal->note, ['class' => 'form-control', 'rows' => 2, 'cols' => 10,'required']) !!}
                            </div>
                        </div>

                        <!-- Show in Ledger -->
                        <div class="{{ $settlement_access == 1 ? 'col-md-4' : 'col-md-6' }}">
                            <div class="form-group">
                                {!! Form::label('show_in_ledger', __('account.show_in_ledger')) !!}
                                {!! Form::select(
                                    'show_in_ledger',
                                [
                                    'no' => 'No need to show',
                                    'customer' => 'Customer Ledger',
                                    'supplier' => 'Supplier Ledger'
                                    ] + ($is_petro_or_settlement_enabled ? ['pump_operator' => 'Pump Operator'] : []),
                                    $journal->show_in_ledger,
                                    ['class' => 'form-control select2','style' => 'width:100%', 'id' => 'show_in_ledger']
                                ) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Conditional Ledger Holder -->
                    <div class="row" id="ledger_holder_wrapper" hidden>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('ledger_holder', __('account.ledger_holder')) !!}
                                {!! Form::select('ledger_holder', [], $ledger_holder, [
                                    'class' => 'form-control select2',
                                    'id' => 'ledger_holder','style' => 'width:100%'
                                ]) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Show In (Debit/Credit) -->
                    <div class="row" id="show_in_fields" hidden>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('show_in', __('account.show_in')) !!}
                                {!! Form::select('show_in',
                                    ['debit' => __('account.debit'), 'credit' => __('account.credit')],
                                    $journal->show_in,
                                    ['class' => 'form-control select2', 'id' => 'show_in', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]
                                ) !!}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Existing journal rows --}}
                @php $i = 0; @endphp
                <div class="col-md-12 journal_rows">
                    <div class="col-md-3">{!! Form::label('account_type_id', __('account.select_account_type')) !!}</div>
                    <div class="col-md-3">{!! Form::label('account_id', __('account.select_account')) !!}</div>
                    <div class="col-md-3">{!! Form::label('amount', __('account.debit_amount')) !!}</div>
                    <div class="col-md-3">{!! Form::label('amount', __('account.credit_amount')) !!}</div>
                    <div class="clearfix"></div>
                    <div class="dynamic_rows"></div>

                    @foreach ($journals as $item)
                        <input type="hidden" name="journal[{{$i}}][id]" value="{{$item->id}}">
                        <div class="row journal_row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::select('journal['.$i.'][account_type_id]', $account_types, $item->account_type_id, [
                                        'class' => 'form-control select2 account_type_ids',
                                        'style' => 'width:100%',
                                        'required',
                                        'placeholder' => 'Please select'
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::select('journal['.$i.'][account_id]', $accounts, $item->account_id, [
                                        'class' => 'form-control select2 account_ids',
                                        'style' => 'width:100%',
                                        'required',
                                        'placeholder' => 'Please select'
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    {!! Form::text('journal['.$i.'][debit_amount]', $item->debit_amount, [
                                        'class' => 'form-control debit-top debit debit_amount'.$i,
                                        'placeholder' => __('account.amount')
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    {!! Form::text('journal['.$i.'][credit_amount]', $item->credit_amount, [
                                        'class' => 'form-control credit credit-top credit_amount'.$i,
                                        'placeholder' => __('account.amount')
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-2">
                                @if($i==0)
                                    <button class="btn btn-xs btn-primary add_row" data-index="{{$i}}" style="margin-top:7px;">+</button>
                                @endif
                            </div>
                        </div>
                        @php $i++; @endphp
                    @endforeach
                </div>
                <input type="hidden" id="index" name="index" value="{{$i-1}}">

                {{-- Footer --}}
                <div class="modal-footer">
                    <div class="col-md-6"><h4>@lang('account.total')</h4></div>
                    <div class="col-md-2">{!! Form::text('debit_total', null, ['class' => 'form-control debit_total', 'readonly']) !!}</div>
                    <div class="col-md-2">{!! Form::text('credit_total', null, ['class' => 'form-control credit_total', 'readonly']) !!}</div>
                    <div class="clearfix"></div>
                    <button type="submit" class="btn btn-primary add_btn">@lang('messages.update')</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>

            {!! Form::close() !!}
        </div>
    </div>
</div>

<script>
    $('.journal_date').datepicker().datepicker("setDate", "{{@format_date($journal->date)}}");
    $('.account_ids, .select2').select2();

    $(document).ready(function() {
        calculate_total();
        update_disables();
        toggleShowInLedger();
    });

    function update_disables(){
        $('.journal_row').each(function() {
            var debit_top = $(this).find('.debit-top');
            var credit_top = $(this).find('.credit-top');

            if (debit_top.val()) {
                credit_top.attr('disabled', 'disabled');
            } else if (credit_top.val()) {
                debit_top.attr('disabled', 'disabled');
            } else {
                debit_top.removeAttr('disabled');
                credit_top.removeAttr('disabled');
            }
        });
    }

    $('body').on('click', '.remove_row', function(e) {
        e.preventDefault();
        $(this).closest('div.row').remove();
        calculate_total();
    });

    // Show/hide based on ledger type
    function toggleShowInLedger(){
        let type = $('#show_in_ledger').val();
        let options = {};

        if (type === 'customer') {
            options = @json($customers);
        } else if (type === 'supplier') {
            options = @json($suppliers);
        } else if (type === 'pump_operator') {
            options = @json($pump_operators);
        }

        if (type !== 'no' && type !== null && type !== '') {
            let $holder = $('#ledger_holder');
            $holder.empty();
            $holder.append(new Option('Please select', ''));
            $.each(options, function(key, value) {
                $holder.append(new Option(value, key));
            });
            $holder.val('{{$ledger_holder}}').trigger('change');
            $('#ledger_holder_wrapper').show();
            $holder.prop('required', true);
            $('#show_in').prop('required', true).prop('disabled', false);
            $('#show_in_fields').show();
        } else {
            $('#ledger_holder_wrapper').hide();
            $('#ledger_holder').prop('required', false).val(null).trigger('change');
            $('#show_in').prop('required', false).prop('disabled', true).val('').trigger('change');
            $('#show_in_fields').hide();
        }
    }

    $('#show_in_ledger').on('change', function() {
        toggleShowInLedger();
    });
</script>
