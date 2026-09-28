<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Distribution Dealer')</title>
    {{-- CUSTOMERS_SOURCE_HARDENING_V1_PORTAL_CSS: module-owned portal foundation. --}}
    @php($customersPortalCss = module_path('Customers', 'Resources/assets/css/customer-portal-foundation.css'))
    <style>{!! is_file($customersPortalCss) ? file_get_contents($customersPortalCss) : '' !!}</style>
<style>
        body{background:#eef3f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;margin:0;}
        .dd-topbar{background:linear-gradient(135deg,#0b72e7,#08b4d8);color:#fff;padding:16px 22px;box-shadow:0 10px 30px rgba(15,76,129,.18);}
        .dd-brand{font-size:20px;font-weight:800;display:inline-block;}
        .dd-nav{float:right;}
        .dd-nav a{color:#fff;margin-left:14px;font-weight:600;text-decoration:none;opacity:.95;}
        .dd-nav a:hover{opacity:1;text-decoration:underline;}
        .dd-wrap{max-width:1220px;margin:24px auto;padding:0 16px;}
        .dd-card{background:#fff;border:1px solid #e5edf6;border-radius:18px;box-shadow:0 14px 34px rgba(15,23,42,.08);margin-bottom:18px;overflow:hidden;}
        .dd-card-header{padding:16px 20px;border-bottom:1px solid #e5edf6;background:#fbfdff;}
        .dd-card-title{font-size:18px;font-weight:800;margin:0;color:#0f172a;}
        .dd-card-body{padding:18px 20px;}
        .dd-summary{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:18px;}
        .dd-summary-item{background:#fff;border:1px solid #e5edf6;border-radius:16px;padding:16px;box-shadow:0 10px 24px rgba(15,23,42,.06);}
        .dd-summary-label{font-size:12px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:7px;}
        .dd-summary-value{font-size:22px;font-weight:900;color:#0f172a;}
        .dd-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
        .dd-table{width:100%;min-width:980px;border-collapse:collapse;background:#fff;}
        .dd-table th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;border:1px solid #e5edf6;padding:10px;white-space:nowrap;}
        .dd-table td{border:1px solid #e5edf6;padding:10px;vertical-align:middle;}
        .text-right{text-align:right!important;}.text-center{text-align:center!important;}
        .dd-badge{display:inline-block;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:800;}
        .dd-aging{display:inline-block;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:800;}
        .dd-aging-good{background:#dcfce7;color:#166534;}
        .dd-aging-warning{background:#ffedd5;color:#9a3412;}
        .dd-aging-danger{background:#fee2e2;color:#991b1b;}
        .dd-badge-due{background:#f59e0b;color:#fff;}.dd-badge-paid{background:#22c55e;color:#fff;}.dd-badge-open{background:#3b82f6;color:#fff;}
        .dd-badge-unread{background:#ef4444;color:#fff;}.dd-badge-read{background:#64748b;color:#fff;}
        .dd-list-item{border:1px solid #e5edf6;border-radius:14px;padding:14px;margin-bottom:12px;background:#fff;}
        .dd-list-title{font-size:16px;font-weight:800;color:#0f172a;margin:0 0 6px 0;}
        .dd-list-meta{font-size:12px;color:#64748b;font-weight:700;margin-bottom:8px;}
        .dd-empty{border:1px dashed #cbd5e1;border-radius:14px;padding:24px;text-align:center;color:#64748b;background:#f8fafc;font-weight:700;}
        .dd-btn{display:inline-block;border-radius:10px;padding:9px 14px;font-weight:800;text-decoration:none;border:0;cursor:pointer;}
        .dd-btn-primary{background:#2563eb;color:#fff;}.dd-btn-default{background:#e5edf6;color:#0f172a;}.dd-btn-danger{background:#ef4444;color:#fff;}
        .dd-btn:hover{text-decoration:none;filter:brightness(.97);}
        .dd-filter{display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:16px;}
        .dd-filter .form-group{margin-bottom:0;}
        .dd-filter input{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:6px 10px;}
        .dd-login-shell{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:linear-gradient(135deg,#eef6ff,#f8fbff);}
        .dd-login-card{max-width:520px;width:100%;background:#fff;border-radius:22px;box-shadow:0 24px 60px rgba(15,76,129,.22);overflow:hidden;}
        .dd-login-head{background:linear-gradient(135deg,#0b72e7,#08b4d8);color:#fff;text-align:center;padding:30px 22px;}
        .dd-login-body{padding:30px;}
        .dd-passcode{font-size:28px!important;letter-spacing:12px;font-weight:900;text-align:center;height:55px!important;}
        @media(max-width:1100px){.dd-summary{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:900px){.dd-summary{grid-template-columns:repeat(2,1fr)}.dd-nav{float:none;margin-top:10px}.dd-nav a{display:inline-block;margin:0 10px 8px 0}}
        @media(max-width:520px){.dd-summary{grid-template-columns:1fr}.dd-wrap{margin:14px auto}.dd-card-body{padding:14px}.dd-card-header{padding:14px}}
        @media print{.dd-topbar,.dd-filter,.dd-no-print{display:none!important}.dd-wrap{max-width:none;margin:0;padding:0}.dd-card{box-shadow:none;border:0}.dd-table{min-width:0;font-size:11px}}
    </style>
    @yield('style')
</head>
<body>
@yield('body')
</body>
</html>
