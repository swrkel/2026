<?php

namespace Modules\Finance\Entities;

/**
 * Finance FixedAsset.
 *
 * MA-002: NOW EXTENDS THE CORE MODEL - THIS RESTORES THE AUDIT TRAIL.
 *
 * This was a standalone copy that declared only $table, $guarded and $casts.
 * Core's App\FixedAsset uses Spatie's LogsActivity with
 *     $logName = 'FixedAssets'
 * so every create, update and delete is recorded in the activity log.
 *
 * The Finance copy did NOT use that trait. Modules/Finance/Http/Controllers/
 * FixedAssets/FixedAssetController.php creates, updates and deletes fixed
 * assets through THIS class, so none of those changes were being logged.
 * Fixed assets could be created or removed with no audit record.
 *
 * Both classes point at the same `fixed_assets` table, so the data was always
 * correct - what was missing was the audit trail.
 *
 * Core has no explicit $table; Laravel's convention gives `fixed_assets`,
 * which is exactly what the copy named. $guarded is inherited too.
 *
 * $casts IS KEPT. Core does not define these, and removing them would change
 * how date_of_operation and amount are returned - which could alter
 * formatting and comparisons in the Finance screens. Behaviour there stays
 * exactly as it is today.
 */
class FixedAsset extends \App\FixedAsset
{
    /**
     * Retained from the previous Finance-local model - core does not define
     * these, and Finance's screens rely on them.
     */
    protected $casts = [
        'date_of_operation' => 'datetime',
        'amount' => 'decimal:4',
    ];
}
