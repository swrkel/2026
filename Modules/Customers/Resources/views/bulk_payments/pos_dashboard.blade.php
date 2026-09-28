@extends('layouts.app')

@section('title', 'Bulk Payment')



@section('content')
<style>
.customer-bulk-payment-page {
    --bulk-primary: #2f67f6;
    --bulk-primary-dark: #174fc4;
    --bulk-green: #16a34a;
    --bulk-orange: #f59e0b;
    --bulk-purple: #7c3aed;
    --bulk-red: #ef4444;
    --bulk-cyan: #0891b2;
    --bulk-ink: #0f172a;
    --bulk-muted: #64748b;
    --bulk-line: #dde6f2;
    --bulk-soft: #f6f9ff;
    --bulk-bg: #f4f7fb;
    padding-bottom: 28px;
}

.customer-bulk-payment-page * {
    box-sizing: border-box;
}

.customer-bulk-payment-page .bulk-page-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
    padding: 6px 2px 2px;
}

.customer-bulk-payment-page .bulk-page-head-main {
    display: flex;
    align-items: center;
    gap: 16px;
}

.customer-bulk-payment-page .bulk-page-head-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    background: linear-gradient(180deg, #f0f5ff 0%, #e6efff 100%);
    color: var(--bulk-primary);
    border: 1px solid #cfe0ff;
    box-shadow: 0 8px 22px rgba(47, 103, 246, .10);
    font-size: 24px;
}

.customer-bulk-payment-page .bulk-page-head h1 {
    margin: 0 0 5px;
    color: var(--bulk-ink);
    font-size: 19px;
    font-weight: 800;
    line-height: 1.25;
}

.customer-bulk-payment-page .bulk-page-head p {
    margin: 0;
    color: var(--bulk-muted);
    font-size: 13px;
    font-weight: 500;
}

.customer-bulk-payment-page .bulk-page-head-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}

.customer-bulk-payment-page .bulk-breadcrumbs {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #7b8aa5;
    font-size: 12px;
    font-weight: 600;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.customer-bulk-payment-page .bulk-breadcrumbs strong {
    color: #51627f;
}

.customer-bulk-payment-page .bulk-reference-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 9px 14px;
    border-radius: 999px;
    background: #eef4ff;
    border: 1px solid #d3e0fb;
    color: var(--bulk-primary);
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
}

.customer-bulk-payment-page .bulk-alert {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    margin-bottom: 16px;
    padding: 15px 18px;
    border-radius: 16px;
    font-weight: 600;
}

.customer-bulk-payment-page .bulk-alert-success {
    color: #fff;
    background: linear-gradient(135deg, #16a34a, #15803d);
    box-shadow: 0 10px 24px rgba(22, 163, 74, .18);
}

.customer-bulk-payment-page .bulk-alert-error {
    color: #991b1b;
    background: #fff1f2;
    border: 1px solid #fecdd3;
}

.customer-bulk-payment-page .bulk-alert a {
    color: inherit;
    text-decoration: underline;
    margin-left: 8px;
}

.customer-bulk-payment-page .bulk-error-list {
    margin: 6px 0 0;
    padding-left: 18px;
}

.customer-bulk-payment-page .bulk-panel {
    margin-bottom: 18px;
    background: #fff;
    border: 1px solid #e3ebf5;
    border-radius: 24px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
    overflow: hidden;
}

.customer-bulk-payment-page .bulk-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 22px 24px;
    border-bottom: 1px solid #ecf1f8;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
}

.customer-bulk-payment-page .bulk-panel-heading {
    display: flex;
    align-items: center;
    gap: 14px;
}

.customer-bulk-payment-page .bulk-panel-heading-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--bulk-primary);
    background: #edf4ff;
    font-size: 18px;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.8);
}

.customer-bulk-payment-page .bulk-panel-title {
    margin: 0 0 3px;
    color: var(--bulk-ink);
    font-size: 17px;
    font-weight: 800;
}

.customer-bulk-payment-page .bulk-panel-subtitle {
    margin: 0;
    color: var(--bulk-muted);
    font-size: 12px;
    font-weight: 500;
}

.customer-bulk-payment-page .bulk-panel-body {
    padding: 22px 24px;
}

.customer-bulk-payment-page .bulk-form-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 18px 20px;
}

