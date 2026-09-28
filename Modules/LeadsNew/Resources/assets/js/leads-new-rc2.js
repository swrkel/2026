(function () {
  'use strict';
  function ready(fn){ if(document.readyState !== 'loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
  ready(function(){
    document.querySelectorAll('.leads-new-search').forEach(function(input){
      input.addEventListener('keyup', function(){
        var term = this.value.toLowerCase();
        document.querySelectorAll('.leads-new-datatable tbody tr').forEach(function(row){
          row.style.display = row.textContent.toLowerCase().indexOf(term) === -1 ? 'none' : '';
        });
      });
    });
    document.querySelectorAll('.leads-new-print').forEach(function(btn){ btn.addEventListener('click', function(){ window.print(); }); });
  });
})();
