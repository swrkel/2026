@extends('layouts.app')
@section('title', 'My Health Security Review')
@section('content')
<section class="content-header"><h1>My Health <small>Security Review</small></h1></section>
<section class="content"><div class="box box-danger"><div class="box-body">
    <ul class="list-group">
        @foreach($checks as $check)
            <li class="list-group-item"><i class="fa fa-shield text-red"></i> {{ $check }}</li>
        @endforeach
    </ul>
</div></div></section>
@endsection
