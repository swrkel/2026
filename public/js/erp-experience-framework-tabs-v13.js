(function(){
    "use strict";
    function normalizeExfTabs(){
        var selectors = [
            'ul.nav.nav-tabs',
            'ul.nav.nav-pills',
            '.nav-tabs-custom > .nav-tabs',
            '.erp-tabs',
            '.module-tabs',
            '.customer-tabs'
        ];
        document.querySelectorAll(selectors.join(',')).forEach(function(list){
            if(list.classList.contains('no-erp-global-tabs') || list.classList.contains('no-exf-tabs')) return;
            list.classList.add('erp-global-tabs');
            list.querySelectorAll('a[data-toggle="tab"],a[data-bs-toggle="tab"],.nav-link').forEach(function(link){
                link.classList.add('erp-tab');
            });
        });
    }
    document.addEventListener('DOMContentLoaded', normalizeExfTabs);
    document.addEventListener('shown.bs.tab', normalizeExfTabs);
    document.addEventListener('click', function(e){
        var link = e.target.closest('a[data-toggle="tab"],a[data-bs-toggle="tab"],button[data-toggle="tab"],button[data-bs-toggle="tab"],.erp-tab,.erp-tab-btn,.customer-tab-btn,.module-tab-btn');
        if(!link) return;
        var list = link.closest('ul.nav.nav-tabs,ul.nav.nav-pills,.nav-tabs,.nav-pills,.erp-global-tabs,.erp-tabs,.module-tabs,.customer-tabs');
        if(!list || list.classList.contains('no-erp-global-tabs') || list.classList.contains('no-exf-tabs')) return;
        list.querySelectorAll('li').forEach(function(li){ li.classList.remove('active'); });
        var li = link.closest('li');
        if(li) li.classList.add('active');
        list.querySelectorAll('a,button').forEach(function(x){ x.classList.remove('active'); x.setAttribute('aria-selected','false'); });
        link.classList.add('active');
        link.setAttribute('aria-selected','true');
    }, true);
})();
