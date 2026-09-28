<?php

namespace Modules\Chequer\Entities;

/**
 * Transitional Finance-owned entity bridge.
 * Keeps Finance code referencing Modules\Finance while preserving existing behaviour.
 * This bridge can later be replaced with a full Finance model after UAT.
 */
class BusinessLocation extends \App\BusinessLocation
{
}
