@extends('reportsother::layouts.app')

@section('title', 'Receipt '.$receipt->receipt_no.' - Reports - Other')
@section('page-title', 'Receipt '.$receipt->receipt_no)

@section('content')
<div class="reo-actions reo-page-actions">
    <a class="reo-btn reo-btn-light" href="{{ route('reports-other.cash-receipt.index', ['tab' => 'list']) }}">Back to List Receipt</a>
    <a class="reo-btn reo-btn-primary" href="{{ route('reports-other.cash-receipt.receipts.print', $receipt) }}" target="_blank">Print</a>
    <a class="reo-btn reo-btn-light" href="{{ route('reports-other.cash-receipt.receipts.edit', $receipt) }}">Edit Manual Details</a>
    <button class="reo-btn reo-btn-light" type="button" data-reo-direct-share data-channel="email" data-share-url="{{ route('reports-other.cash-receipt.receipts.share', $receipt) }}" data-share-title="Receipt {{ $receipt->receipt_no }}">Email</button>
    <button class="reo-btn reo-btn-light" type="button" data-reo-direct-share data-channel="sms" data-share-url="{{ route('reports-other.cash-receipt.receipts.share', $receipt) }}" data-share-title="Receipt {{ $receipt->receipt_no }}">SMS</button>
    <button class="reo-btn reo-btn-light" type="button" data-reo-direct-share data-channel="whatsapp" data-share-url="{{ route('reports-other.cash-receipt.receipts.share', $receipt) }}" data-share-title="Receipt {{ $receipt->receipt_no }}">WhatsApp</button>
</div>

<div class="reo-card">
    <div class="reo-card-body">
        @include('reportsother::cash-receipt.partials.saved-receipt-document')
    </div>
</div>
@endsection
