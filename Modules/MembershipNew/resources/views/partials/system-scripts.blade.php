<script>
(function(){
'use strict';
window.MembershipNew=window.MembershipNew||{};
var MN=window.MembershipNew;
MN.currencyPrecision={{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::currencyPrecision() }};
MN.formatNumber=MN.formatNumber||function(v,d){return Number(v||0).toFixed(d==null?MN.currencyPrecision:d);};
function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
function tableFor(box){var id=box.getAttribute('data-table-id');return id?document.getElementById(id):box.closest('.mn-panel')?.querySelector('table');}
function cleanText(cell){return (cell.innerText||cell.textContent||'').replace(/\s+/g,' ').trim();}
function visibleCols(table){return Array.from(table.querySelectorAll('thead th')).map(function(th,i){return {i:i,show:!th.classList.contains('mn-col-hidden'),name:cleanText(th)||('Column '+(i+1))};});}
function visibleRows(table){return Array.from(table.querySelectorAll('tbody tr')).filter(function(tr){return tr.style.display!=='none'&&!tr.classList.contains('mn-report-empty-row');});}
function rowsAsArray(table){var cols=visibleCols(table).filter(function(c){return c.show;});var data=[cols.map(function(c){return c.name;})];visibleRows(table).forEach(function(tr){var cells=Array.from(tr.children);data.push(cols.map(function(c){return cleanText(cells[c.i]||'');}));});return data;}
function download(name,content,type){var blob=new Blob([content],{type:type});var a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;document.body.appendChild(a);a.click();setTimeout(function(){URL.revokeObjectURL(a.href);a.remove();},100);}
function safeName(v){return (v||'membership-report').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');}
function csvCell(v){v=String(v==null?'':v);return '"'+v.replace(/"/g,'""')+'"';}
function exportCsv(table,title){var csv='\ufeff'+rowsAsArray(table).map(function(r){return r.map(csvCell).join(',');}).join('\r\n');download(safeName(title)+'.csv',csv,'text/csv;charset=utf-8');}
function exportExcel(table,title){var rows=rowsAsArray(table);var html='<html><head><meta charset="utf-8"></head><body><table border="1">'+rows.map(function(r,ri){return '<tr>'+r.map(function(v){var tag=ri===0?'th':'td';return '<'+tag+'>'+esc(v)+'</'+tag+'>';}).join('')+'</tr>';}).join('')+'</table></body></html>';download(safeName(title)+'.xls','\ufeff'+html,'application/vnd.ms-excel;charset=utf-8');}
function printTable(table,title,pdfMode){var cols=visibleCols(table).filter(function(c){return c.show;});var rows=visibleRows(table);var html='<table><thead><tr>'+cols.map(function(c){return '<th>'+esc(c.name)+'</th>';}).join('')+'</tr></thead><tbody>'+rows.map(function(tr){var cells=Array.from(tr.children);return '<tr>'+cols.map(function(c){return '<td>'+esc(cleanText(cells[c.i]||''))+'</td>';}).join('')+'</tr>';}).join('')+'</tbody></table>';var w=window.open('','_blank','width=1200,height=800');if(!w)return;w.document.write('<!doctype html><html><head><title>'+esc(title)+'</title><style>body{font-family:Arial,sans-serif;padding:24px;color:#111827}h1{font-size:20px;margin:0 0 6px}p{font-size:11px;color:#64748b;margin:0 0 16px}table{width:100%;border-collapse:collapse;font-size:10px}th,td{border:1px solid #d1d5db;padding:6px;text-align:left;vertical-align:top}th{background:#f3f4f6}@page{size:landscape;margin:10mm}</style></head><body><h1>'+esc(title)+'</h1><p>Generated '+esc(new Date().toLocaleString())+(pdfMode?' — choose “Save as PDF” in the print destination.':'')+'</p>'+html+'<script>window.onload=function(){window.print();}<\/script></body></html>');w.document.close();}
function setQuery(name,value){var u=new URL(window.location.href);if(value)u.searchParams.set(name,value);else u.searchParams.delete(name);['page','pending_page','payout_page'].forEach(function(k){u.searchParams.delete(k);});window.location.href=u.toString();}
function shareEmail(title){var subject=encodeURIComponent(title);var body=encodeURIComponent(title+'\n'+window.location.href);window.location.href='mailto:?subject='+subject+'&body='+body;}
function shareWhatsApp(title){var text=encodeURIComponent(title+'\n'+window.location.href);window.open('https://wa.me/?text='+text,'_blank','noopener');}
function initReportToolbar(box){if(box.dataset.ready==='1')return;box.dataset.ready='1';var table=tableFor(box);if(!table)return;var title=box.getAttribute('data-report-title')||document.title||'Membership Report';var search=box.querySelector('[data-mn-report-search]');var count=box.querySelector('[data-mn-report-count]');var columnPanel=box.querySelector('[data-mn-column-panel]');var colButton=box.querySelector('[data-mn-columns]');
if(search){search.addEventListener('input',function(){var q=search.value.toLowerCase().trim(),n=0;Array.from(table.querySelectorAll('tbody tr')).forEach(function(tr){var ok=!q||(tr.innerText||'').toLowerCase().indexOf(q)>-1;tr.style.display=ok?'':'none';if(ok)n++;});if(count)count.textContent=n+' visible';});search.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();var named=document.querySelector('form[method=\"GET\"] input[name=\"search\"]');setQuery(named?'search':'q',search.value.trim());}});}
if(columnPanel){visibleCols(table).forEach(function(c){var label=document.createElement('label');var cb=document.createElement('input');cb.type='checkbox';cb.checked=true;cb.addEventListener('change',function(){Array.from(table.rows).forEach(function(row){if(row.cells[c.i])row.cells[c.i].classList.toggle('mn-col-hidden',!cb.checked);});});label.appendChild(cb);label.appendChild(document.createTextNode(c.name));columnPanel.appendChild(label);});}
if(colButton){colButton.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();colButton.closest('.mn-column-menu').classList.toggle('open');});}
document.addEventListener('click',function(e){var m=box.querySelector('.mn-column-menu');if(m&&!m.contains(e.target))m.classList.remove('open');});
box.addEventListener('click',function(e){var b=e.target.closest('[data-mn-action]');if(!b)return;var a=b.getAttribute('data-mn-action');if(a==='csv')exportCsv(table,title);if(a==='excel')exportExcel(table,title);if(a==='print')printTable(table,title,false);if(a==='pdf')printTable(table,title,true);if(a==='email')shareEmail(title);if(a==='whatsapp')shareWhatsApp(title);});
var ps=box.querySelector('[data-mn-page-size]');if(ps){ps.addEventListener('change',function(){setQuery('per_page',ps.value);});}
}
function toolbarHtml(tableId,title){
var perPage=(new URL(window.location.href)).searchParams.get('per_page')||'25';
return '<div class="mn-report-toolbar" data-mn-report-toolbar data-table-id="'+esc(tableId)+'" data-report-title="'+esc(title)+'">'+
'<div class="mn-report-toolbar-left"><div class="mn-report-search-wrap"><i class="fa fa-search"></i><input type="search" data-mn-report-search placeholder="Global Search" aria-label="Global Search"></div>'+
'<label class="mn-report-page-size">Rows per page <select data-mn-page-size>'+[10,25,50,100,200].map(function(n){return '<option value="'+n+'"'+(String(n)===String(perPage)?' selected':'')+'>'+n+'</option>';}).join('')+'</select></label><span class="mn-report-count" data-mn-report-count></span></div>'+
'<div class="mn-report-toolbar-right"><button type="button" class="mn-btn mn-btn-success mn-btn-sm" data-mn-action="excel"><i class="fa fa-file-excel-o"></i> Excel</button>'+
'<button type="button" class="mn-btn mn-btn-primary mn-btn-sm" data-mn-action="csv"><i class="fa fa-file-text-o"></i> CSV</button>'+
'<div class="mn-column-menu"><button type="button" class="mn-btn mn-btn-purple mn-btn-sm" data-mn-columns><i class="fa fa-columns"></i> Columns</button><div class="mn-column-menu-panel" data-mn-column-panel></div></div>'+
'<button type="button" class="mn-btn mn-btn-info mn-btn-sm" data-mn-action="print"><i class="fa fa-print"></i> Print</button>'+
'<button type="button" class="mn-btn mn-btn-danger mn-btn-sm" data-mn-action="pdf"><i class="fa fa-file-pdf-o"></i> PDF</button>'+
'<button type="button" class="mn-btn mn-btn-warning mn-btn-sm" data-mn-action="email"><i class="fa fa-envelope"></i> Email</button>'+
'<button type="button" class="mn-btn mn-btn-success mn-btn-sm" data-mn-action="whatsapp"><i class="fa fa-whatsapp"></i> WhatsApp</button></div></div>';
}
function ensureToolbar(table,index){
if(table.closest('[data-mn-no-toolbar]'))return;
if(!table.id)table.id='mn-auto-table-'+index;
if(document.querySelector('[data-mn-report-toolbar][data-table-id="'+table.id+'"]'))return;
var host=table.parentElement && table.parentElement.classList.contains('mn-table-wrap')?table.parentElement:table;
var panel=table.closest('.mn-panel');
var heading=panel?panel.querySelector('h2,h3,h4'):null;
var title=(heading&&cleanText(heading))||document.title||'Membership New';
var holder=document.createElement('div');holder.innerHTML=toolbarHtml(table.id,title);var toolbar=holder.firstElementChild;
host.parentNode.insertBefore(toolbar,host);
initReportToolbar(toolbar);
}
function initAutoFilterForm(form){
if(form.dataset.mnAutoFilter==='1')return;form.dataset.mnAutoFilter='1';var timer=null;var url=new URL(window.location.href);if(url.searchParams.get('per_page')&&!form.querySelector('[name=\"per_page\"]')){var keep=document.createElement('input');keep.type='hidden';keep.name='per_page';keep.value=url.searchParams.get('per_page');form.appendChild(keep);}
function submitSoon(delay){clearTimeout(timer);timer=setTimeout(function(){if(form.requestSubmit)form.requestSubmit();else form.submit();},delay||0);}
form.querySelectorAll('select').forEach(function(el){el.addEventListener('change',function(){if(el.name==='date_range'&&el.value==='custom')return;submitSoon(0);});});
form.querySelectorAll('input:not([type=hidden]):not([type=submit]):not([type=button])').forEach(function(el){
var eventName=(el.type==='date'||el.type==='checkbox'||el.type==='radio')?'change':'input';
el.addEventListener(eventName,function(){submitSoon(eventName==='input'?450:0);});
});
}
document.addEventListener('DOMContentLoaded',function(){
document.querySelectorAll('.mn-panel .mn-table').forEach(function(t){if(!t.parentElement.classList.contains('mn-table-wrap')){var w=document.createElement('div');w.className='mn-table-wrap';t.parentNode.insertBefore(w,t);w.appendChild(t);}});
document.querySelectorAll('[data-mn-report-toolbar]').forEach(initReportToolbar);
document.querySelectorAll('.mn-table').forEach(ensureToolbar);
document.querySelectorAll('form.mn-toolbar[method="GET"],form.mn-settings-filter[method="GET"]').forEach(initAutoFilterForm);
});
MN.previewRedemption=MN.previewRedemption||function(){var out=document.getElementById('mn_redemption_result');if(out)out.innerHTML='<div class="mn-business-only-note">Enter the member and invoice values, then submit through the configured redemption workflow.</div>';};
})();
</script>
<script>
/* MEMNEW_025: keep Action dropdowns above short/scrollable tables. */
(function(){
'use strict';
var currentDetails=null;

function clearFloating(details){
    if(!details){return;}
    var menu=details.querySelector('.mn-row-action-menu');
    if(!menu){return;}
    menu.classList.remove('mn-row-action-floating');
    menu.style.removeProperty('left');
    menu.style.removeProperty('top');
    menu.style.removeProperty('right');
    menu.style.removeProperty('bottom');
    menu.style.removeProperty('position');
    menu.style.removeProperty('z-index');
    menu.style.removeProperty('margin');
}

function placeFloating(details){
    if(!details || !details.open){return;}
    var summary=details.querySelector(':scope > summary');
    var menu=details.querySelector(':scope > .mn-row-action-menu');
    if(!summary || !menu){return;}

    menu.classList.add('mn-row-action-floating');
    menu.style.position='fixed';
    menu.style.zIndex='12050';
    menu.style.margin='0';
    menu.style.right='auto';
    menu.style.bottom='auto';

    var anchor=summary.getBoundingClientRect();
    var menuWidth=menu.offsetWidth || 160;
    var menuHeight=menu.offsetHeight || 90;
    var gap=6;
    var edge=8;
    var left=anchor.left;
    var top=anchor.bottom+gap;

    if(left+menuWidth>window.innerWidth-edge){
        left=Math.max(edge,anchor.right-menuWidth);
    }
    if(left<edge){left=edge;}

    if(top+menuHeight>window.innerHeight-edge){
        var above=anchor.top-menuHeight-gap;
        if(above>=edge){
            top=above;
        }else{
            top=Math.max(edge,window.innerHeight-menuHeight-edge);
        }
    }

    menu.style.left=Math.round(left)+'px';
    menu.style.top=Math.round(top)+'px';
}

function closeOther(except){
    document.querySelectorAll('details.mn-row-action[open]').forEach(function(details){
        if(details!==except){
            details.open=false;
            clearFloating(details);
        }
    });
}

// "toggle" does not bubble consistently, so capture it at document level.
document.addEventListener('toggle',function(event){
    var details=event.target;
    if(!details || !details.matches || !details.matches('details.mn-row-action')){return;}
    if(details.open){
        closeOther(details);
        currentDetails=details;
        window.requestAnimationFrame(function(){placeFloating(details);});
    }else{
        clearFloating(details);
        if(currentDetails===details){currentDetails=null;}
    }
},true);

document.addEventListener('click',function(event){
    var actionItem=event.target.closest ? event.target.closest('.mn-row-action-item') : null;
    if(actionItem && currentDetails){
        currentDetails.open=false;
        clearFloating(currentDetails);
        currentDetails=null;
        return;
    }
    var summary=event.target.closest ? event.target.closest('details.mn-row-action > summary') : null;
    if(summary){
        var details=summary.parentElement;
        // Native details toggling happens after click; position on next frame.
        window.requestAnimationFrame(function(){
            if(details.open){
                closeOther(details);
                currentDetails=details;
                placeFloating(details);
            }
        });
        return;
    }
    if(currentDetails && !currentDetails.contains(event.target)){
        currentDetails.open=false;
        clearFloating(currentDetails);
        currentDetails=null;
    }
});

window.addEventListener('resize',function(){if(currentDetails && currentDetails.open){placeFloating(currentDetails);}});
window.addEventListener('scroll',function(){if(currentDetails && currentDetails.open){placeFloating(currentDetails);}},true);
})();
</script>
