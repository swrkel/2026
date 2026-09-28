@extends('restaurantnew::layouts.app')

@section('restaurantnew_content')
<section class="content-header">
    <h1>{{ $title ?? __('restaurantnew::lang.restaurant_new') }}</h1>
</section>
<section class="content">
    @include('restaurantnew::partials.toolbar')
    <div class="box box-primary">
        <div class="box-body">
            <p>This standalone Restaurant-New screen is reserved for the next implementation stage.</p>
        </div>
    </div>
</section>
@endsection
