@extends('pos::layouts.app')
@section('title','POS Module - Settings')
@section('pos-content')
@php
    $settings = $settings ?? [];
    $groups = $groups ?? [];
    $fieldLabels = [
        'business_name' => 'Receipt Business Name',
        'header_text' => 'Receipt Header Text',
        'footer_text' => 'Receipt Footer Text',
        'show_logo' => 'Show Logo',
        'show_cashier' => 'Show Cashier',
        'show_customer' => 'Show Customer',
        'paper_width' => 'Receipt Paper Width',
        'auto_print_after_sale' => 'Auto Print After Sale',
        'default_tax_rate' => 'Default Tax Rate %',
        'allow_line_discount' => 'Allow Line Discount',
        'allow_bill_discount' => 'Allow Bill Discount',
        'max_discount_percent' => 'Maximum Discount %',
        'manager_approval_discount_percent' => 'Manager Approval Above %',
        'price_includes_tax' => 'Price Includes Tax',
        'default_register_name' => 'Default Register Name',
        'require_shift_open' => 'Require Open Shift',
        'allow_negative_stock' => 'Allow Negative Stock',
        'barcode_quantity_mode' => 'Barcode Quantity Mode',
        'cash_drawer_enabled' => 'Cash Drawer Enabled',
        'rounding_mode' => 'Rounding Mode',
        'manager_approval_void' => 'Manager Approval for Void',
        'manager_approval_return' => 'Manager Approval for Return',
        'lock_sale_after_print' => 'Lock Sale After Print',
        'audit_all_changes' => 'Audit All Changes',
        'allow_backdated_sale' => 'Allow Backdated Sale',
        'session_timeout_minutes' => 'Session Timeout Minutes',
        'manager_approval_exchange' => 'Manager Approval for Exchange',
        'manager_approval_price_override' => 'Manager Approval for Price Override',
        'template_name' => 'Template Name',
        'receipt_type' => 'Receipt Type',
        'logo_position' => 'Logo Position',
        'show_qr_code' => 'Show QR Code',
        'show_barcode' => 'Show Barcode',
        'show_tax_summary' => 'Show Tax Summary',
        'show_terms' => 'Show Terms',
        'terms_text' => 'Terms Text',
        'barcode_type' => 'Barcode Type',
        'label_width_mm' => 'Label Width (mm)',
        'label_height_mm' => 'Label Height (mm)',
        'show_product_name' => 'Show Product Name',
        'show_price' => 'Show Price',
        'show_sku' => 'Show SKU',
        'receipt_printer_name' => 'Receipt Printer Name',
        'barcode_printer_name' => 'Barcode Printer Name',
        'cash_drawer_command' => 'Cash Drawer Command',
        'scanner_mode' => 'Scanner Mode',
        'customer_display_enabled' => 'Customer Display Enabled',
        'sale_prefix' => 'Sale Prefix',
        'return_prefix' => 'Return Prefix',
        'exchange_prefix' => 'Exchange Prefix',
        'shift_prefix' => 'Shift Prefix',
        'next_sale_no' => 'Next Sale No',
        'next_return_no' => 'Next Return No',
    ];
    $selectFields = ['show_logo','show_cashier','show_customer','auto_print_after_sale','allow_line_discount','allow_bill_discount','price_includes_tax','require_shift_open','allow_negative_stock','barcode_quantity_mode','cash_drawer_enabled','manager_approval_void','manager_approval_return','lock_sale_after_print','audit_all_changes','allow_backdated_sale','manager_approval_exchange','manager_approval_price_override','show_qr_code','show_barcode','show_tax_summary','show_terms','show_product_name','show_price','show_sku','customer_display_enabled'];
@endphp

<div class="pos-page-hero">
    <div>
        <span class="pos-eyebrow">POS MODULE</span>
        <h1>Configuration Center</h1>
        <p>Manage receipt, tax, register, terminal and approval settings for the standalone POS module.</p>
    </div>
    <a class="pos-btn pos-btn-primary" href="{{ route('pos.dashboard') }}">Back to Dashboard</a>
</div>

