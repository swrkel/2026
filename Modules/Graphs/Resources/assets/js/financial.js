(function(){
'use strict';
const cfg=window.GRAPHS_FINANCIAL_CONFIG||{};
const root=document.querySelector('.gr-financial-page');
if(!root||!window.Chart)return;

const $=id=>document.getElementById(id);
const precision=Math.max(0,Math.min(6,Number(cfg.currencyPrecision||2)));
const money=new Intl.NumberFormat('en-US',{minimumFractionDigits:precision,maximumFractionDigits:precision});
const qty=new Intl.NumberFormat('en-US',{minimumFractionDigits:3,maximumFractionDigits:3});
const pct=new Intl.NumberFormat('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
const colors={navy:'#506a88',blue:'#3e6fa8',green:'#19807b',teal:'#2e8f8a',gold:'#c18b38',orange:'#c4773f',red:'#b4574f',violet:'#7969a8',slate:'#70859b',light:'#dbe4ee'};
let reconciliationChart=null,profitabilityChart=null,ageingChart=null;
let selectedBucket='0_30';
let ageingBuckets=[];

Chart.defaults.font.family='Calibri, "Segoe UI", Arial, sans-serif';
Chart.defaults.color='#5f7185';

function esc(v){return String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
function n(v){const x=Number(v);return Number.isFinite(x)?x:0;}
function fmt(v){return money.format(n(v));}
function fmtSigned(v){const x=n(v);return (x>0?'+':'')+fmt(x);}
function set(id,value){const el=$(id);if(el)el.textContent=value;}
function destroy(chart){if(chart)chart.destroy();}
function params(extra){
    const p=new URLSearchParams({
        start_date:$('grFinStartDate').value,
        end_date:$('grFinEndDate').value
    });
    const loc=$('grFinLocation').value;if(loc)p.set('location_id',loc);
    if(extra)Object.entries(extra).forEach(([k,v])=>{if(v!==undefined&&v!==null)p.set(k,String(v));});
    return p;
}
async function get(url,extra){
    const r=await fetch(url+'?'+params(extra).toString(),{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'});
    if(!r.ok)throw new Error('Request failed ('+r.status+')');
    return r.json();
}
function showStatus(message){
    const el=$('grFinStatusMessage');if(!el)return;
    el.textContent=message;el.hidden=false;
    window.clearTimeout(showStatus._t);showStatus._t=window.setTimeout(()=>{el.hidden=true;},5500);
}
function baseOptions(yTitle){
    return {
        responsive:true,maintainAspectRatio:false,
        interaction:{mode:'index',intersect:false},
        plugins:{
            legend:{position:'top',align:'end',labels:{boxWidth:10,boxHeight:10,usePointStyle:true,padding:14,font:{size:11,weight:'600'},color:'#52677e'}},
            tooltip:{backgroundColor:'rgba(30,49,70,.95)',padding:10,cornerRadius:6,titleFont:{size:12,weight:'700'},bodyFont:{size:11}}
        },
        scales:{
            x:{grid:{display:false},ticks:{color:'#718399',font:{size:10},maxRotation:0,autoSkip:false}},
            y:{beginAtZero:true,grid:{color:'rgba(126,147,169,.13)'},ticks:{color:'#718399',font:{size:10},callback:v=>money.format(v)},title:{display:!!yTitle,text:yTitle,color:'#657a91',font:{size:10,weight:'700'}}}
        }
    };
}
function valueClass(v){return n(v)>0?'gr-value-positive':n(v)<0?'gr-value-negative':'';}

async function loadReconciliation(){
    const j=await get(cfg.reconciliationUrl),s=j.summary||{},rows=j.daily||[];
    set('finFuelSales',fmt(s.fuel_sales));set('finCashSales',fmt(s.cash_sales));set('finCardSales',fmt(s.card_sales));set('finCreditSales',fmt(s.credit_sales));set('finBankDeposited',fmt(s.bank_deposited));set('finVariation',fmtSigned(s.variation));
    const finVar=$('finVariation');if(finVar)finVar.className=valueClass(s.variation);
    set('recFuel',fmt(s.fuel_sales));set('recCard',fmt(s.card_sales));set('recCredit',fmt(s.credit_sales));set('recExpected',fmt(s.expected_cash));set('recCash',fmt(s.cash_sales));set('recBank',fmt(s.bank_deposited));set('recCashAfter',fmt(s.cash_after_deposit));set('recVariation',fmtSigned(s.variation));
    const recVar=$('recVariation');if(recVar)recVar.className=valueClass(s.variation);

    const fuel=n(s.fuel_sales),card=n(s.card_sales),credit=n(s.credit_sales),expected=n(s.expected_cash),cash=n(s.cash_sales),variation=n(s.variation),bank=n(s.bank_deposited),after=n(s.cash_after_deposit);
    const data=[
        [0,fuel],
        [fuel-card,fuel],
        [expected,fuel-card],
        [0,expected],
        variation>=0?[expected,cash]:[cash,expected],
        [0,cash],
        [cash-bank,cash],
        [0,after]
    ];
    destroy(reconciliationChart);
    const opt=baseOptions('Amount');
    opt.plugins.legend.display=false;
    opt.plugins.tooltip.callbacks={label:function(ctx){const raw=ctx.raw;if(Array.isArray(raw))return ' '+fmt(Math.abs(n(raw[1])-n(raw[0])));return ' '+fmt(raw);}};
    opt.scales.x.ticks.maxRotation=35;
    opt.scales.x.ticks.minRotation=0;
    reconciliationChart=new Chart($('reconciliationChart').getContext('2d'),{
        type:'bar',
        data:{labels:['Fuel Sales','Less Card','Less Credit','Expected Cash','Variation','Recorded Cash','Bank Deposit','Cash After Deposit'],datasets:[{data:data,backgroundColor:[colors.blue,colors.violet,colors.orange,colors.gold,variation<0?colors.red:colors.green,colors.teal,colors.slate,colors.navy],borderColor:[colors.blue,colors.violet,colors.orange,colors.gold,variation<0?colors.red:colors.green,colors.teal,colors.slate,colors.navy],borderWidth:1,borderRadius:3,maxBarThickness:52}]},
        options:opt
    });

    const tbody=$('reconciliationRows');
    tbody.innerHTML=rows.length?rows.map(r=>`<tr>
        <td>${esc(r.date)}</td>
        <td class="gr-num">${fmt(r.fuel_sales)}</td>
        <td class="gr-num">${fmt(r.cash_sales)}</td>
        <td class="gr-num">${fmt(r.card_sales)}</td>
        <td class="gr-num">${fmt(r.credit_sales)}</td>
        <td class="gr-num">${fmt(r.expected_cash)}</td>
        <td class="gr-num">${fmt(r.bank_deposited)}</td>
        <td class="gr-num ${valueClass(r.variation)}">${fmtSigned(r.variation)}</td>
    </tr>`).join(''):'<tr><td colspan="8" class="gr-table-empty">No finalized reconciliation data found for the selected filters.</td></tr>';
}

async function loadProfitability(){
    const j=await get(cfg.profitabilityUrl),rows=j.rows||[],t=j.totals||{};
    set('profitSales',fmt(t.sales_amount));set('profitCost',fmt(t.cost_of_sales));set('profitCommission',fmt(t.commission_income));set('profitNet',fmt(t.net_profit));set('profitMargin',pct.format(n(t.margin_pct))+'%');
    const net=$('profitNet');if(net)net.className=valueClass(t.net_profit);

    destroy(profitabilityChart);
    const opt=baseOptions('Amount');
    opt.scales.x.stacked=true;opt.scales.y.stacked=true;
    opt.plugins.tooltip.callbacks={label:ctx=>' '+ctx.dataset.label+': '+fmt(ctx.parsed.y)};
    profitabilityChart=new Chart($('profitabilityChart').getContext('2d'),{
        type:'bar',
        data:{labels:rows.map(r=>r.subcategory),datasets:[
            {label:'Cost of Sales',data:rows.map(r=>n(r.cost_of_sales)),backgroundColor:'rgba(112,133,155,.72)',borderColor:colors.slate,borderWidth:1,borderRadius:3,maxBarThickness:58},
            {label:'Net Profit / Commission Income',data:rows.map(r=>n(r.net_profit)),backgroundColor:'rgba(25,128,123,.78)',borderColor:colors.green,borderWidth:1,borderRadius:3,maxBarThickness:58}
        ]},
        options:opt
    });

    const tbody=$('profitabilityRows');
    tbody.innerHTML=rows.length?rows.map(r=>`<tr>
        <td><strong>${esc(r.subcategory)}</strong></td>
        <td class="gr-num">${qty.format(n(r.qty))}</td>
        <td class="gr-num">${fmt(r.sales_amount)}</td>
        <td class="gr-num">${fmt(r.cost_of_sales)}</td>
        <td class="gr-num ${valueClass(r.commission_income)}">${fmt(r.commission_income)}</td>
        <td class="gr-num ${valueClass(r.net_profit)}">${fmt(r.net_profit)}</td>
        <td class="gr-num ${valueClass(r.margin_pct)}">${pct.format(n(r.margin_pct))}%</td>
        <td class="gr-num">${fmt(r.commission_per_unit)}</td>
    </tr>`).join(''):'<tr><td colspan="8" class="gr-table-empty">No finalized fuel sales found for profitability analysis.</td></tr>';
}

function bucketLabel(key){const b=ageingBuckets.find(x=>x.key===key);return b?b.label:key;}
async function loadAgeingCustomers(bucket){
    selectedBucket=bucket||selectedBucket;
    const j=await get(cfg.ageingCustomersUrl,{bucket:selectedBucket}),rows=j.rows||[];
    set('ageingCustomerTitle','Customers — '+bucketLabel(selectedBucket));
    set('ageingCustomerSubtitle','Customers included in the selected ageing bar as of '+(j.as_of||$('grFinEndDate').value)+'.');
    set('ageingBucketTotal',fmt(j.total));
    const tbody=$('ageingCustomerRows');
    tbody.innerHTML=rows.length?rows.map(r=>`<tr>
        <td>${esc(r.customer_code||'—')}</td>
        <td><strong>${esc(r.customer_name||'Unnamed Customer')}</strong></td>
        <td>${esc(r.mobile||'—')}</td>
        <td class="gr-num">${n(r.invoices).toFixed(0)}</td>
        <td class="gr-num">${n(r.min_age_days)===n(r.max_age_days)?n(r.max_age_days).toFixed(0):n(r.min_age_days).toFixed(0)+'–'+n(r.max_age_days).toFixed(0)} days</td>
        <td class="gr-num"><strong>${fmt(r.outstanding)}</strong></td>
    </tr>`).join(''):'<tr><td colspan="6" class="gr-table-empty">No customers found in this ageing period.</td></tr>';
    document.querySelectorAll('.gr-ageing-badge').forEach(b=>b.classList.toggle('active',b.dataset.bucket===selectedBucket));
}
async function loadAgeing(){
    const j=await get(cfg.ageingUrl);ageingBuckets=j.buckets||[];
    set('ageingOutstanding',fmt(j.total_outstanding));set('ageingAsOf','As of '+(j.as_of||$('grFinEndDate').value));

    const badgeWrap=$('ageingBadges');
    badgeWrap.innerHTML=ageingBuckets.map(b=>`<button type="button" class="gr-ageing-badge ${b.key===selectedBucket?'active':''}" data-bucket="${esc(b.key)}"><span>${esc(b.label)}</span><strong>${fmt(b.amount)}</strong><small>${n(b.customers).toFixed(0)} customer${n(b.customers)===1?'':'s'} · ${n(b.invoices).toFixed(0)} invoice${n(b.invoices)===1?'':'s'}</small></button>`).join('');
    badgeWrap.querySelectorAll('[data-bucket]').forEach(btn=>btn.addEventListener('click',()=>loadAgeingCustomers(btn.dataset.bucket).catch(handleError)));

    destroy(ageingChart);
    const opt=baseOptions('Outstanding Amount');
    opt.plugins.legend.display=false;
    opt.onHover=function(evt,elements){evt.native.target.style.cursor=elements.length?'pointer':'default';};
    opt.onClick=function(evt,elements){if(!elements.length)return;const idx=elements[0].index;const b=ageingBuckets[idx];if(b)loadAgeingCustomers(b.key).catch(handleError);};
    ageingChart=new Chart($('ageingChart').getContext('2d'),{
        type:'bar',
        data:{labels:ageingBuckets.map(b=>b.label),datasets:[{label:'Outstanding',data:ageingBuckets.map(b=>n(b.amount)),backgroundColor:['rgba(62,111,168,.78)','rgba(193,139,56,.78)','rgba(196,119,63,.80)','rgba(180,87,79,.82)'],borderColor:[colors.blue,colors.gold,colors.orange,colors.red],borderWidth:1,borderRadius:5,maxBarThickness:75}]},
        options:opt
    });

    if(!ageingBuckets.some(b=>b.key===selectedBucket))selectedBucket='0_30';
    await loadAgeingCustomers(selectedBucket);
}

function activateFinancialTab(tab){
    document.querySelectorAll('[data-fin-tab]').forEach(btn=>btn.classList.toggle('active',btn.dataset.finTab===tab));
    document.querySelectorAll('[data-fin-panel]').forEach(panel=>{panel.hidden=panel.dataset.finPanel!==tab;});
    window.setTimeout(()=>{if(tab==='reconciliation'&&reconciliationChart)reconciliationChart.resize();if(tab==='profitability'&&profitabilityChart)profitabilityChart.resize();if(tab==='ageing'&&ageingChart)ageingChart.resize();},30);
}
function initTabs(){document.querySelectorAll('[data-fin-tab]').forEach(btn=>btn.addEventListener('click',()=>activateFinancialTab(btn.dataset.finTab)));}

function initDateRangePicker(){
    const input=$('grFinDateRange');
    if(!input||!window.jQuery||!window.moment||!window.jQuery.fn||!window.jQuery.fn.daterangepicker)return;
    const jq=window.jQuery,base=window.dateRangeSettings||{},options=jq.extend({},base);
    options.locale=jq.extend({},base.locale||{});options.ranges=base.ranges||{};
    if(!Object.keys(options.ranges).length){
        const fy=window.financial_year||null;
        const fyStart=fy&&fy.start?moment(fy.start):(moment().month()>=3?moment().month(3).startOf('month'):moment().subtract(1,'year').month(3).startOf('month'));
        const fyEnd=fy&&fy.end?moment(fy.end):fyStart.clone().add(1,'year').subtract(1,'day').endOf('day');
        options.ranges={
            'Today':[moment().startOf('day'),moment().endOf('day')],
            'Yesterday':[moment().subtract(1,'day').startOf('day'),moment().subtract(1,'day').endOf('day')],
            'Last 7 Days':[moment().subtract(6,'days').startOf('day'),moment().endOf('day')],
            'Last 30 Days':[moment().subtract(29,'days').startOf('day'),moment().endOf('day')],
            'This Month':[moment().startOf('month'),moment().endOf('month')],
            'Last Month':[moment().subtract(1,'month').startOf('month'),moment().subtract(1,'month').endOf('month')],
            'This Month Last Year':[moment().subtract(1,'year').startOf('month'),moment().subtract(1,'year').endOf('month')],
            'This Year':[moment().startOf('year'),moment().endOf('year')],
            'Last Year':[moment().subtract(1,'year').startOf('year'),moment().subtract(1,'year').endOf('year')],
            'This FY':[fyStart.clone(),fyEnd.clone()],
            'Last FY':[fyStart.clone().subtract(1,'year'),fyEnd.clone().subtract(1,'year')]
        };
    }
    options.startDate=moment($('grFinStartDate').value,'YYYY-MM-DD',true);options.endDate=moment($('grFinEndDate').value,'YYYY-MM-DD',true);options.autoUpdateInput=false;options.alwaysShowCalendars=true;options.showDropdowns=true;
    options.locale.format=options.locale.format||window.moment_date_format||'DD/MM/YYYY';options.locale.separator=options.locale.separator||' - ';if(!options.locale.customRangeLabel)options.locale.customRangeLabel='Custom Date Range';
    const update=(start,end)=>{$('grFinStartDate').value=start.format('YYYY-MM-DD');$('grFinEndDate').value=end.format('YYYY-MM-DD');input.value=start.format(options.locale.format)+(options.locale.separator||' - ')+end.format(options.locale.format);};
    jq(input).daterangepicker(options,(start,end)=>update(start,end));jq(input).on('apply.daterangepicker',(ev,picker)=>update(picker.startDate,picker.endDate));update(options.startDate,options.endDate);
}
function handleError(e){console.error(e);showStatus('Unable to load Financial & Profitability Analytics data. Please check the selected filters and server log.');}
async function loadAll(){
    root.classList.add('gr-loading');
    try{await Promise.all([loadReconciliation(),loadProfitability(),loadAgeing()]);}
    catch(e){handleError(e);}
    finally{root.classList.remove('gr-loading');}
}

initDateRangePicker();initTabs();
$('grFinApply').addEventListener('click',loadAll);$('grFinRefresh').addEventListener('click',loadAll);
loadAll();
})();
