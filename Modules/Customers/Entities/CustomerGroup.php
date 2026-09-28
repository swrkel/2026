<?php

namespace Modules\Customers\Entities;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{

    /*
     * MA-002: activity logging restored to match core.
     *
     * This model writes to `contact_groups`, the same table as App\ContactGroup,
     * which logs every create and update. This copy did not - so a record
     * created or edited through the Customers module left NO AUDIT TRAIL, while the
     * identical record touched through core's screens did.
     *
     * That is the same defect already found and fixed on ContactLedger,
     * Journal and FixedAsset earlier in this project: a module copy of a core
     * model that silently lost its parent's behaviour.
     *
     * $logName is deliberately 'Customer Group' - the SAME value core uses, not a
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

    protected static $logName = 'Customer Group';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*']);
    }

    protected $table = 'contact_groups';

    protected $guarded = ['id'];
}
