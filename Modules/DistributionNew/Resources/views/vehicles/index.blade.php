@extends('layouts.app')
@section('title', __('distributionnew::lang.vehicles'))
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => __('distributionnew::lang.vehicles')])
<section class="content distributionnew-page">
    <div class="disnew-pos-card">
        <div class="disnew-toolbar">
            <div>
                <h4>{{ __('distributionnew::lang.vehicles') }}</h4>
                <small>
                    @if($limitState['limit'] === null)
                        {{ __('distributionnew::lang.unlimited_vehicles') }}
                    @else
                        {{ __('distributionnew::lang.vehicle_limit_status', ['used' => $limitState['used'], 'limit' => $limitState['limit']]) }}
                    @endif
                </small>
            </div>
            @can('distributionnew.vehicle.create')
                @if($limitState['allowed'])
                    <a href="{{ route('distributionnew.vehicles.create') }}" class="btn btn-primary btn-sm">{{ __('distributionnew::lang.add_vehicle') }}</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>{{ __('distributionnew::lang.vehicle_limit_reached') }}</button>
                @endif
            @endcan
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped disnew-table">
                <thead><tr><th>{{ __('distributionnew::lang.vehicle_no') }}</th><th>{{ __('distributionnew::lang.vehicle_name') }}</th><th>{{ __('distributionnew::lang.driver') }}</th><th>{{ __('distributionnew::lang.capacity') }}</th><th>{{ __('distributionnew::lang.status') }}</th><th>{{ __('distributionnew::lang.action') }}</th></tr></thead>
                <tbody>
                @forelse($vehicles as $vehicle)
                    <tr>
                        <td>{{ $vehicle->vehicle_no }}</td><td>{{ $vehicle->vehicle_name }}</td><td>{{ $vehicle->driver_name }}<br><small>{{ $vehicle->driver_mobile }}</small></td><td>{{ $vehicle->capacity_qty }}</td><td>{{ ucfirst($vehicle->status) }}</td>
                        <td><a href="{{ route('distributionnew.vehicles.edit', $vehicle->id) }}" class="btn btn-xs btn-info">{{ __('distributionnew::lang.edit') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">{{ __('distributionnew::lang.no_records_found') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $vehicles->links() }}
    </div>
</section>
@endsection
