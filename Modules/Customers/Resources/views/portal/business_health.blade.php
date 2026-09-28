@extends('customers::portal.layout')

@section('title', 'Business Health')

@section('style')
<style>
    .dd-health-hero{display:grid;grid-template-columns:240px 1fr;gap:18px;align-items:stretch;}
    .dd-health-score{border-radius:22px;padding:28px;text-align:center;background:linear-gradient(135deg,#0b72e7,#08b4d8);color:#fff;box-shadow:0 18px 44px rgba(15,76,129,.18);}
    .dd-health-score-number{font-size:64px;font-weight:900;line-height:1;}
    .dd-health-score-label{font-size:16px;font-weight:800;margin-top:8px;}
    .dd-health-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
    .dd-health-card{border:1px solid #e5edf6;border-radius:16px;padding:16px;background:#fff;}
    .dd-health-card-title{font-size:12px;font-weight:900;color:#64748b;text-transform:uppercase;margin-bottom:8px;}
    .dd-health-card-score{font-size:30px;font-weight:900;color:#0f172a;}
    .dd-health-tag{display:inline-block;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:900;margin-top:6px;}
    .dd-health-good{background:#dcfce7;color:#166534;}
    .dd-health-average{background:#e0f2fe;color:#075985;}
    .dd-health-warning{background:#ffedd5;color:#9a3412;}
    .dd-health-critical{background:#fee2e2;color:#991b1b;}
    .dd-meter{height:9px;background:#e5edf6;border-radius:999px;overflow:hidden;margin-top:10px;}
    .dd-meter-fill{height:9px;background:linear-gradient(135deg,#22c55e,#0ea5e9);border-radius:999px;}
    .dd-insight{border-left:4px solid #2563eb;background:#f8fafc;border-radius:10px;padding:12px 14px;margin-bottom:10px;font-weight:700;color:#334155;}
    .dd-timeline{display:flex;gap:8px;align-items:end;min-height:130px;overflow-x:auto;padding-bottom:6px;}
    .dd-bar{min-width:54px;text-align:center;}
    .dd-bar-fill{width:100%;border-radius:8px 8px 0 0;background:linear-gradient(180deg,#0ea5e9,#2563eb);margin-bottom:6px;}
    .dd-bar-label{font-size:11px;color:#64748b;font-weight:800;}
    @media(max-width:900px){.dd-health-hero{grid-template-columns:1fr}.dd-health-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:560px){.dd-health-grid{grid-template-columns:1fr}.dd-health-score-number{font-size:48px}}
</style>
@endsection

@section('body')
@include('customers::portal.partials_nav')

<div class="dd-wrap">
    <div class="dd-card">
        <div class="dd-card-header">
            <h3 class="dd-card-title">Business Health Score</h3>
        </div>
        <div class="dd-card-body">
            <div class="dd-health-hero">
                <div class="dd-health-score">
                    <div class="dd-health-score-number">{{ $health['overall'] }}</div>
                    <div class="dd-health-score-label">{{ $health['overall_label'] }}</div>
                    <div style="font-size:12px;margin-top:8px;opacity:.9;">Overall Dealer Score</div>
                </div>
                <div class="dd-health-grid">
                    @foreach(['credit' => 'Credit Health', 'payment' => 'Payment Health', 'purchase' => 'Purchase Health', 'delivery' => 'Delivery Health', 'loyalty' => 'Loyalty Health', 'growth' => 'Growth Health'] as $key => $label)
                        <div class="dd-health-card">
                            <div class="dd-health-card-title">{{ $label }}</div>
                            <div class="dd-health-card-score">{{ $health['scores'][$key] ?? 0 }}</div>
                            <span class="dd-health-tag {{ $health['classes'][$key] ?? 'dd-health-average' }}">{{ $health['labels'][$key] ?? 'Average' }}</span>
                            <div class="dd-meter"><div class="dd-meter-fill" style="width:{{ min($health['scores'][$key] ?? 0, 100) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="dd-summary">
        <div class="dd-summary-item"><div class="dd-summary-label">Outstanding</div><div class="dd-summary-value">{{ number_format($health['metrics']['outstanding'], 2) }}</div></div>
        <div class="dd-summary-item"><div class="dd-summary-label">Credit Limit</div><div class="dd-summary-value">{{ number_format($health['metrics']['credit_limit'], 2) }}</div></div>
        <div class="dd-summary-item"><div class="dd-summary-label">Available Credit</div><div class="dd-summary-value">{{ number_format($health['metrics']['available_credit'], 2) }}</div></div>
        <div class="dd-summary-item"><div class="dd-summary-label">Credit Usage</div><div class="dd-summary-value">{{ number_format($health['metrics']['credit_utilization'], 2) }}%</div></div>
        <div class="dd-summary-item"><div class="dd-summary-label">Open Orders</div><div class="dd-summary-value">{{ number_format($health['metrics']['open_orders']) }}</div></div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">AI Health Insights</h3></div>
        <div class="dd-card-body">
            @foreach($health['insights'] as $insight)
                <div class="dd-insight">{{ $insight }}</div>
            @endforeach
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">12 Month Health Timeline</h3></div>
        <div class="dd-card-body">
            <div class="dd-timeline">
                @foreach($health['timeline'] as $row)
                    <div class="dd-bar">
                        <div class="dd-bar-fill" style="height:{{ max(25, min($row['score'], 100)) }}px"></div>
                        <div class="dd-bar-label">{{ $row['month'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
