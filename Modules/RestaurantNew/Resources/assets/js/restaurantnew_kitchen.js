(function ($) {
    'use strict';

    function updateTicketStatus(id, status, reason) {
        $.ajax({
            method: 'POST',
            url: '/restaurant-new/kitchen/tickets/' + id + '/status',
            data: {_token: $('meta[name="csrf-token"]').attr('content'), status: status, cancel_reason: reason || ''},
            success: function () { window.location.reload(); },
            error: function (xhr) { alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to update KOT'); }
        });
    }

    $(document).on('click', '.restaurantnew-kot-status', function () {
        updateTicketStatus($(this).data('id'), $(this).data('status'));
    });

    $(document).on('click', '.restaurantnew-kot-cancel', function () {
        var reason = window.prompt('Cancel reason');
        if (reason !== null) updateTicketStatus($(this).data('id'), 'cancelled', reason);
    });

    $('#restaurantnew_kitchen_search, #restaurantnew_kitchen_status').on('keyup change', function () {
        var search = ($('#restaurantnew_kitchen_search').val() || '').toLowerCase();
        var status = $('#restaurantnew_kitchen_status').val();
        $('.restaurantnew-kot-card').each(function () {
            var textMatch = $(this).text().toLowerCase().indexOf(search) !== -1;
            var statusMatch = !status || $(this).data('status') === status;
            $(this).toggle(textMatch && statusMatch);
        });
    });

    $('#restaurantnew_refresh_kitchen').on('click', function () { window.location.reload(); });
})(jQuery);
