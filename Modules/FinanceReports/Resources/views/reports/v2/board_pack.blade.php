@extends('layouts.app')
@section('title', 'Board Pack - New')
@section('content')
<section class="content-header"><h1>Board Pack - New</h1></section>
<section class="content">
@include('financereports::layouts.toolbar', ['title' => 'Board Pack - New'])
<div class="box box-primary"><div class="box-header"><h3 class="box-title">Executive Summary</h3></div><div class="box-body"><div class="row">@foreach($intelligence['cards'] as $card)<div class="col-md-3"><strong>{{ $card['label'] }}</strong><br>{{ is_numeric($card['value']) ? number_format($card['value'], 2) : $card['value'] }}</div>@endforeach</div></div></div>
<div class="box box-solid"><div class="box-header"><h3 class="box-title">Included Financial Pack Sections</h3></div><div class="box-body"><ul>@foreach(array_keys($pack) as $section)<li>{{ ucwords(str_replace('_',' ', $section)) }}</li>@endforeach</ul></div></div>
</section>
@endsection
