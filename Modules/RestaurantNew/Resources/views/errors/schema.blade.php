@extends('layouts.app')
@section('title','Restaurant-New Setup Required')
@section('content')<div class="container"><div class="alert alert-danger"><h3>Restaurant-New database setup is incomplete.</h3><p>Run the module migrations or the supplied master SQL in this tenant database, then clear caches.</p>@isset($schemaError)<pre>{{ $schemaError }}</pre>@endisset</div></div>@endsection
