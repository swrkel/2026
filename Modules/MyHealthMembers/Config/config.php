<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Central DB connection
    |--------------------------------------------------------------------------
    | Set MYHEALTH_DB_CONNECTION in .env if your central database connection
    | name is different. If not set, the default connection will be used.
    */
    'central_connection' => env('MYHEALTH_DB_CONNECTION', config('database.default')),

    /*
    |--------------------------------------------------------------------------
    | OTP / passcode settings
    |--------------------------------------------------------------------------
    */
    'passcode_expiry_minutes' => env('MYHEALTH_PASSCODE_EXPIRY_MINUTES', 10),
    'member_code_prefix' => env('MYHEALTH_MEMBER_CODE_PREFIX', 'MHM'),
    'mobile_token_expiry_days' => env('MYHEALTH_MOBILE_TOKEN_EXPIRY_DAYS', 30),
];
