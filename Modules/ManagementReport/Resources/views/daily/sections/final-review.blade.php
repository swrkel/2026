<div class="mgmt-review-grid">
    @foreach($data['rows'] as $review)
        <div class="mgmt-review-item mgmt-review-{{ $review['status'] }}">
            <small>{{ $review['label'] }}</small>
            <strong>{{ number_format((float) $review['value'], data_get($meta, 'currency_decimals', 2)) }}</strong>
            <span>{{ $review['status'] === 'attention' ? 'Needs attention' : ucfirst($review['status']) }}</span>
        </div>
    @endforeach
</div>
