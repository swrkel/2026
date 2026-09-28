<?php

namespace Modules\Suppliers\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPurchaseLine extends Model
{

    /*
     * MA-002: SoftDeletes restored.
     *
     * This model is a standalone copy on the shared `purchase_lines` table. Core's
     * App\PurchaseLine uses SoftDeletes; this copy did not. Two consequences:
     *
     *   1. ->delete() through this class was a HARD delete. The row was
     *      physically removed, while the rest of the system expects a
     *      deleted purchase_lines row to be recoverable and excluded by scope.
     *   2. Queries through this class INCLUDED soft-deleted rows, so records
     *      deleted elsewhere could still appear in listings.
     *
     * SAFE TO APPLY NOW: `purchase_lines` currently contains ZERO rows with
     * deleted_at set in the tenant database I was given, so adding the trait
     * cannot change the result of any existing query. It only prevents
     * future hard deletes and future stale reads.
     *
     * account_transactions was DELIBERATELY EXCLUDED from this change - it
     * has 204 soft-deleted rows, so adding the trait there WOULD change
     * results and needs to be agreed and checked first.
     */
    use SoftDeletes;
    protected $table = 'purchase_lines';
    protected $guarded = ['id'];
}
