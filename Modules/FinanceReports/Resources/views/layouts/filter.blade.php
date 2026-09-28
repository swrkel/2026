{{--
 |==============================================================================
 | Finance Reports - shared filter bar
 |==============================================================================
 |
 | THE FAULT IN THE LOG
 |   local.ERROR: Undefined variable $action
 |   (View: Modules/FinanceReports/Resources/views/layouts/filter.blade.php)
 |
 |   Line 6 read action="{{ $action }}" with nothing supplying a default. 30 of
 |   the 38 views that include this partial pass an 'action' key; EIGHT do not,
 |   and every one of those pages died with a 500 before rendering anything:
 |
 |       reports/executive_bi_dashboard      reports/forecast
 |       reports/enterprise_center           reports/consolidation_center
 |       reports/engine/status               reports/performance_center
 |       reports/rc2/export_center           reports/rc2/print_layout_center
 |
 |   The last two pass NO variables at all, so they would also have failed on
 |   $locations, $location_id, $start and $end.
 |
 | THE FIX
 |   Every variable this partial reads now has a sensible default, so a caller
 |   that omits one gets a working filter bar instead of a white page. The
 |   default for $action is request()->url() - which is exactly what 8 of the
 |   callers pass explicitly, so it is the established convention here, not a
 |   guess.
 |
 |   Callers that DO pass values are unaffected: ?? only applies when the
 |   variable is absent.
 |
 |==============================================================================
 | AUTO LOADING (Aug 2026)
 |==============================================================================
 |
 | The reports no longer need Generate pressed.
 |
 | On OPEN they already loaded: every controller here defaults the period to the
 | current month - start_date to date('Y-m-01'), end_date to today, mirrored in
 | the defaults above - and each view renders its rows unconditionally. Nothing
 | was gating the first load.
 |
 | On CHANGE the period now reloads by itself. data-auto-filter-button on the
 | Generate button below is the hook for public/js/erp-global-auto-filter.js,
 | which submits this form after a change: 150ms after a date or dropdown, 600ms
 | after typing, or immediately on Enter.
 |
 | Because this partial is shared by all 38 FinanceReports views, one attribute
 | gives every one of them the same behaviour, and a report added later inherits
 | it without being told.
 --}}
@php
    $action      = $action      ?? request()->url();
    $locations   = $locations   ?? [];
    $location_id = $location_id ?? request()->input('location_id', 'all');
    $asAtMode    = $asAtMode    ?? false;
    $as_at       = $as_at       ?? request()->input('as_at', date('Y-m-d'));
    $start       = $start       ?? request()->input('start_date', date('Y-m-01'));
    $end         = $end         ?? request()->input('end_date', date('Y-m-d'));
    $accounts    = $accounts    ?? [];
    $account_id  = $account_id  ?? request()->input('account_id', '');
    $autoSubmitDate = $autoSubmitDate ?? false;
