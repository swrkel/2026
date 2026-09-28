(function(){
'use strict';
const cfg=window.GRAPHS_OPERATIONAL_CONFIG||{};
const root=document.querySelector('.gr-operational-page');
if(!root||!window.Chart)return;

const $=id=>document.getElementById(id);
const precision=Math.max(0,Math.min(6,Number(cfg.currencyPrecision||2)));
const money=new Intl.NumberFormat('en-US',{minimumFractionDigits:precision,maximumFractionDigits:precision});
const qty=new Intl.NumberFormat('en-US',{minimumFractionDigits:3,maximumFractionDigits:3});
const whole=new Intl.NumberFormat('en-US',{maximumFractionDigits:0});
const palette=['#3f6fa6','#2f8a68','#b46b32','#7562a8','#3e8c92','#b4574f','#7c8b3c','#566b85','#a0617d','#4d8a5b','#8b6a3a','#587d9d'];
let dipChart=null,pumpChart=null;

Chart.defaults.font.family='Calibri, "Segoe UI", Arial, sans-serif';
Chart.defaults.color='#5f7185';

function esc(v){return String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
function n(v){const x=Number(v);return Number.isFinite(x)?x:0;}
function fmtMoney(v){return money.format(n(v));}
function fmtQty(v){return qty.format(n(v));}
function fmtSignedQty(v){const x=n(v);return (x>0?'+':'')+fmtQty(x);}
function set(id,value){const el=$(id);if(el)el.textContent=value;}
function valueClass(v){return n(v)>0?'gr-value-positive':n(v)<0?'gr-value-negative':'';}
function destroy(chart){if(chart)chart.destroy();}
function params(){
    const p=new URLSearchParams({
        start_date:$('grOpStartDate').value,
        end_date:$('grOpEndDate').value,
        period:$('grOpPeriod').value||'daily'
    });
    const loc=$('grOpLocation').value;if(loc)p.set('location_id',loc);
    return p;
}
async function get(url){
    const r=await fetch(url+'?'+params().toString(),{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'});
    if(!r.ok)throw new Error('Request failed ('+r.status+')');
    return r.json();
}
function showStatus(message){
    const el=$('grOpStatusMessage');if(!el)return;
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

async function loadDip(){
    const j=await get(cfg.dipVarianceUrl),s=j.summary||{},rows=j.rows||[];
    set('opDipStock',fmtQty(s.dip_stock));
    set('opSystemStock',fmtQty(s.system_stock));
    set('opVariance',fmtSignedQty(s.variance));
    set('opFuelLoss',fmtQty(s.fuel_loss));
    set('opDipReadingAt',s.reading_at?'Latest reading: '+String(s.reading_at):'No dip reading in selected range');
    const variance=$('opVariance');if(variance)variance.className=valueClass(s.variance);
    const loss=$('opFuelLoss');if(loss)loss.className=n(s.fuel_loss)>0?'gr-value-negative':'';

    destroy(dipChart);
    const opt=baseOptions('Stock Quantity',v=>qty.format(v));
    opt.plugins.tooltip.callbacks={label:ctx=>' '+ctx.dataset.label+': '+fmtQty(ctx.parsed.y)};
    dipChart=new Chart($('dipVarianceChart').getContext('2d'),{
        type:'line',
        data:{labels:j.labels||[],datasets:[
            {label:'Dip Stock',data:j.dip||[],borderColor:'#2f8a68',backgroundColor:'rgba(47,138,104,.09)',borderWidth:2.3,pointRadius:3,pointHoverRadius:5,tension:.25,spanGaps:true},
            {label:'System Stock',data:j.system||[],borderColor:'#3f6fa6',backgroundColor:'rgba(63,111,166,.08)',borderWidth:2.3,pointRadius:3,pointHoverRadius:5,tension:.25,spanGaps:true}
        ]},
        options:opt
    });

    const tbody=$('dipVarianceRows');
    tbody.innerHTML=rows.length?rows.map(r=>`<tr>
        <td>${esc(r.period)}</td>
        <td><strong>${esc(r.tank)}</strong></td>
        <td class="gr-num">${fmtQty(r.dip_stock)}</td>
        <td class="gr-num">${fmtQty(r.system_stock)}</td>
        <td class="gr-num ${valueClass(r.variance)}">${fmtSignedQty(r.variance)}</td>
        <td class="gr-num ${n(r.fuel_loss)>0?'gr-value-negative':''}">${fmtQty(r.fuel_loss)}</td>
        <td>${esc(r.reading_at||'—')}</td>
    </tr>`).join(''):'<tr><td colspan="7" class="gr-table-empty">No dip readings found for the selected filters.</td></tr>';
}

async function loadPumpShift(){
    const j=await get(cfg.pumpShiftSalesUrl),s=j.summary||{},rows=j.rows||[],series=j.series||[];
    set('opPumpSalesAmount',fmtMoney(s.sales_amount));
    set('opPumpSoldQty',fmtQty(s.sold_qty));
    set('opPumpCount',whole.format(n(s.pumps)));
    set('opShiftCount',whole.format(n(s.shifts)));

    destroy(pumpChart);
    const opt=baseOptions('Sales Amount',v=>money.format(v));
    opt.plugins.tooltip.callbacks={label:ctx=>' '+ctx.dataset.label+': '+fmtMoney(ctx.parsed.y)};
    opt.scales.x.stacked=false;opt.scales.y.stacked=false;
    pumpChart=new Chart($('pumpShiftChart').getContext('2d'),{
        type:'bar',
        data:{labels:j.labels||[],datasets:series.map((item,i)=>({
            label:item.name,
            data:item.data||[],
            backgroundColor:palette[i%palette.length]+'cc',
            borderColor:palette[i%palette.length],
            borderWidth:1,
            borderRadius:3,
            maxBarThickness:46
        }))},
        options:opt
    });

    const tbody=$('pumpShiftRows');
    tbody.innerHTML=rows.length?rows.map(r=>`<tr>
        <td>${esc(r.period)}</td>
        <td><strong>${esc(r.pump)}</strong></td>
        <td>${esc(r.shift)}</td>
        <td class="gr-num">${fmtQty(r.sold_qty)}</td>
        <td class="gr-num"><strong>${fmtMoney(r.sales_amount)}</strong></td>
    </tr>`).join(''):'<tr><td colspan="5" class="gr-table-empty">No finalized pump/shift meter sales found for the selected filters.</td></tr>';
}

function activateTab(tab){
    document.querySelectorAll('[data-op-tab]').forEach(btn=>btn.classList.toggle('active',btn.dataset.opTab===tab));
    document.querySelectorAll('[data-op-panel]').forEach(panel=>{panel.hidden=panel.dataset.opPanel!==tab;});
    window.setTimeout(()=>{if(tab==='dip'&&dipChart)dipChart.resize();if(tab==='pump'&&pumpChart)pumpChart.resize();},30);
}
function initTabs(){document.querySelectorAll('[data-op-tab]').forEach(btn=>btn.addEventListener('click',()=>activateTab(btn.dataset.opTab)));}

function initDateRangePicker(){
    const input=$('grOpDateRange');
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
    options.startDate=moment($('grOpStartDate').value,'YYYY-MM-DD',true);options.endDate=moment($('grOpEndDate').value,'YYYY-MM-DD',true);options.autoUpdateInput=false;options.alwaysShowCalendars=true;options.showDropdowns=true;
    options.locale.format=options.locale.format||window.moment_date_format||'DD/MM/YYYY';options.locale.separator=options.locale.separator||' - ';if(!options.locale.customRangeLabel)options.locale.customRangeLabel='Custom Date Range';
    const update=(start,end)=>{$('grOpStartDate').value=start.format('YYYY-MM-DD');$('grOpEndDate').value=end.format('YYYY-MM-DD');input.value=start.format(options.locale.format)+(options.locale.separator||' - ')+end.format(options.locale.format);};
    jq(input).daterangepicker(options,(start,end)=>update(start,end));jq(input).on('apply.daterangepicker',(ev,picker)=>update(picker.startDate,picker.endDate));update(options.startDate,options.endDate);
}

function handleError(e){console.error(e);showStatus('Unable to load Operational & Loss Analytics data. Please check the selected filters and server log.');}
async function loadAll(){
    root.classList.add('gr-loading');
    try{await Promise.all([loadDip(),loadPumpShift()]);}
    catch(e){handleError(e);}
    finally{root.classList.remove('gr-loading');}
}

initDateRangePicker();initTabs();
$('grOpApply').addEventListener('click',loadAll);$('grOpRefresh').addEventListener('click',loadAll);
loadAll();
})();
