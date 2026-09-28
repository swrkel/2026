@php
    $is_petro_or_settlement_enabled = $settlement_access == 1 || $petro_access == 1;

    /*
     | IS2046: offer "Pump Operator" whenever there ARE pump operators.
     |
     | The option was shown only when the Petro or Settlement module reported
     | access. This form is reached from Petro General / Pumper Management /
     | Operators / Ledger - a screen that only exists because pump operators
     | exist - and on the reported business that access flag is not set, so the
     | option was missing on the very screen it is needed.
     |
     | Keying it to the list the controller already passes is both simpler and
     | truer: if there is someone to select, the option appears; if the list is
     | empty, it stays hidden rather than offering a dropdown with nothing in it.
     | The module flag is still honoured as an alternative, so nothing that shows
     | the option today stops showing it.
     */
    $show_pump_operator_ledger = $is_petro_or_settlement_enabled
        || (isset($pump_operators) && count($pump_operators) > 0);
@endphp

<style>
/*
 |----------------------------------------------------------------------------
 | IS2178 - journal entry grid
 |----------------------------------------------------------------------------
 |
 | The header, every entry row, the Add bar, both totals blocks and the added
 | table share ONE column definition, so they cannot drift apart.
 |
 | Bootstrap's 12-column classes could not express what was asked - "30% wider"
 | is not a whole number of columns - so these rows are a CSS grid.
 |
 |   Account Type / Sub Type / Account   1.75fr each
 |       Account now matches Account Type, as asked. All three equal also makes
 |       the dropdowns read as one group.
 |
 |   Debit / Credit                      2.17fr each
 |       2.17 / 1.667 = 1.30 - 30% wider than the col-md-2 they replaced.
 |
 |   Action                              42px, fixed
 |       It holds one small square button and should not grow with the modal.
 */
.fj-grid{
    display:grid;
    /*
     | All five fields the same width, as asked.
     |
     | An earlier revision made Debit and Credit 30% wider than the dropdowns.
     | That is now superseded: equal columns line the row up with the added
     | table and the totals underneath, and nothing has to be re-checked when a
     | column is added later.
     |
     | The action column stays fixed at 42px - it holds one small square button
     | and should not grow with the modal.
     */
    grid-template-columns:repeat(5, 1fr) 42px;
    gap:10px;
    align-items:start;
}
.fj-grid > div{min-width:0;}   /* lets select2 shrink rather than overflow */
.fj-head{font-weight:700;margin-bottom:6px;}
.fj-row{margin-bottom:8px;}
.fj-action{display:flex;align-items:flex-start;padding-top:4px;}

/* Add sits in column 5 only, so it stays under Credit Amount at any width. */
.fj-addbar{margin:4px 0 2px;}
.fj-addbar .fj-add-cell{grid-column:5;}

.fj-table-wrap{margin-top:18px;}
.fj-totals{margin-top:14px;align-items:center;}
.fj-totals .fj-totals-label{grid-column:1 / span 3;font-weight:700;font-size:15px;}

/* Table amounts match one field column above: 1 of 5 equal columns = 20%. */
#journal_details th.fj-col-amount,
#journal_details td.fj-col-amount{width:20%;}
#journal_details th.fj-col-action,
#journal_details td.fj-col-action{width:90px;}

/*
 | A locked amount box.
 |
 | readonly alone changes nothing on screen, so a field that had stopped
 | accepting input looked identical to one that had not. This makes the state
 | visible before the user tries to type into it.
 */
