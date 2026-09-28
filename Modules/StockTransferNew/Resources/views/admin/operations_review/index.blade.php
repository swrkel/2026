@extends('layouts.app')
@section('title', __('stocktransfernew::operations_review.title'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-operations-review.css') }}">
<section class="content-header stn-or-header">
    <h1>{{ __('stocktransfernew::operations_review.title') }}</h1>
    <p>{{ __('stocktransfernew::operations_review.subtitle') }}</p>
</section>

<section class="content stn-or-page">
    <div class="row stn-or-cards">
        @foreach(['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low', 'total' => 'Total'] as $key => $label)
            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="stn-or-card stn-or-{{ $key }}">
                    <span>{{ $label }}</span>
                    <strong>{{ $summary[$key] ?? 0 }}</strong>
                </div>
            </div>
        @endforeach
    </div>

    <div class="box stn-or-filter-box">
        <div class="box-body">
            {!! Form::open(['method' => 'get', 'url' => route('stocktransfernew.admin.operations-review.index'), 'class' => 'row']) !!}
                <div class="col-md-2">{!! Form::label('from_date', 'From Date') !!}{!! Form::date('from_date', $filters['from_date'] ?? null, ['class' => 'form-control']) !!}</div>
                <div class="col-md-2">{!! Form::label('to_date', 'To Date') !!}{!! Form::date('to_date', $filters['to_date'] ?? null, ['class' => 'form-control']) !!}</div>
                <div class="col-md-2">{!! Form::label('severity', 'Severity') !!}{!! Form::select('severity', ['' => 'All', 'critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'], $filters['severity'] ?? null, ['class' => 'form-control']) !!}</div>
                <div class="col-md-3 stn-or-actions">
                    <button class="btn btn-primary">{{ __('stocktransfernew::operations_review.filter') }}</button>
                    <a class="btn btn-success" href="{{ route('stocktransfernew.admin.operations-review.export-csv', request()->query()) }}">CSV</a>
                </div>
            {!! Form::close() !!}
        </div>
    </div>

    <div class="box">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-or-table">
                <thead>
                    <tr>
                        <th>Severity</th>
                        <th>Area</th>
                        <th>Reference</th>
                        <th>Issue</th>
                        <th>Recommendation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($findings as $finding)
                        <tr>
                            <td><span class="stn-badge stn-badge-{{ $finding['severity'] ?? 'low' }}">{{ strtoupper($finding['severity'] ?? '') }}</span></td>
                            <td>{{ $finding['area'] ?? '' }}</td>
                            <td>{{ $finding['reference'] ?? '' }}</td>
                            <td>{{ $finding['issue'] ?? '' }}</td>
                            <td>{{ $finding['recommendation'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No findings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if(method_exists($findings, 'links'))
                {{ $findings->links() }}
            @endif
        </div>
    </div>
</section>
@endsection
