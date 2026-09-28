@extends('layouts.app')

@section('title', __('petro::lang.petro_settings'))

@section('content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petro::lang.petro')</a></li>
                    <li><span>@lang('petro::lang.petro_settings')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content main-content-inner">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['url' => action('\Modules\Petro\Http\Controllers\CustomerBillVatPrefixController@store'), 'method' => 'post']) !!}
            <div class="row">
                <div class="col-md-6">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('show_mechanical_meter_too', 1, $show_mechanical_meter_too, [
                                'class' => 'input-icheck',
                                'id' => 'show_mechanical_meter_too',
                            ]) !!}
                            Show the Mechanical Meter too
                        </label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                        @lang('messages.save')
                    </button>
                </div>
            </div>
        {!! Form::close() !!}
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="petro_settings_table">
                <thead>
                    <tr>
                        <th>Current Date & Time</th>
                        <th>Check Box Status</th>
                        <th>Need to show the Mechanical Meter too</th>
                        <th>Added User</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        $('#petro_settings_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            ajax: {
                url: '{{ action('\Modules\Petro\Http\Controllers\CustomerBillVatPrefixController@index') }}',
            },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'current_date_time', name: 'customer_bill_vat_prefixes.created_at' },
                { data: 'checkbox_status', name: 'customer_bill_vat_prefixes.starting_no', searchable: false },
                { data: 'show_mechanical_meter_too', name: 'customer_bill_vat_prefixes.starting_no', searchable: false },
                { data: 'user_created', name: 'users.username' },
            ],
        });
    });
</script>
@endsection
