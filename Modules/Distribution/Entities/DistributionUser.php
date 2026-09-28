<?php

namespace Modules\Distribution\Entities;

/**
 * Distribution-local wrapper for App\User.
 *
 * This allows Distribution code to depend on module entities first.
 * Table/relationship behaviour remains unchanged because it extends the
 * original ERP model.
 */
class DistributionUser extends \Modules\Distribution\Entities\Core\User
{
}
