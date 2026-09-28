@extends('layouts.app')

@section('title', __('petropd::lang.list_assigned_operators'))

@section('content')
@php
    $defaultStartDate = now()->startOfYear()->format('Y-m-d');
    $defaultEndDate = now()->endOfYear()->format('Y-m-d');
    $reportCssPath = module_path('PetroPD', 'Resources/css/list-assigned-operators.css');
@endphp

@if (is_file($reportCssPath))
<style>{!! file_get_contents($reportCssPath) !!}</style>
@endif

<section class="content-header lao-content-header">
    <h1>@lang('petropd::lang.list_assigned_operators')</h1>
</section>

<section class="content petropd-list-assigned-operators">
    <div class="lao-card lao-filter-card">
        <div class="lao-card-header">
            <div>
                <h3><i class="fa fa-filter"></i> Filters</h3>
                <p>Filter pump assignments by period, operator, pump, pump status, shift status and settlement number.</p>
            </div>
            <button type="button" class="btn btn-default" id="lao_reset_filters"><i class="fa fa-refresh"></i> Reset</button>
        </div>
        <div class="lao-card-body">
            <div class="lao-filter-grid">
                <div class="lao-field">
                    <label>@lang('petropd::lang.date_range')</label>
                    <select id="lao_date_preset" class="form-control lao-select2">
                        <option value="this_year" selected>This Year</option>
                        <option value="last_year">Last Year</option>
                        <option value="this_fy">This FY</option>
                        <option value="last_fy">Last FY</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div class="lao-field">
                    <label>From Date</label>
                    <input type="date" id="lao_start_date" class="form-control" value="{{ $defaultStartDate }}">
                </div>
                <div class="lao-field">
                    <label>To Date</label>
                    <input type="date" id="lao_end_date" class="form-control" value="{{ $defaultEndDate }}">
                </div>
                <div class="lao-field">
                    <label>@lang('petropd::lang.operator')</label>
                    <select id="lao_operator_id" class="form-control lao-select2 lao-filter">
                        <option value="">All Operators</option>
                        @foreach($filters['operators'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lao-field">
                    <label>@lang('petropd::lang.assign_pump')</label>
                    <select id="lao_pump_id" class="form-control lao-select2 lao-filter">
                        <option value="">All Pumps</option>
                        @foreach($filters['pumps'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lao-field">
                    <label>@lang('petropd::lang.pump_status')</label>
                    <select id="lao_pump_status" class="form-control lao-select2 lao-filter">
                        <option value="">All</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="lao-field">
                    <label>@lang('petropd::lang.shift_status')</label>
                    <select id="lao_shift_status" class="form-control lao-select2 lao-filter">
                        <option value="">All</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="lao-field">
                    <label>@lang('petropd::lang.settlement_no')</label>
                    <select id="lao_settlement_no" class="form-control lao-select2 lao-filter">
                        <option value="">All Settlement Nos</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="lao-card">
        <div class="lao-card-header lao-table-header">
            <div>
                <h3><i class="fa fa-users"></i> @lang('petropd::lang.list_assigned_operators')</h3>
                <p>One line per operator shift assignment. Multiple pumps assigned to the same operator/shift are grouped together.</p>
            </div>
            <div class="lao-search-wrap">
                <input type="text" id="lao_search" class="form-control" placeholder="Search operator, pump, status, settlement...">
                <i class="fa fa-search"></i>
            </div>
        </div>
        <div class="lao-card-body">
            <div class="lao-toolbar"><div id="lao_buttons"></div></div>
            <div class="lao-table-wrap">
                <table id="list_assigned_operators_table" class="table table-bordered table-striped nowrap" width="100%">
                    <thead>
                    <tr>
                        <th>@lang('petropd::lang.assigned_date')</th>
                        <th>@lang('petropd::lang.operator')</th>
                        <th>@lang('petropd::lang.assigned_pumps')</th>
                        <th>@lang('petropd::lang.pumps_status')</th>
                        <th>@lang('petropd::lang.shift_status')</th>
                        <th>@lang('petropd::lang.settlement_no')</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
window.PetroPdListAssignedOperators = {
    url: @json(route('petropd.list-assigned-operators')),
    settlementOptionsUrl: @json(route('petropd.list-assigned-operators.settlement-options')),
    title: @json(__('petropd::lang.list_assigned_operators')),
    financialYearStartMonth: {{ (int) $financialYearStartMonth }}
};
</script>
@php($reportJsPath = module_path('PetroPD', 'Resources/js/list-assigned-operators.js'))
@if (is_file($reportJsPath))<script>{!! file_get_contents($reportJsPath) !!}</script>@endif
@endsection
