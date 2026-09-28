@extends('layouts.auth-login')

@section('css')
<style>
.rcm-login-page{min-height:100vh;background:linear-gradient(135deg,#f5f9f6 0%,#eef6ff 100%);display:flex;align-items:center;justify-content:center;padding:28px;font-family:Arial,sans-serif}.rcm-login-card{width:min(980px,100%);display:grid;grid-template-columns:1.05fr .95fr;background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 24px 70px rgba(15,23,42,.16)}.rcm-login-copy{padding:54px;background:linear-gradient(155deg,#1f6b45 0%,#34885f 100%);color:#fff;position:relative;overflow:hidden}.rcm-login-copy:after{content:"";position:absolute;width:280px;height:280px;border:34px solid rgba(255,255,255,.08);border-radius:50%;right:-110px;bottom:-120px}.rcm-login-mark{width:72px;height:72px;border-radius:20px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:34px;margin-bottom:30px}.rcm-login-copy h1{font-size:34px;font-weight:800;margin:0 0 10px}.rcm-login-copy h2{font-size:21px;font-weight:700;margin:0 0 22px;color:#e8fff1}.rcm-login-copy p{font-size:15px;line-height:1.7;color:#e4f3e9;max-width:420px}.rcm-login-panel{padding:50px}.rcm-login-panel h3{margin:0 0 8px;font-size:27px;font-weight:800;color:#1e293b}.rcm-login-panel .sub{color:#64748b;margin-bottom:30px}.rcm-login-field label{display:block;font-weight:700;color:#334155;margin-bottom:8px}.rcm-pass-wrap{position:relative}.rcm-pass-wrap i{position:absolute;left:16px;top:15px;color:#64748b}.rcm-passcode{width:100%;height:52px;border:1px solid #cbd5e1;border-radius:12px;padding:0 16px 0 44px;font-size:22px;letter-spacing:8px;outline:none}.rcm-passcode:focus{border-color:#2d7d55;box-shadow:0 0 0 3px rgba(45,125,85,.12)}.rcm-login-btn{width:100%;height:52px;margin-top:18px;border:0;border-radius:12px;background:linear-gradient(135deg,#188a50,#2ca96d);color:#fff;font-size:16px;font-weight:800;cursor:pointer}.rcm-login-back{display:block;text-align:center;margin-top:18px;color:#64748b}.rcm-login-alert{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:10px;padding:12px 14px;margin-bottom:18px}.rcm-login-business{display:inline-block;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.14);font-weight:700}@media(max-width:768px){.rcm-login-card{grid-template-columns:1fr}.rcm-login-copy{padding:34px}.rcm-login-panel{padding:34px}}
</style>
@endsection

@section('content')
<div class="rcm-login-page">
    <div class="rcm-login-card">
        <section class="rcm-login-copy">
            <div class="rcm-login-mark"><i class="fa fa-industry"></i></div>
            <h1>Rice Mill Dashboard</h1>
            <h2>{{ $business->name }}</h2>
            <p>Secure operational access for permitted Rice Mill users. Enter the same user passcode assigned in User Management New.</p>
            <span class="rcm-login-business"><i class="fa fa-building-o"></i> {{ $business->company_number }}</span>
        </section>
        <section class="rcm-login-panel">
            <h3>Dashboard Login</h3>
            <div class="sub">Enter your user passcode.</div>

            @if($errors->any())
                <div class="rcm-login-alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('rice-mill-dashboard.login.store') }}" autocomplete="off">
                @csrf
                <input type="hidden" name="business_id" value="{{ $business->id }}">
                <input type="hidden" name="company_number" value="{{ $business->company_number }}">
                <div class="rcm-login-field">
                    <label for="rcm_dashboard_passcode">Passcode</label>
                    <div class="rcm-pass-wrap">
                        <i class="fa fa-lock"></i>
                        <input id="rcm_dashboard_passcode" name="passcode" type="password" class="rcm-passcode" maxlength="191" autofocus required>
                    </div>
                </div>
                <button class="rcm-login-btn" type="submit"><i class="fa fa-sign-in"></i> Enter Rice Mill Dashboard</button>
            </form>
            <a class="rcm-login-back" href="{{ url('/login') }}"><i class="fa fa-angle-left"></i> Back to Login</a>
        </section>
    </div>
</div>
@endsection
