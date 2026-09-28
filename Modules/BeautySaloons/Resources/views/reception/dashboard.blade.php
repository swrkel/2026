@extends('layouts.app')
@section('title', __('beautysaloons::reception.reception_dashboard'))

@section('content')
<section class="content-header"><h1>@lang('beautysaloons::reception.reception_dashboard')</h1></section>
<section class="content bs013-reception-dashboard">
    <div class="row">
        @foreach($summary as $key => $value)
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="fa fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                        <span class="info-box-number">{{ $value }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
