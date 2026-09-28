@php
    $settings = DB::table('site_settings')->where('id', 1)->select('*')->first();
    $locked_screen = null;
    $auth_user = null;
    $has_assets = file_exists(public_path('img/loading.gif'));
    $has_assets = $has_assets && file_exists(public_path('img/loading.gif'));
    $is_locked = false;
    if (auth()->check()) {
        $auth_user = auth()->user();
        $locked_screen = DB::table('users')->where('id', $auth_user->id)->select('lock_screen')->first();
        $is_locked = $locked_screen && $locked_screen->lock_screen === 1;
    }
    $file = $settings ? base_path(url($settings->uploadFileLLogo)) : null;
@endphp
<div id="lock_screen_div" class="animated fadeInDown" style="@if (!$is_locked) display:none; @endif ">
    <div class="col-md-12 lock-content">
        <div class="row">
            <div class="lock_logo">
                @if ($settings && !empty($settings->uploadFileLLogo) && file_exists($file))
                    <img src="{{ url($settings->uploadFileLLogo) }}" class="img-rounded">
                @else
                    {{ config('app.name', 'ultimatePOS') }}
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-md-offset-4 text-center">
                @if ($auth_user)
                    <h1>{{ $auth_user->username }}</h1>
                    <h3>{{ $auth_user->email }}</h3>
                @endif
                <p class="locked_p">Locked</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-md-offset-4 text-center">
                <div class="row">
                    <p class="hide_p">&nbsp;</p>
                    <div class="input-group" style="width: 90%; float: left;">
                        <span class="input-group-addon">
                            <i class="fa fa-lock"></i>
                        </span>
                        {!! Form::password('lock_password', [
                            'class' => 'form-control',
                            'id' => 'lock_password',
                            'placeholder' => 'Password',
                        ]) !!}
                    </div>
                    @if ($has_assets)
                        <img src="{{ asset('img/loading.gif') }}" alt="loading" class="loading_gif"
                            style="display:none;">
                    @endif
                    <button class="btn btn-danger" id="check_password_btn" style="border-radius: 0px"><i
                            class="fa fa-arrow-right"></i></button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-md-offset-4 text-center">
                @if ($auth_user)
                    <a href="{{ route('logout') }}" class="not_super_admin">Not <b>@lang('lang_v1.super_admin', ['super_admin' => 'Super Admin'])</b></a>
                @endif
            </div>
        </div>
    </div>

</div>
