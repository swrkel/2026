<?php

namespace Modules\Suppliers\Entities;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{

    /*
     * MA-002: activity logging restored to match core.
     *
     * This model writes to `contacts`, the same table as App\Contact,
     * which logs every create and update. This copy did not - so a record
     * created or edited through the Suppliers module left NO AUDIT TRAIL, while the
     * identical record touched through core's screens did.
     *
     * That is the same defect already found and fixed on ContactLedger,
     * Journal and FixedAsset earlier in this project: a module copy of a core
     * model that silently lost its parent's behaviour.
     *
     * $logName is deliberately 'Contact' - the SAME value core uses, not a
     * module-specific one. The activity screens filter by log name, so a
     * different value here would file these entries somewhere nobody looks.
     *
     * Applied only because this model genuinely creates rows. The read-only
     * copies on the same tables - Airline/AirlineCustomers,
     * Suppliers/SupplierContact, Suppliers/SupplierContactGroup - are
     * deliberately left alone: logging a model that never writes adds risk and
     * produces nothing.
     */
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logName = 'Contact';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }


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

    public function scopeForCurrentBusiness(Builder $query): Builder
    {
        return $query->where('business_id', \Modules\Suppliers\Utils\SupplierContextUtil::businessId());
    }

    public function scopeSupplierOnly(Builder $query): Builder
    {
        return $query->whereIn('type', ['supplier', 'both']);
    }

    public function scopeForLocation(Builder $query, ?int $locationId): Builder
    {
        if (!empty($locationId)) {
            $query->where(function ($q) use ($locationId) {
                $q->where('location_id', $locationId)
                  ->orWhereNull('location_id');
            });
        }
        return $query;
    }
}
