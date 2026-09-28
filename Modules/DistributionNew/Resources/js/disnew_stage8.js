(function(){
  window.DistributionNewStage8 = {
    initToolbar:function(){ document.querySelectorAll('[data-disnew-confirm]').forEach(function(btn){btn.addEventListener('click', function(e){ if(!confirm(btn.dataset.disnewConfirm)){e.preventDefault();}});});},
    recalcCapacity:function(){ var w=document.querySelector('[name=loaded_weight]'), c=document.querySelector('[name=capacity_weight]'); if(w&&c&&parseFloat(w.value)>parseFloat(c.value)){w.classList.add('is-invalid');} }
  };
  document.addEventListener('DOMContentLoaded', window.DistributionNewStage8.initToolbar);
})();
