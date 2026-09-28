(function(){
 var reportCache=new Map();
 var reportRequests=new Map();
 var prefetchStarted=false;

 function reportLinkKey(link){
  var u=new URL(link.href,window.location.origin);
  u.searchParams.delete('partial');
  return u.pathname+'?'+u.searchParams.toString();
 }

 function reportPartialUrl(link){
  var u=new URL(link.href,window.location.origin);
  u.searchParams.set('partial','1');
  return u.toString();
 }

 function reportContent(){return document.getElementById('rcm-report-tab-content');}

 function activateReportTab(link,historyMode){
  document.querySelectorAll('.rcm-report-tabs .nav-tabs > li').forEach(function(li){
   li.classList.remove('active');
   var a=li.querySelector('a[data-rcm-report-tab]');
   if(a)a.setAttribute('aria-selected','false');
  });
  var li=link.closest('li');
  if(li)li.classList.add('active');
  link.setAttribute('aria-selected','true');

  if(historyMode!==false){
   var cleanUrl=new URL(link.href,window.location.origin);
   if(historyMode==='replace')window.history.replaceState({rcmReportTab:true},'',cleanUrl.toString());
   else window.history.pushState({rcmReportTab:true},'',cleanUrl.toString());
  }
 }

 function loadingMarkup(link){
  var label=(link.textContent||'Report').trim();
  return '<div class="rcm-card rcm-report-instant-placeholder" aria-busy="true">'
   +'<div class="rcm-report-placeholder-title">'+escapeHtml(label)+'</div>'
   +'<div class="rcm-report-placeholder-note">Preparing report data…</div>'
   +'<div class="rcm-report-placeholder-lines"><i></i><i></i><i></i></div>'
   +'</div>';
 }

 function escapeHtml(value){
  return String(value).replace(/[&<>'"]/g,function(ch){
   return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch];
  });
 }

 function fetchReport(link){
  var key=reportLinkKey(link);
  if(reportCache.has(key))return Promise.resolve(reportCache.get(key));
  if(reportRequests.has(key))return reportRequests.get(key);

  var p=fetch(reportPartialUrl(link),{
   headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'},
   credentials:'same-origin'
  }).then(function(response){
   if(!response.ok)throw new Error('Report request failed');
   return response.text();
  }).then(function(html){
   reportCache.set(key,html);
   reportRequests.delete(key);
   return html;
  }).catch(function(error){
   reportRequests.delete(key);
   throw error;
  });

  reportRequests.set(key,p);
  return p;
 }

 function prefetchReport(link){
  if(!link||!link.href)return;
  fetchReport(link).catch(function(){/* normal click will retry/fallback */});
 }

 function prefetchRemainingReports(){
  if(prefetchStarted)return;
  prefetchStarted=true;
  var links=[].slice.call(document.querySelectorAll('.rcm-report-tabs a[data-rcm-report-tab]'));
  var current=links.find(function(a){return a.getAttribute('aria-selected')==='true';});
  links=links.filter(function(a){return a!==current&&!reportCache.has(reportLinkKey(a));});
  var cursor=0,active=0,maxConcurrent=2;

  function pump(){
   while(active<maxConcurrent&&cursor<links.length){
    var link=links[cursor++];
    active++;
    fetchReport(link).catch(function(){}).finally(function(){active--;pump();});
   }
  }
  pump();
 }

 function initRiceMillSelect2(root){
  if(!(window.jQuery&&jQuery.fn&&jQuery.fn.select2))return;
  var scope=root||document;
  var nodes=[];
  if(scope.matches&&scope.matches('.rcm-searchable'))nodes.push(scope);
  nodes=nodes.concat([].slice.call(scope.querySelectorAll?scope.querySelectorAll('.rcm-searchable'):[]));
  nodes.forEach(function(node){
   var el=jQuery(node);
   if(el.hasClass('select2-hidden-accessible'))return;
   var modal=el.closest('.rcm-modal');
   // Hidden Settings modals can be numerous. Initialising Select2 in every
   // hidden modal made page load scale with all master rows. Initialise it only
   // when the user actually opens that modal.
   if(modal.length&&!modal.hasClass('is-open'))return;
   var parent=modal;
   if(!parent.length)parent=el.closest('.rcm-shell');
   var options={width:'100%'};
   if(node.multiple){
    options.closeOnSelect=false;
    options.allowClear=true;
    options.placeholder=node.getAttribute('data-placeholder')||'Select one or more';
   }
   if(parent.length)options.dropdownParent=parent;
   el.select2(options);
  });
 }

 function initPaddyVarietyProductFields(root){
  var scope=root||document;
  var nodes=[];
  if(scope.matches&&scope.matches('.rcm-paddy-variety-product-select'))nodes.push(scope);
  nodes=nodes.concat([].slice.call(scope.querySelectorAll?scope.querySelectorAll('.rcm-paddy-variety-product-select'):[]));

  nodes.forEach(function(select){
   function sync(){
    var option=select.options[select.selectedIndex]||null;
    var code=option?String(option.getAttribute('data-code')||''):'';
    var form=select.closest('form')||select.parentNode;
    var codeInput=form?form.querySelector('[data-rcm-paddy-variety-code]'):null;
    var prefixInput=form?form.querySelector('[data-rcm-paddy-variety-prefix]'):null;
    if(codeInput)codeInput.value=code;
    if(prefixInput)prefixInput.value='PD-'+(code||'{VARIETY CODE}')+'-';
   }

   if(select.dataset.rcmPaddyProductBound!=='1'){
    select.dataset.rcmPaddyProductBound='1';
    select.addEventListener('change',sync);
    if(window.jQuery){
     jQuery(select).off('change.rcmPaddyVarietyProduct').on('change.rcmPaddyVarietyProduct',sync);
    }
   }
   sync();
  });
 }

 function initRiceProductMasterFields(root){
  var scope=root||document;
  var nodes=[];
  if(scope.matches&&scope.matches('.rcm-rice-product-select'))nodes.push(scope);
  nodes=nodes.concat([].slice.call(scope.querySelectorAll?scope.querySelectorAll('.rcm-rice-product-select'):[]));

  nodes.forEach(function(select){
   function sync(){
    var option=select.options[select.selectedIndex]||null;
    var code=option?String(option.getAttribute('data-code')||''):'';
    var form=select.closest('form')||select.parentNode;
    var codeInput=form?form.querySelector('[data-rcm-rice-product-code]'):null;
    if(codeInput)codeInput.value=code;
   }

   if(select.dataset.rcmRiceProductBound!=='1'){
    select.dataset.rcmRiceProductBound='1';
    select.addEventListener('change',sync);
    if(window.jQuery){
     jQuery(select).off('change.rcmRiceProductMaster').on('change.rcmRiceProductMaster',sync);
    }
   }
   sync();
  });
 }

 function initPackagingMaterialProductSelector(root){
  var scope=root||document;
  var forms=[];
  if(scope.matches&&scope.matches('[data-rcm-product-master-selector]'))forms.push(scope);
  forms=forms.concat([].slice.call(scope.querySelectorAll?scope.querySelectorAll('[data-rcm-product-master-selector]'):[]));

  forms.forEach(function(form){
   if(form.dataset.rcmProductMasterSelectorBound==='1')return;
   var category=form.querySelector('[data-rcm-master-category]');
   var subCategory=form.querySelector('[data-rcm-master-subcategory]');
   var product=form.querySelector('[data-rcm-master-products]');
   var prefix=String(form.getAttribute('data-selector-prefix')||'');
   var dataNode=document.querySelector('[data-rcm-master-selector-data][data-selector-prefix="'+prefix.replace(/"/g,'\\"')+'"]');
   if(!category||!subCategory||!product||!dataNode)return;

   var data={subcategories:[],products:[]};
   try{data=JSON.parse(dataNode.textContent||'{}')||data;}catch(e){}
   var subcategories=Array.isArray(data.subcategories)?data.subcategories:[];
   var products=Array.isArray(data.products)?data.products:[];

   function select2Refresh(select){
    if(window.jQuery&&jQuery.fn&&jQuery.fn.select2&&jQuery(select).hasClass('select2-hidden-accessible')){
     jQuery(select).trigger('change.select2');
    }
   }

   function replaceOptions(select,items,placeholder,labelFn,current){
    var isMultiple=!!select.multiple;
    var wanted=isMultiple
     ?(Array.isArray(current)?current.map(String):[])
     :[String(current||'')];
    var fragment=document.createDocumentFragment();
    if(!isMultiple){
     var blank=document.createElement('option');
     blank.value='';blank.textContent=placeholder;fragment.appendChild(blank);
    }
    items.forEach(function(item){
     var option=document.createElement('option');
     option.value=String(item.id||'');
     option.textContent=labelFn(item);
     if(wanted.indexOf(option.value)!==-1)option.selected=true;
     fragment.appendChild(option);
    });
    select.innerHTML='';select.appendChild(fragment);
    if(!isMultiple){
     var exists=Array.prototype.some.call(select.options,function(option){return String(option.value)===wanted[0];});
     select.value=exists?wanted[0]:'';
    }
    select2Refresh(select);
   }

   function refreshProducts(){
    var categoryId=parseInt(category.value||'0',10)||0;
    var subCategoryId=parseInt(subCategory.value||'0',10)||0;
    var subParent={};
    subcategories.forEach(function(sub){subParent[parseInt(sub.id||0,10)||0]=parseInt(sub.parent_id||0,10)||0;});
    var current=Array.prototype.map.call(product.selectedOptions||[],function(option){return String(option.value||'');});
    var filtered=products.filter(function(item){
     var itemCategory=parseInt(item.category_id||0,10)||0;
     var itemSub=parseInt(item.sub_category_id||0,10)||0;
     if(subCategoryId&&itemSub!==subCategoryId)return false;
     if(categoryId&&itemCategory!==categoryId&&subParent[itemSub]!==categoryId)return false;
     return true;
    });
    replaceOptions(product,filtered,'Select one or more Products',function(item){
     var code=String(item.code||'').trim();
     return (code?code+' - ':'')+String(item.name||'');
    },current);
   }

   function refreshSubCategories(){
    var categoryId=parseInt(category.value||'0',10)||0;
    var current=subCategory.value;
    var filtered=subcategories.filter(function(item){
     return !categoryId||(parseInt(item.parent_id||0,10)||0)===categoryId;
    });
    replaceOptions(subCategory,filtered,'All Product Sub Categories',function(item){
     var parent=String(item.parent_name||'').trim();
     return (parent?parent+' > ':'')+String(item.name||'');
    },current);
    refreshProducts();
   }

   form.dataset.rcmProductMasterSelectorBound='1';
   category.addEventListener('change',refreshSubCategories);
   subCategory.addEventListener('change',refreshProducts);
   if(window.jQuery){
    var ns='.rcmProductMasterSelector'+prefix.replace(/[^a-z0-9]/gi,'');
    jQuery(category).off('change'+ns).on('change'+ns,refreshSubCategories);
    jQuery(subCategory).off('change'+ns).on('change'+ns,refreshProducts);
   }
   refreshSubCategories();
  });
 }


 function initOutputTypeCheckboxSelector(root){
  var scope=root||document;
  var forms=[];
  if(scope.matches&&scope.matches('[data-rcm-output-checkbox-selector]'))forms.push(scope);
  forms=forms.concat([].slice.call(scope.querySelectorAll?scope.querySelectorAll('[data-rcm-output-checkbox-selector]'):[]));

  forms.forEach(function(form){
   if(form.dataset.rcmOutputCheckboxBound==='1')return;
   var category=form.querySelector('[data-rcm-output-category-filter]');
   var subCategory=form.querySelector('[data-rcm-output-subcategory-filter]');
   var search=form.querySelector('[data-rcm-output-product-search]');
   var items=qsa('[data-rcm-output-product-item]',form);
   var selectedCount=form.querySelector('[data-rcm-output-selected-count]');
   var empty=form.querySelector('[data-rcm-output-filter-empty]');
   var selectVisible=form.querySelector('[data-rcm-output-select-visible]');
   var clearVisible=form.querySelector('[data-rcm-output-clear-visible]');
   if(!category||!subCategory)return;

   var allSubOptions=Array.prototype.map.call(subCategory.options,function(option){return option.cloneNode(true);});

   function select2Refresh(select){
    if(window.jQuery&&jQuery.fn&&jQuery.fn.select2&&jQuery(select).hasClass('select2-hidden-accessible')){
     jQuery(select).trigger('change.select2');
    }
   }

   function selectedValues(select){
    return Array.prototype.filter.call(select.options,function(option){return option.selected;})
     .map(function(option){return String(option.value||'');})
     .filter(function(value){return value!=='';});
   }

   function selectedCategoryIds(){return selectedValues(category);}
   function selectedSubCategoryIds(){return selectedValues(subCategory);}

   function updateSelectedCount(){
    if(!selectedCount)return;
    var count=0;
    items.forEach(function(item){
     var checkbox=item.querySelector('input[type="checkbox"]');
     if(checkbox&&checkbox.checked)count++;
    });
    selectedCount.textContent=count+' selected';
   }

   function refreshItems(){
    var subCategoryIds=selectedSubCategoryIds();
    var query=String(search&&search.value||'').trim().toLowerCase();
    var visibleCount=0;

    items.forEach(function(item){
     var itemSubCategoryId=String(item.getAttribute('data-sub-category-id')||'');
     var matchesSub=subCategoryIds.length>0&&subCategoryIds.indexOf(itemSubCategoryId)!==-1;
     var haystack=String(item.getAttribute('data-search')||item.textContent||'').toLowerCase();
     var matchesSearch=!query||haystack.indexOf(query)!==-1;
     var visible=matchesSub&&matchesSearch;
     item.hidden=!visible;

     // A Product outside the compulsory selected Sub Categories must not remain
     // mapped invisibly when the filters are changed.
     if(!matchesSub){
      var checkbox=item.querySelector('input[type="checkbox"]');
      if(checkbox)checkbox.checked=false;
     }
     if(visible)visibleCount++;
    });

    if(empty){
     if(!subCategoryIds.length){
      empty.textContent='Select at least one Product Sub Category to show linked Products.';
      empty.hidden=false;
     }else{
      empty.textContent='No Products are linked to the selected Product Sub Categories.';
      empty.hidden=visibleCount!==0||items.length===0;
     }
    }
    updateSelectedCount();
   }

   function refreshSubCategories(){
    var categoryIds=selectedCategoryIds();
    var previous=selectedSubCategoryIds();
    var fragment=document.createDocumentFragment();

    allSubOptions.forEach(function(original){
     var topId=String(original.getAttribute('data-top-category-id')||'');
     if(!categoryIds.length||categoryIds.indexOf(topId)!==-1){
      var option=original.cloneNode(true);
      option.selected=previous.indexOf(String(option.value||''))!==-1;
      fragment.appendChild(option);
     }
    });

    subCategory.innerHTML='';
    subCategory.appendChild(fragment);
    select2Refresh(subCategory);
    refreshItems();
   }

   function setVisibleChecked(checked){
    items.forEach(function(item){
     if(item.hidden)return;
     var checkbox=item.querySelector('input[type="checkbox"]');
     if(checkbox&&!checkbox.disabled)checkbox.checked=checked;
    });
    updateSelectedCount();
   }

   form.dataset.rcmOutputCheckboxBound='1';
   category.addEventListener('change',refreshSubCategories);
   subCategory.addEventListener('change',refreshItems);
   if(search)search.addEventListener('input',refreshItems);
   items.forEach(function(item){
    var checkbox=item.querySelector('input[type="checkbox"]');
    if(checkbox)checkbox.addEventListener('change',updateSelectedCount);
   });
   if(selectVisible)selectVisible.addEventListener('click',function(){setVisibleChecked(true);});
   if(clearVisible)clearVisible.addEventListener('click',function(){setVisibleChecked(false);});
   if(window.jQuery){
    jQuery(category).off('change.rcmOutputCheckbox').on('change.rcmOutputCheckbox',refreshSubCategories);
    jQuery(subCategory).off('change.rcmOutputCheckbox').on('change.rcmOutputCheckbox',refreshItems);
   }
   refreshSubCategories();
  });
 }


 function initOperationalPaymentSections(root){
  var scope=root||document;
  var sections=[];
  if(scope.matches&&scope.matches('[data-rcm-operational-payment]'))sections.push(scope);
  sections=sections.concat([].slice.call(scope.querySelectorAll?scope.querySelectorAll('[data-rcm-operational-payment]'):[]));

  sections.forEach(function(section){
   if(section.dataset.rcmOperationalPaymentBound==='1')return;
   var method=section.querySelector('.rcm-operational-payment-method');
   var account=section.querySelector('.rcm-operational-payment-account');
   var dataNode=section.querySelector('[data-rcm-payment-map]');
   if(!method||!account||!dataNode)return;

   var map={methods:{},accounts:{},by_location:{}};
   try{map=JSON.parse(dataNode.textContent||'{}')||map;}catch(e){}
   var form=section.closest('form');
   var location=form?form.querySelector('.rcm-location-select'):null;

   function currentAccounts(){
    var methodKey=String(method.value||'');
    if(!methodKey)return {};
    var locationId=location?String(location.value||''):'';
    var byLocation=map.by_location||{};
    if(locationId){
     return (byLocation[locationId]&&byLocation[locationId][methodKey])||{};
    }
    return (map.accounts&&map.accounts[methodKey])||{};
   }

   function refreshAccounts(){
    var oldValue=String(account.getAttribute('data-old-value')||account.value||'');
    var accounts=currentAccounts();
    var ids=Object.keys(accounts);
    var fragment=document.createDocumentFragment();
    var blank=document.createElement('option');
    blank.value='';
    blank.textContent=method.value?'Select Payment Account':'Select Payment Method first';
    fragment.appendChild(blank);

    ids.forEach(function(id){
     var option=document.createElement('option');
     option.value=String(id);
     option.textContent=String(accounts[id]||('Account #'+id));
     if(String(id)===oldValue)option.selected=true;
     fragment.appendChild(option);
    });

    account.innerHTML='';
    account.appendChild(fragment);

    var hasOld=ids.indexOf(oldValue)!==-1;
    if(hasOld){
     account.value=oldValue;
    }else if(ids.length===1){
     account.value=String(ids[0]);
    }else{
     account.value='';
    }
    account.setAttribute('data-old-value',String(account.value||''));

    if(window.jQuery&&jQuery.fn&&jQuery.fn.select2&&jQuery(account).hasClass('select2-hidden-accessible')){
     jQuery(account).trigger('change.select2');
    }
   }

   section.dataset.rcmOperationalPaymentBound='1';
   method.addEventListener('change',function(){
    account.setAttribute('data-old-value','');
    refreshAccounts();
   });
   if(location){
    location.addEventListener('change',refreshAccounts);
    if(window.jQuery){
     jQuery(location).off('change.rcmOperationalPayment').on('change.rcmOperationalPayment',refreshAccounts);
    }
   }
   if(window.jQuery){
    jQuery(method).off('change.rcmOperationalPayment').on('change.rcmOperationalPayment',function(){
     account.setAttribute('data-old-value','');
     refreshAccounts();
    });
   }
   refreshAccounts();
  });
 }

 function initDynamicRiceMillUi(root){
  initRiceMillSelect2(root||document);
  initPaddyVarietyProductFields(root||document);
  initRiceProductMasterFields(root||document);
  initPackagingMaterialProductSelector(root||document);
  initOutputTypeCheckboxSelector(root||document);
  initLocationStoreGroups(root||document);
  initOperationalPaymentSections(root||document);
  initLocationMillGroups(root||document);
  if(window.RiceMill&&typeof window.RiceMill.refreshManagedTables==='function')window.RiceMill.refreshManagedTables(root||document);
 }

 function initLocationStoreGroups(root){
  var scope=root||document;
  var groups=[];
  if(scope.matches&&scope.matches('.rcm-location-store-group'))groups.push(scope);
  groups=groups.concat([].slice.call(scope.querySelectorAll?scope.querySelectorAll('.rcm-location-store-group'):[]));
  groups.forEach(function(group){
   var location=group.querySelector('.rcm-location-select');
   var store=group.querySelector('.rcm-store-select');
   if(!location||!store||store.dataset.rcmStoreBound==='1')return;
   store.dataset.rcmStoreBound='1';

   /* Keep an immutable copy so changing Location never permanently removes Stores. */
   var allOptions=Array.prototype.map.call(store.options,function(option){return option.cloneNode(true);});

   function refreshStores(){
    var locationId=String(location.value||'');
    var locationIsAll=!locationId||locationId==='all';
    var previous=String(store.value||'all');
    var fragment=document.createDocumentFragment();

    allOptions.forEach(function(original,index){
     var option=original.cloneNode(true);
     var optionLocation=String(option.getAttribute('data-location-id')||'');
     if(index===0||locationIsAll||!optionLocation||optionLocation===locationId){
      fragment.appendChild(option);
     }
    });

    store.innerHTML='';
    store.appendChild(fragment);

    var previousStillExists=Array.prototype.some.call(store.options,function(option){
     return String(option.value)===previous;
    });
    if(previousStillExists && previous!==''){
     store.value=previous;
    }else if(group.getAttribute('data-rcm-default-first-store')==='1'){
     var firstStore=Array.prototype.find.call(store.options,function(option){return String(option.value||'')!==''&&String(option.value||'')!=='all';});
     store.value=firstStore?String(firstStore.value):(store.querySelector('option[value="all"]')?'all':'');
    }else{
     store.value=store.querySelector('option[value="all"]')?'all':'';
    }

    if(window.jQuery&&jQuery.fn&&jQuery.fn.select2){
     jQuery(store).trigger('change.select2');
    }
   }

   location.addEventListener('change',refreshStores);
   refreshStores();
  });
 }


 function initLocationMillGroups(root){
  var scope=root||document;
  var groups=[];
  if(scope.matches&&scope.matches('.rcm-location-mill-group'))groups.push(scope);
  groups=groups.concat([].slice.call(scope.querySelectorAll?scope.querySelectorAll('.rcm-location-mill-group'):[]));
  groups.forEach(function(group){
   var location=group.querySelector('.rcm-location-select');
   var mill=group.querySelector('.rcm-mill-select');
   if(!location||!mill||mill.dataset.rcmMillLocationBound==='1')return;
   mill.dataset.rcmMillLocationBound='1';
   var allOptions=Array.prototype.map.call(mill.options,function(option){return option.cloneNode(true);});

   function refreshMills(){
    var locationId=String(location.value||'');
    var previous=String(mill.value||'');
    var fragment=document.createDocumentFragment();
    allOptions.forEach(function(original,index){
     var option=original.cloneNode(true);
     var optionLocation=String(option.getAttribute('data-location-id')||'');
     if(index===0||!locationId||!optionLocation||optionLocation===locationId){fragment.appendChild(option);}
    });
    mill.innerHTML='';
    mill.appendChild(fragment);
    var keep=Array.prototype.some.call(mill.options,function(option){return String(option.value)===previous;});
    mill.value=keep?previous:'';
    if(window.jQuery&&jQuery.fn&&jQuery.fn.select2){jQuery(mill).trigger('change.select2');}
   }
   location.addEventListener('change',refreshMills);
   refreshMills();
  });
 }

 window.RiceMill={
  addRow:function(tpl,target){var t=document.getElementById(tpl),d=document.getElementById(target);if(!t||!d)return;var idx=d.children.length;var html=t.innerHTML.replace(/__INDEX__/g,idx);d.insertAdjacentHTML('beforeend',html);},
  removeRow:function(btn){var row=btn.closest('[data-rcm-row]');if(row)row.remove();},
  printPage:function(){window.print();},
  exportCsv:function(id,name){
   var t=document.getElementById(id); if(!t)return;
   var rows=[].map.call(t.querySelectorAll('tr'),function(r){
    return [].map.call(r.querySelectorAll('th,td'),function(c){
     return '"'+String(c.innerText).replace(/"/g,'""')+'"';
    }).join(',');
   }).join('\n');
   this.download(rows,(name||'report')+'.csv','text/csv;charset=utf-8;');
  },
  exportExcel:function(id,name){
   var t=document.getElementById(id); if(!t)return;
   var html='<html><head><meta charset="utf-8"></head><body>'+t.outerHTML+'</body></html>';
   this.download(html,(name||'report')+'.xls','application/vnd.ms-excel');
  },
  download:function(content,name,type){
   var b=new Blob([content],{type:type}),a=document.createElement('a');
   a.href=URL.createObjectURL(b);a.download=name;document.body.appendChild(a);a.click();
   setTimeout(function(){URL.revokeObjectURL(a.href);a.remove();},100);
  },
  toggleColumn:function(id,idx,show){
   var t=document.getElementById(id);if(!t)return;
   [].forEach.call(t.rows,function(r){if(r.cells[idx])r.cells[idx].style.display=show?'':'none';});
  },
  loadReportTab:function(link,historyMode){
   var content=reportContent();
   if(!content||!link||!link.href){if(link&&link.href)window.location.href=link.href;return;}

   /* Respond on the click itself. Never wait for the server before changing tab. */
   activateReportTab(link,historyMode);
   var key=reportLinkKey(link);

   if(reportCache.has(key)){
    content.classList.remove('is-loading');
    content.innerHTML=reportCache.get(key);
    initDynamicRiceMillUi();
    return;
   }

   content.classList.add('is-loading');
   content.innerHTML=loadingMarkup(link);
   fetchReport(link).then(function(html){
    /* Do not replace another tab if the user moved again while this request ran. */
    if(link.getAttribute('aria-selected')!=='true')return;
    content.innerHTML=html;
    content.classList.remove('is-loading');
    initDynamicRiceMillUi();
   }).catch(function(){
    if(link.getAttribute('aria-selected')==='true')window.location.href=link.href;
   });
  },
  prefetchReport:function(link){prefetchReport(link);},
  initUi:function(root){initDynamicRiceMillUi(root||document);}
 };

 document.addEventListener('DOMContentLoaded',function(){
  initDynamicRiceMillUi();

  /* Cache the server-rendered first tab immediately. */
  var active=document.querySelector('.rcm-report-tabs a[data-rcm-report-tab][aria-selected="true"]');
  var content=reportContent();
  if(active&&content)reportCache.set(reportLinkKey(active),content.innerHTML);

  /* v27: do not fetch every report in the background. Full report datasets can
   * be large; eager prefetch competed with the user's current page request.
   * Hover/focus prefetch below still makes the report the user is about to open fast. */
 });

 document.addEventListener('input',function(e){if(e.target.matches('[data-rcm-filter]')){var q=e.target.value.toLowerCase();document.querySelectorAll('[data-rcm-search-row]').forEach(function(r){r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});}});

 document.addEventListener('pointerover',function(e){
  var link=e.target.closest&&e.target.closest('a[data-rcm-report-tab]');
  if(link)prefetchReport(link);
 });
 document.addEventListener('focusin',function(e){
  var link=e.target.closest&&e.target.closest('a[data-rcm-report-tab]');
  if(link)prefetchReport(link);
 });

 document.addEventListener('click',function(e){
  var link=e.target.closest&&e.target.closest('a[data-rcm-report-tab]');
  if(!link||e.defaultPrevented||e.button!==0||e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;
  e.preventDefault();
  RiceMill.loadReportTab(link,true);
 });

 window.addEventListener('popstate',function(){
  if(!reportContent())return;
  var params=new URL(window.location.href).searchParams;
  var wanted=params.get('tab')||'purchases';
  var link=document.querySelector('.rcm-report-tabs a[data-rcm-report-tab="'+CSS.escape(wanted)+'"]');
  if(link)RiceMill.loadReportTab(link,false);
 });
})();

/* v9 - Rice Mill Settings / Receive Paddy master data tools. */
(function(){
 function qsa(sel,root){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}
 function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}

 function openModal(id){
  var modal=document.getElementById(id); if(!modal)return;
  modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false');
  document.body.classList.add('rcm-modal-open');
  if(window.RiceMill&&typeof window.RiceMill.initUi==='function')window.RiceMill.initUi(modal);
  var focus=modal.querySelector('input:not([readonly]),select,textarea,button'); if(focus)setTimeout(function(){focus.focus();},0);
 }
 function closeModal(modal){
  if(typeof modal==='string')modal=document.getElementById(modal); if(!modal)return;
  modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true');
  if(!document.querySelector('.rcm-modal.is-open'))document.body.classList.remove('rcm-modal-open');
 }

 function tableRows(table){return qsa('tbody tr',table).filter(function(r){return !r.hasAttribute('data-rcm-empty-row');});}
 function exportColumns(table){
  return qsa('thead th',table).map(function(th,i){return {th:th,index:i};}).filter(function(x){return !x.th.hasAttribute('data-rcm-no-export') && x.th.style.display!=='none';});
 }
 function filteredRows(state){
  var term=state.search.toLowerCase();
  return state.rows.filter(function(row){return !term || row.innerText.toLowerCase().indexOf(term)!==-1;});
 }
 function applyTable(state){
  var matched=filteredRows(state), size=state.pageSize==='all'?Math.max(matched.length,1):parseInt(state.pageSize,10)||25;
  var pages=Math.max(1,Math.ceil(matched.length/size));
  if(state.page>pages)state.page=pages; if(state.page<1)state.page=1;
  var start=(state.page-1)*size,end=state.page*size;
  state.rows.forEach(function(row){row.style.display='none';});
  matched.forEach(function(row,i){row.style.display=(i>=start&&i<end)?'':'none';});
  if(state.emptyRow)state.emptyRow.style.display=state.rows.length?'none':'';
  if(state.info){
   var from=matched.length?start+1:0,to=Math.min(end,matched.length);
   state.info.textContent='Showing '+from+' to '+to+' of '+matched.length+' transactions';
  }
  if(state.pageNo)state.pageNo.textContent=state.page+' / '+pages;
  if(state.prev)state.prev.disabled=state.page<=1;
  if(state.next)state.next.disabled=state.page>=pages;
 }
 function tableMatrix(state,allFiltered){
  var cols=exportColumns(state.table), rows=allFiltered?filteredRows(state):state.rows.filter(function(r){return r.style.display!=='none';});
  return {
   headers:cols.map(function(c){return c.th.innerText.trim();}),
   rows:rows.map(function(row){return cols.map(function(c){var cell=row.cells[c.index];return cell?cell.innerText.replace(/\s+/g,' ').trim():'';});})
  };
 }
 function csvText(matrix){
  return [matrix.headers].concat(matrix.rows).map(function(row){return row.map(function(v){return '"'+String(v).replace(/"/g,'""')+'"';}).join(',');}).join('\r\n');
 }
 function download(content,name,type){
  var blob=new Blob([content],{type:type}),url=URL.createObjectURL(blob),a=document.createElement('a');
  a.href=url;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(url);},300);
 }
 function htmlTable(matrix,title){
  var head='<tr>'+matrix.headers.map(function(h){return '<th>'+esc(h)+'</th>';}).join('')+'</tr>';
  var body=matrix.rows.map(function(r){return '<tr>'+r.map(function(v){return '<td>'+esc(v)+'</td>';}).join('')+'</tr>';}).join('');
  return '<h2>'+esc(title)+'</h2><table border="1" cellspacing="0" cellpadding="6" style="width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:11px"><thead>'+head+'</thead><tbody>'+body+'</tbody></table>';
 }
 function printMatrix(matrix,title,w){
  w=w||window.open('','_blank'); if(!w)return; try{w.opener=null;}catch(e){}
  w.document.write('<!doctype html><html><head><title>'+esc(title)+'</title></head><body>'+htmlTable(matrix,title)+'</body></html>');
  w.document.close();w.focus();setTimeout(function(){w.print();},120);
 }
 function pdfMatrix(matrix,title,w){
  if(window.pdfMake&&typeof window.pdfMake.createPdf==='function'){
   if(w&&!w.closed)w.close();
   var widths=matrix.headers.map(function(){return 'auto';});
   var body=[matrix.headers].concat(matrix.rows);
   window.pdfMake.createPdf({pageOrientation:body[0].length>7?'landscape':'portrait',content:[{text:title,style:'header'},{table:{headerRows:1,widths:widths,body:body},layout:'lightHorizontalLines'}],styles:{header:{fontSize:15,bold:true,margin:[0,0,0,10]}},defaultStyle:{fontSize:7}}).download(title.replace(/\s+/g,'-').toLowerCase()+'.pdf');
  }else{
   printMatrix(matrix,title+' (Choose Save as PDF in the print dialog)',w);
  }
 }
 function shareSummary(matrix,title){
  var lines=[title,window.location.href];
  matrix.rows.slice(0,15).forEach(function(r){lines.push(r.join(' | '));});
  if(matrix.rows.length>15)lines.push('... plus '+(matrix.rows.length-15)+' more row(s)');
  return lines.join('\n');
 }
 function columnPanel(state){
  var panel=state.toolbar.querySelector('[data-rcm-column-panel]'); if(!panel)return;
  if(!panel.dataset.ready){
   panel.innerHTML=qsa('thead th',state.table).map(function(th,i){
    return '<label><input type="checkbox" data-rcm-col="'+i+'" checked> '+esc(th.innerText.trim()||('Column '+(i+1)))+'</label>';
   }).join('');
   panel.addEventListener('change',function(e){
    if(!e.target.matches('[data-rcm-col]'))return;
    var idx=parseInt(e.target.getAttribute('data-rcm-col'),10),show=e.target.checked;
    qsa('tr',state.table).forEach(function(row){if(row.cells[idx])row.cells[idx].style.display=show?'':'none';});
   });
   panel.dataset.ready='1';
  }
  panel.hidden=!panel.hidden;
 }
 function matrixFromDomTable(table){
  var cols=qsa('thead th',table).map(function(th,i){return {th:th,index:i};}).filter(function(x){return !x.th.hasAttribute('data-rcm-no-export')&&x.th.style.display!=='none';});
  var rows=qsa('tbody tr',table).filter(function(r){return !r.hasAttribute('data-rcm-empty-row');});
  return {headers:cols.map(function(c){return c.th.innerText.trim();}),rows:rows.map(function(row){return cols.map(function(c){var cell=row.cells[c.index];return cell?cell.innerText.replace(/\s+/g,' ').trim():'';});})};
 }
 function fetchServerMatrix(state){
  if(!state.serverPaged)return Promise.resolve(tableMatrix(state,true));
  var url=new URL(window.location.href);
  var filterForm=state.toolbar.querySelector('[data-rcm-standard-filter-form]');
  if(filterForm){
   new FormData(filterForm).forEach(function(value,key){url.searchParams.set(key,String(value));});
  }
  url.searchParams.set('per_page','all');
  url.searchParams.set('page','1');
  url.searchParams.delete('partial');
  return fetch(url.toString(),{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'},credentials:'same-origin'})
   .then(function(response){if(!response.ok)throw new Error('Unable to load all filtered rows');return response.text();})
   .then(function(html){
    var doc=new DOMParser().parseFromString(html,'text/html');
    var remote=doc.getElementById(state.table.id);
    if(!remote)throw new Error('Export table not found');
    return matrixFromDomTable(remote);
   });
 }
 function setToolBusy(btn,busy){
  if(!btn)return;
  if(busy){btn.dataset.rcmLabel=btn.innerHTML;btn.disabled=true;btn.innerHTML='<i class="fa fa-spinner fa-spin"></i> Working';}
  else{btn.disabled=false;if(btn.dataset.rcmLabel){btn.innerHTML=btn.dataset.rcmLabel;delete btn.dataset.rcmLabel;}}
 }
 function runTableAction(action,matrix,name,title,popup){
  if(action==='csv')download(csvText(matrix),name+'.csv','text/csv;charset=utf-8');
  if(action==='excel')download('<html><head><meta charset="utf-8"></head><body>'+htmlTable(matrix,title)+'</body></html>',name+'.xls','application/vnd.ms-excel');
  if(action==='print')printMatrix(matrix,title,popup);
  if(action==='pdf')pdfMatrix(matrix,title,popup);
  if(action==='whatsapp'){var wa='https://wa.me/?text='+encodeURIComponent(shareSummary(matrix,title));if(popup)popup.location.href=wa;else window.open(wa,'_blank','noopener');}
  if(action==='email')window.location.href='mailto:?subject='+encodeURIComponent(title)+'&body='+encodeURIComponent(shareSummary(matrix,title));
 }
 function initStandardFilterForm(toolbar){
  var form=toolbar.querySelector('[data-rcm-standard-filter-form]'); if(!form||form.dataset.rcmBound==='1')return;
  form.dataset.rcmBound='1';
  var input=form.querySelector('[data-rcm-system-date-range]');
  var rangeKey=form.querySelector('[data-rcm-date-range-key]');
  var fromInput=form.querySelector('[data-rcm-date-from]');
  var toInput=form.querySelector('[data-rcm-date-to]');
  var allBtn=form.querySelector('[data-rcm-date-all]');
  function submitDate(){if(form)form.submit();}
  function setHidden(key,start,end){
   if(rangeKey)rangeKey.value=key||'custom';
   if(fromInput)fromInput.value=start||'';
   if(toInput)toInput.value=end||'';
  }
  if(input&&window.jQuery&&jQuery.fn&&jQuery.fn.daterangepicker&&window.moment){
   var $input=jQuery(input);
   var fyStart=parseInt(input.getAttribute('data-fy-start-month')||'1',10);
   if(!(fyStart>=1&&fyStart<=12))fyStart=1;
   function fiscal(offset){
    var now=moment(),monthIndex=fyStart-1,startYear=now.month()>=monthIndex?now.year():now.year()-1;
    var start=moment({year:startYear+offset,month:monthIndex,day:1}).startOf('day');
    return [start,start.clone().add(1,'year').subtract(1,'day').endOf('day')];
   }
   var currentFy=fiscal(0),lastFy=fiscal(-1);
   var ranges={
    'Today':[moment(),moment()],
    'Yesterday':[moment().subtract(1,'days'),moment().subtract(1,'days')],
    'Last 7 Days':[moment().subtract(6,'days'),moment()],
    'Last 30 Days':[moment().subtract(29,'days'),moment()],
    'This Month':[moment().startOf('month'),moment().endOf('month')],
    'Last Month':[moment().subtract(1,'month').startOf('month'),moment().subtract(1,'month').endOf('month')],
    'This month last year':[moment().subtract(1,'year').startOf('month'),moment().subtract(1,'year').endOf('month')],
    'This Year':[moment().startOf('year'),moment().endOf('year')],
    'Last Year':[moment().subtract(1,'year').startOf('year'),moment().subtract(1,'year').endOf('year')],
    'Current financial year':currentFy,
    'Last financial year':lastFy
   };
   var start=(fromInput&&fromInput.value)?moment(fromInput.value,'YYYY-MM-DD',true):moment();
   var end=(toInput&&toInput.value)?moment(toInput.value,'YYYY-MM-DD',true):start.clone();
   if(!start.isValid())start=moment(); if(!end.isValid())end=start.clone();
   $input.daterangepicker({
    startDate:start,endDate:end,autoUpdateInput:false,alwaysShowCalendars:true,showDropdowns:true,
    locale:{format:'MM/DD/YYYY',separator:' - ',applyLabel:'Apply',cancelLabel:'Clear',customRangeLabel:'Custom Range'},
    ranges:ranges
   });
   $input.on('apply.daterangepicker',function(ev,picker){
    var label=picker.chosenLabel||'Custom Range';
    var keys={'Today':'today','Yesterday':'yesterday','Last 7 Days':'last_7_days','Last 30 Days':'last_30_days','This Month':'this_month','Last Month':'last_month','This month last year':'this_month_last_year','This Year':'this_year','Last Year':'last_year','Current financial year':'this_fy','Last financial year':'last_fy'};
    this.value=picker.startDate.format('MM/DD/YYYY')+' - '+picker.endDate.format('MM/DD/YYYY');
    setHidden(keys[label]||'custom',picker.startDate.format('YYYY-MM-DD'),picker.endDate.format('YYYY-MM-DD'));
    submitDate();
   });
   $input.on('cancel.daterangepicker',function(){this.value='';setHidden('all','','');submitDate();});
  }
  if(allBtn)allBtn.addEventListener('click',function(){if(input)input.value='';setHidden('all','','');submitDate();});
 }
 function initManagedTable(toolbar){
  var table=document.getElementById(toolbar.getAttribute('data-table-id')); if(!table)return;
  initStandardFilterForm(toolbar);
  if(table.__rcmManagedState){
   table.__rcmManagedState.rows=tableRows(table);
   table.__rcmManagedState.emptyRow=table.querySelector('[data-rcm-empty-row]');
   if(!table.__rcmManagedState.serverPaged)applyTable(table.__rcmManagedState);
   return;
  }
  var status=document.querySelector('[data-rcm-table-status="'+table.id+'"]');
  var size=toolbar.querySelector('[data-rcm-page-size]'),serverPaged=toolbar.getAttribute('data-server-paged')==='1';
  var search=toolbar.querySelector('[data-rcm-table-search]');
  var state={table:table,toolbar:toolbar,serverPaged:serverPaged,rows:tableRows(table),emptyRow:table.querySelector('[data-rcm-empty-row]'),search:search?search.value||'':'',page:1,pageSize:size?size.value:'25',info:status&&status.querySelector('[data-rcm-table-info]'),pageNo:status&&status.querySelector('[data-rcm-page-number]'),prev:status&&status.querySelector('[data-rcm-page-prev]'),next:status&&status.querySelector('[data-rcm-page-next]')};
  table.__rcmManagedState=state;
  if(serverPaged){
   var form=toolbar.querySelector('[data-rcm-standard-filter-form]');
   if(size&&form)size.addEventListener('change',function(){form.submit();});
  }
  if(!serverPaged){
   if(search)search.addEventListener('input',function(){state.search=this.value||'';state.page=1;applyTable(state);});
   if(size)size.addEventListener('change',function(){state.pageSize=this.value;state.page=1;applyTable(state);});
   if(state.prev)state.prev.addEventListener('click',function(){state.page--;applyTable(state);});
   if(state.next)state.next.addEventListener('click',function(){state.page++;applyTable(state);});
   applyTable(state);
  }
  toolbar.addEventListener('click',function(e){
   var btn=e.target.closest('[data-rcm-table-action]'); if(!btn)return;
   var action=btn.getAttribute('data-rcm-table-action'),name=toolbar.getAttribute('data-export-name')||table.id,title=name.replace(/[-_]+/g,' ').replace(/\b\w/g,function(c){return c.toUpperCase();});
   if(action==='columns'){columnPanel(state);return;}
   var popup=(action==='print'||action==='pdf'||action==='whatsapp')?window.open('about:blank','_blank'):null;
   if(popup){try{popup.opener=null;}catch(ignore){}}
   setToolBusy(btn,true);
   fetchServerMatrix(state).then(function(matrix){runTableAction(action,matrix,name,title,popup);}).catch(function(){
    if(popup&&!popup.closed)popup.close();
    alert('Unable to prepare the complete filtered data. Please try again.');
   }).finally(function(){setToolBusy(btn,false);});
  });
 }
 function refreshManagedTables(root){
  var scope=root||document;
  var toolbars=[];
  if(scope.matches&&scope.matches('[data-rcm-table-toolbar]'))toolbars.push(scope);
  toolbars=toolbars.concat(qsa('[data-rcm-table-toolbar]',scope));
  toolbars.forEach(initManagedTable);
 }
 if(window.RiceMill)window.RiceMill.refreshManagedTables=refreshManagedTables;

 function syncReceiveVariety(){
  var select=document.getElementById('rcm-receive-variety'); if(!select)return;
  var option=select.options[select.selectedIndex]; if(!option)return;
  var moisture=document.getElementById('rcm-receive-moisture'),foreign=document.getElementById('rcm-receive-foreign'),limit=document.getElementById('rcm-receive-configured-limit'),grade=document.getElementById('rcm-receive-grade'),lot=document.getElementById('rcm-stock-lot-preview');
  var lim=option.getAttribute('data-foreign')||'';
  if(moisture)moisture.value=option.getAttribute('data-moisture')||'';
  if(foreign)foreign.value=lim;
  if(limit)limit.value=lim;
  if(grade)grade.value=option.getAttribute('data-grade')||'';
  if(lot)lot.value=option.getAttribute('data-lot-preview')||'';
 }

 function initRelatedPurchaseAutoload(){
  var purchase=document.getElementById('rcm-related-purchase');
  if(!purchase||purchase.dataset.rcmBound==='1')return;
  purchase.dataset.rcmBound='1';
  var cache={};
  var supplier=document.getElementById('rcm-receive-supplier');
  var variety=document.getElementById('rcm-receive-variety');
  var gross=document.getElementById('rcm-receive-gross');
  var tare=document.getElementById('rcm-receive-tare');
  var location=document.getElementById('rcm-receive-location');
  var store=document.getElementById('rcm-receive-store');
  var help=document.getElementById('rcm-related-purchase-help');
  var weightNote=document.getElementById('rcm-purchase-weight-note');

  function selectValue(el,value){
   if(!el||value===null||value===undefined||value==='')return;
   el.value=String(value);
   if(window.jQuery&&jQuery.fn&&jQuery.fn.select2&&jQuery(el).hasClass('select2-hidden-accessible'))jQuery(el).trigger('change.select2');
  }
  function applyLine(line){
   if(!line)return;
   if(variety){
    selectValue(variety,line.paddy_variety_id);
    syncReceiveVariety();
   }
   if(gross)gross.value=line.net_weight!==null&&line.net_weight!==undefined?String(line.net_weight):'';
   if(tare)tare.value='0';
  }
  function applyPayload(payload){
   if(!payload||!payload.purchase)return;
   selectValue(supplier,payload.purchase.supplier_id);
   if(location&&payload.purchase.location_id){
    selectValue(location,payload.purchase.location_id);
    location.dispatchEvent(new Event('change',{bubbles:true}));
   }
   if(store&&payload.purchase.store_id){
    setTimeout(function(){selectValue(store,payload.purchase.store_id);store.dispatchEvent(new Event('change',{bubbles:true}));},0);
   }
   var lines=Array.isArray(payload.lines)?payload.lines:[];
   applyLine(lines[0]||null);
   if(weightNote)weightNote.style.display=lines.length?'':'none';
   if(help){
    help.textContent=lines.length>1
     ? 'This purchase has '+lines.length+' Paddy lines. The first line is loaded initially; choose another Paddy Variety to load the matching purchased weight. All fields remain editable.'
     : 'Purchase details loaded. All fields remain editable before saving the receipt.';
   }
   purchase._rcmLines=lines;
  }
  function load(){
   var id=purchase.value;
   if(!id){
    purchase._rcmLines=[];
    if(weightNote)weightNote.style.display='none';
    if(help)help.textContent='Manual entry mode. Enter Supplier, Paddy Variety, weights and quality details directly.';
    return;
   }
   if(cache[id]){applyPayload(cache[id]);return;}
   var base=purchase.getAttribute('data-details-base')||'';
   if(!base)return;
   if(help)help.textContent='Loading purchase details...';
   fetch(base.replace(/\/$/,'')+'/'+encodeURIComponent(id),{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'})
    .then(function(r){if(!r.ok)throw new Error('Unable to load purchase');return r.json();})
    .then(function(data){cache[id]=data;applyPayload(data);})
    .catch(function(){if(help)help.textContent='Unable to auto-load this purchase. You can still enter the receipt details manually.';});
  }
  purchase.addEventListener('change',load);
  if(variety)variety.addEventListener('change',function(){
   var lines=purchase._rcmLines||[];
   if(!purchase.value||!lines.length)return;
   var selected=String(variety.value||'');
   var match=lines.find(function(line){return String(line.paddy_variety_id)===selected;});
   if(match){
    if(gross)gross.value=String(match.net_weight==null?'':match.net_weight);
    if(tare)tare.value='0';
   }
  });
  if(purchase.value)load();
 }

 document.addEventListener('click',function(e){
  var opener=e.target.closest&&e.target.closest('[data-rcm-open-modal]');
  if(opener){e.preventDefault();openModal(opener.getAttribute('data-rcm-open-modal'));return;}
  var close=e.target.closest&&e.target.closest('[data-rcm-close-modal]');
  if(close){e.preventDefault();closeModal(close.closest('.rcm-modal'));return;}
  if(e.target.classList&&e.target.classList.contains('rcm-modal'))closeModal(e.target);
 });
 document.addEventListener('keydown',function(e){if(e.key==='Escape'){var m=document.querySelector('.rcm-modal.is-open');if(m)closeModal(m);}});
 function persistSettingsTabHash(hash){
  if(!hash||hash.indexOf('#rcm-settings-')!==0)return;
  // Keep the selected Settings tab in the URL even when the tab was selected
  // programmatically after an AJAX save. Bootstrap's tab('show') does not
  // update location.hash by itself, which allowed a later refresh/navigation
  // to fall back to the first (Paddy Variety) tab.
  try{
   window.history.replaceState(null,'',window.location.pathname+window.location.search+hash);
  }catch(err){
   window.location.hash=hash;
  }
 }

 function showSettingsTab(hash){
  if(!hash||hash.indexOf('#rcm-settings-')!==0)return;
  var pane=document.querySelector(hash);
  var link=document.querySelector('.rcm-settings-tabs-card a[href="'+hash+'"]');
  if(!pane||!link)return;
  if(window.jQuery&&jQuery.fn&&jQuery.fn.tab){
   jQuery(link).tab('show');
   persistSettingsTabHash(hash);
   return;
  }
  qsa('.rcm-settings-tabs-card .nav-tabs > li').forEach(function(li){li.classList.remove('active');});
  qsa('.rcm-settings-tab-content > .tab-pane').forEach(function(p){p.classList.remove('active','in');});
  var li=link.closest('li');if(li)li.classList.add('active');
  pane.classList.add('active','in');
  persistSettingsTabHash(hash);
 }

 // Keep the selected Settings tab in the URL. Bootstrap's tab plugin
 // prevents normal anchor navigation, so without this a later reload/save can
 // fall back visually to the first tab.
 document.addEventListener('click',function(e){
  var link=e.target.closest&&e.target.closest('.rcm-settings-tabs-card a[href^="#rcm-settings-"]');
  if(!link)return;
  var hash=link.getAttribute('href');
  if(!hash)return;
  persistSettingsTabHash(hash);
 });

 // Product Category Mapping uses four coloured buttons as a compact accordion.
 // Only one mapping section is open at a time; clicking the active button closes it.
 function syncProductMappingSectionButtons(root){
  root=root||document.getElementById('product-category-mapping-settings');
  if(!root)return;
  qsa('[data-rcm-settings-section-button]',root).forEach(function(btn){
   var key=btn.getAttribute('data-rcm-settings-section-button');
   var details=key?root.querySelector('details[data-rcm-settings-section="'+key+'"]'):null;
   var active=!!(details&&details.open);
   btn.classList.toggle('is-active',active);
   btn.setAttribute('aria-expanded',active?'true':'false');
  });
 }

 function openProductMappingSection(details,allowClose){
  if(!details)return;
  var root=details.closest&&details.closest('#product-category-mapping-settings');
  if(!root)return;
  var wasOpen=details.open;
  qsa('details.rcm-settings-collapsible',root).forEach(function(item){
   if(item!==details&&item.open)item.open=false;
  });
  details.open=(allowClose&&wasOpen)?false:true;
  syncProductMappingSectionButtons(root);
 }

 function initProductMappingSectionButtons(){
  var root=document.getElementById('product-category-mapping-settings');
  if(!root)return;
  var openItems=qsa('details.rcm-settings-collapsible[open]',root);
  if(openItems.length>1){
   openItems.slice(1).forEach(function(item){item.open=false;});
  }
  qsa('details.rcm-settings-collapsible',root).forEach(function(details){
   details.addEventListener('toggle',function(){syncProductMappingSectionButtons(root);});
  });
  root.addEventListener('click',function(e){
   var btn=e.target.closest&&e.target.closest('[data-rcm-settings-section-button]');
   if(!btn||!root.contains(btn))return;
   e.preventDefault();
   var key=btn.getAttribute('data-rcm-settings-section-button');
   var details=key?root.querySelector('details[data-rcm-settings-section="'+key+'"]'):null;
   if(!details)return;
   openProductMappingSection(details,true);
  });
  syncProductMappingSectionButtons(root);
 }

 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initProductMappingSectionButtons);
 else initProductMappingSectionButtons();

 // Product Category Mapping Edit uses the same on-page form. Keep the
 // selected Settings tab intact and move focus to the requested Paddy/Rice card.
 document.addEventListener('click',function(e){
  var btn=e.target.closest&&e.target.closest('[data-rcm-edit-category-mapping]');
  if(!btn)return;
  e.preventDefault();
  var id=btn.getAttribute('data-rcm-edit-category-mapping');
  var card=id?document.getElementById(id):null;
  if(!card)return;
  showSettingsTab('#rcm-settings-product-category-mapping');
  var details=card.closest&&card.closest('details.rcm-settings-collapsible');
  if(details)openProductMappingSection(details,false);
  qsa('.rcm-category-mapping-edit-card.rcm-edit-focus').forEach(function(el){el.classList.remove('rcm-edit-focus');});
  card.classList.add('rcm-edit-focus');
  card.scrollIntoView({behavior:'smooth',block:'center'});
  window.setTimeout(function(){
   var field=card.querySelector('select,input');
   if(field&&typeof field.focus==='function')field.focus();
   window.setTimeout(function(){card.classList.remove('rcm-edit-focus');},1600);
  },350);
 });

 function setFormBusy(form,busy){
  var btn=form.querySelector('button[type="submit"],button:not([type])');
  if(busy){
   form.setAttribute('data-rcm-submitting','1');
   if(btn){
    btn.disabled=true;
    btn.setAttribute('aria-busy','true');
    if(!btn.getAttribute('data-rcm-original-html'))btn.setAttribute('data-rcm-original-html',btn.innerHTML);
    btn.innerHTML='<i class="fa fa-spinner fa-spin"></i> Saving...';
   }
  }else{
   form.removeAttribute('data-rcm-submitting');
   if(btn){
    btn.disabled=false;
    btn.removeAttribute('aria-busy');
    var original=btn.getAttribute('data-rcm-original-html');
    if(original)btn.innerHTML=original;
   }
  }
 }

 function showInstantStatus(message,isError){
  var shell=document.querySelector('.rcm-shell'); if(!shell)return;
  var old=document.getElementById('rcm-instant-status'); if(old)old.remove();
  var box=document.createElement('div');
  box.id='rcm-instant-status';
  box.className='rcm-alert '+(isError?'rcm-alert-danger':'rcm-alert-success');
  box.innerHTML='<i class="fa '+(isError?'fa-exclamation-circle':'fa-check-circle')+'"></i><span></span>';
  box.querySelector('span').textContent=message|| (isError?'Unable to save.':'Saved.');
  var topbar=shell.querySelector('.rcm-topbar');
  if(topbar&&topbar.nextSibling)shell.insertBefore(box,topbar.nextSibling);else shell.insertBefore(box,shell.firstChild);
  window.setTimeout(function(){if(box&&box.parentNode)box.parentNode.removeChild(box);},4500);
 }

 function parseHtml(html){
  var t=document.createElement('template'); t.innerHTML=(html||'').trim(); return t.content;
 }

 function refreshVarietyTableState(){
  var table=document.getElementById('rcm-variety-settings-table');
  if(!table)return;
  if(table.__rcmManagedState){
   table.__rcmManagedState.rows=tableRows(table);
   table.__rcmManagedState.emptyRow=table.querySelector('[data-rcm-empty-row]');
   applyTable(table.__rcmManagedState);
  }
 }

 function applyVarietyAjaxResult(data,form){
  var id=String(data.id||'');
  var table=document.getElementById('rcm-variety-settings-table');
  if(table&&data.row_html){
   var frag=parseHtml(data.row_html);
   var newRow=frag.querySelector('tr');
   var oldRow=table.querySelector('tr[data-rcm-variety-id="'+id.replace(/"/g,'')+'"]');
   var empty=table.querySelector('[data-rcm-empty-row]');
   if(newRow){
    if(oldRow)oldRow.replaceWith(newRow);
    else{if(empty)empty.style.display='none';table.querySelector('tbody').appendChild(newRow);}
   }
  }

  if(data.modals_html){
   qsa('[data-rcm-variety-modal="'+id.replace(/"/g,'')+'"]').forEach(function(m){m.remove();});
   var holder=document.getElementById('rcm-variety-dynamic-modals');
   if(holder)holder.appendChild(parseHtml(data.modals_html));
  }

  var modal=form.closest('.rcm-modal');
  if(modal)closeModal(modal);
  if(data.mode==='create'){
   form.reset();
   if(window.jQuery&&jQuery.fn&&jQuery.fn.select2){jQuery(form).find('select.select2-hidden-accessible').trigger('change');}
   initPaddyVarietyProductFields(form);
  }
  refreshVarietyTableState();
  showSettingsTab('#rcm-settings-paddy-variety');
  showInstantStatus(data.message||'Paddy variety saved.',false);
 }

 function showVarietyValidation(form,data){
  var old=form.querySelector('.rcm-variety-ajax-errors'); if(old)old.remove();
  var errors=data&&data.errors?data.errors:null;
  var messages=[];
  if(errors){Object.keys(errors).forEach(function(k){(errors[k]||[]).forEach(function(m){messages.push(m);});});}
  if(!messages.length&&data&&data.message)messages.push(data.message);
  var box=document.createElement('div');
  box.className='rcm-alert rcm-alert-danger rcm-variety-ajax-errors';
  box.innerHTML='<i class="fa fa-exclamation-circle"></i><div><strong>Please correct the following:</strong><ul></ul></div>';
  var ul=box.querySelector('ul');
  messages.forEach(function(m){var li=document.createElement('li');li.textContent=m;ul.appendChild(li);});
  var body=form.querySelector('.rcm-modal-body'); if(body)body.insertBefore(box,body.firstChild);
 }

 function submitVarietyAjax(form){
  var headers={'Accept':'application/json','X-Requested-With':'XMLHttpRequest'};
  fetch(form.action,{method:'POST',body:new FormData(form),headers:headers,credentials:'same-origin'})
   .then(function(res){return res.json().catch(function(){return {};}).then(function(data){return {ok:res.ok,status:res.status,data:data};});})
   .then(function(result){
    if(!result.ok){showVarietyValidation(form,result.data);return;}
    applyVarietyAjaxResult(result.data,form);
   })
   .catch(function(){showVarietyValidation(form,{message:'Unable to save Paddy Variety. Please try again.'});})
   .finally(function(){setFormBusy(form,false);});
 }

 function refreshManagedTableById(id){
  var table=document.getElementById(id); if(!table||!table.__rcmManagedState)return;
  table.__rcmManagedState.rows=tableRows(table);
  table.__rcmManagedState.emptyRow=table.querySelector('[data-rcm-empty-row]');
  applyTable(table.__rcmManagedState);
 }

 function applyMasterAjaxResult(data,form){
  var kind=String(data.kind||form.getAttribute('data-rcm-master-ajax')||'');
  var id=String(data.id||'');
  var config={
   mill:{table:'rcm-mill-settings-table',rowAttr:'data-rcm-mill-id',modalAttr:'data-rcm-mill-modal',holder:'rcm-mill-dynamic-modals',tab:'#rcm-settings-mills'},
   product:{table:'rcm-product-settings-table',rowAttr:'data-rcm-product-id',modalAttr:'data-rcm-product-modal',holder:'rcm-product-dynamic-modals',tab:'#rcm-settings-rice-products'}
  }[kind];
  if(!config)throw new Error('Unknown Rice Mill Settings master response.');

  var table=document.getElementById(config.table);
  if(table&&data.row_html){
   var frag=parseHtml(data.row_html),newRow=frag.querySelector('tr');
   var oldRow=table.querySelector('tr['+config.rowAttr+'="'+id.replace(/"/g,'')+'"]');
   var empty=table.querySelector('[data-rcm-empty-row]');
   if(newRow){
    if(oldRow)oldRow.replaceWith(newRow);
    else{if(empty)empty.style.display='none';table.querySelector('tbody').appendChild(newRow);}
   }
  }

  if(data.modals_html){
   qsa('['+config.modalAttr+'="'+id.replace(/"/g,'')+'"]').forEach(function(m){m.remove();});
   var holder=document.getElementById(config.holder);
   if(holder)holder.appendChild(parseHtml(data.modals_html));
  }

  var modal=form.closest('.rcm-modal'); if(modal)closeModal(modal);
  if(data.mode==='create'){
   form.reset();
   // Keep Select2's visible value in sync with the native select after reset.
   if(window.jQuery&&jQuery.fn&&jQuery.fn.select2){jQuery(form).find('select.select2-hidden-accessible').trigger('change');}
  }
  refreshManagedTableById(config.table);
  showSettingsTab(config.tab);
  if(window.RiceMill&&typeof window.RiceMill.initUi==='function')window.RiceMill.initUi();
  showInstantStatus(data.message||'Saved.',false);
 }

 function submitMasterAjax(form){
  var headers={'Accept':'application/json','X-Requested-With':'XMLHttpRequest'};
  fetch(form.action,{method:'POST',body:new FormData(form),headers:headers,credentials:'same-origin'})
   .then(function(res){return res.json().catch(function(){return {};}).then(function(data){return {ok:res.ok,data:data};});})
   .then(function(result){
    if(!result.ok){showVarietyValidation(form,result.data);return;}
    applyMasterAjaxResult(result.data,form);
   })
   .catch(function(){showVarietyValidation(form,{message:'Unable to save. Please try again.'});})
   .finally(function(){setFormBusy(form,false);});
 }

 // v27: every Rice Mill write form responds on the first click. The browser
 // gets immediate Saving feedback and accidental double-submits are blocked.
 document.addEventListener('submit',function(e){
  var form=e.target;
  if(!form||!form.closest||!form.closest('.rcm-shell'))return;
  var method=String(form.getAttribute('method')||'get').toLowerCase();
  if(method==='get')return;
  if(form.getAttribute('data-rcm-submitting')==='1'){
   e.preventDefault();
   return;
  }
  setFormBusy(form,true);
  if(form.matches('[data-rcm-variety-ajax="1"]')){
   e.preventDefault();
   submitVarietyAjax(form);
   return;
  }
  if(form.matches('[data-rcm-master-ajax]')){
   e.preventDefault();
   submitMasterAjax(form);
  }
 });

 function updateNumberPreview(input){
  if(!input)return;
  var target=document.getElementById(input.getAttribute('data-preview-target'));
  if(!target)return;
  var raw=parseInt(input.value,10);
  if(!Number.isFinite(raw)||raw<1){target.textContent=(input.getAttribute('data-prefix')||'')+'------';return;}
  target.textContent=(input.getAttribute('data-prefix')||'')+String(raw).padStart(6,'0');
 }

 function syncPurchaseOpening(input){
  if(!input||input.readOnly)return;
  var next=document.getElementById('rcm-purchase-next-number');
  if(!next)return;
  var opening=parseInt(input.value,10);
  var safeMin=parseInt(next.getAttribute('data-safe-min')||'1',10);
  if(!Number.isFinite(safeMin)||safeMin<1)safeMin=1;
  if(Number.isFinite(opening)&&opening>0){
   next.min=String(Math.max(safeMin,opening));
   var current=parseInt(next.value,10);
   if(!Number.isFinite(current)||current<opening){next.value=String(opening);updateNumberPreview(next);}
  }else{
   next.min=String(safeMin);
  }
 }

 document.addEventListener('input',function(e){
  if(e.target&&e.target.matches('[data-rcm-number-input]'))updateNumberPreview(e.target);
  if(e.target&&e.target.matches('[data-rcm-purchase-opening]'))syncPurchaseOpening(e.target);
 });

 function initProductionCompleteAutoload(){
  var form=document.getElementById('rcm-production-complete-form');
  if(!form||form.dataset.rcmOutputBound==='1')return;
  form.dataset.rcmOutputBound='1';
  var productHelp=document.getElementById('rcm-production-product-help');
  var yieldHelp=document.getElementById('rcm-production-yield-help');

  function decimalsFor(el){
   var step=el&&el.getAttribute('step')||'0.001';
   var dot=step.indexOf('.');
   return dot<0?0:Math.min(6,step.length-dot-1);
  }
  function setInputQty(el,value){
   if(!el)return;
   var d=decimalsFor(el);
   el.value=(Math.max(0,value)||0).toFixed(d);
  }
  function setFirstYieldQty(key,value){
   var nodes=[].slice.call(form.querySelectorAll('.rcm-production-output-qty[data-yield-key="'+key+'"]'));
   nodes.forEach(function(el,index){setInputQty(el,index===0?value:0);});
  }
  // IS2293: the explicit Settings > Out Put Type mapping is authoritative.
  // Paddy Lot data supplies yield percentages only; it must not block saving by
  // trying to resolve a second Rice Product mapping.
  function calculate(updateOutputs){
   var totals={rice:0,broken:0,bran:0,husk:0};
   var inputTotal=0;
   [].slice.call(form.querySelectorAll('[data-rcm-production-input-row]')).forEach(function(row){
    var lot=row.querySelector('.rcm-production-lot');
    var qtyInput=row.querySelector('.rcm-production-input-qty');
    if(!lot||!qtyInput)return;
    var qty=parseFloat(qtyInput.value||'0');
    if(!Number.isFinite(qty)||qty<=0)return;
    var opt=lot.options[lot.selectedIndex];
    if(!opt)return;
    inputTotal+=qty;
    totals.rice+=qty*(parseFloat(opt.getAttribute('data-rice')||'0')||0)/100;
    totals.broken+=qty*(parseFloat(opt.getAttribute('data-broken')||'0')||0)/100;
    totals.bran+=qty*(parseFloat(opt.getAttribute('data-bran')||'0')||0)/100;
    totals.husk+=qty*(parseFloat(opt.getAttribute('data-husk')||'0')||0)/100;
   });

   if(updateOutputs){
    setFirstYieldQty('rice',totals.rice);
    setFirstYieldQty('broken',totals.broken);
    setFirstYieldQty('bran',totals.bran);
    setFirstYieldQty('husk',totals.husk);
   }

   if(productHelp){
    productHelp.textContent=inputTotal>0
     ? 'Output quantities were suggested from the selected Paddy Variety yield settings. The mapped Out Put Type Products remain authoritative and quantities are editable.'
     : 'Select the Paddy Lot and enter the input quantity. Output Products come from Settings / Product Category Mapping / Out Put Type.';
   }
   if(yieldHelp){
    yieldHelp.textContent=inputTotal>0
     ? 'Mapped Output quantities were auto-calculated where the Product matches Rice, Broken Rice, Bran or Husk yield settings. Other mapped Products stay editable for manual quantity entry.'
     : 'Enter the Paddy input quantity to auto-calculate applicable mapped Output Products from Paddy Variety Settings.';
   }
   return {totals:totals};
  }

  form.addEventListener('change',function(e){if(e.target.matches('.rcm-production-lot'))calculate(true);});
  form.addEventListener('input',function(e){if(e.target.matches('.rcm-production-input-qty'))calculate(true);});
  calculate(form.getAttribute('data-has-old-output')!=='1');
 }

 document.addEventListener('DOMContentLoaded',function(){
  refreshManagedTables(document);
  var v=document.getElementById('rcm-receive-variety');
  if(v){
   // Select2 changes are jQuery events on some host builds and are not always
   // observed by a native addEventListener handler. Bind both paths so changing
   // Paddy Variety always refreshes Moisture, Foreign Matter, configured limit,
   // Quality Grade and the next Stock Lot preview immediately.
   v.addEventListener('change',syncReceiveVariety);
   if(window.jQuery){
    jQuery(v).off('change.rcmReceiveVariety').on('change.rcmReceiveVariety',syncReceiveVariety);
   }
   syncReceiveVariety();
  }
  initRelatedPurchaseAutoload();
  initProductionCompleteAutoload();
  qsa('[data-rcm-number-input]').forEach(updateNumberPreview);
  var purchaseOpening=document.querySelector('[data-rcm-purchase-opening]'); if(purchaseOpening)syncPurchaseOpening(purchaseOpening);
  if(window.location.hash)showSettingsTab(window.location.hash);
  var auto=document.querySelector('.rcm-modal[data-rcm-auto-open="1"]');
  if(auto){showSettingsTab('#rcm-settings-receive-paddy');openModal(auto.id);}
 });
})();

/* v15 - Settings Action menu overlay.
 * Move the open menu to <body> and position it against the Action button.
 * This prevents .rcm-table-wrap horizontal scrolling from clipping the menu.
 */
(function(){
 var activeDetails=null,activeMenu=null,placeholder=null;

 function restoreActionMenu(){
  if(activeMenu&&placeholder&&placeholder.parentNode){
   placeholder.parentNode.insertBefore(activeMenu,placeholder);
   placeholder.parentNode.removeChild(placeholder);
  }
  if(activeMenu){
   activeMenu.classList.remove('rcm-action-menu-floating');
   activeMenu.style.left='';
   activeMenu.style.top='';
   activeMenu.style.visibility='';
  }
  if(activeDetails)activeDetails.open=false;
  activeDetails=null;activeMenu=null;placeholder=null;
 }

 function positionActionMenu(details){
  var summary=details.querySelector('summary');
  var menu=details.querySelector('.rcm-action-menu-items');
  if(!summary||!menu)return;

  restoreActionMenu();
  activeDetails=details;activeMenu=menu;
  placeholder=document.createComment('rcm-action-menu-placeholder');
  menu.parentNode.insertBefore(placeholder,menu);
  document.body.appendChild(menu);
  menu.classList.add('rcm-action-menu-floating');
  details.open=true;

  window.requestAnimationFrame(function(){
   if(!activeMenu||activeMenu!==menu)return;
   var rect=summary.getBoundingClientRect();
   var gap=6,pad=8;
   menu.style.visibility='hidden';
   menu.style.display='block';
   var mw=menu.offsetWidth,mh=menu.offsetHeight;
   var left=rect.right-mw;
   if(left<pad)left=pad;
   if(left+mw>window.innerWidth-pad)left=Math.max(pad,window.innerWidth-pad-mw);
   var top=rect.bottom+gap;
   if(top+mh>window.innerHeight-pad&&rect.top-mh-gap>=pad){
    top=rect.top-mh-gap;
   }else if(top+mh>window.innerHeight-pad){
    top=Math.max(pad,window.innerHeight-pad-mh);
   }
   menu.style.left=Math.round(left)+'px';
   menu.style.top=Math.round(top)+'px';
   menu.style.visibility='visible';
  });
 }

 document.addEventListener('click',function(e){
  var summary=e.target.closest&&e.target.closest('.rcm-action-menu > summary');
  if(summary){
   e.preventDefault();
   var details=summary.parentElement;
   if(activeDetails===details){restoreActionMenu();}
   else{positionActionMenu(details);}
   return;
  }

  if(activeMenu&&activeMenu.contains(e.target)){
   /* Keep the menu available while the user chooses an action.
    * View/Edit modal buttons can close the menu after their existing handler runs. */
   if(e.target.closest&&e.target.closest('[data-rcm-open-modal]')){
    window.setTimeout(restoreActionMenu,0);
   }
   return;
  }

  if(activeDetails)restoreActionMenu();
 });

 window.addEventListener('resize',restoreActionMenu);
 window.addEventListener('scroll',restoreActionMenu,true);
})();

