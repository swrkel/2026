<?php

namespace Modules\Distribution\Entities\Integration;

/**
 * Distribution-owned integration wrapper for Superadmin subscription lookup.
 * Keeps Distribution code independent from direct Superadmin class references.
 */
class Subscription extends \Modules\Superadmin\Entities\Subscription
{
}
