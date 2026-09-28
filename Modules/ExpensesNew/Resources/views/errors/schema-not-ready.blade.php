@extends('layouts.app')

@section('title', 'Expenses New Setup')

@section('content')
<section class="content-header">
    <h1>Expenses New Setup</h1>
</section>
<section class="content">
    <div class="alert alert-danger">
        <h4><i class="fa fa-database"></i> {{ $message }}</h4>
        @if($database)
            <p>Tenant database: <strong>{{ $database }}</strong></p>
        @endif
        <p>
            Run these idempotent SQL files on this tenant database, then reload the page:<br>
            <code>Modules/ExpensesNew/Database/sql/00_EXPENSES_NEW_CORE_SCHEMA_IDEMPOTENT.sql</code><br>
            <code>Modules/ExpensesNew/Database/sql/01_EXPENSES_NEW_CORE_COLUMN_REPAIR_IDEMPOTENT.sql</code>
        </p>
        @if($detail)
            <pre style="white-space: pre-wrap; margin-top: 15px;">{{ $detail }}</pre>
        @endif
    </div>
</section>
@endsection
