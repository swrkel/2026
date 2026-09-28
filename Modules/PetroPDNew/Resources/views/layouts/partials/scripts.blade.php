<script>
(function(){
    'use strict';
    function activateTab(container,name){
        container.querySelectorAll('[data-pdn-tab]').forEach(function(button){
            var selected=button.getAttribute('data-pdn-tab')===name;
            button.classList.toggle('active',selected);
            button.setAttribute('aria-selected',selected?'true':'false');
            button.tabIndex=selected?0:-1;
        });
        var scope=container.parentElement||document;
        scope.querySelectorAll('[data-pdn-panel]').forEach(function(panel){
            var selected=panel.getAttribute('data-pdn-panel')===name;
            panel.classList.toggle('active',selected);
            panel.hidden=!selected;
        });
        try{sessionStorage.setItem('pdnew.activeTab',name);}catch(e){}
    }
    function bindSelectFilter(input){
        var id=input.getAttribute('data-pdn-filter-select'),select=id?document.getElementById(id):null;
        if(!select)return;
        var options=Array.prototype.map.call(select.options,function(option){return {value:option.value,text:option.text,disabled:option.disabled};});
        input.addEventListener('input',function(){
            var term=input.value.trim().toLowerCase(),selected=select.value;select.innerHTML='';
            options.forEach(function(option){
                if(option.value!==''&&option.value!==selected&&term!==''&&option.text.toLowerCase().indexOf(term)===-1)return;
                var created=new Option(option.text,option.value,false,option.value===selected);created.disabled=option.disabled;select.add(created);
            });
            select.value=selected;
        });
    }
    function setColumnVisibility(toggle){
        var table=document.getElementById(toggle.getAttribute('data-pdn-column-toggle')||''),index=parseInt(toggle.getAttribute('data-column-index'),10);
        if(!table||isNaN(index))return;
        table.querySelectorAll('tr').forEach(function(row){if(row.children[index])row.children[index].hidden=!toggle.checked;});
    }
    document.addEventListener('click',function(event){
        var tab=event.target.closest('[data-pdn-tab]');
        if(tab){event.preventDefault();activateTab(tab.closest('.pdn-tabs'),tab.getAttribute('data-pdn-tab'));return;}
        var confirmTarget=event.target.closest('[data-confirm]');
        if(confirmTarget&&!window.confirm(confirmTarget.getAttribute('data-confirm'))){event.preventDefault();event.stopImmediatePropagation();}
    });
    document.addEventListener('change',function(event){var toggle=event.target.closest('[data-pdn-column-toggle]');if(toggle)setColumnVisibility(toggle);});
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.pdn-tabs').forEach(function(tabs){
            var requested=new URLSearchParams(window.location.search).get('tab'),remembered=null;try{remembered=sessionStorage.getItem('pdnew.activeTab');}catch(e){}
            var first=tabs.querySelector('[data-pdn-tab]'),candidate=tabs.querySelector('[data-pdn-tab="'+(requested||remembered||'')+'"]')||first;
            if(candidate)activateTab(tabs,candidate.getAttribute('data-pdn-tab'));
        });
        document.querySelectorAll('[data-pdn-filter-select]').forEach(bindSelectFilter);
        document.querySelectorAll('[data-pdn-column-toggle]').forEach(setColumnVisibility);
        document.querySelectorAll('form[data-prevent-double-submit]').forEach(function(form){
            form.addEventListener('submit',function(){var submit=form.querySelector('[type="submit"]');if(submit){submit.disabled=true;submit.textContent='Processing…';}});
        });

        var shell=document.querySelector('[data-pdn-shell]'),toggle=document.querySelector('[data-pdn-menu-toggle]'),backdrop=document.querySelector('[data-pdn-sidebar-backdrop]');
        function closeMenu(){if(shell)shell.classList.remove('pdn-menu-open');}
        if(toggle&&shell)toggle.addEventListener('click',function(){shell.classList.toggle('pdn-menu-open');});
        if(backdrop)backdrop.addEventListener('click',closeMenu);
        document.querySelectorAll('[data-pdn-sidebar] a').forEach(function(link){link.addEventListener('click',closeMenu);});
        var clock=document.querySelector('[data-pdn-clock]');
        if(clock){setInterval(function(){try{clock.textContent=new Intl.DateTimeFormat('en-GB',{timeZone:'Asia/Colombo',day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}).format(new Date());}catch(e){}},1000);}

        if(window.jQuery&&jQuery.fn&&jQuery.fn.select2){jQuery('.pdn-select:not([multiple])').each(function(){if(!jQuery(this).hasClass('select2-hidden-accessible'))jQuery(this).select2({width:'100%'});});}
    });
})();


