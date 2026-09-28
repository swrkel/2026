@extends('layouts.app')

@section('title', 'Customer Register')

@section('content')

<style>
/* CUS-003: Customer Register action dropdown visibility fix */
#customers_table th:first-child,
#customers_table td:first-child {
    min-width: 170px !important;
    width: 170px !important;
    overflow: visible !important;
}
#customers_table,
#customers_table tbody,
#customers_table tr,
#customers_table td {
    overflow: visible !important;
}
.customers-register-table-wrap {
    position: relative !important;
    overflow-x: auto !important;
    overflow-y: visible !important;
    padding-bottom: 90px !important;
}
.customer-actions-dropdown {
    position: relative !important;
    z-index: 50 !important;
}
.customer-actions-dropdown.open,
.customer-actions-dropdown.show {
    z-index: 999999 !important;
}
.customer-actions-menu {
    display: none !important;
    min-width: 245px !important;
    background: #ffffff !important;
    border: 1px solid #dbe7f3 !important;
    border-radius: 12px !important;
    box-shadow: 0 16px 36px rgba(15, 76, 129, 0.22) !important;
    padding: 8px 0 !important;
    z-index: 999999 !important;
}
.customer-actions-menu > li > a {
    padding: 9px 14px !important;
    font-weight: 600 !important;
    white-space: nowrap !important;
}
.customer-actions-menu > li > a:hover {
    background: #eef6ff !important;
    color: #0b5ed7 !important;
}
/*
 * The floating-menu rule lives further down this file, where it is declared
 * position:fixed to match the viewport coordinates the positioning code
 * computes with getBoundingClientRect(). An earlier duplicate here declared
 * position:absolute, which is document-based - two rules for one class, silently
 * resolved by source order. Removed so there is one definition to reason about.
 */

.customers-action-modal-content {
    border-radius: 14px !important;
    overflow: hidden;
}
.customers-action-modal-content .modal-header {
    background: #f8fafc;
    border-bottom: 1px solid #e5edf6;
}
.customers-action-modal-content .modal-title {
    font-weight: 700;
    color: #1f2d3d;
}
.customer_modal .modal-dialog.customers-action-modal-dialog {
    width: 95% !important;
    max-width: 1400px !important;
    margin: 30px auto !important;
}
.customer_modal .modal-content.customers-action-modal-content {
    border-radius: 18px !important;
    overflow: hidden !important;
    border: none !important;
    box-shadow: 0 16px 45px rgba(0,0,0,0.25) !important;
}
.customer_modal .modal-header {
    background: #ffffff !important;
    border-bottom: 1px solid #e5e7eb !important;
    padding: 18px 22px !important;
}
.customer_modal .modal-body {
    max-height: 75vh !important;
    overflow-y: auto !important;
    padding: 20px 22px !important;
}
body.modal-open,
body.customers-modal-open {
    overflow: hidden !important;
}

/* CUS-011: When a Customer action popup opens, the row action dropdown and page header must not remain visible above/under the modal. */
body.customers-modal-open .customer-actions-menu,
body.customers-modal-open .customers-action-menu-floating,
body.customers-modal-open .customer-actions-dropdown.open .dropdown-menu,
body.customers-modal-open .customer-actions-dropdown.show .dropdown-menu {
    display: none !important;
    visibility: hidden !important;
}

.customer_modal {
    z-index: 2147483645 !important;
}
.customer_modal .modal-dialog {
    z-index: 2147483646 !important;
}
.modal-backdrop,
.modal-backdrop.in {
    z-index: 2147483640 !important;
    opacity: 0.92 !important;
    background: #000000 !important;
}

body.customers-modal-open .content-wrapper,
body.customers-modal-open .content,
body.customers-modal-open .main-content,
body.customers-modal-open .wrapper,
body.customers-modal-open .box,
body.customers-modal-open .box-primary,
body.customers-modal-open section.content {
    pointer-events: none !important;
}

