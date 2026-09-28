
@php
    $loan_show_contact_type = \App\Utils\ModuleUtil::hasThePermissionInSubscription(session('business.id'), 'loan_show_contact_type');
    $col_class = $loan_show_contact_type ? 'col-md-3' : 'col-md-4';
@endphp

<div class="row">
    @if($loan_show_contact_type)
        <div class="col-md-3">
            <div class="form-group">
                <label for="contact_type" class="control-label">{{ trans_choice('loan::lang.contact', 1) }}
                    {{ trans_choice('loan::lang.type', 1) }}</label>
                <select class="form-control @error('contact_type') is-invalid @enderror" name="contact_type" id="contact_type"
                    required>
                    <option value="">Select</option>
                    <option value = "customer" {{ old('contact_type', isset($loan) ? $loan->contact_type : '') == 'customer' ? 'selected' : '' }}>
                        Customer
                    </option>
                    <option value = "supplier" {{ old('contact_type', isset($loan) ? $loan->contact_type : '') == 'supplier' ? 'selected' : '' }}>
                        Supplier
                    </option>
                </select>
                @error('contact_type')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
    @else
        <input type="hidden" name="contact_type" id="contact_type" value="{{ old('contact_type', isset($loan) ? $loan->contact_type : 'customer') }}">
    @endif
    <div class="{{ $col_class }}">
        {!! Form::label('contact_id', __('contact.contact'). ':', []) !!}
        {!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2',
        'placeholder' => __('lang_v1.all'), 'style' => 'width: 100%;']) !!}
    </div>
    <div class="{{ $col_class }}" id="location_filter">
        <div class="form-group">
            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
            {!! Form::select('location_id', $business_locations, null, ['class' =>
            'form-control select2',
            'style' => 'width:100%']); !!}
        </div>
    </div>
    <div class="{{ $col_class }}">
      <div class="form-group">
          {!! Form::label('loan_product_id', __('loan::lang.loan_product') . ':') !!}
          {!! Form::select('loan_product_id', $loan_products, null, ['class' => 'form-control select2 loan_product_id',
          'style' =>
          'width:100%', 'id' => 'loan_product_id', 'placeholder' => __('lang_v1.all')]); !!}
          </div>
      </div>
      
    
</div>


