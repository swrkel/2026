(function(){
'use strict';

document.addEventListener('click',function(e){
    var el=e.target.closest('[data-confirm]');
    if(el&&!window.confirm(el.getAttribute('data-confirm')))e.preventDefault();
});

var preset=document.querySelector('.audit-date-preset');
function toggleDates(){
    if(!preset)return;
    var custom=preset.value==='custom';
    var row=preset.closest('.audit-main-filter-row');
    if(row)row.classList.toggle('audit-custom-active',custom);
    document.querySelectorAll('.audit-filter-date-wrap').forEach(function(w){
        w.style.display=custom?'block':'none';
    });
    document.querySelectorAll('.audit-custom-date').forEach(function(x){
        x.style.display=custom?'block':'none';
    });
}
if(preset){preset.addEventListener('change',toggleDates);toggleDates();}

var search=document.querySelector('.audit-table-search');
if(search){
    search.addEventListener('input',function(){
        var q=this.value.toLowerCase();
        document.querySelectorAll('.audit-report-table tbody tr').forEach(function(tr){
            tr.style.display=tr.innerText.toLowerCase().indexOf(q)>=0?'':'none';
        });
    });
}

var col=document.querySelector('.audit-columns');
if(col){
    col.addEventListener('click',function(){
        var table=document.querySelector('.audit-report-table');
        if(!table)return;
        var heads=Array.prototype.slice.call(table.querySelectorAll('thead th'));
        var menu=document.createElement('div');
        menu.className='audit-card';
        menu.style.position='fixed';menu.style.right='20px';menu.style.top='90px';menu.style.zIndex='9999';
        heads.forEach(function(h,i){
            var l=document.createElement('label');l.style.display='block';l.style.padding='4px';
            var c=document.createElement('input');c.type='checkbox';c.checked=true;
            c.addEventListener('change',function(){
                table.querySelectorAll('tr').forEach(function(r){if(r.children[i])r.children[i].style.display=c.checked?'':'none';});
            });
            l.appendChild(c);l.appendChild(document.createTextNode(' '+h.innerText));menu.appendChild(l);
        });
        var close=document.createElement('button');close.type='button';close.className='audit-btn small';close.innerText='Close';close.onclick=function(){menu.remove();};menu.appendChild(close);document.body.appendChild(menu);
    });
}
})();

// v1.0.14 Rules page filtering + toggle label
(function(){
    'use strict';
    var search = document.querySelector('.audit-rule-search');
    var moduleFilter = document.querySelector('.audit-rule-module-filter');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.audit-rule-row'));
    var empty = document.querySelector('.audit-rule-empty');

    function filterRules(){
        if(!rows.length)return;
        var q = search ? search.value.trim().toLowerCase() : '';
        var moduleName = moduleFilter ? moduleFilter.value.trim().toLowerCase() : '';
        var visible = 0;
        rows.forEach(function(row){
            var matchesSearch = !q || (row.getAttribute('data-search') || '').indexOf(q) !== -1;
            var matchesModule = !moduleName || (row.getAttribute('data-module') || '') === moduleName;
            var show = matchesSearch && matchesModule;
            row.style.display = show ? '' : 'none';
            if(show)visible++;
        });
        if(empty)empty.style.display = visible ? 'none' : '';
    }

    if(search)search.addEventListener('input',filterRules);
    if(moduleFilter)moduleFilter.addEventListener('change',filterRules);

    document.addEventListener('change',function(e){
        var input = e.target.closest('.audit-rule-toggle');
        if(!input)return;
        var label = input.closest('.audit-switch');
        var text = label ? label.querySelector('.audit-switch-text') : null;
        if(text)text.textContent = input.checked ? 'Enabled' : 'Disabled';
    });
})();

// v1.0.15 Schedules page frequency guidance
(function(){
    'use strict';
    var frequency = document.querySelector('.audit-schedule-frequency');
    var timeWrap = document.querySelector('.audit-schedule-time-wrap');
    var help = document.querySelector('[data-schedule-help]');
    if(!frequency)return;

    function refreshScheduleHelp(){
        var value = frequency.value;
        if(timeWrap)timeWrap.style.opacity = value === 'hourly' ? '.55' : '1';
        if(!help)return;
        if(value === 'hourly') help.textContent = 'Hourly schedules run when the hourly Audit scheduler check is due; preferred run time is not used.';
        else if(value === 'daily') help.textContent = 'Runs once per day after the preferred run time is reached.';
        else if(value === 'weekly') help.textContent = 'Runs when at least 7 days have passed since the previous run, after the preferred time.';
        else if(value === 'monthly') help.textContent = 'Runs once in each new month after the preferred time.';
        else help.textContent = '';
    }
    frequency.addEventListener('change', refreshScheduleHelp);
    refreshScheduleHelp();
})();

