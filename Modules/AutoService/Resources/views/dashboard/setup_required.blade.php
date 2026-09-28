@extends('autoservice::layouts.master')
@section('title','Auto Service Setup Required')
@section('autoservice_content')
<div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">Auto Service database setup is incomplete</h3>
    </div>
    <div class="box-body">
        <p>The Auto Service module routes are loading correctly, but the tenant database tables have not been created yet.</p>
        <p>Please run the Auto Service tenant migrations for this business database before UI testing.</p>
        <h4>Missing tenant tables</h4>
        <ul>
            @foreach($missing_tables as $table)
                <li><code>{{ $table }}</code></li>
            @endforeach
        </ul>
        <hr>
        <p><strong>Important:</strong> Central Vehicle Registry migrations must be run only on the central database, not on tenant databases.</p>
        <p>Until the tenant migrations are completed, operational pages such as Vehicles, Jobs, Workshop, Invoices and Reports will show this setup notice instead of a SQL error.</p>
    </div>
</div>
@endsection
