@extends('poultry::layouts.app')
@section('title', __('poultry::lang.reports'))
@section('content')
<div class="row">
    @php
        $reports = [
            ['route' => 'poultry.report.performance', 'label' => __('poultry::lang.performance'), 'desc' => __('poultry::lang.performance_desc'), 'icon' => 'fa-line-chart'],
            ['route' => 'poultry.report.production',  'label' => __('poultry::lang.production'),  'desc' => __('poultry::lang.production_desc'),  'icon' => 'fa-circle-o'],
            ['route' => 'poultry.report.costing',     'label' => __('poultry::lang.costing'),     'desc' => __('poultry::lang.costing_desc'),     'icon' => 'fa-money'],
            ['route' => 'poultry.report.mortality',   'label' => __('poultry::lang.mortality'),   'desc' => __('poultry::lang.mortality_desc'),   'icon' => 'fa-heartbeat'],
        ];
    @endphp
    @foreach ($reports as $r)
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-body">
                    <h4><i class="fa {{ $r['icon'] }}"></i>
                        <a href="{{ route($r['route']) }}">{{ $r['label'] }}</a></h4>
                    <p class="text-muted">{{ $r['desc'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
