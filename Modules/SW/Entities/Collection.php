<?php

namespace Modules\SW\Entities;

class Collection extends SWModel
{
    protected $table = 'sw_collections';

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class, 'settlement_id');
    }

    /**
     * The account this collection posts to.
     *
     * Resolved through the FINANCE module, not core: accounts belong to Finance
     * in this system. The class is read from config so that if Finance moves,
     * this moves with it rather than silently pointing at App\Account.
     */
    public function account()
    {
        return $this->belongsTo(config('sw.finance_account_model'), 'account_id');
    }
}
