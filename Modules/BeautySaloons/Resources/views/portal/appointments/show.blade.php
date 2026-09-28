@extends('beautysaloons::portal.layout')
@section('portal_title', 'Appointment Details')
@section('portal_content')
<pre>{{ json_encode($appointment, JSON_PRETTY_PRINT) }}</pre>
@endsection
