<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP AccountType table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\AccountType imports while preserving the existing database schema and behaviour.
 */
class SettlementSwAccountType extends \App\AccountType
{
}
