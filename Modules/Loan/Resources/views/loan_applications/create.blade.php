@extends('layouts.app')

@section('title', 'Create Loan Application')

@section('content')
<section class="content-header">
    <h1>Create Loan Application <small>Multi Branch Loan Application Entry</small></h1>
</section>

<section class="content loan-application-create-page">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="margin-bottom:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('loan.applications.store') }}">
        @csrf

        <div class="box box-primary erp-form-box">
            <div class="box-header with-border erp-form-header">
                <h3 class="box-title"><i class="fa fa-file-text-o"></i> Application Information</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Application No</label>
                            <input type="text" class="form-control" value="{{ $application_no_preview ?? '' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Application Date *</label>
                            <input type="date" name="application_date" class="form-control" value="{{ old('application_date', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Business Location / Branch *</label>
                            <select name="location_id" class="form-control select2" required>
                                <option value="">Select Business Location</option>
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ (string) old('location_id', $default_location_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Loan Officer</label>
                            <select name="loan_officer_id" class="form-control select2">
                                <option value="">Select Loan Officer</option>
                                @foreach($loan_officers as $id => $name)
                                    <option value="{{ $id }}" {{ (string) old('loan_officer_id', $default_loan_officer_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Loaded from Loan Setup. Logged in user is selected when listed as Loan Officer.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-primary erp-form-box">
            <div class="box-header with-border erp-form-header">
                <h3 class="box-title"><i class="fa fa-user"></i> Customer & Product</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Loan Customer *</label>
                            <a href="{{ url('/loan/customers/create') }}" class="pull-right" target="_blank"><i class="fa fa-plus"></i> Add Loan Customer</a>
                            <select name="customer_id" class="form-control select2" required>
                                <option value="">Select Loan Customer</option>
                                @foreach($customers as $id => $name)
                                    <option value="{{ $id }}" {{ (string) old('customer_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Loan Product *</label>
                            <select name="loan_product_id" id="loan_product_id" class="form-control select2" required>
                                <option value="">Select Loan Product</option>
                                @foreach($loan_products as $product)
                                    <option value="{{ $product->id }}"
                                        data-interest_rate="{{ $product->interest_rate ?? '' }}"
                                        data-duration="{{ $product->duration ?? $product->max_tenure ?? '' }}"
                                        data-frequency="{{ $product->repayment_frequency ?? '' }}"
                                        data-min="{{ $product->min_amount ?? $product->minimum_amount ?? '' }}"
                                        data-max="{{ $product->max_amount ?? $product->maximum_amount ?? '' }}"
                                        {{ (string) old('loan_product_id') === (string) $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-primary erp-form-box">
            <div class="box-header with-border erp-form-header">
                <h3 class="box-title"><i class="fa fa-money"></i> Requested Loan Details</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Requested Amount *</label>
                            <input type="number" step="0.01" name="principal_amount" id="principal_amount" class="form-control text-right" value="{{ old('principal_amount') }}" required>
                            <small class="text-muted" id="product_amount_hint"></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Interest Rate *</label>
                            <input type="number" step="0.01" name="interest_rate" id="interest_rate" class="form-control text-right" value="{{ old('interest_rate') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Interest Type</label>
                            <select name="interest_type" class="form-control">
                                <option value="flat" {{ old('interest_type') == 'flat' ? 'selected' : '' }}>Flat</option>
                                <option value="reducing" {{ old('interest_type') == 'reducing' ? 'selected' : '' }}>Reducing</option>
                                <option value="compound" {{ old('interest_type') == 'compound' ? 'selected' : '' }}>Compound</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Installment Frequency</label>
                            <select name="installment_frequency" id="installment_frequency" class="form-control">
                                @foreach($installment_frequencies as $key => $frequency)
                                    <option value="{{ $key }}" {{ old('installment_frequency') == $key ? 'selected' : '' }}>{{ $frequency }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tenure *</label>
                            <input type="number" name="tenure" id="tenure" class="form-control text-right" value="{{ old('tenure') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tenure Type</label>
                            <select name="tenure_type" class="form-control">
                                <option value="months" {{ old('tenure_type') == 'months' ? 'selected' : '' }}>Months</option>
                                <option value="weeks" {{ old('tenure_type') == 'weeks' ? 'selected' : '' }}>Weeks</option>
                                <option value="years" {{ old('tenure_type') == 'years' ? 'selected' : '' }}>Years</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Loan Purpose</label>
                            <select name="loan_purpose_id" class="form-control select2">
                                <option value="">Select Loan Purpose</option>
                                @foreach($loan_purposes as $id => $name)
                                    <option value="{{ $id }}" {{ (string) old('loan_purpose_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Risk Level</label>
                            <select name="risk_level" class="form-control">
                                @foreach($risk_levels as $key => $risk)
                                    <option value="{{ $key }}" {{ old('risk_level') == $key ? 'selected' : '' }}>{{ $risk }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-primary erp-form-box">
            <div class="box-header with-border erp-form-header">
                <h3 class="box-title"><i class="fa fa-shield"></i> Collateral / Security</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Collateral Type</label>
                            <select name="collateral_type_id" class="form-control select2">
                                <option value="">Select Collateral Type</option>
                                @foreach($collateral_types as $id => $name)
                                    <option value="{{ $id }}" {{ (string) old('collateral_type_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Asset Name</label>
                            <input type="text" name="asset_name" class="form-control" value="{{ old('asset_name') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Estimated Value</label>
                            <input type="number" step="0.01" name="estimated_value" class="form-control text-right" value="{{ old('estimated_value') }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Collateral Description</label>
                    <textarea name="collateral_description" class="form-control" rows="3">{{ old('collateral_description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="box box-primary erp-form-box">
            <div class="box-header with-border erp-form-header">
                <h3 class="box-title"><i class="fa fa-upload"></i> Attachments</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Document Type</label>
                            <select name="document_type" class="form-control">
                                <option value="id_copy">ID Copy</option>
                                <option value="agreement">Loan Agreement</option>
                                <option value="collateral">Collateral Document</option>
                                <option value="guarantor">Guarantor Document</option>
                                <option value="compliance">Compliance Document</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Upload Document</label>
                            <input type="file" name="document_file" id="loan_document_file" class="form-control" accept="image/*,.pdf,.doc,.docx">
                            <small class="text-muted">Image preview will show immediately after upload.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="loan-doc-preview-box">
                            <img id="loan_document_preview" src="" style="display:none; max-width:100%; max-height:145px; border:1px solid #ddd; padding:4px; border-radius:4px;">
                            <div id="loan_document_file_name" class="text-muted" style="padding-top:35px; text-align:center;">No file selected</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-primary erp-form-box">
            <div class="box-header with-border erp-form-header">
                <h3 class="box-title"><i class="fa fa-sticky-note"></i> Notes</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label>Underwriting Notes</label>
                    <textarea name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="box box-solid erp-action-box">
            <div class="box-body text-right">
                <a href="{{ route('loan.applications.index') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a>
                <button type="reset" class="btn btn-warning"><i class="fa fa-refresh"></i> Reset</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Loan Application</button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2').select2({ width: '100%' });
    }

    $('#loan_product_id').on('change', function() {
        var selected = $(this).find(':selected');
        if (selected.data('interest_rate') !== undefined && selected.data('interest_rate') !== '') {
            $('#interest_rate').val(selected.data('interest_rate'));
        }
        if (selected.data('duration') !== undefined && selected.data('duration') !== '') {
            $('#tenure').val(selected.data('duration'));
        }
        if (selected.data('frequency') !== undefined && selected.data('frequency') !== '') {
            $('#installment_frequency').val(selected.data('frequency'));
        }

        var min = selected.data('min');
        var max = selected.data('max');
        var hint = '';
        if (min || max) {
            hint = 'Allowed amount: ' + (min ? min : '-') + ' to ' + (max ? max : '-');
        }
        $('#product_amount_hint').text(hint);
    }).trigger('change');

    $('#loan_document_file').on('change', function(event) {
        var file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
        $('#loan_document_preview').hide().attr('src', '');
        $('#loan_document_file_name').text('No file selected');

        if (!file) {
            return;
        }

        $('#loan_document_file_name').text(file.name);

        if (file.type && file.type.indexOf('image/') === 0) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#loan_document_preview').attr('src', e.target.result).show();
                $('#loan_document_file_name').css('padding-top', '8px');
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>
<style>
.loan-application-create-page .erp-form-box { border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.loan-application-create-page .erp-form-header { background: #f8f9fb; border-bottom: 1px solid #e5e7eb; }
.loan-application-create-page .erp-form-header .box-title { font-weight: 600; }
.loan-application-create-page .form-group label { font-weight: 600; }
.loan-application-create-page .loan-doc-preview-box { min-height: 145px; border: 1px dashed #ccc; border-radius: 6px; background: #fafafa; padding: 8px; }
.loan-application-create-page .erp-action-box { position: sticky; bottom: 0; z-index: 20; box-shadow: 0 -2px 8px rgba(0,0,0,.08); }
@media (max-width: 767px) {
    .loan-application-create-page .erp-action-box .btn { margin-bottom: 5px; width: 100%; }
}
</style>
@endsection