.customer-bulk-payment-page .bulk-field {
    min-width: 0;
}

.customer-bulk-payment-page .bulk-field.is-full {
    grid-column: 3 / -1;
}

.customer-bulk-payment-page .bulk-field label {
    display: block;
    margin: 0 0 8px;
    color: #334155;
    font-size: 13px;
    font-weight: 800;
}

.customer-bulk-payment-page .bulk-field label .required {
    color: var(--bulk-red);
}

.customer-bulk-payment-page .bulk-input-shell {
    position: relative;
    display: block;
}

.customer-bulk-payment-page .bulk-field .form-control,
.customer-bulk-payment-page .bulk-field select,
.customer-bulk-payment-page .bulk-field textarea {
    width: 100%;
    min-height: 46px;
    border: 1px solid #d6dfeb;
    border-radius: 14px;
    padding: 10px 14px;
    color: #172033;
    background: #fff;
    box-shadow: none;
    transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
}

.customer-bulk-payment-page .bulk-field select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.customer-bulk-payment-page .bulk-field textarea {
    min-height: 54px;
    resize: vertical;
    padding-top: 12px;
}

.customer-bulk-payment-page .bulk-field .form-control:focus,
.customer-bulk-payment-page .bulk-field select:focus,
.customer-bulk-payment-page .bulk-field textarea:focus {
    border-color: #79a4ff;
    box-shadow: 0 0 0 3px rgba(35, 103, 242, .10);
    outline: none;
}

.customer-bulk-payment-page .bulk-input-shell.has-icon .form-control,
.customer-bulk-payment-page .bulk-input-shell.has-icon select,
.customer-bulk-payment-page .bulk-input-shell.has-icon textarea {
    padding-left: 48px;
}

.customer-bulk-payment-page .bulk-input-shell.has-date-icon .form-control,
.customer-bulk-payment-page .bulk-input-shell.has-select-icon select {
    padding-right: 38px;
}

.customer-bulk-payment-page .bulk-input-icon,
.customer-bulk-payment-page .bulk-trailing-icon {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #5f7cbf;
    pointer-events: none;
    font-size: 16px;
}

.customer-bulk-payment-page .bulk-input-icon {
    left: 14px;
}

.customer-bulk-payment-page .bulk-input-icon.top-align {
    top: 14px;
    transform: none;
}

.customer-bulk-payment-page .bulk-trailing-icon {
    right: 14px;
    color: #374151;
}

.customer-bulk-payment-page .bulk-input-shell.has-select-icon::after {
    content: '\f107';
    font-family: FontAwesome;
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #7c8799;
    pointer-events: none;
    font-size: 16px;
}

.customer-bulk-payment-page .bulk-readonly {
    background: #f4f7fb !important;
    color: #5a6881 !important;
    font-weight: 700;
}

.customer-bulk-payment-page .is-readonly-shell::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 14px;
    pointer-events: none;
    background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(0,0,0,0));
}

.customer-bulk-payment-page .bulk-method-fields {
    display: contents;
}

.customer-bulk-payment-page .is-hidden {
    display: none !important;
}

.customer-bulk-payment-page .bulk-checkbox-field {
    display: flex;
    align-items: flex-end;
    min-height: 76px;
}

.customer-bulk-payment-page .bulk-inline-check {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-height: 46px;
    margin: 0;
    padding: 11px 14px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-weight: 700;
}

.customer-bulk-payment-page .bulk-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 18px;
}

.customer-bulk-payment-page .bulk-summary-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 16px;
    min-height: 112px;
    padding: 18px 20px 18px 22px;
    border-radius: 22px;
    background: #fff;
    border: 1px solid #e3ebf5;
    box-shadow: 0 10px 22px rgba(15,23,42,.05);
    overflow: hidden;
}

.customer-bulk-payment-page .bulk-summary-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--card-color, var(--bulk-primary));
}

