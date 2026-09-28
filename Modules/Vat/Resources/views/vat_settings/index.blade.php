@extends('layouts.app')
@section('title', __('vat::lang.vat_module'))

@section('content')
@php
    $business_id = request()->session()->get('user.business_id')
        ?? request()->session()->get('business.id')
        ?? optional(auth()->user())->business_id;
    $pacakge_details = [];
    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details ?? [];
    }
@endphp

<section class="content main-content-inner vat-settings-page">
    <style>
        .vat-settings-page {
            width: 100%;
            max-width: 100%;
            padding: 14px 16px 34px;
            overflow: visible;
        }
        .vat-settings-page .settlement_tabs {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }
        .vat-settings-page .nav-tabs {
            display: block;
            width: max-content;
            min-width: 100%;
            white-space: nowrap;
            margin: 0;
        }
        .vat-settings-page .nav-tabs > li {
            float: none;
            display: inline-block;
            margin: 0 4px 0 0;
            vertical-align: bottom;
        }
        .vat-settings-page .nav-tabs > li > a {
            margin: 0;
            cursor: pointer;
        }
        .vat-settings-page .tab-content {
            padding-top: 12px;
            clear: both;
        }
        .vat-settings-page .tab-pane > section.content,
        .vat-settings-page .tab-pane > section.main-content-inner {
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .vat-settings-page .table-responsive,
        .vat-settings-page .vat-user-prefix-table-wrap,
        .vat-settings-page .dataTables_wrapper {
            overflow: visible !important;
        }
        .vat-settings-page .dropdown-menu {
            z-index: 1070;
            min-width: 205px;
        }
        .vat-settings-page .dropdown-toggle { margin: 0 !important; }
        .vat-settings-page .modal-body { overflow: visible; }
        .vat-settings-page .select2-container { width: 100% !important; }
        /*
         * S664 (item 1): the Add popup's dropdowns would not open.
         *
         * This was z-index: 10650, but the modal below was raised to 100050 and
         * its dialog to 100051. Select2 appends its dropdown panel with the
         * container's stacking level, so at 10650 the panel was painted BEHIND
         * the modal: the control showed its "open" caret and nothing else
         * appeared, which is exactly the state in the reported screenshot.
         *
         * Raised above the dialog so the panel lands in front of it. The search
         * field inside the panel needs the same treatment or it stays
         * unclickable.
         */
        .select2-container--open { z-index: 100060 !important; }
        .select2-container--open .select2-dropdown,
        .select2-container--open .select2-search--dropdown,
        .select2-container--open .select2-results { z-index: 100061 !important; }

        /*
         * MA-002 (Issue 4) - force the three Add modals to be visible.
         *
         * Your diagnostic proved the modal DOES open:
         *     modal_exists yes(1)   modal_plugin present
         *     modal_opened YES      modal_display block
         *     backdrop_count 1
         * and the video shows nothing on screen, with the page not even
         * dimming. So the modal and its backdrop are both present and both
         * invisible.
         *
         * Only four things can do that to an element that has display:block:
         *     opacity 0, visibility hidden, a transform moving it off-screen,
         *     or a higher z-index element painting over it.
         *
         * Rather than keep testing them one at a time, all four are closed
         * here at once. Combined with the load-time move to <body> further
         * down this file, there is nothing left for an ancestor to do.
         *
         * Scoped to the three modal ids, plus the backdrop, on this page only.
         */
        #vat_report_settings_add_modal.in,
        #vat_user_prefix_add_modal.in,
        #vat_sms_type_add_modal.in,
        #vat_report_settings_add_modal.show,
        #vat_user_prefix_add_modal.show,
        #vat_sms_type_add_modal.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            z-index: 100050 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            overflow: auto !important;
        }

        #vat_report_settings_add_modal .modal-dialog,
        #vat_user_prefix_add_modal .modal-dialog,
        #vat_sms_type_add_modal .modal-dialog {
            transform: none !important;
            opacity: 1 !important;
            visibility: visible !important;
            position: relative !important;
            z-index: 100051 !important;
            margin: 30px auto !important;
        }

        .modal-backdrop,
        .modal-backdrop.in {
            z-index: 100040 !important;
        }

        #vat_report_settings_add_modal .modal-dialog {
            width: 650px;
            max-width: calc(100% - 30px);
        }
        #vat_user_prefix_add_modal .modal-dialog,
        .vat-settings-page .fuel_tank_modal .modal-dialog {
            width: 900px;
            max-width: calc(100% - 30px);
        }
        #vat_sms_type_add_modal .modal-dialog {
            width: 650px;
            max-width: calc(100% - 30px);
        }
        .vat-report-settings-dialog .modal-body {
            padding: 20px 24px 10px;
        }
        .vat-report-settings-dialog .form-group {
            margin-bottom: 16px;
        }
        @media (max-width: 767px) {
            #vat_report_settings_add_modal .modal-dialog,
            #vat_user_prefix_add_modal .modal-dialog,
            #vat_sms_type_add_modal .modal-dialog,
            .vat-settings-page .fuel_tank_modal .modal-dialog {
                width: auto;
                margin: 10px;
            }
        }
    </style>

    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs no-print" id="vat_settings_main_tabs" role="tablist">
                    <li class="active">
                        <a href="#vat_settings" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.vat_report_settings')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#vat_userinvoice_prefixes" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.vat_userinvoice_prefixes')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#vat_userinvoice_smstypes" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.vat_userinvoice_smstypes')</strong>
                        </a>
                    </li>
                    @if(!empty($pacakge_details['vat_linked_accounts']))
                        <li>
                            <a href="#vat_payable_to" data-toggle="tab">
                                <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.vat_payable_to')</strong>
                            </a>
                        </li>
                    @endif
                    @if(!empty($pacakge_details['vat_credit_bill']))
                        <li>
                            <a href="#vat_credit_bill" data-toggle="tab">
                                <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.vat_credit_bill')</strong>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="tab-content" id="vat_settings_main_tab_content">
                <div class="tab-pane active" id="vat_settings">
                    @include('vat::vat_settings.vat_settings')
                </div>
                <div class="tab-pane" id="vat_userinvoice_prefixes">
                    @include('vat::vat_settings.vat_userinvoice_prefixes')
                </div>
                <div class="tab-pane" id="vat_userinvoice_smstypes">
                    @include('vat::vat_settings.vat_userinvoice_smstypes')
                </div>
                @if(!empty($pacakge_details['vat_linked_accounts']))
                    <div class="tab-pane" id="vat_payable_to">
                        @include('vat::vat_settings.vat_payable_to_accounts')
                    </div>
                @endif
                @if(!empty($pacakge_details['vat_credit_bill']))
                    <div class="tab-pane" id="vat_credit_bill">
                        @include('vat::vat_settings.vat_credit_bill')
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="vat_report_settings_add_modal" tabindex="-1" role="dialog">
        @include('vat::vat_settings.settings_add')
    </div>
    <div class="modal fade" id="vat_user_prefix_add_modal" tabindex="-1" role="dialog">
        @include('vat::vat_userinvoice_prefixes.create')
    </div>
    <div class="modal fade" id="vat_sms_type_add_modal" tabindex="-1" role="dialog">
        @include('vat::vat_invoice_smstype.create')
    </div>
    <div class="modal fade fuel_tank_modal" tabindex="-1" role="dialog"></div>
