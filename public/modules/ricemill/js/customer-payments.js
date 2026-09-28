(function () {
    'use strict';
    var form=document.getElementById('rcm-customer-payment-form');
    if(!form){return;}

    var customer=document.getElementById('rcm-payment-customer');
    var amount=document.getElementById('rcm-payment-amount');
    var body=document.querySelector('#rcm-payment-outstanding-table tbody');
    var save=document.getElementById('rcm-payment-save');
    var errorBox=document.getElementById('rcm-payment-allocation-error');
    var precision=parseInt(form.getAttribute('data-currency-precision') || '4',10);
    var preselect=parseInt(form.getAttribute('data-preselect-invoice') || '0',10);
    var endpoint=form.getAttribute('data-outstanding-url') || '';

    function number(v){var n=parseFloat(v);return isFinite(n)?n:0;}
    function money(v){return number(v).toLocaleString(undefined,{minimumFractionDigits:precision,maximumFractionDigits:precision});}
    function escapeHtml(value){var d=document.createElement('div');d.textContent=value==null?'':String(value);return d.innerHTML;}
    function showError(text){
        if(!errorBox)return;
        var d=errorBox.querySelector('div'); if(d){d.textContent=text||'';}
        errorBox.style.display=text?'flex':'none';
    }
    function totals(){
        var payment=number(amount && amount.value), allocated=0, count=0;
        body.querySelectorAll('tr[data-invoice-id]').forEach(function(row){
            var checkbox=row.querySelector('[data-allocate-check]');
            var input=row.querySelector('[data-allocate-amount]');
            var v=checkbox && checkbox.checked ? number(input.value) : 0;
            if(v>0){allocated+=v;count++;}
        });
        allocated=Math.round(allocated*10000)/10000;
        var advance=Math.max(0,Math.round((payment-allocated)*10000)/10000);
        document.getElementById('rcm-payment-total-card').textContent=money(payment);
        document.getElementById('rcm-payment-allocated-card').textContent=money(allocated);
        document.getElementById('rcm-payment-advance-card').textContent=money(advance);
        document.getElementById('rcm-payment-bills-card').textContent=String(count);
        if(allocated-payment>0.00005){showError('Allocated bill amounts cannot exceed the Payment Amount.'); if(save)save.disabled=true;}
        else{showError(''); if(save)save.disabled=false;}
        return {payment:payment,allocated:allocated,advance:advance};
    }
    function allocationFor(row){
        var checkbox=row.querySelector('[data-allocate-check]');
        var input=row.querySelector('[data-allocate-amount]');
        var max=number(input.getAttribute('data-max'));
        if(!checkbox.checked){input.value='0';input.disabled=true;totals();return;}
        input.disabled=false;
        var current=totals();
        var other=Math.max(0,current.allocated-number(input.value));
        var remaining=Math.max(0,current.payment-other);
        input.value=(Math.min(max,remaining>0?remaining:max)).toFixed(precision);
        totals();
    }
    function render(rows){
        body.innerHTML='';
        if(!rows || !rows.length){body.innerHTML='<tr data-empty><td colspan="8" class="rcm-muted">No outstanding approved Sales Invoices for this customer.</td></tr>';totals();return;}
        rows.forEach(function(r,index){
            var tr=document.createElement('tr'); tr.setAttribute('data-invoice-id',r.id);
            var checked=preselect && parseInt(r.id,10)===preselect;
            tr.innerHTML='<td><input type="checkbox" data-allocate-check '+(checked?'checked':'')+'></td>'+
                '<td><a href="'+escapeHtml(r.view_url)+'" target="_blank" rel="noopener">'+escapeHtml(r.invoice_no)+'</a></td>'+
                '<td>'+escapeHtml(r.invoice_date)+'</td><td>'+escapeHtml(r.due_date)+'</td>'+
                '<td class="rcm-num">'+money(r.invoice_amount)+'</td><td class="rcm-num">'+money(r.paid_amount)+'</td><td class="rcm-num"><strong>'+money(r.outstanding_amount)+'</strong></td>'+
                '<td class="rcm-num"><input type="number" data-allocate-amount data-max="'+r.outstanding_amount+'" name="allocations['+index+'][amount]" value="0" min="0" max="'+r.outstanding_amount+'" step="'+Math.pow(10,-precision)+'" disabled style="min-width:130px"><input type="hidden" data-dispatch-id name="allocations['+index+'][dispatch_id]" value="'+r.id+'"></td>';
            body.appendChild(tr);
            var cb=tr.querySelector('[data-allocate-check]'), input=tr.querySelector('[data-allocate-amount]');
            cb.addEventListener('change',function(){allocationFor(tr);});
            input.addEventListener('input',function(){
                var max=number(input.getAttribute('data-max')); if(number(input.value)>max){input.value=max.toFixed(precision);} totals();
            });
            if(checked){setTimeout(function(){allocationFor(tr);},0);}
        });
        preselect=0;
        totals();
    }
    function load(){
        var id=customer ? customer.value : '';
        if(!id){body.innerHTML='<tr data-empty><td colspan="8" class="rcm-muted">Select a customer to load outstanding Sales Invoices.</td></tr>';totals();return;}
        body.innerHTML='<tr data-empty><td colspan="8" class="rcm-muted"><i class="fa fa-spinner fa-spin"></i> Loading outstanding invoices...</td></tr>';
        var url=endpoint.replace('__CUSTOMER__',encodeURIComponent(id));
        fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'})
            .then(function(response){if(!response.ok){throw new Error('Unable to load outstanding invoices.');}return response.json();})
            .then(function(payload){render(payload.data || []);})
            .catch(function(err){body.innerHTML='<tr data-empty><td colspan="8" class="rcm-muted">'+escapeHtml(err.message)+'</td></tr>';});
    }
    if(customer){customer.addEventListener('change',load);}
    if(amount){amount.addEventListener('input',totals);}
    form.addEventListener('submit',function(e){
        var state=totals();
        if(state.payment<=0){e.preventDefault();showError('Enter a Payment Amount greater than zero.');return;}
        body.querySelectorAll('tr[data-invoice-id]').forEach(function(row){
            var cb=row.querySelector('[data-allocate-check]'), input=row.querySelector('[data-allocate-amount]'), dispatch=row.querySelector('[data-dispatch-id]');
            if(!cb || !cb.checked || number(input.value)<=0){input.disabled=true; if(dispatch){dispatch.disabled=true;}}
        });
    });
    totals();
    if(customer && customer.value){load();}
})();