@endphp
<div class="row no-print">
    <div class="col-sm-12">
        <div class="box box-solid">
            <div class="box-header with-border"><h3 class="box-title">Filters</h3></div>
            <div class="box-body">
                <form method="GET" action="{{ $action }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Branch / Location</label>
                            <select name="location_id" class="form-control select2" style="width:100%;">
                                <option value="all">Consolidated - All Locations</option>
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ (string)$location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(!empty($asAtMode))
                            <div class="col-md-3"><label>As At Date</label><input type="date" name="as_at" value="{{ $as_at }}" class="form-control"></div>
                        @else
                            {{--
                                The system standard date range picker, with the
                                built-in ranges - Today, This Month, Current
                                financial year, Custom Range and the rest. The same
                                control and the same range list as
                                layouts.partials.erp-records-toolbar, which is what
                                List Purchase Entries uses.

                                That partial is not included wholesale because it
                                drives an ajax DataTable (#erp_ajax_table) and these
                                reports render their rows server-side. Only the
                                picker is taken; its configuration is copied
                                verbatim so the two behave identically.

                                start_date and end_date remain as hidden fields, so
                                the controllers and queries are untouched - the
                                picker writes into them and the form submits exactly
                                what it always did.
                            --}}
                            <div class="col-md-6">
                                <label>Date Range</label>
                                <input type="text" id="fr_date_range" class="form-control"
                                       autocomplete="off" readonly
                                       value="{{ $start }} ~ {{ $end }}">
                                <input type="hidden" name="start_date" id="fr_start_date" value="{{ $start }}">
                                <input type="hidden" name="end_date" id="fr_end_date" value="{{ $end }}">
                            </div>
                        @endif
                        @if(!empty($accounts))
                            <div class="col-md-3"><label>Account</label><select name="account_id" class="form-control select2" style="width:100%;"><option value="">Please Select</option>@foreach($accounts as $id => $name)<option value="{{ $id }}" {{ (string)$account_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
                        @endif
                        {{-- data-auto-filter-button is what the global auto-filter
                             script looks for. Its name test accepts "Apply",
                             "Apply Filters" and "Filter" only - deliberately
                             narrow, so it can never click a Save or Delete button
                             by accident - and "Generate" is not on that list. The
                             attribute names this button explicitly instead of
                             widening a test that guards every table in the
                             application. --}}
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block" type="submit" data-auto-filter-button>
                                <i class="fa fa-search"></i> Generate
                            </button>
                        </div>
                    </div>
                </form>

<script>
(function ($) {
    'use strict';

    if (!$ || !$.fn.daterangepicker || typeof moment !== 'function') {
        /*
         * Without the plugin the two hidden fields still hold the dates the
         * server rendered, so the report keeps working - it simply cannot be
         * changed from here. That is better than a broken control.
         */
        return;
    }

    $(function () {
        var $display = $('#fr_date_range');
        var $start = $('#fr_start_date');
        var $end = $('#fr_end_date');

        if (!$display.length || $display.data('frRangeBound')) {
            return;
        }

        function seed($field, fallback) {
            var value = $.trim(String($field.val() || ''));
            var parsed = value ? moment(value, 'YYYY-MM-DD', true) : null;

            return (parsed && parsed.isValid()) ? parsed : fallback;
        }

        var startDate = seed($start, moment().startOf('month'));
        var endDate = seed($end, moment());

        /*
         * Ranges copied verbatim from layouts.partials.erp-records-toolbar, so
         * this picker offers exactly what List Purchase Entries offers. The
         * financial year runs April to March, which is what that list uses.
         */
        $display.daterangepicker({
            startDate: startDate,
            endDate: endDate,
            autoUpdateInput: true,
            alwaysShowCalendars: true,
            showDropdowns: true,
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' ~ ',
                applyLabel: 'Apply',
                cancelLabel: 'Clear',
                customRangeLabel: 'Custom Date Range'
            },
            ranges: (function () {
                var now = moment();
                // Sri Lankan / ERP financial year: 1 April to 31 March. In
                // Jan-Mar the current FY started in the PREVIOUS calendar year.
                var currentFyStartYear = now.month() >= 3 ? now.year() : now.year() - 1;
                var currentFyStart = moment([currentFyStartYear, 3, 1]).startOf('day');
                var currentFyEnd = moment([currentFyStartYear + 1, 2, 31]).endOf('day');
                var lastFyStart = currentFyStart.clone().subtract(1, 'year');
                var lastFyEnd = currentFyEnd.clone().subtract(1, 'year');

                return {
                    'Today': [now.clone().startOf('day'), now.clone().endOf('day')],
                    'Yesterday': [now.clone().subtract(1, 'days').startOf('day'), now.clone().subtract(1, 'days').endOf('day')],
                    'Last 7 Days': [now.clone().subtract(6, 'days').startOf('day'), now.clone().endOf('day')],
                    'Last 30 Days': [now.clone().subtract(29, 'days').startOf('day'), now.clone().endOf('day')],
                    'This Month': [now.clone().startOf('month'), now.clone().endOf('month')],
                    'Last Month': [now.clone().subtract(1, 'month').startOf('month'), now.clone().subtract(1, 'month').endOf('month')],
                    'This month last year': [now.clone().subtract(1, 'year').startOf('month'), now.clone().subtract(1, 'year').endOf('month')],
                    'This Year': [now.clone().startOf('year'), now.clone().endOf('year')],
                    'Last Year': [now.clone().subtract(1, 'year').startOf('year'), now.clone().subtract(1, 'year').endOf('year')],
                    'This FY': [currentFyStart, currentFyEnd],
                    'Last FY': [lastFyStart, lastFyEnd]
                };
            })()
        });

        /*
         * The hidden fields are what the form actually posts, so they are written
         * on every apply. The report then reloads through the global auto-filter
         * script - the same behaviour as changing any other filter here.
         */
        $display.on('apply.daterangepicker', function (ev, picker) {
            $start.val(picker.startDate.format('YYYY-MM-DD'));
            $end.val(picker.endDate.format('YYYY-MM-DD'));
            $display.val(picker.startDate.format('YYYY-MM-DD') + ' ~ ' + picker.endDate.format('YYYY-MM-DD'));

            @if(!empty($autoSubmitDate))
                var $form = $display.closest('form');
                if ($form.length && !$form.data('frSubmitting')) {
                    $form.data('frSubmitting', true);
                    $form.trigger('submit');
                }
            @else
                $display.trigger('change');
            @endif
        });

        // Clear returns to the current month rather than an empty range, which
        // the reports cannot interpret.
        $display.on('cancel.daterangepicker', function () {
            var from = moment().startOf('month');
            var to = moment();

            $start.val(from.format('YYYY-MM-DD'));
            $end.val(to.format('YYYY-MM-DD'));
            $display.val(from.format('YYYY-MM-DD') + ' ~ ' + to.format('YYYY-MM-DD'));

            @if(!empty($autoSubmitDate))
                var $form = $display.closest('form');
                if ($form.length && !$form.data('frSubmitting')) {
                    $form.data('frSubmitting', true);
                    $form.trigger('submit');
                }
            @else
                $display.trigger('change');
            @endif
        });

        $display.data('frRangeBound', true);
    });
})(window.jQuery);
</script>
            </div>
        </div>
    </div>
</div>
