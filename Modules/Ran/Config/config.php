<?php

return [
    'name' => 'Ran',
    'route_prefix' => 'ran',
    'table_prefix' => 'ran_',
    'currency_decimals' => 4,
    'weight_decimals' => 3,
    'quantity_decimals' => 4,
    'share_link_expiry_days' => 30,
    'allow_negative_stock' => false,
    'communication_source' => 'ran',
    'default_accounts' => [
        'raw_material' => 'Raw Material Account',
        'finished_goods' => 'Finished Goods Account',
        'sales_income' => 'Sales Income',
        'cost_of_goods' => 'COGS',
        'accounts_receivable' => 'Accounts Receivable',
        'accounts_payable' => 'Accounts Payable',
        'wastage_expense' => 'Wastage Expense',
        'cash' => 'Cash',
    ],
];
