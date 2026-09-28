@extends('layouts.app')
@section('title', __('lang_v1.sales_agents'))

@section('content')

    <section class="content-header">
        <h1>@lang('lang_v1.sales_agents')
            <small>@lang('lang_v1.manage_sales_agents')</small>
        </h1>
    </section>

    <section class="content">
        {{-- Add Sales Agent Form --}}
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('lang_v1.add_sales_agent')</h3>
            </div>
            <div class="box-body">
                {!! Form::open([
                    'url' => route('salesagent.management.store'),
                    'method' => 'POST',
                    'id' => 'sales_agent_add_form',
                ]) !!}

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('datetime', __('lang_v1.date_time') . ':') !!}
                            {!! Form::text('datetime', \Carbon\Carbon::now()->format('Y-m-d H:i'), [
                                'class' => 'form-control',
                                'readonly',
                                'id' => 'datetime_display',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('name', __('lang_v1.sales_agent_name') . ':*') !!}
                            {!! Form::text('name', null, [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => __('lang_v1.sales_agent_name'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('joined_date', __('lang_v1.joined_date') . ':*') !!}
                            {!! Form::date('joined_date', \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control', 'required']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, $default_location_id ?? null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('messages.please_select'),
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('employment_grade', __('lang_v1.employment_grade') . ':') !!}
                            {!! Form::text('employment_grade', null, [
                                'class' => 'form-control',
                                'placeholder' => __('lang_v1.employment_grade'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('salary', __('lang_v1.salary') . ':') !!}
                            {!! Form::text('salary', null, [
                                'class' => 'form-control input_number sales-agent-decimal',
                                'placeholder' => number_format(0, $currency_precision ?? 2),
                                'data-decimal-precision' => $currency_precision ?? 2,
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('commission_entitled', 'Commission Entitled:') !!}
                            {!! Form::select('commission_entitled', ['yes' => __('messages.yes'), 'no' => __('messages.no')], 'yes', [
                                'class' => 'form-control select2 commission-entitled',
                                'id' => 'commission_entitled_add',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3 commission-fields-add">
                        <div class="form-group">
                            {!! Form::label('commission_type', 'Commission Type:') !!}
                            {!! Form::select('commission_type', ['percentage' => 'Percentage', 'fixed' => 'Fixed'], 'percentage', [
                                'class' => 'form-control select2',
                                'id' => 'commission_type_add',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 commission-fields-add">
                        <div class="form-group">
                            {!! Form::label('commission', 'Commission Value:') !!}
                            {!! Form::text('commission', null, [
                                'class' => 'form-control input_number sales-agent-decimal commission-value',
                                'placeholder' => number_format(0, $currency_precision ?? 2),
                                'data-decimal-precision' => $currency_precision ?? 2,
                                'id' => 'commission_value_add',
                            ]) !!}
                            <small class="help-block commission-percentage-help">Do not type % sign. System will treat the value as percentage.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('user_id', __('lang_v1.link_to_user') . ':') !!}
                            {!! Form::select('user_id', $users, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('messages.please_select'),
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary pull-right">
                            <i class="fa fa-save"></i> @lang('messages.save')
                        </button>
                    </div>
                </div>

                {!! Form::close() !!}
            </div>
        </div>

        {{-- Filters --}}
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('report.filters')</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('filter_location', __('purchase.business_location') . ':') !!}
                            {!! Form::select('filter_location', $business_locations, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('lang_v1.all'),
                                'id' => 'filter_location',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('filter_employment_grade', __('lang_v1.employment_grade') . ':') !!}
                            {!! Form::select(
                                'filter_employment_grade',
                                collect($employment_grades)->mapWithKeys(function ($val) {
                                    return [$val => $val];
                                }),
                                null,
                                [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('lang_v1.all'),
                                    'id' => 'filter_employment_grade',
                                ],
                            ) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date_range', __('report.date_range') . ':') !!}
                            {!! Form::text(
                                'date_range',
                                @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month'),
                                [
                                    'placeholder' => __('lang_v1.select_a_date_range'),
                                    'class' => 'form-control',
                                    'id' => 'sales_agent_date_range',
                                    'readonly',
                                ],
                            ) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('sales_agent', __('salesagent::lang.sales_agent') . ':') !!}
                            {!! Form::select('sales_agent', $sales_agents, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('lang_v1.all'),
                                'id' => 'sales_agent',
                            ]) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sales Agents List --}}
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('lang_v1.sales_agents_list')</h3>
            </div>
            <div class="box-body sales-agent-table-box">
                <div class="table-responsive sales-agent-table-responsive">
                    <table class="table table-bordered table-striped" id="sales_agents_table">
                        <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('lang_v1.name')</th>
                            <th>@lang('lang_v1.joined_date')</th>
                            <th>@lang('lang_v1.employment_grade')</th>
                            <th>@lang('lang_v1.salary')</th>
                            <th>Commission Entitled</th>
                            <th>Commission Type</th>
                            <th>Commission Value</th>
                            <th>@lang('purchase.business_location')</th>
                            <th>@lang('lang_v1.added_by')</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- Add Commission Modal --}}
    <div class="modal fade" id="add_commission_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {!! Form::open([
                    'url' => route('salesagent.management.commission.store'),
                    'method' => 'POST',
                    'id' => 'add_commission_form',
                ]) !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">@lang('lang_v1.add_commission') - <span id="commission_agent_name"></span></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sales_agent_id" id="commission_sales_agent_id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('commission_datetime', __('lang_v1.date_time') . ':') !!}
                                {!! Form::text('commission_datetime', \Carbon\Carbon::now()->format('Y-m-d H:i'), [
                                    'class' => 'form-control',
                                    'readonly',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('amount', __('lang_v1.commission_amount') . ':*') !!}
                                {!! Form::text('amount', null, [
                                    'class' => 'form-control input_number sales-agent-decimal',
                                    'required',
                                    'placeholder' => number_format(0, $currency_precision ?? 2),
                                    'data-decimal-precision' => $currency_precision ?? 2,
                                ]) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('period_start', __('lang_v1.period_start') . ':') !!}
                                {!! Form::date('period_start', null, ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('period_end', __('lang_v1.period_end') . ':') !!}
                                {!! Form::date('period_end', null, ['class' => 'form-control']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('commission_for', __('lang_v1.commission_for') . ':') !!}
                                {!! Form::text('commission_for', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.commission_for'),
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('ref_bill_no', __('lang_v1.ref_bill_no') . ':') !!}
                                {!! Form::text('ref_bill_no', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.ref_bill_no')]) !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    {{-- View/Edit Modal Container --}}
    <div class="modal fade" id="sales_agent_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" id="sales_agent_modal_content">
                {{-- Content loaded via AJAX --}}
            </div>
        </div>
    </div>

@endsection


@section('css')
    <style>
        .sales-agent-table-box,
        .sales-agent-table-responsive,
        #sales_agents_table_wrapper,
        #sales_agents_table,
        #sales_agents_table tbody,
        #sales_agents_table tr,
        #sales_agents_table td {
            overflow: visible !important;
        }

        .sales-agent-table-responsive {
            min-height: 260px;
        }

        #sales_agents_table .btn-group {
            position: static !important;
        }

        #sales_agents_table .dropdown-menu,
        .sales-agent-table-box .dropdown-menu,
        .dataTables_wrapper .dropdown-menu {
            z-index: 999999 !important;
        }

        #sales_agents_table .dropdown-menu {
            min-width: 170px;
        }

        #sales_agents_table td:first-child {
            white-space: nowrap;
        }

        @media (max-width: 767px) {
            .sales-agent-table-responsive {
                overflow-x: auto !important;
                overflow-y: visible !important;
                -webkit-overflow-scrolling: touch;
            }

            #sales_agents_table td:first-child,
            #sales_agents_table th:first-child {
                min-width: 145px;
            }
        }
    </style>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            var currency_precision = parseInt("{{ $currency_precision ?? 2 }}", 10);

            function cleanDecimalInput($input, applyPrecision) {
                var value = $input.val();

                if (typeof value === 'string') {
                    value = value.replace(/%/g, '').replace(/,/g, '').trim();
                }

                if (value === '') {
                    $input.val('');
                    return;
                }

                if (isNaN(value)) {
                    $input.val(Number(0).toFixed(currency_precision));
                    return;
                }

                if (applyPrecision === true) {
                    $input.val(parseFloat(value).toFixed(currency_precision));
                    return;
                }

                var parts = value.split('.');
                if (parts.length > 1 && parts[1].length > currency_precision) {
                    parts[1] = parts[1].substring(0, currency_precision);
                    $input.val(parts.join('.'));
                } else {
                    $input.val(value);
                }
            }

            function toggleCommissionFields(prefix) {
                var entitled = $('#commission_entitled_' + prefix).val();
                if (entitled === 'yes') {
                    $('.commission-fields-' + prefix).show();
                    if (!$('#commission_type_' + prefix).val()) {
                        $('#commission_type_' + prefix).val('percentage').trigger('change.select2');
                    }
                } else {
                    $('.commission-fields-' + prefix).hide();
                    $('#commission_type_' + prefix).val('percentage').trigger('change.select2');
                    $('#commission_value_' + prefix).val('');
                }
            }

            $(document).on('change', '.commission-entitled', function() {
                toggleCommissionFields('add');
            });

            $(document).on('input keyup change', '.commission-value, .sales-agent-decimal', function() {
                cleanDecimalInput($(this), false);
            });

            $(document).on('blur', '.commission-value, .sales-agent-decimal', function() {
                cleanDecimalInput($(this), true);
            });

            toggleCommissionFields('add');

            var sales_agents_table = $('#sales_agents_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('salesagent.management.data') }}",
                    data: function(d) {
                        d.location_id = $('#filter_location').val();
                        d.employment_grade = $('#filter_employment_grade').val();
                        d.sales_agent = $('#sales_agent').val();

                        var date_range = $('#sales_agent_date_range').val();

                        if (date_range) {
                            var split = date_range.split(' ~ ');
                            d.start_date = split[0];
                            d.end_date = split[1];
                        } else {
                            d.start_date = '';
                            d.end_date = '';
                        }
                    }
                },
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'joined_date',
                        name: 'joined_date'
                    },
                    {
                        data: 'employment_grade',
                        name: 'employment_grade'
                    },
                    {
                        data: 'salary',
                        name: 'salary',
                        className: 'text-right'
                    },
                    {
                        data: 'commission_entitled',
                        name: 'commission_entitled'
                    },
                    {
                        data: 'commission_type',
                        name: 'commission_type'
                    },
                    {
                        data: 'commission',
                        name: 'commission',
                        className: 'text-right'
                    },
                    {
                        data: 'location_name',
                        name: 'location_name'
                    },
                    {
                        data: 'added_by',
                        name: 'added_by'
                    }
                ],
                drawCallback: function() {
                    $('#sales_agents_table .dropdown-toggle').dropdown();
                }
            });

            $('#sales_agent_date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    format: 'DD/MM/YYYY',
                    cancelLabel: 'Clear'
                }
            });

            $('#sales_agent_date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(
                    picker.startDate.format('DD/MM/YYYY') + ' ~ ' +
                    picker.endDate.format('DD/MM/YYYY')
                );

                sales_agents_table.ajax.reload();
            });

            $('#sales_agent_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                sales_agents_table.ajax.reload();
            });

            $('#sales_agent, #filter_location, #filter_employment_grade').on('change keyup', function() {
                sales_agents_table.ajax.reload();
            });

            $('.select2').select2({
                width: '100%',
                minimumResultsForSearch: 0
            });


            $(document).on('show.bs.dropdown', '#sales_agents_table .btn-group', function() {
                var $btnGroup = $(this);
                var $menu = $btnGroup.find('.dropdown-menu');
                var $button = $btnGroup.find('[data-toggle="dropdown"]');
                var offset = $button.offset();

                if (!$menu.data('sales-agent-attached')) {
                    $menu.data('sales-agent-parent', $btnGroup);
                    $menu.data('sales-agent-attached', true);
                    $('body').append($menu.detach());
                }

                $menu.css({
                    display: 'block',
                    position: 'absolute',
                    top: offset.top + $button.outerHeight(),
                    left: offset.left,
                    zIndex: 999999
                });
            });

            $(document).on('hide.bs.dropdown', '#sales_agents_table .btn-group', function() {
                var $btnGroup = $(this);
                var $menu = $('body > .dropdown-menu').filter(function() {
                    return $(this).data('sales-agent-parent') && $(this).data('sales-agent-parent')[0] === $btnGroup[0];
                });

                if ($menu.length) {
                    $menu.hide().detach().appendTo($btnGroup);
                }
            });

            $('#sales_agent_add_form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);

                form.find('.sales-agent-decimal, .commission-value').each(function() {
                    cleanDecimalInput($(this), true);
                });

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg);
                            form[0].reset();
                            $('#commission_entitled_add').val('yes').trigger('change.select2');
                            $('#commission_type_add').val('percentage').trigger('change.select2');
                            toggleCommissionFields('add');
                            sales_agents_table.ajax.reload();
                        } else {
                            toastr.error(response.msg);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error("{{ __('messages.something_went_wrong') }}");
                        }
                    }
                });
            });

            $(document).on('click', '.add-commission', function(e) {
                e.preventDefault();
                var agent_id = $(this).data('id');
                var agent_name = $(this).data('name');

                $('#commission_sales_agent_id').val(agent_id);
                $('#commission_agent_name').text(agent_name);
                $('#add_commission_modal').modal('show');
            });

            $('#add_commission_form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);

                form.find('.sales-agent-decimal').each(function() {
                    cleanDecimalInput($(this), true);
                });

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg);
                            form[0].reset();
                            $('#add_commission_modal').modal('hide');
                            sales_agents_table.ajax.reload();
                        } else {
                            toastr.error(response.msg);
                        }
                    },
                    error: function() {
                        toastr.error("{{ __('messages.something_went_wrong') }}");
                    }
                });
            });

            $(document).on('click', '.view-sales-agent', function(e) {
                e.preventDefault();

                $.ajax({
                    url: $(this).data('href'),
                    method: 'GET',
                    success: function(response) {
                        $('#sales_agent_modal_content').html(response);
                        $('#sales_agent_modal').modal('show');
                    }
                });
            });

            $(document).on('click', '.edit-sales-agent', function(e) {
                e.preventDefault();

                $.ajax({
                    url: $(this).data('href'),
                    method: 'GET',
                    success: function(response) {
                        $('#sales_agent_modal_content').html(response);
                        $('#sales_agent_modal').modal('show');
                    }
                });
            });

            $(document).on('shown.bs.modal', '#sales_agent_modal', function() {
                $('#sales_agent_modal .select2').select2({
                    width: '100%',
                    minimumResultsForSearch: 0,
                    dropdownParent: $('#sales_agent_modal')
                });

                if (typeof window.initSalesAgentEditForm === 'function') {
                    window.initSalesAgentEditForm();
                }
            });

            $(document).on('submit', '#sales_agent_edit_form', function(e) {
                e.preventDefault();
                var form = $(this);

                form.find('.sales-agent-decimal, .commission-value').each(function() {
                    cleanDecimalInput($(this), true);
                });

                $.ajax({
                    url: form.attr('action'),
                    method: 'PUT',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg);
                            $('#sales_agent_modal').modal('hide');
                            sales_agents_table.ajax.reload();
                        } else {
                            toastr.error(response.msg);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error("{{ __('messages.something_went_wrong') }}");
                        }
                    }
                });
            });
        });
    </script>
@endsection
