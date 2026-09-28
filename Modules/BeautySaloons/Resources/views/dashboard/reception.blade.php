@extends('beautysaloons::layouts.app')
@section('title', 'Reception Dashboard')
@section('content')
<section class="content-header"><h1>Reception Dashboard</h1></section>
<section class="content bs-dashboard-page">
@include('beautysaloons::dashboard.partials_kpi', ['cards' => [
 ['title'=>'Waiting Queue','value'=>$data['waiting_queue'] ?? 0],
 ['title'=>'Checked In','value'=>$data['checked_in'] ?? 0],
 ['title'=>'Staff Available','value'=>$data['staff_available'] ?? 0],
 ['title'=>'Rooms Available','value'=>$data['rooms_available'] ?? 0],
]])
</section>
@endsection
@push('css')<link rel="stylesheet" href="{{ asset('Modules/BeautySaloons/Resources/css/dashboard/beauty-dashboard.css') }}">@endpush
