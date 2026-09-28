@extends('layouts.app')
@section('title', __('lang_v1.'.$type.'s'))

@section('content')

<!-- Content Header (Page header) -->

<style>
  
.popup{
   
    cursor: pointer
}
.popupshow{
    z-index: 99999;
    display: none;
}
.popupshow .overlay{
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,.66);
    position: absolute;
    top: 0;
    left: 0;
}
.popupshow .img-show{
        width: 900px;
    height: 600px;
    background: #FFF;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%,-50%);
    overflow: hidden;
}
.img-show span{
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 99;
    cursor: pointer;
}
.img-show img{
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
}
/*End style*/

</style>


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Contacts</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">Contacts</a></li>
                    <li><span>Manage contacts</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>



<!-- Main content -->
<section class="content main-content-inner">
 <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs">
                   <li class="">
                         <a class="collapse-item " href="{{action('\Modules\Vat\Http\Controllers\VatContactController@index',['type' => 'customer'])}}">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.customers')</strong>
                        </a>
                    </li>
                  
                    <li class="">
                         <a class="collapse-item " href="{{action('\Modules\Vat\Http\Controllers\VatContactController@index',['type' => 'supplier'])}}">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.suppliers')</strong>
                        </a>
                    </li>
                  
                </ul>
                </div>
            </div>
        </div>
  
    <input type="hidden" value="{{$type}}" id="contact_type">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'contact.all_your_contact', ['contacts' =>
    __('lang_v1.'.$type.'s') ])])
    
   <div class="box-tools pull-right vat-contact-add-wrapper" style="position:relative; z-index:99999; pointer-events:auto;">
        <input type="hidden" id="default_contact_id" value="{{ $contact_id ?? ''}}" >
        @php
            $vat_contact_add_url = url('vat-module/vat-contacts/direct-create/' . $type);
        @endphp
        <a href="{{ $vat_contact_add_url }}"
           class="btn btn-primary"
           id="vat_contact_add_direct_page_btn"
           style="position:relative; z-index:100000; pointer-events:auto;"
           onclick="event.preventDefault(); event.stopPropagation(); window.location.href='{{ $vat_contact_add_url }}'; return false;">
            <i class="fa fa-plus"></i> @lang('messages.add')
        </a>
    </div>
        
    <div class="table-responsive">
        <table class="table table-bordered table-striped" style="width: 100%" id="vat_contact_table">
            <thead>
                <tr>
                    <td colspan="6">
                        <div class="row">
                            <div class="col-sm-2">
                                @if(auth()->user()->can('customer.delete') || auth()->user()->can('supplier.delete'))
                                    {!! Form::open(['url' => action('\Modules\Vat\Http\Controllers\VatContactController@massDestroy'), 'method' => 'post', 'id'
                                    => 'mass_delete_form' ]) !!}
                                    {!! Form::hidden('selected_rows', null, ['id' => 'selected_rows']); !!}
                                    {!! Form::submit(__('lang_v1.delete_selected'), array('class' => 'btn btn-xs btn-danger',
                                    'id' => 'delete-selected')) !!}
                                    {!! Form::close() !!}
                                @endif
                            </div>
                            
                        </div>
                        
                    </td>
                    
                </tr>
                
                <tr>
                    <th><input type="checkbox" id="select-all-row"></th>
                    <th class="notexport">@lang('messages.action')</th>
                    <th >@lang('lang_v1.contact_id')</th>
                    <th>@lang('contact.name')</th>
                    <th>@lang('contact.mobile')</th>
                    <th>@lang('lang_v1.added_on')</th>
                </tr>
            </thead>
        </table>
    </div>
    
    @endcomponent

    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade pay_contact_due_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>


<!-- /.content -->

@endsection

@section('javascript')

@if(session('status'))
    @if(session('status')['success'])
        <script>
            toastr.success('{{ session("status")["msg"] }}');
        </script>
    @else
        <script>
            toastr.error('{{ session("status")["msg"] }}');
        </script>
    @endif
@endif


<script>
    
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
    $(document).on('click', '#delete-selected', function(e){
        e.preventDefault();
        var selected_rows = getSelectedRows();

        if(selected_rows.length > 0){
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
        } else{
        $('input#selected_rows').val('');
            swal('@lang("lang_v1.no_row_selected")');
        }
    });
    
    
    function getSelectedRows() {
        var selected_rows = [];
        var i = 0;
        $('.row-select:checked').each(function () {
            selected_rows[i++] = $(this).val();
        });

        return selected_rows;
    }


</script>

