<!doctype html>

<html lang="{{ config('app.locale') }}">

<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible"
          content="IE=edge">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>

        @yield('title')

    </title>

    <!-- ===================================================== -->
    <!-- GOOGLE FONT -->
    <!-- ===================================================== -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <!-- ===================================================== -->
    <!-- BOOTSTRAP -->
    <!-- ===================================================== -->

    <link rel="stylesheet"
          href="{{ asset('bootstrap/css/bootstrap.min.css?v='.$asset_v) }}">

    <!-- ===================================================== -->
    <!-- ENTERPRISE LANDING FRAMEWORK -->
    <!-- ===================================================== -->

    <style>

        /* =====================================================
           GLOBAL
        ===================================================== */

        body {

            font-family: 'Inter', sans-serif !important;

            min-height: 100vh;

            background:
                linear-gradient(
                    135deg,
                    #0f172a 0%,
                    #111827 35%,
                    #1e293b 100%
                );

            color: #ffffff;

            overflow-x: hidden;

            position: relative;
        }

        body:before {

            content: '';

            position: fixed;

            inset: 0;

            background:
                radial-gradient(
                    circle at top right,
                    rgba(37,99,235,0.22),
                    transparent 40%
                ),
                radial-gradient(
                    circle at bottom left,
                    rgba(6,182,212,0.18),
                    transparent 40%
                );

            z-index: 0;
        }

        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar-default {

            background:
                rgba(255,255,255,0.06) !important;

            backdrop-filter: blur(12px);

            border: none !important;

            border-radius: 18px;

            margin: 20px;

            padding: 10px 14px;

            box-shadow:
                0 10px 30px rgba(0,0,0,0.18);

            position: relative;

            z-index: 10;
        }

        .navbar-default .navbar-brand {

            color: #ffffff !important;

            font-size: 24px;

            font-weight: 800;

            letter-spacing: 1px;
        }

        .navbar-default .navbar-nav > li > a {

            color: rgba(255,255,255,0.84) !important;

            font-weight: 600;

            font-size: 14px;

            border-radius: 12px;

            padding: 10px 16px !important;

            transition: all 0.22s ease;
        }

        .navbar-default .navbar-nav > li > a:hover {

            background: rgba(255,255,255,0.10);

            color: #ffffff !important;

            transform: translateY(-1px);
        }

        /* =====================================================
           MAIN CONTENT
        ===================================================== */

        .container {

            position: relative;

            z-index: 2;
        }

        .content {

            background:
                rgba(255,255,255,0.08);

            backdrop-filter: blur(14px);

            border-radius: 28px;

            padding: 40px;

            margin-top: 20px;

            margin-bottom: 40px;

            border: 1px solid rgba(255,255,255,0.08);

            box-shadow:
                0 20px 50px rgba(0,0,0,0.20);
        }

        /* =====================================================
           TYPOGRAPHY
        ===================================================== */

        h1,
        h2,
        h3,
        h4,
        h5 {

            font-weight: 800;
        }

        p {

            color: rgba(255,255,255,0.82);

            line-height: 1.8;
        }

        /* =====================================================
           ENTERPRISE CARDS
        ===================================================== */

        .card,
        .box,
        .small-box {

            background:
                rgba(255,255,255,0.08) !important;

            border-radius: 20px !important;

            border: 1px solid rgba(255,255,255,0.08) !important;

            box-shadow:
                0 12px 35px rgba(0,0,0,0.16);

            overflow: hidden;

            transition: all 0.25s ease;
        }

        .card:hover,
        .box:hover,
        .small-box:hover {

            transform: translateY(-2px);

            box-shadow:
                0 20px 40px rgba(0,0,0,0.24);
        }

        /* =====================================================
           TABLES
        ===================================================== */

        .table {

            background:
                rgba(255,255,255,0.05);

            border-radius: 16px;

            overflow: hidden;
        }

        .table thead {

            background:
                rgba(255,255,255,0.08);
        }

        .table thead th {

            border: none !important;

            text-transform: uppercase;

            font-size: 12px;

            color: rgba(255,255,255,0.72);

            letter-spacing: 0.5px;
        }

        .table td {

            border-color:
                rgba(255,255,255,0.06) !important;

            color: rgba(255,255,255,0.86);
        }

        /* =====================================================
           BUTTONS
        ===================================================== */

        .btn {

            border-radius: 14px !important;

            border: none !important;

            font-weight: 700;

            transition: all 0.22s ease;
        }

        .btn:hover {

            transform: translateY(-1px);

            box-shadow:
                0 10px 20px rgba(0,0,0,0.18);
        }

        .btn-primary {

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #06b6d4
                ) !important;
        }

        /* =====================================================
           MOBILE TABLES
        ===================================================== */

        @media only screen and (max-width: 800px) {

            #other_account_table_wrapper table,
            #other_account_table_wrapper thead,
            #other_account_table_wrapper tbody,
            #other_account_table_wrapper th,
            #other_account_table_wrapper td,
            #other_account_table_wrapper tr {

                display: block;
            }

            #other_account_table_wrapper thead tr {

                position: absolute;

                top: -9999px;

                left: -9999px;
            }

            #other_account_table_wrapper tr {

                border:
                    1px solid rgba(255,255,255,0.08);

                margin-bottom: 12px;

                border-radius: 14px;

                overflow: hidden;
            }

            #other_account_table_wrapper td {

                border: none;

                border-bottom:
                    1px solid rgba(255,255,255,0.06);

                position: relative;

                padding-left: 50%;

                white-space: normal;

                text-align: left;
            }

            #other_account_table_wrapper td:before {

                position: absolute;

                top: 6px;

                left: 6px;

                width: 45%;

                padding-right: 10px;

                white-space: nowrap;

                text-align: left;

                font-weight: bold;

                color: rgba(255,255,255,0.72);
            }

            #other_account_table_wrapper td:before {

                content: attr(data-title);
            }

            .content {

                padding: 22px;
            }

            .navbar-default {

                margin: 10px;
            }
        }

    </style>

</head>

<body>

    @include('layouts.partials.home_header')

    <div class="container">

        <div class="content">

            @yield('content')

        </div>

    </div>

    @include('layouts.partials.javascripts')

    <script src="{{ asset('plugins/jquery.steps/jquery.steps.min.js?v=' . $asset_v) }}"></script>

    <script src="{{ asset('js/login.js?v=' . $asset_v) }}"></script>

    <script src="{{ asset('AdminLTE/plugins/iCheck/icheck.min.js?v=' . $asset_v) }}"></script>

    @yield('javascript')

    @include('layouts.partials.operation-guard-js')

</body>

</html>
