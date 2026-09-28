<?php

namespace Modules\MPCS\Entities;

/**
 * IS2009: this file previously declared class Mpcs15FormDetails - a byte-for-byte
 * duplicate of Entities/Mpcs15FormDetails.php.
 *
 * Two consequences, both real:
 *
 *   1. PSR-4 requires the class name to match the file name, so composer's
 *      autoloader SKIPPED it. That is the warning already visible on this
 *      server's deployment output:
 *          "Class Modules\MPCS\Entities\Mpcs15FormDetails located in
 *           ./Modules/MPCS/Entities/Mpcs15FormSettings.php does not comply with
 *           psr-4 autoloading standard ... Skipping."
 *
 *   2. Nothing declared Mpcs15FormSettings at all, yet
 *      Form15SettingsController and FormF15Header::fsetting() both reference it.
 *      Any code path reaching those would fail with "Class not found".
 *
 * Declaring the expected class here fixes both. It extends Mpcs15FormDetails
 * rather than redefining the mapping, so there is exactly ONE definition of the
 * table and its relations; this name remains available to the existing callers.
 */
class Mpcs15FormSettings extends Mpcs15FormDetails
{
}
