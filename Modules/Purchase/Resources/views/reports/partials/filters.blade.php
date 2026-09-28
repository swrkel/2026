<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('start_date', __('purchase::lang.start_date')) !!}
            {!! Form::text('start_date', null, ['class' => 'form-control purchase-report-filter', 'placeholder' => 'YYYY-MM-DD']) !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('end_date', __('purchase::lang.end_date')) !!}
            {!! Form::text('end_date', null, ['class' => 'form-control purchase-report-filter', 'placeholder' => 'YYYY-MM-DD']) !!}
        </div>
    </div>
</div>
