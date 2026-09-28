(function(){
  document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('auto-add-line')){
      var tbody=document.querySelector('.auto-lines tbody'); if(!tbody) return;
      var idx=tbody.querySelectorAll('tr').length;
      var tr=tbody.querySelector('tr').cloneNode(true);
      tr.querySelectorAll('input,select').forEach(function(el){ el.name=el.name.replace(/lines\[\d+\]/,'lines['+idx+']'); if(el.tagName==='INPUT') el.value=''; });
      tbody.appendChild(tr);
    }
  });
})();
