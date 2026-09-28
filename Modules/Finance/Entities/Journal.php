<?php

namespace Modules\Finance\Entities;

/**
 * Finance Journal.
 *
 * MA-002: NOW EXTENDS THE CORE MODEL - THIS RESTORES THE AUDIT TRAIL.
 *
 * This was a standalone copy declaring only $table and $guarded. Core's
 * App\Journal uses Spatie's LogsActivity with
 *     $logName = 'Journals'
 * so every create, update and delete is recorded in the activity log.
 *
 * The Finance copy did NOT use that trait. Modules/Finance/Http/Controllers/
 * Journal/JournalController.php creates, updates and deletes journal entries
 * through THIS class - 2 creates, 4 updates and 5 deletes - so none of those
 * were being logged.
 *
 * For an accounting system that matters: journal entries are the manual
 * adjustments most likely to be questioned later, and they had no audit
 * record when posted from the Finance module.
 *
 * Both classes point at the same `journals` table, so the data was always
 * correct - what was missing was the audit trail.
 *
 * Core has no explicit $table; Laravel's convention gives `journals`, exactly
 * what the copy named. $guarded is inherited.
 */
class Journal extends \App\Journal
{
    //
}
