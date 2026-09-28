@php
$isEdit=isset($payment);
$type=old('payment_type',$payment->payment_type ?? 'cash');
$cashRows=$isEdit?$payment->cashDenominations->keyBy(fn($row)=>(string)(float)$row->denomination):collect();
$cardRows=old('card_lines',$isEdit?$payment->cardLines->map(fn($row)=>$row->toArray())->all():[['card_type'=>'','last_four'=>'','slip_no'=>'','account_id'=>'','reference_no'=>'','amount'=>'']]);
$credit=$isEdit?$payment->creditSale:null;
$creditLines=old('lines',$credit?$credit->lines->map(fn($row)=>$row->toArray())->all():[['product_id'=>'','quantity'=>1,'unit_price'=>'','discount_amount'=>0]]);
@endphp
<form method="post" action="{{ $action }}" data-credit-confirm-form data-processing-text="{{ $isEdit?'Updating payment…':'Saving payment…' }}">
@csrf @if($isEdit) @method('PUT') @endif
<div class="pone-form-grid">
    <div class="pone-field pone-col-3"><label class="pone-required">Payment Type</label>
        @if($isEdit)<input type="hidden" name="payment_type" value="{{ $type }}"><select disabled><option>{{ ucfirst($type) }}</option></select>
        @else<select name="payment_type" data-payment-type required>
            <option value="cash" {{ $type==='cash'?'selected':'' }}>Cash</option>
            <option value="card" {{ $type==='card'?'selected':'' }}>Card</option>
            @if($settings['cheque_enabled']??true)<option value="cheque" {{ $type==='cheque'?'selected':'' }}>Cheque</option>@endif
            @if($settings['credit_sale_enabled']??true)<option value="credit" {{ $type==='credit'?'selected':'' }}>Credit Sale</option>@endif
            <option value="other" {{ $type==='other'?'selected':'' }}>Other</option>
        </select>@endif
    </div>
    <div class="pone-field pone-col-3"><label>Date / Time</label><input type="datetime-local" name="transaction_at" value="{{ old('transaction_at',optional($payment->transaction_at ?? null)->format('Y-m-d\\TH:i') ?: now()->format('Y-m-d\\TH:i')) }}"></div>
    <div class="pone-field pone-col-3"><label>Collection Form No.</label><input name="collection_form_no" maxlength="100" value="{{ old('collection_form_no',$payment->collection_form_no ?? $shift->collection_form_no) }}"></div>
    <div class="pone-field pone-col-3"><label>Account</label><select name="account_id"><option value="">Please select</option>@foreach($accounts as $account)<option value="{{ $account->id }}" {{ (string)old('account_id',$payment->account_id ?? '')===(string)$account->id?'selected':'' }}>{{ $account->name }} @if(!empty($account->account_number))· {{ $account->account_number }}@endif</option>@endforeach</select></div>
</div>

<div data-payment-section="cash" class="{{ $type==='cash'?'':'pone-section-hidden' }}" style="margin-top:14px">
    <fieldset class="pone-fieldset"><legend>Cash Denomination Count</legend>
        <div class="pone-table-wrap"><table class="pone-table pone-table-compact"><thead><tr><th>Denomination</th><th style="width:160px">Quantity</th><th class="pone-text-right">Amount</th></tr></thead><tbody>
        @foreach($denominations as $i=>$denomination) @php($saved=$cashRows->get((string)(float)$denomination))
        <tr data-denomination-row><td class="pone-number">{{ number_format($denomination,2) }}<input type="hidden" name="cash_denominations[{{ $i }}][denomination]" value="{{ $denomination }}" data-denomination></td><td><input type="number" min="0" step="1" name="cash_denominations[{{ $i }}][quantity]" value="{{ old('cash_denominations.'.$i.'.quantity',$saved->quantity ?? 0) }}" data-denomination-qty></td><td class="pone-text-right pone-number" data-denomination-amount>0.0000</td></tr>
        @endforeach</tbody><tfoot><tr><th colspan="2">Cash Count Total</th><th class="pone-text-right pone-number" data-cash-total>0.0000</th></tr></tfoot></table></div>
    </fieldset>
</div>

