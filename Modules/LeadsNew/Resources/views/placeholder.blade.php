@extends('leadsnew::layouts.app')
@section('title', $title ?? 'Leads-New')
@section('leadsnew_content')
<section class="content-header">
    <h1>{{ $title ?? 'Leads-New' }}</h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            <p>{{ $message ?? 'This Leads-New page is available.' }}</p>
        </div>
    </div>
</section>
@endsection
