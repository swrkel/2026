<?php

/*
| SW module permissions, one per tab.
|
| Per tab rather than per page: the cash screens are handled by different
| people, and a manager may need Daily Cash Status without being able to record
| a card payment.
|
| The keys are stable - the role screen stores them, so renaming one silently
| revokes it from every role that had it.
*/

return [
    'module' => 'sw',
    'label' => 'SW Module',

    'pages' => [
        [
            'key' => 'sw',
            'label' => 'SW Module',
            'permissions' => [

                // --- SW Operators ---------------------------------------
                ['key' => 'sw.operators.view', 'label' => 'SW Operators: Pump Operators'],
                ['key' => 'sw.operators.edit', 'label' => 'SW Operators: add and edit operators'],
                ['key' => 'sw.excess_shortage.view', 'label' => 'SW Operators: Excess / Shortage Payments'],
                ['key' => 'sw.excess_shortage.edit', 'label' => 'SW Operators: recover shortage, pay excess'],

                // --- SW Payments ----------------------------------------
                ['key' => 'sw.daily_cash.view', 'label' => 'SW Payments: Daily Cash'],
                ['key' => 'sw.daily_cash.edit', 'label' => 'SW Payments: record and edit Daily Cash'],
                ['key' => 'sw.daily_credit_sales.view', 'label' => 'SW Payments: Daily Credit Sales'],
                ['key' => 'sw.daily_credit_sales.edit', 'label' => 'SW Payments: record and edit Credit Sales'],
                ['key' => 'sw.daily_cards.view', 'label' => 'SW Payments: Daily Cards'],
                ['key' => 'sw.daily_cards.edit', 'label' => 'SW Payments: record and edit Daily Cards'],
                ['key' => 'sw.daily_shortage_excess.view', 'label' => 'SW Payments: Daily Shortage Excess'],
                ['key' => 'sw.daily_shortage_excess.edit', 'label' => 'SW Payments: record and edit Shortage Excess'],
                ['key' => 'sw.daily_cheques.view', 'label' => 'SW Payments: Daily Cheques'],
                ['key' => 'sw.daily_cheques.edit', 'label' => 'SW Payments: record and edit Daily Cheques'],

                // --- List SW Shifts / Shift Operations ------------------
                ['key' => 'sw.daily_cash_status.view', 'label' => 'SW Shift Operations: Daily Cash Status'],
                ['key' => 'sw.daily_shift.view', 'label' => 'List SW Shifts / Shift Operations: view shifts'],
                ['key' => 'sw.daily_shift.edit', 'label' => 'SW Shift Operations: open, assign, close shifts'],

                /*
                 | Collection Summary appears on BOTH pages - deliberately, so
                 | someone checking shifts can see the figures without moving to
                 | SW Payments. One permission covers both places; two would
                 | mean the same tab could be allowed here and refused there.
                */
                ['key' => 'sw.collection_summary.view', 'label' => 'Collection Summary (both pages)'],

                /*
                 | 8045: the permitted user may change a service price.
                 |
                 | Its own permission because it is a price, not a quantity - a
                 | changed price alters what a customer was charged, and that
                 | should not follow from being able to record income.
                */
                ['key' => 'sw.other_income.edit_price', 'label' => 'Other Income: edit the price'],

                // --- SW Logs --------------------------------------------
                // --- SW Settlement --------------------------------------
                ['key' => 'sw.settlement.view', 'label' => 'SW Settlement: list and view'],
                ['key' => 'sw.settlement.create', 'label' => 'SW Settlement: add settlement'],

                // --- SW Logs --------------------------------------------
                ['key' => 'sw.logs.view', 'label' => 'SW Logs'],
            ],
        ],
    ],
];
