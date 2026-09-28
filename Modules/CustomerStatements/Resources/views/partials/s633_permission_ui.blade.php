{{--
 |==============================================================================
 | S633 - Customer Statements: a User must not see system errors
 |==============================================================================
 |
 | THE REPORT
 |   Logged in as a User (not admin), /customer-statements showed:
 |     * a browser alert - "DataTables warning: table id=logos_table - Ajax
 |       error. For more information ... http://datatables.net/tn/7"
 |     * red toasts - "Failed / Unauthorized action." (twice)
 |     * a green toast - "Success / Operation completed successfully."
 |
 | WHY
 |   The workspace renders every tab for everyone, and two of them are
 |   settings-only:
 |
 |     #logos                        -> GET /customers/customer-statement-logos
 |     #settings_customer_statements -> GET /customers/customer-statement-settings
 |
 |   Both routes carry middleware('customers.access:settings'). A User with
 |   'view' but not 'settings' gets 403 'Unauthorized action.' from
 |   CustomerPermissionService::authorize().
 |
 |   Those 403s then surfaced twice over:
 |     1. DataTables' default error mode is a raw browser alert().
 |     2. public/js/erp-global-message-system.js listens on every jQuery
 |        ajaxError/ajaxSuccess and turns the payload into a toast - which is
 |        also where the stray green "Success" came from, on a background GET
 |        the user never triggered.
 |
 | WHAT THIS DOES
 |   The permission is decided on the SERVER and handed in as $canSeeSettings.
 |   When the user does not have it:
 |     1. the two settings tabs and their panes are removed, so there is nothing
 |        to click that cannot work;
 |     2. requests to those two endpoints are aborted before they leave the
 |        browser, so no 403 is ever generated - an aborted request carries no
 |        responseJSON, so the global toast bridge stays silent too;
 |     3. if the active tab was one of the removed ones, the first allowed tab
 |        is opened instead.
 |
 |   Regardless of permission, DataTables' alert() is replaced on this page by a
 |   console message. A grid that cannot load should leave an empty grid, not a
 |   modal dialog quoting a URL at the operator.
 |
 | WHY IT IS A SEPARATE FILE
 |   Modules/Customers/Resources/views/customer_statement/index.blade.php is a
 |   large shared view rendered by two modules. This attaches through the
 |   workspace adapter's existing fixes mechanism instead, alongside
 |   is1790_fixes and s564_workspace_fixes, so nothing shared is edited.
 --}}
@php
    $canSeeSettings = (bool) ($canSeeSettings ?? false);
    $settingsOnlyTabs = ['#logos', '#settings_customer_statements'];
    $settingsOnlyEndpoints = [
        '/customers/customer-statement-logos',
        '/customers/customer-statement-settings',
    ];
@endphp
<script id="customer-statements-s633-permission-ui">
(function ($) {
    'use strict';

    if (!$) { return; }

    var CAN_SEE_SETTINGS = {{ $canSeeSettings ? 'true' : 'false' }};
    var SETTINGS_TABS = {!! json_encode($settingsOnlyTabs, JSON_UNESCAPED_SLASHES) !!};
    var SETTINGS_ENDPOINTS = {!! json_encode($settingsOnlyEndpoints, JSON_UNESCAPED_SLASHES) !!};

    /*
     * Never let DataTables raise a browser alert on this page.
     *
     * Registered outside document.ready so it is in place before any grid on
     * the page initialises. 'none' makes DataTables report through its own
     * error event instead; the grid simply stays empty.
     */
    function silenceDataTablesAlerts() {
        if ($.fn && $.fn.dataTable && $.fn.dataTable.ext) {
            $.fn.dataTable.ext.errMode = 'none';
        }
    }

    silenceDataTablesAlerts();
    $(document).ready(silenceDataTablesAlerts);

    $(document).on('error.dt', function (event, settings, techNote, message) {
        // Visible to a developer in the console, invisible to the operator.
        if (window.console && console.warn) {
            console.warn('Customer Statements: a grid could not load.', {
                table: settings && settings.nTable ? settings.nTable.id : null,
                techNote: techNote,
                message: message
            });
        }
    });

    if (CAN_SEE_SETTINGS) {
        return;
    }

    /*
     * Stop the settings requests before they are sent.
     *
     * A prefilter is used rather than unbinding the grids because the grids are
     * initialised inside the shared view's own document.ready handler, which is
     * registered before this file runs. Aborting here is reliable whatever the
     * ordering, and an aborted request produces no responseJSON, so the global
     * message bridge raises no toast for it either.
     */
    $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
        var url = String(options.url || '');

        for (var i = 0; i < SETTINGS_ENDPOINTS.length; i++) {
            if (url.indexOf(SETTINGS_ENDPOINTS[i]) !== -1) {
                jqXHR.abort();
                return;
            }
        }
    });

    $(document).ready(function () {
        var $tabList = $('.customer-statement-is1638-tabs');

        $.each(SETTINGS_TABS, function (index, href) {
            $tabList.find('a[href="' + href + '"]').closest('li').remove();
            $(href).remove();
        });

        // If a removed tab was the active one, fall back to the first that is
        // left rather than showing an empty workspace.
        if (!$tabList.find('li.active').length) {
            $tabList.find('a[data-toggle="tab"]').first().tab('show');
        }
    });
})(window.jQuery);
</script>
