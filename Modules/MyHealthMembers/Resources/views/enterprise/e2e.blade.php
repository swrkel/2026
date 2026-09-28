@extends('layouts.app')
@section('title', 'My Health E2E Checklist')
@section('content')
<section class="content-header"><h1>My Health <small>End-to-End Test Checklist</small></h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
    <ol class="list-group">
        @foreach($steps as $step)
            <li class="list-group-item"><i class="fa fa-check-square-o text-green"></i> {{ $step }}</li>
        @endforeach
    </ol>
</div></div></section>
@endsection
