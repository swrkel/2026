@extends('layouts.app')

@section('title', __('petropd::lang.payment_reconciliation_report'))

@section('content')
@php
    $defaultStartDate = now()->startOfYear()->format('Y-m-d');
    $defaultEndDate = now()->endOfYear()->format('Y-m-d');
    $reportCssPath = module_path('PetroPD', 'Resources/css/payment-reconciliation-report.css');
@endphp

@if (is_file($reportCssPath))
<style>{!! file_get_contents($reportCssPath) !!}</style>
@endif

<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">@lang('petropd::lang.payment_reconciliation_report')</h1>
</section>

<section class="content petropd-payment-reconciliation-report">
    <div class="ppr-shell">
        <div class="ppr-hero">
            <div>
                <div class="ppr-eyebrow">@lang('petropd::lang.petro_pd') · Financial Integrity</div>
                <h1>@lang('petropd::lang.payment_reconciliation_report')</h1>
                <p>Every mismatch detected between the authoritative Pump Operator Payment, supporting payment records, Shift ID and settlement is recorded here. Open critical events block finalization and are never converted into false Excess or Shortage.</p>
            </div>
            <div class="ppr-hero-actions">
                <a href="{{ route('petropd.settlement-pd.create') }}" class="btn btn-primary ppr-btn">
                    <i class="fa fa-calculator"></i> @lang('petropd::lang.pd_settlement')
                </a>
                <a href="{{ route('petropd.list-pd-settlement') }}" class="btn btn-default ppr-btn">
                    <i class="fa fa-list"></i> @lang('petropd::lang.list_pd_settlement')
                </a>
            </div>
        </div>

        @if(empty($filters['eventTableAvailable']) || empty($filters['operationalColumnsAvailable']))
            <div class="alert alert-danger ppr-migration-alert">
                <i class="fa fa-exclamation-triangle"></i>
                The tenant payment-integrity control-centre migration has not been run completely. This report will remain empty until the Parcel 1 and Parcel 2 tenant migrations or combined master SQL are executed.
            </div>
        @endif

        <div class="ppr-kpi-grid">
            <div class="ppr-kpi danger">
                <div class="ppr-kpi-top"><span>Open Critical</span><i class="fa fa-lock"></i></div>
                <strong id="ppr_kpi_open_critical">0</strong>
                <small>Financial finalization blockers</small>
            </div>
            <div class="ppr-kpi warning">
                <div class="ppr-kpi-top"><span>Open Warnings</span><i class="fa fa-exclamation-circle"></i></div>
                <strong id="ppr_kpi_open_warnings">0</strong>
                <small>Review before settlement completion</small>
            </div>
            <div class="ppr-kpi info">
                <div class="ppr-kpi-top"><span>Affected Shifts</span><i class="fa fa-random"></i></div>
                <strong id="ppr_kpi_affected_shifts">0</strong>
                <small>Unique immutable Shift IDs</small>
            </div>
            <div class="ppr-kpi success">
                <div class="ppr-kpi-top"><span>Resolved</span><i class="fa fa-check-circle"></i></div>
                <strong id="ppr_kpi_resolved">0</strong>
                <small>Cleared by controlled recheck</small>
            </div>
            <div class="ppr-kpi neutral">
                <div class="ppr-kpi-top"><span>Total Events</span><i class="fa fa-shield"></i></div>
                <strong id="ppr_kpi_total">0</strong>
                <small>Latest seen: <span id="ppr_kpi_latest">-</span></small>
            </div>
        </div>

        <div class="ppr-card">
            <div class="ppr-card-header">
                <div>
                    <h3><i class="fa fa-filter text-primary"></i> Filters</h3>
                    <p>Filters apply to both the table and summary cards. Recheck never changes financial amounts.</p>
                </div>
                <button type="button" id="ppr_reset_filters" class="btn btn-default ppr-btn"><i class="fa fa-refresh"></i> Reset</button>
            </div>
            <div class="ppr-card-body">
                <div class="ppr-filter-grid">
                    <div class="ppr-field">
                        <label>Date Basis</label>
                        <select id="ppr_date_basis" class="form-control ppr-select2 ppr-filter">
                            <option value="last_seen">Last Seen</option>
                            <option value="first_seen">First Seen</option>
                            <option value="checked">Last Checked</option>
                            <option value="resolved">Resolved Date</option>
                        </select>
                    </div>
                    <div class="ppr-field">
                        <label>Date Range</label>
                        <select id="ppr_date_preset" class="form-control ppr-select2">
                            <option value="this_year" selected>This Year</option>
                            <option value="last_year">Last Year</option>
                            <option value="this_fy">This FY</option>
                            <option value="last_fy">Last FY</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="ppr-field"><label>From Date</label><input type="date" id="ppr_start_date" class="form-control" value="{{ $defaultStartDate }}"></div>
                    <div class="ppr-field"><label>To Date</label><input type="date" id="ppr_end_date" class="form-control" value="{{ $defaultEndDate }}"></div>
                    <div class="ppr-field">
                        <label>Business Location</label>
                        <select id="ppr_location_id" class="form-control ppr-select2 ppr-filter">
                            <option value="">All</option>
                            @foreach($filters['locations'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="ppr-field">
                        <label>Pump Operator</label>
                        <select id="ppr_pump_operator_id" class="form-control ppr-select2 ppr-filter">
                            <option value="">All</option>
                            @foreach($filters['pumpOperators'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="ppr-field">
                        <label>Settlement Number</label>
                        <select id="ppr_settlement_no" class="form-control ppr-select2 ppr-filter">
                            <option value="">All</option>
                            @foreach($filters['settlementNumbers'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="ppr-field"><label>Shift ID</label><input type="number" min="1" id="ppr_shift_id" class="form-control ppr-filter-input" placeholder="Exact Shift ID"></div>
                    <div class="ppr-field"><label>Pump Payment ID</label><input type="number" min="1" id="ppr_pump_payment_id" class="form-control ppr-filter-input" placeholder="Master Payment ID"></div>
                    <div class="ppr-field">
                        <label>Issue Type</label>
                        <select id="ppr_issue_type" class="form-control ppr-select2 ppr-filter">
                            <option value="">All</option>
                            @foreach($filters['issueTypes'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="ppr-field">
                        <label>Severity</label>
                        <select id="ppr_severity" class="form-control ppr-select2 ppr-filter">
                            <option value="">All</option><option value="critical">Critical</option><option value="warning">Warning</option><option value="info">Info</option>
                        </select>
                    </div>
                    <div class="ppr-field">
                        <label>Status</label>
                        <select id="ppr_status" class="form-control ppr-select2 ppr-filter">
                            <option value="open" selected>Open</option><option value="resolved">Resolved</option><option value="">All</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="ppr-card">
            <div class="ppr-card-header ppr-table-header">
                <div>
                    <h3><i class="fa fa-shield text-primary"></i> Reconciliation Events</h3>
                    <p>Use View to inspect evidence. Use Recheck only after correcting the source relationship or Shift ID.</p>
                </div>
                <div class="ppr-search"><input type="text" id="ppr_search" class="form-control" placeholder="Search settlement, operator, issue, payment ID..."><i class="fa fa-search"></i></div>
            </div>
            <div class="ppr-card-body">
                <div class="ppr-tools"><div id="ppr_buttons"></div></div>
                <div class="ppr-table-wrap">
                    <table id="petropd_payment_reconciliation_table" class="table table-bordered nowrap" width="100%">
                        <thead><tr>
                            <th>Action</th><th>Status</th><th>Severity</th><th>Last Seen</th><th>First Seen</th><th>Settlement No</th><th>Shift ID</th><th>Location</th><th>Pump Operator</th><th>Pump Payment ID</th><th>Issue Type</th><th>Message</th><th>Occurrences</th><th>Last Checked</th><th>Resolved By</th><th>Resolved At</th>
                        </tr></thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="ppr_event_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-shield"></i> Payment Reconciliation Event</h4>
            </div>
            <div class="modal-body" id="ppr_event_body"><div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div>
            <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
window.PetroPdPaymentReconciliationReport = {
    url: @json(route('petropd.payment-reconciliation-report')),
    showUrl: @json(url('/petropd/payment-reconciliation-report/__ID__')),
    recheckUrl: @json(url('/petropd/payment-reconciliation-report/__ID__/recheck')),
    title: @json(__('petropd::lang.payment_reconciliation_report')),
    financialYearStartMonth: {{ (int) $financialYearStartMonth }},
    csrfToken: @json(csrf_token())
};
</script>
@php($reportJsPath = module_path('PetroPD', 'Resources/js/payment-reconciliation-report.js'))
@if (is_file($reportJsPath))<script>{!! file_get_contents($reportJsPath) !!}</script>@endif
@endsection
