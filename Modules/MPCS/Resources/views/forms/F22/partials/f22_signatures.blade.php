<div class="row">
    <div class="col-xs-12">
        <button type="button" class="btn btn-primary pull-right" id="add_f22_signature_btn">
            <i class="fa fa-plus"></i> @lang('messages.add') @lang('membership::lang.authorized_signature')
        </button>
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="f22_signatures_table" style="width: 100%">
                <thead>
                <tr>
                    <th>@lang('messages.action')</th>
                    <th>@lang('membership::lang.date_time')</th>
                    <th>@lang('membership::lang.signature')</th>
                    <th>@lang('membership::lang.added_by')</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade signature_modal" tabindex="-1" role="dialog">
    <div id="signature_modal_content"></div>
</div>

<div id="add_f22_signature_form_template" style="display:none;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">{{ __('messages.add') }} {{ __('membership::lang.authorized_signature') }}</h4>
            </div>
            {!! Form::open([
                'url' => action([\Modules\MPCS\Http\Controllers\F22FormController::class, 'storeF22Signature']),
                'method' => 'post',
                'id' => 'add_f22_signature_form',
                'enctype' => 'multipart/form-data'
            ]) !!}
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('signature', __('membership::lang.signature') . ' *') !!}
                    {!! Form::file('signature', [
                        'class' => 'form-control', 
                        'required',
                        'accept' => 'image/*,.pdf,.doc,.docx',
                        'id' => 'f22_signature_file'
                    ]) !!}
                    
                    <div class="mt-3" id="f22_image_preview_container" style="display:none;">
                        <h6>{{ __('membership::lang.preview') }}:</h6>
                        <div class="border p-2 text-center bg-light rounded">
                            <img id="f22_signature_preview"
                                src=""
                                class="img-fluid mb-2"
                                style="max-height:150px; display:none;">

                            <div id="f22_file_info" class="text-muted" style="display:none;"></div>
                        </div>
                    </div>
                    
                    <small class="help-block mt-2">{{ __('membership::lang.signature_help_text') }}</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                </button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

@push('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            var f22_signatures_table = $('#f22_signatures_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ action([\Modules\MPCS\Http\Controllers\F22FormController::class, 'getF22Signatures']) }}",
                columns: [
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'date_time', name: 'created_at' },
                    { data: 'signature_preview', name: 'signature_preview', orderable: false, searchable: false },
                    { data: 'added_by', name: 'createdBy.first_name' }
                ],
                order: [[1, 'desc']]
            });

            $('#add_f22_signature_btn').on('click', function(e) {
                e.preventDefault();
                var formHtml = $('#add_f22_signature_form_template').html();
                $('#signature_modal_content').html(formHtml);
                $('.signature_modal').modal('show');
            });

            $(document).on('change', '#f22_signature_file', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        if (file.type.startsWith('image/')) {
                            $('#f22_signature_preview').attr('src', e.target.result).show();
                            $('#f22_file_info').hide();
                        } else {
                            $('#f22_signature_preview').hide();
                            $('#f22_file_info').html('<strong>' + file.name + '</strong><br>Size: ' + (file.size / 1024).toFixed(2) + ' KB').show();
                        }
                        $('#f22_image_preview_container').show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#f22_image_preview_container').hide();
                }
            });

            $(document).on('click', '.btn-modal[data-href*="editF22Signature"], .btn-modal[data-href*="viewF22Signature"]', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var url = $btn.data('href');
                
                $btn.prop('disabled', true);
                var $modal = $('.signature_modal');
                $('#signature_modal_content').html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading...</p></div></div></div>');
                $modal.modal('show');
                
                $.ajax({
                    url: url,
                    dataType: 'html',
                    cache: false,
                    success: function(result) {
                        $('#signature_modal_content').html(result);
                        $btn.prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        $modal.modal('hide');
                        toastr.error('Failed to load form. Please try again.');
                        $btn.prop('disabled', false);
                    }
                });
            });

            $(document).on('submit', 'form#add_f22_signature_form, form#edit_f22_signature_form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $submitBtn = $form.find('button[type="submit"]');
                var formData = new FormData(this);
                var method = $form.find('input[name="_method"]').val() || 'POST';
                var url = $form.attr('action');
                
                var originalText = $submitBtn.html();
                $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                
                $.ajax({
                    method: method === 'PUT' ? 'POST' : method,
                    url: url,
                    dataType: 'json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    cache: false,
                    success: function(result) {
                        $submitBtn.prop('disabled', false).html(originalText);
                        
                        if (result.success == true) {
                            toastr.success(result.msg);
                            $('.signature_modal').modal('hide');
                            if ($form.attr('id') === 'add_f22_signature_form') {
                                $form[0].reset();
                                $('#f22_image_preview_container').hide();
                            }
                            setTimeout(function() {
                                f22_signatures_table.ajax.reload(null, false);
                            }, 100);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        var message = 'Something went wrong';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            message = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            message = Object.values(errors).flat().join('<br>');
                        }
                        toastr.error(message);
                        $submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            $(document).on('click', '.delete_signature_btn', function(e) {
                e.preventDefault();
                var url = $(this).data('href');
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((confirmed) => {
                    if (confirmed) {
                        $.ajax({
                            method: 'DELETE',
                            url: url,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    f22_signatures_table.ajax.reload(null, false);
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
