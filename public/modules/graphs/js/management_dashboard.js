(function(){
'use strict';
const cfg=window.GRAPHS_MANAGEMENT_CONFIG||{};
const root=document.querySelector('.gr-management-page');
if(!root)return;
const $=id=>document.getElementById(id);
const precision=Math.max(0,Math.min(6,Number(cfg.currencyPrecision||2)));
const money=new Intl.NumberFormat('en-US',{minimumFractionDigits:precision,maximumFractionDigits:precision});
const qty=new Intl.NumberFormat('en-US',{minimumFractionDigits:3,maximumFractionDigits:3});
const whole=new Intl.NumberFormat('en-US',{maximumFractionDigits:0});
let trendChart=null;
if(window.Chart){Chart.defaults.font.family='Calibri, "Segoe UI", Arial, sans-serif';Chart.defaults.color='#5f7185';}
function n(v){const x=Number(v);return Number.isFinite(x)?x:0;}
function esc(v){return String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
function set(id,value){const el=$(id);if(el)el.textContent=value;}
function fmtMoney(v){return money.format(n(v));}
function fmtQty(v){return qty.format(n(v));}
function params(){const p=new URLSearchParams();const loc=$('grMgmtLocation').value;if(loc)p.set('location_id',loc);return p;}
async function getData(){const r=await fetch(cfg.dashboardUrl+'?'+params().toString(),{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'});if(!r.ok)throw new Error('Request failed ('+r.status+')');return r.json();}
function status(msg){const el=$('grMgmtStatusMessage');if(!el)return;el.textContent=msg;el.hidden=false;clearTimeout(status._t);status._t=setTimeout(()=>el.hidden=true,5500);}
function renderKpis(k){
 set('mgmtTotalSales',fmtMoney(k.total_sales));
 set('mgmtSoldLitres',fmtQty(k.today_sold_liters&&k.today_sold_liters.total));
 set('mgmtOutstandingReceived',fmtMoney(k.outstanding_received));
 set('mgmtBankDeposited',fmtMoney(k.bank_deposited));
 const rows=(k.today_sold_liters&&k.today_sold_liters.subcategories)||[];
 $('mgmtFuelChips').innerHTML=rows.length?rows.map(r=>`<span class="gr-mgmt-fuel-chip"><b>${esc(r.name)}</b><strong>${fmtQty(r.litres)} L</strong></span>`).join(''):'<span class="gr-mgmt-empty-chip">No fuel sold today.</span>';
}
function renderTanks(rows){
 const box=$('mgmtTankGauges'),empty=$('mgmtTankEmpty');
 if(!rows.length){box.innerHTML='';empty.hidden=false;return;}empty.hidden=true;
 box.innerHTML=rows.map(t=>{
   const pct=Math.max(0,Math.min(100,n(t.current_pct))),marker=Math.max(0,Math.min(100,n(t.reorder_pct))),alert=!!t.needs_reorder;
   return `<article class="gr-gauge-card ${alert?'is-alert':''}"><div class="gr-gauge-top"><div class="gr-gauge-title">${esc(t.tank)} · ${esc(t.product)}</div><span class="gr-gauge-badge">${alert?'Re-order':'Normal'}</span></div><div class="gr-gauge-sub">${esc(t.location||'All Locations')}</div><div class="gr-gauge-track"><div class="gr-gauge-fill ${alert?'warn':''}" style="width:${pct}%"></div><span class="gr-gauge-marker" style="left:${marker}%" title="Re-order level"></span></div><div class="gr-gauge-values"><span>Current<strong>${fmtQty(t.current)} L</strong></span><span>Storage Volume<strong>${fmtQty(t.storage_volume)} L</strong></span></div>${alert?'<div class="gr-gauge-alert">Current stock has reached the re-order level.</div>':''}</article>`;
 }).join('');
}
function renderTrend(t){
 set('mgmtSalesTrendRange',(t.start||'')+' to '+(t.end||''));
 if(!window.Chart)return;
 if(trendChart)trendChart.destroy();
 trendChart=new Chart($('mgmtSalesTrendChart').getContext('2d'),{type:'line',data:{labels:t.labels||[],datasets:[{label:'Daily Sales',data:t.data||[],borderColor:'#3f6fa6',backgroundColor:'rgba(63,111,166,.10)',borderWidth:2.3,pointRadius:2.4,pointHoverRadius:4.5,tension:.25,fill:true}]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'top',align:'end',labels:{boxWidth:10,boxHeight:10,usePointStyle:true,padding:14,font:{size:11,weight:'600'}}},tooltip:{callbacks:{label:c=>' Daily Sales: '+fmtMoney(c.parsed.y)}}},scales:{x:{grid:{display:false},ticks:{maxRotation:35,minRotation:0,autoSkip:true,maxTicksLimit:15,font:{size:10}}},y:{beginAtZero:true,grid:{color:'rgba(126,147,169,.13)'},ticks:{callback:v=>money.format(v),font:{size:10}},title:{display:true,text:'Sales Amount',font:{size:10,weight:'700'}}}}}});
}
function renderRisk(r){
 set('mgmtRiskCustomers',whole.format(n(r.customers)));set('mgmtRiskTotal',fmtMoney(r.total_outstanding));
 const rows=r.rows||[];
 $('mgmtCreditRiskRows').innerHTML=rows.length?rows.map(x=>`<tr><td><strong>${esc(x.customer_name||'—')}</strong></td><td>${esc(x.customer_code||'—')}</td><td>${esc(x.mobile||'—')}</td><td class="gr-num">${whole.format(n(x.invoices))}</td><td class="gr-num">${whole.format(n(x.min_age_days))}–${whole.format(n(x.max_age_days))}</td><td class="gr-num gr-value-negative"><strong>${fmtMoney(x.outstanding)}</strong></td></tr>`).join(''):'<tr><td colspan="6" class="gr-table-empty">No customers have outstanding balances older than 30 days.</td></tr>';
}
function renderVariance(v){
 set('mgmtDipStock',fmtQty(v.dip_stock));set('mgmtSystemStock',fmtQty(v.system_stock));set('mgmtVarianceValue',(n(v.variance)>0?'+':'')+fmtQty(v.variance));set('mgmtFuelLoss',fmtQty(v.fuel_loss));
 const card=$('mgmtVarianceCard'),badge=$('mgmtVarianceBadge'),msg=$('mgmtVarianceMessage');
 card.classList.toggle('is-danger',!!v.is_alert);badge.textContent=v.is_alert?'RED ALERT':'Within Limit';
 msg.textContent=v.is_alert?'Variance is above the 100 liter threshold. Immediate verification is required.':'Variance is within the 100 liter alert threshold.';
 set('mgmtVarianceReading',v.reading_at?'Latest dip reading: '+v.reading_at:'No dip reading found for today.');
}
async function load(){root.classList.add('gr-loading');try{const j=await getData();renderKpis(j.kpis||{});renderTanks(j.tanks||[]);renderTrend(j.sales_trend||{});renderRisk(j.credit_risk||{});renderVariance(j.variance||{});}catch(e){console.error(e);status('Unable to load the Management Dashboard. Please check the server log.');}finally{root.classList.remove('gr-loading');}}
$('grMgmtApply').addEventListener('click',load);$('grMgmtRefresh').addEventListener('click',load);load();
})();
