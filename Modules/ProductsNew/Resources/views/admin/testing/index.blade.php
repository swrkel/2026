@extends('productsnew::layouts.app')
@section('title', __('productsnew::lang.testing_checklist'))
@section('productsnew_content')
<section class="productsnew-page productsnew-testing-checklist">
    <div class="productsnew-header-card">
        <div>
            <h1>{{ __('productsnew::lang.testing_checklist') }}</h1>
            <p>{{ __('productsnew::lang.testing_checklist_help') }}</p>
        </div>
    </div>
    <div class="productsnew-card">
        <div class="productsnew-card-title">{{ __('productsnew::lang.user_acceptance_testing') }}</div>
        <ol class="productsnew-checklist">
            @foreach($items as $item)
                <li><span>{{ $item }}</span></li>
            @endforeach
        </ol>
    </div>
</section>
@endsection
