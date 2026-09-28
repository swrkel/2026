<?php

namespace Modules\Finance\Entities;

/**
 * Finance ContactLedger.
 *
 * MA-002: NOW EXTENDS THE CORE MODEL. THIS FIXES A LIVE BUG.
 *
 * This class used to be a standalone 15-line copy:
 *
 *     class ContactLedger extends Model
 *     {
 *         use SoftDeletes;
 *         protected $table = 'contact_ledgers';
 *         protected $guarded = ['id'];
 *     }
 *
 * Core's App\ContactLedger is 88 lines and registers model events in boot():
 *
 *     static::created(...)  -> ContactController::clearOutstandingCache($business_id)
 *     static::updated(...)  -> same
 *     static::deleted(...)  -> same
 *
 * The Finance copy had NO boot method, so none of those hooks existed.
 *
 * WHY THAT MATTERED
 * Modules/Finance/Http/Controllers/Journal/JournalController.php calls
 * ContactLedger::create() at lines 560 and 870 - through THIS class. So every
 * journal entry posted from Finance wrote a contact ledger row WITHOUT
 * clearing the customer/supplier outstanding cache. The outstanding figures
 * shown afterwards could be stale until something else happened to clear it.
 *
 * Both classes point at the same `contact_ledgers` table, so the data was
 * always written correctly - it was the cache invalidation that was missing.
 *
 * Extending core restores the boot hooks, the activity logging and
 * createContactLedger(). It is also consistent with the other Finance
 * entities that already subclass core: Contact, Transaction, User,
 * BusinessLocation, TransactionPayment, Customer, System and SiteSettings.
 *
 * $table, SoftDeletes and $guarded are all inherited, so nothing is redefined
 * here - redefining them is what allowed the two to drift apart.
 */
class ContactLedger extends \App\ContactLedger
{
    //
}