(function(){
    'use strict';
    var popover=null;
    function closeActions(){if(popover){popover.remove();popover=null;}}
    function placePopover(button,menu){
        document.body.appendChild(menu);
        var rect=button.getBoundingClientRect(),width=menu.offsetWidth||310,height=menu.offsetHeight||400;
        var left=Math.min(window.innerWidth-width-10,Math.max(10,rect.left));
        var top=rect.bottom+5;
        if(top+height>window.innerHeight-10)top=Math.max(10,rect.top-height-5);
        menu.style.left=left+'px';menu.style.top=top+'px';
    }
    document.addEventListener('click',function(event){
        var actionButton=event.target.closest('[data-pdn-operator-actions]');
        if(actionButton){
            event.preventDefault();event.stopPropagation();
            var id=actionButton.getAttribute('data-pdn-operator-actions');
            var template=document.querySelector('[data-pdn-operator-actions-template="'+id+'"]');
            closeActions();
            if(template){popover=document.createElement('div');popover.className='pdn-operator-action-popover';popover.innerHTML=template.innerHTML;placePopover(actionButton,popover);}
            return;
        }
        if(popover&&!event.target.closest('.pdn-operator-action-popover'))closeActions();

        var modalLink=event.target.closest('[data-pdn-operator-modal]');
        if(modalLink){
            event.preventDefault();closeActions();
            var host=document.getElementById('pdn-operator-modal-host');
            if(!host)return;
            host.innerHTML='<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center" style="padding:40px"><i class="fa fa-spinner fa-spin fa-2x"></i><br><br>Loading...</div></div></div>';
            if(window.jQuery&&jQuery.fn.modal)jQuery(host).modal({backdrop:true,keyboard:true,show:true});
            fetch(modalLink.href,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}})
                .then(function(response){if(!response.ok)throw new Error('Unable to open the requested PD Operator action.');return response.text();})
                .then(function(html){host.innerHTML=html;if(window.jQuery&&jQuery.fn.select2)jQuery(host).find('.pdn-select').select2({width:'100%',dropdownParent:jQuery(host)});})
                .catch(function(error){host.innerHTML='<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">'+error.message+'</div></div></div></div>';});
        }
    });
    window.addEventListener('resize',closeActions);window.addEventListener('scroll',closeActions,true);
})();

</script>
<script>
(function(){
    'use strict';
    function visibleCells(row){return Array.prototype.filter.call(row.cells||[],function(cell){return !cell.hidden&&!cell.classList.contains('notexport');});}
    function tableRows(table){return Array.prototype.filter.call(table.querySelectorAll('tr'),function(row){return row.closest('table')===table;});}
    function cellText(cell){var value=cell.getAttribute('data-export-number');return value!==null?value:cell.textContent.replace(/\s+/g,' ').trim();}
    function csvEscape(value){value=String(value==null?'':value);return /[",\n]/.test(value)?'"'+value.replace(/"/g,'""')+'"':value;}
    function download(content,name,type){var blob=new Blob([content],{type:type}),url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download=name;document.body.appendChild(link);link.click();link.remove();setTimeout(function(){URL.revokeObjectURL(url);},1000);}
    function csv(table){return tableRows(table).map(function(row){return visibleCells(row).map(function(cell){return csvEscape(cellText(cell));}).join(',');}).join('\r\n');}
    function excel(table){var clone=table.cloneNode(true);clone.querySelectorAll('[hidden],.notexport,template,button').forEach(function(node){node.remove();});return '<html><head><meta charset="utf-8"></head><body>'+clone.outerHTML+'</body></html>';}
    document.addEventListener('click',function(event){
        var exportButton=event.target.closest('[data-pdn-daily-export]');
        if(exportButton){
            event.preventDefault();
            var table=document.getElementById(exportButton.getAttribute('data-table-id')),format=exportButton.getAttribute('data-pdn-daily-export');
            if(!table)return;
            var stamp=new Date().toISOString().slice(0,10);
            if(format==='excel')download(excel(table),'daily-pump-status-'+stamp+'.xls','application/vnd.ms-excel;charset=utf-8');
            else download('\ufeff'+csv(table),'daily-pump-status-'+stamp+'.csv','text/csv;charset=utf-8');
            return;
        }
        var printButton=event.target.closest('[data-pdn-daily-print]');
        if(printButton){
            event.preventDefault();
            var source=document.getElementById(printButton.getAttribute('data-pdn-daily-print'));
            if(!source)return;
            var popup=window.open('','_blank','width=1200,height=800');
            if(!popup)return;
            popup.document.write('<!doctype html><html><head><title>Daily Pump Status</title><style>body{font-family:Arial,sans-serif;padding:18px}h2{text-align:center}table{width:100%;border-collapse:collapse;font-size:11px}th,td{border:1px solid #bbb;padding:6px;text-align:left}.amount{text-align:right}.notexport,button,template{display:none!important}</style></head><body><h2>Daily Pump Status</h2>'+source.outerHTML+'</body></html>');
            popup.document.close();popup.focus();setTimeout(function(){popup.print();popup.close();},250);
        }
    });
    document.addEventListener('submit',function(event){
        var form=event.target.closest('#pdn-operator-modal-host form[data-prevent-double-submit]');
        if(!form)return;
        var submit=form.querySelector('[type="submit"]');
        if(submit&&!submit.disabled){submit.disabled=true;submit.setAttribute('data-original-label',submit.textContent);submit.textContent='Processing…';}
    });
})();
</script>
