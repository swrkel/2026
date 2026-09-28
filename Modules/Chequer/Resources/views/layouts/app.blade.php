@extends('layouts.app')

@section('title')
    @yield('title', 'Chequer Module')
@endsection

@section('css')
    @parent
    <style>
        :root {
            --cheq-blue: #2563eb;
            --cheq-blue-2: #38bdf8;
            --cheq-dark: #0f172a;
            --cheq-text: #111827;
            --cheq-muted: #64748b;
            --cheq-border: #dbe7f5;
            --cheq-soft: #f4f8ff;
            --cheq-green: #16a34a;
            --cheq-orange: #f59e0b;
            --cheq-red: #ef4444;
            --cheq-purple: #8b5cf6;
            --cheq-cyan: #06b6d4;
        }
        .chequer-module-v1 {
            font-family: 'Inter', Arial, Helvetica, sans-serif;
            color: var(--cheq-text);
            padding: 18px 20px 35px;
        }
        .cheq-system-shell {
            max-width: 100%;
            margin: 0 auto;
        }
        .cheq-module-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            background: #fff;
            border: 1px solid var(--cheq-border);
            border-radius: 22px;
            padding: 14px 16px;
            margin-bottom: 18px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
        }
        .cheq-module-nav a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 9px 15px;
            border-radius: 14px;
            color: #334155;
            font-size: 15px;
            font-weight: 800;
            text-decoration: none;
            transition: .18s ease;
            border: 1px solid transparent;
        }
        .cheq-module-nav a:hover,
        .cheq-module-nav a.active {
            color: #155eef;
            background: linear-gradient(135deg, #eef6ff 0%, #ffffff 100%);
            border-color: #cfe1ff;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .10);
            text-decoration: none;
        }
        .cheq-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            background: linear-gradient(135deg, #f7fbff 0%, #eef6ff 100%);
            border: 1px solid #cfe0f6;
            border-left: 6px solid var(--cheq-blue);
            border-radius: 22px;
            padding: 24px 26px;
            margin-bottom: 20px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .08);
        }
        .cheq-title {
            font-size: 28px;
            line-height: 1.2;
            font-weight: 900;
            letter-spacing: -.02em;
            color: #06152b;
        }
        .cheq-sub {
            color: var(--cheq-muted);
            font-size: 15px;
            font-weight: 500;
            margin-top: 7px;
        }
        .cheq-card {
            background: #fff;
            border: 1px solid var(--cheq-border);
            border-radius: 22px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
            padding: 22px;
            margin-bottom: 20px;
        }
        .cheq-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(190px, 1fr));
            gap: 18px;
            margin-bottom: 20px;
        }
        .cheq-stat {
            position: relative;
            overflow: hidden;
            background: #fff;
            min-height: 150px;
            border: 1px solid var(--cheq-border);
            border-radius: 22px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
            padding: 24px 22px 20px;
        }
        .cheq-stat:before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--cheq-blue), var(--cheq-blue-2));
        }
        .cheq-stat .n {
            font-size: 32px;
            line-height: 1;
            font-weight: 900;
            color: #06152b;
            margin-top: 32px;
        }
        .cheq-stat .l {
            color: #52627a;
            margin-top: 10px;
            font-weight: 700;
        }

        .cheq-list-tools {
            display: grid;
            grid-template-columns: minmax(520px, auto) auto;
            gap: 18px 20px;
            align-items: center;
            background: #fff;
            border: 1px solid var(--cheq-border);
            border-radius: 22px;
            padding: 18px 20px;
            margin-bottom: 18px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
        }
        .cheq-export-group { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
        .cheq-entry-group { display:flex; gap:10px; align-items:center; justify-content:flex-end; font-size:15px; font-weight:800; color:#172033; }
        .cheq-search-form { grid-column: 1 / -1; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .cheq-search-input {
            width: min(420px, 100%);
            min-height: 48px;
            border: 1px solid #d6e3f2;
            border-radius: 16px;
            padding: 11px 16px;
            font-size: 15px;
            box-shadow: 0 10px 22px rgba(15,23,42,.04);
        }
        .cheq-mini-select {
            min-width: 96px;
            min-height: 48px;
            border: 1px solid #d6e3f2;
            border-radius: 16px;
            padding: 8px 14px;
            background:#fff;
            font-size:15px;
            font-weight:800;
        }
        .cheq-tool-btn {
            display:inline-flex;
            align-items:center;
            gap:8px;
            border:0;
            color:#fff;
            border-radius:14px;
            min-height:48px;
            padding:12px 18px;
            font-weight:900;
            font-size:15px;
            box-shadow:0 12px 24px rgba(15,23,42,.12);
            white-space:nowrap;
        }
        .cheq-tool-btn.purple{background:linear-gradient(135deg,#5b39f6,#7c3aed);}
        .cheq-tool-btn.teal{background:linear-gradient(135deg,#008c95,#06b6d4);}
        .cheq-tool-btn.green{background:linear-gradient(135deg,#16a34a,#22c55e);}
        .cheq-tool-btn.orange{background:linear-gradient(135deg,#ef4423,#fb923c);}
        .cheq-tool-btn.dark{background:linear-gradient(135deg,#0f172a,#1e293b);}
        .cheq-table .w-plus { width:46px; }
        .cheq-table .w-small { width:90px; }
        .cheq-table .w-medium { width:140px; }
        .cheq-table .w-action { width:155px; }
        .cheq-status-active,.cheq-status-available,.cheq-status-draft { background:#dcfce7;color:#166534; }
        .cheq-status-printed,.cheq-status-issued { background:#dbeafe;color:#1d4ed8; }
        .cheq-status-completed { background:#f3e8ff;color:#6b21a8; }
        .cheq-status-cancelled,.cheq-status-void,.cheq-status-deleted { background:#fee2e2;color:#991b1b; }

        .cheq-toolbar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .cheq-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 13px;
            padding: 12px 18px;
            min-height: 46px;
            font-weight: 900;
            font-size: 15px;
            text-decoration: none;
            color: #fff !important;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            box-shadow: 0 10px 22px rgba(37,99,235,.20);
            cursor: pointer;
            white-space: nowrap;
        }
        .cheq-btn:hover { filter: brightness(.98); text-decoration: none; color:#fff; }
        .cheq-btn.green { background: linear-gradient(135deg, #16a34a, #22c55e); box-shadow: 0 10px 22px rgba(22,163,74,.18); }
        .cheq-btn.orange { background: linear-gradient(135deg, #f59e0b, #f97316); box-shadow: 0 10px 22px rgba(245,158,11,.20); }
        .cheq-btn.red { background: linear-gradient(135deg, #ef4444, #f87171); box-shadow: 0 10px 22px rgba(239,68,68,.18); }
        .cheq-btn.purple { background: linear-gradient(135deg, #7c3aed, #a855f7); box-shadow: 0 10px 22px rgba(124,58,237,.18); }
        .cheq-btn.gray { background: linear-gradient(135deg, #64748b, #94a3b8); box-shadow: 0 10px 22px rgba(100,116,139,.18); }
        .cheq-filter-row {
            display: grid;
            grid-template-columns: repeat(5, minmax(145px, 1fr));
            gap: 12px;
            align-items: end;
        }
        .cheq-table-wrap {
            overflow-x: auto;
            width: 100%;
            border: 1px solid var(--cheq-border);
            border-radius: 18px;
            background: #fff;
            -webkit-overflow-scrolling: touch;
        }
        .cheq-table {
            width: 100%;
            min-width: 980px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }
        .cheq-table th {
            background: #f3f7ff;
            color: #506985;
            text-align: center;
            font-size: 13px;
            line-height: 1.25;
            font-weight: 900;
            padding: 14px 12px;
            border-bottom: 1px solid var(--cheq-border);
            white-space: normal;
            vertical-align: middle;
        }
        .cheq-table td {
            padding: 12px 12px;
            border-bottom: 1px solid #edf3fb;
            vertical-align: middle;
            font-size: 14px;
            color: #142033;
            word-break: normal;
        }
        .cheq-table tbody tr:hover { background: #f8fbff; }
        .cheq-table .amount,
        .cheq-table .text-right { text-align: right !important; font-variant-numeric: tabular-nums; }
        .cheq-table .text-center { text-align: center !important; }
        .cheq-actions { white-space: nowrap; text-align: center; }
        .cheq-form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(190px, 1fr));
            gap: 16px;
        }
        .cheq-field label {
            display: block;
            font-weight: 800;
            color: #233653;
            margin-bottom: 7px;
        }
        .cheq-input,
        .cheq-select,
        .cheq-textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d6e3f2;
            border-radius: 13px;
            padding: 11px 13px;
            min-height: 46px;
            background: #fff;
            color: #172033;
            box-shadow: inset 0 1px 0 rgba(15,23,42,.02);
        }
        .cheq-textarea { min-height: 96px; }
        .cheq-input[readonly],
        .cheq-input:disabled {
            background: #eef2f7;
            color: #475569;
            cursor: not-allowed;
        }
        .cheq-alert {
            padding: 13px 16px;
            border-radius: 14px;
            background: #ecfdf5;
            color: #065f46;
            margin-bottom: 16px;
            border: 1px solid #a7f3d0;
            font-weight: 700;
        }
        .cheq-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: 5px 11px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 900;
            font-size: 12px;
        }
        .cheq-action-menu {
            position: relative;
            display: inline-block;
        }
        .cheq-action-toggle {
            min-width: 130px;
            min-height: 42px;
            border-radius: 12px;
            border: 0;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: #fff;
            font-weight: 900;
            padding: 10px 14px;
        }
        .cheq-action-items {
            display: none;
            position: absolute;
            right: 0;
            top: 45px;
            width: 205px;
            background: #fff;
            border-radius: 14px;
            border: 1px solid #dbe7f5;
            box-shadow: 0 18px 40px rgba(15,23,42,.18);
            padding: 9px;
            z-index: 50;
        }
        .cheq-action-menu:hover .cheq-action-items { display: block; }
        .cheq-action-items a,
        .cheq-action-items button {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            width: 100%;
            min-height: 42px;
            border: 0;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 7px;
            color: #fff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 900;
            text-align: left;
        }
        .cheq-action-items a:last-child,
        .cheq-action-items button:last-child { margin-bottom: 0; }
        .cheq-action-edit { background: linear-gradient(135deg, #7c3aed, #a855f7); }
        .cheq-action-print { background: linear-gradient(135deg, #0891b2, #06b6d4); }
        .cheq-action-delete { background: linear-gradient(135deg, #ef4444, #f87171); }
        .cheq-action-default { background: linear-gradient(135deg, #f59e0b, #f97316); }

        .cheq-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: #fff;
            font-size: 22px;
            box-shadow: 0 14px 26px rgba(37,99,235,.18);
        }
        .cheq-stat-green .cheq-stat-icon { background: linear-gradient(135deg, #16a34a, #4ade80); }
        .cheq-stat-orange .cheq-stat-icon { background: linear-gradient(135deg, #f59e0b, #facc15); }
        .cheq-stat-red .cheq-stat-icon { background: linear-gradient(135deg, #ef4444, #fb7185); }
        .cheq-stat-purple .cheq-stat-icon { background: linear-gradient(135deg, #7c3aed, #a855f7); }
        .cheq-stat-cyan .cheq-stat-icon { background: linear-gradient(135deg, #0891b2, #22d3ee); }
        .cheq-stat-green:before { background: linear-gradient(90deg, #16a34a, #4ade80); }
        .cheq-stat-orange:before { background: linear-gradient(90deg, #f59e0b, #facc15); }
        .cheq-stat-red:before { background: linear-gradient(90deg, #ef4444, #fb7185); }
        .cheq-stat-purple:before { background: linear-gradient(90deg, #7c3aed, #a855f7); }
        .cheq-stat-cyan:before { background: linear-gradient(90deg, #0891b2, #22d3ee); }
        .cheq-print {
            max-width: 780px;
            margin: 30px auto;
            background: #fff;
            padding: 38px;
            border: 1px solid #ddd;
        }
        .cheq-print-row { display:flex; justify-content:space-between; margin-bottom:18px; }
        .cheq-print-payee { font-size:18px; font-weight:700; border-bottom:1px solid #333; padding-bottom:8px; margin-bottom:15px; }
        .cheq-print-amount { font-size:22px; font-weight:800; text-align:right; }
        .text-muted { color: var(--cheq-muted) !important; }

        .cheq-eyebrow {
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--cheq-blue);
            font-weight: 900;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .cheq-header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
        }
        .cheq-page-header-v2 { margin-bottom: 16px; }
        .cheq-stat-head { display:flex; align-items:center; gap:14px; min-height:58px; }
        .cheq-empty-state {
            text-align: center;
            padding: 44px 18px;
            color: var(--cheq-muted);
        }
        .cheq-empty-icon {
            width: 72px; height:72px; border-radius:22px; margin:0 auto 16px;
            display:flex; align-items:center; justify-content:center;
            background: linear-gradient(135deg,#eef6ff,#ffffff);
            color: var(--cheq-blue); font-size:28px; border:1px solid #dbeafe;
        }
        .cheq-empty-title { font-size:18px; font-weight:900; color:#0f172a; margin-bottom:6px; }
        .cheq-empty-text { font-size:14px; font-weight:700; margin-bottom:16px; }
        .cheq-quick-grid {
            display:grid; grid-template-columns: repeat(4, minmax(190px,1fr)); gap:16px;
        }
        .cheq-quick-card {
            display:flex; align-items:center; gap:14px; padding:18px; border-radius:18px;
            border:1px solid var(--cheq-border); background:linear-gradient(135deg,#fff,#f8fbff);
            text-decoration:none !important; color:#0f172a; font-weight:900;
            box-shadow:0 12px 28px rgba(15,23,42,.06);
        }
        .cheq-quick-card:hover { transform: translateY(-1px); color:#155eef; }
        .cheq-quick-card .ico {
            width:46px;height:46px;border-radius:15px;display:flex;align-items:center;justify-content:center;
            color:#fff;background:linear-gradient(135deg,#2563eb,#38bdf8);
        }
        .cheq-page-note {
            border-left:4px solid var(--cheq-blue); background:#f8fbff; padding:14px 16px;
            border-radius:14px; font-weight:700; color:#334155; line-height:1.6;
        }
        @media (max-width: 1200px) { .cheq-quick-grid { grid-template-columns: repeat(2, minmax(190px,1fr)); } }
        @media (max-width: 768px) { .cheq-quick-grid { grid-template-columns: 1fr; } .cheq-header-actions { justify-content:flex-start; } }

        @media (max-width: 1200px) {
            .cheq-grid { grid-template-columns: repeat(2, minmax(190px, 1fr)); }
            .cheq-form-grid { grid-template-columns: repeat(2, minmax(190px, 1fr)); }
            .cheq-filter-row { grid-template-columns: repeat(2, minmax(145px, 1fr)); }
            .cheq-list-tools { grid-template-columns: 1fr; }
            .cheq-entry-group { justify-content:flex-start; }
        }
        @media (max-width: 768px) {
            .chequer-module-v1 { padding: 12px; }
            .cheq-grid,
            .cheq-form-grid,
            .cheq-filter-row { grid-template-columns: 1fr; }
            .cheq-top { padding: 18px; }
            .cheq-title { font-size: 24px; }
            .cheq-module-nav { gap: 8px; }
        }
    </style>
@endsection

@section('content')
    <section class="content chequer-module-v1">
        <div class="cheq-system-shell">
            @if(session('status'))
                <div class="cheq-alert">{{ session('status')['msg'] ?? 'Saved successfully' }}</div>
            @endif

            <div class="cheq-module-nav">
                <a href="{{ url('/chequer-module') }}" class="{{ request()->is('chequer-module') ? 'active' : '' }}"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
                <a href="{{ url('/chequer-module/bank-accounts') }}" class="{{ request()->is('chequer-module/bank-accounts*') ? 'active' : '' }}"><i class="fa fa-university"></i> Bank Accounts</a>
                <a href="{{ url('/chequer-module/templates') }}" class="{{ request()->is('chequer-module/templates*') ? 'active' : '' }}"><i class="fa fa-file-alt"></i> Templates</a>
                <a href="{{ url('/chequer-module/cheque-books') }}" class="{{ request()->is('chequer-module/cheque-books*') ? 'active' : '' }}"><i class="fa fa-book"></i> Cheque Books</a>
                <a href="{{ url('/chequer-module/cheque-leaves') }}" class="{{ request()->is('chequer-module/cheque-leaves*') || request()->routeIs('chequer.cheque-numbers.*') || request()->routeIs('chequer.cheque-numbers-m-entries.*') ? 'active' : '' }}"><i class="fa fa-list"></i> Cheque Leaves</a>
                <a href="{{ url('/chequer-module/write-cheque') }}" class="{{ request()->is('chequer-module/write-cheque*') ? 'active' : '' }}"><i class="fa fa-pen-square"></i> Write Cheque</a>
                <a href="{{ url('/chequer-module/settings') }}" class="{{ request()->is('chequer-module/settings*') || request()->routeIs('chequer.default-settings.*') ? 'active' : '' }}"><i class="fa fa-cog"></i> Settings</a>
            </div>

            @yield('chequer_content')
        </div>
    </section>
@endsection
