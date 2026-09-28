<?php

return [
    'module' => 'HRManager',
    'title' => 'HR Manager',
    'icon' => 'fa fa-users',
    'route' => 'hr.dashboard',
    'permission' => 'hr.dashboard.view',
    'children' => [
        ['title' => 'Dashboard', 'route' => 'hr.dashboard', 'permission' => 'hr.dashboard.view'],
        ['title' => 'Employees', 'route' => 'hr.employees.index', 'permission' => 'hr.employee.view'],
        ['title' => 'Attendance', 'route' => 'hr.attendance.index', 'permission' => 'hr.attendance.view'],
        ['title' => 'Face Attendance', 'route' => 'hr.face.dashboard', 'permission' => 'hr.face.view'],
        ['title' => 'Leave Management', 'route' => 'hr.leave.dashboard', 'permission' => 'hr.leave.view'],
        ['title' => 'Payroll', 'route' => 'hr.payroll.dashboard', 'permission' => 'hr.payroll.view'],
        ['title' => 'Employee Records', 'route' => 'hr.employee_records.dashboard', 'permission' => 'hr.employee_records.view'],
        ['title' => 'HR Setup', 'route' => 'hr.setup.index', 'permission' => 'hr.setup.view'],
    ],
];
