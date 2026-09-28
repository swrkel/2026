@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.email_history'))

@section('suppliers_content')
<section class="content-header">
    <h1>{{ __('suppliers::lang.email_history') }} <small>{{ $supplier->name ?? $supplier->supplier_business_name }}</small></h1>
</section>

<section class="content">
    @include('suppliers::communication.partials.nav', ['supplier' => $supplier])
    
    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title">{{ __('suppliers::lang.email_history') }}</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped supplier-communication-table">
                <thead>
                    <tr>
                        <th>{{ __('suppliers::lang.date') }}</th>
                        <th>{{ __('suppliers::lang.title') }}</th>
                        <th>{{ __('suppliers::lang.description') }}</th>
                        <th>{{ __('suppliers::lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($emails as $item)
                        <tr>
                            <td>{ $item->created_at ?? '' }</td>
                            <td>{ $item->title ?? '' }</td>
                            <td>{ $item->description ?? '' }</td>
                            <td><div class="btn-group"><button class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">{{ __('suppliers::lang.actions') }} <span class="caret"></span></button></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No email history found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/suppliers/js/communication/supplier-communication.js') }}"></script>
@endsection
