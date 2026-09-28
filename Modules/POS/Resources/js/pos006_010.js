(function () {
    window.POSMultiplePayments = {
        addRow: function (containerSelector) {
            var container = document.querySelector(containerSelector);
            if (!container) return;
            var row = document.createElement('div');
            row.className = 'pos-payment-row';
            row.innerHTML = '<select name="payments[][payment_method]" class="form-control"><option value="cash">Cash</option><option value="card">Card</option><option value="bank_transfer">Bank Transfer</option><option value="wallet">Wallet</option><option value="gift_card">Gift Card</option><option value="customer_credit">Customer Credit</option></select><input name="payments[][amount]" class="form-control" placeholder="Amount"><input name="payments[][reference_no]" class="form-control" placeholder="Reference">';
            container.appendChild(row);
        }
    };
})();
