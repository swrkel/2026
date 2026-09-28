
/* VAT_GLOBAL_MODAL_FALLBACK_V8: keeps VAT Add/Edit modal buttons working even when the page does not load public/js/app.js correctly. */
(function ($) {
    if (!$ || window.__vatGlobalModalFallbackV8) { return; }
    window.__vatGlobalModalFallbackV8 = true;
    $(document).off('click.vat_global_modal_fallback_v8').on('click.vat_global_modal_fallback_v8', '.vat-btn-modal, .btn-vat-modal, .btn-modal', function (e) {
        var $btn = $(this);
        var url = $btn.data('href') || $btn.attr('data-href') || $btn.attr('href');
        if (!url || url === '#') { return true; }
        e.preventDefault();
        e.stopImmediatePropagation();
        if (typeof window.erpOpenAjaxModal === 'function') {
            return window.erpOpenAjaxModal(this);
        }
        var container = $btn.data('container') || $btn.attr('data-container') || '.view_modal';
        if (container.charAt(0) !== '.' && container.charAt(0) !== '#') { container = '.' + container; }
        if ($(container).length === 0) { $('body').append('<div class="modal fade ' + container.substring(1) + '" tabindex="-1" role="dialog"></div>'); }
        $(container).load(url, function () { $(this).modal('show'); });
        return false;
    });
})(window.jQuery);

$(document).on('submit','#vat_quick_add_reference', function(event) {
            // Prevent the default form submission
            event.preventDefault();
            

            // Perform AJAX request
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(), // Serialize form data
                success: function(response) {
                    if(response.success == true){
                        toastr.success(response.msg);
                        
                        var customerId = response.data.id;
                        var customerName = response.data.reference;
                        $('#reference_id').append($('<option>', {
                            value: customerId,
                            text: customerName,
                            selected: true
                        }));
                    
                        $('#reference_id').val(response.data.id).trigger('change');
                        
                        $('.contact_modal').modal('hide');
                    }else{
                        toastr.error(response.msg);
                    }
                    
                    
                    
                },
                error: function(xhr, status, error) {
                    console.error(error); // Log any errors
                }
            });
        });
$(document).on('submit','#vat_quick_add_customer', function(event) {
            // Prevent the default form submission
            event.preventDefault();
            

            // Perform AJAX request
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(), // Serialize form data
                success: function(response) {
                    if(response.success == true){
                        toastr.success(response.msg);
                        
                        console.log(response);
                        
                        var customerId = response.data.id;
                        var customerName = response.data.name;
                        $('#customer_id').append($('<option>', {
                            value: customerId,
                            text: customerName,
                            selected: true
                        }));
                    
                        $('#customer_id').val(response.data.id).trigger('change');
                        
                        $('.contact_modal').modal('hide');
                    }else{
                        toastr.error(response.msg);
                    }
                    
                    
                    
                },
                error: function(xhr, status, error) {
                    console.error(error); // Log any errors
                }
            });
        });