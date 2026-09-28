@component('components.filters', ['title' => __('contact_credit_sales.filters')])

    {{-- Date Range First --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('date_filter', __('contact_credit_sales.date_range') . ':') !!}
            {!! Form::text('date_filter', null, [
                'placeholder' => __('contact_credit_sales.select_a_date_range'),
                'class' => 'form-control',
                'id' => 'date_filter'
            ]) !!}
        </div>
    </div>

    {{-- Location --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('location', __('contact_credit_sales.location') . ':') !!}
            {!! Form::select('location', $business_locations, null, [
                'class' => 'form-control select2',
                'placeholder' => __('contact_credit_sales.all'),
                'id' => 'location',
                'style' => 'width:100%;'
            ]) !!}
        </div>
    </div>

    {{-- Customer --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('customer_id', __('contact_credit_sales.customer') . ':') !!}
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                {!! Form::select('customer_id', $customers, null, [
                    'class' => 'form-control select2',
                    'placeholder' => __('contact_credit_sales.all'),
                    'id' => 'customer_id',
                    'style' => 'width:100%;'
                ]) !!}
            </div>
        </div>
    </div>

    {{-- Invoice No --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('invoice_no', __('contact_credit_sales.invoice_no') . ':') !!}
            {!! Form::select('invoice_no', $invoices, null, [
                'class' => 'form-control select2',
                'placeholder' => __('contact_credit_sales.all'),
                'id' => 'invoice_no',
                'style' => 'width:100%; !important'
            ]) !!}
        </div>
    </div>

@endcomponent
