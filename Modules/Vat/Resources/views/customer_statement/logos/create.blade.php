
@php
$alignment = ['Left' => 'Left','Center' => 'Center', 'Right' => 'Right' ];
$text_position = ['below' => 'Below the signature line','above' => 'Above the signature line' ];

@endphp


<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('\Modules\Vat\Http\Controllers\VatStatementLogoController@store'), 'method' =>
    'post', 'id' => 'fleet_logos_add_form','enctype' => 'multipart/form-data', 'class' => 'vat-statement-logo-form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'lang_v1.statement_settings' )</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        <div class="form-group col-sm-6">
          {!! Form::label('statement_date', __( 'fleet::lang.date' ) . ':*') !!}
          {!! Form::date('statement_date', isset($driver) && !empty($driver->statement_date) ? $driver->statement_date : (isset($driver) && !empty($driver->created_at) ? \Carbon\Carbon::parse($driver->created_at)->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d')), ['class' => 'form-control vat-statement-date-picker', 'required', 'autocomplete' => 'off']) !!}
        </div>

        
        <div class="form-group col-sm-12">
          {!! Form::label('image_name', __( 'fleet::lang.image_name' ) . ':*') !!}
          {!! Form::text('image_name', null, ['class' => 'form-control', 'placeholder' => __( 'fleet::lang.image_name')]); !!}
        </div>
        <div class="form-group col-sm-12">
          {!! Form::label('alignment', __( 'fleet::lang.alignment' ) . ':*') !!}
          {!! Form::select('alignment', $alignment, null, ['class' => 'form-control select2', 'placeholder' =>
          __('messages.please_select')]); !!}
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
          {!! Form::file('attachment', ['accept' => 'image/*', 'class' => 'vat-statement-image-input']) !!}
          <div class="is1571-image-preview-wrap" style="margin-top:10px;">
            <img class="is1571-image-preview" style="max-height:120px;max-width:240px;border:1px solid #ddd;padding:4px;display:none;">
          </div>

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
// Local preview: this page no longer depends on the global VAT modal rescue script.
(function(){
    var $modal = $('.fuel_tank_modal');
    $modal.find('.vat-statement-date-picker').each(function(){
        if ($(this).data('datepicker')) { $(this).datepicker('destroy'); }
        if ($.fn.datepicker) {
            $(this).datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
        }
    });
    $modal.off('change.vatStatementLogoPreview', '.vat-statement-image-input')
        .on('change.vatStatementLogoPreview', '.vat-statement-image-input', function () {
            var input = this;
            var $preview = $modal.find('.is1571-image-preview');
            if (!input.files || !input.files[0] || !window.FileReader) {
                $preview.hide().attr('src', '');
                return;
            }
            var reader = new FileReader();
            reader.onload = function (event) {
                $preview.attr('src', event.target.result).show();
            };
            reader.readAsDataURL(input.files[0]);
        });
})();
</script>