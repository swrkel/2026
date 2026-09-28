<?php

return [
    'name' => 'Pumper Dashboard',

    // Normal production requests should not write detailed row/request traces.
    'debug_logging' => env('PUMPER_DASHBOARD_DEBUG_LOGGING', false),
];


