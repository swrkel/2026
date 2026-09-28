@extends('layouts.app')

@section('title', 'Create Cash Flow Forecast')

@section('content')

<section class="content-header">
    <h1>
        Create Cash Flow Forecast
    </h1>
</section>

<section class="content">

    <form method="POST"
          action="{{ route('finance.cash_flow_forecast.store') }}">

        @csrf

        <div class="box box-primary">

            <div class="box-body">

                <div class="row">

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Forecast Type</label>

                            <select name="forecast_type"
                                    class="form-control"
                                    required>

                                <option value="inflow">
                                    Inflow
                                </option>

                                <option value="outflow">
                                    Outflow
                                </option>

                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Module</label>

                            <input type="text"
                                   name="module"
                                   class="form-control">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Category</label>

                            <input type="text"
                                   name="category"
                                   class="form-control">
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Subject</label>

                            <input type="text"
                                   name="subject"
                                   class="form-control"
                                   required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Expected Date</label>

                            <input type="date"
                                   name="expected_date"
                                   class="form-control"
                                   required>
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Expected Amount</label>

                            <input type="number"
                                   step="0.01"
                                   name="expected_amount"
                                   class="form-control"
                                   required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Probability %</label>

                            <input type="number"
                                   step="0.01"
                                   name="probability_percent"
                                   class="form-control">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status</label>

                            <select name="status"
                                    class="form-control">

                                <option value="pending">
                                    Pending
                                </option>

                                <option value="confirmed">
                                    Confirmed
                                </option>

                                <option value="completed">
                                    Completed
                                </option>

                                <option value="cancelled">
                                    Cancelled
                                </option>

                            </select>
                        </div>
                    </div>

                </div>

                <div class="form-group">
                    <label>Description</label>

                    <textarea name="description"
                              class="form-control"
                              rows="4"></textarea>
                </div>

            </div>

            <div class="box-footer">
                <button type="submit"
                        class="btn btn-primary">
                    Save Forecast
                </button>
            </div>

        </div>

    </form>

</section>

@endsection