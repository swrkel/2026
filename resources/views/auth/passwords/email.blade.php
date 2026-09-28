@php
    $bg_showing_type = $bg_showing_type ?? null;
@endphp

@extends('layouts.auth-login')

@section('content')
    <style>
        .forgot-password-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .forgot-password-card {
            width: 100%;
            max-width: 420px;
            padding: 28px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .forgot-password-card h3 {
            margin: 0 0 18px;
            font-size: 22px;
            font-weight: 600;
            text-align: center;
        }

        .forgot-password-card .form-group {
            margin-bottom: 16px;
        }

        .forgot-password-card .btn {
            border-radius: 4px;
            font-weight: 600;
        }
    </style>

    <div class="forgot-password-page">
        <div class="forgot-password-card">
            <h3>Forgot your password?</h3>

            @if (session('status'))
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }}">
                    {{ session('status.msg') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                {{ csrf_field() }}

                <div class="form-group has-feedback {{ $errors->has('email') ? ' has-error' : '' }}">
                    <label for="email">Please enter the registered Email</label>
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}"
                        required autofocus placeholder="@lang('lang_v1.email_address')">
                    <span class="fa fa-envelope form-control-feedback"></span>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-flat">
                        Send New Password
                    </button>
                </div>

                @if (session('sms_recharge_url'))
                    <div class="form-group">
                        <a href="{{ session('sms_recharge_url') }}" class="btn btn-warning btn-block btn-flat">
                            Recharge Now
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection
