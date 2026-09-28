<div class="modal-dialog" role="document" style="width: 45%;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'mpcs::lang.f14_settings' )</h4>
        </div>

        <div class="modal-body">
            {!! Form::open(['url' => '/mpcs/forms-setting/form14c', 'method' => 'post', 'id' => 'f14_form_setting_form' ]) !!}
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('F14_form_tdate', __( 'lang_v1.transaction_date' ) . ':*') !!}
                        {!! Form::date('F14_form_tdate', !empty($setting) ? $setting->F14_form_tdate :null, ['class' => 'form-control', 'id' => 'F14_form_tdate',
                        'required', 'placeholder' => __( 'lang_v1.transaction_date' ) ]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('F14_form_sn', 'Form Number:*') !!}
                        {!! Form::text('F14_form_sn', !empty($setting) ? $setting->F14_form_sn :null, ['class' => 'form-control', 'id' => 'F14_form_sn',
                        'required', 'placeholder' => __( 'mpcs::lang.form_starting_number' ) ]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('bill_no_form_14', 'Bill No (Form 14):') !!}
                        {!! Form::text('bill_no_form_14', !empty($setting) ? $setting->bill_no_form_14 :null, ['class' => 'form-control', 'id' => 'bill_no_form_14', 'placeholder' => 'Bill No (Form 14)']) !!}
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>

        <div class="clearfix"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            <button type="submit" class="btn btn-primary" form="f14_form_setting_form">@lang( 'messages.update' )</button>
        </div>

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
$(document).ready(function() {
    // Handle form submission
    $('#f14_form_setting_form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.text();
        
        // Disable submit button and show loading
        submitBtn.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                // Show success message
                toastr.success('F14 form settings updated successfully!');
                
                // Close modal
                $('.modal').modal('hide');
                
                // Reload page to show updated values
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                // Show error message
                toastr.error('Error updating F14 form settings. Please try again.');
                
                // Re-enable submit button
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>
