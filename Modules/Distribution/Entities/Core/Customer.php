<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\Customer.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class Customer extends \App\Customer
{
}
