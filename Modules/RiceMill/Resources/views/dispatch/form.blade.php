@extends('RiceMill::layout')
@section('rcm-title','New Rice Sale / Dispatch')
@section('rcm-content')
<form method="post" action="{{ route('rice-mill.dispatch.store') }}" id="rcm-sales-form" data-currency-precision="{{ $rcmCurrencyPrecision }}">
    @csrf
    <div class="rcm-card">
        <div class="rcm-form-grid rcm-location-store-group">
            <div class="rcm-field"><label>Date</label><input type="date" name="dispatch_date" value="{{ old('dispatch_date',date('Y-m-d')) }}" required></div>
            <div class="rcm-field"><label>Customer</label><select name="customer_id" required><option value="">Select</option>@foreach($customers as $c)<option value="{{ $c['id'] }}" {{ (string)old('customer_id')===(string)$c['id']?'selected':'' }}>{{ $c['name'] }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Vehicle No</label><input name="vehicle_no" value="{{ old('vehicle_no') }}"></div>
            <div class="rcm-field"><label>Driver</label><input name="driver_name" value="{{ old('driver_name') }}"></div>
            <div class="rcm-field"><label>Location</label><select class="rcm-searchable rcm-location-select" name="location_id"><option value="">Select location</option>@foreach($locations as $x)<option value="{{ $x['id'] }}" {{ (string)old('location_id')===(string)$x['id']?'selected':'' }}>{{ $x['name'] }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Store</label><select class="rcm-searchable rcm-store-select" name="store_id"><option value="">Select store</option>@foreach($stores as $x)<option value="{{ $x['id'] }}" data-location-id="{{ $x['location_id'] ?? '' }}" {{ (string)old('store_id')===(string)$x['id']?'selected':'' }}>{{ $x['name'] }}</option>@endforeach</select></div>
        </div>
    </div>

    <div class="rcm-card">
        <h3 style="margin-top:0">Rice Sale Line &amp; Per Unit Discount</h3>
        <div class="rcm-form-grid">
            <div class="rcm-field">
                <label>Rice Product</label>
                <select name="lines[0][product_id]" required>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ (string)old('lines.0.product_id')===(string)$p->id?'selected':'' }}>{{ $p->name }} - {{ number_format($p->current_qty,$rcmQuantityPrecision) }} kg</option>
                    @endforeach
                </select>
            </div>
            <div class="rcm-field">
                <label>Quantity</label>
                <input id="rcm-sale-quantity" type="number" min="0" step="{{ $rcmQuantityStep }}" name="lines[0][quantity]" value="{{ old('lines.0.quantity') }}" required>
                @error('lines.0.quantity')<div class="rcm-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="rcm-field">
                <label>Unit Price</label>
                <input id="rcm-sale-unit-price" type="number" min="0" step="{{ $rcmCurrencyStep }}" name="lines[0][unit_price]" value="{{ old('lines.0.unit_price') }}" required>
                @error('lines.0.unit_price')<div class="rcm-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="rcm-field">
                <label>Per Unit Discount Type</label>
                <select id="rcm-sale-unit-discount-type" name="lines[0][unit_discount_type]" required>
                    <option value="percentage" {{ old('lines.0.unit_discount_type','fixed')==='percentage'?'selected':'' }}>Percentage</option>
                    <option value="fixed" {{ old('lines.0.unit_discount_type','fixed')==='fixed'?'selected':'' }}>Fixed</option>
                </select>
                @error('lines.0.unit_discount_type')<div class="rcm-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="rcm-field">
                <label>Per Unit Discount Value</label>
                <input id="rcm-sale-unit-discount-value" type="number" min="0" step="0.0001" name="lines[0][unit_discount_value]" value="{{ old('lines.0.unit_discount_value',0) }}">
                <small id="rcm-sale-unit-discount-help">Fixed means a fixed amount deducted from every unit.</small>
                @error('lines.0.unit_discount_value')<div class="rcm-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="rcm-field">
                <label>Discount / Unit</label>
                <input id="rcm-sale-unit-discount-per-unit" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Net Unit Price</label>
                <input id="rcm-sale-net-unit-price" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Gross Line Total</label>
                <input id="rcm-sale-gross-line-total" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Total Per Unit Discount</label>
                <input id="rcm-sale-unit-discount-total" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Line Total After Unit Discount</label>
                <input id="rcm-sale-line-total" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
        </div>
    </div>

    <div class="rcm-card">
        <h3 style="margin-top:0">Invoice Discount &amp; Tax</h3>
        <div class="rcm-form-grid">
            <div class="rcm-field">
                <label>Gross Subtotal</label>
                <input id="rcm-sale-subtotal" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Total Per Unit Discount</label>
                <input id="rcm-sale-total-unit-discount" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Subtotal After Unit Discount</label>
                <input id="rcm-sale-after-unit-discount" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Invoice Discount Type</label>
                <select id="rcm-sale-discount-type" name="discount_type" required>
                    <option value="percentage" {{ old('discount_type','fixed')==='percentage'?'selected':'' }}>Percentage</option>
                    <option value="fixed" {{ old('discount_type','fixed')==='fixed'?'selected':'' }}>Fixed</option>
                </select>
                @error('discount_type')<div class="rcm-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="rcm-field">
                <label>Invoice Discount Value</label>
                <input id="rcm-sale-discount-value" type="number" min="0" step="0.0001" name="discount_value" value="{{ old('discount_value',0) }}">
                <small id="rcm-sale-discount-help">Fixed means a fixed amount deducted from the invoice after Per Unit Discounts.</small>
                @error('discount_value')<div class="rcm-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="rcm-field">
                <label>Invoice Discount Amount</label>
                <input id="rcm-sale-discount-amount" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Taxable Amount</label>
                <input id="rcm-sale-taxable-amount" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
                <small>After both Per Unit Discount and Invoice Discount.</small>
            </div>
            <div class="rcm-field">
                <label>Tax %</label>
                <input id="rcm-sale-tax-percent" type="number" min="0" max="100" step="0.0001" name="tax_percent" value="{{ old('tax_percent',0) }}">
                <small>Tax is always percentage-based and calculated on the Taxable Amount.</small>
                @error('tax_percent')<div class="rcm-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="rcm-field">
                <label>Tax Amount</label>
                <input id="rcm-sale-tax-amount" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Net Total</label>
                <input id="rcm-sale-net-total" value="{{ number_format(0,$rcmCurrencyPrecision,'.',',') }}" readonly>
            </div>
            <div class="rcm-field">
                <label>Note</label>
                <textarea name="note">{{ old('note') }}</textarea>
            </div>
        </div>

        <div class="rcm-alert rcm-alert-info" style="margin-top:12px">
            <strong>Calculation:</strong> Gross Subtotal − Per Unit Discounts − Invoice Discount = Taxable Amount. Tax Amount = Taxable Amount × Tax %. Net Total = Taxable Amount + Tax Amount.
        </div>
        <div id="rcm-sale-calculation-message" class="rcm-field-error" style="display:none;margin-top:10px"></div>
    </div>

    @include('RiceMill::partials.operational-payment',[
        'paymentTitle'=>'Payment',
        'paymentMap'=>$salesPaymentMap ?? [],
        'paymentHelp'=>'Optional customer payment for this Sales / Dispatch. If the invoice is saved as Draft, the payment is held and posted only when the Sales Invoice is approved.'
    ])

    <div class="rcm-card">
        <button class="rcm-btn" id="rcm-sales-review-button"><i class="fa fa-eye"></i> Review Sales Invoice</button>
        <span class="rcm-muted" style="margin-left:10px">The draft will not be saved until you review the Sales Invoice.</span>
    </div>
