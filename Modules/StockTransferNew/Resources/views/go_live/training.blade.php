@extends('layouts.app')
@section('title', 'Stock Transfer-New Training Guide')
@section('content')
<section class="content-header stn-go-live-header">
    <h1>Stock Transfer-New Training Guide</h1>
    <p>Simple user flow for testers and production users.</p>
</section>
<section class="content stn-training-guide">
    @foreach($sections as $title => $items)
        <div class="box stn-training-card">
            <div class="box-header with-border"><h3 class="box-title">{{ $title }}</h3></div>
            <div class="box-body"><ol>@foreach($items as $item)<li>{{ $item }}</li>@endforeach</ol></div>
        </div>
    @endforeach
</section>
@endsection
