@extends('customers::portal.layout')
@section('title', 'Distribution Dealer Login')
@section('body')
<section class="dd-login-shell">
    <div class="dd-login-card">
        <div class="dd-login-head">
            <h2 style="margin:0;font-weight:900;">Distribution Dealer</h2>
            <p style="margin:8px 0 0;opacity:.95;">Enter your 4 digit customer passcode</p>
        </div>
        <form method="POST" action="{{ route('customers.portal.login.post') }}">
            @csrf
            <div class="dd-login-body">
                @if(session('status'))
                    <div class="alert {{ !empty(session('status')['success']) ? 'alert-success' : 'alert-danger' }}">
                        {{ session('status')['msg'] ?? '' }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <div class="form-group">
                    <label>Customer Passcode</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-addon"><i class="fa fa-key"></i></span>
                        <input type="password" name="passcode" class="form-control dd-passcode" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" required autofocus placeholder="••••">
                    </div>
                </div>
                <button type="submit" class="dd-btn dd-btn-primary" style="width:100%;height:52px;font-size:17px;">
                    <i class="fa fa-sign-in"></i> Login
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
