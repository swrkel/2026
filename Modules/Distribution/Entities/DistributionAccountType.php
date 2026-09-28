<?php

namespace Modules\Distribution\Entities;

/**
 * Distribution-local wrapper for App\AccountType.
 *
 * This allows Distribution code to depend on module entities first.
 * Table/relationship behaviour remains unchanged because it extends the
 * original ERP model.
 */
class DistributionAccountType extends \Modules\Distribution\Entities\Core\AccountType
{
}
