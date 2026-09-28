@extends('layouts.app')
@section('title', 'Financial Health Score - New')
@section('content')
<section class="content-header"><h1>Financial Health Score - New</h1></section>
<section class="content">
@include('financereports::layouts.toolbar', ['title' => 'Financial Health Score - New'])
@php $score = collect($intelligence['cards'])->firstWhere('label', 'Financial Health Score')['value'] ?? 0; @endphp
<div class="box box-primary"><div class="box-body text-center"><h1>{{ number_format($score, 0) }}/100</h1><p>{{ $score >= 75 ? 'Green' : ($score >= 50 ? 'Amber' : 'Red') }}</p></div></div>
<div class="box box-solid"><div class="box-body"><ul>@foreach($intelligence['insights'] as $insight)<li>{{ $insight }}</li>@endforeach</ul></div></div>
</section>
@endsection
