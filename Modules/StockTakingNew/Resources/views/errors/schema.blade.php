@extends('layouts.app')
@section('title','Stock Taking Database Setup Required')
@section('content')<section class="content"><div class="callout callout-danger"><h4><i class="fa fa-database"></i> Stock Taking - New database setup required</h4><p>The module could not find or create its <code>stk_</code> tables in the current tenant database.</p>@if(!empty($schemaError))<pre>{{ $schemaError }}</pre>@endif<p>Run <code>Modules/StockTakingNew/SQL/00_MASTER_INSTALL_STOCK_TAKING_NEW.sql</code> on the tenant database, then clear Laravel caches.</p></div></section>@endsection
