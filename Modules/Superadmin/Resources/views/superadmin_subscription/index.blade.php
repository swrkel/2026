@extends('layouts.app')
@section('title', 'Superadmin Subscription')

@section('content')

<style>
    /* Keep the Package Subscription action column visible and usable. */
    #superadmin_subscription_table th.sa-action-column,
    #superadmin_subscription_table td.sa-action-column {
        display: table-cell !important;
        visibility: visible !important;
        opacity: 1 !important;
        min-width: 170px !important;
        width: 170px !important;
        white-space: nowrap !important;
        text-align: center !important;
        vertical-align: middle !important;
    }
    #superadmin_subscription_table .sa-subscription-actions {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 6px;
        white-space: nowrap;
    }
    #superadmin_subscription_table .sa-subscription-actions .btn {
        display: inline-flex !important;
        align-items: center;
        gap: 4px;
        opacity: 1 !important;
        visibility: visible !important;
        color: #fff !important;
        position: relative !important;
        z-index: 2;
    }
    #superadmin_subscription_table_wrapper .dataTables_scrollBody {
        overflow-x: auto !important;
    }
</style>


<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'superadmin::lang.subscription' )
        <small>@lang( 'superadmin::lang.view_subscription' )</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">

    @include('superadmin::layouts.partials.currency')

    <div class="row">
        <div class="col-md-12 dip_tab">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs">
                    <li class="active" style="margin-left: 20px;">
                        <a style="font-size:13px;" href="#superadmin_subscription" class="" data-toggle="tab">
                            <i class="fa fa-superpowers"></i>
                            <strong>@lang('superadmin::lang.superadmin_subscription')</strong>
                        </a>
                    </li>
                    <li class="" style="margin-left: 20px;">
                        <a style="font-size:13px;" href="#family_subscription" class="" data-toggle="tab">
                            <i class="fa fa-users"></i>
                            <strong>@lang('superadmin::lang.family_subscription')</strong>
                        </a>
                    </li>
                    
                    <li class="" style="margin-left: 20px;">
                        <a style="font-size:13px;" href="#module_subscription" class="" data-toggle="tab">
                            <i class="fa fa-users"></i>
                            <strong>Module Subscription</strong>
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </div>
    <div class="tab-content">
        <div class="tab-pane active" id="superadmin_subscription">
            @include('superadmin::superadmin_subscription.superadmin_subscription')
        </div>
        <div class="tab-pane" id="family_subscription">
            @include('superadmin::superadmin_subscription.family_subscription')
        </div>
        <div class="tab-pane" id="module_subscription">
            @include('superadmin::superadmin_subscription.module_subscription')
        </div>
    </div>

    <!--<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>-->

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script>
    $(document).ready(function(){
        
        
        if ($('#expiry_date_range').length == 1) {
            $('#expiry_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#expiry_date_range').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
               
            });
            $('#expiry_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#expiry_date_range').val('');
            });
            $('#expiry_date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('year'));
            $('#expiry_date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('year'));
        }
        
        var modules_sub_cols = [
            { data: 'name', name: 'name' },
            { data: 'business', name: 'business' },
            { data: 'status', name: 'status' },
            { data: 'activated_on', name: 'activated_on' },
            { data: 'expired_on', name: 'expired_on' },
            { data: 'price', name: 'price' },
        ];
        
        $(
            '#expiry_date_range,' +
            '#status,' +
            '#expired_on,' +
            '#modules,' +
            '#business'
        ).change(function() {
            if (modules_sub_table) { modules_sub_table.ajax.reload(null, false); }
        });
        
        var modules_sub_table = null;
        var family_subscription_table = null;

        function initialiseModuleSubscriptionTable() {
            if (modules_sub_table || !$('#module_subscription_table').length) {
                return;
            }

            modules_sub_table = $('#module_subscription_table').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                searchDelay: 400,
                scrollY: '75vh',
                scrollX: true,
                scrollCollapse: true,
                ajax: {
                    url: "{{ url('module-subscription') }}",
                    data: function(d) {
                        var dateRange = $('#expiry_date_range').val() || '';
                        var parts = dateRange.split(' - ');
                        d.start_date = parts[0] || '';
                        d.end_date = parts[1] || '';
                        d.status = $('#status').val();
                        d.expired_on = $('#expired_on').val();
                        d.module_name = $('#modules').val();
                        d.business_id = $('#business').val();
                    }
                },
                columns: modules_sub_cols
            });
        }

        function initialiseFamilySubscriptionTable() {
            if (family_subscription_table || !$('#family_subscription_table').length) {
                return;
            }

            family_subscription_table = $('#family_subscription_table').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                searchDelay: 400,
                ajax: "{{ url('/superadmin/family-subscription') }}",
                columnDefs: [{
                    targets: 6,
                    orderable: false,
                    searchable: false
                }],
                fnDrawCallback: function () {
                    __currency_convert_recursively($('#family_subscription_table'), true);
                }
            });
        }

        // superadmin_subscription_table
        var superadmin_subscription_table = $('#superadmin_subscription_table').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            searchDelay: 400,
            pageLength: 25,
            ajax: "{{ url('/superadmin/superadmin-subscription') }}",
            columns: [
                { data: 'action', name: 'action', className: 'sa-action-column', defaultContent: '' },
                { data: 'business_name', name: 'business_name' },
                { data: 'patient_code', name: 'patient_code' },
                { data: 'package_name', name: 'package_name' },
                { data: 'status', name: 'status' },
                { data: 'start_date', name: 'start_date' },
                { data: 'trial_end_date', name: 'trial_end_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'package_price', name: 'package_price' },
                { data: 'paid_via', name: 'paid_via' },
                { data: 'payment_transaction_id', name: 'payment_transaction_id' }
            ],
            columnDefs: [{
                targets: 0,
                orderable: false,
                searchable: false,
                visible: true,
                className: 'sa-action-column'
            }],
            responsive: false,
            autoWidth: false,
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#superadmin_subscription_table'), true);
                var api = this.api();
                api.column(0).visible(true, false);
                api.columns.adjust();
            }
        });

        // Hidden tab tables are initialized only when opened.



        $('a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
            var target = $(event.target).attr('href');
            if (target === '#family_subscription') {
                initialiseFamilySubscriptionTable();
            } else if (target === '#module_subscription') {
                initialiseModuleSubscriptionTable();
            }
        });

        // change_status button
        $(document).on('click', 'button.change_status', function(){
            $("div#statusModal").load($(this).data('href'), function(){
                $(this).modal('show');
                $("form#status_change_form, form#fs_status_change_form").submit(function(e){
                    e.preventDefault();
                    var url = $(this).attr("action");
                    var data = $(this).serialize();
                    $.ajax({
                        method: "POST",
                        dataType: "json",
                        data: data,
                        url: url,
                        success:function(result){
                            if( result.success == true){
                                $("div#statusModal").modal('hide');
                                toastr.success(result.msg);
                                superadmin_subscription_table.ajax.reload(null, false);
                                if (family_subscription_table) { family_subscription_table.ajax.reload(null, false); }
                            }else{
                                toastr.error(result.msg);
                            }
                        }
                    });
                });
            });
        });

        $(document).on('shown.bs.modal', '.view_modal', function(){
            $('.edit-subscription-modal .datepicker').datepicker({
                autoclose: true,
                format:datepicker_date_format
            });
            $("form#edit_subscription_form").submit(function(e){
              e.preventDefault();
              var url = $(this).attr("action");
              var data = $(this).serialize();
              $.ajax({
                  method: "POST",
                  dataType: "json",
                  data: data,
                  url: url,
                  success:function(result){
                      if( result.success == true){
                          $("div.view_modal").modal('hide');
                          toastr.success(result.msg);
                          superadmin_subscription_table.ajax.reload(null, false);
                          if (family_subscription_table) { family_subscription_table.ajax.reload(null, false); }
                      }else{
                          toastr.error(result.msg);
                      }
                  }
              });
            });
        });

    });
</script>
@endsection
