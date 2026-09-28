@extends('layouts.app')

@section('css')
    @parent
    <style>
/* Hotel Management standard UI - aligned to the ERP/POS clean module style */
.hm-page{font-family:"Source Sans Pro","Helvetica Neue",Helvetica,Arial,sans-serif;font-size:13px;color:#333}.hm-page .content-header{position:relative;overflow:hidden;margin:0 15px 24px;padding:24px 28px;border:1px solid #dbe7f3;border-left:6px solid #2563eb;border-radius:18px;background:linear-gradient(135deg,#fff 0%,#f8fbff 55%,#eef6ff 100%);box-shadow:0 14px 35px rgba(15,23,42,.08)}.hm-page .content-header h1{font-size:28px;line-height:1.2;font-weight:800;letter-spacing:-.02em;color:#102033;margin:0}.hm-page .content-header small{display:block;margin-top:8px;font-size:13px;line-height:1.5;font-weight:500;color:#64748b}.hm-card,.hm-page .box{border:1px solid #e6edf3;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04);background:#fff}.hm-page .box-header{padding:12px 15px;border-bottom:1px solid #edf1f5}.hm-page .box-title{font-size:16px;font-weight:600}.hm-page .box-body{padding:15px}.hm-kpi{min-height:96px;border-radius:8px;background:#fff;border:1px solid #e6edf3;padding:16px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,.04)}.hm-kpi .hm-kpi-label{font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#607080;font-weight:700}.hm-kpi .hm-kpi-value{font-size:26px;line-height:32px;font-weight:700;color:#2f4050;margin-top:6px}.hm-kpi .hm-kpi-sub{font-size:12px;color:#7a869a;margin-top:4px}.hm-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:12px}.hm-toolbar .form-control{height:34px;font-size:13px;border-radius:4px}.hm-toolbar .btn{font-size:13px;border-radius:4px;padding:7px 12px;font-weight:600}.hm-btn-add{background:#2f80ed;border-color:#2f80ed;color:#fff}.hm-btn-csv{background:#00a65a;border-color:#00a65a;color:#fff}.hm-btn-excel{background:#008d4c;border-color:#008d4c;color:#fff}.hm-btn-pdf{background:#dd4b39;border-color:#dd4b39;color:#fff}.hm-btn-print{background:#605ca8;border-color:#605ca8;color:#fff}.hm-btn-col{background:#f39c12;border-color:#f39c12;color:#fff}.hm-table{width:100%;font-size:13px}.hm-table>thead>tr>th{background:#f7f9fb;color:#34495e;font-size:12px;text-transform:uppercase;border-bottom:1px solid #dfe6ee;white-space:nowrap}.hm-table>tbody>tr>td{vertical-align:middle;border-color:#edf1f5}.hm-badge{display:inline-block;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700;background:#eef3f8;color:#566573}.hm-badge.active,.hm-badge.available,.hm-badge.open,.hm-badge.clean,.hm-badge.reserved,.hm-badge.posted,.hm-badge.checked_in{background:#eaf8f0;color:#137333}.hm-badge.pending,.hm-badge.dirty{background:#fff6e5;color:#9a5a00}.hm-badge.maintenance,.hm-badge.closed,.hm-badge.cancelled,.hm-badge.out_of_service{background:#fdecea;color:#a61b1b}.hm-form-grid{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px}.hm-form-grid .form-group{margin-bottom:0}.hm-form-grid label{font-size:12px;font-weight:600;color:#4f5b67}.hm-empty{padding:24px;text-align:center;color:#7a869a;border:1px dashed #d7dee8;border-radius:8px;background:#fafcff}.hm-module-nav{display:flex;flex-wrap:wrap;align-items:center;gap:9px;margin:0 0 20px;padding:12px;border:1px solid #dbe7f3;border-radius:16px;background:linear-gradient(135deg,#f9fbfe 0%,#f3f7fc 100%);box-shadow:0 10px 25px rgba(15,23,42,.06)}.hm-module-nav a{--hm-tab-color:#2f80ed;position:relative;display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 17px;border:1px solid var(--hm-tab-color);border-radius:9px;background:var(--hm-tab-color);color:#fff!important;font-size:13px;font-weight:700;line-height:1.2;text-align:center;box-shadow:0 6px 14px rgba(15,23,42,.12);transition:transform .16s ease,filter .16s ease,box-shadow .16s ease}.hm-module-nav a:nth-child(14n+1){--hm-tab-color:#ff5733}.hm-module-nav a:nth-child(14n+2){--hm-tab-color:#800080}.hm-module-nav a:nth-child(14n+3){--hm-tab-color:#2874a6}.hm-module-nav a:nth-child(14n+4){--hm-tab-color:#33691e}.hm-module-nav a:nth-child(14n+5){--hm-tab-color:#f9a825}.hm-module-nav a:nth-child(14n+6){--hm-tab-color:#2874a6}.hm-module-nav a:nth-child(14n+7){--hm-tab-color:#33691e}.hm-module-nav a:nth-child(14n+8){--hm-tab-color:#f9a825}.hm-module-nav a:nth-child(14n+9){--hm-tab-color:#ff5733}.hm-module-nav a:nth-child(14n+10){--hm-tab-color:#800080}.hm-module-nav a:nth-child(14n+11){--hm-tab-color:#0097a7}.hm-module-nav a:nth-child(14n+12){--hm-tab-color:#b71c1c}.hm-module-nav a:nth-child(14n+13){--hm-tab-color:#1565c0}.hm-module-nav a:nth-child(14n){--hm-tab-color:#6a1b9a}.hm-module-nav a:hover,.hm-module-nav a:focus{color:#fff!important;filter:brightness(.92);transform:translateY(-1px);box-shadow:0 9px 18px rgba(15,23,42,.16);text-decoration:none;outline:none}.hm-module-nav a.active,.hm-module-nav a.active:hover,.hm-module-nav a.active:focus{background:#fff!important;color:#111827!important;border-color:var(--hm-tab-color)!important;filter:none;box-shadow:inset 0 0 0 2px var(--hm-tab-color),0 7px 16px rgba(15,23,42,.12);transform:none}.hm-module-nav a.active::after{content:"";position:absolute;left:15px;right:15px;bottom:4px;height:3px;border-radius:3px;background:var(--hm-tab-color)}.hm-actions .btn{margin-right:3px}.hm-alert{border-radius:6px;padding:10px 12px;margin-bottom:12px}.hm-report-grid{display:grid;grid-template-columns:repeat(3,minmax(220px,1fr));gap:14px}.hm-report-card{display:block;padding:16px;border:1px solid #e6edf3;border-radius:8px;background:#fff;color:#2f4050;box-shadow:0 2px 8px rgba(0,0,0,.04);min-height:105px}.hm-report-card:hover{border-color:#2f80ed;color:#2f80ed}.hm-report-card strong{display:block;font-size:16px;margin-bottom:6px}.hm-report-card span{display:block;font-size:12px;color:#7a869a}@media(max-width:991px){.hm-form-grid{grid-template-columns:repeat(2,minmax(160px,1fr))}.hm-report-grid{grid-template-columns:repeat(2,minmax(220px,1fr))}}@media(max-width:575px){.hm-page .content-header{margin:0 10px 18px;padding:20px;border-radius:14px}.hm-page .content-header h1{font-size:24px}.hm-module-nav{gap:7px;padding:9px}.hm-module-nav a{flex:1 1 calc(50% - 7px);min-width:130px;padding:9px 10px}.hm-form-grid,.hm-report-grid{grid-template-columns:1fr}.hm-toolbar{display:block}.hm-toolbar>*{margin-bottom:8px;width:100%}}


/* ================================================================
 | Hotel Management global POS KPI card standard
 | Applies to every Hotel Management summary/KPI box without changing
 | controllers, routes, tenant queries, values, forms or tables.
 * ================================================================ */
.hm-page .hm-kpi,
.hm-page .hm-stat-card,
.hm-page .hm-kpi-card,
.hm-page .hotel-management-card {
    --hm-pos-tone: #2f80ed;
    --hm-pos-tone-rgb: 47, 128, 237;
    position: relative;
    overflow: hidden;
    min-height: 184px;
    margin-bottom: 20px;
    border: 1px solid #dbe7f3 !important;
    border-radius: 18px !important;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%) !important;
    box-shadow: inset 0 -4px 0 var(--hm-pos-tone), 0 14px 30px rgba(15, 23, 42, .08) !important;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.hm-page .hm-kpi:hover,
.hm-page .hm-stat-card:hover,
.hm-page .hm-kpi-card:hover,
.hm-page .hotel-management-card:hover {
    transform: translateY(-2px);
    border-color: rgba(var(--hm-pos-tone-rgb), .34) !important;
    box-shadow: inset 0 -4px 0 var(--hm-pos-tone), 0 18px 42px rgba(15, 23, 42, .12) !important;
}

.hm-page .hm-kpi,
.hm-page .hm-stat-card,
.hm-page .hm-kpi-card {
    padding: 20px 22px 58px !important;
}

.hm-page .hotel-management-card > .box-body {
    position: relative;
    min-height: 184px;
    padding: 20px 22px 58px !important;
}

/* Coloured icon tile */
.hm-page .hm-kpi::before,
.hm-page .hm-stat-card::before,
.hm-page .hm-kpi-card::before,
.hm-page .hotel-management-card > .box-body::before {
    content: "\f201";
    position: absolute;
    top: 20px;
    left: 22px;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border-radius: 16px;
    color: #ffffff;
    background: linear-gradient(135deg, var(--hm-pos-tone), rgba(var(--hm-pos-tone-rgb), .64));
    box-shadow: 0 10px 22px rgba(var(--hm-pos-tone-rgb), .24);
    font-family: FontAwesome;
    font-size: 22px;
    font-style: normal;
    font-weight: normal;
    line-height: 1;
    text-rendering: auto;
    -webkit-font-smoothing: antialiased;
}

/* Decorative POS spark line */
.hm-page .hm-kpi::after,
.hm-page .hm-stat-card::after,
.hm-page .hm-kpi-card::after,
.hm-page .hotel-management-card > .box-body::after {
    content: "";
    position: absolute;
    left: 22px;
    right: 22px;
    bottom: 15px;
    height: 28px;
    border-radius: 15px;
    background:
        linear-gradient(169deg, transparent 0 46%, rgba(var(--hm-pos-tone-rgb), .82) 47% 50%, transparent 51% 100%),
        linear-gradient(90deg, rgba(var(--hm-pos-tone-rgb), .06), rgba(var(--hm-pos-tone-rgb), .19), rgba(var(--hm-pos-tone-rgb), .07));
    pointer-events: none;
}

/* Existing three-part KPI markup */
.hm-page .hm-kpi .hm-kpi-label,
.hm-page .hm-stat-card .hm-stat-label,
.hm-page .hm-kpi-card > span,
.hm-page .hotel-management-card > .box-body > h4 {
    display: flex !important;
    align-items: center;
    min-height: 56px;
    margin: 0 0 0 72px !important;
    padding: 0 !important;
    color: #23364d !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    line-height: 1.3 !important;
    letter-spacing: 0 !important;
    text-transform: none !important;
}

.hm-page .hm-kpi .hm-kpi-value,
.hm-page .hm-stat-card > strong,
.hm-page .hm-kpi-card > strong,
.hm-page .hotel-management-card > .box-body > h2 {
    display: block;
    margin: 15px 0 0 !important;
    color: var(--hm-pos-tone) !important;
    font-size: 31px !important;
    font-weight: 900 !important;
    line-height: 1.08 !important;
    letter-spacing: -.02em;
}

.hm-page .hm-kpi .hm-kpi-sub,
.hm-page .hm-stat-card > small,
.hm-page .hm-kpi-card > small {
    display: block;
    min-height: 18px;
    margin: 9px 0 0 !important;
    color: #64748b !important;
    font-size: 12px !important;
    line-height: 1.4 !important;
}

/* Keep analytics cards in a POS-friendly responsive grid. */
.hm-page .hm-kpi-grid,
.hm-page .hm-analytics-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(190px, 1fr));
    gap: 20px;
    margin: 0 0 24px;
}
.hm-page .hm-kpi-grid .hm-kpi-card,
.hm-page .hm-analytics-grid .hm-kpi-card { margin-bottom: 0; }

