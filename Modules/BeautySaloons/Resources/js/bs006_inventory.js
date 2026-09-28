(function(){
    window.BeautySaloonsInventory = {
        recalcRetailLine: function(row){
            var qty = parseFloat(row.querySelector('.bs-qty')?.value || 0);
            var price = parseFloat(row.querySelector('.bs-price')?.value || 0);
            var total = qty * price;
            var totalEl = row.querySelector('.bs-line-total');
            if(totalEl) totalEl.value = total.toFixed(2);
        }
    };
})();
