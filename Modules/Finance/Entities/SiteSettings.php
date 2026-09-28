<?php

namespace Modules\Finance\Entities;

/**
 * Transitional Finance-owned entity bridge.
 * Keeps Finance code referencing Modules\Finance while preserving existing behaviour.
 * This bridge can later be replaced with a full Finance model after UAT.
 */
class SiteSettings extends \App\SiteSettings
{
}
