(function(){
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.stn-actions form').forEach(function(form){
      form.addEventListener('submit', function(e){
        if(!confirm('Confirm this logistics action?')){ e.preventDefault(); }
      });
    });
  });
})();
