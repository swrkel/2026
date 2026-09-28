{{--
    MA-002: stale modal backdrop guard.

    WHY THIS EXISTS
    ---------------
    Issue 4 ("Add button does nothing") and Issue 5 ("cannot select dropdowns
    or type in fields") were both reported with clean browser consoles. The
    screenshots you supplied show only three errors, on every page alike:

        - Font Awesome kit bb1c887317.js -> 403   (icons only)
        - "Error accessing camera: NotAllowedError"
        - Pusher: App key not in this cluster

    None of those can stop a click handler, and none is page-specific.

    A stale Bootstrap backdrop produces EXACTLY that signature. .modal-backdrop
    is a full-screen absolutely-positioned div. If a modal is opened and hidden
    in a way that leaves its backdrop behind - a second modal opening before the
    first finishes animating, or a hide() call during the fade - the backdrop
    stays in the DOM and silently swallows every click and focus on the page.
    Nothing is logged, because nothing has failed: the clicks are landing on the
    backdrop instead of the control underneath.

    That single cause fits both symptoms. On VAT Settings the Add button appears
    dead; on Add VAT Invoice 2 no select opens and no field accepts text. Both
    pages carry several modals (contact_modal, contact_modal_noreload,
    route_operations_modal, fuel_tank_modal and the settings add modals), so
    there is ample opportunity for one to leave a backdrop behind.

    WHAT THIS DOES
    --------------
    It removes orphaned backdrops ONLY when no modal is actually open, so a
    legitimately open modal is never disturbed. It runs on load, after each
    modal hides, and on a short retry schedule to catch backdrops left by
    animations that finish after page load.

    HOW TO CONFIRM THE DIAGNOSIS
    ----------------------------
    If this resolves it, the cause is confirmed and the right follow-up is to
    find which modal leaks the backdrop. If it does NOT resolve it, please open
    DevTools > Elements, press Ctrl+F and search for "modal-backdrop". Tell me
    whether any element is found while the page looks idle - that answer either
    confirms or eliminates this cause in one step.
--}}
<script>
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    function anyModalVisible() {
        var visible = false;

        $('.modal').each(function () {
            var $m = $(this);
            // Bootstrap 3/4 use .in, Bootstrap 5 uses .show.
            if ($m.hasClass('in') || $m.hasClass('show') || $m.is(':visible')) {
                visible = true;
                return false;
            }
        });

        return visible;
    }

    function clearOrphanBackdrops(reason) {
        if (anyModalVisible()) {
            return;
        }

        var $backdrops = $('.modal-backdrop');
        if (!$backdrops.length && !$('body').hasClass('modal-open')) {
            return;
        }

        if ($backdrops.length) {
            console.warn(
                'MA-002: removed ' + $backdrops.length +
                ' orphaned modal backdrop(s) that were blocking input (' + reason + ').'
            );
            $backdrops.remove();
        }

        $('body').removeClass('modal-open').css({
            'padding-right': '',
            'overflow': ''
        });
    }

    $(function () {
        clearOrphanBackdrops('page load');

        // Backdrops can be left behind by a fade that completes after load.
        [250, 1000, 2500].forEach(function (delay) {
            window.setTimeout(function () {
                clearOrphanBackdrops('delayed check ' + delay + 'ms');
            }, delay);
        });

        // And by a modal that hides while another is still animating.
        $(document).on('hidden.bs.modal', function () {
            window.setTimeout(function () {
                clearOrphanBackdrops('after modal hide');
            }, 300);
        });
    });
}(window.jQuery));
</script>
