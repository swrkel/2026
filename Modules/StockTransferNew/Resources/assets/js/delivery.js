(function(){
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-stn-confirm-delivery]').forEach(function(btn){
      btn.addEventListener('click', function(e){
        if(!confirm('Confirm this delivery record?')) e.preventDefault();
      });
    });
  });
})();
