@extends('distribution::layouts.app')
@section('title', __('distribution::lang.settings'))

<style>
    .select2 {
        width: 100% !important;
    }

/* DSB-001 / DSB-007: Keep Distribution Settings modals above sidebar/topbar overlays.
   Includes User To Route add/edit modals so they are not greyed/inactive behind backdrop. */
.modal.distribution-setting-modal,
.modal.view_modal,
#vehicleModal,
#prefixModal,
#discountModal,
#addUserRouteMapModal,
#editUserRouteMapModal {
    z-index: 100050 !important;
}

#addUserRouteMapModal .modal-dialog,
#editUserRouteMapModal .modal-dialog {
    z-index: 100051 !important;
}

#addUserRouteMapModal .select2-container,
#editUserRouteMapModal .select2-container,
.select2-container--open {
    z-index: 100053 !important;
}

.modal-backdrop {
    z-index: 100040 !important;
}

/* DSB-006: Ensure Distribution Settings Add buttons are always clickable above toolbar/card overlays */
#distribution-settings-page .box-tools,
#distribution-settings-page .box-tools .btn-modal,
#distribution-settings-page button.btn-modal,
#distribution-settings-page a.btn-modal {
    position: relative !important;
    z-index: 100060 !important;
    pointer-events: auto !important;
}
#distribution-settings-page .widget-header,
#distribution-settings-page .box-header,
#distribution-settings-page .box-title {
    pointer-events: auto !important;
}


/* 7998: keep Settings tab area stable and stop page jump/shake on tab switch */
#distribution-settings-page .tab-content {
    min-height: 560px !important;
    overflow-anchor: none !important;
}
#distribution-settings-page .tab-pane {
    min-height: 520px !important;
}
#distribution-settings-page .nav-tabs > li > a {
    min-height: 42px !important;
}
#distribution-settings-page .btn-modal[disabled],
#distribution-settings-page .btn-modal.disabled {
    opacity: 1 !important;
    pointer-events: none !important;
}


/* DIST-328: Settings pages should not show the collapse/minus box button.
   It was hiding table headers/page details and caused the reported blank areas. */
#distribution-settings-page .btn-box-tool[data-widget="collapse"] {
    display: none !important;
}
#distribution-settings-page .box-body {
    display: block !important;
}

</style>

<style>
/* ZIP 062 - Keep Distribution Settings aligned with the GLOBAL ERP professional toolbar standard.
   This styling is intentionally page-safe while the global app.css applies the same
   toolbar design to all DataTable pages across all modules. */
#distribution-settings-page .dataTables_wrapper .dt-buttons {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 10px !important;
    background: #ffffff !important;
    border: 1px solid #e7edf5 !important;
    border-radius: 18px !important;
    padding: 12px 14px !important;
    margin: 0 0 12px 0 !important;
    box-shadow: 0 10px 28px rgba(15, 76, 129, 0.08) !important;
}

#distribution-settings-page .dataTables_wrapper .dt-buttons button,
#distribution-settings-page .dataTables_wrapper .dt-buttons .btn,
#distribution-settings-page button.dt-button,
#distribution-settings-page a.dt-button {
    border: 0 !important;
    border-radius: 12px !important;
    background: linear-gradient(135deg, #0b5ed7 0%, #06b6d4 100%) !important;
    color: #ffffff !important;
    padding: 9px 14px !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    box-shadow: 0 8px 18px rgba(11, 94, 215, 0.20) !important;
}

#distribution-settings-page .dataTables_length,
#distribution-settings-page .dataTables_filter {
    margin: 8px 0 14px 0 !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    color: #334155 !important;
}

#distribution-settings-page .dataTables_length select,
#distribution-settings-page .dataTables_filter input {
    height: 42px !important;
    border: 1px solid #d9e3ef !important;
    border-radius: 14px !important;
    padding: 8px 14px !important;
    background: #ffffff !important;
    box-shadow: 0 6px 18px rgba(15, 76, 129, 0.06) !important;
    font-size: 15px !important;
}

#distribution-settings-page .dataTables_filter input {
    min-width: 270px !important;
}

#distribution-settings-page .dataTables_wrapper .dt-buttons .buttons-colvis,
#distribution-settings-page button.buttons-colvis {
    background: linear-gradient(135deg, #7c3aed, #2563eb) !important;
}
#distribution-settings-page .dataTables_wrapper .dt-buttons .buttons-csv,
#distribution-settings-page button.buttons-csv {
    background: linear-gradient(135deg, #0f766e, #06b6d4) !important;
}
#distribution-settings-page .dataTables_wrapper .dt-buttons .buttons-excel,
#distribution-settings-page button.buttons-excel {
    background: linear-gradient(135deg, #15803d, #22c55e) !important;
}
#distribution-settings-page .dataTables_wrapper .dt-buttons .buttons-pdf,
#distribution-settings-page button.buttons-pdf {
    background: linear-gradient(135deg, #dc2626, #f97316) !important;
}
#distribution-settings-page .dataTables_wrapper .dt-buttons .buttons-print,
#distribution-settings-page button.buttons-print {
    background: linear-gradient(135deg, #334155, #0f172a) !important;
}


/* ZIP 063 - Reliable professional column visibility popup */
#distribution-settings-page .erp-global-colvis-menu {
    position: absolute !important;
    z-index: 99999999 !important;
    min-width: 260px !important;
    max-width: 340px !important;
    background: #ffffff !important;
    border: 1px solid #dbe7f3 !important;
    border-radius: 16px !important;
    box-shadow: 0 18px 45px rgba(15, 76, 129, 0.24) !important;
    padding: 10px !important;
}
#distribution-settings-page .erp-global-colvis-menu-title {
    font-size: 13px !important;
    font-weight: 800 !important;
    color: #0f4c81 !important;
    padding: 8px 10px 10px 10px !important;
    border-bottom: 1px solid #edf2f7 !important;
    margin-bottom: 6px !important;
}
#distribution-settings-page .erp-global-colvis-menu label {
    display: flex !important;
    align-items: center !important;
    gap: 9px !important;
    padding: 9px 10px !important;
    border-radius: 10px !important;
    color: #334155 !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    margin: 0 0 3px 0 !important;
}
#distribution-settings-page .erp-global-colvis-menu label:hover {
    background: #eef6ff !important;
    color: #0b5ed7 !important;
}
#distribution-settings-page .erp-global-colvis-menu input {
    margin: 0 !important;
}

</style>

@section('content')

    <section class="content-header" id="distribution-settings-page">
        <div class="row">
            <div class="col-md-12 dip_tab">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        <li class="@if (empty(session('status.tab'))) active @endif" style="margin-left: 20px;">
                            <a style="font-size:13px;" href="#provinces" id="provinces-link" class="" data-toggle="tab">
                                <i class="fa fa-superpowers"></i> <strong>Provinces</strong>
                            </a>
                        </li>
                        <li class=" @if (session('status.tab') == 'districts') active @endif">
                            <a style="font-size:13px;" href="#districts" id="districts-link" data-toggle="tab">
                                <i class="fa fa-user"></i> <strong>Districts</strong>
                            </a>
                        </li>

                        <li class=" @if (session('status.tab') == 'areas') active @endif">
                            <a style="font-size:13px;" href="#areas" id="areas-link" data-toggle="tab">
                                <i class="fa fa-user-secret"></i> <strong>Areas</strong>
                            </a>
                        </li>

                        <li class=" @if (session('status.tab') == 'routes') active @endif">
                            <a style="font-size:13px;" href="#routes" id="routes-link" data-toggle="tab">
                                <i class="fa fa-user-secret"></i> <strong>Routes</strong>
                            </a>
                        </li>
                        <li class=" @if (session('status.tab') == 'user_routes') active @endif">
                            <a style="font-size:13px;" href="#user_routes" id="user_routes-link" data-toggle="tab">
                                <i class="fa fa-link"></i> <strong>User To Routes</strong>
                            </a>
                        </li>

                        <li class="@if (session('status.tab') == 'prefix') active @endif">
                            <a style="font-size:13px;" href="#prefix" id="prefix-link" data-toggle="tab">
                                <i class="fa fa-hashtag"></i> <strong>Prefix & Starting No</strong>
                            </a>
                        </li>

                        <li class="@if (session('status.tab') == 'vehicles') active @endif">
                            <a style="font-size:13px;" href="#vehicles" id="vehicles-link" data-toggle="tab">
                                <i class="fa fa-truck"></i> <strong>Vehicles</strong>
                            </a>
                        </li>

                        <li class="@if (session('status.tab') == 'discounts') active @endif">
                            <a style="font-size:13px;" href="#discounts" id="discounts-link" data-toggle="tab">
                                <i class="fa fa-percent"></i> <strong>Discounts</strong>
                            </a>
                        </li>
                        <li class=" @if (session('status.tab') == 'free_issue') active @endif">
                            <a style="font-size:13px;" href="#free_issue" id="free_issue-link" data-toggle="tab">
                                <i class="fa fa-user-secret"></i> <strong>Free Issue</strong>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

