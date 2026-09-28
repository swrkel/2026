<?php

return [
    'name' => 'BankingCheque',
    'table_prefix' => 'bkg_cheque_',
    'date_format' => 'Y-m-d',
    'currency_precision' => 4,
    'leaf_statuses' => ['available','issued','presented','cleared','stopped','returned','cancelled','spoiled'],
];
