(function ($) {
    'use strict';

    function syncProfit() {
        var purchase = ProductModule.parseNumber($('#default_purchase_price').val());
        var selling = ProductModule.parseNumber($('#default_sell_price').val());
        var margin = purchase > 0 ? ((selling - purchase) / purchase) * 100 : 0;
        $('#profit_percent').val(ProductModule.formatNumber(margin, 2));
    }

    function syncSellingFromMargin() {
        var purchase = ProductModule.parseNumber($('#default_purchase_price').val());
        var margin = ProductModule.parseNumber($('#profit_percent').val());
        if (purchase > 0) {
            $('#default_sell_price').val(ProductModule.formatNumber(purchase + (purchase * margin / 100), 2));
        }
    }

    $(function () {
        $('.product-form-tabs a[data-toggle="tab"]').on('shown.bs.tab', function () {
            localStorage.setItem('product_form_active_tab', $(this).attr('href'));
        });

        var activeTab = localStorage.getItem('product_form_active_tab');
        if (activeTab && $('.product-form-tabs a[href="' + activeTab + '"]').length) {
            $('.product-form-tabs a[href="' + activeTab + '"]').tab('show');
        }

        $('#default_purchase_price, #default_sell_price').on('keyup change', syncProfit);
        $('#profit_percent').on('keyup change', syncSellingFromMargin);
        syncProfit();
    });
})(jQuery);
