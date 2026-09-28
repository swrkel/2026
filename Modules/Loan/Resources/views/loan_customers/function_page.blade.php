@extends('layouts.app')
@section('title', $page_title . ' - ' . $customer->customer_no)

@section('content')
@php
    $photoUrl = method_exists($customer, 'imageUrl') ? $customer->imageUrl('photo') : null;
    $nicFrontUrl = method_exists($customer, 'imageUrl') ? $customer->imageUrl('nic_front_image') : null;
    $nicBackUrl = method_exists($customer, 'imageUrl') ? $customer->imageUrl('nic_back_image') : null;
    $signatureUrl = method_exists($customer, 'imageUrl') ? $customer->imageUrl('signature_image') : null;
@endphp
<section class="content-header">
    <div class="erp-modern-card erp-page-hero-card">
        <div>
            <h3><i class="fa fa-user"></i> {{ $page_title }}</h3>
            <p>{{ $customer->customer_no }} | {{ $customer->display_name }}</p>
        </div>
        <div class="erp-action-row">
            <a href="{{ route('loan.customers.index') }}" class="btn erp-btn-light"><i class="fa fa-arrow-left"></i> Back to Customers</a>
            <a href="{{ route('loan.customers.show', $customer->id) }}" class="btn erp-btn-info"><i class="fa fa-eye"></i> View Profile</a>
            <a href="{{ route('loan.customers.edit', $customer->id) }}" class="btn erp-btn-primary"><i class="fa fa-pencil"></i> Edit Customer</a>
        </div>
    </div>
</section>

