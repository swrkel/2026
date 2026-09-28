(function(){
  window.LeadsNewStage6 = {
    bulkCollect: function(selector){ return Array.from(document.querySelectorAll(selector+':checked')).map(function(el){return el.value;}); },
    confirmBulk: function(message){ return window.confirm(message || 'Apply this action to selected leads?'); }
  };
})();
