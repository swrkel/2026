<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Separation step 4 (see document 5-18, "Suggested sequence").
 *
 * A VAT-owned model over the SHARED `customer_references` table.
 *
 *
 * WHY THIS EXISTS
 *
 * Six controllers imported App\CustomerReference. This table belongs to the
 * contact/customer side of the system, so it is directly exposed to the Contact
 * module being retired in favour of Customers - the same reasoning that made
 * SharedContact the first thing converted under step 3.
 *
 * The TABLE is not going anywhere; Customers uses it too. What has to go is the
 * dependency on the core CLASS. Category B in the inventory: shared data
 * wrapped, never duplicated.
 *
 *
 * WHY THIS ONE IS SAFE TO WRAP WITHOUT THE CORE SOURCE
 *
 * The module only ever calls plain Eloquent on it - where(), find() and
 * updateOrCreate(), seventeen calls in total. There are no static helpers whose
 * behaviour would have to be reproduced.
 *
 * That is the opposite of App\Contact, which carried customersDropdown() and
 * two siblings. Writing those from inference produced four separate defects
 * before the real source was available, so a model is only wrapped blind when
 * the module's usage is confined to the framework's own methods.
 *
 * App\ContactLedger is deliberately NOT converted alongside this. It is called
 * as ContactLedger::createContactLedger() in eight places - a static helper of
 * unknown behaviour - and guessing at ledger-writing logic is exactly the risk
 * that is not worth taking. It needs the core file first.
 *
 *
 * HOW IT IS ADOPTED
 *
 * One line per controller:
 *
 *     use App\CustomerReference;
 *   → use Modules\Vat\Entities\SharedCustomerReference as CustomerReference;
 *
 * Every existing call then resolves here with no call-site edits.
 */
class SharedCustomerReference extends Model
{
    protected $table = 'customer_references';

    protected $guarded = ['id'];

    /*
     * No SoftDeletes: customer_references has no deleted_at column. Adding the
     * trait would make every query filter on a column that does not exist.
     */

    /**
     * Scope to a business. The table is shared, so a query that forgets this
     * reads another business's references.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('customer_references.business_id', $businessId);
    }
}
