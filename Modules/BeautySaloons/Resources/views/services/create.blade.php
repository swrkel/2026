@extends('beautysaloons::layouts.app')
@section('content')
<section class="content-header"><h1>Add Beauty Service</h1></section>
<section class="content">
<form method="POST" action="{{ route('beautysaloons.services.store') }}">@csrf
@include('beautysaloons::services.form')
</form>
</section>
@endsection
