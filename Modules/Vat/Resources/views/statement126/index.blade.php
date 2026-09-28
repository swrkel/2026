@extends('layouts.app')
@section('title', __('vat::lang.126_statement'))

@section('content')
    <!-- Main content -->
    <section class="content">

        <div class="row">
            @include('vat::statement126.partials.nav')
            <div class="clearfix"></div>
            <hr>
            <div class="row">
                <div class="col-md-3">
                    @component('components.widget', ['class' => 'box'])
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('statement126_filter_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text('statement126_filter_date_range', null, [
                                    'placeholder' => __('lang_v1.select_a_date_range'),
                                    'class' => 'form-control',
                                    'readonly',
                                ]) !!}
                            </div>
                        </div>
                    @endcomponent
                </div>
                <div class="col-md-3">
                    {!! Form::label('contact_id', __('vat::lang.customer') . ':') !!}
                    {!! Form::select('contact_id', $contact_dropdown, null, [
                        'class' => 'form-control select2',
                        'id' => 'contact_id',
                    ]) !!}
                </div>
                <div class="col-md-3">
                    {!! Form::label('sub_contact_id', __('vat::lang.sub_customer') . ':') !!}
                    {!! Form::select('sub_contact_id', $contact_dropdown, null, [
                        'class' => 'form-control select2',
                        'id' => 'sub_contact_id',
                    ]) !!}
                </div>
                <div class="col-md-3">
                    {!! Form::label('customer_bill_no', __('vat::lang.bill_no') . ':') !!}
                    {!! Form::select('customer_bill_no', $bill_no_dropdown, null, [
                        'class' => 'form-control select2',
                        'id' => 'customer_bill_no',
                    ]) !!}
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('vat::lang.126_statement')])
                    @slot('tool')
                        <div class="box-tools">
                            <a type="button" class="btn btn-primary pull-right"
                                href="{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@create') }}">
                                <i class="fa fa-plus"></i> @lang('vat::lang.add')</a>
                        </div>
                    @endslot

                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped table-bordered" id="statement126_table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>@lang('vat::lang.date')</th>
                                        <th>@lang('vat::lang.bill_no')</th>
                                        <th>@lang('vat::lang.total')</th>
                                        <th>@lang('vat::lang.customer')</th>
                                        <th>@lang('vat::lang.sub_customer')</th>
                                        <th>@lang('vat::lang.credit_limit')</th>
                                        <th>@lang('vat::lang.outstanding')</th>
                                        <th>@lang('vat::lang.user')</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="modal fade issue_bill_customer_model" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>
    </section>
    <!-- /.content -->

     <div class="modal fade" id="statePrintFormatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title">@lang('messages.print')</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

               <div class="modal-body">
                    <input type="hidden" id="print_url_old">
                    <input type="hidden" id="print_url_2026">
                    <input type="hidden" id="print_url_126">

                    @php
                        $printFormats = [];
                        $printFormats['126_print'] = __('vat::lang.126_print');

                        if ($vat_print_2026_enabled) {
                            $printFormats['vat_print_2026'] = __('vat::lang.vat_print_2026');
                        }

                        $printFormats['old'] = __('vat::lang.old_vat_print');
                    @endphp

                    <div class="form-group">
                        {!! Form::label('print_format', __('vat::lang.print_format')) !!}
                        {!! Form::select(
                            'print_format',
                            $printFormats,
                            '126_print',
                            [
                                'class' => 'form-control',
                                'id' => 'print_format_modal_form'
                            ]
                        ) !!}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="confirmPrint">
                        <i class="fa fa-print"></i> @lang('messages.print')
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        @lang('messages.close')
                    </button>
                </div>

            </div>
        </div>
    </div>

