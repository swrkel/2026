@php
    $outstandingData = data_get($outstandingSection, 'payload', []);
    $stockValueData = data_get($stockValueSection, 'payload', []);
@endphp

<div class="mgmt-two-column mgmt-outstanding-stock-grid">
    <div class="mgmt-outstanding-stock-column">
        <div class="mgmt-report-section-title">
            <span>{{ $outstandingNumber }}</span>
            <h3>{{ data_get($outstandingSection, 'label', 'Outstanding Details') }}</h3>
        </div>

        @include('managementreport::daily.sections.outstanding', [
            'data' => $outstandingData,
            'meta' => $meta,
        ])
    </div>

    <div class="mgmt-outstanding-stock-column">
        <div class="mgmt-report-section-title">
            <span>{{ $stockValueNumber }}</span>
            <h3>{{ data_get($stockValueSection, 'label', 'Stock Value Status') }}</h3>
        </div>

        @include('managementreport::daily.sections.stock-value', [
            'data' => $stockValueData,
            'meta' => $meta,
        ])
    </div>
</div>
