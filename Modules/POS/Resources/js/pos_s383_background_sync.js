(function(){
    function log(msg, data){
        var box=document.getElementById('s383-console'); if(!box) return;
        var line='['+(new Date()).toLocaleTimeString()+'] '+msg+(data?('\n'+JSON.stringify(data,null,2)):'');
        box.textContent=line+'\n\n'+box.textContent;
    }
    function token(){ var t=document.querySelector('meta[name="csrf-token"]'); return t?t.getAttribute('content'):''; }
    function deviceUuid(){
        var key='pos_device_uuid'; var val=localStorage.getItem(key);
        if(!val){ val='DEV-'+Date.now()+'-'+Math.random().toString(16).slice(2); localStorage.setItem(key,val); }
        return val;
    }
    async function post(url, body){
        var started=performance.now();
        var res=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token(),'Accept':'application/json'},body:JSON.stringify(body||{})});
        var json=await res.json().catch(function(){return {ok:false,message:'Invalid JSON response'};});
        var latency=Math.round(performance.now()-started);
        try{ await fetch('/pos-module/offline-sync/network-sample',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token(),'Accept':'application/json'},body:JSON.stringify({device_uuid:deviceUuid(),online:navigator.onLine,latency_ms:latency,quality:latency<500?'good':(latency<1500?'fair':'poor')})}); }catch(e){}
        return json;
    }
    async function refreshProgress(){
        var res=await fetch('/pos-module/offline-sync/progress',{headers:{'Accept':'application/json'}});
        var json=await res.json();
        if(json.progress){
            if(document.getElementById('s383-pending')) document.getElementById('s383-pending').textContent=json.progress.pending;
            if(document.getElementById('s383-synced')) document.getElementById('s383-synced').textContent=json.progress.synced;
            if(document.getElementById('s383-conflict')) document.getElementById('s383-conflict').textContent=json.progress.conflict;
        }
        log('Progress refreshed', json);
    }
    document.addEventListener('click', async function(e){
        if(e.target.closest('#s383-run-batch')){ log('Running next background batch...'); log('Batch result', await post('/pos-module/offline-sync/batch-next',{limit:50,device_uuid:deviceUuid()})); refreshProgress(); }
        if(e.target.closest('#s383-heartbeat')){ log('Sending device heartbeat...'); log('Heartbeat result', await post('/pos-module/offline-sync/heartbeat',{device_uuid:deviceUuid(),online:navigator.onLine,queue_size:0,network_quality:navigator.onLine?'online':'offline',browser_info:navigator.userAgent})); }
        if(e.target.closest('#s383-progress')){ refreshProgress(); }
    });
    window.addEventListener('online', function(){ log('Connection restored. Starting background sync.'); post('/pos-module/offline-sync/batch-next',{limit:50,device_uuid:deviceUuid()}).then(function(r){log('Auto sync result',r); refreshProgress();}); });
    setInterval(function(){ post('/pos-module/offline-sync/heartbeat',{device_uuid:deviceUuid(),online:navigator.onLine,queue_size:0,network_quality:navigator.onLine?'online':'offline',browser_info:navigator.userAgent}).catch(function(){}); }, 60000);
})();
