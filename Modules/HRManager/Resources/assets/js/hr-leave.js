document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.hr-mini-btn,.hr-btn').forEach(function(btn){
        btn.addEventListener('click', function(){ btn.classList.add('hr-clicked'); setTimeout(()=>btn.classList.remove('hr-clicked'), 250); });
    });
});
