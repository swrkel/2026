@php
    $hrActive = request()->segment(1) === 'hr' || request()->segment(1) === 'hr-manager';

    $hrCanSee = true;
    try {
        if (auth()->check()) {
            $user = auth()->user();
            $hrCanSee = $user->can('superadmin')
                || $user->can('hr.dashboard.view')
                || $user->can('hr.employee.view')
                || $user->can('hr.attendance.view')
                || $user->can('hr.leave.view')
                || $user->can('hr.payroll.view')
                || $user->can('hr.employee_records.view')
                || $user->can('hr.setup.view');
        }
    } catch (\Throwable $e) {
        $hrCanSee = true;
    }

    $hrDashboardUrl = \Illuminate\Support\Facades\Route::has('hr.dashboard') ? route('hr.dashboard') : url('/hr/dashboard');
    $hrEmployeesUrl = \Illuminate\Support\Facades\Route::has('hr.employees.index') ? route('hr.employees.index') : url('/hr/employees');
    $hrAttendanceUrl = \Illuminate\Support\Facades\Route::has('hr.attendance.index') ? route('hr.attendance.index') : url('/hr/attendance');
    $hrFaceUrl = \Illuminate\Support\Facades\Route::has('hr.face.dashboard') ? route('hr.face.dashboard') : url('/hr/face');
    $hrLeaveUrl = \Illuminate\Support\Facades\Route::has('hr.leave.dashboard') ? route('hr.leave.dashboard') : url('/hr/leave');
    $hrPayrollUrl = \Illuminate\Support\Facades\Route::has('hr.payroll.dashboard') ? route('hr.payroll.dashboard') : url('/hr/payroll');
    $hrRecordsUrl = \Illuminate\Support\Facades\Route::has('hr.employee_records.dashboard') ? route('hr.employee_records.dashboard') : url('/hr/employee-records');
    $hrSetupUrl = \Illuminate\Support\Facades\Route::has('hr.setup.index') ? route('hr.setup.index') : url('/hr/setup');
@endphp

@if($hrCanSee)
    <li class="nav-item {{ $hrActive ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#hr-manager-menu"
           aria-expanded="{{ $hrActive ? 'true' : 'false' }}" aria-controls="hr-manager-menu">
            <i class="fa fa-users"></i>
            <span>HR Manager</span>
        </a>
        <div id="hr-manager-menu" class="collapse {{ $hrActive ? 'show' : '' }}"
             aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">HR Manager:</h6>
                <a class="collapse-item {{ $hrActive && in_array(request()->segment(2), [null, '', 'dashboard']) ? 'active' : '' }}" href="{{ $hrDashboardUrl }}">
                    <i class="fa fa-dashboard"></i> Dashboard
                </a>
                <a class="collapse-item {{ request()->segment(2) === 'employees' ? 'active' : '' }}" href="{{ $hrEmployeesUrl }}">
                    <i class="fa fa-id-card"></i> Employees
                </a>
                <a class="collapse-item {{ request()->segment(2) === 'attendance' ? 'active' : '' }}" href="{{ $hrAttendanceUrl }}">
                    <i class="fa fa-clock-o"></i> Attendance
                </a>
                <a class="collapse-item {{ request()->segment(2) === 'face' ? 'active' : '' }}" href="{{ $hrFaceUrl }}">
                    <i class="fa fa-camera"></i> Face Attendance
                </a>
                <a class="collapse-item {{ request()->segment(2) === 'leave' ? 'active' : '' }}" href="{{ $hrLeaveUrl }}">
                    <i class="fa fa-calendar-check-o"></i> Leave Management
                </a>
                <a class="collapse-item {{ request()->segment(2) === 'payroll' ? 'active' : '' }}" href="{{ $hrPayrollUrl }}">
                    <i class="fa fa-money"></i> Payroll
                </a>
                <a class="collapse-item {{ request()->segment(2) === 'employee-records' ? 'active' : '' }}" href="{{ $hrRecordsUrl }}">
                    <i class="fa fa-folder-open"></i> Employee Records
                </a>
                <a class="collapse-item {{ request()->segment(2) === 'setup' ? 'active' : '' }}" href="{{ $hrSetupUrl }}">
                    <i class="fa fa-cogs"></i> HR Setup
                </a>
            </div>
        </div>
    </li>
@endif
