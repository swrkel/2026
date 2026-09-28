<style>
.communication-hub-ui{font-family:inherit;color:#0f172a;background:#f5f8fc;padding-top:0;}
.communication-hub-ui .ch-shell{max-width:100%;}
.communication-hub-ui .ch-hero{background:linear-gradient(135deg,#ffffff 0%,#f8fbff 55%,#eef6ff 100%);border:1px solid #dbe7f3;border-radius:18px;color:#0f172a;padding:24px 26px;margin-bottom:22px;box-shadow:0 14px 35px rgba(15,23,42,.08);display:flex;align-items:center;justify-content:space-between;gap:18px;position:relative;overflow:hidden;}
.communication-hub-ui .ch-hero:before{content:"";position:absolute;left:0;top:0;height:100%;width:6px;background:linear-gradient(180deg,#2563eb,#06b6d4);}
.communication-hub-ui .ch-eyebrow{font-size:12px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;color:#2563eb;margin-bottom:6px;}
.communication-hub-ui .ch-hero h1{margin:0;font-size:28px;font-weight:800;letter-spacing:-.02em;color:#102033;}
.communication-hub-ui .ch-hero p{margin:8px 0 0;color:#64748b;font-size:13px;max-width:720px;line-height:1.55;}
.communication-hub-ui .ch-quick-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;}
.communication-hub-ui .ch-quick-actions .btn,.communication-hub-ui .btn{border-radius:10px;font-weight:700;box-shadow:0 6px 16px rgba(2,6,23,.08);border-width:1px;}
.communication-hub-ui .ch-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(170px,1fr));gap:20px;margin-bottom:22px;}
.communication-hub-ui .ch-kpi{background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%);border-radius:18px;border:1px solid #dbe7f3;padding:18px 18px 15px;min-height:158px;box-shadow:0 14px 30px rgba(15,23,42,.08);position:relative;overflow:hidden;transition:.18s ease;}
.communication-hub-ui .ch-kpi:hover{transform:translateY(-2px);box-shadow:0 18px 42px rgba(15,23,42,.12);}
.communication-hub-ui .ch-kpi:before{content:"";position:absolute;left:0;right:0;bottom:0;height:3px;background:#2563eb;}
.communication-hub-ui .ch-kpi .ch-kpi-top{display:flex;align-items:center;gap:12px;}
.communication-hub-ui .ch-kpi .ch-icon{width:52px;height:52px;border-radius:15px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;background:linear-gradient(135deg,#2563eb,#38bdf8);box-shadow:0 10px 20px rgba(37,99,235,.22);}
.communication-hub-ui .ch-kpi .label-text{font-size:13px;color:#334155;font-weight:800;line-height:1.2;}
.communication-hub-ui .ch-kpi .value{font-size:30px;font-weight:900;color:#0f172a;margin-top:14px;line-height:1;letter-spacing:-.02em;}
.communication-hub-ui .ch-kpi .hint{font-size:12px;color:#64748b;margin-top:8px;}
.communication-hub-ui .ch-kpi .spark{height:22px;margin-top:12px;border-radius:12px;background:linear-gradient(90deg,rgba(37,99,235,.08),rgba(37,99,235,.20),rgba(37,99,235,.08));position:relative;overflow:hidden;}
.communication-hub-ui .ch-kpi .spark:after{content:"";position:absolute;left:10%;right:10%;top:11px;border-top:2px solid rgba(37,99,235,.75);transform:skewY(-7deg);}
.communication-hub-ui .ch-kpi.success:before{background:#16a34a}.communication-hub-ui .ch-kpi.success .ch-icon{background:linear-gradient(135deg,#16a34a,#86efac)}.communication-hub-ui .ch-kpi.success .spark{background:linear-gradient(90deg,rgba(22,163,74,.08),rgba(22,163,74,.20),rgba(22,163,74,.08))}.communication-hub-ui .ch-kpi.success .spark:after{border-color:rgba(22,163,74,.75)}
.communication-hub-ui .ch-kpi.warning:before{background:#f59e0b}.communication-hub-ui .ch-kpi.warning .ch-icon{background:linear-gradient(135deg,#f59e0b,#fde68a)}.communication-hub-ui .ch-kpi.warning .spark{background:linear-gradient(90deg,rgba(245,158,11,.08),rgba(245,158,11,.22),rgba(245,158,11,.08))}.communication-hub-ui .ch-kpi.warning .spark:after{border-color:rgba(245,158,11,.78)}
.communication-hub-ui .ch-kpi.danger:before{background:#ef4444}.communication-hub-ui .ch-kpi.danger .ch-icon{background:linear-gradient(135deg,#ef4444,#fca5a5)}.communication-hub-ui .ch-kpi.danger .spark{background:linear-gradient(90deg,rgba(239,68,68,.08),rgba(239,68,68,.20),rgba(239,68,68,.08))}.communication-hub-ui .ch-kpi.danger .spark:after{border-color:rgba(239,68,68,.78)}
.communication-hub-ui .ch-kpi.purple:before{background:#7c3aed}.communication-hub-ui .ch-kpi.purple .ch-icon{background:linear-gradient(135deg,#7c3aed,#c084fc)}.communication-hub-ui .ch-kpi.cyan:before{background:#0891b2}.communication-hub-ui .ch-kpi.cyan .ch-icon{background:linear-gradient(135deg,#0891b2,#67e8f9)}
.communication-hub-ui .ch-card{background:#fff;border:1px solid #dbe7f3;border-radius:18px;box-shadow:0 14px 30px rgba(15,23,42,.075);margin-bottom:20px;overflow:hidden;}
.communication-hub-ui .ch-card-header{padding:18px 20px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(180deg,#fff,#fbfdff);}
.communication-hub-ui .ch-card-title{font-size:17px;font-weight:850;margin:0;color:#0f172a;letter-spacing:-.01em;}
.communication-hub-ui .ch-card-subtitle{font-size:12px;color:#64748b;margin-top:4px;}
.communication-hub-ui .ch-card-body{padding:20px;}
.communication-hub-ui .ch-toolbar{background:#fff;border:1px solid #dbe7f3;border-radius:16px;padding:14px 16px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;box-shadow:0 10px 24px rgba(15,23,42,.06);}
.communication-hub-ui .ch-filter-pill{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:9px 12px;color:#334155;font-weight:700;display:inline-flex;align-items:center;gap:8px;}
.communication-hub-ui .form-control,.communication-hub-ui select{border-radius:10px;border-color:#dbe7f3;box-shadow:none;min-height:40px;}
.communication-hub-ui table.table{margin-bottom:0;background:#fff;}
.communication-hub-ui table.table thead th{background:#f8fafc;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #dbe7f3!important;white-space:nowrap;font-weight:800;}
.communication-hub-ui table.table td{vertical-align:middle!important;color:#26354a;border-color:#eef2f7!important;}
.communication-hub-ui .label,.communication-hub-ui .badge{border-radius:999px;padding:6px 10px;font-weight:800;}
.communication-hub-ui .empty-state{padding:34px;text-align:center;color:#64748b;background:linear-gradient(180deg,#fbfdff,#f8fafc);border:1px dashed #cbd5e1;border-radius:16px;}
.communication-hub-ui .ch-two-col{display:grid;grid-template-columns:2fr 1.2fr;gap:20px;margin-bottom:20px;}
.communication-hub-ui .ch-three-col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;}
.communication-hub-ui .ch-provider-row,.communication-hub-ui .ch-summary-row,.communication-hub-ui .ch-list-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 0;border-bottom:1px solid #eef2f7;}
.communication-hub-ui .ch-provider-row:last-child,.communication-hub-ui .ch-summary-row:last-child,.communication-hub-ui .ch-list-row:last-child{border-bottom:0;}
.communication-hub-ui .ch-avatar{width:38px;height:38px;border-radius:12px;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center;font-weight:900;}
.communication-hub-ui .ch-badge-soft{border-radius:999px;padding:6px 10px;background:#dcfce7;color:#166534;font-weight:800;font-size:12px;}
.communication-hub-ui .ch-badge-soft.warning{background:#fef3c7;color:#92400e}.communication-hub-ui .ch-badge-soft.danger{background:#fee2e2;color:#991b1b}.communication-hub-ui .ch-badge-soft.info{background:#dbeafe;color:#1d4ed8}
.communication-hub-ui .box,.communication-hub-ui .info-box,.communication-hub-ui .small-box{border-radius:16px!important;border:1px solid #dbe7f3;box-shadow:0 14px 30px rgba(15,23,42,.075)!important;overflow:hidden;}
.communication-hub-ui .box-header{padding:16px 18px;border-bottom:1px solid #edf2f7!important;}.communication-hub-ui .box-title{font-weight:800;color:#0f172a;}
@media(max-width:1199px){.communication-hub-ui .ch-kpi-grid{grid-template-columns:repeat(2,minmax(220px,1fr));}.communication-hub-ui .ch-two-col{grid-template-columns:1fr;}.communication-hub-ui .ch-three-col{grid-template-columns:1fr;}}
@media(max-width:767px){.communication-hub-ui .ch-hero{display:block}.communication-hub-ui .ch-quick-actions{justify-content:flex-start;margin-top:14px}.communication-hub-ui .ch-kpi-grid{grid-template-columns:1fr}.communication-hub-ui .ch-toolbar{display:block}.communication-hub-ui .ch-toolbar .pull-right{float:none!important;margin-top:10px}}

/* CH UI V5 ERP Dashboard Standard spacing and prominent card polish */
.communication-hub-ui .ch-shell{padding:0 22px 24px;}
.communication-hub-ui .ch-kpi-grid{grid-template-columns:repeat(4,minmax(180px,1fr));gap:22px;margin-bottom:26px;}
.communication-hub-ui .ch-kpi{margin-bottom:0;border-color:#cfe0f3;box-shadow:0 16px 36px rgba(15,23,42,.10);}
.communication-hub-ui .row>[class*="col-"]{padding-left:13px;padding-right:13px;}
.communication-hub-ui .row>[class*="col-"]>.ch-kpi{margin-bottom:24px;}
.communication-hub-ui .ch-card{border-color:#cfe0f3;box-shadow:0 16px 36px rgba(15,23,42,.09);}
.communication-hub-ui .ch-card + .ch-card{margin-top:22px;}
.communication-hub-ui .ch-toolbar{margin-top:4px;margin-bottom:22px;}
.communication-hub-ui .ch-actions-strip{background:#fff;border:1px solid #dbe7f3;border-radius:18px;padding:18px 22px;margin:24px 0;box-shadow:0 14px 30px rgba(15,23,42,.07);display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;}
.communication-hub-ui .ch-actions-strip .btn{min-width:140px;padding:10px 16px;}
.communication-hub-ui .ch-panel-gap{margin-top:24px;}
.communication-hub-ui .ch-page-note{font-size:13px;color:#64748b;line-height:1.55;}
@media(max-width:1199px){.communication-hub-ui .ch-kpi-grid{grid-template-columns:repeat(2,minmax(220px,1fr));}}
@media(min-width:1200px){.communication-hub-ui .ch-kpi-grid{grid-template-columns:repeat(4,minmax(180px,1fr));}}
@media(max-width:767px){.communication-hub-ui .ch-shell{padding:0 10px 18px}.communication-hub-ui .ch-kpi-grid{grid-template-columns:1fr;gap:16px}.communication-hub-ui .ch-actions-strip{display:block}.communication-hub-ui .ch-actions-strip .btn{width:100%;margin-top:8px}}

/* ERP Dashboard Standard v1.0 */
.communication-hub-ui .ch-kpi-link{display:block;color:inherit;text-decoration:none;}
.communication-hub-ui .ch-kpi-link:hover,.communication-hub-ui .ch-kpi-link:focus{color:inherit;text-decoration:none;}
.communication-hub-ui .ch-standard-grid{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:22px;margin-bottom:26px;}
.communication-hub-ui .ch-kpi .hint{min-height:18px;}
.communication-hub-ui .ch-kpi .spark{height:20px;}
@media(max-width:1199px){.communication-hub-ui .ch-standard-grid{grid-template-columns:repeat(2,minmax(220px,1fr));}}
@media(max-width:767px){.communication-hub-ui .ch-standard-grid{grid-template-columns:1fr;}}


/* CH RC1 - ERP Dashboard Standard polishing */
.communication-hub-ui .ch-drill{float:right;font-size:11px;color:#2563eb;font-weight:900;}
.communication-hub-ui .ch-dashboard-panels{display:grid;grid-template-columns:1.35fr 1fr 1fr;gap:22px;margin-top:22px;margin-bottom:22px;}
.communication-hub-ui .ch-trend-box{height:238px;border:1px solid #eef2f7;border-radius:16px;background:repeating-linear-gradient(to bottom,#fff 0,#fff 31px,#eef2f7 32px);position:relative;overflow:hidden;}
.communication-hub-ui .ch-trend-line{position:absolute;left:8%;right:7%;border-top:3px solid #2563eb;box-shadow:0 8px 18px rgba(37,99,235,.18);}
.communication-hub-ui .ch-trend-line.primary{bottom:92px;transform:skewY(-7deg);}
.communication-hub-ui .ch-trend-line.success{bottom:48px;border-color:#16a34a;box-shadow:0 8px 18px rgba(22,163,74,.18);transform:skewY(5deg);}
.communication-hub-ui .ch-chart-axis{position:absolute;bottom:12px;color:#64748b;font-size:12px;font-weight:700;}.communication-hub-ui .ch-chart-axis.left{left:8%;}.communication-hub-ui .ch-chart-axis.mid{left:45%;}.communication-hub-ui .ch-chart-axis.right{right:7%;}
.communication-hub-ui .ch-mini-standard{background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:16px;min-height:92px;}.communication-hub-ui .ch-mini-standard strong{display:block;color:#0f172a;margin-bottom:8px;}.communication-hub-ui .ch-mini-standard span{color:#64748b;font-size:13px;line-height:1.45;}
.communication-hub-ui .ch-kpi-link .ch-kpi{height:100%;}.communication-hub-ui .ch-kpi-link:focus .ch-kpi{outline:2px solid rgba(37,99,235,.35);outline-offset:2px;}
@media(max-width:1199px){.communication-hub-ui .ch-dashboard-panels{grid-template-columns:1fr;}}

</style>
<style id="pos-s364-system-ui-bridge">
/* POS S364: use the actual ERP/Communication Hub visual standard, not a custom standalone shell. */
.pos-erp-standard-ui{font-family:inherit!important;background:#f5f8fc!important;color:#0f172a!important;}
.pos-erp-standard-ui .ch-shell{padding:0 22px 24px!important;}
.pos-erp-standard-ui a{text-decoration:none;}
.pos-erp-standard-ui .pos-module-hero .btn{font-family:inherit!important;}
.pos-erp-standard-ui .ch-kpi .value.pos-value-blue{color:#2563eb;}
.pos-erp-standard-ui .ch-kpi.success .value{color:#16a34a;}
.pos-erp-standard-ui .ch-kpi.warning .value{color:#f97316;}
.pos-erp-standard-ui .ch-kpi.purple .value{color:#7c3aed;}
.pos-erp-standard-ui .pos-dashboard-panels{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;}
.pos-erp-standard-ui .pos-actions-list{display:grid;grid-template-columns:1fr;gap:12px;}
.pos-erp-standard-ui .pos-action-tile{border:1px solid #dbe7f3;border-radius:14px;background:#f8fbff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;color:#0f172a;font-weight:800;transition:.16s ease;}
.pos-erp-standard-ui .pos-action-tile:hover{background:#eef6ff;color:#2563eb;box-shadow:0 10px 22px rgba(15,23,42,.08);}
.pos-erp-standard-ui .pos-action-tile .left{display:flex;align-items:center;gap:12px;}
.pos-erp-standard-ui .pos-action-tile .tile-icon{width:34px;height:34px;border-radius:10px;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;}
.pos-erp-standard-ui .pos-action-tile.success{background:#effaf3;border-color:#ccefd9;color:#166534}.pos-erp-standard-ui .pos-action-tile.success .tile-icon{background:#dcfce7;color:#16a34a}
.pos-erp-standard-ui .pos-action-tile.warning{background:#fff7ed;border-color:#fed7aa;color:#9a3412}.pos-erp-standard-ui .pos-action-tile.warning .tile-icon{background:#ffedd5;color:#f97316}
.pos-erp-standard-ui .pos-quick-operations{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px;}
.pos-erp-standard-ui .pos-quick-operation{border:1px solid #dbe7f3;border-radius:12px;background:#fff;padding:12px 14px;display:flex;align-items:center;gap:10px;color:#0f172a;min-height:64px;box-shadow:0 8px 18px rgba(15,23,42,.04);}
.pos-erp-standard-ui .pos-quick-operation:hover{box-shadow:0 12px 24px rgba(15,23,42,.09);color:#2563eb;}
.pos-erp-standard-ui .pos-quick-operation .qo-icon{width:38px;height:38px;border-radius:10px;background:#eef6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:18px;}
.pos-erp-standard-ui .pos-quick-operation strong{display:block;font-size:13px;color:#0f172a;}.pos-erp-standard-ui .pos-quick-operation span span{display:block;font-size:12px;color:#64748b;margin-top:2px;}
.pos-erp-standard-ui .pos-standard-table,.pos-erp-standard-ui .syzygy-table{width:100%;border-collapse:collapse;background:#fff;}
.pos-erp-standard-ui .pos-standard-table thead th,.pos-erp-standard-ui .syzygy-table thead th{background:#f8fafc!important;color:#475569!important;font-size:12px!important;text-transform:uppercase!important;letter-spacing:.04em!important;border-bottom:1px solid #dbe7f3!important;white-space:nowrap!important;font-weight:800!important;padding:12px 14px!important;}
.pos-erp-standard-ui .pos-standard-table td,.pos-erp-standard-ui .syzygy-table td{vertical-align:middle!important;color:#26354a!important;border-color:#eef2f7!important;padding:12px 14px!important;}
.pos-erp-standard-ui .pos-toolbar-card,.pos-erp-standard-ui .box,.pos-erp-standard-ui .syzygy-panel{background:#fff;border:1px solid #dbe7f3!important;border-radius:18px!important;box-shadow:0 14px 30px rgba(15,23,42,.075)!important;overflow:hidden;margin-bottom:20px;}
.pos-erp-standard-ui .box-header,.pos-erp-standard-ui .syzygy-panel-header{padding:18px 20px!important;border-bottom:1px solid #edf2f7!important;background:linear-gradient(180deg,#fff,#fbfdff)!important;display:flex;align-items:center;justify-content:space-between;gap:12px;}
.pos-erp-standard-ui .box-title{font-size:17px!important;font-weight:850!important;margin:0!important;color:#0f172a!important;letter-spacing:-.01em;}
.pos-erp-standard-ui .box-body{padding:20px!important;}
.pos-erp-standard-ui .form-control{border-radius:10px!important;border-color:#dbe7f3!important;box-shadow:none!important;min-height:40px!important;}
.pos-erp-standard-ui .btn{border-radius:10px!important;font-weight:700!important;box-shadow:0 6px 16px rgba(2,6,23,.08);border-width:1px;}
.pos-erp-standard-ui .btn-primary{background:#2563eb!important;border-color:#2563eb!important;color:#fff!important}.pos-erp-standard-ui .btn-success{background:#16a34a!important;border-color:#16a34a!important;color:#fff!important}.pos-erp-standard-ui .btn-warning{background:#f59e0b!important;border-color:#f59e0b!important;color:#fff!important}.pos-erp-standard-ui .btn-danger{background:#ef4444!important;border-color:#ef4444!important;color:#fff!important}.pos-erp-standard-ui .btn-info{background:#0891b2!important;border-color:#0891b2!important;color:#fff!important}
.pos-erp-standard-ui .pos-kpi-row .pos-kpi-card{background:#fff;border:1px solid #dbe7f3;border-radius:16px;box-shadow:0 10px 24px rgba(15,23,42,.06);padding:16px;margin-bottom:16px;}.pos-erp-standard-ui .pos-kpi-card span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase}.pos-erp-standard-ui .pos-kpi-card strong{display:block;color:#0f172a;font-size:22px;margin-top:6px;}
.pos-erp-standard-ui .pos-large-save{padding:10px 20px!important;}
.pos-erp-standard-ui .pos-badge{display:inline-block;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:800}.pos-erp-standard-ui .pos-badge-success{background:#dcfce7;color:#166534}.pos-erp-standard-ui .pos-badge-danger{background:#fee2e2;color:#991b1b}
@media(max-width:1199px){.pos-erp-standard-ui .pos-dashboard-panels{grid-template-columns:1fr}.pos-erp-standard-ui .pos-quick-operations{grid-template-columns:repeat(3,minmax(0,1fr));}}
@media(max-width:767px){.pos-erp-standard-ui .pos-quick-operations{grid-template-columns:1fr}.pos-erp-standard-ui .ch-shell{padding:0 10px 18px!important}.pos-erp-standard-ui .box-header{display:block;}}
</style>
<style>
.pos-settings-nav{margin-bottom:18px}.pos-settings-tile{text-decoration:none!important;color:#263238;display:block;min-height:145px}.pos-settings-tile h4{font-weight:700;font-size:15px;margin:12px 0 6px}.pos-settings-tile p{font-size:12px;color:#6b7280;min-height:32px}.pos-settings-tile span{font-weight:700;color:#1b75bc}.pos-receipt-preview{max-width:360px;margin:0 auto 15px;padding:18px;border:1px dashed #cbd5e1;border-radius:14px;background:#fff;text-align:center}.pos-label-preview{max-width:300px;padding:14px;border:1px dashed #cbd5e1;border-radius:12px;background:#fff;text-align:center}.pos-fake-barcode{font-family:monospace;font-size:30px;letter-spacing:2px;margin:8px 0}.pos-check-list{margin:0 0 15px;padding:0;list-style:none}.pos-check-list li{padding:8px 0;border-bottom:1px solid #eef2f7}.pos-check-list li:before{content:'\f00c';font-family:FontAwesome;color:#10b981;margin-right:8px}.pos-settings-form .pos-card{scroll-margin-top:80px}
</style>
