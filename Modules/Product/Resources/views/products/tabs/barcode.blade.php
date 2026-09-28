<div class="tab-pane" id="product_barcode_tab">
    <div class="row">
        <div class="col-md-4"><div class="form-group">{!! Form::label('barcode_type', __('product::barcode.barcode_type') . ':') !!}{!! Form::select('barcode_type', ['C128' => 'Code 128', 'C39' => 'Code 39', 'EAN13' => 'EAN-13'], null, ['class' => 'form-control']) !!}</div></div>
    </div>
</div>
