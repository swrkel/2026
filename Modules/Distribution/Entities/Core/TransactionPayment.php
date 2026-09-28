<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\TransactionPayment.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class TransactionPayment extends \App\TransactionPayment
{
}
