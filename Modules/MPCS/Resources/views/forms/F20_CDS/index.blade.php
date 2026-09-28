@extends('layouts.app')
@section('title', 'F 20 Form – CDS')

@section('content')
@php
    $f20_currency_precision = isset($currency_precision) ? (int) $currency_precision : 2;
    $f20_quantity_precision = isset($quantity_precision) ? (int) $quantity_precision : 3;
    $f20_amount_zero = number_format(0, $f20_currency_precision, '.', ',');
    $f20_meter_zero = number_format(0, 3, '.', ',');
@endphp
<style>
    .f20-cds-page { background:#f7f8fa; padding-bottom:25px; }
    .f20-cds-top-actions { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
    .f20-cds-print-only-actions { justify-content:flex-end; }
    .f20-cds-tabs { margin-bottom:10px; }
    .f20-cds-tabs > li > a{
        font-weight:600;
        border-radius:6px 6px 0 0;
        color:#fff !important;
        padding:12px 20px;
        border:none !important;
        margin-right:4px;
    }
    .f20-cds-tabs > li.active > a,
    .f20-cds-tabs > li.active > a:hover,
    .f20-cds-tabs > li.active > a:focus{
        color:#fff !important;
        opacity:1;
    }
    .f20-cds-tab-form{ background:#0d6efd !important; color:#fff !important; }
    .f20-cds-tab-settings{ background:#fd7e14 !important; color:#fff !important; }
    .f20-cds-tab-list{ background:#5bc0de !important; color:#fff !important; }
    .f20-cds-tab-form:hover,
    .f20-cds-tab-settings:hover,
    .f20-cds-tab-list:hover{ color:#fff !important; opacity:.95; }
    .f20-cds-paper-wrap { overflow-x:auto; background:#fff; border:1px solid #d8d8d8; padding:14px; box-shadow:0 1px 5px rgba(0,0,0,.08); }
    .f20-cds-paper { min-width:1180px; max-width:1320px; margin:0 auto; background:#fff; color:#111; font-family:Arial, 'Noto Sans Sinhala', sans-serif; }
    .f20-cds-form-head { display:grid; grid-template-columns: 28% 44% 28%; align-items:start; gap:10px; margin-bottom:8px; }
    .f20-cds-location-select { max-width:70%; }
    .f20-cds-location-select { width:70%; height:34px; border:1px solid #cfcfcf; padding:5px 8px; font-size:12px; background:#fff; }
    .f20-cds-business-title { text-align:center; font-size:32px; font-weight:800; border:0; outline:0; width:100%; background:transparent; margin-top:0; line-height:1.15; }
    .f20-cds-daily-report { text-align:center; font-size:30px; font-weight:800; margin-top:14px; line-height:1.15; }
    .f20-cds-right-head { display:grid; grid-template-columns: 78px 1fr; gap:8px; align-items:center; justify-content:start; width:50%; justify-self:start; margin-top:-10px; margin-left:10px; }
    .f20-cds-label-box { border:1px solid #888; background:#f9f9f9; padding:7px 8px; font-size:12px; font-weight:700; min-height:32px; display:flex; align-items:center; justify-content:center; }
    .f20-cds-title-box { border:2px solid #c73535; background:#fff; padding:7px 8px; font-size:14px; font-weight:700; text-align:center; min-height:36px; display:flex; align-items:center; justify-content:center; }
    .f20-cds-input, .f20-cds-paper input[type="text"], .f20-cds-paper input[type="number"], .f20-cds-paper input[type="date"] { width:100%; height:28px; border:0; outline:0; background:transparent; padding:2px 4px; }
    .f20-cds-box-input { border:1px solid #999 !important; background:#fff !important; height:32px !important; }
    .f20-date-wrap { position:relative; }
    .f20-date-shortcuts { display:none; position:absolute; right:0; top:34px; z-index:9999; min-width:210px; background:#fff; border:1px solid #d0d0d0; box-shadow:0 4px 12px rgba(0,0,0,.18); padding:8px; border-radius:4px; }
    .f20-date-wrap.f20-date-open .f20-date-shortcuts { display:block; }
    .f20-date-shortcuts-title { font-size:12px; color:#666; margin-bottom:6px; font-weight:700; }
    .f20-date-shortcuts .btn { margin-right:5px; margin-bottom:5px; }
    .f20-section-title { font-weight:700; font-size:14px; margin:12px 0 6px; }
    .f20-grid-table { width:100%; border-collapse:collapse; table-layout:fixed; }
    .f20-grid-table th, .f20-grid-table td { border:1px solid #9a9a9a; padding:0; height:28px; font-size:12px; vertical-align:middle; }
    .f20-grid-table th { background:#eef2f6; text-align:center; font-weight:700; padding:5px 4px; }
    .f20-grid-table .row-label { background:#eef2f6; font-weight:700; padding:5px 6px; text-align:left; }
    .f20-num { text-align:right; }
    .f20-cds-lower { display:grid; grid-template-columns: 48% 44%; column-gap:7%; align-items:start; margin-top:14px; }
    .daily-sales-wrap { display:grid; grid-template-columns: 1fr 1fr; gap:8px; }
    .balance-stock-title { text-align:left; font-weight:700; font-size:14px; margin:0 0 8px; }
    .f20-total-row td { font-weight:700; background:#fafafa; }
    .f20-cds-save-row { text-align:right; margin-top:14px; }
    .f20-cds-settings-card, .f20-cds-list-card { background:#fff; border:1px solid #d8d8d8; padding:15px; box-shadow:0 1px 5px rgba(0,0,0,.06); }
    .f20-cds-settings-table th { background:#f4f6f8; }
    .f20-small-help { color:#666; font-size:12px; margin-top:6px; }
    .f20-cds-list-toolbar { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
    @media (max-width: 767px) {
        .f20-cds-paper-wrap { padding:8px; }
        .f20-cds-paper { min-width:1040px; }
        .f20-cds-top-actions { align-items:flex-start; }
        .f20-cds-tabs > li > a { padding:10px 12px; font-size:13px; }
    }
    @media print {
        @page { size: A4 landscape; margin: 3mm; }

        html, body {
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #fff !important;
        }

        /*
         * IS2131: hide the TAB STRIP and stop link targets printing.
         *
         * The reported preview showed the three tabs printed as raw URLs -
         * "F 20 Form - CDS (/mpcs/F20-CDS#f20_cds_form_tab)" and the other two -
         * across the top of the sheet, with the sidebar toggle over the table.
         *
         * This block already hid the header and sidebar, but nothing hid the tab
         * strip, and nothing overrode the theme's print rule that appends
         * attr(href) to every anchor. Both are added here.
         *
         * Note for the record: IS2110 put these rules in
         * forms/20Form/20_form.blade.php. That is a DIFFERENT page - this route,
         * /mpcs/F20-CDS, renders forms/F20_CDS/index.blade.php - so that fix was
         * never on the page being tested.
         */
        /*
         * IS2141: targeted by the page's OWN class, at high specificity.
         *
         * The previous rules (.nav-tabs, ul.nav-tabs) are correct and match the
         * markup, yet the tab strip still printed - so something in the theme is
         * winning the cascade. Rather than guess which rule, these use the class
         * this page defines itself, qualified by the element and an ancestor,
         * which beats any generic .nav-tabs rule the theme can hold.
         *
         * The list ITEMS are hidden as well as the list. If the URLs turn out to
         * be real text rather than CSS-generated content - which
         * a[href]:after cannot remove - hiding the items removes them anyway.
         */
        body ul.nav-tabs.f20-cds-tabs,
        body ul.nav-tabs.f20-cds-tabs > li,
        body ul.nav-tabs.f20-cds-tabs > li > a,
        body .f20-cds-tabs,
        .content ul.nav-tabs,
        .content-wrapper ul.nav-tabs,
        .nav-tabs,
        .nav.nav-tabs,
        ul.nav-tabs,
        .sidebar-toggle,
        .dataTables_filter,
        .dataTables_length,
        .dataTables_paginate,
        .dataTables_info {
            display: none !important;
        }

        /* This is what turned the tab captions into URLs. */
        /*
         * IS2141: the same treatment for printed link targets.
         *
         * Bootstrap's print stylesheet sets a[href]:after { content: " (" attr(href) ")" },
         * which is what appends the URL to each caption. The earlier rule was
         * correct but was being outweighed; qualifying by `body` raises its
         * specificity above a bare a[href] rule while remaining harmless.
         */
        body a[href]:after,
        body abbr[title]:after,
        a[href]:after,
        abbr[title]:after {
            content: "" !important;
            display: none !important;
        }

        /* Remove the application chrome and the marked screen-only sections. */
        .header-area,
        .header-area *,
        .main-header,
        .main-header *,
        .main-sidebar,
        .main-sidebar *,
        .sidebar,
        .sidebar *,
        .content-header,
        .page-title-area,
        .page-title-area *,
        .nav-btn,
        .nav-btn *,
        .main-footer,
        .breadcrumb,
        .f20-cds-top-actions,
        .f20-cds-tabs,
        .f20-cds-save-row,
        .f20-date-shortcuts,
        .alert,
        .f20-cds-settings-card,
        .f20-cds-list-card {
            display: none !important;
        }

        .content-wrapper,
        .right-side,
        .main-content,
        .page-container,
        .f20-cds-page,
        section.content {
            width: 100% !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        /* Print only the form panel, even when another tab was active before print. */
        .f20-cds-page > .tab-content > .tab-pane {
            display: none !important;
        }

        #f20_cds_form_tab {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .f20-cds-paper-wrap {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        /* IS2295: no width/zoom expansion. Print at the actual landscape page width. */
        .f20-cds-paper {
            min-width: 0 !important;
            max-width: none !important;
            width: 100% !important;
            margin: 0 !important;
            zoom: 1 !important;
            page-break-inside: avoid !important;
            break-inside: avoid-page !important;
        }

        /* Match the requested preview: Daily Report is the only top heading. */
        .f20-cds-form-head {
            display: block !important;
            margin: 0 0 8px !important;
        }
        .f20-cds-form-head > div:first-child,
        .f20-cds-form-head > div:last-child,
        .f20-cds-business-title {
            display: none !important;
        }
        .f20-cds-daily-report { font-size: 20px !important; margin: 0 0 8px !important; }
        .f20-section-title,
        .balance-stock-title { margin: 5px 0 3px !important; font-size: 11px !important; }
        .f20-cds-lower { margin-top: 6px !important; column-gap: 4% !important; }
        .daily-sales-wrap { gap: 4px !important; }

        .f20-grid-table th,
        .f20-grid-table td {
            height: 21px !important;
            padding: 1px 2px !important;
            font-size: 9px !important;
            line-height: 1.05 !important;
        }

        .f20-cds-paper input,
        .f20-cds-paper select {
            height: 21px !important;
            min-height: 0 !important;
            padding: 0 2px !important;
            font-size: 9px !important;
            box-shadow: none !important;
        }

        .f20-cds-location-select {
            border: 0 !important;
            appearance: none !important;
            -webkit-appearance: none !important;
        }
    }
</style>

<section class="content-header">
    <h1>F 20 Form – CDS <small>MPCS Module</small></h1>
</section>

<section class="content f20-cds-page">
    @if(session('status'))
        <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }}">
            {{ session('status.msg') }}
        </div>
    @endif

    <ul class="nav nav-tabs f20-cds-tabs" role="tablist">
        <li class="{{ ($active_tab ?? 'form') == 'form' ? 'active' : '' }}">
            <a href="#f20_cds_form_tab" data-toggle="tab" class="f20-cds-tab-form"><i class="fa fa-file-text-o"></i> F 20 Form – CDS</a>
        </li>
        <li class="{{ ($active_tab ?? 'form') == 'settings' ? 'active' : '' }}">
            <a href="#f20_cds_settings_tab" data-toggle="tab" class="f20-cds-tab-settings"><i class="fa fa-cogs"></i> F 20 CDS Settings</a>
        </li>
        <li class="{{ ($active_tab ?? 'form') == 'list' ? 'active' : '' }}">
            <a href="#f20_cds_list_tab" data-toggle="tab" class="f20-cds-tab-list"><i class="fa fa-list"></i> List F 20 CDS</a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane {{ ($active_tab ?? 'form') == 'form' ? 'active' : '' }}" id="f20_cds_form_tab">
            <div class="f20-cds-top-actions f20-cds-print-only-actions">
                <button type="button" onclick="openF20CdsPrintPreview(); return false;" class="btn btn-primary btn-sm">
                    <i class="fa fa-print"></i> Print Layout Preview
                </button>
            </div>

            {!! Form::open(['url' => action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@store'), 'method' => 'post', 'id' => 'f20_cds_form']) !!}
            <div class="f20-cds-paper-wrap">
                <div class="f20-cds-paper">
                    <div class="f20-cds-form-head">
                        <div>
                            {!! Form::select('location_id', $business_locations, old('location_id', $default_location_id ?? null), ['class' => 'f20-cds-location-select', 'id' => 'f20_cds_location_id', 'placeholder' => 'MPCS Filling Station - Ayagama (BL0001)']) !!}
                        </div>
                        <div>
                            <input type="text" name="society_name" class="f20-cds-business-title" value="{{ old('society_name', optional($business)->name ?: 'Business Location') }}">
                            <div class="f20-cds-daily-report">Daily Report</div>
                        </div>
                        <div class="f20-cds-right-head">
                            <div class="f20-cds-label-box">Form No</div>
                            <div class="f20-cds-title-box">F 20 Form – CDS</div>
                            <div><input type="text" name="form_no" class="f20-cds-box-input f20-num" value="{{ old('form_no', $form_no) }}" readonly></div>
                            <div class="f20-date-wrap" id="f20_cds_date_wrap">
                                <input type="date" name="form_date" id="f20_cds_form_date" class="f20-cds-box-input" value="{{ old('form_date', $form_date ?? (!empty($settings->opening_date) ? $settings->opening_date : $today)) }}">
                                <div class="f20-date-shortcuts" id="f20_cds_date_shortcuts">
                                    <div class="f20-date-shortcuts-title">Quick Date</div>
                                    <button type="button" class="btn btn-primary btn-xs f20-date-shortcut" data-date-option="today">Today</button>
                                    <button type="button" class="btn btn-info btn-xs f20-date-shortcut" data-date-option="yesterday">Yesterday</button>
                                    <button type="button" class="btn btn-default btn-xs f20-date-shortcut" data-date-option="clear">Clear</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="f20-section-title">Meter Reading Section</div>
                    @php
                        $selected_location_key = !empty(old('location_id', $default_location_id ?? null)) ? (string) old('location_id', $default_location_id ?? null) : 'all';
                        $display_pumps = (!empty($f20_cds_pumps) && count($f20_cds_pumps)) ? $f20_cds_pumps : collect();
                        $meter_data_by_location = $f20_cds_meter_data_by_location ?? [];
                        $meter_rows = [
                            'last_meter' => 'Last Meter',
                            'starting_meter' => 'Starting Meter',
                            'total_sale' => 'Total Sale',
                            'balance' => 'Balance',
                            'pumps_checked' => 'Testing',
                            'cash_sale' => 'Cash Sale',
                        ];
                    @endphp
                    <table class="f20-grid-table f20-meter-table">
                        <colgroup>
                            <col style="width:17%;">
                            @forelse($display_pumps as $pump)
                                <col style="width:{{ count($display_pumps) ? round(83 / count($display_pumps), 2) : 9.22 }}%;">
                            @empty
                                @for($pump = 1; $pump <= 9; $pump++)
                                    <col style="width:9.22%;">
                                @endfor
                            @endforelse
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Pump No.</th>
                                @forelse($display_pumps as $pump)
                                    <th class="f20-pump-head" data-pump-id="{{ $pump->id }}">{{ $pump->pump_name ?: ('Pump ' . $pump->id) }}</th>
                                @empty
                                    @for($pump = 1; $pump <= 9; $pump++)
                                        <th>{{ $pump }}</th>
                                    @endfor
                                @endforelse
                            </tr>
                        </thead>
                        <tbody id="f20_meter_body">
                            @foreach($meter_rows as $row_key => $row_label)
                                <tr data-row-key="{{ $row_key }}">
                                    <td class="row-label">{{ $row_label }}</td>
                                    @forelse($display_pumps as $pump)
                                        @php
                                            $pump_location_key = !empty($pump->location_id) ? (string) $pump->location_id : 'all';
                                            $meter_value = $meter_data_by_location[$pump_location_key][$pump->id][$row_key] ?? '';
                                            if ($meter_value === '' && $pump_location_key !== 'all') {
                                                $meter_value = $meter_data_by_location['all'][$pump->id][$row_key] ?? '';
                                            }
                                            $old_key = 'rows.meter_'.$row_key.'_'.$pump->id.'.description';
                                        @endphp
                                        <td>
                                            <input type="text"
                                                   class="f20-cds-input f20-num meter-cell {{ $row_key == 'cash_sale' ? 'cash-sale-amount' : '' }}"
                                                   data-pump-id="{{ $pump->id }}"
                                                   data-row-key="{{ $row_key }}"
                                                   name="rows[meter_{{ $row_key }}_{{ $pump->id }}][description]"
                                                   value="{{ old($old_key, $meter_value) }}">
                                        </td>
                                    @empty
                                        @for($pump = 1; $pump <= 9; $pump++)
                                            <td>
                                                <input type="text" class="f20-cds-input f20-num {{ $row_key == 'cash_sale' ? 'cash-sale-amount' : '' }}" name="rows[meter_{{ $row_key }}_{{ $pump }}][description]" value="{{ old('rows.meter_'.$row_key.'_'.$pump.'.description', $row_key == 'cash_sale' ? $f20_amount_zero : $f20_meter_zero) }}">
                                            </td>
                                        @endfor
                                    @endforelse
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="f20-cds-lower">
                        <div>
                            <div class="f20-section-title">Daily Sales Status</div>
                            <div class="daily-sales-wrap">
                                <table class="f20-grid-table">
                                    <thead>
                                        <tr><th>Details</th><th>Amount</th></tr>
                                    </thead>
                                    <tbody id="f20_daily_sales_body">
                                        @php
                                            $daily_sales_categories = collect($f20_cds_daily_sales_categories ?? []);
                                            $daily_sales_amounts = $f20_cds_daily_sales_amounts ?? [];
                                            $daily_sales_rows = max(10, $daily_sales_categories->count());
                                        @endphp
                                        @for($i = 1; $i <= $daily_sales_rows; $i++)
                                            @php
                                                $category = $daily_sales_categories->get($i - 1);
                                                $category_id = is_array($category) ? ($category['id'] ?? null) : (is_object($category) ? ($category->id ?? null) : null);
                                                $category_name = is_array($category) ? ($category['name'] ?? '') : (is_object($category) ? ($category->name ?? '') : '');
                                                $category_amount = !empty($category_id) && isset($daily_sales_amounts[$category_id]) ? $daily_sales_amounts[$category_id] : '';
                                            @endphp
                                            <tr>
                                                <td><input type="text" class="f20-cds-input daily-sales-desc" data-category-id="{{ $category_id }}" name="rows[daily_sales_{{ $i }}][description]" value="{{ old('rows.daily_sales_'.$i.'.description', $category_name) }}"></td>
                                                <td><input type="text" class="f20-cds-input f20-num sales-amount" data-category-id="{{ $category_id }}" name="rows[daily_sales_{{ $i }}][amount]" value="{{ old('rows.daily_sales_'.$i.'.amount', $category_amount) }}"></td>
                                            </tr>
                                        @endfor
                                        <tr class="f20-total-row">
                                            <td>Total</td>
                                            <td><input type="text" id="sales_total" class="f20-cds-input f20-num" name="sales_total" value="{{ old('sales_total', $f20_amount_zero) }}"></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table class="f20-grid-table">
                                    <thead>
                                        <tr><th>Details</th><th>Amount</th></tr>
                                    </thead>
                                    <tbody id="f20_credit_sales_body">
                                        @php
                                            $credit_sales_rows = collect($f20_cds_credit_sales ?? []);
                                            $credit_sales_count = $credit_sales_rows->count();
                                            $manual_start = $credit_sales_count + 2;
                                        @endphp

                                        @forelse($credit_sales_rows as $cs_index => $credit_sale)
                                            @php
                                                $row_no = $cs_index + 1;
                                                $customer_name = is_array($credit_sale) ? ($credit_sale['customer_name'] ?? '') : (is_object($credit_sale) ? ($credit_sale->customer_name ?? '') : '');
                                                $customer_amount = is_array($credit_sale) ? ($credit_sale['amount'] ?? '') : (is_object($credit_sale) ? ($credit_sale->amount ?? '') : '');
                                            @endphp
                                            <tr class="f20-credit-auto-row">
                                                <td><input type="text" class="f20-cds-input" name="rows[credit_customer_{{ $row_no }}][description]" value="{{ old('rows.credit_customer_'.$row_no.'.description', $customer_name) }}" readonly></td>
                                                <td><input type="text" class="f20-cds-input f20-num expense-amount credit-sale-auto-amount" name="rows[credit_customer_{{ $row_no }}][amount]" value="{{ old('rows.credit_customer_'.$row_no.'.amount', $customer_amount) }}" readonly></td>
                                            </tr>
                                        @empty
                                            <tr class="f20-credit-auto-row">
                                                <td><input type="text" class="f20-cds-input" name="rows[credit_customer_1][description]" value="Credit Customer 1" readonly></td>
                                                <td><input type="text" class="f20-cds-input f20-num expense-amount credit-sale-auto-amount" name="rows[credit_customer_1][amount]" value="{{ $f20_amount_zero }}" readonly></td>
                                            </tr>
                                            @php $manual_start = 3; @endphp
                                        @endforelse

                                        <tr class="f20-total-row f20-credit-sales-total-row">
                                            <td>Total</td>
                                            <td><input type="text" id="credit_sales_total" class="f20-cds-input f20-num" value="' + f20AmountZero + '" readonly></td>
                                        </tr>

                                        @for($i = 0; $i < 4; $i++)
                                            @php $manual_row_no = $manual_start + $i; @endphp
                                            <tr class="f20-credit-manual-row">
                                                <td><input type="text" class="f20-cds-input" name="rows[credit_customer_{{ $manual_row_no }}][description]" value="{{ old('rows.credit_customer_'.$manual_row_no.'.description', 'Please Enter') }}"></td>
                                                <td><input type="text" class="f20-cds-input f20-num expense-amount manual-credit-amount" name="rows[credit_customer_{{ $manual_row_no }}][amount]" value="{{ old('rows.credit_customer_'.$manual_row_no.'.amount', $f20_amount_zero) }}"></td>
                                            </tr>
                                        @endfor

                                        <tr class="f20-total-row">
                                            <td>Total</td>
                                            <td><input type="text" id="expenses_total" class="f20-cds-input f20-num" name="expenses_total" value="{{ old('expenses_total', $f20_amount_zero) }}"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <div class="balance-stock-title">Balance Stock</div>
                            @php
                                $selected_tank_location_key = !empty(old('location_id', $default_location_id ?? null)) ? (string) old('location_id', $default_location_id ?? null) : 'all';
                                $display_tanks = (!empty($f20_cds_tanks) && count($f20_cds_tanks)) ? $f20_cds_tanks : collect();
                                $tank_data_by_location = $f20_cds_tank_data_by_location ?? [];
                                $tank_rows = [
                                    'previous_balance' => 'Previous Balance Qty',
                                    'received_qty' => 'Received Qty',
                                    'total' => 'Total',
                                    'issued' => 'Issued',
                                    'balance_qty' => 'Balance Qty',
                                    'testing_qty' => 'Testing Qty',
                                    'days_balance_qty' => "Day's Balance Qty",
                                ];
                            @endphp
                            <table class="f20-grid-table f20-tank-table">
                                <colgroup>
                                    <col style="width:33%;">
                                    @forelse($display_tanks as $tank)
                                        <col style="width:{{ count($display_tanks) ? round(67 / count($display_tanks), 2) : 33 }}%;">
                                    @empty
                                        <col style="width:33%;"><col style="width:34%;">
                                    @endforelse
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Tank Nos</th>
                                        @forelse($display_tanks as $tank)
                                            <th class="f20-tank-head" data-tank-id="{{ $tank->id }}">{{ $tank->tank_name ?: ('Tank ' . $tank->id) }}</th>
                                        @empty
                                            <th>Tank No xxxx</th>
                                            <th>Tank No xxxx</th>
                                        @endforelse
                                    </tr>
                                </thead>
                                <tbody id="f20_tank_body">
                                    @foreach($tank_rows as $stock_key => $stock_label)
                                        <tr data-row-key="{{ $stock_key }}" class="{{ $stock_key == 'days_balance_qty' ? 'f20-total-row' : '' }}">
                                            <td class="row-label">{{ $stock_label }}</td>
                                            @forelse($display_tanks as $tank)
                                                @php
                                                    $tank_location_key = !empty($tank->location_id) ? (string) $tank->location_id : 'all';
                                                    $tank_value = $tank_data_by_location[$tank_location_key][$tank->id][$stock_key] ?? '';
                                                    if ($tank_value === '' && $tank_location_key !== 'all') {
                                                        $tank_value = $tank_data_by_location['all'][$tank->id][$stock_key] ?? '';
                                                    }
                                                    $old_key = 'rows.stock_'.$stock_key.'_'.$tank->id.'.amount';
                                                @endphp
                                                <td>
                                                    <input type="text"
                                                           class="f20-cds-input f20-num tank-cell"
                                                           data-tank-id="{{ $tank->id }}"
                                                           data-row-key="{{ $stock_key }}"
                                                           name="rows[stock_{{ $stock_key }}_{{ $tank->id }}][amount]"
                                                           value="{{ old($old_key, $tank_value) }}">
                                                </td>
                                            @empty
                                                <td><input type="text" class="f20-cds-input f20-num tank-cell" name="rows[stock_{{ $stock_key }}_1][amount]" value="{{ $f20_meter_zero }}"></td>
                                                <td><input type="text" class="f20-cds-input f20-num tank-cell" name="rows[stock_{{ $stock_key }}_2][amount]" value="{{ $f20_meter_zero }}"></td>
                                            @endforelse
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <input type="hidden" name="manager_name" value="{{ old('manager_name') }}">
                    <input type="hidden" name="remarks" value="{{ old('remarks') }}">
                </div>
            </div>

            <div class="f20-cds-save-row">
                <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save F 20 CDS</button>
                <button type="button" onclick="openF20CdsPrintPreview(); return false;" class="btn btn-primary"><i class="fa fa-print"></i> Print</button>
            </div>
            {!! Form::close() !!}
        </div>

        <div class="tab-pane {{ ($active_tab ?? 'form') == 'settings' ? 'active' : '' }}" id="f20_cds_settings_tab">
            <div class="f20-cds-settings-card">
                <h4 style="margin-top:0;"><strong>F 20 CDS Settings</strong></h4>
                {!! Form::open(['url' => action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@storeSettings'), 'method' => 'post']) !!}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('opening_date', 'Opening Date') !!}
                            {!! Form::date('opening_date', old('opening_date', optional($settings)->opening_date ?: $today), ['class' => 'form-control', 'required']) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('form_starting_no', 'Form Starting No') !!}
                            {!! Form::number('form_starting_no', old('form_starting_no', optional($settings)->form_starting_no ?: 1), ['class' => 'form-control', 'min' => 1, 'required']) !!}
                        </div>
                    </div>
                    <div class="col-md-4" style="padding-top:25px;">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button>
                    </div>
                </div>
                {!! Form::close() !!}
                <div class="f20-small-help">The latest saved row becomes the active setting. Saved data is shown below on the same page.</div>

                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped f20-cds-settings-table">
                        <thead>
                            <tr>
                                <th>Opening Date</th>
                                <th>Form Starting No</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($settings_list as $setting_row)
                                <tr>
                                    <td>{{ $setting_row->opening_date }}</td>
                                    <td>{{ $setting_row->form_starting_no }}</td>
                                    <td>{!! $setting_row->is_active ? '<span class="label label-success">Active</span>' : '<span class="label label-default">History</span>' !!}</td>
                                    <td>{{ $setting_row->created_at }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center">No F 20 CDS settings saved yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane {{ ($active_tab ?? 'form') == 'list' ? 'active' : '' }}" id="f20_cds_list_tab">
            <div class="f20-cds-list-card">
                <div class="f20-cds-list-toolbar">
                    <h4 style="margin:0;"><strong>List F 20 CDS</strong></h4>
                    <a href="{{ action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@list') }}" class="btn btn-info btn-sm">
                        <i class="fa fa-external-link"></i> Open Full List Page
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Form No</th>
                                <th>Form Date</th>
                                <th>Society / Location</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(!empty($forms) && count($forms))
                                @foreach($forms as $form)
                                    <tr>
                                        <td>{{ $form->form_no }}</td>
                                        <td>{{ $form->form_date }}</td>
                                        <td>{{ $form->society_name }}</td>
                                        <td><span class="label label-default">{{ ucfirst($form->status) }}</span></td>
                                        <td>{{ $form->created_at }}</td>
                                        <td>
                                            <a href="{{ action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@print', [$form->id]) }}" class="btn btn-xs btn-primary"><i class="fa fa-print"></i> Print</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="6" class="text-center">No F 20 CDS forms saved yet.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if(!empty($forms) && method_exists($forms, 'links'))
                    {{ $forms->links() }}
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function(){
    var meterRows = {
        last_meter: 'Last Meter',
        starting_meter: 'Starting Meter',
        total_sale: 'Total Sale',
        balance: 'Balance',
        pumps_checked: 'Testing',
        cash_sale: 'Cash Sale'
    };

    var tankRows = {
        previous_balance: 'Previous Balance Qty',
        received_qty: 'Received Qty',
        total: 'Total',
        issued: 'Issued',
        balance_qty: 'Balance Qty',
        testing_qty: 'Testing Qty',
        days_balance_qty: "Day's Balance Qty"
    };

    var f20CurrencyPrecision = {{ $f20_currency_precision }};
    var f20MeterPrecision = 3;
    var f20AmountZero = formatFixed(0, f20CurrencyPrecision);
    var f20MeterZero = formatFixed(0, f20MeterPrecision);

    function n(v){ v = (v || '').toString().replace(/,/g,''); var x = parseFloat(v); return isNaN(x) ? 0 : x; }
    function formatFixed(v, decimals){
        var parts = n(v).toFixed(decimals).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }
    function f(v){ return formatFixed(v, f20CurrencyPrecision); }
    function fm(v){ return formatFixed(v, f20MeterPrecision); }
    function recalc(){
        var sales = 0, expenses = 0, creditSales = 0;
        $('.sales-amount, .cash-sale-amount').each(function(){ sales += n($(this).val()); });
        $('.credit-sale-auto-amount').each(function(){ creditSales += n($(this).val()); });
        $('.expense-amount').each(function(){ expenses += n($(this).val()); });
        $('#sales_total').val(f(sales));
        $('#credit_sales_total').val(f(creditSales));
        $('#expenses_total').val(f(expenses));
    }

    function htmlEscape(value){
        return String(value === null || typeof value === 'undefined' ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* IS2295 #2: print from an isolated document so application/theme CSS,
     * sidebar controls and tab links can never leak into browser print preview. */
    window.openF20CdsPrintPreview = function(){
        recalc();

        var source = document.querySelector('#f20_cds_form_tab .f20-cds-paper');
        if (!source) {
            window.print();
            return false;
        }

        var clone = source.cloneNode(true);
        var sourceFields = source.querySelectorAll('input, select, textarea');
        var cloneFields = clone.querySelectorAll('input, select, textarea');

        Array.prototype.forEach.call(cloneFields, function(field, index){
            var sourceField = sourceFields[index];
            if (!sourceField) return;
            if (sourceField.type === 'hidden') {
                field.remove();
                return;
            }

            var textValue = '';
            if (sourceField.tagName === 'SELECT') {
                var selected = sourceField.options[sourceField.selectedIndex];
                textValue = selected ? selected.text : '';
            } else {
                textValue = sourceField.value || '';
            }

            var value = document.createElement('div');
            value.className = 'f20-print-value ' + (sourceField.className || '');
            value.textContent = textValue;
            field.parentNode.replaceChild(value, field);
        });

        var formHead = clone.querySelector('.f20-cds-form-head');
        if (formHead) {
            // IS2316 #2: keep the report identity (location, business, form no
            // and date).  Earlier code hid these blocks, leaving a stretched
            // table with almost no report context in browser print preview.
            formHead.classList.add('f20-print-head');
        }

        Array.prototype.forEach.call(clone.querySelectorAll('.f20-date-shortcuts, button, .no-print'), function(el){
            el.remove();
        });

        var printWindow = window.open('', '_blank', 'width=1200,height=850');
        if (!printWindow) {
            window.print();
            return false;
        }

        printWindow.document.open();
        printWindow.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>F 20 Form – CDS</title><style>
            @page { size: A4 landscape; margin: 5mm; }
            html, body { margin:0; padding:0; background:#fff; color:#111; font-family:Arial,'Noto Sans Sinhala',sans-serif; }
            * { box-sizing:border-box; }
            .f20-cds-paper { width:100%; margin:0; padding:0; }
            .f20-cds-form-head.f20-print-head { display:grid; grid-template-columns:27% 46% 27%; align-items:end; gap:8px; margin:0 0 9px; padding:0 0 7px; border-bottom:2px solid #273849; }
            .f20-cds-form-head.f20-print-head > div { min-width:0; }
            .f20-cds-location-select { width:100%!important; max-width:none!important; height:auto!important; border:0!important; padding:0!important; background:transparent!important; font-size:10.5px!important; font-weight:700; }
            .f20-cds-business-title { text-align:center; font-size:16px!important; line-height:1.15; font-weight:800; padding:0!important; }
            .f20-cds-daily-report { text-align:center; font-size:21px; line-height:1.15; font-weight:800; margin:2px 0 0; }
            .f20-cds-right-head { display:grid; grid-template-columns:65px minmax(0,1fr); gap:3px 5px; align-items:center; width:100%!important; margin:0!important; }
            .f20-cds-label-box, .f20-cds-title-box { min-height:22px; padding:2px 4px; border:1px solid #777; background:#fff; font-size:9.5px; font-weight:700; text-align:center; display:flex; align-items:center; justify-content:center; }
            .f20-cds-title-box { border-color:#8a8a8a; }
            .f20-date-wrap { position:static; }
            .f20-cds-box-input.f20-print-value, .f20-date-wrap .f20-print-value { min-height:22px; border:1px solid #777; padding:2px 4px; font-size:10px; background:#fff; }
            .f20-section-title, .balance-stock-title { font-size:12px; line-height:1.15; font-weight:700; margin:0 0 5px; }
            .f20-grid-table { width:100%; border-collapse:collapse; border-spacing:0; table-layout:fixed; background:#fff; }
            .f20-grid-table th, .f20-grid-table td { border:1px solid #9da6af; height:24px; padding:2px 4px; font-size:10.5px; line-height:1.15; vertical-align:middle; }
            .f20-grid-table th { background:#edf1f4; text-align:center; font-weight:700; white-space:normal; overflow-wrap:anywhere; }
            .f20-grid-table .row-label { background:#edf1f4; font-weight:700; text-align:left; padding:2px 4px; }
            .f20-print-value { width:100%; min-height:22px; padding:2px 4px; display:flex; align-items:center; overflow:hidden; }
            .f20-num { text-align:right; justify-content:flex-end; white-space:nowrap; font-variant-numeric:tabular-nums; }
            .f20-meter-table { margin-bottom:10px; }
            .f20-cds-lower { display:grid; grid-template-columns:minmax(0,1.12fr) minmax(0,.88fr); column-gap:18px; align-items:start; margin-top:0; }
            .daily-sales-wrap { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:6px; }
            .f20-total-row td { font-weight:700; background:#fafafa; }
        </style></head><body>${clone.outerHTML}<script>window.onload=function(){window.focus();window.print();};window.onafterprint=function(){window.close();};<\/script></body></html>`);
        printWindow.document.close();
        return false;
    };

    function rebuildMeterTable(pumps, meterData){
        pumps = pumps || [];
        meterData = meterData || {};

        var colWidth = pumps.length ? (83 / pumps.length).toFixed(2) : 9.22;
        var colgroup = '<col style="width:17%;">';
        var header = '<th>Pump No.</th>';
        var body = '';

        if (pumps.length) {
            $.each(pumps, function(i, pump){
                colgroup += '<col style="width:' + colWidth + '%;">';
                header += '<th class="f20-pump-head" data-pump-id="' + htmlEscape(pump.id) + '">' + htmlEscape(pump.pump_name || ('Pump ' + pump.id)) + '</th>';
            });
        } else {
            for (var p = 1; p <= 9; p++) {
                colgroup += '<col style="width:9.22%;">';
                header += '<th>' + p + '</th>';
            }
        }

        $.each(meterRows, function(rowKey, rowLabel){
            body += '<tr data-row-key="' + htmlEscape(rowKey) + '"><td class="row-label">' + htmlEscape(rowLabel) + '</td>';
            if (pumps.length) {
                $.each(pumps, function(i, pump){
                    var value = (rowKey === 'cash_sale') ? f20AmountZero : f20MeterZero;
                    var pumpId = String(pump.id);
                    if (meterData[pumpId] && typeof meterData[pumpId][rowKey] !== 'undefined') {
                        value = meterData[pumpId][rowKey];
                    }
                    body += '<td><input type="text" class="f20-cds-input f20-num meter-cell ' + (rowKey === 'cash_sale' ? 'cash-sale-amount' : '') + '" data-pump-id="' + htmlEscape(pump.id) + '" data-row-key="' + htmlEscape(rowKey) + '" name="rows[meter_' + htmlEscape(rowKey) + '_' + htmlEscape(pump.id) + '][description]" value="' + htmlEscape(value) + '"></td>';
                });
            } else {
                for (var p = 1; p <= 9; p++) {
                    body += '<td><input type="text" class="f20-cds-input f20-num ' + (rowKey === 'cash_sale' ? 'cash-sale-amount' : '') + '" name="rows[meter_' + htmlEscape(rowKey) + '_' + p + '][description]" value="' + f20AmountZero + '"></td>';
                }
            }
            body += '</tr>';
        });

        $('.f20-meter-table colgroup').html(colgroup);
        $('.f20-meter-table thead tr').html(header);
        $('#f20_meter_body').html(body);
        recalc();
    }


    function rebuildTankTable(tanks, tankData){
        tanks = tanks || [];
        tankData = tankData || {};

        var colWidth = tanks.length ? (67 / tanks.length).toFixed(2) : 33;
        var colgroup = '<col style="width:33%;">';
        var header = '<th>Tank Nos</th>';
        var body = '';

        if (tanks.length) {
            $.each(tanks, function(i, tank){
                colgroup += '<col style="width:' + colWidth + '%;">';
                header += '<th class="f20-tank-head" data-tank-id="' + htmlEscape(tank.id) + '">' + htmlEscape(tank.tank_name || ('Tank ' + tank.id)) + '</th>';
            });
        } else {
            colgroup += '<col style="width:33%;"><col style="width:34%;">';
            header += '<th>Tank No xxxx</th><th>Tank No xxxx</th>';
        }

        $.each(tankRows, function(rowKey, rowLabel){
            var totalClass = rowKey === 'days_balance_qty' ? ' class="f20-total-row"' : '';
            body += '<tr data-row-key="' + htmlEscape(rowKey) + '"' + totalClass + '><td class="row-label">' + htmlEscape(rowLabel) + '</td>';
            if (tanks.length) {
                $.each(tanks, function(i, tank){
                    var value = f20MeterZero;
                    var tankId = String(tank.id);
                    if (tankData[tankId] && typeof tankData[tankId][rowKey] !== 'undefined') {
                        value = tankData[tankId][rowKey];
                    }
                    body += '<td><input type="text" class="f20-cds-input f20-num tank-cell" data-tank-id="' + htmlEscape(tank.id) + '" data-row-key="' + htmlEscape(rowKey) + '" name="rows[stock_' + htmlEscape(rowKey) + '_' + htmlEscape(tank.id) + '][amount]" value="' + htmlEscape(value) + '"></td>';
                });
            } else {
                body += '<td><input type="text" class="f20-cds-input f20-num tank-cell" name="rows[stock_' + htmlEscape(rowKey) + '_1][amount]" value="' + f20AmountZero + '"></td>';
                body += '<td><input type="text" class="f20-cds-input f20-num tank-cell" name="rows[stock_' + htmlEscape(rowKey) + '_2][amount]" value="' + f20AmountZero + '"></td>';
            }
            body += '</tr>';
        });

        $('.f20-tank-table colgroup').html(colgroup);
        $('.f20-tank-table thead tr').html(header);
        $('#f20_tank_body').html(body);
    }


    function rebuildDailySalesCategories(categories, amounts){
        categories = categories || [];
        amounts = amounts || {};

        var rows = Math.max(10, categories.length);
        var body = '';
        for (var i = 0; i < rows; i++) {
            var cat = categories[i] || {};
            var catId = (cat.id || cat.id === 0) ? String(cat.id) : '';
            var name = cat.name || '';
            var amount = catId && typeof amounts[catId] !== 'undefined' ? amounts[catId] : f20AmountZero;
            var rowNo = i + 1;

            body += '<tr>';
            body += '<td><input type="text" class="f20-cds-input daily-sales-desc" data-category-id="' + htmlEscape(catId) + '" name="rows[daily_sales_' + rowNo + '][description]" value="' + htmlEscape(name) + '"></td>';
            body += '<td><input type="text" class="f20-cds-input f20-num sales-amount" data-category-id="' + htmlEscape(catId) + '" name="rows[daily_sales_' + rowNo + '][amount]" value="' + htmlEscape(amount) + '"></td>';
            body += '</tr>';
        }
        $('#f20_daily_sales_body').html(body);
        recalc();
    }


    function rebuildCreditSales(creditSales){
        creditSales = creditSales || [];
        var body = '';
        var rowNo = 1;

        if (creditSales.length) {
            $.each(creditSales, function(i, row){
                var name = row.customer_name || 'Credit Customer';
                var amount = row.amount || f20AmountZero;
                body += '<tr class="f20-credit-auto-row">';
                body += '<td><input type="text" class="f20-cds-input" name="rows[credit_customer_' + rowNo + '][description]" value="' + htmlEscape(name) + '" readonly></td>';
                body += '<td><input type="text" class="f20-cds-input f20-num expense-amount credit-sale-auto-amount" name="rows[credit_customer_' + rowNo + '][amount]" value="' + htmlEscape(amount) + '" readonly></td>';
                body += '</tr>';
                rowNo++;
            });
        } else {
            body += '<tr class="f20-credit-auto-row">';
            body += '<td><input type="text" class="f20-cds-input" name="rows[credit_customer_1][description]" value="Credit Customer 1" readonly></td>';
            body += '<td><input type="text" class="f20-cds-input f20-num expense-amount credit-sale-auto-amount" name="rows[credit_customer_1][amount]" value="' + f20AmountZero + '" readonly></td>';
            body += '</tr>';
            rowNo = 3;
        }

        body += '<tr class="f20-total-row f20-credit-sales-total-row">';
        body += '<td>Total</td>';
        body += '<td><input type="text" id="credit_sales_total" class="f20-cds-input f20-num" value="' + f20AmountZero + '" readonly></td>';
        body += '</tr>';

        for (var i = 0; i < 4; i++) {
            body += '<tr class="f20-credit-manual-row">';
            body += '<td><input type="text" class="f20-cds-input" name="rows[credit_customer_' + rowNo + '][description]" value="Please Enter"></td>';
            body += '<td><input type="text" class="f20-cds-input f20-num expense-amount manual-credit-amount" name="rows[credit_customer_' + rowNo + '][amount]" value="' + f20AmountZero + '"></td>';
            body += '</tr>';
            rowNo++;
        }

        body += '<tr class="f20-total-row">';
        body += '<td>Total</td>';
        body += '<td><input type="text" id="expenses_total" class="f20-cds-input f20-num" name="expenses_total" value="' + f20AmountZero + '"></td>';
        body += '</tr>';

        $('#f20_credit_sales_body').html(body);
        recalc();
    }

    var meterAjaxRequest = null;
    function loadMeterDataInstant(){
        var locationId = $('#f20_cds_location_id').val();
        var formDate = $('#f20_cds_form_date').val();

        // Show immediate feedback while keeping the page in place.
        $('.meter-cell').each(function(){ $(this).val($(this).data('row-key') === 'cash_sale' ? f20AmountZero : f20MeterZero); });
        $('.tank-cell').val(f20MeterZero);
        $('.sales-amount').val(f20AmountZero);
        $('#f20_meter_body').addClass('f20-meter-loading');
        $('#f20_tank_body').addClass('f20-meter-loading');

        if (meterAjaxRequest) {
            meterAjaxRequest.abort();
        }

        meterAjaxRequest = $.ajax({
            url: window.location.href.split('?')[0],
            type: 'GET',
            dataType: 'json',
            cache: false,
            data: {
                ajax_meter_data: 1,
                location_id: locationId,
                form_date: formDate
            },
            success: function(response){
                if (response && response.success) {
                    if (typeof response.currency_precision !== 'undefined') { f20CurrencyPrecision = parseInt(response.currency_precision, 10) || f20CurrencyPrecision; f20AmountZero = formatFixed(0, f20CurrencyPrecision); }
                    rebuildMeterTable(response.pumps || [], response.meter_data || {});
                    rebuildTankTable(response.tanks || [], response.tank_data || {});
                    rebuildDailySalesCategories(response.daily_sales_categories || [], response.daily_sales_amounts || {});
                    rebuildCreditSales(response.credit_sales || []);
                }
            },
            complete: function(){
                $('#f20_meter_body').removeClass('f20-meter-loading');
                $('#f20_tank_body').removeClass('f20-meter-loading');
                meterAjaxRequest = null;
            }
        });
    }

    function dateToYmd(d){
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    $(document).on('focus click', '#f20_cds_form_date', function(){
        $('#f20_cds_date_wrap').addClass('f20-date-open');
    });

    $(document).on('click', '.f20-date-shortcut', function(e){
        e.preventDefault();
        var option = $(this).data('date-option');
        var value = '';
        if (option === 'today') {
            value = dateToYmd(new Date());
        } else if (option === 'yesterday') {
            var d = new Date();
            d.setDate(d.getDate() - 1);
            value = dateToYmd(d);
        }
        $('#f20_cds_form_date').val(value).trigger('change');
        $('#f20_cds_date_wrap').removeClass('f20-date-open');
    });

    $(document).on('click', function(e){
        if (!$(e.target).closest('#f20_cds_date_wrap').length) {
            $('#f20_cds_date_wrap').removeClass('f20-date-open');
        }
    });

    $(document).on('blur', '.meter-cell', function(){
        var rowKey = $(this).data('row-key');
        $(this).val(rowKey === 'cash_sale' ? f($(this).val()) : fm($(this).val()));
    });
    $(document).on('blur', '.tank-cell', function(){ $(this).val(fm($(this).val())); });
    $(document).on('blur', '.sales-amount, .expense-amount', function(){ $(this).val(f($(this).val())); });
    $(document).on('keyup change', '.sales-amount, .expense-amount, .cash-sale-amount', recalc);
    $(document).on('change', '#f20_cds_location_id, #f20_cds_form_date', loadMeterDataInstant);
    recalc();
    $('a[data-toggle="tab"]').on('click', function(e){ e.preventDefault(); $(this).tab('show'); });
});
</script>
@endsection
