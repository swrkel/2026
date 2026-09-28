<style>
    .loan-product-page { padding: 15px 20px; }
    .loan-page-header {
        background: #fff;
        border-radius: 14px;
        padding: 20px 24px;
        margin-bottom: 18px;
        box-shadow: 0 8px 25px rgba(15, 23, 42, .08);
        border-left: 4px solid #22a6d5;
    }
    .loan-page-header h2 { margin: 0; font-weight: 700; color: #24384f; font-size: 24px; }
    .loan-page-header p { margin: 6px 0 0; color: #7b8794; }
    .loan-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; }
    .loan-erp-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 18px;
        box-shadow: 0 8px 25px rgba(15, 23, 42, .08);
    }
    .loan-erp-card-title {
        font-size: 17px;
        font-weight: 700;
        color: #17324d;
        padding-bottom: 12px;
        margin-bottom: 18px;
        border-bottom: 1px solid #e7edf3;
    }
    .loan-erp-card-title i { margin-right: 7px; }
    .loan-product-page label { font-weight: 600; color: #34465c; margin-bottom: 7px; }
    .loan-product-page .form-control {
        min-height: 42px;
        border-radius: 8px;
        border: 1px solid #dce6f1;
        box-shadow: none;
    }
    .loan-product-page textarea.form-control { min-height: 96px; }
    .loan-product-page .select2-container .select2-selection--single,
    .loan-product-page .select2-container .select2-selection--multiple {
        min-height: 42px;
        border-radius: 8px !important;
        border: 1px solid #dce6f1 !important;
        padding: 6px 10px;
    }
    .loan-action-footer {
        background: #fff;
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(15, 23, 42, .08);
        text-align: right;
    }
    .loan-action-footer .btn { border-radius: 8px; padding: 10px 16px; font-weight: 700; margin-left: 8px; }
    .loan-summary-card {
        border-radius: 12px;
        padding: 16px 18px;
        color: #fff;
        margin-bottom: 15px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
    }
    .loan-summary-card h3 { margin: 0; font-weight: 800; }
    .loan-summary-card span { font-weight: 700; opacity: .95; }
    .loan-summary-blue { background: linear-gradient(135deg, #2887d8, #1bb5d9); }
    .loan-summary-green { background: linear-gradient(135deg, #229b54, #31c76f); }
    .loan-summary-orange { background: linear-gradient(135deg, #f39c12, #ffbd42); }
    .loan-summary-red { background: linear-gradient(135deg, #dc3545, #ff5d73); }
    .loan-table-wrapper, .loan-table-wrapper .table-responsive, .loan-table-wrapper .card-body { overflow: visible !important; }
    .loan-table-wrapper .dropdown-menu { z-index: 99999 !important; min-width: 190px; }
    .loan-table-wrapper table th { text-transform: uppercase; color: #64748b; font-size: 12px; white-space: nowrap; }
    .loan-table-wrapper table td { vertical-align: middle !important; }
    .loan-badge { border-radius: 20px; padding: 6px 12px; font-weight: 700; font-size: 12px; display: inline-block; }
    .loan-badge-active { background: #55bb60; color: #fff; }
    .loan-badge-inactive { background: #9aa5b1; color: #fff; }
    .loan-repeat-row { background:#f8fafc; border:1px solid #e7edf3; border-radius:10px; padding:12px; margin-bottom:10px; }
    .loan-muted { color:#7b8794; }
    .loan-form-hint { color:#7b8794; font-size:12px; margin-top:4px; }
    .loan-inline-checks label { margin-right: 18px; font-weight: 600; }
    .loan-view-table th { width: 210px; background: #f8fafc; color:#34465c; }
    @media(max-width: 767px) {
        .loan-product-page { padding: 10px; }
        .loan-page-header h2 { font-size: 20px; }
        .loan-action-footer { text-align: left; }
        .loan-action-footer .btn { display:block; width:100%; margin:8px 0; }
        .loan-toolbar { display:block; }
        .loan-toolbar .btn { margin-top: 10px; }
    }
</style>
