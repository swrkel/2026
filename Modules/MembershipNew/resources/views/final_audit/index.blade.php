@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Final Standalone Audit';
@endphp
@section('membership-content')
<div class="mn-card">
    <span>Final Status</span>
    <strong>{{ $status }}</strong>
</div>

<div class="mn-panel" style="margin-top:14px">
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-final-audit-report', 'reportTitle' => 'Membership New Final Standalone Audit'])
    <table class="mn-table" id="mn-final-audit-report">
        <thead><tr><th>Item</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($checklist as $item => $ok)
                <tr>
                    <td>{{ $item }}</td>
                    <td>{{ $ok ? 'OK' : 'Needs Review' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
