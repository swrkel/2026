<?php

return [
    'name' => 'AutoService',
    'route_prefix' => 'auto-service',
    'shared_customers' => true,
    'shared_products' => true,
    'default_reminder_days_before' => 7,

    /*
     * Central vehicle registry connection.
     * Configure AUTO_SERVICE_CENTRAL_DB_CONNECTION to the central database connection
     * so all tenant/business databases can read the same vehicle ownership/service history.
     */
    'central_connection' => env('AUTO_SERVICE_CENTRAL_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
    'central_vehicle_otp_minutes' => env('AUTO_SERVICE_CENTRAL_VEHICLE_OTP_MINUTES', 10),
];