</form>

<script>
(function(){
    var form=document.getElementById('rcm-sales-form');
    if(!form)return;
    var qty=document.getElementById('rcm-sale-quantity');
    var price=document.getElementById('rcm-sale-unit-price');
    var unitDiscountType=document.getElementById('rcm-sale-unit-discount-type');
    var unitDiscountValue=document.getElementById('rcm-sale-unit-discount-value');
    var unitDiscountHelp=document.getElementById('rcm-sale-unit-discount-help');
    var unitDiscountPerUnit=document.getElementById('rcm-sale-unit-discount-per-unit');
    var netUnitPrice=document.getElementById('rcm-sale-net-unit-price');
    var grossLineTotal=document.getElementById('rcm-sale-gross-line-total');
    var unitDiscountLineTotal=document.getElementById('rcm-sale-unit-discount-total');
    var lineTotal=document.getElementById('rcm-sale-line-total');
    var subtotalField=document.getElementById('rcm-sale-subtotal');
    var totalUnitDiscount=document.getElementById('rcm-sale-total-unit-discount');
    var afterUnitDiscount=document.getElementById('rcm-sale-after-unit-discount');
    var discountType=document.getElementById('rcm-sale-discount-type');
    var discountValue=document.getElementById('rcm-sale-discount-value');
    var discountAmount=document.getElementById('rcm-sale-discount-amount');
    var discountHelp=document.getElementById('rcm-sale-discount-help');
    var taxableAmount=document.getElementById('rcm-sale-taxable-amount');
    var taxPercent=document.getElementById('rcm-sale-tax-percent');
    var taxAmount=document.getElementById('rcm-sale-tax-amount');
    var netTotal=document.getElementById('rcm-sale-net-total');
    var message=document.getElementById('rcm-sale-calculation-message');
    var reviewButton=document.getElementById('rcm-sales-review-button');
    var precision=parseInt(form.getAttribute('data-currency-precision')||'4',10);
    if(!isFinite(precision)||precision<0)precision=4;

    function numeric(el){
        var n=parseFloat(el && el.value ? el.value : '0');
        return isFinite(n)?n:0;
    }
    // Match the server's DECIMAL(20,4) calculation precision exactly before
    // applying the Business Settings display precision.
    function r4(n){return Math.round((Number(n||0)+Number.EPSILON)*10000)/10000;}
    function money(n){
        return Number(n||0).toLocaleString('en-US',{minimumFractionDigits:precision,maximumFractionDigits:precision});
    }
    function calculate(){
        var q=Math.max(0,numeric(qty));
        var p=Math.max(0,numeric(price));
        var unitDValue=Math.max(0,numeric(unitDiscountValue));
        var invoiceDValue=Math.max(0,numeric(discountValue));
        var tax=Math.max(0,numeric(taxPercent));
        var error='';

        var perUnitDiscount=unitDiscountType.value==='percentage' ? r4(p*unitDValue/100) : r4(unitDValue);
        if(unitDiscountType.value==='percentage'){
            unitDiscountHelp.textContent='Percentage is calculated from the Unit Price for every unit.';
            unitDiscountValue.setAttribute('max','100');
            if(unitDValue>100)error='Unit Percentage Discount cannot be more than 100%.';
        }else{
            unitDiscountHelp.textContent='Fixed means a fixed amount deducted from every unit.';
            unitDiscountValue.removeAttribute('max');
            if(perUnitDiscount>p && perUnitDiscount>0)error='Fixed Unit Discount cannot be more than the Unit Price.';
        }

        var gross=r4(q*p);
        var unitDiscountTotal=r4(q*perUnitDiscount);
        if(unitDiscountTotal>gross)unitDiscountTotal=gross;
        var netUnit=r4(Math.max(0,p-perUnitDiscount));
        var afterUnit=r4(Math.max(0,gross-unitDiscountTotal));

        var invoiceDiscount=discountType.value==='percentage' ? r4(afterUnit*invoiceDValue/100) : r4(invoiceDValue);
        if(discountType.value==='percentage'){
            discountHelp.textContent='Percentage is calculated on the Subtotal After Unit Discount.';
            discountValue.setAttribute('max','100');
            if(invoiceDValue>100)error='Invoice Percentage Discount cannot be more than 100%.';
        }else{
            discountHelp.textContent='Fixed means a fixed amount deducted after Per Unit Discounts.';
            discountValue.removeAttribute('max');
            if(invoiceDiscount>afterUnit && invoiceDiscount>0)error='Fixed Invoice Discount cannot be more than the subtotal after Unit Discounts.';
        }
        if(tax>100)error='Tax percentage cannot be more than 100%.';

        var taxable=r4(Math.max(0,afterUnit-Math.min(invoiceDiscount,afterUnit)));
        var tAmount=r4(taxable*tax/100);
        var total=r4(taxable+tAmount);

        unitDiscountPerUnit.value=money(perUnitDiscount);
        netUnitPrice.value=money(netUnit);
        grossLineTotal.value=money(gross);
        unitDiscountLineTotal.value=money(unitDiscountTotal);
        lineTotal.value=money(afterUnit);
        subtotalField.value=money(gross);
        totalUnitDiscount.value=money(unitDiscountTotal);
        afterUnitDiscount.value=money(afterUnit);
        discountAmount.value=money(invoiceDiscount);
        taxableAmount.value=money(taxable);
        taxAmount.value=money(tAmount);
        netTotal.value=money(total);

        if(error){
            message.textContent=error;
            message.style.display='block';
            reviewButton.disabled=true;
        }else{
            message.textContent='';
            message.style.display='none';
            reviewButton.disabled=false;
        }
    }
    [qty,price,unitDiscountType,unitDiscountValue,discountType,discountValue,taxPercent].forEach(function(el){
        if(!el)return;
        el.addEventListener('input',calculate);
        el.addEventListener('change',calculate);
    });
    calculate();
})();
</script>
@endsection