.fj-locked{background:#f1f5f9 !important;color:#94a3b8 !important;cursor:not-allowed;}

/* The added table's Total row, and its warning state when the two sides differ. */
#journal_details tfoot.journal-preview-foot th{background:#f8fafc;font-weight:800;border-top:2px solid #dbe7f3;}
#journal_details tfoot.journal-preview-foot tr.fj-foot-unbalanced th{background:#fef2f2;color:#991b1b;}

/* Add, while it is waiting for both rows to have an account. */
.add_row_create[disabled]{opacity:.55;cursor:not-allowed;}

/* Green Save. Its own class, so the theme's btn-success can change without
   silently restyling this button. */
.finance-journal-save-btn.fj-save{background:#28a745;border-color:#28a745;color:#fff;font-weight:700;}
.finance-journal-save-btn.fj-save:hover,
.finance-journal-save-btn.fj-save:focus{background:#218838;border-color:#1e7e34;color:#fff;}
.finance-journal-save-btn.fj-save[disabled]{background:#28a745;border-color:#28a745;color:#fff;opacity:.55;cursor:not-allowed;}

@media (max-width:991px){
    /* One field per line rather than six unreadable slivers. */
    .fj-grid{grid-template-columns:1fr;}
    .fj-grid.fj-head{display:none;}
    .fj-addbar .fj-add-cell{grid-column:1;}
    .fj-totals .fj-totals-label{grid-column:1;}
}
</style>

<div class="modal-dialog modal-lg" role="document" style="width: 60%">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('account.add_journal')</h4>
        </div>

        <div class="modal-body">
            {!! Form::open(['url' => route('finance.accounting.journal.store'), 'method' => 'post', 'id' => 'finance_journal_create_form', 'class' => 'finance-journal-create-form']) !!}
            <input type="hidden" id="index" name="index" value="1">

            <div class="col-md-12">
                <div class="row">
                    <!-- Journal No -->
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('journal_id_display', __('account.journal_no')) !!}
                            {!! Form::text('journal_id_display', 'JOUR' . str_pad((string) $journal_id, 4, '0', STR_PAD_LEFT), ['class' => 'form-control journal_id', 'readonly']) !!}
                            {!! Form::hidden('journal_id', $journal_id) !!}
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
                            {!! Form::select('is_opening_balance', ['yes' => 'Yes', 'no' => 'No'], 'no', [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'is_opening_balance',
                                'required',
                                'placeholder' => 'Please select',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Note -->
                    <div class="{{ $settlement_access == 1 ? 'col-md-4' : 'col-md-6' }}">
                        <div class="form-group">
                            {!! Form::label('note', __('account.note') . ' *') !!}
                            {!! Form::textarea('note', null, [
                                'class' => 'form-control',
                                'rows' => 2,
                                'cols' => 10,
                                'required',
                                'maxlength' => 2000,
                                'aria-required' => 'true',
                                'autocomplete' => 'off',
                            ]) !!}
                            <small class="help-block finance-journal-note-help">
                                The Note field is mandatory for every journal entry.
                            </small>
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
                                ] + ($show_pump_operator_ledger ? ['pump_operator' => 'Pump Operator'] : []),
                                'no',
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
                <div class="fj-grid fj-head">
                    <div>@lang('account.select_account_type')</div>
                    <div>Account Sub Type</div>
                    <div>@lang('account.select_account')</div>
                    <div>@lang('account.debit_amount')</div>
                    <div>@lang('account.credit_amount')</div>
                    <div></div>
                </div>

                <div class="dynamic_rows"></div>

                <!-- First Row -->
                <div class="fj-grid fj-row journal_row">
                    <div>
                        {!! Form::select('journal[account_type_id][]', $account_types, null, [
                            'id' => 'account_type0',
                            'class' => 'form-control select2 account_type_ids',
                            'style' => 'width:100%',
                            'required',
                            'placeholder' => 'Please select',
                        ]) !!}
                    </div>
                    <div>
                        {!! Form::select('journal[account_sub_type_id][]', [], null, [
                            'id' => 'account_sub_type0',
                            'class' => 'form-control select2 account_sub_type_ids',
                            'style' => 'width:100%',
                            'placeholder' => 'Account Sub Type',
                        ]) !!}
                    </div>
                    <div>
                        {!! Form::select('journal[account_id][]', [], null, [
                            'id' => 'account_id0',
                            'class' => 'form-control select2 account_ids',
                            'style' => 'width:100%',
                            'required',
                            'placeholder' => 'Please select',
                        ]) !!}
                    </div>
                    <div>
                        {!! Form::text('journal[debit_amount][]', null, [
                            'id' => 'debit0',
                            'class' => 'debit-top form-control debit_amount0',
                            'placeholder' => __('account.amount'),
                        ]) !!}
                    </div>
                    <div>
                        {!! Form::text('journal[credit_amount][]', null, [
                            'id' => 'credit0',
                            'class' => 'credit-top form-control credit_amount0',
                            'placeholder' => __('account.amount'),
                        ]) !!}
                    </div>
                    {{--
                        IS2178 #3: the + moved out of the header and into the row,
                        beside Credit Amount, where it reads as "add another line
                        like this one" rather than as part of the column title.
                    --}}
                    <div class="fj-action">
                        <button type="button" class="btn btn-xs btn-primary add_row"
                                title="Add another line">+</button>
                    </div>
                </div>

                <!-- Second Row -->
                <div class="fj-grid fj-row journal_row">
                    <div>
                        {!! Form::select('journal[account_type_id][]', $account_types, null, [
                            'id' => 'account_type1',
                            'class' => 'form-control select2 account_type_ids',
                            'style' => 'width:100%',
                            'required',
                            'placeholder' => 'Please select',
                        ]) !!}
                    </div>
                    <div>
                        {!! Form::select('journal[account_sub_type_id][]', [], null, [
                            'id' => 'account_sub_type1',
                            'class' => 'form-control select2 account_sub_type_ids',
                            'style' => 'width:100%',
                            'placeholder' => 'Account Sub Type',
                        ]) !!}
                    </div>
                    <div>
                        {!! Form::select('journal[account_id][]', [], null, [
                            'id' => 'account_id1',
                            'class' => 'form-control select2 account_ids',
                            'style' => 'width:100%',
                            'required',
                            'placeholder' => 'Please select',
                        ]) !!}
                    </div>
                    <div>
                        {!! Form::text('journal[debit_amount][]', null, [
                            'id' => 'debit1',
                            'class' => 'debit-top form-control debit_amount1',
                            'placeholder' => __('account.amount'),
                        ]) !!}
                    </div>
                    <div>
                        {!! Form::text('journal[credit_amount][]', null, [
                            'id' => 'credit1',
                            'class' => 'credit-top form-control credit_amount1',
                            'placeholder' => __('account.amount'),
                        ]) !!}
                    </div>
                    <div class="fj-action"></div>
                </div>

                {{--
                    IS2178 #4: Add sits directly BELOW Credit Amount.

                    It reuses the same grid and occupies column 5 only, so it
                    stays under Credit however wide the modal is - rather than
                    being pushed around by a neighbouring cell as it was when it
                    shared the row.
                --}}
                <div class="fj-grid fj-addbar">
                    <div class="fj-add-cell">
                        <button type="button" class="btn btn-primary btn-block add_row_create" data-index="1">
                            <i class="fa fa-plus"></i> @lang('messages.add')
                        </button>
                    </div>
                </div>

                {{--
                    IS2178 #9: totals use the SAME grid as the rows above, so
                    each figure sits under the column it totals. The old
                    col-md-6 + col-md-2 + col-md-2 added up to 10 of 12, which
                    left both boxes short of the Debit and Credit fields.
                --}}
                <div class="fj-grid fj-totals">
                    <div class="fj-totals-label">@lang('account.total')</div>
                    <div>
                        {!! Form::text('debit_total_top', null, [
                            'class' => 'form-control debit_total_top',
                            'readonly',
                            'style' => 'width:100%;',
                        ]) !!}
                    </div>
                    <div>
                        {!! Form::text('credit_total_top', null, [
                            'class' => 'form-control credit_total_top',
                            'readonly',
                            'style' => 'width:100%;',
                        ]) !!}
                    </div>
                    <div></div>
                </div>

                {{--
                    IS2178 #7 and #8: the Debit and Credit columns are given the
                    same width as the Credit Amount field above, so a figure
                    lines up with the box it was typed into; and the table is
                    spaced away from the totals rather than sitting flush
                    against them.
                --}}
                <div class="table-responsive fj-table-wrap">
                    <table class="table table-bordered table-striped" id="journal_details">
                        <thead>
                            <tr>
                                <th>@lang('account.account')</th>
                                <th class="fj-col-amount">@lang('account.debit')</th>
                                <th class="fj-col-amount">@lang('account.credit')</th>
                                <th>@lang('account.show_in_ledger')</th>
                                <th class="fj-col-action text-center">@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                {{--
                    The footer Total row was REMOVED.

                    It duplicated the Total row that already sits above the added
                    table, showing the same two figures a few centimetres lower,
                    and the added table now carries its own Total row as well -
                    so the same numbers appeared three times.

                    SAFE TO REMOVE, despite the fields having been marked
                    `required`: store() recomputes both totals from the row
                    amounts and merges them into the request BEFORE validation
                    (see the $request->merge call), so whatever the form posted
                    was overwritten and never read. The hidden inputs below keep
                    the field names present for any older script that still
                    writes to them.
                --}}
                {!! Form::hidden('debit_total', null, ['class' => 'debit_total']) !!}
                {!! Form::hidden('credit_total', null, ['class' => 'credit_total']) !!}

                <div class="clearfix"></div>
                {{--
                    S-666 #1b: Submit stays hidden until Add has been used.

                    Choosing an account used to leave Submit sitting there ready,
                    which invites submitting a journal whose lines were never
                    added to the entry below - so nothing is actually posted.
                    It is revealed by the script once #journal_details has at
                    least one row, and hidden again if every row is removed.
                --}}
                {{--
                    IS2178 #10: a green Save button in place of the
                    "click Add to continue" wording.

                    The button is now always ON SCREEN, but DISABLED until at
                    least one line has been added. Simply un-hiding it would
                    have undone S-666 #1b, which hid it for a real reason:
                    choosing an account used to leave Submit ready, inviting a
                    save whose lines were never added to the entry below - so
                    nothing was posted and the user believed it had been.

                    Disabled keeps that protection while giving the user what
                    the ticket asks for - a visible green Save - and the button
                    explains itself through its title rather than through a
                    separate line of text. The hint stays alongside, but only
                    while the button is unusable.
                --}}
                <button type="submit" class="btn add_btn finance-journal-save-btn fj-save"
                    data-idle-label="@lang('messages.save')" aria-busy="false"
                    disabled title="@lang('account.click_add_to_continue')">
                    <span class="finance-journal-save-icon fa fa-save" aria-hidden="true"></span>
                    <span class="finance-journal-save-label">@lang('messages.save')</span>
                </button>
                <span class="finance-journal-add-hint text-muted" style="margin-right:10px;">
                    @lang('account.click_add_to_continue')
                </span>
                <button type="button" class="btn btn-default finance-journal-close-btn" data-dismiss="modal">@lang('messages.close')</button>
                <span class="finance-journal-saving-status text-primary" role="status" aria-live="polite" hidden
                    style="display:none; margin-left:10px; font-weight:600;">
                    <i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Saving journal. Please wait...
                </span>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

@include('finance::journal.partials.date_field_native')
<script>
    var $financeJournalModal = $('.add_modal:visible').last();
    if (!$financeJournalModal.length) {
        $financeJournalModal = $('.add_modal').last();
    }

    $financeJournalModal.find('textarea[name="note"]')
        .prop('required', true)
        .attr('aria-required', 'true');

    // Bind directly on the AJAX-loaded form so this Finance-owned save handler
    // runs before any generic application-level delegated form handler.
    $financeJournalModal.find('form.finance-journal-create-form')
        .off('submit.financeJournalDirect')
        .on('submit.financeJournalDirect', function (event) {
            if (typeof window.submitFinanceJournalForm !== 'function') {
                return true;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            return window.submitFinanceJournalForm(this, event);
        });

    /*
     * IS1992 (follow-up): INITIALISE the picker, then set the date.
     *
     * This called .datepicker('setDate', ...) on a field the picker had never
     * been initialised on - compare edit.blade.php, which correctly does
     * .datepicker().datepicker('setDate', ...). Depending on which picker build
     * is loaded that either does nothing or attaches one with default settings,
     * and a picker using its own default format writes a string the server's
     * uf_date() cannot parse against the business date format. The result was a
     * date that passed 'required' but converted to null on save.
     *
     * datepicker_date_format is the business setting the rest of the application
     * uses; falling back to mm/dd/yyyy only matters if that global is absent.
     */
    /*
     * S-666: the Date field is now the browser's own date picker.
     *
     * bootstrap-datepicker had to be loaded at the instant this ajax-injected
     * script ran, positioned inside the modal and left unstyled by the theme.
     * IS1992 fixed one link in that chain; this removes the chain. The shared
     * converter keeps the submitted value in the business date format, so
     * uf_date() on the server is unaffected.
     */
    window.financeBindJournalDate($financeJournalModal);

    // Initialise after the AJAX modal content is attached and again after the
    // Bootstrap transition.  This avoids stale/hidden Select2 containers.
    if (typeof window.initFinanceJournalDropdowns === 'function') {
        window.initFinanceJournalDropdowns($financeJournalModal);
        window.setTimeout(function () {
            window.initFinanceJournalDropdowns($financeJournalModal);
        }, 100);
    } else if ($.fn.select2) {
        var $dropdownParent = $financeJournalModal.find('.modal-content').first();
        $financeJournalModal.find('select.select2').each(function () {
            var $select = $(this);
            try {
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
            } catch (ignore) {}
            $select.next('.select2-container').remove();
            $select.siblings('.select2-container').remove();
            $select.select2({
                width: '100%',
                dropdownParent: $dropdownParent.length ? $dropdownParent : $financeJournalModal
            });
        });
    }

    /*
     * IS2034 #1: hand the Ledger Holder lists to the shared handler.
     *
     * The "Show In Ledger" handler in index.blade.php un-hid #ledger_holder_wrapper
     * but never put any <option> into #ledger_holder, so the control appeared as an
     * empty box - which is what the ticket screenshot shows. create() already passes
     * $customers, $suppliers and $pump_operators to this view; nothing ever read
     * them. edit.blade.php had its own copy of the populating code, which is why the
     * fault showed up on Add Journal only.
     *
     * The lists are attached to the modal rather than written into globals so the
     * Add and Edit modals can be open against different data without clashing, and
     * so one shared handler can serve both.
     */
    $financeJournalModal.data('financeLedgerHolders', {
        customer: @json($customers),
        supplier: @json($suppliers),
        pump_operator: @json($pump_operators)
    });
    $financeJournalModal.data('financeSelectedLedgerHolder', '');

    // The visible journal rows are the submitted rows. The Save button no longer
    // depends on first copying them into a hidden preview table.
    $financeJournalModal.find('#show_in_ledger').trigger('change');

    if (typeof window.refreshFinanceJournalForm === 'function') {
        window.refreshFinanceJournalForm($financeJournalModal);
    }
</script>
