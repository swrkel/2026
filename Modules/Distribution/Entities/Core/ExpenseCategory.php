<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\ExpenseCategory.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class ExpenseCategory extends \App\ExpenseCategory
{
}
