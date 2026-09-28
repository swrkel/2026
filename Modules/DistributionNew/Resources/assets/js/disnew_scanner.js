(function(){
  function postScan(value){
    var token=document.querySelector('meta[name="csrf-token"]');
    fetch('/distribution-new/scanner/scan',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token?token.content:''},body:JSON.stringify({barcode_value:value,qty:1})})
      .then(r=>r.json()).then(function(resp){
        var body=document.querySelector('.disnew-scanner-table tbody');
        if(!body) return;
        var label=resp.label||{};
        var scan=resp.scan||{};
        var row='<tr><td>'+new Date().toLocaleTimeString()+'</td><td>'+value+'</td><td>'+(label.product_id||'-')+'</td><td>'+(scan.qty||1)+'</td><td>'+(scan.scan_status||'-')+'</td></tr>';
        body.insertAdjacentHTML('afterbegin',row);
      });
  }
  document.addEventListener('keydown',function(e){
    var el=e.target;
    if(el && el.classList.contains('disnew-scan-input') && e.key==='Enter'){
      e.preventDefault(); var v=el.value.trim(); if(v){ postScan(v); el.value=''; }
    }
  });
})();
