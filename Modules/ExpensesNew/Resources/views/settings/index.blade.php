@extends('expensesnew::layouts.app', ['heading' => 'Expenses-New Settings'])

@section('module_content')
{{--
    MA-002: built to the design supplied on 6 Aug - "Add Expense Form Prefix
    Numbers". Same card, spacing, teal Save and grey Close.

    Three fields, in the order given: Date, Starting No., Prefix.

    The DATE is new. It is stored as category_code_date and is the date the
    numbering was set - shown on the form and saved with the other two, so it
    is a record of when the sequence was established rather than a filter.

    THE SCRIPT SITS INSIDE module_content DELIBERATELY. The ExpensesNew layout
    yields module_content and nothing else - a script in its own section is
    discarded silently, which is exactly why the preview never filled before.
--}}
<style>
    .exn-prefix-card {
        max-width: 460px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .15);
        padding: 20px 30px;
        margin: 10px auto 30px;
    }

    .exn-prefix-card h2 {
        font-size: 18px;
        margin: 0 0 20px;
        color: #333;
        text-align: center;
        font-weight: 600;
    }

    .exn-prefix-card .form-group { margin-bottom: 15px; }

    .exn-prefix-card .form-group label {
        display: block;
        font-size: 14px;
        margin-bottom: 6px;
        color: #555;
        font-weight: 400;
    }

    .exn-prefix-card .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color .3s;
        height: auto;
        box-shadow: none;
    }

    .exn-prefix-card .form-group input:focus {
        border-color: #009688;
        outline: none;
        box-shadow: none;
    }

    .exn-prefix-card .exn-preview {
        margin: 4px 0 0;
        padding: 10px 12px;
        border-radius: 6px;
        background: #e0f2f1;
        border: 1px solid #b2dfdb;
        font-size: 13px;
        color: #00695c;
    }

    .exn-prefix-card .exn-preview strong {
        font-size: 16px;
        font-weight: 700;
        letter-spacing: .3px;
    }

    .exn-prefix-card .exn-help {
        font-size: 12px;
        color: #999;
        margin: 6px 0 0;
        line-height: 1.45;
    }

    .exn-prefix-card .buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .exn-prefix-card .btn-exn {
        padding: 10px 18px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color .3s;
    }

    .exn-prefix-card .btn-save  { background-color: #009688; color: #fff; }
    .exn-prefix-card .btn-save:hover  { background-color: #00796b; color: #fff; }
    .exn-prefix-card .btn-close { background-color: #e0e0e0; color: #333; }
    .exn-prefix-card .btn-close:hover { background-color: #c7c7c7; color: #333; }
</style>

<form method="post" action="{{ route('expensesnew.settings.save') }}">
    @csrf

    <div class="exn-prefix-card">
        <h2>Add Expense Form Prefix Numbers</h2>

        <div class="form-group">
            <label for="exn_code_date">Date:</label>
            <input type="text"
                   id="exn_code_date"
                   name="category_code_date"
                   autocomplete="off"
                   value="{{ old('category_code_date', $settings['category_code_date'] ?? now()->format('d/m/Y H:i')) }}">
        </div>

        <div class="form-group">
            <label for="exn_code_start">Starting No.:</label>
            <input type="text"
                   id="exn_code_start"
                   name="category_code_start"
                   maxlength="12"
                   autocomplete="off"
                   placeholder="Enter starting number"
                   value="{{ old('category_code_start', $settings['category_code_start'] ?? '') }}">
            <p class="exn-help">
                Used for the first category only. After that the next code follows the
                highest one already in use. Leading zeros set the width.
            </p>
        </div>

        <div class="form-group">
            <label for="exn_code_prefix">Prefix:</label>
            <input type="text"
                   id="exn_code_prefix"
                   name="category_code_prefix"
                   maxlength="20"
                   autocomplete="off"
                   placeholder="Enter prefix"
                   value="{{ old('category_code_prefix', $settings['category_code_prefix'] ?? '') }}">
        </div>

        <div class="form-group">
            <label>Next code will look like:</label>
            <div class="exn-preview">
                <strong id="exn_code_preview">{{ $nextCategoryCode ?? '—' }}</strong>
            </div>
        </div>

        {{--
            MA-002 (S-621): print options for the Expense Voucher.

            Both default to SHOWING the note, because that is what the voucher
            does today - the print view already renders $expense->notes with no
            condition. Defaulting to hidden would have quietly dropped a note
            from every voucher the moment this deployed.
        --}}
        {{--
            8031: prefill Paid Amount with the Total Amount.

            Off by default, so nothing changes for anyone who does not turn it on.
            The Add and Edit forms read this and copy the total into Paid Amount
            as it is typed - the field stays editable, so a part payment is still
            entered normally.
        --}}
        <div class="form-group" style="margin-top:18px; padding-top:14px; border-top:1px solid #eef2f7;">
            <label style="font-weight:600; color:#333;">Payments</label>

            <div class="checkbox" style="margin-top:8px;">
                <label style="font-weight:400; color:#555;">
                    {{-- Hidden 0 first: an unticked checkbox posts nothing at all. --}}
                    <input type="hidden" name="paid_amount_autofill" value="0">
                    <input type="checkbox"
                           name="paid_amount_autofill"
                           value="1"
                           @checked(old('paid_amount_autofill', $settings['paid_amount_autofill'] ?? 0))>
                    Paid Amount to auto fill with the Total Amount
                </label>
            </div>
        </div>

        <div class="form-group" style="margin-top:18px; padding-top:14px; border-top:1px solid #eef2f7;">
            <label style="font-weight:600; color:#333;">Expense Voucher Print</label>

            <div class="checkbox" style="margin-top:8px;">
                <label style="font-weight:400; color:#555;">
                    <input type="hidden" name="print_show_expense_note" value="0">
                    <input type="checkbox"
                           name="print_show_expense_note"
                           value="1"
                           @checked(old('print_show_expense_note', $settings['print_show_expense_note'] ?? 1))>
                    Show Expense Note in Print
                </label>
            </div>

            <div class="checkbox" style="margin-top:4px;">
                <label style="font-weight:400; color:#555;">
                    <input type="hidden" name="print_show_payment_note" value="0">
                    <input type="checkbox"
                           name="print_show_payment_note"
                           value="1"
                           @checked(old('print_show_payment_note', $settings['print_show_payment_note'] ?? 1))>
                    Show Payment Note in Print
                </label>
            </div>
            {{-- IS1991: the "Applies to both printing paths ..." note was removed
                 as requested. The two checkboxes still govern both printing
                 paths; only the sentence is gone. --}}
        </div>

        <div class="buttons">
            <button type="submit" class="btn-exn btn-save">Save</button>
            <a href="{{ route('expensesnew.dashboard') }}" class="btn-exn btn-close">Close</a>
        </div>
    </div>
</form>

<script>
    /*
     * MA-002: live preview of the generated code.
     *
     * Mirrors the padding rule in CategoryCodeGenerator - the width comes from
     * the digits of the starting number, so 0001 pads to four. Display only;
     * the server still decides the real code, and SERVER_NEXT_CODE is what it
     * currently says, used whenever the starting number box is empty.
     */
    (function () {
        var $prefix  = $('#exn_code_prefix');
        var $start   = $('#exn_code_start');
        var $preview = $('#exn_code_preview');
        var SERVER_NEXT_CODE = '{{ $nextCategoryCode ?? '' }}';

        function render() {
            var prefix = $.trim($prefix.val() || '');
            var digits = ($start.val() || '').replace(/\D/g, '');

            if (digits === '') {
                $preview.text(SERVER_NEXT_CODE || '\u2014');
                return;
            }

            var number = String(parseInt(digits, 10) || 0);
            while (number.length < digits.length) {
                number = '0' + number;
            }

            $preview.text(prefix + number);
        }

        $prefix.add($start).on('input change', render);
        render();
    }());
</script>

{{-- IS1991 (#1): every saved prefix, with its creator, date, time and actions. --}}
@include('expensesnew::settings.partials.prefix-list')
@endsection