<div class="tab-content">
    {{-- DIST-328: Server-render all Distribution Settings tab bodies.
         This prevents a blank settings page if lazy tab ajax/javascript fails and
         makes Add buttons/table headers available immediately. --}}
    <div class="tab-pane @if (empty(session('status.tab'))) active @endif" id="provinces">
        @include('distribution::settings.provinces.index')
    </div>
    <div class="tab-pane @if (session('status.tab') == 'districts') active @endif" id="districts">
        @include('distribution::settings.districts.index')
    </div>
    <div class="tab-pane @if (session('status.tab') == 'areas') active @endif" id="areas">
        @include('distribution::settings.areas.index')
    </div>
    <div class="tab-pane @if (session('status.tab') == 'routes') active @endif" id="routes">
        @include('distribution::settings.routes.index')
    </div>
    <div class="tab-pane @if (session('status.tab') == 'user_routes') active @endif" id="user_routes">
        @include('distribution::settings.routes.user_maps.index')
    </div>
    <div class="tab-pane @if (session('status.tab') == 'prefix') active @endif" id="prefix">
        @include('distribution::settings.prefix.index')
    </div>
    <div class="tab-pane @if (session('status.tab') == 'vehicles') active @endif" id="vehicles">
        @include('distribution::settings.vehicles.index')
    </div>
    <div class="tab-pane @if (session('status.tab') == 'discounts') active @endif" id="discounts">
        @include('distribution::settings.discounts.index')
    </div>
    <div class="tab-pane @if (session('status.tab') == 'free_issue') active @endif" id="free_issue">
        @include('distribution::settings.free_issue.index')
    </div>
</div>

<div class="modal fade view_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>

    </section>

@endsection

@section('javascript')
<script>

// ------------------------------------------------------------------
// Distribution Settings Add/Edit Modal Fix - DSB-002
// ------------------------------------------------------------------
// Permanent fix for ALL Add/Edit buttons in Distribution > Settings.
// Why: global sidebar / toolbar click handlers can swallow normal click events.
// This handler listens on pointerdown + mousedown + click in capture phase,
// opens the modal directly, and prevents duplicate AJAX calls.
(function() {
    'use strict';

    var page = document.getElementById('distribution-settings-page');
    if (!page || !window.jQuery) {
        return;
    }

    var activeRequestKey = null;
    var lastHandledAt = 0;

    function isDistributionSettingsButton(element) {
        if (!element || !element.closest) {
            return null;
        }

        var button = element.closest('#distribution-settings-page .btn-modal, #distribution-settings-page [data-href]');
        if (!button) {
            return null;
        }

        var $button = $(button);
        var href = $button.attr('data-href') || $button.data('href') || $button.attr('href');

        if (!href || href === '#' || href.indexOf('javascript:') === 0) {
            return null;
        }

        return button;
    }

    function ensureModalContainer(containerSelector) {
        var selector = containerSelector || '.view_modal';
        var $modal = $(selector).first();

        if ($modal.length) {
            $modal.appendTo('body');
            return $modal;
        }

        if (selector.charAt(0) === '#') {
            var id = selector.substring(1).replace(/[^A-Za-z0-9_\-]/g, '');
            $('body').append(
                '<div class="modal fade distribution-setting-modal" id="' + id + '" tabindex="-1" role="dialog" aria-hidden="true"></div>'
            );
            return $('#' + id).first();
        }

        $('body').append(
            '<div class="modal fade view_modal distribution-setting-modal" tabindex="-1" role="dialog" aria-hidden="true"></div>'
        );

        return $('.view_modal').last();
    }

    function showLoadingModal($modal) {
        $modal.html(
            '<div class="modal-dialog modal-lg" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-body text-center" style="padding:35px;">' +
                        '<i class="fa fa-spinner fa-spin"></i> Loading form...' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        $modal.appendTo('body');

        if ($.fn.modal) {
            $modal.modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });
        } else {
            $modal.show();
        }
    }

    function initialiseModalControls($modal) {
        if ($.fn.select2) {
            $modal.find('.select2, .select2-search').each(function() {
                var $select = $(this);

                try {
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }
                } catch (err) {}

                $select.select2({
                    width: '100%',
                    dropdownParent: $modal.find('.modal-content').first().length ? $modal.find('.modal-content').first() : $modal
                });
            });
        }

        if ($.fn.datepicker) {
            $modal.find('.datepicker').datepicker({
                autoclose: true,
                format: typeof moment_date_format !== 'undefined' ? moment_date_format : 'yyyy-mm-dd'
            });
        }
    }

    window.openDistributionSettingsModal = function(button, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) {
                event.stopImmediatePropagation();
            }
        }

        var now = Date.now();
        var $button = $(button);
        var url = $button.attr('data-href') || $button.data('href') || $button.attr('href');
        var container = $button.attr('data-container') || $button.data('container') || '.view_modal';

        if (!url || url === '#') {
            if (typeof toastr !== 'undefined') {
                toastr.error('Unable to open form. Missing form URL.');
            } else {
                alert('Unable to open form. Missing form URL.');
            }
            return false;
        }

        var requestKey = url + '|' + container;
        if (activeRequestKey === requestKey || (now - lastHandledAt < 250 && activeRequestKey !== null)) {
            return false;
        }

        activeRequestKey = requestKey;
        lastHandledAt = now;

        var $modal = ensureModalContainer(container);
        showLoadingModal($modal);

        $button.prop('disabled', true).addClass('disabled');

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            cache: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(html) {
                $modal.html(html).appendTo('body');

                if ($.fn.modal) {
                    $modal.modal({
                        backdrop: 'static',
                        keyboard: false,
                        show: true
                    });
                } else {
                    $modal.show();
                }

                setTimeout(function() {
                    initialiseModalControls($modal);
                }, 100);
            },
            error: function(xhr) {
                if ($.fn.modal) {
                    $modal.modal('hide');
                } else {
                    $modal.hide();
                }

                var message = 'Unable to open form.';
                if (xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message)) {
                    message = xhr.responseJSON.msg || xhr.responseJSON.message;
                } else if (xhr.status) {
                    message = 'Unable to open form. Server status: ' + xhr.status;
                }

                if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                } else {
                    alert(message);
                }
            },
            complete: function() {
                $button.prop('disabled', false).removeClass('disabled');
                setTimeout(function() {
                    activeRequestKey = null;
                }, 300);
            }
        });

        return false;
    };

    function distributionSettingsModalEventHandler(event) {
        var button = isDistributionSettingsButton(event.target);
        if (!button) {
            return;
        }

        // Only hijack actual modal buttons/links, not filter selects or normal tab links.
        if (!$(button).hasClass('btn-modal') && !$(button).attr('data-container')) {
            return;
        }

        window.openDistributionSettingsModal(button, event);
        return false;
    }

    // Capture-phase handlers beat global ERP/sidebar handlers that may swallow clicks.
    document.addEventListener('click', distributionSettingsModalEventHandler, true);

    // jQuery fallback for browsers that do not fully support pointer events.
    $(document)
        .off('click.distributionSettingsModalFinal')
        .on('click.distributionSettingsModalFinal', '#distribution-settings-page .btn-modal', function(e) {
            return window.openDistributionSettingsModal(this, e);
        });
})();