@endsection
@section('javascript')
    <script>

        $(document).on('click', '.print_bill', function (e) {
            e.preventDefault();

            $('#print_url_old').val($(this).data('print-old'));
            $('#print_url_2026').val($(this).data('print-2026'));
            $('#print_url_126').val($(this).data('print-126'));

            $('#statePrintFormatModal').modal('show');
        });

        $('#confirmPrint').on('click', function () {
            let format = $('#print_format_modal_form').val();
            localStorage.setItem('vat_print_format', format);

            let url = '';
            if (format === 'old') {
                url = $('#print_url_old').val();
            } else if (format === 'vat_print_2026') {
                url = $('#print_url_2026').val();
            } else if (format === '126_print') {
                url = $('#print_url_126').val();
            }

            // Navigate to the selected print URL so the browser
            // shows the normal print preview page.
            if (url) {
                window.location.href = url;
            }

            $('#statePrintFormatModal').modal('hide');
        });


        if ($('#statement126_filter_date_range').length == 1) {
            $('#statement126_filter_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#statement126_filter_date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
                statement126_table.ajax.reload();
            });
            $('#custom_date_apply_button').on('click', function() {
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $(
                    '#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $(
                    '#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                    '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                    '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                    '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                    '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);

                    $('#statement126_filter_date_range').val(
                        formattedStartDate + ' ~ ' + formattedEndDate
                    );

                    $('#statement126_filter_date_range').data('daterangepicker').setStartDate(moment(startDate));
                    $('#statement126_filter_date_range').data('daterangepicker').setEndDate(moment(endDate));

                    $('.custom_date_typing_modal').modal('hide');
                    statement126_table.ajax.reload();
                } else {
                    toastr.error("Please select both start and end dates.");
                }
            });
            $('#statement126_filter_date_range').on('apply.daterangepicker', function(ev, picker) {
                if (picker.chosenLabel === 'Custom Date Range') {
                    $('.custom_date_typing_modal').modal('show');
                }
            });
            $('#statement126_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#product_sr_date_filter').val('');
            });
            $('#statement126_filter_date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
            $('#statement126_filter_date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('month'));
        }
        $('#contact_id, #sub_contact_id, #customer_bill_no').change(function() {
            statement126_table.ajax.reload();
        });
        // statement126_table
        if ($.fn.DataTable.isDataTable('#statement126_table')) {
            $('#statement126_table').DataTable().destroy();
        }
        statement126_table = $('#statement126_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@index') }}",
                cache: false,
                data: function(d) {
                    if ($('#statement126_filter_date_range').val()) {
                        var start = $('#statement126_filter_date_range').data('daterangepicker').startDate
                            .format('YYYY-MM-DD');
                        var end = $('#statement126_filter_date_range').data('daterangepicker').endDate.format(
                            'YYYY-MM-DD');
                        d.start_date = start;
                        d.end_date = end;
                    }
                    d.contact_id = $('#contact_id').val();
                    d.sub_contact_id = $('#sub_contact_id').val();
                    d.customer_bill_no = $('#customer_bill_no').val();
                },
            },

            columns: [{
                    data: 'date',
                    name: 'date',
                    orderable: false
                },
                {
                    data: 'customer_bill_no',
                    name: 'customer_bill_no'
                },
                {
                    data: 'total_amount',
                    name: 'total_amount'
                },
                {
                    data: 'customer_name',
                    name: 'contacts.name'
                },

                {
                    data: 'sub_customer_name',
                    name: 'subc.name'
                },

                {
                    data: 'credit_limit',
                    name: 'credit_limit'
                },
                {
                    data: 'outstanding_amount',
                    name: 'outstanding_amount'
                },

                {
                    data: 'username',
                    name: 'users.username'
                },
                {
                    data: 'action',
                    name: 'action'
                },
            ],
            @include('layouts.partials.datatable_export_button')
            "fnDrawCallback": function(oSettings) {}
        });
        $(document).on('click', 'a.delete-statement-126', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    let href = $(this).data('href');

                    $.ajax({
                        method: 'delete',
                        url: href,
                        dataType: 'json',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(result) {
                            if (result.success == 1 || result.success == true) {
                                toastr.success(result.msg);
                            } else {
                                toastr.error(result.msg);
                            }
                            statement126_table.ajax.reload();
                        },
                    });
                }
            });
        });

        @if (!empty(session('status')['print_url']))
            // After Save & Print, go directly to the print page
            // so the user sees the full print preview.
            let href = "{{ session('status')['print_url'] }}";
            if (href) {
                window.location.href = href;
            }
        @endif
        
        $(document).on('click', '#add_issue_bill_customer_btn', function() {
            $('.issue_bill_customer_model').modal({
                backdrop: 'static',
                keyboard: false
            })
        })
    </script>
@endsection
