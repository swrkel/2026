<style>
    .pd-card-select-dropdown .select2-results__option {
        padding-top: 8px !important;
        padding-bottom: 8px !important;
        font-size: 150% !important;
        line-height: 1.35 !important;
    }
</style>

{!! Form::open([
    'url' => action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@saveCardPayment'),
    'method' => 'post',
    'id' => 'card_payment_form',
    'class' => 'pd-card-payment-form',
    'autocomplete' => 'off',
]) !!}
<div class="pd-card-payment-layout">
    <section class="pd-card-fields-panel">
        <div class="pd-card-fields-grid">
            <div class="pd-card-field">
                <div class="form-group">
                    {!! Form::label('card_pmt_type', __('pumperdashboard::lang.type') . ':') !!}
                    <select id="card_pmt_type" name="card_pmt_type" class="form-control">
                        <option value="bulk"
                            {{ old('card_pmt_type', $card_pmt_type ?? '') === 'bulk' || empty(old('card_pmt_type')) ? 'selected' : '' }}>
                            {{ __('pumperdashboard::lang.bulk') }}
                        </option>
                        <option value="one_by_one"
                            {{ old('card_pmt_type', $card_pmt_type ?? '') === 'one_by_one' ? 'selected' : '' }}>
                            {{ __('pumperdashboard::lang.one_by_one') }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="pd-card-field">
                <div class="form-group">
                    {!! Form::label('card_type', __('pumperdashboard::lang.card_type') . ':') !!}
                    {!! Form::select('card_type', $card_types, null, [
                        'class' => 'form-control select2',
                        'style' => 'width: 100%;',
                        'placeholder' => __('messages.please_select'),
                    ]) !!}
                </div>
            </div>

            <div class="pd-card-field card-fields">
                <div class="form-group">
                    {!! Form::label('slip_no', __('pumperdashboard::lang.slip_no')) !!}
                    {!! Form::text('slip_no', null, ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="pd-card-field card-fields">
                <div class="form-group">
                    {!! Form::label('card_no', __('pumperdashboard::lang.card_number')) !!}
                    {!! Form::text('card_no', null, ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="pd-card-field">
                <div class="form-group">
                    {!! Form::label('card_amount', __('pumperdashboard::lang.amount')) !!}
                    {!! Form::text('card_amount', null, [
                        'class' => 'form-control card_payment_input',
                        'placeholder' => __('pumperdashboard::lang.amount'),
                    ]) !!}
                </div>
            </div>
        </div>

        <div class="pd-card-add-row">
            <button type="button" class="btn pd-card-add-button card_payment_add">
                <i class="fa fa-plus" aria-hidden="true"></i>
                @lang('messages.add')
            </button>
        </div>

        <input type="hidden" name="enter_card_numbers" id="enter_card_numbers" value="{{ $enter_card_numbers }}">

        <div class="pd-card-table-wrap">
            <table class="table table-bordered table-striped" id="card_payment_table">
                <thead>
                    <tr>
                        <th>@lang('pumperdashboard::lang.card_type')</th>
                        <th>@lang('pumperdashboard::lang.slip_no')</th>
                        @if (!($enter_card_numbers == 'no'))
                            <th>@lang('pumperdashboard::lang.card_number')</th>
                        @endif
                        <th>@lang('pumperdashboard::lang.amount')</th>
                        <th>*</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        @php
            $collection_form_no = '';
        @endphp
        @if (session('status'))
            @php
                $output = session('status');
                if ($output['success'] && isset($output['collection_form_no'])) {
                    $collection_form_no = $output['collection_form_no'] ?? '';
                }
            @endphp
        @endif
        <input type="hidden" class="collection_form_no" name="collection_form_no" value="{{ $collection_form_no }}">

        <div class="pd-card-save-row">
            <button type="submit" class="btn pd-card-save-button card-save-btn">
                @lang('pumperdashboard::lang.save')
            </button>
        </div>
    </section>

    <section id="key_pad" class="pd-card-keypad text-center" aria-label="Card payment numeric keypad">
        @foreach ([7, 8, 9, 4, 5, 6, 1, 2, 3] as $digit)
            <button id="{{ $digit }}" type="button" class="btn btn-primary btn-sm"
                onclick="cardPaymentEnterVal(this.id)">{{ $digit }}</button>
        @endforeach
        <button id="backspace" type="button" class="btn btn-danger"
            onclick="cardPaymentEnterVal(this.id)">⌫</button>
        <button id="0" type="button" class="btn btn-primary btn-sm"
            onclick="cardPaymentEnterVal(this.id)">0</button>
        <button id="precision" type="button" class="btn btn-success"
            onclick="cardPaymentEnterVal(this.id)">.</button>
    </section>
</div>
{!! Form::close() !!}

<script>
    (function () {
        function initCardPaymentSelect2() {
            var $cardType = $('#card_type');
            var $modal = $('#card_payment');
            if (!$cardType.length || typeof $.fn.select2 !== 'function') {
                return;
            }
            if ($cardType.hasClass('select2-hidden-accessible')) {
                $cardType.select2('destroy');
            }
            $cardType.select2({
                width: '100%',
                dropdownParent: $modal,
                dropdownCssClass: 'pd-card-select-dropdown'
            });
        }

        function bindCardPaymentFieldVisibility() {
            const typeSelect = document.getElementById('card_pmt_type');
            if (!typeSelect) {
                return;
            }

            initCardPaymentSelect2();

            if (typeSelect.dataset.visibilityBound === '1') {
                return;
            }
            typeSelect.dataset.visibilityBound = '1';
            const cardFields = document.querySelectorAll('#card_payment_form .card-fields');
            const enterCardNumbers = document.getElementById('enter_card_numbers')?.value;

            function toggleCardFields() {
                cardFields.forEach(function (field) {
                    if (typeSelect.value === 'bulk') {
                        field.style.display = 'none';
                        return;
                    }

                    if (enterCardNumbers === 'no' && field.querySelector('input[name="card_no"]')) {
                        field.style.display = 'none';
                    } else {
                        field.style.display = '';
                    }
                });
            }

            toggleCardFields();
            typeSelect.addEventListener('change', toggleCardFields);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindCardPaymentFieldVisibility, { once: true });
        } else {
            bindCardPaymentFieldVisibility();
        }

        $(document).off('shown.bs.modal.pdCardPayment', '#card_payment')
            .on('shown.bs.modal.pdCardPayment', '#card_payment', bindCardPaymentFieldVisibility);
    })();
</script>

<style>
/*
 * MA-002 (S-609 #7): Card payment screen sizing.
 *
 *   a. Payment Method font  +20%
 *   b. Number Pad          -25%
 *   c. Card Type font      +10%
 *
 * Expressed as percentages of the inherited size rather than absolute pixel
 * values, because this screen sets very few explicit sizes of its own - the
 * payment method buttons and keypad inherit from the shared payment styles.
 * A percentage scales whatever those turn out to be, so this stays correct
 * if the shared sizes change.
 *
 * Scoped to this page. The keypad and payment buttons on the main Payment
 * screen are sized separately in payment_section.blade.php and are NOT
 * affected - #6 asked for the number pad there to grow, #7 asks for this one
 * to shrink, so the two must not share a rule.
 */

/* a. payment method buttons +20% */
.pd-card-payment .payment_type_btn,
.pd-card-payment .payment-type-list > .payment_type_btn {
    font-size: 120%;
}

/* b. number pad -25% - the section, so buttons and their text scale together */
#key_pad.pd-card-keypad {
    font-size: 75%;
}
#key_pad.pd-card-keypad button {
    font-size: 75%;
}

/* c. card type +10%, label and control together */
.pd-card-payment label[for="card_type"],
#card_type,
#card_type + .select2 .select2-selection__rendered {
    font-size: 110%;
}
</style>
