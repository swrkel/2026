@extends('layouts.app')

@section('title', 'Edit Loan Application')

@section('content')

<section class="content-header">

    <h1>
        Edit Loan Application
        <small>
            Enterprise Underwriting Revision Workflow
        </small>
    </h1>

</section>

<section class="content">

    <form method="POST"
          enctype="multipart/form-data"
          action="/loan/loan-applications/{{ $application->id }}/update">

        @csrf

        <!-- ===================================================== -->
        <!-- KPI OVERVIEW -->
        <!-- ===================================================== -->

        <div class="row">

            <div class="col-md-3 col-sm-6 col-xs-12">

                <div class="info-box bg-aqua">

                    <span class="info-box-icon">

                        <i class="fa fa-file-text"></i>

                    </span>

                    <div class="info-box-content">

                        <span class="info-box-text">

                            Application No

                        </span>

                        <span class="info-box-number"
                              style="font-size:14px;">

                            {{ $application->application_no }}

                        </span>

                    </div>

                </div>

            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">

                <div class="info-box bg-green">

                    <span class="info-box-icon">

                        <i class="fa fa-money"></i>

                    </span>

                    <div class="info-box-content">

                        <span class="info-box-text">

                            Loan Amount

                        </span>

                        <span class="info-box-number">

                            {{ number_format($application->principal_amount, 2) }}

                        </span>

                    </div>

                </div>

            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">

                <div class="info-box bg-yellow">

                    <span class="info-box-icon">

                        <i class="fa fa-warning"></i>

                    </span>

                    <div class="info-box-content">

                        <span class="info-box-text">

                            Risk Level

                        </span>

                        <span class="info-box-number">

                            {{ ucfirst($application->risk_level ?? 'low') }}

                        </span>

                    </div>

                </div>

            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">

                <div class="info-box bg-red">

                    <span class="info-box-icon">

                        <i class="fa fa-shield"></i>

                    </span>

                    <div class="info-box-content">

                        <span class="info-box-text">

                            Workflow Stage

                        </span>

                        <span class="info-box-number"
                              style="font-size:13px;">

                            {{ ucwords(str_replace('_', ' ', $application->workflow_stage ?? 'application_submitted')) }}

                        </span>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- APPLICATION DETAILS -->
        <!-- ===================================================== -->

        <div class="box box-primary">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Application Revision

                </h3>

            </div>

            <div class="box-body">

                <div class="row">

                    <!-- LOAN CUSTOMER -->

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Loan Customer *
                            </label>
                            <a href="/loan/customers/create" class="pull-right" target="_blank">
                                <i class="fa fa-plus"></i> Add Loan Customer
                            </a>

                            <select name="customer_id"
                                    class="form-control select2"
                                    required>

                                @foreach($customers as $id => $name)

                                    <option value="{{ $id }}"
                                        {{ $application->customer_id == $id ? 'selected' : '' }}>

                                        {{ $name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <!-- PRODUCT -->

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Loan Product *
                            </label>

                            <select name="loan_product_id"
                                    id="loan_product_id"
                                    class="form-control select2"
                                    required>

                                @foreach($loan_products as $product)

                                    <option
                                        value="{{ $product->id }}"
                                        data-interest_rate="{{ $product->interest_rate ?? '' }}"
                                        data-duration="{{ $product->duration ?? '' }}"
                                        data-frequency="{{ $product->repayment_frequency ?? '' }}"
                                        {{ $application->loan_product_id == $product->id ? 'selected' : '' }}>

                                        {{ $product->name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

                <!-- ============================================= -->

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Principal Amount *
                            </label>

                            <input type="number"
                                   step="0.01"
                                   name="principal_amount"
                                   value="{{ $application->principal_amount }}"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Interest Rate *
                            </label>

                            <input type="number"
                                   step="0.01"
                                   name="interest_rate"
                                   id="interest_rate"
                                   value="{{ $application->interest_rate }}"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Interest Type *
                            </label>

                            <select name="interest_type"
                                    class="form-control">

                                <option value="flat"
                                    {{ $application->interest_type == 'flat' ? 'selected' : '' }}>

                                    Flat

                                </option>

                                <option value="reducing"
                                    {{ $application->interest_type == 'reducing' ? 'selected' : '' }}>

                                    Reducing

                                </option>

                                <option value="compound"
                                    {{ $application->interest_type == 'compound' ? 'selected' : '' }}>

                                    Compound

                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <!-- ============================================= -->

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Tenure *
                            </label>

                            <input type="number"
                                   name="tenure"
                                   id="tenure"
                                   value="{{ $application->tenure }}"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Tenure Type *
                            </label>

                            <select name="tenure_type"
                                    class="form-control">

                                <option value="months"
                                    {{ $application->tenure_type == 'months' ? 'selected' : '' }}>

                                    Months

                                </option>

                                <option value="weeks"
                                    {{ $application->tenure_type == 'weeks' ? 'selected' : '' }}>

                                    Weeks

                                </option>

                                <option value="years"
                                    {{ $application->tenure_type == 'years' ? 'selected' : '' }}>

                                    Years

                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Installment Frequency
                            </label>

                            <select name="installment_frequency"
                                    id="installment_frequency"
                                    class="form-control">

                                <option value="daily"
                                    {{ $application->installment_frequency == 'daily' ? 'selected' : '' }}>

                                    Daily

                                </option>

                                <option value="weekly"
                                    {{ $application->installment_frequency == 'weekly' ? 'selected' : '' }}>

                                    Weekly

                                </option>

                                <option value="biweekly"
                                    {{ $application->installment_frequency == 'biweekly' ? 'selected' : '' }}>

                                    Biweekly

                                </option>

                                <option value="monthly"
                                    {{ $application->installment_frequency == 'monthly' ? 'selected' : '' }}>

                                    Monthly

                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Application Date *
                            </label>

                            <input type="date"
                                   name="application_date"
                                   value="{{ $application->application_date }}"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- GOVERNANCE -->
        <!-- ===================================================== -->

        <div class="box box-warning">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Governance & Risk Controls

                </h3>

            </div>

            <div class="box-body">

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Risk Level
                            </label>

                            <select name="risk_level"
                                    class="form-control">

                                <option value="low"
                                    {{ ($application->risk_level ?? '') == 'low' ? 'selected' : '' }}>

                                    Low

                                </option>

                                <option value="medium"
                                    {{ ($application->risk_level ?? '') == 'medium' ? 'selected' : '' }}>

                                    Medium

                                </option>

                                <option value="high"
                                    {{ ($application->risk_level ?? '') == 'high' ? 'selected' : '' }}>

                                    High

                                </option>

                                <option value="critical"
                                    {{ ($application->risk_level ?? '') == 'critical' ? 'selected' : '' }}>

                                    Critical

                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="checkbox">

                            <label>

                                <input type="checkbox"
                                       name="requires_compliance_review"
                                       value="1">

                                Requires Compliance Review

                            </label>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="checkbox">

                            <label>

                                <input type="checkbox"
                                       name="high_priority_collection"
                                       value="1">

                                High Priority Collection Monitoring

                            </label>

                        </div>

                    </div>

                </div>

                <div class="form-group">

                    <label>
                        Underwriting Notes
                    </label>

                    <textarea name="notes"
                              class="form-control"
                              rows="5">{{ $application->notes }}</textarea>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- COLLATERAL -->
        <!-- ===================================================== -->

        <div class="box box-danger">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Collateral Information

                </h3>

            </div>

            <div class="box-body">

                <div class="row">

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Asset Name
                            </label>

                            <input type="text"
                                   name="asset_name"
                                   value="{{ $application->asset_name }}"
                                   class="form-control">

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Estimated Value
                            </label>

                            <input type="number"
                                   step="0.01"
                                   name="estimated_value"
                                   value="{{ $application->estimated_value }}"
                                   class="form-control">

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- GUARANTOR -->
        <!-- ===================================================== -->

        <div class="box box-success">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Guarantor Information

                </h3>

            </div>

            <div class="box-body">

                <div class="row">

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Guarantor Name
                            </label>

                            <input type="text"
                                   name="guarantor_name"
                                   value="{{ $application->guarantor_name }}"
                                   class="form-control">

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Guarantor Phone
                            </label>

                            <input type="text"
                                   name="guarantor_phone"
                                   value="{{ $application->guarantor_phone }}"
                                   class="form-control">

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- DOCUMENTS -->
        <!-- ===================================================== -->

        <div class="box box-info">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Compliance Documents

                </h3>

            </div>

            <div class="box-body">

                <div class="row">

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Upload Additional Document
                            </label>

                            <input type="file"
                                   name="document_file"
                                   class="form-control">

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Document Type
                            </label>

                            <select name="document_type"
                                    class="form-control">

                                <option value="id_copy">
                                    ID Copy
                                </option>

                                <option value="agreement">
                                    Loan Agreement
                                </option>

                                <option value="collateral">
                                    Collateral Document
                                </option>

                                <option value="guarantor">
                                    Guarantor Document
                                </option>

                                <option value="compliance">
                                    Compliance Document
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- SUBMIT -->
        <!-- ===================================================== -->

        <div class="text-right">

            <button type="submit"
                    class="btn btn-primary btn-lg">

                <i class="fa fa-save"></i>

                Update Loan Application

            </button>

        </div>

    </form>

</section>

@endsection

@section('javascript')

<script>

$(document).ready(function() {

    /*
    |--------------------------------------------------------------------------
    | Product Auto Configuration
    |--------------------------------------------------------------------------
    */

    $('#loan_product_id').change(function() {

        let selected = $(this).find(':selected');

        $('#interest_rate').val(
            selected.data('interest_rate')
        );

        $('#tenure').val(
            selected.data('duration')
        );

        $('#installment_frequency').val(
            selected.data('frequency')
        );

    });

});

</script>

@endsection