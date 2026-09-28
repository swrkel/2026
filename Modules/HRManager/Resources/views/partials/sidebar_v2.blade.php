{{-- HR Manager standalone module sidebar - Bootstrap/SB Admin style --}}
@php $request = request(); @endphp
<li class="nav-item {{ $request->is('hr-manager*') || $request->is('hrmanager*') ? 'active' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#hrmanager-module-menu"
        aria-expanded="{{ $request->is('hr-manager*') ? 'true' : 'false' }}" aria-controls="hrmanager-module-menu">
        <i class="fa fa-users"></i>
        <span>HR Manager</span>
    </a>
    <div id="hrmanager-module-menu" class="collapse {{ $request->is('hr-manager*') || $request->is('hrmanager*') ? 'show' : '' }}">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">HR Manager:</h6>
            <a class="collapse-item {{ $request->is('hr-manager/dashboard') ? 'active' : '' }}" href="{{ url('/hr-manager/dashboard') }}">Dashboard</a>
            <a class="collapse-item {{ $request->is('hr-manager/employees*') ? 'active' : '' }}" href="{{ url('/hr-manager/employees') }}">Employees</a>
            <a class="collapse-item {{ $request->is('hr-manager/attendance*') ? 'active' : '' }}" href="{{ url('/hr-manager/attendance') }}">Attendance</a>
            <a class="collapse-item {{ $request->is('hr-manager/face*') ? 'active' : '' }}" href="{{ url('/hr-manager/face') }}">Face Attendance</a>
            <a class="collapse-item {{ $request->is('hr-manager/leave*') ? 'active' : '' }}" href="{{ url('/hr-manager/leave') }}">Leave Management</a>
            <a class="collapse-item {{ $request->is('hr-manager/payroll*') ? 'active' : '' }}" href="{{ url('/hr-manager/payroll') }}">Payroll</a>
            <a class="collapse-item {{ $request->is('hr-manager/employee-records*') ? 'active' : '' }}" href="{{ url('/hr-manager/employee-records') }}">Employee Records</a>
            <a class="collapse-item {{ $request->is('hr-manager/setup*') ? 'active' : '' }}" href="{{ url('/hr-manager/setup') }}">HR Setup</a>
        </div>
    </div>
</li>
