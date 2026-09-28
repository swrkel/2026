@extends('poultry::layouts.app')
@section('title', __('poultry::lang.masters'))

@section('content')
<div class="row">
    @php
        $cards = [
            ['route' => 'poultry.farms.index',  'label' => __('poultry::lang.farms'),  'count' => $counts['farms'],  'icon' => 'fa-home'],
            ['route' => 'poultry.houses.index', 'label' => __('poultry::lang.houses'), 'count' => $counts['houses'], 'icon' => 'fa-building'],
            ['route' => 'poultry.breeds.index', 'label' => __('poultry::lang.breeds'), 'count' => $counts['breeds'], 'icon' => 'fa-tags'],
            ['route' => 'poultry.egg-grades.index', 'label' => __('poultry::lang.egg_grades'), 'count' => $counts['grades'], 'icon' => 'fa-circle-o'],
            ['route' => 'poultry.vaccination-schedules.index', 'label' => __('poultry::lang.vaccination_schedules'), 'count' => $counts['schedules'], 'icon' => 'fa-medkit'],
        ];
    @endphp
    @foreach ($cards as $card)
        <div class="col-md-4 col-sm-6">
            <a href="{{ route($card['route']) }}" class="small-box bg-aqua" style="display:block;color:#fff">
                <div class="inner">
                    <h3>{{ $card['count'] }}</h3>
                    <p>{{ $card['label'] }}</p>
                </div>
                <div class="icon"><i class="fa {{ $card['icon'] }}"></i></div>
                <span class="small-box-footer">@lang('poultry::lang.manage') <i class="fa fa-arrow-circle-right"></i></span>
            </a>
        </div>
    @endforeach
</div>
@endsection
