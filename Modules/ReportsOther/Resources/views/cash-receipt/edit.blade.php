@extends('reportsother::layouts.app')

@section('title', 'Edit Receipt '.$receipt->receipt_no.' - Reports - Other')
@section('page-title', 'Edit Receipt '.$receipt->receipt_no)

@section('content')
<div class="reo-card">
    <div class="reo-card-body">
        <div class="reo-section-head">
            <div>
                <h2>Edit Manual Details</h2>
                <p>Auto-loaded Source, amounts, Receipt No, date and cheque details are locked. Only fields that were entered manually can be changed.</p>
            </div>
        </div>

        @include('reportsother::cash-receipt.partials.saved-receipt-document')

        <div class="reo-divider"></div>

        @if($receipt->membership_is_manual)
            <form method="post" action="{{ route('reports-other.cash-receipt.receipts.update', $receipt) }}" class="reo-narrow-form">
                @csrf
                @method('PUT')
                <label class="reo-label" for="reo-edit-membership">Membership No <span class="required">*</span></label>
                <input class="reo-input" id="reo-edit-membership" type="text" name="membership_no" maxlength="120" required value="{{ old('membership_no', $receipt->membership_no) }}">
                <div class="reo-help">This field is editable because the Membership No was not available automatically when the Receipt was created.</div>
                <div class="reo-actions">
                    <button class="reo-btn reo-btn-primary" type="submit">Save Manual Change</button>
                    <a class="reo-btn reo-btn-light" href="{{ route('reports-other.cash-receipt.receipts.show', $receipt) }}">Cancel</a>
                </div>
            </form>
        @else
            <div class="reo-alert reo-alert-info">This Receipt has no manually entered field that can be edited. The Membership No was auto-loaded, and all other Receipt details are system-generated snapshots.</div>
            <div class="reo-actions"><a class="reo-btn reo-btn-light" href="{{ route('reports-other.cash-receipt.receipts.show', $receipt) }}">Back to Receipt</a></div>
        @endif
    </div>
</div>
@endsection
