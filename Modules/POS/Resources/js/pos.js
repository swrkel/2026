(function(){
function token(){return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (window.POS_ROUTES||{}).csrf;}
function money(v){return (parseFloat(v || 0)).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});}
function request(url, method, data){
  return fetch(url,{
    method:method,
    credentials:'same-origin',
    headers:{'Content-Type':'application/json','Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':token()},
    body:data?JSON.stringify(data):null
  }).then(function(r){
    return r.json().catch(function(){ return {}; }).then(function(payload){
      if(!r.ok){ throw new Error(payload.message || ('POS request failed ('+r.status+')')); }
      return payload;
    });
  });
}
function renderCart(cart){
  var tbody=document.querySelector('#pos_cart_table tbody'); if(!tbody) return;
  if(!cart.lines || !cart.lines.length){tbody.innerHTML='<tr><td colspan="7" class="text-center muted">Cart is empty</td></tr>';} else {
    tbody.innerHTML=cart.lines.map(function(l){return '<tr><td>'+ (l.product_name || ('Product #'+l.product_id)) +'</td><td class="text-right"><input class="form-control" style="max-width:85px;display:inline-block" value="'+parseFloat(l.quantity).toFixed(3)+'" onchange="POSSales.updateLine('+l.id+', this.value)"></td><td class="text-right">'+money(l.unit_price)+'</td><td class="text-right">'+money(l.discount_amount)+'</td><td class="text-right">'+money(l.tax_amount)+'</td><td class="text-right">'+money(l.line_total)+'</td><td><button class="btn btn-danger btn-xs" onclick="POSSales.removeLine('+l.id+')">Remove</button></td></tr>';}).join('');
  }
  var ids={sum_subtotal:'subtotal',sum_discount:'discount_amount',sum_tax:'tax_amount',sum_total:'total_amount'}; Object.keys(ids).forEach(function(id){var el=document.getElementById(id); if(el) el.textContent=money(cart[ids[id]]);});
  var paid=document.getElementById('paid_amount'); if(paid) paid.value=parseFloat(cart.total_amount||0).toFixed(2);
}
window.POSSales={
  refresh:function(){ if(!window.POS_SALES_ROUTES) return; request(POS_SALES_ROUTES.cart,'GET').then(renderCart); },
  addLine:function(id,price){ request(POS_SALES_ROUTES.addLine,'POST',{product_id:id,quantity:1,unit_price:price}).then(function(res){ if(!res.success){alert(res.message||'Unable to add item');return;} renderCart(res.cart); }); },
  updateLine:function(line,qty){ request(POS_SALES_ROUTES.removeLineBase+'/'+line,'PUT',{quantity:qty}).then(function(res){ if(res.cart) renderCart(res.cart); }); },
  removeLine:function(line){ request(POS_SALES_ROUTES.removeLineBase+'/'+line,'DELETE').then(function(res){ if(res.cart) renderCart(res.cart); }); },
  clearCart:function(){ if(confirm('Clear current POS cart?')) request(POS_SALES_ROUTES.clear,'DELETE').then(function(res){ if(res.cart) renderCart(res.cart); }); },
  setCustomer:function(){ request(POS_SALES_ROUTES.customer,'POST',{customer_id:(document.getElementById('customer_id')?document.getElementById('customer_id').value:''),customer_name:document.getElementById('customer_name').value}).then(function(){alert('Customer updated');}); },
  holdSale:function(){ var note=prompt('Hold note / customer reference',''); if(note===null) return; request(POS_SALES_ROUTES.hold,'POST',{note:note}).then(function(res){ if(res.success) location.reload(); else alert(res.message||'Unable to hold sale'); }); },
  resume:function(id){ request(POS_SALES_ROUTES.resumeBase+'/'+id+'/resume','POST',{}).then(function(res){ if(res.success) location.reload(); else alert(res.message||'Unable to resume'); }); },
  addBarcode:function(){ var b=document.getElementById('pos_product_q').value; if(!b){alert('Enter or scan barcode first');return;} request(POS_SALES_ROUTES.barcode,'POST',{barcode:b}).then(function(res){ if(!res.success){alert(res.message||'Item not found');return;} renderCart(res.cart); document.getElementById('pos_product_q').value=''; }); },
  searchProducts:function(){
    var el=document.getElementById('pos_product_q'); if(!el || !window.POS_SALES_ROUTES)return;
    var grid=document.getElementById('pos_product_grid'); if(!grid)return;
    var q=(el.value||'').trim();
    grid.innerHTML='<div class="empty-state">Searching products...</div>';
    fetch(POS_SALES_ROUTES.search+'?q='+encodeURIComponent(q),{
      headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
      credentials:'same-origin',
      cache:'no-store'
    })
      .then(function(r){
        return r.json().catch(function(){ return {}; }).then(function(payload){
          if(!r.ok){ throw new Error(payload.message || ('Product search failed ('+r.status+')')); }
          return payload;
        });
      })
      .then(function(res){
        var products=(res && Array.isArray(res.products))?res.products:[];
        if(!products.length){grid.innerHTML='<div class="empty-state">No matching POS products found.</div>';return;}
        grid.innerHTML=products.map(function(p){
          var id=parseInt(p.id,10)||0; var price=parseFloat(p.sell_price||0)||0;
          return '<button type="button" class="product-tile" onclick="POSSales.addLine('+id+','+price+')"><strong>'+String(p.name||'Item')+'</strong><span>'+String(p.sku||p.barcode||'')+'</span><em>'+money(price)+'</em><small>Stock: '+parseFloat(p.stock_quantity||0).toFixed(3)+'</small></button>';
        }).join('');
      })
      .catch(function(err){
        grid.innerHTML='<div class="empty-state">'+String(err && err.message ? err.message : 'Unable to load products. Please retry.')+'</div>';
        if(window.console){console.error('POS product search:',err);}
      });
  }
};
})();


/*
 * S-POS: filter the product grid AS THE USER TYPES.
 *
 * The Sale Terminal's search box (#pos_product_q) was only wired to the Search
 * BUTTON - onclick="POSSales.searchProducts()". Nothing listened to the field
 * itself, so typing did nothing until the button was pressed.
 *
 * Note the two pages use different ids: the workspace screen uses
 * #pos_product_search and already debounces on keyup in pos_page_003.js, while
 * this terminal uses #pos_product_q. The behaviour is now the same on both.
 *
 * Debounced at 250ms - the same interval the workspace uses - so a request is
 * sent once the user pauses rather than on every keystroke. A barcode scanner,
 * which types very fast and ends with Enter, therefore fires one search rather
 * than a dozen.
 */
(function () {
    'use strict';

    function bindProductSearch() {
        var field = document.getElementById('pos_product_q');

        if (!field || field.dataset.posLiveSearchBound === '1') {
            return;
        }

        field.dataset.posLiveSearchBound = '1';

        var timer = null;

        field.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                if (window.POSSales && typeof POSSales.searchProducts === 'function') {
                    POSSales.searchProducts();
                }
            }, 250);
        });

        // Enter runs the search immediately - a scanner sends it at the end of
        // the code, and waiting another 250ms would feel like a lag.
        field.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                clearTimeout(timer);

                if (window.POSSales && typeof POSSales.searchProducts === 'function') {
                    POSSales.searchProducts();
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindProductSearch);
    } else {
        bindProductSearch();
    }
}());