</section>
@endsection

@section('javascript')
    @include('vat::partials.ma002_click_diagnostic')
@include('vat::partials.ma002_modal_backdrop_guard')
@if(!empty(session('status')))
<script>
    @if(!empty(session('status')['success']))
        toastr.success(@json(session('status')['msg']));
    @else
        toastr.error(@json(session('status')['msg']));
    @endif
</script>
@endif

<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script>
/*
 * MA-002 (Issue 4): "User to Invoice Prefix / Add" did nothing when clicked.
 *
 * The console screenshots you sent show NO JavaScript exception on this page.
 * The only errors are the Font Awesome kit returning 403, a camera-permission
 * message and Pusher failing to connect - none of which can stop a click
 * handler. The markup is also identical to the two Add buttons on the same
 * page, and the modal element is present exactly once.
 *
 * That combination points at Bootstrap's declarative data-api not firing,
 * which produces no error at all: the button simply does nothing. Causes
 * include a Bootstrap 5 bundle (which expects data-bs-toggle), a second
 * jQuery instance, or a plugin that stops event propagation on .btn.
 *
 * Rather than wait to identify which, this binds the three Add buttons
 * explicitly. jQuery's .modal() is called directly, so it no longer matters
 * whether the data-api is wired up. If the data-api IS working, the guard
 * below prevents the modal opening twice.
 */
