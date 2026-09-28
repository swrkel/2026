@extends('autoservice::layouts.master')
@section('title','Central Vehicle Safe History')
@section('content')
<div class="container-fluid">
  <h3>Central Vehicle Safe History</h3>
  <div class="alert alert-info">
    This workshop view hides previous workshop/business details, contact details, invoice numbers, payment details and all prices. Only service date, mileage, work done, parts, products and lubricants are shown.
  </div>
  <div class="row">
    <div class="col-md-6"><div class="box box-primary"><div class="box-body"><strong>Vehicle</strong><br>{{ $vehicle->registration_no }} {{ $vehicle->make }} {{ $vehicle->model }}<br>Chassis: {{ $vehicle->chassis_no }}<br>Engine: {{ $vehicle->engine_no }}</div></div></div>
    <div class="col-md-6"><div class="box box-info"><div class="box-body"><strong>Last Mileage</strong><br>{{ number_format($lastMileage, 3) }}</div></div></div>
  </div>
  <div class="box"><div class="box-header"><h4>Previous Service Items</h4></div><div class="box-body table-responsive">
    <table class="table table-bordered table-striped">
      <thead><tr><th>Date</th><th>Mileage</th><th>Work Done / Diagnosis</th><th>Parts / Products Used</th><th>Lubricants Used</th></tr></thead>
      <tbody>
      @forelse($workshopRecords as $r)
        <tr>
          <td>{{ $r['service_date'] }}</td>
          <td class="text-right">{{ number_format($r['mileage'], 3) }}</td>
          <td>{{ $r['work_done'] ?: $r['diagnosis'] ?: $r['complaints'] }}</td>
          <td><pre style="white-space:pre-wrap">{{ json_encode($r['parts_used'], JSON_PRETTY_PRINT) }}</pre></td>
          <td><pre style="white-space:pre-wrap">{{ json_encode($r['oils_used'], JSON_PRETTY_PRINT) }}</pre></td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-center">No previous service records are available.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div></div>
</div>
@endsection
