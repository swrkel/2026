@extends('layouts.app')

@section('title', 'Create AML / KYC Profile')

@section('content')

<section class="content-header">

    <h1>
        Create AML / KYC Profile
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <form method="POST"
              action="{{ route('loan.aml_kyc.store') }}">

            @csrf

            <div class="box-body">

                <div class="row">

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Borrower
                            </label>

                            <select name="borrower_id"
                                    class="form-control"
                                    required>

                                <option value="">
                                    Select Borrower
                                </option>

                                @foreach($borrowers as $borrower)

                                    <option value="{{ $borrower->id }}">

                                        {{ $borrower->name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Customer Type
                            </label>

                            <select name="customer_type"
                                    class="form-control">

                                <option value="individual">
                                    INDIVIDUAL
                                </option>

                                <option value="company">
                                    COMPANY
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                KYC Status
                            </label>

                            <select name="kyc_status"
                                    class="form-control">

                                <option value="pending">
                                    PENDING
                                </option>

                                <option value="verified">
                                    VERIFIED
                                </option>

                                <option value="rejected">
                                    REJECTED
                                </option>

                                <option value="expired">
                                    EXPIRED
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                AML Status
                            </label>

                            <select name="aml_status"
                                    class="form-control">

                                <option value="clear">
                                    CLEAR
                                </option>

                                <option value="review">
                                    REVIEW
                                </option>

                                <option value="blocked">
                                    BLOCKED
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Risk Rating
                            </label>

                            <select name="risk_rating"
                                    class="form-control">

                                <option value="low">
                                    LOW
                                </option>

                                <option value="medium">
                                    MEDIUM
                                </option>

                                <option value="high">
                                    HIGH
                                </option>

                                <option value="critical">
                                    CRITICAL
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4">

                        <div class="checkbox">

                            <label>

                                <input type="checkbox"
                                       name="pep_flag"
                                       value="1">

                                Politically Exposed Person

                            </label>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="checkbox">

                            <label>

                                <input type="checkbox"
                                       name="sanctions_flag"
                                       value="1">

                                Sanctions Match

                            </label>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="checkbox">

                            <label>

                                <input type="checkbox"
                                       name="adverse_media_flag"
                                       value="1">

                                Adverse Media Match

                            </label>

                        </div>

                    </div>

                </div>

                <div class="form-group">

                    <label>
                        Source of Funds
                    </label>

                    <textarea name="source_of_funds"
                              class="form-control"
                              rows="3"></textarea>

                </div>

                <div class="form-group">

                    <label>
                        Source of Wealth
                    </label>

                    <textarea name="source_of_wealth"
                              class="form-control"
                              rows="3"></textarea>

                </div>

                <div class="row">

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Occupation
                            </label>

                            <input type="text"
                                   name="occupation"
                                   class="form-control">

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Annual Income
                            </label>

                            <input type="number"
                                   step="0.01"
                                   name="annual_income"
                                   class="form-control">

                        </div>

                    </div>

                </div>

                <div class="form-group">

                    <label>
                        Next Review Date
                    </label>

                    <input type="date"
                           name="next_review_date"
                           class="form-control">

                </div>

                <div class="form-group">

                    <label>
                        Compliance Notes
                    </label>

                    <textarea name="compliance_notes"
                              class="form-control"
                              rows="4"></textarea>

                </div>

            </div>

            <div class="box-footer">

                <button type="submit"
                        class="btn btn-primary">

                    <i class="fa fa-save"></i>
                    Save AML / KYC Profile

                </button>

            </div>

        </form>

    </div>

</section>

@endsection