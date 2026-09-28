<div class="box box-primary">
    <div class="box-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_date', __('purchase::lang.date') . ':') !!}
                    {!! Form::text('transaction_date', old('transaction_date', date('Y-m-d')), ['class' => 'form-control', 'required']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('supplier_id', __('purchase::lang.supplier') . ':') !!}
                    {!! Form::select('supplier_id', [], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width:100%']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase::lang.business_location') . ':') !!}
                    {!! Form::select('location_id', [], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width:100%']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('status', __('purchase::lang.status') . ':') !!}
                    {!! Form::select('status', ['draft' => 'Draft', 'ordered' => 'Ordered', 'received' => 'Received'], null, ['class' => 'form-control']) !!}
                </div>
            </div>
        </div>
    </div>
</div>
