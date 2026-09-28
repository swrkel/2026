<section class="content-header rn-page-header">
    <h1>{{ $title ?? __('restaurantnew::lang.restaurant_new') }}</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('restaurant-new.dashboard') }}"><i class="fa fa-cutlery"></i> @lang('restaurantnew::lang.restaurant_new')</a></li>
        <li class="active">{{ $title ?? '' }}</li>
    </ol>
</section>
