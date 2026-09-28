@extends('layouts.app')
@section('title', 'Cross Module Financial Intelligence - New')
@section('content')
<section class="content-header"><h1>Cross Module Financial Intelligence - New <small>Finance Reports Enterprise v3.0</small></h1></section>
<section class="content">
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Enterprise KPI groups</h3></div><div class="box-body">
<div class="row">@foreach($kpis as $group => $items)<div class="col-md-4"><div class="box box-solid"><div class="box-header"><h3 class="box-title">{{ ucwords(str_replace('_', ' ', $group)) }}</h3></div><div class="box-body"><ul>@foreach($items as $item)<li>{{ $item }}</li>@endforeach</ul></div></div></div>@endforeach</div><div class="box box-warning"><div class="box-header"><h3 class="box-title">Insight Rules</h3></div><div class="box-body"><ul>@foreach($rules as $rule)<li>{{ $rule }}</li>@endforeach</ul></div></div>
</div></div>
</section>
@endsection
