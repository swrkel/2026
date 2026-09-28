<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\Account.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class Account extends \App\Account
{
}
