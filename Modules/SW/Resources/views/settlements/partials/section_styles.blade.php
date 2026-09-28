{{-- Collapsible section styling, shared by every settlement section — 8043. --}}
<style>
.sw-section {
    background: #fff;
    border: 1px solid #e4e9f1;
    border-radius: 8px;
    margin-bottom: 14px;
    overflow: visible;
}

/* The whole strip is the control, not just the caret - a heading that looks
   clickable and is not is worse than one that does not look clickable. */
.sw-section-head {
    padding: 12px 16px;
    background: #f7f9fc;
    border-bottom: 1px solid #e4e9f1;
    border-left: 3px solid #3c8dbc;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: .4px;
    user-select: none;
}

.sw-section-head:hover { background: #eef3fa; }

.sw-section-head .sw-caret {
    margin-right: 8px;
    transition: transform .15s ease;
    font-size: 12px;
}

.sw-section-head.collapsed .sw-caret { transform: rotate(-90deg); }

.sw-section-total {
    float: right;
    font-weight: 700;
    text-transform: none;
    letter-spacing: 0;
}

.sw-section-body { padding: 16px; }

.sw-lines-table { font-size: 13px; margin-bottom: 0; }
.sw-lines-table thead th {
    font-size: 12px;
    white-space: normal;
    vertical-align: middle;
    background: #f7f9fc;
}
.sw-lines-table td { vertical-align: middle; }

/* ---- payments ---- */

.sw-pay-summary {
    padding: 14px 0;
    border-bottom: 1px solid #eef1f6;
    margin-bottom: 12px;
}
.sw-pay-label { font-size: 12px; color: #7b8698; text-transform: uppercase; letter-spacing: .4px; }
.sw-pay-value { font-size: 18px; font-weight: 600; margin-top: 2px; }

.sw-pay-totals {
    padding: 10px 0 14px;
    font-size: 15px;
    border-bottom: 1px solid #eef1f6;
    margin-bottom: 12px;
}

.sw-pay-types {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: stretch;
}

/* S732: payment choices must look like ACTIVE controls, not disabled pills.
   Keep every inactive button at full opacity and give the active one the ERP
   standard white background / dark text treatment. */
.sw-pay-btn {
    border: 1px solid rgba(0,0,0,.16);
    border-radius: 5px;
    padding: 10px 15px;
    min-height: 40px;
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    line-height: 1.2;
    opacity: 1 !important;
    cursor: pointer;
    pointer-events: auto;
    box-shadow: 0 1px 2px rgba(0,0,0,.08);
    transition: transform .08s ease, box-shadow .12s ease, border-color .12s ease;
}

.sw-pay-btn:hover,
.sw-pay-btn:focus {
    opacity: 1 !important;
    box-shadow: 0 2px 7px rgba(0,0,0,.16);
    transform: translateY(-1px);
    outline: 0;
}

.sw-pay-btn.is-active {
    background: #fff !important;
    color: #111 !important;
    border: 2px solid #34495e !important;
    box-shadow: 0 2px 9px rgba(0,0,0,.20);
    transform: none;
}

.sw-pay-btn.disabled,
.sw-pay-btn[disabled] {
    opacity: 1 !important;
    filter: none !important;
    cursor: pointer !important;
    pointer-events: auto !important;
}

/* All eleven SW settlement payment choices remain clear/selectable.  Global
   Bootstrap/AdminLTE disabled-tab rules must not blur or mute this row. */
#sw_sec_payments .sw-pay-btn {
    cursor: pointer !important;
    pointer-events: auto !important;
    opacity: 1 !important;
    filter: none !important;
}

.sw-pt-cash     { background: #2f75b5; color: #fff; }
.sw-pt-cash.is-active { background: #fff; color: #111; }
.sw-pt-deposit  { background: #8e44ad; }
.sw-pt-card     { background: #2e86c1; }
.sw-pt-cheque   { background: #1e7e34; }
.sw-pt-expense  { background: #c77700; }
.sw-pt-shortage { background: #4f81bd; color: #fff; }
.sw-pt-excess   { background: #1e7e34; }
.sw-pt-credit   { background: #2874a6; }
.sw-pt-loan     { background: #e74c3c; }
.sw-pt-drawing  { background: #8e44ad; }
.sw-pt-loanout  { background: #f39c12; }

.sw-pay-locked {
    display: inline-block;
    min-width: 28px;
    padding: 5px 7px;
    font-size: 12px;
}
#sw_cash_source_notice {
    border-radius: 5px;
    padding: 9px 12px;
    font-size: 12px;
}

#sw_balance.is-negative { color: #b94a48; font-weight: 700; }
#sw_balance.is-positive { color: #3c763d; font-weight: 700; }

/*
 * S752/S753: Save Settlement belongs in the Payments totals row requested by
 * the operator. It must remain at that exact place and must not float/stick as
 * the page scrolls. These rules live in this directly included style partial
 * because the host application does not consistently render module stacks.
 */
.sw-settlement-save-bar {
    position: static;
    margin: 0 0 12px;
    padding: 10px 0;
    border: 1px solid #d8e2ec;
    border-radius: 6px;
    background: #fff;
    box-shadow: none;
}
.sw-settlement-save-bar.is-ready {
    border-color: #7cc7a0;
    box-shadow: none;
}
.sw-settlement-save-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 6px;
}
.sw-settlement-save-actions #sw_st_save_btn {
    display: none !important;
    visibility: hidden !important;
    min-width: 145px;
    font-weight: 700;
    opacity: 1 !important;
}
.sw-settlement-save-bar.is-balanced #sw_st_save_btn {
    display: inline-block !important;
    visibility: visible !important;
}
.sw-settlement-save-actions #sw_st_save_btn:disabled {
    cursor: not-allowed;
    opacity: .55 !important;
}
.sw-settlement-save-hint {
    flex: 1 0 100%;
    margin-top: 1px;
    color: #6b7280;
    font-size: 11px;
    line-height: 1.2;
    text-align: right;
}
.sw-settlement-save-bar.is-ready .sw-settlement-save-hint { color: #2f855a; }
@media (max-width: 991px) {
    .sw-settlement-save-bar > div { margin-bottom: 7px; }
    .sw-settlement-save-actions {
        justify-content: flex-start;
        margin-bottom: 0 !important;
    }
    .sw-settlement-save-hint { text-align: left; }
}
@media print {
    .sw-settlement-save-bar {
        position: static;
        box-shadow: none;
    }
    .sw-settlement-save-actions { display: none !important; }
}

/* IS2201: wide result tables must be completely usable inside the screen. */
.sw-full-table-wrap {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.sw-fit-table {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    table-layout: fixed !important;
}
.sw-fit-table th,
.sw-fit-table td {
    padding: 5px 4px !important;
    font-size: 11px !important;
    line-height: 1.2;
    white-space: normal !important;
    overflow-wrap: normal;
    word-break: normal;
    vertical-align: middle;
}
.sw-fit-table thead th {
    font-size: 10px !important;
    line-height: 1.15 !important;
    font-weight: 700;
    text-align: center;
    padding: 6px 3px !important;
    border-right: 1px solid #dfe5ec !important;
}
.sw-fit-table thead th span {
    display: inline-block;
    max-width: 100%;
    white-space: normal;
}
.sw-fit-table .btn-xs { padding: 2px 5px; }

/* IS2204: the Meter Sales total is one compact horizontal row. */
.sw-meter-lines-table tfoot td {
    padding: 3px 5px !important;
    line-height: 1.1 !important;
    height: auto !important;
    white-space: nowrap !important;
}
.sw-meter-total-row { height: auto !important; }
.sw-meter-product-totals {
    text-align: left;
    font-size: 10px !important;
    white-space: normal !important;
}
.sw-meter-product-total { white-space: nowrap; }
.sw-meter-total-sep { color: #9aa4b2; }
.sw-meter-total-label,
.sw-meter-total-amount {
    white-space: nowrap !important;
    vertical-align: middle !important;
}
.sw-meter-total-amount { font-size: 12px !important; }

/* Meter Sales: 14 columns, total width = 100%. */
.sw-meter-lines-table th:nth-child(1),  .sw-meter-lines-table td:nth-child(1)  { width: 5%; }
.sw-meter-lines-table th:nth-child(2),  .sw-meter-lines-table td:nth-child(2)  { width: 9%; }
.sw-meter-lines-table th:nth-child(3),  .sw-meter-lines-table td:nth-child(3)  { width: 6%; }
.sw-meter-lines-table th:nth-child(4),  .sw-meter-lines-table td:nth-child(4)  { width: 8%; }
.sw-meter-lines-table th:nth-child(5),  .sw-meter-lines-table td:nth-child(5)  { width: 8%; }
.sw-meter-lines-table th:nth-child(6),  .sw-meter-lines-table td:nth-child(6)  { width: 7%; }
.sw-meter-lines-table th:nth-child(7),  .sw-meter-lines-table td:nth-child(7)  { width: 7%; }
.sw-meter-lines-table th:nth-child(8),  .sw-meter-lines-table td:nth-child(8)  { width: 8%; }
.sw-meter-lines-table th:nth-child(9),  .sw-meter-lines-table td:nth-child(9)  { width: 8%; }
.sw-meter-lines-table th:nth-child(10), .sw-meter-lines-table td:nth-child(10) { width: 7%; }
.sw-meter-lines-table th:nth-child(11), .sw-meter-lines-table td:nth-child(11) { width: 7%; }
.sw-meter-lines-table th:nth-child(12), .sw-meter-lines-table td:nth-child(12) { width: 8%; }
.sw-meter-lines-table th:nth-child(13), .sw-meter-lines-table td:nth-child(13) { width: 8%; }
.sw-meter-lines-table th:nth-child(14), .sw-meter-lines-table td:nth-child(14) { width: 4%; }

/* Credit Sales: 12 columns, total width = 100%. */
.sw-credit-lines-table th:nth-child(1),  .sw-credit-lines-table td:nth-child(1)  { width: 10%; }
.sw-credit-lines-table th:nth-child(2),  .sw-credit-lines-table td:nth-child(2)  { width: 7%; }
.sw-credit-lines-table th:nth-child(3),  .sw-credit-lines-table td:nth-child(3)  { width: 8%; }
.sw-credit-lines-table th:nth-child(4),  .sw-credit-lines-table td:nth-child(4)  { width: 8%; }
.sw-credit-lines-table th:nth-child(5),  .sw-credit-lines-table td:nth-child(5)  { width: 12%; }
.sw-credit-lines-table th:nth-child(6),  .sw-credit-lines-table td:nth-child(6)  { width: 6%; }
.sw-credit-lines-table th:nth-child(7),  .sw-credit-lines-table td:nth-child(7)  { width: 8%; }
.sw-credit-lines-table th:nth-child(8),  .sw-credit-lines-table td:nth-child(8)  { width: 8%; }
.sw-credit-lines-table th:nth-child(9),  .sw-credit-lines-table td:nth-child(9)  { width: 9%; }
.sw-credit-lines-table th:nth-child(10), .sw-credit-lines-table td:nth-child(10) { width: 9%; }
.sw-credit-lines-table th:nth-child(11), .sw-credit-lines-table td:nth-child(11) { width: 10%; }
.sw-credit-lines-table th:nth-child(12), .sw-credit-lines-table td:nth-child(12) { width: 5%; }

/* IS2240: keep the compact Credit Sales header readable on the full-width table.
   Multi-word headings are deliberately split over two lines instead of being
   compressed into one continuous strip. */
.sw-credit-lines-table thead th {
    height: 38px;
    line-height: 1.05 !important;
    white-space: normal !important;
}
.sw-credit-lines-table .sw-credit-th {
    display: inline-block;
    width: 100%;
    line-height: 1.05;
    white-space: normal !important;
    text-align: center;
}

/* Pump No results: type/search plus vertical slider. */
#select2-sw_ms_pump-results {
    max-height: 300px !important;
    overflow-y: auto !important;
}

</style>
