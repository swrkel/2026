@php
    /*
    |--------------------------------------------------------------------------
    | Dynamic report input
    |--------------------------------------------------------------------------
    | Pass either:
    |   1. A single $report array/object using the structure in README.txt, or
    |   2. Individual variables such as $businessName, $reportDate, etc.
    |
    | Values passed as individual variables take priority.
    */
    $reportSource = $report ?? [];

    $readReport = static function (string $key, $default = null) use ($reportSource) {
        return data_get($reportSource, $key, $default);
    };

    $businessName = $businessName
        ?? $readReport('business_name')
        ?? config('app.name', 'Business Name');

    $reportDate = $reportDate
        ?? $readReport('date')
        ?? now();

    $shiftNumber = $shiftNumber
        ?? $readReport('shift_number', '—');

    $operatorName = $operatorName
        ?? $readReport('operator_name', '—');

    $printedAt = $printedAt
        ?? $readReport('printed_at')
        ?? now();

    $closeShift = array_merge([
        'total_closed_pump_sales'      => 0,
        'total_payments'               => 0,
        'balance_to_settle'            => 0,
        'current_balance_to_operator'  => 0,
    ], (array) ($closeShift ?? $readReport('close_shift', [])));

    $shiftDetails = array_merge([
        'shift_closed'       => 0,
        'closed_pumps'       => [],
        'total_other_sales'  => 0,
        'balance_to_settle'  => 0,
    ], (array) ($shiftDetails ?? $readReport('shift_details', [])));

    $paymentSummary = array_merge([
        'cash'          => 0,
        'credit_sales'  => 0,
        'credit_cards'  => 0,
        'cheque_sales'  => 0,
        'total'         => null,
    ], (array) ($paymentSummary ?? $readReport('payment_summary', [])));

    if ($paymentSummary['total'] === null) {
        $paymentSummary['total'] =
            (float) $paymentSummary['cash']
            + (float) $paymentSummary['credit_sales']
            + (float) $paymentSummary['credit_cards']
            + (float) $paymentSummary['cheque_sales'];
    }

    $cashBreakdown = collect(
        $cashBreakdown ?? $readReport('cash_breakdown', [])
    );

    $creditSalesDetails = collect(
        $creditSalesDetails ?? $readReport('credit_sales_details', [])
    );

    $formatMoney = static function ($value): string {
        return number_format((float) ($value ?? 0), 2, '.', ',');
    };

    $formatDate = static function ($value, string $format = 'm/d/Y'): string {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    };

    $formatTime = static function ($value): string {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    };

    $closedPumps = $shiftDetails['closed_pumps'];

    if ($closedPumps instanceof \Illuminate\Support\Collection) {
        $closedPumps = $closedPumps->all();
    }

    if (is_array($closedPumps)) {
        $closedPumps = implode(', ', array_filter($closedPumps, static fn ($pump) => $pump !== null && $pump !== ''));
    }

    $closedPumps = $closedPumps ?: '—';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        {{ $businessName }} - Close Shift Summary Report
    </title>

    <style>
        :root {
            --csr-primary: #0868c9;
            --csr-primary-dark: #074b91;
            --csr-heading: #063e7d;
            --csr-text: #10233e;
            --csr-muted: #556983;
            --csr-border: #bdd8f4;
            --csr-border-soft: #d8e7f7;
            --csr-surface: #ffffff;
            --csr-surface-soft: #f5f9fe;
            --csr-row-alt: #f3f8fd;
            --csr-page: #f1f6fb;
            --csr-shadow: 0 12px 36px rgba(27, 62, 104, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            color: var(--csr-text);
            background: var(--csr-page);
        }

        body {
            padding: 28px;
        }

        .csr-report {
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
            padding: 28px 30px 48px;
            overflow: hidden;
            background: var(--csr-surface);
            border: 1px solid rgba(195, 216, 238, 0.7);
            border-radius: 18px;
            box-shadow: var(--csr-shadow);
        }

        .csr-header {
            text-align: center;
        }

        .csr-business-name {
            margin: 0;
            color: #101010;
            font-size: clamp(25px, 3vw, 34px);
            font-weight: 500;
            line-height: 1.2;
            letter-spacing: 0.01em;
        }

        .csr-title-divider {
            position: relative;
            width: 190px;
            height: 16px;
            margin: 6px auto 4px;
        }

        .csr-title-divider::before,
        .csr-title-divider::after {
            position: absolute;
            top: 7px;
            width: 82px;
            height: 1px;
            content: "";
            background: linear-gradient(90deg, transparent, var(--csr-primary));
        }

        .csr-title-divider::before {
            left: 0;
        }

        .csr-title-divider::after {
            right: 0;
            transform: scaleX(-1);
        }

        .csr-title-divider span {
            position: absolute;
            top: 3px;
            left: 50%;
            width: 10px;
            height: 10px;
            border: 2px solid var(--csr-primary);
            transform: translateX(-50%) rotate(45deg);
            background: #fff;
        }

        .csr-report-title {
            margin: 2px 0 12px;
            color: #121212;
            font-size: clamp(25px, 3.2vw, 35px);
            font-weight: 400;
            line-height: 1.25;
        }

        .csr-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 7px 13px;
            margin-bottom: 25px;
            color: var(--csr-muted);
            font-size: 16px;
            font-weight: 500;
        }

        .csr-meta-separator {
            color: #8396ab;
        }

        .csr-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 34px;
            margin: 0 2px 22px;
        }

        .csr-panel-heading {
            margin: 0 0 12px 27px;
            color: var(--csr-heading);
            font-size: 23px;
            font-weight: 700;
            line-height: 1.2;
        }

        .csr-summary-card {
            padding: 7px 22px;
            background:
                linear-gradient(120deg, rgba(241, 248, 255, 0.95), rgba(255, 255, 255, 0.98));
            border: 1px solid var(--csr-border);
            border-radius: 9px;
        }

        .csr-summary-row {
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            align-items: center;
            min-height: 45px;
            gap: 10px;
            border-bottom: 1px solid var(--csr-border-soft);
        }

        .csr-summary-row:last-child {
            border-bottom: 0;
        }

        .csr-summary-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--csr-primary);
        }

        .csr-summary-icon svg {
            width: 25px;
            height: 25px;
            stroke-width: 1.9;
        }

        .csr-summary-label {
            min-width: 0;
            font-size: 16px;
            line-height: 1.3;
        }

        .csr-summary-value {
            padding-left: 8px;
            text-align: right;
            white-space: nowrap;
            font-size: 16px;
            font-weight: 500;
        }

        .csr-section {
            margin: 0 23px 12px;
        }

        .csr-section-heading {
            display: flex;
            align-items: center;
            gap: 13px;
            margin: 0 0 7px;
            color: var(--csr-heading);
            font-size: 23px;
            font-weight: 700;
            line-height: 1.2;
        }

        .csr-heading-icon {
            display: inline-flex;
            flex: 0 0 58px;
            width: 58px;
            height: 50px;
            align-items: center;
            justify-content: center;
            color: var(--csr-primary);
            background: linear-gradient(145deg, #eef6ff, #dbeafb);
            border: 1px solid #d5e5f7;
            border-radius: 10px;
        }

        .csr-heading-icon svg {
            width: 31px;
            height: 31px;
            stroke-width: 1.85;
        }

        .csr-heading-accent {
            width: 6px;
            height: 31px;
            margin-left: 8px;
            background: var(--csr-primary);
            border-radius: 5px;
        }

        .csr-table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #c5d9ed;
            border-radius: 6px;
        }

        .csr-table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .csr-table th,
        .csr-table td {
            height: 42px;
            padding: 8px 12px;
            text-align: center;
            vertical-align: middle;
            border-right: 1px solid #c9ddef;
            border-bottom: 1px solid #d4e2f0;
            font-size: 15px;
        }

        .csr-table th:last-child,
        .csr-table td:last-child {
            border-right: 0;
        }

        .csr-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .csr-table th {
            color: #fff;
            background: linear-gradient(180deg, #1078d9, #0863b9);
            font-weight: 700;
        }

        .csr-table tbody tr:nth-child(even) {
            background: var(--csr-row-alt);
        }

        .csr-table .csr-amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .csr-empty {
            color: var(--csr-muted);
            font-style: italic;
        }

        .csr-footer {
            display: grid;
            grid-template-columns: minmax(250px, 1fr) minmax(330px, 1fr);
            gap: 56px;
            align-items: end;
            margin: 34px 25px 0;
            padding-top: 72px;
            border-top: 1px solid #bad5f0;
        }

        .csr-signature {
            max-width: 365px;
            text-align: center;
        }

        .csr-signature-line {
            height: 1px;
            margin-bottom: 17px;
            background: #9bc5eb;
        }

        .csr-signature-label {
            font-size: 16px;
        }

        .csr-print-area {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 27px;
        }

        .csr-printed-at {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            color: var(--csr-text);
            white-space: nowrap;
            font-size: 16px;
        }

        .csr-printed-at svg {
            width: 26px;
            height: 26px;
            color: var(--csr-primary);
            stroke-width: 1.9;
        }

        .csr-print-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 11px;
            min-width: 288px;
            min-height: 56px;
            padding: 13px 22px;
            color: #fff;
            background: linear-gradient(180deg, #0b74d4, #0758a8);
            border: 0;
            border-radius: 7px;
            box-shadow: 0 8px 18px rgba(7, 88, 168, 0.28);
            cursor: pointer;
            font: inherit;
            font-size: 18px;
            font-weight: 700;
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .csr-print-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 11px 22px rgba(7, 88, 168, 0.34);
        }

        .csr-print-button:focus-visible {
            outline: 3px solid rgba(8, 104, 201, 0.28);
            outline-offset: 3px;
        }

        .csr-print-button svg {
            width: 27px;
            height: 27px;
            stroke-width: 1.9;
        }

        .csr-svg-symbols {
            position: absolute;
            width: 0;
            height: 0;
            overflow: hidden;
        }

        @media (max-width: 850px) {
            body {
                padding: 12px;
            }

            .csr-report {
                padding: 24px 16px 36px;
            }

            .csr-summary-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .csr-section {
                margin-right: 0;
                margin-left: 0;
            }

            .csr-footer {
                grid-template-columns: 1fr;
                gap: 34px;
                padding-top: 44px;
            }

            .csr-signature {
                width: 100%;
                max-width: none;
            }

            .csr-print-area {
                align-items: center;
            }

            .csr-printed-at {
                white-space: normal;
                text-align: center;
            }
        }

        @media (max-width: 520px) {
            .csr-meta-separator {
                display: none;
            }

            .csr-meta {
                flex-direction: column;
            }

            .csr-summary-card {
                padding-right: 12px;
                padding-left: 12px;
            }

            .csr-summary-row {
                grid-template-columns: 30px minmax(0, 1fr);
                padding: 9px 0;
            }

            .csr-summary-value {
                grid-column: 2;
                width: 100%;
                padding-left: 0;
                text-align: left;
            }

            .csr-panel-heading {
                margin-left: 0;
            }

            .csr-section-heading {
                font-size: 20px;
            }

            .csr-heading-icon {
                flex-basis: 50px;
                width: 50px;
                height: 44px;
            }

            .csr-print-button {
                width: 100%;
                min-width: 0;
            }
        }

        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        @media print {
            :root {
                --csr-page: #fff;
                --csr-shadow: none;
            }

            html,
            body {
                width: 100%;
                min-height: 0;
                background: #fff !important;
            }

            body {
                padding: 0;
            }

            .csr-report {
                max-width: none;
                padding: 0;
                overflow: visible;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .csr-business-name {
                font-size: 23px;
            }

            .csr-report-title {
                font-size: 25px;
            }

            .csr-meta {
                margin-bottom: 16px;
                font-size: 12px;
            }

            .csr-summary-grid {
                gap: 15px;
                margin-bottom: 13px;
            }

            .csr-panel-heading,
            .csr-section-heading {
                font-size: 16px;
            }

            .csr-panel-heading {
                margin-bottom: 6px;
                margin-left: 15px;
            }

            .csr-summary-card {
                padding: 4px 12px;
            }

            .csr-summary-row {
                min-height: 34px;
                grid-template-columns: 25px minmax(0, 1fr) auto;
                gap: 6px;
            }

            .csr-summary-icon svg {
                width: 19px;
                height: 19px;
            }

            .csr-summary-label,
            .csr-summary-value {
                font-size: 11px;
            }

            .csr-section {
                margin: 0 10px 8px;
            }

            .csr-section-heading {
                gap: 8px;
                margin-bottom: 5px;
            }

            .csr-heading-icon {
                flex-basis: 38px;
                width: 38px;
                height: 34px;
            }

            .csr-heading-icon svg {
                width: 23px;
                height: 23px;
            }

            .csr-heading-accent {
                width: 4px;
                height: 22px;
                margin-left: 4px;
            }

            .csr-table-wrap {
                overflow: visible;
            }

            .csr-table {
                min-width: 0;
            }

            .csr-table th,
            .csr-table td {
                height: 29px;
                padding: 4px 7px;
                font-size: 10px;
            }

            .csr-footer {
                grid-template-columns: 1fr 1fr;
                gap: 38px;
                margin-top: 15px;
                padding-top: 40px;
            }

            .csr-signature-label,
            .csr-printed-at {
                font-size: 10px;
            }

            .csr-printed-at svg {
                width: 18px;
                height: 18px;
            }

            .csr-print-button {
                display: none !important;
            }

            .csr-print-area {
                gap: 0;
            }

            .csr-table thead {
                display: table-header-group;
            }

            .csr-table tr,
            .csr-summary-card,
            .csr-section-heading,
            .csr-footer {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .csr-report,
            .csr-report * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    {{-- Local SVG symbol library: no external icon package is required. --}}
    <svg class="csr-svg-symbols" aria-hidden="true">
        <symbol id="csr-icon-coins" viewBox="0 0 24 24">
            <ellipse cx="12" cy="5" rx="7" ry="3"></ellipse>
            <path d="M5 5v5c0 1.7 3.1 3 7 3s7-1.3 7-3V5"></path>
            <path d="M5 10v5c0 1.7 3.1 3 7 3 1.1 0 2.1-.1 3-.3"></path>
            <path d="M15.5 14.5h4"></path>
            <path d="M17.5 12.5v4"></path>
        </symbol>

        <symbol id="csr-icon-card" viewBox="0 0 24 24">
            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
            <path d="M3 9h18"></path>
            <path d="M7 15h4"></path>
        </symbol>

        <symbol id="csr-icon-balance" viewBox="0 0 24 24">
            <path d="M12 3v18"></path>
            <path d="M5 6h14"></path>
            <path d="m7 6-4 7h8L7 6Z"></path>
            <path d="m17 6-4 7h8l-4-7Z"></path>
            <path d="M8 21h8"></path>
        </symbol>

        <symbol id="csr-icon-user" viewBox="0 0 24 24">
            <circle cx="12" cy="8" r="4"></circle>
            <path d="M4 21a8 8 0 0 1 16 0"></path>
            <path d="m17 17 2 2 3-4"></path>
        </symbol>

        <symbol id="csr-icon-check" viewBox="0 0 24 24">
            <rect x="4" y="4" width="16" height="16" rx="2"></rect>
            <path d="m8 12 3 3 5-7"></path>
        </symbol>

        <symbol id="csr-icon-pump" viewBox="0 0 24 24">
            <path d="M5 21V4h10v17"></path>
            <path d="M3 21h14"></path>
            <path d="M7 7h6v5H7z"></path>
            <path d="M15 8h2l3 3v7a2 2 0 0 1-4 0v-4"></path>
            <path d="m18 9 2-2"></path>
        </symbol>

        <symbol id="csr-icon-tag" viewBox="0 0 24 24">
            <path d="M20 13 11 22l-9-9V4h9l9 9Z"></path>
            <circle cx="7.5" cy="9.5" r="1.5"></circle>
        </symbol>

        <symbol id="csr-icon-chart" viewBox="0 0 24 24">
            <path d="M4 20V10"></path>
            <path d="M10 20V4"></path>
            <path d="M16 20v-7"></path>
            <path d="M22 20H2"></path>
            <path d="M22 20V7"></path>
        </symbol>

        <symbol id="csr-icon-money" viewBox="0 0 24 24">
            <path d="M8 4h8l2 4H6l2-4Z"></path>
            <path d="M6 8c-3 3-4 6-4 9 0 3 3 5 10 5s10-2 10-5c0-3-1-6-4-9"></path>
            <path d="M12 11v7"></path>
            <path d="M15 13.5c0-1-1.3-1.5-3-1.5s-3 .5-3 1.5 1.3 1.5 3 1.5 3 .5 3 1.5-1.3 1.5-3 1.5-3-.5-3-1.5"></path>
        </symbol>

        <symbol id="csr-icon-calendar-clock" viewBox="0 0 24 24">
            <rect x="3" y="5" width="14" height="14" rx="2"></rect>
            <path d="M7 3v4"></path>
            <path d="M13 3v4"></path>
            <path d="M3 9h14"></path>
            <circle cx="18" cy="17" r="4"></circle>
            <path d="M18 15v2l1.5 1"></path>
        </symbol>

        <symbol id="csr-icon-printer" viewBox="0 0 24 24">
            <path d="M6 9V3h12v6"></path>
            <rect x="6" y="14" width="12" height="7" rx="1"></rect>
            <path d="M6 17H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"></path>
            <path d="M18 12h.01"></path>
        </symbol>
    </svg>

    <main class="csr-report">
        <header class="csr-header">
            <h1 class="csr-business-name">{{ $businessName }}</h1>

            <div class="csr-title-divider" aria-hidden="true">
                <span></span>
            </div>

            <h2 class="csr-report-title">Close Shift Summary Report</h2>

            <div class="csr-meta">
                <span>Date: {{ $formatDate($reportDate) }}</span>
                <span class="csr-meta-separator">|</span>
                <span>Shift No: {{ $shiftNumber }}</span>
                <span class="csr-meta-separator">|</span>
                <span>Operator: {{ $operatorName }}</span>
            </div>
        </header>

        <section class="csr-summary-grid">
            <div>
                <h3 class="csr-panel-heading">Close Shift</h3>

                <div class="csr-summary-card">
                    <div class="csr-summary-row">
                        <span class="csr-summary-icon">
                            <svg><use href="#csr-icon-coins"></use></svg>
                        </span>
                        <span class="csr-summary-label">Total Sale of All Closed Pumps:</span>
                        <span class="csr-summary-value">
                            Rs. {{ $formatMoney($closeShift['total_closed_pump_sales']) }}
                        </span>
                    </div>

                    <div class="csr-summary-row">
                        <span class="csr-summary-icon">
                            <svg><use href="#csr-icon-card"></use></svg>
                        </span>
                        <span class="csr-summary-label">Total Payments:</span>
                        <span class="csr-summary-value">
                            Rs. {{ $formatMoney($closeShift['total_payments']) }}
                        </span>
                    </div>

                    <div class="csr-summary-row">
                        <span class="csr-summary-icon">
                            <svg><use href="#csr-icon-balance"></use></svg>
                        </span>
                        <span class="csr-summary-label">Balance to Settle:</span>
                        <span class="csr-summary-value">
                            Rs. {{ $formatMoney($closeShift['balance_to_settle']) }}
                        </span>
                    </div>

                    <div class="csr-summary-row">
                        <span class="csr-summary-icon">
                            <svg><use href="#csr-icon-user"></use></svg>
                        </span>
                        <span class="csr-summary-label">Current Balance to Operator:</span>
                        <span class="csr-summary-value">
                            Rs. {{ $formatMoney($closeShift['current_balance_to_operator']) }}
                        </span>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="csr-panel-heading">Shift Details</h3>

                <div class="csr-summary-card">
                    <div class="csr-summary-row">
                        <span class="csr-summary-icon">
                            <svg><use href="#csr-icon-check"></use></svg>
                        </span>
                        <span class="csr-summary-label">Shift Closed:</span>
                        <span class="csr-summary-value">{{ $shiftDetails['shift_closed'] }}</span>
                    </div>

                    <div class="csr-summary-row">
                        <span class="csr-summary-icon">
                            <svg><use href="#csr-icon-pump"></use></svg>
                        </span>
                        <span class="csr-summary-label">No of Closed Pumps:</span>
                        <span class="csr-summary-value">{{ $closedPumps }}</span>
                    </div>

                    <div class="csr-summary-row">
                        <span class="csr-summary-icon">
                            <svg><use href="#csr-icon-tag"></use></svg>
                        </span>
                        <span class="csr-summary-label">Total Other Sales:</span>
                        <span class="csr-summary-value">
                            Rs. {{ $formatMoney($shiftDetails['total_other_sales']) }}
                        </span>
                    </div>

                    <div class="csr-summary-row">
                        <span class="csr-summary-icon">
                            <svg><use href="#csr-icon-balance"></use></svg>
                        </span>
                        <span class="csr-summary-label">Balance to Settle:</span>
                        <span class="csr-summary-value">
                            Rs. {{ $formatMoney($shiftDetails['balance_to_settle']) }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="csr-section">
            <h3 class="csr-section-heading">
                <span class="csr-heading-icon">
                    <svg><use href="#csr-icon-chart"></use></svg>
                </span>
                <span class="csr-heading-accent" aria-hidden="true"></span>
                <span>Payment Summary</span>
            </h3>

            <div class="csr-table-wrap">
                <table class="csr-table">
                    <thead>
                        <tr>
                            <th>Cash</th>
                            <th>Credit Sales</th>
                            <th>Credit Cards</th>
                            <th>Cheque Sales</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="csr-amount">{{ $formatMoney($paymentSummary['cash']) }}</td>
                            <td class="csr-amount">{{ $formatMoney($paymentSummary['credit_sales']) }}</td>
                            <td class="csr-amount">{{ $formatMoney($paymentSummary['credit_cards']) }}</td>
                            <td class="csr-amount">{{ $formatMoney($paymentSummary['cheque_sales']) }}</td>
                            <td class="csr-amount">{{ $formatMoney($paymentSummary['total']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="csr-section">
            <h3 class="csr-section-heading">
                <span class="csr-heading-icon">
                    <svg><use href="#csr-icon-money"></use></svg>
                </span>
                <span>Cash Breakdown</span>
            </h3>

            <div class="csr-table-wrap">
                <table class="csr-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Order No.</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cashBreakdown as $row)
                            <tr>
                                <td>{{ $formatDate(data_get($row, 'date')) }}</td>
                                <td>{{ $formatTime(data_get($row, 'time', data_get($row, 'created_at'))) }}</td>
                                <td>{{ data_get($row, 'customer_name', '—') ?: '—' }}</td>
                                <td>{{ data_get($row, 'order_number', '—') ?: '—' }}</td>
                                <td class="csr-amount">{{ $formatMoney(data_get($row, 'amount')) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="csr-empty">No cash transactions found for this shift.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="csr-section">
            <h3 class="csr-section-heading">
                <span class="csr-heading-icon">
                    <svg><use href="#csr-icon-card"></use></svg>
                </span>
                <span>Credit Sales Details</span>
            </h3>

            <div class="csr-table-wrap">
                <table class="csr-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Order No.</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($creditSalesDetails as $row)
                            <tr>
                                <td>{{ $formatDate(data_get($row, 'date')) }}</td>
                                <td>{{ $formatTime(data_get($row, 'time', data_get($row, 'created_at'))) }}</td>
                                <td>{{ data_get($row, 'customer_name', '—') ?: '—' }}</td>
                                <td>{{ data_get($row, 'order_number', '—') ?: '—' }}</td>
                                <td class="csr-amount">{{ $formatMoney(data_get($row, 'amount')) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="csr-empty">No credit sales found for this shift.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <footer class="csr-footer">
            <div class="csr-signature">
                <div class="csr-signature-line"></div>
                <div class="csr-signature-label">Signature Pump Operator</div>
            </div>

            <div class="csr-print-area">
                <div class="csr-printed-at">
                    <svg><use href="#csr-icon-calendar-clock"></use></svg>
                    <span>
                        Printed Date &amp; Time:
                        {{ $formatDate($printedAt, 'm/d/Y H:i:s') }}
                    </span>
                </div>

                <button
                    type="button"
                    class="csr-print-button"
                    onclick="window.print()"
                >
                    <svg><use href="#csr-icon-printer"></use></svg>
                    <span>Print Shift Summary</span>
                </button>
            </div>
        </footer>
    </main>
</body>
</html>
