@extends('layouts.app')
@section('title', 'Price Change New Setup Required')
@section('content')
<section class="content-header"><h1>Price Change New <small>Database setup required</small></h1></section>
<section class="content">
    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-database"></i> Active tenant database is not ready</h3></div>
        <div class="box-body">
            <p>The Price Change New Sequence 02 tables or workflow columns are not installed in:</p>
            <p><code>{{ $databaseName }}</code></p>
            <p>Import the following SQL file into this tenant database:</p>
            <p><strong>{{ $sqlFile }}</strong></p>
            <div class="alert alert-danger"><i class="fa fa-warning"></i> Do not import the tenant SQL into <strong>nivasa_base</strong>.</div>
        </div>
    </div>
</section>
@endsection