<div data-payment-section="card" class="{{ $type==='card'?'':'pone-section-hidden' }}" style="margin-top:14px">
    <fieldset class="pone-fieldset"><legend>Card Slips</legend>
        <div class="pone-table-wrap"><table class="pone-table pone-line-table" data-card-table><thead><tr><th>Card Type</th><th>Last 4</th><th>Slip No.</th><th>Account</th><th>Reference</th><th class="pone-text-right">Amount</th><th></th></tr></thead><tbody id="pone-card-rows" data-next-index="{{ count($cardRows) }}">
        @foreach($cardRows as $i=>$row)<tr data-card-row><td><input name="card_lines[{{ $i }}][card_type]" value="{{ $row['card_type']??'' }}" placeholder="Visa / Master"></td><td><input name="card_lines[{{ $i }}][last_four]" maxlength="4" inputmode="numeric" value="{{ $row['last_four']??'' }}"></td><td><input name="card_lines[{{ $i }}][slip_no]" value="{{ $row['slip_no']??'' }}"></td><td><select name="card_lines[{{ $i }}][account_id]"><option value="">Select</option>@foreach($accounts as $account)<option value="{{ $account->id }}" {{ (string)($row['account_id']??'')===(string)$account->id?'selected':'' }}>{{ $account->name }}</option>@endforeach</select></td><td><input name="card_lines[{{ $i }}][reference_no]" value="{{ $row['reference_no']??'' }}"></td><td><input class="pone-text-right" type="number" min="0" step="0.0001" name="card_lines[{{ $i }}][amount]" value="{{ $row['amount']??'' }}" data-card-amount></td><td><button type="button" class="pone-btn pone-btn-danger pone-btn-sm" data-remove-card>×</button></td></tr>@endforeach
        </tbody><tfoot><tr><th colspan="5">Card Total</th><th class="pone-text-right pone-number" data-card-total>0.0000</th><th></th></tr></tfoot></table></div>
        @if($settings['multi_card_enabled']??true)<div class="pone-actions" style="margin-top:9px"><button type="button" class="pone-btn pone-btn-light pone-btn-sm" data-add-card data-target="#pone-card-rows" data-template="#pone-card-template">+ Add Card Slip</button></div>@endif
    </fieldset>
</div>
<template id="pone-card-template"><tr data-card-row><td><input name="card_lines[__INDEX__][card_type]" placeholder="Visa / Master"></td><td><input name="card_lines[__INDEX__][last_four]" maxlength="4" inputmode="numeric"></td><td><input name="card_lines[__INDEX__][slip_no]"></td><td><select name="card_lines[__INDEX__][account_id]"><option value="">Select</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></td><td><input name="card_lines[__INDEX__][reference_no]"></td><td><input class="pone-text-right" type="number" min="0" step="0.0001" name="card_lines[__INDEX__][amount]" data-card-amount></td><td><button type="button" class="pone-btn pone-btn-danger pone-btn-sm" data-remove-card>×</button></td></tr></template>

<div data-payment-section="cheque" class="{{ $type==='cheque'?'':'pone-section-hidden' }}" style="margin-top:14px">
    <fieldset class="pone-fieldset"><legend>Cheque Details</legend><div class="pone-form-grid"><div class="pone-field pone-col-3"><label>Cheque No.</label><input name="cheque_no" value="{{ old('cheque_no',$payment->cheque_no ?? '') }}" data-required-for="cheque"></div><div class="pone-field pone-col-3"><label>Cheque Date</label><input type="date" name="cheque_date" value="{{ old('cheque_date',optional($payment->cheque_date ?? null)->format('Y-m-d')) }}" data-required-for="cheque"></div><div class="pone-field pone-col-3"><label>Bank</label><input name="bank_name" value="{{ old('bank_name',$payment->bank_name ?? '') }}"></div><div class="pone-field pone-col-3"><label>Reference</label><input name="reference_no" value="{{ old('reference_no',$payment->reference_no ?? '') }}"></div></div></fieldset>
</div>

