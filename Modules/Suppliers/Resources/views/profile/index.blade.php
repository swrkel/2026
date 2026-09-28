@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.supplier_profile'))

@push('suppliers_styles')
<link rel="stylesheet" href="{{ asset('modules/suppliers/css/suppliers/profile.css') }}?v=20260731-2">
@endpush

@section('suppliers_content')
<section class="content-header">
    <h1>@lang('suppliers::lang.supplier_profile') <small>{{ $supplier->name }}</small></h1>
</section>

<section class="content supplier-profile-page">
    <div class="box box-primary">
        <div class="box-header with-border clearfix">
            <h3 class="box-title">{{ $supplier->name }}</h3>
            <div class="pull-right">
                <a href="{{ route('suppliers.records.index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> @lang('suppliers::lang.back')
                </a>
                @can('supplier.update')
                    <a href="{{ route('suppliers.records.edit', $supplier->id) }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-edit"></i> @lang('suppliers::lang.edit')
                    </a>
                @endcan
            </div>
        </div>
        <div class="box-body">
            @include('suppliers::partials.tabs', ['supplier' => $supplier])

            <ul class="nav nav-tabs supplier-profile-tabs" role="tablist">
                <li class="active"><a href="#tab-basic" data-toggle="tab">@lang('suppliers::lang.basic_information')</a></li>
                <li><a href="#tab-contact" data-toggle="tab">@lang('suppliers::lang.contact_information')</a></li>
                <li><a href="#tab-address" data-toggle="tab">@lang('suppliers::lang.address_information')</a></li>
                <li><a href="#tab-financial" data-toggle="tab">@lang('suppliers::lang.financial_information')</a></li>
                <li><a href="#tab-tax" data-toggle="tab">@lang('suppliers::lang.tax_information')</a></li>
                <li><a href="#tab-documents" data-toggle="tab">@lang('suppliers::lang.documents_attachments')</a></li>
                <li><a href="#tab-purchases" data-toggle="tab">@lang('suppliers::lang.purchase_history')</a></li>
                <li><a href="#tab-ledger" data-toggle="tab">@lang('suppliers::lang.ledger_summary')</a></li>
                <li><a href="#tab-notes" data-toggle="tab">@lang('suppliers::lang.notes_remarks')</a></li>
                <li><a href="#tab-audit" data-toggle="tab">@lang('suppliers::lang.audit_trail')</a></li>
            </ul>

            <div class="tab-content supplier-profile-tab-content">
                <div class="tab-pane active" id="tab-basic">
                    @include('suppliers::suppliers.profile.tabs.basic_information')
                </div>
                <div class="tab-pane" id="tab-contact">
                    @include('suppliers::suppliers.profile.tabs.contact_information')
                </div>
                <div class="tab-pane" id="tab-address">
                    @include('suppliers::suppliers.profile.tabs.address_information')
                </div>
                <div class="tab-pane" id="tab-financial">
                    @include('suppliers::suppliers.profile.tabs.financial_information')
                </div>
                <div class="tab-pane" id="tab-tax">
                    @include('suppliers::suppliers.profile.tabs.tax_information')
                </div>
                <div class="tab-pane" id="tab-documents">
                    @include('suppliers::suppliers.profile.tabs.documents')
                </div>
                <div class="tab-pane" id="tab-purchases">
                    @include('suppliers::suppliers.profile.tabs.purchase_history')
                </div>
                <div class="tab-pane" id="tab-ledger">
                    @include('suppliers::suppliers.profile.tabs.ledger_summary')
                </div>
                <div class="tab-pane" id="tab-notes">
                    @include('suppliers::suppliers.profile.tabs.notes')
                </div>
                <div class="tab-pane" id="tab-audit">
                    @include('suppliers::suppliers.profile.tabs.audit_trail')
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('suppliers_scripts')
<script src="{{ asset('modules/suppliers/js/suppliers/profile/index.js') }}?v=20260731-2"></script>
@endpush
