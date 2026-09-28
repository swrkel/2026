<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewSettlementLine extends Model
{
    protected $table = 'disnew_settlement_lines';
    protected $fillable = ['business_id','settlement_id','line_type','reference_type','reference_id','description','qty','amount'];
}
