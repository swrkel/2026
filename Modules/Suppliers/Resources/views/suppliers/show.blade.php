@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.view_supplier'))
@section('suppliers_content')
<section class="content-header"><h1>@lang('suppliers::lang.view_supplier')</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            <div class="alert alert-info">
                @lang('suppliers::lang.profile_screen_separated_notice')
            </div>
            <a href="{{ route('suppliers.profile.index', $supplier->id) }}" class="btn btn-primary">
                <i class="fa fa-user"></i> @lang('suppliers::lang.open_supplier_profile')
            </a>
            <a href="{{ route('suppliers.records.index') }}" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> @lang('suppliers::lang.back')
            </a>
        </div>
    </div>
</section>
@endsection
