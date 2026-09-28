(function(){
'use strict';
const cfg=window.GRAPHS_CUSTOMER_PAYMENT_CONFIG||{};
const root=document.querySelector('.gr-customer-page');
if(!root||typeof Chart==='undefined')return;
const $=id=>document.getElementById(id);
const precision=Math.max(0,Math.min(6,Number(cfg.currencyPrecision||2)));
const money=new Intl.NumberFormat('en-US',{minimumFractionDigits:precision,maximumFractionDigits:precision});
const qty=new Intl.NumberFormat('en-US',{minimumFractionDigits:3,maximumFractionDigits:3});
const whole=new Intl.NumberFormat('en-US',{maximumFractionDigits:0});
const pct=new Intl.NumberFormat('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
const palette=['#3f6fa6','#2f8a68','#b46b32','#7562a8','#3e8c92','#b4574f','#7c8b3c','#566b85','#a0617d','#4d8a5b','#8b6a3a','#587d9d','#946f8f','#6e7d9d'];
let paymentChart=null,pumpChart=null,creditChart=null;

Chart.defaults.font.family='Calibri, "Segoe UI", Arial, sans-serif';
Chart.defaults.color='#5f7185';

function esc(v){return String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
function n(v){const x=Number(v);return Number.isFinite(x)?x:0;}
function fmtMoney(v){return money.format(n(v));}
function fmtQty(v){return qty.format(n(v));}
function set(id,value){const el=$(id);if(el)el.textContent=value;}
function destroy(chart){if(chart)chart.destroy();}
function titlePeriod(){const el=$('grCpPeriod'),value=el?el.value:'daily';return value.charAt(0).toUpperCase()+value.slice(1)+' basis';}
function params(){
    const p=new URLSearchParams({
        start_date:$('grCpStartDate').value,
        end_date:$('grCpEndDate').value,
        period:$('grCpPeriod').value||'daily'
    });
    const loc=$('grCpLocation').value;if(loc)p.set('location_id',loc);
    return p;
}
async function get(url){
    const r=await fetch(url+'?'+params().toString(),{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'});
    if(!r.ok)throw new Error('Request failed ('+r.status+')');
    return r.json();
}
function showStatus(message){
    const el=$('grCpStatusMessage');if(!el)return;
    el.textContent=message;el.hidden=false;
    window.clearTimeout(showStatus._t);showStatus._t=window.setTimeout(()=>{el.hidden=true;},5500);
}
function baseOptions(yTitle,formatter){
    return {
        responsive:true,maintainAspectRatio:false,
        interaction:{mode:'index',intersect:false},
        plugins:{
            legend:{position:'top',align:'end',labels:{boxWidth:10,boxHeight:10,usePointStyle:true,padding:14,font:{size:11,weight:'600'},color:'#52677e'}},
            tooltip:{backgroundColor:'rgba(30,49,70,.95)',padding:10,cornerRadius:6,titleFont:{size:12,weight:'700'},bodyFont:{size:11}}
        },
        scales:{
            x:{grid:{display:false},ticks:{color:'#718399',font:{size:10},maxRotation:35,minRotation:0,autoSkip:true,maxTicksLimit:18}},
            y:{beginAtZero:true,grid:{color:'rgba(126,147,169,.13)'},ticks:{color:'#718399',font:{size:10},callback:v=>formatter(v)},title:{display:!!yTitle,text:yTitle,color:'#657a91',font:{size:10,weight:'700'}}}
        }
    };
}

async function loadPayment(){
    const j=await get(cfg.paymentMethodSplitUrl),s=j.summary||{},methods=j.methods||[],rows=j.rows||[];
    const byKey={};methods.forEach(m=>{byKey[m.key]=m;});
    set('cpCashAmount',fmtMoney(s.cash));set('cpCashPct',pct.format(n(byKey.cash&&byKey.cash.percentage))+'%');
    set('cpCardAmount',fmtMoney(s.card));set('cpCardPct',pct.format(n(byKey.card&&byKey.card.percentage))+'%');
    set('cpCreditAmount',fmtMoney(s.credit));set('cpCreditPct',pct.format(n(byKey.credit&&byKey.credit.percentage))+'%');
    set('cpOnlineAmount',fmtMoney(s.online_transfer));set('cpOnlinePct',pct.format(n(byKey.online_transfer&&byKey.online_transfer.percentage))+'%');
    set('cpPaymentTotal',fmtMoney(s.total));set('cpPaymentBasis',titlePeriod());

    destroy(paymentChart);
    const labels=methods.map(m=>m.label),data=methods.map(m=>n(m.amount));
    paymentChart=new Chart($('paymentSplitChart').getContext('2d'),{
        type:'pie',
        data:{labels:labels,datasets:[{data:data,backgroundColor:['#2f8a68','#3f6fa6','#b46b32','#7562a8'],borderColor:'#ffffff',borderWidth:2,hoverOffset:5}]},
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{
                legend:{position:'right',labels:{boxWidth:12,boxHeight:12,usePointStyle:true,padding:17,font:{size:12,weight:'600'},color:'#52677e'}},
                tooltip:{backgroundColor:'rgba(30,49,70,.95)',padding:10,cornerRadius:6,callbacks:{label:ctx=>{
                    const method=methods[ctx.dataIndex]||{};return ' '+ctx.label+': '+fmtMoney(ctx.parsed)+' ('+pct.format(n(method.percentage))+'%)';
                }}}
            }
        }
    });

    const tbody=$('cpPaymentRows');
    tbody.innerHTML=rows.length?rows.map(r=>`<tr>
        <td><strong>${esc(r.period)}</strong></td>
        <td class="gr-num">${fmtMoney(r.cash)}</td>
        <td class="gr-num">${fmtMoney(r.card)}</td>
        <td class="gr-num">${fmtMoney(r.credit)}</td>
        <td class="gr-num">${fmtMoney(r.online_transfer)}</td>
        <td class="gr-num"><strong>${fmtMoney(r.total)}</strong></td>
    </tr>`).join(''):'<tr><td colspan="6" class="gr-table-empty">No Cash, Card, Credit Sale or Online Transfer data found for the selected filters.</td></tr>';
}

async function loadPumpShift(){
    const j=await get(cfg.pumpShiftSalesUrl),s=j.summary||{},rows=j.rows||[],series=j.series||[];
    set('cpPumpSalesAmount',fmtMoney(s.sales_amount));
    set('cpPumpSoldQty',fmtQty(s.sold_qty));
    set('cpPumpCount',whole.format(n(s.pumps)));
    set('cpShiftCount',whole.format(n(s.shifts)));

    destroy(pumpChart);
    const opt=baseOptions('Sales Amount',v=>money.format(v));
    opt.plugins.tooltip.callbacks={label:ctx=>' '+ctx.dataset.label+': '+fmtMoney(ctx.parsed.y)};
    opt.scales.x.stacked=false;opt.scales.y.stacked=false;
    pumpChart=new Chart($('cpPumpShiftChart').getContext('2d'),{
        type:'bar',
        data:{labels:j.labels||[],datasets:series.map((item,i)=>({
            label:item.name,data:item.data||[],backgroundColor:palette[i%palette.length]+'cc',borderColor:palette[i%palette.length],borderWidth:1,borderRadius:3,maxBarThickness:46
        }))},options:opt
    });

    const tbody=$('cpPumpShiftRows');
    tbody.innerHTML=rows.length?rows.map(r=>`<tr>
        <td>${esc(r.period)}</td><td><strong>${esc(r.pump)}</strong></td><td>${esc(r.shift)}</td>
        <td class="gr-num">${fmtQty(r.sold_qty)}</td><td class="gr-num"><strong>${fmtMoney(r.sales_amount)}</strong></td>
    </tr>`).join(''):'<tr><td colspan="5" class="gr-table-empty">No finalized pump/shift meter sales found for the selected filters.</td></tr>';
}

async function loadCreditCustomers(){
    const j=await get(cfg.topCreditCustomersUrl),s=j.summary||{},customers=j.customers||[],rows=j.rows||[],periodLabels=j.period_labels||[],periodRows=j.period_rows||[];
    set('cpCreditSalesTotal',fmtMoney(s.credit_sales));
    set('cpCreditCustomers',whole.format(n(s.customers)));
    set('cpCreditTransactions',whole.format(n(s.transactions)));
    set('cpFleetCount',whole.format(n(s.fleets)));
    set('cpCreditBasis',titlePeriod());

    destroy(creditChart);
    const labels=customers.map(c=>c.customer);
    const datasets=periodLabels.map((periodLabel,periodIndex)=>({
        label:periodLabel,
        data:customers.map(c=>n((c.period_values||[])[periodIndex])),
        backgroundColor:palette[periodIndex%palette.length]+'d9',
        borderColor:palette[periodIndex%palette.length],
        borderWidth:1,
        borderRadius:3,
        maxBarThickness:34
    }));
    const showLegend=periodLabels.length>1&&periodLabels.length<=12;
    creditChart=new Chart($('topCreditCustomersChart').getContext('2d'),{
        type:'bar',
        data:{labels:labels,datasets:datasets},
        options:{
            indexAxis:'y',responsive:true,maintainAspectRatio:false,
            interaction:{mode:'index',intersect:false},
            plugins:{
                legend:{display:showLegend,position:'top',align:'end',labels:{boxWidth:9,boxHeight:9,usePointStyle:true,padding:10,font:{size:10,weight:'600'},color:'#52677e'}},
                tooltip:{backgroundColor:'rgba(30,49,70,.95)',padding:10,cornerRadius:6,callbacks:{label:ctx=>' '+ctx.dataset.label+': '+fmtMoney(ctx.parsed.x),footer:items=>'Customer Total: '+fmtMoney(customers[items[0]&&items[0].dataIndex]?customers[items[0].dataIndex].total:0)}}
            },
            scales:{
                x:{stacked:true,beginAtZero:true,grid:{color:'rgba(126,147,169,.13)'},ticks:{color:'#718399',font:{size:10},callback:v=>money.format(v)},title:{display:true,text:'Credit Sales Amount',color:'#657a91',font:{size:10,weight:'700'}}},
                y:{stacked:true,grid:{display:false},ticks:{color:'#52677e',font:{size:11,weight:'600'}}}
            }
        }
    });

    const tbody=$('cpCreditRows');
    tbody.innerHTML=rows.length?rows.map(r=>`<tr>
        <td><strong>#${whole.format(n(r.rank))}</strong></td>
        <td><strong>${esc(r.customer)}</strong></td>
        <td>${esc(r.customer_code||'—')}</td>
        <td>${esc(r.mobile||'—')}</td>
        <td class="gr-num"><strong>${fmtMoney(r.credit_sales)}</strong></td>
        <td class="gr-num">${whole.format(n(r.transactions))}</td>
        <td class="gr-num">${whole.format(n(r.fleet_count))}</td>
        <td>${(r.fleets&&r.fleets.length)?r.fleets.map(esc).join(', '):'—'}</td>
    </tr>`).join(''):'<tr><td colspan="8" class="gr-table-empty">No finalized credit sales found for the selected filters.</td></tr>';

    const periodBody=$('cpCreditPeriodRows');
    periodBody.innerHTML=periodRows.length?periodRows.map(r=>`<tr><td>${esc(r.period)}</td><td><strong>${esc(r.customer)}</strong></td><td class="gr-num">${fmtMoney(r.amount)}</td></tr>`).join(''):'<tr><td colspan="3" class="gr-table-empty">No credit sales period detail found for the selected filters.</td></tr>';
}

function activateTab(tab){
    document.querySelectorAll('[data-cp-tab]').forEach(btn=>btn.classList.toggle('active',btn.dataset.cpTab===tab));
    document.querySelectorAll('[data-cp-panel]').forEach(panel=>{panel.hidden=panel.dataset.cpPanel!==tab;});
    window.setTimeout(()=>{
        if(tab==='payment'&&paymentChart)paymentChart.resize();
        if(tab==='pump'&&pumpChart)pumpChart.resize();
        if(tab==='credit'&&creditChart)creditChart.resize();
    },30);
}
function initTabs(){document.querySelectorAll('[data-cp-tab]').forEach(btn=>btn.addEventListener('click',()=>activateTab(btn.dataset.cpTab)));}

function initDateRangePicker(){
    const input=$('grCpDateRange');
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
    options.startDate=moment($('grCpStartDate').value,'YYYY-MM-DD',true);options.endDate=moment($('grCpEndDate').value,'YYYY-MM-DD',true);options.autoUpdateInput=false;options.alwaysShowCalendars=true;options.showDropdowns=true;
    options.locale.format=options.locale.format||window.moment_date_format||'DD/MM/YYYY';options.locale.separator=options.locale.separator||' - ';if(!options.locale.customRangeLabel)options.locale.customRangeLabel='Custom Date Range';
    const update=(start,end)=>{$('grCpStartDate').value=start.format('YYYY-MM-DD');$('grCpEndDate').value=end.format('YYYY-MM-DD');input.value=start.format(options.locale.format)+(options.locale.separator||' - ')+end.format(options.locale.format);};
    jq(input).daterangepicker(options,(start,end)=>update(start,end));jq(input).on('apply.daterangepicker',(ev,picker)=>update(picker.startDate,picker.endDate));update(options.startDate,options.endDate);
}

function handleError(e){console.error(e);showStatus('Unable to load Customer & Payment Analytics data. Please check the selected filters and server log.');}
async function loadAll(){
    root.classList.add('gr-loading');
    try{await Promise.all([loadPayment(),loadPumpShift(),loadCreditCustomers()]);}
    catch(e){handleError(e);}
    finally{root.classList.remove('gr-loading');}
}

initDateRangePicker();initTabs();
$('grCpApply').addEventListener('click',loadAll);$('grCpRefresh').addEventListener('click',loadAll);
loadAll();
})();
