<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\BusinessLocation.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class BusinessLocation extends \App\BusinessLocation
{
}
