@extends('layouts.app')
@section('title', __('distributionnew::lang.production_stabilization'))
@section('content')
<section class="content-header"><h1>{{ __('distributionnew::lang.production_stabilization') }}</h1></section>
<section class="content disnew-pos-page">
  <div class="row">
    @foreach($data as $key => $value)
      <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="small-box bg-white disnew-metric-card">
          <div class="inner"><h3>{{ number_format($value) }}</h3><p>{{ __('distributionnew::lang.'.$key) }}</p></div>
        </div>
      </div>
    @endforeach
  </div>
  <div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">{{ __('distributionnew::lang.quick_links') }}</h3></div>
    <div class="box-body">
      <a href="{{ route('distributionnew.production-stabilization.exceptions') }}" class="btn btn-primary">{{ __('distributionnew::lang.exceptions') }}</a>
      <a href="{{ route('distributionnew.production-stabilization.audits') }}" class="btn btn-info">{{ __('distributionnew::lang.audit_centre') }}</a>
    </div>
  </div>
</section>
@endsection