$(document).ready(function() {
    console.log('Settings JS loaded');

    // ── Lazy-load registry ────────────────────────────────────────────────
    var tables = {};
    var loadedTabs = {};

    function loadHtmlIntoPane(tabKey, html) {
        var $pane = $('#' + tabKey);
        if (!$pane.length) return;

        var $tmp = $('<div>').html(html);
        var scripts = [];
        $tmp.find('script').each(function() {
            scripts.push($(this).text());
            $(this).remove();
        });

        $pane.html($tmp.html());

        scripts.forEach(function(code) {
            if (code && code.trim()) {
                $.globalEval(code);
            }
        });

        if ($pane.find('.select2').length) {
            $pane.find('.select2').select2({ width: '100%' });
        }
    }

    function ensureTabContentLoaded(tabKey) {
        var $pane = $('#' + tabKey);
        if (!$pane.length) return $.Deferred().resolve().promise();
        if (loadedTabs[tabKey]) return $.Deferred().resolve().promise();

        // DIST-328: If tab content was already rendered by Blade, do not
        // replace it with an ajax spinner/blank response. Just mark it loaded
        // and allow DataTable initialization to continue.
        if ($.trim($pane.html()).length > 0) {
            loadedTabs[tabKey] = true;
            return $.Deferred().resolve().promise();
        }

        loadedTabs[tabKey] = true;
        $pane.html('<div class="text-center" style="padding:20px;"><i class="fa fa-spinner fa-spin"></i></div>');

        var url = '{{ route('distribution.settings.tab', ['tab' => '__TAB__']) }}'.replace('__TAB__', tabKey);
        console.log('Loading tab from: ' + url);
        return $.get(url)
            .done(function(html) {
                console.log('Tab loaded successfully: ' + tabKey);
                loadHtmlIntoPane(tabKey, html);
            })
            .fail(function(xhr) {
                console.error('Failed to load tab: ' + tabKey, xhr);
                loadedTabs[tabKey] = false;
                $pane.html('<div class="alert alert-danger">Unable to load content. Status: ' + xhr.status + '</div>');
            });
    }

    function initTable(id, config) {
        if (tables[id]) return tables[id];
        tables[id] = $(id).DataTable(config);
        return tables[id];
    }

    // ── DataTable configs ─────────────────────────────────────────────────
    var configs = {
        provinces: {
            processing: true, serverSide: true, aaSorting: [[0,'desc']],
            ajax: { url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionProvincesController@index') }}' },
            @include('distribution::partials.datatable_export_button')
            columns: [
                { data: 'action',     searchable: false, orderable: false },
                { data: 'name',       name: 'name' },
                { data: 'added_by_user',   name: 'users.username' },
                { data: 'created_at', name: 'created_at' }
            ]
        },
        districts: {
            processing: true, serverSide: true, aaSorting: [[0,'desc']],
            ajax: {
                url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionDistrictsController@index') }}',
                data: function(d) { d.province_id = $('#districts_province_id').val(); }
            },
            @include('distribution::partials.datatable_export_button')
            columns: [
                { data: 'action',        searchable: false, orderable: false },
                { data: 'name',          name: 'name' },
                { data: 'province_name', name: 'province_name' },
                { data: 'added_by_user',      name: 'users.username' },
                { data: 'date',          name: 'date' }
            ]
        },
        areas: {
            processing: true, serverSide: true, aaSorting: [[0,'desc']],
            ajax: {
                url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionAreasController@index') }}',
                data: function(d) {
                    d.province_id = $('#area_province_id').val();
                    d.district_id = $('#area_district_id').val();
                }
            },
            @include('distribution::partials.datatable_export_button')
            columns: [
                { data: 'action',        searchable: false, orderable: false },
                { data: 'name',          name: 'name' },
                { data: 'district_name', name: 'district_name' },
                { data: 'province_name', name: 'province_name' },
                { data: 'added_by_user',      name: 'users.username' },
                { data: 'date',          name: 'date' }
            ]
        },
        routes: {
            processing: true, serverSide: true, aaSorting: [[0,'desc']],
            ajax: {
                url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionRoutesController@index') }}',
                data: function(d) {
                    d.province_id = $('#route_province_id').val();
                    d.district_id = $('#route_district_id').val();
                    d.area_id     = $('#route_area_id').val();
                }
            },
            @include('distribution::partials.datatable_export_button')
            columns: [
                { data: 'action',        searchable: false, orderable: false },
                { data: 'route_no',      name: 'id' },
                { data: 'name',          name: 'name' },
                { data: 'district_name', name: 'distribution_districts.name' },
                { data: 'province_name', name: 'distribution_provinces.name' },
                { data: 'area_name',     name: 'distribution_areas.name' },
                { data: 'added_by_user', name: 'users.username' },
                { data: 'date',          name: 'distribution_routes.created_at' }
            ]
        },
        prefix: {
            processing: true, serverSide: true, aaSorting: [[0,'desc']],
            ajax: { url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionNumberingPrefixController@index') }}' },
            @include('distribution::partials.datatable_export_button')
            columns: [
                { data: 'numbering_type', name: 'numbering_type' },
                { data: 'prefix',         name: 'prefix' },
                { data: 'starting_no',    name: 'starting_no' },
                { data: 'current_no',     name: 'current_no' },
                { data: 'added_by_user',  name: 'users.username' },
                { data: 'action',         searchable: false, orderable: false }
            ]
        },
        vehicles: {
            processing: true, serverSide: true,
            ajax: { url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionVehiclesController@index') }}', type: 'GET' },
            order: [[0,'desc']],
            columns: [
                { data: 'vehicle_no',                    name: 'vehicle_no' },
                { data: 'vehicle_type',                  name: 'vehicle_type' },
                { data: 'vehicle_brand',                 name: 'vehicle_brand' },
                { data: 'vehicle_model',                 name: 'vehicle_model' },
                { data: 'revenue_license_renewal_date',  name: 'revenue_license_renewal_date' },
                { data: 'starting_meter',                name: 'starting_meter' },
                { data: 'added_by_user',                 name: 'users.username' },
                { data: 'action',                        name: 'action', orderable: false, searchable: false }
            ]
        },
        discounts: {
            processing: true, serverSide: true, aaSorting: [[0,'desc']],
            ajax: { url: '{{ action('\Modules\Distribution\Http\Controllers\DiscountController@index') }}' },
            @include('distribution::partials.datatable_export_button')
            columns: [
                { data: 'date_time',        name: 'date_time' },
                { data: 'category_name',    name: 'category_name' },
                { data: 'sub_category_name',name: 'sub_category_name' },
                { data: 'product_name',     name: 'product_name' },
                { data: 'unit_name',        name: 'unit_name' },
                { data: 'qty',              name: 'qty' },
                { data: 'discount_type',    name: 'discount_type' },
                { data: 'max_discount',     name: 'max_discount' }
            ]
        }
    };

    // ── Tab → table mapping ───────────────────────────────────────────────
    var tabTableMap = {
        'provinces-link': { id: '#provinces_table', key: 'provinces' },
        'districts-link': { id: '#districts_table', key: 'districts' },
        'areas-link':     { id: '#areas_table',     key: 'areas' },
        'routes-link':    { id: '#routes_table',    key: 'routes' },
        'user_routes-link': { id: null,             key: 'user_routes' },
        'prefix-link':    { id: '#prefix_table',    key: 'prefix' },
        'vehicles-link':  { id: '#vehicles_table',  key: 'vehicles' },
        'discounts-link': { id: '#discount_table',  key: 'discounts' },
        'free_issue-link':{ id: '#free_issue_table',key: 'free_issue' }
    };

    // ── Init select2 ─────────────────────────────────────────────────────
    $(".select2").select2();

    // 7998: prevent Bootstrap tab anchors from changing scroll position.
    $(document).on('click.distributionSettingsStableTabs', '#distribution-settings-page a[data-toggle="tab"]', function(e) {
        e.preventDefault();
    });

    // ── Lazy init on tab click ────────────────────────────────────────────
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var linkId = $(e.target).attr('id');
        var map    = tabTableMap[linkId];
        if (!map) return;

        ensureTabContentLoaded(map.key).done(function() {
            if (map.key === 'free_issue') {
                if (typeof free_issue_table !== 'undefined' && !tables['#free_issue_table']) {
                    tables['#free_issue_table'] = free_issue_table;
                }
                return;
            }
            if (map.id && $(map.id).length) {
                initTable(map.id, configs[map.key]);
            }
        });
    });


    // ── Activate and load the correct tab on load ───────────────────────
    var activePage = "{{ session('page') }}";
    var $targetLink = $('#provinces-link'); // Default

    if (activePage && activePage !== '') {
        var $link = $('#' + activePage + '-link');
        if ($link.length) {
            $targetLink = $link;
        }
    }
    
    $targetLink.tab('show');
    
    // Force load + initialise the DataTable for the active/default tab.
    // Bootstrap does not always fire shown.bs.tab when the tab is already active,
    // so the table must be initialized here after the lazy HTML has loaded.
    var targetId = $targetLink.attr('id');
    var config = tabTableMap[targetId];
    if (config) {
        console.log('Ensuring initial tab is loaded: ' + config.key);
        ensureTabContentLoaded(config.key).done(function() {
            if (config.key === 'free_issue') {
                if (typeof free_issue_table !== 'undefined' && !tables['#free_issue_table']) {
                    tables['#free_issue_table'] = free_issue_table;
                }
                return;
            }

            if (config.id && $(config.id).length) {
                initTable(config.id, configs[config.key]);
            }
        });
    }

    // ── Filter reloads ────────────────────────────────────────────────────
    $('#districts_province_id').on('change', function() {
        if (tables['#districts_table']) tables['#districts_table'].ajax.reload();
    });

    $('#area_province_id, #area_district_id').on('change', function() {
        if (tables['#areas_table']) tables['#areas_table'].ajax.reload();
    });

    $('#route_province_id, #route_district_id, #route_area_id').on('change', function() {
        if (tables['#routes_table']) tables['#routes_table'].ajax.reload();
    });

    // ── Vehicle form submit ───────────────────────────────────────────────
    $(document).on('submit', '#vehicles_add_form, #vehicles_edit_form', function(e) {
        e.preventDefault();
        var form       = $(this);
        var formMethod = form.find('input[name="_method"]').val() || form.attr('method') || 'POST';

        $.ajax({
            url:  form.attr('action'),
            type: formMethod === 'PUT' ? 'POST' : formMethod,
            data: form.serialize(),
            success: function(response) {
                $('#vehicleModal').modal('hide');
                if (tables['#vehicles_table']) tables['#vehicles_table'].ajax.reload();
                toastr.success(response.message || 'Vehicle saved successfully!');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errorMsg = '';
                    $.each(xhr.responseJSON.errors, function(k, v) { errorMsg += v[0] + '<br>'; });
                    toastr.error(errorMsg);
                } else {
                    toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error saving vehicle');
                }
            }
        });
    });


    // 7998: Save District modal forms by AJAX so District records are saved
    // without a full page reload from inside the modal.
    $(document)
        .off('submit.distributionBasicSettingsForms')
        .on('submit.distributionBasicSettingsForms', '#districts_add_form, #districts_edit_form', function(e) {
            e.preventDefault();

            var form = $(this);
            var submitBtn = form.find('button[type="submit"]').first();
            var originalText = submitBtn.html();
            var formMethod = (form.find('input[name="_method"]').val() || form.attr('method') || 'POST').toUpperCase();
            var requestType = formMethod === 'GET' ? 'GET' : (formMethod === 'PUT' ? 'POST' : formMethod);

            if (form.data('submitting')) {
                return false;
            }
            form.data('submitting', true);
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: form.attr('action'),
                type: requestType,
                data: form.serialize(),
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(result) {
                    if (result && result.success) {
                        toastr.success(result.msg || '{{ __('lang_v1.success') }}');
                        $('.view_modal').modal('hide');

                        if (tables['#provinces_table']) tables['#provinces_table'].ajax.reload(null, false);
                        if (tables['#districts_table']) tables['#districts_table'].ajax.reload(null, false);
                        if (tables['#areas_table']) tables['#areas_table'].ajax.reload(null, false);
                        if (tables['#routes_table']) tables['#routes_table'].ajax.reload(null, false);
                    } else {
                        toastr.error((result && result.msg) ? result.msg : '{{ __('messages.something_went_wrong') }}');
                    }
                },
                error: function(xhr) {
                    var message = '{{ __('messages.something_went_wrong') }}';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.msg) {
                            message = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            message = '';
                            $.each(xhr.responseJSON.errors, function(k, v) {
                                message += (Array.isArray(v) ? v[0] : v) + '<br>';
                            });
                        }
                    }
                    toastr.error(message);
                },
                complete: function() {
                    form.data('submitting', false);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });

            return false;
        });

    // ── Vehicle delete ────────────────────────────────────────────────────
    $(document).on('click', '.delete_vehicle', function(e) {
        e.preventDefault();
        var id  = $(this).data('id');
        var url = '{{ action('\Modules\Distribution\Http\Controllers\DistributionVehiclesController@destroy', ':id') }}'.replace(':id', id);

        swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true }).then(function(ok) {
            if (!ok) return;
            $.ajax({
                method: 'DELETE', url: url, dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        if (tables['#vehicles_table']) tables['#vehicles_table'].ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error deleting vehicle');
                }
            });
        });
    });

    // ── Delete button (provinces/districts/areas/routes) ──────────────────
    $(document).on('click', 'a.delete_button', function(e) {
        e.preventDefault();
        var href = $(this).data('href');
        swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true }).then(function(ok) {
            if (!ok) return;
            $.ajax({
                method: 'DELETE', url: href, dataType: 'json',
                success: function(result) {
                    if (result.success) { toastr.success(result.msg); } else { toastr.error(result.msg); }
                    if (tables['#provinces_table']) tables['#provinces_table'].ajax.reload();
                    if (tables['#districts_table']) tables['#districts_table'].ajax.reload();
                    if (tables['#areas_table'])     tables['#areas_table'].ajax.reload();
                    if (tables['#routes_table'])    tables['#routes_table'].ajax.reload();
                }
            });
        });
    });

    // ── Prefix delete ─────────────────────────────────────────────────────
    $(document).on('click', '.delete_prefix', function(e) {
        e.preventDefault();
        var url = '{{ action('\Modules\Distribution\Http\Controllers\DistributionNumberingPrefixController@destroy', ':id') }}'.replace(':id', $(this).data('id'));
        swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true }).then(function(ok) {
            if (!ok) return;
            $.ajax({
                method: 'DELETE', url: url, dataType: 'json',
                success: function(result) {
                    if (result.success) { toastr.success(result.msg); if (tables['#prefix_table']) tables['#prefix_table'].ajax.reload(); }
                    else { toastr.error(result.msg); }
                },
                error: function(xhr) { toastr.error(xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error deleting prefix'); }
            });
        });
    });

    // ── Prefix form submit ────────────────────────────────────────────────
    $(document).on('submit', '#prefix_add_form, #prefix_edit_form', function(e) {
        e.preventDefault();
        var form       = $(this);
        var submitBtn  = form.find('button[type="submit"]');
        var origText   = submitBtn.html();
        var formMethod = form.find('input[name="_method"]').val() || form.attr('method') || 'POST';

        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.ajax({
            url: form.attr('action'), method: formMethod === 'PUT' ? 'POST' : formMethod,
            data: form.serialize(), dataType: 'json',
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    $('#prefixModal').modal('hide');
                    if (tables['#prefix_table']) tables['#prefix_table'].ajax.reload();
                } else { toastr.error(result.msg); }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(k, v) { toastr.error(v[0]); });
                } else {
                    toastr.error(xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error saving prefix');
                }
            },
            complete: function() { submitBtn.prop('disabled', false).html(origText); }
        });
    });

    // ── Free Issue form submit ────────────────────────────────────────────
    $(document).on('submit', '#freeIssueForm, #freeIssueEditForm', function(e) {
        e.preventDefault();
        var form     = $(this);
        var submitBtn= form.find('button[type="submit"]');
        var origText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $.ajax({
            url: form.attr('action'), method: 'POST',
            data: form.serialize(), dataType: 'json',
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    $('.view_modal').modal('hide');
                    if (typeof free_issue_table !== 'undefined') free_issue_table.ajax.reload();
                } else { toastr.error(result.msg || 'Error saving Free Issue'); }
            },
            error: function(xhr) { toastr.error(xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error saving Free Issue'); },
            complete: function() { submitBtn.prop('disabled', false).html(origText); }
        });
    });

}); // end ready

