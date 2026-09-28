$(document).on('click', '.rn-request-status', function () {
    var button = $(this);
    $.post('/restaurant-new/customer-experience/requests/' + button.data('id') + '/status', {
        _token: $('meta[name="csrf-token"]').attr('content'),
        status: button.data('status')
    }).done(function () {
        window.location.reload();
    });
});
