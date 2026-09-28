<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP Variation table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\Variation imports while preserving the existing database schema and behaviour.
 */
class SettlementSwVariation extends \App\Variation
{
}
