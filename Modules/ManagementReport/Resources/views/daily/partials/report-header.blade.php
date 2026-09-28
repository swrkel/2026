<header class="mgmt-report-header">
    <div class="mgmt-report-brand">
        <div class="mgmt-report-logo">{{ strtoupper(substr(data_get($report, 'meta.business_name', 'M'), 0, 1)) }}</div>
        <div>
            <h1>{{ data_get($report, 'meta.business_name', 'Business') }}</h1>
            <h2>Daily Management Report</h2>
        </div>
    </div>
    <div class="mgmt-report-meta">
        <div><span>Period</span><strong>{{ data_get($report, 'meta.period_label') }}</strong></div>
        <div><span>Location</span><strong>{{ data_get($report, 'meta.location_name') }}</strong></div>
        <div><span>Store</span><strong>{{ data_get($report, 'meta.store_name') }}</strong></div>
        <div><span>Data as at</span><strong>{{ \Carbon\Carbon::parse(data_get($report, 'meta.data_as_of', data_get($report, 'meta.end_date')))->format('d M Y, h:i A') }}</strong></div>
        <div><span>Generated</span><strong>{{ \Carbon\Carbon::parse(data_get($report, 'meta.generated_at'))->format('d M Y, h:i A') }}</strong></div>
    </div>
</header>
