<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP Category table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\Category imports while preserving the existing database schema and behaviour.
 */
class SettlementSwCategory extends \App\Category
{
}
