@extends('layouts.app')
@section('title', 'My Health End-to-End Workflow')
@section('content')
<section class="content-header"><h1>My Health <small>End-to-End Workflow Test</small></h1></section>
<section class="content">
@include('myhealthmembers::certification._nav')
<div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Recommended Test Steps</h3></div><div class="box-body"><ol>@foreach($checklist as $step)<li>{{ $step }}</li>@endforeach</ol></div></div>
</section>
@endsection
