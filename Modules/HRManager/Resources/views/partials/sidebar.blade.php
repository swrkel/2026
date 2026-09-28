{{-- HR Manager standalone module sidebar --}}
@php
    $request = request();
@endphp

<li class="treeview {{ $request->is('hr-manager*') || $request->is('hrmanager*') ? 'active active-sub menu-open' : '' }}" style="background:#2563eb;">
    <a href="#">
        <i class="fa fa-users"></i>
        <span class="title">HR Manager</span>
        <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
        </span>
    </a>
    <ul class="treeview-menu" style="{{ $request->is('hr-manager*') || $request->is('hrmanager*') ? 'display:block;' : '' }}">
        <li class="{{ $request->is('hr-manager/dashboard') ? 'active' : '' }}">
            <a href="{{ url('/hr-manager/dashboard') }}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a>
        </li>
        <li class="{{ $request->is('hr-manager/employees*') ? 'active' : '' }}">
            <a href="{{ url('/hr-manager/employees') }}"><i class="fa fa-id-card"></i> <span>Employees</span></a>
        </li>
        <li class="{{ $request->is('hr-manager/attendance*') ? 'active' : '' }}">
            <a href="{{ url('/hr-manager/attendance') }}"><i class="fa fa-clock-o"></i> <span>Attendance</span></a>
        </li>
        <li class="{{ $request->is('hr-manager/face*') ? 'active' : '' }}">
            <a href="{{ url('/hr-manager/face') }}"><i class="fa fa-camera"></i> <span>Face Attendance</span></a>
        </li>
        <li class="{{ $request->is('hr-manager/leave*') ? 'active' : '' }}">
            <a href="{{ url('/hr-manager/leave') }}"><i class="fa fa-calendar-check-o"></i> <span>Leave Management</span></a>
        </li>
        <li class="{{ $request->is('hr-manager/payroll*') ? 'active' : '' }}">
            <a href="{{ url('/hr-manager/payroll') }}"><i class="fa fa-money"></i> <span>Payroll</span></a>
        </li>
        <li class="{{ $request->is('hr-manager/employee-records*') ? 'active' : '' }}">
            <a href="{{ url('/hr-manager/employee-records') }}"><i class="fa fa-folder-open"></i> <span>Employee Records</span></a>
        </li>
        <li class="{{ $request->is('hr-manager/setup*') ? 'active' : '' }}">
            <a href="{{ url('/hr-manager/setup') }}"><i class="fa fa-cogs"></i> <span>HR Setup</span></a>
        </li>
    </ul>
</li>
