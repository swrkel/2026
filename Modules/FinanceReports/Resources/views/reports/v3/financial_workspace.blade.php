@extends('layouts.app')
@section('title', 'Financial Workspace - New')
@section('content')
<section class="content-header"><h1>Financial Workspace - New <small>Finance Reports Enterprise v3.0</small></h1></section>
<section class="content">
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Personal reporting workspace</h3></div><div class="box-body">
<div class="row">@foreach($workspace as $section => $items)<div class="col-md-3"><div class="box box-solid"><div class="box-header"><h3 class="box-title">{{ ucwords(str_replace('_', ' ', $section)) }}</h3></div><div class="box-body"><ul>@foreach($items as $item)<li>{{ $item }}</li>@endforeach</ul></div></div></div>@endforeach</div>
</div></div>
</section>
@endsection
