<?php

namespace Modules\Distribution\Entities;

/**
 * Distribution-local wrapper for App\ContactLedger.
 * Keeps Distribution posting code referencing module-local entities while
 * preserving the existing contact_ledgers table behaviour.
 */
class DistributionContactLedger extends \Modules\Distribution\Entities\Core\ContactLedger
{
}
