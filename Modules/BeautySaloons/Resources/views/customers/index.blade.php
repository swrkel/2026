@extends('beautysaloons::layouts.app')
@section('title', __('beautysaloons::customers.customers'))
@section('content')
<section class="content-header">
    <h1>@lang('beautysaloons::customers.customers')</h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <a href="{{ route('beautysaloons.customers.create') }}" class="btn btn-primary btn-lg pull-right">
                <i class="fa fa-plus"></i> @lang('beautysaloons::customers.add_customer')
            </a>
        </div>
        <div class="box-body">
            <div class="table-responsive bs-table-scroll">
                <table class="table table-bordered table-striped" id="bs_customers_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Action</th><th>Code</th><th>Name</th><th>Mobile</th><th>Email</th><th>Type</th><th>Status</th><th>Created</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('modules/beautysaloons/js/beauty_saloons_customers.js') }}"></script>
@endsection
