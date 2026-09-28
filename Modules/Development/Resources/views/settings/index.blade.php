@extends('layouts.app')

@section('title', __('development::lang.settings'))

@section('content')
<!-- Content Header -->
<section class="content-header">
    <h1>
        @lang('development::lang.settings')
        <small>@lang('development::lang.manage_modules')</small>
    </h1>
</section>

<!-- Main Content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('development::lang.all_modules')])
        @slot('tool')
            <div class="box-tools pull-right">
                <a class="btn btn-primary btn-sm" href="{{ route('development.settings.create') }}">
                    <i class="fa fa-plus"></i> @lang('development::lang.add')
                </a>
            </div>
            <hr>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="development-modules-table">
                <thead>
                    <tr>
                        <th>@lang('development::lang.name')</th>
                        <th>@lang('development::lang.created_at')</th>
                        <th>@lang('development::lang.actions')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        let table = $('#development-modules-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("development.settings.index") }}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // Delete module with SweetAlert confirmation
        $(document).on('click', '.delete-module', function () {
            swal({
                title: LANG.sure,
                text: "@lang('development::lang.delete_module_confirm')",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    let href = $(this).data('href');
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (result) {
                            if (result.success === true) {
                                toastr.success(result.msg);
                                table.ajax.reload();
                            } else {
                                toastr.error(result.msg || "@lang('messages.something_went_wrong')");
                            }
                        },
                        error: function () {
                            toastr.error("@lang('messages.something_went_wrong')");
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
