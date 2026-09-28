(function(){
'use strict';
const cfg=window.GRAPHS_CONFIG||{};
let tankChart,fuelChart,nonFuelChart,mixChart;
let fuelChartType='bar';
const $=id=>document.getElementById(id);
const nf=new Intl.NumberFormat(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
const qf=new Intl.NumberFormat(undefined,{minimumFractionDigits:3,maximumFractionDigits:3});
const graphsRoot=document.querySelector('.gr-app');
if(window.Chart&&Chart.defaults){Chart.defaults.font=Chart.defaults.font||{};Chart.defaults.font.family='Calibri, Segoe UI, Arial, sans-serif';}
const colors=['#3f647f','#19807b','#7969a8','#c18433','#457a57','#9b5d75','#4f739f','#7b8794','#9b6b43','#3d8c94','#745d91','#58734d'];

function params(){
    const p=new URLSearchParams();
    const l=$('grLocation').value;
    if(l)p.set('location_id',l);
    p.set('period',$('grPeriod').value);
    p.set('start_date',$('grStartDate').value);
    p.set('end_date',$('grEndDate').value);
    return p;
}
async function get(url,all=true){
    const p=all?params():new URLSearchParams([['location_id',$('grLocation').value||'']]);
    const r=await fetch(url+'?'+p.toString(),{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
    if(!r.ok)throw new Error('Request failed: '+r.status);
    return r.json();
}
function rgba(hex,a){
    const h=hex.replace('#','');
    const n=parseInt(h,16);
    return `rgba(${(n>>16)&255},${(n>>8)&255},${n&255},${a})`;
}
function destroy(c){if(c)c.destroy()}
function esc(v){return String(v==null?'':v).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))}
function showStatus(message){
    const box=$('grStatusMessage');
    box.textContent=message;
    box.hidden=false;
    window.clearTimeout(showStatus.t);
    showStatus.t=window.setTimeout(()=>{box.hidden=true},5000);
}
function baseChartOptions(yTitle){
    return {
        responsive:true,
        maintainAspectRatio:false,
        interaction:{mode:'index',intersect:false},
        plugins:{
            legend:{position:'top',align:'start',labels:{boxWidth:10,boxHeight:10,usePointStyle:true,padding:16,font:{size:11,weight:'600'},color:'#52677e'}},
            tooltip:{backgroundColor:'rgba(30,49,70,.94)',titleFont:{size:12,weight:'700'},bodyFont:{size:11},padding:10,cornerRadius:6}
        },
        scales:{
            x:{grid:{display:false},ticks:{color:'#718399',font:{size:10},maxRotation:0,autoSkip:true,maxTicksLimit:16}},
            y:{beginAtZero:true,grid:{color:'rgba(126,147,169,.13)'},ticks:{color:'#718399',font:{size:10}},title:{display:!!yTitle,text:yTitle,color:'#657a91',font:{size:10,weight:'700'}}}
        }
    };
}
async function loadTanks(){
    const j=await get(cfg.tanksUrl,false),d=j.data||[];
    destroy(tankChart);
    const ctx=$('tankChart').getContext('2d');
    tankChart=new Chart(ctx,{
        type:'bar',
        data:{
            labels:d.map(x=>x.tank+' · '+x.product),
            datasets:[
                {label:'Storage Volume',data:d.map(x=>x.storage_volume),backgroundColor:rgba('#70859b',.20),borderColor:'#70859b',borderWidth:1,borderRadius:3,maxBarThickness:34},
                {label:'Current Stock',data:d.map(x=>x.current),backgroundColor:rgba('#19807b',.72),borderColor:'#19807b',borderWidth:1,borderRadius:3,maxBarThickness:34},
                {label:'Re-order Level',data:d.map(x=>x.reorder),type:'line',borderColor:'#b45a4f',backgroundColor:rgba('#b45a4f',.08),borderWidth:2,pointRadius:3,pointHoverRadius:4,tension:.2,fill:false}
            ]
        },
        options:baseChartOptions('Quantity')
    });
    const needing=d.filter(x=>x.needs_reorder).length;
    $('summaryTankCount').textContent=d.length;
    $('summaryReorderCount').textContent=needing;
    $('tankGauges').innerHTML=d.map(x=>{
        const pct=Math.max(0,Math.min(100,x.current_pct||0));
        const rp=Math.max(0,Math.min(100,x.reorder_pct||0));
        return `<article class="gr-gauge-card ${x.needs_reorder?'is-alert':''}">
            <div class="gr-gauge-top"><div class="gr-gauge-title">${esc(x.tank)} · ${esc(x.product)}</div><span class="gr-gauge-badge">${x.needs_reorder?'Re-order':'Normal'}</span></div>
            <div class="gr-gauge-sub">${esc(x.location||'Location not specified')}</div>
            <div class="gr-gauge-track"><div class="gr-gauge-fill ${x.needs_reorder?'warn':''}" style="width:${pct}%"></div><span class="gr-gauge-marker" style="left:${rp}%" title="Re-order level"></span></div>
            <div class="gr-gauge-values"><span>Current<strong>${qf.format(x.current)}</strong></span><span>Storage Volume<strong>${qf.format(x.storage_volume)}</strong></span></div>
            ${x.needs_reorder?'<div class="gr-gauge-alert">Re-order level reached — attention required</div>':''}
        </article>`;
    }).join('')||'<div class="gr-empty"><strong>No fuel tanks found</strong><span>No tank data is available for the selected location.</span></div>';
}
async function loadFuel(){
    const j=await get(cfg.fuelSalesUrl,true),series=j.series||[];
    destroy(fuelChart);
    const hasFuel=series.length>0 && series.some(s=>Array.isArray(s.data) && s.data.some(v=>Number(v)!==0));
    $('fuelEmpty').hidden=hasFuel;
    const fuelFrame=$('fuelChartFrame'); if(fuelFrame) fuelFrame.hidden=!hasFuel;
    $('fuelSubtitle').textContent=`Sold quantity of all fuel products · ${$('grDateRange').value}`;
    const sets=series.map((s,i)=>({
        label:s.name,
        data:s.data,
        borderColor:colors[i%colors.length],
        backgroundColor:fuelChartType==='line'?rgba(colors[i%colors.length],.08):rgba(colors[i%colors.length],.70),
        borderWidth:fuelChartType==='line'?2:1,
        borderRadius:fuelChartType==='bar'?3:0,
        maxBarThickness:30,
        pointRadius:fuelChartType==='line'?2:0,
        pointHoverRadius:fuelChartType==='line'?4:0,
        tension:.22,
        fill:false
    }));
    if(hasFuel){
        fuelChart=new Chart($('fuelSalesChart').getContext('2d'),{
            type:fuelChartType,
            data:{labels:j.labels||[],datasets:sets},
            options:baseChartOptions('Sold Qty')
        });
    }
}
async function loadNonFuel(){
    const j=await get(cfg.nonFuelSalesUrl,true),cats=j.categories||[];
    const fuelTotal=Number(j.fuel_total||0),nonFuelTotal=Number(j.non_fuel_total||0);
    const fuelPct=Number(j.fuel_pct||0),nonFuelPct=Number(j.non_fuel_pct||0);
    $('fuelAmount').textContent=nf.format(fuelTotal);
    $('nonFuelAmount').textContent=nf.format(nonFuelTotal);
    $('fuelPct').textContent=nf.format(fuelPct)+'%';
    $('nonFuelPct').textContent=nf.format(nonFuelPct)+'%';
    $('summaryFuelAmount').textContent=nf.format(fuelTotal);
    $('summaryNonFuelAmount').textContent=nf.format(nonFuelTotal);
    $('summaryFuelPct').textContent=nf.format(fuelPct)+'% of total sales value';
    $('summaryNonFuelPct').textContent=nf.format(nonFuelPct)+'% of total sales value';
    const hasCategories=cats.length>0 && nonFuelTotal>0;
    const hasMix=(fuelTotal+nonFuelTotal)>0;
    $('nonFuelEmpty').hidden=hasCategories || hasMix;
    const categoryPanel=$('nonFuelCategoryPanel'); if(categoryPanel) categoryPanel.hidden=!hasCategories;
    const mixPanel=$('salesMixPanel'); if(mixPanel) mixPanel.hidden=!hasMix;
    destroy(nonFuelChart);destroy(mixChart);
    const doughnutOptions={
        responsive:true,maintainAspectRatio:false,cutout:'64%',
        plugins:{
            legend:{position:'right',labels:{boxWidth:9,boxHeight:9,usePointStyle:true,padding:14,font:{size:10,weight:'600'},color:'#52677e'}},
            tooltip:{backgroundColor:'rgba(30,49,70,.94)',padding:10,cornerRadius:6,bodyFont:{size:11}}
        }
    };
    if(hasCategories){
        nonFuelChart=new Chart($('nonFuelChart').getContext('2d'),{
            type:'doughnut',
            data:{labels:cats.map(x=>x.name),datasets:[{data:cats.map(x=>x.amount),backgroundColor:cats.map((_,i)=>rgba(colors[i%colors.length],.82)),borderColor:'#fff',borderWidth:2,hoverOffset:4}]},
            options:doughnutOptions
        });
    }
    if(hasMix){
        mixChart=new Chart($('mixChart').getContext('2d'),{
            type:'doughnut',
            data:{labels:['Fuel Sales','Non-Fuel Sales'],datasets:[{data:[fuelTotal,nonFuelTotal],backgroundColor:[rgba('#19807b',.84),rgba('#7969a8',.82)],borderColor:'#fff',borderWidth:2,hoverOffset:4}]},
            options:doughnutOptions
        });
    }
}
function initDateRangePicker(){
    const input=$('grDateRange');
    if(!input || !window.jQuery || !window.moment || !window.jQuery.fn || !window.jQuery.fn.daterangepicker)return;
    const jq=window.jQuery;
    const base=window.dateRangeSettings||{};
    const options=jq.extend({},base);
    options.locale=jq.extend({},base.locale||{});
    options.ranges=base.ranges||{};
    if(!Object.keys(options.ranges).length){
        const fy=window.financial_year||null;
        const fyStart=fy&&fy.start?moment(fy.start):moment().month()>=3?moment().month(3).startOf('month'):moment().subtract(1,'year').month(3).startOf('month');
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
    options.startDate=moment($('grStartDate').value,'YYYY-MM-DD',true);
    options.endDate=moment($('grEndDate').value,'YYYY-MM-DD',true);
    options.autoUpdateInput=false;
    options.alwaysShowCalendars=true;
    options.showDropdowns=true;
    options.locale.format=options.locale.format||window.moment_date_format||'DD/MM/YYYY';
    options.locale.separator=options.locale.separator||' - ';
    if(!options.locale.customRangeLabel)options.locale.customRangeLabel='Custom Date Range';

    const update=function(start,end){
        $('grStartDate').value=start.format('YYYY-MM-DD');
        $('grEndDate').value=end.format('YYYY-MM-DD');
        input.value=start.format(options.locale.format)+(options.locale.separator||' - ')+end.format(options.locale.format);
    };
    jq(input).daterangepicker(options,function(start,end){update(start,end);});
    jq(input).on('apply.daterangepicker',function(ev,picker){update(picker.startDate,picker.endDate);});
    update(options.startDate,options.endDate);
}

async function loadAll(){
    if(graphsRoot)graphsRoot.classList.add('gr-loading');
    try{
        await Promise.all([loadTanks(),loadFuel(),loadNonFuel()]);
    }catch(e){
        console.error(e);
        showStatus('Unable to load Graphs data. Please check the selected filters and the server log.');
    }finally{
        if(graphsRoot)graphsRoot.classList.remove('gr-loading');
    }
}
initDateRangePicker();
$('grApply').addEventListener('click',loadAll);
$('grRefresh').addEventListener('click',loadAll);
document.querySelectorAll('[data-fuel-chart]').forEach(b=>b.addEventListener('click',function(){
    document.querySelectorAll('[data-fuel-chart]').forEach(x=>x.classList.remove('active'));
    this.classList.add('active');
    fuelChartType=this.dataset.fuelChart;
    loadFuel().catch(e=>{console.error(e);showStatus('Unable to change the fuel chart view.')});
}));
loadAll();
})();