body.customers-modal-open .customer_modal,
body.customers-modal-open .customer_modal * {
    pointer-events: auto !important;
}

/* CUS-012: Hard clean overlay to fully hide ERP header cards/top button section behind customer popups. */
body.customers-modal-open::before {
    content: "" !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.92) !important;
    z-index: 2147483639 !important;
    pointer-events: none !important;
}

body.customers-modal-open .customer_modal {
    display: block !important;
}
body.customers-modal-open .main-header,
body.customers-modal-open .header-area,
body.customers-modal-open .main-content > .header-area,
body.customers-modal-open .main-content > header,
body.customers-modal-open .erp-sidebar-open-btn,
body.customers-modal-open .erp-sidebar-close-btn {
    z-index: 1 !important;
}

.customers-register-heading-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
}
.customers-overall-due-card {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-height: 34px;
    padding: 7px 13px;
    border: 1px solid #d8e8ff;
    border-radius: 12px;
    background: linear-gradient(135deg, #eef6ff, #ffffff);
    color: #153f75;
    font-size: 13px;
    font-weight: 700;
    box-shadow: 0 5px 14px rgba(37, 99, 235, .08);
}
.customers-overall-due-card .customers-overall-due-value {
    color: #c2410c;
    font-size: 17px;
    font-weight: 800;
}
.customers-save-success {
    background: #16a34a !important;
    border-color: #15803d !important;
    color: #ffffff !important;
    border-radius: 10px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    box-shadow: 0 8px 20px rgba(22, 163, 74, .22) !important;
}
.customers-action-menu-floating {
    position: fixed !important;
    z-index: 2147483000 !important;
    display: block !important;
    visibility: visible !important;
    max-width: calc(100vw - 16px) !important;
    overflow-x: hidden !important;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    margin: 0 !important;
    background: #ffffff !important;
}
.customers-action-menu-floating > li > a {
    white-space: nowrap !important;
}
@media (max-width: 767px) {
    .customers-register-heading-actions {
        width: 100%;
        justify-content: flex-start;
        margin-top: 8px;
    }
    .customers-overall-due-card {
        flex: 1 1 100%;
    }
}

@media print {
    #customers_table th:first-child,
    #customers_table td:first-child,
    .customer-actions-dropdown,
    [data-customers-grid-toolbar],
    .box-header .pull-right {
        display: none !important;
    }
}

@media (max-width: 768px) {
    .customer_modal .modal-dialog.customers-action-modal-dialog {
        width: 98% !important;
        margin: 10px auto !important;
    }
    .customer_modal .modal-body {
        max-height: 80vh !important;
        padding: 14px !important;
    }
}

</style>


<section class="content-header">
    <h1>
        Customer Register
        <small>Customer Master Records</small>
    </h1>
</section>

<section class="content">

    @if(session('status') && data_get(session('status'), 'success'))
        <div class="alert alert-success customers-save-success" role="alert">
            <i class="fa fa-check-circle"></i>
            {{ data_get(session('status'), 'msg', 'Successfully Saved') }}
        </div>
    @endif

    <div class="box box-primary">

        <div class="box-header with-border clearfix">

            <div class="pull-left">
                <h3 class="box-title">Customer Register</h3>
            </div>

            <div class="pull-right customers-register-heading-actions">
                <div class="customers-overall-due-card" title="Total Due for all customers in this business">
                    <i class="fa fa-balance-scale"></i>
                    <span>All Customers Total Due</span>
                    <span id="customers_overall_total_due" class="customers-overall-due-value">
                        <i class="fa fa-spinner fa-spin"></i>
                    </span>
                </div>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Add Customer
                </a>
            </div>

        </div>

        <div class="box-body">

            @include('customers::partials.erp-ajax-datatable-standard', [
                'table_id' => 'customers_table',
                'default_per_page' => 25
            ])

            <div class="table-responsive customers-register-table-wrap">
                <table id="customers_table" class="table table-bordered table-striped table-hover" width="100%">
                    <thead>
                        <tr>
                            <th>Actions</th>
                            <th>Customer Code</th>
                            <th>Customer Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Credit Limit</th>
                            <th>Total Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-right">Page Total</th>
                            <th class="text-right" id="customers_page_total_due">0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>

    </div>

</section>

<div class="modal fade customer_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>

@endsection

@section('javascript')
<script>
$(document).ready(function () {

    // IS1851: Customer Register search is intentionally request-only. Remove any
    // DataTables state saved by older builds so a browser refresh always starts
    // with the complete customer list.
    function clearCustomerRegisterTableState() {
        ['localStorage', 'sessionStorage'].forEach(function (storageName) {
            try {
                var storage = window[storageName];
                var keys = [];
                for (var i = 0; i < storage.length; i++) {
                    var key = storage.key(i);
                    if (key && key.indexOf('DataTables_customers_table_') === 0) {
                        keys.push(key);
                    }
                }
                keys.forEach(function (key) { storage.removeItem(key); });
            } catch (ignore) {}
        });

        $('[data-customers-grid-search="customers_table"]').val('');
    }

    clearCustomerRegisterTableState();

    var overallDueRequest = null;
    var overallDueCacheKey = 'customers_overall_due_v4_'
        + {{ (int) session()->get('user.business_id') }}
        + '_' + window.location.host;

    function setOverallCustomerDue(value, persist) {
        var amount = parseFloat(value);
        if (isNaN(amount)) {
            return;
        }

        $('#customers_overall_total_due')
            .attr('data-orig-value', amount)
            .text(amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

        if (persist !== false) {
            try {
                window.localStorage.setItem(overallDueCacheKey, JSON.stringify({
                    amount: amount,
                    saved_at: Date.now()
                }));
            } catch (ignore) {}
        }
    }

    function restoreOverallCustomerDue() {
        try {
            var raw = window.localStorage.getItem(overallDueCacheKey);
            if (!raw) return false;

            var cached = JSON.parse(raw);
            if (!cached || typeof cached.amount === 'undefined') return false;

            // Use only a recent last-known value. The fresh server calculation
            // still starts immediately below and silently replaces this value.
            if (cached.saved_at && (Date.now() - cached.saved_at) > 86400000) {
                return false;
            }

            setOverallCustomerDue(cached.amount, false);
            return true;
        } catch (ignore) {
            return false;
        }
    }

    function loadOverallCustomerDue() {
        // Prevent duplicate Total Due requests when the table and modal refresh
        // hooks fire close together.
        if (overallDueRequest) {
            return overallDueRequest;
        }

        overallDueRequest = $.ajax({
            url: "{{ route('customers.register.total_due') }}",
            type: 'GET',
            dataType: 'json',
            cache: false
        }).done(function (response) {
            if (response && response.success) {
                setOverallCustomerDue(response.total_due, true);
            }
        }).fail(function () {
            // Keep the last verified value if one was already restored.
            if (!$('#customers_overall_total_due').attr('data-orig-value')) {
                $('#customers_overall_total_due').text('Unable to load');
            }
        }).always(function () {
            overallDueRequest = null;
        });

        return overallDueRequest;
    }

    // Show the most recently verified figure immediately, then refresh it from
    // the database in the background so accounting accuracy is preserved.
    restoreOverallCustomerDue();
    loadOverallCustomerDue();

    if ($.fn.DataTable.isDataTable('#customers_table')) {
        var existingCustomersTable = $('#customers_table').DataTable();
        if (existingCustomersTable.state && typeof existingCustomersTable.state.clear === 'function') {
            existingCustomersTable.state.clear();
        }
        existingCustomersTable.search('').destroy();
    }

    let customersTable = $('#customers_table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        searchDelay: 400,
        stateSave: false,
        stateLoadCallback: function () { return null; },
        search: { search: '' },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, 'All']],

        ajax: {
            url: "{{ route('customers.index') }}",
            type: "GET"
        },

        dom: 'rtip',


        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'contact_id', name: 'contact_id' },
            { data: 'name', name: 'name' },
            { data: 'mobile', name: 'mobile' },
            { data: 'email', name: 'email' },
            { data: 'credit_limit', name: 'credit_limit', className: 'text-right' },
            { data: 'total_due', name: 'total_due', className: 'text-right', searchable: false },
            { data: 'active', name: 'active', orderable: false, searchable: false }
        ],

        order: [[2, 'asc']],

        initComplete: function () {
            var api = this.api();
            api.search('');
            $('[data-customers-grid-search="customers_table"]').val('');
        },

        drawCallback: function () {
            closeCustomerActionDropdowns();
            var pageTotalDue = 0;
            $('#customers_table tbody .customer-total-due').each(function () {
                var value = parseFloat($(this).attr('data-orig-value'));
                if (!isNaN(value)) {
                    pageTotalDue += value;
                }
            });
            $('#customers_page_total_due').text(pageTotalDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            if (typeof __currency_convert_recursively === 'function') {
                __currency_convert_recursively($('#customers_table'));
            }
        }
    });


    var activeCustomerActionGroup = null;
    var activeCustomerActionMenu = null;

    function restoreFloatingCustomerMenu() {
        if (!activeCustomerActionMenu || !activeCustomerActionMenu.length) {
            activeCustomerActionGroup = null;
            activeCustomerActionMenu = null;
            return;
        }

        activeCustomerActionMenu
            .removeClass('customers-action-menu-floating')
            .removeAttr('style');

        if (activeCustomerActionGroup && activeCustomerActionGroup.length && $.contains(document, activeCustomerActionGroup[0])) {
            activeCustomerActionMenu.appendTo(activeCustomerActionGroup);
            activeCustomerActionGroup.removeClass('open show');
            activeCustomerActionGroup.find('.customers-action-toggle').attr('aria-expanded', 'false');
        } else {
            activeCustomerActionMenu.remove();
        }

        activeCustomerActionGroup = null;
        activeCustomerActionMenu = null;
    }

    function positionFloatingCustomerMenu($button, $menu) {
        if (!$button.length || !$menu.length || !$.contains(document, $button[0])) {
            return;
        }

        var rect = $button[0].getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        var edge = 8;
        var gap = 4;

        // Reset any previous constrained state first so the natural full menu
        // size is measured on every reposition. This lets top, middle and
        // bottom rows all choose the best available space independently.
        $menu.css({
            display: 'block',
            visibility: 'hidden',
            position: 'fixed',
            top: edge + 'px',
            left: edge + 'px',
            minWidth: Math.max(245, Math.ceil(rect.width)) + 'px',
            maxWidth: Math.max(245, viewportWidth - (edge * 2)) + 'px',
            maxHeight: 'none',
            overflowY: 'visible',
            overflowX: 'hidden'
        });

        var menuWidth = Math.ceil($menu.outerWidth());
        var naturalMenuHeight = Math.ceil($menu.outerHeight());
        var viewportMenuHeight = Math.max(180, viewportHeight - (edge * 2));
        var left = Math.max(edge, Math.min(rect.left, viewportWidth - menuWidth - edge));
        var top;

        if (naturalMenuHeight <= viewportMenuHeight) {
            /*
             * The complete menu fits on the screen, so ALWAYS show the full
             * list for every customer row. Prefer below the button; if that
             * would run off-screen, flip above; if neither side alone is big
             * enough, clamp the complete menu inside the viewport rather than
             * clipping it or unnecessarily adding an internal scrollbar.
             */
            var belowTop = rect.bottom + gap;
            var aboveTop = rect.top - naturalMenuHeight - gap;

            if (belowTop + naturalMenuHeight <= viewportHeight - edge) {
                top = belowTop;
            } else if (aboveTop >= edge) {
                top = aboveTop;
            } else {
                top = Math.max(edge, Math.min(belowTop, viewportHeight - naturalMenuHeight - edge));
            }
        } else {
            // The menu itself is taller than the viewport. Keep it completely
            // inside the screen and make only the menu scrollable so no action
            // option is lost or hidden behind the table/container.
            $menu.css({
                maxHeight: viewportMenuHeight + 'px',
                overflowY: 'auto'
            });
            top = edge;
        }

        var renderedHeight = Math.ceil($menu.outerHeight());
        top = Math.max(edge, Math.min(top, viewportHeight - renderedHeight - edge));

        $menu.css({
            left: left + 'px',
            top: top + 'px',
            visibility: 'visible'
        });
    }

    var customerActionMenuPositionFrame = null;

    function repositionActiveCustomerActionMenu() {
        customerActionMenuPositionFrame = null;

        if (!activeCustomerActionMenu || !activeCustomerActionMenu.length ||
            !activeCustomerActionGroup || !activeCustomerActionGroup.length) {
            return;
        }

        // A DataTables redraw can replace the owner row. In that case there is
        // no valid button to follow, so close cleanly rather than leaving an
        // orphan menu in <body>. Normal page/table scrolling does NOT close it.
        if (!$.contains(document, activeCustomerActionGroup[0])) {
            closeCustomerActionDropdowns();
            return;
        }

        var $button = activeCustomerActionGroup.find('.customers-action-toggle').first();
        if (!$button.length || !$.contains(document, $button[0])) {
            closeCustomerActionDropdowns();
            return;
        }

        positionFloatingCustomerMenu($button, activeCustomerActionMenu);
    }

    function scheduleCustomerActionMenuReposition() {
        if (!activeCustomerActionMenu || !activeCustomerActionMenu.length) {
            return;
        }

        if (customerActionMenuPositionFrame !== null) {
            return;
        }

        var raf = window.requestAnimationFrame || function (callback) {
            return window.setTimeout(callback, 16);
        };

        customerActionMenuPositionFrame = raf(repositionActiveCustomerActionMenu);
    }

    function openCustomerActionDropdown($group) {
        var $button = $group.find('.customers-action-toggle').first();
        var $menu = $group.children('.customer-actions-menu').first();
        if (!$button.length || !$menu.length) {
            return;
        }

        if (activeCustomerActionGroup && activeCustomerActionGroup[0] === $group[0]) {
            restoreFloatingCustomerMenu();
            return;
        }

        restoreFloatingCustomerMenu();

        activeCustomerActionGroup = $group;

        /*
         * Remember the owning row ON the menu element.
         *
         * The module-level activeCustomerActionMenu variable is not enough: a
         * DataTables redraw, or a modal opening and closing, can leave a menu
         * sitting in <body> while that variable has moved on. Without a
         * back-reference the sweep below cannot tell which row an orphan
         * belongs to, and its only option is to destroy it - which is how a
         * row's Actions menu could disappear until the page was reloaded.
         */
        activeCustomerActionMenu = $menu.detach().appendTo(document.body)
            .addClass('customers-action-menu-floating')
            .data('customersOwnerGroup', $group);

        $group.addClass('open show');
        $button.attr('aria-expanded', 'true');
        positionFloatingCustomerMenu($button, activeCustomerActionMenu);
    }

    /*
     * Put any stray floating menu back where it belongs.
     *
     * THE BUG THIS FIXES
     *   A menu is detached to <body> while open. Several things could leave one
     *   there after it should have closed - most reliably the modal-close
     *   handler further down, which stripped the menu's inline styles but left
     *   the customers-action-menu-floating class in place.
     *
     *   That class forces position:fixed, display:block and visibility:visible.
     *   With its top/left gone the browser falls back to auto, and the menu
     *   renders as a panel stuck over the table, overlapping the rows beneath -
     *   which is exactly the reported symptom.
     *
     * WHY REATTACH RATHER THAN REMOVE
     *   The previous close() called .remove() on every floating menu. When the
     *   owning row still existed that destroyed its Actions menu for good, and
     *   it stayed gone until the page was reloaded. Each menu now carries a
     *   reference to its row, so it is put back when the row is still there and
     *   only discarded when the row itself has gone.
     */
    function sweepOrphanFloatingCustomerMenus() {
        $('body').children('.customers-action-menu-floating').each(function () {
            var $menu = $(this);
            var $owner = $menu.data('customersOwnerGroup');

            // Strip the presentation first, so the menu cannot render loose
            // even if reattaching fails below.
            $menu.removeClass('customers-action-menu-floating').removeAttr('style');

            if ($owner && $owner.length && $.contains(document, $owner[0])) {
                $menu.appendTo($owner);
                $owner.removeClass('open show')
                    .find('.customers-action-toggle').attr('aria-expanded', 'false');
                return;
            }

            // The row is gone - a redraw happened while the menu was open.
            $menu.remove();
        });
    }

    function closeCustomerActionDropdowns() {
        restoreFloatingCustomerMenu();
        $('.customer-actions-dropdown').removeClass('open show')
            .find('.customers-action-toggle').attr('aria-expanded', 'false');
        sweepOrphanFloatingCustomerMenus();
        $('.dropdown-backdrop').remove();
    }

    /*
     * Close before a DataTables redraw because the owning row is about to be
     * replaced. During normal page/table scrolling, keep the menu open and
     * continuously reposition it from the Action button's current viewport
     * coordinates. This is the S718 requirement: scrolling must not make the
     * dropdown disappear.
     */
    $('#customers_table').off('preDraw.dt.customersActionMenu draw.dt.customersActionMenu')
        .on('preDraw.dt.customersActionMenu', function () {
            closeCustomerActionDropdowns();
        });

    $(window)
        .off('scroll.customersActionMenu resize.customersActionMenu')
        .on('scroll.customersActionMenu resize.customersActionMenu', scheduleCustomerActionMenuReposition);

    $('.customers-register-table-wrap')
        .off('scroll.customersActionMenu')
        .on('scroll.customersActionMenu', scheduleCustomerActionMenuReposition);

    $(document)
        .off('click.customersActionToggle', '.customers-action-toggle')
        .on('click.customersActionToggle', '.customers-action-toggle', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openCustomerActionDropdown($(this).closest('.customer-actions-dropdown'));
        })
        .off('keydown.customersActionToggle', '.customers-action-toggle')
        .on('keydown.customersActionToggle', '.customers-action-toggle', function (e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                openCustomerActionDropdown($(this).closest('.customer-actions-dropdown'));
            } else if (e.key === 'Escape') {
                closeCustomerActionDropdowns();
            }
        });

    $(document)
        .off('click.customersActionOutside')
        .on('click.customersActionOutside', function (e) {
            if ($(e.target).closest('.customers-action-menu-floating, .customers-action-toggle').length) {
                return;
            }
            closeCustomerActionDropdowns();
        })
        .off('keydown.customersActionEscape')
        .on('keydown.customersActionEscape', function (e) {
            if (e.key === 'Escape') {
                closeCustomerActionDropdowns();
            }
        });


    // Start loading the exact action the user points to, then reuse that same
    // in-flight request on click. A short cache also makes reopening an action
    // immediate without preloading every action for every customer row.
    var customerActionHtmlCache = {};
    var customerActionRequests = {};
    var customerActionCacheTtl = 15000;

    function fetchCustomerActionHtml(url) {
        var now = Date.now();
        var cached = customerActionHtmlCache[url];

        if (cached && (now - cached.loadedAt) < customerActionCacheTtl) {
            return $.Deferred().resolve(cached.html).promise();
        }

        if (customerActionRequests[url]) {
            return customerActionRequests[url];
        }

        customerActionRequests[url] = $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            cache: true,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (html) {
            customerActionHtmlCache[url] = {
                html: html,
                loadedAt: Date.now()
            };
        }).always(function () {
            delete customerActionRequests[url];
        });

        return customerActionRequests[url];
    }

    $(document)
        .off('mouseenter.customersActionPrefetch focusin.customersActionPrefetch touchstart.customersActionPrefetch')
        .on('mouseenter.customersActionPrefetch focusin.customersActionPrefetch touchstart.customersActionPrefetch', '.customers-action-popup', function () {
            var url = $(this).attr('href');
            if (url && url !== '#') {
                fetchCustomerActionHtml(url);
            }
        });

    $(document).off('click.customersActionPopup').on('click.customersActionPopup', '.customers-action-popup', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var url = $(this).attr('href');
        if (!url || url === '#') {
            return false;
        }

        closeCustomerActionDropdowns();
        $('body').addClass('customers-modal-open');

        var $modal = $('.customer_modal').first();
        if (!$modal.length) {
            $('body').append('<div class="modal fade customer_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>');
            $modal = $('.customer_modal').first();
        }

        $modal.appendTo('body');
        $modal.html(
            '<div class="modal-dialog modal-lg" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-body text-center" style="padding:35px;">' +
                        '<i class="fa fa-spinner fa-spin"></i> Loading...' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        $('.modal-backdrop').remove();
        $('#customer_ledger_manual_colvis').remove();
        $modal.modal({backdrop: true, keyboard: true, show: true});

        fetchCustomerActionHtml(url).done(function (html) {
            closeCustomerActionDropdowns();
            $('body').addClass('customers-modal-open');
            $modal.html(html).appendTo('body');
            $modal.modal({backdrop: true, keyboard: true, show: true});
            if (window.CustomersLedgerTable && typeof window.CustomersLedgerTable.init === 'function') {
                window.CustomersLedgerTable.init($modal);
            }

            setTimeout(function () {
                if ($.fn.select2) {
                    $modal.find('.select2').each(function () {
                        var $select = $(this);
                        try {
                            if ($select.hasClass('select2-hidden-accessible')) {
                                $select.select2('destroy');
                            }
                        } catch (ignore) {}
                        $select.select2({width: '100%', dropdownParent: $modal.find('.modal-content').first()});
                    });
                }
                if ($.fn.datepicker) {
                    var $customerDatePickers = $modal.find('.datepicker, .customers-action-datepicker, .customers-datepicker');
                    $customerDatePickers.each(function () {
                        var $dateInput = $(this);
                        try {
                            if ($dateInput.data('datepicker')) {
                                $dateInput.datepicker('destroy');
                            }
                        } catch (ignore) {}

                        $dateInput.datepicker({
                            autoclose: true,
                            todayHighlight: true,
                            format: 'mm/dd/yyyy',
                            container: '.customer_modal',
                            zIndexOffset: 20000
                        });
                    });

                    // IS2300: the calendar icon is part of the input group, so it
                    // must explicitly open the Transaction Date picker as well.
                    // Keep this handler on the persistent modal container because
                    // the Edit Customer form itself is replaced through AJAX.
                    $modal
                        .off('click.is2300TransactionDate keydown.is2300TransactionDate', '.customers-open-datepicker')
                        .on('click.is2300TransactionDate keydown.is2300TransactionDate', '.customers-open-datepicker', function (event) {
                            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ' && event.keyCode !== 13 && event.keyCode !== 32) {
                                return;
                            }

                            event.preventDefault();
                            var $dateInput = $(this).siblings('.customers-datepicker').first();
                            if (!$dateInput.length) {
                                return;
                            }

                            try {
                                if (!$dateInput.data('datepicker')) {
                                    $dateInput.datepicker({
                                        autoclose: true,
                                        todayHighlight: true,
                                        format: 'mm/dd/yyyy',
                                        container: '.customer_modal',
                                        zIndexOffset: 20000
                                    });
                                }
                                $dateInput.datepicker('show');
                            } catch (ignore) {
                                $dateInput.trigger('focus');
                            }
                        });
                }
            }, 100);
        }).fail(function (xhr) {
            delete customerActionHtmlCache[url];
            $modal.modal('hide');
            $('body').removeClass('customers-modal-open');
            var msg = 'Unable to open customer action.';
            if (xhr && xhr.status) {
                msg += ' Server status: ' + xhr.status;
            }
            if (typeof toastr !== 'undefined') {
                toastr.error(msg);
            } else {
                alert(msg);
            }
        });

        return false;
    });

    $(document).off('click.customersDeleteAction').on('click.customersDeleteAction', '.customers-delete-action', function (e) {
        e.preventDefault();
        var formId = $(this).data('form-id');
        var submitDelete = function () {
            var form = document.getElementById(formId);
            if (form) {
                form.submit();
            }
        };

        if (typeof swal === 'function') {
            swal({
                title: 'Are you sure?',
                icon: 'warning',
                buttons: true,
                dangerMode: true
            }).then(function (ok) {
                if (ok) { submitDelete(); }
            });
        } else if (confirm('Are you sure?')) {
            submitDelete();
        }

        return false;
    });

    function showCustomerActionNotice(type, message) {
        message = message || (type === 'success' ? 'Saved successfully.' : 'Unable to save.');

        if (typeof toastr !== 'undefined') {
            if (type === 'success') {
                toastr.success(message);
            } else {
                toastr.error(message);
            }
            return;
        }

        if (typeof swal === 'function') {
            swal(type === 'success' ? 'Success' : 'Error', message, type);
            return;
        }

        alert(message);
    }

    function cleanupCustomerActionModal() {
        $('.customer_modal').removeClass('in').attr('aria-hidden', 'true').hide();
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open customers-modal-open').css('padding-right', '');
    }

    $(document).off('submit.customersActionModal').on('submit.customersActionModal', '.customer_modal form', function (e) {
        var $form = $(this);
        if (!$form.attr('action')) {
            return true;
        }

        e.preventDefault();

        var $submit = $form.find('button[type="submit"], input[type="submit"]').first();
        $submit.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method') || 'POST',
            data: $form.serialize(),
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function (response) {
                var succeeded = response && (response.success === 1 || response.success === true || response.success === '1');
                var message = (response && response.msg) ? response.msg : (succeeded ? 'Saved successfully.' : 'Unable to save.');

                if (!succeeded) {
                    showCustomerActionNotice('error', message);
                    return;
                }

                var refreshRegister = response && (response.refresh_register === true || response.refresh_register === 1 || response.refresh_register === '1');

                $('.customer_modal').modal('hide');
                window.setTimeout(function () {
                    cleanupCustomerActionModal();

                    if (refreshRegister) {
                        window.location.reload();
                        return;
                    }

                    showCustomerActionNotice('success', message);
                }, 150);

                customerActionHtmlCache = {};

                if (refreshRegister) {
                    return;
                }

                if ($.fn.DataTable.isDataTable('#customers_table')) {
                    $('#customers_table').DataTable().ajax.reload(null, false);
                }
                loadOverallCustomerDue();
            },
            error: function (xhr) {
                var msg = 'Unable to save.';
                if (xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message)) {
                    msg = xhr.responseJSON.msg || xhr.responseJSON.message;
                }
                showCustomerActionNotice('error', msg);
            },
            complete: function () {
                $submit.prop('disabled', false);
            }
        });

        return false;
    });


    $(document).off('hidden.bs.modal.customerModalCleanup').on('hidden.bs.modal.customerModalCleanup', '.customer_modal', function () {
        $(this).empty();
        cleanupCustomerActionModal();

        /*
         * This used to be:
         *   $('.customer-actions-menu, .customers-action-menu-floating')
         *       .removeAttr('style').removeClass('show');
         *
         * which took the inline coordinates off a floating menu but left the
         * customers-action-menu-floating class on it. That class forces the
         * menu visible and fixed, so stripping its position left it rendered
         * loose over the table - the reported breakage.
         *
         * Closing properly reattaches the menu to its row and drops the class
         * with the styles, so there is nothing left to render loose.
         */
        closeCustomerActionDropdowns();
        $('.customer-actions-menu').removeAttr('style').removeClass('show');
    });

});
</script>
@include('customers::ledger.partials.table_runtime')
@endsection