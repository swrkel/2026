<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\System.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class System extends \App\System
{
}
