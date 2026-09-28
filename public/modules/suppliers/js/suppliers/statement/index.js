$(document).on('click', '.supplier-ledger-filter-apply', function () {
    const params = new URLSearchParams(window.location.search);
    ['start_date', 'end_date', 'location_id'].forEach(function (field) {
        const value = $('[name="' + field + '"]').val();
        if (value) { params.set(field, value); } else { params.delete(field); }
    });
    window.location.search = params.toString();
});
