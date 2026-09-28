
@php
$alignment = ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'];
$text_position = ['below' => 'Below the signature line', 'above' => 'Above the signature line'];


@endphp


<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('CustomerStatementLogoController@store'), 'method' =>
    'post', 'id' => 'fleet_logos_add_form','enctype' => 'multipart/form-data', 'class' => 'customer-statement-logo-form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'lang_v1.customer_statement_logo' )</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        
        <div class="form-group col-sm-12">
          {!! Form::label('image_name', __( 'fleet::lang.image_name' ) . ':*') !!}
          {!! Form::text('image_name', null, ['class' => 'form-control', 'placeholder' => __( 'fleet::lang.image_name')]); !!}
        </div>
        <div class="form-group col-sm-12">
          {!! Form::label('alignment', __( 'fleet::lang.alignment' ) . ':*') !!}
          {!! Form::select('alignment', $alignment, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'required', 'placeholder' => __('messages.please_select')]); !!}
        </div>
        
        <div class="form-group  col-sm-6">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('business_name', 1, false, ['class' => 'input-icheck']); !!}
                    {{__('lang_v1.business_name')}}
                </label>
            </div>
        </div>
        
        <div class="form-group  col-sm-6">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('business_address', 1, false, ['class' => 'input-icheck']); !!}
                    {{__('lang_v1.business_address')}}
                </label>
            </div>
        </div>
        
        <div class="form-group  col-sm-6">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('contact_no', 1, false, ['class' => 'input-icheck']); !!}
                    {{__('lang_v1.contact_no')}}
                </label>
            </div>
        </div>
        
        <div class="form-group  col-sm-6">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('email', 1, false, ['class' => 'input-icheck']); !!}
                    {{__('lang_v1.email')}}
                </label>
            </div>
        </div>
        
        <div class="form-group  col-sm-6">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('mobile_no', 1, false, ['class' => 'input-icheck']); !!}
                    {{__('lang_v1.mobile_no')}}
                </label>
            </div>
        </div>
        
        <div class="form-group col-sm-8">
          {!! Form::label('statement_note', __( 'vat::lang.statement_note' ) . ':*') !!}
          {!! Form::text('statement_note', null, ['class' => 'form-control', 'placeholder' =>
          __('messages.please_select')]); !!}
        </div>
        
        <div class="form-group col-sm-4">
          {!! Form::label('text_position', __( 'vat::lang.text_position' ) . ':*') !!}
          {!! Form::select('text_position', $text_position, null, ['class' => 'form-control select2', 'required' ,'placeholder' =>
          __('messages.please_select')]); !!}
        </div>
            
        
        <div class="form-group col-sm-12">
          {!! Form::label('attachment', __( 'fleet::lang.add_image' )) !!}
          {!! Form::file('attachment', ['accept' => 'image/*']) !!}

        </div>
        
      </div>

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->


<script>
$(document).off('submit', '.customer-statement-logo-form').on('submit', '.customer-statement-logo-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var submit_button = form.find('button[type="submit"]');
    submit_button.prop('disabled', true);

    $.ajax({
        method: form.attr('method') || 'POST',
        url: form.attr('action'),
        data: new FormData(this),
        dataType: 'json',
        contentType: false,
        processData: false,
        success: function(result) {
            if (result.success) {
                toastr.success(result.msg);
                $('.view_modal').modal('hide');
                if (typeof logos_table !== 'undefined') {
                    logos_table.ajax.reload(null, false);
                }
            } else {
                toastr.error(result.msg || LANG.something_went_wrong);
            }
        },
        error: function(xhr) {
            var msg = LANG.something_went_wrong;
            if (xhr.responseJSON && xhr.responseJSON.msg) {
                msg = xhr.responseJSON.msg;
            }
            toastr.error(msg);
        },
        complete: function() {
            submit_button.prop('disabled', false);
        }
    });
});

var $modalParent = $('.customer-statement-logo-form').closest('.modal');
if (!$modalParent.length) { $modalParent = $('.view_modal:visible, .customer_statement_modal:visible').first(); }
$('.customer-statement-logo-form .select2').select2({ width: '100%', dropdownParent: $modalParent.length ? $modalParent : $(document.body) });
if ($.fn.iCheck) {
    $('.input-icheck').iCheck({ checkboxClass: 'icheckbox_square-blue', radioClass: 'iradio_square-blue' });
}
</script>