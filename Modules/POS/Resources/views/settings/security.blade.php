@extends('pos::layouts.app')
@section('title','POS Module - Security & Approval Rules')
@section('pos-content')
<div class="pos-page-hero">
    <div>
        <span class="pos-eyebrow">POS CONFIGURATION</span>
        <h1>Security & Approval Rules</h1>
        <p>Manage approvals for voids, refunds, exchanges, price overrides, discounts, backdated sales and session timeout.</p>
    </div>
    <div class="ch-quick-actions">
        <a class="btn btn-primary btn-sm" href="{{ route('pos.settings.index') }}"><i class="fa fa-cogs"></i> Settings Home</a>
        <a class="btn btn-default btn-sm" href="{{ route('pos.dashboard') }}"><i class="fa fa-dashboard"></i> Dashboard</a>
    </div>
</div>
<div class="ch-grid ch-grid-4 pos-settings-nav">
        <a href="{{ route('pos.settings.receipt_designer') }}" class="ch-card pos-settings-tile">
            <div class="ch-card-icon"><i class="fa fa-file-text-o"></i></div>
            <h4>Receipt Designer</h4>
            <p>Thermal and A4 receipt templates</p>
            <span>Open →</span>
        </a>

        <a href="{{ route('pos.settings.barcode_designer') }}" class="ch-card pos-settings-tile">
            <div class="ch-card-icon"><i class="fa fa-barcode"></i></div>
            <h4>Barcode Designer</h4>
            <p>Product and shelf label templates</p>
            <span>Open →</span>
        </a>

        <a href="{{ route('pos.settings.terminal') }}" class="ch-card pos-settings-tile">
            <div class="ch-card-icon"><i class="fa fa-desktop"></i></div>
            <h4>Terminal Settings</h4>
            <p>Register, terminal and shift rules</p>
            <span>Open →</span>
        </a>

        <a href="{{ route('pos.settings.hardware') }}" class="ch-card pos-settings-tile">
            <div class="ch-card-icon"><i class="fa fa-print"></i></div>
            <h4>Hardware</h4>
            <p>Printers, cash drawer and scanners</p>
            <span>Open →</span>
        </a>

        <a href="{{ route('pos.settings.security') }}" class="ch-card pos-settings-tile">
            <div class="ch-card-icon"><i class="fa fa-lock"></i></div>
            <h4>Security</h4>
            <p>Approval rules and cashier restrictions</p>
            <span>Open →</span>
        </a>

        <a href="{{ route('pos.settings.number_series') }}" class="ch-card pos-settings-tile">
            <div class="ch-card-icon"><i class="fa fa-sort-numeric-asc"></i></div>
            <h4>Number Series</h4>
            <p>Invoice, return and shift numbering</p>
            <span>Open →</span>
        </a>
</div>

<div class="pos-card"><div class="pos-card-header"><h3><i class="fa fa-shield"></i> Approval Matrix</h3></div>
<table class="table table-hover"><tr><th>Action</th><th>Status</th></tr><tr><td>Void Sale</td><td><span class="label label-success">Approval controlled</span></td></tr><tr><td>Return/Refund</td><td><span class="label label-success">Approval controlled</span></td></tr><tr><td>Price Override</td><td><span class="label label-warning">Permission controlled</span></td></tr></table>
<a href="{{ route('pos.settings.index') }}#security" class="btn btn-success btn-sm">Edit Security Settings</a></div>
@endsection
