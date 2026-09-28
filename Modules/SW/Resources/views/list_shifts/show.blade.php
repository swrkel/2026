@extends('sw::layouts.app', [
    'title' => 'SW Shift ' . $shift->sw_shift_no,
    'heading' => 'SW Shift ' . $shift->sw_shift_no,
    'subheading' => 'Shift collections and settlement details',
])

@section('sw_content')
<div class="sw-shift-detail-print">
    <div class="sw-card">
        <div class="sw-detail-head">
            <div><span>Business Location</span><strong>{{ $shift->location_name ?: '—' }}</strong></div>
            <div><span>Date</span><strong>{{ $shift->shift_date ? \Carbon\Carbon::parse($shift->shift_date)->format('d/m/Y') : '—' }}</strong></div>
            <div><span>Operator</span><strong>{{ $operators->implode(', ') ?: '—' }}</strong></div>
            <div><span>Shift No</span><strong>{{ $shift->sw_shift_no }}</strong></div>
            <div><span>Pump Nos</span><strong>{{ $pumps->filter()->unique()->implode(', ') ?: '—' }}</strong></div>
            <div><span>Status</span><strong>{{ $status_label }}</strong></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-2 col-sm-4"><div class="sw-stat"><div class="k">Cash</div><div class="v">{{ number_format($cash_total,2) }}</div></div></div>
        <div class="col-md-2 col-sm-4"><div class="sw-stat"><div class="k">Cards</div><div class="v">{{ number_format($card_total,2) }}</div></div></div>
        <div class="col-md-2 col-sm-4"><div class="sw-stat"><div class="k">Credit Sales</div><div class="v">{{ number_format($credit_total,2) }}</div></div></div>
        <div class="col-md-2 col-sm-4"><div class="sw-stat"><div class="k">Cheques</div><div class="v">{{ number_format($cheque_total,2) }}</div></div></div>
        <div class="col-md-2 col-sm-4"><div class="sw-stat total"><div class="k">Total Amount</div><div class="v">{{ number_format($total_amount,2) }}</div></div></div>
        <div class="col-md-2 col-sm-4"><div class="sw-stat total"><div class="k">Settlement Amount</div><div class="v">{{ number_format((float)($settlement->settlement_amount ?? 0),2) }}</div></div></div>
    </div>

    <div class="sw-card">
        <h3>Settlement</h3>
        <table class="sw-table">
            <tr><th>Settlement No</th><td>{{ $settlement->settlement_no ?? '—' }}</td>
                <th>Settlement Date</th><td>{{ !empty($settlement->transaction_date) ? \Carbon\Carbon::parse($settlement->transaction_date)->format('d/m/Y') : '—' }}</td></tr>
        </table>
    </div>

    @php
        $sections = [
            ['Cash', $cash_rows], ['Cards', $card_rows], ['Credit Sales', $credit_rows], ['Cheques', $cheque_rows]
        ];
    @endphp
    @foreach($sections as [$title, $rows])
        <div class="sw-card">
            <h3>{{ $title }}</h3>
            <table class="sw-table">
                <thead><tr><th>Operator</th><th>Reference</th><th>Customer / Bank</th><th class="num">Amount</th><th>Note</th></tr></thead>
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
                    <tr><td colspan="5" class="sw-empty">No {{ strtolower($title) }} entries for this shift.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</div>

@if(!$printMode)
<div class="sw-actions" style="margin-top:10px">
    <a href="{{ route('sw.list-shifts.index') }}" class="sw-btn secondary"><i class="fa fa-arrow-left"></i> List SW Shifts</a>
    <a href="{{ route('sw.list-shifts.print', $shift->id) }}" target="_blank" class="sw-btn"><i class="fa fa-print"></i> Print</a>
</div>
@endif
@endsection

@push('css')
<style>
.sw-detail-head{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:14px}
.sw-detail-head span{display:block;color:#68758b;font-size:11px;text-transform:uppercase;font-weight:700;margin-bottom:4px}
.sw-detail-head strong{font-size:14px;color:#172033}
@media(max-width:767px){.sw-detail-head{grid-template-columns:1fr}}
@media print{
    .main-header,.main-sidebar,.content-header,.sw-head,.sw-actions,.main-footer{display:none!important}
    .content-wrapper,.content{margin:0!important;padding:0!important}
    .sw-card{box-shadow:none!important;break-inside:avoid}
    a[href]:after{content:""!important}
}
</style>
@endpush

@if($printMode)
@push('javascript')
<script>$(function(){ setTimeout(function(){ window.print(); }, 150); });</script>
@endpush
@endif
