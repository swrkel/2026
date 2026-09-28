<div class="supplier-ledger-filters row g-2 mb-3">
    <div class="col-md-3 col-sm-6">
        {!! Form::label('start_date', __('suppliers::lang.start_date')) !!}
        {!! Form::text('start_date', request('start_date'), ['class' => 'form-control supplier-date-input', 'placeholder' => 'YYYY-MM-DD']) !!}
    </div>
    <div class="col-md-3 col-sm-6">
        {!! Form::label('end_date', __('suppliers::lang.end_date')) !!}
        {!! Form::text('end_date', request('end_date'), ['class' => 'form-control supplier-date-input', 'placeholder' => 'YYYY-MM-DD']) !!}
    </div>
    <div class="col-md-3 col-sm-6">
        {!! Form::label('location_id', __('suppliers::lang.business_location')) !!}
        {!! Form::select('location_id', $business_locations ?? [], request('location_id'), ['class' => 'form-control select2', 'placeholder' => __('suppliers::lang.all')]) !!}
    </div>
    <div class="col-md-3 col-sm-6 d-flex align-items-end">
        <button type="button" class="btn btn-primary w-100 supplier-ledger-filter-apply">@lang('suppliers::lang.filter')</button>
    </div>
</div>
