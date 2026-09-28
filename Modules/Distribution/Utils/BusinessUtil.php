<?php

namespace Modules\Distribution\Utils;

/**
 * Distribution-owned compatibility utility for App\Utils\BusinessUtil.
 * Kept inside the module so Distribution constructors can type-hint module
 * utilities first while legacy ERP behaviour remains unchanged.
 */
class BusinessUtil extends \App\Utils\BusinessUtil
{
}
