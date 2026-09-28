@extends('layouts.app')
@section('title', __('lang_v1.' . $type . 's'))

@section('content')

    <!-- Content Header (Page header) -->

    <style>
        .popup {

            cursor: pointer
        }

        .popupshow {
            z-index: 99999;
            display: none;
        }

        .popupshow .overlay {
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, .66);
            position: absolute;
            top: 0;
            left: 0;
        }

        .popupshow .img-show {
            width: 900px;
            height: 600px;
            background: #FFF;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            overflow: hidden;
        }

        .img-show span {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 99;
            cursor: pointer;
        }

        .img-show img {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .reduced-width {
            width: 10%;
        }


        /* =========================================================
           CONTACT ACTION DROPDOWN FIX
           Keeps row Actions dropdown above the DataTable container.
        ========================================================= */
        .contact-action-dropdown-fix {
            position: absolute !important;
            z-index: 99999999 !important;
            display: block !important;
            min-width: 240px !important;
            max-height: calc(100vh - 40px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        .contact-action-dropdown-fix > li > a {
            white-space: nowrap !important;
        }

        #contact_table_wrapper,
        #bank_contact_table_wrapper,
        #contact_table,
        #bank_contact_table,
        #contact_table tbody,
        #bank_contact_table tbody,
        .table-responsive {
            overflow: visible !important;
        }


        /* S292: increase Add button size by about 75% on Customer/Supplier list pages. */
        .contact-add-large-btn {
            min-width: 122px !important;
            padding-left: 22px !important;
            padding-right: 22px !important;
            font-size: 14px !important;
            line-height: 1.6 !important;
        }

        /*End style*/
    </style>


    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">
                        @if(isset($is_bank_customer) && $is_bank_customer)
                            Customers - Bank
                        @else
                            Contacts
                        @endif
                    </h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li>
                            <a href="#">
                                @if(isset($is_bank_customer) && $is_bank_customer)
                                    Customers - Bank
                                @else
                                    Contacts
                                @endif
                            </a>
                        </li>
                        <li>
                            <span>
                                @if(isset($is_bank_customer) && $is_bank_customer)
                                    Manage Customers Bank
                                @else
                                    Manage contacts
                                @endif
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>



    <!-- Main content -->
    <section class="content main-content-inner">
        <div class="row">
            <div class="col-sm-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="form-group col-sm-4 form-inline">

                        <div class="input-group">
                            {!! Form::label('user_id', __('lang_v1.assigned_to'), ['class' => 'mr-2']) !!}: &nbsp;
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select('user_id', $user_groups, null, ['class' => 'form-control select2', 'id' => 'assigned_to']) !!}
                        </div>
                    </div>
                @endcomponent

            </div>
            <!--@if ($type == 'supplier')-->
            <!--   <div class="col-sm-12">-->
            <!--           <div class="box-tools pull-right">-->
            <!--                 <p class="text-muted">-->
            <!--           {{ __('lang_v1.supplier_product_mapping') }}-->
            <!--               <input type="hidden" id="default_contact_id" value="{{ $contact_id ?? '' }}" >-->
            <!--               <button type="button" class="btn btn-primary btn-modal"-->
            <!--               data-href="{{ action('SupplierMappingController@createMapping', ['type' => $type]) }}" data-container=".contact_modal">-->
            <!--           <i class="fa fa-plus"></i> @lang('messages.add')</button></p>-->
            <!--       </div> -->

            <!-- </div>-->

            <!-- @endif-->


        </div>




        @php
            if ($type == 'customer') {
                $colspan = 19;
            } else {
                $colspan = 17;
            }

        @endphp
        <input type="hidden" value="{{ $type }}" id="contact_type">
        @component('components.widget', [
            'class' => 'box-primary',
            'title' => __('contact.all_your_contact', ['contacts' => __('lang_v1.' . $type . 's')]),
        ])
            @php
                $can_add_contact = false;

                if (isset($is_bank_customer) && $is_bank_customer) {
                    $can_add_contact = auth()->user()->can('customer.create');
                } elseif ($type == 'customer') {
                    $can_add_contact = auth()->user()->can('customer.create');
                } elseif ($type == 'supplier') {
                    $can_add_contact = auth()->user()->can('supplier.create');
                } else {
                    $can_add_contact = auth()->user()->can('customer.create') || auth()->user()->can('supplier.create');
                }
            @endphp

            @slot('tool')
                @if ($can_add_contact)
                    <input type="hidden" id="default_contact_id" value="{{ $contact_id ?? '' }}">
                    <button type="button" class="btn btn-primary btn-modal pull-right contact-add-large-btn"
                        data-href="{{ isset($is_bank_customer) && $is_bank_customer ? '/bank-customers/create' : action('ContactController@create', ['type' => $type]) }}"
                        data-container=".contact_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </button>
                @endif
            @endslot
            @if (auth()->user()->can('supplier.view') || auth()->user()->can('customer.view'))
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%" id="{{ isset($is_bank_customer) && $is_bank_customer ? 'bank_contact_table' : 'contact_table' }}">
                        <thead>
                            <tr>
                                <td colspan="9">
                                    <div class="row">
                                        <div class="col-sm-2">
                                            @if (auth()->user()->can('customer.delete') || auth()->user()->can('supplier.delete'))
                                                {!! Form::open([
                                                    'url' => action('ContactController@massDestroy'),
                                                    'method' => 'post',
                                                    'id' => 'mass_delete_form',
                                                ]) !!}
                                                {!! Form::hidden('selected_rows', null, ['id' => 'selected_rows']) !!}
                                                {!! Form::submit(__('lang_v1.delete_selected'), ['class' => 'btn btn-xs btn-danger', 'id' => 'delete-selected']) !!}
                                                {!! Form::close() !!}
                                            @endif
                                        </div>
                                        <div class="col-sm-2">
                                            {!! Form::open([
                                                'url' => action('ContactController@exportBalance'),
                                                'method' => 'post',
                                                'id' => 'export_ob_form',
                                            ]) !!}
                                            {!! Form::hidden('selected_rows', null, ['id' => 'ob_selected_rows']) !!}
                                            {!! Form::submit(__('lang_v1.export'), ['class' => 'btn btn-xs btn-success', 'id' => 'export-selected']) !!}
                                            {!! Form::close() !!}
                                        </div>
                                    </div>

                                </td>
                                <td colspan="5">
                                    <table class="table">
                                        <tr>
                                            <th>Total Outstanding</th>
                                            <td>
                                                <span id="total_outstanding" class="display_currency"
                                                    style="margin-left: 0.5rem;"><i class="fa fa-refresh fa-spin"></i></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Total Overpayment</th>
                                            <td>
                                                <span id="total_overpayment" class="display_currency"
                                                    style="margin-left: 0.5rem;"><i class="fa fa-refresh fa-spin"></i></span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <th><input type="checkbox" id="select-all-row"></th>
                                <th class="notexport">@lang('messages.action')</th>
                                <th>@lang('lang_v1.contact_id')</th>
                                @if ($type == 'supplier')
                                    <th>@lang('business.business_name')</th>
                                    <th>@lang('contact.name')</th>
                                    <th>@lang('contact.mobile')</th>
                                    <th>@lang('lang_v1.supplier_group')</th>
                                    <th>Assign To</th>
                                    <th>@lang('contact.pay_term')</th>
                                    <th>@lang('contact.total_purchase_due')</th>
                                    <th>@lang('lang_v1.total_purchase_return_due')</th>
                                    <!--<th html="true">@lang('contact.opening_bal_due')</th>-->
                                    <th>@lang('account.opening_balance')</th>
                                    <th style="max-width: 16px;">@lang('business.email')</th>
                                    <th>@lang('contact.tax_no')</th>
                                    <th>@lang('lang_v1.added_on')</th>
                                @elseif($type == 'customer')
                                    <th>@lang('user.name')</th>
                                    <th>@lang('contact.mobile')</th>
                                    @if(isset($is_bank_customer) && $is_bank_customer)
                                        <th>@lang('contact.nic_number')</th>
                                        <th>@lang('contact.nic_image')</th>
                                        <th>@lang('contact.passport_number')</th>
                                        <th>@lang('contact.passport_image')</th>
                                    @else
                                        <th>@lang('lang_v1.customer_group')</th>
                                        <th>Assign To</th>
                                        <th>@lang('lang_v1.credit_limit')</th>
                                        <th style="color: #9D0606">@lang('contact.total_due')</th>
                                        <!-- <th width="150" style="min-width: 100px"> @lang('contact.total_sale_due')</th> -->
                                        <th> @lang('lang_v1.total_sell_return_due') </th>
                                        <th>@lang('contact.pay_term')</th>
                                    @endif

                                    <!--
                                <th>@lang('contact.tax_no')</th>
                                <th>@lang('business.email')</th>
                                <th>@lang('business.address')</th>
                                -->
                                    <th>
                                        @lang('Photo')
                                    </th>
                                    <th>
                                        @lang('lang_v1.signature')
                                    </th>
                                    <th>@lang('lang_v1.added_on')</th>
                                    @if ($reward_enabled)
                                        <th id="rp_col">{{ session('business.rp_name') }}</th>
                                    @endif
                                @endif
                                <th
                                    class="contact_custom_field1 @if ($is_property && !array_key_exists('property_customer_custom_field_1', $contact_fields)) hide @endif  @if ($type == 'customer' && !array_key_exists('customer_custom_field_1', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_custom_field_1', $contact_fields)) hide @endif">
                                    @lang('lang_v1.contact_custom_field1')
                                </th>

                                <th
                                    class="contact_custom_field2 @if ($is_property && !array_key_exists('property_customer_custom_field_2', $contact_fields)) hide @endif  @if ($type == 'customer' && !array_key_exists('customer_custom_field_2', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_custom_field_2', $contact_fields)) hide @endif">
                                    @lang('lang_v1.contact_custom_field2')
                                </th>

                                <th
                                    class="contact_custom_field3 @if ($is_property && !array_key_exists('property_customer_custom_field_3', $contact_fields)) hide @endif  @if ($type == 'customer' && !array_key_exists('customer_custom_field_3', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_custom_field_3', $contact_fields)) hide @endif">
                                    @lang('lang_v1.contact_custom_field3')
                                </th>

                                <th
                                    class="contact_custom_field4 @if ($is_property && !array_key_exists('property_customer_custom_field_4', $contact_fields)) hide @endif  @if ($type == 'customer' && !array_key_exists('customer_custom_field_4', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_custom_field_4', $contact_fields)) hide @endif">
                                    @lang('lang_v1.contact_custom_field4')
                                </th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 text-center footer-total">
                                <td @if ($type == 'supplier') colspan="8" @elseif($type == 'customer') @if ($reward_enabled)
                        colspan="7" @else colspan="7" @endif
                                    @endif>
                                    <strong>
                                        @lang('sale.total'):
                                    </strong>
                                </td>

                                @if ($type == 'supplier')
                                    <td><span class="display_currency" id="footer_pay_term" data-currency_symbol="true"></span>
                                    </td>
                                    <td><span class="display_currency" id="footer_tot_due" data-currency_symbol="true"></span>
                                    </td>
                                    <td><span class="display_currency" id="footer_contact_return_due"
                                            data-currency_symbol="true"></span></td>
                                    <td><span class="display_currency" id="footer_contact_opening_balance"
                                            data-currency_symbol="true"></span></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                @endif
                                @if ($type == 'customer')
                                    @if(isset($is_bank_customer) && $is_bank_customer)
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    @else
                                        <td><span class="display_currency" id="footer_tot_credit_limit"
                                                data-currency_symbol="true"></span></td>
                                        <td><span class="display_currency" id="footer_tot_due" data-currency_symbol="true"></span>
                                        </td>
                                        <td><span class="display_currency" id="footer_contact_return_due"
                                                data-currency_symbol="true"></span></td>
                                        <td><span class="display_currency" id="footer_pay_term" data-currency_symbol="true"></span>
                                        </td>
                                        <td></td>
                                    @endif
                                @endif

                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        @endcomponent

        <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>
        <div class="modal fade pay_contact_due_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

    </section>

    <div class="popupshow">
        <div class="overlay"></div>
        <div class="img-show">
            <span>X</span>
            <img src="">
        </div>
    </div>

    <!-- /.content -->

@endsection

@section('javascript')
<style>
.contact-add-large-btn{font-size:14px;padding:10px 22px;min-width:96px;}
</style>

    @if (session('status'))
        @if (session('status')['success'])
            <script>
                toastr.success('{{ session('status')['msg'] }}');
            </script>
        @else
            <script>
                toastr.error('{{ session('status')['msg'] }}');
            </script>
        @endif
    @endif


    <script>
        // IS1464-001: Supplier/Customer DataTables warnings must not show user-facing error toast.
        // Real errors remain in the browser console for debugging without disturbing users.
        if ($.fn.dataTable && $.fn.dataTable.ext) {
            $.fn.dataTable.ext.errMode = function(settings, helpPage, message) {
                console.warn('Contact DataTable warning:', message);
            };
        }

        $('#contact_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#contact_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                    moment_date_format));
                contact_table.ajax.reload();
            }
        );
        $('#contact_list_filter_date_range').on('apply.daterangepicker', function(ev, picker) {
            if (picker.chosenLabel === 'Custom Date Range') {
                $('#target_custom_date_input').val('contact_list_filter_date_range');
                $('.custom_date_typing_modal').modal('show');
            }
        });

        $('#custom_date_apply_button').on('click', function() {
            if ($('#target_custom_date_input').val() == "contact_list_filter_date_range") {
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

                    $('#contact_list_filter_date_range').val(
                        formattedStartDate + ' ~ ' + formattedEndDate
                    );

                    $('#contact_list_filter_date_range').data('daterangepicker').setStartDate(moment(startDate));
                    $('#contact_list_filter_date_range').data('daterangepicker').setEndDate(moment(endDate));

                    $('.custom_date_typing_modal').modal('hide');
                    contact_table.ajax.reload();
                } else {
                    alert("Please select both start and end dates.");
                }
            }
        });

        $('#contact_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#contact_list_filter_date_range').val('');
            contact_table.ajax.reload();
        });
        $('.contact_modal').on('shown.bs.modal', function() {
            $('.contact_modal')
                .find('.select2')
                .each(function() {
                    var $p = $(this).parent();
                    $(this).select2({
                        dropdownParent: $p
                    });
                });

        });
        $(document).on('click', '#delete-selected', function(e) {
            e.preventDefault();
            var selected_rows = getSelectedRows();

            if (selected_rows.length > 0) {
                $('input#selected_rows').val(selected_rows);
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        $('form#mass_delete_form').submit();
                    }
                });
            } else {
                $('input#selected_rows').val('');
                swal('@lang('lang_v1.no_row_selected')');
            }
        });


        $(document).on('click', '#export-selected', function(e) {
            e.preventDefault();
            var selected_rows = getSelectedRows();

            if (selected_rows.length > 0) {
                $('input#ob_selected_rows').val(selected_rows);
                $('form#export_ob_form').submit();
            } else {
                $('input#ob_selected_rows').val('');
                swal('@lang('lang_v1.no_row_selected')');
            }
        });


        function getSelectedRows() {
            var selected_rows = [];
            var i = 0;
            $('.row-select:checked').each(function() {
                selected_rows[i++] = $(this).val();
            });

            return selected_rows;
        }
        $(document).ready(function() {
            var url = '/contacts/get_outstanding?type=' + "{{ $type }}";
            @if(isset($is_bank_customer) && $is_bank_customer)
                url += '&register_module=bank';
            @endif
            $.ajax({
                method: 'get',
                url: url,
                success: function(result) {
                    if (result && Object.keys(result).length > 0) {
                        $('#total_outstanding').html(result.total_outstanding);
                        $('#total_overpayment').html(result.total_overpayment);
                        __currency_convert_recursively($('#total_outstanding').parent());
                        __currency_convert_recursively($('#total_overpayment').parent());
                    }
                },
            });
        });
        $(document).on('change', '#assigned_to', function() {
            var table_id = $('#bank_contact_table').length ? '#bank_contact_table' : '#contact_table';
            $(table_id).DataTable().ajax.reload();
        });

        $(document).on('hidden.bs.modal', 'div.contact_modal', function (e) {
            if ($('#bank_contact_table').length && $.fn.DataTable.isDataTable('#bank_contact_table')) {
                $('#bank_contact_table').DataTable().ajax.reload();
            }
        });

        $(document).ready(function() {


            $('body').on('click', '.popup', function() {
                var $src = $(this).attr("src");
                $(".popupshow").fadeIn();
                $(".img-show img").attr("src", $src);
            });


            $('body').on('click', '.overlay', function() {

                $(".popupshow").fadeOut();
            });
            $('body').on('click', 'span', function() {
                $(".popupshow").fadeOut();
            });

            @if(isset($is_bank_customer) && $is_bank_customer)
                if ($.fn.DataTable.isDataTable('#bank_contact_table')) {
                    $('#bank_contact_table').DataTable().destroy();
                }

                // Re-initialize for bank customers
                var contact_table = $('#bank_contact_table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: true,
                    lengthMenu: [[50, 100, 500], ['50', '100', '500']],
                    pageLength: 50,
                    ajax: {
                        url: '/bank-customers',
                        data: function (d) {
                            d.type = 'customer';
                        }
                    },
                    aaSorting: [[1, 'desc']],
                    columns: [
                        { data: 'mass_delete', searchable: false, orderable: false },
                        { data: 'action', searchable: false, orderable: false },
                        { data: 'contact_id', name: 'contact_id' },
                        { data: 'name', name: 'name' },
                        { data: 'mobile', name: 'mobile' },
                        { data: 'nic_number', name: 'nic_number' },
                        { data: 'nic_image', name: 'nic_image', searchable: false, orderable: false },
                        { data: 'passport_number', name: 'passport_number' },
                        { data: 'passport_image', name: 'passport_image', searchable: false, orderable: false },
                        { data: 'image', name: 'image', searchable: false, orderable: false },
                        { data: 'signature', name: 'signature', searchable: false, orderable: false },
                        { data: 'created_at', name: 'created_at' }
                    ],
                    fnDrawCallback: function (oSettings) {
                        __currency_convert_recursively($('#bank_contact_table'));
                    }
                });
            @endif
        });


        /* =========================================================
           CONTACT ACTION DROPDOWN FIX
           Detaches row dropdown to body while open so it is not clipped.
        ========================================================= */
        $(document).on('shown.bs.dropdown', '#contact_table .btn-group, #contact_table .dropdown, #bank_contact_table .btn-group, #bank_contact_table .dropdown', function () {
            var $dropdown = $(this);
            var $menu = $dropdown.find('.dropdown-menu');

            if (!$menu.length) {
                return;
            }

            if ($menu.parent().is('body')) {
                return;
            }

            var offset = $dropdown.offset();
            var windowTop = $(window).scrollTop();
            var windowHeight = $(window).height();
            var topPosition = offset.top + $dropdown.outerHeight();
            var leftPosition = offset.left;

            $dropdown.data('detached-menu', $menu);

            $menu
                .addClass('contact-action-dropdown-fix')
                .appendTo('body')
                .css({
                    top: topPosition,
                    left: leftPosition
                });

            var menuHeight = $menu.outerHeight();
            var menuBottom = topPosition + menuHeight;
            var visibleBottom = windowTop + windowHeight - 20;

            if (menuBottom > visibleBottom) {
                topPosition = Math.max(windowTop + 20, visibleBottom - menuHeight);

                $menu.css({
                    top: topPosition
                });
            }
        });

        $(document).on('hide.bs.dropdown', '#contact_table .btn-group, #contact_table .dropdown, #bank_contact_table .btn-group, #bank_contact_table .dropdown', function () {
            var $dropdown = $(this);
            var $menu = $dropdown.data('detached-menu');

            if ($menu && $menu.length) {
                $menu
                    .removeClass('contact-action-dropdown-fix')
                    .removeAttr('style')
                    .appendTo($dropdown);
            }
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.dropdown-menu, .dropdown-toggle, .btn-group, .dropdown').length) {
                $('body > .contact-action-dropdown-fix').each(function () {
                    $(this).removeClass('contact-action-dropdown-fix').hide();
                });
            }
        });
    </script>
@endsection
