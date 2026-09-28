(function($){
    $(document).on('input', '.bs-wallet-amount, .input_number', function(){
        this.value = this.value.replace(/[^0-9.,-]/g, '');
    });
})(jQuery);
