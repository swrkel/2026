<?php

return [
    'application_statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'disbursed' => 'Disbursed',
        'cancelled' => 'Cancelled',
    ],

    'loan_statuses' => [
        'draft' => 'Draft',
        'approved' => 'Approved',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'settled' => 'Settled',
        'written_off' => 'Written Off',
        'closed' => 'Closed',
    ],

    'allowed_application_transitions' => [
        'draft' => ['submitted', 'under_review', 'rejected', 'cancelled'],
        'submitted' => ['under_review', 'approved', 'rejected', 'cancelled'],
        'under_review' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['disbursed', 'cancelled'],
        'rejected' => ['draft'],
    ],
];
