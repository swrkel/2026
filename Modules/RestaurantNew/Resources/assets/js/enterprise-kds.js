(function(){
    function pad(n){return n<10?'0'+n:n;}
    function updateTimers(){
        var overdue = 0;
        document.querySelectorAll('.kds-timer').forEach(function(el){
            var start = el.getAttribute('data-start');
            var expected = parseInt(el.getAttribute('data-expected') || '0', 10);
            if(!start){ el.textContent='--:--'; return; }
            var elapsed = Math.max(0, Math.floor((Date.now() - new Date(start).getTime())/60000));
            var secs = Math.max(0, Math.floor((Date.now() - new Date(start).getTime())/1000)%60);
            el.textContent = pad(elapsed)+':'+pad(secs);
            el.classList.remove('on-time','warning','overdue');
            if(expected && elapsed > expected){ el.classList.add('overdue'); overdue++; }
            else if(expected && elapsed >= Math.max(1, expected-3)){ el.classList.add('warning'); }
            else { el.classList.add('on-time'); }
        });
        var count = document.querySelector('.js-kds-overdue'); if(count){ count.textContent = overdue; }
    }
    setInterval(updateTimers,1000); updateTimers();

    document.addEventListener('click', function(e){
        var btn = e.target.closest('.js-kds-status');
        if(!btn) return;
        var wrap = btn.closest('.kds-card-wrap');
        var id = wrap.getAttribute('data-id');
        var token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
        fetch(window.location.pathname + '/queue/' + id + '/status', {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
            body: JSON.stringify({status:btn.getAttribute('data-status')})
        }).then(function(r){return r.json();}).then(function(res){
            if(res.success){ wrap.querySelector('.kds-card').className='kds-card status-'+res.item.current_status+' priority-'+res.item.priority; }
        });
    });
})();
