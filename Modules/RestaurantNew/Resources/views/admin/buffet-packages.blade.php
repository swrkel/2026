@extends('restaurantnew::layouts.app')
@section('title', $title ?? 'Buffet Packages')
@section('content')
<div class="restaurantnew-page">
    <div class="rn-card rn-admin-card">
        <div class="rn-card-header">
            <h3>Buffet Packages</h3>
            <button class="btn btn-primary rn-open-admin-form">{ __('restaurantnew::lang.add_new') }</button>
        </div>
        <div class="rn-toolbar">
            <input type="text" class="form-control rn-search" placeholder="{ __('restaurantnew::lang.search') }">
            <button class="btn btn-outline-secondary">CSV</button>
            <button class="btn btn-outline-secondary">Excel</button>
            <button class="btn btn-outline-secondary">PDF</button>
            <button class="btn btn-outline-secondary">Print</button>
            <button class="btn btn-outline-secondary">{ __('restaurantnew::lang.column_visibility') }</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped rn-admin-table">
                <thead><tr><th>#</th><th>{ __('restaurantnew::lang.name') }</th><th>{ __('restaurantnew::lang.status') }</th><th>{ __('restaurantnew::lang.action') }</th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    <tr><td>{ $loop->iteration }</td><td>{ $record->name ?? $record->title ?? $record->event_name ?? $record->order_no }</td><td>{ $record->is_active ? __('restaurantnew::lang.active') : __('restaurantnew::lang.inactive') }</td><td><button class="btn btn-sm btn-info">{ __('restaurantnew::lang.edit') }</button></td></tr>
                @empty
                    <tr><td colspan="4" class="text-center">{ __('restaurantnew::lang.no_records_found') }</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        { $records->links() }
    </div>
</div>
@endsection
