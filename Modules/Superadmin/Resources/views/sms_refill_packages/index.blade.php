@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | ' . __('superadmin::lang.packages'))

@section('content')

<style>
    #sms_packages_table_wrapper .table-responsive,
    #refill_business_table_wrapper .table-responsive,
    #sms_packages_table,
    #refill_business_table {
        overflow: visible !important;
    }
    .sms-action-dropdown .dropdown-menu {
        z-index: 99999 !important;
        min-width: 130px;
    }
</style>
	
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('superadmin::lang.packages') <small>@lang('superadmin::lang.all_packages')</small></h1>
    <!-- <ol class="breadcrumb">
        <a href="#"><i class="fa fa-dashboard"></i> Level</a><br/>
        <li class="active">Here<br/>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
	@include('superadmin::layouts.partials.currency')
	
	<div class="row"> 
                <div class="col-md-12">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="@if(empty(session('status.tab'))) active @endif">
                                <a href="#sms_packages" data-toggle="tab" aria-expanded="true">
                                    @lang('superadmin::lang.sms_packages')
                                </a>
                            </li>
                            
                            <li class="@if(session('status.tab') == 'refill_business') active @endif">
                                <a href="#refill_business" data-toggle="tab" aria-expanded="true">
                                    @lang('superadmin::lang.refill_business')
                                </a>
                            </li>
                            
                            <li class="@if(session('status.tab') == 'external_api_clients') active @endif">
                                <a href="#external_api_clients" data-toggle="tab" aria-expanded="true">
                                    @lang('superadmin::lang.external_api_clients')
                                </a>
                            </li>
                            
                            
                            <li class="@if(session('status.tab') == 'sms_summary') active @endif">
                                <a href="#sms_summary" data-toggle="tab" aria-expanded="true">
                                    @lang('sms::lang.sms_summary')
                                </a>
                            </li>
                            
                            <li class="@if(session('status.tab') == 'sms_history') active @endif">
                                <a href="#sms_history" data-toggle="tab" aria-expanded="true">
                                    @lang('superadmin::lang.sms_history')
                                </a>
                            </li>
                            
                            <li class="@if(session('status.tab') == 'sms_reminder_settings') active @endif">
                                <a href="#sms_reminder_settings" data-toggle="tab" aria-expanded="true">
                                    @lang('superadmin::lang.sms_reminder_settings')
                                </a>
                            </li>
                            
                            
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane @if(empty(session('status.tab'))) active @endif" id="sms_packages">
                                @include('superadmin::sms_refill_packages.partials.sms_packages')
                            </div>
                            
                            <div class="tab-pane @if(session('status.tab') == 'refill_business') active @endif" id="refill_business">
                                @include('superadmin::sms_refill_packages.partials.refill_business')
                            </div>
                            
                            <div class="tab-pane @if(session('status.tab') == 'external_api_clients') active @endif" id="external_api_clients">
                                @include('superadmin::sms_refill_packages.partials.external_api_clients')
                            </div>
                            
                            <div class="tab-pane @if(session('status.tab') == 'sms_summary') active @endif" id="sms_summary">
                                @include('superadmin::sms_refill_packages.partials.sms_summary')
                            </div>
                            
                            <div class="tab-pane @if(session('status.tab') == 'sms_history') active @endif" id="sms_history">
                                @include('superadmin::sms_refill_packages.partials.sms_history')
                            </div>
                            
                            <div class="tab-pane @if(session('status.tab') == 'sms_reminder_settings') active @endif" id="sms_reminder_settings">
                                @include('superadmin::sms_refill_packages.partials.sms_reminder_settings')
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>


    {{-- The SMS Package Add form is rendered locally so global AJAX modal handlers
         can never replace it with the Refill Business form. --}}
    <div class="modal fade" id="smsPackageCreateModal" tabindex="-1" role="dialog"
        aria-labelledby="smsPackageCreateModalLabel">
        @include('superadmin::sms_refill_packages.create')
    </div>

    {{-- Edit keeps a separate, uniquely targeted remote modal. --}}
    <div class="modal fade sms_package_edit_modal" tabindex="-1" role="dialog"
        aria-labelledby="smsPackageEditModalLabel"></div>

    {{-- Used only by the Refill Business tab. --}}
    <div class="modal fade packages_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel"></div>
    
    <div class="modal fade" id="noteModal" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog">
          <div class="modal-content">
    
            <!-- Modal Header -->
            <div class="modal-header">
              <h4 class="modal-title">@lang( 'lang_v1.note' )</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
    
            <!-- Modal Body -->
            <div class="modal-body">
              <p id="noteContent" class="text-center text-bold"></p>
            </div>
    
          </div>
        </div>
      </div>