// ── Discount Modal IIFE ───────────────────────────────────────────────────
(function() {
    var isUpdatingProducts      = false;
    var isUpdatingSubCategories = false;

    function getJSON(url, params) {
        params = params || {};
        var query = new URLSearchParams(params).toString();
        return fetch(url + (query ? '?' + query : ''), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(function(r) { return r.json(); });
    }

    function getSelect2Value($select) {
        var val = $select.val();
        return Array.isArray(val) ? (val.length > 0 ? val[0] : '') : (val || '');
    }

    function loadProducts() {
        if (isUpdatingProducts) return;
        var categoryId    = getSelect2Value($('#category_select'));
        var subCategoryId = getSelect2Value($('#sub_category_select'));
        if (categoryId    === 'all') categoryId    = '';
        if (subCategoryId === 'all') subCategoryId = '';

        isUpdatingProducts = true;
        var params = {};
        if (categoryId)    params.category_id     = categoryId;
        if (subCategoryId) params.sub_category_id = subCategoryId;

        getJSON('{{ route('distribution.discounts.products') }}', params).then(function(data) {
            var arr = Array.isArray(data) ? data : Object.values(data);
            $('.product_select').each(function() {
                var $select      = $(this);
                var currentValue = getSelect2Value($select);
                if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                $select.empty();
                arr.forEach(function(item) {
                    $select.append('<option value="' + (item.id||'') + '" data-unit-id="' + (item.unit_id||'') + '">' + (item.name||'') + '</option>');
                });
                $select.select2({ placeholder: 'Please Select', allowClear: true, width: '100%', minimumResultsForSearch: 0 });
                if (currentValue && $select.find('option[value="' + currentValue + '"]').length) {
                    $select.val(currentValue).trigger('change.select2');
                }
            });
            isUpdatingProducts = false;
        }).catch(function() { isUpdatingProducts = false; });
    }

    function loadSubCategories(categoryId) {
        var $sub         = $('#sub_category_select');
        var currentValue = getSelect2Value($sub);
        isUpdatingSubCategories = true;

        getJSON('{{ url('distribution/discounts/subcategories') }}', { category_id: categoryId || '' }).then(function(data) {
            if ($sub.hasClass('select2-hidden-accessible')) $sub.select2('destroy');
            $sub.empty().append('<option value="">All</option>');
            if (data && data.length) {
                $.each(data, function(i, item) { $sub.append('<option value="' + item.id + '">' + item.name + '</option>'); });
            }
            $sub.select2({ placeholder: 'All', allowClear: true, width: '100%', minimumResultsForSearch: 0 });
            $sub.val(currentValue && $sub.find('option[value="' + currentValue + '"]').length ? currentValue : '').trigger('change.select2');
            isUpdatingSubCategories = false;
        }).catch(function() { isUpdatingSubCategories = false; });
    }

    $(document).on('shown.bs.modal', '#discountModal', function() {
        $('#category_select, #sub_category_select').select2({ placeholder: 'All', allowClear: true, width: '100%', minimumResultsForSearch: 0 });
        loadSubCategories('');
        loadProducts();
    });

    $(document).on('change', '#category_select', function() {
        if (isUpdatingSubCategories) return;
        var v = getSelect2Value($(this));
        loadSubCategories(v === 'all' ? '' : v);
        loadProducts();
    });

    $(document).on('change', '#sub_category_select', function() {
        if (isUpdatingSubCategories) return;
        loadProducts();
    });

    $(document).on('click', '#add_product_row', function() {
        var row = '<tr>' +
            '<td><select name="product_ids[]" class="form-control select2-search product_select"></select></td>' +
            '<td><select name="unit_ids[]" class="form-control select2-search"><option value="">Select</option>' +
            '@foreach ($units as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach' +
            '</select></td>' +
            '<td><input type="number" name="qty[]" class="form-control" step="{{ '0.' . str_repeat('0', $quantity_precision - 1) . '1' }}" min="0"></td>' +
            '<td><select name="discount_type[]" class="form-control"><option value="fixed">Fixed</option><option value="percentage">Percentage</option></select></td>' +
            '<td><input type="number" name="max_discount[]" class="form-control" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" min="0"></td>' +
            '<td><button type="button" class="btn btn-danger remove_row"><i class="fa fa-trash"></i></button></td>' +
            '</tr>';
        $('#product_table tbody').append(row);
        $('#product_table tbody tr:last .select2-search').select2({ placeholder: 'Please Select', allowClear: true, width: '100%', minimumResultsForSearch: 0 });
        loadProducts();
    });

    $(document).on('click', '.remove_row', function() { $(this).closest('tr').remove(); });

    $(document).on('change', '.product_select', function() {
        var $row       = $(this).closest('tr');
        var $unitSelect= $row.find('select[name="unit_ids[]"]');
        var productId  = $(this).val();
        if ($unitSelect.hasClass('select2-hidden-accessible')) $unitSelect.select2('destroy');
        $unitSelect.empty().append('<option value="">Select</option>');

        if (!productId) {
            @foreach ($units as $id => $name)
                $unitSelect.append('<option value="{{ $id }}">{{ $name }}</option>');
            @endforeach
            $unitSelect.select2({ placeholder: 'Select', allowClear: true, width: '100%', minimumResultsForSearch: 0 });
            return;
        }

        getJSON('{{ route('distribution.discounts.product-units') }}', { product_id: productId }).then(function(data) {
            if ($unitSelect.hasClass('select2-hidden-accessible')) $unitSelect.select2('destroy');
            $unitSelect.empty().append('<option value="">Select</option>');
            if (data && data.length) {
                $.each(data, function(i, u) { $unitSelect.append('<option value="' + u.id + '">' + u.name + '</option>'); });
                $unitSelect.val(data[0].id);
            }
            $unitSelect.select2({ placeholder: 'Select', allowClear: true, width: '100%', minimumResultsForSearch: 0 }).trigger('change.select2');
        }).catch(function() {
            @foreach ($units as $id => $name)
                $unitSelect.append('<option value="{{ $id }}">{{ $name }}</option>');
            @endforeach
            $unitSelect.select2({ placeholder: 'Select', allowClear: true, width: '100%', minimumResultsForSearch: 0 });
        });
    });

    $(document).on('submit', '#discountModal form', function(e) {
        e.preventDefault();
        var form     = $(this);
        var submitBtn= form.find('button[type="submit"]');
        var origText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $.ajax({
            url: form.attr('action'), method: 'POST', data: form.serialize(), dataType: 'json',
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    $('#discountModal').modal('hide');
                    // reload discount table if initialized
                    if (window._dssTableRegistry && window._dssTableRegistry['#discount_table']) {
                        window._dssTableRegistry['#discount_table'].ajax.reload();
                    }
                    form[0].reset();
                    $('#product_table tbody').empty();
                } else { toastr.error(result.msg); }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(k, v) { toastr.error(v[0]); });
                } else { toastr.error(xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error saving discount'); }
            },
            complete: function() { submitBtn.prop('disabled', false).html(origText); }
        });
    });
})();