<div data-payment-section="credit" data-credit-section class="{{ $type==='credit'?'':'pone-section-hidden' }}" style="margin-top:14px">
    <fieldset class="pone-fieldset"><legend>Credit Sale – Double Confirmation</legend>
        <input type="hidden" name="customer_confirmed" value="0"><input type="hidden" name="order_confirmed" value="0"><input type="hidden" name="vehicle_confirmed" value="0"><input type="hidden" name="confirmation_rounds" value="0">
        <div class="pone-confirm-box">Customer, Order No. and Vehicle must pass two confirmation prompts. Once saved, the credit sale is locked and cannot be edited.</div>
        <div class="pone-form-grid">
            <div class="pone-field pone-col-4"><label class="pone-required">Customer</label><select name="customer_id" data-required-for="credit"><option value="">Please select</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" {{ (string)old('customer_id',$payment->customer_id ?? '')===(string)$customer->id?'selected':'' }}>{{ $customer->name }} @if($customer->contact_id)· {{ $customer->contact_id }}@endif</option>@endforeach</select></div>
            <div class="pone-field pone-col-4"><label class="pone-required">Order No.</label><input name="order_number" value="{{ old('order_number',$credit->order_number ?? '') }}" data-required-for="credit"></div>
            <div class="pone-field pone-col-4"><label class="pone-required">Vehicle No.</label><input name="vehicle_number" value="{{ old('vehicle_number',$credit->vehicle_number ?? '') }}" data-required-for="credit"></div>
            <div class="pone-field pone-col-3"><label>Bill No.</label><input name="bill_number" value="{{ old('bill_number',$credit->bill_number ?? '') }}"></div>
            <div class="pone-field pone-col-3"><label>Customer Reference</label><input name="customer_reference" value="{{ old('customer_reference',$credit->customer_reference ?? '') }}"></div>
            <div class="pone-field pone-col-3"><label class="pone-required">Order Date</label><input type="date" name="order_date" value="{{ old('order_date',optional($credit->order_date ?? null)->format('Y-m-d') ?: now()->toDateString()) }}" data-required-for="credit"></div>
            <div class="pone-field pone-col-3"><label>Due Date</label><input type="date" name="due_date" value="{{ old('due_date',optional($credit->due_date ?? null)->format('Y-m-d')) }}"></div>
        </div>
        <div class="pone-table-wrap" style="margin-top:12px"><table class="pone-table pone-line-table" data-line-table><thead><tr><th>Product</th><th class="pone-text-right">Quantity</th><th class="pone-text-right">Unit Price</th><th class="pone-text-right">Discount</th><th class="pone-text-right">Amount</th><th></th></tr></thead><tbody id="pone-credit-lines" data-next-index="{{ count($creditLines) }}">@foreach($creditLines as $i=>$line)<tr data-line-row><td><select name="lines[{{ $i }}][product_id]" data-product-select><option value="">Select product</option>@foreach($products as $product)<option value="{{ $product->id }}" data-price="{{ $product->unit_price }}" {{ (string)($line['product_id']??'')===(string)$product->id?'selected':'' }}>{{ $product->name }} @if($product->sku)· {{ $product->sku }}@endif</option>@endforeach</select></td><td><input class="pone-text-right" type="number" min="0.000001" step="0.001" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity']??1 }}" data-line-qty></td><td><input class="pone-text-right" type="number" min="0" step="0.0001" name="lines[{{ $i }}][unit_price]" value="{{ $line['unit_price']??'' }}" data-line-price></td><td><input class="pone-text-right" type="number" min="0" step="0.0001" name="lines[{{ $i }}][discount_amount]" value="{{ $line['discount_amount']??0 }}" data-line-discount></td><td class="pone-text-right pone-line-total" data-line-total>0.0000</td><td><button type="button" class="pone-btn pone-btn-danger pone-btn-sm" data-remove-line>×</button></td></tr>@endforeach</tbody><tfoot><tr><th colspan="4">Credit Sale Total</th><th class="pone-text-right" data-grand-total>0.0000</th><th></th></tr></tfoot></table></div>
        <div class="pone-actions" style="margin-top:9px"><button type="button" class="pone-btn pone-btn-light pone-btn-sm" data-add-line data-target="#pone-credit-lines" data-template="#pone-credit-template">+ Add Product</button></div>
    </fieldset>
</div>
<template id="pone-credit-template"><tr data-line-row><td><select name="lines[__INDEX__][product_id]" data-product-select><option value="">Select product</option>@foreach($products as $product)<option value="{{ $product->id }}" data-price="{{ $product->unit_price }}">{{ $product->name }} @if($product->sku)· {{ $product->sku }}@endif</option>@endforeach</select></td><td><input class="pone-text-right" type="number" min="0.000001" step="0.001" name="lines[__INDEX__][quantity]" value="1" data-line-qty></td><td><input class="pone-text-right" type="number" min="0" step="0.0001" name="lines[__INDEX__][unit_price]" data-line-price></td><td><input class="pone-text-right" type="number" min="0" step="0.0001" name="lines[__INDEX__][discount_amount]" value="0" data-line-discount></td><td class="pone-text-right pone-line-total" data-line-total>0.0000</td><td><button type="button" class="pone-btn pone-btn-danger pone-btn-sm" data-remove-line>×</button></td></tr></template>

<div data-payment-section="cash,card,cheque,other" style="margin-top:14px">
    <div class="pone-form-grid">
        <div class="pone-field pone-col-3"><label>Gross Amount</label><input class="pone-text-right" type="number" min="0" step="0.0001" name="gross_amount" value="{{ old('gross_amount',$payment->gross_amount ?? '') }}"></div>
        <div class="pone-field pone-col-3"><label>Discount</label><input class="pone-text-right" type="number" min="0" step="0.0001" name="discount_amount" value="{{ old('discount_amount',$payment->discount_amount ?? 0) }}"></div>
        <div class="pone-field pone-col-3"><label class="pone-required">Net Amount</label><input class="pone-text-right" type="number" min="0" step="0.0001" name="amount" value="{{ old('amount',$payment->amount ?? '') }}"></div>
        <div class="pone-field pone-col-3"><label>General Reference</label><input name="reference_no" value="{{ old('reference_no',$payment->reference_no ?? '') }}"></div>
    </div>
</div>
<div class="pone-form-grid" style="margin-top:12px">
    <div class="pone-field pone-col-{{ $isEdit?8:12 }}"><label>Note</label><textarea name="note">{{ old('note',$payment->note ?? '') }}</textarea></div>
    @if($isEdit)<div class="pone-field pone-col-4"><label class="pone-required">Reason for Edit</label><textarea name="edit_reason" required>{{ old('edit_reason') }}</textarea></div>@endif
</div>
<div class="pone-actions pone-actions-end" style="margin-top:13px"><a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.payments.index') }}">Cancel</a><button type="submit" class="pone-btn pone-btn-success">{{ $isEdit?'Update Payment':'Save Payment' }}</button></div>
</form>
