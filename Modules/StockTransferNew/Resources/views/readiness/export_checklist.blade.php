@extends('layouts.app')

@section('title', __('stocktransfernew::lang.export_checklist'))

@section('content')
<section class="content-header stn-final-header"><h1>{{ __('stocktransfernew::lang.export_checklist') }}</h1></section>
<section class="content stn-final-page">
    <div class="row">
        @foreach($items as $item)
            <div class="col-md-4 col-sm-6">
                <div class="stn-final-card stn-check-card">
                    <span>{{ $item }}</span>
                    <strong>{{ __('stocktransfernew::lang.verify') }}</strong>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
