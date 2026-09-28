@extends('layouts.app')

@section('title', 'General Ledger')

@section('content')

<section class="content-header">
    <h1>
        General Ledger
    </h1>
</section>

<section class="content">

    @include('finance::finance_reports.partials.navigation')

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                Select Account
            </h3>
        </div>

        <div class="box-body">

            <div class="row">

                <div class="col-md-6">

                    <div class="form-group">

                        <label>Account</label>

                        <select id="ledger_account_id" class="form-control">

                            <option value="">
                                Select Account
                            </option>

                            @foreach($accounts as $id => $name)

                                <option value="{{ $id }}">
                                    {{ $name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="form-group">

                        <label>&nbsp;</label>

                        <button type="button"
                                class="btn btn-primary btn-block"
                                onclick="openLedger()">

                            <i class="fa fa-search"></i>
                            Open Ledger

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<script>
    function openLedger() {
        var accountId = document.getElementById('ledger_account_id').value;

        if (!accountId) {
            alert('Please select an account');
            return;
        }

        window.location.href = @json(route('finance.reports.account_ledger.account', ['accountId' => '__ACCOUNT__'])).replace('__ACCOUNT__', accountId);
    }
</script>

@endsection