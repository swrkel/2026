(function ($) {
    'use strict';

    $(document).on('shown.bs.tab', '.supplier-profile-tabs a[data-toggle="tab"]', function (event) {
        var target = $(event.target).attr('href');
        if (target) {
            window.localStorage.setItem('supplier_profile_active_tab', target);
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, document.title, window.location.pathname + window.location.search + target);
            }
        }
    });

    $(function () {
        // A direct action-menu link must take priority over the previously saved tab.
        var requestedTab = window.location.hash;
        var activeTab = requestedTab || window.localStorage.getItem('supplier_profile_active_tab');

        if (activeTab && $('.supplier-profile-tabs a[href="' + activeTab + '"]').length) {
            $('.supplier-profile-tabs a[href="' + activeTab + '"]').tab('show');
        }
    });
})(jQuery);
