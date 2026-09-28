(function ($) {
    'use strict';

    function toggleVariationTab() {
        var type = $('#product_type').val();
        var $tab = $('a[href="#product_variations_tab"]').closest('li');
        if (type === 'variable') {
            $tab.show();
        } else {
            $tab.hide();
        }
    }

    $(document).on('change', '#product_type', toggleVariationTab);
    $(document).ready(function () {
        if ($.fn.select2) {
            $('.product-module .select2').select2({ width: '100%' });
        }
        toggleVariationTab();
    });
})(jQuery);
