@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Products New Reports')
@section('productsnew_page_subtitle', 'Inventory, product, stock, batch and pricing reports.')

@section('productsnew_content')
<div class="productsnew-page pn-report-centre-v3">
    {{--
        Reports Centre v3
        The styles are intentionally scoped and kept in this Blade file so the
        corrected five-column layout is not affected by stale compiled module CSS.
    --}}
    <style>
        .pn-report-centre-v3 {
            width: 100%;
            color: #172b45;
        }

        .pn-report-centre-v3 * {
            box-sizing: border-box;
        }

        .pn-report-centre-v3 .pn-report-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin: 0 0 18px;
            padding: 16px 18px;
            border: 1px solid #dce7f3;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 5px 18px rgba(31, 50, 81, .07);
        }

        .pn-report-centre-v3 .pn-report-toolbar-main {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 14px;
        }

        .pn-report-centre-v3 .pn-report-toolbar-icon {
            display: inline-flex;
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: #fff;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .22);
            font-size: 21px;
        }

        .pn-report-centre-v3 .pn-report-toolbar-copy {
            min-width: 0;
        }

        .pn-report-centre-v3 .pn-report-toolbar-copy h2 {
            margin: 0 0 4px;
            color: #172b45;
            font-size: 21px;
            line-height: 1.25;
            font-weight: 800;
        }

        .pn-report-centre-v3 .pn-report-toolbar-copy p {
            margin: 0;
            color: #718096;
            font-size: 13px;
            line-height: 1.45;
        }

        .pn-report-centre-v3 .pn-report-toolbar-count {
            display: inline-flex;
            min-width: 118px;
            height: 46px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 14px;
            border: 1px solid #d9e6f5;
            border-radius: 10px;
            background: #f7faff;
            color: #31506f;
        }

        .pn-report-centre-v3 .pn-report-toolbar-count strong {
            color: #2563eb;
            font-size: 22px;
            line-height: 1;
            font-weight: 900;
        }

        .pn-report-centre-v3 .pn-report-toolbar-count span {
            font-size: 12px;
            line-height: 1.15;
            font-weight: 800;
        }

        .pn-report-centre-v3 .pn-report-grid-system {
            display: grid !important;
            width: 100% !important;
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            gap: 14px !important;
            align-items: stretch !important;
        }

        .pn-report-centre-v3 .pn-report-system-card {
            --pn-accent: #2563eb;
            --pn-accent-soft: #eef5ff;
            position: relative;
            display: flex !important;
            min-width: 0;
            min-height: 164px;
            margin: 0 !important;
            padding: 15px 15px 13px;
            overflow: hidden;
            flex-direction: column;
            border: 1px solid #dce6f1;
            border-radius: 12px;
            background: #fff;
            color: #172b45 !important;
            text-decoration: none !important;
            box-shadow: 0 5px 16px rgba(31, 50, 81, .065);
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .pn-report-centre-v3 .pn-report-system-card::before {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 4px;
            background: var(--pn-accent);
            content: '';
        }

        .pn-report-centre-v3 .pn-report-system-card:hover,
        .pn-report-centre-v3 .pn-report-system-card:focus {
            z-index: 2;
            transform: translateY(-3px);
            border-color: var(--pn-accent);
            color: #172b45 !important;
            text-decoration: none !important;
            box-shadow: 0 12px 26px rgba(31, 50, 81, .13);
            outline: none;
        }

        .pn-report-centre-v3 .pn-report-card-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 11px;
        }

        .pn-report-centre-v3 .pn-report-card-icon {
            display: inline-flex;
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--pn-accent-soft);
            color: var(--pn-accent);
            font-size: 18px;
        }

        .pn-report-centre-v3 .pn-report-card-number {
            color: #a0aec0;
            font-size: 11px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .pn-report-centre-v3 .pn-report-card-body {
            display: flex;
            min-width: 0;
            flex: 1 1 auto;
            flex-direction: column;
        }

        .pn-report-centre-v3 .pn-report-card-title {
            display: block;
            margin: 0 0 5px;
            color: #1b324d;
            font-size: 14px;
            line-height: 1.3;
            font-weight: 800;
        }

        .pn-report-centre-v3 .pn-report-card-description {
            display: block;
            margin: 0;
            color: #718096;
            font-size: 11.5px;
            line-height: 1.42;
        }

        .pn-report-centre-v3 .pn-report-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #edf1f6;
            color: var(--pn-accent);
            font-size: 11.5px;
            line-height: 1;
            font-weight: 800;
        }

        .pn-report-centre-v3 .pn-report-card-footer i {
            transition: transform .18s ease;
        }

        .pn-report-centre-v3 .pn-report-system-card:hover .pn-report-card-footer i,
        .pn-report-centre-v3 .pn-report-system-card:focus .pn-report-card-footer i {
            transform: translateX(3px);
        }

        .pn-report-centre-v3 .pn-theme-blue    { --pn-accent: #2563eb; --pn-accent-soft: #eef5ff; }
        .pn-report-centre-v3 .pn-theme-emerald { --pn-accent: #059669; --pn-accent-soft: #ecfdf5; }
        .pn-report-centre-v3 .pn-theme-amber   { --pn-accent: #d97706; --pn-accent-soft: #fff8e8; }
        .pn-report-centre-v3 .pn-theme-violet  { --pn-accent: #7c3aed; --pn-accent-soft: #f5f0ff; }
        .pn-report-centre-v3 .pn-theme-cyan    { --pn-accent: #0891b2; --pn-accent-soft: #ecfbff; }
        .pn-report-centre-v3 .pn-theme-indigo  { --pn-accent: #4f46e5; --pn-accent-soft: #eef0ff; }
        .pn-report-centre-v3 .pn-theme-green   { --pn-accent: #16a34a; --pn-accent-soft: #eefbf1; }
        .pn-report-centre-v3 .pn-theme-orange  { --pn-accent: #ea580c; --pn-accent-soft: #fff4eb; }
        .pn-report-centre-v3 .pn-theme-rose    { --pn-accent: #e11d48; --pn-accent-soft: #fff0f4; }
        .pn-report-centre-v3 .pn-theme-red     { --pn-accent: #dc2626; --pn-accent-soft: #fff0f0; }
        .pn-report-centre-v3 .pn-theme-crimson { --pn-accent: #be123c; --pn-accent-soft: #fff0f3; }
        .pn-report-centre-v3 .pn-theme-slate   { --pn-accent: #475569; --pn-accent-soft: #f1f5f9; }
        .pn-report-centre-v3 .pn-theme-teal    { --pn-accent: #0f766e; --pn-accent-soft: #ecfbf8; }
        .pn-report-centre-v3 .pn-theme-purple  { --pn-accent: #9333ea; --pn-accent-soft: #faf0ff; }
        .pn-report-centre-v3 .pn-theme-sky     { --pn-accent: #0284c7; --pn-accent-soft: #edf8ff; }
        .pn-report-centre-v3 .pn-theme-lime    { --pn-accent: #4d7c0f; --pn-accent-soft: #f6fbe9; }
        .pn-report-centre-v3 .pn-theme-navy    { --pn-accent: #1e3a8a; --pn-accent-soft: #eef3ff; }

        @media (max-width: 1399px) {
            .pn-report-centre-v3 .pn-report-grid-system {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 1099px) {
            .pn-report-centre-v3 .pn-report-grid-system {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 767px) {
            .pn-report-centre-v3 .pn-report-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .pn-report-centre-v3 .pn-report-toolbar-count {
                min-width: 112px;
            }

            .pn-report-centre-v3 .pn-report-grid-system {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 520px) {
            .pn-report-centre-v3 .pn-report-grid-system {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <div class="pn-report-toolbar">
        <div class="pn-report-toolbar-main">
            <span class="pn-report-toolbar-icon" aria-hidden="true">
                <i class="fa fa-bar-chart"></i>
            </span>
            <div class="pn-report-toolbar-copy">
                <h2>Reporting &amp; Analytics</h2>
                <p>Select a report to review product, inventory, batch, pricing and stock information.</p>
            </div>
        </div>
        <div class="pn-report-toolbar-count" aria-label="Available reports">
            <strong>{{ count($cards) }}</strong>
            <span>Available<br>Reports</span>
        </div>
    </div>

    <div class="pn-report-grid-system">
        @foreach($cards as $card)
            @php
                $theme = preg_replace('/[^a-z0-9_-]/i', '', $card['theme'] ?? 'blue');
                $icon = preg_replace('/[^a-z0-9_-]/i', '', $card['icon'] ?? 'fa-file-text-o');
            @endphp
            <a
                class="pn-report-system-card pn-theme-{{ $theme }}"
                href="{{ route($card['route']) }}"
                title="Open {{ $card['title'] }}"
            >
                <span class="pn-report-card-heading">
                    <span class="pn-report-card-icon" aria-hidden="true">
                        <i class="fa {{ $icon }}"></i>
                    </span>
                    <span class="pn-report-card-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </span>

                <span class="pn-report-card-body">
                    <strong class="pn-report-card-title">{{ $card['title'] }}</strong>
                    <small class="pn-report-card-description">{{ $card['description'] }}</small>
                </span>

                <span class="pn-report-card-footer">
                    <span>Open Report</span>
                    <i class="fa fa-arrow-right" aria-hidden="true"></i>
                </span>
            </a>
        @endforeach
    </div>
</div>
@endsection
