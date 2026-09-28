<?php

return [
    'name' => 'Tailoring',
    'route_prefix' => 'tailoring',
    'date_format' => 'Y-m-d',
    'default_currency_precision' => 4,
    'status_flow' => [
        'order_received', 'measurement', 'fabric_issue', 'cutting', 'stitching',
        'trial_fitting', 'alteration', 'finishing', 'ironing', 'quality_check',
        'packing', 'ready_for_delivery', 'delivered'
    ],

    'editions' => [
        'basic' => ['customers','measurements','measurement_profiles','orders','job_cards','payments','delivery','basic_reports','settings'],
        'professional' => ['fabric','inventory','production','tailors','departments','trials','alterations','suppliers','expense_reports'],
        'enterprise' => ['production_planning','capacity_planning','qr_job_cards','barcode_tracking','quality_control','packing','dispatch','executive_dashboard','factory_analytics'],
    ],
];