<section class="content">
    <div class="erp-modern-shell loan-customer-function-page">
        <div class="erp-modern-card loan-customer-mini-profile">
            <div class="row">
                <div class="col-md-2 text-center">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" class="loan-mini-photo" alt="Loan customer photo">
                    @else
                        <div class="loan-mini-photo-placeholder"><i class="fa fa-user"></i></div>
                    @endif
                </div>
                <div class="col-md-10">
                    <h4>{{ $customer->display_name }}</h4>
                    <p>
                        <strong>Customer No:</strong> {{ $customer->customer_no }} &nbsp; | &nbsp;
                        <strong>NIC:</strong> {{ $customer->nic ?: '-' }} &nbsp; | &nbsp;
                        <strong>Mobile:</strong> {{ $customer->mobile ?: '-' }} &nbsp; | &nbsp;
                        <strong>Email:</strong> {{ $customer->email ?: 'No Email ID' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="erp-modern-card loan-customer-function-tabs">
            <div class="loan-action-tabs">
                <a class="{{ $active_tab == 'ledger' ? 'active' : '' }}" href="{{ route('loan.customers.ledger', $customer->id) }}"><i class="fa fa-book"></i> Customer Ledger</a>
                <a class="{{ $active_tab == 'applications' ? 'active' : '' }}" href="{{ route('loan.customers.applications', $customer->id) }}"><i class="fa fa-file-text-o"></i> Loan Applications</a>
                <a class="{{ $active_tab == 'active_loans' ? 'active' : '' }}" href="{{ route('loan.customers.active_loans', $customer->id) }}"><i class="fa fa-check-circle"></i> Active Loans</a>
                <a class="{{ $active_tab == 'documents' ? 'active' : '' }}" href="{{ route('loan.customers.documents', $customer->id) }}"><i class="fa fa-folder-open"></i> Documents</a>
                <a class="{{ $active_tab == 'notes' ? 'active' : '' }}" href="{{ route('loan.customers.notes', $customer->id) }}"><i class="fa fa-sticky-note"></i> Notes</a>
                <a class="{{ $active_tab == 'audit_log' ? 'active' : '' }}" href="{{ route('loan.customers.audit_log', $customer->id) }}"><i class="fa fa-history"></i> Audit Log</a>
            </div>
        </div>

        @if(in_array($active_tab, ['ledger', 'applications', 'active_loans']))
            <div class="erp-modern-card">
                <h4 class="erp-card-title"><i class="fa fa-list"></i> {{ $page_title }}</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped erp-modern-table">
                        <thead>
                            <tr>
                                <th style="width:80px;">#</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Status / Type</th>
                                <th class="text-right">Amount</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ !empty($row->created_at) ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i') : '-' }}</td>
                                    <td>{{ $row->application_no ?? $row->loan_no ?? $row->reference_no ?? $row->transaction_no ?? $row->id ?? '-' }}</td>
                                    <td>{{ $row->status ?? $row->type ?? '-' }}</td>
                                    <td class="text-right">{{ number_format((float)($row->amount ?? $row->loan_amount ?? $row->principal ?? $row->paid_amount ?? 0), 2) }}</td>
                                    <td>{{ $row->notes ?? $row->remarks ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No records found for this customer yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($active_tab == 'documents')
            <div class="erp-modern-card">
                <h4 class="erp-card-title"><i class="fa fa-folder-open"></i> Customer Documents</h4>
                <div class="row erp-document-grid">
                    @include('loan::loan_customers.partials.document_preview', ['label' => 'Photo', 'url' => $photoUrl])
                    @include('loan::loan_customers.partials.document_preview', ['label' => 'NIC Front', 'url' => $nicFrontUrl])
                    @include('loan::loan_customers.partials.document_preview', ['label' => 'NIC Back', 'url' => $nicBackUrl])
                    @include('loan::loan_customers.partials.document_preview', ['label' => 'Signature', 'url' => $signatureUrl])
                </div>
                <p class="text-muted m-t-15">Use Edit Customer to replace or upload these documents.</p>
            </div>
        @elseif($active_tab == 'notes')
            <div class="erp-modern-card">
                <h4 class="erp-card-title"><i class="fa fa-sticky-note"></i> Customer Notes</h4>
                <div class="loan-notes-box">{!! nl2br(e($customer->notes ?: 'No notes added.')) !!}</div>
                <div class="m-t-15">
                    <a href="{{ route('loan.customers.edit', $customer->id) }}" class="btn erp-btn-primary"><i class="fa fa-pencil"></i> Edit Notes</a>
                </div>
            </div>
        @elseif($active_tab == 'audit_log')
            <div class="erp-modern-card">
                <h4 class="erp-card-title"><i class="fa fa-history"></i> Audit Log</h4>
                <table class="table table-bordered erp-modern-table">
                    <tr><th style="width:260px;">Created At</th><td>{{ optional($customer->created_at)->format('Y-m-d H:i') ?: '-' }}</td></tr>
                    <tr><th>Updated At</th><td>{{ optional($customer->updated_at)->format('Y-m-d H:i') ?: '-' }}</td></tr>
                    <tr><th>Created By</th><td>{{ $customer->created_by ?: '-' }}</td></tr>
                    <tr><th>Updated By</th><td>{{ $customer->updated_by ?: '-' }}</td></tr>
                    <tr><th>Status</th><td>{{ ucfirst($customer->status ?: 'active') }}</td></tr>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection

@section('css')
<style>
.loan-mini-photo,
.loan-mini-photo-placeholder {
    width: 90px;
    height: 90px;
    border-radius: 14px;
    object-fit: cover;
    border: 1px solid #dfe7f1;
    background: #f6f9fc;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    color: #9aa8ba;
}
.loan-customer-mini-profile h4 { font-weight: 800; margin-top: 12px; color: #24364b; }
.loan-action-tabs { display: flex; flex-wrap: wrap; gap: 10px; }
.loan-action-tabs a { display: inline-block; padding: 10px 14px; border: 1px solid #dfe7f1; border-radius: 10px; background: #fff; color: #405166; font-weight: 700; }
.loan-action-tabs a.active,
.loan-action-tabs a:hover { background: #2f80c7; color: #fff; border-color: #2f80c7; text-decoration: none; }
.loan-notes-box { min-height: 160px; background: #f6f9fc; border: 1px solid #dfe7f1; border-radius: 12px; padding: 15px; }
.erp-document-grid .erp-document-card { margin-bottom: 14px; }
.erp-document-thumb { height: 130px; border: 1px solid #dfe7f1; border-radius: 12px; background: #f6f9fc; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.erp-document-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
.erp-document-label { margin-top: 6px; text-align: center; font-weight: 700; color: #34495e; }
</style>
@endsection
