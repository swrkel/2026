(function(){
    var idx=0;
    function recalc(){var total=0;document.querySelectorAll('.rn-line-total').forEach(function(e){total+=parseFloat(e.value||0);});document.getElementById('rnGrandTotal').innerText=total.toFixed(2);} 
    function addLine(){var tbody=document.querySelector('#rnSaleLines tbody');var tr=document.createElement('tr');tr.innerHTML='<td><input class="form-control" name="items['+idx+'][menu_item_name]" required></td><td><input class="form-control" name="items['+idx+'][kitchen_section_id]"></td><td><input type="number" step="0.001" class="form-control rn-qty" name="items['+idx+'][quantity]" value="1" required></td><td><input type="number" step="0.01" class="form-control rn-price" name="items['+idx+'][unit_price]" value="0" required></td><td><input readonly class="form-control rn-line-total" value="0.00"></td><td><button type="button" class="btn btn-danger rn-remove">x</button></td>';tbody.appendChild(tr);idx++;}
    document.addEventListener('click',function(e){if(e.target.id==='rnAddLine'){addLine();} if(e.target.classList.contains('rn-remove')){e.target.closest('tr').remove();recalc();}});
    document.addEventListener('input',function(e){if(e.target.classList.contains('rn-qty')||e.target.classList.contains('rn-price')){var tr=e.target.closest('tr');var q=parseFloat(tr.querySelector('.rn-qty').value||0);var p=parseFloat(tr.querySelector('.rn-price').value||0);tr.querySelector('.rn-line-total').value=(q*p).toFixed(2);recalc();}});
    addLine();
})();
