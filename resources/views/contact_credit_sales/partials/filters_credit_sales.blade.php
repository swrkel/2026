@component('components.filters', ['title' => __('contact_credit_sales.filters')])

    {{-- Date Range --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('credit_date_filter', __('contact_credit_sales.date_range') . ':') !!}
            {!! Form::text('credit_date_filter', null, [
                'placeholder' => __('contact_credit_sales.select_a_date_range'),
                'class' => 'form-control',
                'id' => 'credit_date_filter',
                'readonly'
            ]) !!}
        </div>
    </div>

    {{-- Location --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('credit_location', __('contact_credit_sales.location') . ':') !!}
            {!! Form::select('credit_location', $business_locations, null, [
                'class' => 'form-control select2',
                'placeholder' => __('contact_credit_sales.all'),
                'id' => 'credit_location',
                'style' => 'width:100%;'
            ]) !!}
        </div>
    </div>

    {{-- Invoice No --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('credit_invoice_no', __('contact_credit_sales.invoice_no') . ':') !!}
            {!! Form::select('credit_invoice_no', $invoices, null, [
                'class' => 'form-control select2',
                'placeholder' => __('contact_credit_sales.all'),
                'id' => 'credit_invoice_no',
                'style' => 'width:100%;'
            ]) !!}
        </div>
    </div>
@endcomponent