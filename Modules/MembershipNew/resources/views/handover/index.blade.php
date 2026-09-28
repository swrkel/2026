@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Handover / Server Ready';
@endphp
@section('membership-content')
<div class="mn-grid">
    <div class="mn-card"><span>Version</span><strong>{{ $version }}</strong></div>
    <div class="mn-card"><span>Functional Areas</span><strong>{{ count($modules) }}</strong></div>
    <div class="mn-card"><span>Route Files</span><strong>{{ count($routeFiles) }}</strong></div>
    <div class="mn-card"><span>SQL Files</span><strong>{{ count($sqlFiles) }}</strong></div>
</div>

<div class="mn-panel" style="margin-top:14px">
    <h3>Server Readiness Checklist</h3>
    <table class="mn-table">
        <thead><tr><th>Item</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($checklist as $row)
                <tr>
                    <td>{{ $row['item'] }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mn-panel" style="margin-top:14px">
    <h3>Functional Areas</h3>
    <ul>
        @foreach($modules as $module)
            <li>{{ $module }}</li>
        @endforeach
    </ul>
</div>

<div class="mn-panel" style="margin-top:14px">
    <h3>Route Files</h3>
    <pre>{{ implode("\n", $routeFiles) }}</pre>
</div>

<div class="mn-panel" style="margin-top:14px">
    <h3>SQL Files</h3>
    <pre>{{ implode("\n", $sqlFiles) }}</pre>
</div>
@endsection