.customer-bulk-payment-page .bulk-summary-card.total-due { --card-color: #ef4444; --card-soft: #fff1f2; }
.customer-bulk-payment-page .bulk-summary-card.payment { --card-color: #2563eb; --card-soft: #eef4ff; }
.customer-bulk-payment-page .bulk-summary-card.allocated { --card-color: #16a34a; --card-soft: #eefcf2; }
.customer-bulk-payment-page .bulk-summary-card.unallocated { --card-color: #f59e0b; --card-soft: #fff8e8; }

.customer-bulk-payment-page .bulk-summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    background: var(--card-soft);
    color: var(--card-color);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.85);
}

.customer-bulk-payment-page .bulk-summary-content {
    flex: 1 1 auto;
    min-width: 0;
}

.customer-bulk-payment-page .bulk-summary-label {
    display: block;
    margin-bottom: 8px;
    color: #6b7a92;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.customer-bulk-payment-page .bulk-summary-value {
    display: block;
    max-width: 100%;
    color: var(--card-color);
    font-size: clamp(17px, 1.35vw, 23px);
    line-height: 1.08;
    font-weight: 850;
    letter-spacing: -.02em;
    white-space: nowrap;
    word-break: normal;
    overflow: hidden;
    text-overflow: ellipsis;
}

.customer-bulk-payment-page .bulk-summary-sparkline {
    width: 72px;
    height: 34px;
    margin-left: auto;
    opacity: .25;
    background-repeat: no-repeat;
    background-size: contain;
    background-position: center right;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='72' height='34' viewBox='0 0 72 34'%3E%3Cpolyline fill='none' stroke='%23ffffff' stroke-width='0' points='0,0'/%3E%3Cpolyline fill='none' stroke='%23b7c4db' stroke-width='2.3' stroke-linecap='round' stroke-linejoin='round' points='4,25 18,17 30,22 42,7 55,23 68,13'/%3E%3C/svg%3E");
}

.customer-bulk-payment-page .bulk-table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.customer-bulk-payment-page .bulk-select-all {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-height: 40px;
    padding: 9px 15px;
    border-radius: 14px;
    color: #4b5b74;
    background: #f8fbff;
    border: 1px solid #cadcf7;
    font-weight: 800;
    cursor: pointer;
}

.customer-bulk-payment-page .bulk-select-all input {
    margin: 0;
}

.customer-bulk-payment-page .bulk-table-status {
    color: #6f7f98;
    font-size: 13px;
    font-weight: 700;
}

.customer-bulk-payment-page .bulk-table-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.customer-bulk-payment-page .bulk-invoice-table {
    width: 100%;
    min-width: 980px;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
}

.customer-bulk-payment-page .bulk-invoice-table thead th {
    padding: 16px 10px;
    color: #55657e;
    background: #f8fbff;
    border: 0;
    border-bottom: 1px solid #dde7f4;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .045em;
    vertical-align: middle;
    white-space: normal;
    line-height: 1.2;
}

.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(1),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(1) { width: 6%; }
.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(2),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(2) { width: 9%; }
.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(3),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(3) { width: 13%; }
.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(4),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(4) { width: 9%; }
.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(5),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(5) { width: 10%; }
.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(6),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(6) { width: 10%; }
.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(7),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(7) { width: 10%; }
.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(8),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(8) { width: 8%; }
.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(9),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(9) { width: 11%; }
.customer-bulk-payment-page .bulk-invoice-table thead th:nth-child(10),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(10) { width: 14%; }

.customer-bulk-payment-page .bulk-invoice-table tbody td {
    padding: 13px 10px;
    border-top: 0;
    border-bottom: 1px solid #edf2f7;
    color: #344054;
    vertical-align: middle;
    font-size: 13px;
}

.customer-bulk-payment-page .bulk-invoice-table tbody tr:hover td {
    background: #f8fbff;
}

.customer-bulk-payment-page .bulk-invoice-table tbody tr.is-selected td {
    background: #eff6ff;
}

.customer-bulk-payment-page .bulk-invoice-table .form-control {
    min-width: 92px;
    width: 100%;
    height: 39px;
    border-radius: 10px;
    border: 1px solid #d8e1ee;
    text-align: right;
}

.customer-bulk-payment-page .bulk-invoice-table .bulk-head-wrap {
    display: inline-block;
    white-space: normal;
    line-height: 1.15;
}

.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(3),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(4),
.customer-bulk-payment-page .bulk-invoice-table tbody td:nth-child(10) {
    word-break: break-word;
}

.customer-bulk-payment-page .bulk-invoice-number {
    display: block;
    color: #172033;
    font-size: 13px;
}

.customer-bulk-payment-page .bulk-invoice-number + small {
    display: block;
    margin-top: 3px;
    color: #8590a3;
}

.customer-bulk-payment-page .bulk-outstanding-cell {
    color: #c2410c !important;
    font-weight: 800;
}

.customer-bulk-payment-page .bulk-row-total {
    color: #166534 !important;
    font-weight: 800;
}

.customer-bulk-payment-page .bulk-check-wrap {
    position: relative;
    display: inline-flex;
    width: 22px;
    height: 22px;
    margin: 0;
    cursor: pointer;
}

.customer-bulk-payment-page .bulk-check-wrap input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.customer-bulk-payment-page .bulk-check-wrap span {
    width: 22px;
    height: 22px;
    border-radius: 7px;
    border: 2px solid #b8c3d5;
    background: #fff;
    transition: all .15s ease;
}

.customer-bulk-payment-page .bulk-check-wrap input:checked + span {
    border-color: var(--bulk-primary);
    background: var(--bulk-primary);
    box-shadow: inset 0 0 0 4px #fff;
}

.customer-bulk-payment-page .bulk-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 246px;
    color: var(--bulk-muted);
    text-align: center;
}

.customer-bulk-payment-page .bulk-empty-illustration {
    position: relative;
    width: 82px;
    height: 82px;
    border-radius: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 6px;
    background: linear-gradient(180deg, #eef4ff 0%, #f9fbff 100%);
    color: #98afe5;
    font-size: 38px;
}

.customer-bulk-payment-page .bulk-empty-search {
    position: absolute;
    right: -6px;
    bottom: -4px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    color: var(--bulk-primary);
    border: 3px solid #e6efff;
    font-size: 15px;
}

.customer-bulk-payment-page .bulk-empty-state strong {
    color: var(--bulk-ink);
    font-size: 16px;
}

.customer-bulk-payment-page .bulk-floating-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 18px;
    padding: 10px 0 0;
}

.customer-bulk-payment-page .bulk-btn {
    min-height: 46px;
    padding: 11px 22px;
    border: 0;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-size: 15px;
    font-weight: 800;
    text-decoration: none !important;
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
}

.customer-bulk-payment-page .bulk-btn:hover {
    transform: translateY(-1px);
}

.customer-bulk-payment-page .bulk-btn-primary {
    color: #fff !important;
    background: linear-gradient(135deg, #2f67f6, #1b54db);
    box-shadow: 0 12px 24px rgba(47, 103, 246, .22);
}

.customer-bulk-payment-page .bulk-btn-secondary {
    color: #334155 !important;
    background: #fff;
    border: 1px solid #d8e1ee;
    box-shadow: 0 4px 10px rgba(15, 23, 42, .04);
}

.customer-bulk-payment-page .bulk-btn[disabled] {
    opacity: .58;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.customer-bulk-payment-page .bulk-loading {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--bulk-primary);
    font-weight: 700;
}

.customer-bulk-payment-page .bulk-spinner {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #cfe0ff;
    border-top-color: var(--bulk-primary);
    animation: bulk-spin .75s linear infinite;
}

@keyframes bulk-spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 1430px) {
    .customer-bulk-payment-page .bulk-form-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .customer-bulk-payment-page .bulk-field.is-full {
        grid-column: 1 / -1;
    }
}

@media (max-width: 1250px) {
    .customer-bulk-payment-page .bulk-form-grid,
    .customer-bulk-payment-page .bulk-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}


@media (max-width: 1280px) {
    .customer-bulk-payment-page .bulk-invoice-table {
        min-width: 920px;
    }

    .customer-bulk-payment-page .bulk-invoice-table thead th,
    .customer-bulk-payment-page .bulk-invoice-table tbody td {
        padding-left: 8px;
        padding-right: 8px;
        font-size: 12px;
    }

    .customer-bulk-payment-page .bulk-invoice-table .form-control {
        min-width: 82px;
        height: 37px;
        font-size: 12px;
    }
}

@media (max-width: 991px) {
    .customer-bulk-payment-page .bulk-page-head,
    .customer-bulk-payment-page .bulk-panel-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .customer-bulk-payment-page .bulk-page-head-side {
        align-items: flex-start;
    }

    .customer-bulk-payment-page .bulk-table-toolbar {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 767px) {
    .customer-bulk-payment-page .bulk-page-head {
        gap: 14px;
        margin-bottom: 16px;
    }

    .customer-bulk-payment-page .bulk-page-head-main {
        align-items: flex-start;
    }

    .customer-bulk-payment-page .bulk-page-head-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
    }

    .customer-bulk-payment-page .bulk-page-head h1 {
        font-size: 18px;
    }

    .customer-bulk-payment-page .bulk-form-grid,
    .customer-bulk-payment-page .bulk-summary-grid {
        grid-template-columns: 1fr;
    }

    .customer-bulk-payment-page .bulk-panel-header,
    .customer-bulk-payment-page .bulk-panel-body {
        padding-left: 16px;
        padding-right: 16px;
    }

    .customer-bulk-payment-page .bulk-summary-card {
        padding-right: 16px;
    }

    .customer-bulk-payment-page .bulk-summary-sparkline {
        display: none;
    }

    .customer-bulk-payment-page .bulk-floating-actions {
        align-items: stretch;
        flex-direction: column-reverse;
    }

    .customer-bulk-payment-page .bulk-btn {
        width: 100%;
    }
}



/* V4: Keep the approved compact POS layout on laptop/desktop displays,
   including browsers using 125%/150% display scaling. */
@media (min-width: 768px) {
    .customer-bulk-payment-page .bulk-form-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
        gap: 16px 18px !important;
    }

    .customer-bulk-payment-page .bulk-field.is-full {
        grid-column: 3 / -1 !important;
    }

    .customer-bulk-payment-page .bulk-summary-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }

    .customer-bulk-payment-page .bulk-page-head,
    .customer-bulk-payment-page .bulk-panel-header {
        flex-direction: row !important;
        align-items: center !important;
    }

    .customer-bulk-payment-page .bulk-page-head-side {
        align-items: flex-end !important;
    }
}

