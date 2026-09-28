@extends('chequer::layouts.app')
@section('title','Cheque Print')
@section('chequer_content')
@php
    $fields = $fieldMap['fields'] ?? [];
    $image = $fieldMap['template_image'] ?? null;
    $paperWidth = (float)($fieldMap['paper_width'] ?? 6.77);
    $paperHeight = (float)($fieldMap['paper_height'] ?? 3.33);
@endphp
<div class="cheq-top no-print">
    <div>
        <div class="cheq-title">Cheque Print Preview</div>
        <div class="cheq-sub">Action: {{ ucwords(str_replace('_',' ',$action)) }} | Payment Type: {{ ucwords(str_replace('_',' ',$row->payment_type)) }}</div>
    </div>
    <div class="cheq-actions-row"><button class="cheq-btn blue" onclick="window.print()">Print</button><a class="cheq-btn gray" href="{{ url('/chequer-module/write-cheque') }}">Back</a></div>
</div>
<div class="cheq-card no-print">
    <strong>Cheque No:</strong> {{ $row->cheque_no }} &nbsp; <strong>Payee:</strong> {{ $row->payee_name }} &nbsp; <strong>Amount:</strong> {{ number_format($row->amount, 2) }}
</div>
<div class="cheq-print-page">
    <div class="cheq-print-canvas" style="width:{{ max(650,$paperWidth*96) }}px;height:{{ max(320,$paperHeight*96) }}px; @if($image) background-image:url('{{ asset('storage/'.$image) }}'); @endif">
        <div class="p-field" style="{{ cheq_pos($fields['payee'] ?? [], '78px','50px','416px','30px') }}">{{ $row->payee_name }}</div>
        <div class="p-field" style="{{ cheq_pos($fields['date'] ?? [], '30px','480px','130px','26px') }}">{{ \Carbon\Carbon::parse($row->cheque_date)->format('d-m-Y') }}</div>
        <div class="p-field" style="{{ cheq_pos($fields['amount'] ?? [], '120px','500px','120px','28px') }}">{{ number_format($row->amount,2) }}</div>
        <div class="p-field" style="{{ cheq_pos($fields['amount_words'] ?? [], '145px','50px','520px','45px') }}">{{ $row->amount_words ?? '' }}</div>
        @if(!empty($options['account_payee_only']))<div class="p-field" style="{{ cheq_pos($fields['account_payee'] ?? [], '25px','75px','140px','22px') }}">A/C PAYEE ONLY</div>@endif
        @if(!empty($options['not_negotiable']))<div class="p-field" style="{{ cheq_pos($fields['not_negotiable'] ?? [], '50px','75px','145px','22px') }}">NOT NEGOTIABLE</div>@endif
        @if(!empty($options['double_cross_cheque']))<div class="p-field" style="{{ cheq_pos($fields['double_cross'] ?? [], '15px','25px','25px','35px') }}">//</div>@endif
        @if($action === 'print_voucher')
            <div class="voucher-box">
                <strong>Payment Voucher</strong><br>On account of: {{ $row->on_account_of ?? $row->memo }}<br>Prepared by: __________ Approved by: __________ Received by: __________
            </div>
        @endif
    </div>
</div>
@php
function cheq_pos($f,$top,$left,$width,$height){
    $t = isset($f['top']) ? $f['top'].'px' : $top; $l = isset($f['left']) ? $f['left'].'px' : $left; $w = isset($f['width']) ? $f['width'].'px' : $width; $h = isset($f['height']) ? $f['height'].'px' : $height; $fs = isset($f['font_size']) ? $f['font_size'].'px' : '14px';
    return "top:$t;left:$l;width:$w;height:$h;font-size:$fs;";
}
@endphp
<style>
.cheq-print-page{background:#f8fafc;border:1px solid #dbe7f5;border-radius:18px;padding:20px;overflow:auto}.cheq-print-canvas{position:relative;background:#fff;background-size:100% 100%;background-repeat:no-repeat;border:1px solid #334155;box-shadow:0 14px 30px rgba(15,23,42,.12)}.p-field{position:absolute;color:#000;font-weight:700;overflow:hidden}.voucher-box{position:absolute;left:20px;bottom:15px;right:20px;border-top:1px dashed #334155;padding-top:8px;color:#111;font-size:13px}@media print{.no-print,.main-header,.main-sidebar,.content-header{display:none!important}.content-wrapper{margin-left:0!important}.cheq-print-page{border:0;padding:0;background:#fff}.cheq-print-canvas{box-shadow:none;border:0}}
</style>
@endsection
