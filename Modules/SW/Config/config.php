<?php

return [
    'name' => 'SW',

    /*
     | Accounts belong to the Finance module in this system, not to core. Any
     | account, account group or transaction this module touches goes through
     | Finance, so that pointing Finance elsewhere moves SW with it.
    */
    'finance_account_model' => \Modules\Finance\Entities\Account::class,
    'finance_account_group_model' => \Modules\Finance\Entities\AccountGroup::class,

    /*
     | Settlement numbering: SW-<location>-<n>, n running per LOCATION.
     | Neither part is zero padded.
    */
    'settlement_prefix' => 'SW',
];
