@extends('layouts.app')

@section('title', 'Create Watchlist Entry')

@section('content')

<section class="content-header">

    <h1>
        Create Watchlist Entry
    </h1>

</section>

<section class="content">

    <div class="box box-danger">

        <form method="POST"
              action="{{ route('loan.sanctions_watchlist.store') }}">

            @csrf

            <div class="box-body">

                <div class="row">

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                AML / KYC Profile
                            </label>

                            <select name="aml_kyc_profile_id"
                                    class="form-control"
                                    required>

                                <option value="">
                                    Select Profile
                                </option>

                                @foreach($profiles as $profile)

                                    <option value="{{ $profile->id }}">

                                        {{ $profile->borrower->name ?? 'Unknown Borrower' }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Watchlist Type
                            </label>

                            <select name="watchlist_type"
                                    class="form-control">

                                <option value="ofac">
                                    OFAC
                                </option>

                                <option value="un">
                                    UN SANCTIONS
                                </option>

                                <option value="eu">
                                    EU SANCTIONS
                                </option>

                                <option value="internal">
                                    INTERNAL BLACKLIST
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Entity Name
                            </label>

                            <input type="text"
                                   name="entity_name"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Country
                            </label>

                            <input type="text"
                                   name="country"
                                   class="form-control">

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Match Score (%)
                            </label>

                            <input type="number"
                                   name="match_score"
                                   class="form-control"
                                   value="0">

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Risk Level
                            </label>

                            <select name="risk_level"
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

                            </select>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Screening Status
                            </label>

                            <select name="screening_status"
                                    class="form-control">

                                <option value="pending">
                                    PENDING
                                </option>

                                <option value="cleared">
                                    CLEARED
                                </option>

                                <option value="blocked">
                                    BLOCKED
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="form-group">

                    <label>
                        Remarks
                    </label>

                    <textarea name="remarks"
                              class="form-control"
                              rows="4"></textarea>

                </div>

            </div>

            <div class="box-footer">

                <button type="submit"
                        class="btn btn-danger">

                    <i class="fa fa-save"></i>
                    Save Watchlist Entry

                </button>

            </div>

        </form>

    </div>

</section>

@endsection