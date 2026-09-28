(function ($) {
    'use strict';

    window.RestaurantNewPOS = {
        orderType: 'dine_in',
        currentOrder: null,
        lines: [],

        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;
            $(document).on('click', '.restaurantnew-order-type', function () {
                $('.restaurantnew-order-type').removeClass('active btn-primary').addClass('btn-default');
                $(this).addClass('active btn-primary').removeClass('btn-default');
                self.orderType = $(this).data('type');
                $('#restaurantnew_order_type').text($(this).text().trim());
            });

            $(document).on('click', '.restaurantnew-menu-item-card', function () {
                self.addItemFromCard($(this));
            });
        },

        addItemFromCard: function ($card) {
            var line = {
                menu_item_id: $card.data('id'),
                item_name: $card.data('name'),
                qty: 1,
                unit_price: parseFloat($card.data('price') || 0)
            };
            line.line_total = line.qty * line.unit_price;
            this.lines.push(line);
            this.renderLines();
        },

        renderLines: function () {
            var html = '';
            var subtotal = 0;
            this.lines.forEach(function (line) {
                subtotal += line.line_total;
                html += '<div class="restaurantnew-order-line"><span>' + line.item_name + ' x ' + line.qty + '</span><strong>' + line.line_total.toFixed(4) + '</strong></div>';
            });
            $('#restaurantnew_order_lines').html(html || '<div class="restaurantnew-muted">No items added</div>');
            $('#restaurantnew_subtotal').text(subtotal.toFixed(4));
            $('#restaurantnew_grand_total').text(subtotal.toFixed(4));
        }
    };

    $(document).ready(function () {
        RestaurantNewPOS.init();
    });
})(jQuery);
