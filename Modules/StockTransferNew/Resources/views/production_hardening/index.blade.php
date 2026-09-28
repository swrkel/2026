@extends('layouts.app')

@section('title', __('stocktransfernew::lang.production_hardening'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stn_046.css') }}">

<section class="content-header stn046-header">
    <h1>{{ __('stocktransfernew::lang.production_hardening') }}</h1>
    <p>{{ __('stocktransfernew::lang.production_hardening_subtitle') }}</p>
</section>

<section class="content stn046-wrap">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="stn046-kpi-grid">
        <div class="stn046-kpi"><span>Total Checks</span><strong>{{ $summary['total'] ?? 0 }}</strong></div>
        <div class="stn046-kpi success"><span>Passed</span><strong>{{ $summary['passed'] ?? 0 }}</strong></div>
        <div class="stn046-kpi warning"><span>Warnings</span><strong>{{ $summary['warnings'] ?? 0 }}</strong></div>
        <div class="stn046-kpi danger"><span>Failed</span><strong>{{ $summary['failed'] ?? 0 }}</strong></div>
        <div class="stn046-kpi critical"><span>Critical Open</span><strong>{{ $summary['critical_open'] ?? 0 }}</strong></div>
    </div>

    <div class="box stn046-box">
        <div class="box-header with-border stn046-toolbar">
            <h3 class="box-title">Production Hardening Controls</h3>
            <div class="box-tools pull-right">
                <form action="{{ route('stock-transfer-new.production-hardening.run') }}" method="POST" class="stn046-inline-form">
                    @csrf
                    <input type="hidden" name="location_id" value="{{ $locationId }}">
                    <input type="hidden" name="store_id" value="{{ $storeId }}">
                    <button type="submit" class="btn btn-primary btn-sm">Run Checks</button>
                </form>
                <a href="{{ route('stock-transfer-new.production-hardening.export') }}" class="btn btn-success btn-sm">CSV</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn046-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Area</th>
                        <th>Check</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Actual Result</th>
                        <th>Recommendation</th>
                        <th>Checked At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checks as $check)
                        <tr>
                            <td>{{ $check->check_code }}</td>
                            <td>{{ $check->check_area }}</td>
                            <td>{{ $check->check_title }}</td>
                            <td><span class="stn046-pill severity-{{ $check->severity }}">{{ ucfirst($check->severity) }}</span></td>
                            <td><span class="stn046-pill status-{{ $check->status }}">{{ ucfirst(str_replace('_', ' ', $check->status)) }}</span></td>
                            <td>{{ $check->actual_result }}</td>
                            <td>{{ $check->recommendation }}</td>
                            <td>{{ $check->checked_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No production hardening checks yet. Click Run Checks.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if(method_exists($checks, 'links'))
                {{ $checks->links() }}
            @endif
        </div>
    </div>
</section>

<script src="{{ asset('modules/stocktransfernew/js/stn_046.js') }}"></script>
@endsection
