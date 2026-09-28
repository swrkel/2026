{{--
    SW Logs.

    Changes to shifts and settlements, in one place. Read only - there is no
    edit and no delete, and no route to either. A log that can be altered
    answers nothing.
--}}

@extends('layouts.app')

@section('title', __('sw::lang.sw_logs'))

@section('content')

<section class="content-header">
    <h1>@lang('sw::lang.sw_logs')
        <small>@lang('sw::lang.sw_logs_subtitle')</small>
    </h1>
</section>

<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_log_location', __('purchase.business_location') . ':') !!}
                        {!! Form::select('sw_log_location', $business_locations, null, [
                            'class' => 'form-control select2', 'id' => 'sw_log_location',
                            'style' => 'width:100%', 'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_log_type', __('sw::lang.document') . ':') !!}
                        {!! Form::select('sw_log_type', [
                            'shift' => __('sw::lang.shift'),
                            'settlement' => __('sw::lang.settlement'),
                        ], null, [
                            'class' => 'form-control select2', 'id' => 'sw_log_type',
                            'style' => 'width:100%', 'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_log_from', __('sw::lang.from') . ':') !!}
                        {!! Form::date('sw_log_from', null, ['class' => 'form-control', 'id' => 'sw_log_from']) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_log_to', __('sw::lang.to') . ':') !!}
                        {!! Form::date('sw_log_to', null, ['class' => 'form-control', 'id' => 'sw_log_to']) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="checkbox" style="margin-top:0">
                            <label>
                                <input type="checkbox" id="sw_log_after_closure">
                                <strong>@lang('sw::lang.after_closure_only')</strong>
                            </label>
                            <div class="help-block" style="margin:0">
                                @lang('sw::lang.after_closure_help')
                            </div>
                        </div>
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('sw::lang.change_history')])

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="sw_logs_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('sw::lang.when')</th>
                        <th>@lang('sw::lang.document')</th>
                        <th>@lang('sw::lang.record')</th>
                        <th>@lang('messages.action')</th>
                        <th>@lang('sw::lang.what_changed')</th>
                        <th>@lang('sw::lang.reason')</th>
                        <th>@lang('sw::lang.user')</th>
                        <th>@lang('sw::lang.location')</th>
                    </tr>
                </thead>
            </table>
        </div>

    @endcomponent

</section>

@endsection

@push('javascript')
<script>
$(function () {

    var swLogTable = $('#sw_logs_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route('sw.logs.data') }}',
            dataSrc: 'data',
            data: function (d) {
                d.location_id        = $('#sw_log_location').val();
                d.document_type      = $('#sw_log_type').val();
                d.date_from          = $('#sw_log_from').val();
                d.date_to            = $('#sw_log_to').val();
                d.after_closure_only = $('#sw_log_after_closure').is(':checked') ? 1 : 0;
            }
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'when' },
            { data: 'document' },
            { data: 'record' },
            { data: 'action' },
            { data: 'changes', orderable: false },
            { data: 'reason' },
            { data: 'user' },
            { data: 'location' }
        ]
    });

    $('#sw_log_location, #sw_log_type, #sw_log_from, #sw_log_to, #sw_log_after_closure')
        .on('change', function () { swLogTable.ajax.reload(); });

});
</script>
@endpush
