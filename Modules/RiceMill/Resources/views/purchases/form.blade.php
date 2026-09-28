@extends('RiceMill::layout')
@section('rcm-title','New Purchase Order')
@section('rcm-subtitle','Create the Purchase Order using the standard Purchase payment flow')
@section('rcm-content')
@php
    $mappedPayable = collect($paddyPayableAccounts)->firstWhere('id',$paddyPayableAccountId);
    $paymentMethods = $purchasePaymentMap['methods'] ?? [];
@endphp
<form method="post" action="{{ route('rice-mill.purchases.store') }}" id="rcm-purchase-order-form">
    @csrf
    <div class="rcm-card">
        <div class="rcm-section-title">Purchase Order</div>
        <div class="rcm-form-grid rcm-location-store-group" data-rcm-default-first-store="1">
            <div class="rcm-field"><label>Purchase Order No</label><input value="{{ $purchaseNumberPreview }}" readonly><small>Generated automatically when this Purchase Order is saved.</small></div>
            <div class="rcm-field"><label>Purchase Date</label><input type="date" name="purchase_date" value="{{ old('purchase_date',date('Y-m-d')) }}" required></div>
            <div class="rcm-field"><label>Supplier</label><select class="rcm-searchable" name="supplier_id" required><option value="">Select supplier</option>@foreach($suppliers as $s)<option value="{{ $s['id'] }}" {{ (string)old('supplier_id')===(string)$s['id']?'selected':'' }}>{{ $s['name'] }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Location</label><select class="rcm-searchable rcm-location-select" name="location_id" id="rcm-purchase-location"><option value="">Select location</option>@foreach($locations as $x)<option value="{{ $x['id'] }}" {{ (string) old('location_id') === (string) $x['id'] ? 'selected' : '' }}>{{ $x['name'] }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Store</label><select class="rcm-searchable rcm-store-select" name="store_id"><option value="">Select store</option>@foreach($stores as $x)<option value="{{ $x['id'] }}" data-location-id="{{ $x['location_id'] ?? '' }}" {{ (string) old('store_id', $defaultStoreId ?? '') === (string) $x['id'] ? 'selected' : '' }}>{{ $x['name'] }}</option>@endforeach</select></div>
        </div>
    </div>

    <div class="rcm-card">
        <div class="rcm-section-title">Paddy Lines</div>
        <div id="purchase-lines">
            <div data-rcm-row class="rcm-form-grid rcm-purchase-line">
                <div class="rcm-field"><label>Variety</label><select name="lines[0][paddy_variety_id]" required>@foreach($varieties as $v)<option value="{{ $v->id }}">{{ $v->code }} - {{ $v->name }}</option>@endforeach</select></div>
                <div class="rcm-field"><label>Net Weight (kg)</label><input class="rcm-purchase-qty" type="number" step="{{ $rcmQuantityStep }}" name="lines[0][net_weight]" value="{{ old('lines.0.net_weight') }}" required></div>
                <div class="rcm-field"><label>Rate / kg</label><input class="rcm-purchase-rate" type="number" step="{{ $rcmCurrencyStep }}" name="lines[0][unit_rate]" value="{{ old('lines.0.unit_rate') }}" required></div>
                <div class="rcm-field"><label>Deduction</label><input class="rcm-purchase-deduction" type="number" step="{{ $rcmCurrencyStep }}" name="lines[0][deduction_amount]" value="{{ old('lines.0.deduction_amount',0) }}"></div>
            </div>
        </div>
        <template id="purchase-line-template">
            <div data-rcm-row class="rcm-form-grid rcm-purchase-line" style="margin-top:10px">
                <div class="rcm-field"><label>Variety</label><select name="lines[__INDEX__][paddy_variety_id]" required>@foreach($varieties as $v)<option value="{{ $v->id }}">{{ $v->code }} - {{ $v->name }}</option>@endforeach</select></div>
                <div class="rcm-field"><label>Net Weight</label><input class="rcm-purchase-qty" type="number" step="{{ $rcmQuantityStep }}" name="lines[__INDEX__][net_weight]" required></div>
                <div class="rcm-field"><label>Rate / kg</label><input class="rcm-purchase-rate" type="number" step="{{ $rcmCurrencyStep }}" name="lines[__INDEX__][unit_rate]" required></div>
                <div class="rcm-field"><label>Deduction</label><input class="rcm-purchase-deduction" type="number" step="{{ $rcmCurrencyStep }}" name="lines[__INDEX__][deduction_amount]" value="0"></div>
                <button type="button" onclick="RiceMill.removeRow(this)">Remove</button>
            </div>
        </template>
        <div class="rcm-toolbar" style="margin-top:12px"><button type="button" class="rcm-btn secondary" onclick="RiceMill.addRow('purchase-line-template','purchase-lines')">+ Add Line</button></div>
    </div>

    <div class="rcm-card">
        <div class="rcm-section-title">Purchase Totals</div>
        <div class="rcm-alert rcm-alert-info"><i class="fa fa-info-circle"></i><span>Purchase Tax is retained on the Purchase Order for the later actual purchase/receipt. At Purchase Order stage the tax is <strong>not calculated, not added separately to the order total, and not posted to a tax account</strong>.</span></div>
        <div class="rcm-form-grid">
            <div class="rcm-field"><label>Other Charges</label><input id="rcm-purchase-other-charges" type="number" step="{{ $rcmCurrencyStep }}" name="other_charges" value="{{ old('other_charges',0) }}"></div>
            <div class="rcm-field">
                <label>Purchase Tax</label>
                <select class="rcm-searchable" name="purchase_tax_id" id="rcm-purchase-tax">
                    <option value="">No Purchase Tax selected</option>
                    @foreach($purchaseTaxes as $tax)
                        <option value="{{ $tax['id'] }}" data-rate="{{ $tax['amount'] }}" {{ (string)old('purchase_tax_id')===(string)$tax['id']?'selected':'' }}>{{ $tax['name'] }}{{ $tax['amount'] > 0 ? ' ('.number_format($tax['amount'],2).'%)' : '' }}</option>
                    @endforeach
                </select>
                <small>Saved for the actual purchase/receipt stage; not posted now.</small>
            </div>
            <div class="rcm-field"><label>Purchase Order Total (Tax Not Counted Yet)</label><input id="rcm-purchase-total" value="0.0000" readonly></div>
            <div class="rcm-field"><label>Note</label><textarea name="note">{{ old('note') }}</textarea></div>
        </div>
    </div>

    <div class="rcm-card">
        <div class="rcm-section-title">Purchase Payment</div>
        <p class="rcm-muted">Payment is saved through the same standard Purchase-module transaction/payment flow used elsewhere in the ERP. This preserves the normal supplier, account-book, cheque/bank and payment-status relationships.</p>

        @if(!$mappedPayable)
            <div class="rcm-alert rcm-alert-danger"><i class="fa fa-exclamation-circle"></i><span>Paddy Payment Account is not mapped. Please configure <strong>Rice Mill / Settings / Product Category Mapping</strong> before saving this Purchase Order.</span></div>
        @else
            <div class="rcm-alert rcm-alert-info"><i class="fa fa-link"></i><span>Paddy Current Liabilities mapping: <strong>{{ $mappedPayable['name'] }}</strong>. It is retained for the standard purchase/payable flow and future actual purchase/receipt posting.</span></div>
        @endif
        @if(empty($paymentMethods))
            <div class="rcm-alert rcm-alert-danger"><i class="fa fa-exclamation-circle"></i><span>No purchase Payment Method with a linked account is available for this business. Check Super Admin / All Businesses / Manage New and the payment-account mapping.</span></div>
        @endif

        <div class="rcm-form-grid">
            <div class="rcm-field">
                <label>Payment Method</label>
                <select class="rcm-searchable" name="payment_method" id="rcm-purchase-payment-method" required>
                    <option value="">Select Payment Method</option>
                    @foreach($paymentMethods as $key=>$label)
                        <option value="{{ $key }}" {{ old('payment_method')===$key?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rcm-field">
                <label>Payment Account</label>
                <select class="rcm-searchable" name="payment_account_id" id="rcm-purchase-payment-account" data-old-value="{{ old('payment_account_id') }}" required>
                    <option value="">Select Payment Method first</option>
                </select>
                <small>Only accounts linked to the selected Payment Method are shown.</small>
            </div>
            <div class="rcm-field"><label>Payment Amount</label><input id="rcm-purchase-payment-amount" value="0.0000" readonly><small>For paid methods this equals the tax-excluded Purchase Order total. Credit Purchase remains Due.</small></div>
            <div class="rcm-field" id="rcm-purchase-cheque-field" style="display:none"><label>Cheque Number</label><input type="text" name="cheque_number" id="rcm-purchase-cheque-number" value="{{ old('cheque_number') }}" maxlength="120"></div>
            <div class="rcm-field"><label>Payment Note</label><textarea name="payment_note" maxlength="2000">{{ old('payment_note') }}</textarea></div>
        </div>
    </div>

    <div class="rcm-card">
        <button class="rcm-btn" type="submit" {{ !$mappedPayable || empty($paymentMethods) ? 'disabled' : '' }}><i class="fa fa-save"></i> Save Purchase Order</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var paymentMap = @json($purchasePaymentMap);
    var methodEl = document.getElementById('rcm-purchase-payment-method');
    var accountEl = document.getElementById('rcm-purchase-payment-account');
    var locationEl = document.getElementById('rcm-purchase-location');
    var chequeField = document.getElementById('rcm-purchase-cheque-field');
    var chequeInput = document.getElementById('rcm-purchase-cheque-number');
    var oldAccount = accountEl ? String(accountEl.getAttribute('data-old-value') || '') : '';

    function linkedAccounts() {
        var method = methodEl ? methodEl.value : '';
        var locationId = locationEl ? String(locationEl.value || '') : '';
        var byLocation = paymentMap.by_location || {};
        if (locationId) return (byLocation[locationId] || {})[method] || {};
        return (paymentMap.accounts || {})[method] || {};
    }

    function refreshPaymentAccounts() {
        if (!accountEl) return;
        var current = String(accountEl.value || oldAccount || '');
        var accounts = linkedAccounts();
        accountEl.innerHTML = '<option value="">Select Payment Account</option>';
        var accountIds = Object.keys(accounts);
        accountIds.forEach(function (id) {
            var opt = document.createElement('option');
            opt.value = id;
            opt.textContent = accounts[id];
            if (String(id) === current) opt.selected = true;
            accountEl.appendChild(opt);
        });
        var currentExists = accountIds.indexOf(current) !== -1;
        if (!currentExists && accountIds.length === 1) {
            accountEl.value = String(accountIds[0]);
        }
        oldAccount = '';
        if (window.jQuery && jQuery.fn && jQuery.fn.select2 && jQuery(accountEl).hasClass('select2-hidden-accessible')) {
            jQuery(accountEl).trigger('change.select2');
        }
    }

    function refreshChequeField() {
        var isCheque = methodEl && methodEl.value === 'cheque';
        if (chequeField) chequeField.style.display = isCheque ? '' : 'none';
        if (chequeInput) chequeInput.required = !!isCheque;
    }

    function money(n) { return (Math.round((Number(n) || 0) * 10000) / 10000).toFixed({{ (int)$rcmCurrencyPrecision }}); }
    function recalcTotal() {
        var total = 0;
        document.querySelectorAll('#purchase-lines .rcm-purchase-line').forEach(function (row) {
            var qty = parseFloat((row.querySelector('.rcm-purchase-qty') || {}).value || 0) || 0;
            var rate = parseFloat((row.querySelector('.rcm-purchase-rate') || {}).value || 0) || 0;
            var ded = parseFloat((row.querySelector('.rcm-purchase-deduction') || {}).value || 0) || 0;
            total += Math.max(0, (qty * rate) - ded);
        });
        var other = parseFloat((document.getElementById('rcm-purchase-other-charges') || {}).value || 0) || 0;
        total += other;
        var totalEl = document.getElementById('rcm-purchase-total');
        var paymentAmountEl = document.getElementById('rcm-purchase-payment-amount');
        if (totalEl) totalEl.value = money(total);
        if (paymentAmountEl) paymentAmountEl.value = money(total);
    }

    if (methodEl) methodEl.addEventListener('change', function () { refreshPaymentAccounts(); refreshChequeField(); });
    if (locationEl) locationEl.addEventListener('change', refreshPaymentAccounts);
    document.getElementById('rcm-purchase-order-form').addEventListener('input', function (e) {
        if (e.target.matches('.rcm-purchase-qty,.rcm-purchase-rate,.rcm-purchase-deduction,#rcm-purchase-other-charges')) recalcTotal();
    });
    document.getElementById('rcm-purchase-order-form').addEventListener('click', function () { setTimeout(recalcTotal,0); });

    refreshPaymentAccounts();
    refreshChequeField();
    recalcTotal();
});
</script>
@endsection