/*
 * MA-002 - MOVE THE MODALS TO <body> ON PAGE LOAD.
 *
 * Your log proves the modal DOES open:
 *     modal_exists yes(1)  modal_plugin present  modal_opened YES
 *     modal_display block  backdrop_count 1
 * but the video shows nothing appears and the page does not even dim.
 * So it opens correctly and is then rendered invisible.
 *
 * The move-to-body added in Parcel 35 never actually ran. It lived inside
 * the click handler, AFTER an early return:
 *
 *     if ($modal.hasClass('in') || $modal.hasClass('show')) { return; }
 *
 * Bootstrap's own data-api is delegated on document and fires FIRST, so by
 * the time my handler ran the modal already carried .in - and it returned
 * before reaching the move. My mistake: I put a one-time structural fix
 * behind a guard designed for a repeated action.
 *
 * Doing it once at page load removes the ordering problem entirely. Whichever
 * path opens the modal afterwards - the data-api or the explicit handler - it
 * is already a direct child of <body>, where a position:fixed modal must live
 * to be immune to an ancestor transform, filter or overflow.
 */
$(function () {
    $('#vat_report_settings_add_modal, #vat_user_prefix_add_modal, #vat_sms_type_add_modal')
        .each(function () {
            var $m = $(this);
            if ($m.parent().get(0) !== document.body) {
                $m.appendTo(document.body);
            }
        });
});

