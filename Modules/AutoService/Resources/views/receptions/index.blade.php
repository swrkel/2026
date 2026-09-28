@extends('autoservice::layouts.master')
@section('content')
@include('autoservice::layouts.nav')
<section class="content-header"><h1>Vehicle Reception <a href="{{ route('autoservice.receptions.create') }}" class="btn btn-primary btn-sm pull-right">New Reception</a></h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Reception No</th><th>Vehicle</th><th>Customer</th><th>Received At</th><th>Odometer</th><th>Status</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->reception_no }}</td><td>{{ $row->vehicle_id }}</td><td>{{ $row->contact_id }}</td><td>{{ $row->received_at }}</td><td>{{ $row->odometer }}</td><td>{{ ucfirst($row->status) }}</td></tr>
@empty<tr><td colspan="6">No records found</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div></section>
@endsection
