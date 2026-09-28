@extends('bankingaml::layouts.master')

@section('content')
<div class="bkg-rc2-page">
    <div class="bkg-page-header">
        <h1>{{ $pageTitle ?? 'Screening' }}</h1>
        <p>KYC review, PEP/sanctions screening, AML cases, compliance alerts and regulatory dashboard.</p>
    </div>
    <div class="bkg-toolbar">
        <input type="text" class="form-control" placeholder="Search">
        <input type="text" class="form-control" placeholder="Date Range">
        <button class="btn btn-primary">CSV</button>
        <button class="btn btn-primary">Excel</button>
        <button class="btn btn-primary">PDF</button>
        <button class="btn btn-primary">Print</button>
        <button class="btn btn-primary">Column Visibility</button>
    </div>
    <div class="bkg-card-grid">
        <div class="bkg-card"><strong>Status</strong><span>Ready for UI testing</span></div>
        <div class="bkg-card"><strong>Permission</strong><span>bankingaml.view</span></div>
        <div class="bkg-card"><strong>Module</strong><span>AML & Compliance</span></div>
    </div>
    <div class="bkg-panel">
        <h3>Screening</h3>
        <p>This standalone Banking Suite page is prepared for tester navigation and workflow validation. Backend posting logic can be extended after UI approval.</p>
    </div>
</div>
@endsection
