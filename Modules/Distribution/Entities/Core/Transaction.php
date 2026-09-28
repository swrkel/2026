<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\Transaction.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class Transaction extends \App\Transaction
{
}