<div class="modal fade" id="msgModal" role="dialog" 
      aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog">
          <div class="modal-content">
    
            <!-- Modal Header -->
            <div class="modal-header">
              <h4 class="modal-title">@lang( 'superadmin::lang.message' )</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
    
            <!-- Modal Body -->
            <div class="modal-body">
              <p id="msgContent" class="text-center"></p>
            </div>
    
          </div>
        </div>
      </div>
    
    
</section>
<!-- /.content -->

@endsection

@section('javascript')
<!-- START: package subscription scripts-->
<script>

// Prevent browser popup alerts from DataTables; errors are logged server-side and shown in console/toastr.
if ($.fn.dataTable) {
    $.fn.dataTable.ext.errMode = 'none';
}
$(document).on('error.dt', 'table.dataTable', function (e, settings, techNote, message) {
    console.error('DataTables error:', message);
    if (typeof toastr !== 'undefined') {
        toastr.error('Table could not be loaded. Please check the Laravel log.');
    }
});

(function ($) {
    'use strict';

    var editRequest = null;

    function tenantSafeUrl(rawUrl) {
        if (typeof window.erpTenantSafeUrl === 'function') {
            return window.erpTenantSafeUrl(rawUrl);
        }
        return rawUrl;
    }

    function showPackageMessage(message, isSuccess) {
        if (typeof toastr !== 'undefined') {
            if (isSuccess) {
                toastr.success(message);
            } else {
                toastr.error(message);
            }
            return;
        }
        window.alert(message);
    }

    function recalculateSmsCount($form) {
        var unitCost = parseFloat($form.find('[name="unit_cost"]').val()) || 0;
        var amount = parseFloat($form.find('[name="amount"]').val()) || 0;
        var smsCount = unitCost > 0 && amount >= 0 ? Math.floor(amount / unitCost) : 0;
        $form.find('[name="no_of_sms"]').val(smsCount);
    }

    function reloadSmsPackages() {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#sms_packages_table')) {
            $('#sms_packages_table').DataTable().ajax.reload(null, true);
        }
    }

    function firstValidationMessage(xhr) {
        var fallback = 'Unable to save the SMS Package.';
        if (!xhr || !xhr.responseJSON) {
            return fallback;
        }
        if (xhr.responseJSON.errors) {
            var fields = Object.keys(xhr.responseJSON.errors);
            if (fields.length && xhr.responseJSON.errors[fields[0]].length) {
                return xhr.responseJSON.errors[fields[0]][0];
            }
        }
        return xhr.responseJSON.message || xhr.responseJSON.msg || fallback;
    }

    // Add opens a form already rendered inside this page. It is intentionally
    // not a .btn-modal button, preventing global modal scripts from loading
    // another controller's form into the same popup.
    $(document)
        .off('click.smsPackageCreate', '.sms-package-create-trigger')
        .on('click.smsPackageCreate', '.sms-package-create-trigger', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            var $modal = $('#smsPackageCreateModal');
            var $form = $modal.find('form.sms-package-form[data-form-mode="create"]');
            if (!$modal.length || !$form.length) {
                showPackageMessage('The SMS Package form is not available. Please refresh the page.', false);
                return;
            }

            $form[0].reset();
            $form.find('[name="date"]').val('{{ date('Y-m-d') }}');
            recalculateSmsCount($form);
            $modal.modal('show');
            setTimeout(function () { $form.find('[name="name"]').trigger('focus'); }, 150);
        });

    // Edit has its own class and modal container, separate from Refill Business.
    $(document)
        .off('click.smsPackageEdit', '.sms-package-edit-trigger')
        .on('click.smsPackageEdit', '.sms-package-edit-trigger', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            var $button = $(this);
            var url = tenantSafeUrl($button.attr('data-href'));
            var $modal = $('.sms_package_edit_modal').first();

            if (!url || !$modal.length || $button.data('sms-package-loading')) {
                return;
            }

            if (editRequest && editRequest.readyState !== 4) {
                editRequest.abort();
            }

            $button.data('sms-package-loading', true);
            $modal.html('<div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-body text-center" style="padding:30px;"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>').modal('show');

            editRequest = $.ajax({
                url: url,
                method: 'GET',
                dataType: 'html',
                cache: false,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).done(function (html) {
                var $probe = $('<div>').html(html);
                if (!$probe.find('form.sms-package-form[data-form-mode="edit"]').length) {
                    $modal.modal('hide').empty();
                    showPackageMessage('The SMS Package edit form could not be loaded correctly.', false);
                    return;
                }
                $modal.html(html).modal('show');
                recalculateSmsCount($modal.find('form.sms-package-form'));
            }).fail(function (xhr, status) {
                if (status !== 'abort') {
                    $modal.modal('hide').empty();
                    showPackageMessage('Unable to open the SMS Package edit form.', false);
                }
            }).always(function () {
                $button.data('sms-package-loading', false);
            });
        });

    $(document)
        .off('input.smsPackageCalculation change.smsPackageCalculation', 'form.sms-package-form [name="unit_cost"], form.sms-package-form [name="amount"]')
        .on('input.smsPackageCalculation change.smsPackageCalculation', 'form.sms-package-form [name="unit_cost"], form.sms-package-form [name="amount"]', function () {
            recalculateSmsCount($(this).closest('form'));
        });

    $(document)
        .off('submit.smsPackageForm', 'form.sms-package-form[data-form-type="sms-package"]')
        .on('submit.smsPackageForm', 'form.sms-package-form[data-form-type="sms-package"]', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var $modal = $form.closest('.modal');

            if ($form.data('submitting')) {
                return;
            }

            recalculateSmsCount($form);
            $form.data('submitting', true);
            $submit.prop('disabled', true);

            $.ajax({
                url: tenantSafeUrl($form.attr('action')),
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).done(function (result) {
                if (!result || !result.success) {
                    showPackageMessage((result && result.msg) ? result.msg : 'Unable to save the SMS Package.', false);
                    return;
                }

                $modal.modal('hide');
                if ($modal.hasClass('sms_package_edit_modal')) {
                    $modal.empty();
                }
                showPackageMessage(result.msg, true);
                reloadSmsPackages();
            }).fail(function (xhr) {
                showPackageMessage(firstValidationMessage(xhr), false);
            }).always(function () {
                $form.data('submitting', false);
                $submit.prop('disabled', false);
            });
        });

    $('.sms_package_edit_modal').on('hidden.bs.modal', function () {
        if (editRequest && editRequest.readyState !== 4) {
            editRequest.abort();
        }
        $(this).empty();
    });
})(jQuery);