@media (max-width: 767px) {
    .customer-bulk-payment-page .bulk-form-grid,
    .customer-bulk-payment-page .bulk-summary-grid {
        grid-template-columns: 1fr !important;
    }

    .customer-bulk-payment-page .bulk-field.is-full {
        grid-column: 1 / -1 !important;
    }
}

</style>
@php
    $status = session('status');
    $selectedGroup = (string) old('payment_group_id', '');
    $selectedCustomer = (string) old('customer_id', '');
    $selectedAccount = (string) old('account_id', '');
@endphp

<section
    class="content customer-bulk-payment-page" data-bulk-ui-version="pos-dashboard-v3-20260728"
    data-customer-bulk-payment-page
    data-customer-url="{{ route('customers.bulk_payment.customer_data', ['customer' => '__CUSTOMER__']) }}"
    data-customer-summary-url="{{ route('customers.bulk_payment.customer_summary', ['customer' => '__CUSTOMER__']) }}"
    data-customer-invoices-url="{{ route('customers.bulk_payment.customer_invoices', ['customer' => '__CUSTOMER__']) }}"
    data-account-url="{{ route('customers.bulk_payment.accounts', ['group' => '__GROUP__']) }}"
    data-interest-enabled="{{ $interestEnabled ? '1' : '0' }}"
    data-old-account-id="{{ $selectedAccount }}"
