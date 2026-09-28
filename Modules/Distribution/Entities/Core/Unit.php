<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\Unit.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class Unit extends \App\Unit
{
}
