@extends('beautysaloons::layouts.app')
@section('title', 'Staff Dashboard')
@section('content')
<section class="content-header"><h1>Staff Dashboard</h1></section>
<section class="content bs-dashboard-page">
@include('beautysaloons::dashboard.partials_kpi', ['cards' => [
 ['title'=>'Completed Today','value'=>$data['completed_today'] ?? 0],
 ['title'=>'Pending Today','value'=>$data['pending_today'] ?? 0],
 ['title'=>'Commission Today','value'=>number_format($data['commission_today'] ?? 0,2)],
 ['title'=>'Commission Month','value'=>number_format($data['commission_month'] ?? 0,2)],
]])
</section>
@endsection
@push('css')<link rel="stylesheet" href="{{ asset('Modules/BeautySaloons/Resources/css/dashboard/beauty-dashboard.css') }}">@endpush
