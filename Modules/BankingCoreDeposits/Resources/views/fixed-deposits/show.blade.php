@extends("bankingcoredeposits::layouts.app")
@section("module-content")<pre>{{ json_encode($fixedDeposit, JSON_PRETTY_PRINT) }}</pre>@endsection