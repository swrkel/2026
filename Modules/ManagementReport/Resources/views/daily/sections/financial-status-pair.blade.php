<div class="mgmt-two-column mgmt-financial-pair">
    <div class="mgmt-financial-pair-item">
        <div class="mgmt-report-section-title">
            <span>{{ $financialStatusNumber }}</span>
            <h3>{{ data_get($financialStatusSection, 'label', 'Financial Status') }}</h3>
        </div>
        @include('managementreport::daily.sections.financial-status', [
            'data' => data_get($financialStatusSection, 'payload', []),
            'meta' => $meta,
        ])
    </div>

    <div class="mgmt-financial-pair-item">
        <div class="mgmt-report-section-title">
            <span>{{ $financialStatusTwoNumber }}</span>
            <h3>{{ data_get($financialStatusTwoSection, 'label', 'Financial Status II') }}</h3>
        </div>
        @include('managementreport::daily.sections.financial-status-two', [
            'data' => data_get($financialStatusTwoSection, 'payload', []),
            'meta' => $meta,
        ])
    </div>
</div>
