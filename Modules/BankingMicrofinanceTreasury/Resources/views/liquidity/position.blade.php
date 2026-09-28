@extends('bankingmicrofinancetreasury::layout')
@section('treasury_content')<pre>{{ json_encode($position, JSON_PRETTY_PRINT) }}</pre>@endsection
