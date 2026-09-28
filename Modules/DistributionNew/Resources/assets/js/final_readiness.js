$(document).ready(function () {
    if ($('#disnew_final_readiness_table').length) {
        $('#disnew_final_readiness_table').DataTable();
    }
    $(document).on('click', '.disnew-mark-passed', function () {
        var id = $(this).data('id');
        $.ajax({
            method: 'POST',
            url: '/distribution-new/final-readiness/' + id,
            data: {_token: $('meta[name="csrf-token"]').attr('content'), status: 'passed'},
            success: function () { window.location.reload(); }
        });
    });
});
