(function(){
  document.querySelectorAll('.stn39-actions form').forEach(function(form){
    form.addEventListener('submit', function(e){
      var text = form.innerText || 'continue';
      if(!window.confirm('Confirm action: ' + text.trim() + '?')){ e.preventDefault(); }
    });
  });
})();
