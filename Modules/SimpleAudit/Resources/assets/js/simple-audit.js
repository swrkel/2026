(function(){
'use strict';

const app = document.getElementById('sauApp');
if (!app) return;

const I18N = window.SAU_I18N || {};
function t(key, fallback, params={}){
  let value=I18N;for(const part of String(key).split('.')){value=value&&typeof value==='object'?value[part]:undefined}
  let out=(typeof value==='string'&&value.length)?value:(fallback||key);
  Object.entries(params).forEach(([k,v])=>{out=out.replaceAll(':'+k,String(v))});
  return out;
}

const state = {
  report:null,
  businesses:[],
  locations:[],
  stores:[],
  fyStartMonth:4,
  datePreset:'this_year',
  search:'',
  pageLength:Number(app.dataset.pageLength || 25),
  pages:{},
  hiddenColumns:{},
  loading:false,
  loadTimer:null,
  detailCache:new Map(),
  hoverTimer:null,
  hoverTarget:null
};

const sectionDefs = {
  purchases:{
    label:t('purchase','Purchase'),
    nameKey:'product',
    columns:['product','sku','qty','unit_cost','total','discount','tax'],
    money:['unit_cost','total','discount','tax'],qty:['qty']
  },
  stock_movements:{
    label:t('stock_movements','Stock Movements'),nameKey:'product',
    columns:['product','sku','before','purchases','purchase_return','stock_adjustment','after','difference'],
    money:[],qty:['before','purchases','purchase_return','stock_adjustment','after','difference']
  },
  supplier_payments:{label:t('supplier_payments','Supplier Payments'),nameKey:'supplier',columns:['supplier','before','after','difference'],money:['before','after','difference'],qty:[]},
  accounts:{label:t('accounts','Accounts'),nameKey:'account',columns:['account','account_number','before','after','difference'],money:['before','after','difference'],qty:[]},
  supplier_ledgers:{label:t('supplier_ledgers','Supplier Ledgers'),nameKey:'supplier',columns:['supplier','before','after','difference'],money:['before','after','difference'],qty:[]}
};

const detailLabelKeys={
  'Date':'date','Purchase':'purchase','Supplier':'supplier','Qty':'qty','Unit Cost':'unit_cost','Total Inc Tax':'total_inc_tax','Discount':'discount','Tax':'tax','Returned Qty':'returned_qty','Status':'status','User':'user',
  'Movement':'movement','Reference':'reference','Type':'type','Location ID':'location_id_label','Before Qty':'before_qty','After Qty':'after_qty','Source':'source','Payment Ref':'payment_ref','Method':'method','Amount':'amount','Account':'account','Cheque No':'cheque_no','Bank':'bank','Entry':'entry','Note':'note','Sub Type':'sub_type','Page':'page'
};
function detailHeader(key){const lk=detailLabelKeys[key];return lk?t(lk,key):key}
function detailValue(key,value,precision={}){
  if(value==null)return '';
  const movementMap={'Purchase':t('purchase','Purchase'),'Purchase Return':t('purchase_return','Purchase Return'),'Stock Adjustment':t('stock_adjustment','Stock Adjustment'),'Stock Snapshot':t('stock_snapshot','Stock Snapshot')};
  if(key==='Movement'&&movementMap[value])return movementMap[value];
  if(typeof value!=='number')return String(value);
  if(['Qty','Returned Qty','Before Qty','After Qty'].includes(key))return fmt(value,Number(precision.quantity??state.report?.precision?.quantity??2));
  if(['Unit Cost','Total Inc Tax','Discount','Tax','Amount'].includes(key))return fmt(value,Number(precision.currency??state.report?.precision?.currency??2));
  return Number(value).toLocaleString();
}

function qs(sel,root=document){return root.querySelector(sel)}
function qsa(sel,root=document){return Array.from(root.querySelectorAll(sel))}
function esc(v){return String(v==null?'':v).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))}
function num(v){return v==null?null:Number(v)}
function fmt(v,p){if(v==null||Number.isNaN(Number(v)))return t('na','N/A');return Number(v).toLocaleString(undefined,{minimumFractionDigits:p,maximumFractionDigits:p})}
function csrf(){return window.SAU_CSRF || qs('meta[name="csrf-token"]')?.content || ''}
function show(el){if(el)el.hidden=false}function hide(el){if(el)el.hidden=true}
function endpoint(name){return app.dataset[name]}

