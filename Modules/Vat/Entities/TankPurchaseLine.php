<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: Vat-owned model for the SHARED `tank_purchase_lines` table.
 *
 * Vat previously referenced Modules\Petro\Entities\TankPurchaseLine from a
 * blade partial. The DATA stays shared - same table, same rows - but the
 * CODE dependency is gone.
 *
 * READ-ONLY BY CONTRACT. The single Vat usage is a where() lookup in
 * Resources/views/purchase/partials/edit_unload_tank_row.blade.php.
 * LogsActivity is deliberately omitted: audit logging belongs to the module
 * that WRITES this table, and adding it to a read model would risk
 * duplicate or misleading audit entries.
 */
class TankPurchaseLine extends Model
{
    protected $table = 'tank_purchase_lines';

    protected $guarded = ['id'];
}
