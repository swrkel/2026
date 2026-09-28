<?php

namespace Modules\Distribution\Entities;

/**
 * Distribution-local wrapper for App\SalesAgent.
 *
 * This allows Distribution code to depend on module entities first.
 * Table/relationship behaviour remains unchanged because it extends the
 * original ERP model.
 */
class DistributionSalesAgent extends \Modules\Distribution\Entities\Core\SalesAgent
{
}
