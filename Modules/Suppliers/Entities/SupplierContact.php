<?php

namespace Modules\Suppliers\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SupplierContact extends Model
{

    /*
     * MA-002: SoftDeletes restored.
     *
     * This model is a standalone copy on the shared `contacts` table. Core's
     * App\Contact uses SoftDeletes; this copy did not. Two consequences:
     *
     *   1. ->delete() through this class was a HARD delete. The row was
     *      physically removed, while the rest of the system expects a
     *      deleted contacts row to be recoverable and excluded by scope.
     *   2. Queries through this class INCLUDED soft-deleted rows, so records
     *      deleted elsewhere could still appear in listings.
     *
     * SAFE TO APPLY NOW: `contacts` currently contains ZERO rows with
     * deleted_at set in the tenant database I was given, so adding the trait
     * cannot change the result of any existing query. It only prevents
     * future hard deletes and future stale reads.
     *
     * account_transactions was DELIBERATELY EXCLUDED from this change - it
     * has 204 soft-deleted rows, so adding the trait there WOULD change
     * results and needs to be agreed and checked first.
     */
    use SoftDeletes;
    protected $table = 'contacts';
    protected $guarded = ['id'];

    public function scopeSupplierOnly(Builder $query): Builder
    {
        return $query->whereIn('type', ['supplier', 'both']);
    }

    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    public static function dropdown(int $businessId, bool $prependNone = true)
    {
        $items = static::query()
            ->forBusiness($businessId)
            ->supplierOnly()
            ->where('active', 1)
            ->select('id', DB::raw("IF(contact_id IS NULL OR contact_id='', name, CONCAT(name, ' - ', COALESCE(supplier_business_name, ''), '(', contact_id, ')')) AS supplier"))
            ->pluck('supplier', 'id');

        return $prependNone ? $items->prepend(__('lang_v1.none'), '') : $items;
    }
}