class Combo{
  constructor(el){
    this.el=el;this.text=qs('input[type=text]',el);this.hidden=qs('input[type=hidden]',el);this.menu=qs('.sau-combo-menu',el);this.options=[];this.index=-1;
    this.text.addEventListener('focus',()=>{this.open();if(this.hidden.value)requestAnimationFrame(()=>this.text.select())});
    this.text.addEventListener('click',()=>{this.open();if(this.hidden.value)this.text.select()});
    this.text.addEventListener('input',()=>{this.hidden.value='';this.index=-1;this.render();this.open()});
    this.text.addEventListener('keydown',e=>this.key(e));
    document.addEventListener('click',e=>{if(!this.el.contains(e.target))this.close()});
  }
  setOptions(options,placeholder,selectedId){
    this.options=options||[];this.text.placeholder=placeholder||t('select','Select');
    this.render();
    if(selectedId!==undefined&&selectedId!==null)this.selectById(String(selectedId),false);
    else{this.hidden.value='';this.text.value=''}
  }
  render(){
    // If a value is already selected, the text box contains the selected label.
    // That label is display text, not a search term. Show the complete option list
    // when the dropdown is opened; as soon as the user types, input() clears the
    // hidden selected id and this text becomes the live type-and-filter term.
    const f=this.hidden.value ? '' : this.text.value.trim().toLowerCase();
    const terms=f.split(/\s+/).filter(Boolean);
    const list=this.options.filter(o=>{const label=String(o.label).toLowerCase();return !terms.length||terms.every(term=>label.includes(term))});
    this.index=-1;
    this.menu.innerHTML=list.length?list.map((o,i)=>`<div class="sau-combo-option" data-id="${esc(o.id)}" data-i="${i}">${esc(o.label)}</div>`).join(''):`<div class="sau-combo-empty">${esc(t('no_matching_options','No matching options'))}</div>`;
    qsa('.sau-combo-option',this.menu).forEach(node=>node.addEventListener('mousedown',e=>{e.preventDefault();this.selectById(node.dataset.id,true)}));
  }
  open(){this.el.classList.add('open');this.text.setAttribute('aria-expanded','true');this.render()}
  close(){this.el.classList.remove('open');this.text.setAttribute('aria-expanded','false')}
  selectById(id,emit=true){
    const o=this.options.find(x=>String(x.id)===String(id));if(!o)return;
    this.hidden.value=o.id;this.text.value=o.label;this.close();
    if(emit)this.el.dispatchEvent(new CustomEvent('combochange',{bubbles:true,detail:o}));
  }
  value(){return this.hidden.value}
  selected(){return this.options.find(x=>String(x.id)===String(this.hidden.value))||null}
  key(e){
    const items=qsa('.sau-combo-option',this.menu);if(!items.length)return;
    if(e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();this.open();this.index=e.key==='ArrowDown'?Math.min(this.index+1,items.length-1):Math.max(this.index-1,0);items.forEach((n,i)=>n.classList.toggle('active',i===this.index));items[this.index]?.scrollIntoView({block:'nearest'})}
    if(e.key==='Enter'&&this.index>=0){e.preventDefault();this.selectById(items[this.index].dataset.id,true)}
    if(e.key==='Escape')this.close();
  }
}

const combos={};
qsa('.sau-combo').forEach(el=>combos[el.dataset.name]=new Combo(el));

function comboValue(name){return combos[name]?.value()||''}
function currentFilters(){return {tenant_id:comboValue('tenant_id'),business_id:comboValue('business_id'),location_id:comboValue('location_id'),store_id:comboValue('store_id'),from:qs('#dateFrom').value,to:qs('#dateTo').value}}
function queryString(extra={}){const p=new URLSearchParams();Object.entries({...currentFilters(),...extra}).forEach(([k,v])=>{if(v!==''&&v!==null&&v!==undefined)p.set(k,v)});return p.toString()}

