<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if (isset($setting) && $setting)
        {!! SEOMeta::generate() !!}
        {!! OpenGraph::generate() !!}
        {!! Twitter::generate() !!}
        {!! JsonLd::generate() !!}
    @endif

    @if (isset($title) && $title)
        <title>{{ $title }}</title>
    @endif
    @php
        $favicon = config('feedback.settings.favicon');
        $file_url = '';
        $web_business = \App\Business::find(session()->get('business.id'));
        if (!empty($web_business) && !empty($web_business->favicon_path)) {
            $file_url = \Storage::url($web_business->favicon_path);
        } else {
            $site_settings = \App\SiteSettings::where('id', 1)->first();
           
            // if (!empty($site_settings) && !empty($site_settings->uploadFileLLogo)) {
            //     if (file_exists(public_path($site_settings->uploadFileLLogo))) {
            //         $file_url = url($site_settings->uploadFileLLogo);
            //     } else {
            //         if (file_exists(public_path(str_replace('public/', '', $site_settings->uploadFileLLogo)))) {
            //             $file_url = url($site_settings->uploadFileLLogo);
            //         } else {
            //             $file_url = asset('img/setting/icon-1730547010.png');
            //         }
            //     }
            // }else
            if  (!empty($favicon) && file_exists(public_path($favicon))) {
                $file_url = asset($favicon);
            } else {
                $file_url = asset('img/setting/icon-1730547010.png');
            }
        }
    @endphp
    @if (!empty($file_url))
        <link rel="shortcut icon" type="image/x-icon" href="{{ $file_url }}" />
    @endif

    <!-- CSS files -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/frontend/css/tailwind.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/frontend/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/css/sweetalert.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/css/fontawesome.min.css') }}" />
    <!-- daterangepicker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <!-- Bootstrap fileinput CSS -->
    <link rel="stylesheet" href="{{ asset('plugins/bootstrap-fileinput/fileinput.min.css') }}">
    <script type="text/javascript" src="{{ asset('public/js/alpine.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('public/js/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('public/frontend/js/main.js') }}"></script>
    <script type="text/javascript" src="{{ asset('public/js/sweetalert.min.js') }}"></script>
    @php
        $user_language = session()->get('user.language', config('app.locale'));
        $lang_file = public_path('js/lang/' . $user_language . '.js');
        
        // Date format variables
        $business_date_format = session('business.date_format', config('constants.default_date_format', 'd/m/Y'));
        $datepicker_date_format = str_replace('d', 'dd', $business_date_format);
        $datepicker_date_format = str_replace('m', 'mm', $datepicker_date_format);
        $datepicker_date_format = str_replace('Y', 'yyyy', $datepicker_date_format);
        
        $moment_date_format = str_replace('d', 'DD', $business_date_format);
        $moment_date_format = str_replace('m', 'MM', $moment_date_format);
        $moment_date_format = str_replace('Y', 'YYYY', $moment_date_format);
        
        $business_time_format = session('business.time_format', 24);
        $moment_time_format = 'HH:mm';
        if($business_time_format == 12){
            $moment_time_format = 'hh:mm A';
        }
        
        $default_datatable_page_entries = 25;
    @endphp
    @if(file_exists($lang_file))
    <script type="text/javascript" src="{{ asset('js/lang/' . $user_language . '.js') }}"></script>
    @else
    <script type="text/javascript" src="{{ asset('js/lang/en.js') }}"></script>
    @endif
    
    <!-- Moment.js -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <!-- daterangepicker -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <!-- jQuery UI (for datepicker) -->
    <script type="text/javascript" src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <!-- Bootstrap datepicker -->
    <script type="text/javascript" src="{{ asset('AdminLTE/plugins/datepicker/bootstrap-datepicker.min.js') }}"></script>
    <!-- Bootstrap fileinput -->
    <script type="text/javascript" src="{{ asset('plugins/bootstrap-fileinput/fileinput.min.js') }}"></script>
    <!-- DataTables -->
    <script type="text/javascript" src="{{ asset('AdminLTE/plugins/DataTables/datatables.min.js') }}"></script>
    <!-- jQuery Validator -->
    <script type="text/javascript" src="{{ asset('js/jquery-validation-1.16.0/dist/jquery.validate.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/jquery-validation-1.16.0/dist/additional-methods.min.js') }}"></script>
    <!-- iCheck -->
    <script type="text/javascript" src="{{ asset('AdminLTE/plugins/iCheck/icheck.min.js') }}"></script>
    <!-- TinyMCE -->
    <script type="text/javascript" src="{{ asset('plugins/tinymce/tinymce.min.js') }}"></script>
    <!-- Bootstrap datetimepicker -->
    <script type="text/javascript" src="{{ asset('plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js') }}"></script>
    
    <script type="text/javascript">
        // Define base_path for JavaScript
        var base_path = "{{ url('/') }}";
        // Define Pace as a no-op if not loaded (for login page)
        if (typeof Pace === 'undefined') {
            var Pace = {
                restart: function() {},
                start: function() {},
                stop: function() {}
            };
        }
        
        // Date format variables
        var datepicker_date_format = "{{ $datepicker_date_format ?? 'mm/dd/yyyy' }}";
        var moment_date_format = "{{ $moment_date_format ?? 'YYYY-MM-DD' }}";
        var moment_time_format = "{{ $moment_time_format ?? 'HH:mm' }}";
        var __default_datatable_page_entries = {{ $default_datatable_page_entries }};
        
        // App locale and non-UTF8 languages (required by common.js)
        var app_locale = "{{ session()->get('user.language', config('app.locale')) }}";
        var non_utf8_languages = [
            @foreach(config('constants.non_utf8_languages', []) as $const)
            "{{ $const }}",
            @endforeach
        ];
        
        // Define financial_year for dateRangeSettings (required by common.js)
        @php
            $financial_year_start = Session::get('financial_year.start');
            $financial_year_end = Session::get('financial_year.end');
        @endphp
        var financial_year = {
            start: @if(!empty($financial_year_start)) moment('{{ $financial_year_start }}') @else moment().startOf('year') @endif,
            end: @if(!empty($financial_year_end)) moment('{{ $financial_year_end }}') @else moment().endOf('year') @endif
        };
        
        // Define TinyMCE as a no-op if not loaded (for login page)
        // This prevents errors in common.js when TinyMCE is not needed
        // Define immediately before common.js loads
        if (typeof window.tinymce === 'undefined') {
            window.tinymce = {
                overrideDefaults: function() {},
                init: function() {},
                get: function() { return null; }
            };
        }
    </script>
    <script type="text/javascript" src="{{ asset('js/common.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/functions.js') }}"></script>
    @if(auth()->check() && request()->segment(1) != 'login')
    @include('layouts.partials.global-location-dropdown-config')
    <script type="text/javascript" src="{{ asset('js/global-location-dropdown.js?v=' . (file_exists(public_path('js/global-location-dropdown.js')) ? filemtime(public_path('js/global-location-dropdown.js')) : time())) }}"></script>
    @endif
    <script type="text/javascript" src="{{ asset('js/app.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/erp-global-date-range-v3.css') }}">
    <script type="text/javascript" src="{{ asset('js/erp-global-date-range-config-v3.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/erp-global-date-range-v3.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/payment.js') }}"></script>
    @if (isset($setting) && $setting)
        <!-- Global site tag (gtag.js) - Google Analytics -->

        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());
        </script>
    @endif
</head>

<body class="antialiased bg-body text-body font-body"
    dir="{{ App::isLocale('ar') || App::isLocale('ur') || App::isLocale('he') ? 'rtl' : 'ltr' }}">
   
    <div>
        @if (isset($nav) && $nav)
            @include('web.nav')
        @endif
        @yield('content')
    </div>

    @if (isset($footer) && $footer)
        @include('web.footer')
    @endif

    <!-- Smooth Scroll -->
    <script type="text/javascript" src="{{ asset('public/js/smooth-scroll.polyfills.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('public/frontend/js/footer.js') }}"></script>

    @if (isset($setting) && $setting)
    @endif
    @stack('custom-js')
</body>

</html>
