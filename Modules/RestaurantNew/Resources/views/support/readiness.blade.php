@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::lang.readiness_check'))

@section('content')
<div class="restaurantnew-page">
    <div class="restaurantnew-card">
        <h3>{{ __('restaurantnew::lang.readiness_check') }}</h3>
        <table class="table table-bordered">
            <tbody>
                <tr><th>Module Loaded</th><td>{{ !empty($checks['module_loaded']) ? 'OK' : 'FAIL' }}</td></tr>
                <tr><th>Config Loaded</th><td>{{ !empty($checks['config_loaded']) ? 'OK' : 'FAIL' }}</td></tr>
                <tr><th>Database Connection</th><td>{{ !empty($checks['database_connection']) ? 'OK' : 'FAIL' }}</td></tr>
            </tbody>
        </table>

        <h4>Required Tables</h4>
        <table class="table table-bordered">
            <thead><tr><th>Table</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($checks['required_tables'] as $table => $status)
                <tr><td>{{ $table }}</td><td>{{ $status ? 'OK' : 'MISSING' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
