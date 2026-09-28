@extends('distribution::layouts.app')

@section('title', 'List Products Loading')

@section('content')
    <section class="content">

        @component('distribution::components.filters', ['title' => __('Product Loading List')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, [
                        'class' => 'form-control',
                        'readonly',
                        'placeholder' => __('lang_v1.select_a_date_range'),
                    ]) !!}
                    <input type="hidden" id="start_date" value="">
                    <input type="hidden" id="end_date" value="">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sales_rep_id', __('Sales Rep') . ':') !!}
                    {!! Form::select('sales_rep_id', $salesReps, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('vehicle_id', 'Vehicle:') !!}
                    {!! Form::select('vehicle_id', $vehicles, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('product_category_id', __('Product Category') . ':') !!}
                    {!! Form::select('product_category_id', $categories, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                    ]) !!}
                </div>
            </div>
        @endcomponent

        @component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'Product Loading List'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="distribution_loading_table" style="margin-top:10px;">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>
                                @if (!empty($auto_date_time))
                                    Date & Time
                                @else
                                    Date
                                @endif
                            </th>
                            <th>Loading No</th>
                            <th>Sales Rep</th>
                            <th>Vehicle</th>
                            <th>Product Category</th>
                            <th>Total Sale Price</th>
                            <th>User Added</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent
        <div class="modal fade view_modal" tabindex="-1" role="dialog"></div>
        <div class="modal fade edit_modal" tabindex="-1" role="dialog"></div>

    </section>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            // Initialize date range picker
            if ($('#date_range').length == 1) {
                $('#date_range').daterangepicker(dateRangeSettings, function(start, end) {
                    $('#date_range').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    $('#start_date').val(start.format('YYYY-MM-DD'));
                    $('#end_date').val(end.format('YYYY-MM-DD'));
                    table.ajax.reload();
                });

                $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $('#date_range').val('');
                    $('#start_date').val('');
                    $('#end_date').val('');
                    table.ajax.reload();
                });

                $('#date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
                $('#date_range').data('daterangepicker').setEndDate(moment().endOf('month'));
                $('#start_date').val(moment().startOf('month').format('YYYY-MM-DD'));
                $('#end_date').val(moment().endOf('month').format('YYYY-MM-DD'));
            }
            let table = $('#distribution_loading_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('distribution.loadings.index') }}",
                    data: function(d) {
                        d.sales_rep_id = $('#sales_rep_id').val();
                        d.vehicle_id = $('#vehicle_id').val();
                        d.product_category_id = $('#product_category_id').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                columns: [{
                        data: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'date_time',
                        name: 'date_time',
                        render: function(data) {
                            if (!data) return '';
                            return data.toString().split(' ')[0];
                        }
                    },
                    {
                        data: 'loading_no',
                        name: 'loading_no'
                    },
                    {
                        data: 'sales_rep',
                        name: 'sales_rep_id'
                    },
                    {
                        data: 'vehicle',
                        name: 'vehicle_id'
                    },
                    {
                        data: 'category',
                        name: 'product_category_id'
                    },
                    {
                        data: 'total_sale_price',
                        name: 'total_sale_price'
                    },
                    {
                        data: 'creator',
                        name: 'creator.first_name'
                    },
                ]
            });

            // Redraw table on filter change
            $('.select2').on('change', function() {
                table.draw();
            });

            $(document).on('click', '.print_btn', function(e) {
                e.preventDefault();
                const url = $(this).data('href');
                if (url) {
                    window.open(url, '_blank');
                }
            });
        });

        // ============================================
        // EDIT PRODUCT LOADING MODAL FUNCTIONALITY
        // ============================================

        function updateTotalSaleAmount() {
            var grandTotal = 0;
            $('td.line_total').each(function() {
                var amt = parseFloat($(this).text().replace(/,/g, '')) || 0;
                grandTotal += amt;
            });
            var formattedGrandTotal = grandTotal.toFixed(4).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            $('.loading_total_sale').text(formattedGrandTotal);
        }

        // Handle row removal in edit modal
        $(document).on('click', '#edit_loading_form .remove_row', function() {
            var tbody = $(this).closest('tbody');
            $(this).closest('tr').remove();

            // Re-index remaining rows to keep numbers sequential
            tbody.find('tr').each(function(index) {
                $(this).find('td:first').text(index + 1);
            });

            updateTotalSaleAmount();
        });

        // Calculate line total on input change
        $(document).on('change keyup input', '.edit_issued_qty, .edit_sale_price', function() {
            var tr = $(this).closest('tr');
            var qty = parseFloat(tr.find('.edit_issued_qty').val()) || 0;
            var price = parseFloat(tr.find('.edit_sale_price').val()) || 0;
            var total = qty * price;
            
            // Format number to 4 decimal places with commas
            var formatted = total.toFixed(4).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            tr.find('.line_total').text(formatted);

            updateTotalSaleAmount();
        });

        // AJAX submit for edit loading form
        $(document).on('submit', '#edit_loading_form', function(e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();

            $.ajax({
                method: 'POST',
                url: form.attr('action'),
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        $('div.edit_modal').modal('hide');
                        toastr.success(result.msg);
                        // Reload data table
                        if ($.fn.DataTable.isDataTable('#distribution_loading_table')) {
                            $('#distribution_loading_table').DataTable().ajax.reload();
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(response) {
                    if (response.responseJSON && response.responseJSON.errors) {
                        $.each(response.responseJSON.errors, function(key, value) {
                            toastr.error(value[0]);
                        });
                    } else {
                        toastr.error('Something went wrong');
                    }
                }
            });
        });
    </script>
@endsection
