@extends('restaurantnew::layouts.app')

@section('restaurantnew_content')
<section class="content-header">
    <h1>@lang('restaurantnew::lang.dashboard')</h1>
</section>
<section class="content restaurant-new-dashboard">
    @include('restaurantnew::partials.toolbar')
    <div class="row rn-kpi-row">
        @foreach($summary as $label => $value)
            <div class="col-md-3 col-sm-6">
                <div class="info-box rn-info-box">
                    <span class="info-box-icon"><i class="fa fa-cutlery"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                        <span class="info-box-number">{{ $value }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
