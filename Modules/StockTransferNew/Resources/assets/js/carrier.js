(function($){
    $(document).on('change keyup', '.stn-form-box .input_number', function(){
        var total = 0;
        $('.stn-form-box .input_number').each(function(){ total += parseFloat($(this).val() || 0); });
        $('.stn-carrier-total-preview').text(total.toFixed(4));
    });
})(jQuery);
