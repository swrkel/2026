@once
<style id="petrodirect-global-tab-standard-v1">
/*
|--------------------------------------------------------------------------
| Petro Direct - ERP Global Adaptive Compact Tab Standard
|--------------------------------------------------------------------------
| Visual-only rules. Bootstrap/custom tab switching and all page logic remain
| unchanged. Inactive tabs cycle through the approved seven ERP colours;
| active tabs use a white background, black text and blue active indicator.
*/
:root {
    --petrodirect-tab-height: 42px;
    --petrodirect-tab-font-size: 14px;
    --petrodirect-tab-icon-size: 15.5px;
    --petrodirect-tab-gap: 8px;
    --petrodirect-tab-padding-x: 16px;
    --petrodirect-tab-radius: 7px;
    --petrodirect-tab-active-blue: #2563eb;
}

.settlement_tabs > .nav-tabs,
.payment_tabs > .nav-tabs,
.petrodirect-pumper-management-tabs {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: flex-end !important;
    gap: var(--petrodirect-tab-gap) !important;
    width: 100% !important;
    margin: 0 0 12px 0 !important;
    padding: 0 0 8px 0 !important;
    border: 0 !important;
    border-bottom: 1px solid #dbe3ed !important;
    background: transparent !important;
    overflow: visible !important;
}

.settlement_tabs > .nav-tabs > li,
.payment_tabs > .nav-tabs > li,
.petrodirect-pumper-management-tabs > li {
    float: none !important;
    display: block !important;
    flex: 0 1 auto !important;
    width: auto !important;
    min-width: 0 !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
}