$(document).on('click', '#vat_settings_main_tab_content [data-toggle="modal"][data-target], .vat-settings-add-btn', function (e) {
    var target = $(this).data('target') || $(this).attr('data-target');
    if (!target) {
        return;
    }

    var $modal = $(target);
    if (!$modal.length) {
        console.warn('MA-002: modal ' + target + ' is not present in the DOM.');
        return;
    }

    // Already open (data-api fired first) - do nothing.
    if ($modal.hasClass('in') || $modal.hasClass('show')) {
        return;
    }

    e.preventDefault();

    if (typeof $modal.modal !== 'function') {
        console.error('MA-002: Bootstrap modal plugin is not loaded on this page.');
        return;
    }

    /*
     * MA-002: move the modal to <body> before showing it.
     *
     * Your diagnostic proved the click REACHES this button - it was the
     * topmost element at the pointer (67x36, pointer-events auto) and
     * default_prevented was "no". So the click is fine and the modal simply
     * does not appear.
     *
     * The usual cause is the modal being nested inside a container that
     * breaks position:fixed. A CSS transform, filter, will-change or
     * perspective on ANY ancestor re-bases fixed positioning onto that
     * ancestor, so the modal opens but renders off-screen or with no size -
     * silently, with no error. An ancestor with overflow:hidden does the same
     * by clipping it. These modals currently sit inside <section> inside the
     * page content, several containers deep.
     *
     * Bootstrap modals are designed to live directly under <body>. Moving it
     * there removes every ancestor from the equation at once, so it does not
     * matter which one was responsible.
     *
     * Done once per modal and remembered, so repeat clicks do not re-move it.
     * jQuery preserves bound handlers across appendTo, so the select2
     * initialiser bound to shown.bs.modal, and the form inside, are unaffected.
     */
    if (!$modal.data('ma002MovedToBody') && $modal.parent().get(0) !== document.body) {
        $modal.appendTo(document.body).data('ma002MovedToBody', true);
    }

    $modal.modal('show');
});
</script>
<script>
(function ($) {
    'use strict';

    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var tables = {};

    function notifyAjaxError(xhr) {
        var response = xhr && xhr.responseJSON ? xhr.responseJSON : {};
        var message = response.msg || response.message;
        if (!message && xhr && typeof xhr.responseText === 'string') {
            var text = $.trim(xhr.responseText);
            if (text && text.charAt(0) !== '<' && text.length <= 500) {
                message = text;
            }
        }
        if (!message && response.errors) {
            var keys = Object.keys(response.errors);
            if (keys.length) { message = response.errors[keys[0]][0]; }
        }
        toastr.error(message || @json(__('messages.something_went_wrong')));
    }

    function initSelect2($container) {
        if (!$.fn.select2) { return; }
        ($container || $(document)).find('select.select2').each(function () {
            var $select = $(this);
            if ($select.data('select2')) { return; }
            var $modal = $select.closest('.modal');
            var options = { width: '100%' };
            if ($modal.length) { options.dropdownParent = $modal; }
            $select.select2(options);
        });
    }

    function initTable(key, selector, options) {
        var $table = $(selector);
        if (!$table.length || !$.fn.DataTable) { return null; }
        if ($.fn.dataTable.isDataTable($table[0])) {
            tables[key] = $table.DataTable();
            return tables[key];
        }
        tables[key] = $table.DataTable(options);
        return tables[key];
    }

    function initReportTable() {
        return initTable('report', '#vat_settings_table', {
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: { url: @json(action('\Modules\Vat\Http\Controllers\SettingsController@index')), cache: false },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'created_at', name: 'created_at' },
                { data: 'vat_period', name: 'vat_period' },
                { data: 'effective_date', name: 'effective_date' },
                { data: 'status', name: 'status', searchable: false },
                { data: 'tax_report_name', name: 'tax_report_name' },
                { data: 'username', name: 'users.username' }
            ]
        });
    }

    function initUserPrefixTable() {
        return initTable('userPrefix', '#userinvoice_prefixes_table', {
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: { url: @json(action('\Modules\Vat\Http\Controllers\VatUserInvoicePrefixController@index')), cache: false },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'date_time', name: 'vat_user_invoice_prefixes.date_time' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'username', name: 'users.username' },
                { data: 'prefix_name', name: 'vp.prefix' },
                { data: 'prefix_name2', name: 'vp2.prefix' },
                { data: 'user_created', name: 'uc.username' },
                { data: 'action', searchable: false, orderable: false, className: 'notexport noColvis' }
            ]
        });
    }

    function initSmsTable() {
        return initTable('sms', '#userinvoice_smstypes_table', {
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: { url: @json(action('\Modules\Vat\Http\Controllers\VatInvoiceSmsTypeController@index')), cache: false },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'date_time', name: 'date_time' },
                { data: 'sms_to', name: 'sms_to' },
                { data: 'user_created', name: 'users.username' },
                { data: 'action', searchable: false, orderable: false, className: 'notexport noColvis' }
            ]
        });
    }

    function initPayableTable() {
        return initTable('payable', '#vat_payable_to_table', {
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: { url: @json(action('\Modules\Vat\Http\Controllers\VatPayableToAccountController@index')), cache: false },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'created_at', name: 'created_at' },
                { data: 'type', name: 'type' },
                { data: 'account_name', name: 'account_name' },
                { data: 'amount', name: 'amount' },
                { data: 'user_created', name: 'users.username' },
                { data: 'note', name: 'note' },
                { data: 'action', searchable: false, orderable: false, className: 'notexport noColvis' }
            ]
        });
    }

    function initCreditBillTable() {
        return initTable('creditBill', '#vat_credit_bill_table', {
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: { url: @json(action('\Modules\Vat\Http\Controllers\VatCreditBillController@index')), cache: false },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'created_at', name: 'created_at' },
                { data: 'cg_name', name: 'cg_name' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'linked_accounts', name: 'linked_accounts' },
                { data: 'user_created', name: 'users.username' },
                { data: 'action', searchable: false, orderable: false, className: 'notexport noColvis' }
            ]
        });
    }

    function initPane(selector) {
        if (selector === '#vat_settings') { initReportTable(); }
        if (selector === '#vat_userinvoice_prefixes') { initUserPrefixTable(); }
        if (selector === '#vat_userinvoice_smstypes') { initSmsTable(); }
        if (selector === '#vat_payable_to') { initPayableTable(); }
        if (selector === '#vat_credit_bill') { initCreditBillTable(); }

        window.setTimeout(function () {
            $(selector).find('table').each(function () {
                if ($.fn.dataTable.isDataTable(this)) {
                    $(this).DataTable().columns.adjust();
                }
            });
        }, 0);
    }

    function reloadTable(selector) {
        var node = $(selector)[0];
        if (!node || !$.fn.dataTable.isDataTable(node)) { return; }
        var table = $(node).DataTable();
        if (table.ajax) { table.ajax.reload(null, false); }
    }

    function configureReportPeriod($modal) {
        var $period = $modal.find('select[name="vat_period"]');
        if (!$period.length) { return; }
        var custom = $period.val() === 'custom';
        $modal.find('#custom_fields').toggle(custom);
        $modal.find('[name="report_cycle_starting_date"], [name="report_cycle_ending_date"]')
            .prop('required', custom);
    }

    function openAjaxModal(url, containerSelector) {
        var $modal = $(containerSelector || '.fuel_tank_modal');
        $modal.empty();
        $.ajax({ url: url, method: 'GET', dataType: 'html', cache: false })
            .done(function (html) {
                $modal.html(html).modal({ backdrop: true, keyboard: true, show: true });
                initSelect2($modal);
            })
            .fail(notifyAjaxError);
    }

    $(document).on('click.vatSettingsAjaxModal', '.vat-settings-ajax-modal-trigger', function (event) {
        event.preventDefault();
        openAjaxModal($(this).data('href') || $(this).attr('href'), $(this).data('container'));
    });

    $(document).on('submit.vatSettingsForm', 'form.vat-modal-ajax-form', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();
        var $form = $(this);
        if ($form.data('submitting')) { return; }
        if (this.checkValidity && !this.checkValidity()) {
            this.reportValidity();
            return;
        }

        $form.data('submitting', true);
        var $button = $form.find('button[type="submit"]');
        var original = $button.html();
        $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).done(function (result) {
            if (!result.success) {
                toastr.error(result.msg || @json(__('messages.something_went_wrong')));
                return;
            }
            toastr.success(result.msg);
            var tableSelector = $form.attr('data-vat-reload-table');
            $form.closest('.modal').modal('hide');
            reloadTable(tableSelector);
            if ($form.attr('id') === 'settings_add_form') {
                $form[0].reset();
                configureReportPeriod($('#vat_report_settings_add_modal'));
            }
        }).fail(notifyAjaxError).always(function () {
            $form.data('submitting', false);
            $button.prop('disabled', false).html(original);
        });
    });

    $(document).on('click.vatSettingsDelete', 'a.delete_task', function (event) {
        event.preventDefault();
        var href = $(this).data('href');
        swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true })
            .then(function (confirmed) {
                if (!confirmed) { return; }
                $.ajax({
                    url: href,
                    method: 'DELETE',
                    dataType: 'json',
                    data: { _token: csrfToken }
                }).done(function (result) {
                    result.success ? toastr.success(result.msg) : toastr.error(result.msg);
                    Object.keys(tables).forEach(function (key) {
                        if (tables[key] && tables[key].ajax) { tables[key].ajax.reload(null, false); }
                    });
                }).fail(notifyAjaxError);
            });
    });

    $(function () {
        initSelect2($('.vat-settings-page'));
        initPane('#vat_settings');

        $('#vat_settings_main_tabs a[data-toggle="tab"]')
            .off('shown.bs.tab.vatSettings')
            .on('shown.bs.tab.vatSettings', function (event) {
                var selector = $(event.target).attr('href');
                initPane(selector);
                if (selector === '#vat_userinvoice_prefixes' && tables.userPrefix && tables.userPrefix.ajax) {
                    tables.userPrefix.ajax.reload(null, false);
                }
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, document.title, selector);
                }
            });

        var hash = window.location.hash;
        if (hash && $('#vat_settings_main_tabs a[href="' + hash + '"]').length) {
            $('#vat_settings_main_tabs a[href="' + hash + '"]').tab('show');
        }

        // Refresh the assignment lock state when the page is restored from the
        // browser cache after VAT Invoice2 transactions were removed.
        $(window).off('pageshow.vatSettings').on('pageshow.vatSettings', function () {
            if (tables.userPrefix && tables.userPrefix.ajax) {
                tables.userPrefix.ajax.reload(null, false);
            }
        });

        $('#vat_report_settings_add_modal, #vat_user_prefix_add_modal, #vat_sms_type_add_modal')
            .off('shown.bs.modal.vatSettings')
            .on('shown.bs.modal.vatSettings', function () {
                initSelect2($(this));
                configureReportPeriod($(this));
            });

        $(document).off('change.vatReportPeriod', '#vat_report_settings_add_modal select[name="vat_period"]')
            .on('change.vatReportPeriod', '#vat_report_settings_add_modal select[name="vat_period"]', function () {
                configureReportPeriod($('#vat_report_settings_add_modal'));
            });

        $('.fuel_tank_modal').off('hidden.bs.modal.vatSettingsCleanup')
            .on('hidden.bs.modal.vatSettingsCleanup', function () { $(this).empty(); });
    });
})(jQuery);
</script>
@endsection
