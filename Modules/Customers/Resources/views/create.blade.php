@extends('layouts.app')

@section('title', __('customers::lang.add_customer'))

@section('content')

<section class="content-header">
    <h1>
        @lang('customers::lang.add_customer')
        <small>Customer Master Record</small>
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">Add Customer</h3>

            <div class="box-tools">
                <a href="{{ route('customers.index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Back to Customer Register
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('customers.store') }}">
            @csrf

            <div class="box-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin-bottom: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Customer Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   value="{{ old('name') }}"
                                   required
                                   placeholder="Enter customer name">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Mobile</label>
                            <input type="text"
                                   name="mobile"
                                   class="form-control"
                                   value="{{ old('mobile') }}"
                                   placeholder="Enter mobile number">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="{{ old('email') }}"
                                   placeholder="Enter email address">
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Credit Limit</label>
                            <input type="text"
                                   name="credit_limit"
                                   class="form-control input_number"
                                   value="{{ old('credit_limit', '0.00') }}"
                                   placeholder="0.00"
                                   style="text-align: right;">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Customer Passcode <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="customer_passcode"
                                   class="form-control"
                                   value="{{ old('customer_passcode', $generatedPasscode ?? '') }}"
                                   maxlength="4"
                                   pattern="[0-9]{4}"
                                   required
                                   placeholder="4 digit passcode">
                            <small class="text-muted">Auto generated unique 4 digit passcode for Distribution Dealer login.</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text"
                                   name="city"
                                   class="form-control"
                                   value="{{ old('city') }}"
                                   placeholder="Enter city">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="active" class="form-control">
                                <option value="1" {{ old('active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('active') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address_line_1"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Enter address">{{ old('address_line_1') }}</textarea>
                        </div>
                    </div>

                </div>

            </div>

            <div class="box-footer text-right">
                <a href="{{ route('customers.index') }}" class="btn btn-default">
                    Cancel
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save Customer
                </button>
            </div>

        </form>

    </div>

</section>

@endsection