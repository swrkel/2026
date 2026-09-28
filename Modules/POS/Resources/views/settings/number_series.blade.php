@extends('pos::layouts.app')
@section('title','POS Module - Number Series')
@section('pos-content')
<div class="pos-page-hero">
    <div>
        <span class="pos-eyebrow">POS CONFIGURATION</span>
        <h1>Number Series</h1>
        <p>Configure POS invoice, return, exchange and shift prefixes with next-number controls.</p>
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

<div class="pos-card"><div class="pos-card-header"><h3><i class="fa fa-sort-numeric-asc"></i> Numbering Preview</h3></div>
<div class="ch-grid ch-grid-4"><div><strong>Sale</strong><br>POS-000001</div><div><strong>Return</strong><br>RET-000001</div><div><strong>Exchange</strong><br>EXC-000001</div><div><strong>Shift</strong><br>SHF-000001</div></div>
<a href="{{ route('pos.settings.index') }}#number_series" class="btn btn-success btn-sm">Edit Number Series</a></div>
@endsection
