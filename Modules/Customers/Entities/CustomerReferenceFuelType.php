<?php

namespace Modules\Customers\Entities;

/**
 * Task 8046 - constants for the Fuel Type field on a customer reference.
 *
 * The fuel type list is built from PRODUCT sub-categories under the Fuel
 * product category (see CustomerReferenceFuelTypeService). On top of whatever
 * that lookup returns, the spec requires a permanent system default option,
 * "Not Known", which must be selectable even on a tenant that has no Fuel
 * category configured at all.
 *
 * That default is represented by a NULL fuel_type_id rather than by a real row,
 * so it cannot be renamed, deactivated or deleted by a user editing product
 * categories.
 */
class CustomerReferenceFuelType
{
    /**
     * Value posted by the form for the system default option.
     *
     * Deliberately a non-numeric string. A product category id can never
     * collide with it, so the two cases stay unambiguous in request data.
     */
    public const NOT_KNOWN_VALUE = 'not_known';

    public const NOT_KNOWN_LABEL = 'Not Known';

    /**
     * Is the posted fuel type the system default?
     */
    public static function isNotKnown($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return (string) $value === self::NOT_KNOWN_VALUE;
    }
}
