<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\PurchaseLine.
 *
 * Business behaviour is unchanged; this wrapper lets Distribution code
 * depend on module-owned model namespaces while the physical table remains
 * the existing ERP table.
 */
class PurchaseLine extends \App\PurchaseLine
{
}
