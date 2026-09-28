{{--
    Minimal JavaScript bundle for /superadmin/business.
    The standard bundle loads reporting, POS, date-range, editor, Dropzone,
    Pusher, PDF and DataTables assets that this page never uses.
--}}
<script src="{{ asset('bootstrap/js/bootstrap.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('plugins/toastr/toastr.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('plugins/sweetalert/sweetalert.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/AdminLTE-app.js?v=' . $asset_v) }}"></script>

<script>
(function ($) {
    'use strict';

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Preserve the existing Add Subscription modal without loading the full
    // tenant application JavaScript bundle.
    $(document).on('click', '.btn-modal[data-href]', function (event) {
        event.preventDefault();
        var $button = $(this);
        var url = $button.attr('data-href');
        var selector = $button.attr('data-container') || '.view_modal';
        var $modal = $(selector).first();

        if (!url || !$modal.length || $button.data('loading')) {
            return;
        }

        $button.data('loading', true).prop('disabled', true);
        $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center" style="padding:35px;"><i class="fa fa-spinner fa-spin fa-2x"></i></div></div></div>').modal('show');

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'html',
            cache: false,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (html) {
            $modal.html(html);
        }).fail(function (xhr, textStatus) {
            $modal.modal('hide');
            if (window.toastr) {
                /* S-705: say WHICH failure. "Unable to open the form" covered a
                   timeout, a 403 and a 500 alike, so nobody could tell a slow
                   network from a missing permission. */
                var why = xhr && xhr.status
                    ? 'Unable to open the form (' + xhr.status + ').'
                    : 'Unable to open the form (' + (textStatus || 'no response') + ').';
                toastr.error(why);
                if (window.console) { console.error('Form load failed', xhr && xhr.status, url); }
            }
        }).always(function () {
            $button.data('loading', false).prop('disabled', false);
        });
    });

    $(document).on('click', 'a.link_confirmation', function (event) {
        event.preventDefault();
        var href = $(this).attr('href');
        swal({
            title: @json(__('messages.sure')),
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (confirmed) {
            if (confirmed) {
                window.location.href = href;
            }
        });
    });
})(jQuery);
</script>


{{-- Page-specific Super Admin scripts (Manage Side Bar modal, package actions, etc.). --}}
@yield('javascript')
@yield('javascript-banner')
@stack('javascript')
