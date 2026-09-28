@extends('layouts.app')

@section('content')
    <style>
        .container {
            width: 520px;
            margin: auto;
            background: #fff;
            padding: 8px;
        }
    </style>
    <div class="container">
        <h4>Renew Auto Subscription</h4>

        <div class="card p-3">

            <h5>Subscription Details</h5>

            <p><strong>Period:</strong> {{ $period }} Day(s)</p>
            <p><strong>Amount:</strong> {{ number_format($amount, 2) }}</p>

            <hr>

            <form method="POST" action="{{ route('myauto.subscription.pay.online') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button class="btn btn-success btn-block">
                    Online
                </button>
            </form>

            <hr>

            <form method="POST" action="{{ route('myauto.subscription.pay.offline') }}">
                @csrf
                <button class="btn btn-secondary btn-block">
                    Offline
                </button>
            </form>

            <div class="mt-3 p-3 bg-light rounded">
                <h6>Bank Details</h6>
                <p><strong>Bank:</strong> {{ $bank['bank_name'] }}</p>
                <p><strong>Branch:</strong> {{ $bank['branch'] }}</p>
                <p><strong>Account Name:</strong> {{ $bank['account_name'] }}</p>
                <p><strong>Account No:</strong> {{ $bank['account_no'] }}</p>
                <p><strong>SWIFT:</strong> {{ $bank['swift'] }}</p>
            </div>

        </div>

    </div>
@endsection
