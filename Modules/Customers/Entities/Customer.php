<?php

namespace Modules\Customers\Entities;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{

    /*
     * MA-002: activity logging restored to match core.
     *
     * This model writes to `contacts`, the same table as App\Contact,
     * which logs every create and update. This copy did not - so a record
     * created or edited through the Customers module left NO AUDIT TRAIL, while the
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

    use SoftDeletes;

    protected $table = 'contacts';

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'integer',
        'business_id' => 'integer',
        'credit_limit' => 'decimal:4',
    ];

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeCustomersOnly($query)
    {
        return $query->whereIn('type', ['customer', 'both']);
    }

    public function scopeActiveOnly($query)
    {
        return $query->where('active', 1);
    }
}
