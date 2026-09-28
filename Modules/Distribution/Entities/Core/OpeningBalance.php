<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\OpeningBalance.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class OpeningBalance extends \App\OpeningBalance
{
}
