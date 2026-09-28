<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\AccountType.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class AccountType extends \App\AccountType
{
}
