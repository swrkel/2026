@extends('layouts.app')
@section('title', 'My Health Release Notes')
@section('content')
<section class="content-header"><h1>My Health <small>Release Notes</small></h1></section>
<section class="content"><div class="box box-info"><div class="box-body">
    <ul class="list-group">
        @foreach($notes as $note)
            <li class="list-group-item"><i class="fa fa-info-circle text-blue"></i> {{ $note }}</li>
        @endforeach
    </ul>
</div></div></section>
@endsection
