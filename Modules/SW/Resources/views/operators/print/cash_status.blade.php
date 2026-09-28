<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SW Daily Cash Status - {{ $shift->sw_shift_no }}</title>
    <style>
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;background:#eef2f5;color:#172033;font-family:Arial,Helvetica,sans-serif;font-size:12px}
        .toolbar{max-width:1040px;margin:14px auto 0;display:flex;justify-content:flex-end;gap:8px}
        .toolbar a,.toolbar button{display:inline-flex;align-items:center;justify-content:center;min-height:36px;border-radius:7px;padding:8px 14px;border:1px solid #d4dde7;background:#fff;color:#24364b;font-weight:700;text-decoration:none;cursor:pointer}
        .toolbar .primary{background:#176b87;border-color:#176b87;color:#fff}
        .sheet{max-width:1040px;margin:12px auto 28px;background:#fff;border:1px solid #dfe6ed;border-radius:12px;box-shadow:0 8px 28px rgba(15,23,42,.09);overflow:hidden}
        .header{padding:20px 24px 17px;background:linear-gradient(135deg,#f7fbfc,#eef6f7);border-bottom:4px solid #1f7a8c}
        .header-row{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}
        .eyebrow{font-size:10px;font-weight:800;letter-spacing:1.25px;text-transform:uppercase;color:#577185;margin-bottom:5px}
        h1{font-size:24px;line-height:1.1;margin:0;color:#102a43}
        .business{margin-top:6px;font-size:13px;font-weight:700;color:#4f6073}
        .status{text-align:right}
        .badge{display:inline-block;padding:7px 12px;border-radius:999px;background:#fff;border:1px solid #bad9df;color:#176b87;font-size:11px;font-weight:800}
        .doc-no{margin-top:7px;font-size:15px;font-weight:800;color:#26384a}
        .content{padding:18px 24px 22px}
        .meta-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px}
        .meta{border:1px solid #e0e7ee;border-radius:8px;background:#fbfcfd;padding:9px 10px;min-height:56px}
        .k{font-size:9px;text-transform:uppercase;letter-spacing:.55px;color:#78879a;font-weight:800}
        .v{margin-top:4px;font-size:12px;font-weight:700;color:#172033;overflow-wrap:anywhere}
        .equation{display:grid;grid-template-columns:repeat(6,1fr);gap:7px;margin-bottom:16px}
        .sum-card{border:1px solid #dce5ed;border-radius:8px;background:#fff;padding:9px 10px;text-align:right}
        .sum-card .value{margin-top:4px;font-size:15px;font-weight:800;font-variant-numeric:tabular-nums}
        .sum-card.positive{background:#f3faf7;border-color:#c9e7d8}
        .sum-card.negative{background:#fff8f5;border-color:#efd4c9}
        .sum-card.balance{background:#eef7f8;border-color:#b9dce2}
        .sum-card.balance .value{font-size:17px;color:#0f536a}
        .formula{margin:-5px 0 15px;color:#6e7f92;font-size:10px;text-align:right}
        .section{margin:0 0 13px;break-inside:avoid}
        .section-head{display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-radius:7px 7px 0 0;background:#2a3b4d;color:#fff;font-weight:800;font-size:11px}
        .section-head .sub{font-size:9px;font-weight:600;opacity:.86}
        table{width:100%;border-collapse:collapse;table-layout:fixed}
        th,td{border:1px solid #dfe6ee;padding:6px 7px;vertical-align:top;overflow-wrap:anywhere}
        th{background:#f5f8fa;color:#3f5062;text-transform:uppercase;letter-spacing:.28px;font-size:8.7px;text-align:left}
        td{font-size:10px;color:#26384a}
        .num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
        .empty{text-align:center;padding:10px;color:#8996a6}
        .section-total td{font-weight:800;background:#fafcfd}
        .signatures{display:grid;grid-template-columns:repeat(3,1fr);gap:26px;margin-top:26px;padding-top:8px;break-inside:avoid}
        .sig{padding-top:26px;border-top:1px solid #9caabd;text-align:center;color:#536579;font-size:10px}
        .footer{display:flex;justify-content:space-between;gap:12px;padding:10px 24px 12px;border-top:1px solid #e3e9ef;color:#7b899a;font-size:9px}
        @page{size:A4 landscape;margin:9mm}
        @media print{
            html,body{background:#fff}
            .toolbar{display:none!important}
            .sheet{max-width:none;margin:0;border:0;border-radius:0;box-shadow:none}
            .header{padding:12px 15px 10px}
            h1{font-size:20px}
            .content{padding:10px 15px 12px}
            .meta-grid,.equation{gap:5px;margin-bottom:9px}
            .meta,.sum-card{padding:6px 7px;min-height:auto}
            .section{margin-bottom:8px}
            th,td{padding:3.5px 4.5px}
            .signatures{margin-top:18px}
            .footer{padding:6px 15px 0}
        }
    </style>
</head>
<body>
<div class="toolbar">
    <a href="{{ route('sw.shift-operations.index') }}#sw_daily_cash_status">Back to SW Shifts</a>
    <button type="button" class="primary" onclick="window.print()">Print</button>
</div>

<div class="sheet">
    <header class="header">
        <div class="header-row">
            <div>
                <div class="eyebrow">Shift Closing Statement</div>
                <h1>SW Daily Cash Status</h1>
                <div class="business">{{ $business_name ?: ($location_name ?: 'Business') }}</div>
            </div>
            <div class="status">
                <span class="badge">{{ $shift->statusLabel() }}</span>
                <div class="doc-no">{{ $shift->sw_shift_no }}</div>
            </div>
        </div>
    </header>

    <main class="content">
        <div class="meta-grid">
            <div class="meta"><div class="k">Business Location</div><div class="v">{{ $location_name ?: '—' }}</div></div>
            <div class="meta"><div class="k">Shift Date</div><div class="v">{{ $shift->shift_date ? \Carbon\Carbon::parse($shift->shift_date)->format('d/m/Y') : '—' }}</div></div>
            <div class="meta"><div class="k">Shift Name</div><div class="v">{{ $shift->shift_name ?: '—' }}</div></div>
            <div class="meta"><div class="k">Operators</div><div class="v">{{ $operators->filter()->unique()->implode(', ') ?: '—' }}</div></div>
            <div class="meta"><div class="k">Closed Date & Time</div><div class="v">{{ $shift->closed_at ? \Carbon\Carbon::parse($shift->closed_at)->format('d/m/Y h:i A') : '—' }}</div></div>
            <div class="meta"><div class="k">Closed By</div><div class="v">{{ $closed_by_name ?: '—' }}</div></div>
            <div class="meta"><div class="k">Shift No</div><div class="v">{{ $shift->sw_shift_no }}</div></div>
            <div class="meta"><div class="k">Status</div><div class="v">{{ $shift->statusLabel() }}</div></div>
        </div>

        <div class="equation">
            <div class="sum-card positive"><div class="k">Cash Collection</div><div class="value">{{ number_format((float)$figures['collection']['total'],2) }}</div></div>
            <div class="sum-card positive"><div class="k">Customer Payment - Cash</div><div class="value">{{ number_format((float)$figures['customer_payments']['total'],2) }}</div></div>
            <div class="sum-card negative"><div class="k">Cash Expenses</div><div class="value">{{ number_format((float)$figures['expenses']['total'],2) }}</div></div>
            <div class="sum-card negative"><div class="k">Cash Purchases</div><div class="value">{{ number_format((float)$figures['purchases']['total'],2) }}</div></div>
            <div class="sum-card negative"><div class="k">Cash Deposits</div><div class="value">{{ number_format((float)$figures['deposits']['total'],2) }}</div></div>
            <div class="sum-card balance"><div class="k">Balance In Hand</div><div class="value">{{ number_format((float)$figures['balance'],2) }}</div></div>
        </div>
        <div class="formula">Cash Collection + Customer Payment - Cash − Cash Expenses − Cash Purchases − Cash Deposits = Balance In Hand</div>

        @php
            $sections = [
                ['Cash Collection', $figures['collection'], '+'],
                ['Customer Payment - Cash', $figures['customer_payments'], '+'],
                ['Cash Expenses', $figures['expenses'], '−'],
                ['Cash Purchases', $figures['purchases'], '−'],
                ['Cash Deposits', $figures['deposits'], '−'],
            ];
        @endphp

        @foreach($sections as [$title, $section, $sign])
            <section class="section">
                <div class="section-head">
                    <span>{{ $sign }} {{ $title }}</span>
                    <span class="sub">{{ $section['count'] }} entr{{ $section['count'] === 1 ? 'y' : 'ies' }} · Total {{ number_format((float)$section['total'],2) }}</span>
                </div>
                <table>
                    <thead>
                    <tr>
                        <th style="width:15%">Date</th>
                        <th style="width:20%">Reference</th>
                        <th style="width:28%">Details</th>
                        <th style="width:25%">Note</th>
                        <th style="width:12%" class="num">Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($section['rows'] as $row)
                        <tr>
                            <td>{{ $row->date ? \Carbon\Carbon::parse($row->date)->format('d/m/Y') : '—' }}</td>
                            <td>{{ $row->reference ?: '—' }}</td>
                            <td>{{ $row->party ?: '—' }}</td>
                            <td>{{ $row->note ?: '' }}</td>
                            <td class="num">{{ number_format((float)$row->amount,2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No entries recorded for this section.</td></tr>
                    @endforelse
                    <tr class="section-total">
                        <td colspan="4" class="num">Section Total</td>
                        <td class="num">{{ number_format((float)$section['total'],2) }}</td>
                    </tr>
                    </tbody>
                </table>
            </section>
        @endforeach

        <div class="signatures">
            <div class="sig">Prepared / Cash Handed Over By</div>
            <div class="sig">Checked By</div>
            <div class="sig">Authorized By</div>
        </div>
    </main>

    <footer class="footer">
        <span>SW Daily Cash Status · {{ $shift->sw_shift_no }}</span>
        <span>Generated {{ now()->format('d/m/Y h:i A') }}</span>
    </footer>
</div>

@if($auto_print)
<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 300);
});
</script>
@endif
</body>
</html>
