<?php

namespace Modules\PetroPDNew\Entities;

class PdnewPostingLine extends PdnewBaseModel
{
    protected $table = 'pdnew_posting_lines';
    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(PdnewPostingBatch::class, 'posting_batch_id');
    }
}
