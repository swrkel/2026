@php
    use App\Account;

    $cash_account_id = Account::getAccountByAccountName('Cash')->id;
    // Add a helper boolean for showing Pump Operator option
    $is_petro_or_settlement_enabled = $settlement_access == 1 || $petro_access == 1;
@endphp

<div class="modal-dialog modal-lg" role="document" style="width: 60%">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('account.add_journal')</h4>
        </div>

        <div class="modal-body">
            {!! Form::open(['url' => action('JournalController@store'), 'method' => 'post']) !!}
            <input type="hidden" id="index" name="index" value="1">

            <div class="col-md-12">
                <div class="row">
                    <!-- Journal No -->
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('journal_id', __('account.journal_no')) !!}
                            {!! Form::text('journal_id', $journal_id, ['class' => 'form-control journal_id', 'required', 'readonly']) !!}
                        </div>
                    </div>
                    <!-- Date -->
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date', __('account.date')) !!}
                            {!! Form::text('date', null, ['class' => 'form-control journal_date', 'required']) !!}
                        </div>
                    </div>
                    <!-- Location -->
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('select_location', __('account.select_location')) !!}
                            {!! Form::select('location_id', $locations, $default_location_id, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'location_id',
                                'required',
                                'placeholder' => 'Please select',
                            ]) !!}
                        </div>
                    </div>
                    <!-- Opening Balance -->
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('is_opening_balance', __('account.opening_balance')) !!}
                            {!! Form::select('is_opening_balance', ['yes' => 'Yes', 'no' => 'No'], null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'is_opening_balance',
                                'required',
                                'placeholder' => 'Please select',
                                'disabled',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Note -->
                    <div class="{{ $settlement_access == 1 ? 'col-md-4' : 'col-md-6' }}">
                        <div class="form-group">
                            {!! Form::label('note', __('account.note')) !!}
                            {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2, 'cols' => 10, 'required']) !!}
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
                                    'supplier' => 'Supplier Ledger',
                                ] + ($is_petro_or_settlement_enabled ? ['pump_operator' => 'Pump Operator'] : []),
                                null,
                                ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'show_in_ledger'],
                            ) !!}
                        </div>
                    </div>
                </div>

                <!-- Conditional Ledger Holder -->
                <div class="row" id="ledger_holder_wrapper" hidden>
                        <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('ledger_holder', __('account.ledger_holder')) !!}
                            <select name="ledger_holder" id="ledger_holder" class="form-control select2" style="width:100%">
                                <!-- dynamically filled -->
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Show In (Credit/Debit) -->
                <div class="row" id="show_in_fields" hidden>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('show_in', __('account.show_in')) !!}
                            {!! Form::select('show_in', ['credit' => 'Credit', 'debit' => 'Debit'], null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'show_in',
                                'placeholder' => 'Please select',
                            ]) !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Journal Entry Rows -->
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-3"><b>@lang('account.select_account_type')</b></div>
                    <div class="col-md-3"><b>@lang('account.select_account')</b></div>
                    <div class="col-md-2"><b>@lang('account.debit_amount')</b></div>
                    <div class="col-md-2"><b>@lang('account.credit_amount')</b></div>
                    <div class="col-md-2">
                        <button class="btn btn-xs btn-primary add_row" style="margin-top: 7px;">+</button>
                    </div>
                </div>

                <div class="dynamic_rows"></div>

                <!-- First Row -->
                <div class="row journal_row">
                    <div class="col-md-3">
                        {!! Form::select('', $account_types, null, [
                            'id' => 'account_type0',
                            'class' => 'form-control select2 account_type_ids',
                            'style' => 'width:100%',
                            'required',
                            'placeholder' => 'Please select',
                        ]) !!}
                    </div>
                    <div class="col-md-3">
                        {!! Form::select('', [], null, [
                            'id' => 'account_id0',
                            'class' => 'form-control select2 account_ids',
                            'style' => 'width:100%',
                            'required',
                            'placeholder' => 'Please select',
                        ]) !!}
                    </div>
                    <div class="col-md-2">
                        {!! Form::text('', null, [
                            'id' => 'debit0',
                            'class' => 'debit-top form-control debit_amount0',
                            'placeholder' => __('account.amount'),
                        ]) !!}
                    </div>
                    <div class="col-md-2">
                        {!! Form::text('', null, [
                            'id' => 'credit0',
                            'class' => 'credit-top form-control credit_amount0',
                            'placeholder' => __('account.amount'),
                        ]) !!}
                    </div>
                </div>

                <!-- Second Row -->
                <div class="row journal_row">
                    <div class="col-md-3">
                        {!! Form::select('', $account_types, null, [
                            'id' => 'account_type1',
                            'class' => 'form-control select2 account_type_ids',
                            'style' => 'width:100%',
                            'required',
                            'placeholder' => 'Please select',
                        ]) !!}
                    </div>
                    <div class="col-md-3">
                        {!! Form::select('', [], null, [
                            'id' => 'account_id1',
                            'class' => 'form-control select2 account_ids',
                            'style' => 'width:100%',
                            'required',
                            'placeholder' => 'Please select',
                        ]) !!}
                    </div>
                    <div class="col-md-2">
                        {!! Form::text('', null, [
                            'id' => 'debit1',
                            'class' => 'debit-top form-control debit_amount1',
                            'placeholder' => __('account.amount'),
                        ]) !!}
                    </div>
                    <div class="col-md-2">
                        {!! Form::text('', null, [
                            'id' => 'credit1',
                            'class' => 'credit-top form-control credit_amount1',
                            'placeholder' => __('account.amount'),
                        ]) !!}
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-xs btn-primary add_row_create" data-index="1"
                            style="margin-top: 7px;">Add</button>
                    </div>
                </div>

                <!-- Totals -->
                <div class="row">
                    <div class="col-md-6">
                        <h4>@lang('account.total')</h4>
                    </div>
                    <div class="col-md-2">
                        {!! Form::text('debit_total_top', null, [
                            'class' => 'form-control debit_total_top',
                            'readonly',
                            'style' => 'width:100%;',
                        ]) !!}
                    </div>
                    <div class="col-md-2">
                        {!! Form::text('credit_total_top', null, [
                            'class' => 'form-control credit_total_top',
                            'readonly',
                            'style' => 'width:100%;',
                        ]) !!}
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="journal_details">
                        <thead>
                            <tr>
                                <th>@lang('account.account')</th>
                                <th>@lang('account.debit')</th>
                                <th>@lang('account.credit')</th>
                                <th>@lang('account.show_in_ledger')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <div class="col-md-6">
                    <h4>@lang('account.total')</h4>
                </div>
                <div class="col-md-2">
                    {!! Form::text('debit_total', null, [
                        'class' => 'form-control debit_total',
                        'readonly',
                        'style' => 'width:100%;',
                        'required',
                    ]) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('credit_total', null, [
                        'class' => 'form-control credit_total',
                        'readonly',
                        'style' => 'width:100%;',
                        'required',
                    ]) !!}
                </div>

                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary add_btn">@lang('messages.submit')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<script>
    $('.journal_date').datepicker("setDate", new Date());
    $('.select2').select2();
    $('.add_btn').attr('disabled', true);

    // Handle Ledger Holder population
    $('#show_in_ledger').on('change', function() {
        let type = $(this).val();
        let options = {};
        let $holder = $('#ledger_holder');

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
            $('#ledger_holder_wrapper').show();
            $holder.val('').prop('required', true).trigger('change');
            $('#show_in').prop('required', true).prop('disabled', false);
            $('#show_in_fields').show();
        } else {
            $('#ledger_holder_wrapper').hide();
            $('#ledger_holder').prop('required', false).val(null).trigger('change');
            $('#show_in').prop('required', false).prop('disabled', true).val('').trigger('change');
            $('#show_in_fields').hide();
        }
    });

    // ✅ Add this validation logic at the end of your script
    function validateForm() {
        let debit = parseFloat($('.debit_total_top').val()) || 0;
        let credit = parseFloat($('.credit_total_top').val()) || 0;
        let hasBaseFields = !!$('#location_id').val() && !!$('.journal_date').val() && !!$('textarea[name="note"]').val();

        if (debit > 0 && credit > 0 && debit === credit && hasBaseFields) {
            $('.add_btn').attr('disabled', false);
        } else {
            $('.add_btn').attr('disabled', true);
        }
    }

    // Trigger validation on any input/select/textarea change
    $('input, select, textarea').on('change keyup', function () {
        validateForm();
    });

    $('#show_in_ledger').trigger('change');
</script>
