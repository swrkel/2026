@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Health Check';
@endphp
@section('membership-content')
<div class="mn-panel">
    <h3>Tables</h3>
    <table class="mn-table">
        <thead><tr><th>Table</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($results['tables'] as $table => $status)
                <tr>
                    <td>{{ $table }}</td>
                    <td><span class="{{ $status === 'OK' ? 'mn-ok' : 'mn-missing' }}">{{ $status }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mn-panel" style="margin-top:14px">
    <h3>Permissions</h3>
    <table class="mn-table">
        <thead><tr><th>Permission</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($results['permissions'] as $permission => $status)
                <tr>
                    <td>{{ $permission }}</td>
                    <td><span class="{{ $status === 'OK' ? 'mn-ok' : 'mn-missing' }}">{{ $status }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mn-panel" style="margin-top:14px">
    <h3>Route Files</h3>
    <pre>{{ json_encode($results['route_files'], JSON_PRETTY_PRINT) }}</pre>
</div>
@endsection
