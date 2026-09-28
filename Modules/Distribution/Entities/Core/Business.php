<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\Business.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class Business extends \App\Business
{
}
