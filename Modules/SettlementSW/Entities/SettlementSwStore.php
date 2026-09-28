<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP Store table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\Store imports while preserving the existing database schema and behaviour.
 */
class SettlementSwStore extends \App\Store
{
}
