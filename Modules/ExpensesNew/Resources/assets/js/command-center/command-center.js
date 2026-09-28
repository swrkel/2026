(function(){
    function refreshWidgets(){
        var url = document.querySelector('[data-expnew-widget-url]');
        if(!url) return;
        fetch(url.dataset.expnewWidgetUrl).then(r=>r.json()).then(function(data){
            window.dispatchEvent(new CustomEvent('expnew:widgets-refreshed',{detail:data}));
        });
    }
    window.ExpenseNewCommandCenter = { refreshWidgets: refreshWidgets };
})();
