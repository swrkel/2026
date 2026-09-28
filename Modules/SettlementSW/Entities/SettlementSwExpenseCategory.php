<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP ExpenseCategory table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\ExpenseCategory imports while preserving the existing database schema and behaviour.
 */
class SettlementSwExpenseCategory extends \App\ExpenseCategory
{
}
