<?php

return [
    'name' => 'IdentityAccess',
    'display_name' => 'Identity Access',
    'session_lifetime_minutes' => 120,
    'max_failed_attempts' => 5,
    'lockout_minutes' => 15,
    'otp_expiry_minutes' => 5,
    'otp_attempts' => 3,
    'portals' => [
        'erp' => 'ERP User',
        'my_health_member' => 'My Health Member',
        'doctor' => 'Doctor',
        'customer' => 'Customer',
        'supplier' => 'Supplier',
        'employee' => 'Employee',
    ],
];
