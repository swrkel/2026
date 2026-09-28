@extends('pos::layouts.app', ['title' => __('pos::messages.configuration_center')])

@section('pos_content')
<div class="row">
    @foreach($sections as $key => $section)
        <div class="col-md-4 col-sm-6 col-xs-12">
            <a href="{{ route('pos.configuration.section', $key) }}" class="pos-config-card">
                <div class="pos-config-icon"><i class="{{ $section['icon'] }}"></i></div>
                <div class="pos-config-title">{{ $section['title'] }}</div>
                <div class="pos-config-desc">{{ $section['description'] }}</div>
            </a>
        </div>
    @endforeach
</div>
@endsection