(function ($) {
    'use strict';

    function refillTenantSafeUrl(rawUrl) {
        if (typeof window.erpTenantSafeUrl === 'function') {
            return window.erpTenantSafeUrl(rawUrl);
        }
        return rawUrl;
    }

    function showRefillMessage(message, isSuccess) {
        if (typeof toastr !== 'undefined') {
            if (isSuccess) {
                toastr.success(message);
            } else {
                toastr.error(message);
            }
            return;
        }

        window.alert(message);
    }

    function firstRefillValidationMessage(xhr) {
        var fallback = 'Unable to save the business SMS refill.';

        if (!xhr || !xhr.responseJSON) {
            return fallback;
        }

        if (xhr.responseJSON.errors) {
            var fields = Object.keys(xhr.responseJSON.errors);
            if (fields.length && xhr.responseJSON.errors[fields[0]].length) {
                return xhr.responseJSON.errors[fields[0]][0];
            }
        }

        return xhr.responseJSON.message || xhr.responseJSON.msg || fallback;
    }

    $(document)
        .off('submit.refillBusinessForm', 'form.refill-business-form[data-form-type="refill-business"]')
        .on('submit.refillBusinessForm', 'form.refill-business-form[data-form-type="refill-business"]', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var $modal = $form.closest('.modal');

            if ($form.data('submitting')) {
                return;
            }

            $form.data('submitting', true);
            $submit.prop('disabled', true);

            $.ajax({
                url: refillTenantSafeUrl($form.attr('action')),
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).done(function (result) {
                if (!result || !result.success) {
                    showRefillMessage((result && result.msg) ? result.msg : 'Unable to save the business SMS refill.', false);
                    return;
                }

                $modal.modal('hide');
                showRefillMessage(result.msg, true);
                $('a[href="#refill_business"]').tab('show');

                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#refill_business_table')) {
                    $('#refill_business_table').DataTable().ajax.reload(null, true);
                }
            }).fail(function (xhr) {
                showRefillMessage(firstRefillValidationMessage(xhr), false);
            }).always(function () {
                $form.data('submitting', false);
                $submit.prop('disabled', false);
            });
        });

    $('.packages_modal')
        .off('hidden.bs.modal.refillBusiness')
        .on('hidden.bs.modal.refillBusiness', function () {
            $(this).find('.sms-refill-select').each(function () {
                if ($.fn.select2 && $(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            $(this).empty();
        });
})(jQuery);

$(document).ready(function(){
       $('#days_1_status').trigger('change');
       $('#days_2_status').trigger('change');
       $('#days_3_status').trigger('change');
       $('#days_4_status').trigger('change');
       
       $('#days_1_status').change(function() {
            var isChecked = $(this).prop('checked');
            if(isChecked){
                $(".days_1").prop('readonly', false);
                $(".days_1").prop('required', true);
            } else {
                $(".days_1").prop('readonly', true);
                $(".days_1").prop('required', false);
            }
        });
        
       $('#days_2_status').change(function() {
            var isChecked = $(this).prop('checked');
            if(isChecked){
                $(".days_2").prop('readonly', false);
                $(".days_2").prop('required', true);
            } else {
                $(".days_2").prop('readonly', true);
                $(".days_2").prop('required', false);
            }
        });
        
       $('#days_3_status').change(function() {
            var isChecked = $(this).prop('checked');
            if(isChecked){
                $(".days_3").prop('readonly', false);
                $(".days_3").prop('required', true);
            } else {
                $(".days_3").prop('readonly', true);
                $(".days_3").prop('required', false);
            }
        });
        
       $('#days_4_status').change(function() {
            var isChecked = $(this).prop('checked');
            if(isChecked){
                $(".days_4").prop('readonly', false);
                $(".days_4").prop('required', true);
            } else {
                $(".days_4").prop('readonly', true);
                $(".days_4").prop('required', false);
            }
        });
        
       $('#days_1_status').trigger('change');
       $('#days_2_status').trigger('change');
       $('#days_3_status').trigger('change');
       $('#days_4_status').trigger('change');
        
   })

    
    $(document).on('click', '.msg_btn', function(e){
      let note = $(this).data('string');
      // Replace newline characters with <br>
      note = note.replace(/\n/g, '<br>');
      $("#msgContent").html(note);
      $("#msgModal").modal('show');
    });  

    $(document).on('change','#default_gateway', function(){
        
        if($(this).val() == 'ultimate_sms'){
            $(".direct").addClass('hide');
            $(".hutch_sms").addClass('hide');
            $(".ultimate_sms").removeClass('hide');
        }else if($(this).val() == 'hutch_sms'){
            $(".direct").addClass('hide');
            $(".hutch_sms").removeClass('hide');
            $(".ultimate_sms").addClass('hide');
        }else{
            $(".direct").removeClass('hide');
            $(".hutch_sms").addClass('hide');
            $(".ultimate_sms").addClass('hide');
        }
    })
    

    $(document).on('click', '.note_btn', function(e){
      let note = $(this).data('string');
      $("#noteContent").html(note);
      $("#noteModal").modal('show');
       
    });
        
    $(document).ready(function(){
    

        var sms_packages_cols = [
            { data: 'date', name: 'date', defaultContent: '' },
            { data: 'name', name: 'name', defaultContent: '' },
            { data: 'unit_cost', name: 'unit_cost', className: 'text-right', defaultContent: '0.00' },
            { data: 'amount', name: 'amount', className: 'text-right', defaultContent: '0.00' },
            { data: 'no_of_sms', name: 'no_of_sms', className: 'text-right', defaultContent: '0.00' },
            { data: 'username', name: 'username', defaultContent: '' },
            { data: 'action', name: 'action', searchable: false, orderable: false, defaultContent: '' },
        ];
        
        
        sms_packages_table = $('#sms_packages_table').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            ajax: {
                url: "{{ action('\Modules\Superadmin\Http\Controllers\SmsRefillPackageController@index') }}",
                type: 'GET',
                data: function(d) {},
                error: function(xhr) {
                    console.error('SMS Packages DataTable Ajax failed', xhr.responseText || xhr.statusText);
                    if (typeof toastr !== 'undefined') {
                        toastr.error('SMS Packages table could not be loaded. Please check Laravel log.');
                    }
                }
            },
            columns: sms_packages_cols,
            order: [],
        });
        
        
        $('#date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                $("#report_date_range").text("Date Range: "+ $("#date_range").val());
                refill_business_table.ajax.reload();
            }
        );
        $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#date_range').val('');
            $("#report_date_range").text("Date Range: - ");
            refill_business_table.ajax.reload();
        });
        
        $('#date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
    
        $('#date_range').data('daterangepicker').setEndDate(moment().endOf('month'));
        
        
        var refill_business_cols = [
            { data: 'date', name: 'date' },
            { data: 'type', name: 'type' },
            { data: 'business_name', name: 'business_name', searchable: false },
            { data: 'package_name', name: 'sms_refill_packages.name' },
            { data: 'amount', name: 'amount' },
            { data: 'no_of_sms', name: 'no_of_sms' },
            { data: 'expiry_date', name: 'refill_business.expiry_date' },
            { data: 'payment_method', name: 'payment_method' },
            { data: 'username', name: 'users.username' },
            { data: 'action', name: 'action', searchable: false },
        ];
        
        
        refill_business_table = $('#refill_business_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ url('superadmin/refill-business') }}",
                data: function(d) {
                    
                    var start = '';
                      var end = '';
    
                      if($('#date_range').val()){
                        start = $('#date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        end = $('#date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                      }
    
                      d.start_date = start;
                      d.end_date = end;
                    
                    d.business_id = $('#filter_business_id').val(); 
                    
                    var selectedOption = $('#filter_business_id').find('option:selected');
                    d.type = selectedOption.data('string') || '';
                    
                    d.package_id = $('#filter_package_id').val(); 
                    d.payment_method = $('#filter_payment_method').val(); 
                    d.created_by = $('#filter_created_by').val();
                    
                    d.business_type = $('#filter_type').val();
                    
                },
            },
            columns: refill_business_cols,
        });
        
        
        $(document).on('change', '#filter_business_id, #filter_package_id, #filter_payment_method, #filter_created_by,#filter_type',  function() {
            refill_business_table.ajax.reload();
        });
        
        
        sms_summary_table = $('#sms_summary_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ url('superadmin/sms-summary') }}",
                data: function(d) {
                    
                },
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'type', name: 'type' },
                { data: 'sms_balance', name: 'sms_balance', searchable: false },
                { data: 'action', name: 'action', searchable: false },
            ],
        });
        
        
        
        external_api_clients_table = $('#external_api_clients_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ url('superadmin/sms-api-clients') }}",
                data: function(d) {
                    
                },
            },
            columns: [
                { data: 'date', name: 'date' },
                { data: 'name', name: 'name'},
                { data: 'contact_mobile', name: 'contact_mobile'},
                { data: 'land_no', name: 'land_no'},
                { data: 'contact_name', name: 'contact_name'},
                { data: 'api_key', name: 'api_key'},
                { data: 'sender_names', name: 'sender_names'},
                { data: 'username', name: 'username'},
                { data: 'password', name: 'password'},
                { data: 'action', name: 'action', searchable: false },
            ],
        });
        
        
        
        
        $('#history_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#history_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                $("#report_history_date_range").text("Date Range: "+ $("#history_date_range").val());
                sms_history_table.ajax.reload();
            }
        );
        $('#history_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#history_date_range').val('');
            $("#report_history_date_range").text("Date Range: - ");
            sms_history_table.ajax.reload();
        });
        
        $('#history_date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
    
        $('#history_date_range').data('daterangepicker').setEndDate(moment().endOf('month'));
        
        
        var refill_business_cols = [
            { data: 'created_at', name: 'created_at' },
            { data: 'id', name: 'id' },
            { data: 'business_name', name: 'business_name', searchable: false },
            { data: 'business_type', name: 'business_type' },
            { data: 'username', name: 'username' },
            { data: 'sender_name', name: 'sender_name' },
            { data: 'recipient', name: 'recipient' },
            { data: 'message', name: 'message'},
            { data: 'sms_type_', name: 'sms_type_' },
            { data: 'no_of_sms', name: 'no_of_sms' },
            { data: 'sms_status', name: 'sms_status'},
        ];
        
        
        sms_history_table = $('#sms_history_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ url('superadmin/get-history') }}",
                data: function(d) {
                    
                    var start = '';
                      var end = '';
    
                      if($('#history_date_range').val()){
                        start = $('#history_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        end = $('#history_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                      }
    
                      d.start_date = start;
                      d.end_date = end;
                    
                    d.business_id = $('#history_business_id').val(); 
                    
                    var selectedOption = $('#history_business_id').find('option:selected');
                    d.type = selectedOption.data('string') || '';
                    
                    d.username = $('#username').val(); 
                    d.sender_name = $('#sender_name').val(); 
                    d.sms_type_ = $('#sms_type_').val();
                    d.sms_status = $('#sms_status').val();
                    d.business_type = $('#history_type').val();
                    
                    
                },
            },
            columns: refill_business_cols,
        });
        
        
        $(document).on('change', '#history_business_id,#username,#sender_name,#sms_type_,#sms_status,#history_type',  function() {
            sms_history_table.ajax.reload();
        });
        
    });
