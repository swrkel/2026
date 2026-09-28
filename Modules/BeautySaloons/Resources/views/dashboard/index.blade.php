@extends('beautysaloons::layout')
@section('beauty_content')
<section class="content-header"><h1>Beauty Saloons Dashboard</h1></section>
<section class="content">
    <div class="row">
        <div class="col-md-3"><div class="bs-card">Today Appointments<br><strong>0</strong></div></div>
        <div class="col-md-3"><div class="bs-card">Today Sales<br><strong>0.00</strong></div></div>
        <div class="col-md-3"><div class="bs-card">Pending Payments<br><strong>0.00</strong></div></div>
        <div class="col-md-3"><div class="bs-card">Active Staff<br><strong>0</strong></div></div>
    </div>
</section>
@endsection
