@extends('layouts.app')

@section('title', 'Banking Smoke sidebar')

@section('content')
<section class="content-header"><h1>Banking Smoke Check: Sidebar</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            @include('bankingui::partials.check-table', ['checks' => $checks])
        </div>
    </div>
</section>
@endsection