/* POS colour rotation for Bootstrap KPI rows and grid KPI cards. */
.hm-page .row > [class*="col-"]:nth-child(6n+1) .hm-kpi,
.hm-page .row > [class*="col-"]:nth-child(6n+1) .hm-stat-card,
.hm-page .row > [class*="col-"]:nth-child(6n+1) .hotel-management-card,
.hm-page .hm-kpi-grid > :nth-child(6n+1),
.hm-page .hm-analytics-grid > :nth-child(6n+1) { --hm-pos-tone:#2f80ed; --hm-pos-tone-rgb:47,128,237; }

.hm-page .row > [class*="col-"]:nth-child(6n+2) .hm-kpi,
.hm-page .row > [class*="col-"]:nth-child(6n+2) .hm-stat-card,
.hm-page .row > [class*="col-"]:nth-child(6n+2) .hotel-management-card,
.hm-page .hm-kpi-grid > :nth-child(6n+2),
.hm-page .hm-analytics-grid > :nth-child(6n+2) { --hm-pos-tone:#16a34a; --hm-pos-tone-rgb:22,163,74; }

.hm-page .row > [class*="col-"]:nth-child(6n+3) .hm-kpi,
.hm-page .row > [class*="col-"]:nth-child(6n+3) .hm-stat-card,
.hm-page .row > [class*="col-"]:nth-child(6n+3) .hotel-management-card,
.hm-page .hm-kpi-grid > :nth-child(6n+3),
.hm-page .hm-analytics-grid > :nth-child(6n+3) { --hm-pos-tone:#f59e0b; --hm-pos-tone-rgb:245,158,11; }

.hm-page .row > [class*="col-"]:nth-child(6n+4) .hm-kpi,
.hm-page .row > [class*="col-"]:nth-child(6n+4) .hm-stat-card,
.hm-page .row > [class*="col-"]:nth-child(6n+4) .hotel-management-card,
.hm-page .hm-kpi-grid > :nth-child(6n+4),
.hm-page .hm-analytics-grid > :nth-child(6n+4) { --hm-pos-tone:#7c3aed; --hm-pos-tone-rgb:124,58,237; }

.hm-page .row > [class*="col-"]:nth-child(6n+5) .hm-kpi,
.hm-page .row > [class*="col-"]:nth-child(6n+5) .hm-stat-card,
.hm-page .row > [class*="col-"]:nth-child(6n+5) .hotel-management-card,
.hm-page .hm-kpi-grid > :nth-child(6n+5),
.hm-page .hm-analytics-grid > :nth-child(6n+5) { --hm-pos-tone:#0891b2; --hm-pos-tone-rgb:8,145,178; }

.hm-page .row > [class*="col-"]:nth-child(6n) .hm-kpi,
.hm-page .row > [class*="col-"]:nth-child(6n) .hm-stat-card,
.hm-page .row > [class*="col-"]:nth-child(6n) .hotel-management-card,
.hm-page .hm-kpi-grid > :nth-child(6n),
.hm-page .hm-analytics-grid > :nth-child(6n) { --hm-pos-tone:#e11d48; --hm-pos-tone-rgb:225,29,72; }

/* Hotel/POS-friendly icon rotation. */
.hm-page .row > [class*="col-"]:nth-child(8n+1) .hm-kpi::before,
.hm-page .row > [class*="col-"]:nth-child(8n+1) .hm-stat-card::before,
.hm-page .row > [class*="col-"]:nth-child(8n+1) .hotel-management-card > .box-body::before,
.hm-page .hm-kpi-grid > :nth-child(8n+1)::before,
.hm-page .hm-analytics-grid > :nth-child(8n+1)::before { content:"\f236"; }

.hm-page .row > [class*="col-"]:nth-child(8n+2) .hm-kpi::before,
.hm-page .row > [class*="col-"]:nth-child(8n+2) .hm-stat-card::before,
.hm-page .row > [class*="col-"]:nth-child(8n+2) .hotel-management-card > .box-body::before,
.hm-page .hm-kpi-grid > :nth-child(8n+2)::before,
.hm-page .hm-analytics-grid > :nth-child(8n+2)::before { content:"\f073"; }

.hm-page .row > [class*="col-"]:nth-child(8n+3) .hm-kpi::before,
.hm-page .row > [class*="col-"]:nth-child(8n+3) .hm-stat-card::before,
.hm-page .row > [class*="col-"]:nth-child(8n+3) .hotel-management-card > .box-body::before,
.hm-page .hm-kpi-grid > :nth-child(8n+3)::before,
.hm-page .hm-analytics-grid > :nth-child(8n+3)::before { content:"\f058"; }

.hm-page .row > [class*="col-"]:nth-child(8n+4) .hm-kpi::before,
.hm-page .row > [class*="col-"]:nth-child(8n+4) .hm-stat-card::before,
.hm-page .row > [class*="col-"]:nth-child(8n+4) .hotel-management-card > .box-body::before,
.hm-page .hm-kpi-grid > :nth-child(8n+4)::before,
.hm-page .hm-analytics-grid > :nth-child(8n+4)::before { content:"\f08b"; }

.hm-page .row > [class*="col-"]:nth-child(8n+5) .hm-kpi::before,
.hm-page .row > [class*="col-"]:nth-child(8n+5) .hm-stat-card::before,
.hm-page .row > [class*="col-"]:nth-child(8n+5) .hotel-management-card > .box-body::before,
.hm-page .hm-kpi-grid > :nth-child(8n+5)::before,
.hm-page .hm-analytics-grid > :nth-child(8n+5)::before { content:"\f0d6"; }

.hm-page .row > [class*="col-"]:nth-child(8n+6) .hm-kpi::before,
.hm-page .row > [class*="col-"]:nth-child(8n+6) .hm-stat-card::before,
.hm-page .row > [class*="col-"]:nth-child(8n+6) .hotel-management-card > .box-body::before,
.hm-page .hm-kpi-grid > :nth-child(8n+6)::before,
.hm-page .hm-analytics-grid > :nth-child(8n+6)::before { content:"\f0ae"; }

.hm-page .row > [class*="col-"]:nth-child(8n+7) .hm-kpi::before,
.hm-page .row > [class*="col-"]:nth-child(8n+7) .hm-stat-card::before,
.hm-page .row > [class*="col-"]:nth-child(8n+7) .hotel-management-card > .box-body::before,
.hm-page .hm-kpi-grid > :nth-child(8n+7)::before,
.hm-page .hm-analytics-grid > :nth-child(8n+7)::before { content:"\f1ad"; }

.hm-page .row > [class*="col-"]:nth-child(8n) .hm-kpi::before,
.hm-page .row > [class*="col-"]:nth-child(8n) .hm-stat-card::before,
.hm-page .row > [class*="col-"]:nth-child(8n) .hotel-management-card > .box-body::before,
.hm-page .hm-kpi-grid > :nth-child(8n)::before,
.hm-page .hm-analytics-grid > :nth-child(8n)::before { content:"\f201"; }

/* The separately designed owner-dashboard POS cards keep their own markup. */
.hm-page .hm-owner-dashboard .hm-dashboard-kpi {
    min-height: 184px;
}

@media (max-width: 1199px) {
    .hm-page .hm-kpi-grid,
    .hm-page .hm-analytics-grid { grid-template-columns: repeat(3, minmax(180px, 1fr)); }
}

@media (max-width: 991px) {
    .hm-page .hm-kpi-grid,
    .hm-page .hm-analytics-grid { grid-template-columns: repeat(2, minmax(180px, 1fr)); }
}

@media (max-width: 575px) {
    .hm-page .hm-kpi,
    .hm-page .hm-stat-card,
    .hm-page .hm-kpi-card,
    .hm-page .hotel-management-card { min-height: 172px; }

    .hm-page .hm-kpi,
    .hm-page .hm-stat-card,
    .hm-page .hm-kpi-card,
    .hm-page .hotel-management-card > .box-body { padding: 18px 18px 54px !important; }

    .hm-page .hm-kpi::before,
    .hm-page .hm-stat-card::before,
    .hm-page .hm-kpi-card::before,
    .hm-page .hotel-management-card > .box-body::before {
        top: 18px;
        left: 18px;
        width: 50px;
        height: 50px;
        border-radius: 14px;
        font-size: 20px;
    }

    .hm-page .hm-kpi .hm-kpi-label,
    .hm-page .hm-stat-card .hm-stat-label,
    .hm-page .hm-kpi-card > span,
    .hm-page .hotel-management-card > .box-body > h4 {
        min-height: 50px;
        margin-left: 64px !important;
        font-size: 13px !important;
    }

    .hm-page .hm-kpi .hm-kpi-value,
    .hm-page .hm-stat-card > strong,
    .hm-page .hm-kpi-card > strong,
    .hm-page .hotel-management-card > .box-body > h2 { font-size: 28px !important; }

    .hm-page .hm-kpi::after,
    .hm-page .hm-stat-card::after,
    .hm-page .hm-kpi-card::after,
    .hm-page .hotel-management-card > .box-body::after {
        left: 18px;
        right: 18px;
        bottom: 13px;
        height: 25px;
    }

    .hm-page .hm-kpi-grid,
    .hm-page .hm-analytics-grid { grid-template-columns: 1fr; gap: 14px; }
}
/* Hotel Reports landing page - POS dashboard card standard */
.hm-page .hm-pos-report-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(240px, 1fr));
    gap: 22px;
    align-items: stretch;
}

.hm-page .hm-pos-report-card {
    --hm-report-tone: #2f80ed;
    --hm-report-tone-rgb: 47, 128, 237;
    position: relative;
    display: flex;
    flex-direction: column;
    min-height: 198px;
    padding: 24px 24px 58px;
    overflow: hidden;
    border: 1px solid #dce7f2;
    border-radius: 20px;
    background: #ffffff;
    color: #17233b;
    box-shadow: inset 0 -4px 0 var(--hm-report-tone), 0 14px 30px rgba(15, 23, 42, .08);
    transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
}

.hm-page .hm-pos-report-card:hover,
.hm-page .hm-pos-report-card:focus {
    color: #17233b;
    text-decoration: none;
    transform: translateY(-4px);
    border-color: rgba(var(--hm-report-tone-rgb), .38);
    box-shadow: inset 0 -4px 0 var(--hm-report-tone), 0 20px 42px rgba(15, 23, 42, .13);
    outline: 0;
}

.hm-page .hm-report-card-head {
    display: flex;
    align-items: center;
    gap: 16px;
    min-height: 58px;
}

.hm-page .hm-report-icon {
    flex: 0 0 58px;
    width: 58px;
    height: 58px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 18px;
    background: linear-gradient(135deg, var(--hm-report-tone), rgba(var(--hm-report-tone-rgb), .68));
    color: #ffffff;
    font-size: 24px;
    box-shadow: 0 11px 24px rgba(var(--hm-report-tone-rgb), .25);
}

.hm-page .hm-pos-report-card .hm-report-card-head strong {
    display: block;
    margin: 0;
    color: #17233b;
    font-size: 17px;
    line-height: 1.32;
    font-weight: 800;
}

.hm-page .hm-pos-report-card .hm-report-description {
    display: block;
    margin-top: 19px;
    color: #718096;
    font-size: 13px;
    line-height: 1.55;
}

.hm-page .hm-pos-report-card .hm-report-open {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: auto;
    padding-top: 15px;
    color: var(--hm-report-tone);
    font-size: 13px;
    font-weight: 800;
}

.hm-page .hm-pos-report-card .hm-report-open .fa {
    transition: transform .2s ease;
}

.hm-page .hm-pos-report-card:hover .hm-report-open .fa,
.hm-page .hm-pos-report-card:focus .hm-report-open .fa {
    transform: translateX(4px);
}

.hm-page .hm-report-trend {
    position: absolute;
    left: 24px;
    right: 24px;
    bottom: 14px;
    height: 27px;
    overflow: hidden;
    border-radius: 14px;
    background: linear-gradient(90deg, rgba(var(--hm-report-tone-rgb), .06), rgba(var(--hm-report-tone-rgb), .19), rgba(var(--hm-report-tone-rgb), .07));
}

.hm-page .hm-report-trend::after {
    content: "";
    position: absolute;
    left: 8%;
    right: 9%;
    bottom: 5px;
    height: 18px;
    border-top: 3px solid var(--hm-report-tone);
    transform: skewY(-7deg);
    opacity: .9;
}

.hm-page .hm-report-blue   { --hm-report-tone:#2f80ed; --hm-report-tone-rgb:47,128,237; }
.hm-page .hm-report-green  { --hm-report-tone:#16a34a; --hm-report-tone-rgb:22,163,74; }
.hm-page .hm-report-amber  { --hm-report-tone:#f59e0b; --hm-report-tone-rgb:245,158,11; }
.hm-page .hm-report-purple { --hm-report-tone:#7c3aed; --hm-report-tone-rgb:124,58,237; }
.hm-page .hm-report-cyan   { --hm-report-tone:#0891b2; --hm-report-tone-rgb:8,145,178; }
.hm-page .hm-report-red    { --hm-report-tone:#e11d48; --hm-report-tone-rgb:225,29,72; }
.hm-page .hm-report-indigo { --hm-report-tone:#4f46e5; --hm-report-tone-rgb:79,70,229; }

@media (max-width: 1199px) {
    .hm-page .hm-pos-report-grid {
        grid-template-columns: repeat(2, minmax(230px, 1fr));
    }
}

@media (max-width: 767px) {
    .hm-page .hm-pos-report-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .hm-page .hm-pos-report-card {
        min-height: 184px;
        padding: 19px 19px 54px;
        border-radius: 17px;
    }

    .hm-page .hm-report-card-head {
        gap: 13px;
    }

    .hm-page .hm-report-icon {
        flex-basis: 52px;
        width: 52px;
        height: 52px;
        border-radius: 15px;
        font-size: 21px;
    }

    .hm-page .hm-pos-report-card .hm-report-card-head strong {
        font-size: 16px;
    }

    .hm-page .hm-report-trend {
        left: 19px;
        right: 19px;
        bottom: 12px;
    }
}


    </style>
@endsection

@section('content')
    <div class="hm-page">
        @yield('hotel_content')
    </div>
@endsection

@section('javascript')
    @parent
    <script>
        document.addEventListener('keyup', function(e){
            if(!e.target.classList.contains('hm-search-input')) return;
            var box = e.target.closest('.box'); if(!box) return;
            var term = e.target.value.toLowerCase();
            box.querySelectorAll('tbody tr').forEach(function(row){ row.style.display = row.textContent.toLowerCase().indexOf(term) >= 0 ? '' : 'none'; });
        });
        document.addEventListener('click', function(e){
            if(!e.target.classList.contains('hm-export-btn')) return;
            var type = e.target.getAttribute('data-export');
            var form = e.target.closest('.box').querySelector('form.hm-report-filter');
            if(form){ var input = form.querySelector('[name=export]') || document.createElement('input'); input.type='hidden'; input.name='export'; input.value=type; form.appendChild(input); form.submit(); }
        });
    </script>
@endsection
