<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title','HR Manager')</title>
    <link rel="stylesheet" href="{{ asset('modules/hrmanager/css/hr-manager.css') }}">
</head>
<body class="hr-body">
<div class="hr-shell">
    <aside class="hr-sidebar">
        <div class="hr-brand">HR Manager</div>
        <a href="{{ route('hr.employees.index') }}" class="hr-nav">Employees</a>
        <a href="{{ route('hr.setup.index') }}" class="hr-nav">HR Setup</a>
        <a href="#" class="hr-nav disabled">Attendance</a>
        <a href="#" class="hr-nav disabled">Face Kiosk</a>
        <a href="#" class="hr-nav disabled">Leave</a>
        <a href="#" class="hr-nav disabled">Payroll</a>
    </aside>
    <main class="hr-main">
        @yield('content')
    </main>
</div>
<script src="{{ asset('modules/hrmanager/js/hr-employees.js') }}"></script>
<script src="{{ asset('modules/hrmanager/js/hr-setup.js') }}"></script>
</body>
</html>
