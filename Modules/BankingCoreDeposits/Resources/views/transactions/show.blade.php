@extends("bankingcoredeposits::layouts.app")
@section("module-content")<pre>{{ json_encode($transaction, JSON_PRETTY_PRINT) }}</pre>@endsection