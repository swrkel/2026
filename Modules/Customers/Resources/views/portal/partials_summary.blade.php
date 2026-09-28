<div class="dd-summary">
    <div class="dd-summary-item">
        <div class="dd-summary-label">Customer</div>
        <div class="dd-summary-value" style="font-size:18px;">{{ $customer->name }}</div>
    </div>
    <div class="dd-summary-item">
        <div class="dd-summary-label">Customer Code</div>
        <div class="dd-summary-value">{{ $customer->contact_id ?: '-' }}</div>
    </div>
    <div class="dd-summary-item">
        <div class="dd-summary-label">Current Balance</div>
        <div class="dd-summary-value">{{ number_format((float)($summary['current_balance'] ?? $summary['outstanding'] ?? 0), 2) }}</div>
    </div>
    <div class="dd-summary-item">
        <div class="dd-summary-label">Credit Limit</div>
        <div class="dd-summary-value">{{ is_null($customer->credit_limit) ? 'No Limit' : number_format((float)$customer->credit_limit, 2) }}</div>
    </div>
    <div class="dd-summary-item">
        <div class="dd-summary-label">Available Credit</div>
        <div class="dd-summary-value">{{ number_format((float)($summary['available_credit'] ?? 0), 2) }}</div>
    </div>
</div>