@if(session('status'))
    <div class="pos-alert pos-alert-success">{{ session('status') }}</div>
@endif

<div class="ch-grid ch-grid-4 pos-settings-nav">
    <a href="{{ route('pos.settings.receipt_designer') }}" class="ch-card pos-settings-tile"><div class="ch-card-icon"><i class="fa fa-file-text-o"></i></div><h4>Receipt Designer</h4><p>Thermal and A4 templates</p><span>Open →</span></a>
    <a href="{{ route('pos.settings.barcode_designer') }}" class="ch-card pos-settings-tile"><div class="ch-card-icon"><i class="fa fa-barcode"></i></div><h4>Barcode Designer</h4><p>Labels and batch printing</p><span>Open →</span></a>
    <a href="{{ route('pos.settings.terminal') }}" class="ch-card pos-settings-tile"><div class="ch-card-icon"><i class="fa fa-desktop"></i></div><h4>Terminal Settings</h4><p>Register and shift rules</p><span>Open →</span></a>
    <a href="{{ route('pos.settings.security') }}" class="ch-card pos-settings-tile"><div class="ch-card-icon"><i class="fa fa-lock"></i></div><h4>Security Rules</h4><p>Approvals and restrictions</p><span>Open →</span></a>
</div>

<form method="POST" action="{{ route('pos.settings.store') }}" class="pos-settings-form">
    @csrf
    <div class="pos-grid pos-grid-2">
        @foreach($groups as $groupKey => $groupTitle)
            <div id="{{ $groupKey }}" class="pos-card pos-card-accent pos-accent-{{ $loop->iteration }}">
                <div class="pos-card-header">
                    <div>
                        <span class="pos-icon-square">{{ $loop->iteration }}</span>
                        <h3>{{ $groupTitle }}</h3>
                    </div>
                </div>
                <div class="pos-form-grid">
                    @foreach(($settings[$groupKey] ?? []) as $key => $value)
                        <div class="pos-form-group {{ in_array($key, ['header_text','footer_text']) ? 'pos-form-wide' : '' }}">
                            <label>{{ $fieldLabels[$key] ?? ucwords(str_replace('_',' ', $key)) }}</label>
                            @if(in_array($key, $selectFields))
                                <select name="{{ $groupKey }}[{{ $key }}]" class="pos-input">
                                    <option value="1" {{ (string)$value === '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ (string)$value === '0' ? 'selected' : '' }}>No</option>
                                </select>
                            @elseif(in_array($key, ['header_text','footer_text']))
                                <textarea name="{{ $groupKey }}[{{ $key }}]" class="pos-input" rows="2">{{ $value }}</textarea>
                            @elseif($key === 'paper_width')
                                <select name="{{ $groupKey }}[{{ $key }}]" class="pos-input">
                                    <option value="58mm" {{ $value === '58mm' ? 'selected' : '' }}>58mm</option>
                                    <option value="80mm" {{ $value === '80mm' ? 'selected' : '' }}>80mm</option>
                                    <option value="A4" {{ $value === 'A4' ? 'selected' : '' }}>A4</option>
                                </select>
                            @elseif($key === 'rounding_mode')
                                <select name="{{ $groupKey }}[{{ $key }}]" class="pos-input">
                                    <option value="none" {{ $value === 'none' ? 'selected' : '' }}>No rounding</option>
                                    <option value="nearest" {{ $value === 'nearest' ? 'selected' : '' }}>Nearest amount</option>
                                    <option value="up" {{ $value === 'up' ? 'selected' : '' }}>Round up</option>
                                    <option value="down" {{ $value === 'down' ? 'selected' : '' }}>Round down</option>
                                </select>
                            @else
                                <input type="text" name="{{ $groupKey }}[{{ $key }}]" value="{{ $value }}" class="pos-input">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="pos-sticky-actions">
        <button type="submit" class="pos-btn pos-btn-success">Save POS Settings</button>
        <a href="{{ route('pos.configuration.index') }}" class="pos-btn pos-btn-secondary">Configuration Home</a>
    </div>
</form>
@endsection