<script>
(function($) {
    "use strict";

    function vatContactShowError(xhr) {
        var msg = '@lang("messages.something_went_wrong")';
        if (xhr && xhr.status === 403) {
            msg = 'Unauthorized Access. Please check VAT Contacts permission for this business.';
        } else if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
            msg = xhr.responseJSON.msg;
        }

        if (typeof toastr !== 'undefined') {
            toastr.error(msg);
        } else {
            alert(msg);
        }
    }

    window.openVatContactModal = function(url) {
        if (!url) { return false; }

        var $modal = $('.contact_modal');
        $modal.html('<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-body text-center" style="padding: 40px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br><br>Loading...</div></div></div>');
        $modal.modal({backdrop: 'static', keyboard: false});

        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            cache: false,
            success: function(result) {
                $modal.html(result);
                $modal.modal('show');
                if ($.fn.select2) {
                    $modal.find('select.select2').each(function() {
                        var $select = $(this);
                        if ($select.data('select2')) {
                            $select.select2('destroy');
                        }
                        $select.select2({ dropdownParent: $modal });
                    });
                }
            },
            error: function(xhr) {
                $modal.modal('hide');
                vatContactShowError(xhr);
            }
        });

        return false;
    };

    $(document).off('click.vatContactModal', '#vat_contact_add_btn, .vat-contact-modal-trigger, .vat-contact-edit-btn')
        .on('click.vatContactModal', '#vat_contact_add_btn, .vat-contact-modal-trigger, .vat-contact-edit-btn', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return window.openVatContactModal($(this).data('href') || $(this).attr('href'));
        });

    $(document).off('submit.vatContactForm', '.contact_modal form#vat_contact_add_form, .contact_modal form#vat_contact_edit_form')
        .on('submit.vatContactForm', '.contact_modal form#vat_contact_add_form, .contact_modal form#vat_contact_edit_form', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            $submit.prop('disabled', true);

            $.ajax({
                method: $form.attr('method') || 'POST',
                url: $form.attr('action'),
                data: $form.serialize(),
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        $('.contact_modal').modal('hide').html('');
                        if (typeof toastr !== 'undefined') {
                            toastr.success(result.msg);
                        }
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#vat_contact_table')) {
                            $('#vat_contact_table').DataTable().ajax.reload(null, false);
                        } else if (typeof vat_contact_table !== 'undefined' && vat_contact_table.ajax) {
                            vat_contact_table.ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(result.msg || '@lang("messages.something_went_wrong")');
                        } else {
                            alert(result.msg || '@lang("messages.something_went_wrong")');
                        }
                    }
                },
                error: function(xhr) {
                    vatContactShowError(xhr);
                },
                complete: function() {
                    $submit.prop('disabled', false);
                }
            });
        });

    $(document).off('click.vatContactClose', '.closing_contact_modal')
        .on('click.vatContactClose', '.closing_contact_modal', function(e) {
            e.preventDefault();
            $('.contact_modal').modal('hide').html('');
        });
})(jQuery);
</script>


<script>
(function($) {
    "use strict";

    function showVatContactError(xhr) {
        var msg = '@lang("messages.something_went_wrong")';
        if (xhr && xhr.status === 403) {
            msg = 'Unauthorized Access. Please check VAT Contacts permission.';
        } else if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
            msg = xhr.responseJSON.msg;
        }
        if (typeof toastr !== 'undefined') {
            toastr.error(msg);
        } else {
            alert(msg);
        }
    }

    function loadVatContactModal(url) {
        if (!url) { return false; }
        var $modal = $('.contact_modal');
        $modal.html('<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-body text-center" style="padding:40px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br><br>Loading...</div></div></div>');
        $modal.modal({backdrop: 'static', keyboard: false});

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            cache: false,
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function(html) {
                $modal.html(html).modal('show');
                if ($.fn.select2) {
                    $modal.find('select.select2').each(function() {
                        if ($(this).data('select2')) {
                            $(this).select2('destroy');
                        }
                        $(this).select2({dropdownParent: $modal});
                    });
                }
            },
            error: function(xhr) {
                $modal.modal('hide').html('');
                showVatContactError(xhr);
            }
        });
        return false;
    }

    $(document).off('click.vatContactsAddEdit', '.vat-contact-edit-btn')
        .on('click.vatContactsAddEdit', '.vat-contact-edit-btn', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var url = $(this).data('href') || $(this).attr('href');
            return loadVatContactModal(url);
        });

    $(document).off('submit.vatContactsForms', '.contact_modal form#vat_contact_add_form, .contact_modal form#vat_contact_edit_form')
        .on('submit.vatContactsForms', '.contact_modal form#vat_contact_add_form, .contact_modal form#vat_contact_edit_form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            $submit.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method') || 'POST',
                data: $form.serialize(),
                dataType: 'json',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                success: function(result) {
                    if (result && result.success) {
                        $('.contact_modal').modal('hide').html('');
                        if (typeof toastr !== 'undefined') {
                            toastr.success(result.msg);
                        }
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#vat_contact_table')) {
                            $('#vat_contact_table').DataTable().ajax.reload(null, false);
                        } else if (typeof vat_contact_table !== 'undefined' && vat_contact_table.ajax) {
                            vat_contact_table.ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        showVatContactError({responseJSON: result || {}});
                    }
                },
                error: showVatContactError,
                complete: function() {
                    $submit.prop('disabled', false);
                }
            });
        });

    $(document).off('click.vatContactsClose', '.closing_contact_modal')
        .on('click.vatContactsClose', '.closing_contact_modal', function(e) {
            e.preventDefault();
            $('.contact_modal').modal('hide').html('');
        });
})(jQuery);
</script>

@endsection