// ── Free Issue filters ────────────────────────────────────────────────────
$('#filter_category').on('change', function() {
    var ids = $(this).val() || [];
    $('#filter_subcategory').empty();
    if (!ids.length) return;
    $.get('/distribution/free-issues/subcategories/' + ids.join(','), function(res) {
        $.each(res, function(id, name) { $('#filter_subcategory').append(new Option(name, id)); });
    });
});

$('#applyFilters').on('click', function() {
    if (typeof free_issue_table !== 'undefined') free_issue_table.ajax.reload();
});

$(document).on('change', '.toggle-status', function() {
    $.post('/distribution/free-issues/toggle/' + $(this).data('id'), { _token: '{{ csrf_token() }}' }, function() {
        if (typeof free_issue_table !== 'undefined') free_issue_table.ajax.reload(null, false);
    });
});

$(document).on('click', '.user-logs', function() {
    var id = $(this).data('id');
    $.get('/distribution/free-issues/logs/' + id, function(res) {
        var html = '<ul>';
        res.forEach(function(r) { html += '<li>' + r.created_at + ' - ' + r.user.username + ' - ' + r.action + '</li>'; });
        html += '</ul>';
        $('.view_modal').html(html).modal('show');
    });
});

// ------------------------------------------------------------------
// ZIP 063 - Permanent Column Visibility Fix for Distribution Settings
// ------------------------------------------------------------------
// Uses the DataTables API directly instead of relying on the native
// Buttons collection popup, which can be blocked/hidden by page wrappers.
(function() {
    function getDataTableFromButton(button) {
        var $btn = $(button);
        var $wrapper = $btn.closest('.dataTables_wrapper');

        if ($wrapper.length && $wrapper.attr('id')) {
            var tableId = $wrapper.attr('id').replace(/_wrapper$/, '');
            var $table = $('#' + tableId);
            if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
                return { table: $table.DataTable(), tableId: tableId, wrapper: $wrapper };
            }
        }

        var $visibleTable = $('#distribution-settings-page .tab-pane.active table.dataTable:visible').first();
        if ($visibleTable.length && $.fn.DataTable && $.fn.DataTable.isDataTable($visibleTable)) {
            return { table: $visibleTable.DataTable(), tableId: $visibleTable.attr('id'), wrapper: $visibleTable.closest('.dataTables_wrapper') };
        }

        return null;
    }

    function showColumnVisibilityMenu(button) {
        var info = getDataTableFromButton(button);
        if (!info || !info.table) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Column visibility is not ready. Please wait a moment and try again.');
            }
            return;
        }

        $('.erp-global-colvis-menu').remove();

        var $menu = $('<div class="erp-global-colvis-menu"></div>');
        $menu.append('<div class="erp-global-colvis-menu-title"><i class="fa fa-columns"></i> Column Visibility</div>');

        info.table.columns().every(function(index) {
            var column = this;
            var title = $(column.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) {
                title = 'Column ' + (index + 1);
            }

            var checked = column.visible() ? 'checked' : '';
            var $item = $(
                '<label>' +
                    '<input type="checkbox" class="erp-global-colvis-toggle" data-table-id="' + info.tableId + '" data-column="' + index + '" ' + checked + '> ' +
                    '<span>' + $('<div>').text(title).html() + '</span>' +
                '</label>'
            );

            $menu.append($item);
        });

        $('body').append($menu);

        var offset = $(button).offset();
        var left = offset.left;
        var top = offset.top + $(button).outerHeight() + 8;
        var menuWidth = $menu.outerWidth();
        var windowWidth = $(window).width();

        if (left + menuWidth > windowWidth - 20) {
            left = Math.max(12, windowWidth - menuWidth - 20);
        }

        $menu.css({ top: top, left: left });
    }

    $(document).on('click', '#distribution-settings-page .buttons-colvis, #distribution-settings-page button.buttons-colvis, #distribution-settings-page a.buttons-colvis', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) {
            e.stopImmediatePropagation();
        }
        showColumnVisibilityMenu(this);
        return false;
    });

    $(document).on('change', '.erp-global-colvis-toggle', function() {
        var tableId = $(this).data('table-id');
        var columnIndex = $(this).data('column');
        var $table = $('#' + tableId);

        if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            $table.DataTable().column(columnIndex).visible($(this).is(':checked'), false);
            $table.DataTable().columns.adjust().draw(false);
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.erp-global-colvis-menu, .buttons-colvis').length) {
            $('.erp-global-colvis-menu').remove();
        }
    });
})();



