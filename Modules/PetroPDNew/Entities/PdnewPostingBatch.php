<?php

namespace Modules\PetroPDNew\Entities;

class PdnewPostingBatch extends PdnewBaseModel
{
    protected $table = 'pdnew_posting_batches';
    protected $casts = [
        'posting_date' => 'date',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function lines()
    {
        return $this->hasMany(PdnewPostingLine::class, 'posting_batch_id');
    }
}
