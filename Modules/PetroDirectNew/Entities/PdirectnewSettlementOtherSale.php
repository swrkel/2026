<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewSettlementOtherSale extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_settlement_other_sales';
    public function lines() { return $this->hasMany(PdirectnewSettlementOtherSaleLine::class, 'other_sale_id'); }
}
