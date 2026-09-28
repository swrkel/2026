@push('javascript')
    <script type="text/javascript" defer>
        // Default Business Types DataTable
        var default_business_types_table = $('#default_business_types_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{action('\Modules\Superadmin\Http\Controllers\DefaultBusinessTypeController@index')}}",
            columnDefs: [{
                "targets": 3,
                "orderable": false,
                "searchable": false
            }],
            columns: [
                { data: 'date', name: 'created_at' },
                { data: 'business_type', name: 'business_type' },
                { data: 'added_by_name', name: 'addedBy.username' },
                { data: 'action', name: 'action' }
            ]
        });

        $(document).on('submit', 'form#add_business_type_form', function(e) {
            e.preventDefault();
            var data = $(this).serialize();

            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        $('.business_type_modal').modal('hide');
                        default_business_types_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        $(document).on('submit', 'form#edit_business_type_form', function(e) {
            e.preventDefault();
            var data = $(this).serialize();

            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        $('.business_type_modal').modal('hide');
                        default_business_types_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        $(document).on('click', 'button.business_type_delete', function() {
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                default_business_types_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        // Auto select tab based on session
        @if(session('status.tab') == 'default_business_types')
        $('a[href="#default_business_types_tab"]').tab('show');
        default_business_types_table.ajax.reload();
        @endif

        $('.business_type_modal').on('hidden.bs.modal', function () {
            $('#add_business_type_form')[0].reset();
        });
    </script>
@endpush
<!-- Main content -->
<section class="content" id="app">
    <div class="row">
        <div class="col-xs-12">
            <button type="button" class="btn btn-primary btn-modal pull-right"
                    data-href="{{action('\Modules\Superadmin\Http\Controllers\DefaultBusinessTypeController@create')}}"
                    data-container=".business_type_modal">
                <i class="fa fa-plus"></i> Add Default Business Type
            </button>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="default_business_types_table" style="width: 100%">
                    <thead>
                    <tr>
                        <th>@lang('mpcs::lang.date_and_time')</th>
                        <th>@lang('superadmin::lang.business_type')</th>
                        <th>@lang('superadmin::lang.added_by')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade business_type_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>