(function(){
    window.RestaurantNewCRM = {
        refreshDashboard: function(){ document.dispatchEvent(new CustomEvent('restaurantnew.crm.refresh')); },
        openCustomer360: function(url){ window.location.href = url; }
    };
})();
