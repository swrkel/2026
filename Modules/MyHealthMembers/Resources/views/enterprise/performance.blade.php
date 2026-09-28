@extends('layouts.app')
@section('title', 'My Health Performance Review')
@section('content')
<section class="content-header"><h1>My Health <small>Performance Review</small></h1></section>
<section class="content"><div class="box box-warning"><div class="box-body">
    <ul class="list-group">
        @foreach($checks as $check)
            <li class="list-group-item"><i class="fa fa-tachometer text-yellow"></i> {{ $check }}</li>
        @endforeach
    </ul>
</div></div></section>
@endsection
