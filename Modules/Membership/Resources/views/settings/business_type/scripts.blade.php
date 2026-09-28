
<script>
window.MembershipBusinessTypeModal = window.MembershipBusinessTypeModal || {
    close: function () {
        var $modal = $('.business_type_modal');
        $modal.modal('hide');
        $modal.removeClass('in show').attr('aria-hidden', 'true').hide();
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }
};
$(document).on('click', '.business_type_modal [data-dismiss="modal"], .business_type_modal [data-bs-dismiss="modal"]', function () {
    window.MembershipBusinessTypeModal.close();
});
</script>

@push('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            var membership_business_types_table = $('#membership_business_types_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{url('/membership/setting/business-types')}}",
                columns: [
                    { data: 'date_time', name: 'created_at' },
                    { data: 'business_type', name: 'business_type' },
                    { data: 'user', name: 'user', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });

            $(document).on('click', '.add-business-type-row', function() {
                var newRow = $('<div class="business-type-row" style="display:flex; align-items:center; margin-bottom:8px;">' +
                    '<input type="text" class="form-control business-type-input" placeholder="{{ __("membership::lang.business_type") }}" autocomplete="off" style="border-radius:6px;">' +
                    '<button type="button" class="btn btn-danger remove-business-type-row" style="margin-left:8px; border-radius:6px; width:40px; height:34px; padding:0; font-size:16px; flex-shrink:0;">' +
                    '<i class="fa fa-minus"></i></button>' +
                    '</div>');
                $('#business_type_rows_container').append(newRow);
            });
            $(document).on('click', '.remove-business-type-row', function() {
                var $rows = $('#business_type_rows_container .business-type-row');
                if ($rows.length > 1) {
                    $(this).closest('.business-type-row').remove();
                }
            });
            $(document).on('submit', 'form#add_business_type_form', function(e) {
                e.preventDefault();
                var values = [];
                $('#business_type_rows_container .business-type-input').each(function() {
                    var v = $(this).val().trim();
                    if (v) values.push(v);
                });
                if (values.length === 0) {
                    toastr.error('{{ __("membership::lang.please_select") }}: {{ __("membership::lang.business_type") }}');
                    return;
                }
                $('#add_form_business_type').val(values.join(', '));
                var $form = $(this);
                $.ajax({
                    method: 'POST',
                    url: $form.attr('action'),
                    dataType: 'json',
                    data: $form.serialize(),
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            window.MembershipBusinessTypeModal && window.MembershipBusinessTypeModal.close();
                            membership_business_types_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('click', '.add-edit-business-type-row', function() {
                var rowHtml = '<div class="form-group business-type-edit-row">' +
                    '<label>{{ __("membership::lang.business_type") }}:*</label>' +
                    '<div class="input-group">' +
                    '<input type="text" class="form-control business-type-edit-input" placeholder="{{ __("membership::lang.business_type") }}" autocomplete="off">' +
                    '<span class="input-group-btn">' +
                    '<button type="button" class="btn btn-danger remove-edit-business-type-row" title="{{ __("messages.delete") }}" style="height: 34px;"><i class="fa fa-minus"></i></button>' +
                    '<button type="button" class="btn btn-primary add-edit-business-type-row" title="{{ __("messages.add") }}" style="height: 34px;"><i class="fa fa-plus"></i></button>' +
                    '</span></div></div>';
                $('#edit_business_type_rows_container').append(rowHtml);
            });
            $(document).on('click', '.remove-edit-business-type-row', function() {
                var $rows = $('#edit_business_type_rows_container .business-type-edit-row');
                if ($rows.length > 1) {
                    $(this).closest('.business-type-edit-row').remove();
                }
            });
            $(document).on('submit', 'form#edit_business_type_form', function(e) {
                e.preventDefault();
                var $inputs = $('#edit_business_type_rows_container .business-type-edit-input');
                var primary = $inputs.eq(0).val().trim();
                if (!primary) {
                    toastr.error('{{ __("membership::lang.please_select") }}: {{ __("membership::lang.business_type") }}');
                    return;
                }
                var additional = [];
                $inputs.each(function(i) {
                    if (i === 0) return;
                    var v = $(this).val().trim();
                    if (v) additional.push(v);
                });
                $('#edit_form_business_type').val(primary);
                $('#edit_form_additional_business_types').val(additional.join(', '));
                var $form = $(this);
                $.ajax({
                    method: 'POST',
                    url: $form.attr('action'),
                    dataType: 'json',
                    data: $form.serialize(),
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            window.MembershipBusinessTypeModal && window.MembershipBusinessTypeModal.close();
                            membership_business_types_table.ajax.reload();
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
                        $.ajax({
                            method: "DELETE",
                            url: $(this).data('href'),
                            dataType: "json",
                            data: { "_token": "{{csrf_token()}}" },
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    membership_business_types_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', 'button.business_type_disable', function() {
                swal({
                    title: LANG.sure,
                    text: '{{ __("membership::lang.disable_business_type_confirmation") }}',
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDisable) => {
                    if (willDisable) {
                        $.ajax({
                            method: "PUT",
                            url: $(this).data('href'),
                            dataType: "json",
                            data: { "_token": "{{csrf_token()}}" },
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    membership_business_types_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.view-users', function(e) {
                e.preventDefault();
                var businessTypeId = $(this).data('business-type-id');
                var businessTypeName = $(this).data('business-type');

                $('#users_modal .modal-title').text('Users - ' + businessTypeName);

                $.ajax({
                    url: '/membership/setting/business-types/' + businessTypeId + '/users',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        var tbody = $('#users_list_body');
                        tbody.empty();

                        if (data.length === 0) {
                            tbody.append('<tr><td colspan="3" class="text-center">@lang("membership.no_users_found")</td></tr>');
                        } else {
                            $.each(data, function(index, user) {
                                tbody.append(
                                    '<tr>' +
                                    '<td>' + user.username + '</td>' +
                                    '<td>' + user.full_name + '</td>' +
                                    '<td>' + user.email + '</td>' +
                                    '</tr>'
                                );
                            });
                        }

                        $('#users_modal').modal('show');
                    },
                    error: function() {
                        toastr.error('@lang("messages.something_went_wrong")');
                    }
                });
            });
        });
    </script>
@endpush
