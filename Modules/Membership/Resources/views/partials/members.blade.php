@extends('layouts.app')
@section('title', __('membership::lang.members'))

@section('content')
    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('membership::lang.members')</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('membership::lang.members')</a></li>
                        <li><span>@lang('membership::lang.manage_members')</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('next_renewal_date', __('membership::lang.next_renewal') . ':') !!}
                                {!! Form::text('next_renewal_date', null, ['placeholder' => __('lang_v1.select_date_range'), 'class' => 'form-control', 'id' => 'next_renewal_date', 'readonly']) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('filter_membership_type_id', __('membership::lang.membership_type') . ':') !!}
                                {!! Form::select('filter_membership_type_id', $membershipTypes, null, ['class' => 'form-control', 'id' => 'filter_membership_type_id', 'placeholder' => __('lang_v1.all')]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('filter_membership_status_id', __('membership::lang.membership_status') . ':') !!}
                                {!! Form::select('filter_membership_status_id', $membershipStatuses, null, ['class' => 'form-control', 'id' => 'filter_membership_status_id', 'placeholder' => __('lang_v1.all')]) !!}
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="box">
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="member_table" style="width: 100%">
                        <thead>
                        <tr>
                            <th>{{ __('membership::lang.region') }}</th>
                            <th>{{ __('membership::lang.member_number') }}</th>
                            <th>{{ __('membership::lang.member_name') }}</th>
                            <th>{{ __('membership::lang.member_name_other') }}</th>
                            <th>{{ __('membership::lang.business_type') }}</th>
                            <th>{{ __('membership::lang.default_mobile_number') }}</th>
                            <th>{{ __('membership::lang.date_joined') }}</th>
                            <th>{{ __('membership::lang.date_of_birth') }}</th>
                            <th>{{ __('membership::lang.point_balance') }}</th>
                            <th>{{ __('membership::lang.date_time') }}</th>
                            <th>{{ __('messages.action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="9" class="text-center text-muted" style="padding: 2rem;">{{ __('messages.loading') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade member_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    </section>
@endsection

@section('javascript')
   <script>
$(document).ready(function () {

    var getMembersUrl = @json(action('\Modules\Membership\Http\Controllers\MembershipController@getMembers'));
    var errorMsg = @json(__('messages.something_went_wrong'));
    var csrfToken = @json(csrf_token());

    // DataTable first so the first AJAX request starts immediately
    window.member_table = $('#member_table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        pageLength: 25,
        ajax: {
            url: getMembersUrl,
            data: function(d) {
                d.next_renewal_date = $('#next_renewal_date').val();
                d.filter_membership_type_id = $('#filter_membership_type_id').val();
                d.filter_membership_status_id = $('#filter_membership_status_id').val();
            }
        },
        columns: [
            { data: 'region', name: 'region' },
            { data: 'member_number', name: 'member_number' },
            { data: 'member_name', name: 'member_name' },
            { data: 'member_name_other', name: 'member_name_other' },
            { data: 'business_type_name', name: 'business_type_name', orderable: false, searchable: false },
            { data: 'default_mobile_number', name: 'default_mobile_number' },
            { data: 'date_joined', name: 'date_joined' },
            { data: 'date_of_birth', name: 'date_of_birth' },
            { data: 'point_balance_formatted', name: 'point_balance', orderable: true, searchable: false },
            { data: 'date_time', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[9, 'desc']]
    });

    $('#next_renewal_date').on('change', function() {
        member_table.ajax.reload(null, false);
    });

    $('#filter_membership_type_id, #filter_membership_status_id').select2({
        allowClear: true,
        placeholder: @json(__('lang_v1.all')),
        minimumResultsForSearch: 0,
    }).on('change', function () {
        if (window.member_table) {
            member_table.ajax.reload(null, false);
        }
    });

    // Defer filter inits so DataTable AJAX is not delayed
    setTimeout(function () {
        var memberDateFormat = typeof moment_date_format !== 'undefined' ? moment_date_format : 'D MMM YYYY';
        var membershipRanges = $.extend({}, ranges || {});
        membershipRanges['Custom Date Range'] = [moment(), moment()];

        $('#next_renewal_date').daterangepicker(
            $.extend({}, dateRangeSettings, {
                autoUpdateInput: false,
                ranges: membershipRanges,
                alwaysShowCalendars: true,
                linkedCalendars: false,
                opens: 'center',
                drops: 'down'
            }),
            function (start, end) {
                $('#next_renewal_date').val(start.format(memberDateFormat) + ' ~ ' + end.format(memberDateFormat));
                if (window.member_table) {
                    member_table.ajax.reload(null, false);
                }
            }
        );

        $('#next_renewal_date').val('');

        // Handle Custom Date Range option
        $('#next_renewal_date').on('apply.daterangepicker', function(ev, picker) {
            if (picker.chosenLabel === 'Custom Date Range') {
                $('#target_custom_date_input').val('next_renewal_date');
                $('.custom_date_typing_modal').modal('show');
            }
        });

        // Handle custom date modal apply button
        $(document).on('click', '#custom_date_apply_button', function() {
            if($('#target_custom_date_input').val() == "next_renewal_date"){
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(memberDateFormat);
                    let formattedEndDate = moment(endDate).format(memberDateFormat);

                    $('#next_renewal_date').val(formattedStartDate + ' ~ ' + formattedEndDate);
                    $('#next_renewal_date').data('daterangepicker').setStartDate(moment(startDate));
                    $('#next_renewal_date').data('daterangepicker').setEndDate(moment(endDate));

                    $('.custom_date_typing_modal').modal('hide');

                    if (window.member_table) {
                        member_table.ajax.reload(null, false);
                    }
                } else {
                    alert("Please select both start and end dates.");
                }
            }
        });

        // Handle cancel/clear
        $('#next_renewal_date').on('cancel.daterangepicker', function () {
            $(this).val('');
            if (window.member_table) {
                member_table.ajax.reload(null, false);
            }
        });
    }, 0);

    // Open modal (view / edit)
    $(document).on('click', '.btn-modal', function (e) {
        e.preventDefault();

        var container = $(this).data('container');
        var url = $(this).data('href');

        $.ajax({
            url: url,
            dataType: 'html',
            success: function (result) {
                $(container).html(result).modal('show');

                $('#membership_business_type_id').select2({
                    dropdownParent: $(container)
                });
            },
            error: function () {
                toastr.error(errorMsg);
            }
        });
    });

    $(document).on('click', '.renew_member_btn', function (e) {
        e.preventDefault();

        var container = '.member_modal';
        var url = $(this).data('href');

        $.ajax({
            url: url,
            dataType: 'html',
            success: function (result) {
                $(container).html(result).modal('show');
            },
            error: function () {
                toastr.error(errorMsg);
            }
        });
    });

    // Submit form inside modal
    $(document).on('submit', '#member_form', function (e) {
        e.preventDefault();

        var form = $(this);

        $.ajax({
            method: form.attr('method') || 'POST',
            url: form.attr('action'),
            dataType: 'json',
            data: form.serialize(),
            success: function (result) {
                if (result.success) {
                    toastr.success(result.msg);
                    $('.member_modal').modal('hide');
                    member_table.ajax.reload(null, false);
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function () {
                toastr.error(errorMsg);
            }
        });
    });

    $(document).on('submit', '#member_renewal_form', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $('#member_renewal_submit_btn');
        var originalText = $submitBtn.html();

        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            method: 'POST',
            url: $form.attr('action'),
            dataType: 'json',
            data: $form.serialize(),
            success: function (result) {
                $submitBtn.prop('disabled', false).html(originalText);

                if (result.success) {
                    toastr.success(result.msg);
                    $('.member_modal').modal('hide');
                    member_table.ajax.reload(null, false);
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function (xhr) {
                $submitBtn.prop('disabled', false).html(originalText);

                var message = errorMsg;
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    message = xhr.responseJSON.msg;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                toastr.error(message);
            }
        });
    });

    // Delete member
    $(document).on('click', '.member_delete', function (e) {
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
                    success: function (result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            member_table.ajax.reload(null, false);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function () {
                        toastr.error(errorMsg);
                    }
                });
            }
        });
    });

    // Print card
    $(document).on('click', '.print_card_btn', function (e) {
        e.preventDefault();
        window.open($(this).data('href'), '_blank');
    });

});
</script>

@endsection

