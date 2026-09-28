@extends('layouts.app')

@section('title', __('customers::lang.standalone_audit'))

@section('content')
<section class="content-header">
    <h1>{{ __('customers::lang.standalone_audit') }}</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('customers::lang.audit_summary') }}</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-aqua"><i class="fa fa-files-o"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Files Scanned</span>
                            <span class="info-box-number">{{ number_format($audit['total_files_scanned'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon {{ ($audit['total_matches'] ?? 0) > 0 ? 'bg-yellow' : 'bg-green' }}"><i class="fa fa-search"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Legacy References</span>
                            <span class="info-box-number">{{ number_format($audit['total_matches'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="well well-sm">
                        <strong>Status:</strong>
                        {{ strtoupper($audit['summary']['status'] ?? 'unknown') }}<br>
                        <small>Note: database column names such as contact_id are intentionally not counted in RC12 because existing ERP tenant transaction tables still use them.</small>
                    </div>
                </div>
            </div>

            @if(!empty($audit['matches']))
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Line</th>
                                <th>Pattern</th>
                                <th>Snippet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($audit['matches'] as $match)
                                <tr>
                                    <td>{{ $match['file'] }}</td>
                                    <td>{{ $match['line'] }}</td>
                                    <td><code>{{ $match['pattern'] }}</code></td>
                                    <td><code>{{ Str::limit($match['snippet'], 180) }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-success">
                    No legacy Contacts customer route/view/controller references were found inside Modules/Customers.
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
