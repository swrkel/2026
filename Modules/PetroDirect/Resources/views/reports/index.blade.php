@extends('layouts.app')
@section('title', __('petrodirect::lang.reports'))
@section('content')
<section class="content-header">
    <h1>{{ __('petrodirect::lang.petro_direct') }} <small>{{ __('petrodirect::lang.reports') }}</small></h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('petrodirect::lang.reports') }}</h3>
        </div>
        <div class="box-body">
            <div class="row">
                @foreach($reports as $key => $details)
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <a href="{{ route('petrodirect.reports.show', $key) }}" class="btn btn-default btn-block" style="margin-bottom:10px;text-align:left;">
                            <i class="fa fa-file-text-o"></i> {{ $details[2] }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection
