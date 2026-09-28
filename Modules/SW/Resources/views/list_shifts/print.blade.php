<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>SW Shift {{ $shift->sw_shift_no }} - Print</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;background:#eef2f6;color:#172033;font-family:Arial,Helvetica,sans-serif;font-size:12px}
        .print-toolbar{max-width:1120px;margin:14px auto 0;display:flex;justify-content:flex-end;gap:8px}
        .print-toolbar button,.print-toolbar a{border:0;border-radius:7px;padding:9px 15px;font-weight:700;text-decoration:none;cursor:pointer}
        .print-toolbar .primary{background:#176b87;color:#fff}.print-toolbar .secondary{background:#fff;color:#25324a;border:1px solid #d8e0e9}
        .sheet{max-width:1120px;margin:12px auto 30px;background:#fff;border:1px solid #dfe6ee;border-radius:12px;box-shadow:0 8px 28px rgba(15,23,42,.09);overflow:hidden}
        .hero{padding:22px 26px 18px;border-bottom:4px solid #1f7a8c;background:linear-gradient(135deg,#f8fbfd,#eef7f8)}
        .hero-row{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}
        .eyebrow{font-size:10px;font-weight:800;letter-spacing:1.4px;color:#517085;text-transform:uppercase;margin-bottom:5px}
        h1{margin:0;font-size:25px;line-height:1.1;color:#102a43}
        .business{margin-top:6px;font-size:13px;color:#526173;font-weight:600}
        .status-box{text-align:right;min-width:170px}
        .status-label{display:inline-block;padding:7px 12px;border:1px solid #b7d6dc;background:#fff;border-radius:999px;font-size:11px;font-weight:800;color:#176b87}
        .doc-no{margin-top:8px;font-weight:800;font-size:14px;color:#25324a}
        .content{padding:20px 26px 24px}
        .meta-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:9px;margin-bottom:16px}
        .meta{border:1px solid #e1e7ee;border-radius:8px;padding:10px;background:#fafcfd;min-height:59px}
        .meta .k,.stat .k{font-size:9px;text-transform:uppercase;letter-spacing:.65px;color:#748398;font-weight:800}
        .meta .v{font-size:12px;font-weight:750;color:#172033;margin-top:5px;overflow-wrap:anywhere}
        .summary{display:grid;grid-template-columns:repeat(6,1fr);gap:9px;margin:0 0 18px}
        .stat{border:1px solid #dce5ed;border-radius:9px;padding:10px 11px;background:#fff;text-align:right}
        .stat .v{margin-top:4px;font-size:15px;font-weight:800;font-variant-numeric:tabular-nums}
        .stat.total{background:#f0f7f8;border-color:#badce1}
        .section{margin:0 0 15px;break-inside:avoid}
        .section-title{display:flex;align-items:center;justify-content:space-between;margin:0;padding:8px 10px;border-radius:7px 7px 0 0;background:#26384a;color:#fff;font-size:12px;font-weight:800}
        .section-title .count{font-size:10px;font-weight:600;opacity:.85}
        table{width:100%;border-collapse:collapse;table-layout:fixed}
        th,td{border:1px solid #dfe6ee;padding:7px 8px;vertical-align:top;overflow-wrap:anywhere}
        th{background:#f5f8fa;color:#3d4d60;font-size:9px;text-transform:uppercase;letter-spacing:.35px;text-align:left}
        td{font-size:10px;color:#24364b}
        .num{text-align:right;font-variant-numeric:tabular-nums}
        .empty{text-align:center;color:#8995a5;padding:12px}
        .settlement-strip{display:grid;grid-template-columns:1fr 1fr;gap:0;margin-bottom:16px;border:1px solid #dfe6ee;border-radius:8px;overflow:hidden}
        .settlement-strip>div{padding:10px 12px}.settlement-strip>div+div{border-left:1px solid #dfe6ee}
        .settlement-strip span{display:block;font-size:9px;text-transform:uppercase;color:#78879a;font-weight:800;margin-bottom:4px}
        .settlement-strip strong{font-size:12px}
        .footer{display:flex;justify-content:space-between;gap:15px;border-top:1px solid #e2e8ef;padding:10px 26px 13px;color:#77869a;font-size:9px}
        @page{size:A4 landscape;margin:9mm}
        @media print{
            html,body{background:#fff}
            .print-toolbar{display:none!important}
            .sheet{max-width:none;margin:0;border:0;border-radius:0;box-shadow:none}
            .hero{padding:13px 16px 11px}
            h1{font-size:21px}
            .content{padding:12px 16px 14px}
            .meta-grid,.summary{gap:6px;margin-bottom:10px}
            .meta,.stat{padding:7px}
            .section{margin-bottom:9px}
            th,td{padding:4px 5px}
            .footer{padding:7px 16px 0}
        }
    </style>
</head>
<body>
<div class="print-toolbar">
    <a href="{{ route('sw.list-shifts.show', $shift->id) }}" class="secondary">Back to View</a>
    <button type="button" class="primary" onclick="window.print()">Print</button>
</div>

<div class="sheet">
    <header class="hero">
        <div class="hero-row">
            <div>
                <div class="eyebrow">Shift Operations Report</div>
                <h1>SW Shift Report</h1>
                <div class="business">{{ $business_name ?: $shift->location_name ?: 'Business' }}</div>
            </div>
            <div class="status-box">
                <span class="status-label">{{ $status_label }}</span>
                <div class="doc-no">{{ $shift->sw_shift_no }}</div>
            </div>
        </div>
    </header>

    <main class="content">
        <div class="meta-grid">
            <div class="meta"><div class="k">Business Location</div><div class="v">{{ $shift->location_name ?: '—' }}</div></div>
            <div class="meta"><div class="k">Shift Date</div><div class="v">{{ $shift->shift_date ? \Carbon\Carbon::parse($shift->shift_date)->format('d/m/Y') : '—' }}</div></div>
            <div class="meta"><div class="k">Shift No</div><div class="v">{{ $shift->sw_shift_no }}</div></div>
            <div class="meta"><div class="k">Operator</div><div class="v">{{ $operators->implode(', ') ?: '—' }}</div></div>
            <div class="meta"><div class="k">Pump Nos</div><div class="v">{{ $pumps->filter()->unique()->implode(', ') ?: '—' }}</div></div>
            <div class="meta"><div class="k">Status</div><div class="v">{{ $status_label }}</div></div>
        </div>

        <div class="summary">
            <div class="stat"><div class="k">Cash</div><div class="v">{{ number_format($cash_total,2) }}</div></div>
            <div class="stat"><div class="k">Cards</div><div class="v">{{ number_format($card_total,2) }}</div></div>
            <div class="stat"><div class="k">Credit Sales</div><div class="v">{{ number_format($credit_total,2) }}</div></div>
            <div class="stat"><div class="k">Cheques</div><div class="v">{{ number_format($cheque_total,2) }}</div></div>
            <div class="stat total"><div class="k">Total Amount</div><div class="v">{{ number_format($total_amount,2) }}</div></div>
            <div class="stat total"><div class="k">Settlement Amount</div><div class="v">{{ number_format((float)($settlement->settlement_amount ?? 0),2) }}</div></div>
        </div>

        <div class="settlement-strip">
            <div><span>Settlement No</span><strong>{{ $settlement->settlement_no ?? '—' }}</strong></div>
            <div><span>Settlement Date</span><strong>{{ !empty($settlement->transaction_date) ? \Carbon\Carbon::parse($settlement->transaction_date)->format('d/m/Y') : '—' }}</strong></div>
        </div>

        @php
            $sections = [
                ['Cash', $cash_rows],
                ['Cards', $card_rows],
                ['Credit Sales', $credit_rows],
                ['Cheques', $cheque_rows],
            ];
        @endphp

        @foreach($sections as [$title, $rows])
            <section class="section">
                <div class="section-title">
                    <span>{{ $title }}</span>
                    <span class="count">{{ $rows->count() }} entr{{ $rows->count() === 1 ? 'y' : 'ies' }}</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width:19%">Operator</th>
                            <th style="width:19%">Reference</th>
                            <th style="width:24%">Customer / Bank</th>
                            <th style="width:16%" class="num">Amount</th>
                            <th style="width:22%">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->operator_name ?? '—' }}</td>
                            <td>{{ $row->reference_text ?: '—' }}</td>
                            <td>{{ $row->customer_name ?? $row->bank ?? '—' }}</td>
                            <td class="num">{{ number_format((float)$row->display_amount,2) }}</td>
                            <td>{{ $row->note ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No {{ strtolower($title) }} entries for this shift.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>
        @endforeach
    </main>

    <footer class="footer">
        <span>SW Shift {{ $shift->sw_shift_no }}</span>
        <span>Generated {{ now()->format('d/m/Y H:i') }}</span>
    </footer>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 250);
});
</script>
</body>
</html>
