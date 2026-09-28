@extends('beautysaloons::layouts.app')
@section('title', 'Branch Manager Dashboard')
@section('content')
<section class="content-header"><h1>Branch Manager Dashboard</h1></section>
<section class="content bs-dashboard-page">
@include('beautysaloons::dashboard.partials_kpi', ['cards' => [
 ['title'=>'Appointments Today','value'=>$data['appointments_today'] ?? 0],
 ['title'=>'Walk-ins Today','value'=>$data['walkins_today'] ?? 0],
 ['title'=>'No Shows Today','value'=>$data['no_shows_today'] ?? 0],
 ['title'=>'Daily Collection','value'=>number_format($data['daily_collection'] ?? 0,2)],
]])
</section>
@endsection
@push('css')<link rel="stylesheet" href="{{ asset('Modules/BeautySaloons/Resources/css/dashboard/beauty-dashboard.css') }}">@endpush
