@extends('layouts.app')
@section('title', __('membership::lang.point_activities'))

@section('content')
    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('membership::lang.point_activities')</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('membership::lang.point_activities')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('form_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('form_date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month'), ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'member_date_range', 'readonly']) !!}
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="box">
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="list_points_table" style="width: 100%">
                        <thead>
                        <tr>
                            <th>{{ __('messages.action') }}</th>
                            <th>{{ __('membership::lang.date') }}</th>
                            <th>{{ __('membership::lang.add_point_form_no') }}</th>
                            <th>{{ __('membership::lang.member_name') }}</th>
                            <th>{{ __('membership::lang.member_code') }}</th>
                            <th>{{ __('membership::lang.business_type') }}</th>
                            <th>{{ __('membership::lang.business_name') }}</th>
                            <th>{{ __('membership::lang.bill_number') }}</th>
                            <th>{{ __('membership::lang.amount') }}</th>
                            <th>{{ __('membership::lang.current_points') }}</th>
                            <th>{{ __('membership::lang.earned_points') }}</th>
                            <th>{{ __('membership::lang.redeemed_points') }}</th>
                            <th>{{ __('membership::lang.point_balance') }}</th>
                            <th>{{ __('membership::lang.payment_details') }}</th>
                            <th>{{ __('membership::lang.added_by') }}</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade member_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade point_activity_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    </section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#member_date_range').daterangepicker(
                dateRangeSettings,
                function (start, end, label) {
                    if (label === 'Custom Date Range') {
                        $('.custom_date_typing_modal').modal('show');
                    }else{
                        $('#member_date_range').val(
                            start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                        );
                    }
                }
            );

            var getMembersUrl = @json(action('\Modules\Membership\Http\Controllers\MembershipPointController@getListPoints'));
            var errorMsg = @json(__('messages.something_went_wrong'));
            var csrfToken = @json(csrf_token());

            // Khởi tạo DataTable
            window.list_points_table = $('#list_points_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: getMembersUrl,
                columnDefs: [
                    {
                        targets: 5,
                        orderable: false,
                        searchable: false
                    }
                ],
                columns: [
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'date', name: 'membership_points.date' },
                    { data: 'add_point_form_no', name: 'membership_points.form_number' },
                    { data: 'member_name', name: 'member_name', orderable: false, searchable: true },
                    { data: 'member_code', name: 'member_code', orderable: false, searchable: true },
                    { data: 'business_type', name: 'membership_business_types.business_type' },
                    { data: 'business_name', name: 'membership_points.business_name' },
                    { data: 'bill_number', name: 'membership_points.bill_number' },
                    { data: 'amount', name: 'membership_points.amount' },
                    { data: 'current_points', name: 'current_points' },
                    { data: 'earned_points', name: 'membership_points.earned_points' },
                    { data: 'redeemed_points', name: 'membership_points.redeemed_points' },
                    { data: 'point_balance', name: 'membership_points.point_balance' },
                    { data: 'payment_details', name: 'membership_points.payment_details' },
                    { data: 'added_by', name: 'users.username' }
                ]
            });

            $(document).on('click', '.btn-modal', function(e) {
                e.preventDefault();
                var container = $(this).data('container');
                var url = $(this).data('href');

                $.ajax({
                    url: url,
                    dataType: 'html',
                    success: function(result) {
                        $(container).html(result).modal('show');

                        // Reinitialize select2 trong modal
                        $('#membership_business_type_id').select2({
                            dropdownParent: $(container)
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading modal:', error);
                        toastr.error(errorMsg);
                    }
                });
            });

            // Handle form submit trong modal
            $(document).on('submit', '#member_form', function(e) {
                e.preventDefault();
                var form = $(this);
                var url = form.attr('action');

                $.ajax({
                    method: form.attr('method') || 'POST',
                    url: url,
                    dataType: 'json',
                    data: form.serialize(),
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            $('.member_modal').modal('hide');
                            // Reload DataTable
                            member_table.ajax.reload(null, false);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(errorMsg);
                    }
                });
            });

            // Handle delete point activity
            $(document).on('click', '.delete_point_activity_btn', function(e) {
                e.preventDefault();
                var href = $(this).data('href');
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((confirmed) => {
                    if (confirmed) {
                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            data: { _token: csrfToken },
                            success: function(result) {
                                if (result.success) {
                                    toastr.success(result.msg);
                                    list_points_table.ajax.reload(null, false);
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                            error: function(xhr) {
                                toastr.error(errorMsg);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection

