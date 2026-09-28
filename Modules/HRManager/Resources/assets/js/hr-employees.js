document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.hr-form input[name="first_name"], .hr-form input[name="last_name"]').forEach(function(el){
    el.addEventListener('blur', function(){
      const first=document.querySelector('input[name="first_name"]');
      const last=document.querySelector('input[name="last_name"]');
      const display=document.querySelector('input[name="display_name"]');
      if(display && !display.value.trim()){ display.value=((first?.value||'')+' '+(last?.value||'')).trim(); }
    });
  });
});
