(function(){
    function refreshLiveBoard(){
        var table=document.querySelector('.stn-042-live-board');
        if(!table || !window.fetch){return;}
        var url=(window.stn042LiveBoardUrl || '').trim();
        if(!url){return;}
        fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){return r.json();}).then(function(payload){
            if(!payload.rows){return;}
            var tbody=table.querySelector('tbody');
            tbody.innerHTML='';
            payload.rows.forEach(function(row){
                var tr=document.createElement('tr');
                tr.innerHTML='<td>'+row.transfer_no+'</td><td>'+row.status+'</td><td>'+row.priority+'</td><td>'+(row.expected_delivery_at||'')+'</td><td>'+(row.updated_at||'')+'</td>';
                tbody.appendChild(tr);
            });
        }).catch(function(){});
    }
    if(document.querySelector('.stn-042-live-board')){setInterval(refreshLiveBoard,60000);}
})();
