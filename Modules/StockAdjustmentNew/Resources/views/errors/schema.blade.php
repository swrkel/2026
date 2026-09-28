@extends('layouts.app')
@section('title', 'Stock Adjustment New Setup')
@section('content')
<div class="container-fluid">
    <div class="alert alert-danger">
        <h4>Stock Adjustment New database setup is incomplete</h4>
        <p>{{ $message }}</p>
        <p><strong>Database:</strong> {{ $database ?: 'Unknown' }}</p>
        <p><strong>Missing tables:</strong> {{ empty($missing_tables) ? 'Unable to determine' : implode(', ', $missing_tables) }}</p>
        <p>Run the idempotent SQL file below on this tenant database, then reload the page:</p>
        <pre>{{ $sql_file }}</pre>
    </div>
</div>
@endsection
