@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::lang.executive_dashboard'))
@section('leadsnew_subtitle', 'A management-level view of lead volume, conversion performance and the active sales funnel.')

@section('leadsnew_content')
    <div class="ch-kpi-grid">
        @foreach($summary as $key => $value)
            @php
                $tone = $loop->iteration === 2 ? 'success' : ($loop->iteration === 3 ? 'warning' : ($loop->iteration === 4 ? 'purple' : ''));
            @endphp
            <div class="ch-kpi {{ $tone }}">
                <div class="ch-kpi-top">
                    <div class="ch-icon"><i class="fa fa-line-chart"></i></div>
                    <div class="label-text">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                </div>
                <div class="value">{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</div>
                <div class="hint">Executive performance measure</div>
                <div class="spark"></div>
            </div>
        @endforeach
    </div>

    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-filter text-primary"></i> Dashboard Filters</h3>
                <div class="ch-card-subtitle">Refine the executive dashboard by date, business, location or assigned user.</div>
            </div>
        </div>
        <div class="ch-card-body">
            <form method="get" class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>Location ID</label>
                        <input type="number" name="location_id" class="form-control" value="{{ $filters['location_id'] ?? '' }}" placeholder="All locations">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>Assigned User ID</label>
                        <input type="number" name="assigned_to" class="form-control" value="{{ $filters['assigned_to'] ?? '' }}" placeholder="All users">
                    </div>
                </div>
                <div class="col-sm-12 text-right">
                    <a href="{{ url('/leads-new/executive-dashboard') }}" class="btn btn-default"><i class="fa fa-refresh"></i> Clear</a>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-sitemap text-primary"></i> @lang('leadsnew::lang.sales_funnel')</h3>
                <div class="ch-card-subtitle">Lead and opportunity distribution by funnel stage.</div>
            </div>
        </div>
        <div class="ch-card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>@lang('leadsnew::lang.stage')</th>
                        <th class="text-right">@lang('leadsnew::lang.total')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($funnel as $row)
                        <tr>
                            <td><span class="ln-badge">{{ ucwords(str_replace('_', ' ', $row['stage'])) }}</span></td>
                            <td class="text-right"><strong>{{ number_format((float) $row['total'], 0) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="2"><div class="ln-empty"><i class="fa fa-bar-chart"></i>No funnel data found.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
