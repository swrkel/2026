@extends('autoservice::layouts.master')
@section('title','Vehicle Service History')
@section('content')
<div class="container-fluid">
  <div class="pull-right">
    <form method="POST" action="{{ route('autoservice.central_vehicle.logout') }}">@csrf<button class="btn btn-default btn-sm">Logout</button></form>
  </div>
  <h3>Central Vehicle Service History</h3>
  <div class="row">
    <div class="col-md-4"><div class="box box-primary"><div class="box-body"><strong>Vehicle</strong><br>{{ $vehicle->registration_no }} {{ $vehicle->make }} {{ $vehicle->model }}<br>Chassis: {{ $vehicle->chassis_no }}<br>Engine: {{ $vehicle->engine_no }}</div></div></div>
    <div class="col-md-4"><div class="box box-success"><div class="box-body"><strong>Current Owner</strong><br>{{ optional($vehicle->currentOwner)->owner_name }}<br>{{ optional($vehicle->currentOwner)->mobile }}</div></div></div>
    <div class="col-md-4"><div class="box box-info"><div class="box-body"><strong>Total Spent</strong><br>{{ number_format($totalSpent, 2) }}<br><strong>Last Mileage</strong> {{ number_format($lastMileage, 3) }}</div></div></div>
  </div>

  <div class="box box-warning">
    <div class="box-header"><h4>Transfer Ownership</h4></div>
    <div class="box-body">
      <p>Use this only when the vehicle has been sold or ownership has changed. The current registered owner must approve by OTP first, then the new owner must verify by OTP.</p>
      <form method="POST" action="{{ route('autoservice.central_vehicle.transfer.request', $vehicle->id) }}">
        @csrf
        <div class="row">
          <div class="col-md-3"><input name="owner_name" class="form-control" placeholder="New owner name" required></div>
          <div class="col-md-2"><input name="mobile" class="form-control" placeholder="New owner mobile" required></div>
          <div class="col-md-2"><input name="nic_no" class="form-control" placeholder="NIC / ID"></div>
          <div class="col-md-3"><input name="email" class="form-control" placeholder="Email"></div>
          <div class="col-md-2"><button class="btn btn-warning btn-block">Request Transfer</button></div>
        </div>
        <br><textarea name="address" class="form-control" placeholder="New owner address"></textarea>
      </form>
    </div>
  </div>

  <div class="box"><div class="box-header"><h4>All Service Records From All Businesses</h4></div><div class="box-body table-responsive">
    <table class="table table-bordered table-striped">
      <thead><tr><th>Date</th><th>Business</th><th>Job No</th><th>Mileage</th><th>Work Done</th><th>Spare Parts</th><th>Oil Used</th><th>Cost</th></tr></thead>
      <tbody>
      @forelse($ownerRecords as $r)
        <tr>
          <td>{{ $r['service_date'] }}</td>
          <td>{{ $r['business_name'] }}</td>
          <td>{{ $r['job_no'] }}</td>
          <td class="text-right">{{ number_format($r['mileage'], 3) }}</td>
          <td>{{ $r['work_done'] ?: $r['diagnosis'] ?: $r['complaints'] }}</td>
          <td><pre style="white-space:pre-wrap">{{ json_encode($r['parts_used'], JSON_PRETTY_PRINT) }}</pre></td>
          <td><pre style="white-space:pre-wrap">{{ json_encode($r['oils_used'], JSON_PRETTY_PRINT) }}</pre></td>
          <td class="text-right">{{ number_format($r['grand_total'], 2) }}</td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-center">No service records have been posted yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div></div>
</div>
@endsection
