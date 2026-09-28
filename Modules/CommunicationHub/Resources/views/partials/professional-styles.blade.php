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


/* Reseller Dashboard - POS-style operation cards */
.communication-hub-ui .ch-reseller-toolbar-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap;}
.communication-hub-ui .ch-reseller-overview-grid{display:grid;grid-template-columns:repeat(4,minmax(190px,1fr));gap:18px;margin-bottom:22px;}
.communication-hub-ui .ch-reseller-overview-card{position:relative;display:flex;align-items:center;gap:14px;min-height:118px;padding:18px;background:#fff;border:1px solid #dbe7f3;border-radius:18px;box-shadow:0 14px 30px rgba(15,23,42,.075);overflow:hidden;}
.communication-hub-ui .ch-reseller-overview-card:after{content:"";position:absolute;left:0;right:0;bottom:0;height:4px;background:#2563eb;}
.communication-hub-ui .ch-reseller-overview-icon{width:52px;height:52px;border-radius:16px;display:flex;align-items:center;justify-content:center;flex:0 0 52px;color:#fff;font-size:21px;background:linear-gradient(135deg,#2563eb,#38bdf8);box-shadow:0 10px 20px rgba(37,99,235,.22);}
.communication-hub-ui .ch-reseller-overview-copy{min-width:0;display:flex;flex-direction:column;}
.communication-hub-ui .ch-reseller-overview-copy>span{font-size:12px;color:#64748b;font-weight:800;text-transform:uppercase;letter-spacing:.035em;}
.communication-hub-ui .ch-reseller-overview-copy>strong{font-size:23px;line-height:1.2;color:#0f172a;font-weight:900;margin-top:5px;overflow-wrap:anywhere;}
.communication-hub-ui .ch-reseller-overview-copy>small{font-size:12px;color:#64748b;margin-top:4px;line-height:1.35;}
.communication-hub-ui .ch-reseller-overview-card--success:after{background:#16a34a}.communication-hub-ui .ch-reseller-overview-card--success .ch-reseller-overview-icon{background:linear-gradient(135deg,#16a34a,#4ade80);box-shadow:0 10px 20px rgba(22,163,74,.20)}
.communication-hub-ui .ch-reseller-overview-card--warning:after{background:#f59e0b}.communication-hub-ui .ch-reseller-overview-card--warning .ch-reseller-overview-icon{background:linear-gradient(135deg,#f59e0b,#facc15);box-shadow:0 10px 20px rgba(245,158,11,.22)}
.communication-hub-ui .ch-reseller-overview-card--purple:after{background:#7c3aed}.communication-hub-ui .ch-reseller-overview-card--purple .ch-reseller-overview-icon{background:linear-gradient(135deg,#7c3aed,#a855f7);box-shadow:0 10px 20px rgba(124,58,237,.22)}
.communication-hub-ui .ch-pos-launchpad{overflow:visible;}
.communication-hub-ui .ch-pos-action-grid{display:grid;grid-template-columns:repeat(4,minmax(190px,1fr));gap:20px;}
.communication-hub-ui .ch-pos-action-card{--ch-pos-accent:#2563eb;--ch-pos-accent-light:#eff6ff;position:relative;display:flex;flex-direction:column;min-height:210px;padding:22px 20px 18px;border:1px solid #dbe7f3;border-radius:18px;background:linear-gradient(180deg,#fff 0%,#fbfdff 100%);color:#0f172a;text-decoration:none!important;box-shadow:0 12px 28px rgba(15,23,42,.08);overflow:hidden;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;}
.communication-hub-ui .ch-pos-action-card:before{content:"";position:absolute;left:0;top:0;width:100%;height:5px;background:var(--ch-pos-accent);}
.communication-hub-ui .ch-pos-action-card:after{content:"";position:absolute;right:-45px;top:-48px;width:130px;height:130px;border-radius:50%;background:var(--ch-pos-accent-light);opacity:.82;}
.communication-hub-ui .ch-pos-action-card:hover,.communication-hub-ui .ch-pos-action-card:focus{color:#0f172a;text-decoration:none!important;transform:translateY(-4px);border-color:var(--ch-pos-accent);box-shadow:0 20px 42px rgba(15,23,42,.14);outline:0;}
.communication-hub-ui .ch-pos-action-card:focus-visible{box-shadow:0 0 0 3px rgba(37,99,235,.20),0 20px 42px rgba(15,23,42,.14);}
.communication-hub-ui .ch-pos-action-icon{position:relative;z-index:1;width:62px;height:62px;border-radius:18px;display:flex;align-items:center;justify-content:center;background:var(--ch-pos-accent);color:#fff;font-size:25px;box-shadow:0 12px 24px rgba(15,23,42,.18);}
.communication-hub-ui .ch-pos-action-copy{position:relative;z-index:1;display:flex;flex-direction:column;flex:1;margin-top:18px;}
.communication-hub-ui .ch-pos-action-copy>strong{font-size:17px;line-height:1.25;font-weight:900;color:#102033;}
.communication-hub-ui .ch-pos-action-copy>small{font-size:12px;line-height:1.55;color:#64748b;margin-top:7px;}
.communication-hub-ui .ch-pos-action-footer{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:18px;padding-top:13px;border-top:1px solid #e8eef5;color:var(--ch-pos-accent);font-size:12px;font-weight:850;}
.communication-hub-ui .ch-pos-action-footer .fa{transition:transform .18s ease;}
.communication-hub-ui .ch-pos-action-card:hover .ch-pos-action-footer .fa,.communication-hub-ui .ch-pos-action-card:focus .ch-pos-action-footer .fa{transform:translateX(4px);}
.communication-hub-ui .ch-pos-action-card--success{--ch-pos-accent:#16a34a;--ch-pos-accent-light:#ecfdf5;}
.communication-hub-ui .ch-pos-action-card--purple{--ch-pos-accent:#7c3aed;--ch-pos-accent-light:#f5f3ff;}
.communication-hub-ui .ch-pos-action-card--warning{--ch-pos-accent:#d97706;--ch-pos-accent-light:#fffbeb;}
.communication-hub-ui .ch-pos-action-card--cyan{--ch-pos-accent:#0891b2;--ch-pos-accent-light:#ecfeff;}
.communication-hub-ui .ch-pos-action-card--teal{--ch-pos-accent:#0f766e;--ch-pos-accent-light:#f0fdfa;}
.communication-hub-ui .ch-pos-action-card--danger{--ch-pos-accent:#dc2626;--ch-pos-accent-light:#fef2f2;}
.communication-hub-ui .ch-pos-action-card--slate{--ch-pos-accent:#475569;--ch-pos-accent-light:#f8fafc;}
@media(max-width:1199px){.communication-hub-ui .ch-reseller-overview-grid,.communication-hub-ui .ch-pos-action-grid{grid-template-columns:repeat(2,minmax(220px,1fr));}}
@media(max-width:767px){.communication-hub-ui .ch-reseller-toolbar-actions{justify-content:flex-start;margin-top:10px}.communication-hub-ui .ch-reseller-overview-grid,.communication-hub-ui .ch-pos-action-grid{grid-template-columns:1fr}.communication-hub-ui .ch-pos-action-card{min-height:190px}.communication-hub-ui .ch-reseller-overview-card{min-height:108px}}

</style>
