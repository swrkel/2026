@extends('layouts.app')

@section('title', __('petropd::lang.adjusted_amounts_report'))

@section('content')
@php
    $defaultStartDate = now()->startOfYear()->format('Y-m-d');
    $defaultEndDate = now()->endOfYear()->format('Y-m-d');
    $reportCssPath = module_path('PetroPD', 'Resources/css/adjusted-amounts-report.css');
@endphp

@if (is_file($reportCssPath))
<style>{!! file_get_contents($reportCssPath) !!}</style>
@endif

<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">@lang('petropd::lang.adjusted_amounts_report')</h1>
</section>

<section class="content petropd-adjusted-report">
    <div class="ppd-shell">
        <div class="ppd-hero">
            <div>
                <div class="ppd-eyebrow">@lang('petropd::lang.petro_pd') · @lang('report.reports')</div>
                <h1>@lang('petropd::lang.adjusted_amounts_report')</h1>
                <p>Track every requested and applied settlement payment adjustment, including the previous amount, revised amount, reason, requester, authorizer, and the resulting increase or decrease.</p>
            </div>
            <div class="ppd-hero-actions">
                @php
                    $reportUser = auth()->user();
                    $reportBusinessId = (int) request()->session()->get('user.business_id');
                    $reportAdmin = $reportUser && ($reportUser->can('superadmin') || $reportUser->hasRole('Admin#' . $reportBusinessId));
                @endphp
                @if($reportAdmin || ($reportUser && $reportUser->can('petro_pd.create_settlement')))
                    <a href="{{ route('petropd.settlement-pd.create') }}" class="btn btn-default ppd-btn">
                        <i class="fa fa-calculator"></i> @lang('petropd::lang.pd_settlement')
                    </a>
                @endif
                @if($reportAdmin || ($reportUser && $reportUser->can('petro_pd.list_settlement')))
                    <a href="{{ route('petropd.list-pd-settlement') }}" class="btn btn-primary ppd-btn">
                        <i class="fa fa-list"></i> @lang('petropd::lang.list_pd_settlement')
                    </a>
                @endif
            </div>
        </div>

        <div class="ppd-kpi-grid">
            <div class="ppd-kpi">
                <div class="ppd-kpi-top">
                    <div class="ppd-kpi-label">Adjustment Requests</div>
                    <div class="ppd-kpi-icon"><i class="fa fa-file-text-o"></i></div>
                </div>
                <div class="ppd-kpi-value" id="ppd_kpi_requests">0</div>
                <div class="ppd-kpi-hint">Requests matching the selected filters</div>
            </div>
            <div class="ppd-kpi success">
                <div class="ppd-kpi-top">
                    <div class="ppd-kpi-label">Applied Adjustments</div>
                    <div class="ppd-kpi-icon"><i class="fa fa-check-circle"></i></div>
                </div>
                <div class="ppd-kpi-value" id="ppd_kpi_applied_items">0</div>
                <div class="ppd-kpi-hint">Payment method lines successfully adjusted</div>
            </div>
            <div class="ppd-kpi warning">
                <div class="ppd-kpi-top">
                    <div class="ppd-kpi-label">Total Increase</div>
                    <div class="ppd-kpi-icon"><i class="fa fa-arrow-up"></i></div>
                </div>
                <div class="ppd-kpi-value" id="ppd_kpi_increase">0.00</div>
                <div class="ppd-kpi-hint">Value added by applied adjustments</div>
            </div>
            <div class="ppd-kpi danger">
                <div class="ppd-kpi-top">
                    <div class="ppd-kpi-label">Total Decrease</div>
                    <div class="ppd-kpi-icon"><i class="fa fa-arrow-down"></i></div>
                </div>
                <div class="ppd-kpi-value" id="ppd_kpi_decrease">0.00</div>
                <div class="ppd-kpi-hint">Value reduced by applied adjustments</div>
            </div>
        </div>

        <div class="ppd-card">
            <div class="ppd-card-header">
                <div>
                    <h3 class="ppd-card-title"><i class="fa fa-filter text-primary"></i> @lang('report.filters')</h3>
                    <div class="ppd-card-subtitle">Every filter applies instantly. Date inputs can also be typed directly.</div>
                </div>
                <button type="button" id="ppd_reset_filters" class="btn btn-default ppd-btn">
                    <i class="fa fa-refresh"></i> Reset Filters
                </button>
            </div>
            <div class="ppd-card-body">
                <div class="ppd-filter-grid">
                    <div class="ppd-filter-group">
                        <label for="ppd_date_basis">Date Basis</label>
                        <select id="ppd_date_basis" class="form-control ppd-select2 ppd-report-filter">
                            <option value="requested">Request Date</option>
                            <option value="applied">Applied Date</option>
                        </select>
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_date_preset">Date Range</label>
                        <select id="ppd_date_preset" class="form-control ppd-select2">
                            <option value="this_year" selected>This Year</option>
                            <option value="last_year">Last Year</option>
                            <option value="this_fy">This FY</option>
                            <option value="last_fy">Last FY</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_start_date">From Date</label>
                        <input type="date" id="ppd_start_date" class="form-control" value="{{ $defaultStartDate }}">
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_end_date">To Date</label>
                        <input type="date" id="ppd_end_date" class="form-control" value="{{ $defaultEndDate }}">
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_location_id">@lang('purchase.business_location')</label>
                        <select id="ppd_location_id" class="form-control ppd-select2 ppd-report-filter">
                            <option value="">@lang('lang_v1.all')</option>
                            @foreach($filters['locations'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_pump_operator_id">Pump Operator</label>
                        <select id="ppd_pump_operator_id" class="form-control ppd-select2 ppd-report-filter">
                            <option value="">@lang('lang_v1.all')</option>
                            @foreach($filters['pumpOperators'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_settlement_no">Settlement No</label>
                        <select id="ppd_settlement_no" class="form-control ppd-select2 ppd-report-filter">
                            <option value="">@lang('lang_v1.all')</option>
                            @foreach($filters['settlementNumbers'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_payment_method">Payment Method</label>
                        <select id="ppd_payment_method" class="form-control ppd-select2 ppd-report-filter">
                            <option value="">@lang('lang_v1.all')</option>
                            @foreach($filters['methods'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_status">Status</label>
                        <select id="ppd_status" class="form-control ppd-select2 ppd-report-filter">
                            <option value="">@lang('lang_v1.all')</option>
                            <option value="pending">Pending</option>
                            <option value="applied">Applied</option>
                            <option value="rejected">Rejected</option>
                            <option value="superseded">Superseded</option>
                        </select>
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_requested_by">Requested By</label>
                        <select id="ppd_requested_by" class="form-control ppd-select2 ppd-report-filter">
                            <option value="">@lang('lang_v1.all')</option>
                            @foreach($filters['users'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_processed_by">Processed By</label>
                        <select id="ppd_processed_by" class="form-control ppd-select2 ppd-report-filter">
                            <option value="">@lang('lang_v1.all')</option>
                            @foreach($filters['users'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ppd-filter-group">
                        <label for="ppd_amount_direction">Adjustment Direction</label>
                        <select id="ppd_amount_direction" class="form-control ppd-select2 ppd-report-filter">
                            <option value="">@lang('lang_v1.all')</option>
                            <option value="increase">Increase</option>
                            <option value="decrease">Decrease</option>
                            <option value="no_change">No Change</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="ppd-card">
            <div class="ppd-card-header">
                <div>
                    <h3 class="ppd-card-title"><i class="fa fa-list-alt text-primary"></i> Adjustment Details</h3>
                    <div class="ppd-card-subtitle">One row is shown for each adjusted payment method.</div>
                </div>
                <div class="ppd-summary-strip">
                    <span class="ppd-summary-chip">Pending <strong id="ppd_pending_count">0</strong></span>
                    <span class="ppd-summary-chip">Rejected <strong id="ppd_rejected_count">0</strong></span>
                    <span class="ppd-summary-chip">Net Change <strong id="ppd_net_change">0.00</strong></span>
                </div>
            </div>
            <div class="ppd-card-body">
                <div class="ppd-table-tools">
                    <div class="ppd-search-wrap">
                        <input type="text" id="ppd_report_search" class="form-control" placeholder="Search settlement, operator, payment method, reason, or user...">
                        <i class="fa fa-search"></i>
                    </div>
                    <div id="ppd_adjusted_amounts_buttons" class="ppd-table-buttons"></div>
                </div>

                <div class="table-responsive ppd-table-wrap">
                    <table class="table table-bordered nowrap" id="petropd_adjusted_amounts_table" width="100%">
                        <thead>
                            <tr>
                                <th>Request Ref</th>
                                <th>Request Date</th>
                                <th>Settlement Date</th>
                                <th>Settlement No</th>
                                <th>Shift No</th>
                                <th>Location</th>
                                <th>Pump Operator</th>
                                <th>Payment Method</th>
                                <th>Current Amount</th>
                                <th>Requested Amount</th>
                                <th>Applied Amount</th>
                                <th>Difference</th>
                                <th>Reason to Edit</th>
                                <th>Status</th>
                                <th>Requested By</th>
                                <th>Processed By</th>
                                <th>Processed Date</th>
                                <th>Rejection Reason</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="8" class="text-right">Filtered Totals:</th>
                                <th class="text-right" id="ppd_footer_current">0.00</th>
                                <th class="text-right" id="ppd_footer_requested">0.00</th>
                                <th class="text-right" id="ppd_footer_applied">0.00</th>
                                <th class="text-right" id="ppd_footer_difference">0.00</th>
                                <th colspan="6"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
window.PetroPdAdjustedAmountsReport = {
    url: @json(route('petropd.adjusted-amounts-report')),
    title: @json(__('petropd::lang.adjusted_amounts_report')),
    precision: {{ (int) $currencyPrecision }},
    financialYearStartMonth: {{ (int) $financialYearStartMonth }}
};
</script>
@php($reportJsPath = module_path('PetroPD', 'Resources/js/adjusted-amounts-report.js'))
@if (is_file($reportJsPath))
<script>{!! file_get_contents($reportJsPath) !!}</script>
@endif
@endsection
