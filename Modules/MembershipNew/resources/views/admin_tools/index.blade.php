@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Admin Tools';
@endphp
@section('membership-content')
<div class="mn-panel">
    <h3>Table Counts</h3>
    <table class="mn-table">
        <thead><tr><th>Table</th><th>Count</th></tr></thead>
        <tbody>
            @foreach($counts as $table => $count)
                <tr><td>{{ $table }}</td><td>{{ $count }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mn-panel" style="margin-top:14px">
    <h3>Demo Data Reset</h3>
    <p>This deletes only obvious sample rows from the current business/testing data.</p>
    <form method="POST" action="{{ route('membership-new.admin-tools.reset-demo-data') }}">
        @csrf
        <button class="mn-btn mn-btn-danger">Reset Demo Data</button>
    </form>
</div>
@endsection
