@component('components.filters', ['title' => __('contact_credit_sales.filters')])

    {{-- Date Range --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('card_date_filter', __('contact_credit_sales.date_range') . ':') !!}
            {!! Form::text('card_date_filter', null, [
                'placeholder' => __('contact_credit_sales.select_a_date_range'),
                'class' => 'form-control',
                'id' => 'card_date_filter',
                'readonly'
            ]) !!}
        </div>
    </div>

    {{-- Location --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('card_location', __('contact_credit_sales.location') . ':') !!}
            {!! Form::select('card_location', $business_locations, null, [
                'class' => 'form-control select2',
                'placeholder' => __('contact_credit_sales.all'),
                'id' => 'card_location',
                'style' => 'width:100%;'
            ]) !!}
        </div>
    </div>

    {{-- Invoice No --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('card_invoice_no', __('contact_credit_sales.invoice_no') . ':') !!}
            {!! Form::select('card_invoice_no', $invoices, null, [
                'class' => 'form-control select2',
                'placeholder' => __('contact_credit_sales.all'),
                'id' => 'card_invoice_no',
                'style' => 'width:100%;'
            ]) !!}
        </div>
    </div>

    {{-- Card Type --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('card_type', __('contact_credit_sales.card_type') . ':') !!}
            {!! Form::select('card_type', $card_types, null, [
                'class' => 'form-control select2',
                'placeholder' => __('contact_credit_sales.all'),
                'id' => 'card_type',
                'style' => 'width:100%;'
            ]) !!}
        </div>
    </div>

    {{-- Slip Number --}}
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('slip_no', __('contact_credit_sales.slip_no') . ':') !!}
            {!! Form::select('slip_no', $slip_numbers, null, [
                'class' => 'form-control select2',
                'placeholder' => __('contact_credit_sales.all'),
                'id' => 'slip_no',
                'style' => 'width:100%;'
            ]) !!}
        </div>
    </div>

@endcomponent