<style>
/* Petro PD-New is rendered inside the application's standard layouts.app shell. */
.pdn-system-page,
.pdn-system-page * {
    box-sizing: border-box;
}

.pdn-system-page {
    --pdn-primary: #2d6cdf;
    --pdn-primary-dark: #1e54b7;
    --pdn-success: #218838;
    --pdn-warning: #f3a51b;
    --pdn-danger: #dc3545;
    --pdn-purple: #8e2495;
    --pdn-cyan: #1685a9;
    --pdn-text: #253247;
    --pdn-muted: #667085;
    --pdn-border: #dce5f0;
    width: 100%;
    color: var(--pdn-text);
    font-family: inherit !important;
    font-size: inherit;
}

.pdn-system-page-title {
    margin: 0;
}
.pdn-system-page-title .breadcrumbs {
    margin: 15px 0 0;
}
.pdn-system-page-title .breadcrumbs a,
.pdn-system-page-title .breadcrumbs span {
    font-family: inherit !important;
}
.pdn-system-page .pdn-system-alert {
    margin-bottom: 15px;
}
.pdn-page-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin: 0 0 15px;
}
.pdn-page-head > div:first-child { min-width: 0; }
.pdn-page-head h2,
.pdn-page-head h3 {
    margin: 0 0 5px;
    color: #23324a;
    font-family: inherit !important;
    font-weight: 700;
}
.pdn-page-head h2 { font-size: 23px; }
.pdn-page-head h3 { font-size: 18px; }
.pdn-page-head p { margin: 0; color: var(--pdn-muted); font-size: 13px; }
.pdn-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 7px; }
.pdn-grid { display: grid; gap: 13px; }
.pdn-grid.cards { grid-template-columns: repeat(4, minmax(0,1fr)); }
.pdn-grid.two { grid-template-columns: repeat(2, minmax(0,1fr)); }
.pdn-grid.three { grid-template-columns: repeat(3, minmax(0,1fr)); }
.pdn-card {
    min-width: 0;
    padding: 15px;
    background: #fff;
    border: 1px solid var(--pdn-border);
    border-radius: 12px;
    box-shadow: 0 5px 16px rgba(31, 50, 81, .07);
}
.pdn-card + .pdn-card { margin-top: 14px; }
.pdn-card h3 { margin: 0 0 12px; color: #24344d; font-size: 16px; font-weight: 700; }
.pdn-kpi { position: relative; min-height: 108px; overflow: hidden; border-top: 4px solid var(--pdn-primary); }
.pdn-kpi:nth-child(6n+2){border-top-color:#218838}.pdn-kpi:nth-child(6n+3){border-top-color:#8e2495}
.pdn-kpi:nth-child(6n+4){border-top-color:#f3a51b}.pdn-kpi:nth-child(6n+5){border-top-color:#dc3545}.pdn-kpi:nth-child(6n+6){border-top-color:#1685a9}
.pdn-kpi::after { content:""; position:absolute; right:-22px; bottom:-30px; width:82px; height:82px; border-radius:50%; background:rgba(45,108,223,.08); }
.pdn-kpi-label { margin-bottom: 10px; color: var(--pdn-muted); font-size: 12px; font-weight: 700; }
.pdn-kpi-value { color:#20324d; font-size: 27px; font-weight: 800; font-variant-numeric: tabular-nums; }
.pdn-kpi-note { margin-top: 5px; color: var(--pdn-muted); font-size: 11px; }
.pdn-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px;
    margin: 0 0 14px;
    padding: 12px;
    background: #fff;
    border: 1px solid var(--pdn-border);
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(31,50,81,.05);
}
.pdn-field { display: grid; gap: 5px; min-width: 140px; }
.pdn-field.grow { flex: 1 1 220px; }
.pdn-field label { margin: 0; color:#526278; font-size: 12px; font-weight: 700; }
.pdn-input,
.pdn-select,
.pdn-textarea {
    width: 100%;
    min-height: 38px;
    padding: 7px 10px;
    color: var(--pdn-text);
    background: #fff;
    border: 1px solid #c8d3e0;
    border-radius: 8px;
    font-family: inherit !important;
    font-size: inherit;
}
.pdn-select[multiple] { min-height: 90px; }
.pdn-textarea { min-height: 88px; resize: vertical; }
.pdn-input:focus,
.pdn-select:focus,
.pdn-textarea:focus { border-color: var(--pdn-primary); outline: 2px solid rgba(45,108,223,.12); }
.pdn-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 37px;
    padding: 7px 12px;
    border: 1px solid transparent;
    border-radius: 8px;
    color: #fff !important;
    font-family: inherit !important;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.15;
    text-decoration: none !important;
    white-space: nowrap;
    cursor: pointer;
}
.pdn-btn.primary{background:var(--pdn-primary)}.pdn-btn.success{background:var(--pdn-success)}.pdn-btn.warning{background:var(--pdn-warning)}
.pdn-btn.danger{background:var(--pdn-danger)}.pdn-btn.purple{background:var(--pdn-purple)}.pdn-btn.light{background:#fff;color:#26364f!important;border-color:#c9d3df}
.pdn-btn:hover { filter: brightness(1.06); }
.pdn-btn.small { min-height: 29px; padding: 5px 8px; font-size: 11px; }
.pdn-btn[disabled] { opacity:.55; cursor:not-allowed; }
.pdn-table-wrap { width:100%; overflow:auto; background:#fff; border:1px solid var(--pdn-border); border-radius:10px; }
.pdn-table { width:100%; min-width:820px; margin:0; border-collapse:collapse; font-family:inherit!important; font-size:12px; }
.pdn-table th { padding:9px 8px; color:#32445d; background:#edf2f7; border-bottom:1px solid #ced8e4; text-align:left; font-weight:800; white-space:nowrap; }
.pdn-table td { padding:9px 8px; border-bottom:1px solid #e8edf3; vertical-align:top; }
.pdn-table tbody tr:nth-child(even) td { background:#fbfcfe; }
.pdn-table tbody tr:hover td { background:#eef6ff; }
.pdn-table tr:last-child td { border-bottom:0; }
.pdn-table .amount { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.pdn-table .center { text-align:center; }
.pdn-table tfoot th { background:#f4f7fb; border-top:2px solid #cbd5e1; }
.pdn-badge { display:inline-flex; align-items:center; padding:4px 7px; border-radius:999px; color:#344054; background:#eef2f6; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.03em; white-space:nowrap; }
.pdn-badge.success,.pdn-badge.finalized,.pdn-badge.balanced,.pdn-badge.processed,.pdn-badge.active{background:#dff5e8;color:#11623d}
.pdn-badge.warning,.pdn-badge.review,.pdn-badge.approved,.pdn-badge.pending,.pdn-badge.processing{background:#fff1cf;color:#7a4b00}
.pdn-badge.danger,.pdn-badge.changed,.pdn-badge.failed,.pdn-badge.shortage,.pdn-badge.cancelled,.pdn-badge.inactive{background:#fee4e2;color:#9c251d}
.pdn-badge.draft,.pdn-badge.reopened,.pdn-badge.imported,.pdn-badge.available{background:#e5efff;color:#175bb5}
.pdn-alert { margin:0 0 14px; padding:11px 13px; border:1px solid; border-radius:8px; font-size:13px; }
.pdn-alert.success{background:#ebfaef;border-color:#b7e7c3;color:#176536}.pdn-alert.error{background:#fff0ef;border-color:#f3bbb7;color:#982b24}.pdn-alert.warning{background:#fff8e5;border-color:#efd28a;color:#775200}
.pdn-error-list { margin:6px 0 0; padding-left:18px; }
.pdn-tabs { display:flex; flex-wrap:wrap; gap:7px; margin:0 0 14px; }
.pdn-tab { display:inline-flex; align-items:center; min-height:35px; padding:7px 11px; color:#fff!important; background:#4f6f96; border:1px solid transparent; border-radius:7px; font-size:11px; font-weight:700; text-decoration:none!important; cursor:pointer; white-space:nowrap; }
.pdn-tab:nth-child(6n+2){background:#2d6cdf}.pdn-tab:nth-child(6n+3){background:#218838}.pdn-tab:nth-child(6n+4){background:#8e2495}.pdn-tab:nth-child(6n+5){background:#e48b0b}.pdn-tab:nth-child(6n+6){background:#1685a9}
.pdn-tab.active { color:#111827!important; background:#fff!important; border-color:#9caabb; box-shadow:inset 0 -3px 0 #2d6cdf; }
.pdn-tab-panel { display:none; }.pdn-tab-panel.active { display:block; }
.pdn-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
.pdn-summary-item { padding:11px; background:#f9fbfd; border:1px solid var(--pdn-border); border-radius:8px; }
.pdn-summary-item span { display:block; margin-bottom:5px; color:var(--pdn-muted); font-size:11px; }.pdn-summary-item strong { font-size:16px; }
.pdn-stack { display:grid; gap:13px; }.pdn-inline { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.pdn-form-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }.pdn-form-grid .full { grid-column:1/-1; }
.pdn-check-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }.pdn-check { display:flex; align-items:center; gap:7px; min-height:35px; font-size:12px; font-weight:700; }
.pdn-empty { padding:26px !important; color:var(--pdn-muted); text-align:center; }
.pdn-pagination { margin-top:12px; }.pdn-pagination nav { display:flex; justify-content:flex-end; }.pdn-pagination svg { width:18px; }
.pdn-code { max-width:640px; max-height:300px; overflow:auto; margin:8px 0 0; padding:10px; color:#e5e7eb; background:#111827; border-radius:6px; font:11px Consolas,monospace; white-space:pre-wrap; }
.pdn-popover { position:absolute; z-index:100; right:0; top:41px; width:260px; max-height:350px; overflow:auto; padding:10px; background:#fff; border:1px solid var(--pdn-border); border-radius:8px; box-shadow:0 10px 28px rgba(16,24,40,.16); }
.pdn-column-menu { position:relative; }.pdn-column-menu>summary{list-style:none}.pdn-column-menu>summary::-webkit-details-marker{display:none}
.pdn-select.compact { min-height:29px; padding:4px 7px; min-width:100px; }.pdn-table [hidden]{display:none!important}
.pdn-note { margin-top:12px; padding:10px 12px; background:#f1f7fd; border-left:4px solid var(--pdn-primary); border-radius:5px; }
.pdn-mono { font-family:Consolas,monospace; font-size:11px; word-break:break-all; }



/* PD Operators workspace: follows the legacy Petro PD tab arrangement while
   remaining inside the current ERP layout and its inherited typography. */
.pdn-operator-tabs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 10px;
    margin: 0 0 14px;
    padding: 8px 10px;
    background: #fff;
    border: 1px solid var(--pdn-border);
    border-radius: 10px;
    box-shadow: 0 4px 14px rgba(31,50,81,.05);
}
.pdn-operator-tab {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 38px;
    padding: 9px 17px;
    color: #fff !important;
    border: 1px solid transparent;
    border-radius: 7px;
    font-family: inherit !important;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.15;
    text-decoration: none !important;
    white-space: nowrap;
    box-shadow: 0 2px 6px rgba(15,23,42,.10);
    transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
}
.pdn-operator-tab:nth-child(1) { background:#fff; color:#2d6cdf !important; border-color:#cfe0ff; }
.pdn-operator-tab:nth-child(2) { background:#9b0f8f; }
.pdn-operator-tab:nth-child(3) { background:#167ea4; }
.pdn-operator-tab:nth-child(4) { background:#247a2e; }
.pdn-operator-tab:nth-child(5) { background:#f4a51c; }
.pdn-operator-tab:nth-child(6) { background:#0b9eb4; }
.pdn-operator-tab:nth-child(7) { background:#7650c8; }
.pdn-operator-tab:nth-child(8) { background:#1aa645; }
.pdn-operator-tab:nth-child(9) { background:#3387e8; }
.pdn-operator-tab:nth-child(10) { background:#a00095; }
.pdn-operator-tab:nth-child(11) { background:#3387b8; }
.pdn-operator-tab:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(15,23,42,.16); filter:brightness(1.04); }
.pdn-operator-tab.active {
    background:#fff !important;
    border:2px solid currentColor !important;
    box-shadow:0 5px 14px rgba(15,23,42,.14);
}
.pdn-operator-tab:nth-child(1).active { color:#2d6cdf !important; }
.pdn-operator-tab:nth-child(2).active { color:#9b0f8f !important; }
.pdn-operator-tab:nth-child(3).active { color:#167ea4 !important; }
.pdn-operator-tab:nth-child(4).active { color:#247a2e !important; }
.pdn-operator-tab:nth-child(5).active { color:#f4a51c !important; }
.pdn-operator-tab:nth-child(6).active { color:#0b9eb4 !important; }
.pdn-operator-tab:nth-child(7).active { color:#7650c8 !important; }
.pdn-operator-tab:nth-child(8).active { color:#1aa645 !important; }
.pdn-operator-tab:nth-child(9).active { color:#3387e8 !important; }
.pdn-operator-tab:nth-child(10).active { color:#a00095 !important; }
.pdn-operator-tab:nth-child(11).active { color:#3387b8 !important; }
.pdn-operator-tab-panel { min-width:0; }
.pdn-operator-filter .pdn-field { min-width:145px; }
.pdn-operator-filter .pdn-field.grow { min-width:260px; }
.pdn-operator-summary-grid {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:11px;
    margin:0 0 13px;
}
.pdn-operator-summary-grid.wide { grid-template-columns:repeat(6,minmax(0,1fr)); }
.pdn-operator-summary-card {
    min-width:0;
    padding:12px 14px;
    background:#fff;
    border:1px solid var(--pdn-border);
    border-left:4px solid var(--pdn-primary);
    border-radius:9px;
    box-shadow:0 4px 12px rgba(31,50,81,.05);
}
.pdn-operator-summary-card.warning { border-left-color:var(--pdn-warning); }
.pdn-operator-summary-card.success { border-left-color:var(--pdn-success); }
.pdn-operator-summary-card.danger { border-left-color:var(--pdn-danger); }
.pdn-operator-summary-card span { display:block; margin-bottom:5px; color:var(--pdn-muted); font-size:11px; font-weight:700; }
.pdn-operator-summary-card strong { color:#20324d; font-size:20px; font-weight:800; font-variant-numeric:tabular-nums; }
.pdn-operator-table { min-width:940px; }
.pdn-operator-subhead { margin-bottom:12px; }
.pdn-operator-context-actions { margin:0 0 10px; }
.pdn-muted-text { color:var(--pdn-muted); font-size:11px; }

@media(max-width:1450px){.pdn-operator-summary-grid.wide{grid-template-columns:repeat(3,minmax(0,1fr))}.pdn-operator-tab{padding:8px 12px;font-size:12px}}
@media(max-width:900px){.pdn-operator-summary-grid,.pdn-operator-summary-grid.wide{grid-template-columns:repeat(2,minmax(0,1fr))}.pdn-operator-tabs{flex-wrap:nowrap;overflow-x:auto}.pdn-operator-tab{flex:0 0 auto}}
@media(max-width:560px){.pdn-operator-summary-grid,.pdn-operator-summary-grid.wide{grid-template-columns:1fr}}

@media(max-width:1200px){.pdn-grid.cards{grid-template-columns:repeat(2,minmax(0,1fr))}.pdn-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:850px){.pdn-page-head{flex-direction:column}.pdn-actions{justify-content:flex-start}.pdn-grid.two,.pdn-grid.three,.pdn-form-grid{grid-template-columns:1fr}.pdn-check-grid{grid-template-columns:1fr}.pdn-form-grid .full{grid-column:1}.pdn-system-page{padding-left:12px!important;padding-right:12px!important}}
@media(max-width:520px){.pdn-grid.cards,.pdn-summary{grid-template-columns:1fr}.pdn-page-head h2{font-size:20px}}
@media print{.pdn-actions,.pdn-toolbar,.pdn-tabs,.no-print{display:none!important}.pdn-system-page{padding:0!important;margin:0!important}.pdn-card{box-shadow:none;border:0;padding:0}.pdn-tab-panel{display:block!important}.pdn-table-wrap{overflow:visible}.pdn-table{min-width:0}}


/* Legacy-equivalent PD Operator list, action menu and modal workspace. */
.pdn-operator-list-card { overflow: visible; }
.pdn-operator-list-head { align-items:center; }
.pdn-operator-table-wrap { overflow:auto; position:relative; }
.pdn-operator-ledger-table { min-width:1500px; }
.pdn-action-cell { min-width:100px; }
.pdn-operator-actions-template { display:none; }
.pdn-operator-action-popover {
    position:fixed; z-index:99999; width:310px; max-height:min(620px, calc(100vh - 30px));
    overflow:auto; padding:12px; background:#fff; border:1px solid #d8e1eb;
    border-radius:8px; box-shadow:0 18px 45px rgba(15,23,42,.28);
}
.pdn-operator-action-popover a,
.pdn-operator-action-popover form button {
    display:flex; align-items:center; gap:8px; width:100%; padding:9px 10px;
    color:#17233a!important; background:transparent; border:0; border-radius:5px;
    font:inherit; font-weight:600; text-align:left; text-decoration:none!important; cursor:pointer;
}
.pdn-operator-action-popover a:hover,
.pdn-operator-action-popover form button:hover { background:#edf5ff; }
.pdn-operator-action-popover i { width:17px; text-align:center; }
.pdn-action-section-title { padding:7px 10px 5px; color:#607087; font-size:11px; font-weight:800; text-transform:uppercase; }
.pdn-action-divider { height:1px; margin:7px 0; background:#e2e8f0; }
.pdn-operator-modal-dialog { width:min(980px, calc(100vw - 30px)); }
.pdn-operator-modal-content { border-radius:12px; overflow:hidden; }
.pdn-operator-modal-content .modal-header { padding:15px 18px; background:#eef4fb; border-bottom:1px solid #d9e3ef; }
.pdn-operator-modal-content .modal-title { color:#24344d; font-weight:800; }
.pdn-operator-modal-content .modal-body { max-height:calc(100vh - 170px); overflow:auto; padding:18px; }
.pdn-modal-operator-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:15px; }
.pdn-modal-operator-summary>div,.pdn-detail-grid>div { padding:11px; border:1px solid #dce5f0; border-radius:8px; background:#f8fafc; }
.pdn-modal-operator-summary span,.pdn-detail-grid span { display:block; margin-bottom:4px; color:#667085; font-size:11px; font-weight:700; }
.pdn-modal-operator-summary strong,.pdn-detail-grid strong { color:#253247; }
.pdn-detail-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
.pdn-detail-grid .full { grid-column:1/-1; }
.pdn-modal-footer { margin:16px -18px -18px; padding:12px 18px; background:#f8fafc; border-top:1px solid #dce5f0; }
.pdn-modal-filter-note { margin-bottom:10px; color:#667085; font-size:12px; }
.pdn-modal-section-title { margin:18px 0 8px; font-size:16px; }
@media(max-width:760px){.pdn-modal-operator-summary,.pdn-detail-grid{grid-template-columns:1fr}.pdn-detail-grid .full{grid-column:1}.pdn-operator-action-popover{width:min(310px,calc(100vw - 20px))}}

</style>
<style>
/* Daily Pump Status mirrors the operational arrangement of the legacy Petro PD page. */
.pdn-daily-status-legend {
    display:flex;
    flex-wrap:wrap;
    gap:14px;
    margin:0 0 12px;
    padding:10px 12px;
    background:#fff;
    border:1px solid var(--pdn-border);
    border-radius:9px;
    font-size:12px;
    font-weight:700;
}
.pdn-status-dot { display:inline-block; width:12px; height:12px; margin-right:5px; border-radius:3px; vertical-align:-1px; }
.pdn-status-dot.available,.pdn-status-dot.closed{background:#3b83b9}.pdn-status-dot.assigned{background:#f4b400}.pdn-status-dot.received{background:#8f3a84}
.pdn-daily-pump-card-grid {
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:12px;
    margin-bottom:14px;
}
.pdn-daily-pump-card {
    min-height:112px;
    padding:14px 10px;
    color:#fff;
    background:#3b83b9;
    border-radius:8px;
    box-shadow:0 5px 14px rgba(15,23,42,.14);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;
}
.pdn-daily-pump-card.assigned{background:#f4b400}.pdn-daily-pump-card.received{background:#8f3a84}.pdn-daily-pump-card.closed{background:#3b83b9}
.pdn-daily-pump-card strong { color:#fff; font-size:19px; line-height:1.15; }
.pdn-daily-pump-card span { margin-top:6px; color:#fff; font-size:12px; font-weight:700; }
.pdn-daily-pump-card small { margin-top:4px; color:rgba(255,255,255,.94); font-size:10px; }
.pdn-daily-status-summary { grid-template-columns:repeat(4,minmax(0,1fr)); }
.pdn-operator-summary-card.purple { border-left-color:#8f3a84; }
.pdn-daily-status-head { align-items:center; }
.pdn-daily-status-table { min-width:1120px; }
.pdn-daily-status-context { display:flex; flex-wrap:wrap; gap:10px 18px; margin-bottom:15px; padding:10px 12px; background:#f4f7fb; border:1px solid var(--pdn-border); border-radius:8px; }
.pdn-daily-pump-picker { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; max-height:330px; overflow:auto; padding:2px; }
.pdn-daily-pump-choice { position:relative; margin:0; cursor:pointer; }
.pdn-daily-pump-choice input { position:absolute; opacity:0; pointer-events:none; }
.pdn-daily-pump-choice span { display:flex; min-height:92px; flex-direction:column; justify-content:center; padding:10px; border:2px solid #d5dfeb; border-radius:9px; background:#fff; text-align:center; }
.pdn-daily-pump-choice input:checked + span { border-color:var(--pdn-primary); background:#eaf2ff; box-shadow:0 0 0 2px rgba(45,108,223,.12); }
.pdn-daily-pump-choice strong { font-size:14px; }
.pdn-daily-pump-choice small { margin-top:4px; color:var(--pdn-muted); font-size:10px; }
.pdn-operator-action-popover span.disabled { display:block; padding:8px 11px; color:#98a2b3; cursor:not-allowed; }
.pdn-operator-action-popover a.danger { color:#b42318 !important; }
@media (max-width:1500px){.pdn-daily-pump-card-grid{grid-template-columns:repeat(5,minmax(0,1fr))}.pdn-daily-pump-picker{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:1100px){.pdn-daily-pump-card-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.pdn-daily-status-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:760px){.pdn-daily-pump-card-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pdn-daily-pump-picker{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media print {
    .pdn-daily-status-legend,.pdn-daily-pump-card-grid,.pdn-daily-status-summary,.pdn-operator-tabs,.pdn-toolbar,.pdn-page-head .pdn-actions{display:none!important}
    .pdn-daily-status-table{min-width:0;font-size:10px}
}
</style>
