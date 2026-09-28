<?php
namespace Modules\RiceMill\Models;

class PaddyPurchase extends BaseRiceMillModel
{
    protected $table = 'rcm_paddy_purchases';

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_tax_percent' => 'decimal:4',
        'purchase_tax_amount' => 'decimal:4',
    ];

    public function lines(){ return $this->hasMany(PaddyPurchaseLine::class, 'purchase_id'); }
}
