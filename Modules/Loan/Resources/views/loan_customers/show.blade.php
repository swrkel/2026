@extends('layouts.app')
@section('title', 'Loan Customer Profile')

@section('content')
@php
    $photoUrl = method_exists($customer, 'imageUrl') ? $customer->imageUrl('photo') : null;
    $nicFrontUrl = method_exists($customer, 'imageUrl') ? $customer->imageUrl('nic_front_image') : null;
    $nicBackUrl = method_exists($customer, 'imageUrl') ? $customer->imageUrl('nic_back_image') : null;
    $signatureUrl = method_exists($customer, 'imageUrl') ? $customer->imageUrl('signature_image') : null;
    $status = $customer->status ?: 'active';
    $statusClass = $status == 'active' ? 'success' : ($status == 'blacklisted' ? 'danger' : 'default');
    try {
        $dob = !empty($customer->date_of_birth) ? \Carbon\Carbon::parse($customer->date_of_birth)->format('Y-m-d') : '-';
    } catch (\Exception $e) {
        $dob = $customer->date_of_birth ?: '-';
    }
@endphp

<section class="content" style="padding:0;">
    <div class="loan-customer-entry-page loan-customer-view-page">
        <div class="loan-page-top">
            <div>
                <h2><i class="fa fa-user"></i> Loan Customer Profile</h2>
                <p><i class="fa fa-bank"></i> {{ $customer->customer_no }} &nbsp; | &nbsp; {{ $customer->display_name }}</p>
            </div>
            <div class="loan-page-actions">
                <a href="{{ route('loan.customers.index') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a>
                <a href="{{ route('loan.customers.edit', $customer->id) }}" class="btn btn-primary"><i class="fa fa-pencil"></i> Edit Customer</a>
            </div>
        </div>

        <div class="loan-card loan-profile-summary-card">
            <div class="row">
                <div class="col-md-2 text-center">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" class="loan-profile-photo" alt="Loan customer photo">
                    @else
                        <div class="loan-profile-photo-placeholder"><i class="fa fa-user"></i></div>
                    @endif
                </div>
                <div class="col-md-7">
                    <h2 class="loan-profile-title">{{ $customer->display_name }}</h2>
                    <div class="loan-profile-meta">
                        <span><i class="fa fa-id-card"></i> {{ $customer->nic ?: 'No NIC / ID' }}</span>
                        <span><i class="fa fa-phone"></i> {{ $customer->mobile ?: 'No Mobile' }}</span>
                        <span><i class="fa fa-envelope"></i> {{ $customer->email ?: 'No Email ID' }}</span>
                    </div>
                    <span class="label label-{{ $statusClass }} loan-status-label">{{ ucfirst($status) }}</span>
                </div>
                <div class="col-md-3 text-right">
                    <div class="loan-customer-badge">
                        <span>Loan Customer No</span>
                        <strong>{{ $customer->customer_no }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-title"><i class="fa fa-user"></i> Customer Information</div>
                    <table class="table loan-detail-table">
                        <tr><th>Loan Customer No</th><td>{{ $customer->customer_no }}</td></tr>
                        <tr><th>Title</th><td>{{ $customer->title ?: '-' }}</td></tr>
                        <tr><th>First Name</th><td>{{ $customer->first_name ?: '-' }}</td></tr>
                        <tr><th>Middle Name</th><td>{{ $customer->middle_name ?: '-' }}</td></tr>
                        <tr><th>Last Name</th><td>{{ $customer->last_name ?: '-' }}</td></tr>
                        <tr><th>NIC / ID No</th><td>{{ $customer->nic ?: '-' }}</td></tr>
                        <tr><th>Date of Birth</th><td>{{ $dob }}</td></tr>
                        <tr><th>Gender</th><td>{{ ucfirst($customer->gender ?: '-') }}</td></tr>
                        <tr><th>Marital Status</th><td>{{ ucfirst($customer->marital_status ?: '-') }}</td></tr>
                        <tr><th>Status</th><td><span class="label label-{{ $statusClass }}">{{ ucfirst($status) }}</span></td></tr>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-title"><i class="fa fa-address-book"></i> Contact Information</div>
                    <table class="table loan-detail-table">
                        <tr><th>Mobile</th><td>{{ $customer->mobile ?: '-' }}</td></tr>
                        <tr><th>Phone</th><td>{{ $customer->phone ?: '-' }}</td></tr>
                        <tr><th>Alternate Mobile</th><td>{{ $customer->alternate_mobile ?: '-' }}</td></tr>
                        <tr><th>Email</th><td>{{ $customer->email ?: 'No Email ID' }}</td></tr>
                        <tr><th>Address Line 1</th><td>{{ $customer->address ?: '-' }}</td></tr>
                        <tr><th>Address Line 2</th><td>{{ $customer->address_line_2 ?: '-' }}</td></tr>
                        <tr><th>City</th><td>{{ $customer->city ?: '-' }}</td></tr>
                        <tr><th>District</th><td>{{ $customer->district ?: '-' }}</td></tr>
                        <tr><th>Province</th><td>{{ $customer->province ?: '-' }}</td></tr>
                        <tr><th>Postal Code</th><td>{{ $customer->postal_code ?: '-' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-title"><i class="fa fa-briefcase"></i> Loan Profile</div>
                    <table class="table loan-detail-table">
                        <tr><th>Customer Type</th><td>{{ ucfirst($customer->customer_type ?: 'Individual') }}</td></tr>
                        <tr><th>Risk Grade</th><td>{{ $customer->risk_grade ?: '-' }}</td></tr>
                        <tr><th>Preferred Branch</th><td>{{ $customer->branch_name ?: '-' }}</td></tr>
                        <tr><th>Loan Officer</th><td>{{ $customer->loan_officer ?: '-' }}</td></tr>
                        <tr><th>Occupation</th><td>{{ $customer->occupation ?: '-' }}</td></tr>
                        <tr><th>Employer / Business Name</th><td>{{ $customer->employer_name ?: '-' }}</td></tr>
                        <tr><th>Monthly Income</th><td>{{ number_format((float)($customer->monthly_income ?? 0), 2) }}</td></tr>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-title"><i class="fa fa-file-image-o"></i> Images / Documents</div>
                    <div class="row loan-document-grid">
                        @include('loan::loan_customers.partials.document_preview', ['label' => 'Photo', 'url' => $photoUrl])
                        @include('loan::loan_customers.partials.document_preview', ['label' => 'NIC Front', 'url' => $nicFrontUrl])
                        @include('loan::loan_customers.partials.document_preview', ['label' => 'NIC Back', 'url' => $nicBackUrl])
                        @include('loan::loan_customers.partials.document_preview', ['label' => 'Signature', 'url' => $signatureUrl])
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-title"><i class="fa fa-sticky-note"></i> Notes</div>
                    <div class="loan-notes-box">{!! nl2br(e($customer->notes ?: 'No notes added.')) !!}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-title"><i class="fa fa-history"></i> Audit Information</div>
                    <table class="table loan-detail-table">
                        <tr><th>Created At</th><td>{{ optional($customer->created_at)->format('Y-m-d H:i') ?: '-' }}</td></tr>
                        <tr><th>Updated At</th><td>{{ optional($customer->updated_at)->format('Y-m-d H:i') ?: '-' }}</td></tr>
                        <tr><th>Created By</th><td>{{ $customer->created_by ?: '-' }}</td></tr>
                        <tr><th>Updated By</th><td>{{ $customer->updated_by ?: '-' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="loan-card">
            <div class="loan-card-title"><i class="fa fa-sitemap"></i> Related Loan Customer Functions</div>
            <div class="row loan-related-actions">
                <div class="col-md-3 col-sm-6"><a href="{{ route('loan.customers.ledger', $customer->id) }}" class="btn loan-related-btn"><i class="fa fa-book"></i> Customer Ledger</a></div>
                <div class="col-md-3 col-sm-6"><a href="{{ route('loan.customers.applications', $customer->id) }}" class="btn loan-related-btn"><i class="fa fa-file-text-o"></i> Loan Applications</a></div>
                <div class="col-md-3 col-sm-6"><a href="{{ route('loan.customers.active_loans', $customer->id) }}" class="btn loan-related-btn"><i class="fa fa-check-circle"></i> Active Loans</a></div>
                <div class="col-md-3 col-sm-6"><a href="{{ route('loan.customers.documents', $customer->id) }}" class="btn loan-related-btn"><i class="fa fa-folder-open"></i> Documents</a></div>
                <div class="col-md-3 col-sm-6"><a href="{{ route('loan.customers.notes', $customer->id) }}" class="btn loan-related-btn"><i class="fa fa-sticky-note"></i> Notes</a></div>
                <div class="col-md-3 col-sm-6"><a href="{{ route('loan.customers.audit_log', $customer->id) }}" class="btn loan-related-btn"><i class="fa fa-history"></i> Audit Log</a></div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('css')
<style>
    /* LOAN-23: Scoped only to Loan Customer View. Does not affect sidebar or other modules. */
    .loan-customer-entry-page { padding: 12px 18px 85px 18px; background: #f4f8fb; }
    .loan-customer-entry-page .loan-page-top {
        background: #fff; border-radius: 18px; padding: 22px 28px; margin-bottom: 20px;
        box-shadow: 0 10px 28px rgba(15, 48, 80, 0.08); border-left: 5px solid #1d9de0;
        display: flex; justify-content: space-between; align-items: center; gap: 20px;
    }
    .loan-customer-entry-page .loan-page-top h2 { margin: 0; color: #1f3349; font-size: 28px; font-weight: 700; line-height: 1.2; }
    .loan-customer-entry-page .loan-page-top p { margin: 7px 0 0 0; color: #6f8090; font-size: 14px; }
    .loan-customer-entry-page .loan-card {
        background: #fff; border-radius: 18px; padding: 24px 26px; margin-bottom: 22px;
        box-shadow: 0 10px 28px rgba(15, 48, 80, 0.08);
    }
    .loan-customer-entry-page .loan-card-title {
        font-size: 19px; font-weight: 700; color: #1f3349; margin-bottom: 20px; padding-bottom: 13px; border-bottom: 1px solid #e7eef5;
    }
    .loan-customer-entry-page .loan-card-title i { color: #1d9de0; margin-right: 7px; }
    .loan-page-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .loan-page-actions .btn { border-radius: 10px; padding: 10px 18px; font-weight: 700; }
    .loan-customer-badge { min-width: 210px; text-align: center; color: #fff; padding: 18px 24px; border-radius: 16px; background: linear-gradient(135deg, #2c7be5, #18b4d9); box-shadow: 0 12px 30px rgba(44,123,229,.22); display: inline-block; }
    .loan-customer-badge span { display: block; font-size: 12px; letter-spacing: .8px; text-transform: uppercase; opacity: .95; }
    .loan-customer-badge strong { display: block; margin-top: 6px; font-size: 24px; line-height: 1; font-weight: 800; }
    .loan-profile-photo,
    .loan-profile-photo-placeholder { width: 140px; height: 140px; border-radius: 18px; object-fit: cover; border: 1px solid #dfe7f1; background: #f6f9fc; display: inline-flex; align-items: center; justify-content: center; font-size: 54px; color: #9aa8ba; }
    .loan-profile-title { margin: 10px 0 12px; font-weight: 800; color: #24364b; }
    .loan-profile-meta { display: flex; flex-wrap: wrap; gap: 12px; color: #62748a; margin-bottom: 10px; }
    .loan-detail-table { margin-bottom: 0; }
    .loan-detail-table th { width: 215px; color: #34495e; background: #f6f9fc; font-weight: 700; vertical-align: middle !important; }
    .loan-detail-table td { color: #24364b; vertical-align: middle !important; }
    .loan-notes-box { min-height: 130px; background: #f6f9fc; border: 1px solid #dfe7f1; border-radius: 12px; padding: 14px; color: #34495e; }
    .loan-related-btn { width: 100%; margin-bottom: 12px; background: #fff; border: 1px solid #dfe7f1; border-radius: 14px; padding: 16px 10px; font-weight: 800; color: #405166; box-shadow: 0 5px 18px rgba(25,42,70,.06); text-align: center; }
    .loan-related-btn i { display: block; font-size: 22px; margin-bottom: 8px; color: #2675e7; }
    .loan-related-btn:hover { background: #f3f9ff; color: #1f6fb2; }
    .loan-document-grid .loan-document-card,
    .loan-document-grid .erp-document-card { margin-bottom: 14px; }
    .erp-document-thumb, .loan-document-thumb { height: 105px; border: 1px solid #dfe7f1; border-radius: 12px; background: #f6f9fc; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .erp-document-thumb img, .loan-document-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .erp-document-label, .loan-document-label { margin-top: 6px; text-align: center; font-weight: 700; color: #34495e; }
    @media (max-width: 991px) {
        .loan-customer-entry-page .loan-page-top { display: block; }
        .loan-page-actions { margin-top: 15px; }
        .loan-page-actions .btn { width: 100%; }
        .loan-customer-badge { margin-top: 15px; width: 100%; }
        .loan-profile-summary-card .text-right { text-align: left !important; }
    }
</style>
@endsection
