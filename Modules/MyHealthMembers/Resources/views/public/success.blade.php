@extends('layouts.app')

@section('title', 'My Health Registration Completed')

@section('content')
<section class="content-header no-print">
    <h1>My Health Registration Completed</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-success">
                <div class="box-header with-border no-print">
                    <h3 class="box-title">Registration Successful</h3>
                </div>
                <div class="box-body" id="myhealth-registration-success-print-area">
                    <div class="text-center" style="margin-bottom:20px;">
                        <h2 style="margin-top:0;">My Health Member Registration</h2>
                        <p class="text-muted">Please keep these details secure and confidential.</p>
                    </div>

                    @if(!empty($message))
                        <div class="alert alert-success no-print">{{ $message }}</div>
                    @endif

                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th style="width:35%;">Member Name</th>
                                <td>{{ $member->name }}</td>
                            </tr>
                            <tr>
                                <th>My Health Member Code</th>
                                <td><strong style="font-size:20px;letter-spacing:1px;">{{ $member->myhealth_code }}</strong></td>
                            </tr>
                            <tr>
                                <th>Temporary Passcode</th>
                                <td><strong style="font-size:26px;letter-spacing:1px;color:#d9534f;">{{ $passcode }}</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-warning" style="font-size:16px;line-height:1.6;">
                        <strong>Important:</strong> This passcode is shown only once. Please print it, save it as PDF, or write it down now. Do not share it except with an authorized doctor, pharmacy, laboratory, or permitted business when you want to give access to your My Health records.
                    </div>
                </div>
                <div class="box-footer text-center no-print">
                    <button type="button" class="btn btn-default" onclick="window.print();">
                        <i class="fa fa-print"></i> Print / Save as PDF
                    </button>
                    <a href="{{ url('/login') }}" class="btn btn-primary">Continue to Login</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
