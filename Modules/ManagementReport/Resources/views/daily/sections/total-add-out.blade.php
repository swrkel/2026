@php
    $totalAddData = data_get($totalAddSection, 'payload', []);
    $totalOutData = data_get($totalOutSection, 'payload', []);
    $totalAddRows = data_get($totalAddData, 'rows', []);
    $totalOutRows = data_get($totalOutData, 'rows', []);
    $padToRows = max(count($totalAddRows), count($totalOutRows));
@endphp

<div class="mgmt-two-column mgmt-total-add-out-grid">
    <div class="mgmt-total-add-out-column">
        <div class="mgmt-report-section-title">
            <span>{{ $totalAddNumber }}</span>
            <h3>Total Add</h3>
        </div>

        @include('managementreport::daily.sections.total-add', [
            'data' => $totalAddData,
            'meta' => $meta,
            'padToRows' => $padToRows,
        ])
    </div>

    <div class="mgmt-total-add-out-column">
        <div class="mgmt-report-section-title">
            <span>{{ $totalOutNumber }}</span>
            <h3>Total Out</h3>
        </div>

        @include('managementreport::daily.sections.out', [
            'data' => $totalOutData,
            'meta' => $meta,
            'padToRows' => $padToRows,
        ])
    </div>
</div>
