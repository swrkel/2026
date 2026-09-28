@extends('layouts.app')

@section('title', 'Daily Summary Sheet')

@section('content')
    <style>
        /* Free Issue column styling */
        .free-issue-header {
            background-color: #f0fff0 !important;
            color: #006600 !important;
            border-left: 2px solid #4CAF50 !important;
        }

        .free-issue-cell {
            background-color: #f0fff0 !important;
            color: #006600 !important;
            font-weight: bold;
        }

        /* Ensure free issue columns are visible */
        #sheet_table th.free-issue-header,
        #sheet_table td.free-issue-cell {
            border: 1px solid #4CAF50 !important;
        }

        /* Product column base width */
        :root {
            --product-col-width: 70px;
            --lubricant-last-col-width: 120px;
            --free-issue-col-width: 100px;
            /* Slightly wider for free issue columns */
        }

        /* Make free issue columns slightly wider */
        .free-issue-header,
        .free-issue-cell {
            width: var(--free-issue-col-width) !important;
            min-width: var(--free-issue-col-width) !important;
            max-width: var(--free-issue-col-width) !important;
        }

        /* A4 Page Size: 210mm x 297mm */
        .a4-page {
            width: 100%;
            min-height: 297mm;
            max-height: 297mm;
            margin: 0 auto 10mm auto;
            padding: 10mm;
            box-sizing: border-box;
            background: #fff;
            font-family: Arial, sans-serif;
            font-size: 11px;
            page-break-after: always;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .a4-page:last-child {
            page-break-after: auto;
        }

        .sheet {
            margin: 0 auto;
            padding: 10mm;
            box-sizing: border-box;
            background: #fff;
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        /* Page content area - flexible to fill available space */
        .page-content {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Table container with max height */
        .table-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: auto;
            min-height: 0;
        }

        .stock-status-card.products-summary {
            border: none !important;
            margin: 0;
            padding: 0;
        }

        .sheet .header {
            text-align: center;
            margin-bottom: 6px;
        }

        .sheet .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
            flex-wrap: nowrap;
        }

        .header-row>div {
            flex: 1;
            text-align: left;
            min-width: 0;
            margin-right: 8px;
        }

        .header-row>div:last-child {
            margin-right: 0;
        }

        .header-row .form-control,
        .header-row .select2-container {
            width: 100% !important;
            font-size: 11px;
        }

        .header-row label {
            font-size: 11px;
            margin-bottom: 2px;
            white-space: nowrap;
        }

        :root {
            --product-col-width: 70px;
            --lubricant-last-col-width: 120px;
        }

        .stock-status-table {
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
        }

        #sheet_table {
            table-layout: fixed;
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
        }

        #sheet_table th,
        #sheet_table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: left;
            vertical-align: middle;
        }

        .sheet table th,
        .sheet table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: left;
            vertical-align: bottom;
        }

        #sheet_table thead th {
            color: #0066cc;
            font-weight: bold;
            background-color: #fff;
            border-top: 1px solid #ccc;
        }

        #sheet_table thead tr:first-child th {
            border-top: 1px solid #ccc;
        }

        #sheet_table thead th:nth-child(2),
        #sheet_table tbody td:nth-child(2),
        #sheet_table tfoot td:nth-child(2) {
            width: 120px;
            min-width: 120px;
        }

        /* Add separator between Customer column and first product column */
        #sheet_table thead th:nth-child(3),
        #sheet_table tbody td:nth-child(3),
        #sheet_table tfoot td:nth-child(3) {
            border-right: 2px solid #000 !important;
        }

        #sheet_table thead th.product-header {
            /* Clear visual divider for each product in the header */
            border-right: 1px solid #000 !important;
            border-left: 1px solid #000 !important;
            border-top: 1px solid #000 !important;
        }

        /* Ensure first product column has left border to separate from Customer */
        #sheet_table thead th.product-header:first-of-type,
        #sheet_table tbody td.product-cell:first-of-type,
        #sheet_table tfoot td.product-cell:first-of-type {
            border-left: 2px solid #000 !important;
        }

        /* Thicker separator after every second product column:
                                                                                                       between 19 & 18, 17 & 16, 15 & 14, 13 & 12, 11 & 10, etc. */
        #sheet_table thead th.product-header:nth-child(2n + 5),
        #sheet_table tbody td.product-cell:nth-child(2n + 5),
        #sheet_table tfoot td.product-cell:nth-child(2n + 5) {
            border-right: 2px solid #000 !important;
        }

        #sheet_table tbody td.product-cell {
            /* Divider between product columns in body */
            border-right: 1px solid #000 !important;
            border-left: 1px solid #000 !important;
        }

        #sheet_table tfoot td.product-cell {
            /* Divider between product columns in footer */
            border-right: 1px solid #000 !important;
            border-left: 1px solid #000 !important;
        }

        #sheet_table thead th.product-header:nth-last-child(4),
        #sheet_table tbody td:nth-last-child(4),
        #sheet_table tfoot td:nth-last-child(4),
        #sheet_table th.product-header.last-product-col,
        #sheet_table td.product-cell.last-product-col {
            border-right: 1px solid #000 !important;
        }

        /* Static width for Gross Sale, Discount, Net Sale columns */
        #sheet_table th.sticky-amount-col,
        #sheet_table td.sticky-amount-col {
            width: 80px;
            min-width: 80px;
            max-width: 80px;
            text-align: right;
        }

        /* Discount column is 70px to match print view */
        #sheet_table thead th:nth-last-child(2),
        #sheet_table tbody td:nth-last-child(2),
        #sheet_table tfoot td:nth-last-child(2) {
            width: 70px !important;
            min-width: 70px !important;
            max-width: 70px !important;
        }

        /* Sticky amount columns (Gross Sale, Discount, Net Sale) */
        #sheet_table thead th:nth-last-child(3),
        #sheet_table tbody td:nth-last-child(3),
        #sheet_table tfoot td:nth-last-child(3) {
            position: sticky;
            right: 160px;
            background-color: #fff;
            z-index: 5;
            border-left: 1px solid #000 !important;
            box-shadow: inset 1px 0 0 0 #000, -1px 0 0 0 #000;
        }

        #sheet_table thead th:nth-last-child(2),
        #sheet_table tbody td:nth-last-child(2),
        #sheet_table tfoot td:nth-last-child(2) {
            position: sticky;
            right: 80px;
            background-color: #fff;
            z-index: 5;
            border-left: 2px solid #0066cc;
            width: 70px !important;
            min-width: 70px !important;
            max-width: 70px !important;
        }

        #sheet_table thead th:last-child,
        #sheet_table tbody td:last-child,
        #sheet_table tfoot td:last-child {
            position: sticky;
            right: 0;
            background-color: #fff;
            z-index: 5;
            border-left: 2px solid #0066cc;
        }

        /* Ensure sticky columns maintain their width */
        #sheet_table tbody td.sticky-amount-col,
        #sheet_table tfoot td.sticky-amount-col {
            position: sticky;
            background-color: #fff;
            z-index: 5;
        }

        #sheet_table tbody td.sticky-amount-col:nth-last-child(3) {
            right: 160px;
            border-left: 1px solid #000 !important;
            box-shadow: inset 1px 0 0 0 #000, -1px 0 0 0 #000;
        }

        #sheet_table tbody td.sticky-amount-col:nth-last-child(2) {
            right: 80px;
            border-left: 2px solid #0066cc;
        }

        #sheet_table tbody td.sticky-amount-col:last-child {
            right: 0;
            border-left: 2px solid #0066cc;
        }

        .sheet table thead th {
            color: #0066cc;
            font-weight: bold;
        }

        #products_summary_table {
            min-width: var(--products-summary-table-min-width, 65%) !important;
            max-width: var(--products-summary-table-max-width, 65%) !important;
            width: var(--products-summary-table-max-width, 65%) !important;
            table-layout: fixed;
        }

        /* Specific CSS classes for different categories */
        #products_summary_table.category-fuel {
            min-width: 82.5% !important;
            max-width: 82.5% !important;
            width: 82.5% !important;
        }

        #products_summary_table.category-lubricant {
            min-width: 84% !important;
            max-width: 84% !important;
            width: 84% !important;
        }

        #products_summary_table.category-other {
            min-width: 82.5% !important;
            max-width: 82.5% !important;
            width: 82.5% !important;
        }

        /* Also target the parent containers for lubricant */
        .stock-status-card.products-summary:has(#products_summary_table.category-lubricant) {
            width: 84% !important;
            max-width: 84% !important;
        }

        .stock-left:has(#products_summary_table.category-lubricant) {
            width: 84% !important;
            max-width: 84% !important;
            flex: none !important;
        }

        /* Alternative approach using classes on parent containers */
        .stock-status-card.products-summary.category-lubricant {
            width: 84% !important;
            max-width: 84% !important;
        }

        .stock-left.category-lubricant {
            width: 84% !important;
            max-width: 84% !important;
            flex: none !important;
        }

        .stock-status-card.products-summary.category-fuel {
            width: 82.5% !important;
            max-width: 82.5% !important;
        }

        .stock-left.category-fuel {
            width: 82.5% !important;
            max-width: 82.5% !important;
            flex: none !important;
        }

        #products_summary_table th:first-child,
        #products_summary_table td:first-child {
            /* Use dynamic width so Lubricant and Fuel can differ */
            width: var(--products-summary-label-width);
            min-width: var(--products-summary-label-width);
            max-width: var(--products-summary-label-width);
        }

        #products_summary_table td:not(:first-child) {
            width: 70px;
            min-width: 70px;
            max-width: 70px;
        }

        .summary-flex {
            clear: both;
            margin-top: 10px;
        }

        .stock-left {
            overflow-x: auto;
            flex: 1;
            min-width: 0;
        }

        #products_summary_table th:nth-child(3),
        #products_summary_table td:nth-child(3) {
            /* Label column (e.g. Store - Day Opening Qty) width is dynamic per category
                                                                                                           via --products-summary-label-width, so Fuel can be narrower than Lubricant. */
            width: var(--products-summary-label-width) !important;
            min-width: var(--products-summary-label-width) !important;
            max-width: var(--products-summary-label-width) !important;
            text-align: left;
            font-weight: bold;
        }

        #products_summary_table td.hide-cell,
        #products_summary_table th.hide-cell {
            display: none;
        }

        #products_summary_table .product-header-summary {
            width: var(--product-col-width) !important;
            min-width: var(--product-col-width) !important;
            max-width: var(--product-col-width) !important;
            overflow: visible !important;
        }

        /* Special wider columns for Lubricant category - last 2 columns in summary table */
        #products_summary_table .product-header-summary.lubricant-wide {
            width: var(--lubricant-last-col-width) !important;
            min-width: var(--lubricant-last-col-width) !important;
            max-width: var(--lubricant-last-col-width) !important;
        }

        #products_summary_table td.product-cell {
            width: var(--product-col-width) !important;
            min-width: var(--product-col-width) !important;
            max-width: var(--product-col-width) !important;
            text-align: center;
        }

        /* Special wider columns for Lubricant category - last 2 columns in summary table cells */
        #products_summary_table td.product-cell.lubricant-wide {
            width: var(--lubricant-last-col-width) !important;
            min-width: var(--lubricant-last-col-width) !important;
            max-width: var(--lubricant-last-col-width) !important;
        }

        .product-header,
        .product-cell {
            width: var(--product-col-width);
            min-width: var(--product-col-width);
            max-width: var(--product-col-width);
            text-align: center;
            box-sizing: border-box;
            overflow: visible;
            white-space: nowrap;
        }

        /* Special wider columns for Lubricant category - last 2 columns */
        .product-header.lubricant-wide,
        .product-cell.lubricant-wide {
            width: var(--lubricant-last-col-width) !important;
            min-width: var(--lubricant-last-col-width) !important;
            max-width: var(--lubricant-last-col-width) !important;
        }

        #sheet_table thead th.product-header.lubricant-wide,
        #sheet_table tbody td.product-cell.lubricant-wide,
        #sheet_table tfoot td.product-cell.lubricant-wide {
            width: var(--lubricant-last-col-width) !important;
            min-width: var(--lubricant-last-col-width) !important;
            max-width: var(--lubricant-last-col-width) !important;
        }

        #sheet_table td.product-cell {
            width: var(--product-col-width);
            min-width: var(--product-col-width);
            max-width: var(--product-col-width);
            text-align: center;
            box-sizing: border-box;
        }

        .product-header-summary,
        .stock-status-table td.product-cell {
            width: var(--product-col-width);
            min-width: var(--product-col-width);
            max-width: var(--product-col-width);
            text-align: center;
        }

        .product-header {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            white-space: nowrap;
            color: #0066cc;
            font-weight: bold;
            min-width: var(--product-col-width) !important;
            max-width: var(--product-col-width) !important;
            width: var(--product-col-width) !important;
            height: auto;
            min-height: 80px;
            text-align: left;
            vertical-align: bottom;
            padding: 12px 4px 4px 4px !important;
            overflow: visible;
        }

        .table-scroll {
            overflow-x: hidden;
            overflow-y: visible;
            width: 100%;
            margin-top: 0;
            position: relative;
        }

        .table-scroll-wrapper {
            overflow-x: hidden;
            overflow-y: visible;
            width: 100%;
            position: relative;
        }

        .page-content-wrapper {
            overflow-x: visible;
            overflow-y: visible;
            width: 100%;
        }

        .page-content-wrapper>* {
            overflow-x: visible;
        }

        .sheet-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
            text-align: center;
        }

        .business-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
            text-align: center;
        }

        .business-location {
            font-size: 14px;
            font-weight: normal;
            margin-bottom: 8px;
            text-align: center;
        }

        .header-field {
            display: inline-block;
            margin: 0 8px;
        }

        .header-field label {
            font-weight: bold;
            margin-right: 3px;
        }

        .signature-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 10px;
            width: 100%;
        }

        .signature-section>div {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .signature-section>div:last-child {
            align-items: flex-end;
        }

        .signature-line {
            border-top: 1px dotted #333;
            width: 180px;
            text-align: center;
            padding-top: 4px;
            font-weight: bold;
            font-size: 11px;
        }

        .summary-flex {
            display: flex;
            /* gap: 6px; */
            margin-top: 4px;
            align-items: flex-start;
        }

        .stock-left {
            flex: 1;
            overflow-x: auto;
            overflow-y: visible;
            min-width: 0;
            margin: 0;
            padding: 0;
        }

        .right-stack {
            width: 20%;
            min-width: 200px;
            display: flex;
            flex-direction: column;
            position: sticky;
            right: 0;
            align-self: flex-start;
            z-index: 10;
            background: #fff;
            flex-shrink: 0;
            margin: 0;
            padding: 0;
        }

        .summary-flex {
            position: relative;
            display: flex;
            align-items: flex-start;
            overflow-x: visible;
            gap: 0;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .cash-card {
            position: sticky;
            right: 0;
            z-index: 10;
            background: #fff;
            flex-shrink: 0;
        }

        .products-summary {
            position: relative;
            z-index: 1;
            flex: 1;
            min-width: 0;
            overflow-x: auto;
            overflow-y: visible;
            margin: 0;
            padding: 0;
        }

        /* Page content wrapper - controls bottom scrollbar for table and summary */
        .page-content-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            width: 100%;
        }

        .stock-status-card,
        .cash-card,
        .calls-card {
            border: 1px solid #000;
            padding: 0;
            background: #fff;
        }

        .stock-status-card {
            flex: 0 0 auto;
        }

        .cash-card {
            flex: 0 0 auto;
        }

        .calls-card {
            flex: 0 0 auto;
        }

        .stock-status-card h5,
        .cash-card h5,
        .calls-card h5 {
            font-weight: 700;
            margin: 0;
            padding: 2px 4px;
            background: #f0f0f0;
            font-size: 11px;
        }

        .stock-status-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            border-top: 1px solid #000;
        }

        .stock-status-table th,
        .stock-status-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: center;
            white-space: nowrap;
        }

        .stock-status-table thead th {
            border-top: 1px solid #000 !important;
        }

        .stock-status-table thead tr:first-child th {
            border-top: 1px solid #000 !important;
        }

        #products_summary_table thead th {
            border-top: 1px solid #000 !important;
        }

        #products_summary_table thead tr:first-child th {
            border-top: 1px solid #000 !important;
        }

        .stock-status-table th:first-child,
        .stock-status-table td:first-child {
            text-align: left;
            font-weight: bold;
        }

        .stock-status-table .product-header-summary {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            white-space: nowrap;
            color: #0066cc;
            font-weight: bold;
            width: var(--product-col-width) !important;
            min-width: var(--product-col-width) !important;
            max-width: var(--product-col-width) !important;
            height: auto;
            min-height: 60px;
            text-align: center;
            vertical-align: middle;
            padding: 4px !important;
            overflow: visible;
        }

        .cash-card table,
        .calls-card table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .cash-card table td:first-child {
            width: 40%;
            max-width: 120px;
            padding: 2px 4px;
        }

        .cash-card table td:last-child {
            width: 60%;
            padding: 2px 4px;
        }

        .cash-card td,
        .cash-card th,
        .calls-card td,
        .calls-card th {
            padding: 2px 4px;
            vertical-align: middle;
            border: 1px solid #000;
            height: auto;
            line-height: 1.2;
        }

        .cash-card table tr {
            height: auto;
            margin: 0;
        }

        .cash-card table td {
            height: auto;
            padding: 2px 4px;
            margin: 0;
        }

        .cash-card table .form-control,
        .cash-card table .form-control-sm {
            padding: 2px 4px;
            height: 22px;
            font-size: 11px;
            line-height: 1.2;
            margin: 0;
            border: 1px solid #000;
        }

        .calls-card th {
            text-align: center;
            font-weight: bold;
        }

        .loading-sheet-badge {
            display: inline-block;
            padding: 2px 6px;
            background: #eef2ff;
            border: 1px solid #cbd5ff;
            border-radius: 3px;
            font-weight: 600;
            color: #2f4f8f;
            font-size: 10px;
        }

        .total-row {
            background: #f5f5f5;
            font-weight: bold;
        }

        .total-row td {
            color: red;
        }

        .page-number-display {
            text-align: right;
            margin-top: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .a4-page {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 10mm;
                page-break-after: always;
                page-break-inside: avoid;
            }

            .a4-page:last-child {
                page-break-after: auto;
            }

            .sheet {
                width: 210mm;
                min-height: 297mm;
                padding: 5mm;
                page-break-after: always;
            }

            .sheet:last-child {
                page-break-after: auto;
            }

            .btn,
            button {
                display: none !important;
            }

            #sheet_table tbody tr {
                page-break-inside: avoid;
            }

            .page-number {
                position: fixed;
                bottom: 10mm;
                right: 10mm;
                font-size: 10px;
            }
        }

        .page-number-display {
            text-align: right;
            margin-top: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .hide-cell {
            border: none !important;
            padding: 0 !important;
            background: transparent !important;
        }

        #sheet_table tfoot td.hide-cell,
        #products_summary_table td.hide-cell {
            border-left: 0 !important;
            border-right: 0 !important;
        }

        :root {
            --col-index: 36px;
            --col-bill: 70px;
            --col-customer: 120px;
            /* Default label width for bottom summary/stock tables (Lubricant) */
            --products-summary-label-width: 278px;
            /* Dynamic width control for Products Summary table */
            --products-summary-table-min-width: 65%;
            --products-summary-table-max-width: 65%;
        }

        #sheet_table th:nth-child(1),
        #sheet_table td:nth-child(1) {
            width: var(--col-index);
        }

        #sheet_table th:nth-child(2),
        #sheet_table td:nth-child(2) {
            width: var(--col-bill);
        }

        #sheet_table th:nth-child(3),
        #sheet_table td:nth-child(3) {
            width: var(--col-customer);
        }

        #products_summary_table td.hide-cell:nth-child(1) {
            width: var(--col-index);
        }

        #products_summary_table th:nth-child(1),
        #products_summary_table td:nth-child(1) {
            width: var(--col-index);
        }

        #products_summary_table th:nth-child(2),
        #products_summary_table td:nth-child(2) {
            width: var(--col-bill);
        }

        #products_summary_table td.hide-cell:nth-child(2) {
            width: var(--col-bill);
        }

        /* For the Stock Status table (bottom, with Store - Day Opening Qty, etc),
                                                                                                       make the first column (labels) use the same total width as the top table's
                                                                                                       Index + Bill No + Customer so that the product columns align vertically
                                                                                                       with the top product columns (works for Fuel and Lubricant). */
        #stock_status_table th:nth-child(1),
        #stock_status_table td:nth-child(1) {
            width: var(--products-summary-label-width);
        }

        #products_summary_table td.hide-cell {
            width: var(--col-index);
        }

        #products_summary_table td.hide-cell:nth-child(2) {
            width: var(--col-bill);
        }









        /* ========== COLUMN WIDTH FIXES ========== */
        /* Override all column widths - make them narrower */
        :root {
            --product-col-width: 60px !important;
            --lubricant-last-col-width: 100px !important;
            --free-issue-col-width: 55px !important;
        }

        /* Force all product columns to be narrower */
        #sheet_table th.product-header,
        #sheet_table td.product-cell,
        #products_summary_table th.product-header-summary,
        #products_summary_table td.product-cell {
            width: var(--product-col-width) !important;
            min-width: var(--product-col-width) !important;
            max-width: var(--product-col-width) !important;
        }

        /* Force free issue columns to be even narrower */
        #sheet_table th.free-issue-header,
        #sheet_table td.free-issue-cell,
        #products_summary_table th.free-issue-header,
        #products_summary_table td.free-issue-cell {
            width: var(--free-issue-col-width) !important;
            min-width: var(--free-issue-col-width) !important;
            max-width: var(--free-issue-col-width) !important;
            text-align: center !important;
            padding: 2px 2px !important;
        }

        /* Lubricant last 2 columns - keep wider for longer names */
        #sheet_table th.product-header.lubricant-wide,
        #sheet_table td.product-cell.lubricant-wide,
        #products_summary_table th.product-header-summary.lubricant-wide,
        #products_summary_table td.product-cell.lubricant-wide {
            width: var(--lubricant-last-col-width) !important;
            min-width: var(--lubricant-last-col-width) !important;
            max-width: var(--lubricant-last-col-width) !important;
        }

        /* Reduce font size for product headers to fit narrower columns */
        #sheet_table th.product-header {
            font-size: 10px !important;
            padding: 8px 2px 2px 2px !important;
            word-break: keep-all !important;
        }

        /* Ensure free issue header text fits */
        #sheet_table th.free-issue-header {
            font-size: 9px !important;
            padding: 8px 2px 2px 2px !important;
            writing-mode: horizontal-tb !important;
            transform: none !important;
            white-space: normal !important;
            line-height: 1.2 !important;
        }

        /* Free issue cell numbers - smaller font */
        #sheet_table td.free-issue-cell {
            font-size: 10px !important;
            text-align: center !important;
        }

        /* Products summary table - same width adjustments */
        #products_summary_table th.product-header-summary,
        #products_summary_table td.product-cell {
            font-size: 10px !important;
            padding: 2px 2px !important;
        }

        #products_summary_table th.free-issue-header,
        #products_summary_table td.free-issue-cell {
            font-size: 9px !important;
            padding: 2px 2px !important;
        }
    </style>

    @php
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business->id)->where('is_active', 1)->first();
        $locationText = '';
        if ($location) {
            $parts = array_filter([$location->address_1, $location->city, $location->state, $location->country]);
            $locationText = implode(', ', $parts);
        }
    @endphp

    <section class="content">
        <div class="container-fluid" id="pages_container">
            <form id="dss_form" method="POST" action="{{ route('distribution.daily_summary.store') }}">
                @csrf
                <!-- Pages will be dynamically created here -->
                <div class="a4-page" id="page_1" data-page-number="1">

                    <!-- First Row: Title and Business Name at Center -->
                    <div class="header">
                        <div class="sheet-title">Daily Summary Sheet</div>
                        <div class="business-name">{!! $business->name !!}</div>
                        <div class="business-location">
                            @php
                                $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business->id)
                                    ->where('is_active', 1)
                                    ->first();
                                $locationText = '';
                                if ($location) {
                                    $parts = array_filter([
                                        $location->address_1,
                                        $location->city,
                                        $location->state,
                                        $location->country,
                                    ]);
                                    $locationText = implode(', ', $parts);
                                }
                            @endphp
                            {!! $locationText ?: '' !!}
                        </div>
                    </div>

                    <!-- Single Row: All header fields in one line -->
                    <div class="header-row">
                        <div class="header-field form-group">
                            <label class="control-label">Sales Rep:</label>
                            <select id="sales_rep_id" name="sales_rep_id" class="form-control select2" style="width: 100%;">
                                <option value="">-- Select Sales Rep --</option>
                                @foreach ($salesReps as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="header-field form-group">
                            <label class="control-label">Agent:</label>
                            <input type="text" id="agent_name" name="agent_name"
                                value="{{ request()->session()->get('business.name') }}" class="form-control">
                        </div>

                        <div class="header-field form-group">
                            <label class="control-label">Date:</label>
                            @if (!empty($show_date_picker))
                                <input type="date" id="sheet_date" name="date" value="{{ date('Y-m-d') }}"
                                    class="form-control">
                            @elseif(!empty($auto_date_time))
                                <input type="text" id="sheet_date" name="date" value="{{ date('Y-m-d H:i:s') }}"
                                    class="form-control" readonly>
                            @else
                                <input type="date" id="sheet_date" name="date" value="{{ date('Y-m-d') }}"
                                    class="form-control">
                            @endif
                        </div>

                        <div class="header-field form-group">
                            <label class="control-label">Route:</label>
                            <select id="route_id" name="route_id" class="form-control">
                                <option value="">--Select--</option>
                                @foreach ($routes as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="header-field form-group">
                            <label class="control-label">Vehicle No:</label>
                            <select id="vehicle_id" name="vehicle_id" class="form-control" required>
                                <option value="">--Select--</option>
                                @foreach ($vehicles as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="header-field form-group">
                            <label class="control-label">Product Category:</label>
                            <select id="product_category_id" name="product_category_id" class="form-control">
                                <option value="">--Select--</option>
                                @foreach ($categories as $id => $n)
                                    <option value="{{ $id }}">{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="header-field form-group">
                            <label class="control-label">Distance (km):</label>
                            <input type="number" step="0.01" id="distance_km" name="distance_km" class="form-control">
                        </div>
                    </div>

                    <!-- Sheet Number (Visible - from Settings page) -->
                    <div class="header-row" style="justify-content: center; margin-top: 8px;">
                        <div class="header-field form-group">
                            <label class="control-label">Sheet No:</label>
                            <input type="text" id="sheet_number" name="sheet_number" value="{{ $sheet_number }}"
                                class="form-control" readonly style="min-width: 120px; text-align: center;">
                        </div>
                        <div class="header-field form-group" style="padding-left: 20px;">
                            <label class="control-label">Loading Sheet:</label>
                            <div id="loading_sheet_display" style="font-weight:700; font-size: 14px; padding-top: 5px;">--
                            </div>
                            <input type="hidden" id="loading_sheet_no" name="loading_sheet_no">
                        </div>
                        <div class="header-field" style="text-align:center; padding-left: 30px; padding-top: 15px;">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="restore_last_draft">
                                Restore last draft
                            </button>
                        </div>
                    </div>



                    <!-- Scrollable container for table and summary - single bottom scrollbar -->
                    <div class="page-content-wrapper">
                        <!-- Table Section -->
                        <div class="table-scroll">
                            <div class="table-scroll-wrapper">
                                <table id="sheet_table">
                                    <thead id="sheet_table_head">
                                        <tr id="header_row">
                                            <th style="color:#0066cc;">Index</th>
                                            <th style="color:#0066cc;">Bill No</th>
                                            <th style="color:#0066cc;">Customer</th>
                                            <!-- dynamic product headers will be injected here -->
                                            <th style="color:#0066cc;" class="sticky-amount-col">Gross Sale</th>
                                            <th style="color:#0066cc;" class="sticky-amount-col">Discount</th>
                                            <th style="color:#0066cc;" class="sticky-amount-col">Net Sale</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sheet_table_body">
                                        <!-- JS will append rows here -->
                                    </tbody>
                                    <tfoot id="sheet_table_foot">

                                        <tr id="footer_row_1">
                                            <td class="hide-cell"></td>
                                            <td class="hide-cell"></td>
                                            <td><strong>Total This Page</strong></td>

                                            <!-- Product columns injected by JS -->

                                            <td></td>
                                            <td></td>
                                            <td style="text-align:right;">
                                                <span id="total_this_page">0.00</span>
                                            </td>
                                        </tr>

                                        <tr id="footer_row_2">
                                            <td class="hide-cell"></td>
                                            <td class="hide-cell"></td>
                                            <td><strong>Previous Page GT</strong></td>

                                            <!-- Product columns injected by JS -->

                                            <td></td>
                                            <td></td>
                                            <td style="text-align:right;">
                                                <input type="text" id="previous_page_gt" value="0.00"
                                                    style="width:100%;border:none;text-align:right;">
                                            </td>
                                        </tr>

                                        <tr id="footer_row_3">
                                            <td class="hide-cell"></td>
                                            <td class="hide-cell"></td>
                                            <td><strong>Grand Total</strong></td>

                                            <!-- Product columns injected by JS -->

                                            <td></td>
                                            <td></td>
                                            <td style="text-align:right;">
                                                <strong><span id="grand_total">0.00</span></strong>
                                            </td>
                                        </tr>

                                    </tfoot>

                                </table>
                            </div>
                            <br>
                            <input type="hidden" id="product_list_input" name="product_list">
                            <input type="hidden" id="lines_input" name="lines"> {{-- JSON payload --}}
                            <input type="hidden" id="stock_status_input" name="stock_status">
                            <input type="hidden" id="loading_sheets_input" name="loading_sheets">
                            <input type="hidden" id="daily_summary_id" name="id">
                            <input type="hidden" id="total_this_page_input" name="total_this_page" value="0">
                            <input type="hidden" id="grand_total_input" name="grand_total" value="0">
                            <input type="hidden" name="page_no" id="page_no" value="1">
                            <input type="hidden" name="total_pages" id="total_pages" value="1">

                            <!-- Summary Section - inside same scrollable container -->
                            <div class="summary-flex">
                                <!-- Left side: Products Summary and Cash/Credit Section -->
                                <div
                                    style="flex: 1; min-width: 0; overflow-x: auto; overflow-y: visible; margin: 0; padding: 0;">
                                    <!-- Products Summary Section -->
                                    <div class="stock-status-card products-summary stock-left">
                                        <table class="stock-status-table" id="products_summary_table">
                                            <thead>
                                                <tr id="products_summary_header">
                                                    <td class="hide-cell"></td>
                                                    <td class="hide-cell"></td>
                                                    <th></th>
                                                    <!-- Product columns will be inserted here by JS -->
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr id="products_day_opening_row">
                                                    <td>Store - Day Opening Qty</td>
                                                </tr>
                                                <tr id="products_vehicle_qty_row">
                                                    <td>Vehicle Qty before Loading</td>
                                                </tr>
                                                <tr id="products_loaded_qty_row">
                                                    <td>Loaded Qty</td>
                                                </tr>
                                                <tr id="products_sold_qty_row">
                                                    <td>Sold Qty</td>
                                                </tr>
                                                <tr id="products_balance_qty_row">
                                                    <td>Balance Qty - Vehicle</td>
                                                </tr>
                                                <tr id="products_free_qty_row" style="background-color: #f0fff0;">
                                                    <td style="color: #006600; font-weight: bold;">Free Issues Given</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Cash / Credit and Calls Visited Section - side by side -->
                                    <div style="display: flex; gap: 16px; margin-top: 10px; align-items: flex-start;">
                                        <!-- Cash / Credit Section -->
                                        <div class="cash-card" style="flex: 1;">
                                            <table class="table table-sm">
                                                <tr>
                                                    <td>Cash Deposited</td>
                                                    <td><input type="number" step="0.0001"
                                                            class="form-control form-control-sm" name="cash_deposited"
                                                            id="cash_deposited" value="0"></td>
                                                </tr>
                                                <tr>
                                                    <td>Cheque Deposited</td>
                                                    <td><input type="number" step="0.0001"
                                                            class="form-control form-control-sm" name="cheque_deposited"
                                                            id="cheque_deposited" value="0"></td>
                                                </tr>
                                                <tr>
                                                    <td>Credit Bills B/F</td>
                                                    <td><input type="number" step="0.0001"
                                                            class="form-control form-control-sm" name="credit_bills_bf"
                                                            id="credit_bills_bf" value="0"></td>
                                                </tr>
                                                <tr>
                                                    <td>Cheques in Hand</td>
                                                    <td><input type="number" step="0.0001"
                                                            class="form-control form-control-sm" name="cheques_in_hand"
                                                            id="cheques_in_hand" value="0"></td>
                                                </tr>
                                                <tr>
                                                    <td>Credit Bills in hand todate</td>
                                                    <td><input type="number" step="0.0001"
                                                            class="form-control form-control-sm"
                                                            name="credit_bills_in_hand" id="credit_bills_in_hand"
                                                            value="0"></td>
                                                </tr>
                                                <tr>
                                                    <td>Cash in Hand</td>
                                                    <td><input type="number" step="0.0001"
                                                            class="form-control form-control-sm" name="cash_in_hand"
                                                            id="cash_in_hand" value="0"></td>
                                                </tr>
                                                <tr style="background:#f5f5f5;font-weight:bold;color:red;">
                                                    <td style="color:red;">Total</td>
                                                    <td><span id="cash_total">0.00</span></td>
                                                </tr>
                                            </table>
                                        </div>

                                        <!-- Calls Visited Section -->
                                        <div class="calls-card" style="flex: 1;">
                                            <table style="width:100%;">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th style="text-align:center;">Calls Visited</th>
                                                        <th style="text-align:center;">Productive Calls</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><strong>B / F</strong></td>
                                                        <td style="text-align:center;"><input type="number"
                                                                min="0" class="form-control form-control-sm"
                                                                name="calls_visited_bf" id="calls_visited_bf"
                                                                value="0" style="width:80px;margin:0 auto;"></td>
                                                        <td style="text-align:center;"><input type="number"
                                                                min="0" class="form-control form-control-sm"
                                                                name="productive_calls_bf" id="productive_calls_bf"
                                                                value="0" style="width:80px;margin:0 auto;"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Day Calls</strong></td>
                                                        <td style="text-align:center;"><span
                                                                id="day_calls_visited">0</span>
                                                            <input type="hidden" name="calls_visited" id="calls_visited"
                                                                value="0">
                                                        </td>
                                                        <td style="text-align:center;"><span
                                                                id="day_productive_calls">0</span>
                                                            <input type="hidden" name="productive_calls"
                                                                id="productive_calls" value="0">
                                                        </td>
                                                    </tr>
                                                    <tr style="background:#f5f5f5; font-weight:bold; color:red;">
                                                        <td><strong style="color:red;">Total</strong></td>
                                                        <td style="text-align:center;"><span
                                                                id="total_calls_visited">0</span></td>
                                                        <td style="text-align:center;"><span
                                                                id="total_productive_calls">0</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End of summary-flex -->
                        </div>
                        <!-- End of page-content-wrapper -->

                        <!-- Stock Status Section (Hidden for print - only shows in UI) -->
                        <div class="summary-flex" style="margin-top:16px; display:none;" id="stock_status_section">
                            <div class="stock-status-card" style="flex:1;">
                                <h5>Stock Status</h5>
                                <table class="stock-status-table" id="stock_status_table">
                                    <thead>
                                        <tr id="stock_header_row">
                                            <th>Stock Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="stock_row_store">
                                            <td>Store - Day Opening Qty</td>
                                        </tr>
                                        <tr id="stock_row_vehicle_opening">
                                            <td>Vehicle Qty before Loading</td>
                                        </tr>
                                        <tr id="stock_row_loaded">
                                            <td>Loaded Qty</td>
                                        </tr>
                                        <tr id="stock_row_sold">
                                            <td>Sold Qty</td>
                                        </tr>
                                        <tr id="stock_row_balance">
                                            <td>Balance Qty - Vehicle</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="cash-card">
                                <h5>Loading Sheets</h5>
                                <div id="loading_sheets_list" style="font-size:12px;"></div>
                            </div>
                        </div>

                        <!-- Page Number Display -->
                        <div class="page-number-display">
                            Page <span id="current_page_no">1</span> of <span id="total_pages_display">1</span>
                        </div>

                        <!-- Signature Section -->
                        <div class="signature-section">
                            <div>
                                <div style="border-top:1px dotted #000;width:100%;margin-bottom:4px;"></div>
                                <div style="font-weight:bold;font-size:11px;">Sales Rep Signature</div>
                            </div>
                            <div>
                                <div style="border-top:1px dotted #000;width:100%;margin-bottom:4px;"></div>
                                <div style="font-weight:bold;font-size:11px;">Agent Signature</div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary"
                                    style="padding:4px 16px;font-size:12px;">Save</button>
                            </div>
                        </div>
                    </div> <!-- End of page_1 -->
            </form>
        </div>
    </section>
@endsection

@section('javascript')

    <script>
        $(document).ready(function() {
            $('#sales_rep_id').select2({
                placeholder: '-- Select Sales Rep --',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0
            });

            $('#sales_rep_id').on('change', function() {
                try {
                    if (typeof window.loadVehiclesForSalesRep === 'function') {
                        window.loadVehiclesForSalesRep();
                    }
                    if (typeof window.loadStockStatusAndLoading === 'function') {
                        window.loadStockStatusAndLoading();
                    }
                } catch (e) {
                    console.error('Error in sales rep change handler:', e);
                }
            });
        });

        $('#sales_rep_id').on('select2:open', function() {
            document.querySelector('.select2-search__field').focus();
        });
    </script>


    <script>
        (function() {
            const productCat = document.getElementById('product_category_id');
            const tableHead = document.getElementById('sheet_table_head').querySelector('#header_row');
            const tableBody = document.getElementById('sheet_table_body');
            const productListInput = document.getElementById('product_list_input');
            const linesInput = document.getElementById('lines_input');
            const stockStatusInput = document.getElementById('stock_status_input');
            const loadingSheetsInput = document.getElementById('loading_sheets_input');
            const loadingSheetNoInput = document.getElementById('loading_sheet_no');
            const loadingSheetDisplay = document.getElementById('loading_sheet_display');
            const loadingSheetsList = document.getElementById('loading_sheets_list');
            const draftIdInput = document.getElementById('daily_summary_id');
            const restoreBtn = document.getElementById('restore_last_draft');
            const previousPageGtInput = document.getElementById('previous_page_gt');
            const totalThisPageSpan = document.getElementById('total_this_page');
            const grandTotalSpan = document.getElementById('grand_total');
            const totalThisPageInput = document.getElementById('total_this_page_input');
            const grandTotalInput = document.getElementById('grand_total_input');
            const stockHeaderRow = document.getElementById('stock_header_row');
            const stockRows = {
                store: document.getElementById('stock_row_store'),
                vehicle: document.getElementById('stock_row_vehicle_opening'),
                loaded: document.getElementById('stock_row_loaded'),
                sold: document.getElementById('stock_row_sold'),
                balance: document.getElementById('stock_row_balance'),
            };
            const manualFields = [
                'cash_deposited',
                'cheque_deposited',
                'credit_bills_bf',
                'cheques_in_hand',
                'credit_bills_in_hand',
                'cash_in_hand',
                'calls_visited_bf',
                'productive_calls_bf',
            ];
            const csrfToken = '{{ csrf_token() }}';

            // Holds the ordered product ids used as columns for the sheet
            let productColumns = [];

            // Holds discovered free-issue columns {key, product_id, unit_id, product_name, unit_name}
            // key is "pid_uid" to uniquely identify a product+unit pair for free issues
            let freeIssueColumns = [];


            // Store the original static headers (Gross, Discount, Net)
            const staticHeaders = ['Gross Sale', 'Discount', 'Net Sale'];

            let autosaveTimer = null;

            // Initialize layout once on page load (in case a category is pre-selected)
            updateCategoryLayout();

            function safeJsonParse(val, fallback = []) {
                if (!val) return fallback;
                try {
                    return JSON.parse(val);
                } catch (e) {
                    return fallback;
                }
            }

            function queueAutosave() {
                clearTimeout(autosaveTimer);
                autosaveTimer = setTimeout(runAutosave, 900);
            }

            // Calculate and display B/F, Day Calls, and Total for Calls section
            function recalcCalls() {
                const bfVisited = parseInt(document.getElementById('calls_visited_bf').value) || 0;
                const bfProductive = parseInt(document.getElementById('productive_calls_bf').value) || 0;

                // Day Calls = number of bills (rows) loaded in the table
                const dayCallsCount = tableBody.querySelectorAll('tr').length;
                // Productive calls = same as day calls for now (each bill is a productive call)
                const dayProductiveCalls = dayCallsCount;

                document.getElementById('day_calls_visited').textContent = dayCallsCount;
                document.getElementById('day_productive_calls').textContent = dayProductiveCalls;
                document.getElementById('calls_visited').value = dayCallsCount;
                document.getElementById('productive_calls').value = dayProductiveCalls;

                // Totals
                document.getElementById('total_calls_visited').textContent = bfVisited + dayCallsCount;
                document.getElementById('total_productive_calls').textContent = bfProductive + dayProductiveCalls;
            }

            // Listen for B/F changes
            document.getElementById('calls_visited_bf').addEventListener('input', function() {
                recalcCalls();
                queueAutosave();
            });
            document.getElementById('productive_calls_bf').addEventListener('input', function() {
                recalcCalls();
                queueAutosave();
            });

            // Calculate the width for lubricant table to align with Gross Sale column
            function calculateLubricantTableWidth() {
                const indexWidth = 36; // --col-index
                const billWidth = 70; // --col-bill  
                const customerWidth = 120; // --col-customer
                const productWidth = 70; // --product-col-width
                const productCount = productColumns ? productColumns.length : 0;

                // Calculate total width up to Gross Sale column
                const totalWidth = indexWidth + billWidth + customerWidth + (productWidth * productCount);
                return totalWidth + 'px';
            }

            // Calculate width for fuel category (same as lubricant - align with Gross Sale)
            function calculateFuelTableWidth() {
                // Use the same calculation as lubricant for proper alignment
                return calculateLubricantTableWidth();
            }

            // Calculate width for other categories (slightly narrower than full width)
            function calculateOtherTableWidth() {
                const baseWidth = parseInt(calculateLubricantTableWidth());
                // Make other categories about 10% narrower than the full width
                const otherWidth = Math.floor(baseWidth * 0.9);
                return otherWidth + 'px';
            }

            // Adjust layout (bottom label width) based on selected product category
            function updateCategoryLayout() {
                const selectedOption = productCat.options[productCat.selectedIndex];
                if (!selectedOption) return;

                const label = (selectedOption.text || '').toLowerCase();
                console.log('Category selected:', selectedOption.text, 'Lowercase:', label);

                // If category name contains 'fuel', use a slightly narrower first column
                // and align products with the top table (approx. 3px less than previous value)
                if (label.includes('fuel')) {
                    labelWidth = '278px';
                    tableMinWidth = '82.5%';
                    tableMaxWidth = '82.5%';
                } else if (label.includes('lubricant')) {
                    // For Lubricant, use a slightly narrower width to provide more scrollable space
                    labelWidth = '278px';
                    tableMinWidth = '84%';
                    tableMaxWidth = '84%';
                } else {
                    // For other product types, use 65% width
                    labelWidth = '278px';
                    tableMinWidth = '82.5%';
                    tableMaxWidth = '82.5%';
                }

                // Apply CSS variables
                document.documentElement.style.setProperty('--products-summary-label-width', labelWidth);
                document.documentElement.style.setProperty('--products-summary-table-min-width', tableMinWidth);
                document.documentElement.style.setProperty('--products-summary-table-max-width', tableMaxWidth);
                console.log('Applied CSS variables:', {
                    labelWidth,
                    tableMinWidth,
                    tableMaxWidth
                });

                // Also add CSS class for more specific targeting
                const table = document.getElementById('products_summary_table');
                const stockCard = document.querySelector('.stock-status-card.products-summary');
                const stockLeft = document.querySelector('.stock-left');

                if (table) {
                    table.classList.remove('category-fuel', 'category-lubricant', 'category-other');
                    if (stockCard) stockCard.classList.remove('category-fuel', 'category-lubricant', 'category-other');
                    if (stockLeft) stockLeft.classList.remove('category-fuel', 'category-lubricant', 'category-other');

                    if (label.includes('fuel')) {
                        table.classList.add('category-fuel');
                        if (stockCard) stockCard.classList.add('category-fuel');
                        if (stockLeft) stockLeft.classList.add('category-fuel');
                    } else if (label.includes('lubricant')) {
                        table.classList.add('category-lubricant');
                        if (stockCard) stockCard.classList.add('category-lubricant');
                        if (stockLeft) stockLeft.classList.add('category-lubricant');
                        console.log('Added category-lubricant class to table and containers');
                    } else {
                        table.classList.add('category-other');
                        if (stockCard) stockCard.classList.add('category-other');
                        if (stockLeft) stockLeft.classList.add('category-other');
                    }
                }
            }


            productCat.addEventListener('change', function() {
                const catId = this.value;
                console.log('Product category changed to:', catId);

                // ── ADDED: Reset free issue columns on category change so old
                // columns from a previous category don't carry over.
                freeIssueColumns = [];

                // Update dynamic layout (Lubricant vs Fuel)
                updateCategoryLayout();

                if (!catId) {
                    productColumns = [];
                    renderHeader();
                    clearTableBody();
                    productCat.disabled = false;
                    productCat.style.backgroundColor = '';
                    return;
                }

                clearTableBody();
                const safeColspan = 6;
                tableBody.innerHTML = `
        <tr>
            <td colspan="${safeColspan}" style="text-align:center;padding:6px;color:#666;">
                Loading bills for this category...
            </td>
        </tr>
    `;

                loadBillsAndPopulateTable();
                loadStockStatusAndLoading();
                loadFreeIssueColumnsFromSettings();
            });

            function renderHeader() {
                // Remove product columns from header (keep the 6 static cols)
                while (tableHead.children.length > 6) {
                    tableHead.removeChild(tableHead.children[3]);
                }

                const footerRow1 = document.getElementById('footer_row_1');
                const footerRow2 = document.getElementById('footer_row_2');
                const footerRow3 = document.getElementById('footer_row_3');

                // Remove ALL product cells but KEEP first three cells (two hide-cells + label)
                [footerRow1, footerRow2, footerRow3].forEach(row => {
                    if (!row) return;
                    while (row.children.length > 3) {
                        row.removeChild(row.lastChild);
                    }
                });

                // Add product headers and footer cells (one td per product)
                productColumns.forEach((p, idx) => {
                    const th = document.createElement('th');
                    th.textContent = p.name || ('Product ' + (idx + 1));
                    th.dataset.productId = p.id;
                    th.className = 'product-header';

                    const selectedOption = productCat.options[productCat.selectedIndex];
                    const isLubricant = selectedOption && selectedOption.text.toLowerCase().includes(
                        'lubricant');
                    const isLastTwoColumns = idx >= productColumns.length - 2;
                    const isLastProduct = idx === productColumns.length - 1;

                    if (isLubricant && isLastTwoColumns) {
                        th.classList.add('lubricant-wide');
                    }
                    if (isLastProduct) {
                        th.classList.add('last-product-col');
                    }

                    tableHead.insertBefore(th, tableHead.children[tableHead.children.length - 3]);

                    [footerRow1, footerRow2, footerRow3].forEach(row => {
                        if (row) {
                            const td = document.createElement('td');
                            td.className = 'product-cell';
                            if (isLubricant && isLastTwoColumns) {
                                td.classList.add('lubricant-wide');
                            }
                            if (isLastProduct) {
                                td.classList.add('last-product-col');
                            }
                            row.appendChild(td);
                        }
                    });
                });

                freeIssueColumns.forEach(fic => {
                    // ALWAYS add a free column, even if the product already exists in regular products
                    const thFi = document.createElement('th');
                    thFi.textContent = 'Free' + (fic.unit_name ? ' (' + fic.unit_name + ')' : '') + ': ' + fic
                        .product_name;
                    thFi.className = 'product-header free-issue-header';
                    thFi.dataset.freeIssueKey = fic.key;
                    thFi.style.backgroundColor = '#f0fff0';
                    thFi.style.color = '#006600';
                    thFi.style.borderLeft = '2px solid #4CAF50';
                    tableHead.insertBefore(thFi, tableHead.children[tableHead.children.length - 3]);

                    // Footer cells for free issue columns
                    [footerRow1, footerRow2, footerRow3].forEach(row => {
                        if (!row) return;
                        const td = document.createElement('td');
                        td.className = 'product-cell free-issue-cell';
                        td.dataset.freeIssueKey = fic.key;
                        td.style.textAlign = 'center';
                        td.style.backgroundColor = '#f0fff0';
                        td.style.color = '#006600';
                        row.appendChild(td);
                    });
                });


                // Add the last 3 cells (Gross, Discount, Net) for footer rows
                // ── CHANGED: Only add static cells if they haven't been added yet
                // (prevents double-rendering when renderHeader is called multiple times)
                [footerRow1, footerRow2, footerRow3].forEach(row => {
                    if (!row) return;

                    // Check if static amount cells already exist — if so, skip
                    const existingAmountCells = row.querySelectorAll(
                        'td:not(.product-cell):not(.hide-cell):not(.free-issue-cell)');
                    if (existingAmountCells.length >= 3) return; // already added

                    for (let i = 0; i < 3; i++) {
                        const td = document.createElement('td');
                        td.style.textAlign = 'right';
                        if (row === footerRow1) {
                            if (i === 0) {
                                td.innerHTML = '<span id="total_gross_sale">0.00</span>';
                            } else if (i === 1) {
                                td.innerHTML = '<span id="total_discount">0.00</span>';
                            } else if (i === 2) {
                                td.innerHTML = '<span id="total_this_page">0.00</span>';
                            }
                        } else if (row === footerRow2 && i === 2) {
                            td.innerHTML =
                                '<input type="text" id="previous_page_gt" name="previous_page_gt" value="0.00" style="width:100%;border:none;text-align:right;">';
                        } else if (row === footerRow3 && i === 2) {
                            td.innerHTML = '<strong><span id="grand_total">0.00</span></strong>';
                        }
                        row.appendChild(td);
                    }
                });

                renderStockStatusHeaders();
            }


            function renderStockStatusHeaders() {
                // clear existing product headers except the first label cell
                while (stockHeaderRow.children.length > 1) {
                    stockHeaderRow.removeChild(stockHeaderRow.lastChild);
                }
                // clear product cells from rows (preserve first cell)
                Object.values(stockRows).forEach(row => {
                    while (row.children.length > 3) {
                        row.removeChild(row.lastChild);
                    }
                });

                productColumns.forEach((p, idx) => {
                    const th = document.createElement('th');
                    th.textContent = p.name || ('Product ' + (idx + 1));
                    th.className = 'product-header';

                    // Check if this is Lubricant category and if this is one of the last 2 columns
                    const selectedOption = productCat.options[productCat.selectedIndex];
                    const isLubricant = selectedOption && selectedOption.text.toLowerCase().includes(
                        'lubricant');
                    const isLastTwoColumns = idx >= productColumns.length - 2;

                    if (isLubricant && isLastTwoColumns) {
                        th.classList.add('lubricant-wide');
                    }

                    stockHeaderRow.appendChild(th);

                    Object.values(stockRows).forEach(row => {
                        const td = document.createElement('td');
                        td.className = 'product-cell';
                        if (isLubricant && isLastTwoColumns) {
                            td.classList.add('lubricant-wide');
                        }
                        td.textContent = '0.00';
                        row.appendChild(td);
                    });
                });

                // Also render products summary table (keeps columns in sync)
                renderProductsSummaryHeaders();
            }


            function renderProductsSummaryHeaders() {
                const productsSummaryHeader = document.getElementById('products_summary_header');
                const productsDayOpeningRow = document.getElementById('products_day_opening_row');
                const productsVehicleRow = document.getElementById('products_vehicle_qty_row');
                const productsLoadedRow = document.getElementById('products_loaded_qty_row');
                const productsSoldRow = document.getElementById('products_sold_qty_row');
                const productsBalanceRow = document.getElementById('products_balance_qty_row');
                const productsFreeRow = document.getElementById('products_free_qty_row');

                if (!productsSummaryHeader) return;

                const summaryRows = [
                    productsSummaryHeader,
                    productsDayOpeningRow,
                    productsVehicleRow,
                    productsLoadedRow,
                    productsSoldRow,
                    productsBalanceRow,
                    productsFreeRow
                ];

                summaryRows.forEach(row => {
                    if (!row) return;
                    const keepCount = row.id === 'products_summary_header' ? 3 : 1;
                    while (row.children.length > keepCount) {
                        row.removeChild(row.lastChild);
                    }
                });

                // Add regular product columns
                productColumns.forEach((p, idx) => {
                    const selectedOption = productCat.options[productCat.selectedIndex];
                    const isLubricant = selectedOption && selectedOption.text.toLowerCase().includes(
                        'lubricant');
                    const isLastTwoColumns = idx >= productColumns.length - 2;

                    // Header cell
                    const th = document.createElement('th');
                    th.textContent = p.name || ('Product ' + (idx + 1));
                    th.className = 'product-header-summary';
                    if (isLubricant && isLastTwoColumns) {
                        th.classList.add('lubricant-wide');
                    }
                    productsSummaryHeader.appendChild(th);

                    // Data cells for each summary row
                    [productsDayOpeningRow, productsVehicleRow, productsLoadedRow, productsSoldRow,
                        productsBalanceRow, productsFreeRow
                    ].forEach(row => {
                        if (!row) return;
                        const td = document.createElement('td');
                        td.className = 'product-cell';
                        if (isLubricant && isLastTwoColumns) {
                            td.classList.add('lubricant-wide');
                        }
                        td.textContent = '0.00';
                        if (row.id === 'products_free_qty_row') {
                            td.style.color = '#006600';
                            td.style.fontWeight = 'bold';
                            td.style.backgroundColor = '#f0fff0';
                        }
                        row.appendChild(td);
                    });
                });

                // Add free issue columns (only for products NOT already in productColumns)
                if (freeIssueColumns && freeIssueColumns.length > 0) {
                    freeIssueColumns.forEach((fic) => {
                        // Skip if this free product already exists in regular products
                        const alreadyExists = productColumns.some(p => p.id == fic.product_id);
                        if (alreadyExists) {
                            console.log(
                                `Skipping free column for ${fic.product_name} - already in regular products`
                            );
                            return;
                        }

                        const selectedOption = productCat.options[productCat.selectedIndex];
                        const isLubricant = selectedOption && selectedOption.text.toLowerCase().includes(
                            'lubricant');

                        // Header cell for free product
                        const th = document.createElement('th');
                        th.textContent = 'Free: ' + fic.product_name;
                        th.className = 'product-header-summary free-issue-header';
                        th.style.backgroundColor = '#f0fff0';
                        th.style.color = '#006600';
                        productsSummaryHeader.appendChild(th);

                        // Data cells for free product in each summary row
                        [productsDayOpeningRow, productsVehicleRow, productsLoadedRow, productsSoldRow,
                            productsBalanceRow, productsFreeRow
                        ].forEach(row => {
                            if (!row) return;
                            const td = document.createElement('td');
                            td.className = 'product-cell free-issue-cell';
                            td.textContent = '0.00';
                            td.style.backgroundColor = '#f0fff0';
                            td.style.color = '#006600';
                            row.appendChild(td);
                        });
                    });
                }
            }

            function recalcStockStatus() {
                if (!productColumns.length) return;

                const productsDayOpeningRow = document.getElementById('products_day_opening_row');
                const productsVehicleRow = document.getElementById('products_vehicle_qty_row');
                const productsLoadedRow = document.getElementById('products_loaded_qty_row');
                const productsSoldRow = document.getElementById('products_sold_qty_row');
                const productsBalanceRow = document.getElementById('products_balance_qty_row');
                const productsFreeRow = document.getElementById('products_free_qty_row');

                const stockStatusData = stockStatusInput.value ? JSON.parse(stockStatusInput.value) : [];

                console.log('=== RECALC STOCK STATUS START ===');
                console.log('productsFreeRow exists:', !!productsFreeRow);
                if (productsFreeRow) {
                    console.log('productsFreeRow children count:', productsFreeRow.children.length);
                }

                // First, calculate all free quantities per product
                let freeQuantities = {};

                productColumns.forEach((product, idx) => {
                    let freeQty = 0;

                    // Sum free issue quantities for this product
                    if (freeIssueColumns && freeIssueColumns.length > 0) {
                        freeIssueColumns.forEach(fic => {
                            if (String(fic.product_id) === String(product.id)) {
                                tableBody.querySelectorAll('tr').forEach(r => {
                                    const freeCell = r.querySelector(
                                        `td[data-free-issue-key="${fic.key}"]`);
                                    if (freeCell && freeCell.textContent) {
                                        const freeQtyVal = parseFloat(freeCell.textContent
                                            .replace(/,/g, '')) || 0;
                                        freeQty += freeQtyVal;
                                    }
                                });
                            }
                        });
                    }

                    freeQuantities[product.id] = freeQty;
                    console.log(`Product ${idx}: ${product.name} - freeQty: ${freeQty}`);
                });

                // Now update all rows (stock status and free row)
                productColumns.forEach((product, idx) => {
                    const colIndex = idx + 1;
                    const skipLastTwoInSummary = idx >= productColumns.length - 2;


                    let soldQty = 0;
tableBody.querySelectorAll('tr').forEach(r => {
    const productInput = r.querySelector(`input[name*="[products][${product.id}]"]`);
    const qty = parseFloat(productInput ? productInput.value : 0);
    soldQty += (isNaN(qty) ? 0 : qty);
});

                    const stockItem = stockStatusData.find(item => item.product_id == product.id);
                    const storeOpening = stockItem ? parseFloat(stockItem.store_opening_qty || 0) : 0;
                    const vehicleOpening = stockItem ? parseFloat(stockItem.vehicle_opening_qty || 0) : 0;
                    const loadedQty = stockItem ? parseFloat(stockItem.loaded_qty || 0) : 0;
                    const balanceQty = vehicleOpening + loadedQty - soldQty;

                    // Update stockRows (hidden stock status table)
                    if (stockRows.store && stockRows.store.children[colIndex]) {
                        stockRows.store.children[colIndex].textContent = storeOpening.toFixed(2);
                    }
                    if (stockRows.vehicle && stockRows.vehicle.children[colIndex]) {
                        stockRows.vehicle.children[colIndex].textContent = vehicleOpening.toFixed(2);
                    }
                    if (stockRows.loaded && stockRows.loaded.children[colIndex]) {
                        stockRows.loaded.children[colIndex].textContent = loadedQty.toFixed(2);
                    }
                    if (stockRows.sold && stockRows.sold.children[colIndex]) {
                        stockRows.sold.children[colIndex].textContent = soldQty.toFixed(2);
                    }
                    if (stockRows.balance && stockRows.balance.children[colIndex]) {
                        stockRows.balance.children[colIndex].textContent = balanceQty.toFixed(2);
                    }

                    // Update products summary table (visible to user)
                    if (productsDayOpeningRow && productsDayOpeningRow.children[colIndex]) {
                        productsDayOpeningRow.children[colIndex].textContent = storeOpening.toFixed(2);
                    }
                    if (productsVehicleRow && productsVehicleRow.children[colIndex]) {
                        productsVehicleRow.children[colIndex].textContent = vehicleOpening.toFixed(2);
                    }
                    if (productsLoadedRow && productsLoadedRow.children[colIndex]) {
                        productsLoadedRow.children[colIndex].textContent = loadedQty.toFixed(2);
                    }
                    if (productsSoldRow && productsSoldRow.children[colIndex]) {
                        productsSoldRow.children[colIndex].textContent = soldQty.toFixed(2);
                    }
                    if (productsBalanceRow && productsBalanceRow.children[colIndex]) {
                        productsBalanceRow.children[colIndex].textContent = balanceQty.toFixed(2);
                    }


                    // UPDATE FREE ROW - ALWAYS update for ALL products
                    if (productsFreeRow && productsFreeRow.children[colIndex]) {
                        const freeQty = freeQuantities[product.id] || 0;
                        productsFreeRow.children[colIndex].textContent = freeQty.toFixed(2);
                    }
                });

                stockStatusInput.value = JSON.stringify(stockStatusData);
                console.log('=== RECALC STOCK STATUS END ===');
            }


            function populateStockStatusRows(data) {
                const productsDayOpeningRow = document.getElementById('products_day_opening_row');
                const productsVehicleRow = document.getElementById('products_vehicle_qty_row');
                const productsLoadedRow = document.getElementById('products_loaded_qty_row');
                const productsSoldRow = document.getElementById('products_sold_qty_row');
                const productsBalanceRow = document.getElementById('products_balance_qty_row');
                const productsFreeRow = document.getElementById('products_free_qty_row'); // ← ADD THIS

                Object.values(stockRows).forEach(row => {
                    Array.from(row.children).forEach((cell, idx) => {
                        if (idx === 0) return;
                        cell.textContent = '0.00';
                    });
                });

                // Reset all summary rows including free row
                [productsDayOpeningRow, productsVehicleRow, productsLoadedRow, productsSoldRow, productsBalanceRow,
                    productsFreeRow
                ]
                .forEach(row => {
                    if (row) {
                        Array.from(row.children).forEach((cell, idx) => {
                            if (idx === 0) return;
                            cell.textContent = '0.00';
                        });
                    }
                });

                if (!data || !Array.isArray(data) || data.length === 0) {
                    stockStatusInput.value = '';
                    recalcStockStatus();
                    return;
                }

                stockStatusInput.value = JSON.stringify(data);

                data.forEach(item => {
                    const colIndex = productColumns.findIndex(p => p.id == item.product_id);
                    if (colIndex === -1) return;
                    const targetIndex = colIndex + 1;

                    const setters = [{
                            row: stockRows.store,
                            value: item.store_opening_qty
                        },
                        {
                            row: stockRows.vehicle,
                            value: item.vehicle_opening_qty
                        },
                        {
                            row: stockRows.loaded,
                            value: item.loaded_qty
                        },
                        {
                            row: stockRows.sold,
                            value: item.sold_qty || 0
                        },
                        {
                            row: stockRows.balance,
                            value: item.balance_qty || 0
                        },
                    ];
                    setters.forEach(set => {
                        const cell = set.row.children[targetIndex];
                        if (cell) {
                            cell.textContent = parseFloat(set.value ?? 0).toFixed(2);
                        }
                    });

                    // Update products summary table (excluding free row - it will be updated by recalcStockStatus)
                    const productsSetters = [{
                            row: productsDayOpeningRow,
                            value: item.store_opening_qty
                        },
                        {
                            row: productsVehicleRow,
                            value: item.vehicle_opening_qty
                        },
                        {
                            row: productsLoadedRow,
                            value: item.loaded_qty
                        },
                        {
                            row: productsSoldRow,
                            value: item.sold_qty || 0
                        },
                        {
                            row: productsBalanceRow,
                            value: item.balance_qty || 0
                        },
                    ];
                    productsSetters.forEach(set => {
                        if (set.row) {
                            const cell = set.row.children[targetIndex];
                            if (cell) {
                                cell.textContent = parseFloat(set.value ?? 0).toFixed(2);
                            }
                        }
                    });
                });

                // Call recalcStockStatus to update the free row from the actual invoice data
                recalcStockStatus();
            }


            function renderLoadingSheetList(list, sheetNo) {
                if (loadingSheetDisplay) {
                    loadingSheetDisplay.textContent = sheetNo || 'N/A';
                }
                if (loadingSheetNoInput) {
                    loadingSheetNoInput.value = sheetNo || '';
                }
                if (loadingSheetsInput) {
                    loadingSheetsInput.value = JSON.stringify(list || []);
                }
                if (loadingSheetsList) {
                    loadingSheetsList.innerHTML = '';
                    (list || []).forEach(ls => {
                        const link = document.createElement('a');
                        link.href = ls.view_url;
                        link.target = '_blank';
                        link.textContent = ls.loading_no;
                        link.style.display = 'inline-block';
                        link.style.marginRight = '8px';
                        loadingSheetsList.appendChild(link);
                    });
                }
            }

            function loadStockStatusAndLoading() {
                if (!productColumns.length) {
                    populateStockStatusRows([]);
                    renderLoadingSheetList([], null);
                    return;
                }

                const date = document.getElementById('sheet_date').value;
                const salesRep = document.getElementById('sales_rep_id').value;
                const vehicle = document.getElementById('vehicle_id').value;
                const route = document.getElementById('route_id').value;

                const payload = {
                    date,
                    sales_rep_id: salesRep,
                    vehicle_id: vehicle,
                    route_id: route,
                    product_category_id: productCat.value,
                    product_ids: productColumns.map(p => p.id)
                };

                fetch('{{ route('distribution.daily_summary.stock_status') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(r => r.json())
                    .then(resp => {
                        populateStockStatusRows(resp.stock_status || []);
                        const loadingSheetNo = resp.loading_sheet_no || (resp.loading_sheets && resp.loading_sheets
                            .length > 0 ? resp.loading_sheets.map(ls => ls.loading_no).join(', ') : '');
                        renderLoadingSheetList(resp.loading_sheets || [], loadingSheetNo);

                        // Only queue autosave if products are already loaded
                        if (productColumns.length > 0) {
                            queueAutosave();
                        }
                    })
                    .catch(error => {
                        console.error('Error loading stock status and loading sheets:', error);
                        populateStockStatusRows([]);
                        renderLoadingSheetList([], null);
                    });
            }


            document.getElementById('sheet_date').addEventListener('change', function() {
                loadVehiclesForSalesRep();
                if (productCat.value) {
                    if (selectedBillIds.size === 0) {
                        loadBillsAndPopulateTable();
                    }
                    loadStockStatusAndLoading();
                    loadFreeIssueColumnsFromSettings(); // ← ADD THIS
                }
            });


            function loadFreeIssueColumnsFromSettings() {
                const catId = productCat.value;
                const date = document.getElementById('sheet_date').value;
                if (!catId || !date) return;

                fetch(`{{ url('distribution/daily-summary-sheet/free-issue-columns') }}?category_id=${catId}&date=${date}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(columns => {
                        if (!columns || !columns.length) return;

                        // ── CHANGED: Only STORE the known free-issue column definitions.
                        // Do NOT render them into the header yet — they will be rendered
                        // only when a bill is selected and /free-issues returns actual qty.
                        columns.forEach(col => {
                            const key = `${col.product_id}_${col.unit_id || 0}`;
                            if (!freeIssueColumns.find(c => c.key === key)) {
                                freeIssueColumns.push({
                                    key,
                                    product_id: col.product_id,
                                    unit_id: col.unit_id || 0,
                                    product_name: col.product_name,
                                    unit_name: col.unit_name || ''
                                });
                                // ── REMOVED: renderHeader() and the tr injection loop.
                                // Header will be re-rendered only when a bill is selected.
                            }
                        });
                    })
                    .catch(err => console.error('Free issue columns fetch error:', err));
            }

            // Expose function to window for access from other scripts
            window.loadVehiclesForSalesRep = loadVehiclesForSalesRep;
            window.loadStockStatusAndLoading = loadStockStatusAndLoading;

            // Add vehicle change event listener
            const vehicleSelectEl = document.getElementById('vehicle_id');
            if (vehicleSelectEl) {
                vehicleSelectEl.addEventListener('change', function() {
                    console.log('Vehicle changed to:', this.value);
                    if (productCat && productCat.value && productColumns && productColumns.length > 0) {
                        if (selectedBillIds.size === 0) {
                            loadBillsAndPopulateTable();
                        }
                        loadStockStatusAndLoading();
                    } else if (this.value && (!productCat || !productCat.value)) {
                        clearTableBody();
                        tableBody.innerHTML =
                            '<tr><td colspan="6" style="text-align:center;padding:6px;color:orange;">Please select a product category first</td></tr>';
                    } else if (productColumns && productColumns.length > 0) {
                        loadStockStatusAndLoading();
                    }
                });
            }

            // Initialize vehicles on page load if sales rep and date are already selected
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    const salesRep = document.getElementById('sales_rep_id');
                    const sheetDate = document.getElementById('sheet_date');
                    if (salesRep && sheetDate && salesRep.value && sheetDate.value) {
                        console.log('Initializing vehicles on page load');
                        setTimeout(function() {
                            if (typeof loadVehiclesForSalesRep === 'function') {
                                loadVehiclesForSalesRep();
                            }
                        }, 500);
                    }
                });
            } else {
                // DOM already loaded
                const salesRep = document.getElementById('sales_rep_id');
                const sheetDate = document.getElementById('sheet_date');
                if (salesRep && sheetDate && salesRep.value && sheetDate.value) {
                    console.log('Initializing vehicles (DOM already loaded)');
                    setTimeout(function() {
                        if (typeof loadVehiclesForSalesRep === 'function') {
                            loadVehiclesForSalesRep();
                        }
                    }, 500);
                }
            }

            // Clear table body
            function clearTableBody() {
                tableBody.innerHTML = '';
                attachRecalcListeners();
                recalcTotals();
            }

            // Track available bills and selected bill IDs
            let availableBills = [];
            let selectedBillIds = new Set();
            let isSelectingBill = false; // Flag to prevent reload during bill selection

            // Load vehicles based on sales rep and date
            function loadVehiclesForSalesRep() {
                const date = document.getElementById('sheet_date').value;
                const salesRep = document.getElementById('sales_rep_id').value;
                const vehicleSelect = document.getElementById('vehicle_id');

                if (!salesRep) {
                    if (vehicleSelect) {
                        vehicleSelect.innerHTML = '<option value="">--Select--</option>';
                    }
                    return;
                }

                // Preserve currently selected vehicle
                const currentVehicleId = vehicleSelect ? vehicleSelect.value : null;

                const params = new URLSearchParams({
                    date,
                    sales_rep_id: salesRep
                });

                fetch('{{ route('distribution.daily_summary.vehicles') }}?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => {
                        if (!r.ok) {
                            throw new Error('HTTP error! status: ' + r.status);
                        }
                        return r.json();
                    })
                    .then(vehicles => {
                        console.log('Vehicles loaded:', vehicles);
                        if (vehicleSelect) {
                            vehicleSelect.innerHTML = '<option value="">--Select--</option>';
                            if (vehicles && vehicles.length > 0) {
                                vehicles.forEach(v => {
                                    const opt = document.createElement('option');
                                    opt.value = v.id;
                                    opt.textContent = v.vehicle_no || ('Vehicle #' + v.id);
                                    vehicleSelect.appendChild(opt);
                                });

                                // Restore previously selected vehicle if it exists in the new list
                                if (currentVehicleId) {
                                    const vehicleExists = Array.from(vehicleSelect.options).some(opt => opt
                                        .value === currentVehicleId);
                                    if (vehicleExists) {
                                        vehicleSelect.value = currentVehicleId;
                                        // Don't trigger change event if bills are already selected
                                        // Only trigger if table is empty
                                        if (selectedBillIds.size === 0) {
                                            // Trigger change event to reload bills
                                            vehicleSelect.dispatchEvent(new Event('change'));
                                        }
                                    }
                                }
                            } else {
                                console.warn('No vehicles found for sales rep:', salesRep, 'date:', date);
                            }
                        }

                        // Trigger vehicle change to reload bills if category is selected
                        // Only reload if no bills are currently selected AND not currently selecting a bill
                        if (productCat && productCat.value && vehicleSelect && vehicleSelect.value) {
                            if (selectedBillIds.size === 0 && !isSelectingBill) {
                                console.log('Loading bills after vehicle load (no bills selected yet)');
                                loadBillsAndPopulateTable();
                            } else {
                                console.log(
                                    'Skipping bill reload - bills already selected or selection in progress:',
                                    Array.from(selectedBillIds));
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading vehicles:', error);
                        if (vehicleSelect) {
                            vehicleSelect.innerHTML = '<option value="">--Select--</option>';
                            const errorOpt = document.createElement('option');
                            errorOpt.value = '';
                            errorOpt.textContent = 'Error loading vehicles';
                            errorOpt.disabled = true;
                            vehicleSelect.appendChild(errorOpt);
                        }
                    });
            }

            // Expose function to window for access from other scripts
            window.loadVehiclesForSalesRep = loadVehiclesForSalesRep;


            function loadBillsAndPopulateTable() {
                // Don't load if no bills are selected (prevent reload)
                if (selectedBillIds.size > 0) {
                    console.log('Preventing bill reload - bills already selected:', Array.from(selectedBillIds));
                    return;
                }

                const rawDate = document.getElementById('sheet_date').value;
                const salesRep = document.getElementById('sales_rep_id').value;
                const vehicle = document.getElementById('vehicle_id').value;
                const route = document.getElementById('route_id').value;
                const categoryId = productCat.value; // Get the selected category

                // Normalize date
                let date = rawDate;
                if (rawDate) {
                    const dateOnly = rawDate.trim().split(' ')[0];
                    if (/^\d{4}-\d{2}-\d{2}$/.test(dateOnly)) {
                        date = dateOnly;
                    } else if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateOnly)) {
                        const parts = dateOnly.split('/');
                        date = parts[2] + '-' + parts[0] + '-' + parts[1];
                    }
                }

                // Validate required fields
                if (!date) {
                    clearTableBody();
                    const safeColspan = productColumns && productColumns.length > 0 ? (6 + productColumns.length) : 6;
                    tableBody.innerHTML = '<tr><td colspan="' + safeColspan +
                        '" style="text-align:center;padding:6px;color:orange;">Please select a date to load bills</td></tr>';
                    return;
                }

                if (!categoryId) {
                    clearTableBody();
                    tableBody.innerHTML =
                        '<tr><td colspan="6" style="text-align:center;padding:6px;color:orange;">Please select a product category first</td></tr>';
                    return;
                }

                // Show loading
                const safeColspan = 6;
                tableBody.innerHTML = '<tr><td colspan="' + safeColspan +
                    '" style="text-align:center;padding:6px;">Loading bills...</td></tr>';

                // Build params with category filter
                const params = new URLSearchParams({
                    date,
                    sales_rep_id: salesRep,
                    vehicle_id: vehicle,
                    route_id: route,
                    product_category_id: categoryId // This filters bills by category
                });

                console.log('Loading bills with params:', params.toString());

                fetch('{{ route('distribution.daily_summary.bills') }}?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => {
                        if (!r.ok) {
                            throw new Error('HTTP error! status: ' + r.status);
                        }
                        return r.json();
                    })
                    .then(bills => {
                        console.log('Bills loaded successfully:', bills ? bills.length : 0);

                        if (bills && bills.error) {
                            throw new Error(bills.message || bills.error);
                        }

                        availableBills = bills || [];
                        clearTableBody();

                        if (!availableBills || availableBills.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="' + safeColspan +
                                '" style="text-align:center;padding:6px;color:orange;">No bills found for the selected date and category</td></tr>';
                        } else {
                            // Add first empty row with bill dropdown
                            addEmptyRow(1);
                        }

                        attachRecalcListeners();
                        recalcTotals();
                        recalcCalls();
                        loadStockStatusAndLoading();
                    })
                    .catch(error => {
                        console.error('Error loading bills:', error);
                        tableBody.innerHTML = '<tr><td colspan="' + safeColspan +
                            '" style="text-align:center;color:red;padding:10px;">Error loading bills: ' + error
                            .message + '</td></tr>';
                    });
            }


            function addEmptyRow(index) {
                const tr = document.createElement('tr');
                tr.dataset.rowIndex = index;

                // Index cell
                const idxCell = document.createElement('td');
                idxCell.textContent = index;
                tr.appendChild(idxCell);

                // Bill No dropdown
                const billCell = document.createElement('td');
                const billSelect = document.createElement('select');
                billSelect.className = 'form-control bill-select';
                billSelect.style.width = '100%';
                const emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = '--Select Bill--';
                billSelect.appendChild(emptyOpt);

                // Add available bills
                if (availableBills && availableBills.length > 0) {
                    availableBills.forEach(bill => {
                        if (!selectedBillIds.has(bill.id)) {
                            const opt = document.createElement('option');
                            opt.value = bill.id;
                            const displayText = bill.invoice_no || bill.text || ('Bill #' + bill.id);
                            opt.textContent = displayText;
                            opt.dataset.billData = JSON.stringify(bill);
                            billSelect.appendChild(opt);
                        }
                    });
                }

                // Attach the event handler - THIS IS THE FIX
                billSelect.addEventListener('change', handleBillSelect);

                billCell.appendChild(billSelect);
                tr.appendChild(billCell);

                // Customer cell
                const custCell = document.createElement('td');
                const custInput = document.createElement('input');
                custInput.type = 'hidden';
                custInput.name = `lines[${index-1}][customer_id]`;
                custInput.value = '';
                custCell.appendChild(custInput);
                tr.appendChild(custCell);


                // Product quantity cells
                if (productColumns && productColumns.length > 0) {
                    productColumns.forEach((product, idx) => {
                        const td = document.createElement('td');
                        td.textContent = '0';
                        td.className = 'product-cell';
                        td.style.textAlign = 'center';
                        const selectedOption = productCat.options[productCat.selectedIndex];
                        const isLubricant = selectedOption && selectedOption.text.toLowerCase().includes(
                            'lubricant');
                        if (isLubricant && idx >= productColumns.length - 2) td.classList.add('lubricant-wide');
                        if (idx === productColumns.length - 1) td.classList.add('last-product-col');
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `lines[${index-1}][products][${product.id}]`;
                        input.value = '0';
                        td.appendChild(input);
                        tr.appendChild(td);
                    });
                }

                // Free issue cells if they exist
                if (freeIssueColumns && freeIssueColumns.length > 0) {
                    freeIssueColumns.forEach(fic => {
                        const td = document.createElement('td');
                        td.textContent = '';
                        td.className = 'free-issue-cell';
                        td.style.textAlign = 'center';
                        td.style.backgroundColor = '#f0fff0';
                        td.style.color = '#006600';
                        td.dataset.freeIssueKey = fic.key;
                        tr.appendChild(td);
                    });
                }

                // Gross sale cell
                const grossCell = document.createElement('td');
                grossCell.textContent = '0.00';
                grossCell.style.textAlign = 'right';
                grossCell.className = 'sticky-amount-col';
                const grossInput = document.createElement('input');
                grossInput.type = 'hidden';
                grossInput.name = `lines[${index-1}][gross_sale]`;
                grossInput.value = '0';
                grossCell.appendChild(grossInput);
                tr.appendChild(grossCell);

                // Discount cell
                const discountCell = document.createElement('td');
                discountCell.textContent = '0.00';
                discountCell.style.textAlign = 'right';
                discountCell.className = 'sticky-amount-col';
                const discountInput = document.createElement('input');
                discountInput.type = 'hidden';
                discountInput.name = `lines[${index-1}][discount]`;
                discountInput.value = '0';
                discountCell.appendChild(discountInput);
                tr.appendChild(discountCell);

                // Net sale cell
                const netCell = document.createElement('td');
                netCell.textContent = '0.00';
                netCell.style.textAlign = 'right';
                netCell.className = 'sticky-amount-col';
                const netInput = document.createElement('input');
                netInput.type = 'hidden';
                netInput.name = `lines[${index-1}][net_sale]`;
                netInput.value = '0';
                netCell.appendChild(netInput);
                tr.appendChild(netCell);

                tableBody.appendChild(tr);
                attachRecalcListeners();

                return tr;
            }

            // Define the bill select handler function
            function handleBillSelect(event) {
                const billSelect = event.target;
                const selectedBillId = billSelect.value;
                const tr = billSelect.closest('tr');

                console.log('Bill select changed. New value:', selectedBillId);

                if (selectedBillId) {
                    isSelectingBill = true;
                    billSelect.style.backgroundColor = '#e8f5e8';

                    const selectedOption = billSelect.options[billSelect.selectedIndex];
                    if (!selectedOption || !selectedOption.dataset.billData) {
                        console.error('Selected option has no bill data');
                        billSelect.style.backgroundColor = '#ffe6e6';
                        alert('Error: Selected bill has no data. Please try selecting a different bill.');
                        isSelectingBill = false;
                        return;
                    }


                    try {
                        const billData = JSON.parse(selectedOption.dataset.billData);


               if (billData.products && billData.products.length > 0) {
    const seenIds = new Set();
    let columnsChanged = false;

    billData.products.forEach(p => {
        const pid = String(p.product_id);
        if (seenIds.has(pid)) return;
        seenIds.add(pid);

        const hasActualSaleQty = parseFloat(p.qty) > 0;
        const isInFreeIssueColumns = freeIssueColumns.some(
            fic => String(fic.product_id) === pid
        );

        // Skip products that are free-issue only (no actual sale qty)
        if (!hasActualSaleQty && isInFreeIssueColumns) return;

        // Only add column if it doesn't already exist
        const alreadyExists = productColumns.some(pc => String(pc.id) === pid);
        if (!alreadyExists) {
            productColumns.push({
                id: p.product_id,
                name: p.product_name || `Product ${p.product_id}`
            });
            columnsChanged = true;
        }
    });

    if (columnsChanged) {
        renderHeader();
        renderStockStatusHeaders();
    }
    console.log('Product columns after bill select:', productColumns);

} else {
    console.warn('No products found in this bill');
    // Don't clear productColumns - other bills may have already set them
    if (productColumns.length === 0) {
        renderHeader();
        renderStockStatusHeaders();
    }
}
                        tr.dataset.billId = selectedBillId;
                        selectedBillIds.add(parseInt(selectedBillId));

                        populateRowFromBill(tr, billData, selectedBillId);
                        billSelect.style.backgroundColor = '#d4edda';

                    } catch (error) {
                        console.error('Error parsing bill data:', error);
                        billSelect.style.backgroundColor = '#ffe6e6';
                        alert('Error: Failed to parse bill data. Please try again.');
                        isSelectingBill = false;
                        return;
                    }

                    // Add new empty row if this was the last row
                    setTimeout(() => {
                        if (tr && tr.parentNode && tr === tableBody.lastElementChild) {
                            const newIndex = tableBody.children.length + 1;
                            console.log('Adding new empty row at index:', newIndex);
                            addEmptyRow(newIndex);
                        }
                        isSelectingBill = false;
                    }, 100);

                    setTimeout(() => {
                        recalcTotals();
                        recalcCalls();
                        recalcStockStatus();
                        queueAutosave();
                    }, 50);

                } else {
                    // Bill deselected
                    console.log('Bill deselected (empty value)');
                    billSelect.style.backgroundColor = '#fff3cd';

                    const oldBillId = tr.dataset.billId;
                    clearRowData(tr);
                    if (oldBillId) {
                        selectedBillIds.delete(parseInt(oldBillId));
                    }

                    setTimeout(() => {
                        billSelect.style.backgroundColor = '';
                    }, 1000);

                    updateAllBillDropdowns();
                    recalcTotals();
                    recalcCalls();
                    recalcStockStatus();
                    queueAutosave();
                }
            }
            // Format number with commas and decimals
            function formatProductQty(num) {
                const numValue = parseFloat(num) || 0;
                // Format with commas for thousands, preserve decimals
                return numValue.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 4
                });
            }

            function attachRecalcListeners() {
                tableBody.querySelectorAll(
                    'input[name*="[gross_sale]"], input[name*="[discount]"], input[name*="[net_sale]"]').forEach(
                    input => {
                        input.removeEventListener('input', handleAmountChange);
                        input.addEventListener('input', handleAmountChange);
                    });

                tableBody.querySelectorAll('input[name*="[products]"]').forEach(input => {
                    input.removeEventListener('input', handleProductChange);
                    input.addEventListener('input', handleProductChange);
                });
            }

            function handleAmountChange() {
                recalcTotals();
                queueAutosave();
            }

            function handleProductChange() {
                recalcTotals();
                queueAutosave();
            }


            function populateRowFromBill(tr, billData, billId) {
    tr.dataset.billId = billId;
    tr.dataset.customerId = billData.customer_id || '';

    // Update customer
    const custCell = tr.children[2];
    custCell.textContent = billData.customer_name || '';
    const custInput = custCell.querySelector('input[name*="[customer_id]"]');
    if (custInput) custInput.value = billData.customer_id || '';

    // ── Update product quantities by INPUT NAME (never by cell index) ──────
    productColumns.forEach((product, idx) => {
        let productInput = tr.querySelector(`input[name*="[products][${product.id}]"]`);

        if (!productInput) {
            // Cell doesn't exist yet — create and insert before free-issue cells or amount cols
            const firstFreeOrAmount = tr.querySelector('.free-issue-cell') || tr.querySelector('.sticky-amount-col');
            if (firstFreeOrAmount) {
                const td = document.createElement('td');
                td.className = 'product-cell';
                td.style.textAlign = 'center';
                const selOpt = productCat.options[productCat.selectedIndex];
                const isLub = selOpt && selOpt.text.toLowerCase().includes('lubricant');
                if (isLub && idx >= productColumns.length - 2) td.classList.add('lubricant-wide');
                if (idx === productColumns.length - 1) td.classList.add('last-product-col');
                productInput = document.createElement('input');
                productInput.type = 'hidden';
                productInput.name = `lines[${(parseInt(tr.dataset.rowIndex) || 1) - 1}][products][${product.id}]`;
                productInput.value = '0';
                td.appendChild(productInput);
                tr.insertBefore(td, firstFreeOrAmount);
            }
        }

        // Get qty from bill data — only actual sale qty, never free issue qty
        let productQty = 0;
        if (billData.products && Array.isArray(billData.products)) {
            const productItem = billData.products.find(p => p.product_id == product.id);
            if (productItem) {
                productQty = parseFloat(productItem.qty) || 0;
            }
        }

        const productCell = productInput ? productInput.closest('td') : null;
        if (productCell) {
            productCell.textContent = formatProductQty(productQty);
            productCell.appendChild(productInput);
            productInput.value = productQty;
        }
    });

    // ── Update Gross Sale, Discount, Net Sale by INPUT NAME ────────────────
    const grossValue    = parseFloat(billData.gross_sale || billData.total || 0);
    const discountValue = parseFloat(billData.discount || 0);
    const netValue      = parseFloat(billData.net_sale || billData.grand_total || (grossValue - discountValue));

    const grossInput = tr.querySelector('input[name*="[gross_sale]"]');
    if (grossInput) {
        const grossCell = grossInput.closest('td');
        if (grossCell) {
            grossCell.classList.add('sticky-amount-col');
            grossCell.textContent = grossValue.toFixed(2);
            grossCell.appendChild(grossInput);
            grossInput.value = grossValue;
            grossInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    const discountInput = tr.querySelector('input[name*="[discount]"]');
    if (discountInput) {
        const discountCell = discountInput.closest('td');
        if (discountCell) {
            discountCell.classList.add('sticky-amount-col');
            discountCell.textContent = discountValue.toFixed(2);
            discountCell.appendChild(discountInput);
            discountInput.value = discountValue;
            discountInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    const netInput = tr.querySelector('input[name*="[net_sale]"]');
    if (netInput) {
        const netCell = netInput.closest('td');
        if (netCell) {
            netCell.classList.add('sticky-amount-col');
            netCell.textContent = netValue.toFixed(2);
            netCell.appendChild(netInput);
            netInput.value = netValue;
            netInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    // ── Fetch free issue quantities for this invoice ───────────────────────
    console.log('Fetching free issues for invoice:', billId);

    fetch(`/distribution/daily-summary-sheet/free-issues?invoice_id=${billId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(freeLines => {
        console.log('Free issues data received:', freeLines);

        // Clear any existing free issue data for this row first
        tr.querySelectorAll('td.free-issue-cell').forEach(cell => {
            cell.textContent = '';
        });

        if (!freeLines || freeLines.length === 0) {
            console.log('No free issues for invoice', billId);
            tr.dataset.freeIssues = JSON.stringify([]);
            recalcTotals();
            recalcStockStatus();
            return;
        }

        let columnsExpanded = false;
        const freeIssuesData = [];

        freeLines.forEach((fl) => {
            const productId   = fl.product_id;
            const productName = fl.product_name || 'Free Product';
            const unitId      = fl.unit_id || 0;
            const unitName    = fl.unit_name || '';
            const qty         = parseFloat(fl.total_qty) || 0;

            if (!productId || qty <= 0) return;

            const key = `${productId}_${unitId}`;

            freeIssuesData.push({
                key, qty,
                product_id: productId,
                unit_id: unitId,
                product_name: productName,
                unit_name: unitName
            });

            // Add to freeIssueColumns only if not already there
            if (!freeIssueColumns.find(c => c.key === key)) {
                freeIssueColumns.push({
                    key, product_id: productId, unit_id: unitId,
                    product_name: productName, unit_name: unitName
                });
                columnsExpanded = true;
            }

            // Find or create free issue cell — always by data attribute, never by index
            let fiCell = tr.querySelector(`td[data-free-issue-key="${key}"]`);
            if (!fiCell) {
                const firstAmountCol = tr.querySelector('.sticky-amount-col');
                if (firstAmountCol) {
                    fiCell = document.createElement('td');
                    fiCell.className = 'free-issue-cell';
                    fiCell.dataset.freeIssueKey = key;
                    fiCell.style.textAlign = 'center';
                    fiCell.style.backgroundColor = '#f0fff0';
                    fiCell.style.color = '#006600';
                    fiCell.style.fontWeight = 'bold';
                    tr.insertBefore(fiCell, firstAmountCol);
                }
            }
            // Set ONLY the free issue qty — never product sale qty
            if (fiCell) fiCell.textContent = qty.toFixed(2);
        });

        tr.dataset.freeIssues = JSON.stringify(freeIssuesData);

        if (columnsExpanded) {
            renderHeader();
            // Ensure all OTHER existing rows also get the new free-issue cell (empty)
            tableBody.querySelectorAll('tr').forEach(bodyRow => {
                if (bodyRow === tr) return;
                freeIssueColumns.forEach(fic => {
                    if (!bodyRow.querySelector(`td[data-free-issue-key="${fic.key}"]`)) {
                        const firstAmountCol = bodyRow.querySelector('.sticky-amount-col');
                        if (firstAmountCol) {
                            const tdNew = document.createElement('td');
                            tdNew.className = 'free-issue-cell';
                            tdNew.dataset.freeIssueKey = fic.key;
                            tdNew.style.textAlign = 'center';
                            tdNew.style.backgroundColor = '#f0fff0';
                            tdNew.style.color = '#006600';
                            tdNew.style.fontWeight = 'bold';
                            tdNew.textContent = '';
                            bodyRow.insertBefore(tdNew, firstAmountCol);
                        }
                    }
                });
                // Re-apply stored free issue data for this row
                if (bodyRow.dataset.freeIssues) {
                    try {
                        JSON.parse(bodyRow.dataset.freeIssues).forEach(fi => {
                            const cell = bodyRow.querySelector(`td[data-free-issue-key="${fi.key}"]`);
                            if (cell && fi.qty > 0) cell.textContent = parseFloat(fi.qty).toFixed(2);
                        });
                    } catch(e) {}
                }
            });
        }

        updateFreeRowTotals();
        recalcTotals();
        recalcStockStatus();
    })
    .catch(error => {
        console.error('Error fetching free issues:', error);
        recalcTotals();
        recalcStockStatus();
    });
}


            // Helper function to update free row totals
            function updateFreeRowTotals() {
                const productsFreeRow = document.getElementById('products_free_qty_row');
                if (!productsFreeRow) return;

                for (let i = 1; i < productsFreeRow.children.length; i++) {
                    if (productsFreeRow.children[i]) productsFreeRow.children[i].textContent = '0.00';
                }

                const freeQuantities = {};
                tableBody.querySelectorAll('tr').forEach(row => {
                    if (row.dataset.freeIssues) {
                        try {
                            const rowFreeIssues = JSON.parse(row.dataset.freeIssues);
                            rowFreeIssues.forEach(fi => {
                                const qty = parseFloat(fi.qty) || 0;
                                if (fi.product_id && qty > 0) {
                                    freeQuantities[fi.product_id] = (freeQuantities[fi.product_id] ||
                                        0) + qty;
                                }
                            });
                        } catch (e) {}
                    }
                });

                productColumns.forEach((product, idx) => {
                    const colIndex = idx + 1;
                    const freeQty = freeQuantities[product.id] || 0;
                    if (productsFreeRow.children[colIndex]) {
                        productsFreeRow.children[colIndex].textContent = freeQty.toFixed(2);
                        productsFreeRow.children[colIndex].style.fontWeight = 'bold';
                        productsFreeRow.children[colIndex].style.color = '#006600';
                        productsFreeRow.children[colIndex].style.backgroundColor = '#f0fff0';
                    }
                });
            }


            function clearRowData(tr) {
                delete tr.dataset.billId;
                delete tr.dataset.customerId;

                // Clear customer
                const custCell = tr.children[2];
                custCell.textContent = '';
                const custInput = custCell.querySelector('input[name*="[customer_id]"]');
                if (custInput) custInput.value = '';

                // Clear products
                const productStartIndex = 3;
                productColumns.forEach((product, idx) => {
                    const productCell = tr.children[productStartIndex + idx];
                    productCell.textContent = '0';
                    const productInput = productCell.querySelector('input');
                    if (productInput) productInput.value = '0';
                });

                // Clear free-issue cells
                tr.querySelectorAll('td.free-issue-cell').forEach(cell => {
                    cell.textContent = '';
                });

                // Clear amounts
                const grossIndex = productStartIndex + productColumns.length + freeIssueColumns.length;
                const grossCell = tr.children[grossIndex];
                grossCell.textContent = '0.00';
                const grossInput = grossCell.querySelector('input[name*="[gross_sale]"]');
                if (grossInput) grossInput.value = '0';

                const discountIndex = grossIndex + 1;
                const discountCell = tr.children[discountIndex];
                discountCell.textContent = '0.00';
                const discountInput = discountCell.querySelector('input[name*="[discount]"]');
                if (discountInput) discountInput.value = '0';

                const netIndex = discountIndex + 1;
                const netCell = tr.children[netIndex];
                netCell.textContent = '0.00';
                const netInput = netCell.querySelector('input[name*="[net_sale]"]');
                if (netInput) netInput.value = '0';
            }

            // Update all bill dropdowns to exclude selected bills
            function updateAllBillDropdowns() {
                if (!availableBills || availableBills.length === 0) {
                    console.log('No available bills to update dropdowns');
                    return;
                }

                console.log('Updating bill dropdowns. Selected bill IDs:', Array.from(selectedBillIds));

                tableBody.querySelectorAll('.bill-select').forEach((select, idx) => {
                    // IMPORTANT: Save the current value BEFORE clearing
                    const currentValue = select.value;
                    const currentRow = select.closest('tr');
                    // Get billId from dataset first, then fallback to current value
                    const currentBillId = currentRow ? (currentRow.dataset.billId || currentValue || '') : (
                        currentValue || '');

                    console.log(
                        `  Row ${idx}: currentValue="${currentValue}", dataset.billId="${currentRow?.dataset.billId}", will restore="${currentBillId || currentValue}"`
                    );

                    // Store the value to restore after rebuild
                    const valueToRestore = currentBillId || currentValue;

                    // Temporarily disable the change event to prevent triggering during rebuild
                    const originalOnChange = select.onchange;
                    select.onchange = null;

                    // Clear and rebuild options
                    select.innerHTML = '<option value="">--Select Bill--</option>';

                    availableBills.forEach(bill => {
                        // Include bill if:
                        // 1. It's not selected in any other row, OR
                        // 2. It's the current row's selected bill (preserve current selection)
                        const billIdInt = parseInt(bill.id);
                        const currentBillIdInt = currentBillId ? parseInt(currentBillId) : null;
                        const currentValueInt = currentValue ? parseInt(currentValue) : null;

                        // Include if not selected elsewhere, OR if it's this row's current selection
                        const shouldInclude = !selectedBillIds.has(billIdInt) ||
                            (currentBillIdInt === billIdInt) ||
                            (currentValueInt === billIdInt);

                        if (shouldInclude) {
                            const opt = document.createElement('option');
                            opt.value = bill.id;
                            // Use invoice_no if available, otherwise use id or text
                            const displayText = bill.invoice_no || bill.text || ('Bill #' + bill.id);
                            opt.textContent = displayText;
                            opt.dataset.billData = JSON.stringify(bill);
                            select.appendChild(opt);
                        }
                    });

                    // CRITICAL: Restore the selected value AFTER rebuilding all options
                    if (valueToRestore) {
                        select.value = valueToRestore;
                        // Double-check it was set correctly
                        if (select.value !== valueToRestore) {
                            console.warn(
                                `  Row ${idx}: FAILED to restore bill selection: wanted "${valueToRestore}", got "${select.value}"`
                            );
                            console.warn(`  Available options:`, Array.from(select.options).map(o => o.value));
                        } else {
                            console.log(`  Row ${idx}: Successfully restored value "${valueToRestore}"`);
                        }
                    }

                    // Restore the change event handler
                    select.onchange = originalOnChange;
                });
            }

            // Recalculate totals - using net_sale for totals
            function recalcTotals() {
                const footerRow1 = document.getElementById('footer_row_1');
                if (!footerRow1) return;

                // Calculate product column totals
                const productTotals = {};
       
                productColumns.forEach((product, idx) => {
    productTotals[product.id] = 0;
    tableBody.querySelectorAll('tr').forEach(r => {
        const productInput = r.querySelector(`input[name*="[products][${product.id}]"]`);
        const qty = parseFloat(productInput ? productInput.value : 0);
        productTotals[product.id] += (isNaN(qty) ? 0 : qty);
    });
});
                // Update product totals in footer row 1 (Total This Page)
                // Product columns start at index 3 (after hide-cell, hide-cell, label)
                productColumns.forEach((product, idx) => {
                    const productCellIndex = 3 + idx;
                    if (footerRow1.children[productCellIndex]) {
                        const totalQty = productTotals[product.id] || 0;
                        footerRow1.children[productCellIndex].textContent = formatProductQty(totalQty);
                        footerRow1.children[productCellIndex].style.textAlign = 'center';
                    }
                });

                // Calculate amount totals
                let totalGross = 0;
                let totalDiscount = 0;
                let totalNet = 0;

// Use input name selectors — never cell index — so column count doesn't matter
tableBody.querySelectorAll('tr').forEach(r => {
    const grossInput    = r.querySelector('input[name*="[gross_sale]"]');
    const discountInput = r.querySelector('input[name*="[discount]"]');
    const netInput      = r.querySelector('input[name*="[net_sale]"]');

    const gross    = parseFloat(grossInput    ? grossInput.value    : 0) || 0;
    const discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
    const net      = parseFloat(netInput      ? netInput.value      : 0) || 0;

    totalGross    += gross;
    totalDiscount += discount;
    totalNet      += net;
});

                // Update amount totals in footer row 1
                // Gross, Discount, Net columns are after product columns
                const grossIndex = 3 + productColumns.length;
                const discountIndex = grossIndex + 1;
                const netIndex = discountIndex + 1;

                const totalGrossSpan = document.getElementById('total_gross_sale');
                const totalDiscountSpan = document.getElementById('total_discount');
                const totalThisPageSpan = document.getElementById('total_this_page');

                if (totalGrossSpan) totalGrossSpan.textContent = totalGross.toFixed(2);
                if (totalDiscountSpan) totalDiscountSpan.textContent = totalDiscount.toFixed(2);
                if (totalThisPageSpan) totalThisPageSpan.textContent = totalNet.toFixed(2);

                const totalThisPageInput = document.getElementById('total_this_page_input');
                if (totalThisPageInput) totalThisPageInput.value = totalNet.toFixed(2);

                updatePreviousPageGTForAllPages();
                updateGrandTotalForAllPages();
                updatePageNumbers();
                recalcStockStatus();
            }

            // Function to calculate cumulative total up to a specific page
            function calculateCumulativeTotalUpToPage(pageNumber) {
                let cumulativeTotal = 0;

                for (let i = 1; i <= pageNumber; i++) {
                    let pageNetTotal = 0;

                    if (i === 1) {
                        const netIndex = 3 + productColumns.length + freeIssueColumns.length + 2;
                        tableBody.querySelectorAll('tr').forEach(r => {
                            const netCell = r.children[netIndex];
                            const netInput = r.querySelector('input[name*="[net_sale]"]');

                            const net = parseFloat(
                                netCell && netCell.textContent.trim() ? netCell.textContent.trim().replace(
                                    /,/g, '') :
                                (netInput ? netInput.value : 0)
                            );
                            pageNetTotal += (isNaN(net) ? 0 : net);
                        });
                    } else {
                        const page = document.getElementById(`page_${i}`);
                        if (page) {
                            const pageBody = page.querySelector(`#sheet_table_body_${i}`);
                            if (pageBody) {
                                const netIndex = 3 + productColumns.length + freeIssueColumns.length + 2;
                                pageBody.querySelectorAll('tr').forEach(r => {
                                    const netCell = r.children[netIndex];
                                    const netInput = r.querySelector('input[name*="[net_sale]"]');

                                    const net = parseFloat(
                                        netCell && netCell.textContent.trim() ? netCell.textContent.trim()
                                        .replace(/,/g, '') :
                                        (netInput ? netInput.value : 0)
                                    );
                                    pageNetTotal += (isNaN(net) ? 0 : net);
                                });
                            }
                        }
                    }

                    cumulativeTotal += pageNetTotal;
                }

                return cumulativeTotal;
            }

            // Function to calculate Total This Page for a specific page
            function calculateTotalThisPage(pageNumber) {
                let pageNetTotal = 0;

                if (pageNumber === 1) {
                    const netIndex = 3 + productColumns.length + freeIssueColumns.length + 2;
                    tableBody.querySelectorAll('tr').forEach(r => {
                        const netCell = r.children[netIndex];
                        const netInput = r.querySelector('input[name*="[net_sale]"]');

                        const net = parseFloat(
                            netCell && netCell.textContent.trim() ? netCell.textContent.trim().replace(/,/g,
                                '') :
                            (netInput ? netInput.value : 0)
                        );
                        pageNetTotal += (isNaN(net) ? 0 : net);
                    });
                } else {
                    const page = document.getElementById(`page_${pageNumber}`);
                    if (page) {
                        const pageBody = page.querySelector(`#sheet_table_body_${pageNumber}`);
                        if (pageBody) {
                            const netIndex = 3 + productColumns.length + freeIssueColumns.length + 2;
                            pageBody.querySelectorAll('tr').forEach(r => {
                                const netCell = r.children[netIndex];
                                const netInput = r.querySelector('input[name*="[net_sale]"]');

                                const net = parseFloat(
                                    netCell && netCell.textContent.trim() ? netCell.textContent.trim()
                                    .replace(/,/g, '') :
                                    (netInput ? netInput.value : 0)
                                );
                                pageNetTotal += (isNaN(net) ? 0 : net);
                            });
                        }
                    }
                }

                return pageNetTotal;
            }

            // Function to update Previous Page GT for all pages
            function updatePreviousPageGTForAllPages() {
                const pages = document.querySelectorAll('.a4-page');
                const totalPagesCount = pages.length || 1;

                pages.forEach((page, index) => {
                    const pageNumber = index + 1;
                    const footerRow2 = page.querySelector(`#footer_row_2_${pageNumber}`) ||
                        (pageNumber === 1 ? document.getElementById('footer_row_2') : null);

                    if (footerRow2) {
                        // Find the Previous Page GT input in the Net Sale column
                        const prevPageGtInput = footerRow2.querySelector('#previous_page_gt') ||
                            footerRow2.querySelector(`input[name*="previous_page_gt"]`);

                        if (prevPageGtInput) {
                            // For page 1, Previous Page GT = 0
                            // For other pages, Previous Page GT = cumulative total of all previous pages
                            if (pageNumber === 1) {
                                prevPageGtInput.value = '0.00';
                            } else {
                                const previousTotal = calculateCumulativeTotalUpToPage(pageNumber - 1);
                                prevPageGtInput.value = previousTotal.toFixed(2);
                            }
                        }
                    }
                });
            }

            // Function to update Grand Total for all pages
            function updateGrandTotalForAllPages() {
                const pages = document.querySelectorAll('.a4-page');

                pages.forEach((page, index) => {
                    const pageNumber = index + 1;
                    const footerRow3 = page.querySelector(`#footer_row_3_${pageNumber}`) ||
                        (pageNumber === 1 ? document.getElementById('footer_row_3') : null);

                    if (footerRow3) {
                        const grandTotalSpan = footerRow3.querySelector('#grand_total') ||
                            footerRow3.querySelector(`#grand_total_${pageNumber}`);

                        if (grandTotalSpan) {
                            let previousPageGT = 0;
                            if (pageNumber > 1) {
                                previousPageGT = calculateCumulativeTotalUpToPage(pageNumber - 1);
                            }

                            const totalThisPageSpan = pageNumber === 1 ?
                                document.getElementById('total_this_page') :
                                page.querySelector(`#total_this_page_${pageNumber}`);

                            let totalThisPage = 0;
                            if (totalThisPageSpan && totalThisPageSpan.textContent.trim()) {
                                totalThisPage = parseFloat(totalThisPageSpan.textContent.trim().replace(/,/g,
                                    '')) || 0;
                            } else {
                                totalThisPage = calculateTotalThisPage(pageNumber);
                            }

                            const grandTotal = previousPageGT + totalThisPage;
                            grandTotalSpan.textContent = grandTotal.toFixed(2);

                            if (pageNumber === 1) {
                                const grandTotalInput = document.getElementById('grand_total_input');
                                if (grandTotalInput) grandTotalInput.value = grandTotal.toFixed(2);
                            }
                        }
                    }
                });
            }

            // Update page number display
            function collectLinesFromTable() {
                const lines = [];
                if (!tableBody) {
                    console.warn('tableBody not found');
                    return lines;
                }

                tableBody.querySelectorAll('tr').forEach((r, index) => {
                    const billSelect = r.querySelector('.bill-select');
                    const billId = billSelect ? billSelect.value : (r.dataset.billId || null);

                    if (!billId || billId === '') {
                        return;
                    }

                    const customerInput = r.querySelector('input[name*="[customer_id]"]');
                    const grossInput = r.querySelector('input[name*="[gross_sale]"]');
                    const discountInput = r.querySelector('input[name*="[discount]"]');
                    const netInput = r.querySelector('input[name*="[net_sale]"]');
                    const products = {};

                    if (productColumns && productColumns.length > 0) {
                        productColumns.forEach(product => {
                            const productInput = r.querySelector(
                                `input[name*="[products][${product.id}]"]`);
                            if (productInput) {
                                products[product.id] = parseFloat(productInput.value) || 0;
                            }
                        });
                    }

                    // Get free issues from dataset if available

                    // Get free issues from dataset if available
                    let freeIssues = [];
                    if (r.dataset.freeIssues) {
                        try {
                            const parsed = JSON.parse(r.dataset.freeIssues);
                            freeIssues = Array.isArray(parsed) ? parsed : [];
                        } catch (e) {
                            console.error('Error parsing free issues dataset:', e);
                            freeIssues = [];
                        }
                    }

                    lines.push({
                        page_no: parseInt(document.getElementById('page_no').value) || 1,
                        bill_id: billId,
                        customer_id: customerInput ? customerInput.value : null,
                        gross_sale: grossInput ? grossInput.value : 0,
                        discount: discountInput ? discountInput.value : 0,
                        net_sale: netInput ? netInput.value : 0,
                        products: products,
                        free_issues: freeIssues.length > 0 ? freeIssues : null,
                    });
                });
                return lines;
            }

            function collectSheetPayload(status = 'draft') {
                const sheetNumberEl = document.getElementById('sheet_number');
                const salesRepEl = document.getElementById('sales_rep_id');
                const agentNameEl = document.getElementById('agent_name');
                const dateEl = document.getElementById('sheet_date');
                const routeEl = document.getElementById('route_id');
                const vehicleEl = document.getElementById('vehicle_id');
                const distanceEl = document.getElementById('distance_km');
                const pageNoEl = document.getElementById('page_no');
                const totalPagesEl = document.getElementById('total_pages');
                const cashDepositedEl = document.getElementById('cash_deposited');
                const chequeDepositedEl = document.getElementById('cheque_deposited');
                const creditBillsBfEl = document.getElementById('credit_bills_bf');
                const chequesInHandEl = document.getElementById('cheques_in_hand');
                const creditBillsInHandEl = document.getElementById('credit_bills_in_hand');
                const cashInHandEl = document.getElementById('cash_in_hand');
                const callsVisitedEl = document.getElementById('calls_visited');
                const productiveCallsEl = document.getElementById('productive_calls');
                const callsVisitedBfEl = document.getElementById('calls_visited_bf');
                const productiveCallsBfEl = document.getElementById('productive_calls_bf');

                // Append free issues to product list so they are saved
                let combinedProductList = [];
                if (productColumns && productColumns.length > 0) {
                    combinedProductList = [...productColumns];
                }
                if (freeIssueColumns && freeIssueColumns.length > 0) {
                    freeIssueColumns.forEach(fic => {
                        combinedProductList.push({
                            id: 'free_' + fic.key,
                            name: 'Free' + (fic.unit_name ? ' (' + fic.unit_name + ')' : '') + ': ' +
                                fic.product_name,
                            is_free: true // Flag to distinguish in print view
                        });
                    });
                }

                return {
                    id: draftIdInput ? draftIdInput.value || null : null,
                    sheet_number: sheetNumberEl ? sheetNumberEl.value : '',
                    sales_rep_id: salesRepEl ? salesRepEl.value : '',
                    agent_name: agentNameEl ? agentNameEl.value : '',
                    date: dateEl ? dateEl.value : '',
                    route_id: routeEl ? routeEl.value : '',
                    vehicle_id: vehicleEl ? vehicleEl.value : '',
                    product_category_id: productCat ? productCat.value : '',
                    distance_km: distanceEl ? distanceEl.value : '',
                    page_no: pageNoEl ? pageNoEl.value : 1,
                    total_pages: totalPagesEl ? totalPagesEl.value : 1,
                    total_this_page: totalThisPageInput ? totalThisPageInput.value : 0,
                    previous_page_gt: previousPageGtInput ? previousPageGtInput.value : 0,
                    grand_total: grandTotalInput ? grandTotalInput.value : 0,
                    product_list: combinedProductList,
                    lines: collectLinesFromTable(),
                    status,
                    cash_deposited: cashDepositedEl ? cashDepositedEl.value || 0 : 0,
                    cheque_deposited: chequeDepositedEl ? chequeDepositedEl.value || 0 : 0,
                    credit_bills_bf: creditBillsBfEl ? creditBillsBfEl.value || 0 : 0,
                    cheques_in_hand: chequesInHandEl ? chequesInHandEl.value || 0 : 0,
                    credit_bills_in_hand: creditBillsInHandEl ? creditBillsInHandEl.value || 0 : 0,
                    cash_in_hand: cashInHandEl ? cashInHandEl.value || 0 : 0,
                    calls_visited: callsVisitedEl ? callsVisitedEl.value || 0 : 0,
                    productive_calls: productiveCallsEl ? productiveCallsEl.value || 0 : 0,
                    calls_visited_bf: callsVisitedBfEl ? callsVisitedBfEl.value || 0 : 0,
                    productive_calls_bf: productiveCallsBfEl ? productiveCallsBfEl.value || 0 : 0,
                    loading_sheet_no: loadingSheetNoInput ? loadingSheetNoInput.value || '' : '',
                    loading_sheets: loadingSheetsInput ? safeJsonParse(loadingSheetsInput.value, []) : [],
                    stock_status: stockStatusInput ? safeJsonParse(stockStatusInput.value, []) : []
                };
            }

            function runAutosave() {
                // Don't autosave if essential data is missing
                if (!productColumns.length) {
                    return;
                }

                // Don't autosave if sales rep or date is not selected
                const salesRep = document.getElementById('sales_rep_id').value;
                const date = document.getElementById('sheet_date').value;
                if (!salesRep || !date) {
                    return;
                }

                const payload = collectSheetPayload('draft');

                // Save to localStorage as backup (works even if server is unavailable)
                try {
                    localStorage.setItem('dss_draft_data', JSON.stringify(payload));
                    localStorage.setItem('dss_draft_timestamp', new Date().toISOString());
                } catch (e) {
                    console.warn('Failed to save to localStorage:', e);
                }

                // Try to save to server
                fetch('{{ route('distribution.daily_summary.autosave') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(r => {
                        if (!r.ok) {
                            return r.json().then(err => {
                                throw new Error(err.error || 'Server error: ' + r.status);
                            });
                        }
                        return r.json();
                    })
                    .then(resp => {
                        if (resp.error) {
                            console.error('Autosave error:', resp.error);
                            return;
                        }
                        if (resp.id) {
                            draftIdInput.value = resp.id;
                            localStorage.setItem('dss_last_id', resp.id);
                            // Also update localStorage backup with server ID
                            try {
                                payload.id = resp.id;
                                localStorage.setItem('dss_draft_data', JSON.stringify(payload));
                            } catch (e) {
                                console.warn('Failed to update localStorage:', e);
                            }
                        }
                    })
                    .catch(error => {
                        // Server save failed, but localStorage backup is already saved
                        console.warn('Server autosave failed:', error.message || error);
                    });
            }

            // Function to restore from localStorage backup
            function restoreFromLocalStorage() {
                try {
                    const savedData = localStorage.getItem('dss_draft_data');
                    if (!savedData) return false;

                    const data = JSON.parse(savedData);
                    const timestamp = localStorage.getItem('dss_draft_timestamp');

                    // Check if data is recent (within 24 hours)
                    if (timestamp) {
                        const savedTime = new Date(timestamp);
                        const now = new Date();
                        const hoursDiff = (now - savedTime) / (1000 * 60 * 60);

                        if (hoursDiff > 24) {
                            // Data is too old, don't restore
                            localStorage.removeItem('dss_draft_data');
                            localStorage.removeItem('dss_draft_timestamp');
                            return false;
                        }
                    }

                    // Restore the data
                    applySheetData(data);
                    return true;
                } catch (e) {
                    console.warn('Failed to restore from localStorage:', e);
                    return false;
                }
            }

            // Save before page unload
            window.addEventListener('beforeunload', function(e) {
                if (productColumns.length > 0) {
                    // Save to localStorage immediately
                    try {
                        const payload = collectSheetPayload('draft');
                        localStorage.setItem('dss_draft_data', JSON.stringify(payload));
                        localStorage.setItem('dss_draft_timestamp', new Date().toISOString());
                    } catch (err) {
                        console.warn('Failed to save on page unload:', err);
                    }
                }
            });

            const urlParams = new URLSearchParams(window.location.search);
            const saved = urlParams.get('saved');

            if (saved === '1') {
                localStorage.removeItem('dss_last_id');
                localStorage.removeItem('dss_draft_data');
                localStorage.removeItem('dss_draft_timestamp');

                document.getElementById('dss_form').reset();
                clearTableBody();

                window.location.href = '{{ route('distribution.daily_summary.create') }}';
            }
            // Auto-restore disabled - drafts will only restore when "Restore last draft" button is clicked
            // } else {
            //     window.addEventListener('load', function() {
            //         setTimeout(function() {
            //             const savedId = localStorage.getItem('dss_last_id');
            //             if (savedId) {
            //                 restoreDraftById(savedId).catch(() => {
            //                     restoreFromLocalStorage();
            //                 });
            //             } else {
            //                 restoreFromLocalStorage();
            //             }
            //         }, 1000);
            //     });
            // }

            // Update totals when previous page GT changes
            previousPageGtInput.addEventListener('input', function() {
                recalcTotals();
                recalcCalls();
                queueAutosave();
            });




            document.getElementById('route_id').addEventListener('change', function() {
                if (productCat.value) {
                    // Only reload bills if no bills are currently selected
                    if (selectedBillIds.size === 0) {
                        loadBillsAndPopulateTable();
                    }
                    loadStockStatusAndLoading();
                }
            });

            manualFields.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', queueAutosave);
                }
            });

            function applySheetData(sheet) {
                if (!sheet) return;

                draftIdInput.value = sheet.id || '';
                localStorage.setItem('dss_last_id', sheet.id || '');

                // Restore basic fields
                document.getElementById('sheet_number').value = sheet.sheet_number || '';
                document.getElementById('sales_rep_id').value = sheet.sales_rep_id || '';
                document.getElementById('agent_name').value = sheet.agent_name || '';
                document.getElementById('sheet_date').value = sheet.date || '';
                document.getElementById('route_id').value = sheet.route_id || '';
                document.getElementById('vehicle_id').value = sheet.vehicle_id || '';
                document.getElementById('distance_km').value = sheet.distance_km || '';
                document.getElementById('page_no').value = sheet.page_no || 1;
                document.getElementById('total_pages').value = sheet.total_pages || 1;
                previousPageGtInput.value = sheet.previous_page_gt || 0;

                document.getElementById('cash_deposited').value = sheet.cash_deposited || 0;
                document.getElementById('cheque_deposited').value = sheet.cheque_deposited || 0;
                document.getElementById('credit_bills_bf').value = sheet.credit_bills_bf || 0;
                document.getElementById('cheques_in_hand').value = sheet.cheques_in_hand || 0;
                document.getElementById('credit_bills_in_hand').value = sheet.credit_bills_in_hand || 0;
                document.getElementById('cash_in_hand').value = sheet.cash_in_hand || 0;
                document.getElementById('calls_visited').value = sheet.calls_visited || 0;
                document.getElementById('productive_calls').value = sheet.productive_calls || 0;
                document.getElementById('calls_visited_bf').value = sheet.calls_visited_bf || 0;
                document.getElementById('productive_calls_bf').value = sheet.productive_calls_bf || 0;

                // Restore product category and trigger change
                if (sheet.product_category_id) {
                    productCat.value = sheet.product_category_id;

                    // Build free issue columns from product list first
                    if (Array.isArray(sheet.product_list)) {
                        // Clear existing free issue columns
                        freeIssueColumns = [];

                        // Separate regular products from free issue products
                        const regularProducts = [];
                        sheet.product_list.forEach(p => {
                            if (p.is_free) {
                                // Extract key from id (format: free_pid_uid)
                                const key = p.id.replace(/^free_/, '');
                                freeIssueColumns.push({
                                    key: key,
                                    product_id: key.split('_')[0],
                                    unit_id: key.split('_')[1] || 0,
                                    product_name: p.name.replace(/^Free[^:]*:\s*/, ''),
                                    unit_name: p.name.match(/\(([^)]+)\)/)?.[1] || ''
                                });
                            } else {
                                regularProducts.push(p);
                            }
                        });

                        // Set product columns to regular products only
                        productColumns = regularProducts;
                    } else {
                        productColumns = [];
                    }

                    // Render headers with both regular and free issue columns
                    renderHeader();
                    renderStockStatusHeaders();

                    // Recalculate table width
                    updateCategoryLayout();
                } else {
                    productColumns = Array.isArray(sheet.product_list) ? sheet.product_list : [];
                    renderHeader();
                    renderStockStatusHeaders();
                    updateCategoryLayout();
                }

                // Load vehicles first
                if (sheet.sales_rep_id && sheet.date) {
                    loadVehiclesForSalesRep();
                }

                // After vehicles load, restore bills and rows
                setTimeout(function() {
                    if (sheet.product_category_id && productCat.value) {
                        loadBillsAndPopulateTable();
                    }

                    // After bills load, restore the rows
                    setTimeout(function() {
                        if (sheet.lines && sheet.lines.length) {
                            selectedBillIds.clear();
                            clearTableBody();

                            // Process each line
                            sheet.lines.forEach((line, idx) => {
                                // Wait for availableBills to be populated
                                const checkBills = setInterval(function() {
                                    if (availableBills && availableBills.length > 0) {
                                        clearInterval(checkBills);

                                        let billData = availableBills.find(b => b.id ==
                                            line.bill_id);

                                        // If bill not found in current available bills, create a basic bill object
                                        if (!billData) {
                                            billData = {
                                                id: line.bill_id,
                                                invoice_no: line.invoice_no || (
                                                    'Bill #' + line.bill_id),
                                                customer_id: line.customer_id,
                                                customer_name: line.customer_name ||
                                                    '',
                                                gross_sale: line.gross_sale || 0,
                                                discount: line.discount || 0,
                                                net_sale: line.net_sale || 0,
                                                products: []
                                            };
                                        }

                                        // Restore products data
                                        if (line.products_json) {
                                            let productsData = line.products_json;
                                            if (typeof productsData === 'string') {
                                                try {
                                                    productsData = JSON.parse(
                                                        productsData);
                                                } catch (e) {
                                                    productsData = {};
                                                }
                                            }

                                            // Convert to array format
                                            if (typeof productsData === 'object' && !
                                                Array.isArray(productsData)) {
                                                billData.products = Object.keys(
                                                    productsData).map(productId =>
                                                    ({
                                                        product_id: parseInt(
                                                            productId),
                                                        qty: parseFloat(
                                                            productsData[
                                                                productId]
                                                        ) || 0
                                                    }));
                                            }
                                        }

                                        // Restore free issues data
                                        if (line.free_issues_json) {
                                            let freeIssuesData = line.free_issues_json;
                                            if (typeof freeIssuesData === 'string') {
                                                try {
                                                    freeIssuesData = JSON.parse(
                                                        freeIssuesData);
                                                } catch (e) {
                                                    freeIssuesData = null;
                                                }
                                            }

                                            if (freeIssuesData) {
                                                billData.free_issues = freeIssuesData;

                                                // Add any new free issue columns that weren't in product_list
                                                if (Array.isArray(freeIssuesData)) {
                                                    freeIssuesData.forEach(fi => {
                                                        const key = fi.key ||
                                                            `${fi.product_id}_${fi.unit_id || 0}`;
                                                        const exists =
                                                            freeIssueColumns
                                                            .some(col => col
                                                                .key === key);

                                                        if (!exists) {
                                                            freeIssueColumns
                                                                .push({
                                                                    key: key,
                                                                    product_id: fi
                                                                        .product_id,
                                                                    unit_id: fi
                                                                        .unit_id ||
                                                                        0,
                                                                    product_name: fi
                                                                        .product_name ||
                                                                        'Product',
                                                                    unit_name: fi
                                                                        .unit_name ||
                                                                        ''
                                                                });
                                                        }
                                                    });

                                                    // Re-render headers if new columns added
                                                    if (freeIssueColumns.length > 0) {
                                                        renderHeader();
                                                    }
                                                }
                                            }
                                        }

                                        const rowIndex = idx + 1;
                                        const tr = addEmptyRow(rowIndex);

                                        if (tr) {
                                            const billSelect = tr.querySelector(
                                                '.bill-select');
                                            if (billSelect) {
                                                // Temporarily disable change event
                                                const originalOnChange = billSelect
                                                    .onchange;
                                                billSelect.onchange = null;

                                                billSelect.value = line.bill_id;

                                                // Restore row data
                                                restoreRowFromData(tr, billData, line);

                                                // Re-enable change event
                                                billSelect.onchange = originalOnChange;

                                                selectedBillIds.add(parseInt(line
                                                    .bill_id));
                                            }
                                        }
                                    }
                                }, 100);
                            });

                            // Add one empty row at the end after all lines are processed
                            setTimeout(function() {
                                if (tableBody.children.length > 0) {
                                    const newIndex = tableBody.children.length + 1;
                                    addEmptyRow(newIndex);
                                }

                                // Update all calculations
                                recalcTotals();
                                recalcCalls();
                                updatePageNumbers();
                                queueAutosave();
                            }, sheet.lines.length * 200);
                        } else {
                            // Add empty row if no lines
                            addEmptyRow(1);
                            recalcTotals();
                            recalcCalls();
                            updatePageNumbers();
                            queueAutosave();
                        }
                    }, 500);
                }, 300);

                // Restore stock status and loading sheets
                if (sheet.stock_status) {
                    populateStockStatusRows(sheet.stock_status);
                }
                renderLoadingSheetList(sheet.loading_sheets || [], sheet.loading_sheet_no || '');

                // Build and save full product list
                const fullProductList = [...(productColumns || [])];
                if (freeIssueColumns && freeIssueColumns.length > 0) {
                    freeIssueColumns.forEach(fic => {
                        fullProductList.push({
                            id: 'free_' + fic.key,
                            name: 'Free' + (fic.unit_name ? ' (' + fic.unit_name + ')' : '') + ': ' +
                                fic.product_name,
                            is_free: true
                        });
                    });
                }
                productListInput.value = JSON.stringify(fullProductList);

                // Save other data
                stockStatusInput.value = sheet.stock_status ? JSON.stringify(sheet.stock_status) : '';
                loadingSheetsInput.value = sheet.loading_sheets ? JSON.stringify(sheet.loading_sheets) : '';
                loadingSheetNoInput.value = sheet.loading_sheet_no || '';
            }

            // Helper function to restore row data
            function restoreRowFromData(tr, billData, lineData) {
                if (!tr || !billData) return;

                // Set dataset attributes
                tr.dataset.billId = billData.id;
                tr.dataset.customerId = billData.customer_id || '';

                // Restore customer
                const custCell = tr.children[2];
                custCell.textContent = billData.customer_name || lineData.customer_name || '';
                const custInput = custCell.querySelector('input[name*="[customer_id]"]');
                if (custInput) custInput.value = billData.customer_id || lineData.customer_id || '';

                // Restore product quantities
                const productStartIndex = 3;
                if (productColumns && productColumns.length > 0) {
                    productColumns.forEach((product, idx) => {
                        const productCell = tr.children[productStartIndex + idx];
                        let productQty = 0;

                        // Try from billData first, then from lineData
                        if (billData.products && Array.isArray(billData.products)) {
                            const productItem = billData.products.find(p => p.product_id == product.id);
                            productQty = productItem ? parseFloat(productItem.qty) : 0;
                        } else if (lineData.products_json) {
                            let productsData = lineData.products_json;
                            if (typeof productsData === 'string') {
                                try {
                                    productsData = JSON.parse(productsData);
                                } catch (e) {
                                    productsData = {};
                                }
                            }
                            productQty = parseFloat(productsData[product.id]) || 0;
                        }

                        productCell.textContent = formatProductQty(productQty);
                        const productInput = productCell.querySelector('input');
                        if (productInput) productInput.value = productQty;
                    });
                }

                // Restore free issues
                const freeIssuesData = billData.free_issues || (lineData.free_issues_json ?
                    (typeof lineData.free_issues_json === 'string' ? JSON.parse(lineData.free_issues_json) :
                        lineData.free_issues_json) :
                    null);

                if (freeIssuesData && freeIssueColumns && freeIssueColumns.length > 0) {
                    // Convert to array if it's an object
                    let freeIssuesArray = Array.isArray(freeIssuesData) ? freeIssuesData :
                        Object.values(freeIssuesData).filter(v => typeof v === 'object');

                    freeIssuesArray.forEach(fi => {
                        const key = fi.key || `${fi.product_id}_${fi.unit_id || 0}`;
                        const cell = tr.querySelector(`td[data-free-issue-key="${key}"]`);
                        if (cell) {
                            const qty = parseFloat(fi.qty) || 0;
                            cell.textContent = qty > 0 ? qty.toFixed(4).replace(/\.?0+$/, '') : '';
                            cell.style.fontWeight = 'bold';
                            cell.style.color = '#006600';
                        }
                    });
                }

                // Restore amounts
                const amountStartIndex = productStartIndex + (productColumns?.length || 0) + (freeIssueColumns
                    ?.length || 0);

                // Gross Sale
                const grossCell = tr.children[amountStartIndex];
                const grossValue = parseFloat(billData.gross_sale || lineData.gross_sale || 0);
                grossCell.textContent = grossValue.toFixed(2);
                const grossInput = grossCell.querySelector('input[name*="[gross_sale]"]');
                if (grossInput) grossInput.value = grossValue;

                // Discount
                const discountCell = tr.children[amountStartIndex + 1];
                const discountValue = parseFloat(billData.discount || lineData.discount || 0);
                discountCell.textContent = discountValue.toFixed(2);
                const discountInput = discountCell.querySelector('input[name*="[discount]"]');
                if (discountInput) discountInput.value = discountValue;

                // Net Sale
                const netCell = tr.children[amountStartIndex + 2];
                const netValue = parseFloat(billData.net_sale || lineData.net_sale || 0);
                netCell.textContent = netValue.toFixed(2);
                const netInput = netCell.querySelector('input[name*="[net_sale]"]');
                if (netInput) netInput.value = netValue;
            }

            function restoreDraftById(id) {
                if (!id) return Promise.reject('No ID provided');
                return fetch(`{{ url('distribution/daily-summary-sheet/restore') }}/${id}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        applySheetData(data);
                        // Update localStorage backup after successful restore
                        try {
                            const payload = collectSheetPayload('draft');
                            payload.id = id;
                            localStorage.setItem('dss_draft_data', JSON.stringify(payload));
                            localStorage.setItem('dss_draft_timestamp', new Date().toISOString());
                        } catch (e) {
                            console.warn('Failed to update localStorage after restore:', e);
                        }
                        return data;
                    })
                    .catch((error) => {
                        console.warn('Unable to restore draft from server:', error);
                        throw error;
                    });
            }

            restoreBtn.addEventListener('click', function() {
                const storedId = draftIdInput.value || localStorage.getItem('dss_last_id');
                if (!storedId) {
                    alert('No draft found to restore.');
                    return;
                }
                restoreDraftById(storedId);
            });


            // Submit form: populate hidden inputs lines & product_list
            const dssForm = document.getElementById('dss_form');
            if (dssForm) {
                dssForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const salesRep = document.getElementById('sales_rep_id').value;
                    const date = document.getElementById('sheet_date').value;
                    const productCategory = document.getElementById('product_category_id').value;

                    if (!salesRep) {
                        alert('Please select a Sales Rep');
                        document.getElementById('sales_rep_id').focus();
                        return false;
                    }
                    if (!date) {
                        alert('Please select a Date');
                        document.getElementById('sheet_date').focus();
                        return false;
                    }
                    if (!productCategory) {
                        alert('Please select a Product Category');
                        document.getElementById('product_category_id').focus();
                        return false;
                    }

                    recalcTotals();

                    // ── Collect lines fresh from the DOM ──────────────────────────────────
                    const lines = collectLinesFromTable();
                    console.log('SUBMIT: Collected lines:', JSON.stringify(lines));

                    // ── Build full product list including free issue columns ──────────────
                    const fullProductList = [...(productColumns || [])];
                    if (freeIssueColumns && freeIssueColumns.length > 0) {
                        freeIssueColumns.forEach(fic => {
                            fullProductList.push({
                                id: 'free_' + fic.key,
                                name: 'Free' + (fic.unit_name ? ' (' + fic.unit_name + ')' :
                                    '') + ': ' + fic.product_name,
                                is_free: true
                            });
                        });
                    }

                    // ── Populate ALL hidden inputs before submitting ──────────────────────
                    if (productListInput) {
                        productListInput.value = JSON.stringify(fullProductList);
                    }
                    if (linesInput) {
                        linesInput.value = JSON.stringify(lines || []);
                        console.log('SUBMIT: linesInput set to:', linesInput.value);
                    }

                    const stockStatusInputEl = document.getElementById('stock_status_input');
                    const loadingSheetsInputEl = document.getElementById('loading_sheets_input');
                    if (stockStatusInputEl && !stockStatusInputEl.value) {
                        stockStatusInputEl.value = JSON.stringify([]);
                    }
                    if (loadingSheetsInputEl && !loadingSheetsInputEl.value) {
                        loadingSheetsInputEl.value = JSON.stringify([]);
                    }

                    // Re-read totals from DOM in case recalcTotals updated them
                    const totalThisPageSpanEl = document.getElementById('total_this_page');
                    const grandTotalSpanEl = document.getElementById('grand_total');
                    const totalThisPageInpEl = document.getElementById('total_this_page_input');
                    const grandTotalInpEl = document.getElementById('grand_total_input');

                    if (totalThisPageInpEl && totalThisPageSpanEl) {
                        totalThisPageInpEl.value = totalThisPageSpanEl.textContent || '0';
                    }
                    if (grandTotalInpEl && grandTotalSpanEl) {
                        grandTotalInpEl.value = grandTotalSpanEl.textContent || '0';
                    }

                    console.log('SUBMIT: linesInput just before submit:', linesInput.value);
                    console.log('SUBMIT: productListInput:', productListInput.value);

                    // ── Cancel any pending autosave then submit ───────────────────────────
                    clearTimeout(autosaveTimer);

                    dssForm.submit();
                });
            }

            // Initialize if category is already selected (on page refresh)
            if (productCat.value) {
                productCat.dispatchEvent(new Event('change'));
            }

            // ========== A4 PAGINATION SYSTEM ==========
            const A4_HEIGHT_MM = 297;
            const A4_HEIGHT_PX = A4_HEIGHT_MM * 3.779527559; // Convert mm to px (1mm = 3.779527559px at 96dpi)
            const PAGE_MARGIN_TOP = 10; // 10mm top margin
            const PAGE_MARGIN_BOTTOM = 10; // 10mm bottom margin
            const AVAILABLE_HEIGHT = A4_HEIGHT_PX - (PAGE_MARGIN_TOP + PAGE_MARGIN_BOTTOM) * 3.779527559;

            let currentPageNumber = 1;
            let totalPages = 1;
            const pagesContainer = document.getElementById('pages_container');

            // Function to get header HTML (without Bill Numbers section)
            function getHeaderHTML() {
                const salesRepSelect = document.getElementById('sales_rep_id');
                const agentInput = document.getElementById('agent_name');
                const dateInput = document.getElementById('sheet_date');
                const routeSelect = document.getElementById('route_id');
                const vehicleSelect = document.getElementById('vehicle_id');
                const productCatSelect = document.getElementById('product_category_id');
                const distanceInput = document.getElementById('distance_km');
                const sheetNumberInput = document.getElementById('sheet_number');
                const loadingSheetDisplay = document.getElementById('loading_sheet_display');

                return `
                    <div class="header">
                        <div class="sheet-title">Daily Summary Sheet</div>
                        <div class="business-name">{!! $business->name !!}</div>
                        <div class="business-location">{!! $locationText ?: '' !!}</div>
                    </div>
                    <div class="header-row">
                        <div class="header-field form-group">
                            <label class="control-label">Sales Rep:</label>
                            <span class="form-control-static">${salesRepSelect.options[salesRepSelect.selectedIndex]?.text || '-- Select Sales Rep --'}</span>
                        </div>
                        <div class="header-field form-group">
                            <label class="control-label">Agent:</label>
                            <span class="form-control-static">${agentInput.value || ''}</span>
                        </div>
                        <div class="header-field form-group">
                            <label class="control-label">Date:</label>
                            <span class="form-control-static">${dateInput.value || ''}</span>
                        </div>
                    </div>
                    <div class="header-row">
                        <div class="header-field form-group">
                            <label class="control-label">Route:</label>
                            <span class="form-control-static">${routeSelect.options[routeSelect.selectedIndex]?.text || '--Select--'}</span>
                        </div>
                        <div class="header-field form-group">
                            <label class="control-label">Vehicle No:</label>
                            <span class="form-control-static">${vehicleSelect.options[vehicleSelect.selectedIndex]?.text || '--Select--'}</span>
                        </div>
                        <div class="header-field form-group">
                            <label class="control-label">Product Category:</label>
                            <span class="form-control-static">${productCatSelect.options[productCatSelect.selectedIndex]?.text || '--Select--'}</span>
                        </div>
                        <div class="header-field form-group">
                            <label class="control-label">Distance (km):</label>
                            <span class="form-control-static">${distanceInput.value || ''}</span>
                        </div>
                    </div>
                    <div class="header-row" style="justify-content: center; margin-top: 8px;">
                        <div class="header-field form-group">
                            <label class="control-label">Sheet No:</label>
                            <span class="form-control-static">${sheetNumberInput.value || ''}</span>
                        </div>
                    </div>
                `;
            }

            // Function to create a new page
            function createNewPage(pageNumber) {
                const pageDiv = document.createElement('div');
                pageDiv.className = 'a4-page';
                pageDiv.id = `page_${pageNumber}`;
                pageDiv.setAttribute('data-page-number', pageNumber);

                const headerHTML = getHeaderHTML();
                const tableHeadHTML = tableHead.outerHTML.replace('id="header_row"', `id="header_row_${pageNumber}"`);
                const tableFootHTML = document.getElementById('sheet_table_foot').innerHTML.replace(/id="([^"]+)"/g, (
                    match, id) => `id="${id}_${pageNumber}"`);

                pageDiv.innerHTML = `
                    <div class="page-content">
                        ${headerHTML}
                        <div class="table-container">
                            <table id="sheet_table_${pageNumber}" class="sheet-table">
                                <thead id="sheet_table_head_${pageNumber}">
                                    ${tableHeadHTML}
                                </thead>
                                <tbody id="sheet_table_body_${pageNumber}">
                                    <!-- Rows will be added here -->
                                </tbody>
                                <tfoot id="sheet_table_foot_${pageNumber}">
                                    ${tableFootHTML}
                        </tfoot>
                        </table>
                        </div>
                    </div>
                        <div class="summary-flex" id="summary_${pageNumber}">
                            <!-- Summary will be cloned -->
                        </div>
                        <div class="page-number-display" id="page_number_${pageNumber}">
                            Page <span class="current_page_no">${pageNumber}</span> of <span class="total_pages_display">${totalPages}</span>
                        </div>
                        <div class="signature-section" id="signature_${pageNumber}">
                            <div>
                                <div style="border-top:1px dotted #000;width:180px;margin-bottom:4px;"></div>
                                <div style="font-weight:bold;font-size:11px;">Sales rep Signature</div>
                            </div>
                            <div>
                                <div style="border-top:1px dotted #000;width:180px;margin-bottom:4px;"></div>
                                <div style="font-weight:bold;font-size:11px;">Agent Signature</div>
                            </div>
                            <div style="text-align:right;">
                                <button type="submit" class="btn btn-primary" style="padding:6px 24px;">Save</button>
                            </div>
                        </div>
                    </div>
                `;

                return pageDiv;
            }

            // Function to check if content exceeds page and paginate
            function checkAndPaginate() {
                const firstPage = document.getElementById('page_1');
                if (!firstPage) return;

                // Get all table rows from the main table body
                const allRows = Array.from(tableBody.querySelectorAll('tr'));
                if (allRows.length === 0) {
                    updatePageNumbers();
                    return;
                }

                // Clear all pages except first
                const existingPages = pagesContainer.querySelectorAll('.a4-page');
                for (let i = 1; i < existingPages.length; i++) {
                    existingPages[i].remove();
                }

                // Reset to first page
                currentPageNumber = 1;
                totalPages = 1;

                // Simple pagination: distribute rows evenly or based on approximate row height
                const APPROX_ROW_HEIGHT = 25; // Approximate height per row in pixels
                const HEADER_HEIGHT = 200; // Approximate header height
                const FOOTER_HEIGHT = 150; // Approximate footer height
                const ROWS_PER_PAGE = Math.floor((AVAILABLE_HEIGHT - HEADER_HEIGHT - FOOTER_HEIGHT) /
                    APPROX_ROW_HEIGHT);

                if (allRows.length <= ROWS_PER_PAGE) {
                    // All rows fit on first page
                    updatePageNumbers();
                    return;
                }

                // Distribute rows across pages
                let rowIndex = 0;
                let currentPageRows = [];

                while (rowIndex < allRows.length) {
                    currentPageRows.push(allRows[rowIndex]);

                    if (currentPageRows.length >= ROWS_PER_PAGE || rowIndex === allRows.length - 1) {
                        if (currentPageNumber === 1) {
                            // Keep rows in original table
                            currentPageRows = [];
                        } else {
                            // Create new page and add rows
                            const newPage = createNewPage(currentPageNumber);
                            pagesContainer.appendChild(newPage);
                            const newPageBody = newPage.querySelector(`#sheet_table_body_${currentPageNumber}`);

                            currentPageRows.forEach(row => {
                                const clonedRow = row.cloneNode(true);
                                newPageBody.appendChild(clonedRow);
                            });

                            currentPageRows = [];
                        }

                        if (rowIndex < allRows.length - 1) {
                            currentPageNumber++;
                            totalPages = currentPageNumber;
                        }
                    }

                    rowIndex++;
                }

                updatePageNumbers();

                // Update Previous Page GT for all pages after pagination
                updatePreviousPageGTForAllPages();

                // Update Grand Total for all pages after pagination
                updateGrandTotalForAllPages();
            }

            // Function to update page numbers on all pages
            function updatePageNumbers() {
                const pagesContainer = document.getElementById('pages_container');

                let calculatedTotalPages = 1;
                let calculatedCurrentPage = 1;

                if (pagesContainer) {
                    const pages = pagesContainer.querySelectorAll('.a4-page');
                    calculatedTotalPages = Math.max(calculatedTotalPages, pages.length);

                    pages.forEach((page, index) => {
                        const pageNum = index + 1;
                        const pageNumberDisplay = page.querySelector('.page-number-display');
                        if (pageNumberDisplay) {
                            const currentSpan = pageNumberDisplay.querySelector('.current_page_no');
                            const totalSpan = pageNumberDisplay.querySelector('.total_pages_display');
                            if (currentSpan) currentSpan.textContent = pageNum;
                            if (totalSpan) totalSpan.textContent = calculatedTotalPages;
                        }
                    });
                }

                const pageNoInput = document.getElementById('page_no');
                const totalPagesInput = document.getElementById('total_pages');
                const currentPageNoSpan = document.getElementById('current_page_no');
                const totalPagesDisplaySpan = document.getElementById('total_pages_display');

                const currentPage = parseInt(pageNoInput ? pageNoInput.value : 1) || 1;
                const totalPagesValue = parseInt(totalPagesInput ? totalPagesInput.value : 1) || 1;

                let finalCurrentPage = currentPage;
                let finalTotalPages = Math.max(totalPagesValue, calculatedTotalPages);

                try {
                    if (typeof currentPageNumber !== 'undefined' && currentPageNumber) {
                        finalCurrentPage = currentPageNumber;
                    }
                } catch (e) {}

                try {
                    if (typeof totalPages !== 'undefined' && totalPages) {
                        finalTotalPages = Math.max(totalPages, calculatedTotalPages);
                    }
                } catch (e) {}

                if (pageNoInput) pageNoInput.value = finalCurrentPage;
                if (totalPagesInput) totalPagesInput.value = finalTotalPages;
                if (currentPageNoSpan) currentPageNoSpan.textContent = finalCurrentPage;
                if (totalPagesDisplaySpan) totalPagesDisplaySpan.textContent = finalTotalPages;
            }

            // Monitor table changes and repaginate
            const observer = new MutationObserver(() => {
                setTimeout(checkAndPaginate, 100);
            });

            if (tableBody) {
                observer.observe(tableBody, {
                    childList: true,
                    subtree: true
                });
            }

            // Initial pagination check after a delay
            setTimeout(checkAndPaginate, 1000);

            // Load vehicles and routes on page load if sales rep and date are already selected
            const initialSalesRep = document.getElementById('sales_rep_id').value;
            const initialDate = document.getElementById('sheet_date').value;
            if (initialSalesRep && initialDate) {
                // Load vehicles for the selected sales rep
                loadVehiclesForSalesRep();
            }

            // Calculate cash total when any cash field changes
            function recalcCashTotal() {
                const cashDeposited = parseFloat(document.getElementById('cash_deposited').value || 0);
                const chequeDeposited = parseFloat(document.getElementById('cheque_deposited').value || 0);
                const creditBillsBf = parseFloat(document.getElementById('credit_bills_bf').value || 0);
                const chequesInHand = parseFloat(document.getElementById('cheques_in_hand').value || 0);
                const creditBillsInHand = parseFloat(document.getElementById('credit_bills_in_hand').value || 0);
                const cashInHand = parseFloat(document.getElementById('cash_in_hand').value || 0);

                const total = cashDeposited + chequeDeposited + creditBillsBf + chequesInHand + creditBillsInHand +
                    cashInHand;
                const cashTotalEl = document.getElementById('cash_total');
                if (cashTotalEl) {
                    cashTotalEl.textContent = total.toFixed(2);
                }
            }

            // Add event listeners to all cash fields
            ['cash_deposited', 'cheque_deposited', 'credit_bills_bf', 'cheques_in_hand', 'credit_bills_in_hand',
                'cash_in_hand'
            ].forEach(id => {
                const field = document.getElementById(id);
                if (field) {
                    field.addEventListener('input', recalcCashTotal);
                    field.addEventListener('change', recalcCashTotal);
                }
            });

            // Calculate initial total
            recalcCashTotal();

            setTimeout(() => {
                attachRecalcListeners();
                recalcTotals();
            }, 500);

            function syncTableScrolling() {
                const tableScrollWrapper = document.querySelector('.table-scroll-wrapper');
                const productsSummaryContainer = document.querySelector('.stock-left');

                if (!tableScrollWrapper || !productsSummaryContainer) return;

                function syncScroll() {
                    const scrollLeft = productsSummaryContainer.scrollLeft;
                    tableScrollWrapper.scrollLeft = scrollLeft;
                }

                productsSummaryContainer.addEventListener('scroll', syncScroll);

                const resizeObserver = new ResizeObserver(() => {
                    setTimeout(syncScroll, 100);
                });

                resizeObserver.observe(tableScrollWrapper);
                resizeObserver.observe(productsSummaryContainer);

                setTimeout(syncScroll, 500);
            }

            setTimeout(syncTableScrolling, 500);

        })();
    </script>
@endsection
