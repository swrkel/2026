@extends('layouts.app')

@section('title', 'Banking Installation & Smoke Test')

@section('content')
<section class="content-header"><h1>Banking Installation & Smoke Test RC6</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Release Checklist</h3></div>
        <div class="box-body">
            @include('bankingui::partials.check-table', ['checks' => $releaseChecklist])
        </div>
    </div>
</section>
@endsection
