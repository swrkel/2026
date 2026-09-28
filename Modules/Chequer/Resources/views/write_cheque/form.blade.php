@extends('chequer::layouts.app')
@section('title','Write Cheque')
@section('chequer_content')
<div class="cheq-top">
    <div>
        <div class="cheq-title">Write Cheque</div>
        <div class="cheq-sub">Same business fields from the current Write Cheque page, rewritten inside the standalone Chequer module.</div>
    </div>
    <a class="cheq-btn gray" href="{{ url('/chequer-module/write-cheque') }}">Back</a>
</div>

<form class="cheq-card" id="write-cheque-form" method="post" enctype="multipart/form-data" action="{{ url('/chequer-module/write-cheque') }}">
    @csrf
    @if($books->count() == 0)
        <div class="cheq-alert" style="background:#fff7ed;color:#9a3412;border-color:#fed7aa">No active cheque book with available cheque leaves found. Please add a cheque book first.</div>
    @endif

    <div class="cheq-section-title">Payment information</div>
    <div class="cheq-form-grid cheq-form-grid-tight">
        <div class="cheq-field">
            <label>Select Payee Type</label>
            <select class="cheq-select" name="payee_type" id="payee_type">
                <option value="">Select Payee Type</option>
                <option value="supplier">Supplier</option>
                <option value="customer">Customer</option>
                <option value="employee">Employee</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="cheq-field">
            <label>Select Payee Name <span class="req">*</span></label>
            <select class="cheq-select" name="payee_id" id="payee_id">
                <option value="">Select Payee</option>
                @foreach($payees as $payee)
                    <option value="{{ $payee->id }}" data-type="{{ $payee->type ?? '' }}" data-name="{{ $payee->name }}">{{ $payee->name }} @if(!empty($payee->type)) ({{ ucfirst($payee->type) }}) @endif</option>
                @endforeach
            </select>
            <input type="hidden" name="payee_name" id="payee_name">
        </div>
        <div class="cheq-field">
            <label>Payment For</label>
            <select class="cheq-select" name="payment_for" id="payment_for">
                <option value="">Payment For</option>
                @foreach($paymentForOptions as $val=>$lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
            </select>
        </div>
        <div class="cheq-field">
            <label>Payment Type <span class="req">*</span></label>
            <select class="cheq-select" name="payment_type" id="payment_type" required>
                <option value="">Payment Type</option>
                @foreach($paymentTypeOptions as $val=>$lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
            </select>
        </div>
        <div class="cheq-field">
            <label>Select Purchase Order</label>
            <input class="cheq-input" name="purchase_id" id="purchase_id" placeholder="Not Available / enter reference">
        </div>
        <div class="cheq-field">
            <label>Bill Number</label>
            <input class="cheq-input" name="purchase_bill_no" id="purchase_bill_no">
        </div>
        <div class="cheq-field">
            <label>Supplier Order Number</label>
            <input class="cheq-input" name="supplier_order_no" id="supplier_order_no">
        </div>
        <div class="cheq-field">
            <label>Unpaid Amount</label>
            <input class="cheq-input text-right" type="number" step="0.01" name="payable_amount" id="payable_amount">
        </div>
        <div class="cheq-field">
            <label>Payment Status</label>
            <select class="cheq-select" name="payment_status" id="payment_status">
                <option value="">Select Payment Status</option>
                <option value="full">Full Payment</option>
                <option value="partial">Partial Payment</option>
                <option value="last">Last Payment</option>
            </select>
        </div>
        <div class="cheq-field">
            <label>Business Outlet</label>
            <input class="cheq-input" name="business_outlet" id="business_outlet" placeholder="Optional">
        </div>
        <div class="cheq-field">
            <label>Choose File</label>
            <input class="cheq-input" type="file" name="attachment" id="attachment">
        </div>
    </div>

    <div class="cheq-section-title">Cheque setup</div>
    <div class="cheq-form-grid cheq-form-grid-tight">
        <div class="cheq-field">
            <label>Cheque Book <span class="req">*</span></label>
            <select class="cheq-select" name="cheq_cheque_book_id" id="cheque_book" required {{ $books->count() == 0 ? 'disabled' : '' }}>
                @foreach($books as $b)
                    <option value="{{ $b->id }}" data-next="{{ $b->next_no }}" data-bank="{{ $b->account_id }}">
                        {{ $b->bank_account_name ?? 'Bank Account' }} @if(!empty($b->account_number)) - {{ $b->account_number }} @endif / {{ $b->book_no }} (Next: {{ $b->next_no }}, Available: {{ $b->available_leaves ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="cheq-field">
            <label>Cheque No</label>
            <input class="cheq-input locked" name="cheque_no_display" id="cheque_no_display" value="{{ $next_no }}" readonly tabindex="-1">
        </div>
        <div class="cheq-field">
            <label>Select Bank Account</label>
            <select class="cheq-select" name="bank_account_id" id="bank_account_id">
                <option value="">Select Bank Account</option>
                @foreach($bankAccounts as $id=>$label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="cheq-field">
            <label>Choose Template</label>
            <select class="cheq-select" name="cheq_template_id" id="cheq_template_id">
                <option value="">Select Template</option>
                @foreach($templates as $template)
                    <option value="{{ $template->id }}" data-map='@json(json_decode($template->field_map ?? "{}", true))'>{{ $template->template_name }} @if($template->bank_name) - {{ $template->bank_name }} @endif</option>
                @endforeach
            </select>
        </div>
        <div class="cheq-field">
            <label>Enter Amount <span class="req">*</span></label>
            <input class="cheq-input text-right" type="number" min="0.01" step="0.01" name="amount" id="amount" required>
        </div>
        <div class="cheq-field">
            <label>Amount in Words</label>
            <input class="cheq-input" name="amount_words" id="amount_words" readonly>
        </div>
        <div class="cheq-field">
            <label>Select Stamp / Seal</label>
            <select class="cheq-select" name="stamp_id" id="stamp_id"><option value="">None</option>@foreach($stamps as $stamp)<option value="{{ $stamp->id ?? $stamp->name }}">{{ $stamp->name ?? 'Stamp' }}</option>@endforeach</select>
        </div>
        <div class="cheq-field">
            <label>Date Condition</label>
            <select class="cheq-select" name="date_condition" id="date_condition"><option value="select_date">Select Date</option><option value="today">Today</option></select>
        </div>
        <div class="cheq-field">
            <label>Select Date <span class="req">*</span></label>
            <input class="cheq-input" type="date" name="cheque_date" id="cheque_date" value="{{ date('Y-m-d') }}" required>
        </div>
        <div class="cheq-field">
            <label>Currency</label>
            <select class="cheq-select" name="currency_id" id="currency_id">
                @foreach($currencies as $currency)
                    <option value="{{ $currency->id }}" data-code="{{ $currency->code ?? '' }}" data-symbol="{{ $currency->symbol ?? '' }}">{{ $currency->code ?? 'LKR' }} @if(!empty($currency->symbol)) - {{ $currency->symbol }} @endif</option>
                @endforeach
            </select>
            <input type="hidden" name="currency_code" id="currency_code" value="LKR">
        </div>
        <div class="cheq-field">
            <label>On Account Of</label>
            <input class="cheq-input" name="on_account_of" id="on_account_of">
        </div>
        <div class="cheq-field">
            <label>Date & Time</label>
            <input class="cheq-input" name="date_time" id="date_time" value="{{ date('d-m-Y H:i') }}" readonly>
        </div>
        <div class="cheq-field">
            <label>Double Entry Account</label>
            <select class="cheq-select" name="double_entry_account_id" id="double_entry_account_id"><option value="">Select Account</option>@foreach($doubleEntryAccounts as $acc)<option value="{{ $acc->id }}">{{ $acc->name }} @if(!empty($acc->account_number)) - {{ $acc->account_number }} @endif</option>@endforeach</select>
        </div>
        <div class="cheq-field" style="grid-column:1/-1">
            <label>Memo / Narration</label>
            <textarea class="cheq-textarea" name="memo" id="memo"></textarea>
        </div>
    </div>

    <div class="cheq-section-title">Cheque options</div>
    <div class="cheq-template-options">
        <label><input type="checkbox" name="post_date_cheque" id="post_date_cheque"> Post Date Cheque</label>
        <label><input type="checkbox" name="show_in_account" id="show_in_account" checked> Show in Accounts</label>
        <label><input type="checkbox" name="not_negotiable" id="not_negotiable"> Not Negotiable</label>
        <label><input type="checkbox" name="account_payee_only" id="account_payee_only"> A/C Payee Only</label>
        <label><input type="checkbox" name="strike_bearer" id="strike_bearer"> Strike Bearer</label>
        <label><input type="checkbox" name="cross_cheque" id="cross_cheque"> Cross Cheque</label>
        <label><input type="checkbox" name="prefix" id="prefix"> Prefix</label>
        <label><input type="checkbox" name="double_cross_cheque" id="double_cross_cheque"> Double Cross Cheque</label>
        <label><input type="checkbox" name="suffix" id="suffix"> Suffix</label>
        <label><input type="checkbox" name="remove_currency" id="remove_currency"> Remove Currency</label>
        <label><input type="checkbox" name="print_signature" id="print_signature"> Print Signature</label>
        <label><input type="checkbox" name="stubs" id="stubs"> Stubs</label>
        <label><input type="checkbox" name="delete_reverse" id="delete_reverse"> Delete Reverse</label>
    </div>

    <div class="cheq-preview-card">
        <div class="cheq-section-title">Cheque Preview</div>
        <div id="cheque_preview" class="cheq-cheque-preview">
            <div class="preview-field" id="prev_payee" style="top:78px;left:50px;width:416px;height:30px">Payee Name</div>
            <div class="preview-field" id="prev_date" style="top:30px;left:480px;width:130px;height:26px">{{ date('d-m-Y') }}</div>
            <div class="preview-field" id="prev_amount" style="top:120px;left:500px;width:120px;height:28px">0.00</div>
            <div class="preview-field" id="prev_words" style="top:145px;left:50px;width:520px;height:45px">Amount in words</div>
            <div class="preview-field opt opt-ac" style="top:25px;left:75px;width:140px;height:22px;display:none">A/C PAYEE ONLY</div>
            <div class="preview-field opt opt-nn" style="top:50px;left:75px;width:145px;height:22px;display:none">NOT NEGOTIABLE</div>
            <div class="preview-field opt opt-dc" style="top:15px;left:25px;width:25px;height:35px;display:none">//</div>
        </div>
    </div>

    <div class="cheq-actions-row">
        <button class="cheq-btn green" type="submit" name="print_action" value="save" {{ $books->count() == 0 ? 'disabled' : '' }}>Save Cheque</button>
        <button class="cheq-btn blue" type="submit" name="print_action" value="print_data_only" {{ $books->count() == 0 ? 'disabled' : '' }}>Print Data Only</button>
        <button class="cheq-btn orange" type="submit" name="print_action" value="pre_print" {{ $books->count() == 0 ? 'disabled' : '' }}>Pre Print</button>
        <button class="cheq-btn purple" type="submit" name="print_action" value="print_cheque" {{ $books->count() == 0 ? 'disabled' : '' }}>Print Cheque</button>
        <button class="cheq-btn gray" type="submit" name="print_action" value="print_voucher" {{ $books->count() == 0 ? 'disabled' : '' }}>Print Voucher</button>
    </div>
</form>

<style>
.cheq-section-title{font-weight:900;font-size:17px;color:#0f172a;margin:0 0 14px}.req{color:#ef4444}.cheq-form-grid-tight{grid-template-columns:repeat(4,minmax(180px,1fr));gap:14px}.locked{background:#f1f5f9!important;color:#64748b!important;cursor:not-allowed}.text-right{text-align:right}.cheq-template-options{display:flex;flex-wrap:wrap;gap:12px;margin:18px 0}.cheq-template-options label{background:#f8fafc;border:1px solid #dbe7f5;border-radius:14px;padding:10px 13px;font-weight:800}.cheq-preview-card{background:#f8fbff;border:1px solid #dbe7f5;border-radius:18px;padding:18px;margin-top:18px}.cheq-cheque-preview{position:relative;background:#fff;border:1px solid #334155;width:650px;height:320px;max-width:100%;overflow:auto;background-size:100% 100%;box-shadow:0 14px 30px rgba(15,23,42,.12)}.preview-field{position:absolute;border:1px dashed #64748b;background:rgba(255,255,255,.65);padding:4px 6px;font-weight:800;color:#000}.cheq-actions-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:18px}@media(max-width:1000px){.cheq-form-grid-tight{grid-template-columns:repeat(2,minmax(180px,1fr))}}
</style>
<script>
(function(){
    function q(id){return document.getElementById(id)}
    function words(n){
        n = parseFloat(n||0); if(!n) return '';
        const a=['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        const b=['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        function inWords(num){ if(num<20)return a[num]; if(num<100)return b[Math.floor(num/10)]+' '+a[num%10]; if(num<1000)return a[Math.floor(num/100)]+' Hundred '+inWords(num%100); if(num<100000)return inWords(Math.floor(num/1000))+' Thousand '+inWords(num%1000); if(num<10000000)return inWords(Math.floor(num/100000))+' Lakh '+inWords(num%100000); return inWords(Math.floor(num/10000000))+' Crore '+inWords(num%10000000); }
        let whole=Math.floor(n), cents=Math.round((n-whole)*100); return (inWords(whole)+' Rupees'+(cents?' and '+inWords(cents)+' Cents':'')+' Only').replace(/\s+/g,' ').trim();
    }
    function updatePreview(){
        const payee = q('payee_id').selectedOptions[0]?.dataset.name || q('payee_name').value || 'Payee Name';
        q('payee_name').value = payee;
        q('prev_payee').innerText = payee;
        q('prev_amount').innerText = parseFloat(q('amount').value||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
        const aw = words(q('amount').value); q('amount_words').value=aw; q('prev_words').innerText=aw||'Amount in words';
        q('prev_date').innerText = q('cheque_date').value || '';
        document.querySelector('.opt-ac').style.display = q('account_payee_only').checked ? 'block':'none';
        document.querySelector('.opt-nn').style.display = q('not_negotiable').checked ? 'block':'none';
        document.querySelector('.opt-dc').style.display = q('double_cross_cheque').checked ? 'block':'none';
        const curr = q('currency_id').selectedOptions[0]; if(curr){q('currency_code').value=curr.dataset.code||'';}
    }
    ['payee_id','amount','cheque_date','account_payee_only','not_negotiable','double_cross_cheque','currency_id'].forEach(id=>{ if(q(id)) q(id).addEventListener('change',updatePreview); if(q(id)) q(id).addEventListener('input',updatePreview); });
    q('cheque_book')?.addEventListener('change', function(){
        const opt=this.options[this.selectedIndex]; q('cheque_no_display').value=opt?.dataset.next||''; if(opt?.dataset.bank){ q('bank_account_id').value=opt.dataset.bank; }
    });
    q('cheq_template_id')?.addEventListener('change', function(){
        const opt=this.options[this.selectedIndex]; if(!opt || !opt.dataset.map) return;
        let map={}; try{map=JSON.parse(opt.dataset.map||'{}')}catch(e){}
        if(map.template_image){ q('cheque_preview').style.backgroundImage='url(/storage/'+map.template_image+')'; }
        const fields=map.fields||{};
        const fieldLookup={payee:'prev_payee',date:'prev_date',amount:'prev_amount',amount_words:'prev_words',account_payee:'opt-ac',not_negotiable:'opt-nn',double_cross:'opt-dc'};
        Object.keys(fieldLookup).forEach(function(k){ const f=fields[k]; const el=document.getElementById(fieldLookup[k]); if(f&&el){ el.style.top=(f.top||0)+'px'; el.style.left=(f.left||0)+'px'; el.style.width=(f.width||120)+'px'; el.style.height=(f.height||28)+'px'; el.style.fontSize=(f.font_size||14)+'px'; }});
        updatePreview();
    });
    updatePreview();
})();
</script>
@endsection