>
    <div class="bulk-page-head">
        <div class="bulk-page-head-main">
            <span class="bulk-page-head-icon"><i class="fa fa-credit-card"></i></span>
            <div>
                <h1>Customer Bulk Payment</h1>
                <p>Apply payment across multiple outstanding invoices for a customer.</p>
            </div>
        </div>
        <div class="bulk-page-head-side">
            <div class="bulk-breadcrumbs">
                <span>Home</span>
                <i class="fa fa-angle-right"></i>
                <span>Payments</span>
                <i class="fa fa-angle-right"></i>
                <strong>Customer Bulk Payment</strong>
            </div>
            <div class="bulk-reference-pill">
                <i class="fa fa-hashtag"></i>
                <span>{{ old('payment_reference', $nextReference) }}</span>
            </div>
        </div>
    </div>

    @if(is_array($status) && !empty($status['msg']))
        <div class="bulk-alert {{ !empty($status['success']) ? 'bulk-alert-success' : 'bulk-alert-error' }}">
            <i class="fa {{ !empty($status['success']) ? 'fa-check-circle' : 'fa-exclamation-triangle' }}"></i>
            <div>
                {{ $status['msg'] }}
                @if(!empty($status['receipt_url']))
                    <a href="{{ $status['receipt_url'] }}" target="_blank" rel="noopener">View / Print Receipt</a>
                @endif
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="bulk-alert bulk-alert-error">
            <i class="fa fa-exclamation-triangle"></i>
            <div>
                <strong>Please correct the following:</strong>
                <ul class="bulk-error-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form id="customer_bulk_payment_form" action="{{ route('customers.bulk_payment.store') }}" method="POST" autocomplete="off">
        @csrf
        {{-- IS2264: one browser submission token = one Bulk Payment save. --}}
        <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <input type="hidden" name="payment_reference" value="{{ old('payment_reference', $nextReference) }}">

        <div class="bulk-panel bulk-panel-form">
            <div class="bulk-panel-header">
                <div class="bulk-panel-heading">
                    <span class="bulk-panel-heading-icon"><i class="fa fa-file-text-o"></i></span>
                    <div>
                        <h2 class="bulk-panel-title">Customer &amp; Payment Details</h2>
                        <p class="bulk-panel-subtitle">Choose the customer, receipt method and account before allocating invoices.</p>
                    </div>
                </div>
            </div>

            <div class="bulk-panel-body">
                <div class="bulk-form-grid">
                    <div class="bulk-field">
                        <label for="bulk_customer_id">Customer <span class="required">*</span></label>
                        <div class="bulk-input-shell has-icon has-select-icon">
                            <span class="bulk-input-icon"><i class="fa fa-user"></i></span>
                            <select id="bulk_customer_id" name="customer_id" class="form-control" required>
                                <option value="">Please select customer</option>
                                @foreach($customers as $id => $label)
                                    <option value="{{ $id }}" {{ (string) $id === $selectedCustomer ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="bulk-field">
                        <label for="bulk_total_due">Customer Total Due</label>
                        <div class="bulk-input-shell has-badge is-readonly-shell">
                            <input id="bulk_total_due" type="text" class="form-control bulk-readonly" value="" readonly>
                        </div>
                    </div>

                    <div class="bulk-field">
                        <label for="bulk_points">Customer Points</label>
                        <div class="bulk-input-shell has-badge is-readonly-shell">
                            <input id="bulk_points" name="points" type="text" class="form-control bulk-readonly" value="{{ old('points') }}" readonly>
                        </div>
                    </div>

                    <div class="bulk-field">
                        <label for="bulk_transaction_date">Transaction Date <span class="required">*</span></label>
                        <div class="bulk-input-shell has-date-icon">
                            <input id="bulk_transaction_date" name="transaction_date" type="date" class="form-control" value="{{ old('transaction_date', $today) }}" required>
                            <span class="bulk-trailing-icon"><i class="fa fa-calendar"></i></span>
                        </div>
                    </div>

                    <div class="bulk-field">
                        <label for="bulk_payment_group_id">Payment Method <span class="required">*</span></label>
                        <div class="bulk-input-shell has-select-icon">
                            <select id="bulk_payment_group_id" name="payment_group_id" class="form-control" required>
                                <option value="">Please select</option>
                                @foreach($paymentGroups as $groupId => $group)
                                    <option
                                        value="{{ $groupId }}"
                                        data-method="{{ $group['method'] }}"
                                        {{ (string) $groupId === $selectedGroup ? 'selected' : '' }}
                                    >{{ $group['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="bulk-field">
                        <label for="bulk_account_id">Payment Account <span class="required">*</span></label>
                        <div class="bulk-input-shell has-icon has-select-icon">
                            <span class="bulk-input-icon"><i class="fa fa-university"></i></span>
                            <select id="bulk_account_id" name="account_id" class="form-control" required>
                                <option value="">Select payment method first</option>
                            </select>
                        </div>
                    </div>

                    <div class="bulk-field">
                        <label for="bulk_payment_amount">Payment Amount <span class="required">*</span></label>
                        <div class="bulk-input-shell has-icon">
                            <span class="bulk-input-icon"><i class="fa fa-money"></i></span>
                            <input
                                id="bulk_payment_amount"
                                name="payment_amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                class="form-control"
                                value="{{ old('payment_amount') }}"
                                placeholder="0.00"
                                required
                            >
                        </div>
                    </div>

                    <div class="bulk-field {{ $interestEnabled ? '' : 'is-hidden' }}">
                        <label for="bulk_interest_mode">Add Customer Interest</label>
                        <div class="bulk-input-shell has-select-icon">
                            @if($interestEnabled)
                                <select id="bulk_interest_mode" name="interest_mode" class="form-control">
                                    <option value="no" {{ old('interest_mode', 'no') === 'no' ? 'selected' : '' }}>No</option>
                                    <option value="yes" {{ old('interest_mode') === 'yes' ? 'selected' : '' }}>Yes</option>
                                </select>
                            @else
                                <input id="bulk_interest_mode" type="hidden" name="interest_mode" value="no">
                            @endif
                        </div>
                    </div>

                    @if(!empty($shiftNumbers))
                        <div class="bulk-field">
                            <label for="bulk_shift_number">Daily Shift No</label>
                            <div class="bulk-input-shell has-select-icon">
                                <select id="bulk_shift_number" name="shift_number" class="form-control">
                                    <option value="">Please select</option>
                                    @foreach($shiftNumbers as $shift => $label)
                                        <option value="{{ $shift }}" {{ (string) old('shift_number') === (string) $shift ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="bulk-field is-full">
                        <label for="bulk_note">Note</label>
                        <div class="bulk-input-shell has-icon textarea-shell">
                            <span class="bulk-input-icon top-align"><i class="fa fa-sticky-note-o"></i></span>
                            <textarea id="bulk_note" name="note" class="form-control" placeholder="Optional payment note">{{ old('note') }}</textarea>
                        </div>
                    </div>

                    <div class="bulk-method-fields">
                        <div class="bulk-field is-hidden" data-method-field="card">
                            <label for="bulk_card_number">Card Number</label>
                            <div class="bulk-input-shell">
                                <input id="bulk_card_number" name="card_number" type="text" class="form-control" value="{{ old('card_number') }}" maxlength="50">
                            </div>
                        </div>

                        <div class="bulk-field is-hidden" data-method-field="card">
                            <label for="bulk_card_type">Card Name / Type</label>
                            <div class="bulk-input-shell">
                                <input id="bulk_card_type" name="card_type" type="text" class="form-control" value="{{ old('card_type') }}" maxlength="50">
                            </div>
                        </div>

                        <div class="bulk-field is-hidden" data-method-field="bank">
                            <label for="bulk_bank_name">Bank Name</label>
                            <div class="bulk-input-shell">
                                <input id="bulk_bank_name" name="bank_name" type="text" class="form-control" value="{{ old('bank_name') }}" maxlength="100">
                            </div>
                        </div>

                        <div class="bulk-field is-hidden" data-method-field="cheque">
                            <label for="bulk_cheque_number">Cheque Number</label>
                            <div class="bulk-input-shell">
                                <input id="bulk_cheque_number" name="cheque_number" type="text" class="form-control" value="{{ old('cheque_number') }}" maxlength="100">
                            </div>
                        </div>

                        <div class="bulk-field is-hidden" data-method-field="cheque">
                            <label for="bulk_cheque_date">Cheque Date</label>
                            <div class="bulk-input-shell has-date-icon">
                                <input id="bulk_cheque_date" name="cheque_date" type="date" class="form-control" value="{{ old('cheque_date', $today) }}">
                                <span class="bulk-trailing-icon"><i class="fa fa-calendar"></i></span>
                            </div>
                        </div>

                        <div class="bulk-field bulk-checkbox-field is-hidden" data-method-field="post-dated">
                            <label class="bulk-inline-check">
                                <input type="checkbox" name="post_dated_cheque" value="1" {{ old('post_dated_cheque') ? 'checked' : '' }}>
                                Post-dated cheque
                            </label>
                        </div>

                        <div class="bulk-field bulk-checkbox-field is-hidden" data-method-field="post-dated">
                            <label class="bulk-inline-check">
                                <input type="checkbox" name="update_post_dated_cheque" value="1" {{ old('update_post_dated_cheque') ? 'checked' : '' }}>
                                Update post-dated cheque register
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bulk-summary-grid">
            <div class="bulk-summary-card total-due">
                <div class="bulk-summary-icon"><i class="fa fa-money"></i></div>
                <div class="bulk-summary-content">
                    <span class="bulk-summary-label">Customer Total Due</span>
                    <strong id="bulk_total_due_value" class="bulk-summary-value">0.00</strong>
                </div>
                <span class="bulk-summary-sparkline" aria-hidden="true"></span>
            </div>
            <div class="bulk-summary-card payment">
                <div class="bulk-summary-icon"><i class="fa fa-credit-card"></i></div>
                <div class="bulk-summary-content">
                    <span class="bulk-summary-label">Payment Amount</span>
                    <strong id="bulk_payment_summary_value" class="bulk-summary-value">0.00</strong>
                </div>
                <span class="bulk-summary-sparkline" aria-hidden="true"></span>
            </div>
            <div class="bulk-summary-card allocated">
                <div class="bulk-summary-icon"><i class="fa fa-check-circle"></i></div>
                <div class="bulk-summary-content">
                    <span class="bulk-summary-label">Allocated Amount</span>
                    <strong id="bulk_allocated_summary_value" class="bulk-summary-value">0.00</strong>
                </div>
                <span class="bulk-summary-sparkline" aria-hidden="true"></span>
            </div>
            <div class="bulk-summary-card unallocated">
                <div class="bulk-summary-icon"><i class="fa fa-database"></i></div>
                <div class="bulk-summary-content">
                    <span class="bulk-summary-label">Unallocated / Advance</span>
                    <strong id="bulk_unallocated_summary_value" class="bulk-summary-value">0.00</strong>
                </div>
                <span class="bulk-summary-sparkline" aria-hidden="true"></span>
            </div>
        </div>

        <div class="bulk-panel bulk-panel-table">
            <div class="bulk-panel-header">
                <div class="bulk-panel-heading">
                    <span class="bulk-panel-heading-icon"><i class="fa fa-file-o"></i></span>
                    <div>
                        <h2 class="bulk-panel-title">Outstanding Invoice Allocation</h2>
                        <p class="bulk-panel-subtitle">Select one or many invoices and enter the amount to apply against each bill.</p>
                    </div>
                </div>
                <div class="bulk-table-toolbar">
                    <label class="bulk-select-all">
                        <input id="bulk_select_all" type="checkbox">
                        <span>Pay All / Select All</span>
                    </label>
                    <span id="bulk_table_status" class="bulk-table-status">0 outstanding invoices • 0 selected</span>
                </div>
            </div>

            <div class="bulk-table-wrap">
                <table class="table bulk-invoice-table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th><span class="bulk-head-wrap">Reference /<br>Order No</span></th>
                            <th class="text-right"><span class="bulk-head-wrap">Invoice<br>Amount</span></th>
                            <th class="text-right"><span class="bulk-head-wrap">Already<br>Paid</span></th>
                            <th class="text-right">Outstanding</th>
                            <th class="bulk-interest-column {{ $interestEnabled ? '' : 'is-hidden' }}">Interest</th>
                            <th><span class="bulk-head-wrap">Payment<br>Amount</span></th>
                            <th class="text-right">Row Total</th>
                        </tr>
                    </thead>
                    <tbody id="bulk_invoice_rows">
                        <tr class="bulk-empty-row">
                            <td colspan="10">
                                <div class="bulk-empty-state">
                                    <div class="bulk-empty-illustration">
                                        <i class="fa fa-files-o"></i>
                                        <span class="bulk-empty-search"><i class="fa fa-search"></i></span>
                                    </div>
                                    <strong>Select a customer</strong>
                                    <span>Outstanding invoices will load instantly here.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bulk-floating-actions">
            <a href="{{ route('customers.index') }}" class="bulk-btn bulk-btn-secondary">
                <i class="fa fa-times"></i> Cancel
            </a>
            <button id="bulk_submit_button" type="submit" class="bulk-btn bulk-btn-primary" disabled>
                <i class="fa fa-save"></i> Save Bulk Payment
            </button>
        </div>
    </form>

    <script id="bulk_payment_accounts_map" type="application/json">{!! json_encode($paymentAccountsByGroup ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script src="{{ route('customers.bulk_payment.runtime') }}?v=20260728-v5-performance"></script>
</section>
@endsection