async function getJSON(url){const ctl=new AbortController();const timer=setTimeout(()=>ctl.abort(),45000);try{const r=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'},credentials:'same-origin',cache:'no-store',signal:ctl.signal});const j=await r.json().catch(()=>({message:t('invalid_server_response','Invalid server response')}));if(!r.ok)throw new Error(j.message||t('request_failed','Request failed'));return j}catch(e){if(e&&e.name==='AbortError')throw new Error(t('request_timeout','The request took too long. Please retry.'));throw e}finally{clearTimeout(timer)}}
async function postJSON(url,body){const ctl=new AbortController();const timer=setTimeout(()=>ctl.abort(),45000);try{const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'},body:JSON.stringify(body),credentials:'same-origin',cache:'no-store',signal:ctl.signal});const j=await r.json().catch(()=>({message:t('invalid_server_response','Invalid server response')}));if(!r.ok)throw new Error(j.message||Object.values(j.errors||{}).flat().join(' ')||t('request_failed','Request failed'));return j}catch(e){if(e&&e.name==='AbortError')throw new Error(t('request_timeout','The request took too long. Please retry.'));throw e}finally{clearTimeout(timer)}}

async function loadContext(){
  setLoading(true);
  try{
    const tenants=(await getJSON(endpoint('tenantsUrl'))).data||[];
    if(tenants.length){
      combos.tenant_id.setOptions(tenants.map(x=>({id:x.id,label:x.label})), t('select_tenant','Select tenant'));
      qs('#tenantField').style.display='';
      combos.tenant_id.selectById(String(tenants[0].id),false);
      await loadBusinesses(tenants[0].id);
    }else{
      qs('#tenantField').style.display='none';
      combos.tenant_id.setOptions([], t('current_tenant','Current tenant'));
      await loadBusinesses('');
    }
  }catch(e){
    clearReport();
    alertBox(e.message);
  }finally{
    setLoading(false);
  }
}

async function loadBusinesses(tenantId){
  const p=new URLSearchParams();if(tenantId)p.set('tenant_id',tenantId);
  const data=(await getJSON(endpoint('businessesUrl')+'?'+p.toString())).data||[];
  state.businesses=data;
  combos.business_id.setOptions(data.map(x=>({id:x.id,label:x.name,raw:x})), t('select_business','Select business'));
  if(data.length){
    combos.business_id.selectById(String(data[0].id),false);
    state.fyStartMonth=Number(data[0].fy_start_month||4);
    await loadLocations(data[0].id,tenantId);
    if(state.datePreset==='this_fy'||state.datePreset==='last_fy')applyDatePreset(state.datePreset,false);
    initDateRangePicker();
    scheduleLoad(20);
  }else{clearReport(t('no_businesses','No businesses are available for this tenant.'))}
}

async function loadLocations(businessId,tenantId){
  const p=new URLSearchParams({business_id:String(businessId)});if(tenantId)p.set('tenant_id',tenantId);
  const data=(await getJSON(endpoint('locationsUrl')+'?'+p.toString())).data||[];state.locations=data;
  const opts=[{id:'',label:t('all_locations','All Locations')},...data.map(x=>({id:x.id,label:x.name+(x.city?' — '+x.city:''),raw:x}))];
  combos.location_id.setOptions(opts,t('all_locations','All Locations'));
  // System standard: auto-load first available location while preserving All Locations.
  combos.location_id.selectById(data.length?String(data[0].id):'',false);
  await loadStores(businessId,comboValue('location_id'),tenantId);
}

async function loadStores(businessId,locationId,tenantId){
  if(app.dataset.showStore!=='1'){combos.store_id.setOptions([{id:'',label:t('all_stores','All Stores')}]);combos.store_id.selectById('',false);return}
  const p=new URLSearchParams({business_id:String(businessId)});if(locationId)p.set('location_id',locationId);if(tenantId)p.set('tenant_id',tenantId);
  const data=(await getJSON(endpoint('storesUrl')+'?'+p.toString())).data||[];state.stores=data;
  combos.store_id.setOptions([{id:'',label:t('all_stores','All Stores')},...data.map(x=>({id:x.id,label:x.name,raw:x}))],t('all_stores','All Stores'));
  combos.store_id.selectById('',false);
}

app.addEventListener('combochange',async e=>{
  const name=e.target.dataset.name;
  try{
    if(name==='tenant_id')await loadBusinesses(comboValue('tenant_id'));
    else if(name==='business_id'){
      const b=state.businesses.find(x=>String(x.id)===comboValue('business_id'));state.fyStartMonth=Number(b?.fy_start_month||4);
      await loadLocations(comboValue('business_id'),comboValue('tenant_id'));if(state.datePreset==='this_fy'||state.datePreset==='last_fy')applyDatePreset(state.datePreset,false);initDateRangePicker();scheduleLoad(10);
    }
    else if(name==='location_id'){await loadStores(comboValue('business_id'),comboValue('location_id'),comboValue('tenant_id'));scheduleLoad(10)}
    else if(name==='store_id')scheduleLoad(10);
  }catch(err){alertBox(err.message)}
});

function isoDate(d){const x=new Date(d.getTime()-d.getTimezoneOffset()*60000);return x.toISOString().slice(0,10)}
function fyRange(yearOffset){
  const now=new Date();const m=state.fyStartMonth||4;let startYear=now.getFullYear();if(now.getMonth()+1<m)startYear--;startYear+=yearOffset;
  const start=new Date(startYear,m-1,1);const end=new Date(startYear+1,m-1,0);return [start,end]
}
function displayDate(date){
  if(window.moment){const fmt=window.moment_date_format||'YYYY-MM-DD';return window.moment(date).format(fmt)}
  return isoDate(date)
}
function dateRangeText(start,end){return `${displayDate(start)} ~ ${displayDate(end)}`}
function syncDateRangePicker(){
  if(!window.jQuery||!window.moment)return;
  const $input=window.jQuery('#dateRange'),picker=$input.data('daterangepicker');
  const from=qs('#dateFrom').value,to=qs('#dateTo').value;
  if(picker&&from&&to){picker.setStartDate(window.moment(from,'YYYY-MM-DD'));picker.setEndDate(window.moment(to,'YYYY-MM-DD'))}
}
function setDateRange(start,end,preset='custom',load=true){
  state.datePreset=preset||'custom';
  qs('#dateFrom').value=isoDate(start);qs('#dateTo').value=isoDate(end);qs('#dateRange').value=dateRangeText(start,end);syncDateRangePicker();
  if(load)scheduleLoad(30)
}
function applyDatePreset(value,load=true){
  const now=new Date();let start,end;
  if(value==='this_year'){start=new Date(now.getFullYear(),0,1);end=new Date(now.getFullYear(),11,31)}
  else if(value==='last_year'){start=new Date(now.getFullYear()-1,0,1);end=new Date(now.getFullYear()-1,11,31)}
  else if(value==='this_fy'){[start,end]=fyRange(0)}
  else if(value==='last_fy'){[start,end]=fyRange(-1)}
  else return;
  setDateRange(start,end,value,load)
}
function standardDateRanges(){
  if(!window.moment)return null;
  const now=window.moment();
  const [fyStart,fyEnd]=fyRange(0),[lastFyStart,lastFyEnd]=fyRange(-1);
  return {
    [t('this_year','This Year')]:[now.clone().startOf('year'),now.clone().endOf('year')],
    [t('last_year','Last Year')]:[now.clone().subtract(1,'year').startOf('year'),now.clone().subtract(1,'year').endOf('year')],
    [t('this_fy','This FY')]:[window.moment(fyStart),window.moment(fyEnd)],
    [t('last_fy','Last FY')]:[window.moment(lastFyStart),window.moment(lastFyEnd)]
  }
}
function presetFromChosenLabel(label){
  if(label===t('this_year','This Year'))return 'this_year';
  if(label===t('last_year','Last Year'))return 'last_year';
  if(label===t('this_fy','This FY'))return 'this_fy';
  if(label===t('last_fy','Last FY'))return 'last_fy';
  return 'custom'
}
function initDateRangePicker(){
  const input=qs('#dateRange');if(!input)return;
  const $=window.jQuery;
  if(!$||!$.fn||!$.fn.daterangepicker||!window.moment)return;
  const $input=$(input),existing=$input.data('daterangepicker');if(existing&&typeof existing.remove==='function')existing.remove();
  const globalSettings=(window.dateRangeSettings&&typeof window.dateRangeSettings==='object')?window.dateRangeSettings:{};
  const locale=Object.assign({},globalSettings.locale||{}, {
    format:window.moment_date_format||(globalSettings.locale&&globalSettings.locale.format)||'YYYY-MM-DD',
    applyLabel:t('apply','Apply'),cancelLabel:t('clear','Clear'),customRangeLabel:t('custom','Custom')
  });
  const settings=Object.assign({},globalSettings,{
    autoUpdateInput:false,alwaysShowCalendars:true,showDropdowns:true,locale:locale,ranges:standardDateRanges()
  });
  delete settings.startDate;delete settings.endDate;
  $input.daterangepicker(settings);
  $input.off('.sauDateRange')
    .on('apply.daterangepicker.sauDateRange',function(ev,picker){setDateRange(picker.startDate.toDate(),picker.endDate.toDate(),presetFromChosenLabel(picker.chosenLabel),true)})
    .on('cancel.daterangepicker.sauDateRange',function(){this.value='';qs('#dateFrom').value='';qs('#dateTo').value='';state.datePreset='custom';clearReport();});
  syncDateRangePicker();
}
let manualDateTimer=null;
qs('#dateRange').addEventListener('input',e=>{
  clearTimeout(manualDateTimer);manualDateTimer=setTimeout(()=>{
    const raw=String(e.target.value||'').trim();if(!raw)return;
    const parts=raw.split(/\s*~\s*/);if(parts.length!==2)return;
    if(window.moment){
      const fmt=window.moment_date_format||'YYYY-MM-DD';const a=window.moment(parts[0],fmt,true),b=window.moment(parts[1],fmt,true);
      if(a.isValid()&&b.isValid()&&!a.isAfter(b))setDateRange(a.toDate(),b.toDate(),'custom',true)
    }else if(/^\d{4}-\d{2}-\d{2}$/.test(parts[0])&&/^\d{4}-\d{2}-\d{2}$/.test(parts[1])){
      const a=new Date(parts[0]+'T00:00:00'),b=new Date(parts[1]+'T00:00:00');if(!Number.isNaN(a.getTime())&&!Number.isNaN(b.getTime())&&a<=b)setDateRange(a,b,'custom',true)
    }
  },450)
});

function scheduleLoad(delay=180){clearTimeout(state.loadTimer);state.loadTimer=setTimeout(loadReport,delay)}
async function loadReport(){
  const f=currentFilters();if(!f.business_id||!f.from||!f.to)return;
  setLoading(true);clearAlert();
  try{
    const report=await getJSON(endpoint('dataUrl')+'?'+queryString());state.report=report;state.detailCache.clear();
    Object.keys(sectionDefs).forEach(s=>{state.pages[s]=1;renderSection(s)});
    renderSummary();renderCoverage();
  }catch(e){clearReport();alertBox(e.message)}finally{setLoading(false)}
}
function setLoading(v){state.loading=!!v;const el=qs('#sauLoading');if(!el)return;if(v){el.hidden=false;el.removeAttribute('hidden');el.style.display='flex'}else{el.hidden=true;el.setAttribute('hidden','hidden');el.style.display='none'}}
function clearReport(message){state.report=null;Object.keys(sectionDefs).forEach(s=>{const card=qs(`[data-section="${s}"]`);qs('tbody',card).innerHTML='';qs('tfoot',card).innerHTML='';qs('.sau-section-count',card).textContent=`0 ${t('rows','rows')}`;qs('.sau-pagination',card).innerHTML=''});if(message)alertBox(message)}
function alertBox(message){const el=qs('#sauAlert');el.textContent=message;show(el)}function clearAlert(){hide(qs('#sauAlert'));qs('#sauAlert').textContent=''}
function renderSummary(){
  if(!state.report)return;const m=state.report.meta;qs('#sauSelectionSummary').textContent=`${m.business_name} • ${m.location_name}${m.store_name&&m.store_name!==t('all_stores','All Stores')?' • '+m.store_name:''} • ${m.from} ${t('to','to')} ${m.to}`;
  qs('#sauChangePill').textContent=`${state.report.audit_changes?.total||0} ${t((state.report.audit_changes?.total||0)===1?'audit_change':'audit_changes',(state.report.audit_changes?.total||0)===1?'audit change':'audit changes')}`
}
function renderCoverage(){
  const el=qs('#sauCoverageAlert');if(!state.report){hide(el);return}
  const meta=state.report.meta||{};const stock=state.report.sections.stock_movements;const started=meta.stock_tracking_started_at;
  const incomplete=stock?.totals?.snapshot_complete===false;const messages=[];
  if(meta.store_id){
    messages.push(t('store_stock_location_only','The supplied ERP schema keeps physical stock balances at Location level, not Store level. For a specific Store, Purchases/Returns/Adjustments are store-filtered while Before/After/Difference are shown as N/A to avoid mixing store movements with location-wide stock.'));
  }else if(incomplete){
    messages.push(started?t('stock_tracking_started','Stock Before/After snapshot tracking started on :date. For an earlier date period, movement columns remain available but unavailable historical stock balances are shown as N/A.',{date:started}):t('stock_tracking_incomplete','Stock Before/After snapshot history is incomplete for this period. Movement columns are still calculated from source transactions.'));
  }
  if(meta.purchase_return_tracking_complete===false){
    messages.push(meta.audit_tracking_started_at?t('purchase_return_history_incomplete','Detailed per-period Purchase Return quantity tracking started on :date. For an earlier period, the legacy schema exposes cumulative returned quantity only, so Purchase Return quantities are shown using the best available historical value.',{date:meta.audit_tracking_started_at}):t('purchase_return_history_unavailable','Detailed historical Purchase Return quantity changes are not available before Simple Audit tracking starts.'));
  }
  if(messages.length){el.textContent=messages.join('\n');show(el)}else hide(el)
}

function filteredRows(section){
  if(!state.report)return[];const rows=state.report.sections[section]?.rows||[];const s=state.search.toLowerCase().trim();if(!s)return rows;
  return rows.filter(r=>Object.values(r).some(v=>v!=null&&String(v).toLowerCase().includes(s)))
}
function renderSection(section){
  const card=qs(`[data-section="${section}"]`);if(!card||!state.report)return;const def=sectionDefs[section];const all=filteredRows(section);const len=state.pageLength;const pages=Math.max(1,Math.ceil(all.length/len));state.pages[section]=Math.min(state.pages[section]||1,pages);const page=state.pages[section];const rows=all.slice((page-1)*len,page*len);
  qs('.sau-section-count',card).textContent=`${all.length} ${t(all.length===1?'row':'rows',all.length===1?'row':'rows')}`;
  const tbody=qs('tbody',card);tbody.innerHTML=rows.length?rows.map(r=>rowHTML(section,r)).join(''):`<tr><td colspan="${def.columns.length}" class="sau-muted" style="text-align:center;padding:18px">${esc(t('no_rows','No affected or changed records found.'))}</td></tr>`;
  renderFooter(section,card);renderPagination(section,card,all.length,pages,page);applyColumnVisibility(section);bindDetailClicks(card)
}
function rowHTML(section,row){
  const def=sectionDefs[section];return `<tr data-key="${esc(row.key)}">`+def.columns.map(col=>cellHTML(section,col,row)).join('')+'</tr>'
}
function cellHTML(section,col,row){
  const def=sectionDefs[section];let value=row[col];let display;let cls=[];let clickable=false;
  if(def.money.includes(col)){display=fmt(value,state.report.precision.currency);cls.push('num');clickable=true}
  else if(def.qty.includes(col)){display=fmt(value,row.qty_precision??state.report.precision.quantity);cls.push('num');clickable=true}
  else display=esc(value??'');
  if(col===def.nameKey){clickable=true;const cc=Number(row.change_count||0);const cLabel=t(cc===1?'captured_change':'captured_changes',cc===1?'captured change':'captured changes');display=esc(value??'')+(cc?`<span class="sau-change-badge" title="${esc(cc+' '+cLabel)}">${esc(cc+' '+cLabel)}</span>`:'')}
  if(section==='accounts'&&col==='account_number')clickable=true;
  if(col==='difference'&&value!=null){cls.push(Math.abs(Number(value))>0.0000001?'sau-diff-bad':'sau-diff-ok')}
  if(clickable)cls.push('sau-clickable');
  const detailRows=Number(row.transaction_count||row.payment_count||row.activity_count||row.ledger_count||0);
  const changeRows=Number(row.change_count||0);
  let title='';
  if(clickable){
    if(detailRows&&changeRows) title=t('click_details_full','Click to view transaction details • :rows transaction(s) • :changes captured change(s)',{rows:detailRows,changes:changeRows});
    else if(detailRows) title=t('click_details_with_count','Click to view transaction details • :count transaction(s)',{count:detailRows});
    else if(changeRows) title=t('click_details_with_changes','Click to view transaction details • :count captured change(s)',{count:changeRows});
    else title=t('click_details','Click to view transaction details');
  }
  return `<td data-col="${col}" class="${cls.join(' ')}" ${clickable?`data-detail-column="${col}" title="${esc(title)}"`:''}>${display}</td>`
}
function renderFooter(section,card){
  const def=sectionDefs[section],tot=state.report.sections[section]?.totals||{};const footer=qs('tfoot',card);
  footer.innerHTML='<tr>'+def.columns.map((col,i)=>{
    if(i===0)return `<td data-col="${col}">${esc(String(t('total','Total')).toUpperCase())}</td>`;
    if(!(col in tot))return `<td data-col="${col}"></td>`;
    let v=tot[col],out='';if(v==null)out=t('na','N/A');else if(def.money.includes(col))out=fmt(v,state.report.precision.currency);else if(def.qty.includes(col))out=fmt(v,state.report.precision.quantity);else out=esc(v);
    return `<td data-col="${col}" class="${def.money.includes(col)||def.qty.includes(col)?'num':''}">${out}</td>`
  }).join('')+'</tr>'
}
function renderPagination(section,card,total,pages,page){
  const el=qs('.sau-pagination',card);if(!total){el.innerHTML='';return}
  let html=`<span>${esc(t('showing','Showing'))} ${(page-1)*state.pageLength+1}–${Math.min(page*state.pageLength,total)} ${esc(t('of','of'))} ${total}</span>`;
  html+=`<button data-page="${Math.max(1,page-1)}" ${page===1?'disabled':''}>‹</button>`;
  const start=Math.max(1,page-2),end=Math.min(pages,page+2);for(let i=start;i<=end;i++)html+=`<button data-page="${i}" class="${i===page?'active':''}">${i}</button>`;
  html+=`<button data-page="${Math.min(pages,page+1)}" ${page===pages?'disabled':''}>›</button>`;el.innerHTML=html;
  qsa('button[data-page]',el).forEach(b=>b.addEventListener('click',()=>{state.pages[section]=Number(b.dataset.page);renderSection(section)}))
}
function bindDetailClicks(card){
  qsa('td.sau-clickable',card).forEach(td=>{
    td.addEventListener('click',()=>openDetails(card.dataset.section,td.closest('tr').dataset.key,td.dataset.detailColumn||''));
    td.addEventListener('mouseenter',e=>scheduleHoverDetails(e,card.dataset.section,td.closest('tr').dataset.key,td.dataset.detailColumn||''));
    td.addEventListener('mousemove',positionHoverTooltip);
    td.addEventListener('mouseleave',hideHoverTooltip);
  })
}
function hoverTooltip(){
  let el=qs('#sauHoverTooltip');
  if(!el){el=document.createElement('div');el.id='sauHoverTooltip';el.className='sau-hover-tooltip';el.hidden=true;document.body.appendChild(el)}
  return el;
}
function scheduleHoverDetails(e,section,key,column){
  clearTimeout(state.hoverTimer);state.hoverTarget={section,key,column};positionHoverTooltip(e);const el=hoverTooltip();
  state.hoverTimer=setTimeout(async()=>{
    if(!state.hoverTarget||state.hoverTarget.section!==section||state.hoverTarget.key!==key||state.hoverTarget.column!==column)return;
    el.innerHTML=`<div class="sau-hover-loading">${esc(t('loading_details','Loading details…'))}</div>`;el.hidden=false;
    const cacheKey=queryString({section,key,column});
    try{
      let data=state.detailCache.get(cacheKey);
      if(!data){data=await getJSON(endpoint('detailsUrl')+'?'+cacheKey);state.detailCache.set(cacheKey,data)}
      if(!state.hoverTarget||state.hoverTarget.section!==section||state.hoverTarget.key!==key||state.hoverTarget.column!==column)return;
      renderHoverDetails(data,el);
    }catch(err){el.textContent=err.message;el.hidden=false}
  },220);
}
function positionHoverTooltip(e){
  const el=qs('#sauHoverTooltip');if(!el)return;const pad=14;let x=e.clientX+pad,y=e.clientY+pad;
  const w=el.offsetWidth||360,h=el.offsetHeight||120;if(x+w>window.innerWidth-8)x=Math.max(8,e.clientX-w-pad);if(y+h>window.innerHeight-8)y=Math.max(8,e.clientY-h-pad);
  el.style.left=x+'px';el.style.top=y+'px';
}
function renderHoverDetails(data,el){
  const rows=(data.rows||[]).slice(0,3),changes=data.changes||[];
  if(!rows.length&&!changes.length){el.innerHTML=`<div class="sau-muted">${esc(t('no_current_rows','No current transaction rows for this selection.'))}</div>`;el.hidden=false;return}
  let html='';
  rows.forEach((row,i)=>{const pairs=Object.entries(row).slice(0,5);html+=`<div class="sau-hover-row">${pairs.map(([k,v])=>`<span><strong>${esc(detailHeader(k))}:</strong> ${esc(detailValue(k,v,data.precision||{}))}</span>`).join('')}</div>`});
  if((data.rows||[]).length>3)html+=`<div class="sau-hover-more">+${(data.rows||[]).length-3} ${esc(t('more_rows','more row(s)'))}</div>`;
  if(changes.length)html+=`<div class="sau-hover-changes">${changes.length} ${esc(t(changes.length===1?'captured_change':'captured_changes',changes.length===1?'captured change':'captured changes'))}</div>`;
  el.innerHTML=html;el.hidden=false;
}
function hideHoverTooltip(){clearTimeout(state.hoverTimer);state.hoverTarget=null;const el=qs('#sauHoverTooltip');if(el)el.hidden=true}

async function openDetails(section,key,column){
  const modal=qs('#detailsModal');show(modal);qs('#detailsRows').innerHTML=`<div class="sau-muted">${esc(t('loading_details','Loading details…'))}</div>`;qs('#detailsChanges').innerHTML='';activateDetailTab('rows');
  try{const d=await getJSON(endpoint('detailsUrl')+'?'+queryString({section,key,column}));renderDetails(d)}catch(e){qs('#detailsRows').innerHTML=`<div class="sau-alert">${esc(e.message)}</div>`}
}
function renderDetails(data){
  const rows=data.rows||[];const box=qs('#detailsRows');if(!rows.length)box.innerHTML=`<p class="sau-muted">${esc(t('no_current_rows','No current transaction rows for this selection.'))}</p>`;else{
    const cols=Array.from(new Set(rows.flatMap(r=>Object.keys(r))));box.innerHTML=`<div class="sau-table-wrap"><table class="sau-detail-table"><thead><tr>${cols.map(c=>`<th>${esc(detailHeader(c))}</th>`).join('')}</tr></thead><tbody>${rows.map(r=>`<tr>${cols.map(c=>`<td>${esc(detailValue(c,r[c],data.precision||{}))}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`
  }
  const changes=data.changes||[];const cb=qs('#detailsChanges');cb.innerHTML=changes.length?changes.map(ch=>`<div class="sau-json-change"><div class="sau-json-change-head">${esc(ch.date)} • ${esc(ch.event)} • ${esc(ch.source)} #${esc(ch.source_id)}</div><div class="sau-json-grid"><div><strong>${esc(t('before','Before'))}</strong><pre>${esc(JSON.stringify(ch.old,null,2)||'—')}</pre></div><div><strong>${esc(t('after','After'))}</strong><pre>${esc(JSON.stringify(ch.new,null,2)||'—')}</pre></div></div></div>`).join(''):`<p class="sau-muted">${esc(t('no_captured_changes','No captured edit/delete changes for this item in the selected period.'))}</p>`
}
function activateDetailTab(tab){qsa('[data-detail-tab]').forEach(b=>b.classList.toggle('active',b.dataset.detailTab===tab));qs('#detailsRows').hidden=tab!=='rows';qs('#detailsChanges').hidden=tab!=='changes'}
qsa('[data-detail-tab]').forEach(b=>b.addEventListener('click',()=>activateDetailTab(b.dataset.detailTab)));

qs('#universalSearch').addEventListener('input',e=>{state.search=e.target.value;Object.keys(sectionDefs).forEach(s=>{state.pages[s]=1;renderSection(s)})});
qs('#pageLength').value=String(state.pageLength);qs('#pageLength').addEventListener('change',e=>{state.pageLength=Number(e.target.value);Object.keys(sectionDefs).forEach(s=>{state.pages[s]=1;renderSection(s)})});

function openColumns(){
  const box=qs('#columnOptions');box.innerHTML=Object.entries(sectionDefs).map(([s,d])=>`<div class="sau-check-group"><strong>${esc(d.label)}</strong>${d.columns.map(c=>`<label><input type="checkbox" data-section="${s}" data-column="${c}" ${state.hiddenColumns[s]?.has(c)?'':'checked'}> ${esc(columnLabel(s,c))}</label>`).join('')}</div>`).join('');
  qsa('input[type=checkbox]',box).forEach(ch=>ch.addEventListener('change',()=>{state.hiddenColumns[ch.dataset.section]??=new Set();ch.checked?state.hiddenColumns[ch.dataset.section].delete(ch.dataset.column):state.hiddenColumns[ch.dataset.section].add(ch.dataset.column);applyColumnVisibility(ch.dataset.section)}));show(qs('#columnsModal'))
}
function columnLabel(section,col){const th=qs(`[data-section="${section}"] th[data-col="${col}"]`);return th?th.textContent.trim().replace(/\s+/g,' '):col.replaceAll('_',' ')}
function applyColumnVisibility(section){const hidden=state.hiddenColumns[section]||new Set();const card=qs(`[data-section="${section}"]`);qsa('[data-col]',card).forEach(el=>el.style.display=hidden.has(el.dataset.col)?'none':'')}

qsa('[data-close-modal]').forEach(el=>el.addEventListener('click',()=>qsa('.sau-modal').forEach(hide)));
document.addEventListener('keydown',e=>{if(e.key==='Escape')qsa('.sau-modal').forEach(hide)});

qsa('[data-action]').forEach(btn=>btn.addEventListener('click',()=>toolbarAction(btn.dataset.action)));
function toolbarAction(action){
  if(!state.report&&action!=='columns'){alertBox(t('load_audit_first','Please load a Purchase Audit first.'));return}
  if(action==='columns'){openColumns();return}
  if(action==='csv'||action==='xls'||action==='pdf'){window.location.href=endpoint('exportUrl')+'/'+action+'?'+queryString();return}
  if(action==='print'){window.open(endpoint('printUrl')+'?'+queryString(),'_blank','noopener');return}
  if(action==='email'){show(qs('#emailModal'));return}
  if(action==='whatsapp'){show(qs('#whatsappModal'));return}
}

qs('#emailForm').addEventListener('submit',async e=>{
  e.preventDefault();const submit=qs('button[type=submit]',e.target);submit.disabled=true;
  try{const fd=new FormData(e.target);const body={...currentFilters(),email:fd.get('email'),note:fd.get('note')};const r=await postJSON(endpoint('emailUrl'),body);hide(qs('#emailModal'));alertBox(r.message||t('email_sent','Email sent successfully.'));qs('#sauAlert').classList.add('sau-alert-info')}
  catch(err){alertBox(err.message)}finally{submit.disabled=false}
});

qs('#whatsappForm').addEventListener('submit',async e=>{
  e.preventDefault();const submit=qs('button[type=submit]',e.target);submit.disabled=true;const win=window.open('about:blank','_blank');
  try{const fd=new FormData(e.target);const phone=String(fd.get('phone')||'').replace(/\D/g,'');if(!phone)throw new Error(t('valid_whatsapp_number','Enter a valid WhatsApp number.'));const note=String(fd.get('note')||'').trim();const r=await postJSON(endpoint('shareUrl'),{...currentFilters(),note});const m=state.report?.meta||{};const text=[note,`${t('module_name','Simple Audit')} - ${t('purchase_audit','Purchase Audit')}`,`${t('business','Business')}: ${m.business_name||''}`,`${t('date','Date')}: ${m.from||''} ${t('to','to')} ${m.to||''}`,r.url].filter(Boolean).join('\n');if(win)win.location.href='https://wa.me/'+phone+'?text='+encodeURIComponent(text);else window.open('https://wa.me/'+phone+'?text='+encodeURIComponent(text),'_blank');hide(qs('#whatsappModal'))}
  catch(err){if(win)win.close();alertBox(err.message)}finally{submit.disabled=false}
});

// Initialise the system-standard date range picker before context is loaded.
applyDatePreset('this_year',false);
initDateRangePicker();
window.addEventListener('unhandledrejection',e=>{setLoading(false);if(e&&e.reason&&e.reason.message)alertBox(e.reason.message)});
window.addEventListener('error',()=>setLoading(false));
setLoading(false);
loadContext();
})();