// v1.0.18 Central multi-tenant scope selectors
(function(){
    'use strict';

    function values(select){
        if(!select)return [];
        return Array.prototype.filter.call(select.options,function(o){return o.selected;}).map(function(o){return o.value;});
    }

    function dispatchChange(select){
        if(!select)return;
        select.dispatchEvent(new Event('change',{bubbles:true}));
    }

    // Multi-selects should behave like checkbox lists: no Ctrl/Cmd key required.
    document.addEventListener('mousedown',function(e){
        var option=e.target;
        if(!option || option.tagName!=='OPTION')return;
        var select=option.parentElement;
        if(!select || !select.classList.contains('audit-multi-select'))return;
        e.preventDefault();
        option.selected=!option.selected;
        select.focus();
        dispatchChange(select);
    });

    document.querySelectorAll('.audit-scope-search').forEach(function(input){
        var targetId=input.getAttribute('data-filter-select');
        var select=targetId ? document.getElementById(targetId) : null;
        if(!select)return;
        input.addEventListener('input',function(){
            var q=(input.value||'').trim().toLowerCase();
            Array.prototype.forEach.call(select.options,function(option){
                var visible=!q || option.text.toLowerCase().indexOf(q)>=0;
                option.hidden=!visible;
            });
        });
    });

    document.querySelectorAll('[data-clear-select]').forEach(function(button){
        button.addEventListener('click',function(){
            var select=document.getElementById(button.getAttribute('data-clear-select'));
            if(!select)return;
            Array.prototype.forEach.call(select.options,function(option){option.selected=false;});
            dispatchChange(select);
        });
    });

    function refreshCounts(root){
        if(!root)return;
        var source=root.querySelector('.audit-source-select');
        var business=root.querySelector('.audit-business-select');
        var location=root.querySelector('.audit-location-select');
        var sourceCount=root.querySelector('[data-source-count]');
        var businessCount=root.querySelector('[data-business-count]');
        var locationCount=root.querySelector('[data-location-count]');
        if(sourceCount)sourceCount.textContent=values(source).length || 'All';
        if(businessCount)businessCount.textContent=values(business).length || 'All';
        if(locationCount)locationCount.textContent=values(location).length || 'All';
    }

    function fillSelect(select,rows,selectedValues){
        if(!select)return;
        var selected={};
        (selectedValues||[]).forEach(function(v){selected[v]=true;});
        select.innerHTML='';
        (rows||[]).forEach(function(row){
            var option=document.createElement('option');
            option.value=row.key;
            option.textContent=row.label;
            option.selected=!!selected[row.key];
            select.appendChild(option);
        });
    }

    var scopeRoot=document.querySelector('.audit-central-scope[data-scope-options-url]');
    if(!scopeRoot)return;

    var endpoint=scopeRoot.getAttribute('data-scope-options-url');
    var sourceSelect=scopeRoot.querySelector('.audit-source-select');
    var businessSelect=scopeRoot.querySelector('.audit-business-select');
    var locationSelect=scopeRoot.querySelector('.audit-location-select');
    var businessHelp=scopeRoot.querySelector('.audit-business-help');
    var locationHelp=scopeRoot.querySelector('.audit-location-help');
    var sourceTimer=null;
    var businessTimer=null;
    var requestSerial=0;

    function setPickerLoading(select,on){
        if(!select)return;
        var picker=select.closest('.audit-scope-picker');
        if(picker)picker.classList.toggle('is-loading',!!on);
    }

    function buildUrl(sourceValues,businessValues){
        var params=new URLSearchParams();
        sourceValues.forEach(function(v){params.append('source_keys[]',v);});
        (businessValues||[]).forEach(function(v){params.append('business_keys[]',v);});
        return endpoint+(endpoint.indexOf('?')>=0?'&':'?')+params.toString();
    }

    function fetchOptions(sourceValues,businessValues,mode){
        if(!sourceValues.length){
            fillSelect(businessSelect,[],[]);
            fillSelect(locationSelect,[],[]);
            if(businessSelect)businessSelect.disabled=true;
            if(locationSelect)locationSelect.disabled=true;
            if(businessHelp)businessHelp.textContent='Select source(s) first';
            if(locationHelp)locationHelp.textContent='Select source(s) first';
            refreshCounts(scopeRoot);
            return;
        }

        var serial=++requestSerial;
        if(mode==='source')setPickerLoading(businessSelect,true);
        setPickerLoading(locationSelect,true);
        fetch(buildUrl(sourceValues,businessValues),{
            headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
            credentials:'same-origin'
        }).then(function(response){
            if(!response.ok)throw new Error('Unable to load scope options ('+response.status+').');
            return response.json();
        }).then(function(data){
            if(serial!==requestSerial)return;
            if(mode==='source'){
                fillSelect(businessSelect,data.businesses||[],[]);
                if(businessSelect)businessSelect.disabled=false;
            }
            fillSelect(locationSelect,data.locations||[],[]);
            if(locationSelect)locationSelect.disabled=false;
            if(businessHelp)businessHelp.textContent='Blank = all businesses in selected sources';
            if(locationHelp)locationHelp.textContent='Blank = all locations in selected businesses/sources';
            refreshCounts(scopeRoot);
        }).catch(function(error){
            if(serial!==requestSerial)return;
            if(businessHelp && mode==='source')businessHelp.textContent=error.message;
            if(locationHelp)locationHelp.textContent=error.message;
        }).finally(function(){
            if(serial!==requestSerial)return;
            setPickerLoading(businessSelect,false);
            setPickerLoading(locationSelect,false);
        });
    }

    if(sourceSelect){
        sourceSelect.addEventListener('change',function(){
            clearTimeout(sourceTimer);
            if(businessSelect){Array.prototype.forEach.call(businessSelect.options,function(o){o.selected=false;});}
            if(locationSelect){Array.prototype.forEach.call(locationSelect.options,function(o){o.selected=false;});}
            refreshCounts(scopeRoot);
            sourceTimer=setTimeout(function(){fetchOptions(values(sourceSelect),[],'source');},220);
        });
    }

    if(businessSelect){
        businessSelect.addEventListener('change',function(){
            clearTimeout(businessTimer);
            if(locationSelect){Array.prototype.forEach.call(locationSelect.options,function(o){o.selected=false;});}
            refreshCounts(scopeRoot);
            businessTimer=setTimeout(function(){fetchOptions(values(sourceSelect),values(businessSelect),'business');},220);
        });
    }

    if(locationSelect)locationSelect.addEventListener('change',function(){refreshCounts(scopeRoot);});
    refreshCounts(scopeRoot);
})();
