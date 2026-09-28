@extends('RiceMill::layout')
@section('rcm-title','Customer Payments')
@section('rcm-actions')
    @if($canViewPaymentHistory)<a class="rcm-btn" href="{{ route('rice-mill.customer-accounts.payment-history') }}"><i class="fa fa-history"></i> Payment History</a>@endif
@endsection
@section('rcm-content')
@include('RiceMill::customer-accounts.partials.sync-note',['syncPendingCount'=>$rows->whereNull('transaction_id')->count()])

<div class="rcm-card">
    @include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-customer-payment-allocation-table','exportName'=>'rice-mill-customer-payment-outstanding','serverPaged'=>false,'rowsLabel'=>'outstanding invoices'])
    @include('RiceMill::customer-accounts.partials.filters',['filterAction'=>route('rice-mill.customer-accounts.payments'),'requireCustomer'=>true])
</div>

@if(!$selectedCustomerId)
    <div class="rcm-card rcm-muted">Select a Customer above. The system will load that customer's approved Rice Mill Sales Invoices with an outstanding balance.</div>
@elseif(!$coreReady)
    <div class="rcm-card rcm-muted">Customer payment entry is unavailable because the ERP receivable/payment tables are not available.</div>
@elseif(!$canCreatePayment)
    <div class="rcm-card rcm-muted">You can view the outstanding invoices, but your role does not have permission to create a Rice Mill Customer Payment.</div>
    <div class="rcm-card"><div class="rcm-table-wrap"><table id="rcm-customer-payment-allocation-table" class="rcm-table rcm-managed-table"><thead><tr><th>Date</th><th>Sales Invoice</th><th class="rcm-num">Invoice Amount</th><th class="rcm-num">Paid</th><th class="rcm-num">Outstanding</th><th>Finance Sync</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ \Illuminate\Support\Carbon::parse($row->dispatch_date)->format('Y-m-d') }}</td><td>{{ $row->dispatch_no }}</td><td class="rcm-num">{{ number_format($row->invoice_amount,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($row->paid_amount,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($row->outstanding_amount,$rcmCurrencyPrecision) }}</td><td>{{ $row->transaction_id?'Synced':'Pending' }}</td></tr>@empty<tr data-rcm-empty-row><td colspan="6" class="rcm-muted">No outstanding invoices found.</td></tr>@endforelse</tbody></table></div></div>
@else
<form method="post" action="{{ route('rice-mill.customer-accounts.payments.store') }}" id="rcm-customer-payment-form">
    @csrf
    <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">
    <div class="rcm-card">
        <div class="rcm-panel-head"><h3>Payment Details</h3><span class="rcm-panel-hint">The payment will be posted through the ERP customer payment/accounting engine.</span></div>
        <div class="rcm-form-grid">
            <div class="rcm-field"><label>Paid Date *</label><input type="date" name="paid_on" value="{{ old('paid_on',now()->format('Y-m-d')) }}" required></div>
            <div class="rcm-field"><label>Payment Method *</label><select name="method" id="rcm-payment-method" required>@foreach($paymentMethods as $key=>$label)<option value="{{ $key }}" {{ old('method','cash')===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Payment Account</label><select class="rcm-searchable" name="account_id" id="rcm-payment-account"><option value="">System Default / Select Account</option>@foreach($paymentAccounts as $id=>$name)<option value="{{ $id }}" {{ (int)old('account_id')===(int)$id?'selected':'' }}>{{ $name }}</option>@endforeach</select><small>Required for Cheque, Bank Transfer and Direct Bank Deposit.</small></div>
            <div class="rcm-field"><label>Cheque No.</label><input name="cheque_number" value="{{ old('cheque_number') }}"></div>
            <div class="rcm-field"><label>Cheque Date</label><input type="date" name="cheque_date" value="{{ old('cheque_date') }}"></div>
            <div class="rcm-field"><label>Bank Name</label><input name="bank_name" value="{{ old('bank_name') }}"></div>
            <div class="rcm-field"><label>Card Transaction No.</label><input name="card_transaction_number" value="{{ old('card_transaction_number') }}"></div>
            <div class="rcm-field"><label>Note</label><input name="note" value="{{ old('note') }}" maxlength="191"></div>
        </div>
    </div>

    <div class="rcm-card">
        <div class="rcm-panel-head"><h3>Outstanding Invoice Allocation</h3><span class="rcm-panel-hint">Select one or more Sales Invoices and enter the amount to apply to each bill.</span></div>
        <div class="rcm-table-wrap">
            <table id="rcm-customer-payment-allocation-table" class="rcm-table rcm-managed-table">
                <thead><tr><th data-rcm-no-export>Select</th><th>Date</th><th>Sales Invoice</th><th class="rcm-num">Invoice Amount</th><th class="rcm-num">Already Paid</th><th class="rcm-num">Outstanding</th><th>Finance Sync</th><th class="rcm-num" data-rcm-no-export>Amount to Allocate</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    @php $payable=!empty($row->transaction_id); @endphp
                    <tr data-rcm-payment-row data-outstanding="{{ $row->outstanding_amount }}">
                        <td><input type="checkbox" class="rcm-pay-select" {{ $payable?'':'disabled' }}></td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->dispatch_date)->format('Y-m-d') }}</td>
                        <td>{{ $row->dispatch_no }}</td>
                        <td class="rcm-num">{{ number_format($row->invoice_amount,$rcmCurrencyPrecision) }}</td>
                        <td class="rcm-num">{{ number_format($row->paid_amount,$rcmCurrencyPrecision) }}</td>
                        <td class="rcm-num"><strong>{{ number_format($row->outstanding_amount,$rcmCurrencyPrecision) }}</strong></td>
                        <td>{{ $payable?'Synced':'Pending - cannot pay yet' }}</td>
                        <td class="rcm-num"><input class="rcm-payment-allocation" type="number" name="allocations[{{ $row->dispatch_id }}]" value="{{ old('allocations.'.$row->dispatch_id) }}" min="0" max="{{ $row->outstanding_amount }}" step="{{ $rcmCurrencyStep }}" {{ $payable?'':'disabled' }} style="max-width:150px;text-align:right;"></td>
                    </tr>
                @empty
                    <tr data-rcm-empty-row><td colspan="8" class="rcm-muted">No outstanding synchronized Rice Mill Sales Invoices found for this customer and selected filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="rcm-summary-grid" style="margin-top:14px;">
            <div class="rcm-summary-box"><span>Selected Invoices</span><strong id="rcm-payment-selected-count">0</strong></div>
            <div class="rcm-summary-box"><span>Total Payment</span><strong id="rcm-payment-total">{{ number_format(0,$rcmCurrencyPrecision) }}</strong></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px;">
            <button type="button" class="rcm-btn" id="rcm-pay-all"><i class="fa fa-check-square-o"></i> Pay All Visible</button>
            <button type="submit" class="rcm-btn"><i class="fa fa-save"></i> Save Customer Payment</button>
        </div>
        <div class="rcm-muted" style="margin-top:10px;">The server rechecks every invoice balance before saving and will not allow an allocation above the current outstanding amount.</div>
    </div>
</form>
<script>
(function(){
    var rows=[].slice.call(document.querySelectorAll('[data-rcm-payment-row]'));
    var precision={{ (int)$rcmCurrencyPrecision }};
    function recalc(){
        var total=0,count=0;
        rows.forEach(function(row){
            var check=row.querySelector('.rcm-pay-select'), input=row.querySelector('.rcm-payment-allocation');
            if(!check||!input||input.disabled)return;
            var value=parseFloat(input.value||0)||0;
            check.checked=value>0;
            if(value>0){count++;total+=value;}
        });
        var c=document.getElementById('rcm-payment-selected-count'); if(c)c.textContent=count;
        var t=document.getElementById('rcm-payment-total'); if(t)t.textContent=total.toLocaleString(undefined,{minimumFractionDigits:precision,maximumFractionDigits:precision});
    }
    rows.forEach(function(row){
        var check=row.querySelector('.rcm-pay-select'), input=row.querySelector('.rcm-payment-allocation');
        if(!check||!input)return;
        check.addEventListener('change',function(){input.value=check.checked?(parseFloat(row.getAttribute('data-outstanding')||0)||0).toFixed(precision):'';recalc();});
        input.addEventListener('input',function(){var max=parseFloat(row.getAttribute('data-outstanding')||0)||0;var v=parseFloat(input.value||0)||0;if(v>max)input.value=max.toFixed(precision);recalc();});
    });
    var payAll=document.getElementById('rcm-pay-all'); if(payAll)payAll.addEventListener('click',function(){rows.forEach(function(row){var input=row.querySelector('.rcm-payment-allocation');if(input&&!input.disabled)input.value=(parseFloat(row.getAttribute('data-outstanding')||0)||0).toFixed(precision);});recalc();});
    var form=document.getElementById('rcm-customer-payment-form'); if(form)form.addEventListener('submit',function(e){var total=0;rows.forEach(function(row){var input=row.querySelector('.rcm-payment-allocation');if(input&&!input.disabled)total+=parseFloat(input.value||0)||0;});if(total<=0){e.preventDefault();alert('Please select at least one outstanding Sales Invoice and enter the amount to allocate.');return;}var method=document.getElementById('rcm-payment-method').value;var account=document.getElementById('rcm-payment-account').value;if(['cheque','bank_transfer','direct_bank_deposit'].indexOf(method)>=0&&!account){e.preventDefault();alert('Please select the Payment Account for this payment method.');}});
    recalc();
})();
</script>
@endif
@endsection