// ------------------------------------------------------------------
// DSB-006 TRUE FINAL: Distribution Settings Add/Edit buttons
// ------------------------------------------------------------------
// This is intentionally placed at the end and overrides older/broken
// handlers. It works for lazy-loaded tab HTML, DataTable redraws and
// global sidebar/toolbar click handlers.
(function () {
    'use strict';

    if (!window.jQuery) { return; }

    var loadingKey = null;

    function getModalTarget($btn) {
        var selector = $btn.attr('data-container') || $btn.data('container') || '.view_modal';
        var $modal = $(selector).first();

        if (!$modal.length) {
            if (selector.charAt(0) === '#') {
                var id = selector.substring(1).replace(/[^A-Za-z0-9_\-]/g, '');
                $('body').append('<div id="' + id + '" class="modal fade view_modal distribution-setting-modal" tabindex="-1" role="dialog" aria-hidden="true"></div>');
                $modal = $('#' + id).first();
            } else {
                $('body').append('<div class="modal fade view_modal distribution-setting-modal" tabindex="-1" role="dialog" aria-hidden="true"></div>');
                $modal = $('.view_modal').last();
            }
        }

        $modal.appendTo('body');
        return $modal;
    }

    function initialiseDistributionModal($modal) {
        setTimeout(function () {
            if ($.fn.select2) {
                $modal.find('select.select2, select.select2-search, .select2').each(function () {
                    var $el = $(this);
                    try {
                        if ($el.hasClass('select2-hidden-accessible')) {
                            $el.select2('destroy');
                        }
                    } catch (ignore) {}
                    $el.select2({
                        width: '100%',
                        dropdownParent: $modal.find('.modal-content').first().length ? $modal.find('.modal-content').first() : $modal
                    });
                });
            }
            if ($.fn.datepicker) {
                $modal.find('.datepicker').datepicker({ autoclose: true });
            }
        }, 120);
    }

    window.openDistributionSettingsModal = function (button, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) {
                event.stopImmediatePropagation();
            }
        }

        var $btn = $(button);
        var url = $btn.attr('data-href') || $btn.data('href') || $btn.attr('href');

        if (!url || url === '#' || String(url).indexOf('javascript:') === 0) {
            if (typeof toastr !== 'undefined') { toastr.error('Unable to open form. Missing form URL.'); }
            return false;
        }

        var $modal = getModalTarget($btn);
        var key = url + '|' + ($btn.attr('data-container') || '.view_modal');
        if (loadingKey === key) { return false; }
        loadingKey = key;

        $modal.html(
            '<div class="modal-dialog modal-lg" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-body text-center" style="padding:35px;">' +
                        '<i class="fa fa-spinner fa-spin"></i> Loading form...' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        if ($.fn.modal) {
            $modal.modal({ backdrop: 'static', keyboard: false, show: true });
        } else {
            $modal.show();
        }

        $btn.prop('disabled', true).addClass('disabled');

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            cache: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (html) {
                $modal.html(html).appendTo('body');
                if ($.fn.modal) {
                    $modal.modal({ backdrop: 'static', keyboard: false, show: true });
                } else {
                    $modal.show();
                }
                initialiseDistributionModal($modal);
            },
            error: function (xhr) {
                if ($.fn.modal) { $modal.modal('hide'); } else { $modal.hide(); }
                var msg = 'Unable to open form.';
                if (xhr && xhr.status) { msg += ' Server status: ' + xhr.status; }
                if (xhr && xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message)) {
                    msg = xhr.responseJSON.msg || xhr.responseJSON.message;
                }
                if (typeof toastr !== 'undefined') { toastr.error(msg); } else { alert(msg); }
            },
            complete: function () {
                $btn.prop('disabled', false).removeClass('disabled');
                setTimeout(function () { loadingKey = null; }, 250);
            }
        });

        return false;
    };

    function findButtonFromEvent(event) {
        var target = event.target;
        var btn = target && target.closest ? target.closest('#distribution-settings-page .btn-modal[data-href]') : null;
        if (btn) { return btn; }

        // Fallback for rare overlay cases: use pointer coordinates.
        if (event.clientX !== undefined && event.clientY !== undefined) {
            var el = document.elementFromPoint(event.clientX, event.clientY);
            if (el && el.closest) {
                return el.closest('#distribution-settings-page .btn-modal[data-href]');
            }
        }
        return null;
    }

    function modalClickCapture(event) {
        var btn = findButtonFromEvent(event);
        if (!btn) { return; }
        return window.openDistributionSettingsModal(btn, event);
    }

    document.removeEventListener('click', modalClickCapture, true);
    document.removeEventListener('mouseup', modalClickCapture, true);
    document.removeEventListener('touchend', modalClickCapture, true);
    document.addEventListener('click', modalClickCapture, true);
    document.addEventListener('mouseup', modalClickCapture, true);
    document.addEventListener('touchend', modalClickCapture, true);

    $(document)
        .off('click.dsb006DistributionModal')
        .on('click.dsb006DistributionModal', '#distribution-settings-page .btn-modal[data-href]', function (e) {
            return window.openDistributionSettingsModal(this, e);
        });
})();

</script>
@endsection

