<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ data_get($customer, 'name') }} - {{ __('customers::lang.customer_profile') }}</title>
    <style>
        body { font-family: Arial, sans-serif; color:#111827; margin:24px; }
        .print-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #2563eb; padding-bottom:14px; margin-bottom:18px; }
        .print-title { font-size:24px; font-weight:700; margin:0; }
        .print-subtitle { color:#64748b; margin-top:4px; }
        .badge { display:inline-block; padding:6px 10px; border-radius:999px; background:#eaf2ff; color:#2563eb; font-weight:700; }
        .grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-bottom:18px; }
        .card { border:1px solid #e5e7eb; border-radius:10px; padding:12px; background:#f8fafc; }
        .label { color:#64748b; font-size:12px; text-transform:uppercase; font-weight:700; display:block; margin-bottom:4px; }
        .value { font-size:16px; font-weight:700; }
        table { width:100%; border-collapse:collapse; margin-top:14px; }
        th, td { border:1px solid #e5e7eb; padding:10px; text-align:left; vertical-align:top; }
        th { width:28%; background:#f8fafc; color:#374151; }
        .footer { margin-top:24px; color:#64748b; font-size:12px; border-top:1px solid #e5e7eb; padding-top:10px; }
        .actions { text-align:right; margin-bottom:14px; }
        .btn { border:0; background:#2563eb; color:#fff; padding:9px 14px; border-radius:8px; cursor:pointer; font-weight:700; }
        @media print {
            .actions { display:none; }
            body { margin:12mm; }
            .card { break-inside:avoid; }
        }
        @media (max-width: 768px) {
            .print-header, .grid { display:block; }
            .card { margin-bottom:10px; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="btn" onclick="window.print()">{{ __('customers::lang.print_profile') }}</button>
    </div>

    <div class="print-header">
        <div>
            <h1 class="print-title">{{ data_get($customer, 'name') }}</h1>
            <div class="print-subtitle">{{ __('customers::lang.customer_profile') }}</div>
        </div>
        <div class="badge">{{ data_get($customer, 'contact_id', '-') ?: '-' }}</div>
    </div>

    <div class="grid">
        <div class="card">
            <span class="label">{{ __('customers::lang.mobile') }}</span>
            <span class="value">{{ data_get($customer, 'mobile', '-') ?: '-' }}</span>
        </div>
        <div class="card">
            <span class="label">{{ __('customers::lang.email') }}</span>
            <span class="value">{{ data_get($customer, 'email', '-') ?: '-' }}</span>
        </div>
        <div class="card">
            <span class="label">{{ __('customers::lang.credit_limit') }}</span>
            <span class="value">{{ number_format((float) data_get($customer, 'credit_limit', 0), 2) }}</span>
        </div>
    </div>

    <table>
        <tr><th>{{ __('customers::lang.customer_code') }}</th><td>{{ data_get($customer, 'contact_id', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.name') }}</th><td>{{ data_get($customer, 'name', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.business_name') }}</th><td>{{ data_get($customer, 'supplier_business_name', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.mobile') }}</th><td>{{ data_get($customer, 'mobile', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.alternate_number') }}</th><td>{{ data_get($customer, 'alternate_number', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.email') }}</th><td>{{ data_get($customer, 'email', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.address_line_1') }}</th><td>{{ data_get($customer, 'address_line_1', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.address_line_2') }}</th><td>{{ data_get($customer, 'address_line_2', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.city') }}</th><td>{{ data_get($customer, 'city', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.state') }}</th><td>{{ data_get($customer, 'state', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.country') }}</th><td>{{ data_get($customer, 'country', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.zip_code') }}</th><td>{{ data_get($customer, 'zip_code', '-') ?: '-' }}</td></tr>
        <tr><th>{{ __('customers::lang.tax_number') }}</th><td>{{ data_get($customer, 'tax_number', '-') ?: '-' }}</td></tr>
    </table>

    <div class="footer">
        {{ __('customers::lang.printed_on') }}: {{ date('Y-m-d H:i') }}
    </div>

    <script>
        window.onload = function () {
            setTimeout(function () { window.print(); }, 400);
        };
    </script>
</body>
</html>
