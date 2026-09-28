<?php

namespace Modules\PetroDirect\Exceptions;

use RuntimeException;

/**
 * MA-002: thrown when a settlement's transaction date would move into, or
 * out of, a financial year that is already closed.
 *
 * Distinct from a generic failure so the caller can BLOCK the save and show
 * the operator a specific message, rather than logging and carrying on.
 */
class SettlementPeriodClosedException extends RuntimeException
{
}
