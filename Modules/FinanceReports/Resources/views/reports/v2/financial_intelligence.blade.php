@extends('layouts.app')
@section('title', 'Financial Intelligence - New')
@section('content')
<section class="content-header"><h1>Financial Intelligence - New</h1></section>
<section class="content">
@include('financereports::layouts.toolbar', ['title' => 'Financial Intelligence - New'])
<div class="row">
@foreach($intelligence['cards'] as $card)
<div class="col-md-3"><div class="box box-solid"><div class="box-body"><small>{{ $card['label'] }}</small><h3>{{ is_numeric($card['value']) ? number_format($card['value'], 2) : $card['value'] }}</h3></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header"><h3 class="box-title">Automatic Financial Insights</h3></div><div class="box-body"><ul>@foreach($intelligence['insights'] as $insight)<li>{{ $insight }}</li>@endforeach</ul></div></div>
</section>
@endsection
