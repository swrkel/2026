@extends('expensesnew::layouts.app')
@section('title', __('expensesnew::lang.master_data'))
@section('content')
<div class="expnew-page">
    <div class="expnew-card expnew-mb-20">
        <div class="expnew-card-header">
            <div>
                <h3>@yield('title')</h3>
                <p>{{ __('expensesnew::lang.standalone_master_data_note') }}</p>
            </div>
        </div>
        <form method="POST" action="{{ url()->current() }}" class="expnew-grid-form">
            @csrf
            <div><label>{{ __('expensesnew::lang.code') }}</label><input name="code" class="form-control"></div>
            <div><label>{{ __('expensesnew::lang.name') }}</label><input name="name" class="form-control" required></div>
            <div><label>{{ __('expensesnew::lang.description') }}</label><input name="description" class="form-control"></div>
            <div class="expnew-form-actions"><button class="expnew-btn expnew-btn-primary">{{ __('expensesnew::lang.save') }}</button></div>
        </form>
    </div>
    <div class="expnew-card">
        @include('expensesnew::components.toolbar')
        <div class="table-responsive">
            <table class="table table-bordered table-striped expnew-table">
                <thead><tr><th>{{ __('expensesnew::lang.code') }}</th><th>{{ __('expensesnew::lang.name') }}</th><th>{{ __('expensesnew::lang.description') }}</th><th>{{ __('expensesnew::lang.status') }}</th><th>{{ __('expensesnew::lang.action') }}</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ $row->description }}</td><td>{{ $row->is_active ? __('expensesnew::lang.active') : __('expensesnew::lang.inactive') }}</td><td><form method="POST" action="{{ url()->current().'/'.$row->id }}">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">{{ __('expensesnew::lang.delete') }}</button></form></td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('expensesnew::lang.no_records_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </div>
</div>
@endsection
