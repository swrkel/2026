@extends('beautysaloons::layouts.app')
@section('content')
<section class="content-header"><h1>Edit Beauty Service</h1></section>
<section class="content">
<form method="POST" action="{{ route('beautysaloons.services.update', $service->id) }}">@csrf @method('PUT')
@include('beautysaloons::services.form')
</form>
</section>
@endsection
