<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\User.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class User extends \App\User
{
}