.settlement_tabs > .nav-tabs > li > a,
.settlement_tabs > .nav-tabs > li > button,
.payment_tabs > .nav-tabs > li > a,
.payment_tabs > .nav-tabs > li > button,
.petrodirect-pumper-management-tabs > li > a,
.petrodirect-pumper-management-tabs > li > button {
    box-sizing: border-box !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 5px !important;
    width: auto !important;
    min-width: 0 !important;
    max-width: 100% !important;
    height: var(--petrodirect-tab-height) !important;
    min-height: var(--petrodirect-tab-height) !important;
    margin: 0 !important;
    padding: 0 var(--petrodirect-tab-padding-x) !important;
    border: 1px solid var(--petrodirect-tab-colour, #2563eb) !important;
    border-radius: var(--petrodirect-tab-radius) var(--petrodirect-tab-radius) 0 0 !important;
    background: var(--petrodirect-tab-colour, #2563eb) !important;
    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.10) !important;
    color: #ffffff !important;
    font-family: inherit !important;
    font-size: var(--petrodirect-tab-font-size) !important;
    font-style: normal !important;
    font-weight: 700 !important;
    line-height: 1 !important;
    text-align: center !important;
    text-decoration: none !important;
    text-transform: none !important;
    white-space: nowrap !important;
    cursor: pointer !important;
    outline: none !important;
    transition: filter .16s ease, box-shadow .16s ease, transform .16s ease !important;
    vertical-align: middle !important;
}

.settlement_tabs > .nav-tabs > li:nth-child(7n + 1) > a,
.settlement_tabs > .nav-tabs > li:nth-child(7n + 1) > button,
.payment_tabs > .nav-tabs > li:nth-child(7n + 1) > a,
.payment_tabs > .nav-tabs > li:nth-child(7n + 1) > button,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 1) > a,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 1) > button { --petrodirect-tab-colour: #2563eb; }

.settlement_tabs > .nav-tabs > li:nth-child(7n + 2) > a,
.settlement_tabs > .nav-tabs > li:nth-child(7n + 2) > button,
.payment_tabs > .nav-tabs > li:nth-child(7n + 2) > a,
.payment_tabs > .nav-tabs > li:nth-child(7n + 2) > button,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 2) > a,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 2) > button { --petrodirect-tab-colour: #7c3aed; }

.settlement_tabs > .nav-tabs > li:nth-child(7n + 3) > a,
.settlement_tabs > .nav-tabs > li:nth-child(7n + 3) > button,
.payment_tabs > .nav-tabs > li:nth-child(7n + 3) > a,
.payment_tabs > .nav-tabs > li:nth-child(7n + 3) > button,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 3) > a,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 3) > button { --petrodirect-tab-colour: #0f766e; }

.settlement_tabs > .nav-tabs > li:nth-child(7n + 4) > a,
.settlement_tabs > .nav-tabs > li:nth-child(7n + 4) > button,
.payment_tabs > .nav-tabs > li:nth-child(7n + 4) > a,
.payment_tabs > .nav-tabs > li:nth-child(7n + 4) > button,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 4) > a,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 4) > button { --petrodirect-tab-colour: #15803d; }

.settlement_tabs > .nav-tabs > li:nth-child(7n + 5) > a,
.settlement_tabs > .nav-tabs > li:nth-child(7n + 5) > button,
.payment_tabs > .nav-tabs > li:nth-child(7n + 5) > a,
.payment_tabs > .nav-tabs > li:nth-child(7n + 5) > button,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 5) > a,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 5) > button { --petrodirect-tab-colour: #d97706; }

.settlement_tabs > .nav-tabs > li:nth-child(7n + 6) > a,
.settlement_tabs > .nav-tabs > li:nth-child(7n + 6) > button,
.payment_tabs > .nav-tabs > li:nth-child(7n + 6) > a,
.payment_tabs > .nav-tabs > li:nth-child(7n + 6) > button,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 6) > a,
.petrodirect-pumper-management-tabs > li:nth-child(7n + 6) > button { --petrodirect-tab-colour: #e11d48; }

.settlement_tabs > .nav-tabs > li:nth-child(7n) > a,
.settlement_tabs > .nav-tabs > li:nth-child(7n) > button,
.payment_tabs > .nav-tabs > li:nth-child(7n) > a,
.payment_tabs > .nav-tabs > li:nth-child(7n) > button,
.petrodirect-pumper-management-tabs > li:nth-child(7n) > a,
.petrodirect-pumper-management-tabs > li:nth-child(7n) > button { --petrodirect-tab-colour: #4f46e5; }

.settlement_tabs > .nav-tabs > li:not(.active) > a:hover,
.settlement_tabs > .nav-tabs > li:not(.active) > a:focus,
.settlement_tabs > .nav-tabs > li:not(.active) > button:hover,
.settlement_tabs > .nav-tabs > li:not(.active) > button:focus,
.payment_tabs > .nav-tabs > li:not(.active) > a:hover,
.payment_tabs > .nav-tabs > li:not(.active) > a:focus,
.payment_tabs > .nav-tabs > li:not(.active) > button:hover,
.payment_tabs > .nav-tabs > li:not(.active) > button:focus,
.petrodirect-pumper-management-tabs > li:not(.active) > a:hover,
.petrodirect-pumper-management-tabs > li:not(.active) > a:focus,
.petrodirect-pumper-management-tabs > li:not(.active) > button:hover,
.petrodirect-pumper-management-tabs > li:not(.active) > button:focus {
    background: var(--petrodirect-tab-colour, #2563eb) !important;
    border-color: var(--petrodirect-tab-colour, #2563eb) !important;
    color: #ffffff !important;
    filter: brightness(1.10) !important;
    box-shadow: 0 5px 12px rgba(15, 23, 42, 0.17) !important;
    transform: translateY(-1px) !important;
}

.settlement_tabs > .nav-tabs > li.active > a,
.settlement_tabs > .nav-tabs > li.active > a:hover,
.settlement_tabs > .nav-tabs > li.active > a:focus,
.settlement_tabs > .nav-tabs > li.active > button,
.settlement_tabs > .nav-tabs > li.active > button:hover,
.settlement_tabs > .nav-tabs > li.active > button:focus,
.settlement_tabs > .nav-tabs > li > a.active,
.settlement_tabs > .nav-tabs > li > button.active,
.payment_tabs > .nav-tabs > li.active > a,
.payment_tabs > .nav-tabs > li.active > a:hover,
.payment_tabs > .nav-tabs > li.active > a:focus,
.payment_tabs > .nav-tabs > li.active > button,
.payment_tabs > .nav-tabs > li.active > button:hover,
.payment_tabs > .nav-tabs > li.active > button:focus,
.payment_tabs > .nav-tabs > li > a.active,
.payment_tabs > .nav-tabs > li > button.active,
.petrodirect-pumper-management-tabs > li.active > a,
.petrodirect-pumper-management-tabs > li.active > a:hover,
.petrodirect-pumper-management-tabs > li.active > a:focus,
.petrodirect-pumper-management-tabs > li.active > button,
.petrodirect-pumper-management-tabs > li.active > button:hover,
.petrodirect-pumper-management-tabs > li.active > button:focus,
.petrodirect-pumper-management-tabs > li > a.active,
.petrodirect-pumper-management-tabs > li > button.active {
    background: #ffffff !important;
    border: 1px solid var(--petrodirect-tab-active-blue) !important;
    border-bottom: 3px solid var(--petrodirect-tab-active-blue) !important;
    color: #111827 !important;
    filter: none !important;
    box-shadow: 0 5px 13px rgba(37, 99, 235, 0.20) !important;
    transform: none !important;
}

.settlement_tabs > .nav-tabs > li > a *,
.settlement_tabs > .nav-tabs > li > button *,
.payment_tabs > .nav-tabs > li > a *,
.payment_tabs > .nav-tabs > li > button *,
.petrodirect-pumper-management-tabs > li > a *,
.petrodirect-pumper-management-tabs > li > button * {
    color: inherit !important;
    font-size: inherit !important;
    line-height: 1 !important;
}

.settlement_tabs > .nav-tabs > li > a i,
.settlement_tabs > .nav-tabs > li > button i,
.payment_tabs > .nav-tabs > li > a i,
.payment_tabs > .nav-tabs > li > button i,
.petrodirect-pumper-management-tabs > li > a i,
.petrodirect-pumper-management-tabs > li > button i {
    flex: 0 0 auto !important;
    margin: 0 !important;
    font-size: var(--petrodirect-tab-icon-size) !important;
}

.settlement_tabs > .nav-tabs > li > a strong,
.settlement_tabs > .nav-tabs > li > button strong,
.payment_tabs > .nav-tabs > li > a strong,
.payment_tabs > .nav-tabs > li > button strong,
.petrodirect-pumper-management-tabs > li > a strong,
.petrodirect-pumper-management-tabs > li > button strong {
    font-weight: 700 !important;
}

.settlement_tabs > .nav-tabs > li.active > a span,
.settlement_tabs > .nav-tabs > li.active > a strong,
.settlement_tabs > .nav-tabs > li.active > a i,
.settlement_tabs > .nav-tabs > li.active > button span,
.settlement_tabs > .nav-tabs > li.active > button strong,
.settlement_tabs > .nav-tabs > li.active > button i,
.settlement_tabs > .nav-tabs > li > a.active span,
.settlement_tabs > .nav-tabs > li > a.active strong,
.settlement_tabs > .nav-tabs > li > a.active i,
.settlement_tabs > .nav-tabs > li > button.active span,
.settlement_tabs > .nav-tabs > li > button.active strong,
.settlement_tabs > .nav-tabs > li > button.active i,
.payment_tabs > .nav-tabs > li.active > a span,
.payment_tabs > .nav-tabs > li.active > a strong,
.payment_tabs > .nav-tabs > li.active > a i,
.payment_tabs > .nav-tabs > li.active > button span,
.payment_tabs > .nav-tabs > li.active > button strong,
.payment_tabs > .nav-tabs > li.active > button i,
.payment_tabs > .nav-tabs > li > a.active span,
.payment_tabs > .nav-tabs > li > a.active strong,
.payment_tabs > .nav-tabs > li > a.active i,
.payment_tabs > .nav-tabs > li > button.active span,
.payment_tabs > .nav-tabs > li > button.active strong,
.payment_tabs > .nav-tabs > li > button.active i,
.petrodirect-pumper-management-tabs > li.active > a span,
.petrodirect-pumper-management-tabs > li.active > a strong,
.petrodirect-pumper-management-tabs > li.active > a i,
.petrodirect-pumper-management-tabs > li.active > button span,
.petrodirect-pumper-management-tabs > li.active > button strong,
.petrodirect-pumper-management-tabs > li.active > button i,
.petrodirect-pumper-management-tabs > li > a.active span,
.petrodirect-pumper-management-tabs > li > a.active strong,
.petrodirect-pumper-management-tabs > li > a.active i,
.petrodirect-pumper-management-tabs > li > button.active span,
.petrodirect-pumper-management-tabs > li > button.active strong,
.petrodirect-pumper-management-tabs > li > button.active i {
    color: #111827 !important;
}

@media (max-width: 767px) {
    .settlement_tabs > .nav-tabs > li,
    .payment_tabs > .nav-tabs > li,
    .petrodirect-pumper-management-tabs > li {
        flex: 1 1 calc(50% - var(--petrodirect-tab-gap)) !important;
    }

    .settlement_tabs > .nav-tabs > li > a,
    .settlement_tabs > .nav-tabs > li > button,
    .payment_tabs > .nav-tabs > li > a,
    .payment_tabs > .nav-tabs > li > button,
    .petrodirect-pumper-management-tabs > li > a,
    .petrodirect-pumper-management-tabs > li > button {
        width: 100% !important;
        height: auto !important;
        min-height: var(--petrodirect-tab-height) !important;
        padding: 10px 12px !important;
        white-space: normal !important;
    }
}

@media (max-width: 479px) {
    .settlement_tabs > .nav-tabs > li,
    .payment_tabs > .nav-tabs > li,
    .petrodirect-pumper-management-tabs > li {
        flex-basis: 100% !important;
    }
}
</style>
@endonce
