(function(){
  document.addEventListener('DOMContentLoaded', function(){
    var dateInputs = document.querySelectorAll('.leads-new-date');
    dateInputs.forEach(function(el){ if(!el.value){ el.value = new Date().toISOString().slice(0,10); } });
  });
})();
