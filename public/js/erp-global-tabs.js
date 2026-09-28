(function(){
  function normalizeTabs(){
    document.querySelectorAll('ul.nav.nav-tabs:not(.no-erp-global-tabs)').forEach(function(tabList){
      tabList.classList.add('erp-tabs');
      tabList.querySelectorAll('.nav-link').forEach(function(link){
        link.classList.add('erp-tab');
      });
    });
  }
  document.addEventListener('DOMContentLoaded', normalizeTabs);
  document.addEventListener('shown.bs.tab', normalizeTabs);
  document.addEventListener('click', function(e){
    var link = e.target.closest('a[data-toggle="tab"],a[data-bs-toggle="tab"],.erp-tab');
    if(!link) return;
    var list = link.closest('ul.nav.nav-tabs,.erp-tabs');
    if(list){
      list.querySelectorAll('.nav-link,.erp-tab').forEach(function(x){x.classList.remove('active'); x.setAttribute('aria-selected','false');});
      link.classList.add('active'); link.setAttribute('aria-selected','true');
    }
  });
})();
