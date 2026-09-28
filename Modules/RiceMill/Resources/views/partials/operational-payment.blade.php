@php
    $paymentTitle = $paymentTitle ?? 'Payment';
    $paymentMap = is_array($paymentMap ?? null) ? $paymentMap : ['methods'=>[], 'accounts'=>[], 'by_location'=>[]];
    $paymentHelp = $paymentHelp ?? 'Leave Payment Amount as 0.0000 when no payment is being recorded.';
@endphp
<div class="rcm-card" data-rcm-operational-payment>
    <div class="rcm-section-title">{{ $paymentTitle }}</div>
    <div class="rcm-help-text" style="margin-bottom:12px">{{ $paymentHelp }}</div>
    <script type="application/json" data-rcm-payment-map>@json($paymentMap)</script>
    <div class="rcm-form-grid">
        <div class="rcm-field">
            <label>Payment Method</label>
            <select class="rcm-searchable rcm-operational-payment-method" name="payment_method">
                <option value="">Select Payment Method</option>
                @foreach(($paymentMap['methods'] ?? []) as $key => $label)
                    <option value="{{ $key }}" {{ (string)old('payment_method') === (string)$key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('payment_method')<div class="rcm-field-error">{{ $message }}</div>@enderror
        </div>
        <div class="rcm-field">
            <label>Payment Account</label>
            <select class="rcm-searchable rcm-operational-payment-account" name="payment_account_id" data-old-value="{{ old('payment_account_id') }}">
                <option value="">Select Payment Method first</option>
            </select>
            <small>Only Account Books linked to the selected Payment Method are shown.</small>
            @error('payment_account_id')<div class="rcm-field-error">{{ $message }}</div>@enderror
        </div>
        <div class="rcm-field">
            <label>Payment Amount</label>
            <input type="number" min="0" step="{{ $rcmCurrencyStep }}" name="payment_amount" value="{{ old('payment_amount',0) }}">
            @error('payment_amount')<div class="rcm-field-error">{{ $message }}</div>@enderror
        </div>
        <div class="rcm-field">
            <label>Payment Note</label>
            <textarea name="payment_note">{{ old('payment_note') }}</textarea>
            @error('payment_note')<div class="rcm-field-error">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
