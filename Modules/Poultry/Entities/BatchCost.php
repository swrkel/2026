<?php

namespace Modules\Poultry\Entities;

/**
 * The module's cost subsidiary ledger - authoritative for cost per bird and
 * cost per egg analytics, while Finance stays authoritative for money. Each
 * row optionally carries the account_transactions.id it posted.
 */
class BatchCost extends PoultryModel
{
    protected $table = 'poultry_batch_costs';

    protected $dates = ['cost_date'];

    protected $casts = ['is_posted' => 'boolean'];

    public const TYPES = [
        'doc'         => 'Day old chicks',
        'feed'        => 'Feed',
        'medication'  => 'Medication',
        'vaccination' => 'Vaccination',
        'labour'      => 'Labour',
        'utilities'   => 'Utilities',
        'litter'      => 'Litter',
        'transport'   => 'Transport',
        'overhead'    => 'Overhead',
        'other'       => 'Other',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function scopeOfSource($query, $sourceType, $sourceId)
    {
        return $query->where('source_type', $sourceType)->where('source_id', $sourceId);
    }
}
