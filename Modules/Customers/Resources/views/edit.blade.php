@extends('customers::layouts.action', ['title' => 'Edit Customer'])

@section('customer_action_body')
<form method="POST" action="{{ route('customers.update', $customer->id) }}">
    @csrf
    @method('PUT')

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
                <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Mobile</label>
                <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $customer->mobile) }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Credit Limit</label>
                <input type="text" name="credit_limit" class="form-control input_number" value="{{ old('credit_limit', number_format((float) $customer->credit_limit, 2)) }}" style="text-align:right;">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Customer Passcode <span class="text-danger">*</span></label>
                <input type="text" name="customer_passcode" class="form-control" value="{{ old('customer_passcode', $customer->customer_passcode) }}" maxlength="4" pattern="[0-9]{4}" required>
                <small class="text-muted">Used for Distribution Dealer login.</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Status</label>
                <select name="active" class="form-control">
                    <option value="1" {{ old('active', $customer->active) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('active', $customer->active) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div class="col-md-8">
            <div class="form-group">
                <label>Address</label>
                <textarea name="address_line_1" class="form-control" rows="3">{{ old('address_line_1', $customer->address_line_1) }}</textarea>
            </div>
        </div>
    </div>

    <div class="text-right">
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Customer</button>
    </div>
</form>
@endsection
