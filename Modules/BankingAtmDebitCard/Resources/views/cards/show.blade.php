@extends('banking-cards::layout')
@section('banking_card_content')
<h3>Debit Card Details</h3>
<p>Record ID: {{ $id ?? '' }}</p>
@endsection