{{-- 
@section('javascript')

    <script>
        $(document).ready(function() {
            var page = "{{ session('page') }}";

            if (page != "") {
                page = "#" + page + "-link";
                console.log(page);
                $(page).click();

            } else {
                $("#provinces-link").click(); // Click the link, not the div ID
            }

            $(".select2").select2();
            provinces_table = $('#provinces_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionProvincesController@index') }}',
                    data: function(d) {

                    }
                },
                @include('distribution::partials.datatable_export_button')
                columns: [{
                        data: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'added_by',
                        name: 'added_by'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    }

                ],
                fnDrawCallback: function(oSettings) {

                },
            });
            //prefix table
            prefix_table = $('#prefix_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionNumberingPrefixController@index') }}',
                    data: function(d) {

                    }
                },
                @include('distribution::partials.datatable_export_button')
                columns: [

                    {
                        data: 'numbering_type',
                        name: 'numbering_type'
                    },
                    {
                        data: 'prefix',
                        name: 'prefix'
                    },
                    {
                        data: 'starting_no',
                        name: 'starting_no'
                    },
                    {
                        data: 'current_no',
                        name: 'current_no'
                    },
                    {
                        data: 'added_by',
                        name: 'added_by'
                    },
                    {
                        data: 'action',
                        searchable: false,
                        orderable: false
                    }
                ],
                fnDrawCallback: function(oSettings) {

                },
            });
            // free_issue_table is initialized via the included free_issue.index view

            // select districts
            districts_table = $('#districts_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionDistrictsController@index') }}',
                    data: function(d) {
                        d.province_id = $('#districts_province_id').val()
                    }
                },
                @include('distribution::partials.datatable_export_button')
                columns: [{
                        data: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'province_name',
                        name: 'province_name'
                    },
                    {
                        data: 'added_by',
                        name: 'added_by'
                    },
                    {
                        data: 'date',
                        name: 'date'
                    }

                ],
                fnDrawCallback: function(oSettings) {

                },
            });

            // initialize vehicles table
            var vehicles_table = $('#vehicles_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ action('\Modules\Distribution\Http\Controllers\DistributionVehiclesController@index') }}",
                    type: 'GET'
                },
                columns: [{
                        data: 'vehicle_no',
                        name: 'vehicle_no'
                    },
                    {
                        data: 'vehicle_type',
                        name: 'vehicle_type'
                    },
                    {
                        data: 'vehicle_brand',
                        name: 'vehicle_brand'
                    },
                    {
                        data: 'vehicle_model',
                        name: 'vehicle_model'
                    },
                    {
                        data: 'revenue_license_renewal_date',
                        name: 'revenue_license_renewal_date'
                    },
                    {
                        data: 'starting_meter',
                        name: 'starting_meter'
                    },
                    {
                        data: 'added_by',
                        name: 'added_by'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ]
            });

            // Reload table after adding or editing vehicle
            $(document).on('submit', '#vehicles_add_form, #vehicles_edit_form', function(e) {
                e.preventDefault();
                var form = $(this);
                var formMethod = form.find('input[name="_method"]').val() || form.attr('method') || 'POST';
                var url = form.attr('action');

                $.ajax({
                    url: url,
                    type: formMethod === 'PUT' ? 'POST' : formMethod,
                    data: form.serialize(),
                    success: function(response) {
                        $('#vehicleModal').modal('hide');
                        vehicles_table.ajax.reload();
                        toastr.success(response.message || 'Vehicle saved successfully!');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            var errorMsg = '';
                            $.each(errors, function(key, value) {
                                errorMsg += value[0] + '<br>';
                            });
                            toastr.error(errorMsg);
                        } else {
                            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr
                                .responseJSON.message : 'Error saving vehicle';
                            toastr.error(msg);
                        }
                    }
                });
            });

            // Handle delete vehicle button click
            $(document).on('click', '.delete_vehicle', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var url =
                    '{{ action('\Modules\Distribution\Http\Controllers\DistributionVehiclesController@destroy', ':id') }}';
                url = url.replace(':id', id);

                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        $.ajax({
                            method: 'DELETE',
                            url: url,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    vehicles_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                            error: function(xhr) {
                                var errorMsg = 'Error deleting vehicle';
                                if (xhr.responseJSON && xhr.responseJSON.msg) {
                                    errorMsg = xhr.responseJSON.msg;
                                }
                                toastr.error(errorMsg);
                            }
                        });
                    }
                });
            });

            // select areas
            areas_table = $('#areas_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionAreasController@index') }}',
                    data: function(d) {
                        d.province_id = $('#area_province_id').val()
                        d.district_id = $('#area_district_id').val()
                    }
                },
                @include('distribution::partials.datatable_export_button')
                columns: [{
                        data: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'district_name',
                        name: 'district_name'
                    },
                    {
                        data: 'province_name',
                        name: 'province_name'
                    },
                    {
                        data: 'added_by',
                        name: 'added_by'
                    },
                    {
                        data: 'date',
                        name: 'date'
                    }

                ],
                fnDrawCallback: function(oSettings) {

                },
            });

            // select routes
            routes_table = $('#routes_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ action('\Modules\Distribution\Http\Controllers\DistributionRoutesController@index') }}',
                    data: function(d) {
                        d.province_id = $('#route_province_id').val()
                        d.district_id = $('#route_district_id').val()
                        d.area_id = $('#route_area_id').val()
                    }
                },
                @include('distribution::partials.datatable_export_button')
                columns: [{
                        data: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'route_no',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'district_name',
                        name: 'distribution_districts.name'
                    },
                    {
                        data: 'province_name',
                        name: 'distribution_provinces.name'
                    },
                    {
                        data: 'area_name',
                        name: 'distribution_areas.name'
                    },
                    {
                        data: 'user_names',
                        name: 'users.username'
                    },
                    {
                        data: 'date',
                        name: 'distribution_routes.created_at'
                    }

                ],
                fnDrawCallback: function(oSettings) {

                },
            });


            $('#districts_province_id').change(function() {
                console.log("districts");
                districts_table.ajax.reload();
            })

            $('#area_province_id,#area_district_id').change(function() {
                console.log("areas");
                areas_table.ajax.reload();
            })

            $('#route_province_id,#route_district_id,#route_area_id').change(function() {
                console.log("routes");
                routes_table.ajax.reload();
            })

            discount_table = $('#discount_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ action('\Modules\Distribution\Http\Controllers\DiscountController@index') }}',
                    data: function(d) {

                    }
                },
                @include('distribution::partials.datatable_export_button')
                columns: [{
                        data: 'date_time',
                        name: 'date_time'
                    },
                    {
                        data: 'category_name',
                        name: 'category_name'
                    },
                    {
                        data: 'sub_category_name',
                        name: 'sub_category_name'
                    },
                    {
                        data: 'product_name',
                        name: 'product_name'
                    },
                    {
                        data: 'unit_name',
                        name: 'unit_name'
                    },
                    {
                        data: 'qty',
                        name: 'qty'
                    },
                    {
                        data: 'discount_type',
                        name: 'discount_type'
                    },
                    {
                        data: 'max_discount',
                        name: 'max_discount'
                    }
                ],
                fnDrawCallback: function(oSettings) {

                },
            });



        })



        $(document).on('click', 'a.delete_button', function(e) {
            var page_details = $(this).closest('div.page_details')
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();
                    $.ajax({
                        url: href,
                        type: 'GET',
                        success: function(response) {
                            $('#prefixModal').html(response);
                            $('#prefixModal').modal('show');

                            // Initialize select2 for numbering type
                            $('#prefixModal select.select2').select2({
                                dropdownParent: $('#prefixModal')
                            });
                        },
                        error: function() {
                            toastr.error('Error loading edit form');
                        }
                    });
                }
            });

            // Handle delete prefix button click
            $(document).on('click', '.delete_prefix', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var url =
                    '{{ action('\Modules\Distribution\Http\Controllers\DistributionNumberingPrefixController@destroy', ':id') }}';
                url = url.replace(':id', id);

                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        $.ajax({
                            method: 'DELETE',
                            url: url,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    prefix_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                            error: function(xhr) {
                                var errorMsg = 'Error deleting prefix';
                                if (xhr.responseJSON && xhr.responseJSON.msg) {
                                    errorMsg = xhr.responseJSON.msg;
                                }
                                toastr.error(errorMsg);
                            }
                        });
                    }
                });
            });

            // Handle prefix form submissions (both create and edit)
            $(document).on('submit', '#prefix_add_form, #prefix_edit_form', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var originalText = submitBtn.html();

                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + submitBtn
                    .text());

                // Get form method (PUT for edit, POST for create)
                var formMethod = form.find('input[name="_method"]').val() || form.attr('method') || 'POST';
                var formData = form.serialize();

                $.ajax({
                    url: form.attr('action'),
                    method: formMethod === 'PUT' ? 'POST' : formMethod,
                    data: formData,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            $('#prefixModal').modal('hide');
                            prefix_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = 'Error saving prefix';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            errorMsg = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            // Handle validation errors
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                toastr.error(value[0]);
                            });
                            errorMsg = null;
                        }
                        if (errorMsg) {
                            toastr.error(errorMsg);
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Handle Free Issue form submissions (create and edit)
            $(document).on('submit', '#freeIssueForm, #freeIssueEditForm', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var originalText = submitBtn.html();

                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST', // usually POST for both, since Laravel might use spoofing via _method
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            $('.view_modal').modal('hide');
                            if (typeof free_issue_table !== 'undefined') {
                                free_issue_table.ajax.reload();
                            } else {
                                // Find the datatable block and reload if var is not exposed
                                $('#free_issue_table').DataTable().ajax.reload();
                            }
                        } else {
                            toastr.error(result.msg || 'Error saving Free Issue');
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = 'Error saving Free Issue';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            errorMsg = xhr.responseJSON.msg;
                        }
                        toastr.error(errorMsg);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

        });

        // Discount Modal JavaScript - Rebuilt to match working invoice page pattern
        (function() {
            var isUpdatingProducts = false;
            var isUpdatingSubCategories = false;

            // Helper: AJAX get JSON (same as invoice page)
            function getJSON(url, params = {}) {
                const query = new URLSearchParams(params).toString();
                return fetch(url + (query ? '?' + query : ''), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).then(r => r.json());
            }

            // Get Select2 value (handles arrays)
            function getSelect2Value($select) {
                var val = $select.val();
                if (Array.isArray(val)) {
                    return val.length > 0 ? val[0] : '';
                }
                return val || '';
            }

            // Load products into all product selects
            function loadProducts() {
                if (isUpdatingProducts) return;

                var $categorySelect = $('#category_select');
                var $subCategorySelect = $('#sub_category_select');

                var categoryId = getSelect2Value($categorySelect);
                var subCategoryId = getSelect2Value($subCategorySelect);

                if (categoryId === 'all' || categoryId === '') categoryId = '';
                if (subCategoryId === 'all' || subCategoryId === '') subCategoryId = '';

                isUpdatingProducts = true;

                var params = {};
                if (categoryId) {
                    params.category_id = categoryId;
                }
                if (subCategoryId) {
                    params.sub_category_id = subCategoryId;
                }

                getJSON('{{ route('distribution.discounts.products') }}', params)
                    .then(data => {
                        // Normalise: data is now an array of {id, name, unit_id} objects
                        var arr = Array.isArray(data) ? data : Object.values(data);

                        $('.product_select').each(function() {
                            var $select = $(this);
                            var currentValue = getSelect2Value($select);

                            if ($select.hasClass('select2-hidden-accessible')) {
                                $select.select2('destroy');
                            }

                            $select.empty();

                            if (arr && arr.length > 0) {
                                arr.forEach(function(item) {
                                    var id = item.id !== undefined ? item.id : '';
                                    var name = item.name !== undefined ? item.name : '';
                                    var unitId = item.unit_id !== undefined ? item.unit_id : '';
                                    $select.append('<option value="' + id + '" data-unit-id="' +
                                        unitId + '">' + name + '</option>');
                                });
                            }

                            $select.select2({
                                placeholder: 'Please Select',
                                allowClear: true,
                                width: '100%',
                                minimumResultsForSearch: 0
                            });

                            if (currentValue && $select.find('option[value="' + currentValue + '"]')
                                .length > 0) {
                                $select.val(currentValue).trigger('change.select2');
                            }
                        });

                        isUpdatingProducts = false;
                    })
                    .catch(error => {
                        console.error('Error loading products:', error);
                        isUpdatingProducts = false;
                    });
            }

            // Load sub categories
            function loadSubCategories(categoryId) {
                var $subCategorySelect = $('#sub_category_select');
                var currentValue = getSelect2Value($subCategorySelect);

                isUpdatingSubCategories = true;

                var params = {
                    category_id: categoryId || ''
                };

                getJSON('{{ url('distribution/discounts/subcategories') }}', params)
                    .then(data => {
                        if ($subCategorySelect.hasClass('select2-hidden-accessible')) {
                            $subCategorySelect.select2('destroy');
                        }

                        $subCategorySelect.empty().append('<option value="">All</option>');

                        if (data && data.length > 0) {
                            $.each(data, function(index, item) {
                                $subCategorySelect.append('<option value="' + item.id + '">' + item.name +
                                    '</option>');
                            });
                        }

                        $subCategorySelect.select2({
                            placeholder: 'All',
                            allowClear: true,
                            width: '100%',
                            minimumResultsForSearch: 0
                        });

                        if (currentValue && $subCategorySelect.find('option[value="' + currentValue + '"]').length >
                            0) {
                            $subCategorySelect.val(currentValue).trigger('change.select2');
                        } else {
                            $subCategorySelect.val('').trigger('change.select2');
                        }

                        isUpdatingSubCategories = false;
                    })
                    .catch(error => {
                        console.error('Error loading subcategories:', error);
                        isUpdatingSubCategories = false;
                    });
            }

            // Initialize discount modal when it opens
            $(document).on('shown.bs.modal', '#discountModal', function() {
                $('#category_select').select2({
                    placeholder: 'All',
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 0
                });

                $('#sub_category_select').select2({
                    placeholder: 'All',
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 0
                });

                loadSubCategories('');
                loadProducts();
            });

            // When category changes
            $(document).on('change', '#category_select', function() {
                if (isUpdatingSubCategories) return;

                var categoryId = getSelect2Value($(this));
                if (categoryId === 'all') categoryId = '';

                loadSubCategories(categoryId);
                loadProducts();
            });

            // When sub category changes
            $(document).on('change', '#sub_category_select', function() {
                if (isUpdatingSubCategories) return;
                loadProducts();
            });

            // Add product row
            $(document).on('click', '#add_product_row', function() {
                var row = `
        <tr>
            <td>
                <select name="product_ids[]" class="form-control select2-search product_select">
                </select>
            </td>
            <td>
                <select name="unit_ids[]" class="form-control select2-search">
                    <option value="">Select</option>
                    @foreach ($units as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="qty[]" class="form-control" step="{{ '0.' . str_repeat('0', $quantity_precision - 1) . '1' }}" min="0">
            </td>
            <td>
                <select name="discount_type[]" class="form-control">
                    <option value="fixed">Fixed</option>
                    <option value="percentage">Percentage</option>
                </select>
            </td>
            <td>
                <input type="number" name="max_discount[]" class="form-control" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" min="0">
            </td>
            <td>
                <button type="button" class="btn btn-danger remove_row">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

                $('#product_table tbody').append(row);

                $('#product_table tbody tr:last .select2-search').select2({
                    placeholder: 'Please Select',
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 0
                });

                loadProducts();
            });

            // Remove row
            $(document).on('click', '.remove_row', function() {
                $(this).closest('tr').remove();
            });

            // Auto-load units when a product is chosen
            $(document).on('change', '.product_select', function() {
                var $productSelect = $(this);
                var $row = $productSelect.closest('tr');
                var $unitSelect = $row.find('select[name="unit_ids[]"]');

                if ($unitSelect.length === 0) return;

                var productId = $productSelect.val();

                if (!productId) {
                    // If no product selected, reset to default units
                    if ($unitSelect.hasClass('select2-hidden-accessible')) {
                        $unitSelect.select2('destroy');
                    }

                    $unitSelect.empty().append('<option value="">Select</option>');
                    @foreach ($units as $id => $name)
                        $unitSelect.append(
                        '<option value="{{ $id }}">{{ $name }}</option>');
                    @endforeach

                    $unitSelect.select2({
                        placeholder: 'Select',
                        allowClear: true,
                        width: '100%',
                        minimumResultsForSearch: 0
                    });
                    return;
                }

                // Load units for the selected product
                getJSON('{{ route('distribution.discounts.product-units') }}', {
                        product_id: productId
                    })
                    .then(data => {
                        if ($unitSelect.hasClass('select2-hidden-accessible')) {
                            $unitSelect.select2('destroy');
                        }

                        $unitSelect.empty().append('<option value="">Select</option>');

                        if (data && data.length > 0) {
                            $.each(data, function(index, unit) {
                                $unitSelect.append('<option value="' + unit.id + '">' + unit.name +
                                    '</option>');
                            });

                            // Auto-select the first unit (usually the main unit)
                            if (data.length > 0) {
                                $unitSelect.val(data[0].id);
                            }
                        }

                        $unitSelect.select2({
                            placeholder: 'Select',
                            allowClear: true,
                            width: '100%',
                            minimumResultsForSearch: 0
                        });

                        $unitSelect.trigger('change.select2');
                    })
                    .catch(error => {
                        console.error('Error loading product units:', error);

                        // Fallback to default units on error
                        if ($unitSelect.hasClass('select2-hidden-accessible')) {
                            $unitSelect.select2('destroy');
                        }

                        $unitSelect.empty().append('<option value="">Select</option>');
                        @foreach ($units as $id => $name)
                            $unitSelect.append(
                                '<option value="{{ $id }}">{{ $name }}</option>');
                        @endforeach

                        $unitSelect.select2({
                            placeholder: 'Select',
                            allowClear: true,
                            width: '100%',
                            minimumResultsForSearch: 0
                        });
                    });
            });

            $(document).on('submit', '#discountModal form', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var originalText = submitBtn.html();

                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            $('#discountModal').modal('hide');
                            discount_table.ajax.reload();
                            form[0].reset();
                            $('#product_table tbody').empty();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = 'Error saving discount';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            errorMsg = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                toastr.error(value[0]);
                            });
                            errorMsg = null;
                        }
                        if (errorMsg) {
                            toastr.error(errorMsg);
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        })();

        $(document).on('click', 'a.delete_button', function(e) {
            var page_details = $(this).closest('div.page_details')
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        data: data,
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                            } else {
                                toastr.error(result.msg);
                            }
                            provinces_table.ajax.reload();
                            districts_table.ajax.reload();
                            areas_table.ajax.reload();
                            routes_table.ajax.reload();
                        },
                    });
                }
            });
        });
    </script>

    <script>
        // $('.select2').select2({ width: '100%' });

        /* CATEGORY → SUBCATEGORY FILTER */
        $('#filter_category').on('change', function() {
            let ids = $(this).val() || [];
            $('#filter_subcategory').empty();

            if (ids.length === 0) return;

            $.get('/distribution/free-issues/subcategories/' + ids.join(','), function(res) {
                $.each(res, function(id, name) {
                    $('#filter_subcategory').append(new Option(name, id));
                });
            });
        });

        /* DATATABLE */
        //   let table = $('#free_issue_table').DataTable({
        //     processing: true,
        //     serverSide: true,
        //     ajax: {
        //         url: "{{ route('distribution.free-issues.index') }}",
        //         data: function (d) {
        //             d.products = $('#filter_product').val();
        //             d.categories = $('#filter_category').val();
        //             d.subcategories = $('#filter_subcategory').val();
        //         }
        //     },
        //     columns: [
        //         { data: 'action', name: 'action', orderable: false }, // Action first
        //         { data: 'created_at', name: 'created_at' },
        //         { data: 'products', name: 'products', orderable: false },
        //         { data: 'categories', name: 'categories', orderable: false },
        //         { data: 'subcategories', name: 'subcategories', orderable: false },
        //         { data: 'qty_from', name: 'qty_from' },
        //         { data: 'qty_till', name: 'qty_till' },
        //         { data: 'free_qty', name: 'free_qty' },
        //     ]
        // });


        /* APPLY FILTERS */
        $('#applyFilters').on('click', function() {
            if (typeof free_issue_table !== 'undefined') {
                free_issue_table.ajax.reload();
            }
        });

        /* STATUS TOGGLE */
        $(document).on('change', '.toggle-status', function() {
            $.post('/distribution/free-issues/toggle/' + $(this).data('id'), {
                _token: '{{ csrf_token() }}'
            }, function() {
                if (typeof free_issue_table !== 'undefined') {
                    free_issue_table.ajax.reload(null, false);
                }
            });
        });

        $(document).on('click', '.user-logs', function() {
            let id = $(this).data('id');

            $.get('/distribution/free-issues/logs/' + id, function(res) {
                let html = '<ul>';
                res.forEach(r => {
                    html += `<li>${r.created_at} - ${r.user.username} - ${r.action}</li>`;
                });
                html += '</ul>';

                $('.view_modal').html(html).modal('show');
            });
        });
    </script>

@endsection --}}
