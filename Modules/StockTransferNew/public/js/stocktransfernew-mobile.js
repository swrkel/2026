(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var input = document.querySelector('.stn-scan-input');
        if(input){ input.focus(); input.select(); }
        document.querySelectorAll('.stn-scan-form').forEach(function(form){
            form.addEventListener('submit', function(){
                var btn = form.querySelector('.stn-scan-submit');
                if(btn){ btn.disabled = true; setTimeout(function(){ btn.disabled = false; }, 1200); }
            });
        });
    });
})();
