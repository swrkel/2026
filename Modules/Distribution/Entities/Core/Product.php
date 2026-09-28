<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\Product.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class Product extends \App\Product
{
}
