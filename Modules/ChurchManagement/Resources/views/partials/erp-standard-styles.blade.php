{{--
    Church Management — ERP Dashboard Standard.

    A SELF-CONTAINED copy of the house design system used by the POS Dashboard
    (POS/Resources/views/partials/erp-standard-styles.blade.php).

    WHY A COPY RATHER THAN @include of the POS partial
        This module is meant to stand on its own. Including another module's
        view would mean Church Management loses its styling the moment POS is
        disabled, moved or restyled - and it must run on an install where POS is
        not present at all.

        A copy costs a few kilobytes and cannot break that way. If the house
        standard changes, this file is the one place to update.

    Everything is scoped under .communication-hub-ui and .chc-erp-standard-ui, so
    nothing here can affect another module's pages.
--}}
<style id="chc-erp-standard">
/* ------------------------------------------------------------------ */
/* 1. ERP Dashboard Standard — as used by the POS Dashboard            */
/* ------------------------------------------------------------------ */
.communication-hub-ui{font-family:inherit;color:#0f172a;background:#f5f8fc;padding-top:0;}
.communication-hub-ui .ch-shell{max-width:100%;padding:0 22px 24px;}
.communication-hub-ui .ch-hero{background:linear-gradient(135deg,#ffffff 0%,#f8fbff 55%,#eef6ff 100%);border:1px solid #dbe7f3;border-radius:18px;color:#0f172a;padding:24px 26px;margin-bottom:22px;box-shadow:0 14px 35px rgba(15,23,42,.08);display:flex;align-items:center;justify-content:space-between;gap:18px;position:relative;overflow:hidden;}
.communication-hub-ui .ch-hero:before{content:"";position:absolute;left:0;top:0;height:100%;width:6px;background:linear-gradient(180deg,#2563eb,#06b6d4);}
.communication-hub-ui .ch-eyebrow{font-size:12px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;color:#2563eb;margin-bottom:6px;}
.communication-hub-ui .ch-hero h1{margin:0;font-size:28px;font-weight:800;letter-spacing:-.02em;color:#102033;}
.communication-hub-ui .ch-hero p{margin:8px 0 0;color:#64748b;font-size:13px;max-width:720px;line-height:1.55;}
.communication-hub-ui .ch-quick-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;}
.communication-hub-ui .ch-quick-actions .btn,.communication-hub-ui .btn{border-radius:10px;font-weight:700;box-shadow:0 6px 16px rgba(2,6,23,.08);border-width:1px;}

.communication-hub-ui .ch-kpi-grid,.communication-hub-ui .ch-standard-grid{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:22px;margin-bottom:26px;}
.communication-hub-ui .ch-kpi{background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%);border-radius:18px;border:1px solid #cfe0f3;padding:18px 18px 15px;min-height:158px;box-shadow:0 16px 36px rgba(15,23,42,.10);position:relative;overflow:hidden;transition:.18s ease;}
.communication-hub-ui .ch-kpi:hover{transform:translateY(-2px);box-shadow:0 18px 42px rgba(15,23,42,.12);}
.communication-hub-ui .ch-kpi:before{content:"";position:absolute;left:0;right:0;bottom:0;height:3px;background:#2563eb;}
.communication-hub-ui .ch-kpi .ch-kpi-top{display:flex;align-items:center;gap:12px;}
.communication-hub-ui .ch-kpi .ch-icon{width:52px;height:52px;border-radius:15px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;background:linear-gradient(135deg,#2563eb,#38bdf8);box-shadow:0 10px 20px rgba(37,99,235,.22);}
.communication-hub-ui .ch-kpi .label-text{font-size:13px;color:#334155;font-weight:800;line-height:1.2;}
.communication-hub-ui .ch-kpi .value{font-size:30px;font-weight:900;color:#0f172a;margin-top:14px;line-height:1;letter-spacing:-.02em;}
.communication-hub-ui .ch-kpi .hint{font-size:12px;color:#64748b;margin-top:8px;min-height:18px;}
.communication-hub-ui .ch-kpi .spark{height:20px;margin-top:12px;border-radius:12px;background:linear-gradient(90deg,rgba(37,99,235,.08),rgba(37,99,235,.20),rgba(37,99,235,.08));position:relative;overflow:hidden;}
.communication-hub-ui .ch-kpi .spark:after{content:"";position:absolute;left:10%;right:10%;top:11px;border-top:2px solid rgba(37,99,235,.75);transform:skewY(-7deg);}
.communication-hub-ui .ch-kpi.success:before{background:#16a34a}.communication-hub-ui .ch-kpi.success .ch-icon{background:linear-gradient(135deg,#16a34a,#86efac)}.communication-hub-ui .ch-kpi.success .spark{background:linear-gradient(90deg,rgba(22,163,74,.08),rgba(22,163,74,.20),rgba(22,163,74,.08))}.communication-hub-ui .ch-kpi.success .spark:after{border-color:rgba(22,163,74,.75)}
.communication-hub-ui .ch-kpi.warning:before{background:#f59e0b}.communication-hub-ui .ch-kpi.warning .ch-icon{background:linear-gradient(135deg,#f59e0b,#fde68a)}.communication-hub-ui .ch-kpi.warning .spark{background:linear-gradient(90deg,rgba(245,158,11,.08),rgba(245,158,11,.22),rgba(245,158,11,.08))}.communication-hub-ui .ch-kpi.warning .spark:after{border-color:rgba(245,158,11,.78)}
.communication-hub-ui .ch-kpi.purple:before{background:#7c3aed}.communication-hub-ui .ch-kpi.purple .ch-icon{background:linear-gradient(135deg,#7c3aed,#c084fc)}
.communication-hub-ui .ch-kpi.cyan:before{background:#0891b2}.communication-hub-ui .ch-kpi.cyan .ch-icon{background:linear-gradient(135deg,#0891b2,#67e8f9)}
.communication-hub-ui .ch-kpi-link{display:block;color:inherit;text-decoration:none;}
.communication-hub-ui .ch-kpi-link:hover,.communication-hub-ui .ch-kpi-link:focus{color:inherit;text-decoration:none;}
.communication-hub-ui .ch-kpi-link .ch-kpi{height:100%;}
.communication-hub-ui .ch-drill{float:right;font-size:11px;color:#2563eb;font-weight:900;}

.communication-hub-ui .ch-card{background:#fff;border:1px solid #cfe0f3;border-radius:18px;box-shadow:0 16px 36px rgba(15,23,42,.09);margin-bottom:20px;overflow:hidden;}
.communication-hub-ui .ch-card + .ch-card{margin-top:22px;}
.communication-hub-ui .ch-card-header{padding:18px 20px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between;gap:12px;background:linear-gradient(180deg,#fff,#fbfdff);}
.communication-hub-ui .ch-card-title{font-size:17px;font-weight:850;margin:0;color:#0f172a;letter-spacing:-.01em;}
.communication-hub-ui .ch-card-subtitle{font-size:12px;color:#64748b;margin-top:4px;}
.communication-hub-ui .ch-card-body{padding:20px;}

.communication-hub-ui .ch-toolbar{background:#fff;border:1px solid #dbe7f3;border-radius:16px;padding:14px 16px;margin-top:4px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;box-shadow:0 10px 24px rgba(15,23,42,.06);}
.communication-hub-ui .form-control,.communication-hub-ui select{border-radius:10px;border-color:#dbe7f3;box-shadow:none;min-height:40px;}
.communication-hub-ui table.table{margin-bottom:0;background:#fff;}
.communication-hub-ui table.table thead th{background:#f8fafc;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #dbe7f3!important;white-space:nowrap;font-weight:800;}
.communication-hub-ui table.table td{vertical-align:middle!important;color:#26354a;border-color:#eef2f7!important;}
.communication-hub-ui .ch-badge-soft{border-radius:999px;padding:6px 10px;background:#dcfce7;color:#166534;font-weight:800;font-size:12px;}
.communication-hub-ui .ch-badge-soft.warning{background:#fef3c7;color:#92400e}.communication-hub-ui .ch-badge-soft.danger{background:#fee2e2;color:#991b1b}.communication-hub-ui .ch-badge-soft.info{background:#dbeafe;color:#1d4ed8}
.communication-hub-ui .empty-state{padding:34px;text-align:center;color:#64748b;background:linear-gradient(180deg,#fbfdff,#f8fafc);border:1px dashed #cbd5e1;border-radius:16px;}
.communication-hub-ui .ch-two-col{display:grid;grid-template-columns:2fr 1.2fr;gap:20px;margin-bottom:20px;}
.communication-hub-ui .ch-actions-strip{background:#fff;border:1px solid #dbe7f3;border-radius:18px;padding:18px 22px;margin:24px 0;box-shadow:0 14px 30px rgba(15,23,42,.07);display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;}
.communication-hub-ui .ch-page-note{font-size:13px;color:#64748b;line-height:1.55;}

@media(max-width:1199px){.communication-hub-ui .ch-kpi-grid,.communication-hub-ui .ch-standard-grid{grid-template-columns:repeat(2,minmax(220px,1fr));}.communication-hub-ui .ch-two-col{grid-template-columns:1fr;}}
@media(max-width:767px){.communication-hub-ui .ch-hero{display:block}.communication-hub-ui .ch-quick-actions{justify-content:flex-start;margin-top:14px}.communication-hub-ui .ch-kpi-grid,.communication-hub-ui .ch-standard-grid{grid-template-columns:1fr;gap:16px}.communication-hub-ui .ch-toolbar{display:block}.communication-hub-ui .ch-shell{padding:0 10px 18px}}


/* ------------------------------------------------------------------ */
/* Church Management specifics                                         */
/* ------------------------------------------------------------------ */
.chc-erp-standard-ui{font-family:inherit;background:#f5f8fc;color:#0f172a;}
.chc-erp-standard-ui a{text-decoration:none;}

/* Module tab strip, matching the POS module navigation. */
.chc-erp-standard-ui .chc-nav{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:22px;padding:12px 14px;border:1px solid #dbe7f3;border-radius:16px;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.06);}
.chc-erp-standard-ui .chc-nav a{display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:0 16px;border:1px solid #dbe7f3;border-radius:10px;background:#f8fafc;color:#334155;font-size:13px;font-weight:700;}
.chc-erp-standard-ui .chc-nav a:hover{background:#eef6ff;border-color:#cfe0f3;color:#2563eb;}
.chc-erp-standard-ui .chc-nav a.active{background:#2563eb;border-color:#2563eb;color:#fff;box-shadow:0 6px 16px rgba(37,99,235,.24);}

/* Forms. */
.chc-erp-standard-ui .chc-form-grid{display:grid;grid-template-columns:repeat(3,minmax(200px,1fr));gap:16px;}
.chc-erp-standard-ui .chc-form-grid.two{grid-template-columns:repeat(2,minmax(220px,1fr));}
.chc-erp-standard-ui .chc-field{margin-bottom:0;}
.chc-erp-standard-ui .chc-field label{display:block;font-size:12px;font-weight:800;color:#475569;margin-bottom:6px;}
.chc-erp-standard-ui .chc-field input,.chc-erp-standard-ui .chc-field select,.chc-erp-standard-ui .chc-field textarea{width:100%;border:1px solid #dbe7f3;border-radius:10px;padding:9px 12px;font-size:13px;background:#fff;min-height:40px;}
.chc-erp-standard-ui .chc-field input:focus,.chc-erp-standard-ui .chc-field select:focus,.chc-erp-standard-ui .chc-field textarea:focus{outline:0;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12);}
.chc-erp-standard-ui .chc-field textarea{min-height:88px;}
.chc-erp-standard-ui .chc-field .chc-hint{font-size:12px;color:#64748b;margin-top:5px;}
.chc-erp-standard-ui .chc-form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px;}

/* Tables. */
.chc-erp-standard-ui .chc-table{width:100%;border-collapse:collapse;background:#fff;font-size:13px;}
.chc-erp-standard-ui .chc-table th{background:#f8fafc;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #dbe7f3;white-space:nowrap;font-weight:800;padding:12px 14px;text-align:left;}
.chc-erp-standard-ui .chc-table td{vertical-align:middle;color:#26354a;border-bottom:1px solid #eef2f7;padding:12px 14px;}
.chc-erp-standard-ui .chc-table tbody tr:hover{background:#f8fbff;}

/*
 * Row actions on ONE line.
 *
 * nowrap on the cell and the group together, so the column sizes to the full
 * row of buttons. Without both, a shrink-to-fit column and a wrapping group
 * fight each other and the buttons stack down the cell.
 *
 * display:contents on the forms lets their buttons take part in the group's own
 * spacing; a <form> is otherwise a flex item in its own right with the button
 * nested inside, which makes the gaps uneven.
 */
.chc-erp-standard-ui .chc-actions-cell{white-space:nowrap;width:1%;}
.chc-erp-standard-ui .chc-actions{display:inline-flex;align-items:center;gap:6px;flex-wrap:nowrap;white-space:nowrap;}
.chc-erp-standard-ui .chc-actions form{margin:0;display:contents;}
.chc-erp-standard-ui .chc-btn-sm{display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 12px;border-radius:9px;font-size:12px;font-weight:700;line-height:1;border:1px solid #dbe7f3;background:#f8fafc;color:#334155;cursor:pointer;white-space:nowrap;}
.chc-erp-standard-ui .chc-btn-sm:hover{background:#eef6ff;border-color:#cfe0f3;color:#2563eb;}
.chc-erp-standard-ui .chc-btn-sm.danger{background:#fef2f2;border-color:#fecaca;color:#991b1b;}
.chc-erp-standard-ui .chc-btn-sm.danger:hover{background:#fee2e2;border-color:#fca5a5;color:#991b1b;}

/* Status pills. */
.chc-erp-standard-ui .chc-pill{display:inline-block;border-radius:999px;padding:5px 11px;font-size:12px;font-weight:800;background:#f1f5f9;color:#475569;}
.chc-erp-standard-ui .chc-pill.member{background:#dcfce7;color:#166534;}
.chc-erp-standard-ui .chc-pill.visitor{background:#dbeafe;color:#1d4ed8;}
.chc-erp-standard-ui .chc-pill.inactive{background:#fef3c7;color:#92400e;}
.chc-erp-standard-ui .chc-pill.departed{background:#fee2e2;color:#991b1b;}

/* Filter bar. */
.chc-erp-standard-ui .chc-filters{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;}
.chc-erp-standard-ui .chc-filters .chc-field{min-width:190px;flex:1;}

@media(max-width:991px){.chc-erp-standard-ui .chc-form-grid,.chc-erp-standard-ui .chc-form-grid.two{grid-template-columns:1fr;}}
</style>
