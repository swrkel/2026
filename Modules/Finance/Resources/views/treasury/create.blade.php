@extends('layouts.app')

@section('title', 'Create Treasury Transaction')

@section('content')

<section class="content-header">
    <h1>
        Create Treasury Transaction
    </h1>
</section>

<section class="content">

    <form method="POST"
          action="{{ route('finance.treasury.store') }}">

        @csrf

        <div class="box box-primary">

            <div class="box-header with-border">

                <h3 class="box-title">
                    Treasury Transaction Details
                </h3>

            </div>

            <div class="box-body">

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Transaction Date *</label>

                            <input type="date"
                                   name="transaction_date"
                                   class="form-control"
                                   value="{{ date('Y-m-d') }}"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Branch</label>

                            <select name="location_id"
                                    class="form-control">

                                <option value="">
                                    All Branches
                                </option>

                                @foreach($locations as $id => $name)

                                    <option value="{{ $id }}">
                                        {{ $name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Account</label>

                            <select name="account_id"
                                    class="form-control">

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

                            <label>Treasury Type *</label>

                            <select name="treasury_type"
                                    class="form-control"
                                    required>

                                <option value="cash_in">
                                    Cash In
                                </option>

                                <option value="cash_out">
                                    Cash Out
                                </option>

                                <option value="bank_deposit">
                                    Bank Deposit
                                </option>

                                <option value="bank_withdrawal">
                                    Bank Withdrawal
                                </option>

                                <option value="transfer">
                                    Transfer
                                </option>

                                <option value="adjustment">
                                    Adjustment
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Amount *</label>

                            <input type="number"
                                   step="0.01"
                                   name="amount"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Reference Type</label>

                            <input type="text"
                                   name="reference_type"
                                   class="form-control"
                                   placeholder="Example: journal_entries">

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Reference ID</label>

                            <input type="number"
                                   name="reference_id"
                                   class="form-control">

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-12">

                        <div class="form-group">

                            <label>Description</label>

                            <textarea name="description"
                                      class="form-control"
                                      rows="5"></textarea>

                        </div>

                    </div>

                </div>

            </div>

            <div class="box-footer">

                <button type="submit"
                        class="btn btn-primary">

                    <i class="fa fa-save"></i>
                    Save Treasury Transaction

                </button>

            </div>

        </div>

    </form>

</section>

@endsection