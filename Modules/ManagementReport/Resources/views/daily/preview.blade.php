<div class="mgmt-report-paper">
    <div class="mgmt-preview-toolbar">
        <span><i class="fa fa-eye"></i> Live Preview</span>
        <small>This is a date-effective live report. Save Snapshot only preserves an optional audit copy.</small>
    </div>
    @include('managementreport::daily.partials.report-body', ['report' => $report])
</div>
