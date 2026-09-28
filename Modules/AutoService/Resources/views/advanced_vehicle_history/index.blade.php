@extends('layouts.app')
@section('title', 'Advanced Vehicle History')
@section('content')
<section class="content-header">
    <h1>Advanced Vehicle History <small>Vehicle medical record</small></h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Search Vehicles</h3>
        </div>
        <div class="box-body">
            <form method="get" class="row">
                <div class="col-md-3"><label>Registration No</label><input name="registration_no" value="{{ $filters['registration_no'] ?? '' }}" class="form-control"></div>
                <div class="col-md-3"><label>VIN / Chassis</label><input name="vin" value="{{ $filters['vin'] ?? '' }}" class="form-control"></div>
                <div class="col-md-3"><label>Make</label><input name="make" value="{{ $filters['make'] ?? '' }}" class="form-control"></div>
                <div class="col-md-3" style="padding-top:25px"><button class="btn btn-primary"><i class="fa fa-search"></i> Search</button> <a href="{{ route('autoservice.advanced_vehicle_history.index') }}" class="btn btn-default">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title">Vehicles</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Registration</th><th>Make/Model</th><th>VIN</th><th>Engine / Chassis</th><th>Odometer</th><th>Next Service</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($vehicles as $v)
                    <tr>
                        <td><strong>{{ $v->registration_no }}</strong></td>
                        <td>{{ $v->make }} {{ $v->model }} {{ $v->year }}</td>
                        <td>{{ $v->vin }}</td>
                        <td>{{ $v->engine_no }} / {{ $v->chassis_no }}</td>
                        <td>{{ number_format((float)($v->current_odometer ?? 0), 0) }}</td>
                        <td>{{ $v->next_service_date }} {{ $v->next_service_odometer ? ' / '.number_format($v->next_service_odometer) : '' }}</td>
                        <td><a href="{{ route('autoservice.advanced_vehicle_history.show', $v->id) }}" class="btn btn-xs btn-info"><i class="fa fa-history"></i> Open History</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No vehicles found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
