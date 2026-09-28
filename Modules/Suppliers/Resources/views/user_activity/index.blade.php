@extends('suppliers::layouts.app')

@section('title', 'Supplier User Activity')
@section('module_title', 'Supplier User Activity')

@section('suppliers_content')
<div class="box box-primary supplier-list-box">
    <div class="box-header with-border">
        <h3 class="box-title">Contact User Activity - Suppliers</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('suppliers.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="box-body table-responsive supplier-table-wrap">
        <table class="table table-bordered table-striped supplier-standard-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Supplier Code</th>
                    <th>Supplier</th>
                    <th>Event</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td>{{ !empty($activity->created_at) ? date('Y-m-d H:i', strtotime($activity->created_at)) : '' }}</td>
                        <td>{{ $activity->supplier_code }}</td>
                        <td>{{ $activity->supplier_business_name ?: $activity->supplier_name }}</td>
                        <td>{{ ucfirst($activity->event ?? '') }}</td>
                        <td>{{ $activity->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No supplier user activity found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
