@extends('layouts.app')
@section('title', __('cheque.templates'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('cheque.templates')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'cheque.templates_list')])

    @slot('tool')
    <div class="box-tools pull-right">
        <a type="button" class="btn btn-primary"
            href="{{action('Chequer\ChequeTemplateController@create', [], false)}}">
            <i class="fa fa-plus"></i> @lang('messages.add')</a>
    </div>
    <hr>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="templates_table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>@lang('cheque.template_name')</th>
                    <th>@lang('cheque.template_size')</th>
                    <th>Account Name</th>
                    <th>@lang('cheque.last_changed_by')</th>
                    <th>@lang('cheque.last_changed_date')</th>
                    <th>@lang('cheque.action')</th>
                </tr>
            </thead>

        </table>
    </div>

    @endcomponent
    <div class="modal fade template_link_bank_model" role="dialog"></div>
    
</section>
@endsection

@section('javascript')
<style>
    .cheque-template-actions { position: relative; }
    .cheque-action-parent { min-width: 120px; height: 38px; font-weight: 700; border-radius: 8px; }
    .cheque-action-menu { min-width: 170px; padding: 8px; border: 0; border-radius: 12px; box-shadow: 0 12px 30px rgba(15, 23, 42, .18); }
    .cheque-action-menu > li > a { display:block; margin-bottom:6px; padding:9px 12px; color:#fff !important; font-weight:700; border-radius:8px; }
    .cheque-action-menu > li:last-child > a { margin-bottom:0; }
    .cheque-action-child.add-account { background:#22c55e; }
    .cheque-action-child.edit-template { background:#8b5cf6; }
    .cheque-action-child.delete-template { background:#ef4444; }
</style>
<script>
    $('#location_id').change(function () {
        templates_table.ajax.reload();
    });
    //employee list
    templates_table = $('#templates_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{action("Chequer\ChequeTemplateController@index", [], false)}}',
            data: function (d) {
                d.location_id = $('#location_id').val();
                // d.contact_type = $('#contact_type').val();
            }
        },
        columns: [
            { data: 'id', name: 'cheque_templates.id' },
            { data: 'template_name', name: 'cheque_templates.template_name' },
            { data: 'template_size', name: 'cheque_templates.template_size' },
            { data: 'account_name', name: 'account_name', orderable: false, searchable: false },
            { data: 'username', name: 'users.username' },
            { data: 'created_date', name: 'cheque_templates.created_date' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
        error: function(xhr, error, thrown) {
            console.log('Cheque templates DataTable error:', xhr.responseText || thrown);
        },
        fnDrawCallback: function (oSettings) {
          
        },
    });

    $(document).on('click', 'a.delete_employee', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            text: 'This template will be deleted.',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                swal({
                    title: 'Confirm Delete',
                    text: 'Please confirm again. This will delete the template and its linked accounts.',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(confirmDelete => {
                    if (confirmDelete) {
                        var href = $(this).data('href');
                        var data = $(this).serialize();

                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success === true) {
                                    toastr.success(result.msg);
                                    templates_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            }
        });
    });
    
    $(document).on('click', '.link_bank', function(e) {
        e.preventDefault();
        
        var container = $(this).data('container');
        
        var url = $(this).data('href');
        
        $('.template_link_bank_model').load(url, function() {
            $(this).modal('show');

        });
    });
        $('#filter_business').select2();
    
    $(document).on('ready',function(){
        
        $("#template_link_bank_model").on("show.bs.modal", function(e) {
            var link = $(e.relatedTarget);
            $(this).load(link.attr("href"));
        });
    });
</script>
@endsection