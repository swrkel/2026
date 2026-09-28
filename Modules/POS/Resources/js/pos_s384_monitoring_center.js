(function(){
    function csrf(){ var m=document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; }
    function log(msg){ var c=document.getElementById('s384-console'); if(c){ c.textContent = (new Date()).toLocaleTimeString() + ' - ' + msg + "\n" + c.textContent; } }
    function postJson(url, payload){
        return fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'}, body:JSON.stringify(payload||{})}).then(function(r){return r.json().then(function(j){ if(!r.ok){ throw j; } return j; });});
    }
    var refresh=document.getElementById('s384-refresh');
    if(refresh){ refresh.addEventListener('click', function(){
        fetch('/pos-module/offline-sync/monitoring/status', {headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(j){
            if(j && j.ok && j.status){
                document.getElementById('s384-online').textContent = (j.status.monitor && j.status.monitor.online_devices) || 0;
                document.getElementById('s384-offline').textContent = ((j.status.monitor && j.status.monitor.offline_devices) || 0) + ((j.status.monitor && j.status.monitor.weak_network_devices) || 0);
                document.getElementById('s384-pending').textContent = (j.status.queue && j.status.queue.pending) || 0;
                document.getElementById('s384-conflicts').textContent = j.status.conflicts_open || 0;
                log('Monitoring status refreshed.');
            }
        }).catch(function(e){ log('Refresh failed: '+(e.message||'error')); });
    });}
    document.querySelectorAll('.s384-trust').forEach(function(btn){ btn.addEventListener('click', function(){
        var id=this.closest('tr').getAttribute('data-device'); postJson('/pos-module/offline-sync/devices/'+encodeURIComponent(id)+'/trust', {}).then(function(){ log('Device trusted: '+id); }).catch(function(e){ log(e.message||'Trust failed'); });
    });});
    document.querySelectorAll('.s384-block').forEach(function(btn){ btn.addEventListener('click', function(){
        var id=this.closest('tr').getAttribute('data-device'); if(!confirm('Block this terminal from synchronization?')) return; postJson('/pos-module/offline-sync/devices/'+encodeURIComponent(id)+'/block', {}).then(function(){ log('Device blocked: '+id); }).catch(function(e){ log(e.message||'Block failed'); });
    });});
})();
