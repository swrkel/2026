(function(){
  'use strict';
  function byId(id){ return document.getElementById(id); }
  function visible(el){ return !!(el && el.offsetParent !== null); }
  function focusScanner(){
    var q = byId('pos_product_search') || byId('pos_product_q') || document.querySelector('[data-pos-barcode-input]') || document.querySelector('[name="barcode"]');
    if(q){ q.focus(); q.select && q.select(); q.classList.add('pos-cashier-focus-ring'); setTimeout(function(){q.classList.remove('pos-cashier-focus-ring');}, 900); }
  }
  function clickFirst(selector){ var el = document.querySelector(selector); if(el){ el.click(); return true; } return false; }
  function toggleHelp(){ var help = byId('pos_shortcut_help'); if(help){ help.classList.toggle('is-open'); } }
  function setQuickCash(amount){
    var paid = byId('paid_amount') || document.querySelector('[name="paid_amount"]');
    if(paid){ paid.value = parseFloat(amount || 0).toFixed(2); paid.focus(); paid.dispatchEvent(new Event('input', {bubbles:true})); }
  }
  function getTotal(){
    var totalEl = byId('sum_total') || document.querySelector('[data-pos-total]');
    if(!totalEl) return 0;
    return parseFloat((totalEl.textContent || totalEl.value || '0').replace(/[^0-9.\-]/g,'')) || 0;
  }
  function bindQuickCash(){
    document.querySelectorAll('[data-pos-quick-cash]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var value = btn.getAttribute('data-pos-quick-cash');
        setQuickCash(value === 'total' ? getTotal() : value);
      });
    });
  }
  document.addEventListener('keydown', function(e){
    if(e.target && ['INPUT','TEXTAREA','SELECT'].indexOf(e.target.tagName) !== -1 && !e.altKey && !e.ctrlKey){
      if(e.key !== 'F2' && e.key !== 'F4') return;
    }
    if(e.key === 'F2'){ e.preventDefault(); focusScanner(); }
    if(e.key === 'F4'){ e.preventDefault(); clickFirst('[data-pos-action="customer"], [data-action="customer"], #customer_name, [name="customer_name"]'); }
    if(e.key === 'F6'){ e.preventDefault(); clickFirst('[data-pos-action="hold"], [data-action="hold"], .pos-hold-sale-btn'); }
    if(e.key === 'F7'){ e.preventDefault(); clickFirst('[data-pos-action="resume"], [data-action="resume"], .pos-resume-sale-btn'); }
    if(e.key === 'F8'){ e.preventDefault(); clickFirst('[data-pos-action="payment"], [data-action="payment"], #paid_amount, [name="paid_amount"]'); }
    if(e.key === 'F9'){ e.preventDefault(); clickFirst('[data-pos-action="checkout"], [data-action="checkout"], .pos-checkout-btn, button[type="submit"]'); }
    if(e.key === 'F10'){ e.preventDefault(); toggleHelp(); }
    if(e.key === 'Escape'){ var help = byId('pos_shortcut_help'); if(help && help.classList.contains('is-open')){ help.classList.remove('is-open'); } }
  });
  document.addEventListener('DOMContentLoaded', function(){
    bindQuickCash();
    focusScanner();
    var status = document.querySelector('.pos-scan-status');
    if(status){ status.innerHTML = '<strong>Ready</strong> for barcode scan. Press F2 anytime to return to scan box.'; }
  });
  window.POSEnterpriseCashier = {focusScanner: focusScanner, setQuickCash: setQuickCash, toggleHelp: toggleHelp};
})();
