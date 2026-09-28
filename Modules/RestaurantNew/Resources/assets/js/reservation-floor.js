(function ($) {
    'use strict';
    $(document).on('click', '.rn-table-card', function () {
        $('.rn-table-card').removeClass('selected');
        $(this).addClass('selected');
    });
    window.RestaurantNewReservationFloor = {
        refresh: function () {
            var board = $('#restaurant-new-floor-board');
            if (!board.length) return;
            board.addClass('rn-loading');
            setTimeout(function(){ board.removeClass('rn-loading'); }, 250);
        }
    };
})(jQuery);
