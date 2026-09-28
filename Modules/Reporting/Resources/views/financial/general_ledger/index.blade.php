@extends('layouts.app')

@section('title', __('General Ledger'))

@section('content')

<section class="content-header">
    <h1>
        General Ledger
        <small>Account-wise transaction history</small>
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-book"></i>
                General Ledger Filter
            </h3>
        </div>

        <div class="box-body">

            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Account</label>

                        <select id="gl_account_id"
                                class="form-control select2"
                                style="width: 100%;">

                            <option value="">Select Account</option>

                            @foreach($accounts as $account_id => $account_name)
                                <option value="{{ $account_id }}">
                                    {{ $account_name }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Branch</label>

                        <select id="gl_location_id"
                                class="form-control select2"
                                style="width: 100%;">

                            <option value="all">All Branches</option>

                            @foreach($locations as $location_id => $location_name)
                                <option value="{{ $location_id }}">
                                    {{ $location_name }} - {{ $location_id }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="text"
                               id="gl_from_date"
                               class="form-control datepicker"
                               placeholder="From Date">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="text"
                               id="gl_to_date"
                               class="form-control datepicker"
                               placeholder="To Date">
                    </div>
                </div>

                <div class="col-md-1">
                    <label>&nbsp;</label>

                    <button type="button"
                            id="generate_gl"
                            class="btn btn-primary btn-block">

                        <i class="fa fa-search"></i>

                    </button>
                </div>

            </div>

        </div>

    </div>

</section>

@endsection

@section('javascript')
<script>
    $(document).ready(function () {

        $('.select2').select2();

        $('#generate_gl').on('click', function () {

            var account_id = $('#gl_account_id').val();

            if (!account_id) {
                alert('Please select an account.');
                return;
            }

            var location_id = $('#gl_location_id').val();
            var from_date = $('#gl_from_date').val();
            var to_date = $('#gl_to_date').val();

            var url = "{{ url('/reporting/general-ledger/account') }}/" + account_id;

            var params = [];

            if (location_id) {
                params.push('location_id=' + encodeURIComponent(location_id));
            }

            if (from_date) {
                params.push('from_date=' + encodeURIComponent(from_date));
            }

            if (to_date) {
                params.push('to_date=' + encodeURIComponent(to_date));
            }

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            window.location.href = url;
        });

    });
</script>
@endsection