</script>
<!--END: package subscription scripts -->

<!-- start: Tenant management-->
<script>
    $(document).on('click', '.delete_record', function(){
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete)=>{
                if(willDelete){
                     var url = $(this).data('href');
                     $.ajax({
                         method: "delete",
                         url: url,
                         dataType: "json",
                         success: function(result){
                             if(result.success == true){
                                toastr.success(result.msg);
                                sms_packages_table.ajax.reload();
                                refill_business_table.ajax.reload();
                             }else{
                                toastr.error(result.msg);
                            }

                        }
                    });
                }
            });
        });
        
        $(document).on('submit','#sms_list_interest_form', function(event) {
            // Prevent the default form submission
            event.preventDefault();
            
    
            // Perform AJAX request
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(), // Serialize form data
                success: function(response) {
                    if(response.success == true){
                        toastr.success(response.msg);
                        $("#form_no").val(response.form_no);
                        $(".sms_list_fields").val("");
                        sms_list_interests_table.ajax.reload();
                        sms_summary_table.ajax.reload();
                    }else{
                        toastr.error(response.msg);
                    }
                    
                    
                    
                },
                error: function(xhr, status, error) {
                    console.error(error); // Log any errors
                }
            });
        });
</script>
<!-- END: Tenant management-->

@endsection