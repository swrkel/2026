@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::messages.qr_menus'))

@section('content')
<div class="restaurantnew-page">
    <div class="rn-card rn-card-primary">
        <div class="rn-card-header">
            <h3>{{ __('restaurantnew::messages.qr_menus') }}</h3>
            <button class="btn btn-primary" data-toggle="modal" data-target="#rnQrMenuModal">{{ __('restaurantnew::messages.add_qr_menu') }}</button>
        </div>
        <div class="rn-toolbar">
            <input type="text" class="form-control rn-search" placeholder="{{ __('restaurantnew::messages.search') }}">
            <button class="btn btn-outline-secondary rn-export">CSV</button>
            <button class="btn btn-outline-secondary rn-export">Excel</button>
            <button class="btn btn-outline-secondary rn-export">PDF</button>
            <button class="btn btn-outline-secondary rn-export">Print</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped rn-datatable">
                <thead>
                    <tr>
                        <th>{{ __('restaurantnew::messages.title') }}</th>
                        <th>{{ __('restaurantnew::messages.location') }}</th>
                        <th>{{ __('restaurantnew::messages.self_order') }}</th>
                        <th>{{ __('restaurantnew::messages.status') }}</th>
                        <th>{{ __('restaurantnew::messages.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td>{{ $menu->title }}</td>
                            <td>{{ $menu->location_id }}</td>
                            <td>{{ $menu->allow_self_order ? __('restaurantnew::messages.yes') : __('restaurantnew::messages.no') }}</td>
                            <td>{{ $menu->is_active ? __('restaurantnew::messages.active') : __('restaurantnew::messages.inactive') }}</td>
                            <td><a class="btn btn-sm btn-info" target="_blank" href="{{ route('restaurantnew.public.menu', $menu->public_token) }}">{{ __('restaurantnew::messages.open') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('restaurantnew::messages.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $menus->links() }}
    </div>
</div>
@endsection
