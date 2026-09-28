@extends('layouts.app')
@section('title', __('vat::lang.prefix_and_starting_nos') . ' - 126')

@section('content')
<!-- Main content -->

<section class="content">

<div class="row">
    @include('vat::statement126.partials.nav')
    <div class="col-md-12">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('vat::lang.prefix_and_starting_nos') . ' - 126'])
            @can('vat_module.create')
                @slot('tool')
                    <div class="box-tools">
                        <a href="#"
                           data-href="{{action('\Modules\Vat\Http\Controllers\VatStatement126PrefixController@create')}}"
                           data-container=".prefix_modal"
                           class="btn btn-block btn-primary btn-modal statement126-prefix-modal">
                            <i class="fa fa-plus"></i> @lang( 'messages.add' )</a>
                    </div>
                @endslot
            @endcan
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="statement126_prefixes_table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>@lang('vat::lang.prefix')</th>
                            <th>@lang('vat::lang.starting_no')</th>
                            <th>@lang('vat::lang.created_by')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent
    </div>
</div>

<div class="modal fade prefix_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

</section>

@endsection


@section('javascript')
<script>
    var statement126_prefixes_table;

    $(document).ready(function () {
        statement126_prefixes_table = $('#statement126_prefixes_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            ajax: {
                url: '{{action('\Modules\Vat\Http\Controllers\VatStatement126PrefixController@index')}}'
            },
            columns: [
                { data: 'prefix', name: 'prefix' },
                { data: 'starting_no', name: 'starting_no' },
                { data: 'user_created', name: 'users.username' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });
    });

    $(document).off('click.statement126PrefixModal', '.statement126-prefix-modal, .btn-modal[data-container=".prefix_modal"]')
        .on('click.statement126PrefixModal', '.statement126-prefix-modal, .btn-modal[data-container=".prefix_modal"]', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            var $modal = $('.prefix_modal');
            if (!href || !$modal.length) {
                return false;
            }
            $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>');
            $modal.modal('show');
            $.ajax({
                method: 'GET',
                url: href,
                dataType: 'html',
                success: function(result) {
                    $modal.html(result).modal('show');
                },
                error: function(xhr) {
                    $modal.modal('hide');
                    toastr.error(xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : '{{__('messages.something_went_wrong')}}');
                }
            });
            return false;
        });

    $(document).off('submit.statement126PrefixForm', 'form#statement126_prefix_add_form, form#statement126_prefix_edit_form')
        .on('submit.statement126PrefixForm', 'form#statement126_prefix_add_form, form#statement126_prefix_edit_form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            $submit.prop('disabled', true);
            $.ajax({
                method: $form.attr('method') || 'POST',
                url: $form.attr('action'),
                dataType: 'json',
                data: $form.serialize(),
                success: function(result) {
                    if (result.success) {
                        $('.prefix_modal').modal('hide');
                        toastr.success(result.msg);
                        if (statement126_prefixes_table) {
                            statement126_prefixes_table.ajax.reload(null, false);
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : '{{__('messages.something_went_wrong')}}');
                },
                complete: function() {
                    $submit.prop('disabled', false);
                }
            });
        });
    
    $(document).on('click', 'a.delete_task', function(e) {
        e.preventDefault();
        var href = $(this).data('href');
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                        if (statement126_prefixes_table) {
                            statement126_prefixes_table.ajax.reload(null, false);
                        }
                    },
                });
            }
        });
    });
</script>
@endsection
