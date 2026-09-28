<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\Category.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class Category extends \App\Category
{
}
