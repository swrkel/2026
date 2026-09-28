@extends('beautysaloons::layouts.app')
@section('title', __('beautysaloons::dashboard.executive_dashboard'))
@section('content')
<section class="content-header">
    <h1>{{ __('beautysaloons::dashboard.executive_dashboard') }}</h1>
</section>
<section class="content bs-dashboard-page" data-dashboard-url="{{ route('beautysaloons.dashboards.executive.data') }}">
    @include('beautysaloons::dashboard.partials_kpi', ['cards' => [
        ['title' => 'Today Revenue', 'value' => number_format($data['owner']['revenue_today'] ?? 0, 2)],
        ['title' => 'Month Revenue', 'value' => number_format($data['owner']['revenue_month'] ?? 0, 2)],
        ['title' => 'Year Revenue', 'value' => number_format($data['owner']['revenue_year'] ?? 0, 2)],
        ['title' => 'Today Appointments', 'value' => $data['owner']['appointments_today'] ?? 0],
    ]])
    <div class="row">
        <div class="col-md-8"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Revenue Trend</h3></div><div class="box-body"><canvas id="bs_revenue_trend"></canvas></div></div></div>
        <div class="col-md-4"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Payment Mix</h3></div><div class="box-body"><canvas id="bs_payment_mix"></canvas></div></div></div>
    </div>
    <script type="application/json" id="bs_dashboard_payload">{!! json_encode($charts) !!}</script>
</section>
@endsection
@push('css')<link rel="stylesheet" href="{{ asset('Modules/BeautySaloons/Resources/css/dashboard/beauty-dashboard.css') }}">@endpush
@push('javascript')<script src="{{ asset('Modules/BeautySaloons/Resources/js/dashboard/beauty-dashboard.js') }}"></script>@endpush
